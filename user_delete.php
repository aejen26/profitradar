<?php
require_once __DIR__.'/includes/auth.php';
require_role(['admin']);

$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$currentUserId = $_SESSION['user']['id'];

if ($id) {

    // ❗ prevent disabling yourself
    if ($id == $currentUserId) {
        $_SESSION['error'] = "You cannot disable your own account.";
        header("Location: users.php");
        exit;
    }

    // ✔ toggle active/disabled
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        $newStatus = $user['is_active'] ? 0 : 1;

        $pdo->prepare("
        UPDATE users 
        SET is_active=? 
        WHERE id=?
        ")->execute([$newStatus, $id]);

        $_SESSION['success'] = $newStatus ? "User enabled." : "User disabled.";
    }
}

header("Location: users.php");
exit;