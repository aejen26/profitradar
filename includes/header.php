<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();

/* Low stock count */

$lowCount = 0;
try{
    $lowCount = get_low_stock_count($pdo);
}catch(Throwable $e){
    $lowCount = 0;
}

/* Dashboard stats */

$totalProducts = 0;
$salesToday = 0;

try{

$totalProducts = $pdo->query("
SELECT COUNT(*) FROM products
")->fetchColumn();


$salesToday = $pdo->query("
SELECT IFNULL(SUM(ti.qty * ti.unit_price),0)
FROM transaction_items ti
JOIN transactions t ON t.id = ti.transaction_id
WHERE t.type='sale'
AND DATE(t.date)=CURDATE()
")->fetchColumn();

}catch(Throwable $e){}

$revenueToday = $salesToday;

/* session */

if(session_status()===PHP_SESSION_NONE){
session_start();
}

/* detect page */

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= h(APP_NAME) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>

body{
background:#f4f6f9;
font-size:14px;
}

/* SIDEBAR */

.sidebar{
position:fixed;
top:0;
left:0;
bottom:0;
width:250px;
background:#212529;
color:#fff;
overflow-y:auto;
padding-top:10px;
transition:all .3s;
}

/* Scrollbar */

.sidebar::-webkit-scrollbar{
width:6px;
}

.sidebar::-webkit-scrollbar-thumb{
background:#444;
border-radius:10px;
}

/* Brand */

.sidebar-brand{
padding:12px 18px;
font-weight:600;
font-size:16px;
border-bottom:1px solid rgba(255,255,255,.1);
margin-bottom:10px;
display:flex;
align-items:center;
justify-content:space-between;
}

/* Links */

.sidebar a{
display:flex;
align-items:center;
gap:10px;
padding:10px 18px;
font-size:14px;
color:#adb5bd;
text-decoration:none;
}

.sidebar a:hover{
background:#343a40;
color:#fff;
}

.sidebar a.active{
background:#0d6efd;
color:#fff;
}

/* Titles */

.sidebar-title{
padding:14px 18px 5px;
font-size:11px;
text-transform:uppercase;
color:#6c757d;
}

/* Collapse */

.sidebar.collapsed{
width:70px;
}

.sidebar.collapsed span{
display:none;
}

.sidebar.collapsed .sidebar-title{
display:none;
}

.sidebar.collapsed a{
justify-content:center;
}

/* MAIN */

.main{
margin-left:250px;
min-height:100vh;
transition:all .3s;
}

.main.expanded{
margin-left:70px;
}

/* TOPBAR */

.topbar{
background:#fff;
border-bottom:1px solid #ddd;
padding:12px 20px;
display:flex;
justify-content:space-between;
align-items:center;
}

/* PAGE */

.page{
padding:20px;
}

/* CARDS */

.card{
border:0;
border-radius:10px;
box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.stat-card{
padding:15px;
}

.stat-card small{
color:#6c757d;
}

.stat-card h5{
margin-top:4px;
}

</style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

<div class="sidebar-brand">

<span>ProfitRadar</span>

<button class="btn btn-sm btn-outline-light"
onclick="toggleSidebar()">
<i class="bi bi-list"></i>
</button>

</div>


<div class="sidebar-title">Dashboard</div>

<a class="<?= $currentPage=='dashboard.php'?'active':'' ?>"
href="/dashboard.php">
<i class="bi bi-speedometer2"></i>
<span>Dashboard</span>
</a>

<div class="sidebar-title">Inventory</div>

<a href="/inventory.php">
<i class="bi bi-file-fill"></i>
<span>Inventory</span>
</a>    

<div class="sidebar-title">Products</div>

    
<a href="/products.php">
<i class="bi bi-folder"></i>
<span>Products</span>
</a>
    
<a href="/categories.php">
<i class="bi bi-bookmarks-fill"></i>
<span>Categories</span>
</a>

<a href="/suppliers.php">
<i class="bi bi-truck"></i>
<span>Suppliers</span>
</a>

<a href="/locations.php">
<i class="bi bi-geo-alt-fill"></i>
<span>Locations</span>
</a>   
    
<a href="/batches.php">
<i class="bi bi-stack"></i>
<span>Batches</span>
</a>

<a href="/reservations.php">
<i class="bi bi-cart"></i>
<span>Reservations</span>
</a>

<a href="/damaged.php">
<i class="bi bi-exclamation-triangle"></i>
<span>Damaged Items</span>
</a>

<a href="/tickets.php">
<i class="bi bi-ticket"></i>
<span>Tickets</span>
</a>


<div class="sidebar-title">Transactions</div>

<a href="/purchase_order_add.php">
<i class="bi bi-file-earmark-plus"></i>
<span>Create PO</span>
</a>

<a href="/purchase_orders.php">
<i class="bi bi-list-check"></i>
<span>View PO</span>
</a>

<a href="/purchase_add.php">
<i class="bi bi-box-arrow-in-down"></i>
<span>Purchase (IN)</span>
</a>

<a href="/sale_add.php">
<i class="bi bi-cash"></i>
<span>Sales (OUT)</span>
</a>

<a href="/stock_adjust.php">
<i class="bi bi-arrow-left-right"></i>
<span>Stock Transfer</span>
</a>

<a href="/transactions.php">
<i class="bi bi-clock-history"></i>
<span>All Movements</span>
</a>

<a href="/deliveries/index.php">
<i class="bi bi-truck"></i>
<span>Deliveries</span>
</a>



<div class="sidebar-title">Reports</div>

<a href="/reports/sales_report.php">
<i class="bi bi-bar-chart"></i>
<span>Sales Report</span>
</a>

<a href="/reports/purchases_report.php">
<i class="bi bi-bag"></i>
<span>Purchases Report</span>
</a>

<a href="/reports/income_report.php">
<i class="bi bi-cash-stack"></i>
<span>Income Report</span>
</a>

<a href="/reports/stock_movement.php">
<i class="bi bi-arrow-repeat"></i>
<span>Stock Movement</span>
</a>

<a href="/inventory_movements.php">
<i class="bi bi-box2-fill"></i>
<span>Inventory Movement</span>
</a>    

<a href="/inventory_report.php">
<i class="bi bi-box-seam"></i>
<span>Inventory Report</span>
</a>

<a href="/reports/forecast.php">
<i class="bi bi-arrow-up-right"></i>
<span>Forecast</span>
</a>
 
<a href="/analytics.php">
<i class="bi bi-graph-up"></i>
<span>Analytics</span>
</a>



<div class="sidebar-title">Alerts</div>

<a href="/low_stock.php">

<i class="bi bi-exclamation-triangle"></i>
<span>Low Stock</span>

<?php if($lowCount>0): ?>
<span class="badge bg-danger ms-auto"><?= $lowCount ?></span>
<?php endif; ?>

</a>
    
<a href="/expiry.php">
<i class="bi bi-clock-history"></i>
<span>Expiry Alerts</span>
</a>


<?php if(has_role('admin')): ?>

<div class="sidebar-title">System</div>

<a href="/users.php">
<i class="bi bi-people"></i>
<span>User Management</span>
</a>

<a href="/audit_logs.php">
<i class="bi bi-activity"></i>
<span>Audit Logs</span>
</a>  
    
<a href="/settings.php">
<i class="bi bi-gear"></i>
<span>Settings</span>
</a>

<?php endif; ?>


</div>



<!-- MAIN -->

<div class="main">

<div class="topbar">

<strong><?= h(APP_NAME) ?></strong>

<div>

<?php if(is_logged_in()): ?>

<span class="me-3 text-muted small">
<?= h(current_user()['name']) ?>
</span>

<a class="btn btn-sm btn-outline-secondary"
href="/logout.php">
Logout
</a>

<?php endif; ?>

</div>

</div>


<div class="page">


<?php if (!empty($_SESSION['success'])): ?>

<div class="alert alert-success alert-dismissible fade show">

<?= h($_SESSION['success']) ?>

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php unset($_SESSION['success']); endif; ?>


<?php if (!empty($_SESSION['error'])): ?>

<div class="alert alert-danger alert-dismissible fade show">

<?= h($_SESSION['error']) ?>

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php unset($_SESSION['error']); endif; ?>



<script>

function toggleSidebar(){

document.querySelector(".sidebar")
.classList.toggle("collapsed");

document.querySelector(".main")
.classList.toggle("expanded");

}

</script>