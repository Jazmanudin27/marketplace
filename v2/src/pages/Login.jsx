import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { 
  User, 
  Lock, 
  ShieldCheck, 
  ArrowRight, 
  Layers, 
  BookOpen, 
  Cpu, 
  Boxes, 
  Sparkles,
  AlertCircle
} from 'lucide-react';

const Login = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setErrorMessage('');

    if (!username.trim() || !password.trim()) {
      setErrorMessage('Username/Email dan Password tidak boleh kosong!');
      return;
    }

    setLoading(true);

    try {
      const apiUrl = typeof window !== 'undefined' && window.location.pathname.startsWith('/v2')
        ? '/v2/api/login'
        : '/api/login';

      const response = await axios.post(apiUrl, { username, password });

      if (response.data?.success) {
        // Save user data & token to localStorage
        localStorage.setItem('v2_token', response.data.data.token);
        localStorage.setItem('v2_user', JSON.stringify(response.data.data.user));

        // Redirect to dashboard
        navigate('/', { replace: true });
      } else {
        setErrorMessage(response.data?.message || 'Login gagal!');
      }
    } catch (err) {
      console.error('Login error:', err);
      const msg = err.response?.data?.message || 'Gagal masuk. Periksa kembali username/email dan password Anda.';
      setErrorMessage(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-page-wrapper">
      <div className="login-card-container">
        {/* Left Side: Futuristic Isometric Visual Card */}
        <div className="login-visual-panel">
          {/* Top Badge */}
          <div className="login-brand-badge">
            <div className="login-brand-badge-icon">
              <Boxes size={18} />
            </div>
            <div>
              <div className="fw-bold" style={{ fontSize: '0.8rem', letterSpacing: '0.5px' }}>ASPARTECH ERP PRO</div>
              <div style={{ fontSize: '0.62rem', color: '#38bdf8', letterSpacing: '0.8px' }}>DIGITAL SMART SYSTEM</div>
            </div>
          </div>

          {/* Central Isometric Cyber Graphic */}
          <div className="login-graphic-center">
            <div className="login-hologram-circle">
              <Cpu size={72} className="text-cyan text-opacity-75" />
            </div>
            <div className="login-radar-pulse"></div>
            <div className="login-access-granted">
              <span className="dot-pulse"></span> BIOMETRIC & ACCESS VERIFIED
            </div>
          </div>

          {/* Bottom Overlay Card */}
          <div className="login-info-box">
            <h5 className="fw-bold text-white mb-2" style={{ fontSize: '1rem' }}>
              Sistem Informasi ERP & Manajemen Marketplace
            </h5>
            <p className="text-white text-opacity-75 mb-0" style={{ fontSize: '0.78rem', lineHeight: '1.4' }}>
              Platform pintar yang menghubungkan administrator, semua toko marketplace, inventori gudang, dan pesanan online dalam satu ekosistem terpadu.
            </p>
          </div>
        </div>

        {/* Right Side: Login Form (Exact Match) */}
        <div className="login-form-panel">
          {/* Top Center Logo Badge */}
          <div className="text-center mb-3">
            <div className="login-logo-glow">
              <Boxes size={28} className="text-white" />
            </div>
            <h4 className="fw-bold text-white mt-2 mb-0" style={{ letterSpacing: '0.5px' }}>
              ASPARTECH ERP PRO
            </h4>
            <div className="text-muted" style={{ fontSize: '0.75rem' }}>
              Sistem Manajemen Toko & Order Marketplace
            </div>
          </div>

          {/* Welcome Header with Shield Check Icon */}
          <div className="d-flex align-items-center justify-content-between mb-3 pt-2">
            <div>
              <h5 className="fw-bold text-white m-0" style={{ fontSize: '1.15rem' }}>
                Selamat Datang
              </h5>
              <div className="text-muted" style={{ fontSize: '0.75rem' }}>
                Silakan masuk untuk mengakses sistem
              </div>
            </div>
            <div className="login-shield-badge" title="Keamanan Terverifikasi">
              <ShieldCheck size={22} className="text-success" />
            </div>
          </div>

          {/* Error Message Alert */}
          {errorMessage && (
            <div className="alert alert-danger py-2 px-3 d-flex align-items-center gap-2 mb-3" style={{ fontSize: '0.78rem', borderRadius: '8px' }}>
              <AlertCircle size={16} className="flex-shrink-0" />
              <div>{errorMessage}</div>
            </div>
          )}

          {/* Form */}
          <form onSubmit={handleLogin}>
            {/* Input Username / Email */}
            <div className="mb-3">
              <label className="login-input-label">USERNAME / NIP / EMAIL</label>
              <div className="login-input-group">
                <User size={16} className="login-input-icon" />
                <input
                  type="text"
                  className="login-input-field"
                  placeholder="Masukkan NIP, Email, atau Username"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  required
                  autoFocus
                />
              </div>
            </div>

            {/* Input Password */}
            <div className="mb-4">
              <label className="login-input-label">PASSWORD</label>
              <div className="login-input-group">
                <Lock size={16} className="login-input-icon" />
                <input
                  type="password"
                  className="login-input-field"
                  placeholder="Masukkan password akun"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              className="btn btn-primary w-100 login-submit-btn"
              disabled={loading}
            >
              {loading ? (
                <span>Memverifikasi Akun...</span>
              ) : (
                <>
                  <span>Masuk Aplikasi</span>
                  <ArrowRight size={16} />
                </>
              )}
            </button>
          </form>

          {/* Bottom Transparency Footer Note */}
          <div className="text-center mt-4">
            <span className="text-muted d-inline-flex align-items-center gap-1" style={{ fontSize: '0.72rem' }}>
              <BookOpen size={13} /> Ekosistem Digital Transparan & Akurat
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
