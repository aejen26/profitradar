<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();
check_csrf();

/* ===== Update thresholds ===== */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['th'])){
  foreach($_POST['th'] as $pid=>$val){
    $pid=(int)$pid;
    $v=trim($val);

    if($v===''){
      $pdo->prepare('UPDATE products SET low_stock_threshold=NULL WHERE id=?')
          ->execute([$pid]);
    }else{
      $pdo->prepare('UPDATE products SET low_stock_threshold=? WHERE id=?')
          ->execute([(int)$v,$pid]);
    }
  }
}

/* ===== Load products ===== */

$global = (int)get_setting($pdo,'low_stock_default',5);

$products = $pdo->prepare("
SELECT id,code,name,stock_qty,low_stock_threshold
FROM products
WHERE is_active=1
ORDER BY name
");

$products->execute();
?>

<!-- Page Header -->
<div class="mb-4">

<h4 class="fw-semibold mb-0">Low Stock Alerts</h4>

<small class="text-muted">
Configure product stock thresholds
</small>

</div>


<!-- Global Info -->
<div class="card shadow-sm mb-4 border-warning">

<div class="card-body">

<div class="d-flex align-items-center">

<div class="me-3">
<span class="badge bg-warning text-dark">
Global Default
</span>
</div>

<div>
Global low-stock threshold is
<strong><?= (int)$global ?></strong>.
Leave a field blank to use this value.
</div>

</div>

</div>

</div>



<form method="post">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">



<!-- Threshold Table -->
<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>

<th style="width:140px">Code</th>

<th>Product</th>

<th class="text-center" style="width:120px">Stock</th>

<th style="width:200px">Threshold</th>

</tr>

</thead>

<tbody>

<?php foreach($products as $p):

$stock = (int)$p['stock_qty'];
$th = $p['low_stock_threshold'] ?? $global;

$isLow = $stock <= $th;

?>

<tr class="<?= $isLow ? 'table-warning' : '' ?>">

<td class="fw-semibold">
<?= h($p['code']) ?>
</td>

<td>
<?= h($p['name']) ?>
</td>

<td class="text-center">

<?php if($isLow): ?>

<span class="badge bg-danger">
<?= $stock ?>
</span>

<?php else: ?>

<?= $stock ?>

<?php endif; ?>

</td>

<td>

<input
class="form-control"
type="number"
name="th[<?= (int)$p['id'] ?>]"
value="<?= h($p['low_stock_threshold']) ?>"
placeholder="(<?= (int)$global ?>)">

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>



<div class="text-end mt-3">

<button class="btn btn-primary">

Save Thresholds

</button>

</div>

</form>

<?php require_once __DIR__.'/includes/footer.php'; ?>