<?php
require_once __DIR__.'/includes/header.php';
require_login();
$pdo = getDB();

$expired = $pdo->query("
    SELECT pb.*, p.name 
    FROM product_batches pb
    JOIN products p ON pb.product_id = p.id
    WHERE pb.expiry_date < CURDATE()
    AND pb.remaining_qty > 0
")->fetchAll(PDO::FETCH_ASSOC);

$expiring = $pdo->query("
    SELECT pb.*, p.name 
    FROM product_batches pb
    JOIN products p ON pb.product_id = p.id
    WHERE pb.expiry_date BETWEEN CURDATE() 
    AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND pb.remaining_qty > 0
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="mb-4">
<h4 class="fw-semibold mb-0">Expiry Monitoring</h4>
<small class="text-muted">Track expired and soon-to-expire product batches</small>
</div>



<!-- Expired Items -->
<div class="card shadow-sm mb-4 border-danger">

<div class="card-header bg-danger bg-opacity-10">
<strong class="text-danger">Expired Items</strong>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>Product</th>
<th>Batch</th>
<th>Remaining Qty</th>
<th>Expiry Date</th>
</tr>
</thead>

<tbody>

<?php if (!$expired): ?>

<tr>
<td colspan="4" class="text-center text-muted py-4">
No expired items.
</td>
</tr>

<?php else: ?>

<?php foreach ($expired as $e): ?>

<tr class="table-danger">

<td class="fw-semibold">
<?= h($e['name']) ?>
</td>

<td>
<?= h($e['batch_no']) ?>
</td>

<td>
<?= number_format($e['remaining_qty']) ?>
</td>

<td class="text-danger fw-semibold">
<?= h($e['expiry_date']) ?>
</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>
</div>



<!-- Expiring Soon -->
<div class="card shadow-sm border-warning">

<div class="card-header bg-warning bg-opacity-10">
<strong class="text-warning">Expiring Within 30 Days</strong>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>Product</th>
<th>Batch</th>
<th>Remaining Qty</th>
<th>Expiry Date</th>
</tr>
</thead>

<tbody>

<?php if (!$expiring): ?>

<tr>
<td colspan="4" class="text-center text-muted py-4">
No products expiring soon.
</td>
</tr>

<?php else: ?>

<?php foreach ($expiring as $e): ?>

<tr class="table-warning">

<td class="fw-semibold">
<?= h($e['name']) ?>
</td>

<td>
<?= h($e['batch_no']) ?>
</td>

<td>
<?= number_format($e['remaining_qty']) ?>
</td>

<td class="fw-semibold text-warning">
<?= h($e['expiry_date']) ?>
</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>
</div>


<?php require_once __DIR__.'/includes/footer.php'; ?>