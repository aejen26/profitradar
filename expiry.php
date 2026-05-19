<?php
require_once __DIR__.'/includes/header.php';
require_login();

$pdo = getDB();

/* ================= DELETE EXPIRED ITEMS ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expired'])) {

    try {

        check_csrf();

        $pdo->beginTransaction();

        $expiredItems = $pdo->query("
            SELECT pb.*, p.name
            FROM product_batches pb
            JOIN products p ON pb.product_id = p.id
            WHERE pb.expiry_date < CURDATE()
            AND pb.remaining_qty > 0
        ")->fetchAll(PDO::FETCH_ASSOC);

        $userId = $_SESSION['user']['id'] ?? 1;

        foreach ($expiredItems as $item) {

            $batchId = (int)$item['id'];
            $productId = (int)$item['product_id'];
            $qty = (float)$item['remaining_qty'];

            if ($qty <= 0) continue;

            /* deduct only expired batch qty from total stock */

$currentStockStmt = $pdo->prepare("
    SELECT stock_qty
    FROM products
    WHERE id = ?
");

$currentStockStmt->execute([$productId]);

$currentStock = (float)$currentStockStmt->fetchColumn();

/* never deduct more than actual stock */
$deductQty = min($qty, $currentStock);

$upd = $pdo->prepare("
    UPDATE products
    SET stock_qty = stock_qty - ?
    WHERE id = ?
");

$upd->execute([
    $deductQty,
    $productId
]);

            /* empty batch */
            $clear = $pdo->prepare("
                UPDATE product_batches
                SET remaining_qty = 0
                WHERE id = ?
            ");

            $clear->execute([$batchId]);

            /* create transaction */
            $refNo = 'EXP_' . date('Ymd_His') . '_' . $batchId;

            $tx = $pdo->prepare("
                INSERT INTO transactions
                (
                    type,
                    ref_no,
                    date,
                    user_id,
                    notes,
                    created_at
                )
                VALUES
                (
                    'adjust',
                    ?,
                    CURDATE(),
                    ?,
                    ?,
                    NOW()
                )
            ");

            $reason = 'Expired item disposal';

            $tx->execute([
                $refNo,
                $userId,
                $reason
            ]);

            $transactionId = $pdo->lastInsertId();

            /* transaction item */
            $txItem = $pdo->prepare("
                INSERT INTO transaction_items
                (
                    transaction_id,
                    product_id,
                    qty,
                    unit_price
                )
                VALUES
                (?, ?, ?, 0)
            ");

            $txItem->execute([
                $transactionId,
                $productId,
                $qty
            ]);

            /* audit log */
            $audit = $pdo->prepare("
                INSERT INTO audit_log
                (
                    user_id,
                    action,
                    description,
                    created_at
                )
                VALUES
                (?, ?, ?, NOW())
            ");

            $audit->execute([
                $userId,
                'delete_expired',
                'Deleted expired batch: ' . $item['batch_no']
            ]);
        }

        $pdo->commit();

        $_SESSION['success'] = 'Expired items deleted successfully';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: expiry.php');
    exit;
}

/* ================= LOAD DATA ================= */

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
<small class="text-muted">
Track expired and soon-to-expire product batches
</small>
</div>

<!-- Delete Button -->
<div class="mb-3">

<form method="post"
      onsubmit="return confirm('Delete all expired items?')">

<input type="hidden"
       name="csrf"
       value="<?= csrf_token() ?>">

<button type="submit"
        name="delete_expired"
        class="btn btn-danger">

Delete Expired Items

</button>

</form>

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
<strong class="text-warning">
Expiring Within 30 Days
</strong>
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
