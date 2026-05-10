<?php
require_once __DIR__.'/includes/header.php';
require_login();

$pdo = getDB();

/* ---------- filters ---------- */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$type = $_GET['type'] ?? '';   // '', 'purchase', 'sale', 'adjust', 'refund'
$q    = trim($_GET['q'] ?? ''); // matches ref_no, notes, supplier/customer

// Build base date filter
$params = [$from . ' 00:00:00', $to . ' 23:59:59'];
$where  = "t.created_at BETWEEN ? AND ?";

// If user selected a type, add it (allow 'refund' and others)
if ($type !== '') {
    // only accept known types to avoid injection / errors
    $allowed = ['purchase','sale','adjust','refund'];
    if (in_array($type, $allowed, true)) {
        $where .= " AND t.type = ?";
        $params[] = $type;
    }
}


if ($q !== '') {
  $where .= " AND (t.ref_no LIKE ? OR t.notes LIKE ? OR COALESCE(s.name,'') LIKE ? OR COALESCE(c.name,'') LIKE ?)";
  $like = '%'.$q.'%';
  array_push($params, $like, $like, $like, $like);
}

/* ---------- main rows (grouped) ---------- */
$sql = "
  SELECT
    t.id AS transaction_id,
    t.type,
    t.ref_no,
    t.date,
    t.created_at,
    u.name AS user_name,
    s.name AS supplier_name,
    c.name AS customer_name,
    COUNT(ti.id) AS item_count,
    SUM(ti.qty) AS qty_total,
    SUM(
      GREATEST(
        0,
        (ti.qty * ti.unit_price)
        - CASE
            WHEN ti.discount_type='percent' AND ti.discount_value IS NOT NULL
              THEN (ti.qty * ti.unit_price) * (ti.discount_value/100)
            WHEN ti.discount_type='amount' AND ti.discount_value IS NOT NULL
              THEN ti.discount_value
            ELSE 0
          END
      )
    ) AS net_value
  FROM transactions t
  JOIN users u             ON u.id = t.user_id
  LEFT JOIN suppliers s    ON s.id = t.supplier_id
  LEFT JOIN customers c    ON c.id = t.customer_id
  JOIN transaction_items ti ON ti.transaction_id = t.id
  WHERE $where
  GROUP BY t.id, t.type, t.ref_no, t.date, t.created_at, u.name, s.name, c.name
  ORDER BY t.date DESC, t.id DESC
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---------- line items for drilldown ---------- */
// Separate base filters (without s/c joins)
$where_tx = "t.created_at BETWEEN ? AND ?";
$params_tx = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($type !== '') {
    if (in_array($type, ['purchase','sale','refund','adjust'], true)) {
        $where_tx .= " AND t.type = ?";
        $params_tx[] = $type;
    }
}


// fetch line items
$li = $pdo->prepare("
  SELECT
    ti.transaction_id, p.code, p.name,
    ti.qty, ti.unit_price, ti.discount_type, ti.discount_value
  FROM transaction_items ti
  JOIN products p ON p.id = ti.product_id
  JOIN transactions t ON t.id = ti.transaction_id
  WHERE $where_tx
  ORDER BY ti.transaction_id, ti.id
");
$li->execute($params_tx);

$itemsByTx = [];
foreach ($li as $r) { $itemsByTx[$r['transaction_id']][] = $r; }


/* ---------------- Include stock_adjust audit_log entries as pseudo-transactions (compatible) ---------------- */
/* ... (audit_log -> pseudo transaction conversion unchanged) ... */

$auditSql = "SELECT id, user_id, description, new_data, created_at
             FROM audit_log
             WHERE 1=1";
$auditParamsPos = [];

if (!empty($from)) {
    $auditSql .= " AND created_at >= ?";
    $auditParamsPos[] = $from . ' 00:00:00';
}
if (!empty($to)) {
    $auditSql .= " AND created_at <= ?";
    $auditParamsPos[] = $to . ' 23:59:59';
}

$auditSql .= " AND (
    LOWER(new_data) LIKE ?
    OR LOWER(new_data) LIKE ?
    OR LOWER(description) LIKE ?
    OR description LIKE ?
    OR new_data REGEXP ?
)";
$auditParamsPos[] = '%"type":"stock_adjust"%';
$auditParamsPos[] = '%stock_adjust%';
$auditParamsPos[] = '%adjusted pid%';
$auditParamsPos[] = '%Adjusted PID%';
$auditParamsPos[] = 'stock.?adjust';

