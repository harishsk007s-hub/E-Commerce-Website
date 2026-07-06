<?php
/**
 * Robust Database Update Script for PostgreSQL
 */
$config = require __DIR__ . '/config/database.php';

try {
    // Attempt direct connection to database first (standard for cloud hosted DBs)
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Attempt database creation if connection failed (standard for local development)
    try {
        $pdo = new PDO(
            "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
            $config['user'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbname = $config['dbname'];
        
        // Check if database exists
        $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
        $stmt->execute([$dbname]);
        if (!$stmt->fetch()) {
            $pdo->exec("CREATE DATABASE \"$dbname\"");
            echo "Created database $dbname.\n";
        }
        
        // Reconnect
        $pdo = new PDO(
            "pgsql:host={$config['host']};port={$config['port']};dbname={$dbname}",
            $config['user'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $createEx) {
        die("Error connecting to database: " . $createEx->getMessage() . "\n");
    }
}

/**
 * Helper to add a column only if it doesn't already exist in PostgreSQL
 */
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

try {
// 0. Ensure tables exist with PostgreSQL types
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    image VARCHAR(255),
    description TEXT,
    parent_id INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_desc TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE,
    category_id INT,
    price VARCHAR(255) NOT NULL DEFAULT '0.00',
    stock INT DEFAULT 0,
    status SMALLINT DEFAULT 1,
    slug VARCHAR(255) UNIQUE,
    images JSON,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL,
    status SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    customer_id INT,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    items JSON,
    shipping_address JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_group VARCHAR(50),
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string'
)");

// 1. Update users table columns
addColumnIfNotExists($pdo, 'users', 'password', 'VARCHAR(255)');
addColumnIfNotExists($pdo, 'users', 'phone', 'VARCHAR(20)');
addColumnIfNotExists($pdo, 'users', 'otp_code', 'VARCHAR(6)');
addColumnIfNotExists($pdo, 'users', 'otp_expires', 'TIMESTAMP');
addColumnIfNotExists($pdo, 'users', 'login_attempts', 'INT DEFAULT 0');

// Ensure admin user exists with correct password
$admin_pass = password_hash('admin@2026', PASSWORD_DEFAULT);
$developer_pass = password_hash('admin@2026', PASSWORD_DEFAULT);

$default_users = [
    ['developer', $developer_pass, 'developer-admin', 'developer@example.com', '0000000000', 1],
    ['admin', $admin_pass, 'super-admin', 'admin@example.com', '0000000000', 1]
];

foreach ($default_users as $u) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$u[0]]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($u);
        echo "Created user {$u[0]}.\n";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
        $stmt->execute([$u[2], $u[0]]);
    }
}

// 2. Update orders table columns
addColumnIfNotExists($pdo, 'orders', 'shipping_name', 'VARCHAR(255)');
addColumnIfNotExists($pdo, 'orders', 'shipping_phone', 'VARCHAR(20)');
addColumnIfNotExists($pdo, 'orders', 'shipping_address1', 'TEXT');
addColumnIfNotExists($pdo, 'orders', 'shipping_address2', 'TEXT');
addColumnIfNotExists($pdo, 'orders', 'shipping_landmark', 'VARCHAR(255)');
addColumnIfNotExists($pdo, 'orders', 'shipping_city', 'VARCHAR(100)');
addColumnIfNotExists($pdo, 'orders', 'shipping_state', 'VARCHAR(100)');
addColumnIfNotExists($pdo, 'orders', 'shipping_pincode', 'VARCHAR(10)');
addColumnIfNotExists($pdo, 'orders', 'order_notes', 'TEXT');
addColumnIfNotExists($pdo, 'orders', 'cod_delivery_otp', 'VARCHAR(6)');
addColumnIfNotExists($pdo, 'orders', 'coupon_code', 'VARCHAR(50)');

// 2.1 Set Order ID Sequence to start from 1001 if empty
try {
    $pdo->exec("ALTER SEQUENCE orders_id_seq RESTART WITH 1001");
} catch (Exception $e) {}

// 3. Update customers table columns
addColumnIfNotExists($pdo, 'customers', 'username', 'VARCHAR(100) UNIQUE');
addColumnIfNotExists($pdo, 'customers', 'password_hash', 'VARCHAR(255)');
addColumnIfNotExists($pdo, 'customers', 'auth_token', 'VARCHAR(255)');
addColumnIfNotExists($pdo, 'customers', 'auth_token_expires', 'TIMESTAMP');
addColumnIfNotExists($pdo, 'customers', 'otp_code', 'VARCHAR(6)');
addColumnIfNotExists($pdo, 'customers', 'otp_expires', 'TIMESTAMP');
addColumnIfNotExists($pdo, 'customers', 'login_attempts', 'INT DEFAULT 0');
addColumnIfNotExists($pdo, 'customers', 'last_login', 'TIMESTAMP');
addColumnIfNotExists($pdo, 'customers', 'role', 'VARCHAR(50) DEFAULT \'customer\'');
addColumnIfNotExists($pdo, 'customers', 'reset_token', 'VARCHAR(255)');
addColumnIfNotExists($pdo, 'customers', 'reset_expires', 'TIMESTAMP');

try { 
    $pdo->exec("ALTER TABLE customers DROP COLUMN password"); 
    echo "Dropped column `password` from `customers`.\n";
} catch(Exception $e) {}

// 4. Create invoices table
$pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
    id SERIAL PRIMARY KEY,
    order_id INT UNIQUE,
    invoice_pdf BYTEA,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)");
