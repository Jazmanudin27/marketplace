import React, { useState, useEffect } from 'react';
import Header from './components/layout/Header';
import SubPageHeader from './components/layout/SubPageHeader';
import BottomNav from './components/layout/BottomNav';
import DashboardPage from './pages/DashboardPage';
import OrdersPage from './pages/OrdersPage';
import AnalyticsPage from './pages/AnalyticsPage';
import FinancePage from './pages/FinancePage';
import TargetKomisiPage from './pages/TargetKomisiPage';
import LabaRugiPage from './pages/LabaRugiPage';
import MutasiKeuanganPage from './pages/MutasiKeuanganPage';
import SaldoMarketplacePage from './pages/SaldoMarketplacePage';
import LaporanPenjualanPage from './pages/LaporanPenjualanPage';
import ReturMasukPage from './pages/ReturMasukPage';
import ScanKemasanPage from './pages/ScanKemasanPage';
import ScanSpkPage from './pages/ScanSpkPage';
import ProfilePage from './pages/ProfilePage';
import LoginPage from './pages/LoginPage';
import SupplierPortal from './pages/supplier/SupplierPortal';
import TitipanBarangPage from './pages/TitipanBarangPage';
import StockCardPage from './pages/StockCardPage';
import PaymentHistoryPage from './pages/PaymentHistoryPage';
import SalesHistoryPage from './pages/SalesHistoryPage';
import PenjualanOfflinePage from './pages/PenjualanOfflinePage';
import OrderDetailModal from './components/orders/OrderDetailModal';
import AllModulesModal from './components/dashboard/AllModulesModal';
import FinanceMetricDetailModal from './components/dashboard/FinanceMetricDetailModal';
import NotificationModal from './components/dashboard/NotificationModal';
import { showAlert, showToast, showConfirm } from './utils/alerts';
import { fetchOrders } from './api/ordersApi';
import { fetchOwnerMetrics } from './api/metricsApi';
import { getCurrentOwnerUser, logoutOwner } from './api/authApi';
import { initialOwnerProfile, initialOwnerMetrics, initialOrders } from './api/mockData';

