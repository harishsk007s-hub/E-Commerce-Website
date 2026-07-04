<?php
require_once 'includes/db_connection.php';

try {
    $pdo->beginTransaction();
    
    // Disable foreign key checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Truncate tables to remove all data and reset AUTO_INCREMENT
    $pdo->exec("TRUNCATE TABLE payments");
    $pdo->exec("TRUNCATE TABLE invoices");
    $pdo->exec("TRUNCATE TABLE orders");
    $pdo->exec("TRUNCATE TABLE inventory_logs");
    $pdo->exec("TRUNCATE TABLE activity_logs");
    $pdo->exec("TRUNCATE TABLE api_logs");
    
    // Set AUTO_INCREMENT to 1001 for orders
    $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1001");
    
    // Reset customer order counts
    $pdo->exec("UPDATE customers SET orders_count = 0");
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $pdo->commit();
    echo "Successfully reset all orders and transaction history. New orders will start from ID 1001.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error resetting orders: " . $e->getMessage();
}
?>