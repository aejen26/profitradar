<?php
ob_start();
// /public/supplier_edit.php — add/edit supplier
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);
$pdo = getDB();
check_csrf();

$id = (int)($_GET['id'] ?? 0);
$err = '';

$s = ['name'=>'','contact'=>'','phone'=>'','email'=>'','address'=>''];

if ($id) {
  $st = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
  $st->execute([$id]);
  $row = $st->fetch();
  if ($row) { $s = $row; } else { $id = 0; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'name'    => trim($_POST['name'] ?? ''),
    'contact' => trim($_POST['contact'] ?? ''),
    'phone'   => trim($_POST['phone'] ?? ''),
    'email'   => trim($_POST['email'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
  ];

  if ($data['name'] === '') {
    $err = 'Name is required.';
    $s = array_merge($s, $data);
  } else {
    try {
      if ($id) {
        $sql = 'UPDATE suppliers SET name=?, contact=?, phone=?, email=?, address=? WHERE id=?';
        $pdo->prepare($sql)->execute([$data['name'],$data['contact'],$data['phone'],$data['email'],$data['address'],$id]);
        log_action($pdo, current_user()['id'], 'update', 'suppliers', $id, 'Supplier updated', $s, $data);
      } else {
        $sql = 'INSERT INTO suppliers (name, contact, phone, email, address) VALUES (?,?,?,?,?)';
        $pdo->prepare($sql)->execute([$data['name'],$data['contact'],$data['phone'],$data['email'],$data['address']]);
        $id = (int)$pdo->lastInsertId();
        log_action($pdo, current_user()['id'], 'create', 'suppliers', $id, 'Supplier created', null, $data);
      }
      header('Location: /suppliers.php'); exit;
    } catch (Throwable $e) {
      $err = 'Save failed: ' . h($e->getMessage());
      $s = array_merge($s, $data);
    }
  }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= $id ? 'Edit' : 'Add' ?> Supplier</h4>
</div>

<?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

<form method="post" class="row g-3">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <div class="col-md-6">
    <label class="form-label">Name<span class="text-danger">*</span></label>
    <input class="form-control" name="name" required value="<?= h($s['name']) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Contact Person</label>
    <input class="form-control" name="contact" value="<?= h($s['contact']) ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Phone</label>
    <input class="form-control" name="phone" value="<?= h($s['phone']) ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Email</label>
    <input class="form-control" type="email" name="email" value="<?= h($s['email']) ?>">
  </div>
<!--<div class="col-md-12">
    <label class="form-label">Address</label>
    <textarea class="form-control" name="address" rows="3"><?= h($s['address']) ?></textarea>
  </div>-->
  <div class="col-12 text-end">
    <a class="btn btn-outline-secondary" href="/suppliers.php">Cancel</a>
    <button class="btn btn-primary">Save</button>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
