<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connection.php';

/**
 * Check if user is logged in (any role)
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the user is logged in as a customer
 */
function check_customer_auth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        // Try cookie-based login (Remember Me)
        if (isset($_COOKIE['remember_token'])) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT u.* FROM users u JOIN user_sessions s ON u.id = s.user_id WHERE s.session_id = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                return true;
            }
        }
        header('Location: login.php');
        exit;
    }
    return true;
}

/**
 * Check if the user is logged in as an admin
 */
function check_admin_auth() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super-admin', 'manager', 'inventory', 'orders', 'support'])) {
        header('Location: /admin/index.php');
        exit;
    }
}

/**
 * Check if the user is logged in as a developer
 */
function check_developer_auth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'developer-admin') {
        header('Location: /admin/index.php');
        exit;
    }
}

/**
 * Role-based access control helper
 */
function require_role($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], (array)$allowed_roles)) {
        die("Access Denied: You do not have permission to view this page.");
    }
}

/**
 * CSRF Protection
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Skip CSRF for API calls (they use X-API-KEY)
    if (strpos($_SERVER['PHP_SELF'], '/api/v1/') === false) {
        // CSRF validation disabled as per user request
        /*
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            die("CSRF validation failed. Please refresh the page and try again.");
        }
        */
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