if ($q !== '') {
    $auditSql .= " AND (description LIKE ? OR new_data LIKE ?)";
    $auditParamsPos[] = '%' . $q . '%';
    $auditParamsPos[] = '%' . $q . '%';
}

if (!empty($_GET['user_id'])) {
    $auditSql .= " AND user_id = ?";
    $auditParamsPos[] = (int)$_GET['user_id'];
}

$auditSql .= " ORDER BY created_at DESC, id DESC LIMIT 1000";

$ast = $pdo->prepare($auditSql);
$ast->execute($auditParamsPos);
$auditRows = $ast->fetchAll(PDO::FETCH_ASSOC);

foreach ($auditRows as $ar) {
    $aid = (int)$ar['id'];
    $newDataRaw = $ar['new_data'] ?? null;

    $decoded = null;
    if ($newDataRaw) {
        $decoded = @json_decode($newDataRaw, true);
        if ($decoded === null && is_string($newDataRaw)) {
            $try = @json_decode(stripslashes($newDataRaw), true);
            if (is_array($try)) $decoded = $try;
            else {
                if (preg_match('/^"(.*)"$/s', $newDataRaw, $m)) {
                    $try2 = @json_decode(str_replace('\"','"',$m[1]), true);
                    if (is_array($try2)) $decoded = $try2;
                }
            }
        }
    }

    $items = [];
    if (is_array($decoded)) {
        if (!empty($decoded['items']) && is_array($decoded['items'])) {
            $items = $decoded['items'];
        } elseif (!empty($decoded['data']['items']) && is_array($decoded['data']['items'])) {
            $items = $decoded['data']['items'];
        } else {
            foreach ($decoded as $k => $v) {
                if (is_array($v) && isset($v[0]) && (isset($v[0]['product_id']) || isset($v[0]['delta']))) {
                    $items = $v;
                    break;
                }
            }
        }
    }

    if (empty($items) && !empty($ar['description'])) {
        $matches = [];
        preg_match_all('/Adjusted\s+PID\s*(\d+)\s*:\s*before=([0-9.\-]+)\s*,\s*delta=([0-9.\-]+)\s*,\s*after=([0-9.\-]+)/i', $ar['description'], $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $items[] = [
                'product_id' => (int)$m[1],
                'before' => (float)$m[2],
                'delta' => (float)$m[3],
                'after' => (float)$m[4],
            ];
        }
    }

    if (empty($items)) continue;

    $qty_total = 0.0;
    foreach ($items as $it) $qty_total += (float)($it['delta'] ?? 0);

    $pseudoId = 'adj' . $aid;
    $row = [
        'id' => $pseudoId,
        'type' => 'adjust',
        'ref_no' => 'ADJ_' . $aid,
        'date' => $ar['created_at'],
        'created_at' => $ar['created_at'],
        'user_name' => null,
        'supplier_name' => null,
        'customer_name' => null,
        'item_count' => count($items),
        'qty_total' => $qty_total,
        'net_value' => 0.0,
    ];

    $uid = (int)$ar['user_id'];
    if ($uid) {
        $u = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
        $u->execute([$uid]);
        $row['user_name'] = $u->fetchColumn() ?: null;
    }

    $rows[] = $row;

    $itemsList = [];
    foreach ($items as $it) {
        $pid = isset($it['product_id']) ? (int)$it['product_id'] : 0;
        $qty = isset($it['delta']) ? (float)$it['delta'] : 0;
        $code = ''; $name = '';
        if ($pid) {
            $p = $pdo->prepare("SELECT code, name FROM products WHERE id = ? LIMIT 1");
            $p->execute([$pid]);
            $pr = $p->fetch(PDO::FETCH_ASSOC);
            if ($pr) { $code = $pr['code']; $name = $pr['name']; }
        }
        $itemsList[] = [
            'transaction_id' => $pseudoId,
            'code' => $code,
            'name' => $name,
            'qty' => $qty,
            'unit_price' => 0,
            'discount_type' => null,
            'discount_value' => null
        ];
    }
    $itemsByTx[$pseudoId] = $itemsList;
}

