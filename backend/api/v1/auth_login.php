<?php
/**
 * Login API
 */
require_once 'api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$username = sanitize($data['username'] ?? '');
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    json_response(['error' => 'Username and password required'], 400);
}

try {
    // Check customers table
    $stmt = $pdo->prepare("SELECT id, name, email, username, phone, addresses, password_hash FROM customers WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        
        if (!empty($user['addresses'])) {
            $user['addresses'] = json_decode($user['addresses'], true);
        } else {
            $user['addresses'] = null;
        }
        
        // Update last login and generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("UPDATE customers SET last_login = NOW(), auth_token = ?, auth_token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        log_api_call($pdo, 200);
        json_response([
            'status' => 'success',
            'user' => $user,
            'token' => $token
        ]);
    } else {
        log_api_call($pdo, 401, 'Invalid credentials');
        json_response(['error' => 'Invalid username or password'], 401);
    }

} catch (PDOException $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
