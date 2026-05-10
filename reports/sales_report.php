<?php
    ini_set('display_errors', 1);
error_reporting(E_ALL);
// public/reports/sales_report.php
require_once __DIR__ . '/../includes/header.php';
require_role(['admin','staff','auditor']);
$pdo = getDB();
function money($n) {
    return '₱' . number_format((float)$n, 2);
}
/* ----------------- Config ----------------- */
define('PAGE_SIZE', 12); // buckets per page (adjust)

/* ---------------- filters ---------------- */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$mode = $_GET['mode'] ?? ''; // '', 'retail', 'wholesale'
$gran = $_GET['granularity'] ?? 'day'; // 'day' | 'week' | 'month'
$page = max(1, (int)($_GET['page'] ?? 1));
$ajax_bucket = $_GET['ajax_bucket'] ?? null; // for AJAX detail load

// Category (only) and product_id (set by clicking tile; optional)
$category_id = $_GET['category_id'] ?? '';
$product_id  = $_GET['product_id'] ?? '';

$validGrans = ['day','week','month'];
if (!in_array($gran, $validGrans, true)) $gran = 'day';

/* ---------------- helper functions ---------------- */
function line_gross($qty, $unit) {
  return max(0.0, (float)$qty * (float)$unit);
}
function line_discount_amount($gross, $dt, $dv) {
  if ($dv === null || $dv === '' || $dt === null) return 0.0;
  if ($dt === 'percent') return max(0.0, $gross * (max(0, min(100, (float)$dv))/100));
  if ($dt === 'amount')  return max(0.0, (float)$dv);
  return 0.0;
}
function invoice_discount_amount($subtotal, $dt, $dv) {
  if (!$dt || $dv === null) return 0.0;
  if ($dt === 'percent') return max(0.0, $subtotal * (max(0,min(100,(float)$dv))/100));
  if ($dt === 'amount')  return max(0.0, (float)$dv);
  return 0.0;
}
function bucket_key($dtString, $gran) {
  $ts = strtotime($dtString);
  if ($gran === 'day')   return date('Y-m-d', $ts);
  if ($gran === 'week')  return date('o-\WW', $ts); // ISO year-week
  return date('Y-m', $ts); // month
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

/* ---------------- load categories & all products (for client-side rendering) ---------------- */
$categories = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allProducts = $pdo->query("SELECT id, code, name, category_id, sell_price, stock_qty, unit, sold_by FROM products WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- fetch tx + lines for period (apply category/product filters) ---------------- */
$params = [$from, $to];
$modeSql = '';
if ($mode === 'retail' || $mode === 'wholesale') {
  $modeSql = " AND t.sale_mode = ? ";
  $params[] = $mode;
}

// Add EXISTS filters for product/category if provided (server side safety)
$existsProductSql = '';
if ($product_id !== '') {
  $existsProductSql = " AND EXISTS (SELECT 1 FROM transaction_items ti WHERE ti.transaction_id = t.id AND ti.product_id = ?)";
  $params[] = (int)$product_id;
}
$existsCategorySql = '';
if ($category_id !== '') {
  $existsCategorySql = " AND EXISTS (SELECT 1 FROM transaction_items ti JOIN products p ON p.id = ti.product_id WHERE ti.transaction_id = t.id AND p.category_id = ?)";
  $params[] = (int)$category_id;
}

$txSql = "
  SELECT t.id, t.type, t.date, t.created_at,
         t.extra_discount_type, t.extra_discount_value, t.sale_mode
  FROM transactions t
  WHERE t.type IN ('sale','refund')
    AND t.date BETWEEN ? AND ?
    $modeSql
    $existsProductSql
    $existsCategorySql
  ORDER BY t.date, t.id
";
$txSt = $pdo->prepare($txSql);
$txSt->execute($params);
$txRows = $txSt->fetchAll(PDO::FETCH_ASSOC);

// collect tx ids
$txIds = array_map(function($r){ return (int)$r['id']; }, $txRows);
$items = [];
if ($txIds) {
  $in  = implode(',', array_fill(0, count($txIds), '?'));
  $itSql = "
    SELECT ti.transaction_id, ti.qty, ti.unit_price, ti.discount_type, ti.discount_value, ti.cost_at_sale,
           p.id AS product_id, p.code, p.name, p.category_id
    FROM transaction_items ti
    JOIN products p ON p.id = ti.product_id
    WHERE ti.transaction_id IN ($in)
    ORDER BY ti.transaction_id, ti.id
  ";
  $itSt = $pdo->prepare($itSql);
  $itSt->execute($txIds);
  foreach ($itSt as $r) $items[] = $r;
}
$itemsByTx = [];
foreach ($items as $it) $itemsByTx[(int)$it['transaction_id']][] = $it;

/* ---------------- aggregate into buckets ---------------- */
$byBucket = [];
function ensure_bucket(&$byBucket, $key) {
  if (!isset($byBucket[$key])) {
    $byBucket[$key] = [
      'gross' => 0.0, 'refunds' => 0.0, 'discounts' => 0.0, 'net_sales' => 0.0,
      'cogs' => 0.0, 'gross_profit' => 0.0, 'tx_count' => 0, 'items' => 0
    ];
  }
}

foreach ($txRows as $t) {
  $tid = (int)$t['id'];
  $rows = $itemsByTx[$tid] ?? [];
  if (!$rows) continue;

  $subtotal_net_before_invoice = 0.0;
  $lineInfos = [];
  foreach ($rows as $li) {
    $qty = (float)$li['qty'];
    $unit = (float)$li['unit_price'];
    $gross = line_gross($qty, $unit);
    $lineDisc = line_discount_amount($gross, $li['discount_type'], $li['discount_value']);
    $netBeforeInvoice = max(0.0, $gross - $lineDisc);
    $lineInfos[] = [
      'qty'=>$qty,'unit'=>$unit,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBeforeInvoice'=>$netBeforeInvoice,
      'cost_at_sale'=> ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : null
    ];
    $subtotal_net_before_invoice += $netBeforeInvoice;
  }

  $invDiscAmt = invoice_discount_amount($subtotal_net_before_invoice, $t['extra_discount_type'], $t['extra_discount_value']);

  $cogs_total = 0.0; $gross_total = 0.0; $line_discounts_total = 0.0; $inv_alloc_total = 0.0; $net_total_after_all = 0.0; $items_count = 0;
  foreach ($lineInfos as $li) {
    $share = ($subtotal_net_before_invoice > 0) ? ($li['netBeforeInvoice'] / $subtotal_net_before_invoice) : 0.0;
    $alloc = $invDiscAmt * $share;
    $line_net_after = max(0.0, $li['netBeforeInvoice'] - $alloc);
    $line_discounts_total += $li['lineDisc'];
    $inv_alloc_total += $alloc;
    $gross_total += $li['gross'];
    $net_total_after_all += $line_net_after;
    $items_count += $li['qty'];
    $unitCost = $li['cost_at_sale'] ?? 0.0;
    $cogs_total += $li['qty'] * $unitCost;
  }

  $dtRaw = $t['created_at'] ?: $t['date'];
  $bucketKey = bucket_key($dtRaw ?: $t['date'], $gran);
  ensure_bucket($byBucket, $bucketKey);

  if ($t['type'] === 'refund') {

    $byBucket[$bucketKey]['refunds'] += $net_total_after_all;

    // keep gross sales untouched
    // refunds shown separately

    $byBucket[$bucketKey]['cogs'] -= $cogs_total;

    $byBucket[$bucketKey]['gross_profit'] -= ($net_total_after_all - $cogs_total);

    $byBucket[$bucketKey]['tx_count'] += 1;
    $byBucket[$bucketKey]['items'] += $items_count;
} else {
    $byBucket[$bucketKey]['gross'] += $gross_total;
    $byBucket[$bucketKey]['discounts'] += ($line_discounts_total + $inv_alloc_total);
    $byBucket[$bucketKey]['net_sales'] += $net_total_after_all;
    $byBucket[$bucketKey]['cogs'] += $cogs_total;
    $byBucket[$bucketKey]['gross_profit'] += ($net_total_after_all - $cogs_total);
    $byBucket[$bucketKey]['tx_count'] += 1;
    $byBucket[$bucketKey]['items'] += $items_count;
  }
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
$totGross = $totRefunds = $totDiscounts = $totNet = $totCogs = $totProfit = 0.0;
foreach ($byBucket as $m) {
  $totGross += (float)$m['gross'];
  $totRefunds += (float)$m['refunds'];
  $totDiscounts += (float)$m['discounts'];
  $totNet += (float)$m['net_sales'];
  $totCogs += (float)$m['cogs'];
  $totProfit += (float)$m['gross_profit'];
}

/* ---------- server-side pagination of buckets ---------- */
$bucketKeys = array_keys($byBucket);
$totalBuckets = count($bucketKeys);
$totalPages = max(1, (int)ceil($totalBuckets / PAGE_SIZE));
if ($page > $totalPages) $page = $totalPages;
$startIndex = ($page - 1) * PAGE_SIZE;
$paginatedKeys = array_slice($bucketKeys, $startIndex, PAGE_SIZE, true);

/* ---------------- AJAX fragment handler (modal content) ----------------
   If ajax_bucket is set, load transactions/items for that bucket and echo HTML fragment
*/
if ($ajax_bucket) {
  list($bStart, $bEnd) = bucket_range_from_key($ajax_bucket, $gran);
  if (!$bStart || !$bEnd) {
    echo "<div class='alert alert-warning'>Invalid bucket.</div>"; exit;
  }

  // We'll search transactions that either have t.date in [bStart,bEnd]
  // OR whose created_at (datetime) falls in the same inclusive range.
  // For created_at we'll expand to full day timestamps to include all times.
  $qParams = [$bStart, $bEnd];

  // created_at bounds (00:00:00..23:59:59)
  $createdStart = $bStart . ' 00:00:00';
  $createdEnd   = $bEnd   . ' 23:59:59';
  // We'll push them after the date params and use them in the SQL below.
  $qParams[] = $createdStart;
  $qParams[] = $createdEnd;

  $modeWhere = '';
  if ($mode === 'retail' || $mode === 'wholesale') { $modeWhere = " AND t.sale_mode = ? "; $qParams[] = $mode; }
  if ($product_id !== '') { $modeWhere .= " AND EXISTS (SELECT 1 FROM transaction_items ti WHERE ti.transaction_id = t.id AND ti.product_id = ?) "; $qParams[] = (int)$product_id; }
  if ($category_id !== '') { $modeWhere .= " AND EXISTS (SELECT 1 FROM transaction_items ti JOIN products p ON p.id = ti.product_id WHERE ti.transaction_id = t.id AND p.category_id = ?) "; $qParams[] = (int)$category_id; }

  // Note: the query uses placeholders in the same order as $qParams
  $detailTxSql = "
    SELECT t.id, t.type, t.ref_no, t.date, t.created_at, t.user_id, t.extra_discount_type, t.extra_discount_value, t.sale_mode
    FROM transactions t
    WHERE t.type IN ('sale','refund')
      AND (
            (t.date BETWEEN ? AND ?)
            OR (t.created_at BETWEEN ? AND ?)
          )
      $modeWhere
    ORDER BY t.date DESC, t.id DESC
  ";
  $dSt = $pdo->prepare($detailTxSql);
  $dSt->execute($qParams);
  $detailTxRows = $dSt->fetchAll(PDO::FETCH_ASSOC);
  $detailItemsByTx = [];
  if ($detailTxRows) {
    $dTxIds = array_map(function($r){ return (int)$r['id']; }, $detailTxRows);
    $in = implode(',', array_fill(0, count($dTxIds), '?'));
    $itSql = "
      SELECT ti.transaction_id, p.code, p.name, ti.qty, ti.unit_price, ti.discount_type, ti.discount_value, ti.cost_at_sale
      FROM transaction_items ti
      JOIN products p ON p.id = ti.product_id
      WHERE ti.transaction_id IN ($in)
      ORDER BY ti.transaction_id, ti.id
    ";
    $itSt = $pdo->prepare($itSql);
    $itSt->execute($dTxIds);
    foreach ($itSt as $r) { $detailItemsByTx[$r['transaction_id']][] = $r; }
  }

  if (empty($detailTxRows)) {
    echo "<div class='alert alert-info'>No transactions found for this bucket.</div>";
    exit;
  }

  foreach ($detailTxRows as $t) {
    $txItems = $detailItemsByTx[$t['id']] ?? [];
    $dtRaw  = $t['created_at'] ?: $t['date'];
    $dtDisp = $dtRaw ? date('Y-m-d H:i', strtotime($dtRaw)) : '';
    echo "<div class='card mb-2'><div class='card-body p-2'>";
    echo "<div class='d-flex justify-content-between'><div><strong>" . h($t['ref_no'] ?? ('TX_'.$t['id'])) . "</strong>";
    if ($t['type'] === 'refund') echo " <span class='badge bg-danger ms-2'>Refund</span>";
    echo "<div class='small text-muted'>" . h($dtDisp) . " · " . h(ucfirst($t['sale_mode'] ?? '')) . "</div></div>";
    // compute tx totals
    $txGross = 0.0; $txLineDisc = 0.0; $txInvAlloc = 0.0; $txNet = 0.0; $txCogs = 0.0;
    $linesTemp = [];
    foreach ($txItems as $li) {
      $qty = (float)$li['qty']; $unit = (float)$li['unit_price'];
      $gross = line_gross($qty,$unit);
      $lineDisc = line_discount_amount($gross, $li['discount_type'], $li['discount_value']);
      $netBeforeInvoice = max(0.0, $gross - $lineDisc);
      $linesTemp[] = ['qty'=>$qty,'gross'=>$gross,'lineDisc'=>$lineDisc,'netBeforeInvoice'=>$netBeforeInvoice,'cost_at_sale'=>$li['cost_at_sale']];
      $txGross += $gross; $txLineDisc += $lineDisc;
    }
    $txInvAmt = invoice_discount_amount(array_sum(array_column($linesTemp,'netBeforeInvoice')), $t['extra_discount_type'], $t['extra_discount_value']);
    $sumNetBefore = array_sum(array_column($linesTemp,'netBeforeInvoice'));
    foreach ($linesTemp as $li) {
      $share = ($sumNetBefore>0)? ($li['netBeforeInvoice']/$sumNetBefore):0;
      $alloc = $txInvAmt * $share; $txInvAlloc += $alloc;
      $txNet += max(0.0, $li['netBeforeInvoice'] - $alloc);
      $unitCost = ($li['cost_at_sale'] !== null && $li['cost_at_sale'] !== '') ? (float)$li['cost_at_sale'] : 0.0;
      $txCogs += $li['qty'] * $unitCost;
    }
    $txDiscounts = $txLineDisc + $txInvAlloc; $txProfit = $txNet - $txCogs;
    echo "<div class='text-end'><div class='fw-bold'>Net: ₱" . number_format($txNet,2) . "</div>";
    echo "<div class='small text-muted'>Gross: ₱" . number_format($txGross,2) . " · Discounts: ₱" . number_format($txDiscounts,2) . "</div></div></div>";

    if ($txItems) {
      echo "<table class='table table-sm mt-2 mb-0'><thead><tr><th style='width:18%'>Code</th><th>Item</th><th class='text-end'>Qty</th><th class='text-end'>Unit</th><th class='text-end'>Discount</th><th class='text-end'>Line Total</th></tr></thead><tbody>";
      foreach ($txItems as $li) {
        $ln = line_gross($li['qty'],$li['unit_price']);
        $ld = line_discount_amount($ln,$li['discount_type'],$li['discount_value']);
        $lnAfter = max(0, $ln - $ld);
        $discLabel = '';
        if ($li['discount_type']==='percent' && $li['discount_value']!=='') $discLabel = number_format((float)$li['discount_value'],2).' %';
        if ($li['discount_type']==='amount'  && $li['discount_value']!=='') $discLabel = '₱'.number_format((float)$li['discount_value'],2);
        echo "<tr><td>" . h($li['code']) . "</td><td>" . h($li['name']) . "</td><td class='text-end'>" . (float)$li['qty'] . "</td><td class='text-end'>₱" . number_format((float)$li['unit_price'],2) . "</td><td class='text-end'>" . ($discLabel?:'—') . "</td><td class='text-end'>₱" . number_format($lnAfter,2) . "</td></tr>";
      }
      echo "</tbody></table>";
    }

    echo "<div class='mt-2 small text-muted'>Invoice discount: " . ($t['extra_discount_type'] ? h($t['extra_discount_type']).' '.h($t['extra_discount_value']) : '—') . " · COGS: ₱" . number_format($txCogs,2) . " · Profit: ₱" . number_format($txProfit,2) . "</div>";
    echo "</div></div>";
  }

  exit;
}

/* ---------------- Render full page ---------------- */
?>
<style>
/* small tile styles matching POS */
.product-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); gap:10px; margin-bottom:12px; }
.product-card { background:#fff; border:1px solid #e6eaee; padding:10px; border-radius:8px; cursor:pointer; display:flex; justify-content:space-between; flex-direction:column; height:110px; }
.product-title { font-weight:600; font-size:14px; }
.product-meta { font-size:12px; color:#666; }
.product-card.active { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,0.08); }
.tile-all { background:#0d6efd; color:#fff; text-align:center; display:flex; align-items:center; justify-content:center; font-weight:600; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Sales Summary</h4>
  <div class="text-end small">
    <div><strong>Period:</strong> <?= h($from) ?> → <?= h($to) ?></div>
    <div><strong>Granularity:</strong> <?= h(ucfirst($gran)) ?></div>
    <div><strong>Mode:</strong> <?= ($mode==='retail'||$mode==='wholesale') ? h(ucfirst($mode)) : 'All' ?></div>
  </div>
</div>

<form id="filterForm" class="row g-2 mb-3" method="get" action="">
  <input type="hidden" name="product_id" id="product_id_input" value="<?= h($product_id) ?>">
  <div class="col-md-2">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">Mode</label>
    <select class="form-select" name="mode">
      <option value="" <?= $mode==='' ? 'selected' : '' ?>>All</option>
      <option value="retail" <?= $mode==='retail' ? 'selected' : '' ?>>Retail</option>
      <option value="wholesale" <?= $mode==='wholesale' ? 'selected' : '' ?>>Wholesale</option>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Group by</label>
    <select class="form-select" name="granularity">
      <option value="day" <?= $gran==='day'?'selected':'' ?>>Days</option>
      <option value="week" <?= $gran==='week'?'selected':'' ?>>Weeks</option>
      <option value="month" <?= $gran==='month'?'selected':'' ?>>Months</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Category</label>
    <select class="form-select" name="category_id" id="categorySelect">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): $sel = ((string)$category_id === (string)$c['id']) ? 'selected' : ''; ?>
        <option value="<?= (int)$c['id'] ?>" <?= $sel ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-1 align-self-end">
    <button class="btn btn-outline-secondary w-100">Filter</button>
  </div>
</form>

<!-- Product tiles (populated client-side) -->
<div id="productsTileWrapper" class="mb-3">
  <div class="product-grid" id="productGrid">
    <!-- Tiles rendered via JS -->
  </div>
</div>

<!-- KPIs -->
<?php
  // Explicitly compute "visible" net sales after refunds + discounts
  // Some versions of your logic already subtract refunds, but this ensures it’s correct.
  $visible_tot_net = $totGross - $totRefunds;
?>
<div class="row g-3 mb-4">
  <div class="col-md-2">
    <div class="p-3 bg-light border rounded small">
      Gross Sales<br><strong><?= money($totGross) ?></strong>
    </div>
  </div>

  <div class="col-md-2">
    <div class="p-3 bg-light border rounded small">
      Refunds<br><strong><?= money($totRefunds) ?></strong>
    </div>
  </div>

  <div class="col-md-2">
    <div class="p-3 bg-light border rounded small">
      Discounts<br><strong><?= money($totDiscounts) ?></strong>
    </div>
  </div>

  <div class="col-md-2">
    <div class="p-3 bg-light border rounded small">
      Net Sales (After Refunds)<br><strong><?= money($visible_tot_net) ?></strong>
    </div>
  </div>

  <div class="col-md-2">
    <div class="p-3 bg-light border rounded small">
      COGS<br><strong><?= money($totCogs) ?></strong>
    </div>
  </div>

  <div class="col-md-2">
    <div class="p-3 bg-light border rounded small">
      Gross Profit<br><strong><?= money($totProfit) ?></strong>
    </div>
  </div>
</div>

<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>Date</th>
      <th class="text-end">Gross sales</th>
      <th class="text-end">Refunds</th>
      <th class="text-end">Discounts</th>
      <th class="text-end">Net sales</th>
      <th class="text-end">Cost of goods</th>
      <th class="text-end">Gross profit</th>
    </tr>
  </thead>
  
<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>Date</th>
      <th class="text-end">Gross sales</th>
      <th class="text-end">Refunds</th>
      <th class="text-end">Discounts</th>
      <th class="text-end">Net sales</th>
      <th class="text-end">Cost of goods</th>
      <th class="text-end">Gross profit</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($paginatedKeys)): ?>
      <tr><td colspan="7" class="text-muted">No data for this period.</td></tr>
    <?php else: ?>
      <?php foreach ($paginatedKeys as $key): $m = $byBucket[$key]; ?>
        <?php $bucketDataAttr = htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>
        <tr>
          <!-- Date / clickable bucket -->
          <td>
            <a href="javascript:void(0)" class="bucket-link" data-bucket="<?= $bucketDataAttr ?>">
              <?= h(bucket_label($key, $gran)) ?>
            </a>
          </td>

          <!-- amounts (use money() for consistent formatting) -->
          <td class="text-end"><?= money($m['gross']) ?></td>
          <td class="text-end"><?= money($m['refunds']) ?></td>
          <td class="text-end"><?= money($m['discounts']) ?></td>

          <?php
            // compute visible net-after-refunds for this bucket
            $visible_net_after_refunds = $m['gross'] - $m['refunds'];
          ?>
          <td class="text-end"><?= money($visible_net_after_refunds) ?></td>
          <td class="text-end"><?= money($m['cogs']) ?></td>
          <td class="text-end"><?= money($m['gross_profit']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- pagination -->
<nav aria-label="Buckets pagination" class="mt-3">
  <ul class="pagination">
    <li class="page-item <?= $page<=1 ? 'disabled' : '' ?>">
      <?php $prev = $page-1; $pqp = http_build_query(['from'=>$from,'to'=>$to,'mode'=>$mode,'granularity'=>$gran,'page'=>$prev,'category_id'=>$category_id,'product_id'=>$product_id]); ?>
      <a class="page-link" href="?<?= $pqp ?>">« Prev</a>
    </li>

    <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>

    <li class="page-item <?= $page>=$totalPages ? 'disabled' : '' ?>">
      <?php $next = $page+1; $nqp = http_build_query(['from'=>$from,'to'=>$to,'mode'=>$mode,'granularity'=>$gran,'page'=>$next,'category_id'=>$category_id,'product_id'=>$product_id]); ?>
      <a class="page-link" href="?<?= $nqp ?>">Next »</a>
    </li>
  </ul>
</nav>


<!-- Modal for AJAX details -->
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
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
const ALL_PRODUCTS = <?= json_encode($allProducts, JSON_UNESCAPED_UNICODE) ?>;
const selectedCategory = '<?= h($category_id) ?>';
const selectedProduct  = '<?= h($product_id) ?>';
const productGrid = document.getElementById('productGrid');
const categorySelect = document.getElementById('categorySelect');
const productIdInput = document.getElementById('product_id_input');

// Render products as tiles for the chosen category
function renderProducts(catId){
  productGrid.innerHTML = '';
  // "All" tile
  const allTile = document.createElement('div');
  allTile.className = 'product-card tile-all';
  allTile.textContent = 'All';
  allTile.title = 'Show all products (clear product filter)';
  allTile.addEventListener('click', ()=> {
    productIdInput.value = '';
    document.getElementById('filterForm').submit();
  });
  productGrid.appendChild(allTile);

  const filtered = ALL_PRODUCTS.filter(p => {
    if (!catId) return true;
    return String(p.category_id) === String(catId);
  });

  if (!filtered.length) {
    const note = document.createElement('div');
    note.className = 'text-muted';
    note.textContent = 'No products in this category.';
    productGrid.appendChild(note);
    return;
  }

  for (const p of filtered) {
    const card = document.createElement('div');
    card.className = 'product-card' + (String(p.id) === selectedProduct ? ' active' : '');
    card.dataset.id = p.id;
    card.innerHTML = `<div>
                        <div class="product-title">${p.code} — ${p.name}</div>
                        <div class="product-meta">${(p.stock_qty||0)} ${p.unit||'pc'}</div>
                      </div>
                      <div style="text-align:right">
                        <div class="fw-bold">₱${(parseFloat(p.sell_price)||0).toFixed(2)}</div>
                        <div class="product-meta">${p.sold_by==='weight' ? (p.unit||'kg') : 'pc'}</div>
                      </div>`;
    card.addEventListener('click', ()=> {
      // set product_id and submit to filter by product (clicking tile acts as filter)
      productIdInput.value = p.id;
      document.getElementById('filterForm').submit();
    });
    productGrid.appendChild(card);
  }
}

document.addEventListener('DOMContentLoaded', function(){
  // initial render using selectedCategory
  renderProducts(selectedCategory);

  categorySelect.addEventListener('change', function(){
    // update product tiles immediately (but user must press Filter to reload report,
    // or we could auto-submit; leaving manual submit to give control)
    renderProducts(this.value);
  });

  // Modal AJAX for bucket details (passes category/product filters)
  const modalEl = document.getElementById('bucketModal');
  const modalBody = document.getElementById('bucketModalBody');
  const bsModal = new bootstrap.Modal(modalEl);

  document.querySelectorAll('.bucket-link').forEach(link=>{
    link.addEventListener('click', function(e){
      const bucket = this.dataset.bucket;
      if (!bucket) return;
      modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
      bsModal.show();

      // build AJAX URL
      const params = new URLSearchParams();
      params.set('ajax_bucket', bucket);
      params.set('granularity', '<?= h($gran) ?>');
      params.set('from', '<?= h($from) ?>');
      params.set('to', '<?= h($to) ?>');
      params.set('mode', '<?= h($mode) ?>');
      // include category/product filters
      const cat = document.getElementById('categorySelect').value;
      const prod = document.getElementById('product_id_input').value;
      if (cat) params.set('category_id', cat);
      if (prod) params.set('product_id', prod);

      fetch('sales_report.php?' + params.toString(), { credentials: 'same-origin' })
        .then(r => r.text())
        .then(html => {
          modalBody.innerHTML = html;
        })
        .catch(err => {
          modalBody.innerHTML = '<div class="alert alert-danger">Failed to load details.</div>';
          console.error(err);
        });
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
