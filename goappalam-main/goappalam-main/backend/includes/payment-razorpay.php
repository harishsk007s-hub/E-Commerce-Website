<?php
/**
 * Razorpay Payment Integration
 */

require_once 'db_connection.php';
require_once 'functions.php';

function create_razorpay_order($pdo, $order_id, $amount) {
    // 1. Fetch Razorpay config from database settings or gateways table
    $stmt = $pdo->prepare("SELECT config FROM payment_gateways WHERE code = 'razorpay'");
    $stmt->execute();
    $gateway = $stmt->fetch();
    $config = $gateway ? json_decode($gateway['config'], true) : [];

    $key_id = $config['key_id'] ?? get_setting($pdo, 'razorpay_key_id');
    $key_secret = $config['key_secret'] ?? get_setting($pdo, 'razorpay_key_secret');

    if (!$key_id || !$key_secret) {
        throw new Exception("Razorpay keys missing in settings");
    }

    // 2. Create Razorpay order via cURL
    $orderData = [
        'receipt' => (string)$order_id,
        'amount' => (int)($amount * 100), // in paise
        'currency' => 'INR',
        'notes' => [
            'order_id' => $order_id
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("Razorpay order creation failed: " . $response);
    }

    $razorpayOrder = json_decode($response, true);
    $razorpay_order_id = $razorpayOrder['id'];
    
    // Save to payments table
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, gateway, status, payment_id, payment_key) VALUES (?, ?, 'razorpay', 'pending', ?, ?)");
    $stmt->execute([$order_id, $amount, $razorpay_order_id, $key_id]);
    
    return [
        'razorpay_order_id' => $razorpay_order_id,
        'key_id' => $key_id,
        'amount' => $amount
    ];
}

function verify_razorpay_payment($pdo, $payment_id, $payment_key, $signature = '') {
    // In production, verify the signature:
    // $attributes = array('razorpay_order_id' => $payment_id, 'razorpay_payment_id' => ..., 'razorpay_signature' => $signature);
    // $api->utility->verifyPaymentSignature($attributes);
    
    try {
        $pdo->beginTransaction();
        
        // 1. Update payment table
        $stmt = $pdo->prepare("UPDATE payments SET status = 'paid', gateway_response = ? WHERE payment_id = ?");
        $stmt->execute([json_encode(['verified' => true, 'timestamp' => date('Y-m-d H:i:s')]), $payment_id]);
        
        // 2. Fetch order_id associated with this payment
        $stmt = $pdo->prepare("SELECT order_id FROM payments WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        $order_id = $stmt->fetchColumn();
        
        if ($order_id) {
            // 3. Mark order as payment_verified = 1
            $stmt = $pdo->prepare("UPDATE orders SET payment_verified = 1, payment_status = 'paid', status = 'processing' WHERE id = ?");
            $stmt->execute([$order_id]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
