import React, { useState } from 'react';
import QuickStatusGrid from '../components/dashboard/QuickStatusGrid';
import HighlightBanners from '../components/dashboard/HighlightBanners';
import OwnerMenuGrid from '../components/dashboard/OwnerMenuGrid';
import OrderCard from '../components/orders/OrderCard';
import { Search, ArrowRight } from 'lucide-react';

export default function DashboardPage({ 
  metrics, 
  orders, 
  onSelectOrder, 
  onViewAllOrders, 
  onOpenFinance, 
  onOpenTarget,
  onSelectMenu,
  onOpenAllModules
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
      {/* 4 Quick Status Cards (Pesanan Baru, Perlu Dikirim, Komplain/Retur, Selesai) */}
      <QuickStatusGrid 
        metrics={metrics} 
        onSelectStatus={(status) => onViewAllOrders && onViewAllOrders(status)} 
      />

      {/* 2 Wide Highlight Banners (Omset & Target Komisi) */}
      <HighlightBanners 
        metrics={metrics} 
        onOpenFinance={onOpenFinance}
        onOpenTarget={onOpenTarget}
      />

      {/* 12 Owner App Launcher Grid */}
      <OwnerMenuGrid 
        onSelectMenu={(menuId) => {
          if (menuId === 'orders') onViewAllOrders('ALL');
          else if (menuId === 'returns') onViewAllOrders('RETURNED');
          else if (menuId === 'cashflow' || menuId === 'margin') onOpenFinance();
          else if (menuId === 'target') onOpenTarget();
          else if (onSelectMenu) onSelectMenu(menuId);
        }}
        onOpenAllModules={onOpenAllModules}
      />

      {/* Recent Live Transactions Section */}
      <div>
        <div className="section-title-wrap">
          <h3 className="section-title">
            <span>📋 Transaksi Terbaru Selesai</span>
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
            placeholder="Cari invoice, toko, atau nama pembeli..."
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
          {recentOrders.length > 0 ? (
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
