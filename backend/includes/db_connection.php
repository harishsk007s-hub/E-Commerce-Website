<?php
// Simple .env loader if not already defined (duplicated for reliability across different entry points)
if (!function_exists('loadEnvForDB')) {
    function loadEnvForDB($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (preg_match('/^"(.+)"$/', $value, $matches) || preg_match("/^'(.+)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Try to load .env from root
loadEnvForDB(__DIR__ . '/../../.env');

$db_config = require __DIR__ . '/../config/database.php';

$dsn = "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_config['user'], $db_config['password'], $options);
} catch (\PDOException $e) {
    // Log the detailed error on the server and return a generic message to the client
    $errId = uniqid('dberr_', true);
    error_log("DB_CONN_ERROR [{$errId}]: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed", 
        "details" => $e->getMessage(),
        "error_id" => $errId
    ]);
    exit;
}