echo "Ensured `invoices` table exists.\n";

// 5. Ensure API tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS api_clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    api_key VARCHAR(255) UNIQUE,
    status SMALLINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "Ensured `api_clients` table exists.\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS api_logs (
    id SERIAL PRIMARY KEY,
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
$stmt = $pdo->prepare("SELECT 1 FROM api_clients WHERE api_key = ?");
$stmt->execute(['sk_live_zenco_123456789']);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO api_clients (name, api_key, status) VALUES (?, ?, ?)");
    $stmt->execute(['Frontend App', 'sk_live_zenco_123456789', 1]);
    echo "Created API Client.\n";
}

// 6. Ensure carts table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS carts (
    id SERIAL PRIMARY KEY,
    client_id INT,
    session_id VARCHAR(255),
    items JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE CASCADE
)");

addColumnIfNotExists($pdo, 'carts', 'user_id', 'INT NULL');
try {
    $pdo->exec("ALTER TABLE carts ADD FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE");
} catch(Exception $e) {}

$pdo->exec("CREATE TABLE IF NOT EXISTS magic_links (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at BIGINT NOT NULL,
    used SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

echo "Ensured `magic_links` table exists.\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS payment_gateways (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    enabled SMALLINT DEFAULT 0,
    config JSON,
    sort_order INT DEFAULT 0
)");

echo "Ensured `payment_gateways` table exists.\n";

// 7. Add default settings using check-then-insert
$default_settings = [
    ['general', 'store_gstin', '33AAAAA0000A1Z5', 'string'],
    ['general', 'store_name', 'Goappalam', 'string'],
    ['general', 'store_email', 'goappalam@gmail.com', 'string']
];
foreach ($default_settings as $sett) {
    $stmt = $pdo->prepare("SELECT 1 FROM settings WHERE setting_key = ?");
    $stmt->execute([$sett[1]]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value, setting_type) VALUES (?, ?, ?, ?)");
        $stmt->execute($sett);
    }
}
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

foreach ($categories_to_seed as $cat) {
    $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE slug = ?");
    $stmt->execute([$cat['slug']]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$cat['name'], $cat['slug']]);
    }
}
echo "Categories seeded.\n";

// 8. Update products table columns
addColumnIfNotExists($pdo, 'products', 'price_1kg', 'DECIMAL(10, 2)');
addColumnIfNotExists($pdo, 'products', 'price_500g', 'DECIMAL(10, 2)');
addColumnIfNotExists($pdo, 'products', 'price_250g', 'DECIMAL(10, 2)');
addColumnIfNotExists($pdo, 'products', 'slug', 'VARCHAR(255) UNIQUE');
addColumnIfNotExists($pdo, 'products', 'status', 'SMALLINT DEFAULT 1');
addColumnIfNotExists($pdo, 'products', 'images', 'JSON');
addColumnIfNotExists($pdo, 'products', 'variants', 'JSON');
addColumnIfNotExists($pdo, 'products', 'tags', 'JSON');
addColumnIfNotExists($pdo, 'products', 'description', 'TEXT');

// 9. Populate Slugs for products that don't have them
$stmt = $pdo->query("SELECT id, name FROM products WHERE slug IS NULL OR slug = ''");
$products_to_update = $stmt->fetchAll();

if (count($products_to_update) > 0) {
    $update_stmt = $pdo->prepare("UPDATE products SET slug = ? WHERE id = ?");
    foreach ($products_to_update as $p) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $p['name'])));
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