usort($rows, function($a, $b){
    $ta = strtotime($a['created_at'] ?? $a['date'] ?? '1970-01-01 00:00:00');
    $tb = strtotime($b['created_at'] ?? $b['date'] ?? '1970-01-01 00:00:00');
    return $tb <=> $ta;
});


/* helpers */
function line_net_php($qty,$unit,$dt,$dv){
  $gross = $qty*$unit;
  if ($dt==='percent' && $dv!==null && $dv!=='') { $gross -= $gross * (max(0,min(100,(float)$dv))/100); }
  elseif ($dt==='amount' && $dv!==null && $dv!=='') { $gross -= max(0,(float)$dv); }
  return max(0,$gross);
}

/* ---------- summary (purchases, sales, adjustments, refunds) ---------- */
$sumPurchasesValue = 0.0; $cntPurchases = 0; $qtyPurchases = 0;
$sumSalesValue     = 0.0; $cntSales     = 0; $qtySales     = 0;
$cntAdjust = 0; $qtyAdjust = 0.0;
$cntRefund = 0; $qtyRefund = 0.0;

foreach ($rows as $r) {
  $rt = ($r['type'] ?? '');
  if ($rt === 'purchase') {
    $cntPurchases++;
    $qtyPurchases += (int)$r['qty_total'];
    $sumPurchasesValue += (float)$r['net_value'];
  } elseif ($rt === 'sale') {
    $cntSales++;
    $qtySales += (int)$r['qty_total'];
    $sumSalesValue += (float)$r['net_value'];
  } elseif ($rt === 'refund') {
    $cntRefund++;
    $qtyRefund += (float)$r['qty_total'];
    // if refunds stored as negative net_value, abs() keeps logic consistent
    $sumSalesValue -= abs((float)$r['net_value']);
    // subtract refunded qty from sales count for net movement
    $qtySales -= (int)$r['qty_total'];
  } elseif ($rt === 'adjust') {
    $cntAdjust++;
    $qtyAdjust += (float)$r['qty_total'];
  }
}
$netQtyMovement = ($qtyPurchases + $qtyAdjust) - $qtySales;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">All Movements</h4>

  <!-- compact summary -->
  <div class="text-end small">
    <div>
      <span class="badge bg-success">Purchases</span>
      <span class="ms-1"><?= $cntPurchases ?> tx • <?= (int)$qtyPurchases ?> qty • ₱<?= number_format($sumPurchasesValue,2) ?></span>
    </div>
    <div class="mt-1">
      <span class="badge bg-warning">Adjustments</span>
      <span class="ms-1"><?= $cntAdjust ?> tx • <?= (int)$qtyAdjust ?> qty</span>
    </div>
    <div class="mt-1">
      <span class="badge bg-secondary">Refunds</span>
      <span class="ms-1"><?= $cntRefund ?> tx • <?= (int)$qtyRefund ?> qty</span>
    </div>
    <div class="mt-1">
      <span class="badge bg-danger">Sales</span>
      <span class="ms-1"><?= $cntSales ?> tx • <?= (int)$qtySales ?> qty • ₱<?= number_format($sumSalesValue,2) ?></span>
    </div>
    <div class="mt-1 text-muted">
      Net stock movement: <strong><?= ($netQtyMovement>=0?'+':'').(int)$netQtyMovement ?></strong> units
    </div>
  </div>
