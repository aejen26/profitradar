<?php
require_once __DIR__.'/../includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$pdo->prepare("
    UPDATE purchase_orders
    SET status='ordered'
    WHERE id=? AND status='draft'
")->execute([$id]);

$_SESSION['success'] = "Purchase Order confirmed.";
header("Location: purchase_orders.php");
exit;