// 11. Seed Products using check-then-insert
$products_to_seed = [
    [
        'id' => 10,
        'name' => 'Appalam',
        'description' => '100 % Traditional Hand made Appalam, Papad / Papadum prepared from selected quality Urad Dhal from specific region of India. Crispy & Tasty, Hygienically made.',
        'sku' => 'APP-001',
        'category_id' => 5, // Papadam
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'appalam',
        'images' => '["/assests/uploads/2021/04/cumin-round-big-1.jpg"]',
        'variants' => '[{"name": "Kilogram", "options": ["1/4 Kg", "1/2 Kg", "1 Kilogram"]}]'
    ],
    [
        'id' => 11,
        'name' => 'Sovi appalam',
        'description' => 'Traditional crunchy sovi appalam made with pure ingredients.',
        'sku' => 'SOVI-001',
        'category_id' => 7, // Sovi
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'sovi-appalam',
        'images' => '["/assests/uploads/2020/08/sovi-big-450x450.jpg"]',
        'variants' => '[]'
    ],
    [
        'id' => 12,
        'name' => 'Black Pepper Leaf appalam',
        'description' => 'Leaf shaped appalam with authentic black pepper flavor.',
        'sku' => 'BP-001',
        'category_id' => 1, // Black Pepper
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'black-pepper-leaf-appalam',
        'images' => '["/assests/uploads/2021/04/Product-1-450x450.jpg"]',
        'variants' => '[]'
    ],
    [
        'id' => 13,
        'name' => 'Chilli Pepper and Cumin Papad',
        'description' => 'Perfect blend of spicy chilli and aromatic cumin in every bite.',
        'sku' => 'CP-001',
        'category_id' => 2, // Chilli Pepper
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'chilli-pepper-cumin-papad',
        'images' => '["/assests/uploads/2021/12/C3-450x450.jpg"]',
        'variants' => '[]'
    ],
    [
        'id' => 14,
        'name' => 'Black Pepper and Cumin Papad',
        'description' => 'A classic combination of black pepper and cumin for a rich taste.',
        'sku' => 'BPC-001',
        'category_id' => 5, // Papadam
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'black-pepper-cumin-papad',
        'images' => '["/assests/uploads/2021/12/C2-450x450.jpg"]',
        'variants' => '[]'
    ],
    [
        'id' => 15,
        'name' => 'Jeera Leaf Appalam Big',
        'description' => 'Large leaf-shaped appalam infused with premium cumin seeds.',
        'sku' => 'JL-001',
        'category_id' => 4, // Cumin
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'jeera-leaf-appalam-big',
        'images' => '["/assests/uploads/2021/04/cumin-round-big-1-450x450.jpg"]',
        'variants' => '[]'
    ],
    [
        'id' => 16,
        'name' => 'Ring Appalam Big',
        'description' => 'Fun ring-shaped appalams that kids and adults both love.',
        'sku' => 'RING-001',
        'category_id' => 6, // Ring
        'price' => '65.00',
        'stock' => 100,
        'slug' => 'ring-appalam-big',
        'images' => '["/assests/uploads/2020/08/ring-big-450x450.jpg"]',
        'variants' => '[]'
    ]
];

foreach ($products_to_seed as $p) {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
    $stmt->execute([$p['sku']]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO products (id, name, description, sku, category_id, price, stock, slug, images, variants) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?::json, ?::json)");
        $stmt->execute([
            $p['id'],
            $p['name'],
            $p['description'],
            $p['sku'],
            $p['category_id'],
            $p['price'],
            $p['stock'],
            $p['slug'],
            $p['images'],
            $p['variants']
        ]);
        echo "Seeded product: {$p['name']}.\n";
    }
}

// Reset products sequence after seeding
try {
    $pdo->exec("SELECT setval('products_id_seq', (SELECT MAX(id) FROM products))");
} catch (Exception $e) {}

// 12. Seed Payment Gateways using check-then-insert
$gateways_to_seed = [
    ['cod', 'Cash on Delivery', 1, '{"instruction": "Pay on delivery"}'],
    ['stripe', 'Stripe', 0, '{"api_key": "pk_test_...", "secret_key": "sk_test_...", "mode": "test"}'],
    ['paypal', 'PayPal', 1, '{"client_id": "AZ...", "secret": "EL...", "mode": "sandbox"}'],
    ['razorpay', 'Razorpay', 0, '{"key_id": "rzp_test_...", "key_secret": "..."}'],
    ['upi', 'UPI Pay', 1, '{"upi_id": "zenco@upi", "merchant_name": "Zenco Hub"}']
];
foreach ($gateways_to_seed as $g) {
    $stmt = $pdo->prepare("SELECT 1 FROM payment_gateways WHERE code = ?");
    $stmt->execute([$g[0]]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO payment_gateways (code, name, enabled, config) VALUES (?, ?, ?, ?::json)");
        $stmt->execute($g);
        echo "Seeded gateway: {$g[1]}.\n";
    }
}

echo "Database schema update check completed successfully.\n";

} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}
