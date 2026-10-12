import React, { useState } from 'react';
import SupplierHeader from './SupplierHeader';
import SupplierBottomNav from './SupplierBottomNav';
import SupplierTitipanBarangPage from './SupplierTitipanBarangPage';
import SupplierStockCardPage from './SupplierStockCardPage';
import SupplierProfilePage from './SupplierProfilePage';

export default function SupplierPortal({ currentUser, onLogout }) {
  const [activeTab, setActiveTab] = useState(() => {
    try {
      const saved = localStorage.getItem('supplier_active_tab');
      return saved || 'titipan';
    } catch {
      return 'titipan';
    }
  });

  const handleTabChange = (tab) => {
    setActiveTab(tab);
    try {
      localStorage.setItem('supplier_active_tab', tab);
    } catch (_) {}
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="mobile-app-wrapper">
      <div className="mobile-screen" style={{ minHeight: '100vh', background: '#f8fafc' }}>
        {/* Top Header Khusus Supplier */}
        <SupplierHeader
          user={currentUser}
          onLogout={onLogout}
        />

        {/* Dynamic Tab Body */}
        <div key={activeTab} className="animated-tab-container">
          {activeTab === 'titipan' && (
            <SupplierTitipanBarangPage
              currentSupplierUser={currentUser}
              onOpenStockCard={() => handleTabChange('kartu_stok')}
            />
          )}

          {activeTab === 'kartu_stok' && (
            <SupplierStockCardPage
              currentSupplierUser={currentUser}
            />
          )}

          {activeTab === 'profile' && (
            <SupplierProfilePage
              currentUser={currentUser}
              onLogout={onLogout}
            />
          )}
        </div>

        {/* Bottom Nav Khusus Supplier (Hanya Titipan Barang, Kartu Stok & Akun) */}
        <SupplierBottomNav
          activeTab={activeTab}
          onTabChange={handleTabChange}
        />
      </div>
    </div>
  );
}
