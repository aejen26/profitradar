<?php
require_once __DIR__.'/../includes/header.php';
require_login();

$pdo = getDB();

/* ---------- VALIDATION ---------- */
$transaction_id = $_GET['transaction_id'] ?? null;

if (!$transaction_id || !is_numeric($transaction_id) || (int)$transaction_id <= 0) {
    echo '<div class="alert alert-danger m-3">
            Invalid or missing Transaction ID.
          </div>';
    require_once __DIR__.'/../includes/footer.php';
    exit;
}

$transaction_id = (int)$transaction_id;

/* ---------- CHECK IF EXISTS ---------- */
$check = $pdo->prepare("SELECT id FROM transactions WHERE id = ? LIMIT 1");
$check->execute([$transaction_id]);

if (!$check->fetch()) {
    echo '<div class="alert alert-warning m-3">
            Transaction not found.
          </div>';
    require_once __DIR__.'/../includes/footer.php';
    exit;
}

/* ---------- FETCH ITEMS ---------- */
$stmt = $pdo->prepare("
SELECT
    ti.id AS transaction_item_id,
    ti.product_id,
    p.name,
    ti.qty AS ordered_qty,
    COALESCE(SUM(di.quantity),0) AS delivered_qty,
    (ti.qty - COALESCE(SUM(di.quantity),0)) AS remaining_qty
FROM transaction_items ti
JOIN products p ON p.id = ti.product_id
LEFT JOIN delivery_items di ON di.transaction_item_id = ti.id
WHERE ti.transaction_id = ?
GROUP BY ti.id
");

$stmt->execute([$transaction_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-4">
    <h4 class="fw-semibold mb-0">Create Delivery</h4>
    <small class="text-muted">
        Deliver items from transaction #<?php echo $transaction_id; ?>
    </small>
</div>

<form action="store.php" method="POST">
<input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>">

<div class="card shadow-sm">

<div class="card-header">
    <strong>Delivery Items</strong>
</div>

<div class="table-responsive">

<table class="table table-hover table-sm align-middle mb-0">

<thead class="table-light">
<tr>
<th>Product</th>
<th class="text-center">Ordered</th>
<th class="text-center">Delivered</th>
<th>Progress</th>
<th class="text-center">Remaining</th>
<th class="text-center">Deliver Qty</th>
</tr>
</thead>

<tbody>

<?php if (!$result): ?>

<tr>
<td colspan="6" class="text-center text-muted py-4">
No items found for this transaction.
</td>
</tr>

<?php else: ?>

<?php foreach ($result as $row):

$ordered = $row['ordered_qty'];
$delivered = $row['delivered_qty'];
$remaining = $row['remaining_qty'];

$percent = $ordered > 0 ? ($delivered / $ordered) * 100 : 0;
?>

<tr>

<td class="fw-semibold"><?php echo h($row['name']); ?></td>

<td class="text-center"><?php echo $ordered; ?></td>

<td class="text-center"><?php echo $delivered; ?></td>

<td style="width:200px">

<div class="progress" style="height:8px">
<div class="progress-bar bg-success"
     style="width: <?php echo $percent; ?>%">
</div>
</div>

<small class="text-muted">
<?php echo number_format($percent,1); ?>%
</small>

</td>

<td class="text-center">

<?php if ($remaining > 0): ?>
<span class="badge bg-warning text-dark">
<?php echo $remaining; ?>
</span>
<?php else: ?>
<span class="badge bg-success">Completed</span>
<?php endif; ?>

</td>

<td class="text-center">

<input
type="number"
class="form-control text-center deliver-input"
name="deliver_qty[<?php echo $row['transaction_item_id']; ?>]"
min="0"
max="<?php echo $remaining; ?>"
step="0.001"
value="0">

<small class="text-muted">
Max: <?php echo $remaining; ?>
</small>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>
</table>

</div>
</div>


<div class="card shadow-sm mt-3">

<div class="card-body d-flex justify-content-between align-items-center">

<div>
<strong>Total Items To Deliver:</strong>
<span id="totalDeliver">0</span>
</div>

<div class="d-flex gap-2">

<a href="index.php" class="btn btn-outline-secondary">
Back
</a>

<button type="submit" class="btn btn-primary">
Create Delivery
</button>

</div>

</div>

</div>

</form>

<script>

const inputs = document.querySelectorAll('.deliver-input');
const totalDeliver = document.getElementById('totalDeliver');
const form = document.querySelector("form");
const submitBtn = form.querySelector("button[type='submit']");

function updateTotal(){
    let total = 0;
    inputs.forEach(i => {
        total += parseFloat(i.value || 0);
    });
    totalDeliver.textContent = total.toFixed(2);
}

function validateForm(){
    let total = 0;
    inputs.forEach(i => total += parseFloat(i.value || 0));
    submitBtn.disabled = total <= 0;
}

inputs.forEach(i => {
    i.addEventListener('input', () => {
        updateTotal();
        validateForm();
    });
});

form.addEventListener("submit", function(e){
    let total = 0;
    inputs.forEach(i => total += parseFloat(i.value || 0));

    if (total <= 0) {
        e.preventDefault();
        alert("Please enter at least one delivery quantity.");
    }
});

updateTotal();
validateForm();

</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>