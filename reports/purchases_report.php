<?php
    ini_set('display_errors', 1);
error_reporting(E_ALL);
// public/reports/purchases_report.php
require_once __DIR__ . '/../includes/header.php';
require_role(['admin','staff','auditor']);
$pdo = getDB();

/* ----------------- Config ----------------- */
define('PAGE_SIZE', 12);

/* ---------------- filters ---------------- */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$cat  = $_GET['category_id'] ?? '';
$product_id = $_GET['product_id'] ?? '';
$gran = $_GET['granularity'] ?? 'day'; // 'day' | 'week' | 'month'
$page = max(1, (int)($_GET['page'] ?? 1));
$ajax_bucket = $_GET['ajax_bucket'] ?? null;

$validGrans = ['day','week','month'];
if (!in_array($gran, $validGrans, true)) $gran = 'day';

/* ---------------- helpers (no duplicate global helpers) ---------------- */
function bucket_key($dtString, $gran) {
  $ts = strtotime($dtString);
  if ($gran === 'day')   return date('Y-m-d', $ts);
  if ($gran === 'week')  return date('o-\WW', $ts);
  return date('Y-m', $ts);
}
function bucket_label($key, $gran) {
  if ($gran === 'day') return date('M j, Y', strtotime($key));
  if ($gran === 'week') {
    if (preg_match('/^(\d+)-W(\d+)$/', $key, $m)) return "Week {$m[2]}, {$m[1]}";
    return $key;
  }
  return date('M Y', strtotime($key . '-01'));
}
function bucket_range_from_key($key, $gran) {
  if ($gran === 'day') {
    $start = $key; $end = $key;
  } elseif ($gran === 'week') {
    if (!preg_match('/^(\d+)-W(\d+)$/', $key, $m)) return [null,null];
    $y = (int)$m[1]; $w = (int)$m[2];
    $d = new DateTime(); $d->setISODate($y, $w);
    $start = $d->format('Y-m-d'); $d->modify('+6 days'); $end = $d->format('Y-m-d');
  } else {
    if (!preg_match('/^\d{4}-\d{2}$/', $key)) return [null,null];
    $start = $key . '-01'; $end = date('Y-m-t', strtotime($start));
  }
  return [$start, $end];
}

/* ---------------- fetch purchases + lines for period ---------------- */
/* We'll fetch transactions in the date range, then fetch items only for those txs.
   If category or product filter is set, items query will apply them. Transactions without matching
   items will be ignored during aggregation.
*/

$params = [$from, $to];

$txSql = "
  SELECT t.id, t.type, t.date, t.created_at, t.extra_discount_type, t.extra_discount_value
  FROM transactions t
  WHERE t.type = 'purchase'
    AND t.date BETWEEN ? AND ?
  ORDER BY t.date, t.id
";
$txSt = $pdo->prepare($txSql);
$txSt->execute($params);
$txRows = $txSt->fetchAll(PDO::FETCH_ASSOC);

$txIds = array_map(function($r){ return (int)$r['id']; }, $txRows);
$items = [];
if ($txIds) {
  $in  = implode(',', array_fill(0, count($txIds), '?'));

  // base items query (joins for supplier/location)
  $itSql = "
    SELECT ti.transaction_id, ti.qty, ti.unit_price, ti.discount_type, ti.discount_value, ti.cost_at_sale,
           p.id AS product_id, p.code, p.name, p.category_id,
           s.id AS supplier_id, s.name AS supplier_name, l.name AS location_name
    FROM transaction_items ti
    JOIN products p ON p.id = ti.product_id
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    LEFT JOIN locations l ON l.id = p.location_id
    WHERE ti.transaction_id IN ($in)
  ";

  // optional filters applied to the items query (not the tx list)
  $itemParams = $txIds;
  if ($cat !== '') {
    $itSql .= " AND p.category_id = ? ";
    $itemParams[] = (int)$cat;
  }
  if ($product_id !== '') {
    $itSql .= " AND p.id = ? ";
    $itemParams[] = (int)$product_id;
  }

  $itSql .= " ORDER BY ti.transaction_id, ti.id";
  $itSt = $pdo->prepare($itSql);
  $itSt->execute($itemParams);
  foreach ($itSt as $r) $items[] = $r;
}
$itemsByTx = [];
foreach ($items as $it) $itemsByTx[(int)$it['transaction_id']][] = $it;

/* ---------------- aggregate into buckets ---------------- */
$byBucket = [];
function ensure_bucket(&$byBucket, $key) {
  if (!isset($byBucket[$key])) {
    $byBucket[$key] = [
      'qty' => 0, 'total' => 0.0, 'avg_unit' => 0.0,
      'tx_count' => 0, 'items' => 0
    ];
  }
}