export default function App() {
  const [currentUser, setCurrentUser] = useState(() => getCurrentOwnerUser());
  // Persist activeTab: cek URL parameter, hash, atau localStorage, fallback ke 'home'
  const [activeTab, setActiveTab] = useState(() => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const urlTab = urlParams.get('tab') || window.location.hash.replace('#', '');
      if (urlTab) return urlTab;

      const cached = localStorage.getItem('owner_active_tab');
      if (cached) return cached;
    } catch (_) {}
    return 'home';
  });

  const handleTabChange = (tab) => {
    setActiveTab(tab);
    try {
      localStorage.setItem('owner_active_tab', tab);
      const url = new URL(window.location.href);
      if (tab === 'home') {
        url.searchParams.delete('tab');
      } else {
        url.searchParams.set('tab', tab);
      }
      window.history.replaceState({}, '', url.toString());
    } catch (_) {}
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  useEffect(() => {
    const handlePopState = () => {
      try {
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab') || window.location.hash.replace('#', '');
        const target = urlTab || localStorage.getItem('owner_active_tab') || 'home';
        setActiveTab(target);
      } catch (_) {}
    };
    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);

  useEffect(() => {
    try {
      const url = new URL(window.location.href);
      if (activeTab && activeTab !== 'home') {
        url.searchParams.set('tab', activeTab);
        window.history.replaceState({}, '', url.toString());
      }
    } catch (_) {}
  }, []);
  const [profile, setProfile] = useState(() => {
    try {
      const cached = localStorage.getItem('owner_profile_cache');
      const parsed = cached ? JSON.parse(cached) : initialOwnerProfile;
      const cur = getCurrentOwnerUser();
      if (cur?.name) {
        parsed.name = cur.name;
        parsed.avatarInitials = cur.name.split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
      }
      return parsed;
    } catch {
      return initialOwnerProfile;
    }
  });
  const [metrics, setMetrics] = useState(() => {
    try {
      const cached = localStorage.getItem('owner_metrics_cache');
      return cached ? JSON.parse(cached) : initialOwnerMetrics;
    } catch {
      return initialOwnerMetrics;
    }
  });
  const [orders, setOrders] = useState(() => {
    try {
      const cached = localStorage.getItem('owner_orders_cache');
      return cached ? JSON.parse(cached) : initialOrders;
    } catch {
      return initialOrders;
    }
  });
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [selectedFinanceMetric, setSelectedFinanceMetric] = useState(null);
  const [ordersFilter, setOrdersFilter] = useState('ALL');
  const [isAllModulesOpen, setIsAllModulesOpen] = useState(false);
  const [isNotifOpen, setIsNotifOpen] = useState(false);
  const [readNotifIds, setReadNotifIds] = useState(() => {
    try {
      const saved = localStorage.getItem('owner_read_notif_ids');
      return saved ? JSON.parse(saved) : [];
    } catch {
      return [];
    }
  });

  const [isDataLoading, setIsDataLoading] = useState(() => {
    // If no cached metrics exist yet, start with loading true so user sees shimmer instead of 0
    return !localStorage.getItem('owner_metrics_cache');
  });

  // Filter pesanan yang BELUM dikirim / harus dikirim dan belum diproses
  const pendingOrders = (orders || []).filter((o) => {
    const s = String(o.status || '').toUpperCase();
    return s === 'READY_TO_SHIP' || s === 'SIAP_KIRIM' || s === 'NEW' || s === 'UNPAID' || s === 'IN_PROCESS';
  });

  // Unread count: hanya pending orders yang belum pernah dilihat
  const unreadPendingCount = pendingOrders.filter(o => !readNotifIds.includes(o.id)).length;

  // Synchronize profile with logged-in user if available
  useEffect(() => {
    if (currentUser?.name) {
      const initials = currentUser.name.split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
      setProfile((prev) => ({
        ...prev,
        name: currentUser.name,
        role: currentUser.role ? `Owner (${currentUser.role})` : prev.role,
        tenantName: currentUser.tenantName || currentUser.tenant_name || prev.tenantName,
        avatarInitials: initials,
        email: currentUser.email || prev.email,
      }));
    }
  }, [currentUser]);

  const [orderCounts, setOrderCounts] = useState(() => {
    try {
      const cached = localStorage.getItem('owner_order_counts_cache');
      return cached ? JSON.parse(cached) : { tabCounts: {}, processCounts: {} };
    } catch {
      return { tabCounts: {}, processCounts: {} };
    }
  });

  // Load API data on mount if logged in (Instant Cache-Then-Network)
  useEffect(() => {
    if (!currentUser) return;

    async function loadData() {
      setIsDataLoading(true);
      try {
        const [metricRes, ordersRes] = await Promise.all([
          fetchOwnerMetrics(),
          fetchOrders({ status: 'ALL' })
        ]);
        if (metricRes?.data) {
          setMetrics(metricRes.data);
          localStorage.setItem('owner_metrics_cache', JSON.stringify(metricRes.data));
        }
        if (metricRes?.profile) {
          const resolvedName = currentUser?.name || metricRes.profile.name;
          const initials = (resolvedName || 'AD').split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
          setProfile(prev => ({ 
            ...prev, 
            ...metricRes.profile,
            name: resolvedName,
            avatarInitials: initials,
            role: currentUser?.role ? `Owner (${currentUser.role})` : (metricRes.profile.role || prev.role),
            email: currentUser?.email || metricRes.profile.email,
          }));
          localStorage.setItem('owner_profile_cache', JSON.stringify({
            ...metricRes.profile,
            name: resolvedName,
            avatarInitials: initials,
          }));
        }
        if (ordersRes?.data) {
          setOrders(ordersRes.data);
          localStorage.setItem('owner_orders_cache', JSON.stringify(ordersRes.data));
        }
        if (ordersRes?.tabCounts) {
          const countsObj = {
            tabCounts: ordersRes.tabCounts,
            processCounts: ordersRes.processCounts || { to_process: 0, processed: 0 }
          };
          setOrderCounts(countsObj);
          localStorage.setItem('owner_order_counts_cache', JSON.stringify(countsObj));
        }
      } catch (err) {
        console.warn('API fetch error:', err);
      } finally {
        setIsDataLoading(false);
      }
    }
    loadData();
  }, [currentUser]);

  const handleLoginSuccess = (user) => {
    setCurrentUser(user);
    setActiveTab('home');
    try {
      localStorage.setItem('owner_active_tab', 'home');
    } catch (_) {}
  };

  const handleOpenOrders = (filter = 'ALL') => {
    setOrdersFilter(filter);
    handleTabChange('orders');
  };

  const handleOpenFinance = () => {
    handleTabChange('finance');
  };

  const handleScanClick = () => {
    handleTabChange('scan_kemasan');
  };

  const handleOpenNotif = () => {
    setIsNotifOpen(true);
    // Saat notif dibuka/dilihat, tandai semua pending order saat ini sudah dibaca
    const currentPendingIds = pendingOrders.map(o => o.id);
    const updated = Array.from(new Set([...readNotifIds, ...currentPendingIds]));
    setReadNotifIds(updated);
    try {
      localStorage.setItem('owner_read_notif_ids', JSON.stringify(updated));
    } catch (e) {
      console.warn('Failed saving read notifs:', e);
    }
  };

  const handleLogout = async () => {
    const res = await showConfirm({
      icon: 'question',
      title: 'Keluar dari Portal?',
      text: 'Apakah Anda yakin ingin keluar dari Portal Owner?',
      confirmButtonText: 'Ya, Keluar',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#94a3b8'
    });

    if (res.isConfirmed) {
      logoutOwner();
      setCurrentUser(null);
      setActiveTab('home');
      try {
        localStorage.removeItem('owner_active_tab');
      } catch (_) {}
      showToast('success', 'Berhasil keluar');
    }
  };

  // If not authenticated, render Mobile Login Page
  if (!currentUser) {
    return (
      <div className="mobile-app-wrapper">
        <div className="mobile-screen" style={{ paddingBottom: 0 }}>
          <LoginPage onLoginSuccess={handleLoginSuccess} />
        </div>
      </div>
    );
  }

  // KHUSUS ROLE SUPPLIER: Render Halaman & Menu Khusus Supplier (File Terpisah)
  const userRole = String(currentUser?.role || '').toLowerCase();
  const isSupplierRole = userRole.includes('supplier');

  if (isSupplierRole) {
    return (
      <SupplierPortal 
        currentUser={currentUser}
        onLogout={handleLogout}
      />
    );
  }

  return (
    <div className="mobile-app-wrapper">
      <div className="mobile-screen">
        {/* Top Header: Beranda Full Header, Sub-Halaman Gunakan Header Biasa dg Tombol Back & Radius Bottom */}
        {activeTab === 'home' ? (
          <Header 
            profile={profile}
            unreadCount={unreadPendingCount}
            onOpenNotif={handleOpenNotif}
            onLogout={handleLogout}
          />
        ) : activeTab === 'orders' ? (
          <SubPageHeader 
            title="Pesanan Masuk" 
            subtitle="Monitor & kelola semua pesanan marketplace real-time"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'analytics' ? (
          <SubPageHeader 
            title="Grafik & Analisis" 
            subtitle="Performa omset, margin & channel"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'finance' ? (
          <SubPageHeader 
            title="Mutasi Keuangan" 
            subtitle="Buku mutasi kas masuk, keluar & transfer dana"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'target_komisi' ? (
          <SubPageHeader 
            title="Target & Komisi Penjualan V2" 
            subtitle="Target margin & realisasi komisi tim marketing ERP"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'laba_rugi' ? (
          <SubPageHeader 
            title="Laba Rugi" 
            subtitle="Laporan keuangan & profitabilitas real ERP"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'mutasi_keuangan' ? (
          <SubPageHeader 
            title="Mutasi Keuangan" 
            subtitle="Buku mutasi kas masuk, keluar & transfer dana"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'saldo_marketplace' ? (
          <SubPageHeader 
            title="Saldo Marketplace" 
            subtitle="Saldo dompet, tertahan (escrow) & mutasi dompet toko"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'penjualan_offline' ? (
          <SubPageHeader 
            title="Penjualan Offline" 
            subtitle="Transaksi kasir POS toko fisik & pre-order SPK"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'laporan_penjualan' ? (
          <SubPageHeader 
            title="Laporan Penjualan" 
            subtitle="Rekap omset, HPP, fee marketplace & margin bersih"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'retur_masuk' ? (
          <SubPageHeader 
            title="Retur Masuk" 
            subtitle="Data retur marketplace & QC gudang V2"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'scan_kemasan' ? (
          <SubPageHeader 
            title="Scan & Kemasan" 
            subtitle="Pemindai barcode kemasan, update stok & request produksi"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'scan_spk' ? (
          <SubPageHeader 
            title="Scan SPK Produksi" 
            subtitle="Pemindai QR SPK & penerimaan hasil jahit gudang"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'titipan_barang' ? (
          <SubPageHeader 
            title="Titipan Barang (Konsinyasi)" 
            subtitle="Penerimaan barang konsinyasi supplier & stok master gudang"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'kartu_stok' ? (
          <SubPageHeader 
            title="Kartu Stok Gudang Jadi" 
            subtitle="Monitoring stok fisik barang jadi & riwayat mutasi gudang"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'pembayaran_titipan' ? (
          <SubPageHeader 
            title="Histori Pembayaran Titipan" 
            subtitle="Rekapitulasi transfer & pelunasan hasil penjualan titipan"
            onBack={() => handleTabChange('titipan_barang')} 
          />
        ) : activeTab === 'penjualan_titipan' ? (
          <SubPageHeader 
            title="Histori Penjualan Titipan" 
            subtitle="Rincian pesanan marketplace dari barang titipan konsinyasi"
            onBack={() => handleTabChange('titipan_barang')} 
          />
        ) : activeTab === 'profile' ? (
          <SubPageHeader 
            title="Profile" 
            subtitle="Pengaturan akun & kredensial login"
            onBack={() => handleTabChange('home')} 
          />
        ) : null}

        {/* Tab Content with Fluid Transition */}
        <div key={activeTab} className="animated-tab-container">
          {activeTab === 'home' && (
            <DashboardPage 
              metrics={metrics}
              orders={orders}
              isLoading={isDataLoading}
              onSelectOrder={(order) => setSelectedOrder(order)}
              onViewAllOrders={handleOpenOrders}
              onOpenFinance={handleOpenFinance}
              onOpenTarget={() => handleTabChange('target_komisi')}
              onSelectFinanceMetric={(type) => setSelectedFinanceMetric(type)}
              onOpenAnalytics={() => handleTabChange('analytics')}
              onOpenAllModules={() => setIsAllModulesOpen(true)}
              onSelectMenu={(menuId) => {
                if (menuId === 'orders') handleOpenOrders('ALL');
                else if (menuId === 'penjualan_offline' || menuId === 'offline_sales' || menuId === 'pos') handleTabChange('penjualan_offline');
                else if (menuId === 'returns' || menuId === 'retur_masuk') handleTabChange('retur_masuk');
                else if (menuId === 'analytics') handleTabChange('analytics');
                else if (menuId === 'target' || menuId === 'target_komisi') handleTabChange('target_komisi');
                else if (menuId === 'margin' || menuId === 'laba_rugi') handleTabChange('laba_rugi');
                else if (menuId === 'mutasi' || menuId === 'mutasi_keuangan' || menuId === 'cashflow') handleTabChange('mutasi_keuangan');
                else if (menuId === 'saldo_marketplace' || menuId === 'marketplace_wallets') handleTabChange('saldo_marketplace');
                else if (menuId === 'laporan_penjualan' || menuId === 'reports') handleTabChange('laporan_penjualan');
                else if (menuId === 'scan_kemasan' || menuId === 'scanner') handleTabChange('scan_kemasan');
                else if (menuId === 'scan_spk' || menuId === 'spks') handleTabChange('scan_spk');
                else if (menuId === 'titipan_barang' || menuId === 'supplier_consignments' || menuId === 'consignments') handleTabChange('titipan_barang');
                else if (menuId === 'kartu_stok' || menuId === 'stock_card') handleTabChange('kartu_stok');
                else if (menuId === 'pembayaran_titipan' || menuId === 'settlements') handleTabChange('pembayaran_titipan');
                else if (menuId === 'penjualan_titipan' || menuId === 'sales_history') handleTabChange('penjualan_titipan');
                else if (menuId === 'settings' || menuId === 'stores' || menuId === 'company' || menuId === 'users') handleTabChange('profile');
                else if (menuId === 'all_modules') setIsAllModulesOpen(true);
                else {
                  showAlert({
                    icon: 'info',
                    title: `Modul ERP`,
                    text: `Modul "${menuId}" aktif. Membuka data sinkronisasi ERP.`
                  });
                }
              }}
            />
          )}

          {activeTab === 'orders' && (
            <OrdersPage 
              orders={orders}
              orderCounts={orderCounts}
              isLoading={isDataLoading}
              initialFilter={ordersFilter}
              onSelectOrder={(order) => setSelectedOrder(order)}
            />
          )}

          {activeTab === 'analytics' && (
            <AnalyticsPage 
              metrics={metrics}
              orders={orders}
            />
          )}

          {activeTab === 'finance' && (
            <FinancePage 
              metrics={metrics}
              onOpenTargetKomisi={() => handleTabChange('target_komisi')}
            />
          )}

          {activeTab === 'target_komisi' && (
            <TargetKomisiPage 
              initialMetrics={metrics}
            />
          )}

          {activeTab === 'mutasi_keuangan' && (
            <MutasiKeuanganPage />
          )}

          {activeTab === 'saldo_marketplace' && (
            <SaldoMarketplacePage />
          )}

          {activeTab === 'laporan_penjualan' && (
            <LaporanPenjualanPage 
              onSelectOrder={(order) => setSelectedOrder(order)}
            />
          )}

          {activeTab === 'retur_masuk' && (
            <ReturMasukPage />
          )}

          {activeTab === 'penjualan_offline' && (
            <PenjualanOfflinePage 
              onOpenScanKemasan={() => handleTabChange('scan_kemasan')}
              onOpenScanSpk={() => handleTabChange('scan_spk')}
            />
          )}

          {activeTab === 'scan_kemasan' && (
            <ScanKemasanPage />
          )}

          {activeTab === 'scan_spk' && (
            <ScanSpkPage />
          )}

          {activeTab === 'laba_rugi' && (
            <LabaRugiPage />
          )}

          {activeTab === 'titipan_barang' && (
            <TitipanBarangPage 
              currentOwnerUser={currentUser}
              onOpenStockCard={() => handleTabChange('kartu_stok')}
              onOpenPayments={() => handleTabChange('pembayaran_titipan')}
              onOpenSales={() => handleTabChange('penjualan_titipan')}
            />
          )}

          {activeTab === 'kartu_stok' && (
            <StockCardPage 
              onBack={() => handleTabChange('home')}
            />
          )}

          {activeTab === 'pembayaran_titipan' && (
            <PaymentHistoryPage 
              currentOwnerUser={currentUser}
              onOpenTitipan={() => handleTabChange('titipan_barang')}
              onOpenStockCard={() => handleTabChange('kartu_stok')}
              onOpenPayments={() => handleTabChange('pembayaran_titipan')}
              onOpenSales={() => handleTabChange('penjualan_titipan')}
            />
          )}

          {activeTab === 'penjualan_titipan' && (
            <SalesHistoryPage 
              currentOwnerUser={currentUser}
              onOpenTitipan={() => handleTabChange('titipan_barang')}
              onOpenStockCard={() => handleTabChange('kartu_stok')}
              onOpenPayments={() => handleTabChange('pembayaran_titipan')}
              onOpenSales={() => handleTabChange('penjualan_titipan')}
            />
          )}

          {activeTab === 'profile' && (
            <ProfilePage 
              profile={profile}
              onLogout={handleLogout}
              onProfileUpdate={(updatedUser) => {
                if (updatedUser?.name) {
                  setProfile(prev => ({
                    ...prev,
                    name: updatedUser.name,
                    email: updatedUser.email || prev.email,
                    avatarInitials: updatedUser.name.split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase()
                  }));
                }
              }}
            />
          )}
        </div>

        {/* Floating Bottom Navigation Bar */}
        <BottomNav 
          activeTab={activeTab}
          onTabChange={handleTabChange}
          onScanClick={handleScanClick}
        />

        {/* Financial Metric Breakdown Modal (Penjualan, Admin, Refund, HPP, Margin) */}
        {selectedFinanceMetric && (
          <FinanceMetricDetailModal 
            metricType={selectedFinanceMetric}
            metrics={metrics}
            onClose={() => setSelectedFinanceMetric(null)}
            onSelectOrder={(order) => setSelectedOrder(order)}
            onOpenOrders={(filter) => {
              setSelectedFinanceMetric(null);
              handleOpenOrders(filter);
            }}
          />
        )}

        {selectedOrder && (
          <OrderDetailModal 
            order={selectedOrder}
            onClose={() => setSelectedOrder(null)}
          />
        )}

        {/* Notification Modal (Maks 5 Pesanan Belum Diproses) */}
        <NotificationModal 
          isOpen={isNotifOpen}
          orders={orders}
          onClose={() => setIsNotifOpen(false)}
          onSelectOrder={(order) => setSelectedOrder(order)}
          onViewAllOrders={(filter) => handleOpenOrders(filter || 'READY_TO_SHIP')}
        />

        {/* All Modules ERP Catalog Drawer */}
        <AllModulesModal 
          isOpen={isAllModulesOpen}
          onClose={() => setIsAllModulesOpen(false)}
          onSelectAction={(actionId) => {
            if (actionId === 'orders') handleOpenOrders('ALL');
            else if (actionId === 'returns' || actionId === 'retur_masuk') handleTabChange('retur_masuk');
            else if (actionId === 'target') handleTabChange('target_komisi');
            else if (actionId === 'margin') handleTabChange('laba_rugi');
            else if (actionId === 'cashflow' || actionId === 'marketplace_wallets' || actionId === 'hutang_supplier') handleOpenFinance();
            else if (actionId === 'scan_kemasan' || actionId === 'scanner') handleTabChange('scan_kemasan');
            else if (actionId === 'scan_spk' || actionId === 'spks') handleTabChange('scan_spk');
            else if (actionId === 'titipan_barang' || actionId === 'supplier_consignments') handleTabChange('titipan_barang');
            else if (actionId === 'kartu_stok' || actionId === 'stock_card') handleTabChange('kartu_stok');
            else if (actionId === 'pembayaran_titipan' || actionId === 'settlements') handleTabChange('pembayaran_titipan');
            else if (actionId === 'penjualan_titipan' || actionId === 'sales_history') handleTabChange('penjualan_titipan');
            else if (actionId === 'stores' || actionId === 'users' || actionId === 'company') handleTabChange('profile');
            else {
              showAlert({
                icon: 'info',
                title: 'Modul ERP',
                text: `Modul "${actionId}" terpilih. Sedang sinkronisasi data ERP.`
              });
            }
          }}
        />
      </div>
    </div>
  );
}
