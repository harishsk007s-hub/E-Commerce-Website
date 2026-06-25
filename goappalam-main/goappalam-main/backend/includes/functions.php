<?php

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token (Disabled)
 */
function validate_csrf_token($token) {
    return true; // Always return true for now
}

/**
 * Sanitize user input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL-friendly slug
 */
function generate_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'n-a';
    return $text;
}

/**
 * Send JSON response
 */
function json_response($data, $status = 200) {
    if (ob_get_level()) ob_end_clean(); // Final check for clean output
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Handle file upload
 */
function handle_file_upload($file, $target_dir = '../uploads/') {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
        return false;
    }
    
    $filename = uniqid('img_', true) . '.' . $extension;
    $target_path = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    
    return false;
}

/**
 * Format image path for display (replicates React formatImagePath)
 */
function format_image_path($img) {
    if (empty($img)) return 'https://via.placeholder.com/50';
    if (strpos($img, 'http') === 0 || strpos($img, '/') === 0 || strpos($img, 'data:') === 0) return $img;
    
    $uploads_url = getenv('VITE_UPLOADS_URL') ?: (isset($_ENV['VITE_UPLOADS_URL']) ? $_ENV['VITE_UPLOADS_URL'] : '');
    
    // Handle legacy paths containing 'assests' (misspelled as requested)
    if (strpos($img, 'assests/') !== false) {
        if (!empty($uploads_url)) {
            // If we have a full URL, we should probably use it, but legacy paths might be different
            // For now, keep the original behavior for local but allow URL prefixing if needed
            return (strpos($img, '/') === 0 ? '' : '/') . $img;
        }
        return (strpos($img, '/') === 0 ? '' : '/') . $img;
    }
    
    if (!empty($uploads_url)) {
        return rtrim($uploads_url, '/') . '/' . ltrim($img, '/');
    }
    
    return '../uploads/' . $img;
}

/**
 * Log user activity
 */
function log_activity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $_SERVER['REMOTE_ADDR']]);
}

/**
 * Log notification (Stub)
 */
function log_notification($pdo, $customer_id, $type, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (customer_id, type, message) VALUES (?, ?, ?)");
    $stmt->execute([$customer_id, $type, $message]);
    // Actual sending code would go here
}

/**
 * Inventory logger helper
 */
function log_inventory($pdo, $product_id, $quantity_change, $type = 'manual', $order_id = null) {
    $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, quantity_change, type, order_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $quantity_change, $type, $order_id]);
    
    // Update product stock
    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    $stmt->execute([$quantity_change, $product_id]);
}

/**
 * CONFIGURATION DRIVEN HELPERS
 */

/**
 * Get a setting from the database
 */
function get_setting($pdo, $key, $default = null, $group = null) {
    $sql = "SELECT setting_value, setting_type FROM settings WHERE setting_key = ?";
    $params = [$key];
    if ($group) {
        $sql .= " AND setting_group = ?";
        $params[] = $group;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    
    if (!$row) return $default;
    
    $val = $row['setting_value'];
    switch ($row['setting_type']) {
        case 'integer': return (int)$val;
        case 'boolean': return (bool)$val;
        case 'decimal': return (float)$val;
        case 'json': return json_decode($val, true);
        default: return $val;
    }
}

/**
 * Alias for get_setting to match user request
 */
function getsettingpdo($key, $default = null) {
    global $pdo;
    return get_setting($pdo, $key, $default);
}

/**
 * Set or update a setting in the database
 */
function set_setting($pdo, $key, $value, $group = 'general', $type = 'string') {
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value);
        $type = 'json';
    }
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value, setting_type) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
    return $stmt->execute([$group, $key, $value, $type]);
}

/**
 * Check if a feature is enabled
 */
function is_feature_enabled($pdo, $feature_key, $default = true) {
    return (bool)get_setting($pdo, $feature_key, $default, 'features');
}

/**
 * Get all enabled payment gateways
 */
function get_payment_gateways($pdo, $only_enabled = true) {
    $sql = "SELECT * FROM payment_gateways WHERE code NOT IN ('stripe', 'paypal', 'upi')";
    if ($only_enabled) {
        $sql .= " AND enabled = 1";
    }
    $sql .= " ORDER BY sort_order ASC";
    
    $stmt = $pdo->query($sql);
    $gateways = $stmt->fetchAll();
    
    foreach ($gateways as &$gw) {
        $gw['config'] = json_decode($gw['config'], true);
    }
    
    return $gateways;
}

/**
 * Calculate Shipping Fee based on Rules
 */
function calculate_shipping($pdo, $subtotal, $country = '', $state = '', $city = '') {
    // Find matching zone
    $stmt = $pdo->prepare("SELECT id FROM shipping_zones 
                           WHERE status = 1 
                           AND (country = ? OR country IS NULL OR country = '')
                           AND (state = ? OR state IS NULL OR state = '')
                           AND (city = ? OR city IS NULL OR city = '')
                           ORDER BY country DESC, state DESC, city DESC LIMIT 1");
    $stmt->execute([$country, $state, $city]);
    $zone_id = $stmt->fetchColumn();
    
    if (!$zone_id) {
        // Fallback to global/default zone if exists
        $zone_id = $pdo->query("SELECT id FROM shipping_zones WHERE status = 1 LIMIT 1")->fetchColumn();
    }
    
    if (!$zone_id) return 0;
    
    // Find matching rule in zone
    $stmt = $pdo->prepare("SELECT flat_rate, free_shipping FROM shipping_rules 
                           WHERE zone_id = ? AND ? >= min_amount AND ? <= max_amount 
                           ORDER BY min_amount DESC LIMIT 1");
    $stmt->execute([$zone_id, $subtotal, $subtotal]);
    $rule = $stmt->fetch();
    
    if (!$rule) return 0;
    if ($rule['free_shipping']) return 0;
    
    return (float)$rule['flat_rate'];
}

/**
 * Get Bearer token from headers safely
 */
function get_auth_token() {
    $auth_header = '';
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        }
    }
    
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        return $matches[1];
    }
    
    return !empty($auth_header) ? $auth_header : null;
}
