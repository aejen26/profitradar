<?php
ob_start();
// public/product_edit.php — add/edit product with sold_by + unit
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$user_id = $_SESSION['user']['id'];
$pdo = getDB();
check_csrf();

$id  = (int)($_GET['id'] ?? 0);
$err = '';

// defaults
$p = [
  'code' => '',
  'name' => '',
  'category' => '',
  'category_id' => null,
  'supplier_id' => null,
  'location_id' => null,
  'cost_price' => 0,
  'sell_price' => 0,
  'barcode' => '',
  'low_stock_threshold' => null,
  'sold_by' => 'each',   // NEW
  'unit'    => 'pc',     // NEW
];

// load existing when editing
if ($id) {

  // try current user first
  $st = $pdo->prepare('SELECT * FROM products WHERE id=? AND user_id=?');
  $st->execute([$id, $user_id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  // fallback for old products
  if (!$row) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
  }

  if ($row) {
    $p = array_merge($p, $row);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'code' => trim($_POST['code'] ?? ''), // may be empty to auto-generate
    'name' => trim($_POST['name'] ?? ''),
    'category' => trim($_POST['category'] ?? ''),
    'category_id' => ($_POST['category_id']!=='') ? (int)$_POST['category_id'] : null,
    'supplier_id' => ($_POST['supplier_id']!=='') ? (int)$_POST['supplier_id'] : null,
    'location_id' => ($_POST['location_id']!=='') ? (int)$_POST['location_id'] : null,
    'cost_price' => (float)($_POST['cost_price'] ?? 0),
    'sell_price' => (float)($_POST['sell_price'] ?? 0),
    'barcode' => trim($_POST['barcode'] ?? ''),
    'low_stock_threshold' => ($_POST['low_stock_threshold']!=='') ? (float)$_POST['low_stock_threshold'] : null,
    'sold_by' => strtolower(trim($_POST['sold_by'] ?? 'each')),
    'unit'    => strtolower(trim($_POST['unit'] ?? 'pc')),
  ];

if (!in_array($data['sold_by'], ['each','weight'], true)) {
    $data['sold_by'] = 'each';
}

$allowedUnits = ($data['sold_by'] === 'weight')
    ? ['kg','g','lb']
    : ['pc','bag','sack'];

if (!in_array($data['unit'], $allowedUnits)) {
    $data['unit'] = ($data['sold_by'] === 'weight') ? 'kg' : 'pc';
}

  // Auto-create/link category BEFORE validation/duplicate checks
  if (empty($data['category_id']) && !empty($data['category'])) {
    $data['category_id'] = ensure_category_exists($pdo, $data['category']);
  }

  // basic required fields
  if ($data['name'] === '') {
    $err = 'Name is required.';
    $p = array_merge($p, $data);
  } else {
    try {
      if ($id) {
        // UPDATE flow: code must still be unique (exclude current id)
        if ($data['code'] === '') $data['code'] = $p['code'] ?? '';

        $chk = $pdo->prepare('SELECT id FROM products WHERE code = ? AND id <> ? AND user_id = ?');
        $chk->execute([$data['code'], $id, $user_id]);
        if ($chk->fetch()) {
          $err = "Another product already uses the code <strong>".h($data['code'])."</strong>. Please choose a different code.";
          $p = array_merge($p, $data);
        } else {
          $sql = 'UPDATE products
        SET code=?, name=?, category=?, category_id=?, supplier_id=?, location_id=?, cost_price=?, sell_price=?, barcode=?, low_stock_threshold=?, sold_by=?, unit=?, updated_at=NOW()
        WHERE id=? AND user_id=?';

$pdo->prepare($sql)->execute([
  $data['code'],$data['name'],$data['category'],$data['category_id'],$data['supplier_id'],
  $data['location_id'],$data['cost_price'],$data['sell_price'],$data['barcode'],
  $data['low_stock_threshold'],$data['sold_by'],$data['unit'],$id,$user_id
]);
          log_action($pdo, current_user()['id'], 'update', 'products', $id, 'Product updated', $p, $data);
          header('Location: /products.php'); exit;
        }
      } else {
        // INSERT flow: if code empty, generate one now using safe fallback
        if ($data['code'] === '') {
          // get next available code (scans existing products and finds next unused)
          $data['code'] = get_next_available_product_code($pdo, 'P', 4);
        }

        // final duplicate check for INSERT
        $chk = $pdo->prepare('SELECT id FROM products WHERE code = ? LIMIT 1');
        $chk->execute([$data['code']]);
        if ($chk->fetch()) {
          $err = "A product with code <strong>".h($data['code'])."</strong> already exists. Please use a unique code.";
          $p = array_merge($p, $data);
        } else {
          $sql = 'INSERT INTO products 
(code,name,category,category_id,supplier_id,location_id,cost_price,sell_price,barcode,low_stock_threshold,sold_by,unit,user_id)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';

$pdo->prepare($sql)->execute([
  $data['code'],$data['name'],$data['category'],$data['category_id'],$data['supplier_id'],
  $data['location_id'],$data['cost_price'],$data['sell_price'],$data['barcode'],$data['low_stock_threshold'],
  $data['sold_by'],$data['unit'],$user_id
]);
          $newId = (int)$pdo->lastInsertId();
          log_action($pdo, current_user()['id'], 'create', 'products', $newId, 'Product created', null, $data);
          header('Location: /products.php'); exit;
        }
      }
    } catch (Throwable $e) {
      if (!$err) $err = 'Save failed: '.h($e->getMessage());
      $p = array_merge($p, $data);
    }
  }
}

// Categories (fallback if user_id mismatch)
$catsStmt = $pdo->prepare('SELECT id,name FROM categories WHERE user_id=? ORDER BY name');
$catsStmt->execute([$user_id]);
$cats = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

// If empty, fallback to all (fix for your current DB issue)
if (empty($cats)) {
  $cats = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}

// Suppliers (same fix)
$supStmt = $pdo->prepare('SELECT id,name FROM suppliers WHERE user_id=? ORDER BY name');
$supStmt->execute([$user_id]);
$sup = $supStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sup)) {
  $sup = $pdo->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}

