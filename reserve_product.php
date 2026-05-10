<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
require_login();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

/* ---- Load product info (for UI + availability check) ---- */
$stmt = $pdo->prepare("
    SELECT id, code, name, stock_qty, reserved_qty
    FROM products
    WHERE id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

$available = (float)$product['stock_qty'] - (float)$product['reserved_qty'];

/* ---- Handle submit ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $qty      = (float)($_POST['qty'] ?? 0);
    $note     = $_POST['note'] ?? '';
    $customer = $_POST['customer_name'] ?? '';
    $expires  = $_POST['expires_at'] ?? null;

    if ($qty <= 0) {
        die("Invalid quantity.");
    }

    if ($qty > $available) {
        die("Not enough available stock.");
    }

    $pdo->beginTransaction();

    try {

        /* Save reservation */
        $stmt = $pdo->prepare("
            INSERT INTO reservations (product_id, quantity, customer_name, note, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $qty, $customer, $note, $expires]);

        /* Update reserved qty */
        $stmt = $pdo->prepare("
            UPDATE products
            SET reserved_qty = reserved_qty + ?
            WHERE id = ?
        ");
        $stmt->execute([$qty, $id]);

        /* Log inventory movement */
        logStockMovement(
            $pdo,
            $id,
            'reserve',
            $qty,
            'reservation',
            null,
            'Stock reserved'
        );

        $pdo->commit();

    } catch (Exception $e) {

        $pdo->rollBack();
        die("Reservation failed: " . $e->getMessage());
    }

    header("Location: reservations.php");
    exit;
}
?>

<div class="mb-4">
<h4 class="fw-semibold mb-0">Reserve Stock</h4>
<small class="text-muted">Reserve product stock for a customer</small>
</div>


<div class="card shadow-sm" style="max-width:650px">

<div class="card-header">
<strong>Product Information</strong>
</div>

<div class="card-body">

<div class="row mb-3">

<div class="col-md-6">
<strong>Product</strong><br>
<?= h($product['name']) ?>
</div>

<div class="col-md-3">
<strong>Code</strong><br>
<?= h($product['code']) ?>
</div>

<div class="col-md-3">
<strong>Available</strong><br>
<span class="badge bg-success">
<?= number_format($available,2) ?>
</span>
</div>

</div>

</div>

</div>


<div class="card shadow-sm mt-3" style="max-width:650px">

<div class="card-header">
<strong>Reservation Details</strong>
</div>

<div class="card-body">

<form method="POST" onsubmit="return confirm('Reserve this stock?');">

<div class="mb-3">

<label class="form-label">Quantity</label>

<input
type="number"
name="qty"
id="qtyInput"
class="form-control"
min="0.01"
max="<?= $available ?>"
step="0.01"
required>

<small class="text-muted">
Maximum reservable: <?= number_format($available,2) ?>
</small>

</div>


<div class="mb-3">

<label class="form-label">Customer</label>

<input
type="text"
name="customer_name"
class="form-control"
placeholder="Customer name (optional)">

</div>


<div class="mb-3">

<label class="form-label">Note</label>

<input
type="text"
name="note"
class="form-control"
placeholder="Reservation note">

</div>


<div class="mb-3">

<label class="form-label">Reservation Expiry</label>

<input
type="datetime-local"
name="expires_at"
class="form-control">

</div>


<div class="alert alert-light mt-3">

<strong>Stock after reservation:</strong>
<span id="remainingPreview">
<?= number_format($available,2) ?>
</span>

</div>


<div class="d-flex justify-content-between">

<a href="products.php" class="btn btn-outline-secondary">
Cancel
</a>

<button class="btn btn-warning">
Reserve Stock
</button>

</div>

</form>

</div>

</div>


<script>

const available = <?= json_encode($available) ?>;
const qtyInput = document.getElementById('qtyInput');
const remainingPreview = document.getElementById('remainingPreview');

qtyInput.addEventListener('input', function(){

let qty = parseFloat(this.value || 0);
let remaining = available - qty;

if(remaining < 0) remaining = 0;

remainingPreview.textContent = remaining.toFixed(2);

});

</script>