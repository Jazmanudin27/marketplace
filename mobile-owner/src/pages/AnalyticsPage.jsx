import React, { useState, useMemo } from 'react';
import { 
  BarChart3, 
  TrendingUp, 
  PieChart as PieIcon, 
  ShoppingBag
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';

export default function AnalyticsPage({ metrics, orders }) {
  const [activeRange, setActiveRange] = useState('7d'); // 'today', '7d', '30d'

  // Hitung data tren hari dari array orders real database
  const analyticsData = useMemo(() => {
    const list = Array.isArray(orders) ? orders : [];
    
    // Kelompokkan omset & margin per channel real dari database
    const channelMap = {};
    let totalAllOmset = 0;

    list.forEach(order => {
      const chName = order.storeName || order.channel || 'Marketplace';
      const orderAmount = Number(order.releasedValue || order.totalAmount || 0);
      totalAllOmset += orderAmount;

      if (!channelMap[chName]) {
        channelMap[chName] = { omset: 0, count: 0 };
      }
      channelMap[chName].omset += orderAmount;
      channelMap[chName].count += 1;
    });

    const channelColors = ['#f97316', '#0f172a', '#10b981', '#2563eb', '#8b5cf6'];
    const channelBreakdown = Object.keys(channelMap).map((name, i) => {
      const ch = channelMap[name];
      const share = totalAllOmset > 0 ? Math.round((ch.omset / totalAllOmset) * 100) : 0;
      return {
        name,
        share,
        omset: ch.omset,
        color: channelColors[i % channelColors.length]
      };
    });

    // 7 hari terakhir (hari ini + 6 hari sebelumnya)
    const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    const now = new Date();
    const last7Days = [];

    for (let i = 6; i >= 0; i--) {
      const d = new Date();
      d.setDate(now.getDate() - i);
      const dayName = days[d.getDay()];
      const dateStr = d.toISOString().slice(0, 10);
      const isToday = i === 0;

      // Filter orders di tanggal ini
      const dayOrders = list.filter(o => {
        const oDate = (o.completedAt || o.createdAt || '').slice(0, 10);
        return oDate === dateStr;
      });

      const dayOmset = isToday 
        ? (metrics?.todayOmset || dayOrders.reduce((sum, o) => sum + Number(o.releasedValue || o.totalAmount || 0), 0))
        : dayOrders.reduce((sum, o) => sum + Number(o.releasedValue || o.totalAmount || 0), 0);

      const dayMargin = isToday 
        ? (metrics?.todayMargin || dayOrders.reduce((sum, o) => sum + Number(o.margin || 0), 0))
        : dayOrders.reduce((sum, o) => sum + Number(o.margin || 0), 0);

      last7Days.push({
        day: dayName,
        date: dateStr,
        omset: dayOmset,
        margin: dayMargin,
        orders: dayOrders.length,
        isToday
      });
    }

    const maxOmset = Math.max(...last7Days.map(d => d.omset), 1);
    const totalWeeklyOmset = last7Days.reduce((acc, d) => acc + d.omset, 0);
    const totalWeeklyMargin = last7Days.reduce((acc, d) => acc + d.margin, 0);
    const totalWeeklyOrders = last7Days.reduce((acc, d) => acc + d.orders, 0);

    return {
      dailyData: last7Days,
      maxOmset,
      totalWeeklyOmset,
      totalWeeklyMargin,
      totalWeeklyOrders,
      channelBreakdown
    };
  }, [orders, metrics]);

  const {
    dailyData,
    maxOmset,
    totalWeeklyOmset,
    totalWeeklyMargin,
    totalWeeklyOrders,
    channelBreakdown
  } = analyticsData;

  const currentOmset = activeRange === 'today' ? (metrics?.todayOmset || 0) : totalWeeklyOmset;
  const currentMargin = activeRange === 'today' ? (metrics?.todayMargin || 0) : totalWeeklyMargin;
  const currentOrders = activeRange === 'today' ? (metrics?.ordersCompletedToday || 0) : totalWeeklyOrders;

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
          7 Hari
        </button>
        <button 
          type="button" 
          className={`range-pill ${activeRange === '30d' ? 'active' : ''}`}
          onClick={() => setActiveRange('30d')}
        >
          Bulan Ini
        </button>
      </div>

      {/* Hero Stats Card */}
      <div className="analytics-hero-card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span style={{ fontSize: '12px', color: '#93c5fd', fontWeight: '600' }}>
            Total Omset ({activeRange === 'today' ? 'Hari Ini' : activeRange === '7d' ? '7 Hari' : 'Bulan Ini'})
          </span>
          <span className="analytics-badge-tag">
            Live Database
          </span>
        </div>

        <div style={{ fontSize: '26px', fontWeight: '800', letterSpacing: '-0.5px', marginTop: '4px' }}>
          {formatRupiah(currentOmset)}
        </div>

        <div className="analytics-hero-subgrid">
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Margin Bersih:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#38bdf8' }}>
              {formatRupiah(currentMargin)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Total Order:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#4ade80' }}>
              {currentOrders} Order
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
          <span style={{ fontSize: '11px', color: '#64748b' }}>Data Asli Database</span>
        </div>

        {/* SVG/HTML Pure CSS Bar Chart */}
        <div className="bar-chart-container">
          <div className="bar-chart-bars">
            {dailyData.map((item, idx) => {
              const heightPct = maxOmset > 0 ? Math.round((item.omset / maxOmset) * 100) : 0;
              return (
                <div key={idx} className="bar-col">
                  <div className="bar-val-bubble">
                    {item.omset > 0 ? `${(item.omset / 1000000).toFixed(1)}jt` : '0'}
                  </div>
                  <div className="bar-track">
                    <div 
                      className={`bar-fill ${item.isToday ? 'today' : ''}`}
                      style={{ height: `${Math.max(4, heightPct)}%` }}
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
            <span>7 Hari Terakhir</span>
          </div>
          <div className="legend-item">
            <span className="legend-dot" style={{ background: '#10b981' }}></span>
            <span>Hari Ini</span>
          </div>
        </div>
      </div>

      {/* Card 2: Kontribusi Channel / Toko */}
      {channelBreakdown.length > 0 && (
        <div className="analytics-card">
          <div className="analytics-card-header">
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <PieIcon size={18} color="#f97316" />
              <h3 style={{ fontSize: '14.5px', fontWeight: '800', color: '#0f172a' }}>
                Kontribusi Toko & Channel
              </h3>
            </div>
          </div>

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
                    {ch.share}%
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
