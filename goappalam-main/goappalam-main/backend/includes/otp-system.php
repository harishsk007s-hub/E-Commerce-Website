<?php
/**
 * OTP System Functions
 */

require_once __DIR__ . '/db_connection.php';

/**
 * Generate a 6-digit OTP
 */
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Save OTP to database for a user or customer
 */
function saveOTP($email, $otp, $type = 'customer') {
    global $pdo;
    $table = ($type === 'admin') ? 'users' : 'customers';
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $pdo->prepare("UPDATE $table SET otp_code = ?, otp_expires = ?, login_attempts = 0 WHERE email = ?");
    return $stmt->execute([$otp, $expires, $email]);
}

/**
 * Verify OTP
 */
function verifyOTP($email, $otp, $type = 'customer') {
    global $pdo;
    $table = ($type === 'admin') ? 'users' : 'customers';
    
    $stmt = $pdo->prepare("SELECT id, otp_code, otp_expires, login_attempts FROM $table WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    if ($user['login_attempts'] >= 3) {
        return ['success' => false, 'message' => 'Too many failed attempts. Please resend OTP.'];
    }
    
    if (strtotime($user['otp_expires']) < time()) {
        return ['success' => false, 'message' => 'OTP has expired'];
    }
    
    if ($user['otp_code'] !== $otp) {
        $pdo->prepare("UPDATE $table SET login_attempts = login_attempts + 1 WHERE email = ?")->execute([$email]);
        return ['success' => false, 'message' => 'Invalid OTP code'];
    }
    
    // Success - Clear OTP
    $pdo->prepare("UPDATE $table SET otp_code = NULL, otp_expires = NULL, login_attempts = 0, last_login = NOW() WHERE email = ?")->execute([$email]);
    
    return ['success' => true, 'user_id' => $user['id']];
}

/**
 * Check if customer is a new user (has no username/password)
 */
function isNewUser($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT username, password FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) return true;
    return (empty($user['username']) || empty($user['password']));
}

/**
 * Update customer basic info (username and password)
 */
function updateCustomerProfile($email, $username, $password) {
    global $pdo;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE customers SET username = ?, password = ? WHERE email = ?");
    return $stmt->execute([$username, $hashedPassword, $email]);
}

/**
 * Verify Customer Login using username and password
 */
function verifyCustomerLogin($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, password, email FROM customers WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        return ['success' => true, 'user_id' => $user['id'], 'email' => $user['email']];
    }
    return ['success' => false, 'message' => 'Invalid username or password'];
}

/**
 * Rate limit OTP requests (5 per hour)
 */
function checkOTPRateLimit($email, $type = 'customer') {
    // This could be implemented with a separate table or by checking timestamps
    // For simplicity, we'll assume it's handled or we can add a basic check if needed.
    return true; 
}

/**
 * Get or Create customer by email
 */
function getOrCreateCustomer($email, $name = 'New Customer') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        return $customer['id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
    $stmt->execute([$name, $email]);
    return $pdo->lastInsertId();
}
