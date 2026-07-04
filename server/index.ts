import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import mysql from 'mysql2/promise';

dotenv.config();

const app = express();
// Express always listens on port 8000 inside the container, isolated from Render's public $PORT
const port = 8000;

// Dynamic CORS configuration for maximum robustness
app.use(cors({
  origin: (origin, callback) => {
    // Allow requests with no origin (like mobile apps, curl, or same-origin requests)
    if (!origin) return callback(null, true);
    
    const allowedOrigins = [
      'http://localhost:5173',
      'http://localhost:3000',
      'http://127.0.0.1:5173',
      process.env.FRONTEND_URL || '',
      process.env.BASE_URL || ''
    ].map(url => url.replace(/\/$/, '')).filter(Boolean);
    
    const cleanOrigin = origin.replace(/\/$/, '');
    const isLocalhost = cleanOrigin.includes('localhost') || cleanOrigin.includes('127.0.0.1');

    if (allowedOrigins.indexOf(cleanOrigin) !== -1 || isLocalhost || process.env.NODE_ENV !== 'production') {
      callback(null, true);
    } else {
      // In production behind a proxy, we fallback to allowing the origin to prevent CORS failures
      callback(null, true);
    }
  },
  credentials: true
}));

app.use(express.json());

// Database configuration
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'database',
  port: parseInt(process.env.DB_PORT || '3306'),
};

let pool: any;

