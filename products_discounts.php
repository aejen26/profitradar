<?php
// public/products_discounts.php
// 1) Load core (NO HTML OUTPUT before redirects)
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';

require_role(['admin','staff']);
$pdo = getDB();

/* -------------------------
   POST handler (runs first)
   ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); // uses your functions.php helper

  // keep current filters in URL on redirect
  $qs = $_SERVER['QUERY_STRING'] ? ('?'.$_SERVER['QUERY_STRING']) : '';

  $id   = (int)($_POST['product_id'] ?? 0);
  $type = isset($_POST['promo_discount_type']) ? trim($_POST['promo_discount_type']) : '';
  $val  = isset($_POST['promo_discount_value']) ? trim($_POST['promo_discount_value']) : '';

  if ($id <= 0) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid product.'];
    header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
  }

  // ensure product exists
  $chk = $pdo->prepare('SELECT id FROM products WHERE id=? LIMIT 1');
  $chk->execute([$id]);
  if (!$chk->fetchColumn()) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Product not found.'];
    header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
  }

  // Remove / clear
  if (isset($_POST['remove']) || $type === '' || $val === '') {
    $pdo->prepare("UPDATE products
                     SET promo_discount_type=NULL,
                         promo_discount_value=NULL,
                         updated_at=NOW()
                   WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Discount removed.'];
    header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
  }

  // Save validations
  if (!in_array($type, ['percent','amount'], true)) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid discount type.'];
    header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
  }
  if (!is_numeric($val) || (float)$val < 0) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid discount value.'];
    header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
  }
  if ($type==='percent' && (float)$val > 100) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Percent must be 0–100.'];
    header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
  }

  $val = round((float)$val, 2);
  $pdo->prepare("UPDATE products
                   SET promo_discount_type=?, promo_discount_value=?, updated_at=NOW()
                 WHERE id=?")->execute([$type, $val, $id]);

  $_SESSION['flash'] = ['type'=>'success','msg'=>'Discount saved.'];
  header('Location: '.$_SERVER['PHP_SELF'].$qs); exit;
}

/* -------------------------
   GET view (HTML starts now)
   ------------------------- */
require_once __DIR__.'/../includes/header.php';

// filters
$q            = trim($_GET['q'] ?? '');
$category_id  = ($_GET['category_id'] ?? '') !== '' ? (int)$_GET['category_id'] : null;
$has_discount = $_GET['has_discount'] ?? ''; // '', 'yes', 'no'

// categories
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// query
$sql = "
  SELECT p.id, p.code, p.name, p.sell_price,
         p.promo_discount_type, p.promo_discount_value,
         c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.id = p.category_id
  WHERE 1=1
";
$params = [];

if ($q !== '') {
  $sql .= " AND (p.code LIKE :t OR p.name LIKE :t) ";
  $params[':t'] = '%'.str_replace(['%','_'], ['\%','\_'], $q).'%';
}
if ($category_id) {
  $sql .= " AND p.category_id = :cid ";
  $params[':cid'] = $category_id;
}
if ($has_discount === 'yes') {
  $sql .= " AND p.promo_discount_type IS NOT NULL AND p.promo_discount_value IS NOT NULL ";
} elseif ($has_discount === 'no') {
  $sql .= " AND (p.promo_discount_type IS NULL OR p.promo_discount_value IS NULL) ";
}

$sql .= " ORDER BY p.name ASC LIMIT 500";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Product Discounts</h4>
</div>

<?php if (!empty($_SESSION['flash'])): $f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= h($f['type']) ?>"><?= h($f['msg']) ?></div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-4">
    <label class="form-label">Search</label>
    <input class="form-control" type="search" name="q" value="<?= h($q) ?>" placeholder="Code or Name">
  </div>
  <div class="col-md-3">
    <label class="form-label">Category</label>
    <select class="form-select" name="category_id">
      <option value="">All</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ($category_id===$c['id'])?'selected':'' ?>>
          <?= h($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Has Discount?</label>
    <select class="form-select" name="has_discount">
      <option value="">All</option>
      <option value="yes" <?= $has_discount==='yes'?'selected':'' ?>>Yes</option>
      <option value="no"  <?= $has_discount==='no' ?'selected':''  ?>>No</option>
    </select>
  </div>
  <div class="col-md-2 align-self-end">
    <button class="btn btn-outline-secondary w-100">Filter</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-striped table-sm align-middle">
    <thead>
      <tr>
        <th style="width:120px">Code</th>
        <th>Name</th>
        <th style="width:180px">Category</th>
        <th class="text-end" style="width:140px">Sell Price</th>
        <th style="width:380px">Discount</th>
        <th style="width:60px"></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="text-muted">No products found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td><span class="small text-monospace"><?= h($p['code']) ?></span></td>
            <td><?= h($p['name']) ?></td>
            <td><?= h($p['category_name'] ?? '—') ?></td>
            <td class="text-end">₱<?= number_format((float)$p['sell_price'],2) ?></td>

            <td>
              <!-- One compact form per row: Save/Remove POST back to SAME page -->
              <form method="post" class="row g-1 align-items-center mb-0">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                <div class="col-5">
                  <select class="form-select form-select-sm" name="promo_discount_type">
                    <option value="" <?= $p['promo_discount_type']===null?'selected':'' ?>>— none —</option>
                    <option value="percent" <?= $p['promo_discount_type']==='percent'?'selected':'' ?>>Percent (%)</option>
                    <option value="amount"  <?= $p['promo_discount_type']==='amount' ?'selected':'' ?>>Amount (₱)</option>
                  </select>
                </div>

                <div class="col-4">
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0"
                         name="promo_discount_value" value="<?= h($p['promo_discount_value']) ?>" placeholder="Value">
                </div>

                <div class="col-3 text-end">
                  <button class="btn btn-sm btn-primary" type="submit" name="save" value="1">Save</button>
                  <button class="btn btn-sm btn-outline-danger" type="submit" name="remove" value="1">Remove</button>
                </div>
              </form>
            </td>

            <td></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
