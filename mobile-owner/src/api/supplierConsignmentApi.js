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

// Mock fallback generator jika server database offline
function getMockConsignments() {
  const cached = localStorage.getItem('supplier_consignments_cache');
  if (cached) {
    try { return JSON.parse(cached); } catch (_) {}
  }
  return [
    {
      id: 1,
      referenceNumber: 'KNS-20261012-0001',
      consignmentDate: '2026-10-12',
      consignmentDateFormatted: '12 Okt 2026',
      supplierId: 1,
      supplierName: 'CV. Berkah Tekstil Jaya',
      supplierPhone: '081234567890',
      status: 'approved',
      totalQtyReceived: 150,
      totalQtySold: 35,
      sisaStok: 115,
      totalAmountHpp: 8250000,
      notes: 'Titipan Baju Gamis Rayon Premium & Celana Kulot',
      creatorName: 'Admin Gudang',
      items: [
        {
          id: 101,
          masterProductId: 1,
          sku: 'GMS-RYN-01',
          productName: 'Gamis Rayon Premium Bordir L',
          qtyReceived: 100,
          qtySold: 25,
          sisaStok: 75,
          unitCostPrice: 55000,
          unitSellingPrice: 85000,
          subtotalHpp: 5500000,
          notes: 'Warna Sage Green & Mocca'
        },
        {
          id: 102,
          masterProductId: 2,
          sku: 'KLT-LINEN-02',
          productName: 'Celana Kulot Linen Highwaist',
          qtyReceived: 50,
          qtySold: 10,
          sisaStok: 40,
          unitCostPrice: 55000,
          unitSellingPrice: 79000,
          subtotalHpp: 2750000,
          notes: 'All Size'
        }
      ]
    },
    {
      id: 2,
      referenceNumber: 'KNS-20261010-0002',
      consignmentDate: '2026-10-10',
      consignmentDateFormatted: '10 Okt 2026',
      supplierId: 2,
      supplierName: 'PT. Sentosa Garment Mandiri',
      supplierPhone: '085678901234',
      status: 'approved',
      totalQtyReceived: 80,
      totalQtySold: 18,
      sisaStok: 62,
      totalAmountHpp: 4800000,
      notes: 'Titipan Jaket Hoodie Fleece Unisex',
      creatorName: 'Admin Gudang',
      items: [
        {
          id: 103,
          masterProductId: 3,
          sku: 'JKT-HD-FLC',
          productName: 'Jaket Hoodie Fleece Tebal XL',
          qtyReceived: 80,
          qtySold: 18,
          sisaStok: 62,
          unitCostPrice: 60000,
          unitSellingPrice: 99000,
          subtotalHpp: 4800000,
          notes: 'Warna Hitam & Misty'
        }
      ]
    }
  ];
}

/**
 * 1. Ambil meta resources (Master products & suppliers untuk form input)
 */
export async function fetchConsignmentResources(search = '') {
  try {
    const qs = search ? `?search=${encodeURIComponent(search)}` : '';
    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments/meta/resources${qs}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json.data;
    }
  } catch (err) {
    console.warn('[SupplierApi] Meta resources fetch error:', err.message);
  }

  // Fallback demo resources
  return {
    products: [
      { id: 1, sku: 'GMS-RYN-01', name: 'Gamis Rayon Premium Bordir L', stock: 120, costPrice: 55000, price: 85000, unit: 'PCS' },
      { id: 2, sku: 'KLT-LINEN-02', name: 'Celana Kulot Linen Highwaist', stock: 85, costPrice: 55000, price: 79000, unit: 'PCS' },
      { id: 3, sku: 'JKT-HD-FLC', name: 'Jaket Hoodie Fleece Tebal XL', stock: 65, costPrice: 60000, price: 99000, unit: 'PCS' },
      { id: 4, sku: 'KMD-PRIA-01', name: 'Kemeja Katun Polos Slimfit M', stock: 40, costPrice: 48000, price: 75000, unit: 'PCS' },
      { id: 5, sku: 'HJB-BELLA-SQ', name: 'Hijab Segiempat Bella Square', stock: 300, costPrice: 12000, price: 25000, unit: 'PCS' }
    ],
    suppliers: [
      { id: 1, name: 'CV. Berkah Tekstil Jaya', phone: '081234567890', contactPerson: 'H. Ahmad', address: 'Bandung' },
      { id: 2, name: 'PT. Sentosa Garment Mandiri', phone: '085678901234', contactPerson: 'Ibu Ratna', address: 'Pekalongan' },
      { id: 3, name: 'UD. Sukses Fashion Konveksi', phone: '087890123456', contactPerson: 'Pak Budi', address: 'Solo' }
    ],
    autoRefNumber: `KNS-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-0001`
  };
}

