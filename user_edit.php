<?php
require_once __DIR__.'/includes/header.php';
require_role(['admin']);

$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$err = '';

$user = ['name'=>'','email'=>'','role'=>'staff'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (!$name || !$email) {
        $err = "All fields required";
    } else {

        if ($id) {

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("
                UPDATE users 
                SET name=?, email=?, role=?, password_hash=? 
                WHERE id=?
                ")->execute([$name,$email,$role,$hash,$id]);
            } else {
                $pdo->prepare("
                UPDATE users 
                SET name=?, email=?, role=? 
                WHERE id=?
                ")->execute([$name,$email,$role,$id]);
            }

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->prepare("
            INSERT INTO users (name,email,password_hash,role)
            VALUES (?,?,?,?)
            ")->execute([$name,$email,$hash,$role]);
        }

        header("Location: users.php");
        exit;
    }
}
?>

<h4><?= $id ? 'Edit' : 'Add' ?> User</h4>

<?php if($err): ?>
<div class="alert alert-danger"><?= $err ?></div>
<?php endif; ?>

<form method="post">

<input class="form-control mb-2" name="name" placeholder="Name" value="<?= h($user['name']) ?>">

<input class="form-control mb-2" name="email" placeholder="Email" value="<?= h($user['email']) ?>">

<input type="password" class="form-control mb-2" name="password" placeholder="Password (leave blank if no change)">

<select class="form-control mb-3" name="role">
<option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
<option value="staff" <?= $user['role']=='staff'?'selected':'' ?>>Staff</option>
<option value="auditor" <?= $user['role']=='auditor'?'selected':'' ?>>Auditor</option>
</select>

<button class="btn btn-primary">Save</button>

</form>