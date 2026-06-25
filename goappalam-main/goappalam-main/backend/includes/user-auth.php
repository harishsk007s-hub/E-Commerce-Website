<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connection.php';

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function check_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'customer';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function get_user_data($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Rate limiting for login
function check_login_attempts($pdo, $email) {
    $stmt = $pdo->prepare("SELECT login_attempts FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ? $user['login_attempts'] : 0;
}

function increment_login_attempts($pdo, $email) {
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?");
    $stmt->execute([$email]);
}

function reset_login_attempts($pdo, $email) {
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0 WHERE email = ?");
    $stmt->execute([$email]);
}
