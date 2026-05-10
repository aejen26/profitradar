<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/includes/header.php';
require_login();

$pdo = getDB();

/* ---------- FILTERS ---------- */
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

/* ---------- QUERY ---------- */
$sql = "
SELECT 
    p.id,
    p.name,
    p.sell_price,
    p.low_stock_threshold,
    p.sold_by,

    c.name AS category_name,
    s.name AS supplier_name,

    p.stock_qty AS stock,

    p.stock_qty AS valid_stock,

    COALESCE(MIN(
        CASE 
            WHEN pb.expiry_date >= CURDATE() 
            THEN pb.expiry_date 
        END
    ), NULL) AS next_expiry,

    COALESCE(SUM(
        CASE 
            WHEN pb.expiry_date < CURDATE() 
            THEN 1 
            ELSE 0 
        END
    ),0) AS expired_batches

FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN suppliers s ON p.supplier_id = s.id
LEFT JOIN product_batches pb 
    ON pb.product_id = p.id AND pb.status = 'active'

WHERE p.is_active = 1
";

if ($search) {
    $sql .= " AND p.name LIKE :search ";
}

$sql .= " GROUP BY p.id ";

$stmt = $pdo->prepare($sql);

if ($search) {
    $stmt->bindValue(':search', "%$search%");
}

$stmt->execute();
$products = $stmt->fetchAll();

/* ---------- SUMMARY CALCULATION ---------- */
$lowCount = 0;
$expiredCount = 0;
$expiringCount = 0;

foreach ($products as $p) {

    $stock = $p['stock'] ?? 0;
    $validStock = $p['valid_stock'] ?? 0;
    $threshold = $p['low_stock_threshold'] ?? 5;

    if ($validStock <= 0 && $stock > 0) {
        $expiredCount++;
    } elseif ($validStock > 0 && $validStock <= $threshold) {
        $lowCount++;
    }

    if (!empty($p['next_expiry'])) {
        $days = (strtotime($p['next_expiry']) - time()) / 86400;
        if ($days <= 7 && $days >= 0) {
            $expiringCount++;
        }
    }
}
?>

<div class="container mt-3">

<h4>📦 Inventory</h4>

<!-- SUMMARY CARDS -->
<div class="row mb-3">

<div class="col-md-4">
<a href="?filter=low" style="text-decoration:none;">
<div class="card p-3 shadow-sm bg-warning text-dark">
Low Stock: <b><?= $lowCount ?></b>
</div>
</a>
</div>

<div class="col-md-4">
<a href="?filter=expired" style="text-decoration:none;">
<div class="card p-3 shadow-sm bg-danger text-white">
Expired Items: <b><?= $expiredCount ?></b>
</div>
</a>
</div>

<div class="col-md-4">
<a href="?filter=expiring" style="text-decoration:none;">
<div class="card p-3 shadow-sm bg-info text-white">
Expiring Soon: <b><?= $expiringCount ?></b>
</div>
</a>
</div>

</div>

<form class="row mb-3">
<div class="col-md-5">
<input type="text" name="search" class="form-control" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>">
</div>

<div class="col-md-3">
<select name="filter" class="form-control">
<option value="">All</option>
<option value="low" <?= $filter=='low'?'selected':'' ?>>Low Stock</option>
<option value="out" <?= $filter=='out'?'selected':'' ?>>Out of Stock</option>
<option value="expiring" <?= $filter=='expiring'?'selected':'' ?>>Expiring Soon</option>
<option value="expired" <?= $filter=='expired'?'selected':'' ?>>Expired</option>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary">Apply</button>
</div>
</form>

<div class="card shadow-sm">
<div class="card-body">

<table class="table table-hover table-bordered align-middle">
<thead class="table-light">
<tr>
<th>Product</th>
<th>Category</th>
<th>Supplier</th>
<th>Stock</th>
<th>Valid</th>
<th>%</th>
<th>Batches</th>
<th>Expiry Info</th>
<th>Status</th>
<th style="width:140px;">Action</th>
</tr>
</thead>

<tbody>

<?php foreach($products as $p): 

$stock = $p['stock'] ?? 0;
$validStock = $p['valid_stock'] ?? 0;
$threshold = $p['low_stock_threshold'] ?? 5;

if ($p['sold_by'] === 'each') {
    $stock = (int)$stock;
    $validStock = (int)$validStock;
}

