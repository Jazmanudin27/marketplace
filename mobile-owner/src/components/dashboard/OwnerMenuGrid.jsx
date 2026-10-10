import React, { useState } from 'react';
import { 
  ShoppingBag, 
  RotateCcw,
  Target, 
  Boxes, 
  ScanLine, 
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
      title: 'Pesanan Retur',
      category: 'Transaksi',
      icon: RotateCcw,
      gradient: 'linear-gradient(135deg, #ef4444, #dc2626)'
    },
    {
      id: 'target',
      title: 'Target Marketing',
      category: 'Marketing',
      icon: Target,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)'
    },
    {
      id: 'offline_sales',
      title: 'POS Offline',
      category: 'Transaksi',
      icon: Receipt,
      gradient: 'linear-gradient(135deg, #10b981, #059669)'
    },
    {
      id: 'warehouse',
      title: 'Gudang Jadi',
      category: 'Gudang',
      icon: Boxes,
      gradient: 'linear-gradient(135deg, #8b5cf6, #7c3aed)'
    },
    {
      id: 'scanner',
      title: 'Scan & Kemas',
      category: 'Gudang',
      icon: ScanLine,
      gradient: 'linear-gradient(135deg, #06b6d4, #0891b2)'
    },
    {
      id: 'pembelian',
      title: 'Masuk Barang',
      category: 'Pembelian',
      icon: PackagePlus,
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)'
    },
    {
      id: 'titipan',
      title: 'Titipan Barang',
      category: 'Konsinyasi',
      icon: HandCoins,
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)'
    },
    {
      id: 'cashflow',
      title: 'Mutasi Kas',
      category: 'Keuangan',
      icon: CreditCard,
      gradient: 'linear-gradient(135deg, #14b8a6, #0d9488)'
    },
    {
      id: 'margin',
      title: 'Laba Rugi',
      category: 'Keuangan',
      icon: LineChart,
      gradient: 'linear-gradient(135deg, #f97316, #c2410c)'
    },
    {
      id: 'master_products',
      title: 'Master Produk',
      category: 'Master',
      icon: Layers,
      gradient: 'linear-gradient(135deg, #a855f7, #9333ea)'
    },
    {
      id: 'all_modules',
      title: 'Semua Modul',
      category: 'ERP Lengkap',
      icon: Grid,
      gradient: 'linear-gradient(135deg, #475569, #334155)',
      isAction: true
    }
  ];

  return (
    <div>
      <div className="section-title-wrap">
        <h3 className="section-title">
          <span>⚡ Menu Utama Owner</span>
        </h3>
        <span 
          className="section-view-all"
          onClick={() => onOpenAllModules && onOpenAllModules()}
          style={{ display: 'inline-flex', alignItems: 'center', gap: '3px', cursor: 'pointer' }}
        >
          Semua Modul ERP <ChevronRight size={13} />
        </span>
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
