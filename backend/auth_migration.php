<?php
/**
 * Auth Migration Script
 */
$config = require __DIR__ . '/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    function addColumnIfNotExists($pdo, $table, $column, $definition, $after = '') {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        if (!$stmt->fetch()) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            if ($after) {
                $sql .= " AFTER `$after`";
            }
            $pdo->exec($sql);
            echo "Added column `$column` to table `$table`.\n";
            return true;
        }
        return false;
    }

    // Update customers table
    addColumnIfNotExists($pdo, 'customers', 'username', 'VARCHAR(100) UNIQUE', 'email');
    addColumnIfNotExists($pdo, 'customers', 'password_hash', 'VARCHAR(255)', 'username');
    addColumnIfNotExists($pdo, 'customers', 'auth_token', 'VARCHAR(255)', 'password_hash');
    addColumnIfNotExists($pdo, 'customers', 'auth_token_expires', 'DATETIME', 'auth_token');
    addColumnIfNotExists($pdo, 'customers', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');

    // Create magic_links table
    $pdo->exec("CREATE TABLE IF NOT EXISTS magic_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at BIGINT NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Ensured `magic_links` table exists.\n";

    echo "Auth migration completed successfully.\n";

} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}
