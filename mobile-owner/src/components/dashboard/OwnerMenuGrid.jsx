import React from 'react';
import { 
  ShoppingBag, 
  Target, 
  LineChart, 
  Wallet, 
  Boxes, 
  ClipboardList, 
  Store, 
  QrCode, 
  Users, 
  BarChart3, 
  Award, 
  SlidersHorizontal 
} from 'lucide-react';

export default function OwnerMenuGrid({ onSelectMenu }) {
  const menus = [
    {
      id: 'orders',
      title: 'Pesanan Masuk',
      icon: ShoppingBag,
      gradient: 'linear-gradient(135deg, #2563eb, #1d4ed8)'
    },
    {
      id: 'target',
      title: 'Target Komisi',
      icon: Target,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)'
    },
    {
      id: 'margin',
      title: 'Rekap Margin',
      icon: LineChart,
      gradient: 'linear-gradient(135deg, #10b981, #059669)'
    },
    {
      id: 'cashflow',
      title: 'Mutasi Kas',
      icon: Wallet,
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)'
    },
    {
      id: 'warehouse',
      title: 'Gudang Ready',
      icon: Boxes,
      gradient: 'linear-gradient(135deg, #8b5cf6, #7c3aed)'
    },
    {
      id: 'spk',
      title: 'SPK Produksi',
      icon: ClipboardList,
      gradient: 'linear-gradient(135deg, #a855f7, #9333ea)'
    },
    {
      id: 'stores',
      title: 'Toko Online',
      icon: Store,
      gradient: 'linear-gradient(135deg, #f43f5e, #e11d48)'
    },
    {
      id: 'scanner',
      title: 'Scanner Gudang',
      icon: QrCode,
      gradient: 'linear-gradient(135deg, #06b6d4, #0891b2)'
    },
    {
      id: 'customers',
      title: 'Pelanggan',
      icon: Users,
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)'
    },
    {
      id: 'reports',
      title: 'Laporan Laba',
      icon: BarChart3,
      gradient: 'linear-gradient(135deg, #f97316, #c2410c)'
    },
    {
      id: 'teams',
      title: 'Performa Tim',
      icon: Award,
      gradient: 'linear-gradient(135deg, #d946ef, #c026d3)'
    },
    {
      id: 'settings',
      title: 'Pengaturan',
      icon: SlidersHorizontal,
      gradient: 'linear-gradient(135deg, #64748b, #475569)'
    }
  ];

  return (
    <div>
      <div className="section-title-wrap">
        <h3 className="section-title">
          <span>⚡ Menu Utama Owner</span>
        </h3>
        <span className="section-view-all">12 Modul Aktif</span>
      </div>

      <div className="owner-apps-grid">
        {menus.map((item) => {
          const IconComp = item.icon;
          return (
            <div 
              key={item.id} 
              className="app-grid-item"
              onClick={() => onSelectMenu && onSelectMenu(item.id)}
            >
              <div 
                className="app-icon-squircle"
                style={{ background: item.gradient }}
              >
                <IconComp size={24} strokeWidth={2.2} />
              </div>
              <span className="app-icon-label">{item.title}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
}
