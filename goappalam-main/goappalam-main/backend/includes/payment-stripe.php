<?php
/**
 * Mock Stripe Integration
 */
function stripe_create_session($pdo, $order_id, $amount) {
    // In real integration, call Stripe API here
    $stripe_session_id = 'cs_' . bin2hex(random_bytes(12));
    
    // Save to payments table
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, gateway, status, payment_id) VALUES (?, ?, 'stripe', 'pending', ?)");
    $stmt->execute([$order_id, $amount, $stripe_session_id]);
    
    return $stripe_session_id;
}

function stripe_handle_webhook($payload) {
    // Webhook logic
    return ['status' => 'success', 'order_id' => 1]; // Mock
}
