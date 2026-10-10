import React, { useState } from 'react';
import OrderCard from '../components/orders/OrderCard';
import { Search, Filter } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';

export default function OrdersPage({ orders, onSelectOrder, initialFilter = 'ALL' }) {
  const [activeFilter, setActiveFilter] = useState(initialFilter);
  const [searchTerm, setSearchTerm] = useState('');

  const filterTabs = [
    { id: 'ALL', label: 'Semua' },
    { id: 'READY_TO_SHIP', label: 'Perlu Dikirim' },
    { id: 'SHIPPED', label: 'Dikirim' },
    { id: 'COMPLETED', label: 'Selesai' },
    { id: 'RETURNED', label: 'Retur' }
  ];

  const filteredOrders = (orders || []).filter((order) => {
    // Filter status
    if (activeFilter !== 'ALL') {
      if (activeFilter === 'READY_TO_SHIP' && order.status !== 'READY_TO_SHIP') return false;
      if (activeFilter === 'SHIPPED' && order.status !== 'SHIPPED') return false;
      if (activeFilter === 'COMPLETED' && order.status !== 'COMPLETED') return false;
      if (activeFilter === 'RETURNED' && order.status !== 'RETURNED') return false;
    }

    // Filter search
    if (searchTerm) {
      const q = searchTerm.toLowerCase();
      return (
        order.invoiceNumber.toLowerCase().includes(q) ||
        order.storeName.toLowerCase().includes(q) ||
        order.buyerName.toLowerCase().includes(q)
      );
    }

    return true;
  });

  const totalFilteredMargin = filteredOrders.reduce((acc, curr) => acc + (curr.margin || 0), 0);

  return (
    <div className="mobile-body-content" style={{ paddingTop: '20px' }}>
      {/* Page Title & Stats */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>Daftar Pesanan</h2>
          <p style={{ fontSize: '12px', color: '#64748b' }}>
            {filteredOrders.length} Pesanan • Total Margin: <strong style={{ color: '#4338ca' }}>{formatRupiah(totalFilteredMargin)}</strong>
          </p>
        </div>
      </div>

      {/* Filter Tabs */}
      <div style={{
        display: 'flex',
        gap: '8px',
        overflowX: 'auto',
        paddingBottom: '4px',
        scrollbarWidth: 'none'
      }}>
        {filterTabs.map((tab) => {
          const isActive = activeFilter === tab.id;
          return (
            <button
              key={tab.id}
              type="button"
              onClick={() => setActiveFilter(tab.id)}
              style={{
                background: isActive ? '#4f46e5' : '#ffffff',
                color: isActive ? '#ffffff' : '#64748b',
                border: '1px solid',
                borderColor: isActive ? '#4f46e5' : '#e2e8f0',
                padding: '7px 14px',
                borderRadius: '999px',
                fontSize: '12px',
                fontWeight: '700',
                cursor: 'pointer',
                whiteSpace: 'nowrap',
                transition: 'all 0.2s ease'
              }}
            >
              {tab.label}
            </button>
          );
        })}
      </div>

      {/* Search Bar */}
      <div style={{
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        background: '#ffffff',
        border: '1px solid #e2e8f0',
        borderRadius: '14px',
        padding: '10px 14px',
        boxShadow: '0 2px 5px rgba(0,0,0,0.03)'
      }}>
        <Search size={16} color="#94a3b8" />
        <input 
          type="text"
          placeholder="Cari no invoice, toko, atau nama pembeli..."
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

      {/* Order Cards */}
      <div className="order-cards-list">
        {filteredOrders.length > 0 ? (
          filteredOrders.map((order) => (
            <OrderCard 
              key={order.id} 
              order={order} 
              onClick={onSelectOrder} 
            />
          ))
        ) : (
          <div style={{
            background: '#ffffff',
            borderRadius: '20px',
            padding: '36px 20px',
            textAlign: 'center',
            border: '1px solid #e2e8f0'
          }}>
            <p style={{ color: '#64748b', fontSize: '13px', fontWeight: '600' }}>
              Tidak ada pesanan yang sesuai dengan filter.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
