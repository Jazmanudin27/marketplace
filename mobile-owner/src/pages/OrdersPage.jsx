import React, { useState } from 'react';
import OrderCard from '../components/orders/OrderCard';
import { Search, Filter } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';

export default function OrdersPage({ orders, orderCounts, isLoading = false, onSelectOrder, initialFilter = 'ALL' }) {
  const [activeFilter, setActiveFilter] = useState(initialFilter);
  const [activeProcess, setActiveProcess] = useState('ALL'); // ALL, to_process, processed
  const [searchTerm, setSearchTerm] = useState('');

  // Hitung jumlah count riil dari orders lokal jika data API belum masuk
  const counts = orderCounts?.tabCounts || {};
  const procCounts = orderCounts?.processCounts || {};

  // Status mapping matching ERP (Semua, Perlu Dikirim, Dikirim, Selesai, Retur, Batal)
  const filterTabs = [
    { id: 'ALL', label: 'Semua', count: counts.ALL ?? (orders || []).length },
    { id: 'READY_TO_SHIP', label: 'Perlu Dikirim', count: counts.READY_TO_SHIP ?? (orders || []).filter(o => ['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM'].includes(o.status)).length, isUrgent: true },
    { id: 'SHIPPED', label: 'Dikirim', count: counts.SHIPPED ?? (orders || []).filter(o => ['SHIPPED', 'DIKIRIM'].includes(o.status)).length },
    { id: 'COMPLETED', label: 'Selesai', count: counts.COMPLETED ?? 0 },
    { id: 'TO_RETURN', label: 'Retur', count: counts.TO_RETURN ?? 0 },
    { id: 'CANCELLED', label: 'Batal', count: counts.CANCELLED ?? 0 }
  ];

  // Process sub-tabs (Khusus tab Perlu Dikirim / ALL)
  const readyOrders = (orders || []).filter(o => ['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM'].includes(o.status));
  const localToProcess = readyOrders.filter(o => !o.isPrinted && (!o.trackingNumber || o.trackingNumber === '')).length;
  const localProcessed = readyOrders.filter(o => o.isPrinted || (o.trackingNumber && o.trackingNumber !== '')).length;

  const toProcessBadge = procCounts.to_process ?? localToProcess;
  const processedBadge = procCounts.processed ?? localProcessed;

  const processSubTabs = [
    { id: 'ALL', label: 'Semua Status', count: (filterTabs.find(t => t.id === activeFilter)?.count) || 0 },
    { id: 'to_process', label: 'Belum diproses', count: toProcessBadge, isHighlight: true },
    { id: 'processed', label: 'Telah diproses', count: processedBadge }
  ];

  const filteredOrders = (orders || []).filter((order) => {
    // 1. Filter status utama
    if (activeFilter !== 'ALL') {
      if (activeFilter === 'READY_TO_SHIP' && !['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM'].includes(order.status)) return false;
      if (activeFilter === 'SHIPPED' && !['SHIPPED', 'DIKIRIM'].includes(order.status)) return false;
      if (activeFilter === 'COMPLETED' && !['COMPLETED', 'RELEASED', 'SELESAI', 'DELIVERED', 'FINISHED'].includes(order.status)) return false;
      if (activeFilter === 'RETURNED' && !['RETURNED', 'REFUNDED', 'RETUR'].includes(order.status)) return false;
      if (activeFilter === 'CANCELLED' && !['CANCELLED', 'BATAL', 'CANCELED', 'IN_CANCEL'].includes(order.status)) return false;
    }

    // 2. Filter status proses (Belum diproses vs Telah diproses)
    if (activeProcess !== 'ALL') {
      const isDone = Boolean(order.isPrinted || (order.trackingNumber && order.trackingNumber !== ''));
      if (activeProcess === 'to_process' && isDone) return false;
      if (activeProcess === 'processed' && !isDone) return false;
    }

    // 3. Filter search
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

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px' }}>

      {/* Main Filter Tabs with ERP Notification Badges */}
      <div style={{
        display: 'flex',
        gap: '8px',
        overflowX: 'auto',
        paddingBottom: '2px',
        scrollbarWidth: 'none'
      }}>
        {filterTabs.map((tab) => {
          const isActive = activeFilter === tab.id;
          return (
            <button
              key={tab.id}
              type="button"
              onClick={() => {
                setActiveFilter(tab.id);
                setActiveProcess('ALL');
              }}
              style={{
                background: isActive ? '#2563eb' : '#ffffff',
                color: isActive ? '#ffffff' : '#475569',
                border: '1px solid',
                borderColor: isActive ? '#2563eb' : '#e2e8f0',
                padding: '6px 12px',
                borderRadius: '999px',
                fontSize: '12px',
                fontWeight: '700',
                cursor: 'pointer',
                whiteSpace: 'nowrap',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                transition: 'all 0.15s ease'
              }}
            >
              <span>{tab.label}</span>
              {tab.count > 0 && (
                <span style={{
                  background: isActive ? 'rgba(255, 255, 255, 0.25)' : (tab.isUrgent ? '#ef4444' : '#e2e8f0'),
                  color: isActive ? '#ffffff' : (tab.isUrgent ? '#ffffff' : '#475569'),
                  fontSize: '10px',
                  fontWeight: '800',
                  padding: '1px 6px',
                  borderRadius: '999px',
                  lineHeight: '1.3'
                }}>
                  {tab.count > 999 ? '999+' : tab.count}
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* Sub-Tabs: Proses Status (Belum Diproses vs Telah Diproses ala ERP) */}
      {(activeFilter === 'READY_TO_SHIP' || activeFilter === 'ALL') && (
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '6px',
          overflowX: 'auto',
          paddingBottom: '2px',
          scrollbarWidth: 'none'
        }}>
          {processSubTabs.map((sub) => {
            const isSubActive = activeProcess === sub.id;
            return (
              <button
                key={sub.id}
                type="button"
                onClick={() => setActiveProcess(sub.id)}
                style={{
                  background: isSubActive ? '#eff6ff' : '#f8fafc',
                  color: isSubActive ? '#1d4ed8' : '#64748b',
                  border: '1px solid',
                  borderColor: isSubActive ? '#93c5fd' : '#e2e8f0',
                  padding: '4px 10px',
                  borderRadius: '10px',
                  fontSize: '11px',
                  fontWeight: '700',
                  cursor: 'pointer',
                  whiteSpace: 'nowrap',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '5px'
                }}
              >
                <span>{sub.label}</span>
                {sub.count > 0 && (
                  <span style={{
                    background: sub.isHighlight ? '#fef3c7' : '#e2e8f0',
                    color: sub.isHighlight ? '#b45309' : '#475569',
                    fontSize: '9.5px',
                    fontWeight: '800',
                    padding: '1px 5px',
                    borderRadius: '6px'
                  }}>
                    {sub.count}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      )}

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

      {/* Order Cards with Skeleton Support */}
      <div className="order-cards-list">
        {isLoading ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {[1, 2, 3, 4].map((i) => (
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
        ) : filteredOrders.length > 0 ? (
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
