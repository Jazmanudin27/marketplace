import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

/**
 * Database connection pool configured with production & local fallback.
 */
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

// Test connection on boot and provide auto-fallback for local root dev
pool.getConnection()
  .then(conn => {
    console.log(`[DB] Successfully connected to MySQL "${process.env.DB_NAME}" as "${process.env.DB_USER}"`);
    conn.release();
  })
  .catch(async (err) => {
    console.warn(`[DB] Primary credentials failed: ${err.message}. Trying local root fallback...`);
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

/**
 * Execute a query using the active connection pool
 * @param {string} sql 
 * @param {Array} params 
 * @returns {Promise<[any, any]>}
 */
export const query = (sql, params) => pool.query(sql, params);

export default {
  query,
  getPool: () => pool,
};
