<?php
/**
 * Create Account API
 */
require_once 'api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$token = sanitize($data['token'] ?? '');
$name = sanitize($data['name'] ?? '');
$phone = sanitize($data['phone'] ?? '');
$password = $data['password'] ?? '';

if (empty($token) || empty($name) || empty($phone) || empty($password)) {
    json_response(['error' => 'All fields are required'], 400);
}

try {
    // Verify token using timestamp to avoid timezone issues
    $current_time = time();
    
    // DEBUG LOG
    error_log("Attempting account creation with token: " . $token);
    error_log("Current Server Time: " . $current_time . " (" . date('Y-m-d H:i:s', $current_time) . ")");
    
    $stmt = $pdo->prepare("SELECT * FROM magic_links WHERE token = ?");
    $stmt->execute([$token]);
    $link_debug = $stmt->fetch();
    if ($link_debug) {
        error_log("Token found in DB. Used: " . $link_debug['used'] . ", Expires At: " . $link_debug['expires_at'] . " (" . date('Y-m-d H:i:s', $link_debug['expires_at']) . ")");
    } else {
        error_log("Token NOT found in DB: " . $token);
    }

    $stmt = $pdo->prepare("SELECT email FROM magic_links WHERE token = ? AND used = 0 AND expires_at > ?");
    $stmt->execute([$token, $current_time]);
    $link = $stmt->fetch();

    if (!$link) {
        $debug_info = [
            'token_sent' => $token,
            'current_time' => $current_time,
            'current_time_readable' => date('Y-m-d H:i:s', $current_time)
        ];
        
        $check_stmt = $pdo->prepare("SELECT * FROM magic_links WHERE token = ?");
        $check_stmt->execute([$token]);
        $check = $check_stmt->fetch();
        if ($check) {
            $debug_info['token_in_db'] = true;
            $debug_info['used'] = $check['used'];
            $debug_info['expires_at'] = $check['expires_at'];
            $debug_info['expires_at_readable'] = date('Y-m-d H:i:s', (int)$check['expires_at']);
        } else {
            $debug_info['token_in_db'] = false;
        }
        
        json_response([
            'error' => 'Invalid or expired magic link',
            'debug' => $debug_info
        ], 400);
    }

    $email = $link['email'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Auto-generate username from email local part
    $username = explode('@', $email)[0];
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['error' => 'User already exists'], 400);
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Username already exists'], 400);
    }

    $pdo->beginTransaction();

    // Mark token as used
    $stmt = $pdo->prepare("UPDATE magic_links SET used = 1 WHERE token = ?");
    $stmt->execute([$token]);

    // Create customer
    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, username, password_hash) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $username, $password_hash]);
    $user_id = $pdo->lastInsertId();

    // Generate token for direct login
    $token_str = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("UPDATE customers SET last_login = NOW(), auth_token = ?, auth_token_expires = ? WHERE id = ?");
    $stmt->execute([$token_str, $expires, $user_id]);

    // Fetch user data to return
    $stmt = $pdo->prepare("SELECT id, name, email, username, phone FROM customers WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $pdo->commit();
    log_api_call($pdo, 200);
    json_response([
        'status' => 'success', 
        'message' => 'Account created successfully!',
        'user' => $user,
        'token' => $token_str
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
