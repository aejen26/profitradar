<?php
require_once __DIR__ . '/includes/header.php';
require_login();
$pdo = getDB();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT id, name, description, created_at FROM locations WHERE 1";
$params = [];
if ($q !== '') { $sql .= " AND (name LIKE ? OR description LIKE ?)"; $params=['%'.$q.'%','%'.$q.'%']; }
$sql .= " ORDER BY name ASC LIMIT 200";
$st = $pdo->prepare($sql); $st->execute($params);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Locations</h4>
  <?php if (has_role(['admin','staff'])): ?>
    <a class="btn btn-primary" href="/location_edit.php">Add Location</a>
  <?php endif; ?>
</div>

<form class="row g-2 mb-3">
  <div class="col-md-6">
    <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search name or description">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100">Search</button>
  </div>
</form>

<table class="table table-striped table-sm align-middle">
  <thead><tr><th>Name</th><th>Description</th><th>Created</th><?php if (has_role(['admin','staff'])): ?><th class="text-end"></th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($st as $l): ?>
    <tr>
      <td><?= h($l['name']) ?></td>
      <td><?= h($l['description']) ?></td>
      <td><?= h($l['created_at']) ?></td>
      <?php if (has_role(['admin','staff'])): ?>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="location_edit.php?id=<?= (int)$l['id'] ?>">Edit</a>
        <?php if (has_role('admin')): ?>
          <a class="btn btn-sm btn-outline-danger" href="location_delete.php?id=<?= (int)$l['id'] ?>"
             onclick="return confirm('Delete this location? Products will keep working but without this location.');">Delete</a>
          
        <?php endif; ?>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if ($st->rowCount() === 0): ?>
    <tr><td colspan="4" class="text-muted">No locations yet.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
