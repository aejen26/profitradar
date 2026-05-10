<?php
require_once '../includes/db.php';
require_role(['admin','staff']);
$product_id = (int)$_POST['product_id'];
$qty = (float)$_POST['quantity'];
$note = $_POST['note'] ?? '';

$pdo->beginTransaction();

$stmt = $pdo->prepare("SELECT stock_qty, reserved_qty FROM products WHERE id=?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

$available = $product['stock_qty'] - $product['reserved_qty'];

if ($qty > $available) {
    die("Not enough stock available.");
}

$pdo->prepare("
INSERT INTO reservations (product_id, quantity, note)
VALUES (?, ?, ?)
")->execute([$product_id, $qty, $note]);

$pdo->prepare("
UPDATE products
SET reserved_qty = reserved_qty + ?
WHERE id = ?
")->execute([$qty, $product_id]);

$pdo->commit();

echo "Reservation successful.";