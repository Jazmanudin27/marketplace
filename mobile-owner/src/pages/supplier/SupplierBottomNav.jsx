import React from 'react';
import { Package, Layers, User } from 'lucide-react';

export default function SupplierBottomNav({ activeTab, onTabChange }) {
  const tabs = [
    {
      id: 'titipan',
      label: 'Titipan Barang',
      icon: Package,
      color: '#f59e0b',
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)',
      glow: 'rgba(245, 158, 11, 0.45)',
      idleBg: 'rgba(245, 158, 11, 0.12)'
    },
    {
      id: 'kartu_stok',
      label: 'Kartu Stok',
      icon: Layers,
      color: '#10b981',
      gradient: 'linear-gradient(135deg, #10b981, #059669)',
      glow: 'rgba(16, 185, 129, 0.45)',
      idleBg: 'rgba(16, 185, 129, 0.12)'
    },
    {
      id: 'profile',
      label: 'Akun Saya',
      icon: User,
      color: '#6366f1',
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)',
      glow: 'rgba(99, 102, 241, 0.45)',
      idleBg: 'rgba(99, 102, 241, 0.12)'
    }
  ];

  return (
    <div className="bottom-dock-wrapper">
      <div className="bottom-dock" style={{ gridTemplateColumns: 'repeat(3, 1fr)' }}>
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
                <IconComp size={19} strokeWidth={isActive ? 2.5 : 2} />
              </div>
              <span style={{ fontWeight: isActive ? '700' : '500' }}>{tab.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
