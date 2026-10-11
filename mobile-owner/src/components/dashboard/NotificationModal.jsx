import React from 'react';
import { X, Bell, Package, ChevronRight, Clock, CheckCircle } from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../../utils/formatters';

export default function NotificationModal({ 
  isOpen, 
  onClose, 
  orders = [], 
  onViewAllOrders,
  onSelectOrder 
}) {
  if (!isOpen) return null;

  // Khusus pesanan yang BELUM dikirim / harus dikirim dan belum diproses (READY_TO_SHIP, UNPAID, NEW)
  const pendingOrders = (orders || []).filter((o) => {
    const s = String(o.status || '').toUpperCase();
    return s === 'READY_TO_SHIP' || s === 'SIAP_KIRIM' || s === 'NEW' || s === 'UNPAID' || s === 'IN_PROCESS';
  });

  const displayOrders = pendingOrders.slice(0, 5);

  return (
    <div className="modal-overlay" style={{ zIndex: 2100 }} onClick={onClose}>
      <div 
        className="bottom-sheet-card" 
        onClick={(e) => e.stopPropagation()}
        style={{ maxHeight: '80vh', display: 'flex', flexDirection: 'column' }}
      >
        {/* Top Handle */}
        <div className="sheet-handle"></div>

        {/* Modal Header */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <div style={{ 
              width: '36px', 
              height: '36px', 
              borderRadius: '12px', 
              background: '#eff6ff', 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center' 
            }}>
              <Bell size={18} color="#2563eb" />
            </div>
            <div>
              <h3 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                Notifikasi Pesanan
              </h3>
              <span style={{ fontSize: '11px', color: '#64748b' }}>
                {pendingOrders.length} pesanan perlu diproses
              </span>
            </div>
          </div>

          <button 
            type="button" 
            onClick={onClose}
            style={{ 
              background: '#f1f5f9', 
              border: 'none', 
              borderRadius: '50%', 
              width: '30px', 
              height: '30px', 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center',
              cursor: 'pointer' 
            }}
          >
            <X size={16} color="#64748b" />
          </button>
        </div>

        {/* List Pesanan Belum Diproses (Maksimal 5) */}
        <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {displayOrders.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '36px 16px', color: '#94a3b8' }}>
              <CheckCircle size={36} color="#10b981" style={{ margin: '0 auto 8px', display: 'block' }} />
              <div style={{ fontSize: '13px', fontWeight: '700', color: '#334155' }}>Semua Pesanan Sudah Diproses</div>
              <p style={{ fontSize: '11.5px', color: '#94a3b8', margin: '4px 0 0' }}>Tidak ada pesanan tertunda yang perlu dikirim.</p>
            </div>
          ) : (
            displayOrders.map((order) => {
              const badge = getChannelBadge(order.channel || order.storeName);
              return (
                <div 
                  key={order.id}
                  onClick={() => {
                    onClose();
                    if (onSelectOrder) onSelectOrder(order);
                  }}
                  style={{
                    background: '#f8fafc',
                    border: '1px solid #e2e8f0',
                    borderRadius: '14px',
                    padding: '12px 14px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '6px',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontSize: '12px', fontWeight: '800', fontFamily: 'monospace', color: '#0f172a' }}>
                      {order.invoiceNumber}
                    </span>
                    <span className={`store-pill ${badge.class}`} style={{ fontSize: '9.5px', padding: '2px 7px' }}>
                      {order.storeName}
                    </span>
                  </div>

                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '11px', color: '#64748b' }}>
                      <Clock size={11} color="#94a3b8" />
                      <span>{order.completedAt || 'Baru masuk'}</span>
                      <span>• {order.quantity} pcs</span>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <span style={{ 
                        fontSize: '10px', 
                        fontWeight: '700', 
                        background: '#fef3c7', 
                        color: '#d97706', 
                        padding: '2px 6px', 
                        borderRadius: '6px' 
                      }}>
                        Perlu Kirim
                      </span>
                      <ChevronRight size={14} color="#94a3b8" />
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Footer: Lihat Semua Pesanan */}
        <div style={{ paddingTop: '14px', marginTop: '6px', borderTop: '1px solid #f1f5f9' }}>
          <button
            type="button"
            onClick={() => {
              onClose();
              if (onViewAllOrders) onViewAllOrders('READY_TO_SHIP');
            }}
            style={{
              width: '100%',
              background: '#2563eb',
              color: '#ffffff',
              border: 'none',
              borderRadius: '14px',
              padding: '12px',
              fontSize: '13px',
              fontWeight: '700',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px'
            }}
          >
            <span>Lihat Semua Pesanan ({pendingOrders.length})</span>
            <ChevronRight size={16} />
          </button>
        </div>
      </div>
    </div>
  );
}
