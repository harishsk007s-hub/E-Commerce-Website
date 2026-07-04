<?php
require_once 'db_connection.php';
require_once 'functions.php';

// This would be the endpoint for webhooks/callbacks
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$gateway = $_GET['gateway'] ?? 'unknown';
$order_id = $data['order_id'] ?? 0;
$payment_id = $data['payment_id'] ?? '';
$status = $data['status'] ?? 'failed';

if ($order_id && $payment_id) {
    try {
        $pdo->beginTransaction();

        // Update payment record
        $stmt = $pdo->prepare("UPDATE payments SET status = ?, gateway_response = ? WHERE payment_id = ?");
        $stmt->execute([
            $status === 'success' ? 'paid' : 'failed',
            json_encode($data),
            $payment_id
        ]);

        if ($status === 'success') {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_verified = 1, status = 'processing' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            log_activity($pdo, 0, "Payment successful for Order #$order_id via $gateway");
        }

        $pdo->commit();
        json_response(['status' => 'processed']);

    } catch (Exception $e) {
        $pdo->rollBack();
        log_api_call($pdo, 500, $e->getMessage());
        json_response(['error' => 'Callback processing failed'], 500);
    }
} else {
    json_response(['error' => 'Invalid callback data'], 400);
}
