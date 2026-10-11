import React, { useState } from 'react';
import QuickFinanceGrid from '../components/dashboard/QuickFinanceGrid';
import OwnerMenuGrid from '../components/dashboard/OwnerMenuGrid';
import OrderCard from '../components/orders/OrderCard';
import { Search, ArrowRight } from 'lucide-react';

export default function DashboardPage({ 
  metrics, 
  orders, 
  isLoading = false,
  onSelectOrder, 
  onViewAllOrders, 
  onOpenFinance, 
  onOpenTarget,
  onOpenAnalytics,
  onOpenAllModules,
  onSelectMenu,
  onSelectFinanceMetric
}) {
  const [searchTerm, setSearchTerm] = useState('');

  const recentOrders = (orders || []).filter(o => {
    if (!searchTerm) return true;
    const q = searchTerm.toLowerCase();
    return o.invoiceNumber.toLowerCase().includes(q) || 
           o.storeName.toLowerCase().includes(q) ||
           o.buyerName.toLowerCase().includes(q);
  }).slice(0, 4);

  return (
    <div className="mobile-body-content">
      {/* 5 Metrik Finansial: Penjualan Hari ini, Biaya Admin, Refund, HPP, Margin */}
      <QuickFinanceGrid 
        metrics={metrics} 
        onOpenFinance={onOpenFinance} 
        onSelectMetric={onSelectFinanceMetric}
        isLoading={isLoading}
      />

      {/* 12 Owner App Launcher Grid */}
      <OwnerMenuGrid 
        onSelectMenu={(menuId) => {
          if (menuId === 'orders') onViewAllOrders('ALL');
          else if (menuId === 'returns') onViewAllOrders('RETURNED');
          else if (menuId === 'analytics') onOpenAnalytics && onOpenAnalytics();
          else if (menuId === 'cashflow' || menuId === 'mutasi') onSelectMenu && onSelectMenu('mutasi');
          else if (menuId === 'margin' || menuId === 'laba_rugi') onSelectMenu && onSelectMenu('laba_rugi');
          else if (menuId === 'target') onOpenTarget();
          else if (onSelectMenu) onSelectMenu(menuId);
        }}
        onOpenAllModules={onOpenAllModules}
      />

      {/* Recent Live Transactions Section */}
      <div>
        <div className="section-title-wrap">
          <h3 className="section-title">
            <span>📋 Order Terbaru</span>
          </h3>
          <span 
            className="section-view-all" 
            onClick={() => onViewAllOrders && onViewAllOrders('ALL')}
            style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}
          >
            Lihat Semua <ArrowRight size={13} />
          </span>
        </div>

        {/* Quick Search */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          background: '#ffffff',
          border: '1px solid #e2e8f0',
          borderRadius: '14px',
          padding: '10px 14px',
          marginBottom: '12px',
          boxShadow: '0 2px 5px rgba(0,0,0,0.03)'
        }}>
          <Search size={16} color="#94a3b8" />
          <input 
            type="text"
            placeholder="Cari order, toko, pembeli..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{
              border: 'none',
              outline: 'none',
              background: 'transparent',
              width: '100%',
              fontSize: '12.5px',
              fontFamily: 'inherit'
            }}
          />
        </div>

        {/* Orders Card List */}
        <div className="order-cards-list">
          {isLoading ? (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              {[1, 2, 3].map((i) => (
                <div 
                  key={i}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #e2e8f0',
                    borderRadius: '16px',
                    padding: '14px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div className="skeleton-shimmer skeleton-text" style={{ width: '120px', height: '14px' }} />
                    <div className="skeleton-shimmer skeleton-text" style={{ width: '80px', height: '18px', borderRadius: '999px' }} />
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                      <div className="skeleton-shimmer skeleton-text" style={{ width: '90px', height: '10px' }} />
                      <div className="skeleton-shimmer skeleton-text" style={{ width: '100px', height: '14px' }} />
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '5px' }}>
                      <div className="skeleton-shimmer skeleton-text" style={{ width: '80px', height: '10px' }} />
                      <div className="skeleton-shimmer skeleton-text" style={{ width: '90px', height: '14px' }} />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : recentOrders.length > 0 ? (
            recentOrders.map((order) => (
              <OrderCard 
                key={order.id} 
                order={order} 
                onClick={onSelectOrder} 
              />
            ))
          ) : (
            <div style={{ textAlign: 'center', padding: '24px', color: '#94a3b8', fontSize: '13px' }}>
              Tidak ada transaksi yang cocok.
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
