import React from 'react';
import { formatRupiah, getChannelBadge } from '../../utils/formatters';
import { Clock, ChevronRight } from 'lucide-react';

export default function OrderCard({ order, onClick }) {
  const channelBadge = getChannelBadge(order.channel || order.storeName);

  const invoice = order.invoiceNumber || order.orderNumber || `ORD-${order.id || ''}`;
  const released = Number(order.releasedValue ?? order.netReleased ?? order.netAmount ?? order.totalAmount ?? 0);
  const hpp = Number(order.hppModal ?? order.hpp ?? 0);
  const margin = Number(order.margin ?? order.marginRp ?? (released - hpp));
  const commission = Number(order.commission ?? 0);
  const qty = order.quantity ?? order.itemsCount ?? (order.items?.length || 1);
  const dateStr = order.completedAt || order.orderDate || '';

  return (
    <div className="mobile-order-card" onClick={() => onClick && onClick(order)}>
      {/* Top Header: Invoice & Store */}
      <div className="order-card-header">
        <div className="order-code-badge">
          <span>{invoice}</span>
        </div>
        <span className={`store-pill ${channelBadge.class}`}>
          {order.storeName}
        </span>
      </div>

      {/* Body: Financial Breakdown */}
      <div className="order-card-body">
        <div className="order-metric-box">
          <span className="metric-label">Dilepas / Omset Bersih</span>
          <span className="metric-value">{formatRupiah(released)}</span>
          <span className="metric-label" style={{ marginTop: '2px', fontSize: '9.5px' }}>
            HPP: {formatRupiah(hpp)}
          </span>
        </div>

        <div className="order-metric-box" style={{ textAlign: 'right' }}>
          <span className="metric-label">Margin (Laba)</span>
          <span className="metric-value margin">{formatRupiah(margin)}</span>
          <span className="metric-label" style={{ marginTop: '2px', color: '#16a34a', fontWeight: '700' }}>
            Komisi: {formatRupiah(commission)}
          </span>
        </div>
      </div>

      {/* Footer: Date, Status, Detail trigger */}
      <div className="order-card-footer">
        <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
          <Clock size={12} color="#64748b" />
          <span>{dateStr}</span>
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
            {order.status} • {qty} pcs
          </span>
          <ChevronRight size={14} color="#94a3b8" />
        </div>
      </div>
    </div>
  );
}
