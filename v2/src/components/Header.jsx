import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useLayout } from '../context/LayoutContext';
import { 
  Menu, 
  Clock, 
  Bell, 
  Building, 
  LogOut, 
  RotateCw, 
  ChevronDown, 
  Check, 
  ExternalLink,
  Shield,
  User as UserIcon,
  Sparkles,
  Store,
  CheckCircle2,
  AlertTriangle
} from 'lucide-react';

const Header = () => {
  const { toggleSidebar, toggleMobileSidebar } = useLayout();
  const navigate = useNavigate();

  const [timeString, setTimeString] = useState('');
  const [isSyncing, setIsSyncing] = useState(false);
  const [profileDropdownOpen, setProfileDropdownOpen] = useState(false);
  const [notifDropdownOpen, setNotifDropdownOpen] = useState(false);

  const profileRef = useRef(null);
  const notifRef = useRef(null);

  const [user, setUser] = useState({
    name: 'Ruang Seragam Admin',
    email: 'admin@ruangseragam.com',
    tenant_name: 'Ruang Seragam',
    role: 'ADMIN'
  });

  // Realtime clock
  useEffect(() => {
    const updateClock = () => {
      const now = new Date();
      const options = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
      setTimeString(now.toLocaleDateString('id-ID', options).replace(/\./g, ':'));
    };
    updateClock();
    const interval = setInterval(updateClock, 1000);

    // Read stored user
    try {
      const savedUser = localStorage.getItem('v2_user');
      if (savedUser) {
        setUser(JSON.parse(savedUser));
      }
    } catch (e) {
      console.error(e);
    }

    return () => clearInterval(interval);
  }, []);

  // Click outside to close dropdowns
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (profileRef.current && !profileRef.current.contains(e.target)) {
        setProfileDropdownOpen(false);
      }
      if (notifRef.current && !notifRef.current.contains(e.target)) {
        setNotifDropdownOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSyncNow = () => {
    setIsSyncing(true);
    setTimeout(() => {
      setIsSyncing(false);
    }, 1200);
  };

  const handleLogout = (e) => {
    e.preventDefault();
    localStorage.removeItem('v2_token');
    localStorage.removeItem('v2_user');
    navigate('/login', { replace: true });
  };

  const initials = (user.tenant_name || user.name || 'RS')
    .split(' ')
    .map(w => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();

  return (
    <header className="v2-header">
      {/* Left: Sidebar Toggle & Realtime Clock */}
      <div className="d-flex align-items-center gap-3">
        {/* Toggle Sidebar Button */}
        <button 
          className="v2-header-toggle-btn"
          type="button"
          onClick={() => {
            if (window.innerWidth < 992) {
              toggleMobileSidebar();
            } else {
              toggleSidebar();
            }
          }}
          title="Toggle Navigasi Sidebar"
        >
          <Menu size={18} />
        </button>

        {/* Realtime Live Clock */}
        <div className="v2-time-indicator d-none d-sm-flex align-items-center gap-1.5">
          <Clock size={13} className="text-primary" />
          <span>{timeString || 'Memuat waktu...'}</span>
        </div>
      </div>

      {/* Right: Actions, Sync, Notifications & User Dropdown */}
      <div className="d-flex align-items-center gap-2.5">

        {/* Quick Sync Button */}
        <button 
          type="button" 
          onClick={handleSyncNow}
          className="v2-header-action-btn"
          title="Sinkronisasi Marketplace Sekarang"
        >
          <RotateCw size={15} className={isSyncing ? 'spin text-primary' : 'text-secondary'} />
          <span className="d-none d-xl-inline ms-1 fw-medium" style={{ fontSize: '0.75rem' }}>
            {isSyncing ? 'Menyinkron...' : 'Sync Toko'}
          </span>
        </button>

        {/* Notification Bell Dropdown */}
        <div className="position-relative" ref={notifRef}>
          <button 
            type="button" 
            className="v2-header-action-btn position-relative"
            onClick={() => setNotifDropdownOpen(prev => !prev)}
            title="Notifikasi Sistem"
          >
            <Bell size={16} />
            <span className="v2-notif-pill">3</span>
          </button>

          {/* Interactive Notifications Popup Menu */}
          {notifDropdownOpen && (
            <div className="v2-dropdown-panel v2-notif-dropdown animate-fade-in">
              <div className="v2-dropdown-header d-flex align-items-center justify-content-between">
                <span className="fw-bold text-dark" style={{ fontSize: '0.82rem' }}>Pemberitahuan Sistem</span>
                <span className="badge bg-primary rounded-pill px-2 py-0.5" style={{ fontSize: '0.62rem' }}>3 Baru</span>
              </div>
              <div className="v2-notif-list">
                <div className="v2-notif-item unread">
                  <div className="v2-notif-icon bg-danger-subtle text-danger">
                    <Store size={14} />
                  </div>
                  <div className="v2-notif-content">
                    <div className="v2-notif-title">Pesanan Baru Masuk (Shopee)</div>
                    <div className="v2-notif-desc">Pesanan #SP-8821 senilai Rp 185.000 siap diproses.</div>
                    <div className="v2-notif-time">2 menit lalu</div>
                  </div>
                </div>

                <div className="v2-notif-item unread">
                  <div className="v2-notif-icon bg-warning-subtle text-warning">
                    <AlertTriangle size={14} />
                  </div>
                  <div className="v2-notif-content">
                    <div className="v2-notif-title">Peringatan Stok Menipis</div>
                    <div className="v2-notif-desc">Produk 'Seragam Putih OSIS L' sisa 2 pcs di gudang.</div>
                    <div className="v2-notif-time">15 menit lalu</div>
                  </div>
                </div>

                <div className="v2-notif-item">
                  <div className="v2-notif-icon bg-success-subtle text-success">
                    <CheckCircle2 size={14} />
                  </div>
                  <div className="v2-notif-content">
                    <div className="v2-notif-title">Sinkronisasi Toko Sukses</div>
                    <div className="v2-notif-desc">3 Channel toko aktif (Shopee, TikTok, Lazada) terhubung.</div>
                    <div className="v2-notif-time">1 jam lalu</div>
                  </div>
                </div>
              </div>
              <div className="v2-dropdown-footer text-center">
                <button 
                  type="button" 
                  className="btn btn-sm btn-link text-decoration-none text-primary fw-semibold p-0"
                  style={{ fontSize: '0.74rem' }}
                  onClick={() => setNotifDropdownOpen(false)}
                >
                  Tandai semua sudah dibaca
                </button>
              </div>
            </div>
          )}
        </div>

        {/* User Profile & Tenant Pill with Interactive Dropdown */}
        <div className="position-relative" ref={profileRef}>
          <button 
            type="button" 
            className="v2-profile-pill-btn"
            onClick={() => setProfileDropdownOpen(prev => !prev)}
            aria-expanded={profileDropdownOpen}
          >
            <div className="v2-profile-avatar-gradient">
              {initials}
              <span className="v2-avatar-online-dot"></span>
            </div>
            <div className="d-none d-md-flex flex-column text-start">
              <span className="v2-profile-name">{user.name || 'User'}</span>
              <span className="v2-profile-tenant">{user.tenant_name || 'Ruang Seragam'}</span>
            </div>
            <ChevronDown 
              size={14} 
              className={`v2-profile-chevron ${profileDropdownOpen ? 'rotate-180' : ''}`} 
            />
          </button>

          {/* Interactive Profile Dropdown Menu */}
          {profileDropdownOpen && (
            <div className="v2-dropdown-panel v2-profile-dropdown animate-fade-in">
              {/* User Header Details */}
              <div className="v2-profile-dropdown-header">
                <div className="d-flex align-items-center gap-2.5">
                  <div className="v2-profile-avatar-large">
                    {initials}
                  </div>
                  <div className="overflow-hidden">
                    <div className="fw-bold text-dark text-truncate" style={{ fontSize: '0.85rem' }}>
                      {user.name || 'User'}
                    </div>
                    <div className="text-muted text-truncate" style={{ fontSize: '0.72rem' }}>
                      {user.email || 'user@aspartech.com'}
                    </div>
                    <div className="d-flex align-items-center gap-1 mt-1">
                      <span className="badge bg-primary text-uppercase" style={{ fontSize: '0.58rem', padding: '0.15rem 0.4rem' }}>
                        {user.role || 'ADMIN'}
                      </span>
                      <span className="badge bg-success-subtle text-success" style={{ fontSize: '0.58rem' }}>
                        {user.tenant_name || 'Toko Utama'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Menu Links */}
              <div className="v2-dropdown-menu-list">
                <a 
                  href="#profile" 
                  className="v2-dropdown-item"
                  onClick={(e) => { e.preventDefault(); setProfileDropdownOpen(false); }}
                >
                  <UserIcon size={14} className="text-secondary" />
                  <span>Profil Akun Saya</span>
                </a>
                <a 
                  href="#toko" 
                  className="v2-dropdown-item"
                  onClick={(e) => { e.preventDefault(); setProfileDropdownOpen(false); }}
                >
                  <Store size={14} className="text-secondary" />
                  <span>Kelola Toko Marketplace</span>
                </a>
                <a 
                  href="/dashboard" 
                  className="v2-dropdown-item text-warning-emphasis"
                  onClick={() => setProfileDropdownOpen(false)}
                >
                  <ExternalLink size={14} className="text-warning" />
                  <span>Buka Portal ERP V1</span>
                </a>
              </div>

              <div className="v2-dropdown-divider"></div>

              {/* Logout Button */}
              <div className="p-1">
                <button 
                  type="button" 
                  onClick={handleLogout} 
                  className="v2-dropdown-item text-danger fw-semibold w-100"
                >
                  <LogOut size={14} className="text-danger" />
                  <span>Keluar / Logout</span>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};

export default Header;
