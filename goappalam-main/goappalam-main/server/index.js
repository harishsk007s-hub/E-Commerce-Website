import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { Pool } from 'pg';
dotenv.config();
const app = express();
const port = process.env.PORT || 8000;
app.use(cors());
app.use(express.json());
// Database configuration
const pool = new Pool({
    user: process.env.DB_USER || 'postgres',
    host: process.env.DB_HOST || 'localhost',
    database: process.env.DB_NAME || 'goappalam',
    password: process.env.DB_PASSWORD || 'postgres',
    port: parseInt(process.env.DB_PORT || '5432'),
});
// Initialize database
const initDB = async () => {
    try {
        await pool.query(`
      CREATE TABLE IF NOT EXISTS products (
        id SERIAL PRIMARY KEY,
        frontend_id INTEGER UNIQUE,
        name VARCHAR(255) NOT NULL,
        price VARCHAR(100),
        image TEXT,
        category VARCHAR(100),
        description TEXT,
        variations JSONB
      )
    `);
        console.log('Database initialized');
    }
    catch (err) {
        console.error('Error initializing database:', err);
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
// API Endpoints
app.get('/api/branding', (req, res) => {
    res.json(branding);
});
app.get('/api/products', async (req, res) => {
    try {
        const result = await pool.query('SELECT * FROM products ORDER BY id ASC');
        if (result.rows.length === 0) {
            // Fallback to initial data if DB is empty
            return res.json([]);
        }
        res.json(result.rows.map(row => ({
            ...row,
            id: row.frontend_id || row.id
        })));
    }
    catch (err) {
        console.error(err);
        res.status(500).json({ error: 'Database error' });
    }
});
app.get('/api/products/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const result = await pool.query('SELECT * FROM products WHERE frontend_id = $1 OR id = $2', [id, id]);
        if (result.rows.length > 0) {
            const row = result.rows[0];
            res.json({
                ...row,
                id: row.frontend_id || row.id
            });
        }
        else {
            res.status(404).send('Product not found');
        }
    }
    catch (err) {
        console.error(err);
        res.status(500).json({ error: 'Database error' });
    }
});
// Endpoint to sync frontend data to DB
app.post('/api/products/sync', async (req, res) => {
    const { products } = req.body;
    if (!Array.isArray(products)) {
        return res.status(400).json({ error: 'Invalid data format' });
    }
    try {
        for (const product of products) {
            await pool.query(`INSERT INTO products (frontend_id, name, price, image, category, description, variations)
         VALUES ($1, $2, $3, $4, $5, $6, $7)
         ON CONFLICT (frontend_id) DO UPDATE SET
         name = EXCLUDED.name,
         price = EXCLUDED.price,
         image = EXCLUDED.image,
         category = EXCLUDED.category,
         description = EXCLUDED.description,
         variations = EXCLUDED.variations`, [
                product.id,
                product.name,
                product.price,
                product.image,
                product.category,
                product.description,
                JSON.stringify(product.variations || [])
            ]);
        }
        res.json({ message: 'Products synced successfully' });
    }
    catch (err) {
        console.error(err);
        res.status(500).json({ error: 'Sync failed' });
    }
});
app.listen(port, () => {
    console.log(`Server is running on port ${port}`);
});
//# sourceMappingURL=index.js.map