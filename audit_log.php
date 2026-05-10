<?php
require_once __DIR__.'/../includes/header.php';
require_role(['admin','auditor']);
$pdo=getDB();
$st=$pdo->query('SELECT a.*, u.name FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 200');
?>
<h4>Audit Log</h4>
<table class="table table-striped table-sm">
  <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>ID</th><th>Description</th></tr></thead>
  <tbody>
    <?php foreach($st as $r): ?>
    <tr>
      <td><?= h($r['created_at']) ?></td>
      <td><?= h($r['name'] ?? 'System') ?></td>
      <td><?= h($r['action']) ?></td>
      <td><?= h($r['entity']) ?></td>
      <td><?= h($r['entity_id']) ?></td>
      <td><?= h($r['description']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__.'/../includes/footer.php'; ?>