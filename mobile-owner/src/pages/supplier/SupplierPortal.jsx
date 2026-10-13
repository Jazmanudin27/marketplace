import React, { useState } from 'react';
import SupplierHeader from './SupplierHeader';
import SupplierBottomNav from './SupplierBottomNav';
import SupplierTitipanBarangPage from './SupplierTitipanBarangPage';
import SupplierStockCardPage from './SupplierStockCardPage';
import SupplierPaymentHistoryPage from './SupplierPaymentHistoryPage';
import SupplierSalesHistoryPage from './SupplierSalesHistoryPage';
import SupplierProfilePage from './SupplierProfilePage';

export default function SupplierPortal({ currentUser, onLogout }) {
  // Persist activeTab: cek URL parameter atau localStorage, fallback ke 'titipan'
  const [activeTab, setActiveTab] = useState(() => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const urlTab = urlParams.get('tab') || window.location.hash.replace('#', '');
      if (urlTab) return urlTab;
      const cached = localStorage.getItem('supplier_active_tab');
      if (cached) return cached;
    } catch (_) {}
    return 'titipan';
  });

  const handleTabChange = (tab) => {
    setActiveTab(tab);
    try {
      localStorage.setItem('supplier_active_tab', tab);
      const url = new URL(window.location.href);
      if (tab === 'titipan') {
        url.searchParams.delete('tab');
      } else {
        url.searchParams.set('tab', tab);
      }
      window.history.replaceState({}, '', url.toString());
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

          {activeTab === 'pembayaran' && (
            <SupplierPaymentHistoryPage
              currentSupplierUser={currentUser}
            />
          )}

          {activeTab === 'penjualan' && (
            <SupplierSalesHistoryPage
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

        {/* Bottom Nav Khusus Supplier (Titipan, Stok, Pembayaran, Penjualan, Akun) */}
        <SupplierBottomNav
          activeTab={activeTab}
          onTabChange={handleTabChange}
        />
      </div>
    </div>
  );
}
