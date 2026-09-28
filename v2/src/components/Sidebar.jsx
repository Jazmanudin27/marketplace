import React, { useState, useEffect } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useLayout } from '../context/LayoutContext';
import { 
  Grid, 
  Database, 
  ShoppingCart, 
  Store, 
  FileText, 
  Settings, 
  ArrowLeftCircle, 
  ChevronDown,
  Building,
  Box,
  Layers,
  Archive,
  RefreshCw,
  TrendingUp,
  X,
  ShieldCheck,
  CheckCircle2
} from 'lucide-react';

const Sidebar = () => {
  const { sidebarCollapsed, mobileSidebarOpen, closeMobileSidebar } = useLayout();
  const location = useLocation();

  // Accordion Dropdown States
  const [openMenus, setOpenMenus] = useState({
    dataMaster: true,
    marketplace: true,
    laporan: false,
    sistem: false
  });

  const [user, setUser] = useState({
    name: 'Ruang Seragam Admin',
    email: 'admin@ruangseragam.com',
    tenant_name: 'Ruang Seragam',
    role: 'ADMIN'
  });

  useEffect(() => {
    try {
      const savedUser = localStorage.getItem('v2_user');
      if (savedUser) {
        setUser(JSON.parse(savedUser));
      }
    } catch (e) {
      console.error(e);
    }
  }, []);

  // Auto-expand menu based on current route
  useEffect(() => {
    if (location.pathname.includes('/produk')) {
      setOpenMenus(prev => ({ ...prev, dataMaster: true }));
    }
  }, [location.pathname]);

  const toggleSubmenu = (menuKey) => {
    setOpenMenus(prev => ({
      ...prev,
      [menuKey]: !prev[menuKey]
    }));
  };

  const initials = (user.tenant_name || user.name || 'RS')
    .split(' ')
    .map(w => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();

  return (
    <aside className={`v2-sidebar ${sidebarCollapsed ? 'collapsed' : ''} ${mobileSidebarOpen ? 'show-mobile' : ''}`}>
      {/* Brand Header */}
      <div className="v2-sidebar-brand">
        <div className="v2-brand-icon">
          P
        </div>
        {!sidebarCollapsed && (
          <div className="v2-brand-text">
            <span className="v2-brand-name">PORTAL</span>
            <span className="v2-brand-subtitle">ASPARTECH SYSTEM</span>
          </div>
        )}
        {mobileSidebarOpen && (
          <button 
            type="button" 
            className="btn btn-sm btn-link text-white ms-auto d-lg-none p-1"
            onClick={closeMobileSidebar}
            title="Tutup Menu"
          >
            <X size={18} />
          </button>
        )}
      </div>

      {/* Tenant / Store Card */}
      {!sidebarCollapsed && (
        <div className="px-3 pt-2 pb-1">
          <div className="v2-tenant-card">
            <div className="v2-tenant-avatar">
              <Building size={14} />
            </div>
            <div className="overflow-hidden flex-1">
              <div className="text-white text-truncate fw-bold" style={{ fontSize: '0.78rem' }}>
                {user.tenant_name || user.name || 'Ruang Seragam'}
              </div>
              <div className="d-flex align-items-center gap-1 mt-0.5">
                <span className="badge bg-primary text-uppercase" style={{ fontSize: '0.58rem', padding: '0.15rem 0.4rem' }}>
                  {user.role || 'ADMIN'}
                </span>
                <span className="text-success d-inline-flex align-items-center gap-0.5" style={{ fontSize: '0.65rem' }}>
                  <CheckCircle2 size={10} /> Online
                </span>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Navigation List with Collapsible Dropdowns */}
      <div className="v2-sidebar-nav">
        {/* Main Menu */}
        <div className="v2-nav-section-title">
          {!sidebarCollapsed ? 'MAIN MENU' : '•'}
        </div>
        <div className="v2-nav-item">
          <NavLink 
            to="/" 
            className={({ isActive }) => `v2-nav-link ${isActive ? 'active' : ''}`}
            onClick={closeMobileSidebar}
          >
            <div className="v2-nav-link-content">
              <Grid size={16} />
              {!sidebarCollapsed && <span>Dashboard</span>}
            </div>
          </NavLink>
        </div>

        {/* 1. DATA MASTER (Collapsible Dropdown Accordion) */}
        <div className="v2-nav-section-title">
          {!sidebarCollapsed ? 'DATA MASTER' : '•'}
        </div>
        <div className="v2-nav-item">
          <button 
            type="button"
            className={`v2-nav-dropdown-btn ${openMenus.dataMaster ? 'open' : ''}`}
            onClick={() => toggleSubmenu('dataMaster')}
          >
            <div className="v2-nav-link-content">
              <Database size={16} />
              {!sidebarCollapsed && <span>Data Master</span>}
            </div>
            {!sidebarCollapsed && (
              <ChevronDown size={14} className={`v2-chevron ${openMenus.dataMaster ? 'rotate' : ''}`} />
            )}
          </button>

          {/* Submenu Dropdown Items */}
          {openMenus.dataMaster && !sidebarCollapsed && (
            <div className="v2-submenu">
              <NavLink 
                to="/produk" 
                className={({ isActive }) => `v2-submenu-link ${isActive ? 'active' : ''}`}
                onClick={closeMobileSidebar}
              >
                <Box size={13} className="me-2" />
                <span>Data Produk</span>
              </NavLink>
              <a 
                href="#kategori" 
                className="v2-submenu-link"
                onClick={(e) => { e.preventDefault(); }}
              >
                <Layers size={13} className="me-2" />
                <span>Kategori & Brand</span>
              </a>
              <a 
                href="#stok" 
                className="v2-submenu-link"
                onClick={(e) => { e.preventDefault(); }}
              >
                <Archive size={13} className="me-2" />
                <span>Stok & Gudang</span>
              </a>
            </div>
          )}
        </div>

        {/* 2. MARKETPLACE & SALES (Collapsible Dropdown Accordion) */}
        <div className="v2-nav-section-title">
          {!sidebarCollapsed ? 'MARKETPLACE & SALES' : '•'}
        </div>
        <div className="v2-nav-item">
          <button 
            type="button"
            className={`v2-nav-dropdown-btn ${openMenus.marketplace ? 'open' : ''}`}
            onClick={() => toggleSubmenu('marketplace')}
          >
            <div className="v2-nav-link-content">
              <ShoppingCart size={16} />
              {!sidebarCollapsed && <span>Marketplace & Toko</span>}
            </div>
            {!sidebarCollapsed && (
              <ChevronDown size={14} className={`v2-chevron ${openMenus.marketplace ? 'rotate' : ''}`} />
            )}
          </button>

          {/* Submenu Dropdown Items */}
          {openMenus.marketplace && !sidebarCollapsed && (
            <div className="v2-submenu">
              <a 
                href="/orders" 
                className="v2-submenu-link"
                onClick={closeMobileSidebar}
              >
                <ShoppingCart size={13} className="me-2" />
                <span className="flex-1">Pesanan Masuk</span>
                <span className="badge bg-danger rounded-pill px-1.5 py-0.5" style={{ fontSize: '0.62rem' }}>2</span>
              </a>
              <a 
                href="/stores" 
                className="v2-submenu-link"
                onClick={closeMobileSidebar}
              >
                <Store size={13} className="me-2" />
                <span className="flex-1">Toko Terhubung</span>
                <span className="badge bg-success rounded-pill px-1.5 py-0.5" style={{ fontSize: '0.62rem' }}>3 Toko</span>
              </a>
              <a 
                href="#sync" 
                className="v2-submenu-link"
                onClick={(e) => { e.preventDefault(); }}
              >
                <RefreshCw size={13} className="me-2" />
                <span>Sinkronisasi API</span>
              </a>
            </div>
          )}
        </div>

        {/* 3. LAPORAN & REKAP (Collapsible Dropdown Accordion) */}
        <div className="v2-nav-section-title">
          {!sidebarCollapsed ? 'LAPORAN & REKAP' : '•'}
        </div>
        <div className="v2-nav-item">
          <button 
            type="button"
            className={`v2-nav-dropdown-btn ${openMenus.laporan ? 'open' : ''}`}
            onClick={() => toggleSubmenu('laporan')}
          >
            <div className="v2-nav-link-content">
              <FileText size={16} />
              {!sidebarCollapsed && <span>Laporan & Rekap</span>}
            </div>
            {!sidebarCollapsed && (
              <ChevronDown size={14} className={`v2-chevron ${openMenus.laporan ? 'rotate' : ''}`} />
            )}
          </button>

          {/* Submenu Dropdown Items */}
          {openMenus.laporan && !sidebarCollapsed && (
            <div className="v2-submenu">
              <a 
                href="/reports" 
                className="v2-submenu-link"
                onClick={closeMobileSidebar}
              >
                <TrendingUp size={13} className="me-2" />
                <span>Laporan Penjualan</span>
              </a>
              <a 
                href="#omset" 
                className="v2-submenu-link"
                onClick={(e) => { e.preventDefault(); }}
              >
                <FileText size={13} className="me-2" />
                <span>Rekap Omset Bulanan</span>
              </a>
            </div>
          )}
        </div>

        {/* 4. SISTEM & INTEGRASI */}
        <div className="v2-nav-section-title">
          {!sidebarCollapsed ? 'SISTEM' : '•'}
        </div>
        <div className="v2-nav-item">
          <a href="/dashboard" className="v2-nav-link text-warning-emphasis">
            <div className="v2-nav-link-content">
              <ArrowLeftCircle size={16} className="text-warning" />
              {!sidebarCollapsed && <span className="text-warning">Kembali ke ERP V1</span>}
            </div>
          </a>
        </div>
      </div>

      {/* Sidebar Footer with Logged In User Info */}
      <div className="v2-sidebar-footer">
        <div className="d-flex align-items-center gap-2">
          <div className="v2-user-initials-badge">
            {initials}
          </div>
          {!sidebarCollapsed && (
            <div className="overflow-hidden flex-1">
              <div className="text-white fw-bold text-truncate" style={{ fontSize: '0.78rem' }}>
                {user.name || 'User'}
              </div>
              <div className="text-white-50 text-truncate" style={{ fontSize: '0.68rem' }}>
                {user.email || ''}
              </div>
            </div>
          )}
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;