foreach ($txRows as $t) {
  $tid = (int)$t['id'];
  $rows = $itemsByTx[$tid] ?? [];
  if (!$rows) continue; // skip txs that have no matching items under current filters

  $bucketKey = bucket_key($t['created_at'] ?: $t['date'], $gran);
  ensure_bucket($byBucket, $bucketKey);

  $txQty = 0; $txTotal = 0.0; $lineCount = 0;
  foreach ($rows as $li) {
    $qty = (float)$li['qty'];
    $unit = (float)$li['unit_price'];
    $lineTotal = max(0.0, $qty * $unit);
    $txQty += $qty;
    $txTotal += $lineTotal;
    $lineCount++;
  }
  $byBucket[$bucketKey]['qty'] += $txQty;
  $byBucket[$bucketKey]['total'] += $txTotal;
  $byBucket[$bucketKey]['tx_count'] += 1;
  $byBucket[$bucketKey]['items'] += $lineCount;
}

/* compute avg_unit per bucket */
foreach ($byBucket as $k => $v) {
  $byBucket[$k]['avg_unit'] = ($v['qty'] > 0) ? ($v['total'] / $v['qty']) : 0.0;
}

/* sort bucket keys recent-first */
uksort($byBucket, function($a,$b) use($gran){
  if ($gran === 'day') return strtotime($b) <=> strtotime($a);
  if ($gran === 'week') {
    $getTs = function($k){
      if (preg_match('/^(\d+)-W(\d+)$/',$k,$m)) { $d=new DateTime(); $d->setISODate((int)$m[1], (int)$m[2]); return $d->getTimestamp(); }
      return strtotime($k);
    };
    return $getTs($b) <=> $getTs($a);
  }
  return strtotime($b . '-01') <=> strtotime($a . '-01');
});

/* totals */
$totQty = $totTotal = 0.0;
foreach ($byBucket as $m) {
  $totQty += (float)$m['qty'];
  $totTotal += (float)$m['total'];
}

/* ---------- server-side pagination of buckets ---------- */
$bucketKeys = array_keys($byBucket);
$totalBuckets = count($bucketKeys);
$totalPages = max(1, (int)ceil($totalBuckets / PAGE_SIZE));
if ($page > $totalPages) $page = $totalPages;
$startIndex = ($page - 1) * PAGE_SIZE;
$paginatedKeys = array_slice($bucketKeys, $startIndex, PAGE_SIZE, true);

