import axios from 'axios';

const API_BASE_URL = typeof window !== 'undefined' && window.location.pathname.startsWith('/v2')
  ? '/v2/api'
  : '/api';


const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export const fetchDashboardData = async () => {
  try {
    const res = await api.get('/dashboard');
    return res.data.data;
  } catch (err) {
    console.warn('API Error, returning mock dashboard fallback:', err);
    return {
      totalStoresCount: 8,
      totalOmset: 73493669,
      totalOrdersCount: 8618,
      pendingOrdersCount: 17,
      shopeeOmset: 45200000,
      shopeeOrdersCount: 5200,
      tiktokOmset: 22100000,
      tiktokOrdersCount: 2800,
      lazadaOmset: 6193669,
      lazadaOrdersCount: 618,
      recentOrders: [
        { id: 1, order_number: '240928SP1234', channel: 'shopee', created_at: '2026-09-28 14:30', buyer_name: 'Budi Santoso', store: { name: 'Toko Seragam Official' }, total_amount: 185000, status: 'Diproses' },
        { id: 2, order_number: '585293879388', channel: 'tiktok', created_at: '2026-09-28 14:15', buyer_name: 'Siti Rahma', store: { name: 'Ruang Seragam' }, total_amount: 240000, status: 'Selesai' },
        { id: 3, order_number: '240928LZ9912', channel: 'lazada', created_at: '2026-09-28 13:50', buyer_name: 'Ahmad Fauzi', store: { name: 'Artanita Shop' }, total_amount: 95000, status: 'Selesai' },
      ],
    };
  }
};

export const fetchProdukData = async (params = {}) => {
  try {
    const res = await api.get('/produk', { params });
    return res.data.data;
  } catch (err) {
    console.warn('API Error, returning mock produk fallback:', err);
    return {
      products: {
        total: 35,
        current_page: 1,
        data: [
          { id: 1, name: 'Seragam Sekolah SD Lengan Pendek', sku: 'SD-LPD-01', category: { name: 'Seragam SD' }, brand: { name: 'Artanita' }, cost_price: 35000, price: 65000, stock: 120, unit: 'pcs', is_active: true },
          { id: 2, name: 'Celana Panjang SMP Biru', sku: 'SMP-CLN-02', category: { name: 'Seragam SMP' }, brand: { name: 'Artanita' }, cost_price: 45000, price: 85000, stock: 4, unit: 'pcs', is_active: true },
          { id: 3, name: 'Rok Span SMA Abu-Abu', sku: 'SMA-ROK-03', category: { name: 'Seragam SMA' }, brand: { name: 'Ruang Seragam' }, cost_price: 50000, price: 95000, stock: 85, unit: 'pcs', is_active: true },
        ],
      },
    };
  }
};

export default api;
