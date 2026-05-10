<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$po = $pdo->prepare("SELECT * FROM purchase_orders WHERE id=?");
$po->execute([$id]);
$po = $po->fetch();

if (!$po) die("Invalid PO");

$itemsStmt = $pdo->prepare("
SELECT poi.*, p.name
FROM purchase_order_items poi
JOIN products p ON p.id = poi.product_id
WHERE purchase_order_id = ?
");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->beginTransaction();

    try {

        $allReceived = true;

        foreach ($items as $it) {

            $receiveQty = (float)($_POST['receive'][$it['id']] ?? 0);
            $remaining = $it['qty'] - $it['received_qty'];

            if ($receiveQty > 0 && $receiveQty <= $remaining) {

                $pdo->prepare("
                    UPDATE purchase_order_items
                    SET received_qty = received_qty + ?
                    WHERE id = ?
                ")->execute([$receiveQty, $it['id']]);

                $pdo->prepare("
                    UPDATE products
                    SET stock_qty = stock_qty + ?
                    WHERE id = ?
                ")->execute([$receiveQty, $it['product_id']]);
            }

            if ($it['received_qty'] + $receiveQty < $it['qty']) {
                $allReceived = false;
            }
        }

        $newStatus = $allReceived ? 'received' : 'partially_received';

        $pdo->prepare("
            UPDATE purchase_orders
            SET status = ?
            WHERE id = ?
        ")->execute([$newStatus, $id]);

        $pdo->commit();

        $_SESSION['success'] = "Items received successfully!";
        header("Location: purchase_orders.php");
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        die($e->getMessage());
    }
}
?>

<!-- Page Header -->
<div class="mb-4">
<h4 class="fw-semibold mb-0">Receive Purchase Order #<?= $id ?></h4>
<small class="text-muted">Record items received from supplier</small>
</div>


<form method="post">

<div class="card shadow-sm">

<div class="card-header">
<strong>Items to Receive</strong>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>Product</th>
<th class="text-center" style="width:120px">Ordered</th>
<th class="text-center" style="width:150px">Already Received</th>
<th class="text-center" style="width:180px">Receive Now</th>
</tr>
</thead>

<tbody>

<?php if (!$items): ?>

<tr>
<td colspan="4" class="text-center text-muted py-4">
No items found for this purchase order.
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

<input
type="number"
name="receive[<?= $it['id'] ?>]"
class="form-control text-center"
min="0"
max="<?= $remaining ?>"
step="0.001"
value="0">

<small class="text-muted">
Remaining: <?= $remaining ?>
</small>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


<div class="mt-3 text-end">

<button class="btn btn-success">
Confirm Receive
</button>

</div>

</form>

<?php require_once __DIR__.'/includes/footer.php'; ?>