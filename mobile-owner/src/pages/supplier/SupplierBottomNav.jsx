import React from 'react';
import { Package, Layers, Receipt, ShoppingBag, User } from 'lucide-react';

export default function SupplierBottomNav({ activeTab, onTabChange }) {
  const tabs = [
    {
      id: 'titipan',
      label: 'Titipan',
      icon: Package,
      color: '#f59e0b',
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)',
      glow: 'rgba(245, 158, 11, 0.45)',
      idleBg: 'rgba(245, 158, 11, 0.12)'
    },
    {
      id: 'kartu_stok',
      label: 'Stok',
      icon: Layers,
      color: '#10b981',
      gradient: 'linear-gradient(135deg, #10b981, #059669)',
      glow: 'rgba(16, 185, 129, 0.45)',
      idleBg: 'rgba(16, 185, 129, 0.12)'
    },
    {
      id: 'pembayaran',
      label: 'Pembayaran',
      icon: Receipt,
      color: '#059669',
      gradient: 'linear-gradient(135deg, #059669, #047857)',
      glow: 'rgba(5, 150, 105, 0.45)',
      idleBg: 'rgba(5, 150, 105, 0.12)'
    },
    {
      id: 'penjualan',
      label: 'Penjualan',
      icon: ShoppingBag,
      color: '#2563eb',
      gradient: 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
      glow: 'rgba(37, 99, 235, 0.45)',
      idleBg: 'rgba(37, 99, 235, 0.12)'
    },
    {
      id: 'profile',
      label: 'Akun',
      icon: User,
      color: '#6366f1',
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)',
      glow: 'rgba(99, 102, 241, 0.45)',
      idleBg: 'rgba(99, 102, 241, 0.12)'
    }
  ];

  return (
    <div className="bottom-dock-wrapper">
      <div className="bottom-dock" style={{ gridTemplateColumns: 'repeat(5, 1fr)' }}>
        {tabs.map((tab) => {
          const IconComp = tab.icon;
          const isActive = activeTab === tab.id;

          return (
            <button
              key={tab.id}
              type="button"
              className={`dock-item ${isActive ? 'active' : ''}`}
              style={{
                '--tab-color': tab.color,
                '--tab-gradient': tab.gradient,
                '--tab-glow': tab.glow,
                '--tab-idle-bg': tab.idleBg,
              }}
              onClick={() => onTabChange(tab.id)}
            >
              <div className="dock-icon-box">
                <IconComp size={18} strokeWidth={isActive ? 2.5 : 2} />
              </div>
              <span style={{ fontWeight: isActive ? '800' : '500', fontSize: '10.5px' }}>{tab.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
