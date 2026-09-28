import React from 'react';
import { NavLink } from 'react-router-dom';
import { 
  Grid, 
  Database, 
  ShoppingCart, 
  Store, 
  FileText, 
  Settings, 
  ArrowLeftCircle, 
  ChevronRight,
  Building
} from 'lucide-react';

const Sidebar = () => {
  const [user, setUser] = React.useState({
    name: 'Ruang Seragam Admin',
    email: 'admin@ruangseragam.com',
    tenant_name: 'Ruang Seragam',
    role: 'ADMIN'
  });

  React.useEffect(() => {
    try {
      const savedUser = localStorage.getItem('v2_user');
      if (savedUser) {
        setUser(JSON.parse(savedUser));
      }
    } catch (e) {
      console.error(e);
    }
  }, []);

  const initials = (user.tenant_name || user.name || 'RS')
    .split(' ')
    .map(w => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();

  return (
    <aside className="v2-sidebar">
      {/* Brand Header Exact Match to Screenshot */}
      <div className="v2-sidebar-brand">
        <div className="v2-brand-icon">
          P
        </div>
        <div className="v2-brand-text">
          <span className="v2-brand-name">PORTAL</span>
          <span className="v2-brand-subtitle">ASPARTECH SYSTEM</span>
        </div>
      </div>

      {/* Tenant Card Exact Match */}
      <div className="px-2 pt-2 pb-1">
        <div className="p-2 rounded-2 d-flex align-items-center gap-2" style={{ background: 'rgba(255, 255, 255, 0.05)', border: '1px solid rgba(255, 255, 255, 0.08)' }}>
          <div className="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style={{ width: '28px', height: '28px', fontSize: '0.75rem', flexShrink: 0 }}>
            <Building size={14} />
          </div>
          <div className="overflow-hidden">
            <div className="text-white text-truncate fw-bold" style={{ fontSize: '0.75rem' }}>
              {user.tenant_name || user.name || 'Ruang Seragam'}
            </div>
            <span className="badge bg-primary text-uppercase" style={{ fontSize: '0.6rem', padding: '0.1rem 0.35rem' }}>
              {user.role || 'ADMIN'}
            </span>
          </div>
        </div>
      </div>

      {/* Navigation List Exact Hierarchy */}
      <div className="v2-sidebar-nav">
        {/* Main Menu */}
        <div className="v2-nav-section-title">MAIN MENU</div>
        <div className="v2-nav-item">
          <NavLink to="/" className={({ isActive }) => `v2-nav-link ${isActive ? 'active' : ''}`}>
            <div className="v2-nav-link-content">
              <Grid size={15} />
              <span>Dashboard</span>
            </div>
          </NavLink>
        </div>

        {/* Data Master */}
        <div className="v2-nav-section-title">DATA MASTER</div>
        <div className="v2-nav-item">
          <NavLink to="/produk" className={({ isActive }) => `v2-nav-link ${isActive ? 'active' : ''}`}>
            <div className="v2-nav-link-content">
              <Database size={15} />
              <span>Data Produk</span>
            </div>
            <ChevronRight size={13} className="text-secondary opacity-75" />
          </NavLink>
        </div>

        {/* Marketplace & Sales */}
        <div className="v2-nav-section-title">MARKETPLACE & SALES</div>
        <div className="v2-nav-item">
          <a href="/orders" className="v2-nav-link">
            <div className="v2-nav-link-content">
              <ShoppingCart size={15} />
              <span>Pesanan Marketplace</span>
            </div>
            <ChevronRight size={13} className="text-secondary opacity-75" />
          </a>
        </div>
        <div className="v2-nav-item">
          <a href="/stores" className="v2-nav-link">
            <div className="v2-nav-link-content">
              <Store size={15} />
              <span>Toko Marketplace</span>
            </div>
            <ChevronRight size={13} className="text-secondary opacity-75" />
          </a>
        </div>

        {/* Laporan & Rekap */}
        <div className="v2-nav-section-title">LAPORAN & REKAP</div>
        <div className="v2-nav-item">
          <a href="/reports" className="v2-nav-link">
            <div className="v2-nav-link-content">
              <FileText size={15} />
              <span>Laporan Penjualan</span>
            </div>
            <ChevronRight size={13} className="text-secondary opacity-75" />
          </a>
        </div>

        {/* Sistem */}
        <div className="v2-nav-section-title">SISTEM</div>
        <div className="v2-nav-item">
          <a href="/dashboard" className="v2-nav-link text-warning">
            <div className="v2-nav-link-content">
              <ArrowLeftCircle size={15} />
              <span>Kembali ke ERP V1</span>
            </div>
          </a>
        </div>
      </div>

      {/* Sidebar Footer */}
      <div className="v2-sidebar-footer">
        <div className="d-flex align-items-center gap-2">
          <div className="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style={{ width: '28px', height: '28px', fontSize: '0.75rem', flexShrink: 0 }}>
            {initials}
          </div>
          <div className="overflow-hidden">
            <div className="text-white fw-semibold text-truncate" style={{ fontSize: '0.75rem' }}>{user.name || 'User'}</div>
            <div className="text-muted text-truncate" style={{ fontSize: '0.68rem' }}>{user.email || ''}</div>
          </div>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;
