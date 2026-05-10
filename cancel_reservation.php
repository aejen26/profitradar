<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_role(['admin','staff']);

$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
SELECT product_id, quantity
FROM reservations
WHERE id = ?
");
$stmt->execute([$id]);

$res = $stmt->fetch();

if (!$res) {
    die("Reservation not found.");
}

$pdo->beginTransaction();

try {

    /* return reserved stock */

    $stmt = $pdo->prepare("
    UPDATE products
    SET reserved_qty = reserved_qty - ?
    WHERE id = ?
    ");
    $stmt->execute([$res['quantity'], $res['product_id']]);


    /* log stock movement */

    logStockMovement(
        $pdo,
        $res['product_id'],
        'release',
        $res['quantity'],
        'reservation',
        $id,
        'Reservation cancelled'
    );


    /* delete reservation */

    $stmt = $pdo->prepare("
    DELETE FROM reservations
    WHERE id = ?
    ");
    $stmt->execute([$id]);

    $pdo->commit();

} catch(Exception $e) {

    $pdo->rollBack();
    die($e->getMessage());
}

header("Location: reservations.php");
exit;