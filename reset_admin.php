<?php
// /public/reset_admin.php  (TEMPORARY SCRIPT — DELETE AFTER RUNNING)
require_once __DIR__ . '/../includes/db.php';

$email = $_GET['email'] ?? 'admin@example.com';
$new   = $_GET['pw']    ?? 'admin123';

try {
    $pdo = getDB();
    $hash = password_hash($new, PASSWORD_BCRYPT);
    $st = $pdo->prepare("UPDATE users SET password_hash=? WHERE email=?");
    $st->execute([$hash, $email]);

    header('Content-Type: text/plain; charset=utf-8');
    echo "✅ Password reset successful\n";
    echo "User: {$email}\n";
    echo "New password: {$new}\n";
    echo "Hash: {$hash}\n\n";
    echo "Now login and then DELETE this file: /public/reset_admin.php";
} catch (Throwable $e) {
    http_response_code(500);
    echo "❌ Error: " . $e->getMessage();
}
