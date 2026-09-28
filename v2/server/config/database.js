import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Explicitly load .env from v2/ or parent directory
dotenv.config({ path: path.resolve(__dirname, '../../.env') });
dotenv.config({ path: path.resolve(__dirname, '../../../.env') });

const DB_HOST = process.env.DB_HOST || '127.0.0.1';
const DB_PORT = parseInt(process.env.DB_PORT || '3306', 10);
const DB_USER = process.env.DB_USER || 'marketplace';
const DB_PASSWORD = process.env.DB_PASSWORD || 'Jazman@271998';
const DB_NAME = process.env.DB_NAME || 'marketplace';

/**
 * Database connection pool configured with production & local fallback.
 */
let pool = mysql.createPool({
  host: DB_HOST,
  port: DB_PORT,
  user: DB_USER,
  password: DB_PASSWORD,
  database: DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

// Test connection on boot and provide auto-fallback for local root dev
pool.getConnection()
  .then(conn => {
    console.log(`[DB] Successfully connected to MySQL "${DB_NAME}" as "${DB_USER}"`);
    conn.release();
  })
  .catch(async (err) => {
    console.warn(`[DB] Primary credentials failed: ${err.message}. Trying local root fallback...`);
    try {
      const localPool = mysql.createPool({
        host: DB_HOST,
        port: DB_PORT,
        user: 'root',
        password: '',
        database: DB_NAME,
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
