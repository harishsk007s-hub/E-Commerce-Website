<?php
/**
 * Razorpay Payment Verification API
 */

require_once __DIR__ . '/api_init.php';
require_once __DIR__ . '/../../includes/email-functions.php';
require_once __DIR__ . '/../../includes/pdf-invoice.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['razorpay_payment_id']) || !isset($data['razorpay_order_id']) || !isset($data['razorpay_signature'])) {
    json_response(['error' => 'Missing verification data'], 400);
}

$razorpay_payment_id = sanitize($data['razorpay_payment_id']);
$razorpay_order_id = sanitize($data['razorpay_order_id']);
$razorpay_signature = sanitize($data['razorpay_signature']);
$order_id = (int)($data['order_id'] ?? 0);

// Fetch Razorpay keys from settings or gateways table
$stmt = $pdo->prepare("SELECT config FROM payment_gateways WHERE code = 'razorpay'");
$stmt->execute();
$gateway = $stmt->fetch();
$gw_config = $gateway ? json_decode($gateway['config'], true) : [];

$key_id = $gw_config['key_id'] ?? get_setting($pdo, 'razorpay_key_id');
$key_secret = $gw_config['key_secret'] ?? get_setting($pdo, 'razorpay_key_secret');

if (!$key_id || !$key_secret) {
    // Fallback to constants if set, or log error
    $key_id = defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '';
    $key_secret = defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '';
}

// Verify signature
$is_verified = false;
try {
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $key_secret);
    if (hash_equals($generated_signature, $razorpay_signature)) {
        $is_verified = true;
    } else {
        error_log("Razorpay signature verification failed for order #$order_id. Expected: $generated_signature, Got: $razorpay_signature");
    }
} catch (Exception $e) {
    error_log("Razorpay verification error: " . $e->getMessage());
}

if ($is_verified) {
    try {
        $pdo->beginTransaction();

        // Update Order
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_verified = 1, status = 'processing' WHERE id = ?");
        $stmt->execute([$order_id]);

        // Update Payment Record
        $stmt = $pdo->prepare("UPDATE payments SET status = 'paid', transaction_id = ?, gateway_response = ? WHERE order_id = ? AND payment_id = ? AND gateway = 'razorpay'");
        $stmt->execute([$razorpay_payment_id, json_encode($data), $order_id, $razorpay_order_id]);

        // Generate Invoice
        $pdfData = generateInvoicePDF($order_id);

        // Send Confirmation Email
        $stmt = $pdo->prepare("SELECT o.*, c.email, c.name FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if ($order) {
            // Update customer order count
            $pdo->prepare("UPDATE customers SET orders_count = IFNULL(orders_count, 0) + 1 WHERE id = ?")->execute([$order['customer_id']]);

            // Deduct stock
            $items = json_decode($order['items'], true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    log_inventory($pdo, $item['product_id'], -$item['quantity'], 'order', $order_id);
                }
            }

            // Clear cart (using session_id or user_id)
            $pdo->prepare("DELETE FROM carts WHERE client_id = ? AND (user_id = ? OR session_id = ?)")->execute([
                CLIENT_ID, 
                $order['customer_id'],
                $data['session_id'] ?? '' 
            ]);

            // Format address for email
            $customer_full_address = ($order['shipping_address1'] ?? '') . ', ' . ($order['shipping_address2'] ?? '') . ', ' . ($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? '') . ' - ' . ($order['shipping_pincode'] ?? '');

            send_online_payment_confirmation_email($order['email'], $order['name'], $order_id, $pdfData, $order['subtotal'], $order['shipping_fee'], $order['tax_amount'], $order['discount_amount'], $order['total'], $items, [
                'phone' => $order['shipping_phone'] ?? '',
                'address' => $customer_full_address
            ]);
            
            // Send Admin Notification
            send_admin_order_notification($order_id, $order['total'], $order['shipping_name'] ?? $order['name'], $items, $order['subtotal'], $order['shipping_fee'], $order['tax_amount'], $order['discount_amount'], $order['payment_method'], [
                'phone' => $order['shipping_phone'] ?? '',
                'email' => $order['email'] ?? '',
                'address' => $customer_full_address
            ]);
        }

        $pdo->commit();
        json_response(['status' => 'success', 'message' => 'Payment verified and order updated']);

    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['error' => 'Verification processing failed: ' . $e->getMessage()], 500);
    }
} else {
    json_response(['error' => 'Invalid signature'], 400);
}
