<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
require_login();

$pdo = getDB();

/* ================== STOCK FORMATTERS ================== */

function fmt_stock($stock, $sold_by, $unit) {
    $v = is_null($stock) ? 0 : (float)$stock;

    if ($sold_by === 'weight') {
        $qty = rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
    } else {
        $qty = number_format((int)$v);
    }

    $unit = strtolower(trim($unit));

    switch ($unit) {
        case 'pc':   $label = 'Pieces'; break;
        case 'kg':   $label = 'Kg'; break;
        case 'g':    $label = 'Grams'; break;
        case 'sack': $label = 'Sacks'; break;
        case 'bag':  $label = 'Bags'; break;
        case 'box':  $label = 'Boxes'; break;
        default:     $label = 'Units';
    }

    return $qty . ' ' . $label;
}

/* ================== FILTERS ================== */

$q     = trim($_GET['q'] ?? '');
$catId = trim($_GET['category_id'] ?? '');
$supId = trim($_GET['supplier_id'] ?? '');
$locId = trim($_GET['location_id'] ?? '');

/* ================== QUERY ================== */

$sql = "SELECT p.*,
               GREATEST(p.stock_qty - p.reserved_qty,0) AS available_stock,
               s.name AS supplier_name,
               c.name AS category_name,
               l.name AS location_name
        FROM products p
        LEFT JOIN suppliers s ON s.id = p.supplier_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN locations l ON l.id = p.location_id
        WHERE p.is_active = 1";

$params = [];

if ($q !== '') {
  $like = "%$q%";
  $sql .= " AND (
              p.code LIKE ? OR
              p.name LIKE ? OR
              s.name LIKE ? OR
              c.name LIKE ? OR
              l.name LIKE ? OR
              CAST(p.sell_price AS CHAR) LIKE ?
            )";
  array_push($params,$like,$like,$like,$like,$like,$like);
}

if ($catId !== '') { $sql .= " AND p.category_id = ?"; $params[] = (int)$catId; }
if ($supId !== '') { $sql .= " AND p.supplier_id = ?"; $params[] = (int)$supId; }
if ($locId !== '') { $sql .= " AND p.location_id = ?"; $params[] = (int)$locId; }

$sql .= " ORDER BY p.name ASC LIMIT 200";

$st = $pdo->prepare($sql);
$st->execute($params);

/* ================== DROPDOWNS ================== */

$cats = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$sups = $pdo->query("SELECT id,name FROM suppliers ORDER BY name")->fetchAll();
$locs = $pdo->query("SELECT id,name FROM locations ORDER BY name")->fetchAll();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h4 class="mb-0 fw-semibold">Products</h4>
<small class="text-muted">Manage inventory items</small>
</div>

<a class="btn btn-primary" href="<?= app_url('product_edit.php') ?>">
+ Add Product
</a>

</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
<div class="card-body">

<form class="row g-3">

<div class="col-md-4">
<input class="form-control"
       name="q"
       value="<?= h($q) ?>"
       placeholder="Search code, product, price...">
</div>

<div class="col-md-2">
<select class="form-select" name="category_id">
<option value="">All Categories</option>
<?php foreach($cats as $c): ?>
<option value="<?= $c['id'] ?>" <?= ($catId==$c['id']?'selected':'') ?>>
<?= h($c['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<select class="form-select" name="supplier_id">
<option value="">All Suppliers</option>
<?php foreach($sups as $s): ?>
<option value="<?= $s['id'] ?>" <?= ($supId==$s['id']?'selected':'') ?>>
<?= h($s['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<select class="form-select" name="location_id">
<option value="">All Locations</option>
<?php foreach($locs as $l): ?>
<option value="<?= $l['id'] ?>" <?= ($locId==$l['id']?'selected':'') ?>>
<?= h($l['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2 d-grid">
<button class="btn btn-outline-secondary">
Search
</button>
</div>

</form>

</div>
</div>

<!-- Product Table -->
<div class="card shadow-sm">
<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>Code</th>
<th>Name</th>
<th>Category</th>
<th>Supplier</th>
<th>Location</th>
<th class="text-end">Price</th>
<th class="text-center">Stock</th>
<th class="text-center">Reserved</th>
<th class="text-center">Available</th>
<th>Barcode</th>
<th class="text-center">Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($st as $p): $low = is_low_stock($pdo,$p); ?>

<tr>

<td><?= h($p['code']) ?></td>

<td>
<?= h($p['name']) ?>
<?= $low ? '<span class="badge bg-danger ms-1">Low</span>' : '' ?>
</td>

<td><?= h($p['category_name'] ?? '') ?></td>

<td><?= h($p['supplier_name']) ?></td>

<td><?= h($p['location_name']) ?></td>

<td class="text-end fw-semibold">
₱<?= number_format($p['sell_price'],2) ?>
</td>

<td class="text-center">
<?= fmt_stock($p['stock_qty'],$p['sold_by'],$p['unit']) ?>
</td>

<td class="text-center">
<?= fmt_stock($p['reserved_qty'],$p['sold_by'],$p['unit']) ?>
</td>

<td class="text-center">
<?= fmt_stock($p['available_stock'],$p['sold_by'],$p['unit']) ?>
</td>

<td>
<?php if(!empty($p['barcode'])): ?>
<svg class="barcode"
     jsbarcode-value="<?= h($p['barcode']) ?>"
     jsbarcode-height="24"></svg>
<?php endif; ?>
</td>

<td class="text-end">

<a class="btn btn-sm btn-warning"
   href="/reserve_product.php?id=<?= $p['id'] ?>">
Reserve
</a>

<a class="btn btn-sm btn-outline-secondary"
   href="/product_edit.php?id=<?= $p['id'] ?>">
Edit
</a>

<?php if(has_role('admin')): ?>
<a class="btn btn-sm btn-outline-danger"
   href="/product_delete.php?id=<?= $p['id'] ?>"
   onclick="return confirm('Archive this product?');">
Archive
</a>
<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
if (window.JsBarcode) {
document.querySelectorAll('svg.barcode').forEach(el => {
JsBarcode(el, el.getAttribute('jsbarcode-value'), {
height: 24,
displayValue: false
});
});
}
});
</script>

<?php require_once __DIR__.'/includes/footer.php'; ?>