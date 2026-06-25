<?php
require_once 'c:/xampp/htdocs/goappalam-main/backend/includes/db_connection.php';
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
header('Content-Type: application/json');
echo json_encode($categories, JSON_PRETTY_PRINT);
?>