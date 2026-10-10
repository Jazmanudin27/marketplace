import React, { useState, useEffect } from 'react';
import Header from './components/layout/Header';
import BottomNav from './components/layout/BottomNav';
import DashboardPage from './pages/DashboardPage';
import OrdersPage from './pages/OrdersPage';
import FinancePage from './pages/FinancePage';
import ProfilePage from './pages/ProfilePage';
import LoginPage from './pages/LoginPage';
import OrderDetailModal from './components/orders/OrderDetailModal';
import AllModulesModal from './components/dashboard/AllModulesModal';
import { fetchOrders } from './api/ordersApi';
import { fetchOwnerMetrics } from './api/metricsApi';
import { getCurrentOwnerUser, logoutOwner } from './api/authApi';
import { mockOwnerProfile, mockOwnerMetrics, mockOrders } from './api/mockData';

export default function App() {
  const [currentUser, setCurrentUser] = useState(() => getCurrentOwnerUser());
  const [activeTab, setActiveTab] = useState('home');
  const [profile, setProfile] = useState(mockOwnerProfile);
  const [metrics, setMetrics] = useState(mockOwnerMetrics);
  const [orders, setOrders] = useState(mockOrders);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [ordersFilter, setOrdersFilter] = useState('ALL');
  const [isAllModulesOpen, setIsAllModulesOpen] = useState(false);

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

  // Load API data on mount if logged in
  useEffect(() => {
    if (!currentUser) return;

    async function loadData() {
      try {
        const [metricRes, ordersRes] = await Promise.all([
          fetchOwnerMetrics(),
          fetchOrders({ status: 'ALL' })
        ]);
        if (metricRes?.data) setMetrics(metricRes.data);
        if (ordersRes?.data) setOrders(ordersRes.data);
      } catch (err) {
        console.warn('API fetch error, using local dataset:', err);
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
    setActiveTab('orders');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleOpenFinance = () => {
    setActiveTab('finance');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleScanClick = () => {
    alert('📷 Fitur Scanner Barcode Gudang V2 aktif. Arahkan kamera ke barcode pesanan pick & pack.');
  };

  const handleOpenKeyStatus = () => {
    alert('🔑 Sinkronisasi API Marketplace Aktif: \n• Shopee Open API: Online\n• TikTok Shop API: Online\n• Tokopedia API: Online\n• Status Database: 100% Realtime');
  };

  const handleOpenNotif = () => {
    alert('🔔 Notifikasi Terbaru:\n• 18 pesanan baru masuk dari Shopee & TikTok Shop.\n• 2 komplain retur perlu verifikasi owner.');
  };

  const handleLogout = () => {
    if (confirm('Apakah Anda yakin ingin keluar dari Portal Owner?')) {
      logoutOwner();
      setCurrentUser(null);
      setActiveTab('home');
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
        {/* Top Header */}
        <Header 
          profile={profile}
          onOpenKeyStatus={handleOpenKeyStatus}
          onOpenNotif={handleOpenNotif}
          onLogout={handleLogout}
        />

        {/* Tab Content */}
        {activeTab === 'home' && (
          <DashboardPage 
            metrics={metrics}
            orders={orders}
            onSelectOrder={(order) => setSelectedOrder(order)}
            onViewAllOrders={handleOpenOrders}
            onOpenFinance={handleOpenFinance}
            onOpenTarget={handleOpenFinance}
            onOpenAllModules={() => setIsAllModulesOpen(true)}
            onSelectMenu={(menuId) => {
              if (menuId === 'orders') handleOpenOrders('ALL');
              else if (menuId === 'returns') handleOpenOrders('RETURNED');
              else if (menuId === 'target' || menuId === 'margin' || menuId === 'cashflow' || menuId === 'reports') handleOpenFinance();
              else if (menuId === 'settings' || menuId === 'stores' || menuId === 'company' || menuId === 'users') setActiveTab('profile');
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
          onTabChange={(tab) => {
            setActiveTab(tab);
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }}
          onScanClick={handleScanClick}
        />

        {/* Order Detail Modal */}
        {selectedOrder && (
          <OrderDetailModal 
            order={selectedOrder}
            onClose={() => setSelectedOrder(null)}
          />
        )}

        {/* All Modules ERP Catalog Drawer */}
        <AllModulesModal 
          isOpen={isAllModulesOpen}
          onClose={() => setIsAllModulesOpen(false)}
          onSelectAction={(actionId) => {
            if (actionId === 'orders') handleOpenOrders('ALL');
            else if (actionId === 'returns') handleOpenOrders('RETURNED');
            else if (actionId === 'margin' || actionId === 'cashflow' || actionId === 'marketplace_wallets' || actionId === 'hutang_supplier') handleOpenFinance();
            else if (actionId === 'scanner') handleScanClick();
            else if (actionId === 'stores' || actionId === 'users' || actionId === 'company') setActiveTab('profile');
            else {
              alert(`🚀 Modul "${actionId}" terpilih. Sedang sinkronisasi data ERP.`);
            }
          }}
        />
      </div>
    </div>
  );
}
