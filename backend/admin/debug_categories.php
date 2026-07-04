<?php
require_once '../includes/db_connection.php';
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
header('Content-Type: application/json');
echo json_encode($categories, JSON_PRETTY_PRINT);
?>