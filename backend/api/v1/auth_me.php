<?php
/**
 * Get Current User Profile API
 */
require_once 'api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Get token from Authorization header using helper
$token = get_auth_token();

if (empty($token)) {
    json_response(['error' => 'Authorization token required'], 401);
}

try {
    $current_time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT id, name, email, username, phone, addresses FROM customers WHERE auth_token = ? AND auth_token_expires > ?");
    $stmt->execute([$token, $current_time]);
    $user = $stmt->fetch();

    if ($user) {
        if (!empty($user['addresses'])) {
            $user['addresses'] = json_decode($user['addresses'], true);
        } else {
            $user['addresses'] = null;
        }
        log_api_call($pdo, 200);
        json_response([
            'status' => 'success',
            'user' => $user
        ]);
    } else {
        log_api_call($pdo, 401, 'Invalid or expired token');
        json_response(['error' => 'Invalid or expired session'], 401);
    }

} catch (PDOException $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
