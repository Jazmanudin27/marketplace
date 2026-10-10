import React from 'react';
import { Home, Package, ScanLine, Wallet, User } from 'lucide-react';

export default function BottomNav({ activeTab, onTabChange, onScanClick }) {
  const tabs = [
    { id: 'home', label: 'Beranda', icon: Home },
    { id: 'orders', label: 'Pesanan', icon: Package },
    { id: 'scan', label: 'Scan', icon: ScanLine, isCenter: true },
    { id: 'finance', label: 'Keuangan', icon: Wallet },
    { id: 'profile', label: 'Profil', icon: User }
  ];

  return (
    <div className="bottom-dock-wrapper">
      <div className="bottom-dock">
        {tabs.map((tab) => {
          const IconComp = tab.icon;

          if (tab.isCenter) {
            return (
              <button
                key={tab.id}
                type="button"
                className="dock-center-fab"
                onClick={onScanClick}
                title="Scanner Cepat Gudang / Barcode"
              >
                <IconComp size={24} strokeWidth={2.5} />
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
