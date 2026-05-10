<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$pass || !$confirm) {
        $error = "All fields are required.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {

        $hash = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $pdo->prepare("
                INSERT INTO users (name,email,password_hash,role,is_active)
                VALUES (?,?,?,'staff',1)
            ")->execute([$name,$email,$hash]);

            $success = "Account created successfully.";

        } catch (Exception $e) {
            $error = "Email already exists.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Register - <?= h(APP_NAME) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
height:100vh;
background: linear-gradient(135deg,#0d6efd,#4e73df);
display:flex;
align-items:center;
justify-content:center;
font-family:system-ui;
}

.login-card{
width:100%;
max-width:420px;
border-radius:12px;
}

.logo{
font-size:28px;
font-weight:600;
color:#0d6efd;
}

.input-group-text{
background:#f1f3f5;
}
</style>

</head>

<body>

<div class="login-card card shadow-lg border-0">

<div class="card-body p-4">

<div class="text-center mb-4">
<div class="logo"><?= h(APP_NAME) ?></div>
<small class="text-muted">Create Account</small>
</div>

<?php if($error): ?>
<div class="alert alert-danger text-center">
<?= h($error) ?>
</div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert alert-success text-center">
<?= h($success) ?>
</div>
<?php endif; ?>

<form method="post">

<!-- Full Name -->
<div class="mb-3">
<label class="form-label">Full Name</label>
<div class="input-group">
<span class="input-group-text">👤</span>
<input class="form-control" type="text" name="name" placeholder="Enter full name" required>
</div>
</div>

<!-- Email -->
<div class="mb-3">
<label class="form-label">Email</label>
<div class="input-group">
<span class="input-group-text">📧</span>
<input class="form-control" type="email" name="email" placeholder="Enter email" required>
</div>
</div>

<!-- Password -->
<div class="mb-3">
<label class="form-label">Password</label>
<div class="input-group">
<span class="input-group-text">🔒</span>
<input class="form-control" type="password" name="password" id="password" placeholder="Enter password" required>
<button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">Show</button>
</div>
</div>

<!-- Confirm Password -->
<div class="mb-3">
<label class="form-label">Confirm Password</label>
<div class="input-group">
<span class="input-group-text">🔒</span>
<input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
<button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">Show</button>
</div>
</div>

<button class="btn btn-primary w-100 py-2">
Register
</button>

<div class="text-center mt-3">
<a href="login.php">Already have an account?</a>
</div>

</form>

</div>
</div>

<script>
function togglePassword(id){
const input = document.getElementById(id);
input.type = input.type === "password" ? "text" : "password";
}
</script>

</body>
</html>