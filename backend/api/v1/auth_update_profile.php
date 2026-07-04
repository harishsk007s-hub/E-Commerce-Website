<?php
/**
 * Update User Profile API (Name and Password)
 */
require_once 'api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Get token from Authorization header using helper
$token = get_auth_token();

if (empty($token)) {
    json_response(['error' => 'Authorization token required'], 401);
}

try {
    $current_time = date('Y-m-d H:i:s');
    // Verify user and token
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE auth_token = ? AND auth_token_expires > ?");
    $stmt->execute([$token, $current_time]);
    $user = $stmt->fetch();

    if (!$user) {
        log_api_call($pdo, 401, 'Invalid or expired token');
        json_response(['error' => 'Invalid or expired session'], 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $name = sanitize($data['name'] ?? '');
    $phone = sanitize($data['phone'] ?? '');
    
    // Address fields
    $address_line1 = sanitize($data['address_line1'] ?? '');
    $address_line2 = sanitize($data['address_line2'] ?? '');
    $address_line3 = sanitize($data['address_line3'] ?? '');
    $pincode = sanitize($data['pincode'] ?? '');

    $password = $data['password'] ?? '';

    if (empty($name)) {
        json_response(['error' => 'Name is required'], 400);
    }

    $full_address = [
        'line1' => $address_line1,
        'line2' => $address_line2,
        'line3' => $address_line3,
        'pincode' => $pincode
    ];

    // Update Name, Phone, Address
    $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, addresses = ? WHERE id = ?");
    $stmt->execute([$name, $phone, json_encode(['shipping' => $full_address]), $user['id']]);

    // Update Password if provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE customers SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
    }

    log_api_call($pdo, 200);
    json_response([
        'status' => 'success',
        'message' => 'Profile updated successfully'
    ]);

} catch (PDOException $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
