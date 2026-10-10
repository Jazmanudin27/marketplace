import React, { useState, useEffect } from 'react';
import Header from './components/layout/Header';
import BottomNav from './components/layout/BottomNav';
import DashboardPage from './pages/DashboardPage';
import OrdersPage from './pages/OrdersPage';
import FinancePage from './pages/FinancePage';
import ProfilePage from './pages/ProfilePage';
import OrderDetailModal from './components/orders/OrderDetailModal';
import { fetchOrders } from './api/ordersApi';
import { fetchOwnerMetrics } from './api/metricsApi';
import { mockOwnerProfile, mockOwnerMetrics, mockOrders } from './api/mockData';

export default function App() {
  const [activeTab, setActiveTab] = useState('home');
  const [profile, setProfile] = useState(mockOwnerProfile);
  const [metrics, setMetrics] = useState(mockOwnerMetrics);
  const [orders, setOrders] = useState(mockOrders);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [ordersFilter, setOrdersFilter] = useState('ALL');

  // Load API data on mount
  useEffect(() => {
    async function loadData() {
      try {
        const [metricRes, ordersRes] = await Promise.all([
          fetchOwnerMetrics(),
          fetchOrders({ status: 'ALL' })
        ]);
        if (metricRes?.data) setMetrics(metricRes.data);
        if (metricRes?.profile) setProfile(metricRes.profile);
        if (ordersRes?.data) setOrders(ordersRes.data);
      } catch (err) {
        console.warn('API fetch error, using local fallback:', err);
      }
    }
    loadData();
  }, []);

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
      alert('Sesi berhasil diakhiri.');
    }
  };

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
            onSelectMenu={(menuId) => {
              if (menuId === 'orders') handleOpenOrders();
              else if (menuId === 'target' || menuId === 'margin' || menuId === 'cashflow' || menuId === 'reports') handleOpenFinance();
              else if (menuId === 'settings') setActiveTab('profile');
              else if (menuId === 'scanner') handleScanClick();
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

        {/* Floating Bottom Navigation Bar matching the reference UI */}
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
      </div>
    </div>
  );
}
