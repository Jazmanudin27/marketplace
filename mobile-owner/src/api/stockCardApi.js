const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

function getAuthHeaders() {
  const token = localStorage.getItem('owner_token') || '';
  const userStr = localStorage.getItem('owner_user');
  let tenantId = '1';
  let userId = '';
  if (userStr) {
    try {
      const u = JSON.parse(userStr);
      if (u.tenant_id) tenantId = String(u.tenant_id);
      if (u.id) userId = String(u.id);
    } catch (_) {}
  }
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
    'X-Tenant-Id': tenantId,
    'X-User-Id': userId,
  };
}

/**
 * 1. Ambil daftar stok gudang jadi + ringkasan metrik (Total Produk, Total Stok Fisik, Stok Menipis, Nilai Aset)
 */
export async function fetchFinishedGoodsStockCard({ search = '', status = 'all', page = 1, limit = 50 } = {}) {
  try {
    const q = new URLSearchParams();
    if (search) q.append('search', search);
    if (status && status !== 'all') q.append('status', status);
    if (page > 1) q.append('page', String(page));
    if (limit) q.append('limit', String(limit));

    const qs = q.toString() ? `?${q.toString()}` : '';
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/stock-card/finished-goods${qs}`, {
      headers: getAuthHeaders()
    });

    if (res.ok) {
      const json = await res.json();
      if (json && json.success) {
        return json;
      }
    }
  } catch (err) {
    console.error('[StockCardApi] fetchFinishedGoodsStockCard error:', err);
  }

  return {
    success: false,
    summary: {
      totalProducts: 0,
      totalStock: 0,
      lowStockCount: 0,
      emptyStockCount: 0,
      readyStockCount: 0,
      totalAssetValue: 0
    },
    data: []
  };
}

/**
 * 2. Ambil detail kartu stok & riwayat mutasi masuk/keluar produk gudang jadi
 */
export async function fetchProductStockMovements(productId) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/stock-card/finished-goods/${productId}/movements`, {
      headers: getAuthHeaders()
    });

    if (res.ok) {
      const json = await res.json();
      if (json && json.success) {
        return json;
      }
    }
  } catch (err) {
    console.error('[StockCardApi] fetchProductStockMovements error:', err);
  }

  return {
    success: false,
    product: null,
    movements: []
  };
}
