<?php
ob_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_role(['admin']);

$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

if ($id) {
  try {
    $pdo->prepare('UPDATE products SET category_id=NULL WHERE category_id=?')->execute([$id]);

    $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);

    if (function_exists('log_action')) {
        log_action(
            $pdo,
            current_user()['id'],
            'delete',
            'categories',
            $id,
            'Category deleted'
        );
    }

  } catch (Throwable $e) {
    echo '<div class="alert alert-danger m-3">Delete failed: '.h($e->getMessage()).'</div>';
  }
}

header('Location: /categories.php');
exit;