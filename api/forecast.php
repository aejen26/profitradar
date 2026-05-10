<?php
require_once __DIR__.'/../includes/db.php';

$pdo = getDB();

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
if ($days <= 0) $days = 7;

$today = date('Y-m-d');
$last7_from = date('Y-m-d', strtotime("-7 days"));
$prev7_from = date('Y-m-d', strtotime("-14 days"));
$prev7_to   = date('Y-m-d', strtotime("-7 days"));

/* get products */
$stmt = $pdo->query("
SELECT id, code, name, stock_qty
FROM products
WHERE is_active = 1
ORDER BY name ASC
");

$rows = [];
$totalForecast = 0;
$totalReorder = 0;

while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $pid = $p['id'];
    $stock = (int)$p['stock_qty'];

    /* recent 7 days */
    $stmt1 = $pdo->prepare("
    SELECT SUM(ti.qty)
    FROM transaction_items ti
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE t.type='sale'
    AND ti.product_id=?
    AND t.date BETWEEN ? AND ?
    ");
    $stmt1->execute([$pid,$last7_from,$today]);
    $sold7 = (int)$stmt1->fetchColumn();

    /* previous 7 days */
    $stmt2 = $pdo->prepare("
    SELECT SUM(ti.qty)
    FROM transaction_items ti
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE t.type='sale'
    AND ti.product_id=?
    AND t.date BETWEEN ? AND ?
    ");
    $stmt2->execute([$pid,$prev7_from,$prev7_to]);
    $soldPrev7 = (int)$stmt2->fetchColumn();

    $avgRecent = $sold7 / 7;
    $avgOlder  = $soldPrev7 / 7;

    $avgPerDay = ($avgRecent * 0.7) + ($avgOlder * 0.3);

    /* dynamic forecast */
    $forecast = $avgPerDay * $days;

    $safetyStock = $avgPerDay * ($days * 0.4);

    $reorder = max(0, ceil(($forecast + $safetyStock) - $stock));

    $totalForecast += $forecast;
    $totalReorder += $reorder;

    $rows[] = [
        'code'=>$p['code'],
        'name'=>$p['name'],
        'avg'=>round($avgPerDay,2),
        'forecast'=>round($forecast,2),
        'stock'=>$stock,
        'reorder'=>$reorder
    ];
}

echo json_encode([
    'rows'=>$rows,
    'totalForecast'=>$totalForecast,
    'totalReorder'=>$totalReorder
]);