// Initialize database
const initDB = async () => {
  try {
    // 1. Attempt direct connection to database (standard for pre-created cloud DBs)
    pool = mysql.createPool(dbConfig);
    const conn = await pool.getConnection();
    console.log('✅ Connected to MySQL Database');
    conn.release();
  } catch (err: any) {
    console.error('❌ Failed to connect to MySQL database:', err.message);
    
    // In production, do NOT attempt database creation. Throw the error immediately.
    if (process.env.APP_ENV === 'production' || process.env.NODE_ENV === 'production') {
      throw err;
    }
    
    // In non-production/development, fallback to database creation (standard for local development)
    console.log('⚠️ Attempting database creation fallback for development...');
    try {
      const connectionConfig = {
        host: dbConfig.host,
        user: dbConfig.user,
        password: dbConfig.password,
        port: dbConfig.port
      };
      const connection = await mysql.createConnection(connectionConfig);
      await connection.query(`CREATE DATABASE IF NOT EXISTS \`${dbConfig.database}\``);
      await connection.end();
      
      pool = mysql.createPool(dbConfig);
      console.log('✅ Created and Connected to MySQL Database (Dev Fallback)');
    } catch (createErr: any) {
      console.error('❌ Failed to create MySQL database in dev fallback:', createErr.message);
      throw createErr;
    }
  }

  // 2. Run migrations and tables checks
  try {
    // Ensure categories table exists (required for products FK)
    await pool.query(`
      CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        parent_id INT DEFAULT 0,
        image VARCHAR(255),
        description TEXT,
        sort_order INT DEFAULT 0,
        seo_title VARCHAR(255),
        seo_desc TEXT,
        slug VARCHAR(255) UNIQUE
      )
    `);

    // Ensure products table exists
    await pool.query(`
      CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        sku VARCHAR(100) UNIQUE,
        category_id INT,
        subcategory_id INT DEFAULT 0,
        brand VARCHAR(100),
        price VARCHAR(100) NOT NULL,
        discount_price DECIMAL(10, 2),
        cost_price DECIMAL(10, 2),
        stock INT DEFAULT 0,
        image TEXT,
        images JSON,
        category VARCHAR(255),
        variants JSON,
        tags JSON,
        status TINYINT DEFAULT 1,
        seo_title VARCHAR(255),
        seo_desc TEXT,
        slug VARCHAR(255) UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
      )
    `);

    // Ensure customers table exists
    await pool.query(`
      CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(20),
        otp_code VARCHAR(6),
        otp_expires DATETIME,
        login_attempts INT DEFAULT 0,
        role VARCHAR(50) DEFAULT 'customer',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Ensure all required columns exist (migration for existing tables)
    const [custCols]: any = await pool.query('SHOW COLUMNS FROM customers');
    const custColNames = custCols.map((col: any) => col.Field);

    if (!custColNames.includes('role')) {
      await pool.query('ALTER TABLE customers ADD COLUMN role VARCHAR(50) DEFAULT "customer"');
    }
    if (!custColNames.includes('otp_code')) {
      await pool.query('ALTER TABLE customers ADD COLUMN otp_code VARCHAR(6) AFTER phone');
    }
    if (!custColNames.includes('otp_expires')) {
      await pool.query('ALTER TABLE customers ADD COLUMN otp_expires DATETIME AFTER otp_code');
    }
    if (!custColNames.includes('login_attempts')) {
      await pool.query('ALTER TABLE customers ADD COLUMN login_attempts INT DEFAULT 0 AFTER otp_expires');
    }
    if (custColNames.includes('password')) {
      await pool.query('ALTER TABLE customers DROP COLUMN password');
    }

    // Add or Update default test users
    const usersToCreate = [
      { name: 'Developer Admin', email: 'developer@example.com', role: 'developer-admin' },
      { name: 'Super Admin', email: 'admin@example.com', role: 'super-admin' },
      { name: 'John Doe', email: 'john@example.com', role: 'customer' }
    ];

    for (const u of usersToCreate) {
      const [existing]: any = await pool.query('SELECT * FROM customers WHERE email = ?', [u.email]);
      if (existing.length === 0) {
        await pool.query(
          'INSERT INTO customers (name, email, role) VALUES (?, ?, ?)',
          [u.name, u.email, u.role]
        );
        console.log(`User created: ${u.email} (${u.role})`);
      } else {
        await pool.query('UPDATE customers SET role = ? WHERE email = ?', [u.role, u.email]);
        console.log(`User updated: ${u.email}`);
      }
    }

  } catch (err) {
    console.warn('⚠️ MySQL migration failed or init error:', err);
  }
};

initDB();

// Branding data
const branding = {
  data: {
    site_title: "Go Appalam - Traditional South Indian Papadum",
    primary_color: "#FFC222",
    primary_font: "Poppins",
    favicon: "wp-content/uploads/2021/08/GO-APPALAM-FINAL-LOGO-01.svg"
  }
};

// Auth Endpoints (Deprecated in favor of PHP Backend OTP Login)
app.post('/api/login', async (req, res) => {
  res.status(501).json({ error: 'Password login is deprecated. Please use OTP login via PHP backend.' });
});

app.post('/api/register', async (req, res) => {
  res.status(501).json({ error: 'Registration is now handled via PHP backend OTP system.' });
});

// API Endpoints
app.get('/', (req, res) => {
  res.send('Goappalam Backend Server is running');
});

app.get('/api/branding', (req, res) => {
  res.json(branding);
});

app.get('/api/products', async (req, res) => {
  try {
    if (!pool) return res.json([]);
    const [rows]: any = await pool.query('SELECT * FROM products ORDER BY id ASC');
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Database error' });
  }
});

app.get('/api/products/:id', async (req, res) => {
  try {
    if (!pool) return res.status(404).send('Product not found');
    const { id } = req.params;
    const [rows]: any = await pool.query('SELECT * FROM products WHERE id = ?', [id]);
    if (rows.length > 0) {
      res.json(rows[0]);
    } else {
      res.status(404).send('Product not found');
    }
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Database error' });
  }
});

// Sync endpoint for MySQL
app.post('/api/products/sync', async (req, res) => {
  const { products } = req.body;
  if (!Array.isArray(products)) {
    return res.status(400).json({ error: 'Invalid data format' });
  }

  try {
    if (!pool) return res.status(500).json({ error: 'Database not connected' });

    for (const product of products) {
      await pool.query(
        `INSERT INTO products (id, name, price, image, category, description)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
         name = VALUES(name),
         price = VALUES(price),
         image = VALUES(image),
         category = VALUES(category),
         description = VALUES(description)`,
        [
          product.id,
          product.name,
          product.price,
          product.image,
          product.category,
          product.description
        ]
      );
    }
    res.json({ message: 'Products synced successfully' });
  } catch (err) {
    console.error('Sync error:', err);
    res.status(500).json({ error: 'Sync failed' });
  }
});

app.listen(port as number, '0.0.0.0', () => {
  console.log(`Server is running on port ${port}`);
});
