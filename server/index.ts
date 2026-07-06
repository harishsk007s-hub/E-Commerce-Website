import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import pg from 'pg';

const { Pool } = pg;

dotenv.config();

const app = express();
const port = 8000;

app.use(cors({
  origin: (origin, callback) => {
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
      callback(null, true);
    }
  },
  credentials: true
}));

app.use(express.json());

// Database configuration parsing DATABASE_URL for Render production or fallback
const connectionString = process.env.DATABASE_URL;

let pool: pg.Pool;

if (connectionString) {
  pool = new Pool({
    connectionString,
    ssl: { rejectUnauthorized: false }
  });
} else {
  pool = new Pool({
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USER || 'postgres',
    password: process.env.DB_PASS || process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'goappala-demo',
    port: parseInt(process.env.DB_PORT || '5432'),
  });
}

// Initialize database
const initDB = async () => {
  try {
    const conn = await pool.connect();
    console.log('✅ Connected to PostgreSQL Database');
    conn.release();
  } catch (err: any) {
    console.error('❌ Failed to connect to PostgreSQL database:', err.message);
    
    if (process.env.APP_ENV === 'production' || process.env.NODE_ENV === 'production') {
      throw err;
    }
    
    console.log('⚠️ Attempting database creation fallback for development...');
    try {
      const pgPool = new Pool({
        host: process.env.DB_HOST || '127.0.0.1',
        user: process.env.DB_USER || 'postgres',
        password: process.env.DB_PASS || process.env.DB_PASSWORD || '',
        port: parseInt(process.env.DB_PORT || '5432'),
        database: 'postgres'
      });
      const dbName = process.env.DB_NAME || 'goappala-demo';
      const checkRes = await pgPool.query("SELECT 1 FROM pg_database WHERE datname = $1", [dbName]);
      if (checkRes.rowCount === 0) {
        await pgPool.query(`CREATE DATABASE "${dbName}"`);
        console.log(`✅ Created Database: ${dbName}`);
      }
      await pgPool.end();
      
      console.log('✅ Connected to PostgreSQL Database (Dev Fallback)');
    } catch (createErr: any) {
      console.error('❌ Failed to create PostgreSQL database in dev fallback:', createErr.message);
      throw createErr;
    }
  }

  // Run migrations and tables checks
  try {
    // Ensure categories table exists
    await pool.query(`
      CREATE TABLE IF NOT EXISTS categories (
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        sku VARCHAR(100) UNIQUE,
        category_id INT,
        subcategory_id INT DEFAULT 0,
        brand VARCHAR(100),
        price VARCHAR(100) NOT NULL DEFAULT '0.00',
        discount_price DECIMAL(10, 2),
        cost_price DECIMAL(10, 2),
        stock INT DEFAULT 0,
        image TEXT,
        images JSON,
        category VARCHAR(255),
        variants JSON,
        tags JSON,
        status SMALLINT DEFAULT 1,
        seo_title VARCHAR(255),
        seo_desc TEXT,
        slug VARCHAR(255) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
      )
    `);

    // Ensure customers table exists
    await pool.query(`
      CREATE TABLE IF NOT EXISTS customers (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(20),
        otp_code VARCHAR(6),
        otp_expires TIMESTAMP,
        login_attempts INT DEFAULT 0,
        role VARCHAR(50) DEFAULT 'customer',
        last_login TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Ensure all required columns exist (migration for existing tables)
    const colsRes = await pool.query("SELECT column_name FROM information_schema.columns WHERE table_name = 'customers'");
    const custColNames = colsRes.rows.map((row: any) => row.column_name);

    if (!custColNames.includes('role')) {
      await pool.query("ALTER TABLE customers ADD COLUMN role VARCHAR(50) DEFAULT 'customer'");
    }
    if (!custColNames.includes('otp_code')) {
      await pool.query("ALTER TABLE customers ADD COLUMN otp_code VARCHAR(6)");
    }
    if (!custColNames.includes('otp_expires')) {
      await pool.query("ALTER TABLE customers ADD COLUMN otp_expires TIMESTAMP");
    }
    if (!custColNames.includes('login_attempts')) {
      await pool.query("ALTER TABLE customers ADD COLUMN login_attempts INT DEFAULT 0");
    }
    if (custColNames.includes('password')) {
      await pool.query("ALTER TABLE customers DROP COLUMN password");
    }

    // Add or Update default test users
    const usersToCreate = [
      { name: 'Developer Admin', email: 'developer@example.com', role: 'developer-admin' },
      { name: 'Super Admin', email: 'admin@example.com', role: 'super-admin' },
      { name: 'John Doe', email: 'john@example.com', role: 'customer' }
    ];

    for (const u of usersToCreate) {
      const existing = await pool.query('SELECT * FROM customers WHERE email = $1', [u.email]);
      if (existing.rowCount === 0) {
        await pool.query(
          'INSERT INTO customers (name, email, role) VALUES ($1, $2, $3)',
          [u.name, u.email, u.role]
        );
        console.log(`User created: ${u.email} (${u.role})`);
      } else {
        await pool.query('UPDATE customers SET role = $1 WHERE email = $2', [u.role, u.email]);
        console.log(`User updated: ${u.email}`);
      }
    }

  } catch (err) {
    console.warn('⚠️ PostgreSQL migration failed or init error:', err);
  }
};

initDB();

const branding = {
  data: {
    site_title: "Go Appalam - Traditional South Indian Papadum",
    primary_color: "#FFC222",
    primary_font: "Poppins",
    favicon: "wp-content/uploads/2021/08/GO-APPALAM-FINAL-LOGO-01.svg"
  }
};

app.post('/api/login', async (req, res) => {
  res.status(501).json({ error: 'Password login is deprecated. Please use OTP login via PHP backend.' });
});

app.post('/api/register', async (req, res) => {
  res.status(501).json({ error: 'Registration is now handled via PHP backend OTP system.' });
});

app.get('/', (req, res) => {
  res.send('Goappalam Backend Server is running');
});

app.get('/api/branding', (req, res) => {
  res.json(branding);
});

app.get('/api/products', async (req, res) => {
  try {
    if (!pool) return res.json([]);
    const result = await pool.query('SELECT * FROM products ORDER BY id ASC');
    res.json(result.rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Database error' });
  }
});

app.get('/api/products/:id', async (req, res) => {
  try {
    if (!pool) return res.status(404).send('Product not found');
    const { id } = req.params;
    const numericId = parseInt(id);
    if (isNaN(numericId)) {
      return res.status(400).send('Invalid product ID');
    }
    const result = await pool.query('SELECT * FROM products WHERE id = $1', [numericId]);
    if (result.rows.length > 0) {
      res.json(result.rows[0]);
    } else {
      res.status(404).send('Product not found');
    }
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Database error' });
  }
});

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
         VALUES ($1, $2, $3, $4, $5, $6)
         ON CONFLICT (id) DO UPDATE SET
         name = EXCLUDED.name,
         price = EXCLUDED.price,
         image = EXCLUDED.image,
         category = EXCLUDED.category,
         description = EXCLUDED.description`,
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