</div>

<form class="row g-2 mb-3">
  <div class="col-md-2">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">Type</label>
    <select class="form-select" name="type">
      <option value="">All</option>
      <option value="purchase" <?= $type==='purchase'?'selected':'' ?>>Purchase (IN)</option>
      <option value="sale"     <?= $type==='sale'?'selected':'' ?>>Sale (OUT)</option>
      <!--<option value="refund"   <?= $type==='refund'?'selected':'' ?>>Refund</option>-->
      <option value="adjust"   <?= $type==='adjust'?'selected':'' ?>>Adjust (Stock Transfer)</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Search</label>
    <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Ref, notes, supplier/customer">
  </div>
  <div class="col-md-2 align-self-end">
    <button class="btn btn-outline-secondary w-100">Filter</button>
  </div>
</form>

<table class="table table-striped table-sm align-middle">
  <thead>
    <tr>
      <th>Date/Time</th>
      <th>Type</th>
      <th>Ref</th>
      <th>Customer</th>
      <!--<th>By</th>-->
      <th class="text-end">Lines</th>
      <th class="text-end">Qty</th>
      <th class="text-end">Net Value</th>
      <th style="width:36px;"></th>
    </tr>
  </thead>
  <tbody>
  <?php
// Filter rows based on selected type
if ($type === 'adjust') {
    $rows = array_filter($rows, fn($r) => ($r['type'] ?? '') === 'adjust');
} elseif ($type === 'purchase') {
    $rows = array_filter($rows, fn($r) => ($r['type'] ?? '') === 'purchase');
} elseif ($type === 'sale') {
    $rows = array_filter($rows, fn($r) => ($r['type'] ?? '') === 'sale');
} elseif ($type === 'refund') {
    $rows = array_filter($rows, fn($r) => ($r['type'] ?? '') === 'refund');
}
?>
  <?php if (!$rows): ?>
    <tr><td colspan="9" class="text-muted">No movements for this filter.</td></tr>
  <?php else: ?>
    <?php
$currentUser = $_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Admin';

foreach ($rows as $t):
    $dtRaw  = $t['created_at'] ?: $t['date'];
    $dtDisp = $dtRaw ? date('Y-m-d H:i', strtotime($dtRaw)) : '';

    $type = (string)($t['type'] ?? '');

    // partner / customer
    if ($type === 'purchase') {
        $partner = $t['supplier_name'] ?? '— walk-in —';
    } elseif ($type === 'sale') {
        $partner = $t['customer_name'] ?? '— walk-in —';
    } elseif ($type === 'refund') {
        // refunds relate to customers: prefer recorded customer_name
        $partner = $t['customer_name'] ?? '— walk-in —';
    } elseif ($type === 'adjust') {
        $partner = '— walk-in —';
    } else {
        $partner = '—';
    }

    // row class and badge
    if ($type === 'purchase') {
        $rowClass = 'table-success';
        $badge = '<span class="badge bg-success">IN</span>';
    } elseif ($type === 'sale') {
        $rowClass = 'table-danger';
        $badge = '<span class="badge bg-danger">OUT</span>';
    } elseif ($type === 'refund') {
        $rowClass = 'table-secondary';
        $badge = '<span class="badge bg-secondary text-light">REF</span>';
    } else {
        $rowClass = 'table-warning';
        $badge = '<span class="badge bg-warning text-dark">ADJ</span>';
    }

    // By column: prefer recorded user_name, but for adjustments or missing names fall back to current user
    $byName = $t['user_name'] ?? null;
    if ($type === 'adjust' || $type === 'refund') {
        $by = $byName ? $byName : $currentUser;
    } else {
        $by = $byName ?? '—';
    }

    // hide monetary net value for adjustments
    $showValue = ($type !== 'adjust');
    $txItems = $itemsByTx[$t['transaction_id']] ?? [];
