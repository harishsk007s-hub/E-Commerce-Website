<?php
/**
 * Auth Migration Script for PostgreSQL
 */
$config = require __DIR__ . '/config/database.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    function addColumnIfNotExists($pdo, $table, $column, $definition, $after = '') {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
        $stmt->execute([strtolower($table), strtolower($column)]);
        if (!$stmt->fetch()) {
            $sql = "ALTER TABLE \"$table\" ADD COLUMN \"$column\" $definition";
            $pdo->exec($sql);
            echo "Added column `$column` to table `$table`.\n";
            return true;
        }
        return false;
    }

    // Update customers table
    addColumnIfNotExists($pdo, 'customers', 'username', 'VARCHAR(100) UNIQUE');
    addColumnIfNotExists($pdo, 'customers', 'password_hash', 'VARCHAR(255)');
    addColumnIfNotExists($pdo, 'customers', 'auth_token', 'VARCHAR(255)');
    addColumnIfNotExists($pdo, 'customers', 'auth_token_expires', 'TIMESTAMP');
    addColumnIfNotExists($pdo, 'customers', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

    // Create magic_links table
    $pdo->exec("CREATE TABLE IF NOT EXISTS magic_links (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at BIGINT NOT NULL,
        used SMALLINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Ensured `magic_links` table exists.\n";

    echo "Auth migration completed successfully.\n";

} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}
