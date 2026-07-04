<?php
/**
 * Robust Database Update Script for MySQL (XAMPP Compatible)
 */
$config = require __DIR__ . '/config/database.php';

try {
    // Attempt direct connection to database first (standard for cloud hosted DBs)
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // If unknown database error (1049), attempt database creation (standard for local development)
    if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};charset={$config['charset']}",
                $config['user'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}`");
            
            // Reconnect
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $createEx) {
            die("Error creating database: " . $createEx->getMessage() . "\n");
        }
    } else {
        die("Error connecting to database: " . $e->getMessage() . "\n");
    }
}

/**
 * Helper to add a column only if it doesn't already exist
 */
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

// 0. Ensure tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    image VARCHAR(255),
    description TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE,
    category_id INT,
    price VARCHAR(255) NOT NULL,
    stock INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    slug VARCHAR(255) UNIQUE,
    images JSON,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    items JSON,
    shipping_address JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) AUTO_INCREMENT = 1001");

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_group VARCHAR(50),
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string'
)");

// 1. Update users table
addColumnIfNotExists($pdo, 'users', 'password', 'VARCHAR(255)', 'username');
addColumnIfNotExists($pdo, 'users', 'phone', 'VARCHAR(20)', 'email');
addColumnIfNotExists($pdo, 'users', 'otp_code', 'VARCHAR(6)', 'status');
addColumnIfNotExists($pdo, 'users', 'otp_expires', 'DATETIME', 'otp_code');
addColumnIfNotExists($pdo, 'users', 'login_attempts', 'INT DEFAULT 0', 'otp_expires');

// Ensure admin user exists with correct password
$admin_pass = password_hash('admin@2026', PASSWORD_DEFAULT);

// Check if admin user exists at all
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
$stmt->execute();
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, status) VALUES ('admin', ?, 'admin@example.com', 'super-admin', 1)");
    $stmt->execute([$admin_pass]);
    echo "Created admin user.\n";
} else {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin' AND (password IS NULL OR password = '')");
    $stmt->execute([$admin_pass]);
    
    // Check if admin has a password, if not, set it anyway (force update if needed)
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin_current = $stmt->fetch();
    if ($admin_current && empty($admin_current['password'])) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$admin_pass]);
    }
}

// 2. Update orders table
addColumnIfNotExists($pdo, 'orders', 'shipping_name', 'VARCHAR(255)', 'items');
addColumnIfNotExists($pdo, 'orders', 'shipping_phone', 'VARCHAR(20)', 'shipping_name');
addColumnIfNotExists($pdo, 'orders', 'shipping_address1', 'TEXT', 'shipping_phone');
addColumnIfNotExists($pdo, 'orders', 'shipping_address2', 'TEXT', 'shipping_address1');
addColumnIfNotExists($pdo, 'orders', 'shipping_landmark', 'VARCHAR(255)', 'shipping_address2');
addColumnIfNotExists($pdo, 'orders', 'shipping_city', 'VARCHAR(100)', 'shipping_landmark');
addColumnIfNotExists($pdo, 'orders', 'shipping_state', 'VARCHAR(100)', 'shipping_city');
addColumnIfNotExists($pdo, 'orders', 'shipping_pincode', 'VARCHAR(10)', 'shipping_state');
addColumnIfNotExists($pdo, 'orders', 'order_notes', 'TEXT', 'shipping_pincode');
addColumnIfNotExists($pdo, 'orders', 'cod_delivery_otp', 'VARCHAR(6)', 'payment_method');
addColumnIfNotExists($pdo, 'orders', 'coupon_code', 'VARCHAR(50)', 'cod_delivery_otp');

// 2.1 Set Order ID to start from 1001 if it's currently low
$pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1001");

// 3. Update customers table
addColumnIfNotExists($pdo, 'customers', 'username', 'VARCHAR(100) UNIQUE', 'email');
addColumnIfNotExists($pdo, 'customers', 'password_hash', 'VARCHAR(255)', 'username');
addColumnIfNotExists($pdo, 'customers', 'auth_token', 'VARCHAR(255)', 'password_hash');
addColumnIfNotExists($pdo, 'customers', 'auth_token_expires', 'DATETIME', 'auth_token');
addColumnIfNotExists($pdo, 'customers', 'otp_code', 'VARCHAR(6)', 'email');
addColumnIfNotExists($pdo, 'customers', 'otp_expires', 'DATETIME', 'otp_code');
addColumnIfNotExists($pdo, 'customers', 'login_attempts', 'INT DEFAULT 0', 'email');
addColumnIfNotExists($pdo, 'customers', 'last_login', 'DATETIME', 'login_attempts');
addColumnIfNotExists($pdo, 'customers', 'role', 'VARCHAR(50) DEFAULT "customer"', 'last_login');
addColumnIfNotExists($pdo, 'customers', 'reset_token', 'VARCHAR(255)', 'role');
addColumnIfNotExists($pdo, 'customers', 'reset_expires', 'DATETIME', 'reset_token');

try { 
    $pdo->exec("ALTER TABLE customers DROP COLUMN password"); 
    echo "Dropped column `password` from `customers`.\n";
} catch(Exception $e) {}

// 4. Create invoices table
$pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    invoice_pdf LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)");
echo "Ensured `invoices` table exists.\n";

// 5. Ensure API tables exist for auth.php
$pdo->exec("CREATE TABLE IF NOT EXISTS api_clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    api_key VARCHAR(255) UNIQUE,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS api_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    endpoint VARCHAR(255),
    method VARCHAR(10),
    status INT,
    response_time INT,
    error TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default API key used by frontend
$stmt = $pdo->prepare("INSERT IGNORE INTO api_clients (name, api_key, status) VALUES (?, ?, ?)");
$stmt->execute(['Frontend App', 'sk_live_zenco_123456789', 1]);
echo "Ensured API Client exists.\n";

// 6. Ensure carts table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    session_id VARCHAR(255),
    items JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE CASCADE
)");

addColumnIfNotExists($pdo, 'carts', 'user_id', 'INT NULL', 'session_id');
try {
    $pdo->exec("ALTER TABLE carts ADD FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE");
} catch(Exception $e) {}

$pdo->exec("CREATE TABLE IF NOT EXISTS magic_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at BIGINT NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Explicitly update expires_at column to BIGINT if it's currently something else
try {
    $pdo->exec("ALTER TABLE magic_links MODIFY COLUMN expires_at BIGINT NOT NULL");
} catch(Exception $e) {}

echo "Ensured `magic_links` table exists and uses BIGINT for expiration.\n";

// 7. Add default settings
$stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_group, setting_key, setting_value, setting_type) VALUES (?, ?, ?, ?)");
$stmt->execute(['general', 'store_gstin', '33AAAAA0000A1Z5', 'string']);
$stmt->execute(['general', 'store_name', 'Goappalam', 'string']);
$stmt->execute(['general', 'store_email', 'goappalam@gmail.com', 'string']);
echo "Settings updated.\n";

// 7.5 Seed Categories
$categories_to_seed = [
    ['name' => 'Combo', 'slug' => 'combo'],
    ['name' => 'Papadam', 'slug' => 'papadam'],
    ['name' => 'Black Pepper', 'slug' => 'black-pepper'],
    ['name' => 'Chili Pepper', 'slug' => 'chili-pepper'],
    ['name' => 'Cumin', 'slug' => 'cumin'],
    ['name' => 'Ring', 'slug' => 'ring'],
    ['name' => 'Sovi', 'slug' => 'sovi']
];

$stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
foreach ($categories_to_seed as $cat) {
    $stmt->execute([$cat['name'], $cat['slug']]);
}
echo "Categories seeded.\n";

// 8. Update products table for price range support and missing columns
try {
    // Change price to VARCHAR to support ranges like "65 - 250"
    $pdo->exec("ALTER TABLE `products` MODIFY COLUMN `price` VARCHAR(255) NOT NULL");
    echo "Modified `price` column in `products` to VARCHAR(255).\n";
} catch(Exception $e) {
    echo "Note: Could not modify `price` column (might already be VARCHAR).\n";
}

addColumnIfNotExists($pdo, 'products', 'price_1kg', 'DECIMAL(10, 2)', 'price');
addColumnIfNotExists($pdo, 'products', 'price_500g', 'DECIMAL(10, 2)', 'price_1kg');
addColumnIfNotExists($pdo, 'products', 'price_250g', 'DECIMAL(10, 2)', 'price_500g');
addColumnIfNotExists($pdo, 'products', 'slug', 'VARCHAR(255) UNIQUE', 'name');
addColumnIfNotExists($pdo, 'products', 'status', 'TINYINT DEFAULT 1', 'price_250g');
addColumnIfNotExists($pdo, 'products', 'images', 'JSON', 'status');
addColumnIfNotExists($pdo, 'products', 'variants', 'JSON', 'images');
addColumnIfNotExists($pdo, 'products', 'tags', 'JSON', 'variants');
addColumnIfNotExists($pdo, 'products', 'description', 'TEXT', 'name');

// 9. Populate Slugs for products that don't have them
$stmt = $pdo->query("SELECT id, name FROM products WHERE slug IS NULL OR slug = ''");
$products_to_update = $stmt->fetchAll();

if (count($products_to_update) > 0) {
    $update_stmt = $pdo->prepare("UPDATE products SET slug = ? WHERE id = ?");
    foreach ($products_to_update as $p) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $p['name'])));
        // Ensure unique slug (simple version)
        $slug = $slug . '-' . $p['id'];
        $update_stmt->execute([$slug, $p['id']]);
    }
    echo "Populated slugs for " . count($products_to_update) . " products.\n";
}

// 10. Auto-link products to categories based on name
$cats = $pdo->query("SELECT id, name FROM categories")->fetchAll();
foreach ($cats as $cat) {
    $stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE (name LIKE ? OR description LIKE ?) AND (category_id IS NULL OR category_id = 0)");
    $searchTerm = '%' . $cat['name'] . '%';
    $stmt->execute([$cat['id'], $searchTerm, $searchTerm]);
}

// Special case for Combo if name doesn't contain "Combo" but "and"
$combo_id = $pdo->query("SELECT id FROM categories WHERE name = 'Combo'")->fetchColumn();
if ($combo_id) {
    $pdo->prepare("UPDATE products SET category_id = ? WHERE name LIKE '% and %' AND (category_id IS NULL OR category_id = 0)")->execute([$combo_id]);
}

echo "Database schema update check completed successfully.\n";

} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}
