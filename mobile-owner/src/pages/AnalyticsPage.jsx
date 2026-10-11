import React, { useState } from 'react';
import { 
  BarChart3, 
  TrendingUp, 
  PieChart as PieIcon, 
  Calendar,
  Layers,
  ArrowUpRight,
  ShoppingBag
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';

export default function AnalyticsPage({ metrics, orders }) {
  const [activeRange, setActiveRange] = useState('7d'); // 'today', '7d', '30d'

  // Data series 7 hari
  const dailyData = [
    { day: 'Sen', date: '05 Okt', omset: 12200000, margin: 2650000, orders: 74 },
    { day: 'Sel', date: '06 Okt', omset: 13800000, margin: 3100000, orders: 81 },
    { day: 'Rab', date: '07 Okt', omset: 11400000, margin: 2400000, orders: 69 },
    { day: 'Kam', date: '08 Okt', omset: 15600000, margin: 3450000, orders: 92 },
    { day: 'Jum', date: '09 Okt', omset: 14100000, margin: 3050000, orders: 86 },
    { day: 'Sab', date: '10 Okt', omset: 16900000, margin: 3800000, orders: 104 },
    { day: 'Min', date: '11 Okt', omset: metrics?.todayOmset || 14850000, margin: metrics?.todayMargin || 3240000, orders: 89, isToday: true }
  ];

  // Channel share breakdown
  const channelBreakdown = [
    { name: 'Shopee', share: 58, omset: 8613000, color: '#f97316' },
    { name: 'TikTok Shop', share: 27, omset: 4009500, color: '#0f172a' },
    { name: 'Tokopedia', share: 10, omset: 1485000, color: '#10b981' },
    { name: 'POS Offline', share: 5, omset: 742500, color: '#2563eb' }
  ];

  const maxOmset = Math.max(...dailyData.map((d) => d.omset));
  const totalWeeklyOmset = dailyData.reduce((acc, d) => acc + d.omset, 0);
  const totalWeeklyMargin = dailyData.reduce((acc, d) => acc + d.margin, 0);
  const totalWeeklyOrders = dailyData.reduce((acc, d) => acc + d.orders, 0);

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px' }}>
      {/* Filter Waktu Range */}
      <div className="analytics-range-pills">
        <button 
          type="button" 
          className={`range-pill ${activeRange === 'today' ? 'active' : ''}`}
          onClick={() => setActiveRange('today')}
        >
          Hari Ini
        </button>
        <button 
          type="button" 
          className={`range-pill ${activeRange === '7d' ? 'active' : ''}`}
          onClick={() => setActiveRange('7d')}
        >
          7 Hari Terakhir
        </button>
        <button 
          type="button" 
          className={`range-pill ${activeRange === '30d' ? 'active' : ''}`}
          onClick={() => setActiveRange('30d')}
        >
          30 Hari
        </button>
      </div>

      {/* Hero Stats Card */}
      <div className="analytics-hero-card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span style={{ fontSize: '12px', color: '#93c5fd', fontWeight: '600' }}>
            Total Omset ({activeRange === '7d' ? '7 Hari' : activeRange === '30d' ? 'Bulan Ini' : 'Hari Ini'})
          </span>
          <span className="analytics-badge-tag">
            +14.2% vs Pekan Lalu
          </span>
        </div>

        <div style={{ fontSize: '26px', fontWeight: '800', letterSpacing: '-0.5px', marginTop: '4px' }}>
          {formatRupiah(activeRange === 'today' ? (metrics?.todayOmset || 14850000) : totalWeeklyOmset)}
        </div>

        <div className="analytics-hero-subgrid">
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Margin Bersih:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#38bdf8' }}>
              {formatRupiah(activeRange === 'today' ? (metrics?.todayMargin || 3240000) : totalWeeklyMargin)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Total Order:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#4ade80' }}>
              {activeRange === 'today' ? 89 : totalWeeklyOrders} Pesanan
            </div>
          </div>
        </div>
      </div>

      {/* Card 1: Grafik Batang Tren Omset 7 Hari */}
      <div className="analytics-card">
        <div className="analytics-card-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <BarChart3 size={18} color="#2563eb" />
            <h3 style={{ fontSize: '14.5px', fontWeight: '800', color: '#0f172a' }}>
              Tren Omset Harian
            </h3>
          </div>
          <span style={{ fontSize: '11px', color: '#64748b' }}>Skala Juta Rp</span>
        </div>

        {/* SVG/HTML Pure CSS Bar Chart */}
        <div className="bar-chart-container">
          <div className="bar-chart-bars">
            {dailyData.map((item, idx) => {
              const heightPct = Math.round((item.omset / maxOmset) * 100);
              return (
                <div key={idx} className="bar-col">
                  <div className="bar-val-bubble">
                    {(item.omset / 1000000).toFixed(1)}jt
                  </div>
                  <div className="bar-track">
                    <div 
                      className={`bar-fill ${item.isToday ? 'today' : ''}`}
                      style={{ height: `${heightPct}%` }}
                    />
                  </div>
                  <span className={`bar-label ${item.isToday ? 'today-text' : ''}`}>
                    {item.day}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        <div className="chart-legend-row">
          <div className="legend-item">
            <span className="legend-dot" style={{ background: '#2563eb' }}></span>
            <span>Hari Biasa</span>
          </div>
          <div className="legend-item">
            <span className="legend-dot" style={{ background: '#10b981' }}></span>
            <span>Hari Ini (Aktif)</span>
          </div>
        </div>
      </div>

      {/* Card 2: Pembagian Kontribusi Channel Marketplace */}
      <div className="analytics-card">
        <div className="analytics-card-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <PieIcon size={18} color="#f97316" />
            <h3 style={{ fontSize: '14.5px', fontWeight: '800', color: '#0f172a' }}>
              Kontribusi Marketplace
            </h3>
          </div>
        </div>

        {/* Multi-segmented Progress Bar */}
        <div className="stacked-progress-bar">
          {channelBreakdown.map((ch, idx) => (
            <div 
              key={idx} 
              style={{ 
                width: `${ch.share}%`, 
                background: ch.color,
                height: '100%' 
              }}
              title={`${ch.name}: ${ch.share}%`}
            />
          ))}
        </div>

        {/* Channel Details List */}
        <div className="channel-list">
          {channelBreakdown.map((ch, idx) => (
            <div key={idx} className="channel-row">
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span className="channel-bullet" style={{ background: ch.color }} />
                <span style={{ fontSize: '12.5px', fontWeight: '700', color: '#0f172a' }}>{ch.name}</span>
              </div>
              <div style={{ textAlign: 'right' }}>
                <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
                  {formatRupiah(ch.omset)}
                </div>
                <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                  {ch.share}% dari omset
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
