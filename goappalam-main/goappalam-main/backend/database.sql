-- Database for eCommerce Backend
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS returns;
DROP TABLE IF EXISTS blog_posts;
DROP TABLE IF EXISTS banners;
DROP TABLE IF EXISTS cms_pages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS shipping_rules;
DROP TABLE IF EXISTS shipping_zones;
DROP TABLE IF EXISTS payment_gateways;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS api_logs;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS magic_links;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS inventory_logs;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS api_clients;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super-admin', 'manager', 'inventory', 'orders', 'support', 'developer-admin', 'customer') NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    status TINYINT DEFAULT 1,
    otp_code VARCHAR(6),
    otp_expires DATETIME,
    login_attempts INT DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add email index for fast lookup
CREATE INDEX idx_user_email ON users(email);

-- User sessions table
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    parent_id INT DEFAULT 0,
    image VARCHAR(255),
    description TEXT,
    sort_order INT DEFAULT 0,
    seo_title VARCHAR(255),
    seo_desc TEXT,
    slug VARCHAR(255) UNIQUE
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(100) UNIQUE,
    category_id INT,
    subcategory_id INT DEFAULT 0,
    brand VARCHAR(100),
    price VARCHAR(255) NOT NULL DEFAULT '0.00',
    price_1kg VARCHAR(50) DEFAULT '0.00',
    price_500g VARCHAR(50) DEFAULT '0.00',
    price_250g VARCHAR(50) DEFAULT '0.00',
    discount_price DECIMAL(10, 2),
    cost_price DECIMAL(10, 2),
    stock INT DEFAULT 0,
    images JSON,
    variants JSON,
    tags JSON,
    status TINYINT DEFAULT 1,
    seo_title VARCHAR(255),
    seo_desc TEXT,
    slug VARCHAR(255) UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    auth_token VARCHAR(255),
    auth_token_expires DATETIME,
    phone VARCHAR(20),
    otp_code VARCHAR(6),
    otp_expires DATETIME,
    addresses JSON,
    orders_count INT DEFAULT 0,
    login_attempts INT DEFAULT 0,
    role VARCHAR(50) DEFAULT 'customer',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add email index for fast lookup
CREATE INDEX idx_customer_email ON customers(email);

-- API Clients table
CREATE TABLE api_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    api_key VARCHAR(100) NOT NULL UNIQUE,
    status TINYINT DEFAULT 1,
    api_base_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    client_id INT,
    status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(10, 2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    items JSON,
    shipping_name VARCHAR(255),
    shipping_phone VARCHAR(20),
    shipping_address1 TEXT,
    shipping_address2 TEXT,
    shipping_landmark VARCHAR(255),
    shipping_city VARCHAR(100),
    shipping_state VARCHAR(100),
    shipping_pincode VARCHAR(10),
    order_notes TEXT,
    shipping_address JSON,
    payment_method VARCHAR(50),
    cod_delivery_otp VARCHAR(6),
    coupon_code VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_verified TINYINT(1) DEFAULT 0,
    tracking_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE SET NULL
) AUTO_INCREMENT = 1;

-- Invoices table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    invoice_pdf LONGBLOB,
    created_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Carts table
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    session_id VARCHAR(255),
    user_id INT NULL,
    items JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Inventory logs table
CREATE TABLE inventory_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    quantity_change INT,
    type ENUM('manual', 'order', 'return') DEFAULT 'manual',
    order_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    gateway VARCHAR(50),
    status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_id VARCHAR(255),
    payment_key VARCHAR(255),
    gateway_response JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Coupons table
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    discount_type ENUM('percentage', 'fixed', 'bogo') DEFAULT 'percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    expiry DATE,
    usage_limit INT DEFAULT 0,
    used_count INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- API logs table
CREATE TABLE api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    endpoint VARCHAR(255),
    method VARCHAR(10),
    status INT,
    response_time INT,
    error TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_group VARCHAR(50) DEFAULT 'general',
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'decimal') DEFAULT 'string',
    UNIQUE KEY (setting_group, setting_key)
);

-- Payment Gateways table
CREATE TABLE payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    enabled TINYINT DEFAULT 0,
    config JSON,
    sort_order INT DEFAULT 0
);

