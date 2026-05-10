<?php
require_once __DIR__.'/../includes/header.php';
require_login();

$pdo = getDB();

/* ---------- VALIDATION ---------- */
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo '<div class="alert alert-danger m-3">Invalid Delivery ID.</div>';
    require_once __DIR__.'/../includes/footer.php';
    exit;
}

$id = (int)$id;

/* ---------- GET DELIVERY ---------- */
$stmt = $pdo->prepare("
SELECT 
    d.delivery_number,
    d.delivery_date,
    d.status,
    c.name AS customer
FROM deliveries d
LEFT JOIN customers c ON c.id = d.customer_id
WHERE d.id = ?
");
$stmt->execute([$id]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    echo '<div class="alert alert-warning m-3">Delivery not found.</div>';
    require_once __DIR__.'/../includes/footer.php';
    exit;
}

/* ---------- GET ITEMS ---------- */
$stmt = $pdo->prepare("
SELECT
    p.code,
    p.name,
    di.quantity
FROM delivery_items di
JOIN products p ON p.id = di.product_id
WHERE di.delivery_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- STATUS BADGE ---------- */
function statusBadge($status){
    $status = strtolower(trim($status));

    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark px-3 py-2">Pending</span>';
        case 'partial':
            return '<span class="badge bg-info text-dark px-3 py-2">Partial</span>';
        case 'delivered':
            return '<span class="badge bg-success px-3 py-2">Delivered</span>';
        default:
            return '<span class="badge bg-secondary px-3 py-2">'.h($status).'</span>';
    }
}
?>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-semibold mb-0">Delivery Details</h4>
        <small class="text-muted">View delivery information and items</small>
    </div>

    <a href="index.php" class="btn btn-outline-secondary">
        ← Back
    </a>
</div>

<!-- DELIVERY INFO -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body">

        <div class="row text-center text-md-start">

            <div class="col-md-3 mb-3">
                <small class="text-muted">Delivery #</small><br>
                <strong><?= h($delivery['delivery_number'] ?? $id) ?></strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Customer</small><br>
                <strong><?= h($delivery['customer'] ?? 'Walk-in') ?></strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Date</small><br>
                <strong><?= date('M d, Y', strtotime($delivery['delivery_date'])) ?></strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Status</small><br>
                <?= statusBadge($delivery['status'] ?? '') ?>
            </div>

        </div>

    </div>
</div>

<!-- ITEMS -->
<div class="card shadow-sm border-0">

<div class="card-header bg-white fw-semibold">
    Delivered Items
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th style="width:150px">Code</th>
<th>Product</th>
<th class="text-center" style="width:150px">Quantity</th>
</tr>
</thead>

<tbody>

<?php if (!$items): ?>

<tr>
<td colspan="3" class="text-center text-muted py-5">
No items in this delivery.
</td>
</tr>

<?php else: ?>

<?php foreach ($items as $row): ?>

<tr>

<td class="fw-semibold">
<?= h($row['code']) ?>
</td>

<td>
<?= h($row['name']) ?>
</td>

<td class="text-center">
<span class="badge bg-light text-dark px-3 py-2">
<?= (int)$row['quantity'] ?>
</span>
</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>