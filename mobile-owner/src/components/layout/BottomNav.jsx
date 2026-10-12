import React from 'react';
import { Home, Package, BarChart3, Wallet, User } from 'lucide-react';

export default function BottomNav({ activeTab, onTabChange, onScanClick }) {
  const tabs = [
    { 
      id: 'home', 
      label: 'Beranda', 
      icon: Home,
      color: '#4f46e5',
      gradient: 'linear-gradient(135deg, #6366f1, #4338ca)',
      glow: 'rgba(79, 70, 229, 0.45)',
      idleBg: 'rgba(79, 70, 229, 0.1)'
    },
    { 
      id: 'orders', 
      label: 'Order', 
      icon: Package,
      color: '#059669',
      gradient: 'linear-gradient(135deg, #10b981, #047857)',
      glow: 'rgba(16, 185, 129, 0.45)',
      idleBg: 'rgba(16, 185, 129, 0.12)'
    },
    { 
      id: 'analytics', 
      label: 'Grafik', 
      icon: BarChart3, 
      isCenter: true 
    },
    { 
      id: 'finance', 
      label: 'Keuangan', 
      icon: Wallet,
      color: '#d97706',
      gradient: 'linear-gradient(135deg, #f59e0b, #b45309)',
      glow: 'rgba(245, 158, 11, 0.45)',
      idleBg: 'rgba(245, 158, 11, 0.12)'
    },
    { 
      id: 'profile', 
      label: 'Profile', 
      icon: User,
      color: '#8b5cf6',
      gradient: 'linear-gradient(135deg, #a78bfa, #6d28d9)',
      glow: 'rgba(139, 92, 246, 0.45)',
      idleBg: 'rgba(139, 92, 246, 0.12)'
    }
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
              <span>{tab.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
