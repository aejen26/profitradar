<?php
// /public/categories.php — list & search categories
require_once __DIR__ . '/includes/header.php';
require_role(['admin','staff']);
require_login();
$pdo = getDB();

$q = trim($_GET['q'] ?? '');

$sql = "SELECT id, name, description, created_at FROM categories WHERE 1";
$params = [];

if ($q !== '') {
  $sql .= " AND (name LIKE ? OR description LIKE ?)";
  $params = ['%'.$q.'%','%'.$q.'%'];
}

$sql .= " ORDER BY name ASC LIMIT 200";

$st = $pdo->prepare($sql);
$st->execute($params);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h4 class="mb-0 fw-semibold">Categories</h4>
<small class="text-muted">Manage product categories</small>
</div>

<?php if (has_role(['admin','staff'])): ?>
<a class="btn btn-primary" href="/category_edit.php">
+ Add Category
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
placeholder="Search category name or description">
</div>

<div class="col-md-2 d-grid">
<button class="btn btn-outline-secondary">
Search
</button>
</div>

</form>

</div>
</div>



<!-- Category Table -->
<div class="card shadow-sm">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>

<th style="width:25%">Category</th>

<th>Description</th>

<th style="width:160px">Created</th>

<?php if (has_role(['admin','staff'])): ?>
<th class="text-end" style="width:160px">Actions</th>
<?php endif; ?>

</tr>

</thead>

<tbody>

<?php if ($st->rowCount() === 0): ?>

<tr>
<td colspan="4" class="text-center text-muted py-4">
No categories found.
</td>
</tr>

<?php else: ?>

<?php foreach ($st as $c): ?>

<tr>

<td class="fw-semibold">
<?= h($c['name']) ?>
</td>

<td>
<?= h($c['description']) ?>
</td>

<td class="text-muted">
<?= date('Y-m-d', strtotime($c['created_at'])) ?>
</td>

<?php if (has_role(['admin','staff'])): ?>

<td class="text-end">

<a
class="btn btn-sm btn-outline-secondary"
href="/category_edit.php?id=<?= (int)$c['id'] ?>">
Edit
</a>

<?php if (has_role('admin')): ?>

<a
class="btn btn-sm btn-outline-danger"
href="/category_delete.php?id=<?= (int)$c['id'] ?>"
onclick="return confirm('Delete this category? Products will keep the link but the name will vanish.');">
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