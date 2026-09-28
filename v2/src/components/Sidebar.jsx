import React from 'react';
import { NavLink } from 'react-router-dom';
import { Grid, Box, ShoppingCart, Store, ArrowLeftCircle, Building } from 'lucide-react';

const Sidebar = () => {
  return (
    <aside className="v2-sidebar">
      {/* Brand Header */}
      <div class="v2-sidebar-brand">
        <div class="v2-brand-icon">
          <Grid size={18} />
        </div>
        <div class="v2-brand-text">
          <span class="v2-brand-name">PORTAL</span>
          <span class="v2-brand-subtitle">ASPARTECH SYSTEM (V2 REACT)</span>
        </div>
      </div>

      {/* Tenant Badge Card */}
      <div className="px-2 pt-2">
        <div className="p-2 rounded-2 d-flex align-items-center gap-2" style={{ background: 'rgba(255, 255, 255, 0.05)', border: '1px solid rgba(255, 255, 255, 0.08)' }}>
          <div className="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style={{ width: '26px', height: '26px', fontSize: '0.7rem' }}>
            <Building size={14} />
          </div>
          <div className="overflow-hidden">
            <div className="text-white text-truncate fw-bold" style={{ fontSize: '0.75rem' }}>Ruang Seragam</div>
            <span className="badge bg-primary text-uppercase" style={{ fontSize: '0.6rem', padding: '0.1rem 0.35rem' }}>
              ADMIN
            </span>
          </div>
        </div>
      </div>

      {/* Navigation List */}
      <div className="v2-sidebar-nav">
        <div className="v2-nav-section-title">MAIN MENU</div>
        <div className="v2-nav-item">
          <NavLink to="/" className={({ isActive }) => `v2-nav-link ${isActive ? 'active' : ''}`}>
            <Grid size={16} />
            <span>Dashboard</span>
          </NavLink>
        </div>

        <div className="v2-nav-section-title">DATA MASTER</div>
        <div className="v2-nav-item">
          <NavLink to="/v2/produk" className={({ isActive }) => `v2-nav-link ${isActive ? 'active' : ''}`}>
            <Box size={16} />
            <span>Data Produk</span>
          </NavLink>
        </div>

        <div className="v2-nav-section-title">MARKETPLACE & SALES</div>
        <div className="v2-nav-item">
          <a href="/orders" className="v2-nav-link">
            <ShoppingCart size={16} />
            <span>Pesanan Marketplace</span>
          </a>
        </div>
        <div className="v2-nav-item">
          <a href="/stores" className="v2-nav-link">
            <Store size={16} />
            <span>Toko Marketplace</span>
          </a>
        </div>

        <div className="v2-nav-section-title">SISTEM & MODE</div>
        <div className="v2-nav-item">
          <a href="/dashboard" className="v2-nav-link text-warning">
            <ArrowLeftCircle size={16} />
            <span>Kembali ke ERP V1</span>
          </a>
        </div>
      </div>

      {/* Sidebar Footer */}
      <div className="v2-sidebar-footer">
        <div className="d-flex align-items-center gap-2">
          <div className="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style={{ width: '28px', height: '28px', fontSize: '0.75rem' }}>
            RS
          </div>
          <div className="overflow-hidden">
            <div className="text-white fw-semibold" style={{ fontSize: '0.78rem' }}>Ruang Seragam Admin</div>
            <div className="text-muted" style={{ fontSize: '0.68rem' }}>admin@ruangseragam.com</div>
          </div>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;
