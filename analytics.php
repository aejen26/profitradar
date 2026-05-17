<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff','auditor']);

$pdo = getDB();

/* ---------------- FILTERS ---------------- */

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-d');

$category = $_GET['category'] ?? null;
$productFilter = $_GET['product_filter'] ?? null;
/* ---------------- KPI ---------------- */

$stmt=$pdo->query("
SELECT COALESCE(SUM(ti.qty * ti.unit_price),0)
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end' AND DATE(t.date)=CURDATE()
");
$salesToday=$stmt->fetchColumn();

$stmt=$pdo->query("
SELECT COUNT(*) FROM transactions
WHERE type='sale' AND DATE(date)=CURDATE()
");
$ordersToday=$stmt->fetchColumn();

$global=(int)get_setting($pdo,'low_stock_default',5);

$stmt=$pdo->prepare("
SELECT COUNT(*) FROM products
WHERE is_active=1
AND stock_qty <= COALESCE(low_stock_threshold,?)
");
$stmt->execute([$global]);
$lowStock=$stmt->fetchColumn();

$stmt=$pdo->query("
SELECT COALESCE(SUM(ti.qty * ti.unit_price),0)
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
AND MONTH(t.date)=MONTH(CURDATE())
AND YEAR(t.date)=YEAR(CURDATE())
");
$monthlyRevenue=$stmt->fetchColumn();

$stmt=$pdo->query("SELECT COALESCE(SUM(stock_qty * cost_price),0) FROM products");
$totalInventoryValue=$stmt->fetchColumn();

/* ---------------- SALES TREND ---------------- */

$stmt=$pdo->query("
SELECT DATE(t.date) d,
SUM(ti.qty * ti.unit_price) total
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY d ORDER BY d
");

$dates=[];$sales=[];
while($r=$stmt->fetch()){
$dates[]=$r['d'];
$sales[]=$r['total'];
}

/* ---------------- TOP PRODUCTS ---------------- */

$stmt=$pdo->query("
SELECT p.name,SUM(ti.qty) qty
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id=t.id
JOIN products p ON ti.product_id=p.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY p.id
ORDER BY qty DESC LIMIT 5
");

$productNames=[];$productQty=[];
while($r=$stmt->fetch()){
$productNames[]=$r['name'];
$productQty[]=$r['qty'];
}

/* ---------------- CATEGORY SALES ---------------- */

$stmt=$pdo->query("
SELECT c.name,
SUM(ti.qty * ti.unit_price) total
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id=t.id
JOIN products p ON ti.product_id=p.id
JOIN categories c ON p.category_id=c.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY c.id
");

$catNames=[];$catTotals=[];
while($r=$stmt->fetch()){
$catNames[]=$r['name'];
$catTotals[]=$r['total'];
}

/* ---------------- MONTHLY REVENUE ---------------- */

$stmt=$pdo->query("
SELECT DATE_FORMAT(t.date,'%Y-%m') month,
SUM(ti.qty * ti.unit_price) total
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY month ORDER BY month
");

$months=[];$monthTotals=[];
while($r=$stmt->fetch()){
$months[]=$r['month'];
$monthTotals[]=$r['total'];
}

/* ---------------- PROFIT ---------------- */

$stmt=$pdo->query("
SELECT p.name,
SUM((ti.unit_price - ti.cost_at_sale) * ti.qty) profit
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id=t.id
JOIN products p ON ti.product_id=p.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY p.id ORDER BY profit DESC LIMIT 5
");

$profitNames=[];$profits=[];
while($r=$stmt->fetch()){
$profitNames[]=$r['name'];
$profits[]=$r['profit'];
}

/* ---------------- SLOW PRODUCTS ---------------- */

$stmt=$pdo->query("
SELECT p.name, COALESCE(SUM(ti.qty),0) sold
FROM products p
LEFT JOIN transaction_items ti 
    ON ti.product_id = p.id
LEFT JOIN transactions t 
    ON ti.transaction_id = t.id
WHERE (t.type='sale' 
       AND DATE(t.date) BETWEEN '$start' AND '$end')
   OR t.id IS NULL
GROUP BY p.id
ORDER BY sold ASC
LIMIT 5
");

$slowNames=[];$slowQty=[];
while($r=$stmt->fetch()){
$slowNames[]=$r['name'];
$slowQty[]=$r['sold'];
}

/* ---------------- SALES BY HOUR ---------------- */

$stmt=$pdo->query("
SELECT HOUR(t.date) hr,
SUM(ti.qty * ti.unit_price) revenue
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY hr ORDER BY hr
");

$hours=[];$hourSales=[];
while($r=$stmt->fetch()){
$hours[]=$r['hr'];
$hourSales[]=$r['revenue'];
}

/* ---------------- SALES GROWTH ---------------- */

$stmt=$pdo->query("
SELECT DATE_FORMAT(t.date,'%Y-%m') month,
SUM(ti.qty * ti.unit_price) revenue
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY month ORDER BY month
");

$growthMonths=[];$growthValues=[];
$prev=null;
while($r=$stmt->fetch()){
    $growthMonths[]=$r['month'];

    if($prev === null){
        $growthValues[] = 0;
    } elseif($prev == 0){
        $growthValues[] = 0;
    } else {
        $growthValues[] = round((($r['revenue'] - $prev) / $prev) * 100, 2);
    }

    $prev = $r['revenue']; // ← ADD THIS
}

/* ---------------- INVENTORY HEALTH ---------------- */

$stmt=$pdo->prepare("
SELECT
SUM(stock_qty=0) out_stock,
SUM(stock_qty <= COALESCE(low_stock_threshold,?)) low_stock,
COUNT(*) total
FROM products WHERE is_active=1
");
$stmt->execute([$global]);
$inv=$stmt->fetch();
$healthy=$inv['total']-$inv['low_stock']-$inv['out_stock'];

/* ---------------- FINANCIAL SUMMARY ---------------- */

$stmt=$pdo->query("
SELECT
SUM(ti.qty * ti.unit_price) revenue,
SUM(ti.qty * ti.cost_at_sale) cost
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
");
$row=$stmt->fetch();
$revenue=$row['revenue'];
$cost=$row['cost'];
$profit=$revenue-$cost;
$roi = $cost > 0 ? round(($profit / $cost) * 100, 2) : 0;

/* ---------------- PRODUCT DEMAND ---------------- */

$products=$pdo->query("SELECT id,name FROM products ORDER BY name")->fetchAll();
$selected=$_GET['product'] ?? $products[0]['id'];

$stmt=$pdo->prepare("
SELECT DATE(t.date) d,SUM(ti.qty) qty
FROM transactions t
JOIN transaction_items ti ON ti.transaction_id=t.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end' AND ti.product_id=?
GROUP BY d ORDER BY d
");
$stmt->execute([$selected]);

$demandDates=[];$demandQty=[];
while($r=$stmt->fetch()){
$demandDates[]=$r['d'];
$demandQty[]=$r['qty'];
}

/* ---------------- FORECAST ---------------- */

$forecastMonths=$months;
$forecastRevenue=$monthTotals;

$last3=array_slice($monthTotals,-3);
$forecast=array_sum($last3)/max(count($last3),1);

$forecastMonths[]="Forecast";
$forecastRevenue[]=$forecast;

/* ---------------- RESTOCK ---------------- */

$days = max(1, (strtotime($end) - strtotime($start)) / 86400);

$stmt=$pdo->query("
SELECT p.name,
COALESCE(SUM(ti.qty),0)/$days avg_daily_sales,
p.stock_qty
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id=t.id
JOIN products p ON ti.product_id=p.id
WHERE t.type='sale'
AND DATE(t.date) BETWEEN '$start' AND '$end'
GROUP BY p.id
");

$restockNames=[];$restockQty=[];
while($r=$stmt->fetch()){
    $reorder = round($r['avg_daily_sales'] * 7);

    $restockNames[] = $r['name'];
$restockQty[] = max(0, $reorder - $r['stock_qty']);
    }
?>

<div class="container-fluid mt-4">

<h2>Analytics Dashboard</h2>

<form method="GET" class="row g-2 mb-4">

<div class="col-md-3">
<label class="form-label">Start Date</label>
<input type="date" name="start" class="form-control"
value="<?= $start ?>">
</div>

<div class="col-md-3">
<label class="form-label">End Date</label>
<input type="date" name="end" class="form-control"
value="<?= $end ?>">
</div>

<div class="col-md-3">
<label class="form-label">Category</label>
<select name="category" class="form-control">
<option value="">All Categories</option>
<?php
$cats=$pdo->query("SELECT id,name FROM categories ORDER BY name");
foreach($cats as $c){
$sel=($category==$c['id'])?'selected':'';
echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<label class="form-label">Product</label>
<select name="product_filter" class="form-control">
<option value="">All Products</option>
<?php
$prods=$pdo->query("SELECT id,name FROM products ORDER BY name");
foreach($prods as $p){
$sel=($productFilter==$p['id'])?'selected':'';
echo "<option value='{$p['id']}' $sel>{$p['name']}</option>";
}
?>
</select>
</div>

<div class="col-md-1 d-flex align-items-end">
<button class="btn btn-primary w-100">Apply</button>
</div>

</form>

<div class="row mt-4">

<div class="row mt-4">

<div class="col-md-3">
<div class="card shadow-sm border-0 p-3">
<small class="text-muted">Sales Today</small>
<h4 class="fw-bold text-primary">₱<?=number_format($salesToday,2)?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm border-0 p-3">
<small class="text-muted">Orders Today</small>
<h4 class="fw-bold text-success"><?=$ordersToday?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm border-0 p-3">
<small class="text-muted">Low Stock Items</small>
<h4 class="fw-bold text-warning"><?=$lowStock?></h4>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm border-0 p-3">
<small class="text-muted">Monthly Revenue</small>
<h4 class="fw-bold text-dark">₱<?=number_format($monthlyRevenue,2)?></h4>
</div>
</div>

</div>

<div class="row mt-3">
<div class="col-md-3">
<div class="card shadow-sm border-0 p-3">
<small class="text-muted">Total Inventory Value</small>
<h4 class="fw-bold text-dark">₱<?=number_format($totalInventoryValue,2)?></h4>
</div>
</div>
</div>

<hr>

<div class="row mt-4">

<div class="col-md-6"><div class="card p-3">
<h5>Sales Trend</h5><canvas id="salesTrend"></canvas>
</div></div>

<div class="col-md-6"><div class="card p-3">
<h5>Top Products</h5><canvas id="topProducts"></canvas>
</div></div>

</div>

<div class="row mt-4">

<div class="col-md-6"><div class="card p-3">
<h5>Category Sales</h5><canvas id="categoryChart"></canvas>
</div></div>

<div class="col-md-6"><div class="card p-3">
<h5>Monthly Revenue</h5><canvas id="monthlyRevenue"></canvas>
</div></div>

</div>

<div class="row mt-4">

<div class="col-md-6"><div class="card p-3">
<h5>Most Profitable Products</h5><canvas id="profitChart"></canvas>
</div></div>

<div class="col-md-6"><div class="card p-3">
<h5>Slow Moving Products</h5><canvas id="slowProducts"></canvas>
</div></div>

</div>

<div class="row mt-4">
<div class="col-md-12"><div class="card p-3">
<h5>Sales by Hour</h5><canvas id="hourSales"></canvas>
</div></div>
</div>

<div class="row mt-4">

<div class="col-md-6"><div class="card p-3">
<h5>Sales Growth %</h5><canvas id="salesGrowth"></canvas>
</div></div>

<div class="col-md-6"><div class="card p-3">
<h5>Inventory Health</h5><canvas id="inventoryHealth"></canvas>
</div></div>

</div>

<div class="row mt-4">

<div class="col-md-6"><div class="card p-3">
<h5>Revenue vs Cost vs Profit</h5><canvas id="financeChart"></canvas>
</div></div>

<div class="col-md-6">
<div class="card shadow-sm border-0 p-3">

<h5>Return on Investment (ROI)</h5>

<!--<canvas id="roiChart"></canvas>

<div class="text-center mt-3">
<h4 class="fw-bold <?= $roi >= 0 ? 'text-success' : 'text-danger' ?>">
<?= $roi ?>%
</h4>
<small class="text-muted">Return on Investment</small>
</div>
</div>
    
</div>
</div>  
<div class="col-md-6"><div class="card p-3">-->

<h5>Product Demand Trend</h5>

<select id="productSelect" class="form-select mb-3">
<?php foreach($products as $p): ?>
<option value="<?=$p['id']?>" <?= $p['id']==$selected?'selected':'' ?>>
<?=$p['name']?>
</option>
<?php endforeach ?>
</select>

<canvas id="productDemand"></canvas>

</div></div>

</div>

<div class="row mt-4">

<div class="col-md-6"><div class="card p-3">
<h5>Sales Forecast</h5><canvas id="salesForecast"></canvas>
</div></div>

<div class="col-md-6"><div class="card p-3">
<h5>Restock Recommendation</h5><canvas id="restockChart"></canvas>
</div></div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

/* GLOBAL STYLE */
Chart.defaults.font.family = 'Segoe UI, sans-serif';
Chart.defaults.color = '#6c757d';

/* gradient helper */
function gradient(ctx, c1, c2){
    let g = ctx.createLinearGradient(0,0,0,300);
    g.addColorStop(0,c1);
    g.addColorStop(1,c2);
    return g;
}

/* SALES TREND */
let ctx1 = document.getElementById('salesTrend').getContext('2d');
new Chart(ctx1,{
type:'line',
data:{
labels:<?=json_encode($dates)?>,
datasets:[{
    label:'Sales Trend',
    data:<?=json_encode($sales)?>,
borderColor:'#0d6efd',
backgroundColor:gradient(ctx1,'rgba(13,110,253,0.4)','rgba(13,110,253,0.05)'),
fill:true,
tension:0.4
}]
},
options:{plugins:{legend:{display:false}}}
});

/* TOP PRODUCTS */
new Chart(document.getElementById('topProducts'),{
type:'bar',
data:{
labels:<?=json_encode($productNames)?>,
datasets:[{
data:<?=json_encode($productQty)?>,
backgroundColor:'#198754',
borderRadius:10
}]
},
options:{plugins:{legend:{display:false}}}
});

/* CATEGORY */
new Chart(document.getElementById('categoryChart'),{
type:'doughnut',
data:{
labels:<?=json_encode($catNames)?>,
datasets:[{
data:<?=json_encode($catTotals)?>,
backgroundColor:['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1']
}]
}
});

/* MONTHLY */
let ctx2 = document.getElementById('monthlyRevenue').getContext('2d');
new Chart(ctx2,{
type:'line',
data:{
labels:<?=json_encode($months)?>,
datasets:[{
    label:'Revenue',
    data:<?=json_encode($monthTotals)?>,
borderColor:'#20c997',
backgroundColor:gradient(ctx2,'rgba(32,201,151,0.4)','rgba(32,201,151,0.05)'),
fill:true,
tension:0.4
}]
},
options:{plugins:{legend:{display:false}}}
});

/* PROFIT */
new Chart(document.getElementById('profitChart'),{
type:'bar',
data:{
labels:<?=json_encode($profitNames)?>,
datasets:[{
    label:'Profit',   // ✅ ADD
    data:<?=json_encode($profits)?>,
    backgroundColor:'#0dcaf0',
    borderRadius:8
}]
}
});

/* SLOW PRODUCTS */
new Chart(document.getElementById('slowProducts'),{
type:'bar',
data:{
labels:<?=json_encode($slowNames)?>,
datasets:[{
    label:'Least Sold Products',
    data:<?=json_encode($slowQty)?>,
    backgroundColor:'#dc3545',
    borderRadius:8
}]
},
options:{
scales:{
y:{
beginAtZero:true,
ticks:{
stepSize:1,
precision:0
}
}
}
}
});

/* SALES BY HOUR */
new Chart(document.getElementById('hourSales'),{
type:'bar',
data:{
labels:<?=json_encode($hours)?>,
datasets:[{
    label:'Sales',   // ✅ ADD
    data:<?=json_encode($hourSales)?>,
    backgroundColor:'#6f42c1',
    borderRadius:8
}]
}
});

/* INVENTORY */
new Chart(document.getElementById('inventoryHealth'),{
type:'doughnut',
data:{
labels:['Healthy','Low','Out'],
datasets:[{
data:[<?=$healthy?>,<?=$inv['low_stock']?>,<?=$inv['out_stock']?>],
backgroundColor:['#198754','#ffc107','#dc3545']
}]
}
});

/* FINANCE */
new Chart(document.getElementById('financeChart'),{
type:'bar',
data:{
labels:['Revenue','Cost','Profit'],
datasets:[{
    label:'Financial Summary',   // ✅ ADD THIS
    data:[<?=$revenue?>,<?=$cost?>,<?=$profit?>],
    backgroundColor:['#0d6efd','#6c757d','#198754'],
    borderRadius:10
}]
}
});

/* SALES GROWTH */
new Chart(document.getElementById('salesGrowth'),{
type:'line',
data:{
labels:<?=json_encode($growthMonths)?>,
datasets:[{
label:'Growth %',
data:<?=json_encode($growthValues)?>,
borderColor:'#dc3545',
backgroundColor:'rgba(220,53,69,0.1)',
fill:true,
tension:0.4
}]
},
options:{
plugins:{
legend:{display:true}
},
scales:{
y:{
ticks:{
callback: function(value){
return value + '%';
}
}
}
}
}
});
    
/* DEMAND */
let demandChart = new Chart(document.getElementById('productDemand'),{
type:'line',
data:{
labels:[],
datasets:[{
    label:'Demand',   // ✅ ADD
    data:[],
    borderColor:'#fd7e14',
    backgroundColor:'rgba(253,126,20,0.1)',
    fill:true,
    tension:0.4
}]
},
options:{plugins:{legend:{display:false}}}
});

const select = document.getElementById('productSelect');

function loadDemand(productId){
    fetch('/api/product_demand.php?product_id=' + productId)
    .then(res => res.json())
    .then(data => {
        demandChart.data.labels = data.dates;
        demandChart.data.datasets[0].data = data.qty;
        demandChart.update();
    });
}

select.addEventListener('change', function(){
    loadDemand(this.value);
});

// load first product automatically
loadDemand(select.value);

/* FORECAST */
new Chart(document.getElementById('salesForecast'),{
type:'line',
data:{
labels:<?=json_encode($forecastMonths)?>,
datasets:[{
    label:'Forecast',   // ✅ ADD
    data:<?=json_encode($forecastRevenue)?>,
    borderColor:'#6610f2',
    borderDash:[5,5],
    tension:0.4
}]
}
});

/* RESTOCK */
new Chart(document.getElementById('restockChart'),{
type:'bar',
data:{
labels:<?=json_encode($restockNames)?>,
datasets:[{
    label:'Restock Qty',   // ← ADD THIS
    data:<?=json_encode($restockQty)?>,
    backgroundColor:'#fd7e14',
    borderRadius:8
}]
}
});
let roiValue = <?=$roi?>;

// cap visual at 100% (for chart only)
let displayROI = Math.min(Math.max(roiValue, 0), 100);

new Chart(document.getElementById('roiChart'),{
type:'doughnut',
data:{
labels:['Profit','Cost'], // 👈 labels shown on top
datasets:[{
data:[<?=$profit?>, <?=$cost?>],
backgroundColor:['#198754','#dc3545']
}]
},
options:{
plugins:{
legend:{
position:'top',   // 👈 same as Inventory Health
labels:{
usePointStyle:true,  // 👈 rounded color dots
padding:15
}
},
tooltip:{
callbacks:{
label: function(ctx){
let value = ctx.raw.toLocaleString();
return ctx.label + ': ₱' + value;
}
}
}
}
}
});
</script>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
