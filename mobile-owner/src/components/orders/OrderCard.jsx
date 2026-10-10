import React from 'react';
import { formatRupiah, getChannelBadge } from '../../utils/formatters';
import { Clock, ChevronRight } from 'lucide-react';

export default function OrderCard({ order, onClick }) {
  const channelBadge = getChannelBadge(order.channel || order.storeName);

  return (
    <div className="mobile-order-card" onClick={() => onClick && onClick(order)}>
      {/* Top Header: Invoice & Store */}
      <div className="order-card-header">
        <div className="order-code-badge">
          <span>{order.invoiceNumber}</span>
        </div>
        <span className={`store-pill ${channelBadge.class}`}>
          {order.storeName}
        </span>
      </div>

      {/* Body: Financial Breakdown */}
      <div className="order-card-body">
        <div className="order-metric-box">
          <span className="metric-label">Dilepas / Omset Bersih</span>
          <span className="metric-value">{formatRupiah(order.releasedValue)}</span>
          <span className="metric-label" style={{ marginTop: '2px', fontSize: '9.5px' }}>
            HPP: {formatRupiah(order.hppModal)}
          </span>
        </div>

        <div className="order-metric-box" style={{ textAlign: 'right' }}>
          <span className="metric-label">Margin (Laba)</span>
          <span className="metric-value margin">{formatRupiah(order.margin)}</span>
          <span className="metric-label" style={{ marginTop: '2px', color: '#16a34a', fontWeight: '700' }}>
            Komisi: {formatRupiah(order.commission)}
          </span>
        </div>
      </div>

      {/* Footer: Date, Status, Detail trigger */}
      <div className="order-card-footer">
        <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
          <Clock size={12} color="#64748b" />
          <span>{order.completedAt}</span>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
          <span style={{ 
            fontSize: '10.5px', 
            fontWeight: '700', 
            background: '#ecfdf5', 
            color: '#059669', 
            padding: '2px 8px', 
            borderRadius: '999px' 
          }}>
            {order.status} • {order.quantity} pcs
          </span>
          <ChevronRight size={14} color="#94a3b8" />
        </div>
      </div>
    </div>
  );
}
