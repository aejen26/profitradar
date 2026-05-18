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

        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {

            $error = "This email is already registered.";

        } else {

            $hash = password_hash($pass, PASSWORD_DEFAULT);

            try {

                $pdo->prepare("
                    INSERT INTO users (name,email,password_hash,role,is_active)
                    VALUES (?,?,?,'staff',1)
                ")->execute([$name,$email,$hash]);

                $success = "Account created successfully.";

            } catch (Exception $e) {

                $error = "Something went wrong. Please try again.";

            }

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
padding:20px;
}

/* Animated background */

body::before,
body::after{
content:'';
position:absolute;
border-radius:50%;
filter:blur(90px);
opacity:.4;
animation:float 8s infinite ease-in-out;
}

body::before{
width:320px;
height:320px;
background:#60a5fa;
top:-100px;
left:-100px;
}

body::after{
width:260px;
height:260px;
background:#2563eb;
bottom:-100px;
right:-100px;
animation-delay:2s;
}

@keyframes float{

0%,100%{
transform:translateY(0);
}

50%{
transform:translateY(25px);
}

}

.register-wrapper{
width:100%;
display:flex;
justify-content:center;
z-index:1;
}

.register-card{
width:100%;
max-width:460px;
background:rgba(255,255,255,0.12);
backdrop-filter:blur(18px);
border:1px solid rgba(255,255,255,0.18);
border-radius:24px;
padding:40px 35px;
box-shadow:0 8px 40px rgba(0,0,0,.35);
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
width:85px;
height:85px;
margin:auto;
margin-bottom:20px;
border-radius:22px;
background:linear-gradient(135deg,#2563eb,#60a5fa);
display:flex;
align-items:center;
justify-content:center;
font-size:34px;
box-shadow:0 8px 20px rgba(37,99,235,.5);
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

.register-btn{
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

.register-btn:hover{
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

.password-strength{
height:6px;
border-radius:20px;
background:rgba(255,255,255,.15);
margin-top:10px;
overflow:hidden;
}

.password-strength span{
display:block;
height:100%;
width:0%;
transition:.4s;
border-radius:20px;
}

.footer-text{
margin-top:25px;
text-align:center;
font-size:12px;
color:rgba(255,255,255,.6);
}

@media(max-width:480px){

.register-card{
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

<div class="register-wrapper">

<div class="register-card">

<div class="logo-box">
<i class="fa-solid fa-user-plus"></i>
</div>

<div class="system-title">
<?= h(APP_NAME) ?>
</div>

<div class="system-subtitle">
Create Your Account
</div>

<?php if($error): ?>
<div class="alert alert-danger text-center">
<i class="fa-solid fa-circle-exclamation me-2"></i>
<?= h($error) ?>
</div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert alert-success text-center">
<i class="fa-solid fa-circle-check me-2"></i>
<?= h($success) ?>
</div>
<?php endif; ?>

<form method="post">

<!-- Full Name -->

<div class="mb-3">

<label class="form-label">
Full Name
</label>

<div class="input-group">

<span class="input-group-text">
<i class="fa-solid fa-user"></i>
</span>

<input
type="text"
name="name"
class="form-control"
placeholder="Enter your full name"
required>

</div>

</div>

<!-- Email -->

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

<!-- Password -->

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
placeholder="Create password"
required
onkeyup="checkStrength()">

<button
type="button"
class="toggle-btn"
onclick="togglePassword('password','eye1')">

<i class="fa-solid fa-eye" id="eye1"></i>

</button>

</div>

<div class="password-strength">
<span id="strengthBar"></span>
</div>

<small id="strengthText" class="text-light d-block mt-2">
Password strength
</small>

</div>

<!-- Confirm Password -->

<div class="mb-3">

<label class="form-label">
Confirm Password
</label>

<div class="input-group">

<span class="input-group-text">
<i class="fa-solid fa-lock"></i>
</span>

<input
type="password"
name="confirm_password"
id="confirm_password"
class="form-control"
placeholder="Confirm your password"
required>

<button
type="button"
class="toggle-btn"
onclick="togglePassword('confirm_password','eye2')">

<i class="fa-solid fa-eye" id="eye2"></i>

</button>

</div>

</div>

<div class="form-check mb-3">

</div>

<button class="register-btn">

<i class="fa-solid fa-user-plus me-2"></i>
Create Account

</button>

<div class="extra-links">

<a href="login.php">
Already have an account?
</a>

</div>

<div class="footer-text">
© <?= date('Y') ?> ProfitRadar System
</div>

</form>

</div>

</div>

<script>

function togglePassword(inputId, eyeId){

const input = document.getElementById(inputId);
const eye = document.getElementById(eyeId);

if(input.type === 'password'){

input.type = 'text';

eye.classList.remove('fa-eye');
eye.classList.add('fa-eye-slash');

}else{

input.type = 'password';

eye.classList.remove('fa-eye-slash');
eye.classList.add('fa-eye');

}

}

function checkStrength(){

const password = document.getElementById('password').value;
const bar = document.getElementById('strengthBar');
const text = document.getElementById('strengthText');

let strength = 0;

if(password.length >= 6) strength++;
if(password.match(/[A-Z]/)) strength++;
if(password.match(/[0-9]/)) strength++;
if(password.match(/[^A-Za-z0-9]/)) strength++;

if(strength == 1){

bar.style.width = '25%';
bar.style.background = '#ef4444';
text.innerHTML = 'Weak password';

}else if(strength == 2){

bar.style.width = '50%';
bar.style.background = '#f59e0b';
text.innerHTML = 'Medium password';

}else if(strength == 3){

bar.style.width = '75%';
bar.style.background = '#3b82f6';
text.innerHTML = 'Strong password';

}else if(strength == 4){

bar.style.width = '100%';
bar.style.background = '#22c55e';
text.innerHTML = 'Very strong password';

}else{

bar.style.width = '0%';
text.innerHTML = 'Password strength';

}

}

</script>

</body>
</html>
