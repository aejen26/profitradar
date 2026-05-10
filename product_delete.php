<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_role(['admin','staff']);

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user']['id'];

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE products 
        SET is_active = 0, updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$id, $user_id]);

    if (function_exists('log_action')) {
        log_action(
            $pdo,
            current_user()['id'],
            'archive',
            'products',
            $id,
            'Product archived',
            null,
            null
        );
    }
}

header('Location: products.php');
exit;