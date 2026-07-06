<?php

/**
 * Database Configuration for PostgreSQL
 * Parses DATABASE_URL if available (Render format), otherwise falls back to environment variables.
 */
$db_url_str = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';

if (!empty($db_url_str)) {
    $db_url = parse_url($db_url_str);
    $host = $db_url['host'] ?? 'localhost';
    $port = $db_url['port'] ?? '5432';
    $user = $db_url['user'] ?? '';
    $pass = $db_url['pass'] ?? '';
    $dbname = ltrim($db_url['path'] ?? '', '/');
} else {
    $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: '5432';
    $dbname = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'goappala-demo';
    $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: 'postgres';
    $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?: '';
}

return [
    'host' => $host,
    'port' => $port,
    'dbname' => $dbname,
    'user' => $user,
    'password' => $pass
];