// Locations (already correct)
$locs = $pdo->query('SELECT id,name FROM locations ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= $id ? 'Edit' : 'Add' ?> Product</h4>
</div>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= $err ?></div>
<?php endif; ?>

<form method="post" class="row g-3">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

  <div class="col-md-3">
    <label class="form-label">Code <span class="text-muted small">Leave empty to auto-generate</span></label>
    <input class="form-control" name="code" value="<?= h($p['code']) ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" required value="<?= h($p['name']) ?>">
  </div>

  <!-- Category dropdown (FK) + optional legacy text -->
  <div class="col-md-3">
    <label class="form-label">Category</label>
    <select class="form-select" name="category_id">
      <option value="">— none —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((string)$p['category_id'] === (string)$c['id']) ? 'selected' : '' ?>>
          <?= h($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Or type new category:</div>
    <input class="form-control" name="category" value="<?= h($p['category']) ?>" placeholder="Optional text">
  </div>

  <div class="col-md-4">
    <label class="form-label">Supplier</label>
    <select class="form-select" name="supplier_id" id="supplier_id">
  <option value="">— none —</option>

  <?php foreach ($sup as $s): ?>
    <option value="<?= (int)$s['id'] ?>" <?= ($p['supplier_id']==$s['id']) ? 'selected' : '' ?>>
      <?= h($s['name']) ?>
    </option>
  <?php endforeach; ?>

  <option value="__add_new__">+ Add new supplier</option>
</select>
  </div>

  <!-- Location dropdown -->
  <div class="col-md-4">
    <label class="form-label">Location</label>
    <select class="form-select" name="location_id">
      <option value="">— none —</option>
      <?php foreach ($locs as $l): ?>
        <option value="<?= (int)$l['id'] ?>" <?= ((string)$p['location_id'] === (string)$l['id']) ? 'selected' : '' ?>>
          <?= h($l['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <label class="form-label">Cost</label>
    <input class="form-control" type="number" step="0.01" name="cost_price" value="<?= h($p['cost_price']) ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label">Sell Price</label>
    <input class="form-control" type="number" step="0.01" name="sell_price" value="<?= h($p['sell_price']) ?>">
  </div>

  <!-- NEW: Sold By + Unit -->
  <div class="col-md-3">
    <label class="form-label">Sold By</label>
    <select class="form-select" name="sold_by" id="sold_by">
      <option value="each"   <?= $p['sold_by']==='each'   ? 'selected' : '' ?>>Each (per piece)</option>
      <option value="weight" <?= $p['sold_by']==='weight' ? 'selected' : '' ?>>Weight (per kg)</option>
    </select>
  </div>
<div class="col-md-3">
  <label class="form-label">Unit</label>
  <select class="form-select" name="unit" id="unit" required>
    <?php if ($p['sold_by'] === 'weight'): ?>
      <option value="kg" <?= $p['unit']==='kg'?'selected':'' ?>>kg</option>
      <option value="g"  <?= $p['unit']==='g'?'selected':'' ?>>g</option>
      <option value="lb" <?= $p['unit']==='lb'?'selected':'' ?>>lb</option>
    <?php else: ?>
      <option value="pc"   <?= $p['unit']==='pc'?'selected':'' ?>>pc</option>
      <option value="bag"  <?= $p['unit']==='bag'?'selected':'' ?>>bag</option>
      <option value="sack" <?= $p['unit']==='sack'?'selected':'' ?>>sack</option>
    <?php endif; ?>
  </select>
  <div class="form-text">Price is per unit (e.g., ₱/kg for weight).</div>
</div>

  <div class="col-md-4">
    <label class="form-label">Barcode</label>
    <input class="form-control" name="barcode" value="<?= h($p['barcode']) ?>" placeholder="Optional">
  </div>

  <div class="col-md-4">
    <label class="form-label">Low Stock Threshold (override)</label>
    <input class="form-control" type="number" step="0.001" name="low_stock_threshold" value="<?= h($p['low_stock_threshold']) ?>" placeholder="Leave blank to use global">
  </div>

  <div class="col-12 text-end">
    <button class="btn btn-primary">Save</button>
  </div>
</form>
<div class="modal fade" id="supplierModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Add Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        
        <div class="mb-2">
          <label>Name *</label>
          <input type="text" id="new_supplier_name" class="form-control">
        </div>

        <div class="mb-2">
          <label>Contact Person</label>
          <input type="text" id="new_supplier_contact" class="form-control">
        </div>

        <div class="mb-2">
          <label>Phone</label>
          <input type="text" id="new_supplier_phone" class="form-control" inputmode="numeric">
        </div>

        <div class="mb-2">
          <label>Email</label>
          <input type="email" id="new_supplier_email" class="form-control">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveSupplierBtn">Save</button>
      </div>

    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const soldSel = document.getElementById('sold_by');
  const unitSel = document.getElementById('unit');

  function syncUnit() {
    const options = (soldSel.value === 'weight')
        ? ['kg','g','lb']
        : ['pc','bag','sack'];

    const currentUnit = unitSel.value;

    unitSel.innerHTML = '';

    options.forEach(u => {
      const opt = document.createElement('option');
      opt.value = u;
      opt.textContent = u;
      unitSel.appendChild(opt);
    });

    unitSel.value = options.includes(currentUnit) ? currentUnit : options[0];
  }

  soldSel.addEventListener('change', syncUnit);

  // ✅ Supplier modal (FIXED)
  const supplierSelect = document.getElementById('supplier_id');
  const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));

  supplierSelect.addEventListener('change', function () {
    if (this.value === '__add_new__') {
      this.value = '';
      supplierModal.show();
    }
  });

 document.getElementById('saveSupplierBtn').addEventListener('click', function () {

  const name = document.getElementById('new_supplier_name').value.trim();
  const contact = document.getElementById('new_supplier_contact').value.trim();
  const phone = document.getElementById('new_supplier_phone').value.trim();
  const email = document.getElementById('new_supplier_email').value.trim();

  if (!name) {
    alert('Supplier name is required');
    return;
  }

  fetch('ajax_add_supplier.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      name: name,
      contact: contact,
      phone: phone,
      email: email
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const opt = document.createElement('option');
      opt.value = data.id;
      opt.textContent = data.name;
      opt.selected = true;

      const supplierSelect = document.getElementById('supplier_id');
      supplierSelect.insertBefore(opt, supplierSelect.lastElementChild);

      // clear fields
      document.getElementById('new_supplier_name').value = '';
      document.getElementById('new_supplier_contact').value = '';
      document.getElementById('new_supplier_phone').value = '';
      document.getElementById('new_supplier_email').value = '';

      bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();

    } else {
      alert(data.error || 'Failed');
    }
  });
});
});
    document.getElementById('new_supplier_phone').addEventListener('input', function () {
  this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