?>
  <tr class="<?= $rowClass ?>">
    <td><?= h($dtDisp) ?></td>
    <td><?= $badge ?> <span class="ms-1"><?= h(ucfirst($type)) ?></span></td>
    <td><?= h($t['ref_no']) ?></td>
    <td><?= h($partner) ?></td>
    <!--<td><?= h($by) ?></td>-->
    <td class="text-end"><?= (int)$t['item_count'] ?></td>
    <td class="text-end"><?= (int)$t['qty_total'] ?></td>
    <td class="text-end"><?= $showValue ? '₱'.number_format((float)$t['net_value'], 2) : '—' ?></td>
    <td class="text-end">
  <?php if ($txItems): ?>
    <button class="btn btn-sm btn-outline-secondary" type="button"
        data-bs-toggle="collapse"
        data-bs-target="#tx<?= h($t['transaction_id']) ?>">
    View
</button>
  <?php endif; ?>

  <?php if (($t['type'] ?? '') === 'sale'): ?>

<a href="/deliveries/create.php?transaction_id=<?php echo (int)$t['transaction_id']; ?>"
   class="btn btn-sm btn-outline-info ms-1">
   Deliver
</a>

<a href="refund.php?sale_id=<?= (int)$t['transaction_id'] ?>"
   class="btn btn-sm btn-outline-danger ms-1"
   onclick="return confirm('Refund this sale?')">
Refund
</a>

<?php endif; ?>
</td>
<?php if ($t['type'] === 'sale'): ?>


<?php endif; ?>
  </tr>

  <?php if ($txItems): ?>
    <tr class="collapse" id="tx<?= h($t['transaction_id']) ?>">
      <td colspan="9" class="p-0">
        <table class="table table-sm mb-0">
          <thead>
            <?php if ($type === 'adjust'): ?>
              <tr>
                <th style="width:18%">Code</th>
                <th>Item</th>
                <th class="text-end" style="width:10%">Qty</th>
              </tr>
            <?php else: ?>
              <tr>
                <th style="width:18%">Code</th>
                <th>Item</th>
                <th class="text-end" style="width:10%">Qty</th>
                <th class="text-end" style="width:12%">Unit</th>
                <th class="text-end" style="width:12%">Discount</th>
                <th class="text-end" style="width:12%">Line Total</th>
              </tr>
            <?php endif; ?>
          </thead>
          <tbody>
            <?php foreach ($txItems as $li): ?>
              <?php if ($type === 'adjust'): ?>
                <tr>
                  <td><?= h($li['code']) ?></td>
                  <td><?= h($li['name']) ?></td>
                  <td class="text-end"><?= (int)$li['qty'] ?></td>
                </tr>
              <?php else:
                $discLabel = '';
                if (($li['discount_type'] ?? '') === 'percent' && ($li['discount_value'] ?? '') !== '') {
                  $discLabel = number_format((float)$li['discount_value'],2).' %';
                } elseif (($li['discount_type'] ?? '') === 'amount' && ($li['discount_value'] ?? '') !== '') {
                  $discLabel = '₱'.number_format((float)$li['discount_value'],2);
                }
                $ln = line_net_php((int)$li['qty'], (float)$li['unit_price'], $li['discount_type'] ?? null, $li['discount_value'] ?? null);
              ?>
                <tr>
                  <td><?= h($li['code']) ?></td>
                  <td><?= h($li['name']) ?></td>
                  <td class="text-end"><?= (int)$li['qty'] ?></td>
                  <td class="text-end"><?= $li['unit_price'] ? '₱'.number_format((float)$li['unit_price'], 2) : '—' ?></td>
                  <td class="text-end"><?= $discLabel ?: '—' ?></td>
                  <td class="text-end">₱<?= number_format($ln, 2) ?></td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </td>
    </tr>
  <?php endif; ?>

<?php endforeach; ?>

  <?php endif; ?>
  </tbody>
</table>

<?php require_once __DIR__.'/includes/footer.php'; ?>
