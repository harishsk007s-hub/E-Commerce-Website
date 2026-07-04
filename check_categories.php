<?php
require_once 'backend/includes/db_connection.php';
$stmt = $pdo->query("SELECT id, name, image FROM categories");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Image: [{$row['image']}]\n";
}
