<?php
// /public/suppliers.php — list & search suppliers
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);
require_login();
$pdo = getDB();

$q = trim($_GET['q'] ?? '');

$sql = "SELECT id, name, contact, phone, email FROM suppliers WHERE 1";
$params = [];

if ($q !== '') {
  $sql .= " AND (name LIKE ? OR contact LIKE ? OR phone LIKE ? OR email LIKE ?)";
  $params = array_fill(0, 4, '%'.$q.'%');
}

$sql .= " ORDER BY name ASC LIMIT 200";
$st = $pdo->prepare($sql);
$st->execute($params);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h4 class="mb-0 fw-semibold">Suppliers</h4>
<small class="text-muted">Manage product suppliers</small>
</div>

<?php if (has_role(['admin','staff'])): ?>
<a class="btn btn-primary" href="/supplier_edit.php">
+ Add Supplier
</a>
<?php endif; ?>

</div>



<!-- Search Card -->
<div class="card shadow-sm mb-4">

<div class="card-body">

<form class="row g-3">

<div class="col-md-6">

<input
class="form-control"
name="q"
value="<?= h($q) ?>"
placeholder="Search by name, contact, phone, or email">

</div>

<div class="col-md-2 d-grid">

<button class="btn btn-outline-secondary">
Search
</button>

</div>

</form>

</div>
</div>



<!-- Supplier Table -->
<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>

<th style="width:25%">Supplier</th>

<th>Contact Person</th>

<th style="width:160px">Phone</th>

<th>Email</th>

<?php if (has_role(['admin','staff'])): ?>
<th class="text-end" style="width:170px">Actions</th>
<?php endif; ?>

</tr>

</thead>

<tbody>

<?php if ($st->rowCount() === 0): ?>

<tr>
<td colspan="5" class="text-center text-muted py-4">
No suppliers found.
</td>
</tr>

<?php else: ?>

<?php foreach ($st as $r): ?>

<tr>

<td class="fw-semibold">
<?= h($r['name']) ?>
</td>

<td>
<?= h($r['contact']) ?>
</td>

<td>
<?= h($r['phone']) ?>
</td>

<td class="text-muted">
<?= h($r['email']) ?>
</td>

<?php if (has_role(['admin','staff'])): ?>

<td class="text-end">

<a
class="btn btn-sm btn-outline-secondary"
href="/supplier_edit.php?id=<?= (int)$r['id'] ?>">
Edit
</a>

<?php if (has_role('admin')): ?>

<a
class="btn btn-sm btn-outline-danger"
href="/supplier_delete.php?id=<?= (int)$r['id'] ?>"
onclick="return confirm('Delete this supplier? This action cannot be undone.');">
Delete
</a>

<?php endif; ?>

</td>

<?php endif; ?>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>