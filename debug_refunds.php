<?php
require_once __DIR__ . '/../includes/header.php';
$pdo = getDB();

$st = $pdo->prepare("SELECT id, type, ref_no, user_id, date, created_at, notes FROM transactions WHERE type='refund' ORDER BY created_at DESC LIMIT 50");
$st->execute();
$rs = $st->fetchAll(PDO::FETCH_ASSOC);

echo '<pre>';
echo "Refund rows:\n";
print_r($rs);

if ($rs) {
  $ids = array_column($rs,'id');
  $in = implode(',', array_map('intval',$ids));
  $q = $pdo->query("SELECT * FROM transaction_items WHERE transaction_id IN ($in) ORDER BY transaction_id");
  echo "Refund items:\n";
  print_r($q->fetchAll(PDO::FETCH_ASSOC));
}
echo '</pre>';
require_once __DIR__ . '/../includes/footer.php';
