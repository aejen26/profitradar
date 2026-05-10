<?php
ob_start();
// /public/supplier_delete.php — delete supplier (admin only)
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
  try {
    $pdo->beginTransaction();

    // Detach from products and transactions
    $pdo->prepare('UPDATE products SET supplier_id = NULL WHERE supplier_id = ?')->execute([$id]);
    $pdo->prepare('UPDATE transactions SET supplier_id = NULL WHERE supplier_id = ?')->execute([$id]);

    // Delete supplier
    $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);

    log_action($pdo, current_user()['id'], 'delete', 'suppliers', $id, 'Supplier deleted', null, null);
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    // show a friendly error
    echo '<div class="alert alert-danger m-3">Delete failed: '.h($e->getMessage()).'</div>';
  }
}
header('Location: /suppliers.php'); exit;
