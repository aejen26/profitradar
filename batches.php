<?php
require_once __DIR__.'/includes/header.php';
require_login();
$pdo = getDB();

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT b.*, p.name AS product_name
    FROM product_batches b
    JOIN products p ON p.id = b.product_id
    WHERE 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        p.name LIKE ?
        OR b.batch_no LIKE ?
        OR DATE_FORMAT(b.expiry_date, '%Y-%m-%d') LIKE ?
    )";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY b.expiry_date ASC, b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- SUMMARY ---------- */
$totalBatches = count($batches);
$lowStock = count(array_filter($batches, fn($b) => $b['remaining_qty'] <= 10 && $b['remaining_qty'] > 0));
$expired = count(array_filter($batches, fn($b) => $b['status'] == 'expired'));
$outStock = count(array_filter($batches, fn($b) => $b['remaining_qty'] <= 0));
?>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">Batch Inventory</h4>
        <small class="text-muted">Track product batches and expiry dates</small>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row mb-4">

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<strong>Total Batches</strong>
<h5 class="mb-0"><?= $totalBatches ?></h5>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<strong>Low Stock</strong>
<h5 class="mb-0 text-warning"><?= $lowStock ?></h5>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<strong>Out of Stock</strong>
<h5 class="mb-0 text-danger"><?= $outStock ?></h5>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm text-center p-3">
<strong>Expired</strong>
<h5 class="mb-0 text-danger"><?= $expired ?></h5>
</div>
</div>

</div>

<!-- FILTER -->
<div class="card shadow-sm mb-4">
<div class="card-body">

<form method="get" class="row g-3">

<div class="col-md-5">
<input
type="text"
name="search"
value="<?= h($search) ?>"
class="form-control"
placeholder="Search product, batch number, or expiry">
</div>

<div class="col-md-2 d-grid">
<button class="btn btn-primary">Filter</button>
</div>

<div class="col-md-2 d-grid">
<a href="batches.php" class="btn btn-outline-secondary">Reset</a>
</div>

</form>

</div>
</div>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th style="width:20%">Product</th>
<th style="width:140px">Batch No</th>
<th class="text-center">Quantity</th>
<th class="text-center">Remaining</th>
<th>Expiry Date</th>
<th>Stock</th>
<th>Status</th>
</tr>
</thead>

<tbody>

<?php if (!$batches): ?>

<tr>
<td colspan="7" class="text-center text-muted py-4">
No batch records found.
</td>
</tr>

<?php else: ?>

<?php foreach ($batches as $b):

$expiry = $b['expiry_date'] ? strtotime($b['expiry_date']) : null;
$today = strtotime(date('Y-m-d'));
$diff = $expiry ? ($expiry - $today) / (60*60*24) : null;

/* row highlight */
$rowClass = '';
if ($b['status'] == 'expired') $rowClass = 'table-danger';
elseif ($b['remaining_qty'] <= 10) $rowClass = 'table-warning';
?>

<tr class="<?= $rowClass ?>">

<td class="fw-semibold">
<?= h($b['product_name']) ?>
</td>

<td>
<?= h($b['batch_no']) ?>
</td>

<td class="text-center">
<?= number_format($b['quantity']) ?>
</td>

<td class="text-center fw-semibold">
<?= number_format($b['remaining_qty']) ?>
</td>

<td>

<?php if ($expiry): ?>

<?php
if ($expiry < $today) {
    echo '<span class="text-danger fw-semibold">'.date('M d, Y', $expiry).'</span>';
} elseif ($diff <= 7) {
    echo '<span class="text-warning fw-semibold">'.date('M d, Y', $expiry).' (Soon)</span>';
} else {
    echo date('M d, Y', $expiry);
}
?>

<?php else: ?>
<span class="badge bg-secondary">No Expiry</span>
<?php endif; ?>

</td>

<td>

<?php
if ($b['remaining_qty'] <= 0) {
    echo '<span class="badge bg-danger">Out</span>';
} elseif ($b['remaining_qty'] <= 10) {
    echo '<span class="badge bg-warning text-dark">Low</span>';
} else {
    echo '<span class="badge bg-success">Good</span>';
}
?>

</td>

<td>

<?php if ($b['status'] == 'expired'): ?>
<span class="badge bg-danger">Expired</span>
<?php elseif ($b['status'] == 'damaged'): ?>
<span class="badge bg-warning text-dark">Damaged</span>
<?php else: ?>
<span class="badge bg-success">Active</span>
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