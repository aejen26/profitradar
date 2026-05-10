<?php
require_once __DIR__ . '/../includes/header.php';
require_role(['admin','staff','auditor']);
$pdo = getDB();

/* ---------- Filters ---------- */

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$q    = trim($_GET['q'] ?? '');

$whereProd = "p.is_active = 1";

$paramsOpen = [];
$paramsPeriod = [];

/* Product search */

if ($q !== '') {

  $whereProd .= " AND (p.code LIKE ? OR p.name LIKE ?)";

  $like = '%'.$q.'%';

  $paramsOpen[] = $like;
  $paramsOpen[] = $like;

  $paramsPeriod[] = $like;
  $paramsPeriod[] = $like;

}

/* ---------- Opening balance ---------- */

$sqlOpening = "

  SELECT

    p.id,

    COALESCE(SUM(

      CASE t.type

        WHEN 'purchase' THEN ti.qty

        WHEN 'sale' THEN -ti.qty

        ELSE 0

      END

    ),0) AS opening_qty

  FROM products p

  LEFT JOIN transaction_items ti ON ti.product_id = p.id

  LEFT JOIN transactions t ON t.id = ti.transaction_id
        AND t.type IN ('purchase','sale')
        AND t.date < ?

  WHERE $whereProd

  GROUP BY p.id

";

$stOpen = $pdo->prepare($sqlOpening);

$stOpen->execute(array_merge([$from], $paramsOpen));

$openingById = [];

foreach ($stOpen as $r) {
    $openingById[(int)$r['id']] = (int)$r['opening_qty'];
}

/* ---------- Period movement ---------- */

$sqlPeriod = "

SELECT

  p.id, p.code, p.name,

  SUM(CASE WHEN t.type='purchase' THEN ti.qty ELSE 0 END) AS in_qty,

  SUM(CASE WHEN t.type='purchase' THEN ti.qty*ti.unit_price ELSE 0 END) AS in_value,

  SUM(CASE WHEN t.type='sale' THEN ti.qty ELSE 0 END) AS out_qty,

  SUM(CASE WHEN t.type='sale' THEN ti.qty*ti.unit_price ELSE 0 END) AS out_value

FROM products p

LEFT JOIN transaction_items ti ON ti.product_id = p.id

LEFT JOIN transactions t ON t.id = ti.transaction_id
      AND t.type IN ('purchase','sale')
      AND t.date BETWEEN ? AND ?

WHERE $whereProd

GROUP BY p.id,p.code,p.name

ORDER BY p.name

";

$stPeriod = $pdo->prepare($sqlPeriod);

$stPeriod->execute(array_merge([$from,$to], $paramsPeriod));

$rows = $stPeriod->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Reservations ---------- */

$reservedById = [];

$res = $pdo->query("

SELECT product_id, SUM(quantity) AS reserved_qty
FROM reservations
WHERE status='reserved'
GROUP BY product_id

");

foreach ($res as $r) {

  $reservedById[(int)$r['product_id']] = (int)$r['reserved_qty'];

}

/* ---------- Totals ---------- */

$totalInQty = 0;
$totalOutQty = 0;

$totalInVal = 0;
$totalOutVal = 0;

/* ---------- Calculations ---------- */

foreach ($rows as &$r) {

  $pid = (int)$r['id'];

  $open = $openingById[$pid] ?? 0;

  $inQ = (int)$r['in_qty'];

  $outQ = (int)$r['out_qty'];

  $r['opening_qty'] = $open;

  $r['closing_qty'] = $open + $inQ - $outQ;

  $r['net_qty'] = $inQ - $outQ;

  $r['reserved_qty'] = $reservedById[$pid] ?? 0;

  $r['available_qty'] = $r['closing_qty'] - $r['reserved_qty'];

  $totalInQty += $inQ;
  $totalOutQty += $outQ;

  $totalInVal += (float)$r['in_value'];
  $totalOutVal += (float)$r['out_value'];

}

unset($r);

?>

<div class="d-flex justify-content-between align-items-center mb-3">

<h4 class="mb-0">Stock Movement</h4>

<div class="text-end small">

<div>

<span class="badge bg-success">IN</span>

<?= $totalInQty ?> qty • ₱<?= number_format($totalInVal,2) ?>

</div>

<div class="mt-1">

<span class="badge bg-danger">OUT</span>

<?= $totalOutQty ?> qty • ₱<?= number_format($totalOutVal,2) ?>

</div>

<div class="mt-1 text-muted">

Net movement:

<strong><?= ($totalInQty-$totalOutQty>=0?'+':'').($totalInQty-$totalOutQty) ?></strong>

</div>

</div>

</div>

<form class="row g-2 mb-3">

<div class="col-md-3">

<label class="form-label">From</label>

<input type="date" class="form-control" name="from" value="<?=h($from)?>">

</div>

<div class="col-md-3">

<label class="form-label">To</label>

<input type="date" class="form-control" name="to" value="<?=h($to)?>">

</div>

<div class="col-md-4">

<label class="form-label">Product</label>

<input class="form-control" name="q" value="<?=h($q)?>" placeholder="Search code or name">

</div>

<div class="col-md-2 align-self-end">

<button class="btn btn-outline-secondary w-100">Filter</button>

</div>

</form>

<table class="table table-striped table-sm align-middle">

<thead>

<tr>

<th>Code</th>
<th>Product</th>

<th class="text-end">Opening</th>

<th class="text-end">IN</th>
<th class="text-end">IN Value</th>

<th class="text-end">OUT</th>
<th class="text-end">OUT Value</th>

<th class="text-end">Net</th>

<th class="text-end">Closing</th>

<th class="text-end">Reserved</th>

<th class="text-end">Available</th>

</tr>

</thead>

<tbody>

<?php if(!$rows): ?>

<tr>

<td colspan="11" class="text-muted">No movement found</td>

</tr>

<?php else: ?>

<?php foreach($rows as $r): ?>

<tr>

<td><?=h($r['code'])?></td>

<td><?=h($r['name'])?></td>

<td class="text-end"><?= $r['opening_qty'] ?></td>

<td class="text-end text-success"><?= $r['in_qty'] ?></td>

<td class="text-end text-success">₱<?= number_format($r['in_value'],2) ?></td>

<td class="text-end text-danger"><?= $r['out_qty'] ?></td>

<td class="text-end text-danger">₱<?= number_format($r['out_value'],2) ?></td>

<td class="text-end <?= $r['net_qty']>=0?'text-success':'text-danger' ?>">

<?= $r['net_qty'] ?>

</td>

<td class="text-end"><?= $r['closing_qty'] ?></td>

<td class="text-end text-warning"><?= $r['reserved_qty'] ?></td>

<td class="text-end fw-bold"><?= $r['available_qty'] ?></td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>