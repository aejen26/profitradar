<?php
require_once __DIR__.'/../includes/header.php';
require_role(['admin','staff']);

$pdo = getDB();

/* Filters */

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "c.name LIKE ?";
    $params[] = "%$q%";
}

if ($statusFilter !== '') {
    $where[] = "d.status = ?";
    $params[] = $statusFilter;
}

$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

/* Fetch deliveries */

$stmt = $pdo->prepare("
SELECT 
d.id,
d.delivery_date,
d.status,
c.name AS customer
FROM deliveries d
LEFT JOIN customers c ON c.id = d.customer_id
$whereSql
ORDER BY d.delivery_date DESC
");

$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Stats */

$total = count($rows);
$pending = 0;
$partial = 0;
$delivered = 0;

foreach ($rows as $r){

if($r['status']=='pending') $pending++;
if($r['status']=='partial') $partial++;
if($r['status']=='delivered') $delivered++;

}
?>

<div class="mb-4">
<h4 class="fw-semibold mb-0">Deliveries</h4>
<small class="text-muted">Manage product deliveries and shipment status</small>
</div>


<!-- Stats Cards -->

<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Total Deliveries</div>
<div class="fs-5 fw-semibold"><?= $total ?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Pending</div>
<div class="fs-5 text-warning fw-semibold"><?= $pending ?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Partial</div>
<div class="fs-5 text-info fw-semibold"><?= $partial ?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Delivered</div>
<div class="fs-5 text-success fw-semibold"><?= $delivered ?></div>
</div>
</div>
</div>

</div>



<!-- Filters -->

<div class="card shadow-sm mb-3">
<div class="card-body">

<form class="row g-3">

<div class="col-md-4">

<input
class="form-control"
name="q"
value="<?= h($q) ?>"
placeholder="Search customer">

</div>

<div class="col-md-3">

<select class="form-select" name="status">

<option value="">All Status</option>

<option value="pending" <?= $statusFilter=='pending'?'selected':'' ?>>
Pending
</option>

<option value="partial" <?= $statusFilter=='partial'?'selected':'' ?>>
Partial
</option>

<option value="delivered" <?= $statusFilter=='delivered'?'selected':'' ?>>
Delivered
</option>

</select>

</div>

<div class="col-md-2 d-grid">

<button class="btn btn-outline-secondary">
Filter
</button>

</div>

</form>

</div>
</div>



<!-- Deliveries Table -->

<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>
<th style="width:80px">#</th>
<th>Customer</th>
<th style="width:150px">Date</th>
<th style="width:140px">Status</th>
<th class="text-end" style="width:150px">Action</th>
</tr>

</thead>

<tbody>

<?php if (!$rows): ?>

<tr>
<td colspan="6" class="text-center text-muted py-4">
No deliveries found.
</td>
</tr>

<?php else: ?>

<?php foreach ($rows as $r): ?>

<tr>

<td class="fw-semibold">
<?= $r['id'] ?>
</td>


<td>
<?= h($r['customer'] ?? 'Walk-in') ?>
</td>

<td>
<?= h($r['delivery_date']) ?>
</td>

<td>

<?php

$status = strtolower(trim($r['status'] ?? ''));

switch ($status){

case 'pending':
echo '<span class="badge bg-warning text-dark">Pending</span>';
break;

case 'partial':
echo '<span class="badge bg-info text-dark">Partial</span>';
break;

case 'delivered':
echo '<span class="badge bg-success">Delivered</span>';
break;

default:
echo '<span class="badge bg-secondary">Unknown</span>';

}

?>

</td>

<td class="text-end">

<a
href="view.php?id=<?= $r['id'] ?>"
class="btn btn-sm btn-primary">

View

</a>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php require_once __DIR__.'/../footer.php'; ?>