import express from 'express';
import cors from 'cors';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 5008;

app.use(cors());
app.use(express.json());

// MySQL Connection Pool (Configured with production credentials & local fallback)
let pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  user: process.env.DB_USER || 'marketplace',
  password: process.env.DB_PASSWORD || 'Jazman@271998',
  database: process.env.DB_NAME || 'marketplace',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

// Test DB Connection with automatic local fallback
pool.getConnection()
  .then(conn => {
    console.log(`[DB] Successfully connected to MySQL database "${process.env.DB_NAME}" as "${process.env.DB_USER}"`);
    conn.release();
  })
  .catch(async (err) => {
    console.warn(`[DB] Primary connection failed: ${err.message}. Trying local dev credentials (root)...`);
    try {
      const localPool = mysql.createPool({
        host: process.env.DB_HOST || '127.0.0.1',
        port: parseInt(process.env.DB_PORT || '3306', 10),
        user: 'root',
        password: '',
        database: process.env.DB_NAME || 'marketplace',
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0,
      });
      const conn = await localPool.getConnection();
      console.log(`[DB] Successfully connected to MySQL database using local root!`);
      conn.release();
      pool = localPool;
    } catch (fallbackErr) {
      console.error('[DB] Fallback connection error:', fallbackErr.message);
    }
  });


// API: Dashboard Stats
app.get('/api/dashboard', async (req, res) => {
  try {
    const [storesRow] = await pool.query('SELECT COUNT(*) as total FROM stores');
    const totalStoresCount = storesRow[0]?.total || 0;

    const [omsetRow] = await pool.query(`
      SELECT COALESCE(SUM(net_amount), 0) as total 
      FROM orders 
      WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) AND order_status != 'CANCELLED'
    `);
    const totalOmset = parseFloat(omsetRow[0]?.total || 0);

    const [ordersRow] = await pool.query('SELECT COUNT(*) as total FROM orders');
    const totalOrdersCount = ordersRow[0]?.total || 0;

    const [pendingRow] = await pool.query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'READY_TO_SHIP'");
    const pendingOrdersCount = pendingRow[0]?.total || 0;

    // Shopee
    const [shopeeRow] = await pool.query(`
      SELECT COALESCE(SUM(o.net_amount), 0) as total, COUNT(o.id) as orders_count 
      FROM orders o 
      LEFT JOIN stores s ON o.store_id = s.id 
      LEFT JOIN channels c ON s.channel_id = c.id 
      WHERE LOWER(c.name) LIKE '%shopee%'
    `);
    const shopeeOmset = parseFloat(shopeeRow[0]?.total || 0);
    const shopeeOrdersCount = shopeeRow[0]?.orders_count || 0;

    // TikTok
    const [tiktokRow] = await pool.query(`
      SELECT COALESCE(SUM(o.net_amount), 0) as total, COUNT(o.id) as orders_count 
      FROM orders o 
      LEFT JOIN stores s ON o.store_id = s.id 
      LEFT JOIN channels c ON s.channel_id = c.id 
      WHERE LOWER(c.name) LIKE '%tiktok%'
    `);
    const tiktokOmset = parseFloat(tiktokRow[0]?.total || 0);
    const tiktokOrdersCount = tiktokRow[0]?.orders_count || 0;

    // Lazada
    const [lazadaRow] = await pool.query(`
      SELECT COALESCE(SUM(o.net_amount), 0) as total, COUNT(o.id) as orders_count 
      FROM orders o 
      LEFT JOIN stores s ON o.store_id = s.id 
      LEFT JOIN channels c ON s.channel_id = c.id 
      WHERE LOWER(c.name) LIKE '%lazada%'
    `);
    const lazadaOmset = parseFloat(lazadaRow[0]?.total || 0);
    const lazadaOrdersCount = lazadaRow[0]?.orders_count || 0;

    // Recent 10 Orders
    const [recentOrders] = await pool.query(`
      SELECT o.id, COALESCE(o.order_marketplace_id, o.invoice_number, CONCAT('ORD-', o.id)) as order_number, 
             COALESCE(c.name, 'Marketplace') as channel, o.order_date, o.created_at, 
             o.buyer_name, o.total_amount, o.order_status as status, s.store_name
      FROM orders o
      LEFT JOIN stores s ON o.store_id = s.id
      LEFT JOIN channels c ON s.channel_id = c.id
      ORDER BY o.created_at DESC
      LIMIT 10
    `);

    res.json({
      success: true,
      data: {
        totalStoresCount,
        totalOmset,
        totalOrdersCount,
        pendingOrdersCount,
        shopeeOmset,
        shopeeOrdersCount,
        tiktokOmset,
        tiktokOrdersCount,
        lazadaOmset,
        lazadaOrdersCount,
        recentOrders: recentOrders.map(o => ({
          ...o,
          store: { name: o.store_name || '-' }
        }))
      }
    });

  } catch (err) {
    console.error('Error fetching dashboard stats:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

// API: Master Produk
app.get('/api/produk', async (req, res) => {
  try {
    const search = req.query.search ? `%${req.query.search}%` : null;
    const page = parseInt(req.query.page || '1', 10);
    const limit = parseInt(req.query.limit || '15', 10);
    const offset = (page - 1) * limit;

    let query = `
      SELECT p.id, p.name, p.sku, p.cost_price, p.price, p.stock, p.unit, p.is_active, p.image_url,
             p.ukuran, p.warna,
             c.name as category_name, b.name as brand_name
      FROM master_products p
      LEFT JOIN categories c ON p.category_id = c.id
      LEFT JOIN brands b ON p.brand_id = b.id
    `;
    let countQuery = `SELECT COUNT(*) as total FROM master_products p`;
    const params = [];
    const countParams = [];

    if (search) {
      const where = ` WHERE p.name LIKE ? OR p.sku LIKE ?`;
      query += where;
      countQuery += where;
      params.push(search, search);
      countParams.push(search, search);
    }


    query += ` ORDER BY p.name ASC LIMIT ? OFFSET ?`;
    params.push(limit, offset);

    const [rows] = await pool.query(query, params);
    const [countRows] = await pool.query(countQuery, countParams);
    const total = countRows[0]?.total || 0;

    res.json({
      success: true,
      data: {
        products: {
          total,
          current_page: page,
          per_page: limit,
          data: rows.map(r => ({
            id: r.id,
            name: r.name,
            sku: r.sku,
            image_url: r.image_url,
            ukuran: r.ukuran,
            warna: r.warna,
            cost_price: r.cost_price,
            price: r.price,
            stock: r.stock,
            unit: r.unit,
            is_active: r.is_active === 1 || r.is_active === true,
            category: { name: r.category_name || '-' },
            brand: { name: r.brand_name || '-' }
          }))
        }
      }
    });
  } catch (err) {
    console.error('Error fetching produk:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

// Serve Vite Production Static Files
app.use(express.static(path.join(__dirname, 'dist')));

// SPA Fallback for client-side routing
app.use((req, res) => {
  res.sendFile(path.join(__dirname, 'dist', 'index.html'));
});


app.listen(PORT, () => {
  console.log(`=========================================`);
  console.log(`🚀 ASPARTECH V2 React & Node API Server`);
  console.log(`📡 Server running on http://127.0.0.1:${PORT}`);
  console.log(`=========================================`);
});
