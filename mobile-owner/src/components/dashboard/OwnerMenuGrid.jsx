import React, { useState } from 'react';
import { 
  ShoppingBag, 
  RotateCcw,
  Target, 
  Boxes, 
  ScanLine, 
  QrCode,
  PackagePlus, 
  PackageMinus, 
  HandCoins, 
  Store, 
  CreditCard, 
  LineChart, 
  Receipt, 
  Layers, 
  FileText, 
  Users, 
  SlidersHorizontal,
  ChevronRight,
  Sparkles,
  Grid
} from 'lucide-react';

export default function OwnerMenuGrid({ onSelectMenu, onOpenAllModules }) {
  // 12 Menu Utama di Beranda (Grid Squircle)
  const mainMenus = [
    {
      id: 'orders',
      title: 'Pesanan Masuk',
      category: 'Transaksi',
      icon: ShoppingBag,
      gradient: 'linear-gradient(135deg, #2563eb, #1d4ed8)'
    },
    {
      id: 'returns',
      title: 'Retur Masuk',
      category: 'Transaksi',
      icon: RotateCcw,
      gradient: 'linear-gradient(135deg, #ef4444, #dc2626)'
    },
    {
      id: 'target',
      title: 'Target Komisi',
      category: 'Marketing',
      icon: Target,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)'
    },
    {
      id: 'analytics',
      title: 'Grafik',
      category: 'Analisis',
      icon: LineChart,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)'
    },
    {
      id: 'scan_kemasan',
      title: 'Scan Kemasan',
      category: 'Gudang',
      icon: ScanLine,
      gradient: 'linear-gradient(135deg, #06b6d4, #0891b2)'
    },
    {
      id: 'scan_spk',
      title: 'Scan SPK',
      category: 'Produksi',
      icon: QrCode,
      gradient: 'linear-gradient(135deg, #8b5cf6, #7c3aed)'
    },
    {
      id: 'saldo_marketplace',
      title: 'Saldo MP',
      category: 'Keuangan',
      icon: Store,
      gradient: 'linear-gradient(135deg, #0284c7, #0369a1)'
    },
    {
      id: 'laporan_penjualan',
      title: 'Lap Penjualan',
      category: 'Transaksi',
      icon: FileText,
      gradient: 'linear-gradient(135deg, #4f46e5, #4338ca)'
    },
    {
      id: 'cashflow',
      title: 'Mutasi Kas',
      category: 'Keuangan',
      icon: CreditCard,
      gradient: 'linear-gradient(135deg, #14b8a6, #0d9488)'
    },
    {
      id: 'laba_rugi',
      title: 'Laba Rugi',
      category: 'Keuangan',
      icon: LineChart,
      gradient: 'linear-gradient(135deg, #10b981, #047857)'
    }
  ];

  return (
    <div>
      <div className="section-title-wrap">
        <h3 className="section-title">
          <span>Menu</span>
        </h3>
      </div>

      <div className="owner-apps-grid">
        {mainMenus.map((item) => {
          const IconComp = item.icon;
          return (
            <div 
              key={item.id} 
              className="app-grid-item"
              onClick={() => {
                if (item.id === 'all_modules') {
                  onOpenAllModules && onOpenAllModules();
                } else {
                  onSelectMenu && onSelectMenu(item.id);
                }
              }}
            >
              <div 
                className="app-icon-squircle"
                style={{ background: item.gradient }}
              >
                <IconComp size={23} strokeWidth={2.2} />
              </div>
              <span className="app-icon-label">{item.title}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
}
