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
 * 1. Ambil resources asli database (Master products & suppliers untuk form input)
 */
export async function fetchConsignmentResources(search = '') {
  try {
    const qs = search ? `?search=${encodeURIComponent(search)}` : '';
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/meta/resources${qs}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success && json.data) {
        return json.data;
      }
    }
  } catch (err) {
    console.error('[SupplierApi] Meta resources fetch error:', err);
  }

  return {
    products: [],
    suppliers: [],
    autoRefNumber: ''
  };
}

/**
 * 2. Ambil daftar transaksi riil penerimaan barang konsinyasi dari database
 */
export async function fetchSupplierConsignments(params = {}) {
  try {
    const q = new URLSearchParams();
    if (params.search) q.append('search', params.search);
    if (params.supplier_id) q.append('supplier_id', params.supplier_id);
    if (params.status) q.append('status', params.status);
    if (params.date_from) q.append('date_from', params.date_from);
    if (params.date_to) q.append('date_to', params.date_to);

    const qs = q.toString() ? `?${q.toString()}` : '';
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments${qs}`, {
      headers: getAuthHeaders()
    });

    if (res.ok) {
      const json = await res.json();
      if (json && json.success) {
        return json;
      }
    }
  } catch (err) {
    console.error('[SupplierApi] List consignments fetch error:', err);
  }

  return {
    success: false,
    kpi: {
      totalTransaksi: 0,
      totalQtyReceived: 0,
      totalAmountHpp: 0,
      totalSisaStok: 0,
      totalQtySold: 0
    },
    data: []
  };
}

/**
 * 3. Ambil detail transaksi konsinyasi riil dari database
 */
export async function fetchSupplierConsignmentDetail(id) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/${id}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json.data;
    }
  } catch (err) {
    console.error('[SupplierApi] Detail fetch error:', err);
  }

  return null;
}

/**
 * 4. Tambah transaksi penerimaan titipan barang baru langsung ke database
 */
export async function createSupplierConsignment(payload) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[SupplierApi] Create consignment error:', err);
    return {
      success: false,
      message: 'Gagal terhubung ke backend database: ' + err.message
    };
  }
}

/**
 * 5. Update transaksi penerimaan titipan barang di database
 */
export async function updateSupplierConsignment(id, payload) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/${id}`, {
      method: 'PUT',
      headers: getAuthHeaders(),
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[SupplierApi] Update consignment error:', err);
    return {
      success: false,
      message: 'Gagal terhubung ke backend database: ' + err.message
    };
  }
}

/**
 * 6. Hapus transaksi penerimaan titipan barang dari database
 */
export async function deleteSupplierConsignment(id) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[SupplierApi] Delete consignment error:', err);
    return {
      success: false,
      message: 'Gagal terhubung ke backend database: ' + err.message
    };
  }
}

/**
 * 7. Rekap Kartu Stok Konsinyasi riil dari database (Stock Card)
 */
export async function fetchSupplierStockCard(params = {}) {
  try {
    const q = new URLSearchParams();
    if (params.supplier_id) q.append('supplier_id', params.supplier_id);
    if (params.search) q.append('search', params.search);
    const qs = q.toString() ? `?${q.toString()}` : '';

    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/reports/stock-card${qs}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json;
    }
  } catch (err) {
    console.error('[SupplierApi] Stock card fetch error:', err);
  }

  return {
    success: false,
    summary: {
      totalProducts: 0,
      totalRemainingQty: 0,
      totalSoldQty: 0,
      totalRemainingValue: 0
    },
    data: []
  };
}

/**
 * 8. Ambil Riwayat Setoran Pembayaran dari database (Settlements)
 */
export async function fetchSupplierSettlements(params = {}) {
  try {
    const q = new URLSearchParams();
    if (params.supplier_id) q.append('supplier_id', params.supplier_id);
    if (params.search) q.append('search', params.search);
    if (params.date_from) q.append('date_from', params.date_from);
    if (params.date_to) q.append('date_to', params.date_to);
    if (params.payment_method) q.append('payment_method', params.payment_method);
    const qs = q.toString() ? `?${q.toString()}` : '';

    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/settlements${qs}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json;
    }
  } catch (err) {
    console.error('[SupplierApi] Settlements fetch error:', err);
  }

  return {
    success: false,
    kpi: {
      totalTransaksi: 0,
      totalQtySettled: 0,
      totalAmountPaid: 0
    },
    data: []
  };
}

/**
 * 9. Ambil Riwayat Penjualan Barang Konsinyasi dari database (Sales History)
 */
export async function fetchSupplierSalesHistory(params = {}) {
  try {
    const q = new URLSearchParams();
    if (params.supplier_id) q.append('supplier_id', params.supplier_id);
    if (params.search) q.append('search', params.search);
    if (params.date_from) q.append('date_from', params.date_from);
    if (params.date_to) q.append('date_to', params.date_to);
    const qs = q.toString() ? `?${q.toString()}` : '';

    const res = await fetch(`${API_BASE_URL}/api/v2/owner/supplier-consignments/sales-history${qs}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json;
    }
  } catch (err) {
    console.error('[SupplierApi] Sales history fetch error:', err);
  }

  return {
    success: false,
    kpi: {
      totalTransaksi: 0,
      totalQtySold: 0,
      totalCostValue: 0
    },
    data: []
  };
}
