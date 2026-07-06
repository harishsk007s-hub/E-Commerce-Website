<?php
require_once 'api_init.php';
require_once __DIR__ . '/../../includes/otp-system.php';
require_once __DIR__ . '/../../includes/email-functions.php';
require_once __DIR__ . '/../../includes/pdf-invoice.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'checkout';

try {
    if ($method === 'POST' && $action === 'checkout') {
        $data = json_decode(file_get_contents('php://input'), true);
        $session_id = sanitize($data['session_id'] ?? '');
        $customer_id = (int)($data['customer_id'] ?? 0);
        $user_id = (int)($data['user_id'] ?? 0); // From registered user
        $shipping_address = $data['shipping_address'] ?? [];
        $coupon_code = sanitize($data['coupon_code'] ?? '');
        $payment_method = sanitize($data['payment_method'] ?? 'cod');
        
        if (empty($session_id) && $user_id <= 0) {
            log_api_call($pdo, 400, 'session_id or user_id required');
            json_response(['error' => 'session_id or user_id required'], 400);
        }

        if (empty($shipping_address)) {
            log_api_call($pdo, 400, 'shipping_address required');
            json_response(['error' => 'shipping_address required'], 400);
        }
        
        // Fetch cart
        if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE client_id = ? AND user_id = ?");
            $stmt->execute([CLIENT_ID, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE client_id = ? AND session_id = ?");
            $stmt->execute([CLIENT_ID, $session_id]);
        }
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) {
            // Check fallback: maybe it's in localStorage but not synced? 
            // In a real app we'd trust the DB, but for debugging let's be descriptive
            log_api_call($pdo, 400, 'Cart not found in database for session: ' . $session_id);
            json_response(['error' => 'Cart not found. Please try adding items again.'], 400);
        }
        
        $items = json_decode($cart['items'], true);
        if (empty($items)) {
            log_api_call($pdo, 400, 'Cart items empty in database');
            json_response(['error' => 'Your cart is empty'], 400);
        }
        
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Handle coupon
        $discount = 0;
        if (!empty($coupon_code)) {
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 1 AND (expiry IS NULL OR expiry >= CURDATE()) AND used_count < usage_limit");
            $stmt->execute([$coupon_code]);
            $coupon = $stmt->fetch();
            
            if ($coupon) {
                if ($coupon['discount_type'] === 'percentage') {
                    $discount = ($subtotal * $coupon['discount_value']) / 100;
                } else {
                    $discount = min($subtotal, $coupon['discount_value']);
                }
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$coupon['id']]);
            }
        }
        
        // Shipping & Tax
        $shipping_fee = calculate_shipping($pdo, $subtotal - $discount, $shipping_address['country'] ?? '', $shipping_address['state'] ?? '', $shipping_address['city'] ?? '');
        $tax_percent = (float)get_setting($pdo, 'tax_gst_percentage', 0, 'tax');
        $tax_amount = ($subtotal - $discount) * ($tax_percent / 100);
        $total = ($subtotal - $discount) + $tax_amount + $shipping_fee;
        
        // Create order
        $cod_otp = ($payment_method === 'cod') ? generateOTP() : null;
        $order_status = ($payment_method === 'cod') ? 'pending' : 'pending_payment';
        
        // Update customer profile with shipping details if not already set or update them
        if ($customer_id || $user_id) {
            $cid = $customer_id ?: $user_id;
            $stmt = $pdo->prepare("UPDATE customers SET 
                phone = COALESCE(NULLIF(phone, ''), ?),
                addresses = ?::json
                WHERE id = ?");
            
            // For addresses, we can store the latest one in the JSON array or just as a single object if that's how it's used
            // Here we'll store it as a single address object in the JSON for simplicity, as requested
            $stmt->execute([
                $shipping_address['phone'] ?? '',
                json_encode($shipping_address),
                $cid
            ]);
        }

        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, client_id, subtotal, tax_amount, shipping_fee, discount_amount, total, items, shipping_name, shipping_phone, shipping_address1, shipping_address2, shipping_landmark, shipping_city, shipping_state, shipping_pincode, shipping_address, payment_method, cod_delivery_otp, status, payment_status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?::json, ?, ?, ?, ?, ?, ?, ?, ?, ?::json, ?, ?, ?, ?)");
        $stmt->execute([
            $customer_id ?: ($user_id ?: null),
            CLIENT_ID,
            $subtotal,
            $tax_amount,
            $shipping_fee,
            $discount,
            $total,
            json_encode($items),
            $shipping_address['name'] ?? '',
            $shipping_address['phone'] ?? '',
            $shipping_address['address1'] ?? '',
            $shipping_address['address2'] ?? '',
            $shipping_address['address3'] ?? '',
            $shipping_address['city'] ?? '',
            $shipping_address['state'] ?? '',
            $shipping_address['pincode'] ?? '',
            json_encode($shipping_address),
            $payment_method,
            $cod_otp,
            $order_status,
            'pending' // payment_status
        ]);
        $order_id = $pdo->lastInsertId('orders_id_seq');
        
        $customer_full_address = ($shipping_address['address1'] ?? '') . ', ' . ($shipping_address['address2'] ?? '') . ', ' . ($shipping_address['city'] ?? '') . ', ' . ($shipping_address['state'] ?? '') . ' - ' . ($shipping_address['pincode'] ?? '');

        // Post-order actions ONLY for COD. 
        // For Razorpay, these will happen in payment-verify.php after successful payment.
        if ($payment_method === 'cod') {
            // Update customer order count
            if ($customer_id || $user_id) {
                $pdo->prepare("UPDATE customers SET orders_count = COALESCE(orders_count, 0) + 1 WHERE id = ?")->execute([$customer_id ?: $user_id]);
            }
            
            // Generate Invoice PDF
            $pdfData = generateInvoicePDF($order_id);
            
            // Send Email
            $stmt = $pdo->prepare("SELECT email FROM customers WHERE id = ?");
            $stmt->execute([$customer_id ?: $user_id]);
            $customer = $stmt->fetch();
            
            if ($customer) {
                $customer_name = $shipping_address['name'] ?? 'Customer';
                
                send_cod_confirmation_email($customer['email'], $customer_name, $order_id, $cod_otp, $pdfData, $subtotal, $shipping_fee, $tax_amount, $discount, $total, $items, [
                    'phone' => $shipping_address['phone'] ?? '',
                    'address' => $customer_full_address
                ]);
            }
            
            // Send Admin Notification
            send_admin_order_notification($order_id, $total, $shipping_address['name'] ?? 'Customer', $items, $subtotal, $shipping_fee, $tax_amount, $discount, $payment_method, [
                'phone' => $shipping_address['phone'] ?? '',
                'email' => $customer['email'] ?? '',
                'address' => $customer_full_address
            ]);
            
            // Deduct stock
            foreach ($items as $item) {
                log_inventory($pdo, $item['product_id'], -$item['quantity'], 'order', $order_id);
            }
            
            // Create payment record for COD (initially pending)
            $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, gateway, status, transaction_id) 
                                   VALUES (?, ?, 'cod', 'pending', ?)");
            $stmt->execute([$order_id, $total, "COD_" . uniqid()]);
            
            // Clear cart
            $pdo->prepare("DELETE FROM carts WHERE id = ?")->execute([$cart['id']]);
        }
        
        log_api_call($pdo, 201);
        
        $response_data = [
            'status' => 'success',
            'order_id' => $order_id,
            'total' => $total,
            'cod_otp' => $cod_otp
        ];

        // Handle Razorpay Order Creation
        if ($payment_method === 'razorpay') {
            require_once __DIR__ . '/../../includes/payment-razorpay.php';
            try {
                $rzp_order = create_razorpay_order($pdo, $order_id, $total);
                $response_data['razorpay_order_id'] = $rzp_order['razorpay_order_id'];
                $response_data['razorpay_key_id'] = $rzp_order['key_id'];
            } catch (Exception $e) {
                // Log the error and return 400
                error_log("Razorpay Error: " . $e->getMessage());
                json_response(['error' => 'Razorpay Error: ' . $e->getMessage()], 400);
            }
        }

        json_response($response_data, 201);
        
    } elseif ($method === 'GET' && $action === 'list') {
        $user_id = (int)($_GET['user_id'] ?? 0);
        if ($user_id <= 0) {
            json_response(['error' => 'user_id required'], 400);
        }
        
        // ORDER BY id DESC as requested
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? AND client_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id, CLIENT_ID]);
        $orders = $stmt->fetchAll();
        
        $uploads_url = getenv('VITE_UPLOADS_URL') ?: (defined('UPLOADS_URL') ? UPLOADS_URL : '/backend/uploads/');
        
        foreach ($orders as &$o) {
            $items = json_decode($o['items'], true);
            if (is_array($items)) {
                foreach ($items as &$item) {
                    if (isset($item['image']) && !empty($item['image'])) {
                        // If it's not an absolute URL, prefix with uploads_url
                        if (!preg_match('~^(?:f|ht)tps?://~i', $item['image']) && substr($item['image'], 0, 1) !== '/') {
                            $item['image'] = rtrim($uploads_url, '/') . '/' . ltrim($item['image'], '/');
                        }
                    }
                }
            }
            $o['items'] = $items;
            $o['shipping_address'] = json_decode($o['shipping_address'], true);
        }
        
        json_response(['status' => 'success', 'orders' => $orders]);
        
    } elseif ($method === 'GET' && $action === 'view') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND client_id = ?");
        $stmt->execute([$id, CLIENT_ID]);
        $order = $stmt->fetch();
        
        if (!$order) {
            json_response(['error' => 'Order not found'], 404);
        }
        
        $uploads_url = getenv('VITE_UPLOADS_URL') ?: (defined('UPLOADS_URL') ? UPLOADS_URL : '/backend/uploads/');
        $items = json_decode($order['items'], true);
        if (is_array($items)) {
            foreach ($items as &$item) {
                if (isset($item['image']) && !empty($item['image'])) {
                    if (!preg_match('~^(?:f|ht)tps?://~i', $item['image']) && substr($item['image'], 0, 1) !== '/') {
                        $item['image'] = rtrim($uploads_url, '/') . '/' . ltrim($item['image'], '/');
                    }
                }
            }
        }
        $order['items'] = $items;
        $order['shipping_address'] = json_decode($order['shipping_address'], true);
        json_response(['status' => 'success', 'order' => $order]);
    }
} catch (Throwable $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Server Error: ' . $e->getMessage()], 500);
}
