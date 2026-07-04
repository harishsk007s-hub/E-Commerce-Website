<?php
require_once '../../includes/db_connection.php';
require_once '../../includes/functions.php';

// Initialize API
require_once 'api_init.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = sanitize($data['code'] ?? '');
    
    if (empty($code)) {
        json_response(['error' => 'Coupon code is required'], 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        json_response(['error' => 'Invalid coupon code'], 404);
    }
    
    // Check expiry
    if ($coupon['expiry'] && strtotime($coupon['expiry']) < time()) {
        json_response(['error' => 'Coupon has expired'], 400);
    }
    
    // Check usage limit
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        json_response(['error' => 'Coupon usage limit reached'], 400);
    }
    
    json_response([
        'success' => true,
        'coupon' => [
            'code' => $coupon['code'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => (float)$coupon['discount_value']
        ]
    ]);
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
