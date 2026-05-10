<?php
require_once __DIR__ . '/includes/db.php';
require_role(['admin','staff']);
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    header("Location: reservations.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        UPDATE products
        SET stock_qty = stock_qty - ?,
            reserved_qty = reserved_qty - ?
        WHERE id = ?
    ")->execute([$r['quantity'], $r['quantity'], $r['product_id']]);

    $pdo->prepare("
        UPDATE reservations
        SET status='completed'
        WHERE id=?
    ")->execute([$id]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}

header("Location: reservations.php");
exit;