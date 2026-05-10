<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();
check_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $items = $_POST['items'] ?? [];
    if (!$items) {
        $_SESSION['error'] = 'Add at least one item.';
        header('Location: purchase_order_add.php');
        exit;
    }

    $supplier_id = $_POST['supplier_id'] ?: null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');

    $pdo->beginTransaction();

    try {

        $stmt = $pdo->prepare("
            INSERT INTO purchase_orders
            (supplier_id, user_id, order_date, notes, status, total_amount)
            VALUES (?, ?, ?, ?, 'ordered', 0)
        ");

        $stmt->execute([
            $supplier_id,
            current_user()['id'],
            $date,
            $notes
        ]);

        $po_id = $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("
            INSERT INTO purchase_order_items
            (purchase_order_id, product_id, qty, unit_price)
            VALUES (?, ?, ?, ?)
        ");

        $totalAmount = 0;

        foreach ($items as $it) {

            $product_id = (int)($it['product_id'] ?? 0);
            $qty = (float)($it['qty'] ?? 0);
            $price = (float)($it['unit_price'] ?? 0);

            if ($product_id && $qty > 0) {

                $itemStmt->execute([
                    $po_id,
                    $product_id,
                    $qty,
                    $price
                ]);

                $totalAmount += ($qty * $price);
            }
        }

        $pdo->prepare("
            UPDATE purchase_orders
            SET total_amount = ?
            WHERE id = ?
        ")->execute([$totalAmount, $po_id]);

        $pdo->commit();

        $_SESSION['success'] = "Purchase Order created!";
        header("Location: purchase_orders.php");
        exit;

    } catch (Throwable $e) {

        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: purchase_order_add.php');
        exit;
    }
}

$products = $pdo->query("
SELECT id, name, cost_price
FROM products
WHERE is_active=1
")->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query("
SELECT id, name
FROM suppliers
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="mb-4">
<h4 class="fw-semibold mb-0">Create Purchase Order</h4>
<small class="text-muted">Order products from suppliers</small>
</div>


<form method="post">

<input type="hidden" name="csrf" value="<?= csrf_token() ?>">


<!-- Order Info -->
<div class="card shadow-sm mb-4">

<div class="card-header">
<strong>Order Details</strong>
</div>

<div class="card-body">

<div class="row g-3">

<div class="col-md-4">

<label class="form-label">Order Date</label>

<input
type="date"
name="date"
class="form-control"
value="<?= date('Y-m-d') ?>">

</div>

<div class="col-md-4">

<label class="form-label">Supplier</label>

<select name="supplier_id" class="form-select">

<option value="">-- Select Supplier --</option>

<?php foreach($suppliers as $s): ?>

<option value="<?= $s['id'] ?>">
<?= h($s['name']) ?>
</option>

<?php endforeach; ?>

</select>

</div>

</div>

</div>

</div>


<!-- Items -->
<div class="card shadow-sm mb-4">

<div class="card-header d-flex justify-content-between align-items-center">

<strong>Order Items</strong>

<button
type="button"
class="btn btn-sm btn-outline-secondary"
onclick="addRow()">

+ Add Item

</button>

</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0" id="itemsTbl">

<thead class="table-light">

<tr>
<th style="width:45%">Product</th>
<th style="width:150px">Quantity</th>
<th style="width:150px">Unit Price</th>
<th style="width:80px"></th>
</tr>

</thead>

<tbody></tbody>

</table>

</div>

</div>


<!-- Notes -->
<div class="card shadow-sm mb-4">

<div class="card-header">
<strong>Notes</strong>
</div>

<div class="card-body">

<textarea
name="notes"
class="form-control"
rows="3"
placeholder="Optional notes for this purchase order"></textarea>

</div>

</div>


<div class="text-end">

<button class="btn btn-primary">
Save Purchase Order
</button>

</div>

</form>


<script>

const PRODUCTS = <?= json_encode($products) ?>;

function addRow(){

let tb = document.querySelector('#itemsTbl tbody');
let idx = tb.children.length;

let options = '<option value="">-- Select Product --</option>';

PRODUCTS.forEach(p=>{
options += `<option value="${p.id}">${p.name}</option>`;
});

let tr = document.createElement('tr');

tr.innerHTML = `
<td>
<select name="items[${idx}][product_id]" class="form-select">
${options}
</select>
</td>

<td>
<input
type="number"
name="items[${idx}][qty]"
class="form-control"
value="1"
min="0.001"
step="0.001">
</td>

<td>
<input
type="number"
step="0.01"
name="items[${idx}][unit_price]"
class="form-control"
value="0">
</td>

<td class="text-center">
<button
type="button"
class="btn btn-sm btn-outline-danger"
onclick="this.closest('tr').remove()">
×
</button>
</td>
`;

tb.appendChild(tr);

}

addRow();

</script>


<?php require_once __DIR__.'/includes/footer.php'; ?>