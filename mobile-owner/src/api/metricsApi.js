import { mockOwnerMetrics, mockOwnerProfile } from './mockData';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v2';

export async function fetchOwnerMetrics() {
  try {
    if (import.meta.env.VITE_USE_LIVE_API === 'true') {
      const response = await fetch(`${API_BASE_URL}/owner/metrics/overview`, {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('owner_token') || ''}`
        }
      });
      if (response.ok) {
        return await response.json();
      }
    }
  } catch (error) {
    console.warn('[MetricsApi] Live fallback:', error.message);
  }

  return {
    success: true,
    data: mockOwnerMetrics,
    profile: mockOwnerProfile
  };
}
