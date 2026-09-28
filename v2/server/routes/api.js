import { Router } from 'express';
import { getDashboardStats } from '../controllers/dashboardController.js';
import { getProdukList } from '../controllers/produkController.js';

const router = Router();

// Dashboard routes
router.get('/dashboard', getDashboardStats);

// Master Produk routes
router.get('/produk', getProdukList);

export default router;
