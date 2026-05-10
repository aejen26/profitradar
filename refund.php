<?php
// public/refund.php
require_once __DIR__ . '/includes/header.php';
require_login();

$pdo = getDB();

/* ================= GET SALE ================= */
$sale_id = (int)($_GET['sale_id'] ?? 0);
if ($sale_id <= 0) {
  $_SESSION['error'] = 'Invalid sale ID.';
  header('Location: transactions.php'); exit;
}

$st = $pdo->prepare("
  SELECT * FROM transactions
  WHERE id=? AND type='sale'
");
$st->execute([$sale_id]);
$sale = $st->fetch(PDO::FETCH_ASSOC);
if (!$sale) {
  $_SESSION['error'] = 'Sale not found.';
  header('Location: transactions.php'); exit;
}

/* ================= SALE ITEMS ================= */
$items = $pdo->prepare("
  SELECT ti.id, ti.product_id, ti.qty, ti.unit_price,
         p.code, p.name, p.sold_by, p.unit
  FROM transaction_items ti
  JOIN products p ON p.id = ti.product_id
  WHERE ti.transaction_id=?
");
$items->execute([$sale_id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

/* ================= ALREADY REFUNDED ================= */
$refunded = [];
$rst = $pdo->prepare("
  SELECT product_id, SUM(qty) qty
  FROM transaction_items ti
  JOIN transactions t ON t.id = ti.transaction_id
  WHERE t.type='refund' AND t.notes LIKE ?
  GROUP BY product_id
");
$rst->execute(['%sale_id='.$sale_id.'%']);
foreach ($rst as $r) {
  $refunded[(int)$r['product_id']] = (float)$r['qty'];
}

/* ================= POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $refundQty = $_POST['refund_qty'] ?? [];
  $notes = trim($_POST['notes'] ?? '');

  $clean = [];

  foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $orig = (float)$it['qty'];
    $already = $refunded[$pid] ?? 0.0;
    $remain = max(0, $orig - $already);

    $rq = (float)($refundQty[$it['id']] ?? 0);
    if ($rq <= 0 || $rq > $remain) continue;

    // normalize qty
    if ($it['sold_by'] === 'weight') {
      $rq = round($rq, 3);
    } else {
      $rq = (int)$rq;
    }

    $clean[] = [
      'product_id' => $pid,
      'qty' => $rq,
      'unit_price' => (float)$it['unit_price'],
      'price_tier' => null
    ];
  }

  if (!$clean) {
    $_SESSION['error'] = 'No valid refund quantities.';
    header("Location: refund.php?sale_id={$sale_id}");
    exit;
  }

  create_transaction(
    $pdo,
    'refund',
    current_user()['id'],
    date('Y-m-d'),
    'refund sale_id='.$sale_id.' '.$notes,
    null,
    $sale['customer_id'],
    $clean
  );

  $_SESSION['success'] = 'Refund processed successfully.';
  header('Location: transactions.php?type=refund');
  exit;
}
?>

<h4>Refund Sale <?= h($sale['ref_no']) ?></h4>

<?php if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

  <table class="table table-sm">
    <thead>
      <tr>
        <th>Item</th>
        <th class="text-end">Sold</th>
        <th class="text-end">Refunded</th>
        <th class="text-end">Remaining</th>
        <th class="text-end">Refund Qty</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it):
        $orig = (float)$it['qty'];
        $already = $refunded[$it['product_id']] ?? 0;
        $remain = max(0, $orig - $already);
        $step = $it['sold_by'] === 'weight' ? '0.25' : '1';
      ?>
      <tr>
        <td><?= h($it['code'].' — '.$it['name']) ?></td>
        <td class="text-end"><?= $orig ?></td>
        <td class="text-end"><?= $already ?></td>
        <td class="text-end"><?= $remain ?></td>
        <td class="text-end">
          <input
            type="number"
            name="refund_qty[<?= (int)$it['id'] ?>]"
            min="0"
            max="<?= $remain ?>"
            step="<?= $step ?>"
            class="form-control form-control-sm"
          >
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="text-end">
    <button class="btn btn-danger">Process Refund</button>
    <a href="transactions.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
