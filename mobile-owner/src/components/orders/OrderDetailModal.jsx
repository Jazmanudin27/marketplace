import React from 'react';
import { X, User, Truck, MapPin, Package, ShieldCheck, DollarSign } from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../../utils/formatters';

export default function OrderDetailModal({ order, onClose }) {
  if (!order) return null;

  const channelBadge = getChannelBadge(order.channel || order.storeName);

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="bottom-sheet-card" onClick={(e) => e.stopPropagation()}>
        {/* Top Drag Handle */}
        <div className="sheet-handle"></div>

        {/* Header */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '16px' }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' }}>
              <span style={{ fontFamily: 'monospace', fontWeight: '800', fontSize: '15px', color: '#1e293b' }}>
                {order.invoiceNumber}
              </span>
              <span className={`store-pill ${channelBadge.class}`}>
                {order.storeName}
              </span>
            </div>
            <span style={{ fontSize: '11px', color: '#64748b' }}>
              ID Marketplace: {order.marketplaceId || order.invoiceNumber}
            </span>
          </div>

          <button 
            type="button" 
            onClick={onClose}
            style={{ 
              background: '#f1f5f9', 
              border: 'none', 
              borderRadius: '50%', 
              width: '32px', 
              height: '32px', 
              display: 'flex', 
              alignItems: 'center', 
              justifyContent: 'center',
              cursor: 'pointer' 
            }}
          >
            <X size={18} color="#475569" />
          </button>
        </div>

        {/* Customer & Shipping Info Box */}
        <div style={{ 
          background: '#f8fafc', 
          border: '1px solid #e2e8f0', 
          borderRadius: '16px', 
          padding: '12px 14px', 
          marginBottom: '16px',
          display: 'flex',
          flexDirection: 'column',
          gap: '8px'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '12px', color: '#334155' }}>
            <User size={15} color="#2563eb" />
            <strong style={{ color: '#0f172a' }}>{order.buyerName || 'Pembeli Marketplace'}</strong>
            <span style={{ color: '#64748b' }}>({order.buyerPhone || '0812-xxxx-xxxx'})</span>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '11.5px', color: '#475569' }}>
            <Truck size={15} color="#059669" />
            <span>Kurir: <strong>{order.courier || 'J&T Express'}</strong></span>
            {order.trackingNumber && (
              <span style={{ fontFamily: 'monospace', background: '#e2e8f0', padding: '1px 6px', borderRadius: '4px' }}>
                {order.trackingNumber}
              </span>
            )}
          </div>
        </div>

        {/* Items List */}
        <div style={{ marginBottom: '16px' }}>
          <h4 style={{ fontSize: '13px', fontWeight: '800', color: '#1e293b', marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <Package size={15} color="#4f46e5" />
            <span>Daftar Produk Pesanan ({order.items?.length || 1} Item)</span>
          </h4>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
            {order.items?.map((item) => (
              <div 
                key={item.id}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '10px 12px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center'
                }}
              >
                <div>
                  <div style={{ fontSize: '12.5px', fontWeight: '700', color: '#0f172a', marginBottom: '2px' }}>
                    {item.productName}
                  </div>
                  <div style={{ fontSize: '11px', color: '#64748b' }}>
                    SKU: <code>{item.sku}</code> • {item.size} • {item.quantity} pcs
                  </div>
                </div>

                <div style={{ textAlign: 'right' }}>
                  <div style={{ fontSize: '13px', fontWeight: '800', color: '#1e293b' }}>
                    {formatRupiah(item.price)}
                  </div>
                  <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                    HPP: {formatRupiah(item.costPrice)}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Financial Calculation Breakdown Card */}
        <div style={{ 
          background: 'linear-gradient(135deg, #f8fafc, #eff6ff)', 
          border: '1.5px solid #dbeafe', 
          borderRadius: '18px', 
          padding: '14px 16px',
          display: 'flex',
          flexDirection: 'column',
          gap: '7px'
        }}>
          <h4 style={{ fontSize: '12.5px', fontWeight: '800', color: '#1e40af', marginBottom: '4px', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <DollarSign size={15} />
            <span>Rincian Rekonsiliasi & Margin Owner</span>
          </h4>

          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#475569' }}>
            <span>Dana Dilepas Marketplace:</span>
            <strong style={{ color: '#0f172a' }}>{formatRupiah(order.releasedValue)}</strong>
          </div>

          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#475569' }}>
            <span>Total Modal HPP Barang:</span>
            <span style={{ color: '#dc2626', fontWeight: '600' }}>- {formatRupiah(order.hppModal)}</span>
          </div>

          <div style={{ borderTop: '1px dashed #cbd5e1', margin: '4px 0' }}></div>

          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13.5px', color: '#1e1b4b' }}>
            <strong style={{ color: '#4338ca' }}>Margin Bersih (Laba):</strong>
            <strong style={{ color: '#4338ca', fontSize: '15px' }}>{formatRupiah(order.margin)}</strong>
          </div>

          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#059669', background: '#ecfdf5', padding: '6px 10px', borderRadius: '8px', marginTop: '4px' }}>
            <span>Komisi Tim Marketing ({order.commissionRate || 7}%):</span>
            <strong>{formatRupiah(order.commission)}</strong>
          </div>
        </div>

        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          style={{
            width: '100%',
            marginTop: '16px',
            background: '#1e293b',
            color: '#ffffff',
            border: 'none',
            borderRadius: '14px',
            padding: '12px',
            fontSize: '13.5px',
            fontWeight: '700',
            cursor: 'pointer'
          }}
        >
          Tutup Rincian
        </button>
      </div>
    </div>
  );
}
