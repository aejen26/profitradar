<?php
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/auth.php';
require_role(['admin']);

$pdo = getDB();

/* ===== FILTERS ===== */
$q = trim($_GET['q'] ?? '');
$user_id = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

/* ===== PAGINATION ===== */
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

/* ===== BASE SQL ===== */
$baseSql = "
FROM audit_log a
LEFT JOIN users u ON u.id = a.user_id
WHERE 1
";

$params = [];

/* filters */
if ($q !== '') {
    $baseSql .= " AND a.description LIKE ?";
    $params[] = "%$q%";
}

if ($user_id !== '') {
    $baseSql .= " AND a.user_id = ?";
    $params[] = $user_id;
}

if ($action !== '') {
    $baseSql .= " AND a.action = ?";
    $params[] = $action;
}

if ($date_from !== '') {
    $baseSql .= " AND DATE(a.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $baseSql .= " AND DATE(a.created_at) <= ?";
    $params[] = $date_to;
}

/* ===== COUNT ===== */
$countSql = "SELECT COUNT(*) " . $baseSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

/* ===== MAIN QUERY ===== */
$sql = "
SELECT a.*, u.name AS user_name
" . $baseSql . "
ORDER BY a.created_at DESC
LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

/* users dropdown */
$users = $pdo->query("SELECT id,name FROM users ORDER BY name")->fetchAll();
?>

<div class="mb-4">
<h4 class="fw-semibold mb-0">Audit Logs</h4>
<small class="text-muted">Track system activity and user actions</small>
</div>

<!-- FILTERS -->
<div class="card shadow-sm mb-3">
<div class="card-body">

<form class="row g-3">

<div class="col-md-3">
<input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search description">
</div>

<div class="col-md-2">
<select class="form-select" name="user_id">
<option value="">All Users</option>
<?php foreach($users as $u): ?>
<option value="<?= $u['id'] ?>" <?= $user_id==$u['id']?'selected':'' ?>>
<?= h($u['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<select class="form-select" name="action">
<option value="">All Actions</option>
<option value="create" <?= $action=='create'?'selected':'' ?>>Create</option>
<option value="update" <?= $action=='update'?'selected':'' ?>>Update</option>
<option value="delete" <?= $action=='delete'?'selected':'' ?>>Delete</option>
<option value="archive" <?= $action=='archive'?'selected':'' ?>>Archive</option>
<option value="login" <?= $action=='login'?'selected':'' ?>>Login</option>
<option value="logout" <?= $action=='logout'?'selected':'' ?>>Logout</option>
</select>
</div>

<div class="col-md-2">
<input type="date" class="form-control" name="date_from" value="<?= h($date_from) ?>">
</div>

<div class="col-md-2">
<input type="date" class="form-control" name="date_to" value="<?= h($date_to) ?>">
</div>

<div class="col-md-1 d-grid">
<button class="btn btn-outline-secondary">Go</button>
</div>

</form>

</div>
</div>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">
<tr>
<th>User</th>
<th>Action</th>
<th>Module</th>
<th>ID</th>
<th>Description</th>
<th>Date</th>
</tr>
</thead>

<tbody>

<?php if (!$logs): ?>
<tr>
<td colspan="6" class="text-center text-muted py-4">
No logs found.
</td>
</tr>
<?php endif; ?>

<?php foreach ($logs as $log): ?>
<tr>

<td><?= h($log['user_name'] ?? 'Unknown') ?></td>

<td>
<?php
switch ($log['action']) {
    case 'create': echo '<span class="badge bg-success">Create</span>'; break;
    case 'update': echo '<span class="badge bg-primary">Update</span>'; break;
    case 'delete': echo '<span class="badge bg-danger">Delete</span>'; break;
    case 'archive': echo '<span class="badge bg-warning text-dark">Archive</span>'; break;
    case 'login': echo '<span class="badge bg-success">Login</span>'; break;
    case 'logout': echo '<span class="badge bg-dark">Logout</span>'; break;
    default: echo '<span class="badge bg-secondary">'.h($log['action']).'</span>';
}
?>
</td>

<td><?= ucfirst(h($log['entity'])) ?></td>
<td><?= h($log['entity_id']) ?></td>
<td><?= h($log['description']) ?></td>
<td><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>

</tr>
<?php endforeach; ?>

</tbody>

</table>

</div>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="card shadow-sm mt-3">
<div class="card-body text-center">

<!-- PAGE NUMBERS -->
<ul class="pagination justify-content-center mb-3">

<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">
Prev
</a>
</li>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<li class="page-item <?= $i == $page ? 'active' : '' ?>">
<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>

<li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">
Next
</a>
</li>

</ul>

<!-- JUMP TO PAGE -->
<div class="d-flex justify-content-center align-items-center gap-2">

<form method="get" class="d-flex align-items-center gap-2">

<?php foreach ($_GET as $key => $val): ?>
    <?php if ($key !== 'page'): ?>
        <input type="hidden" name="<?= h($key) ?>" value="<?= h($val) ?>">
    <?php endif; ?>
<?php endforeach; ?>

<label class="mb-0 small text-muted">Go to page:</label>

<input
type="number"
name="page"
min="1"
max="<?= $totalPages ?>"
value="<?= $page ?>"
class="form-control form-control-sm"
style="width:80px">

<button class="btn btn-sm btn-primary">Go</button>

</form>

</div>

</div>
</div>
<?php endif; ?>

<?php require_once __DIR__.'/includes/footer.php'; ?>