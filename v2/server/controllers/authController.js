import db from '../config/database.js';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';

const JWT_SECRET = process.env.JWT_SECRET || 'aspartech-v2-super-secret-key-2026';

/**
 * POST /api/login
 * Authenticates user from database `users` table
 */
export const login = async (req, res) => {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({
        success: false,
        message: 'Username/Email dan Password wajib diisi!'
      });
    }

    // Find user by email or name
    const [users] = await db.query(`
      SELECT u.id, u.tenant_id, u.name, u.email, u.password, u.role, t.name as tenant_name
      FROM users u
      LEFT JOIN tenants t ON u.tenant_id = t.id
      WHERE u.email = ? OR u.name = ?
      LIMIT 1
    `, [username.trim(), username.trim()]);

    if (!users || users.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Akun tidak ditemukan. Periksa kembali Username/Email Anda.'
      });
    }

    const user = users[0];

    // Verify bcrypt hash (replace PHP $2y$ prefix with $2a$ for bcryptjs compatibility)
    const normalizedHash = user.password.replace(/^\$2y\$/, '$2a$');
    const isMatch = await bcrypt.compare(password, normalizedHash);

    if (!isMatch) {
      return res.status(401).json({
        success: false,
        message: 'Password yang Anda masukkan salah!'
      });
    }

    // Generate JWT Token
    const token = jwt.sign(
      {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        tenant_id: user.tenant_id,
        tenant_name: user.tenant_name || 'ASPARTECH'
      },
      JWT_SECRET,
      { expiresIn: '7d' }
    );

    return res.json({
      success: true,
      message: 'Login berhasil!',
      data: {
        token,
        user: {
          id: user.id,
          name: user.name,
          email: user.email,
          role: user.role,
          tenant_id: user.tenant_id,
          tenant_name: user.tenant_name || 'ASPARTECH'
        }
      }
    });
  } catch (err) {
    console.error('[AuthController] Login error:', err);
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server: ' + err.message
    });
  }
};

/**
 * GET /api/me
 * Returns current authenticated user from JWT Token
 */
export const getMe = async (req, res) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return res.status(401).json({ success: false, message: 'Unauthorized' });
    }

    const token = authHeader.split(' ')[1];
    const decoded = jwt.verify(token, JWT_SECRET);

    return res.json({
      success: true,
      data: { user: decoded }
    });
  } catch (err) {
    return res.status(401).json({ success: false, message: 'Token tidak valid atau kedaluwarsa' });
  }
};
