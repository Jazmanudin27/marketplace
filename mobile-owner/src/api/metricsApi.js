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

export async function fetchLabaRugi(period = 'month') {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/laba-rugi?period=${period}`, {
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

