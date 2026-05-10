<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff','auditor']);

$pdo = getDB();

/* ================= INVENTORY DATA ================= */

$sql = "
SELECT 
    p.id,
    p.code,
    p.name,
    p.stock_qty,
    p.reserved_qty,
    GREATEST(p.stock_qty - p.reserved_qty,0) AS available_stock,
    p.cost_price,
    (p.stock_qty * p.cost_price) AS stock_value,
    c.name AS category,
    s.name AS supplier
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN suppliers s ON p.supplier_id = s.id
WHERE p.is_active = 1
ORDER BY p.name ASC
";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ================= INVENTORY METRICS ================= */

$totalValue = 0;
$totalStock = 0;
$totalReserved = 0;
$lowStock = 0;
$outOfStock = 0;

foreach ($products as $p) {

    $totalValue += $p['stock_value'];
    $totalStock += $p['stock_qty'];
    $totalReserved += $p['reserved_qty'];

    if ($p['stock_qty'] <= 0) $outOfStock++;
    if ($p['stock_qty'] <= 5) $lowStock++;
}

$productCount = count($products);


/* ================= INVENTORY TURNOVER ================= */

$stmt = $pdo->query("
SELECT SUM(ti.qty * ti.cost_at_sale)
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id = t.id
WHERE t.type='sale'
");

$cogs = $stmt->fetchColumn() ?? 0;

$turnover = $totalValue > 0 ? $cogs / $totalValue : 0;
$daysInventory = $turnover > 0 ? 365 / $turnover : 0;

?>


<style>

.kpi-card{
border-left:4px solid #0d6efd;
}

.kpi-warning{
border-left:4px solid #ffc107;
}

.kpi-danger{
border-left:4px solid #dc3545;
}

.progress-sm{
height:6px;
}

.stock-low{
color:#dc3545;
font-weight:600;
}

</style>


<!-- HEADER -->

<div class="mb-4">
<h4 class="fw-semibold mb-0">Inventory Report</h4>
<small class="text-muted">Overview of inventory value and stock health</small>
</div>


<!-- KPI DASHBOARD -->

<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card shadow-sm kpi-card">
<div class="card-body">
<div class="text-muted small">Products</div>
<div class="fs-4 fw-bold"><?=number_format($productCount)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm kpi-card">
<div class="card-body">
<div class="text-muted small">Inventory Value</div>
<div class="fs-4 fw-bold">₱<?=number_format($totalValue,2)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm kpi-warning">
<div class="card-body">
<div class="text-muted small">Low Stock</div>
<div class="fs-4 fw-bold text-warning"><?=number_format($lowStock)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm kpi-danger">
<div class="card-body">
<div class="text-muted small">Out of Stock</div>
<div class="fs-4 fw-bold text-danger"><?=number_format($outOfStock)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Stock Units</div>
<div class="fs-4 fw-bold"><?=number_format($totalStock)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Reserved Units</div>
<div class="fs-4 fw-bold"><?=number_format($totalReserved)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Inventory Turnover</div>
<div class="fs-4 fw-bold"><?=number_format($turnover,2)?></div>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Days of Inventory</div>
<div class="fs-4 fw-bold"><?=number_format($daysInventory,0)?> days</div>
</div>
</div>
</div>

</div>


<!-- INVENTORY TABLE -->

<div class="card shadow-sm">

<div class="card-body border-bottom">
<strong>Inventory Breakdown</strong>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>
<th>Code</th>
<th>Product</th>
<th>Category</th>
<th>Supplier</th>
<th class="text-center">Stock</th>
<th class="text-center">Reserved</th>
<th class="text-center">Available</th>
<th class="text-end">Cost Price</th>
<th class="text-end">Stock Value</th>
<th style="width:160px">Inventory Share</th>
</tr>

</thead>

<tbody>

<?php foreach ($products as $p):

$percent = $totalValue > 0 
    ? ($p['stock_value'] / $totalValue) * 100 
    : 0;

$low = $p['stock_qty'] <= 5;

?>

<tr>

<td><?=htmlspecialchars($p['code'])?></td>

<td class="fw-semibold">
<?=htmlspecialchars($p['name'])?>
<?php if($low): ?>
<span class="badge bg-danger ms-1">Low</span>
<?php endif; ?>
</td>

<td><?=htmlspecialchars($p['category'])?></td>

<td><?=htmlspecialchars($p['supplier'])?></td>

<td class="text-center <?=$low?'stock-low':''?>">
<?=number_format($p['stock_qty'])?>
</td>

<td class="text-center"><?=number_format($p['reserved_qty'])?></td>

<td class="text-center fw-semibold"><?=number_format($p['available_stock'])?></td>

<td class="text-end">₱<?=number_format($p['cost_price'],2)?></td>

<td class="text-end fw-semibold">₱<?=number_format($p['stock_value'],2)?></td>

<td>

<div class="small mb-1">
<?=number_format($percent,2)?>%
</div>

<div class="progress progress-sm">
<div class="progress-bar bg-primary"
style="width:<?=$percent?>%">
</div>
</div>

</td>

</tr>

<?php endforeach; ?>

</tbody>

<tfoot class="table-light">

<tr>

<td colspan="8" class="text-end fw-bold">
Total Inventory Value
</td>

<td class="text-end fw-bold">
₱<?=number_format($totalValue,2)?>
</td>

<td class="text-center fw-bold">
100%
</td>

</tr>

</tfoot>

</table>

</div>

</div>

<?php require_once '/includes/footer.php'; ?>