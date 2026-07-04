<?php
require_once '../includes/db_connection.php';
$products = $pdo->query("SELECT id, name, image FROM products LIMIT 5")->fetchAll();
header('Content-Type: application/json');
echo json_encode($products, JSON_PRETTY_PRINT);
?>