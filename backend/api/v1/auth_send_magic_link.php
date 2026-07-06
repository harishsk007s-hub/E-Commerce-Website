<?php
/**
 * Send Magic Link API
 */
require_once 'api_init.php';
require_once __DIR__ . '/../../includes/email-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = sanitize($data['email'] ?? '');

if (empty($email)) {
    json_response(['error' => 'Email required'], 400);
}

// Check if email already exists in customers table
$stmt = $pdo->prepare("SELECT id, name, email, username, phone, addresses FROM customers WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

$is_new = false;
if (!$user) {
    // Create new customer immediately
    $is_new = true;
    $name = explode('@', $email)[0];
    $username = $name . rand(100, 999);
    
    $stmt = $pdo->prepare("INSERT INTO customers (name, email, username) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $username]);
    $user_id = $pdo->lastInsertId('customers_id_seq');
    
    $user = [
        'id' => $user_id,
        'name' => $name,
        'email' => $email,
        'username' => $username,
        'phone' => '',
        'addresses' => null
    ];
} else {
    if (!empty($user['addresses'])) {
        $user['addresses'] = json_decode($user['addresses'], true);
    } else {
        $user['addresses'] = null;
    }
}

// Generate token for direct login
$token_str = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 days'));

try {
    // Update last login and set token
    $stmt = $pdo->prepare("UPDATE customers SET last_login = NOW(), auth_token = ?, auth_token_expires = ? WHERE id = ?");
    $stmt->execute([$token_str, $expires, $user['id']]);

    if ($is_new) {
        // Send signup success email with link to set password later
        $reset_token = bin2hex(random_bytes(16));
        $reset_expires = time() + 3600;
        $pdo->prepare("INSERT INTO magic_links (email, token, expires_at) VALUES (?, ?, ?)")->execute([$email, $reset_token, $reset_expires]);
        
        send_signup_success_email($email, $user['name'], $reset_token);
    }

    log_api_call($pdo, 200);
    json_response([
        'status' => 'success', 
        'message' => $is_new ? 'Account created and logged in!' : 'Logged in successfully!',
        'user' => $user,
        'token' => $token_str,
        'is_new' => $is_new
    ]);

} catch (Throwable $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Server error: ' . $e->getMessage()], 500);
}
