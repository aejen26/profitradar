<?php
ob_start();
// includes/auth.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !!current_user();
}

/**
 * True if the current user has the given role OR is admin.
 * Accepts a single role string or an array of roles.
 */
function has_role($role): bool {
    $u = current_user();
    if (!$u) return false;
    if ($u['role'] === 'admin') return true;

    if (is_array($role)) {
        return in_array($u['role'], $role, true);
    }
    return $u['role'] === $role;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }

    // ❗ Check if disabled after login
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user']['id']]);
    $u = $stmt->fetch();

    if (!$u || !$u['is_active']) {
        session_destroy();
        header('Location: /login.php');
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function login(string $email, string $password): bool {
    $pdo = getDB();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {

        if (isset($u['is_active']) && !$u['is_active']) {
            return false;
        }

        $_SESSION['user'] = [
            'id'    => (int)$u['id'],
            'name'  => $u['name'],
            'email' => $u['email'],
            'role'  => $u['role'],
        ];

        // ✔ ADD THIS (LOGIN LOG)
        require_once __DIR__ . '/functions.php';
        log_action(
            $pdo,
            $u['id'],
            'login',
            'users',
            $u['id'],
            'User logged in'
        );

        return true;
    }

    return false;
}

function logout() {
    $pdo = getDB();

    if (!empty($_SESSION['user']['id'])) {

        // ✔ ADD THIS (LOGOUT LOG)
        require_once __DIR__ . '/functions.php';
        log_action(
            $pdo,
            $_SESSION['user']['id'],
            'logout',
            'users',
            $_SESSION['user']['id'],
            'User logged out'
        );
    }

    $_SESSION = [];
    session_destroy();
}
