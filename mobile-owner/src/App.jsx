import React, { useState, useEffect } from 'react';
import Header from './components/layout/Header';
import SubPageHeader from './components/layout/SubPageHeader';
import BottomNav from './components/layout/BottomNav';
import DashboardPage from './pages/DashboardPage';
import OrdersPage from './pages/OrdersPage';
import AnalyticsPage from './pages/AnalyticsPage';
import FinancePage from './pages/FinancePage';
import ProfilePage from './pages/ProfilePage';
import LoginPage from './pages/LoginPage';
import OrderDetailModal from './components/orders/OrderDetailModal';
import AllModulesModal from './components/dashboard/AllModulesModal';
import FinanceMetricDetailModal from './components/dashboard/FinanceMetricDetailModal';
import NotificationModal from './components/dashboard/NotificationModal';
import { fetchOrders } from './api/ordersApi';
import { fetchOwnerMetrics } from './api/metricsApi';
import { getCurrentOwnerUser, logoutOwner } from './api/authApi';
import { initialOwnerProfile, initialOwnerMetrics, initialOrders } from './api/mockData';

export default function App() {
  const [currentUser, setCurrentUser] = useState(() => getCurrentOwnerUser());
  const [activeTab, setActiveTab] = useState(() => {
    try {
      const savedTab = localStorage.getItem('owner_active_tab');
      return savedTab || 'home';
    } catch {
      return 'home';
    }
  });

  const handleTabChange = (tab) => {
    setActiveTab(tab);
    localStorage.setItem('owner_active_tab', tab);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  const [profile, setProfile] = useState(() => {
    try {
      const cached = localStorage.getItem('owner_profile_cache');
      return cached ? JSON.parse(cached) : initialOwnerProfile;
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
    if (currentUser) {
      setProfile((prev) => ({
        ...prev,
        name: currentUser.name || prev.name,
        role: currentUser.role ? `Owner (${currentUser.role})` : prev.role,
        tenantName: currentUser.tenant_name || prev.tenantName,
        avatarInitials: currentUser.avatar_initials || prev.avatarInitials,
        email: currentUser.email || prev.email,
      }));
    }
  }, [currentUser]);

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
          setProfile(prev => ({ ...prev, ...metricRes.profile }));
          localStorage.setItem('owner_profile_cache', JSON.stringify(metricRes.profile));
        }
        if (ordersRes?.data) {
          setOrders(ordersRes.data);
          localStorage.setItem('owner_orders_cache', JSON.stringify(ordersRes.data));
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
  };

  const handleOpenOrders = (filter = 'ALL') => {
    setOrdersFilter(filter);
    handleTabChange('orders');
  };

  const handleOpenFinance = () => {
    handleTabChange('finance');
  };

  const handleScanClick = () => {
    alert('📷 Fitur Scanner Barcode Gudang V2 aktif. Arahkan kamera ke barcode pesanan pick & pack.');
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

  const handleLogout = () => {
    if (confirm('Apakah Anda yakin ingin keluar dari Portal Owner?')) {
      logoutOwner();
      setCurrentUser(null);
      handleTabChange('home');
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
            title="Daftar Order" 
            subtitle="Semua transaksi marketplace & POS"
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
            title="Keuangan & Kas" 
            subtitle="Ringkasan kas, target & laba rugi"
            onBack={() => handleTabChange('home')} 
          />
        ) : activeTab === 'profile' ? (
          <SubPageHeader 
            title="Profil & Toko" 
            subtitle="Pengaturan akun & integrasi toko"
            onBack={() => handleTabChange('home')} 
          />
        ) : null}

        {/* Tab Content */}
        {activeTab === 'home' && (
          <DashboardPage 
            metrics={metrics}
            orders={orders}
            isLoading={isDataLoading}
            onSelectOrder={(order) => setSelectedOrder(order)}
            onViewAllOrders={handleOpenOrders}
            onOpenFinance={handleOpenFinance}
            onOpenTarget={handleOpenFinance}
            onSelectFinanceMetric={(type) => setSelectedFinanceMetric(type)}
            onOpenAnalytics={() => handleTabChange('analytics')}
            onOpenAllModules={() => setIsAllModulesOpen(true)}
            onSelectMenu={(menuId) => {
              if (menuId === 'orders') handleOpenOrders('ALL');
              else if (menuId === 'returns') handleOpenOrders('RETURNED');
              else if (menuId === 'analytics') handleTabChange('analytics');
              else if (menuId === 'target' || menuId === 'margin' || menuId === 'cashflow' || menuId === 'reports') handleOpenFinance();
              else if (menuId === 'settings' || menuId === 'stores' || menuId === 'company' || menuId === 'users') handleTabChange('profile');
              else if (menuId === 'scanner') handleScanClick();
              else if (menuId === 'all_modules') setIsAllModulesOpen(true);
              else {
                alert(`📌 Modul "${menuId}" aktif. Membuka data sinkronisasi ERP.`);
              }
            }}
          />
        )}

        {activeTab === 'orders' && (
          <OrdersPage 
            orders={orders}
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
          />
        )}

        {activeTab === 'profile' && (
          <ProfilePage 
            profile={profile}
            onLogout={handleLogout}
          />
        )}

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
            else if (actionId === 'returns') handleOpenOrders('RETURNED');
            else if (actionId === 'margin' || actionId === 'cashflow' || actionId === 'marketplace_wallets' || actionId === 'hutang_supplier') handleOpenFinance();
            else if (actionId === 'scanner') handleScanClick();
            else if (actionId === 'stores' || actionId === 'users' || actionId === 'company') handleTabChange('profile');
            else {
              alert(`🚀 Modul "${actionId}" terpilih. Sedang sinkronisasi data ERP.`);
            }
          }}
        />
      </div>
    </div>
  );
}
