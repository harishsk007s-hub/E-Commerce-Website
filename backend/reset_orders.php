<?php
require_once 'includes/db_connection.php';

try {
    $pdo->beginTransaction();
    
    // Truncate tables to remove all data and reset sequences using CASCADE in PostgreSQL
    $pdo->exec("TRUNCATE TABLE payments, invoices, orders, inventory_logs, activity_logs, api_logs RESTART IDENTITY CASCADE");
    
    // Set serial sequence to start from 1001 for orders
    try {
        $pdo->exec("ALTER SEQUENCE orders_id_seq RESTART WITH 1001");
    } catch (Exception $seqEx) {}
    
    // Reset customer order counts
    $pdo->exec("UPDATE customers SET orders_count = 0");
    
    $pdo->commit();
    echo "Successfully reset all orders and transaction history. New orders will start from ID 1001.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error resetting orders: " . $e->getMessage();
}
?>