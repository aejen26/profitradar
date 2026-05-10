<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();

$supplierSummary = $pdo->query("
SELECT 
    s.name AS supplier_name,
    COUNT(po.id) AS total_orders,
    SUM(po.total_amount) AS total_spent
FROM purchase_orders po
JOIN suppliers s ON s.id = po.supplier_id
WHERE po.status = 'received'
GROUP BY po.supplier_id
ORDER BY total_spent DESC
")->fetchAll(PDO::FETCH_ASSOC);

$rows = $pdo->query("
SELECT po.*, s.name AS supplier_name
FROM purchase_orders po
LEFT JOIN suppliers s ON s.id = po.supplier_id
ORDER BY po.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h4 class="fw-semibold mb-0">View Purchase Orders</h4>
<small class="text-muted">Manage supplier purchase orders</small>
</div>

<div class="d-flex gap-2">
<a href="purchase_order_add.php" class="btn btn-primary">
+ New PO
</a>
</div>

</div>



<?php if ($supplierSummary): ?>

<div class="card shadow-sm mb-4">

<div class="card-header">
<strong>Supplier Purchase Summary</strong>
</div>

<div class="table-responsive">

<table class="table table-hover table-sm mb-0">

<thead class="table-light">
<tr>
<th>Supplier</th>
<th>Total Orders</th>
<th>Total Purchased</th>
</tr>
</thead>

<tbody>

<?php foreach ($supplierSummary as $s): ?>

<tr>

<td class="fw-semibold">
<?= h($s['supplier_name']) ?>
</td>

<td>
<?= $s['total_orders'] ?>
</td>

<td>
₱<?= number_format($s['total_spent'], 2) ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>



<!-- Purchase Orders Table -->
<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>
<th style="width:80px">#</th>
<th style="width:140px">Date</th>
<th>Supplier</th>
<th style="width:180px">Status</th>
<th style="width:140px">Total</th>
<th class="text-end" style="width:240px">Actions</th>
</tr>

</thead>

<tbody>

<?php if (!$rows): ?>

<tr>
<td colspan="6" class="text-center text-muted py-4">
No purchase orders yet.
</td>
</tr>

<?php else: ?>

<?php foreach ($rows as $r): ?>

<tr>

<td class="fw-semibold">
<?= $r['id'] ?>
</td>

<td>
<?= h($r['order_date']) ?>
</td>

<td>
<?= h($r['supplier_name'] ?? '—') ?>
</td>


<td>

<?php
switch ($r['status']) {

case 'draft':
echo '<span class="badge bg-secondary">Draft</span>';
break;

case 'ordered':
echo '<span class="badge bg-warning text-dark">Ordered</span>';
break;

case 'partially_received':
echo '<span class="badge bg-info text-dark">Partially Received</span>';
break;

case 'received':
echo '<span class="badge bg-success">Received</span>';
break;

case 'cancelled':
echo '<span class="badge bg-danger">Cancelled</span>';
break;

default:
echo '<span class="badge bg-secondary">'.h($r['status']).'</span>';
}
?>

</td>


<td class="fw-semibold">
₱<?= number_format($r['total_amount'], 2) ?>
</td>


<td class="text-end">

<a
href="purchase_view.php?id=<?= $r['id'] ?>"
class="btn btn-sm btn-primary">

View

</a>


<?php if ($r['status'] === 'draft'): ?>

<a
href="purchase_confirm.php?id=<?= $r['id'] ?>"
class="btn btn-sm btn-warning">

Confirm

</a>

<?php elseif (in_array($r['status'], ['ordered','partially_received'])): ?>

<a
href="purchase_receive.php?id=<?= $r['id'] ?>"
class="btn btn-sm btn-success">

Receive

</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php require_once __DIR__.'/includes/footer.php'; ?>