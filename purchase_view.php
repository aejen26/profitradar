<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$po = $pdo->prepare("
SELECT po.*, s.name AS supplier_name
FROM purchase_orders po
LEFT JOIN suppliers s ON s.id = po.supplier_id
WHERE po.id = ?
");
$po->execute([$id]);
$po = $po->fetch();

if (!$po) die("PO not found");

$items = $pdo->prepare("
SELECT poi.*, p.name
FROM purchase_order_items poi
JOIN products p ON p.id = poi.product_id
WHERE purchase_order_id = ?
");
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="mb-4">
<h4 class="fw-semibold mb-0">Purchase Order #<?= $id ?></h4>
<small class="text-muted">View received and remaining items</small>
</div>


<!-- PO Information -->
<div class="card shadow-sm mb-4">

<div class="card-header">
<strong>Purchase Order Details</strong>
</div>

<div class="card-body">

<div class="row">

<div class="col-md-3">
<strong>Date</strong><br>
<?= h($po['order_date']) ?>
</div>

<div class="col-md-3">
<strong>Supplier</strong><br>
<?= h($po['supplier_name'] ?? '—') ?>
</div>

<div class="col-md-3">
<strong>Status</strong><br>

<?php
$status = strtolower(trim($po['status'] ?? ''));

switch ($status) {

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

default:
echo '<span class="badge bg-secondary">'.h($po['status']).'</span>';
}
?>

</div>

<div class="col-md-3">
<strong>Total</strong><br>
<span class="fw-semibold">
₱<?= number_format($po['total_amount'], 2) ?>
</span>
</div>

</div>

</div>

</div>



<!-- Items Table -->
<div class="card shadow-sm">

<div class="card-header">
<strong>Order Items</strong>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>
<th>Product</th>
<th class="text-center">Ordered</th>
<th class="text-center">Received</th>
<th class="text-center">Remaining</th>
<th class="text-end">Unit Price</th>
<th class="text-end">Line Total</th>
</tr>

</thead>

<tbody>

<?php if (!$items): ?>

<tr>
<td colspan="6" class="text-center text-muted py-4">
No items found.
</td>
</tr>

<?php else: ?>

<?php foreach ($items as $it):

$remaining = $it['qty'] - $it['received_qty'];
?>

<tr>

<td class="fw-semibold">
<?= h($it['name']) ?>
</td>

<td class="text-center">
<?= $it['qty'] ?>
</td>

<td class="text-center">
<?= $it['received_qty'] ?>
</td>

<td class="text-center">

<?php if ($remaining > 0): ?>

<span class="badge bg-warning text-dark">
<?= $remaining ?>
</span>

<?php else: ?>

<span class="badge bg-success">
0
</span>

<?php endif; ?>

</td>

<td class="text-end">
₱<?= number_format($it['unit_price'], 2) ?>
</td>

<td class="text-end fw-semibold">
₱<?= number_format($it['qty'] * $it['unit_price'], 2) ?>
</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


<div class="mt-3">
<a href="purchase_orders.php" class="btn btn-outline-secondary">
Back
</a>
</div>

<?php require_once __DIR__.'/includes/footer.php'; ?>