const mysql = require('mysql2/promise');
const path = require('path');
const dotenv = require('dotenv');

// Load environment variables (priority: local .env > parent .env)
dotenv.config({ path: path.join(__dirname, '../.env') });
dotenv.config({ path: path.join(__dirname, '.env'), override: true });

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  user: process.env.DB_USERNAME && process.env.DB_USERNAME !== 'root' ? process.env.DB_USERNAME : 'marketplace',
  password: process.env.DB_PASSWORD || 'Jazman@271998',
  database: process.env.DB_DATABASE || 'marketplace',
  waitForConnections: true,
  connectionLimit: 15,
  queueLimit: 0,
  decimalNumbers: true,
  dateStrings: true,
  timezone: '+07:00', // Waktu Indonesia Barat
});

function sanitizeParams(params) {
  if (!Array.isArray(params)) return params;
  return params.map(p => {
    if (typeof p === 'number' && isNaN(p)) return null;
    if (Array.isArray(p)) return sanitizeParams(p);
    return p;
  });
}

async function query(sql, params = []) {
  const safeParams = sanitizeParams(params);
  const [rows] = await pool.query(sql, safeParams);
  return rows;
}

module.exports = {
  pool,
  query,
};