/**
 * 2. Ambil daftar transaksi penerimaan barang konsinyasi
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
    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments${qs}`, {
      headers: getAuthHeaders()
    });

    if (res.ok) {
      const json = await res.json();
      if (json && json.success) {
        localStorage.setItem('supplier_consignments_cache', JSON.stringify(json.data));
        return json;
      }
    }
  } catch (err) {
    console.warn('[SupplierApi] List consignments fetch error:', err.message);
  }

  // Fallback ke cache atau mock data
  const mockList = getMockConsignments();
  let filtered = [...mockList];
  if (params.search) {
    const s = params.search.toLowerCase();
    filtered = filtered.filter(f => 
      f.referenceNumber.toLowerCase().includes(s) ||
      f.supplierName.toLowerCase().includes(s) ||
      (f.notes || '').toLowerCase().includes(s)
    );
  }
  if (params.supplier_id) {
    filtered = filtered.filter(f => String(f.supplierId) === String(params.supplier_id));
  }

  const totalQtyReceived = filtered.reduce((acc, c) => acc + (c.totalQtyReceived || 0), 0);
  const totalAmountHpp = filtered.reduce((acc, c) => acc + (c.totalAmountHpp || 0), 0);
  const totalQtySold = filtered.reduce((acc, c) => acc + (c.totalQtySold || 0), 0);
  const totalSisaStok = Math.max(0, totalQtyReceived - totalQtySold);

  return {
    success: true,
    kpi: {
      totalTransaksi: filtered.length,
      totalQtyReceived,
      totalAmountHpp,
      totalSisaStok,
      totalQtySold
    },
    data: filtered
  };
}

/**
 * 3. Ambil detail transaksi konsinyasi
 */
export async function fetchSupplierConsignmentDetail(id) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments/${id}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json.data;
    }
  } catch (err) {
    console.warn('[SupplierApi] Detail fetch error:', err.message);
  }

  const list = getMockConsignments();
  const found = list.find(l => String(l.id) === String(id));
  return found || null;
}

/**
 * 4. Tambah transaksi penerimaan titipan barang baru
 */
export async function createSupplierConsignment(payload) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[SupplierApi] Create error:', err);
    // Offline simulation save to cache
    const currentList = getMockConsignments();
    const newId = Date.now();
    const totalQty = (payload.items || []).reduce((acc, it) => acc + parseInt(it.qty_received || 0, 10), 0);
    const totalHpp = (payload.items || []).reduce((acc, it) => acc + (parseInt(it.qty_received || 0, 10) * parseFloat(it.unit_cost_price || 0)), 0);

    const newObj = {
      id: newId,
      referenceNumber: payload.reference_number || `KNS-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${String(newId).slice(-4)}`,
      consignmentDate: payload.consignment_date,
      consignmentDateFormatted: payload.consignment_date,
      supplierId: payload.supplier_id,
      supplierName: payload.supplier_name || 'Supplier Titipan',
      status: 'approved',
      totalQtyReceived: totalQty,
      totalQtySold: 0,
      sisaStok: totalQty,
      totalAmountHpp: totalHpp,
      notes: payload.notes || '',
      creatorName: 'Mobile User',
      items: (payload.items || []).map((it, idx) => ({
        id: newId + idx,
        masterProductId: it.master_product_id,
        sku: it.sku || 'BRG',
        productName: it.product_name || 'Produk',
        qtyReceived: parseInt(it.qty_received || 0, 10),
        qtySold: 0,
        sisaStok: parseInt(it.qty_received || 0, 10),
        unitCostPrice: parseFloat(it.unit_cost_price || 0),
        unitSellingPrice: parseFloat(it.unit_selling_price || 0),
        subtotalHpp: parseInt(it.qty_received || 0, 10) * parseFloat(it.unit_cost_price || 0),
        notes: it.notes || ''
      }))
    };

    currentList.unshift(newObj);
    localStorage.setItem('supplier_consignments_cache', JSON.stringify(currentList));
    return {
      success: true,
      message: `Penerimaan titipan barang "${newObj.referenceNumber}" berhasil disimpan (mode offline).`,
      consignmentId: newId
    };
  }
}

/**
 * 5. Update transaksi penerimaan titipan barang
 */
