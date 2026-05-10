<?php
// /public/settings.php — Global Settings (Low Stock Threshold)

require_once __DIR__ . '/includes/header.php';
require_role(['admin']);
$pdo = getDB();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  check_csrf();

  $val = trim($_POST['low_stock_default'] ?? '');

  if ($val === '' || !ctype_digit($val) || (int)$val < 0) {
      $err = 'Please enter a non-negative whole number.';
  } else {

      try {

          set_setting($pdo, 'low_stock_default', (string)(int)$val);

          $msg = 'Global low stock threshold updated successfully.';

      } catch (Throwable $e) {

          $err = 'Save failed: ' . h($e->getMessage());

      }
  }
}

$current = (int)get_setting($pdo, 'low_stock_default', 5);
?>

<!-- Page Header -->
<div class="mb-4">

<h4 class="fw-semibold mb-0">System Settings</h4>

<small class="text-muted">
Configure global system behavior
</small>

</div>



<?php if ($msg): ?>

<div class="alert alert-success">
<?= h($msg) ?>
</div>

<?php endif; ?>

<?php if ($err): ?>

<div class="alert alert-danger">
<?= h($err) ?>
</div>

<?php endif; ?>



<!-- Settings Card -->
<div class="card shadow-sm" style="max-width:600px;">

<div class="card-header">

<strong>Inventory Settings</strong>

</div>


<div class="card-body">

<form method="post" class="row g-3">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">



<div class="col-12">

<label class="form-label">

Global Low Stock Threshold

</label>

<input
class="form-control"
type="number"
min="0"
step="1"
name="low_stock_default"
value="<?= h($current) ?>">

<div class="form-text">

Products with stock below this number will trigger a
<strong>Low Stock Alert</strong>, unless a product-specific threshold is set.

</div>

</div>



<div class="col-12 d-flex justify-content-between">

<a
class="btn btn-outline-secondary"
href="/dashboard.php">

Cancel

</a>

<button class="btn btn-primary">

Save Settings

</button>

</div>



</form>

</div>

</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>