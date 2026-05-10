<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff','auditor']);
require_login();
$pdo = getDB();

/* ---------- Dates ---------- */
$today        = date('Y-m-d');
$monthStart   = date('Y-m-01');
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
$thirtyAgo    = date('Y-m-d', strtotime('-29 days')); // 30 points incl today

/* ---------- Helpers ---------- */
$lineNetExpr = "GREATEST(
  0,
  (ti.qty * ti.unit_price) -
  CASE
    WHEN ti.discount_type='percent' AND ti.discount_value IS NOT NULL
      THEN (ti.qty * ti.unit_price) * (ti.discount_value/100)
    WHEN ti.discount_type='amount'  AND ti.discount_value IS NOT NULL
      THEN ti.discount_value
    ELSE 0
  END
)";

/* ---------- KPIs ---------- */
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$totalUnits    = (int)$pdo->query("SELECT COALESCE(SUM(stock_qty),0) FROM products WHERE is_active=1")->fetchColumn();
$lowCount = 0; try { $lowCount = get_low_stock_count($pdo); } catch (Throwable $e) { $lowCount = 0; }

$st = $pdo->prepare("
  SELECT COALESCE(SUM($lineNetExpr),0) FROM transactions t
  JOIN transaction_items ti ON ti.transaction_id=t.id
  WHERE t.type='sale' AND t.date = ?
"); $st->execute([$today]); $salesToday = (float)$st->fetchColumn();

$st = $pdo->prepare("
  SELECT COALESCE(SUM($lineNetExpr),0) FROM transactions t
  JOIN transaction_items ti ON ti.transaction_id=t.id
  WHERE t.type='sale' AND t.date BETWEEN ? AND ?
"); $st->execute([$monthStart, $today]); $salesMonth = (float)$st->fetchColumn();

$st = $pdo->prepare("
  SELECT COALESCE(SUM($lineNetExpr),0) FROM transactions t
  JOIN transaction_items ti ON ti.transaction_id=t.id
  WHERE t.type='purchase' AND t.date = ?
"); $st->execute([$today]); $purchasesToday = (float)$st->fetchColumn();

$st = $pdo->prepare("
  SELECT COALESCE(SUM($lineNetExpr),0) FROM transactions t
  JOIN transaction_items ti ON ti.transaction_id=t.id
  WHERE t.type='purchase' AND t.date BETWEEN ? AND ?
"); $st->execute([$monthStart, $today]); $purchasesMonth = (float)$st->fetchColumn();

/* ---------- Recent movements ---------- */
$rows = $pdo->query("
  SELECT
    t.id, t.type, t.ref_no, t.date, t.created_at,
    u.name AS user_name,
    s.name AS supplier_name,
    c.name AS customer_name,
    COUNT(ti.id) AS line_count,
    SUM(ti.qty)  AS qty_total,
    SUM($lineNetExpr) AS net_value
  FROM transactions t
  JOIN users u              ON u.id = t.user_id
  LEFT JOIN suppliers s     ON s.id = t.supplier_id
  LEFT JOIN customers c     ON c.id = t.customer_id
  JOIN transaction_items ti ON ti.transaction_id = t.id
  GROUP BY t.id
  ORDER BY t.date DESC, t.id DESC
  LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Low stock ---------- */
$globalLow = (int)get_setting($pdo,'low_stock_default',5);
$lowRows = $pdo->prepare("
  SELECT id, code, name, stock_qty, COALESCE(low_stock_threshold, ?) AS threshold
  FROM products
  WHERE is_active=1 AND stock_qty < COALESCE(low_stock_threshold, ?)
  ORDER BY stock_qty ASC
  LIMIT 10
"); $lowRows->execute([$globalLow, $globalLow]);
$lowList = $lowRows->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Expiry ---------- */
$expiredCount = (int)$pdo->query("
  SELECT COUNT(*) FROM product_batches
  WHERE expiry_date < CURDATE()
  AND remaining_qty > 0
")->fetchColumn();

$expiringCount = (int)$pdo->query("
  SELECT COUNT(*) FROM product_batches
  WHERE expiry_date BETWEEN CURDATE()
  AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  AND remaining_qty > 0
")->fetchColumn();

/* ---------- Top sellers ---------- */
$top = $pdo->prepare("
  SELECT p.code, p.name, SUM(ti.qty) AS qty_sold, SUM($lineNetExpr) AS amount
  FROM transactions t
  JOIN transaction_items ti ON ti.transaction_id=t.id
  JOIN products p           ON p.id = ti.product_id
  WHERE t.type='sale' AND t.date BETWEEN ? AND ?
  GROUP BY p.id
  ORDER BY qty_sold DESC
  LIMIT 5
"); $top->execute([$sevenDaysAgo,$today]);
$topItems = $top->fetchAll(PDO::FETCH_ASSOC);

/* ---------- 30-day chart data ---------- */
$labels=[];$sales30=[];$purch30=[];$mapSales=[];$mapPurch=[];
for ($d=strtotime($thirtyAgo);$d<=strtotime($today);$d=strtotime('+1 day',$d)){
  $k=date('Y-m-d',$d);
  $labels[]=date('M j',$d);
  $mapSales[$k]=0;$mapPurch[$k]=0;
}

$sp=$pdo->prepare("
SELECT t.date d,COALESCE(SUM($lineNetExpr),0) amt
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale' AND t.date BETWEEN ? AND ?
GROUP BY t.date
"); $sp->execute([$thirtyAgo,$today]);
foreach($sp as $r){$mapSales[$r['d']]=$r['amt'];}

$pp=$pdo->prepare("
SELECT t.date d,COALESCE(SUM($lineNetExpr),0) amt
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='purchase' AND t.date BETWEEN ? AND ?
GROUP BY t.date
"); $pp->execute([$thirtyAgo,$today]);
foreach($pp as $r){$mapPurch[$r['d']]=$r['amt'];}

$sales30=array_values($mapSales);
$purch30=array_values($mapPurch);
?>

<!-- Dashboard header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-0">Dashboard</h3>
    <small class="text-muted">Sales & Inventory Overview</small>
  </div>
  <div class="d-flex gap-2">
    <a href="/sale_add.php" class="btn btn-primary shadow-sm">+ New Sale</a>
    <a href="/purchase_add.php" class="btn btn-outline-primary shadow-sm">+ New Purchase</a>
  </div>
</div>

<!-- KPI cards -->
<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card shadow-sm border-0 h-100">
<div class="card-body">
<small class="text-muted">Products</small>
<h2 class="fw-bold"><?=number_format($totalProducts)?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm border-0 h-100">
<div class="card-body">
<small class="text-muted">Stock on Hand</small>
<h2 class="fw-bold"><?=number_format($totalUnits)?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm border-0 h-100">
<div class="card-body">
<small class="text-muted">Sales (This Month)</small>
<h5 class="fw-bold text-success">₱<?=number_format($salesMonth,2)?></h5>
<small class="text-muted">Today ₱<?=number_format($salesToday,2)?></small>
<canvas id="mtSparkSales" height="40"></canvas>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm border-0 h-100">
<div class="card-body">
<small class="text-muted">Purchases (This Month)</small>
<h5 class="fw-bold text-primary">₱<?=number_format($purchasesMonth,2)?></h5>
<small class="text-muted">Today ₱<?=number_format($purchasesToday,2)?></small>
<canvas id="mtSparkPurch" height="40"></canvas>
</div>
</div>
</div>

</div>

<!-- Alerts -->
<div class="row g-3 mb-4">

<div class="col-md-4">
<div class="card shadow-sm border-0 bg-warning-subtle">
<div class="card-body">
<small class="text-muted">Low Stock</small>
<h3 class="fw-bold text-warning"><?=$lowCount?></h3>
<a href="/low_stock.php" class="small">Manage →</a>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm border-0 bg-danger-subtle">
<div class="card-body">
<small class="text-muted">Expired Items</small>
<h3 class="fw-bold text-danger"><?=$expiredCount?></h3>
<a href="/expiry.php" class="small text-danger">View →</a>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm border-0 bg-warning-subtle">
<div class="card-body">
<small class="text-muted">Expiring Soon</small>
<h3 class="fw-bold text-warning"><?=$expiringCount?></h3>
<a href="/expiry.php" class="small text-warning">View →</a>
</div>
</div>
</div>

</div>

<div class="row g-3">

<!-- Recent Transactions -->
<div class="col-lg-7">
<div class="card shadow-sm border-0">
<div class="card-header bg-white fw-semibold">Recent Transactions</div>

<div class="table-responsive">
<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>Date</th>
<th>Type</th>
<th>Ref</th>
<th>Customer</th>
<th class="text-end">Qty</th>
<th class="text-end">Amount</th>
</tr>
</thead>

<tbody>
<?php foreach($rows as $t): ?>
<tr>
<td><?=date('M d H:i',strtotime($t['created_at']?:$t['date']))?></td>

<td>
<?= $t['type']=='purchase'
? '<span class="badge bg-success">Purchase</span>'
: '<span class="badge bg-primary">Sale</span>' ?>
</td>

<td class="fw-semibold"><?=$t['ref_no']?></td>
<td><?= $t['customer_name'] ?? 'Walk-in' ?></td>

<td class="text-end"><?=$t['qty_total']?></td>
<td class="text-end fw-semibold">₱<?=number_format($t['net_value'],2)?></td>

</tr>
<?php endforeach ?>
</tbody>

</table>
</div>

</div>
</div>

<!-- Right side -->
<div class="col-lg-5 d-flex flex-column gap-3">

<!-- Low Stock -->
<div class="card shadow-sm border-0">
<div class="card-header bg-white fw-semibold">Low Stock</div>

<table class="table table-sm table-hover mb-0">
<thead class="table-light">
<tr>
<th>Code</th>
<th>Item</th>
<th class="text-center">Qty</th>
<th class="text-center">Min</th>
</tr>
</thead>

<tbody>
<?php foreach($lowList as $p): ?>
<tr class="table-warning">
<td><?=$p['code']?></td>
<td><?=$p['name']?></td>
<td class="text-center"><?=$p['stock_qty']?></td>
<td class="text-center"><?=$p['threshold']?></td>
</tr>
<?php endforeach ?>
</tbody>
</table>

</div>

<!-- Top Sellers -->
<div class="card shadow-sm border-0">
<div class="card-header bg-white fw-semibold">Top Sellers (7 Days)</div>

<table class="table table-sm table-hover mb-0">
<thead class="table-light">
<tr>
<th>Code</th>
<th>Item</th>
<th class="text-end">Qty</th>
<th class="text-end">Amount</th>
</tr>
</thead>

<tbody>
<?php foreach($topItems as $it): ?>
<tr>
<td><?=$it['code']?></td>
<td><?=$it['name']?></td>
<td class="text-end"><?=$it['qty_sold']?></td>
<td class="text-end fw-semibold">₱<?=number_format($it['amount'],2)?></td>
</tr>
<?php endforeach ?>
</tbody>
</table>

</div>

</div>
</div>

<!-- Chart -->
<div class="card shadow-sm border-0 mt-4">
<div class="card-header bg-white fw-semibold">Sales vs Purchases (Last 30 Days)</div>
<div class="p-3">
<canvas id="bar30"></canvas>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
const labels=<?=json_encode($labels)?>;
const sales30=<?=json_encode($sales30)?>;
const purch30=<?=json_encode($purch30)?>;

new Chart(document.getElementById('mtSparkSales'),{
type:'line',
data:{labels,datasets:[{data:sales30,borderWidth:2,pointRadius:0,tension:.35}]},
options:{plugins:{legend:{display:false}},scales:{x:{display:false},y:{display:false}}}
});

new Chart(document.getElementById('mtSparkPurch'),{
type:'line',
data:{labels,datasets:[{data:purch30,borderWidth:2,pointRadius:0,tension:.35}]},
options:{plugins:{legend:{display:false}},scales:{x:{display:false},y:{display:false}}}
});

new Chart(document.getElementById('bar30'),{
type:'bar',
data:{labels,datasets:[
{label:'Sales',data:sales30},
{label:'Purchases',data:purch30}
]},
options:{responsive:true,plugins:{legend:{position:'top'}}}
});
})();
</script>

<?php require_once __DIR__.'/includes/footer.php'; ?>