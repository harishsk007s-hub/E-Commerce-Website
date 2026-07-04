<?php
require_once __DIR__ . '/api_init.php';

try {
    // 1. Fetch general settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value, setting_type FROM settings");
    $stmt->execute();
    $raw_settings = $stmt->fetchAll();
    
    $settings = [
        'general' => [],
        'tax' => [],
        'seo' => [],
        'features' => []
    ];
    
    foreach ($raw_settings as $s) {
        $val = $s['setting_value'];
        if ($s['setting_type'] === 'boolean') $val = (bool)$val;
        if ($s['setting_type'] === 'decimal' || $s['setting_type'] === 'integer') $val = (float)$val;
        if ($s['setting_type'] === 'json') $val = json_decode($val, true);
        
        // Group them by setting_group if we have it, or infer from prefix
        // For simplicity, let's map them to common groups
        if (in_array($s['setting_key'], ['store_name', 'store_email', 'currency_code', 'currency_symbol', 'timezone'])) {
            $settings['general'][$s['setting_key']] = $val;
        } elseif (strpos($s['setting_key'], 'tax_') === 0) {
            $settings['tax'][$s['setting_key']] = $val;
        } elseif (strpos($s['setting_key'], 'google_') === 0) {
            $settings['seo'][$s['setting_key']] = $val;
        } else {
            $settings['features'][$s['setting_key']] = $val;
        }
    }

    // 2. Fetch enabled payment gateways
    $stmt = $pdo->prepare("SELECT code, name, config FROM payment_gateways WHERE enabled = 1 AND code NOT IN ('stripe', 'paypal', 'upi') ORDER BY sort_order ASC");
    $stmt->execute();
    $gateways = $stmt->fetchAll();
    foreach ($gateways as &$g) {
        $g['config'] = json_decode($g['config'], true);
        // Remove secrets if needed, but for demo we keep them or just public ones
        unset($g['config']['secret_key']);
        unset($g['config']['secret']);
        unset($g['config']['key_secret']);
    }
    $settings['payment_methods'] = $gateways;

    // 3. Fetch banners
    $stmt = $pdo->prepare("SELECT title, subtitle, image, link_url FROM banners WHERE status = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $settings['banners'] = $stmt->fetchAll();

    // 4. Fetch categories
    $stmt = $pdo->prepare("SELECT id, name, slug, image, parent_id FROM categories ORDER BY sort_order ASC");
    $stmt->execute();
    $settings['categories'] = $stmt->fetchAll();

    log_api_call($pdo, 200);
    json_response($settings);

} catch (PDOException $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Internal Server Error'], 500);
}