-- Shipping Zones table
CREATE TABLE shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(100),
    state VARCHAR(100),
    city VARCHAR(100),
    status TINYINT DEFAULT 1
);

-- Shipping Rules table
CREATE TABLE shipping_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT,
    min_amount DECIMAL(10, 2) DEFAULT 0,
    max_amount DECIMAL(10, 2) DEFAULT 9999999.99,
    flat_rate DECIMAL(10, 2) DEFAULT 0,
    free_shipping TINYINT DEFAULT 0,
    FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    customer_id INT,
    rating INT,
    comment TEXT,
    status TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- CMS pages table
CREATE TABLE cms_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    slug VARCHAR(255) UNIQUE,
    seo_title VARCHAR(255),
    seo_desc TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Banners table
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    subtitle VARCHAR(255),
    image VARCHAR(255),
    link_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    status TINYINT DEFAULT 1
);

-- Blog table
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    image VARCHAR(255),
    category VARCHAR(100),
    slug VARCHAR(255) UNIQUE,
    seo_title VARCHAR(255),
    seo_desc TEXT,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Returns/Refunds table
CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    items JSON,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'processed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Activity Logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    type VARCHAR(50),
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Magic links table for auth
CREATE TABLE IF NOT EXISTS magic_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at BIGINT NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- SEED DATA --

INSERT INTO users (username, password, role, email, phone, status, is_verified) VALUES 
('developer', '$2y$10$nsZWk6Ts8cMXVzQqZnqmZudZ28YpC/yUXWpvIA2zwmS4mNSNIYoK6', 'developer-admin', 'developer@example.com', '0000000000', 1, 1),
('admin', '$2y$10$nsZWk6Ts8cMXVzQqZnqmZudZ28YpC/yUXWpvIA2zwmS4mNSNIYoK6', 'super-admin', 'admin@example.com', '0000000000', 1, 1),
('customer_user', '$2y$10$nsZWk6Ts8cMXVzQqZnqmZudZ28YpC/yUXWpvIA2zwmS4mNSNIYoK6', 'customer', 'customer_user@test.com', '9876543210', 1, 1);

INSERT INTO settings (setting_group, setting_key, setting_value, setting_type) VALUES 
('general', 'store_name', 'Goappalam', 'string'),
('general', 'store_email', 'admin@goappalam.in', 'string'),
('general', 'store_gstin', '33AAAAA0000A1Z5', 'string'),
('general', 'currency_code', 'INR', 'string'),
('general', 'currency_symbol', '₹', 'string'),
('general', 'timezone', 'Asia/Kolkata', 'string'),
('tax', 'tax_gst_percentage', '5.0', 'decimal'),
('tax', 'tax_inclusive', '0', 'boolean'),
('seo', 'google_analytics_id', 'G-XXXXXXXXXX', 'string'),
('features', 'reviews_enabled', '1', 'boolean'),
('features', 'blog_enabled', '1', 'boolean'),
('features', 'returns_enabled', '1', 'boolean'),
('features', 'abandoned_cart_tracking', '1', 'boolean');

INSERT INTO payment_gateways (code, name, enabled, config) VALUES 
('cod', 'Cash on Delivery', 1, '{"instruction": "Pay on delivery"}'),
('stripe', 'Stripe', 0, '{"api_key": "pk_test_...", "secret_key": "sk_test_...", "mode": "test"}'),
('paypal', 'PayPal', 1, '{"client_id": "AZ...", "secret": "EL...", "mode": "sandbox"}'),
('razorpay', 'Razorpay', 0, '{"key_id": "rzp_test_...", "key_secret": "..."}'),
('upi', 'UPI Pay', 1, '{"upi_id": "zenco@upi", "merchant_name": "Zenco Hub"}');

INSERT INTO shipping_zones (name, country, status) VALUES ('Domestic', 'India', 1), ('International', 'Canada', 1);
INSERT INTO shipping_rules (zone_id, min_amount, flat_rate, free_shipping) VALUES 
(1, 0, 10.00, 0), (1, 500.00, 0, 1),
(2, 0, 35.00, 0);

