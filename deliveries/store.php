<?php
require_once __DIR__.'/../includes/header.php';
require_login();

$pdo = getDB();

$transaction_id = $_POST['transaction_id'];
$deliver_qty = $_POST['deliver_qty'] ?? [];

if (!$transaction_id) {
    die("Transaction ID missing.");
}

/* get customer from transaction */
$stmt = $pdo->prepare("
SELECT customer_id
FROM transactions
WHERE id = ?
");
$stmt->execute([$transaction_id]);
$customer_id = $stmt->fetchColumn();

if (!$customer_id) {
    $customer_id = 1; // default walk-in customer
}

$pdo->beginTransaction();

try {

    /* create delivery record */
    $stmt = $pdo->prepare("
    INSERT INTO deliveries
    (transaction_id, customer_id, delivery_date, status)
    VALUES (?, ?, NOW(), 'pending')
    ");

    $stmt->execute([
        $transaction_id,
        $customer_id
    ]);

    $delivery_id = $pdo->lastInsertId();

    /* insert delivery items */
    foreach ($deliver_qty as $transaction_item_id => $qty) {

        $qty = (int)$qty;
        if ($qty <= 0) continue;

        // get product_id
        $stmt = $pdo->prepare("
            SELECT product_id
            FROM transaction_items
            WHERE id = ?
        ");
        $stmt->execute([$transaction_item_id]);
        $product_id = $stmt->fetchColumn();

        if (!$product_id) {
            throw new Exception("Invalid transaction item: ".$transaction_item_id);
        }

        $stmt = $pdo->prepare("
            INSERT INTO delivery_items
            (delivery_id, transaction_item_id, product_id, quantity)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $delivery_id,
            $transaction_item_id,
            $product_id,
            $qty
        ]);
    }

    /* calculate delivery status */

    $stmt = $pdo->prepare("
    SELECT 
    SUM(ti.qty) AS ordered_qty,
    COALESCE(SUM(di.quantity),0) AS delivered_qty
    FROM transaction_items ti
    LEFT JOIN delivery_items di 
    ON di.transaction_item_id = ti.id
    WHERE ti.transaction_id = ?
    ");

    $stmt->execute([$transaction_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $ordered = (int)$data['ordered_qty'];
    $delivered = (int)$data['delivered_qty'];

    if ($delivered == 0) {
        $status = 'pending';
    } elseif ($delivered < $ordered) {
        $status = 'partial';
    } else {
        $status = 'delivered';
    }

    /* update delivery status */

    $stmt = $pdo->prepare("
    UPDATE deliveries
    SET status = ?
    WHERE id = ?
    ");

    $stmt->execute([$status, $delivery_id]);

    $pdo->commit();

    header("Location: index.php");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    die($e->getMessage());

}