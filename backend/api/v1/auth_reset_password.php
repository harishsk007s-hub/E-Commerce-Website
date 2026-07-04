<?php
/**
 * Reset Password API
 */
require_once 'api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$token = sanitize($data['token'] ?? '');
$new_password = $data['new_password'] ?? '';

if (empty($token) || empty($new_password)) {
    json_response(['error' => 'Token and new password required'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['error' => 'Invalid or expired reset token'], 400);
    }

    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE customers SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$password_hash, $user['id']]);

    log_api_call($pdo, 200);
    json_response([
        'status' => 'success',
        'message' => 'Password reset successfully. You can now login.'
    ]);

} catch (PDOException $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
