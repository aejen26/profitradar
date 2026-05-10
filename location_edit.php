<?php
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB(); check_csrf();

$id = (int)($_GET['id'] ?? 0);
$err = '';
$l = ['name'=>'', 'description'=>''];

if ($id) {
  $st = $pdo->prepare('SELECT * FROM locations WHERE id=?'); $st->execute([$id]);
  if ($row = $st->fetch()) $l = $row; else $id = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'name' => trim($_POST['name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
  ];
  if ($data['name'] === '') { $err = 'Name is required.'; $l = array_merge($l,$data); }
  else {
    try {
      if ($id) {
        $pdo->prepare('UPDATE locations SET name=?, description=? WHERE id=?')
            ->execute([$data['name'],$data['description'],$id]);
        log_action($pdo,current_user()['id'],'update','locations',$id,'Location updated',$l,$data);
      } else {
        $pdo->prepare('INSERT INTO locations (name, description) VALUES(?,?)')
            ->execute([$data['name'],$data['description']]);
        $id = (int)$pdo->lastInsertId();
        log_action($pdo,current_user()['id'],'create','locations',$id,'Location created',null,$data);
      }
      header('Location: /locations.php'); exit;
    } catch (Throwable $e) {
      $err = 'Save failed: '.h($e->getMessage()); $l = array_merge($l,$data);
    }
  }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= $id? 'Edit':'Add' ?> Location</h4>
</div>
<?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
<form method="post" class="row g-3">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <div class="col-md-6">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" required value="<?= h($l['name']) ?>">
  </div>
  <div class="col-md-12">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3"><?= h($l['description']) ?></textarea>
  </div>
  <div class="col-12 text-end">
    <a class="btn btn-outline-secondary" href="/locations.php">Cancel</a>
    <button class="btn btn-primary">Save</button>
  </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
