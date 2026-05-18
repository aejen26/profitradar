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

    // check_csrf();

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

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
min-height:100vh;
display:flex;
align-items:center;
justify-content:center;
background:
linear-gradient(135deg,#0f172a,#1e3a8a,#2563eb);
font-family:'Segoe UI',sans-serif;
overflow:hidden;
position:relative;
}

/* Animated background */

body::before,
body::after{
content:'';
position:absolute;
border-radius:50%;
filter:blur(80px);
opacity:.4;
animation:float 8s infinite ease-in-out;
}

body::before{
width:300px;
height:300px;
background:#60a5fa;
top:-80px;
left:-80px;
}

body::after{
width:250px;
height:250px;
background:#3b82f6;
bottom:-80px;
right:-80px;
animation-delay:2s;
}

@keyframes float{
0%,100%{
transform:translateY(0px);
}
50%{
transform:translateY(25px);
}
}

.login-wrapper{
width:100%;
padding:20px;
display:flex;
justify-content:center;
z-index:1;
}

.login-card{
width:100%;
max-width:430px;
background:rgba(255,255,255,0.12);
backdrop-filter:blur(18px);
border:1px solid rgba(255,255,255,0.2);
border-radius:24px;
padding:40px 35px;
box-shadow:0 8px 40px rgba(0,0,0,0.3);
color:white;
animation:fadeIn .7s ease;
}

@keyframes fadeIn{
from{
opacity:0;
transform:translateY(30px);
}
to{
opacity:1;
transform:translateY(0);
}
}

.logo-box{
width:80px;
height:80px;
margin:auto;
margin-bottom:20px;
border-radius:20px;
background:linear-gradient(135deg,#2563eb,#60a5fa);
display:flex;
align-items:center;
justify-content:center;
font-size:32px;
box-shadow:0 6px 20px rgba(37,99,235,.5);
}

.system-title{
font-size:30px;
font-weight:700;
text-align:center;
margin-bottom:5px;
}

.system-subtitle{
text-align:center;
font-size:14px;
color:rgba(255,255,255,.8);
margin-bottom:30px;
}

.form-label{
font-weight:500;
margin-bottom:8px;
}

.input-group{
background:rgba(255,255,255,0.08);
border:1px solid rgba(255,255,255,0.15);
border-radius:14px;
overflow:hidden;
transition:.3s;
}

.input-group:focus-within{
border-color:#60a5fa;
box-shadow:0 0 0 3px rgba(96,165,250,.25);
}

.input-group-text{
background:transparent;
border:none;
color:#cbd5e1;
padding-left:15px;
}

.form-control{
background:transparent;
border:none;
color:white;
padding:14px 10px;
}

.form-control:focus{
background:transparent;
box-shadow:none;
color:white;
}

.form-control::placeholder{
color:#cbd5e1;
}

.toggle-btn{
border:none;
background:transparent;
color:#cbd5e1;
padding:0 15px;
transition:.3s;
}

.toggle-btn:hover{
color:white;
}

.login-btn{
width:100%;
padding:14px;
border:none;
border-radius:14px;
background:linear-gradient(135deg,#2563eb,#60a5fa);
color:white;
font-size:16px;
font-weight:600;
margin-top:10px;
transition:.3s;
}

.login-btn:hover{
transform:translateY(-2px);
box-shadow:0 10px 20px rgba(37,99,235,.4);
}

.extra-links{
margin-top:20px;
text-align:center;
}

.extra-links a{
color:#dbeafe;
text-decoration:none;
font-size:14px;
transition:.3s;
}

.extra-links a:hover{
color:white;
text-decoration:underline;
}

.alert{
border:none;
border-radius:12px;
}

.footer-text{
margin-top:25px;
text-align:center;
font-size:12px;
color:rgba(255,255,255,.6);
}

@media(max-width:480px){

.login-card{
padding:30px 22px;
border-radius:20px;
}

.system-title{
font-size:24px;
}

}

</style>

</head>

<body>

<div class="login-wrapper">

<div class="login-card">

<div class="logo-box">
<i class="fa-solid fa-chart-line"></i>
</div>

<div class="system-title">
<?= h(APP_NAME) ?>
</div>

<div class="system-subtitle">
Inventory Management System
</div>

<?php if($error): ?>
<div class="alert alert-danger text-center">
<i class="fa-solid fa-circle-exclamation me-2"></i>
<?= h($error) ?>
</div>
<?php endif; ?>

<form method="post">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

<div class="mb-3">

<label class="form-label">
Email Address
</label>

<div class="input-group">

<span class="input-group-text">
<i class="fa-solid fa-envelope"></i>
</span>

<input
type="email"
name="email"
class="form-control"
placeholder="Enter your email"
required>

</div>

</div>

<div class="mb-3">

<label class="form-label">
Password
</label>

<div class="input-group">

<span class="input-group-text">
<i class="fa-solid fa-lock"></i>
</span>

<input
type="password"
name="password"
id="password"
class="form-control"
placeholder="Enter your password"
required>

<button
type="button"
class="toggle-btn"
onclick="togglePassword()">

<i class="fa-solid fa-eye" id="eyeIcon"></i>

</button>

</div>

</div>

<div class="d-flex justify-content-between align-items-center mb-3">

<div class="form-check">


</div>


</div>

<button class="login-btn">
<i class="fa-solid fa-right-to-bracket me-2"></i>
Login
</button>

<div class="extra-links">
<a href="register.php">
Create an account
</a>
</div>

<div class="footer-text">
© <?= date('Y') ?> ProfitRadar System
</div>

</form>

</div>

</div>

<script>

function togglePassword(){

const password = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');

if(password.type === 'password'){

password.type = 'text';
eyeIcon.classList.remove('fa-eye');
eyeIcon.classList.add('fa-eye-slash');

}else{

password.type = 'password';
eyeIcon.classList.remove('fa-eye-slash');
eyeIcon.classList.add('fa-eye');

}

}

</script>

</body>
</html>
