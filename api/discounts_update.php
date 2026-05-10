<?php
// public/api/discounts_update.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin','staff']);
$pdo = getDB();

// Detect AJAX vs normal form
function is_ajax(): bool {
  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// Emit JSON (AJAX) or redirect back with flash (forms)
function respond($ok, $msg = '', $extra = []) {
  if (is_ajax()) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok'=>(bool)$ok, 'error'=>$ok?null:$msg], $extra));
    exit;
  }
  $_SESSION['flash'] = ['type'=>$ok?'success':'danger', 'msg'=>$ok?($msg?:'OK'):($msg?:'Error')];
  $back = $_SERVER['HTTP_REFERER'] ?? '/profitradar/public/products_discounts.php';
  header('Location: ' . $back);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, 'Method Not Allowed');
}

// ✅ Use your existing CSRF helper
check_csrf();

$id   = (int)($_POST['product_id'] ?? 0);
$type = isset($_POST['promo_discount_type']) ? trim($_POST['promo_discount_type']) : '';
$valR = isset($_POST['promo_discount_value']) ? trim($_POST['promo_discount_value']) : '';
$removePressed = isset($_POST['remove']); // form "Remove" button

if ($id <= 0) respond(false, 'Invalid product');

// Ensure product exists
$chk = $pdo->prepare('SELECT id FROM products WHERE id=? LIMIT 1');
$chk->execute([$id]);
if (!$chk->fetchColumn()) respond(false, 'Product not found');

// Clear discount if Remove clicked or fields empty
if ($removePressed || $type === '' || $valR === '') {
  $pdo->prepare("UPDATE products
                   SET promo_discount_type = NULL,
                       promo_discount_value = NULL,
                       updated_at = NOW()
                 WHERE id = ?")->execute([$id]);
  respond(true, 'Discount removed', ['cleared'=>true]);
}

// Validate + normalize
if (!in_array($type, ['percent','amount'], true)) respond(false, 'Invalid discount type');
if (!is_numeric($valR)) respond(false, 'Invalid discount value');

$val = (float)$valR;
if ($val < 0) respond(false, 'Invalid discount value');
if ($type === 'percent' && $val > 100) respond(false, 'Percent must be 0–100');
$val = round($val, 2);

// Save
$st = $pdo->prepare("UPDATE products
                        SET promo_discount_type = ?,
                            promo_discount_value = ?,
                            updated_at = NOW()
                     WHERE id = ?");
$st->execute([$type, $val, $id]);

respond(true, 'Discount saved', ['type'=>$type, 'value'=>$val]);
