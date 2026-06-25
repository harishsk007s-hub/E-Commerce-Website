-- Database Update for OTP Login, COD OTP, and PDF Invoices

-- 1. Users Table Changes
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS otp_code VARCHAR(6) AFTER status,
ADD COLUMN IF NOT EXISTS otp_expires DATETIME AFTER otp_code,
MODIFY COLUMN login_attempts INT DEFAULT 0,
DROP COLUMN IF EXISTS password;

-- 2. Customers Table Changes
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS otp_code VARCHAR(6) AFTER phone,
ADD COLUMN IF NOT EXISTS otp_expires DATETIME AFTER otp_code,
ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0 AFTER orders_count,
DROP COLUMN IF EXISTS password;

-- 3. Add Indexes
CREATE INDEX IF NOT EXISTS idx_user_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_customer_email ON customers(email);

-- 4. Orders Table Changes
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS cod_delivery_otp VARCHAR(6) AFTER payment_method;

-- 5. Invoices Table
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    invoice_pdf LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 6. Add GSTIN to settings
INSERT IGNORE INTO settings (setting_group, setting_key, setting_value, setting_type) VALUES 
('general', 'store_gstin', '33AAAAA0000A1Z5', 'string');
