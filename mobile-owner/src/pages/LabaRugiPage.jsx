import React, { useState, useEffect } from 'react';
import { DollarSign, TrendingUp, TrendingDown, Percent, Calendar, PieChart, ShoppingBag, Receipt, Building2 } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { fetchLabaRugi } from '../api/metricsApi';

export default function LabaRugiPage() {
  const [period, setPeriod] = useState('month'); // 'today', 'month', 'year'
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setLoading(true);
      const res = await fetchLabaRugi(period);
      if (res && res.success) {
        setData(res.data);
      }
      setLoading(false);
    }
    load();
  }, [period]);

  const lr = data || {
    totalOmset: 0,
    onlineRevenue: 0,
    offlineRevenue: 0,
    totalHpp: 0,
    marketplaceFee: 0,
    labaKotor: 0,
    grossMarginPct: 0,
    totalOperasional: 0,
    expenseCategories: [],
    labaBersih: 0,
    netMarginPct: 0,
    ordersCount: 0
  };

  const isNetProfitPositive = lr.labaBersih >= 0;

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      
      {/* Period Filter Pills */}
      <div style={{
        display: 'flex',
        background: '#e2e8f0',
        borderRadius: '999px',
        padding: '3px',
        gap: '4px'
      }}>
        {[
          { id: 'today', label: 'Hari Ini' },
          { id: 'month', label: 'Bulan Ini' },
          { id: 'year', label: 'Tahun Ini' },
        ].map((tab) => {
          const isActive = period === tab.id;
          return (
            <button
              key={tab.id}
              type="button"
              onClick={() => setPeriod(tab.id)}
              style={{
                flex: 1,
                border: 'none',
                background: isActive ? '#ffffff' : 'transparent',
                color: isActive ? '#0f172a' : '#64748b',
                fontWeight: '700',
                fontSize: '12px',
                padding: '7px 0',
                borderRadius: '999px',
                cursor: 'pointer',
                boxShadow: isActive ? '0 2px 4px rgba(0,0,0,0.06)' : 'none',
                transition: 'all 0.15s ease'
              }}
            >
              {tab.label}
            </button>
          );
        })}
      </div>

      {/* Main Net Profit (Laba Bersih) Banner */}
      <div style={{
        background: isNetProfitPositive 
          ? 'linear-gradient(135deg, #059669, #047857)' 
          : 'linear-gradient(135deg, #dc2626, #b91c1c)',
        borderRadius: '24px',
        padding: '20px',
        color: '#ffffff',
        boxShadow: isNetProfitPositive 
          ? '0 10px 25px rgba(5, 150, 105, 0.35)' 
          : '0 10px 25px rgba(220, 38, 38, 0.35)',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span style={{ fontSize: '12px', color: '#e2e8f0', fontWeight: '600' }}>
            Laba Bersih (Net Profit)
          </span>
          <span style={{
            fontSize: '11px',
            background: 'rgba(255, 255, 255, 0.2)',
            padding: '3px 9px',
            borderRadius: '999px',
            fontWeight: '800'
          }}>
            Margin {lr.netMarginPct}%
          </span>
        </div>

        <div style={{ fontSize: '28px', fontWeight: '800', letterSpacing: '-0.5px' }}>
          {loading ? (
            <div className="skeleton-shimmer skeleton-text" style={{ width: '160px', height: '28px' }} />
          ) : (
            formatRupiah(lr.labaBersih)
          )}
        </div>

        <div style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: '8px',
          paddingTop: '10px',
          borderTop: '1px solid rgba(255, 255, 255, 0.15)'
        }}>
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Total Omset:</span>
            <div style={{ fontSize: '14px', fontWeight: '700', color: '#ffffff' }}>
              {formatRupiah(lr.totalOmset)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#cbd5e1' }}>Laba Kotor:</span>
            <div style={{ fontSize: '14px', fontWeight: '700', color: '#fef08a' }}>
              {formatRupiah(lr.labaKotor)}
            </div>
          </div>
        </div>
      </div>

      {/* Rincian Komponen Laba Rugi */}
      <div style={{
        background: '#ffffff',
        borderRadius: '20px',
        padding: '16px',
        border: '1px solid #e2e8f0',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <h4 style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
          📊 Struktur Laba Rugi ERP
        </h4>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '12px' }}>
          
          {/* Omset Penjualan */}
          <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '6px', borderBottom: '1px solid #f1f5f9' }}>
            <span style={{ color: '#334155', fontWeight: '600' }}>1. Pendapatan / Omset</span>
            <strong style={{ color: '#0f172a' }}>{formatRupiah(lr.totalOmset)}</strong>
          </div>
          <div style={{ paddingLeft: '12px', display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b' }}>
            <span>• Online Marketplace ({lr.ordersCount} order)</span>
            <span>{formatRupiah(lr.onlineRevenue)}</span>
          </div>
          {lr.offlineRevenue > 0 && (
            <div style={{ paddingLeft: '12px', display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b' }}>
              <span>• Offline POS Toko Fisik</span>
              <span>{formatRupiah(lr.offlineRevenue)}</span>
            </div>
          )}

          {/* HPP Modal */}
          <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '6px', borderBottom: '1px solid #f1f5f9', paddingTop: '4px' }}>
            <span style={{ color: '#334155', fontWeight: '600' }}>2. HPP Modal Pokok Produk</span>
            <strong style={{ color: '#4f46e5' }}>- {formatRupiah(lr.totalHpp)}</strong>
          </div>

          {/* Potongan Fee Platform */}
          <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '6px', borderBottom: '1px solid #f1f5f9' }}>
            <span style={{ color: '#334155', fontWeight: '600' }}>3. Biaya Admin Marketplace</span>
            <strong style={{ color: '#d97706' }}>- {formatRupiah(lr.marketplaceFee)}</strong>
          </div>

          {/* Laba Kotor Subtotal */}
          <div style={{ 
            display: 'flex', 
            justifyContent: 'space-between', 
            padding: '6px 10px', 
            background: '#f8fafc', 
            borderRadius: '10px',
            border: '1px solid #e2e8f0' 
          }}>
            <strong style={{ color: '#0f172a' }}>= Laba Kotor (Gross Profit)</strong>
            <strong style={{ color: '#059669' }}>{formatRupiah(lr.labaKotor)} ({lr.grossMarginPct}%)</strong>
          </div>

          {/* Beban Operasional */}
          <div style={{ display: 'flex', justifyContent: 'space-between', paddingBottom: '6px', borderBottom: '1px solid #f1f5f9', paddingTop: '6px' }}>
            <span style={{ color: '#334155', fontWeight: '600' }}>4. Beban Operasional / Pengeluaran</span>
            <strong style={{ color: '#ef4444' }}>- {formatRupiah(lr.totalOperasional)}</strong>
          </div>

          {lr.expenseCategories && lr.expenseCategories.length > 0 ? (
            lr.expenseCategories.map((exp, idx) => (
              <div key={idx} style={{ paddingLeft: '12px', display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b' }}>
                <span>• {exp.name}</span>
                <span>{formatRupiah(exp.amount)}</span>
              </div>
            ))
          ) : (
            <div style={{ paddingLeft: '12px', fontSize: '11px', color: '#94a3b8' }}>
              • Belum ada catatan biaya operasional di periode ini.
            </div>
          )}

          {/* Laba Bersih Final */}
          <div style={{ 
            display: 'flex', 
            justifyContent: 'space-between', 
            padding: '8px 10px', 
            background: isNetProfitPositive ? '#ecfdf5' : '#fef2f2', 
            borderRadius: '10px',
            border: `1px solid ${isNetProfitPositive ? '#a7f3d0' : '#fecaca'}`,
            marginTop: '4px'
          }}>
            <strong style={{ color: '#0f172a' }}>= Laba Bersih (Net Profit)</strong>
            <strong style={{ color: isNetProfitPositive ? '#059669' : '#dc2626', fontSize: '13px' }}>
              {formatRupiah(lr.labaBersih)}
            </strong>
          </div>

        </div>
      </div>

    </div>
  );
}
