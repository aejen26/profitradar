<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();
check_csrf();

/* ================= POST HANDLER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $items = $_POST['items'] ?? [];

    if (!$items) {
      $_SESSION['error'] = 'Please add at least one item.';
      header('Location: purchase_add.php');
      exit;
    }

    $supplier_id = ($_POST['supplier_id'] !== '') ? (int)$_POST['supplier_id'] : null;
    $date  = $_POST['date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');

    $prodRows = $pdo->query("SELECT id, sold_by FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $prodIndex = [];
    foreach ($prodRows as $pr) {
      $prodIndex[(int)$pr['id']] = $pr;
    }

    $clean = [];

    foreach ($items as $i) {
      $pid   = (int)($i['product_id'] ?? 0);
      $qtyIn = (float)($i['qty'] ?? 0);
      $price = (float)($i['unit_price'] ?? 0);
      $expiry = $i['expiry_date'] ?? null;

      if ($pid <= 0 || $qtyIn <= 0) continue;

      $sold_by = $prodIndex[$pid]['sold_by'] ?? 'each';
      $qty = ($sold_by === 'weight')
        ? round($qtyIn, 3)
        : (int)round($qtyIn);

      if ($qty > 0) {
        $clean[] = [
          'product_id' => $pid,
          'qty' => $qty,
          'unit_price' => $price,
          'expiry_date' => $expiry
        ];
      }
    }

    if (!$clean) {
      $_SESSION['error'] = 'Please add at least one valid item.';
      header('Location: purchase_add.php');
      exit;
    }

    unset($_SESSION['tickets']);

    create_transaction(
      $pdo,
      'purchase',
      current_user()['id'],
      $date,
      $notes,
      $supplier_id,
      null,
      $clean
    );


    $_SESSION['success'] = 'Purchase recorded successfully!';
    header('Location: purchase_add.php');
    exit;

  } catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: purchase_add.php');
    exit;
  }
}

/* ================= LOAD DATA FOR UI ================= */
$products = $pdo->query("
  SELECT id, code, name, stock_qty, cost_price, sell_price, sold_by, unit
  FROM products
  WHERE is_active=1
  ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$sup = $pdo->query("SELECT id,name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Purchase (IN)</h4>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

  <div class="row g-2 mb-3">
    <div class="col-md-3">
      <label class="form-label">Date</label>
      <input class="form-control" type="date" name="date" value="<?= h(date('Y-m-d')) ?>">
    </div>
    <div class="col-md-5">
      <label class="form-label">Supplier</label>
      <select class="form-select" name="supplier_id">
        <option value="">— none —</option>
        <?php foreach($sup as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <table class="table table-sm align-middle" id="itemsTbl">
    <thead>
      <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Unit Price</th>
        <th>Expiration</th>
        <th>Line Total</th>
        <th></th>
      </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
      <tr>
        <th colspan="4" class="text-end">Grand Total:</th>
        <th id="grandTotal">₱0.00</th>
        <th></th>
      </tr>
    </tfoot>
  </table>

  <button type="button" class="btn btn-outline-secondary" onclick="addRow()">Add Item</button>

  <div class="mt-3 text-end">
    <textarea class="form-control mb-2" name="notes" placeholder="Notes (optional)"></textarea>
    <button class="btn btn-primary">Save Purchase</button>
  </div>
</form>

<script>
const PRODUCTS = <?= json_encode($products) ?>;

function getProductById(id){
  return PRODUCTS.find(p => parseInt(p.id,10) === parseInt(id,10));
}
function money(n){ return '₱' + Number(n||0).toFixed(2); }

function recalcRow(tr){
  const qty   = parseFloat(tr.querySelector('.qty').value || 0);
  const price = parseFloat(tr.querySelector('.unit-price').value || 0);
  tr.querySelector('.line-total').textContent = money(qty * price);
  recalcGrand();
}

function recalcGrand(){
  let sum = 0;
  document.querySelectorAll('#itemsTbl tbody tr').forEach(tr=>{
    sum += parseFloat(tr.querySelector('.qty').value || 0)
         * parseFloat(tr.querySelector('.unit-price').value || 0);
  });
  document.getElementById('grandTotal').textContent = money(sum);
}

function addRow(){
  const tb = document.querySelector('#itemsTbl tbody');
  const idx = tb.children.length;

  let opts = '<option value="">-- Select Product --</option>';
  PRODUCTS.forEach(p=>{
    opts += `<option value="${p.id}">${p.code} — ${p.name}</option>`;
  });

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-select" name="items[${idx}][product_id]" required>${opts}</select>
    </td>
    <td>
      <input class="form-control qty" type="number" name="items[${idx}][qty]" value="1" min="1" step="1">
    </td>
    <td>
      <input class="form-control unit-price" type="number" step="0.01" name="items[${idx}][unit_price]" value="0" readonly>
    </td>
    <td>
      <input class="form-control" type="date" name="items[${idx}][expiry_date]" required>
    </td>
    <td class="line-total">₱0.00</td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger"
        onclick="this.closest('tr').remove(); recalcGrand();">Remove</button>
    </td>
  `;
  tb.appendChild(tr);

  tr.querySelector('select').addEventListener('change', function(e){
    const prod = getProductById(e.target.value);
    if (prod) {
      tr.querySelector('.unit-price').value = prod.cost_price || 0;
    }
    recalcRow(tr);
  });

  tr.querySelector('.qty').addEventListener('input', ()=>recalcRow(tr));
}

addRow();
</script>

<?php require_once __DIR__.'/includes/footer.php'; ?>
