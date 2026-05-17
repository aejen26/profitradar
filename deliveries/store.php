<?php
require_once __DIR__.'/../includes/header.php';
require_login();

$pdo = getDB();

$transaction_id = $_POST['transaction_id'];
$deliver_qty = $_POST['deliver_qty'] ?? [];

if (!$transaction_id) {
    die("Transaction ID missing.");
}

/* get customer */
$stmt = $pdo->prepare("
SELECT customer_id
FROM transactions
WHERE id = ?
");

$stmt->execute([$transaction_id]);

$customer_id = $stmt->fetchColumn();

if (!$customer_id) {
    $customer_id = 1;
}

$pdo->beginTransaction();

try {

    /* =========================================
       CHECK IF DELIVERY ALREADY EXISTS
    ========================================= */

    $stmt = $pdo->prepare("
    SELECT id
    FROM deliveries
    WHERE transaction_id = ?
    LIMIT 1
    ");

    $stmt->execute([$transaction_id]);

    $existing_delivery_id = $stmt->fetchColumn();

    /* =========================================
       CREATE DELIVERY ONLY IF NONE EXISTS
    ========================================= */

    if ($existing_delivery_id) {

        $delivery_id = $existing_delivery_id;

    } else {

        $delivery_number =
            'DEL-' .
            date('Ymd-His');

        $stmt = $pdo->prepare("
        INSERT INTO deliveries
        (
            delivery_number,
            transaction_id,
            customer_id,
            delivery_date,
            status
        )
        VALUES
        (?, ?, ?, NOW(), 'pending')
        ");

        $stmt->execute([
            $delivery_number,
            $transaction_id,
            $customer_id
        ]);

        $delivery_id = $pdo->lastInsertId();
    }

    /* =========================================
       INSERT DELIVERY ITEMS
    ========================================= */

    foreach ($deliver_qty as $transaction_item_id => $qty) {

        $qty = (float)$qty;

        if ($qty <= 0) continue;

        /* get product */
        $stmt = $pdo->prepare("
        SELECT product_id, qty
        FROM transaction_items
        WHERE id = ?
        ");

        $stmt->execute([$transaction_item_id]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception(
                "Invalid transaction item."
            );
        }

        $product_id = $item['product_id'];

        /* already delivered qty */
        $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity),0)
        FROM delivery_items
        WHERE transaction_item_id = ?
        ");

        $stmt->execute([$transaction_item_id]);

        $already_delivered =
            (float)$stmt->fetchColumn();

        $remaining =
            (float)$item['qty']
            - $already_delivered;

        if ($qty > $remaining) {

            throw new Exception(
                "Delivery exceeds remaining quantity."
            );
        }

        /* insert item */
        $stmt = $pdo->prepare("
        INSERT INTO delivery_items
        (
            delivery_id,
            transaction_item_id,
            product_id,
            quantity
        )
        VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $delivery_id,
            $transaction_item_id,
            $product_id,
            $qty
        ]);
    }

    /* =========================================
       CALCULATE STATUS
    ========================================= */

    $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(qty),0)
    FROM transaction_items
    WHERE transaction_id = ?
    ");

    $stmt->execute([$transaction_id]);

    $ordered =
        (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(di.quantity),0)
    FROM delivery_items di
    INNER JOIN transaction_items ti
        ON ti.id = di.transaction_item_id
    WHERE ti.transaction_id = ?
    ");

    $stmt->execute([$transaction_id]);

    $delivered =
        (float)$stmt->fetchColumn();

    if ($delivered < $ordered) {

    $status = 'partial';

} else {

    $status = 'delivered';

}

    /* =========================================
       UPDATE DELIVERY STATUS
    ========================================= */

    $stmt = $pdo->prepare("
    UPDATE deliveries
    SET status = ?
    WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $delivery_id
    ]);

    $pdo->commit();

    header("Location: index.php");

    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    die($e->getMessage());
}
