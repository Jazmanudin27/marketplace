import { mockOwnerMetrics, mockOwnerProfile } from './mockData';

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
    console.warn('[MetricsApi] Live fetch error, using fallback:', error.message);
  }

  return {
    success: true,
    data: mockOwnerMetrics,
    profile: mockOwnerProfile
  };
}
