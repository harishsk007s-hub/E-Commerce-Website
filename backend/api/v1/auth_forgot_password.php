<?php
/**
 * Forgot Password API
 */
require_once 'api_init.php';
require_once __DIR__ . '/../../includes/email-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = sanitize($data['email'] ?? '');

if (empty($email)) {
    json_response(['error' => 'Email is required'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE customers SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);
        
        send_password_reset_email($email, $token);
    }

    // Always return success for security
    log_api_call($pdo, 200);
    json_response([
        'status' => 'success',
        'message' => 'If an account exists for this email, we have sent a password reset link.'
    ]);

} catch (PDOException $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
