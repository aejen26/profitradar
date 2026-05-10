<?php
ob_start();
// /public/category_edit.php — add/edit category
require_once __DIR__.'/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();
check_csrf();

$id = (int)($_GET['id'] ?? 0);
$err = '';
$c = ['name'=>'', 'description'=>''];

if ($id) {
  $st = $pdo->prepare('SELECT * FROM categories WHERE id=?');
  $st->execute([$id]);
  if ($row = $st->fetch()) $c = $row; else $id = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'name' => trim($_POST['name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
  ];
  if ($data['name'] === '') {
    $err = 'Name is required.';
    $c = array_merge($c, $data);
  } else {
    try {
      if ($id) {
        $pdo->prepare('UPDATE categories SET name=?, description=? WHERE id=?')
            ->execute([$data['name'], $data['description'], $id]);
        log_action($pdo, current_user()['id'], 'update', 'categories', $id, 'Category updated', $c, $data);
      } else {
        $pdo->prepare('INSERT INTO categories (name, description) VALUES(?,?)')
            ->execute([$data['name'], $data['description']]);
        $id = (int)$pdo->lastInsertId();
        log_action($pdo, current_user()['id'], 'create', 'categories', $id, 'Category created', null, $data);
      }
      header('Location: ' . app_url('categories.php')); exit;
    } catch (Throwable $e) {
      $err = 'Save failed: ' . h($e->getMessage());
      $c = array_merge($c, $data);
    }
  }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= $id ? 'Edit' : 'Add' ?> Category</h4>
</div>

<?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

<form method="post" class="row g-3">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <div class="col-md-6">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" required value="<?= h($c['name']) ?>">
  </div>
  <div class="col-md-12">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3"><?= h($c['description']) ?></textarea>
  </div>
  <div class="col-12 text-end">
    <a class="btn btn-outline-secondary" href="<?= app_url('categories.php') ?>">Cancel</a>
    <button class="btn btn-primary">Save</button>
  </div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
