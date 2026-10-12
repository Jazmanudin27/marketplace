import React, { useState, useEffect, useMemo } from 'react';
import { 
  BarChart3, 
  TrendingUp, 
  PieChart as PieIcon, 
  ShoppingBag,
  Calendar,
  RefreshCw,
  SlidersHorizontal
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { fetchLaporanPenjualan } from '../api/metricsApi';

export default function AnalyticsPage({ metrics, orders }) {
  // Helper Date Defaults
  const pad = (n) => String(n).padStart(2, '0');
  const getTodayStr = () => {
    const d = new Date();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  };
  const getDaysAgoStr = (days) => {
    const d = new Date();
    d.setDate(d.getDate() - days);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  };
  const getStartOfMonthStr = () => {
    const d = new Date();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`;
  };

  const [dateFrom, setDateFrom] = useState(getDaysAgoStr(6));
  const [dateTo, setDateTo] = useState(getTodayStr());
  const [reportData, setReportData] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  // Load live data based on date range
  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      setIsLoading(true);
      try {
        const res = await fetchLaporanPenjualan({ dateFrom, dateTo });
        if (isMounted && res && res.success) {
          setReportData(res);
        }
      } catch (err) {
        console.warn('Analytics report fetch failed:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadData();
    return () => { isMounted = false; };
  }, [dateFrom, dateTo]);

  // Aggregate Data for Graphs & Cards
  const analytics = useMemo(() => {
    // 1. If reportData is available from API, prioritize it
    if (reportData?.success) {
      const summary = reportData.summary || {};
      const byDate = reportData.byDate || [];
      const byChannel = reportData.byChannel || [];

      const totalOmset = summary.omset || summary.netReleased || 0;
      const totalMargin = summary.margin || 0;
      const totalOrders = summary.totalOrders || 0;

      // Format Channel/Store Breakdown
      const channelColors = ['#f97316', '#2563eb', '#10b981', '#8b5cf6', '#0ea5e9', '#ec4899'];
      const channelBreakdown = byChannel.map((ch, i) => {
        const amt = Number(ch.netReleased || ch.omset || 0);
        const share = totalOmset > 0 ? Math.round((amt / totalOmset) * 100) : 0;
        return {
          name: ch.channel || ch.channelName || 'Marketplace',
          share,
          omset: amt,
          color: channelColors[i % channelColors.length]
        };
      });

      // Generate Daily Trend Bars
      const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
      const dailyData = byDate.map(d => {
        const dt = new Date(d.date + 'T00:00:00');
        const dayName = isNaN(dt.getDay()) ? '' : days[dt.getDay()];
        return {
          day: dayName,
          date: d.date,
          omset: Number(d.netReleased || d.omset || 0),
          margin: Number(d.margin || 0),
          orders: Number(d.totalOrders || 0),
          isToday: d.date === getTodayStr()
        };
      });

      const maxOmset = Math.max(...dailyData.map(d => d.omset), 1);

      return {
        totalOmset,
        totalMargin,
        totalOrders,
        dailyData,
        maxOmset,
        channelBreakdown
      };
    }

    // 2. Fallback: compute from prop orders
    const list = Array.isArray(orders) ? orders : [];
    const filteredOrders = list.filter(o => {
      const oDate = (o.completedAt || o.createdAt || '').slice(0, 10);
      return (!dateFrom || oDate >= dateFrom) && (!dateTo || oDate <= dateTo);
    });

    const channelMap = {};
    let totalOmset = 0;
    let totalMargin = 0;

    filteredOrders.forEach(o => {
      const chName = o.storeName || o.channel || 'Marketplace';
      const amt = Number(o.releasedValue || o.totalAmount || 0);
      const mgn = Number(o.margin || 0);
      totalOmset += amt;
      totalMargin += mgn;

      if (!channelMap[chName]) {
        channelMap[chName] = { omset: 0, count: 0 };
      }
      channelMap[chName].omset += amt;
      channelMap[chName].count += 1;
    });

    const channelColors = ['#f97316', '#2563eb', '#10b981', '#8b5cf6', '#0ea5e9'];
    const channelBreakdown = Object.keys(channelMap).map((name, i) => {
      const ch = channelMap[name];
      const share = totalOmset > 0 ? Math.round((ch.omset / totalOmset) * 100) : 0;
      return {
        name,
        share,
        omset: ch.omset,
        color: channelColors[i % channelColors.length]
      };
    });

    // Daily breakdown from filtered orders
    const dateGroups = {};
    filteredOrders.forEach(o => {
      const d = (o.completedAt || o.createdAt || '').slice(0, 10);
      if (!d) return;
      if (!dateGroups[d]) dateGroups[d] = { omset: 0, margin: 0, count: 0 };
      dateGroups[d].omset += Number(o.releasedValue || o.totalAmount || 0);
      dateGroups[d].margin += Number(o.margin || 0);
      dateGroups[d].count += 1;
    });

    const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    const sortedDates = Object.keys(dateGroups).sort();
    const dailyData = sortedDates.map(dStr => {
      const dt = new Date(dStr + 'T00:00:00');
      const dayName = isNaN(dt.getDay()) ? '' : days[dt.getDay()];
      return {
        day: dayName,
        date: dStr,
        omset: dateGroups[dStr].omset,
        margin: dateGroups[dStr].margin,
        orders: dateGroups[dStr].count,
        isToday: dStr === getTodayStr()
      };
    });

    const maxOmset = Math.max(...dailyData.map(d => d.omset), 1);

    return {
      totalOmset,
      totalMargin,
      totalOrders: filteredOrders.length,
      dailyData,
      maxOmset,
      channelBreakdown
    };
  }, [reportData, orders, dateFrom, dateTo]);

  // Quick Preset Actions
  const applyPreset = (preset) => {
    if (preset === 'today') {
      const t = getTodayStr();
      setDateFrom(t);
      setDateTo(t);
    } else if (preset === '7d') {
      setDateFrom(getDaysAgoStr(6));
      setDateTo(getTodayStr());
    } else if (preset === '30d') {
      setDateFrom(getDaysAgoStr(29));
      setDateTo(getTodayStr());
    } else if (preset === 'this_month') {
      setDateFrom(getStartOfMonthStr());
      setDateTo(getTodayStr());
    }
  };

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px', paddingBottom: '32px' }}>
      
      {/* ── CARD FILTER TANGGAL: DARI - SAMPAI ── */}
      <div style={{
        background: '#ffffff',
        borderRadius: '18px',
        padding: '14px',
        border: '1px solid #e2e8f0',
        boxShadow: '0 2px 8px rgba(0,0,0,0.03)',
        marginBottom: '14px',
        display: 'flex',
        flexDirection: 'column',
        gap: '10px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            <Calendar size={15} color="#2563eb" />
            <span style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>
              Filter Tanggal Analisa
            </span>
          </div>

          {isLoading && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '11px', color: '#2563eb' }}>
              <RefreshCw size={12} className="spin-animate" />
              <span>Memuat...</span>
            </div>
          )}
        </div>

        {/* Input Tanggal: Dari & Sampai */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
          <div>
            <label style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', display: 'block', marginBottom: '3px' }}>
              Dari Tanggal
            </label>
            <input 
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 8px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '11px',
                fontWeight: '600',
                outline: 'none',
                background: '#f8fafc',
                color: '#0f172a'
              }}
            />
          </div>

          <div>
            <label style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', display: 'block', marginBottom: '3px' }}>
              Sampai Tanggal
            </label>
            <input 
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 8px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '11px',
                fontWeight: '600',
                outline: 'none',
                background: '#f8fafc',
                color: '#0f172a'
              }}
            />
          </div>
        </div>

        {/* Quick Range Presets */}
        <div style={{ display: 'flex', gap: '6px', paddingTop: '4px', borderTop: '1px dashed #f1f5f9' }}>
          {[
            { id: 'today', label: 'Hari Ini' },
            { id: '7d', label: '7 Hari' },
            { id: 'this_month', label: 'Bulan Ini' },
            { id: '30d', label: '30 Hari' },
          ].map((pill) => (
            <button
              key={pill.id}
              type="button"
              onClick={() => applyPreset(pill.id)}
              style={{
                flex: 1,
                padding: '5px 0',
                borderRadius: '8px',
                border: '1px solid #e2e8f0',
                background: '#f8fafc',
                color: '#475569',
                fontSize: '11px',
                fontWeight: '700',
                cursor: 'pointer'
              }}
            >
              {pill.label}
            </button>
          ))}
        </div>
      </div>

      {/* Hero Stats Card */}
      <div className="analytics-hero-card" style={{ marginBottom: '14px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span style={{ fontSize: '12px', color: '#93c5fd', fontWeight: '600' }}>
            Total Omset ({dateFrom} s/d {dateTo})
          </span>
          <span className="analytics-badge-tag">
            {isLoading ? 'Mengupdate...' : 'Data Aktual'}
          </span>
        </div>

        <div style={{ fontSize: '26px', fontWeight: '800', letterSpacing: '-0.5px', marginTop: '4px' }}>
          {formatRupiah(analytics.totalOmset)}
        </div>

        <div className="analytics-hero-subgrid">
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Margin Bersih:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#38bdf8' }}>
              {formatRupiah(analytics.totalMargin)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Total Order:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#4ade80' }}>
              {analytics.totalOrders} Order
            </div>
          </div>
        </div>
      </div>

      {/* Card 1: Grafik Batang Tren Omset Harian */}
      <div className="analytics-card" style={{ marginBottom: '14px' }}>
        <div className="analytics-card-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <BarChart3 size={18} color="#2563eb" />
            <h3 style={{ fontSize: '14.5px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
              Tren Omset Harian
            </h3>
          </div>
          <span style={{ fontSize: '11px', color: '#64748b' }}>
            {analytics.dailyData.length} Hari Terdata
          </span>
        </div>

        {/* SVG/HTML Pure CSS Bar Chart */}
        {analytics.dailyData.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '30px 10px', color: '#94a3b8', fontSize: '12px' }}>
            Tidak ada transaksi pada rentang tanggal ini.
          </div>
        ) : (
          <div className="bar-chart-container" style={{ overflowX: 'auto', paddingBottom: '6px' }}>
            <div className="bar-chart-bars" style={{ minWidth: analytics.dailyData.length > 7 ? `${analytics.dailyData.length * 48}px` : '100%' }}>
              {analytics.dailyData.map((item, idx) => {
                const heightPct = analytics.maxOmset > 0 ? Math.round((item.omset / analytics.maxOmset) * 100) : 0;
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
                    <span className={`bar-label ${item.isToday ? 'today-text' : ''}`} style={{ fontSize: '9.5px' }}>
                      {item.day ? `${item.day} ` : ''}{item.date.slice(8)}
                    </span>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        <div className="chart-legend-row">
          <div className="legend-item">
            <span className="legend-dot" style={{ background: '#2563eb' }}></span>
            <span>Rentang Terpilih</span>
          </div>
          <div className="legend-item">
            <span className="legend-dot" style={{ background: '#10b981' }}></span>
            <span>Hari Ini</span>
          </div>
        </div>
      </div>

      {/* Card 2: Kontribusi Channel / Toko */}
      {analytics.channelBreakdown.length > 0 && (
        <div className="analytics-card">
          <div className="analytics-card-header">
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <PieIcon size={18} color="#f97316" />
              <h3 style={{ fontSize: '14.5px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                Kontribusi Toko & Channel
              </h3>
            </div>
          </div>

          <div className="stacked-progress-bar">
            {analytics.channelBreakdown.map((ch, idx) => (
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
            {analytics.channelBreakdown.map((ch, idx) => (
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
