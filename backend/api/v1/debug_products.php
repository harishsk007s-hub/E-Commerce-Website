<?php
require_once 'api_init.php';

try {
    $sql = "SELECT p.id, p.name, p.status, p.category_id, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($products, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
