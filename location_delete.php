<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(['admin']);

$pdo = getDB();

$id = $_GET['id'] ?? null;

if ($id === null || !is_numeric($id)) {
  $_SESSION['error'] = 'Invalid ID';
  header('Location: /locations.php');
  exit;
}

$id = (int)$id;

try {
  $pdo->beginTransaction();

  $pdo->prepare('UPDATE products SET location_id=NULL WHERE location_id=?')->execute([$id]);

  $stmt = $pdo->prepare('DELETE FROM locations WHERE id=?');
  $stmt->execute([$id]);

  if ($stmt->rowCount() === 0) {
    throw new Exception('Location not found');
  }

  log_action($pdo,current_user()['id'],'delete','locations',$id,'Location deleted',null,null);

  $pdo->commit();

} catch (Throwable $e) {
  $pdo->rollBack();
  $_SESSION['error'] = $e->getMessage();
}

header('Location: /locations.php');
exit;