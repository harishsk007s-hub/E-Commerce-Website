<?php
/**
 * Razorpay API Credentials
 * Loaded from environment variables for production security.
 */
return [
    'key_id' => $_ENV['RAZORPAY_KEY_ID'] ?? $_SERVER['RAZORPAY_KEY_ID'] ?? getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_XXXXXXXXXXXXXX',
    'key_secret' => $_ENV['RAZORPAY_KEY_SECRET'] ?? $_SERVER['RAZORPAY_KEY_SECRET'] ?? getenv('RAZORPAY_KEY_SECRET') ?: 'XXXXXXXXXXXXXXXXXXXXXX',
    'display_currency' => 'INR'
];
