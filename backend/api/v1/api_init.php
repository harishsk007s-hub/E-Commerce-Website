<?php
ob_start();
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../../includes/db_connection.php';
require_once __DIR__ . '/../../includes/functions.php';

// More robust CORS handling for production
$env_origins = $_ENV['ALLOWED_ORIGINS'] ?? $_SERVER['ALLOWED_ORIGINS'] ?? getenv('ALLOWED_ORIGINS');
$default_origins = 'http://localhost:5173,http://localhost:5174,http://127.0.0.1:5173,http://127.0.0.1:5174,http://localhost,https://delight.goappalam.in,http://delight.goappalam.in,https://goappalam.in';
$allowed_origins = array_filter(explode(',', $env_origins ?: $default_origins));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else if (!$origin) {
    header("Access-Control-Allow-Origin: *");
} else {
    $prod_url = getenv('BASE_URL');
    header("Access-Control-Allow-Origin: " . ($prod_url ?: "*"));
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set Frontend URL for magic links and redirects using environment variable

// Production Error Handling - prevent leaking paths/DB info
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    
    $error_msg = "PHP Error [$errno]: $errstr in $errfile on line $errline";
    error_log($error_msg);
    
    if (getenv('APP_ENV') === 'production') {
        // Log more info but still keep production safe for normal users
        // However, for debugging this specific issue, let's include the error string
        json_response(['error' => 'Server Error: ' . $errstr], 500);
    } else {
        json_response(['error' => $error_msg], 500);
    }
    exit;
});

$start_time = microtime(true);

// Authentication
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($api_key)) {
    json_response(['error' => 'API Key required'], 401);
}

try {
    // Check if api_clients table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'api_clients'");
    if (!$stmt->fetch()) {
        json_response(['error' => 'System error: api_clients table missing. Run update_db.php'], 500);
    }

    $stmt = $pdo->prepare("SELECT id, status FROM api_clients WHERE api_key = ?");
    $stmt->execute([$api_key]);
    $client = $stmt->fetch();

    if (!$client || $client['status'] != 1) {
        json_response(['error' => 'Invalid or inactive API Key'], 403);
    }

    define('CLIENT_ID', $client['id']);
} catch (PDOException $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

/**
 * Log API Call
 */
function log_api_call($pdo, $status, $error = null) {
    if (!defined('CLIENT_ID')) return;
    try {
        global $start_time;
        $response_time = round((microtime(true) - $start_time) * 1000);
        $endpoint = $_SERVER['PHP_SELF'];
        $method = $_SERVER['REQUEST_METHOD'];
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $pdo->prepare("INSERT INTO api_logs (client_id, endpoint, method, status, response_time, error, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([CLIENT_ID, $endpoint, $method, $status, $response_time, $error, $ip]);
    } catch (Exception $e) {
        // Silent fail for logs
    }
}
