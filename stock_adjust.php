<?php
declare(strict_types=1);

$headers = function_exists('getallheaders') ? getallheaders() : [];
$isAjax = (
  (isset($headers['X-Requested-With']) && strtolower($headers['X-Requested-With']) === 'xmlhttprequest')
  || (isset($headers['Accept']) && strpos($headers['Accept'], 'application/json') !== false)
  || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1')
);

if (!$isAjax) {
  require_once __DIR__ . '/includes/header.php';
}

require_once __DIR__ . '/includes/db.php';
if (function_exists('check_csrf')) check_csrf();

if (!isset($pdo) || !($pdo instanceof PDO)) {
    if (function_exists('getDB')) {
        $pdo = getDB();
    } elseif (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        $pdo = $GLOBALS['pdo'];
    } else {
        $msg = 'Database connection not found.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['ok'=>false,'msg'=>$msg]);
            exit;
        }
        throw new RuntimeException($msg);
    }
}

$categories = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$products = $pdo->query("
SELECT id, code, name, category_id, unit, sold_by,
COALESCE(stock_qty,0) AS stock_qty
FROM products
WHERE is_active=1
ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);


/* ---------------- AJAX ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {

  header('Content-Type: application/json');

  try {

    $items = $_POST['items'] ?? [];

    if (!$items) {
        throw new Exception("No items.");
    }

    $pdo->beginTransaction();

    $sel = $pdo->prepare("SELECT stock_qty FROM products WHERE id=:id FOR UPDATE");

    $upd = $pdo->prepare("
        UPDATE products
        SET stock_qty=:stock
        WHERE id=:id
    ");

    $applied = [];

    foreach ($items as $it) {

        $pid = (int)$it['product_id'];
        $delta = (float)$it['delta'];

        $sel->execute([':id'=>$pid]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$row) continue;

        $before = (float)$row['stock_qty'];
        $after = $before + $delta;

        if ($after < 0) $after = 0;

        $upd->execute([
            ':stock'=>$after,
            ':id'=>$pid
        ]);

        $applied[]=[
            'product_id'=>$pid,
            'before'=>$before,
            'after'=>$after
        ];
    }

    $pdo->commit();

    $ids = array_column($applied,'product_id');

    $stocks=[];

    if ($ids) {

        $in=implode(',',array_map('intval',$ids));

        $q=$pdo->query("SELECT id,stock_qty FROM products WHERE id IN ($in)");

        foreach($q as $r){
            $stocks[$r['id']]=$r['stock_qty'];
        }
    }

    echo json_encode([
        'ok'=>true,
        'msg'=>'Adjustment applied',
        'stocks'=>$stocks
    ]);

    exit;

  } catch(Throwable $e){

    if($pdo->inTransaction()) $pdo->rollBack();

    http_response_code(400);

    echo json_encode([
        'ok'=>false,
        'msg'=>$e->getMessage()
    ]);

    exit;
  }
}

?>

<style>

.container-wide{
max-width:1200px;
margin:25px auto;
}

.page-title{
font-weight:700;
margin-bottom:20px;
}

.filter-bar{
background:#fff;
border:1px solid #e6e9ed;
border-radius:10px;
padding:14px;
margin-bottom:18px;
box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.product-card{
display:flex;
align-items:center;
justify-content:space-between;
gap:14px;
padding:14px 16px;
border-radius:10px;
border:1px solid #e9ecef;
background:#fff;
margin-bottom:10px;
transition:all .15s ease;
}

.product-card:hover{
box-shadow:0 3px 10px rgba(0,0,0,0.08);
transform:translateY(-1px);
}

.product-meta{
flex:2;
}

.product-name{
font-weight:700;
font-size:15px;
}

.product-code{
font-size:12px;
color:#6c757d;
}

.unit-badge{
padding:3px 8px;
font-size:11px;
border-radius:999px;
background:#f1f3f5;
font-weight:600;
margin-left:6px;
}

.stock-box{
text-align:center;
min-width:90px;
}

.stock-label{
font-size:11px;
color:#6c757d;
}

.stock-val{
font-size:18px;
font-weight:700;
color:#198754;
}

.adjust-box{
display:flex;
align-items:center;
gap:8px;
}

.adjust-input{
width:110px;
text-align:center;
}

.status-bar{
margin-top:15px;
padding:10px;
border-radius:8px;
background:#f8f9fa;
font-size:13px;
}

</style>

<div class="container container-wide">

<h4 class="page-title">Stock Adjustment</h4>

<div class="filter-bar">
<div class="row g-2">

<div class="col-md-4">
<label class="form-label small">Category</label>

<select id="categorySelect" class="form-select form-select-sm">

<option value="">All Categories</option>

<?php foreach($categories as $c): ?>

<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>

<?php endforeach; ?>

</select>
</div>

<div class="col-md-8">

<label class="form-label small">Search Product</label>

<input
id="search"
class="form-control form-control-sm"
placeholder="Search name or code..."
>

</div>

</div>
</div>

<form id="adjustForm">

<input type="hidden" name="csrf" value="<?= csrf_token() ?>">

<div id="productList">

<?php foreach($products as $p):

$unit=$p['unit'] ?: 'pc';
$stock=(float)$p['stock_qty'];

?>

<div class="product-card adj-row" data-cat="<?= $p['category_id'] ?>">

<div class="product-meta">

<div class="product-name">
<?= htmlspecialchars($p['name']) ?>
<span class="unit-badge"><?= $unit ?></span>
</div>

<div class="product-code">
<?= htmlspecialchars($p['code']) ?>
</div>

</div>

<div class="stock-box">

<div class="stock-label">Stock</div>

<div class="stock-val" data-pid="<?= $p['id'] ?>">
<?= $stock ?>
</div>

</div>

<div class="adjust-box">

<input
class="form-control form-control-sm adjust-input"
type="number"
step="0.001"
placeholder="+ / -"
data-pid="<?= $p['id'] ?>"
>

<button
type="button"
class="btn btn-primary btn-sm btn-queue"
data-pid="<?= $p['id'] ?>"
>
Add
</button>

</div>

</div>

<?php endforeach; ?>

</div>

<div id="statusBox" class="status-bar">
Adjustments will apply immediately.
</div>

<div id="alertBox" style="display:none;margin-top:10px;"></div>

</form>

</div>

<script>

const PRODUCTS = <?= json_encode($products) ?>;

const alertBox=document.getElementById('alertBox');
const categorySelect=document.getElementById('categorySelect');
const searchInput=document.getElementById('search');
const statusBox=document.getElementById('statusBox');

function showAlert(msg,type='success'){
alertBox.style.display='block';
alertBox.className='alert alert-'+(type==='success'?'success':'danger');
alertBox.textContent=msg;
setTimeout(()=>alertBox.style.display='none',3000);
}

async function sendAdjustments(items){

const fd=new FormData();

fd.append('ajax','1');
fd.append('csrf',document.querySelector('input[name="csrf"]').value);

items.forEach((q,i)=>{

fd.append(`items[${i}][product_id]`,q.product_id);
fd.append(`items[${i}][delta]`,q.delta);

});

const res=await fetch(window.location.href,{
method:'POST',
body:fd
});

const j=await res.json();

if(!res.ok) throw new Error(j.msg);

return j;
}

document.querySelectorAll('.btn-queue').forEach(btn=>{

btn.addEventListener('click',async()=>{

const pid=btn.dataset.pid;

const inp=document.querySelector(`.adjust-input[data-pid="${pid}"]`);

const val=inp.value;

if(val===''||isNaN(val)||Number(val)==0){
alert('Enter value');
return;
}

btn.disabled=true;

statusBox.textContent='Applying adjustment...';

try{

const j=await sendAdjustments([
{product_id:pid,delta:Number(val)}
]);

for(const id in j.stocks){

const el=document.querySelector(`.stock-val[data-pid="${id}"]`);

if(el) el.textContent=j.stocks[id];

}

showAlert(j.msg);

statusBox.textContent='Adjustment applied';

inp.value='';

}catch(err){

showAlert(err.message,'error');

statusBox.textContent='Error';

}

btn.disabled=false;

});

});


function filterList(){

const cat=categorySelect.value;
const q=searchInput.value.toLowerCase();

document.querySelectorAll('.adj-row').forEach(row=>{

const text=row.textContent.toLowerCase();
const rowCat=row.dataset.cat;

if(cat && cat!=rowCat){
row.style.display='none';
return;
}

if(q && !text.includes(q)){
row.style.display='none';
return;
}

row.style.display='flex';

});

}

categorySelect.addEventListener('change',filterList);
searchInput.addEventListener('input',filterList);

</script>

<?php if(!$isAjax) require_once __DIR__.'/includes/footer.php'; ?>