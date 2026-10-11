import React from 'react';
import { DollarSign, Percent, RotateCcw, Box, TrendingUp } from 'lucide-react';
import { formatRupiah } from '../../utils/formatters';

export default function QuickFinanceGrid({ metrics, onOpenFinance }) {
  // Hitung perkiraan biaya admin & refund jika belum ada di payload
  const omset = metrics?.todayOmset ?? 14850000;
  const hpp = metrics?.todayHpp ?? 11610000;
  const margin = metrics?.todayMargin ?? (omset > hpp ? omset - hpp : 3240000);
  const biayaAdmin = metrics?.todayMarketplaceFee ?? Math.round(omset * 0.08); // estimasi 8% fee
  const refund = metrics?.todayRefund ?? 0;

  const items = [
    {
      id: 'omset',
      label: 'Penjualan',
      sublabel: 'Hari ini',
      value: formatRupiah(omset),
      icon: DollarSign,
      gradient: 'linear-gradient(135deg, #10b981, #059669)', // Emerald
      textColor: '#059669',
      bgLight: '#ecfdf5',
      borderColor: '#a7f3d0'
    },
    {
      id: 'admin',
      label: 'Biaya Admin',
      sublabel: 'Marketplace',
      value: formatRupiah(biayaAdmin),
      icon: Percent,
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)', // Amber
      textColor: '#d97706',
      bgLight: '#fffbeb',
      borderColor: '#fde68a'
    },
    {
      id: 'refund',
      label: 'Refund',
      sublabel: 'Retur/Batal',
      value: formatRupiah(refund),
      icon: RotateCcw,
      gradient: 'linear-gradient(135deg, #ef4444, #dc2626)', // Red
      textColor: '#dc2626',
      bgLight: '#fef2f2',
      borderColor: '#fecaca'
    },
    {
      id: 'hpp',
      label: 'HPP',
      sublabel: 'Modal Pokok',
      value: formatRupiah(hpp),
      icon: Box,
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)', // Indigo
      textColor: '#4f46e5',
      bgLight: '#eef2ff',
      borderColor: '#c7d2fe'
    },
    {
      id: 'margin',
      label: 'Margin',
      sublabel: 'Laba Bersih',
      value: formatRupiah(margin),
      icon: TrendingUp,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)', // Sky blue
      textColor: '#0284c7',
      bgLight: '#f0f9ff',
      borderColor: '#bae6fd'
    }
  ];

  return (
    <div className="quick-finance-wrapper">
      <div className="finance-metric-strip">
        {items.map((item) => {
          const IconComp = item.icon;
          return (
            <div 
              key={item.id} 
              className="finance-strip-card"
              onClick={onOpenFinance}
              style={{
                borderColor: item.borderColor,
                background: '#ffffff'
              }}
            >
              <div 
                className="finance-strip-icon"
                style={{ background: item.gradient }}
              >
                <IconComp size={15} strokeWidth={2.4} color="#ffffff" />
              </div>
              <div className="finance-strip-info">
                <span className="finance-strip-label">{item.label}</span>
                <span className="finance-strip-val" style={{ color: item.textColor }}>
                  {item.value}
                </span>
                <span className="finance-strip-sub">{item.sublabel}</span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