/* ---------------- AJAX fragment handler (modal content) ---------------- */
if ($ajax_bucket) {
  list($bStart, $bEnd) = bucket_range_from_key($ajax_bucket, $gran);
  if (!$bStart || !$bEnd) {
    echo "<div class='alert alert-warning'>Invalid bucket.</div>"; exit;
  }

  // fetch purchase transactions for this bucket (with items, applying product/category filters if present)
  $qParams = [$bStart, $bEnd];
  $txSql = "
    SELECT t.id, t.ref_no, t.date, t.created_at, t.notes
    FROM transactions t
    WHERE t.type = 'purchase'
      AND t.date BETWEEN ? AND ?
    ORDER BY t.date DESC, t.id DESC
  ";
  $dSt = $pdo->prepare($txSql);
  $dSt->execute($qParams);
  $detailTxRows = $dSt->fetchAll(PDO::FETCH_ASSOC);

  if ($detailTxRows) {
    $dTxIds = array_map(function($r){ return (int)$r['id']; }, $detailTxRows);
    $in = implode(',', array_fill(0, count($dTxIds), '?'));
    $itSql = "
      SELECT ti.transaction_id, p.code, p.name, ti.qty, ti.unit_price, ti.cost_at_sale,
             s.name AS supplier_name, l.name as location_name
      FROM transaction_items ti
      JOIN products p ON p.id = ti.product_id
      LEFT JOIN suppliers s ON s.id = p.supplier_id
      LEFT JOIN locations l ON l.id = p.location_id
      WHERE ti.transaction_id IN ($in)
    ";
    $detailParams = $dTxIds;
    if ($cat !== '') { $itSql .= " AND p.category_id = ? "; $detailParams[] = (int)$cat; }
    if ($product_id !== '') { $itSql .= " AND p.id = ? "; $detailParams[] = (int)$product_id; }
    $itSql .= " ORDER BY ti.transaction_id, ti.id";
    $itSt = $pdo->prepare($itSql);
    $itSt->execute($detailParams);
    $detailItemsByTx = [];
    foreach ($itSt as $r) { $detailItemsByTx[$r['transaction_id']][] = $r; }
  } else {
    echo "<div class='alert alert-info'>No purchases found for this bucket.</div>";
    exit;
  }

  // output fragment
  foreach ($detailTxRows as $t) {
    $txItems = $detailItemsByTx[$t['id']] ?? [];
    if (!$txItems) continue; // skip if no items after filter
    $dtRaw  = $t['created_at'] ?: $t['date'];
    $dtDisp = $dtRaw ? date('Y-m-d H:i', strtotime($dtRaw)) : '';
    echo "<div class='card mb-2'><div class='card-body p-2'>";
    echo "<div class='d-flex justify-content-between'><div><strong>" . h($t['ref_no'] ?? ('TX_'.$t['id'])) . "</strong>";
    echo "<div class='small text-muted'>" . h($dtDisp) . "</div></div>";
    echo "</div>";

    echo "<table class='table table-sm mt-2 mb-0'><thead><tr><th style='width:15%'>Code</th><th>Product</th><th>Supplier</th><th>Location</th><th class='text-end'>Qty</th><th class='text-end'>Unit Price</th><th class='text-end'>Total</th></tr></thead><tbody>";
    foreach ($txItems as $li) {
      $lineTotal = (float)$li['qty'] * (float)$li['unit_price'];
      echo "<tr>";
      echo "<td>" . h($li['code']) . "</td>";
      echo "<td>" . h($li['name']) . "</td>";
      echo "<td>" . h($li['supplier_name'] ?? '—') . "</td>";
      echo "<td>" . h($li['location_name'] ?? '—') . "</td>";
      echo "<td class='text-end'>" . rtrim(rtrim(number_format($li['qty'], 3, '.', ''), '0'), '.') . "</td>";
      echo "<td class='text-end'>₱" . number_format((float)$li['unit_price'], 2) . "</td>";
      echo "<td class='text-end'>₱" . number_format($lineTotal, 2) . "</td>";
      echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='mt-2 small text-muted'>Notes: " . h($t['notes'] ?? '—') . "</div>";
    echo "</div></div>";
  }

  exit;
}

/* ---------------- fetch categories for dropdown & products preview ---------------- */
$cats = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// fetch products for preview (filtered by selected category, show limited)
$prodParams = [];
$prodSql = "SELECT id, code, name, stock_qty, unit, sold_by, sell_price, wholesale_price, category_id FROM products WHERE is_active=1";
if ($cat !== '') { $prodSql .= " AND category_id = ?"; $prodParams[] = (int)$cat; }
$prodSql .= " ORDER BY name LIMIT 200";
$prodSt = $pdo->prepare($prodSql);
$prodSt->execute($prodParams);
$products = $prodSt->fetchAll(PDO::FETCH_ASSOC);

/* get selected product info for UI */
$selectedProduct = null;
if ($product_id !== '') {
  $pstm = $pdo->prepare("SELECT id,code,name FROM products WHERE id = ? LIMIT 1");
  $pstm->execute([(int)$product_id]);
  $selectedProduct = $pstm->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ---------------- Render page ---------------- */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Purchases Summary</h4>
  <div class="text-end small">
    <div><strong>Period:</strong> <?= h($from) ?> → <?= h($to) ?></div>
    <div><strong>Group by:</strong> <?= h(ucfirst($gran)) ?></div>
  </div>
</div>

<form class="row g-2 mb-3">
  <div class="col-md-3">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">Group by</label>
    <select class="form-select" name="granularity">
      <option value="day" <?= $gran==='day'?'selected':'' ?>>Days</option>
      <option value="week" <?= $gran==='week'?'selected':'' ?>>Weeks</option>
      <option value="month" <?= $gran==='month'?'selected':'' ?>>Months</option>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Category</label>
    <select class="form-select" name="category_id" onchange="this.form.submit()">
      <option value="">All categories</option>
      <?php foreach($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((string)$c['id'] === (string)$cat ? 'selected' : '') ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <label class="form-label">Product</label>
    <div class="d-flex gap-2">
      <input type="hidden" name="product_id" id="productFilterInput" value="<?= h($product_id) ?>">
      <input class="form-control" type="text" readonly value="<?= $selectedProduct ? h($selectedProduct['code'] . ' — ' . $selectedProduct['name']) : '' ?>" placeholder="(click product tile)">
      <?php if ($product_id !== ''): ?>
        <a class="btn btn-outline-danger" href="?<?= http_build_query(['from'=>$from,'to'=>$to,'category_id'=>$cat,'granularity'=>$gran]) ?>" title="Clear product">Clear</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-12 mt-2">
    <button class="btn btn-outline-secondary">Filter</button>
  </div>
</form>

<!-- Products preview tiles (clickable -> filter by product) -->
<div class="mb-3">
  <div class="d-flex gap-2 align-items-center mb-2">
    <div class="small text-muted ms-2">Click a product tile to filter the report to that product for the selected date range/category.</div>
  </div>
  <div id="productTiles" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
    <?php foreach ($products as $p): 
      $isSelected = ($product_id !== '' && (int)$product_id === (int)$p['id']);
    ?>
      <div class="product-tile" data-id="<?= (int)$p['id'] ?>" style="border:1px solid <?= $isSelected? '#0d6efd':'#e6eaee' ?>;padding:10px;border-radius:8px;background:<?= $isSelected? '#e9f2ff':'#fff' ?>;cursor:pointer;">
        <div style="font-weight:600"><?= h($p['code']) ?> — <?= h($p['name']) ?></div>
        <div style="font-size:12px;color:#666"><?= rtrim(rtrim(number_format($p['stock_qty'],3,'.',''),'0'),'.') ?> <?= h($p['unit'] ?: 'pc') ?></div>
        <div style="text-align:right;font-weight:700">₱<?= number_format($p['sell_price'],2) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- KPI tiles -->
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="p-3 bg-light border rounded small">Total Quantity<br><strong><?= number_format($totQty,2) ?></strong></div></div>
  <div class="col-md-3"><div class="p-3 bg-light border rounded small">Total Value<br><strong>₱<?= number_format($totTotal,2) ?></strong></div></div>
  <div class="col-md-3"><div class="p-3 bg-light border rounded small">Buckets<br><strong><?= $totalBuckets ?></strong></div></div>
  <div class="col-md-3"><div class="p-3 bg-light border rounded small">Pages<br><strong><?= $totalPages ?></strong></div></div>
</div>

<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>Date</th>
      <th class="text-end">Quantity</th>
      <th class="text-end">Avg Unit Price</th>
      <th class="text-end">Total</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($paginatedKeys)): ?>
      <tr><td colspan="4" class="text-muted">No data for this period.</td></tr>
    <?php else: ?>
      <?php foreach ($paginatedKeys as $key): $m = $byBucket[$key]; ?>
        <tr>
          <td><a href="javascript:void(0)" class="bucket-link" data-bucket="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= h(bucket_label($key, $gran)) ?></a></td>
          <td class="text-end"><?= rtrim(rtrim(number_format($m['qty'],3,'.',''),'0'),'.') ?></td>
          <td class="text-end">₱<?= number_format($m['avg_unit'],2) ?></td>
          <td class="text-end">₱<?= number_format($m['total'],2) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- pagination -->
<nav aria-label="Buckets pagination" class="mt-3">
  <ul class="pagination">
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <?php $prev = $page-1; $pqp = http_build_query(['from'=>$from,'to'=>$to,'category_id'=>$cat,'product_id'=>$product_id,'granularity'=>$gran,'page'=>$prev]); ?>
      <a class="page-link" href="?<?= $pqp ?>">« Prev</a>
    </li>
    <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
      <?php $next = $page+1; $nqp = http_build_query(['from'=>$from,'to'=>$to,'category_id'=>$cat,'product_id'=>$product_id,'granularity'=>$gran,'page'=>$next]); ?>
      <a class="page-link" href="?<?= $nqp ?>">Next »</a>
    </li>
  </ul>
</nav>

<!-- modal for bucket details -->
<div class="modal fade" id="bucketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bucket details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="bucketModalBody">
        <div class="text-center text-muted">Loading...</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // bucket modal
  const modalEl = document.getElementById('bucketModal');
  const modalBody = document.getElementById('bucketModalBody');
  const bsModal = new bootstrap.Modal(modalEl);
  document.querySelectorAll('.bucket-link').forEach(link=>{
    link.addEventListener('click', function(e){
      const bucket = this.dataset.bucket;
      if (!bucket) return;
      modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      bsModal.show();

      const params = new URLSearchParams();
      params.set('ajax_bucket', bucket);
      params.set('granularity', '<?= h($gran) ?>');
      params.set('from', '<?= h($from) ?>');
      params.set('to', '<?= h($to) ?>');
      <?php if ($cat !== ''): ?> params.set('category_id', '<?= h($cat) ?>'); <?php endif; ?>
      <?php if ($product_id !== ''): ?> params.set('product_id', '<?= h($product_id) ?>'); <?php endif; ?>

      fetch('purchases_report.php?' + params.toString(), { credentials: 'same-origin' })
        .then(r => r.text())
        .then(html => modalBody.innerHTML = html)
        .catch(err => {
          modalBody.innerHTML = '<div class="alert alert-danger">Failed to load details.</div>';
          console.error(err);
        });
    });
  });

  // product tile click -> reload with product_id and keep other filters
  document.querySelectorAll('.product-tile').forEach(tile=>{
    tile.addEventListener('click', function(){
      const pid = this.dataset.id;
      const params = new URLSearchParams(window.location.search);
      params.set('product_id', pid);
      // reset to page 1
      params.set('page', '1');
      window.location.search = params.toString();
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
