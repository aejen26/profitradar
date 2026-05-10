<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // check_csrf(); // temporarily disable for testing

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (login($email, $pass)) {
        header('Location: ' . app_url('dashboard.php'));
        exit;
    }

    $error = 'Invalid credentials or account disabled';
}

?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login - <?= h(APP_NAME) ?></title>

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
<small class="text-muted">Management System</small>

</div>

<?php if($error): ?>
<div class="alert alert-danger text-center">
<?= h($error) ?>
</div>
<?php endif; ?>

<form method="post">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

<div class="mb-3">

<label class="form-label">Email</label>

<div class="input-group">

<span class="input-group-text">📧</span>

<input class="form-control" type="email" name="email" placeholder="Enter email" required>

</div>

</div>

<div class="mb-3">

<label class="form-label">Password</label>

<div class="input-group">

<span class="input-group-text">🔒</span>

<input class="form-control" type="password" name="password" id="password" placeholder="Enter password" required>

<button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">Show</button>

</div>

</div>

<button class="btn btn-primary w-100 py-2">
Login
</button>
<div class="text-center mt-3">
    <a href="register.php">Create an account</a>
</div>
</form>

</div>
</div>

<script>

function togglePassword(){

const input = document.getElementById("password");

if(input.type==="password"){
input.type="text";
}else{
input.type="password";
}

}

</script>

</body>
</html>