INSERT INTO categories (id, name, slug, description) VALUES 
(1, 'Black Pepper', 'black-pepper', 'Leaf shaped appalam with authentic black pepper flavor.'),
(2, 'Chilli Pepper', 'chilli-pepper', 'Perfect blend of spicy chilli and aromatic cumin in every bite.'),
(3, 'Combo', 'combo', 'Great combinations of our best appalams.'),
(4, 'Cumin', 'cumin', 'Large leaf-shaped appalam infused with premium cumin seeds.'),
(5, 'Papadam', 'papadam', '100 % Traditional Hand made Appalam, Papad / Papadum.'),
(6, 'Ring', 'ring', 'Fun ring-shaped appalams that kids and adults both love.'),
(7, 'Sovi', 'sovi', 'Traditional crunchy sovi appalam made with pure ingredients.');

INSERT INTO products (id, name, description, sku, category_id, price, stock, slug, status, images, variants) VALUES 
(10, 'Appalam', '100 % Traditional Hand made Appalam, Papad / Papadum prepared from selected quality Urad Dhal from specific region of India. Crispy & Tasty, Hygienically made.', 'APP-001', 5, 65.00, 100, 'appalam', 1, '["/assests/uploads/2021/04/cumin-round-big-1.jpg"]', '[{"name": "Kilogram", "options": ["1/4 Kg", "1/2 Kg", "1 Kilogram"]}]'),
(11, 'Sovi appalam', 'Traditional crunchy sovi appalam made with pure ingredients.', 'SOVI-001', 7, 65.00, 100, 'sovi-appalam', 1, '["/assests/uploads/2020/08/sovi-big-450x450.jpg"]', '[]'),
(12, 'Black Pepper Leaf appalam', 'Leaf shaped appalam with authentic black pepper flavor.', 'BP-001', 1, 65.00, 100, 'black-pepper-leaf-appalam', 1, '["/assests/uploads/2021/04/Product-1-450x450.jpg"]', '[]'),
(13, 'Chilli Pepper and Cumin Papad', 'Perfect blend of spicy chilli and aromatic cumin in every bite.', 'CP-001', 2, 65.00, 100, 'chilli-pepper-cumin-papad', 1, '["/assests/uploads/2021/12/C3-450x450.jpg"]', '[]'),
(14, 'Black Pepper and Cumin Papad', 'A classic combination of black pepper and cumin for a rich taste.', 'BPC-001', 5, 65.00, 100, 'black-pepper-cumin-papad', 1, '["/assests/uploads/2021/12/C2-450x450.jpg"]', '[]'),
(15, 'Jeera Leaf Appalam Big', 'Large leaf-shaped appalam infused with premium cumin seeds.', 'JL-001', 4, 65.00, 100, 'jeera-leaf-appalam-big', 1, '["/assests/uploads/2021/04/cumin-round-big-1-450x450.jpg"]', '[]'),
(16, 'Ring Appalam Big', 'Fun ring-shaped appalams that kids and adults both love.', 'RING-001', 6, 65.00, 100, 'ring-appalam-big', 1, '["/assests/uploads/2020/08/ring-big-450x450.jpg"]', '[]');

INSERT INTO api_clients (name, slug, api_key, status) VALUES ('Main Website', 'main-site', 'sk_live_zenco_123456789', 1), ('Mobile App', 'mobile-app', 'sk_live_zenco_app_0987', 1);

INSERT INTO blog_posts (title, content, category, slug, status) VALUES 
('Top 5 Tech Trends 2026', 'Discover the future of technology...', 'Technology', 'tech-trends-2026', 1),
('Essential Home Decor Tips', 'How to make your living room cozy...', 'Lifestyle', 'home-decor-tips', 1);

INSERT INTO coupons (code, discount_type, discount_value, status, usage_limit) VALUES ('WELCOME20', 'percentage', 20.00, 1, 100), ('FLAT50', 'fixed', 50.00, 1, 50);

INSERT INTO banners (title, subtitle, status, sort_order) VALUES ('Spring Sale', 'Get up to 50% off on fashion', 1, 1), ('New Tech Arrivals', 'The latest gadgets are here', 1, 2);
