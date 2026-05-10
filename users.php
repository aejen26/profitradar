<?php
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/auth.php';
require_role(['admin']);

$pdo = getDB();

/* ===== FILTERS ===== */
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';

$sql = "SELECT * FROM users WHERE 1";
$params = [];

if ($q !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
}

if ($role !== '') {
    $sql .= " AND role = ?";
    $params[] = $role;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

/* ===== STATS ===== */
$total = count($users);
$active = 0;
$disabled = 0;

foreach ($users as $u) {
    if ($u['is_active']) $active++;
    else $disabled++;
}
?>

<!-- HEADER -->
<div class="mb-4">
    <h4 class="fw-semibold mb-0">User Management</h4>
    <small class="text-muted">Manage system accounts and roles</small>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">

<div class="col-md-4">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Total Users</div>
<div class="fs-5 fw-semibold"><?= $total ?></div>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Active Users</div>
<div class="fs-5 text-success fw-semibold"><?= $active ?></div>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm">
<div class="card-body">
<div class="text-muted small">Disabled Users</div>
<div class="fs-5 text-danger fw-semibold"><?= $disabled ?></div>
</div>
</div>
</div>

</div>

<!-- FILTERS -->
<div class="card shadow-sm mb-3">
<div class="card-body">

<form class="row g-3">

<div class="col-md-5">
<input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search name or email">
</div>

<div class="col-md-3">
<select class="form-select" name="role">
<option value="">All Roles</option>
<option value="admin" <?= $role=='admin'?'selected':'' ?>>Admin</option>
<option value="staff" <?= $role=='staff'?'selected':'' ?>>Staff</option>
<option value="auditor" <?= $role=='auditor'?'selected':'' ?>>Auditor</option>
</select>
</div>

<div class="col-md-2 d-grid">
<button class="btn btn-outline-secondary">Filter</button>
</div>

</form>

</div>
</div>

<!-- USERS TABLE -->
<div class="card shadow-sm">
<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>Name</th>
<th>Email</th>
<th style="width:120px">Role</th>
<th style="width:120px">Status</th>
<th style="width:180px">Created</th>
<th class="text-end">Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($users as $u): ?>

<tr>

<td class="fw-semibold"><?= h($u['name']) ?></td>

<td><?= h($u['email']) ?></td>

<td>
<?php
switch ($u['role']) {
    case 'admin':
        echo '<span class="badge bg-primary">Admin</span>';
        break;
    case 'staff':
        echo '<span class="badge bg-secondary">Staff</span>';
        break;
    case 'auditor':
        echo '<span class="badge bg-info text-dark">Auditor</span>';
        break;
}
?>
</td>

<td>
<?php if ($u['is_active']): ?>
<span class="badge bg-success">Active</span>
<?php else: ?>
<span class="badge bg-danger">Disabled</span>
<?php endif; ?>
</td>

<td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>

<td class="text-end">

<a href="user_edit.php?id=<?= $u['id'] ?>"
class="btn btn-sm btn-outline-secondary">
Edit
</a>

<?php if ($u['id'] != $_SESSION['user']['id']): ?>

<?php if ($u['is_active']): ?>
<a href="user_delete.php?id=<?= $u['id'] ?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Disable this user?')">
Disable
</a>
<?php else: ?>
<a href="user_delete.php?id=<?= $u['id'] ?>"
class="btn btn-sm btn-success"
onclick="return confirm('Enable this user?')">
Enable
</a>
<?php endif; ?>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>
</div>

<?php require_once __DIR__.'/includes/footer.php'; ?>