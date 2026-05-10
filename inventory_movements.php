<?php
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff','auditor']);

$pdo = getDB();

/* Initialize filter variables */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');
$q    = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';

$where = [];
$params = [];

$where[] = "m.created_at BETWEEN ? AND ?";
$params[] = $from . " 00:00:00";
$params[] = $to . " 23:59:59";

if ($q !== '') {
$where[] = "(p.name LIKE ? OR p.code LIKE ?)";
$like = "%$q%";
$params[] = $like;
$params[] = $like;
}

if ($type !== '') {
$where[] = "m.movement_type = ?";
$params[] = $type;
}

$whereSql = implode(" AND ", $where);

$sql = "
SELECT
m.created_at,
p.code,
p.name,
m.movement_type,
m.quantity,
m.stock_after,
m.source,
m.note
FROM inventory_movements m
JOIN products p ON p.id = m.product_id
WHERE $whereSql
ORDER BY m.created_at DESC
LIMIT 200
";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Header -->
<div class="mb-4">
<h4 class="fw-semibold mb-0">Inventory Movement History</h4>
<small class="text-muted">Track all stock changes and inventory transactions</small>
</div>


<!-- Filter Card -->

<div class="card shadow-sm mb-4">

<div class="card-body">

<form class="row g-3">

<div class="col-md-2">
<label class="form-label">From</label>
<input type="date" class="form-control" name="from" value="<?= h($from) ?>">
</div>

<div class="col-md-2">
<label class="form-label">To</label>
<input type="date" class="form-control" name="to" value="<?= h($to) ?>">
</div>

<div class="col-md-3">
<label class="form-label">Product</label>
<input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Code or name">
</div>

<div class="col-md-3">
<label class="form-label">Movement Type</label>
<select class="form-select" name="type">

<option value="">All</option>

<option value="purchase" <?= $type==='purchase'?'selected':'' ?>>
Purchase
</option>

<option value="sale" <?= $type==='sale'?'selected':'' ?>>
Sale
</option>

<option value="refund" <?= $type==='refund'?'selected':'' ?>>
Refund
</option>

</select>
</div>

<div class="col-md-2 d-grid align-self-end">
<button class="btn btn-outline-secondary">
Filter
</button>
</div>

</form>

</div>

</div>


<!-- Movement Table -->

<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-hover table-sm align-middle mb-0">

<thead class="table-light">

<tr>
<th style="width:160px">Time</th>
<th style="width:120px">Code</th>
<th>Product</th>
<th style="width:140px">Type</th>
<th class="text-end" style="width:120px">Qty</th>
<th class="text-end" style="width:140px">Stock After</th>
<th>Source</th>
</tr>

</thead>

<tbody>

<?php if (!$rows): ?>

<tr>
<td colspan="7" class="text-center text-muted py-4">
No movement records found.
</td>
</tr>

<?php else: ?>

<?php foreach($rows as $r): ?>

<tr>

<td>
<?= date('Y-m-d H:i', strtotime($r['created_at'])) ?>
</td>

<td class="fw-semibold">
<?= h($r['code']) ?>
</td>

<td>
<?= h($r['name']) ?>
</td>

<td>

<?php
$typeLabel = strtolower($r['movement_type']);

if ($typeLabel === 'purchase') {
echo '<span class="badge bg-success">Purchase</span>';
}
elseif ($typeLabel === 'sale') {
echo '<span class="badge bg-danger">Sale</span>';
}
elseif ($typeLabel === 'refund') {
echo '<span class="badge bg-info text-dark">Refund</span>';
}
else {
echo '<span class="badge bg-secondary">'.h(ucfirst($typeLabel)).'</span>';
}
?>

</td>

<td class="text-end <?= $r['quantity'] >= 0 ? 'text-success' : 'text-danger' ?>">

<?= $r['quantity'] >= 0 ? '+' . $r['quantity'] : $r['quantity'] ?>

</td>

<td class="text-end fw-semibold">

<?= $r['stock_after'] ?>

</td>

<td>
<?= h($r['source']) ?>
</td>

</tr>

<?php endforeach ?>

<?php endif ?>

</tbody>

</table>

</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>