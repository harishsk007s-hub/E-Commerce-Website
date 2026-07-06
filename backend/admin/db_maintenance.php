<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Only super-admin can run this
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super-admin') {
    die("Unauthorized access.");
}

try {
    // Reset sequence counters for core tables in PostgreSQL
    $tables = ['customers', 'orders', 'payments', 'invoices', 'carts', 'activity_logs', 'api_logs', 'inventory_logs'];
    
    foreach ($tables as $table) {
        $seq = "{$table}_id_seq";
        $res = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM \"$table\"")->fetch();
        $next_id = $res['next_id'] ?? 1;
        
        // For orders, start from 1001 if empty
        if ($table === 'orders' && $next_id == 1) {
            $next_id = 1001;
        }
        
        try {
            $pdo->exec("ALTER SEQUENCE \"$seq\" RESTART WITH $next_id");
        } catch (Exception $seqEx) {
            // Ignore if sequence doesn't exist
        }
    }
    
    echo "Database auto-increment counters have been reset successfully.";
} catch (Exception $e) {
    echo "Error resetting database: " . $e->getMessage();
}
