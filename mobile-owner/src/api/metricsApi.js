import { initialOwnerMetrics, initialOwnerProfile } from './mockData';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

export async function fetchOwnerMetrics() {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/metrics/overview`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      const result = await response.json();
      if (result && result.data) {
        return result;
      }
    }
  } catch (error) {
    console.warn('[MetricsApi] Live fetch error:', error.message);
  }

  return {
    success: false,
    data: initialOwnerMetrics,
    profile: initialOwnerProfile
  };
}

export async function fetchTargetKomisi(month = null, year = null) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const params = new URLSearchParams();
    if (month) params.append('month', month);
    if (year) params.append('year', year);
    const qs = params.toString() ? `?${params.toString()}` : '';

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/target-komisi${qs}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      const result = await response.json();
      if (result && result.success) return result;
    }
  } catch (error) {
    console.warn('[MetricsApi] fetchTargetKomisi error:', error.message);
  }
  return { success: false, summary: null, teams: [], availableStores: [] };
}

export async function createTargetKomisi(payload) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/target-komisi`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();
    return result;
  } catch (error) {
    console.error('[MetricsApi] createTargetKomisi error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function updateTargetKomisi(id, payload) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/target-komisi/${id}`, {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();
    return result;
  } catch (error) {
    console.error('[MetricsApi] updateTargetKomisi error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function deleteTargetKomisi(id) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/target-komisi/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
    });

    const result = await response.json();
    return result;
  } catch (error) {
    console.error('[MetricsApi] deleteTargetKomisi error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function toggleTargetKomisi(id) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/target-komisi/${id}/toggle`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
    });

    const result = await response.json();
    return result;
  } catch (error) {
    console.error('[MetricsApi] toggleTargetKomisi error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function fetchLabaRugi(param = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const q = new URLSearchParams();
    if (typeof param === 'string') {
      q.append('period', param);
    } else if (param && typeof param === 'object') {
      if (param.month) q.append('month', param.month);
      if (param.year) q.append('year', param.year);
      if (param.dateFrom) q.append('date_from', param.dateFrom);
      if (param.dateTo) q.append('date_to', param.dateTo);
      if (param.period && !param.month && !param.year) q.append('period', param.period);
    }

    const qs = q.toString() ? `?${q.toString()}` : '';
    const response = await fetch(`${API_BASE_URL}/api/v2/owner/laba-rugi${qs}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      const result = await response.json();
      if (result && result.success) return result;
    }
  } catch (error) {
    console.warn('[MetricsApi] fetchLabaRugi error:', error.message);
  }
  return { success: false, data: null };
}

// ─── MUTASI KEUANGAN API ─────────────────────────────────────────────────────

export async function fetchMutasiKeuangan(params = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const q = new URLSearchParams();
    if (params.dateFrom) q.append('date_from', params.dateFrom);
    if (params.dateTo) q.append('date_to', params.dateTo);
    if (params.account && params.account !== 'all') q.append('account', params.account);
    if (params.category && params.category !== 'all') q.append('category', params.category);
    if (params.direction && params.direction !== 'all') q.append('direction', params.direction);
    if (params.sourceType && params.sourceType !== 'all') q.append('source_type', params.sourceType);
    if (params.search) q.append('search', params.search);

    const qs = q.toString() ? `?${q.toString()}` : '';
    const response = await fetch(`${API_BASE_URL}/api/v2/owner/mutasi-keuangan${qs}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      const result = await response.json();
      if (result && result.success) return result;
    }
  } catch (error) {
    console.warn('[MetricsApi] fetchMutasiKeuangan error:', error.message);
  }
  return { success: false, mutations: [], summary: {} };
}

export async function createMutationIncome(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/mutasi-keuangan/income`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data)
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] createMutationIncome error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function createMutationExpense(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/mutasi-keuangan/expense`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data)
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] createMutationExpense error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function createMutationTransfer(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/mutasi-keuangan/transfer`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data)
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] createMutationTransfer error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function deleteMutation(type, id) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/mutasi-keuangan/${type}/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] deleteMutation error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function fetchSaldoMarketplace() {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/saldo-marketplace`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      return await response.json();
    }
  } catch (error) {
    console.warn('[MetricsApi] fetchSaldoMarketplace error:', error.message);
  }
  return { success: false, data: { totalWalletBalance: 0, totalPendingBalance: 0, totalPendingCount: 0, totalEstimated: 0, stores: [] } };
}

export async function fetchSaldoMarketplaceMutasi(storeId, { dateFrom = '', dateTo = '', search = '' } = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (search) params.append('search', search);

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/saldo-marketplace/${storeId}/mutasi?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      return await response.json();
    }
  } catch (error) {
    console.warn('[MetricsApi] fetchSaldoMarketplaceMutasi error:', error.message);
  }
  return { success: false, data: [] };
}

export async function fetchLaporanPenjualan({ dateFrom = '', dateTo = '', storeId = '' } = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (storeId) params.append('store_id', storeId);

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/laporan-penjualan?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      return await response.json();
    }
  } catch (error) {
    console.warn('[MetricsApi] fetchLaporanPenjualan error:', error.message);
  }
  return { success: false, summary: {}, byStore: [], byChannel: [], byDate: [], transactions: [] };
}

