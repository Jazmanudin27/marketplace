import { Router } from 'express';
import { login, getMe } from '../controllers/authController.js';
import { getDashboardStats } from '../controllers/dashboardController.js';
import { getProdukList } from '../controllers/produkController.js';

const router = Router();

// Authentication routes
router.post('/login', login);
router.get('/me', getMe);

// Dashboard routes
router.get('/dashboard', getDashboardStats);

// Master Produk routes
router.get('/produk', getProdukList);

export default router;
