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
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Reset auto-increment for core tables
    $tables = ['customers', 'orders', 'payments', 'invoices', 'carts', 'activity_logs', 'api_logs', 'inventory_logs'];
    
    foreach ($tables as $table) {
        // Double check if empty before resetting? Or just reset.
        $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1;");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "Database auto-increment counters have been reset to 1 successfully.";
} catch (Exception $e) {
    echo "Error resetting database: " . $e->getMessage();
}
