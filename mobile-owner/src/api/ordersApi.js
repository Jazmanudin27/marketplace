const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

/**
 * Fetch Order list with lightweight pagination & filtering
 */
export async function fetchOrders({ status = 'ALL', search = '', page = 1, limit = 20 } = {}) {
  try {
    const params = new URLSearchParams({ status, search, page: String(page), limit: String(limit) });
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/orders?${params}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...tenantHeader,
      }
    });

    if (response.ok) {
      const result = await response.json();
      if (result && Array.isArray(result.data)) {
        return result;
      }
    }
  } catch (error) {
    console.warn('[OrdersApi] Live API fetch error:', error.message);
  }

  return {
    success: false,
    data: [],
    total: 0,
    page,
    limit
  };
}

/**
 * Fetch single order detailed breakdown
 */
export async function fetchOrderDetail(orderId) {
  try {
    const token = localStorage.getItem('owner_token') || '';
    const userStr = localStorage.getItem('owner_user');
    const user = userStr ? JSON.parse(userStr) : null;
    const tenantHeader = user?.tenant_id ? { 'X-Tenant-Id': String(user.tenant_id) } : {};

    const response = await fetch(`${API_BASE_URL}/api/v2/owner/orders/${orderId}`, {
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
    console.warn('[OrdersApi] Detail live fetch error:', error.message);
  }

  return {
    success: false,
    data: null
  };
}
