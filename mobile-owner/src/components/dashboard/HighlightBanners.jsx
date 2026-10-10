import React from 'react';
import { TrendingUp, Target, ArrowUpRight } from 'lucide-react';
import { formatRupiah } from '../../utils/formatters';

export default function HighlightBanners({ metrics, onOpenFinance, onOpenTarget }) {
  return (
    <div className="highlight-banners-row">
      {/* Banner 1: Omset & Margin Bersih Hari Ini */}
      <div 
        className="highlight-banner-card banner-green"
        onClick={onOpenFinance}
      >
        <div className="banner-header">
          <div className="banner-icon-circle">
            <TrendingUp size={20} strokeWidth={2.5} />
          </div>
          <div>
            <span className="banner-title">Omset Hari Ini</span>
          </div>
        </div>
        <div className="banner-value">
          {formatRupiah(metrics?.todayOmset || 14850000)}
        </div>
        <div className="banner-sub">
          Laba Bersih: {formatRupiah(metrics?.todayMargin || 3240000)}
        </div>
      </div>

      {/* Banner 2: Target Komisi & Realisasi Tim */}
      <div 
        className="highlight-banner-card banner-red"
        onClick={onOpenTarget}
      >
        <div className="banner-header">
          <div className="banner-icon-circle">
            <Target size={20} strokeWidth={2.5} />
          </div>
          <div>
            <span className="banner-title">Target Komisi</span>
          </div>
        </div>
        <div className="banner-value">
          {metrics?.targetProgressPercent || 84.5}% Tercapai
        </div>
        <div className="banner-sub">
          Margin: {formatRupiah(metrics?.actualMonthlyMargin || 126750000)}
        </div>
      </div>
    </div>
  );
}