export async function fetchReturMasuk({ search = '', storeId = '', status = '', isRestocked = '', page = 1, limit = 30 } = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const params = new URLSearchParams({ page: String(page), limit: String(limit) });
    if (search) params.append('search', search);
    if (storeId) params.append('store_id', storeId);
    if (status) params.append('status', status);
    if (isRestocked !== '') params.append('is_restocked', isRestocked);

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/retur?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    if (response.ok) return await response.json();
  } catch (error) {
    console.warn('[MetricsApi] fetchReturMasuk error:', error.message);
  }
  return { success: false, stats: {}, data: [], filters: { stores: [], statuses: [] }, pagination: { page: 1, lastPage: 1, total: 0 } };
}

export async function submitReturQc(returnId, data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/retur/${returnId}/qc`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] submitReturQc error:', error.message);
    return { success: false, message: error.message };
  }
}

// ─── SCAN KEMASAN ONLINE (MARKETPLACE V2 PACKING) ────────────────────────────

export async function lookupOnlineOrderForPacking(code = '') {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const url = `${API_BASE_URL}/api/v2/owner/scan-kemasan/order-lookup?code=${encodeURIComponent(code)}`;
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] lookupOnlineOrderForPacking error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function completeOnlineOrderPacking(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-kemasan/order-complete`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] completeOnlineOrderPacking error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function searchSubstituteProducts(query = '') {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const url = `${API_BASE_URL}/api/v2/owner/scan-kemasan/substitute-search?q=${encodeURIComponent(query)}`;
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] searchSubstituteProducts error:', error.message);
    return { success: false, products: [] };
  }
}

export async function substituteOrderItem(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-kemasan/substitute-item`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] substituteOrderItem error:', error.message);
    return { success: false, message: error.message };
  }
}

// ─── SCAN KEMASAN PRODUK (INVENTORY ADJUST) ──────────────────────────────────
export async function searchScanKemasan(code = '') {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const url = `${API_BASE_URL}/api/v2/owner/scan-kemasan/search?code=${encodeURIComponent(code)}`;
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    if (response.ok) return await response.json();
  } catch (error) {
    console.warn('[MetricsApi] searchScanKemasan error:', error.message);
  }
  return { success: false, matched: null, suggestions: [], recentMovements: [], marketplaceListings: [] };
}

export async function adjustScanKemasanStock(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-kemasan/adjust-stock`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] adjustScanKemasanStock error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function requestScanKemasanProduction(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-kemasan/request-production`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] requestScanKemasanProduction error:', error.message);
    return { success: false, message: error.message };
  }
}

// ─── SCAN SPK API ────────────────────────────────────────────────────────────

export async function lookupScanSpk(code = '') {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const url = `${API_BASE_URL}/api/v2/owner/scan-spk/lookup?code=${encodeURIComponent(code)}`;
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    return await response.json();
  } catch (error) {
    console.warn('[MetricsApi] lookupScanSpk error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function submitScanSpkPickup(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-spk/pickup`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] submitScanSpkPickup error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function processScanSpk(data) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-spk/process-scan`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    console.error('[MetricsApi] processScanSpk error:', error.message);
    return { success: false, message: error.message };
  }
}

export async function fetchScanSpkHistory({ search = '', date = '' } = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const q = new URLSearchParams();
    if (search) q.append('search', search);
    if (date) q.append('date', date);

    const qs = q.toString() ? `?${q.toString()}` : '';
    const response = await fetch(`${API_BASE_URL}/api/v2/owner/scan-spk/history${qs}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });
    if (response.ok) return await response.json();
  } catch (error) {
    console.warn('[MetricsApi] fetchScanSpkHistory error:', error.message);
  }
  return { success: false, kpi: { todayScans: 0, todayPcs: 0, todaySpks: 0 }, data: [] };
}

// ─── PENJUALAN OFFLINE API ───────────────────────────────────────────────────

export async function fetchOfflineSales({
  search = '',
  status = 'ALL',
  packingStatus = 'ALL',
  filterType = 'ALL',
  from = '',
  to = '',
  page = 1,
  limit = 25
} = {}) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const q = new URLSearchParams();
    if (search) q.append('search', search);
    if (status && status !== 'ALL') q.append('status', status);
    if (packingStatus && packingStatus !== 'ALL') q.append('packing_status', packingStatus);
    if (filterType && filterType !== 'ALL') q.append('filter_type', filterType);
    if (from) q.append('from', from);
    if (to) q.append('to', to);
    if (page) q.append('page', String(page));
    if (limit) q.append('limit', String(limit));

    const qs = q.toString() ? `?${q.toString()}` : '';
    const response = await fetch(`${API_BASE_URL}/api/v2/owner/offline-sales${qs}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) return await response.json();
  } catch (error) {
    console.warn('[MetricsApi] fetchOfflineSales error:', error.message);
  }
  return {
    success: false,
    summary: { totalOmset: 0, totalTerbayar: 0, totalPiutang: 0, totalCount: 0, unpackedCount: 0, poSpkCount: 0 },
    pagination: { page: 1, limit: 25, total: 0, totalPages: 1 },
    data: []
  };
}

export async function fetchOfflineSaleDetail(id) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/offline-sales/${id}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) return await response.json();
  } catch (error) {
    console.warn('[MetricsApi] fetchOfflineSaleDetail error:', error.message);
  }
  return { success: false, data: null };
}
