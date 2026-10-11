import React from 'react';
import { Home, Package, BarChart3, Wallet, User } from 'lucide-react';

export default function BottomNav({ activeTab, onTabChange, onScanClick }) {
  const tabs = [
    { id: 'home', label: 'Beranda', icon: Home },
    { id: 'orders', label: 'Order', icon: Package },
    { id: 'analytics', label: 'Grafik', icon: BarChart3, isCenter: true },
    { id: 'finance', label: 'Keuangan', icon: Wallet },
    { id: 'profile', label: 'Profil', icon: User }
  ];

  return (
    <div className="bottom-dock-wrapper">
      <div className="bottom-dock">
        {tabs.map((tab) => {
          const IconComp = tab.icon;

          if (tab.isCenter) {
            const isActive = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                type="button"
                className={`dock-center-fab ${isActive ? 'active' : ''}`}
                onClick={() => onTabChange('analytics')}
                title="Grafik & Analisis Penjualan"
              >
                <IconComp size={22} strokeWidth={2.4} />
              </button>
            );
          }

          const isActive = activeTab === tab.id;

          return (
            <button
              key={tab.id}
              type="button"
              className={`dock-item ${isActive ? 'active' : ''}`}
              onClick={() => onTabChange(tab.id)}
            >
              <IconComp size={20} strokeWidth={isActive ? 2.5 : 2} />
              <span>{tab.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
