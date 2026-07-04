<?php
require_once 'api_init.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'view';

try {
    if ($method === 'GET' && $action === 'view') {
        $session_id = sanitize($_GET['session_id'] ?? '');
        $user_id = (int)($_GET['user_id'] ?? 0);
        
        $country = sanitize($_GET['country'] ?? '');
        $state = sanitize($_GET['state'] ?? '');
        $city = sanitize($_GET['city'] ?? '');
        
        if (empty($session_id) && $user_id <= 0) {
            log_api_call($pdo, 400, 'session_id or user_id required');
            json_response(['error' => 'session_id or user_id required'], 400);
        }
        
        // Fetch cart by user_id first, then session_id
        if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE client_id = ? AND user_id = ?");
            $stmt->execute([CLIENT_ID, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE client_id = ? AND session_id = ?");
            $stmt->execute([CLIENT_ID, $session_id]);
        }
        $cart = $stmt->fetch();
        
        if (!$cart) {
            log_api_call($pdo, 200);
            json_response(['status' => 'success', 'items' => [], 'subtotal' => 0, 'shipping' => 0, 'total' => 0]);
        }
        
        $items = json_decode($cart['items'], true) ?: [];
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Dynamic Shipping Estimate
        $shipping = calculate_shipping($pdo, $subtotal, $country, $state, $city);
        
        // Tax Estimate
        $tax_percent = (float)get_setting($pdo, 'tax_gst_percentage', 0, 'tax');
        $tax_inclusive = (bool)get_setting($pdo, 'tax_inclusive', false, 'tax');
        
        if ($tax_inclusive) {
            $tax_amount = $subtotal * ($tax_percent / (100 + $tax_percent));
            $total = $subtotal + $shipping;
        } else {
            $tax_amount = $subtotal * ($tax_percent / 100);
            $total = $subtotal + $tax_amount + $shipping;
        }
        
        log_api_call($pdo, 200);
        json_response([
            'status' => 'success', 
            'items' => $items, 
            'subtotal' => $subtotal,
            'tax' => $tax_amount,
            'shipping' => $shipping,
            'total' => $total,
            'currency' => get_setting($pdo, 'currency_code', 'INR'),
            'symbol' => get_setting($pdo, 'currency_symbol', '₹')
        ]);
        
    } elseif ($method === 'POST' && $action === 'add') {
        $data = json_decode(file_get_contents('php://input'), true);
        $product_id = (int)($data['product_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);
        $variant = sanitize($data['variant'] ?? '');
        $session_id = sanitize($data['session_id'] ?? '');
        $user_id = (int)($data['user_id'] ?? 0);
        
        if ((empty($session_id) && $user_id <= 0) || $product_id <= 0) {
            log_api_call($pdo, 400, 'Invalid parameters');
            json_response(['error' => 'Invalid parameters'], 400);
        }
        
        // Get product info
        $stmt = $pdo->prepare("SELECT p.name, p.price, p.price_1kg, p.price_500g, p.price_250g, p.stock, p.images, c.name as category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id
                               WHERE p.id = ? AND p.status = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            log_api_call($pdo, 400, 'Product not found');
            json_response(['error' => 'Product not found'], 400);
        }

        if ($product['stock'] < $quantity) {
            log_api_call($pdo, 400, 'Insufficient stock');
            json_response(['error' => 'Insufficient stock'], 400);
        }

        // Determine correct price based on variant
        $price = (float)$product['price']; // Default/Base
        if ($variant === '1 Kilogram') {
            $price = (float)$product['price_1kg'];
        } elseif ($variant === '1/2 Kg') {
            $price = (float)$product['price_500g'];
        } elseif ($variant === '1/4 Kg') {
            $price = (float)$product['price_250g'];
        }
        
        if ($price <= 0) {
            $price = (float)$product['price'];
        }

        $images = json_decode($product['images'], true) ?: [];
        $image = !empty($images) ? $images[0] : '';
        
        // Fetch existing cart
        if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE client_id = ? AND user_id = ?");
            $stmt->execute([CLIENT_ID, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE client_id = ? AND session_id = ?");
            $stmt->execute([CLIENT_ID, $session_id]);
        }
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $items = ($cart && !empty($cart['items'])) ? json_decode($cart['items'], true) : [];
        if (!is_array($items)) $items = [];
        
        $found = false;
        foreach ($items as &$item) {
            if ($item['product_id'] == $product_id && ($item['variant'] ?? '') == $variant) {
                $item['quantity'] += $quantity;
                $item['price'] = $price; // Update price in case it was wrong
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $items[] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'variant' => $variant,
                'image' => $image,
                'category' => $product['category_name'] ?: 'General'
            ];
        }
        
        $items_json = json_encode($items);
        
        if ($cart) {
            $stmt = $pdo->prepare("UPDATE carts SET items = ?, session_id = ?, user_id = ? WHERE id = ?");
            $stmt->execute([$items_json, $session_id, $user_id ?: $cart['user_id'], $cart['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO carts (client_id, session_id, user_id, items) VALUES (?, ?, ?, ?)");
            $stmt->execute([CLIENT_ID, $session_id, $user_id ?: null, $items_json]);
        }
        
        log_api_call($pdo, 201);
        json_response(['status' => 'success', 'message' => 'Product added to cart']);

    } elseif ($method === 'POST' && ($action === 'update' || $action === 'remove')) {
        $data = json_decode(file_get_contents('php://input'), true);
        $product_id = (int)($data['product_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 0);
        $variant = sanitize($data['variant'] ?? '');
        $session_id = sanitize($data['session_id'] ?? '');
        $user_id = (int)($data['user_id'] ?? 0);

        if ((empty($session_id) && $user_id <= 0) || $product_id <= 0) {
            json_response(['error' => 'Invalid parameters'], 400);
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
            json_response(['error' => 'Cart not found'], 404);
        }

        $items = json_decode($cart['items'], true) ?: [];
        $new_items = [];

        foreach ($items as $item) {
            if ($item['product_id'] == $product_id && ($item['variant'] ?? '') == $variant) {
                if ($action === 'update' && $quantity > 0) {
                    $item['quantity'] = $quantity;
                    $new_items[] = $item;
                }
                // if action is 'remove' or quantity <= 0, we don't add it to new_items
            } else {
                $new_items[] = $item;
            }
        }

        $items_json = json_encode($new_items);
        $stmt = $pdo->prepare("UPDATE carts SET items = ?, session_id = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$items_json, $session_id, $user_id ?: $cart['user_id'], $cart['id']]);

        json_response(['status' => 'success', 'message' => 'Cart updated']);

    } elseif ($method === 'POST' && $action === 'clear') {
        $data = json_decode(file_get_contents('php://input'), true);
        $session_id = sanitize($data['session_id'] ?? '');
        $user_id = (int)($data['user_id'] ?? 0);

        if (empty($session_id) && $user_id <= 0) {
            json_response(['error' => 'Invalid parameters'], 400);
        }

        if ($user_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM carts WHERE client_id = ? AND user_id = ?");
            $stmt->execute([CLIENT_ID, $user_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM carts WHERE client_id = ? AND session_id = ?");
            $stmt->execute([CLIENT_ID, $session_id]);
        }

        json_response(['status' => 'success', 'message' => 'Cart cleared']);

    } elseif ($method === 'POST' && $action === 'merge') {
        // Link cart to user_id after login
        $data = json_decode(file_get_contents('php://input'), true);
        $session_id = sanitize($data['session_id'] ?? '');
        $user_id = (int)($data['user_id'] ?? 0);

        if (empty($session_id) || $user_id <= 0) {
            json_response(['error' => 'Invalid parameters'], 400);
        }

        try {
            $pdo->beginTransaction();

            // 1. Fetch Guest Cart
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE session_id = ? AND client_id = ? AND user_id IS NULL");
            $stmt->execute([$session_id, CLIENT_ID]);
            $guest_cart = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Fetch User Cart
            $stmt = $pdo->prepare("SELECT * FROM carts WHERE user_id = ? AND client_id = ?");
            $stmt->execute([$user_id, CLIENT_ID]);
            $user_cart = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($guest_cart) {
                $guest_items = json_decode($guest_cart['items'], true) ?: [];
                
                if ($user_cart) {
                    // Merge Guest items into User cart
                    $user_items = json_decode($user_cart['items'], true) ?: [];
                    
                    foreach ($guest_items as $g_item) {
                        $found = false;
                        foreach ($user_items as &$u_item) {
                            if ($u_item['product_id'] == $g_item['product_id'] && ($u_item['variant'] ?? '') == ($g_item['variant'] ?? '')) {
                                // If already in user cart, we could either keep user's quantity or guest's
                                // User said "no more adding extra or repeated", so if it's already there, we just keep it as is.
                                // Or we could add quantities? "no more adding extra" usually means don't duplicate rows.
                                // Let's keep the user's item as is if it exists, or update if user wants guest's selection.
                                // Usually merging means user wants what they just picked as guest.
                                $u_item['quantity'] = $g_item['quantity']; 
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $user_items[] = $g_item;
                        }
                    }
                    
                    $stmt = $pdo->prepare("UPDATE carts SET items = ?, session_id = ? WHERE id = ?");
                    $stmt->execute([json_encode($user_items), $session_id, $user_cart['id']]);
                    
                    // Delete guest cart
                    $pdo->prepare("DELETE FROM carts WHERE id = ?")->execute([$guest_cart['id']]);
                } else {
                    // Just associate guest cart with user
                    $stmt = $pdo->prepare("UPDATE carts SET user_id = ? WHERE id = ?");
                    $stmt->execute([$user_id, $guest_cart['id']]);
                }
            }

            $pdo->commit();
            json_response(['status' => 'success', 'message' => 'Cart merged successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['error' => 'Merge failed: ' . $e->getMessage()], 500);
        }
    }
} catch (Throwable $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Server Error: ' . $e->getMessage()], 500);
}