$percent = $stock > 0 ? round(($validStock/$stock)*100) : 0;

/* STATUS */
if ($validStock <= 0 && $stock > 0) {
    $status = '<span class="badge bg-danger">Expired</span>';
} elseif ($stock <= 0) {
    $status = '<span class="badge bg-danger">Out</span>';
} elseif ($validStock <= $threshold) {
    $status = '<span class="badge bg-warning text-dark">Low</span>';
} else {
    $status = '<span class="badge bg-success">OK</span>';
}

/* REORDER (light version) */
$reorder = max(0, ($threshold * 2) - $validStock);

/* EXPIRY CLEAN */
if (!empty($p['next_expiry'])) {
    $expiryText = "<b>" . htmlspecialchars($p['next_expiry']) . "</b>";
} else {
    $expiryText = '<span class="text-muted">No expiry</span>';
}

if (!empty($p['expired_batches'])) {
    $expiryText .= "<br><small class='text-danger'>Expired: {$p['expired_batches']}</small>";
}

/* FILTER */
if ($filter == 'low' && $validStock > $threshold) continue;
if ($filter == 'out' && $validStock > 0) continue;

/* ROW COLOR */
$rowClass = '';
if ($validStock <= 0 && $stock > 0) $rowClass = 'table-danger';
elseif ($validStock <= $threshold) $rowClass = 'table-warning';

?>

<tr class="<?= $rowClass ?>">

<td>
<b><?= htmlspecialchars($p['name']) ?></b>

<?php if($reorder > 0): ?>
<br><small class="text-danger">Reorder: <?= $reorder ?> pcs</small>
<?php endif; ?>

</td>

<td><?= $p['category_name'] ?? '—' ?></td>
<td><?= $p['supplier_name'] ?? '—' ?></td>

<td><?= $stock ?></td>

<td>
<b><?= $validStock ?></b><br>
<small class="text-muted"><?= $percent ?>%</small>
</td>

<td>

<?php
$batchStmt = $pdo->prepare("
    SELECT batch_no, remaining_qty, expiry_date
    FROM product_batches
    WHERE product_id = ? AND status='active'
    ORDER BY expiry_date ASC
    LIMIT 2
");
$batchStmt->execute([$p['id']]);
$batches = $batchStmt->fetchAll();

foreach ($batches as $b):
$today = date('Y-m-d');

if ($b['expiry_date'] && $b['expiry_date'] < $today) {
    $badge = '<span class="badge bg-danger">Expired</span>';
} elseif ($b['expiry_date'] && strtotime($b['expiry_date']) <= strtotime('+7 days')) {
    $badge = '<span class="badge bg-warning text-dark">Soon</span>';
} else {
    $badge = '<span class="badge bg-success">Safe</span>';
}
?>

<div style="font-size:12px;">
<?= (int)$b['remaining_qty'] ?> pcs
<?= $badge ?>
</div>

<?php endforeach; ?>

<!-- BATCHES -->
<td>
<?php
$batchStmt = $pdo->prepare("
    SELECT remaining_qty, expiry_date
    FROM product_batches
    WHERE product_id = ? AND status='active'
    ORDER BY expiry_date ASC
    LIMIT 2
");
$batchStmt->execute([$p['id']]);
$batches = $batchStmt->fetchAll();

foreach ($batches as $b):
$today = date('Y-m-d');

if ($b['expiry_date'] && $b['expiry_date'] < $today) {
    $badge = '<span class="badge bg-danger">Expired</span>';
} elseif ($b['expiry_date'] && strtotime($b['expiry_date']) <= strtotime('+7 days')) {
    $badge = '<span class="badge bg-warning text-dark">Soon</span>';
} else {
    $badge = '<span class="badge bg-success">Safe</span>';
}
?>
<div style="font-size:12px;">
<?= (int)$b['remaining_qty'] ?> pcs <?= $badge ?>
</div>
<?php endforeach; ?>
</td>
<!-- ✅ END -->

<td><?= $expiryText ?></td>
<td><?= $status ?></td>

<td style="white-space:nowrap;">
<a href="batches.php?product_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1">View</a>
<a href="purchase.php?product_id=<?= $p['id'] ?>" class="btn btn-sm btn-success">+ Stock</a>
</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

</div>