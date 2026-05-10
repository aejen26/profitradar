<?php
require_once __DIR__.'/../includes/db.php';

$pdo = getDB();

$product_id = $_GET['product_id'] ?? 0;

if (!$product_id || !is_numeric($product_id)) {
    echo json_encode(['dates'=>[], 'qty'=>[]]);
    exit;
}

$stmt = $pdo->prepare("
SELECT DATE(t.date) as d, SUM(ti.qty) as total
FROM transaction_items ti
JOIN transactions t ON t.id = ti.transaction_id
WHERE ti.product_id = ?
GROUP BY d
ORDER BY d ASC
");

$stmt->execute([$product_id]);

$dates = [];
$qty = [];

while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
    $dates[] = $r['d'];
    $qty[] = (float)$r['total'];
}

echo json_encode([
    'dates'=>$dates,
    'qty'=>$qty
]);