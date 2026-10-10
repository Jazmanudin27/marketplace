import React from 'react';
import { PackagePlus, Truck, RotateCcw, CheckCircle2 } from 'lucide-react';

export default function QuickStatusGrid({ metrics, onSelectStatus }) {
  const cards = [
    {
      id: 'NEW',
      type: 'green',
      icon: PackagePlus,
      label: 'Pesanan Baru',
      count: metrics?.ordersNew ?? 0,
      badge: metrics?.ordersNew > 0 ? String(metrics.ordersNew) : null,
      statusFilter: 'ALL'
    },
    {
      id: 'TO_SHIP',
      type: 'orange',
      icon: Truck,
      label: 'Perlu Dikirim',
      count: metrics?.ordersToShip ?? 0,
      badge: metrics?.ordersToShip > 0 ? String(metrics.ordersToShip) : null,
      statusFilter: 'READY_TO_SHIP'
    },
    {
      id: 'RETURNED',
      type: 'red',
      icon: RotateCcw,
      label: 'Komplain / Retur',
      count: metrics?.ordersReturned ?? 0,
      badge: metrics?.ordersReturned > 0 ? String(metrics.ordersReturned) : null,
      statusFilter: 'RETURNED'
    },
    {
      id: 'COMPLETED',
      type: 'blue',
      icon: CheckCircle2,
      label: 'Selesai Hari Ini',
      count: metrics?.ordersCompletedToday ?? 0,
      badge: metrics?.ordersCompletedToday > 0 ? String(metrics.ordersCompletedToday) : null,
      statusFilter: 'COMPLETED'
    }
  ];

  return (
    <div className="quick-status-grid">
      {cards.map((card) => {
        const IconComponent = card.icon;
        return (
          <div 
            key={card.id} 
            className={`status-squircle-card ${card.type}`}
            onClick={() => onSelectStatus && onSelectStatus(card.statusFilter)}
          >
            {card.badge && (
              <span className="status-card-badge">{card.badge}</span>
            )}
            
            <div className="squircle-icon-wrap">
              <IconComponent size={22} strokeWidth={2.4} />
            </div>

            <span className="status-card-label">{card.label}</span>
            <span className="status-card-count">{card.count} pesanan</span>
          </div>
        );
      })}
    </div>
  );
}