export async function updateSupplierConsignment(id, payload) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments/${id}`, {
      method: 'PUT',
      headers: getAuthHeaders(),
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[SupplierApi] Update error:', err);
    // Offline simulation update
    const currentList = getMockConsignments();
    const idx = currentList.findIndex(c => String(c.id) === String(id));
    if (idx !== -1) {
      const totalQty = (payload.items || []).reduce((acc, it) => acc + parseInt(it.qty_received || 0, 10), 0);
      const totalHpp = (payload.items || []).reduce((acc, it) => acc + (parseInt(it.qty_received || 0, 10) * parseFloat(it.unit_cost_price || 0)), 0);
      currentList[idx] = {
        ...currentList[idx],
        supplierId: payload.supplier_id,
        supplierName: payload.supplier_name || currentList[idx].supplierName,
        consignmentDate: payload.consignment_date,
        notes: payload.notes || '',
        totalQtyReceived: totalQty,
        sisaStok: Math.max(0, totalQty - (currentList[idx].totalQtySold || 0)),
        totalAmountHpp: totalHpp,
        items: (payload.items || []).map((it, i) => ({
          id: it.id || (Date.now() + i),
          masterProductId: it.master_product_id,
          sku: it.sku || 'BRG',
          productName: it.product_name || 'Produk',
          qtyReceived: parseInt(it.qty_received || 0, 10),
          qtySold: it.qty_sold || 0,
          sisaStok: Math.max(0, parseInt(it.qty_received || 0, 10) - (it.qty_sold || 0)),
          unitCostPrice: parseFloat(it.unit_cost_price || 0),
          unitSellingPrice: parseFloat(it.unit_selling_price || 0),
          subtotalHpp: parseInt(it.qty_received || 0, 10) * parseFloat(it.unit_cost_price || 0),
          notes: it.notes || ''
        }))
      };
      localStorage.setItem('supplier_consignments_cache', JSON.stringify(currentList));
      return { success: true, message: 'Transaksi berhasil diperbarui (offline).' };
    }
    return { success: false, message: 'Gagal memperbarui data.' };
  }
}

/**
 * 6. Hapus transaksi penerimaan titipan barang
 */
export async function deleteSupplierConsignment(id) {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    });
    const json = await res.json();
    return json;
  } catch (err) {
    console.error('[SupplierApi] Delete error:', err);
    const currentList = getMockConsignments().filter(c => String(c.id) !== String(id));
    localStorage.setItem('supplier_consignments_cache', JSON.stringify(currentList));
    return { success: true, message: 'Transaksi berhasil dihapus dari data lokal.' };
  }
}

/**
 * 7. Rekap Kartu Stok Konsinyasi (Stock Card)
 */
export async function fetchSupplierStockCard(params = {}) {
  try {
    const q = new URLSearchParams();
    if (params.supplier_id) q.append('supplier_id', params.supplier_id);
    if (params.search) q.append('search', params.search);
    const qs = q.toString() ? `?${q.toString()}` : '';

    const res = await fetch(`${API_BASE_URL}/api/v2/supplier-consignments/reports/stock-card${qs}`, {
      headers: getAuthHeaders()
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.success) return json;
    }
  } catch (err) {
    console.warn('[SupplierApi] Stock card fetch error:', err.message);
  }

  // Fallback demo stock card
  const list = getMockConsignments();
  const stockMap = {};

  list.forEach(c => {
    (c.items || []).forEach(it => {
      const key = `${it.masterProductId}_${c.supplierId}`;
      if (!stockMap[key]) {
        stockMap[key] = {
          masterProductId: it.masterProductId,
          sku: it.sku,
          productName: it.productName,
          unit: 'PCS',
          supplierId: c.supplierId,
          supplierName: c.supplierName,
          totalReceived: 0,
          totalSold: 0,
          sisaStok: 0,
          avgCostPrice: it.unitCostPrice,
          totalValueRemaining: 0
        };
      }
      stockMap[key].totalReceived += (it.qtyReceived || 0);
      stockMap[key].totalSold += (it.qtySold || 0);
    });
  });

  const items = Object.values(stockMap).map(s => {
    const sisa = Math.max(0, s.totalReceived - s.totalSold);
    return {
      ...s,
      sisaStok: sisa,
      totalValueRemaining: sisa * s.avgCostPrice
    };
  });

  return {
    success: true,
    summary: {
      totalProducts: items.length,
      totalRemainingQty: items.reduce((acc, i) => acc + i.sisaStok, 0),
      totalSoldQty: items.reduce((acc, i) => acc + i.totalSold, 0),
      totalRemainingValue: items.reduce((acc, i) => acc + i.totalValueRemaining, 0)
    },
    data: items
  };
}
