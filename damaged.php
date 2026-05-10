<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();
check_csrf();

/* ================= POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $batch_id = (int)($_POST['batch_id'] ?? 0);
    $qty = (float)($_POST['qty'] ?? 0);

    if ($batch_id > 0 && $qty > 0) {

        $stmt = $pdo->prepare("SELECT * FROM product_batches WHERE id=?");
        $stmt->execute([$batch_id]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($batch && $batch['remaining_qty'] >= $qty) {

            $pdo->beginTransaction();

            $upd = $pdo->prepare("
                UPDATE product_batches
                SET remaining_qty = remaining_qty - ?
                WHERE id = ?
            ");
            $upd->execute([$qty, $batch_id]);

            $check = $pdo->prepare("SELECT remaining_qty FROM product_batches WHERE id=?");
            $check->execute([$batch_id]);
            $remaining = (float)$check->fetchColumn();

            if ($remaining <= 0) {
                $pdo->prepare("
                    UPDATE product_batches
                    SET status = 'damaged'
                    WHERE id = ?
                ")->execute([$batch_id]);
            }

            $upd2 = $pdo->prepare("
                UPDATE products
                SET stock_qty = stock_qty - ?
                WHERE id = ?
            ");
            $upd2->execute([$qty, $batch['product_id']]);

            $ins = $pdo->prepare("
                INSERT INTO damaged_items (batch_id, product_id, qty, damaged_date)
                VALUES (?, ?, ?, CURDATE())
            ");
            $ins->execute([$batch_id, $batch['product_id'], $qty]);

            $pdo->commit();

            $_SESSION['success'] = "Damaged item recorded.";
            header('Location: damaged.php');
            exit;

        } else {
            $_SESSION['error'] = "Invalid quantity.";
        }
    }
}

/* ================= LOAD DATA ================= */

$batches = $pdo->query("
    SELECT b.id, p.name, b.batch_no, b.remaining_qty
    FROM product_batches b
    JOIN products p ON p.id = b.product_id
    WHERE b.remaining_qty > 0
    ORDER BY p.name
")->fetchAll(PDO::FETCH_ASSOC);

$damaged = $pdo->query("
    SELECT p.name, d.qty, d.damaged_date
    FROM damaged_items d
    JOIN products p ON p.id = d.product_id
    ORDER BY d.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="mb-4">
<h4 class="fw-semibold mb-0">Damaged Inventory</h4>
<small class="text-muted">Record and monitor damaged products</small>
</div>



<!-- Record Damage Card -->
<div class="card shadow-sm border-danger mb-4">

<div class="card-header bg-danger bg-opacity-10">
<strong class="text-danger">Record Damaged Item</strong>
</div>

<div class="card-body">

<form method="post">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

<div class="row g-3">

<div class="col-md-6">

<label class="form-label">Batch</label>

<select name="batch_id" class="form-select" required>

<option value="">Select Batch</option>

<?php foreach($batches as $b): ?>

<option value="<?= $b['id'] ?>">

<?= h($b['name']) ?> — <?= h($b['batch_no']) ?>
(Remaining: <?= number_format($b['remaining_qty']) ?>)

</option>

<?php endforeach; ?>

</select>

</div>


<div class="col-md-3">

<label class="form-label">Quantity</label>

<input
type="number"
name="qty"
step="0.001"
min="0.001"
class="form-control"
placeholder="Enter quantity"
required>

</div>


<div class="col-md-3 d-grid">

<label class="form-label invisible">Action</label>

<button class="btn btn-danger">
Record Damage
</button>

</div>

</div>

</form>

</div>

</div>



<!-- Damaged Inventory Table -->
<div class="card shadow-sm">

<div class="card-header">
<strong>Damaged Records</strong>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>
<th>Product</th>
<th style="width:150px">Quantity</th>
<th style="width:180px">Date Recorded</th>
</tr>

</thead>

<tbody>

<?php if (!$damaged): ?>

<tr>
<td colspan="3" class="text-center text-muted py-4">
No damaged items recorded.
</td>
</tr>

<?php else: ?>

<?php foreach($damaged as $d): ?>

<tr>

<td class="fw-semibold">
<?= h($d['name']) ?>
</td>

<td>
<span class="badge bg-danger">
<?= number_format($d['qty']) ?>
</span>
</td>

<td class="text-muted">
<?= h($d['damaged_date']) ?>
</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php require_once __DIR__.'/includes/footer.php'; ?>