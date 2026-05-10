<?php
// /public/user_reset.php  — TEMP helper: upsert a user + set password
// Usage examples:
//   /profitradar/public/user_reset.php?email=staff@example.com&name=Staff%20User&role=staff&pw=staff123
//   /profitradar/public/user_reset.php?email=auditor@example.com&name=Auditor%20User&role=auditor&pw=auditor123
require_once __DIR__ . '/../includes/db.php';

$email = $_GET['email'] ?? '';
$name  = $_GET['name']  ?? 'Temp User';
$role  = $_GET['role']  ?? 'staff';   // 'admin' | 'staff' | 'auditor'
$pw    = $_GET['pw']    ?? 'password123';

header('Content-Type: text/plain; charset=utf-8');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['admin','staff','auditor'], true)) {
  http_response_code(400);
  exit("Bad request. Need valid ?email= & ?role=admin|staff|auditor\n");
}

try {
  $pdo = getDB();
  // Ensure user row exists
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
  $stmt->execute([$email]);
  $row = $stmt->fetch();

  if (!$row) {
    $ins = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,created_at)
                          VALUES (?,?, '', ?, NOW())");
    $ins->execute([$name, $email, $role]);
    $userId = (int)$pdo->lastInsertId();
    echo "Created user id #{$userId}\n";
  } else {
    $userId = (int)$row['id'];
    // also ensure role matches desired role
    $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $userId]);
    echo "Found existing user id #{$userId}; role set to {$role}\n";
  }

  // Set password (bcrypt)
  $hash = password_hash($pw, PASSWORD_BCRYPT);
  $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $userId]);

  echo "✅ Password set for {$email}\n";
  echo "Now login, then DELETE this file: /public/user_reset.php\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "❌ Error: ".$e->getMessage();
}
