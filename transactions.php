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
    t.notes,
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
  GROUP BY 
t.id,
t.type,
t.ref_no,
t.date,
t.notes,
t.created_at,
u.name,
s.name,
c.name
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

    $txType = (string)($t['type'] ?? '');

    /* partner/customer/reason */
    if ($txType === 'purchase') {

        $partner = $t['supplier_name'] ?? '— walk-in —';

    } elseif ($txType === 'sale') {

        $partner = $t['customer_name'] ?? '— walk-in —';

    } elseif ($txType === 'refund') {

        $partner = $t['customer_name'] ?? '— walk-in —';

    } elseif ($txType === 'adjust') {

    $partner = !empty(trim($t['notes'] ?? ''))
        ? trim($t['notes'])
        : (
            !empty(trim($t['ref_no'] ?? ''))
                ? trim($t['ref_no'])
                : 'Stock adjusted'
        );
}

    /* row colors + badges */
    if ($txType === 'purchase') {

        $rowClass = 'table-success';
        $badge = '<span class="badge bg-success">IN</span>';

    } elseif ($txType === 'sale') {

        $rowClass = 'table-danger';
        $badge = '<span class="badge bg-danger">OUT</span>';

    } elseif ($txType === 'refund') {

        $rowClass = 'table-secondary';
        $badge = '<span class="badge bg-secondary text-light">REF</span>';

    } else {

        $rowClass = 'table-warning';
        $badge = '<span class="badge bg-warning text-dark">ADJ</span>';
    }

    /* user */
    $byName = $t['user_name'] ?? null;

    if ($txType === 'adjust' || $txType === 'refund') {

        $by = $byName ? $byName : $currentUser;

    } else {

        $by = $byName ?? '—';
    }

    $showValue = ($txType !== 'adjust');

    $txId = $t['transaction_id'] ?? $t['id'] ?? null;

    $txItems = $itemsByTx[$txId] ?? [];
?>

<tr class="<?= $rowClass ?>">

    <td><?= h($dtDisp) ?></td>

    <td>
        <?= $badge ?>
        <span class="ms-1"><?= h(ucfirst($txType)) ?></span>
    </td>

    <td><?= h($t['ref_no']) ?></td>

    <td>

<?php if ($txType === 'adjust'): ?>

    <?= h($t['notes'] ?: 'Stock adjusted') ?>

<?php else: ?>

    <?= h($partner) ?>

<?php endif; ?>

</td>
    <!--<td><?= h($by) ?></td>-->

    <td class="text-end">
        <?= (int)$t['item_count'] ?>
    </td>

    <td class="text-end">
        <?= (int)$t['qty_total'] ?>
    </td>

    <td class="text-end">
        <?= $showValue
            ? '₱'.number_format((float)$t['net_value'], 2)
            : '—' ?>
    </td>

    <td class="text-end">

        <?php if ($txItems): ?>

            <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#tx<?= h($txId) ?>">
                View
            </button>

        <?php endif; ?>

        <?php if ($txType === 'sale'): ?>

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

</tr>

<?php if ($txItems): ?>

<tr class="collapse" id="tx<?= h($txId) ?>">

    <td colspan="9" class="p-0">

        <table class="table table-sm mb-0">

            <thead>

                <tr>

                    <th style="width:18%">Code</th>

                    <th>Item</th>

                    <th class="text-end" style="width:10%">Qty</th>

                    <?php if ($txType !== 'adjust'): ?>

                        <th class="text-end" style="width:12%">Unit</th>

                        <th class="text-end" style="width:12%">Discount</th>

                        <th class="text-end" style="width:12%">Line Total</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($txItems as $li): ?>

                <?php
                $discLabel = '';

                if (($li['discount_type'] ?? '') === 'percent'
                    && ($li['discount_value'] ?? '') !== '') {

                    $discLabel =
                        number_format((float)$li['discount_value'],2).' %';

                } elseif (($li['discount_type'] ?? '') === 'amount'
                    && ($li['discount_value'] ?? '') !== '') {

                    $discLabel =
                        '₱'.number_format((float)$li['discount_value'],2);
                }

                $ln = line_net_php(
                    (int)$li['qty'],
                    (float)$li['unit_price'],
                    $li['discount_type'] ?? null,
                    $li['discount_value'] ?? null
                );
                ?>

                <tr>

                    <td><?= h($li['code']) ?></td>

                    <td><?= h($li['name']) ?></td>

                    <td class="text-end">
                        <?= (int)$li['qty'] ?>
                    </td>

                    <?php if ($txType !== 'adjust'): ?>

                        <td class="text-end">
                            <?= $li['unit_price']
                                ? '₱'.number_format((float)$li['unit_price'], 2)
                                : '—' ?>
                        </td>

                        <td class="text-end">
                            <?= $discLabel ?: '—' ?>
                        </td>

                        <td class="text-end">
                            ₱<?= number_format($ln, 2) ?>
                        </td>

                    <?php endif; ?>

                </tr>

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
