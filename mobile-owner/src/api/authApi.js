const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

/**
 * Login API untuk Mobile Owner
 * Mengirim data ke backend Laravel (mencocokkan ke tabel `users`)
 */
export async function loginOwner({ login, password }) {
  try {
    const response = await fetch(`${API_BASE_URL}/api/v2/owner/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ login, password }),
    });

    const contentType = response.headers.get('content-type') || '';
    let data;

    if (contentType.includes('application/json')) {
      data = await response.json();
    } else {
      const text = await response.text();
      console.error('[AuthApi] Server non-JSON response:', text);
      return {
        success: false,
        message: `Server mengembalikan respon (${response.status}): Silakan periksa konfigurasi domain atau rute API di web server.`
      };
    }

    if (response.ok && data.success) {
      if (data.data?.token) {
        localStorage.setItem('owner_token', data.data.token);
      }
      if (data.data?.user) {
        localStorage.setItem('owner_user', JSON.stringify(data.data.user));
      }
      return { success: true, user: data.data?.user, message: data.message };
    } else {
      return { 
        success: false, 
        message: data.message || 'Login gagal. Email atau kata sandi tidak cocok.' 
      };
    }
  } catch (error) {
    console.error('[AuthApi] Server fetch error:', error);
    return { 
      success: false, 
      message: 'Gagal terhubung ke API backend: ' + error.message 
    };
  }
}

/**
 * Ambil sesi user yang sedang login dari localStorage
 */
export function getCurrentOwnerUser() {
  try {
    const raw = localStorage.getItem('owner_user');
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

/**
 * Logout
 */
export function logoutOwner() {
  localStorage.removeItem('owner_token');
  localStorage.removeItem('owner_user');
}
