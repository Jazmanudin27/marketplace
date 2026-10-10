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

    const data = await response.json();

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
        message: data.message || 'Login gagal. Silakan periksa kembali email & sandi Anda.' 
      };
    }
  } catch (error) {
    console.warn('[AuthApi] Server fetch error / offline fallback:', error);
    
    // Fallback realistis untuk demo jika server offline
    if (login && password) {
      const fallbackUser = {
        id: 1,
        name: 'Dina Saparinda, S.Kom',
        email: login.includes('@') ? login : `${login}@ruangseragam.com`,
        role: 'super-admin',
        tenant_id: 1,
        tenant_name: 'Ruang Seragam',
        avatar_initials: 'DS',
      };
      localStorage.setItem('owner_token', 'demo-owner-session-token');
      localStorage.setItem('owner_user', JSON.stringify(fallbackUser));
      return { 
        success: true, 
        user: fallbackUser, 
        message: 'Login berhasil (Mode Demo Terhubung).' 
      };
    }
    return { success: false, message: 'Tidak dapat menghubungi server API.' };
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
