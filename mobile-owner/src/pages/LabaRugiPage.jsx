import React, { useState, useEffect } from 'react';
import { 
  DollarSign, TrendingUp, TrendingDown, Percent, Calendar, 
  PieChart, ShoppingBag, Receipt, Building2, ChevronDown, 
  ChevronUp, Store, FileText, Wallet 
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { fetchLabaRugi } from '../api/metricsApi';

export default function LabaRugiPage() {
  const now = new Date();
  const [selectedMonth, setSelectedMonth] = useState(now.getMonth() + 1); // 1 - 12
  const [selectedYear, setSelectedYear] = useState(now.getFullYear()); // 2024, 2025, 2026, 2027
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  // Accordion drilldown toggle states
  const [showSalesDetail, setShowSalesDetail] = useState(false);
  const [showHppDetail, setShowHppDetail] = useState(false);
  const [expandedExpenseCat, setExpandedExpenseCat] = useState(null);

  useEffect(() => {
    async function load() {
      setLoading(true);
      const res = await fetchLabaRugi({ month: selectedMonth, year: selectedYear });
      if (res && res.success) {
        setData(res.data);
      }
      setLoading(false);
    }
    load();
  }, [selectedMonth, selectedYear]);

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
    storeBreakdown: [],
    labaBersih: 0,
    netMarginPct: 0,
    ordersCount: 0
  };

  const isNetProfitPositive = lr.labaBersih >= 0;
  const storeBreakdown = lr.storeBreakdown || [];

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '14px', paddingBottom: '32px' }}>
      
      {/* Filter Berdasarkan Bulan dan Tahun */}
      <div style={{
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        background: '#ffffff',
        borderRadius: '16px',
        padding: '10px 14px',
        border: '1px solid #e2e8f0',
        boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
      }}>
        <div style={{ flex: 1.4, display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Calendar size={15} color="#7c3aed" />
          <select
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(Number(e.target.value))}
            style={{
              width: '100%',
              border: 'none',
              outline: 'none',
              background: 'transparent',
              fontSize: '12.5px',
              fontWeight: '700',
              color: '#0f172a',
              cursor: 'pointer'
            }}
          >
            {[
              { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
              { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
              { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
              { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' }
            ].map(m => (
              <option key={m.v} value={m.v}>{m.l}</option>
            ))}
          </select>
        </div>

        <div style={{ width: '1px', height: '18px', background: '#cbd5e1' }} />

        <div style={{ flex: 1, display: 'flex', alignItems: 'center', gap: '4px' }}>
          <select
            value={selectedYear}
            onChange={(e) => setSelectedYear(Number(e.target.value))}
            style={{
              width: '100%',
              border: 'none',
              outline: 'none',
              background: 'transparent',
              fontSize: '12.5px',
              fontWeight: '700',
              color: '#0f172a',
              cursor: 'pointer'
            }}
          >
            {[2024, 2025, 2026, 2027].map(y => (
              <option key={y} value={y}>{y}</option>
            ))}
          </select>
        </div>
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

      {/* Rincian Komponen Laba Rugi dengan Accordion Drilldown */}
      <div style={{
        background: '#ffffff',
        borderRadius: '20px',
        padding: '16px',
        border: '1px solid #e2e8f0',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <h4 style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
            📊 Struktur Laba Rugi ERP
          </h4>
          <span style={{ fontSize: '10px', color: '#94a3b8' }}>
            Tap baris untuk rincian
          </span>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '12px' }}>
          
          {/* ── 1. Omset Penjualan (Accordion Trigger) ── */}
          <div 
            onClick={() => setShowSalesDetail(!showSalesDetail)}
            style={{ 
              display: 'flex', 
              justifyContent: 'space-between', 
              alignItems: 'center',
              padding: '6px 4px', 
              borderBottom: '1px solid #f1f5f9',
              cursor: 'pointer',
              borderRadius: '8px',
              background: showSalesDetail ? '#f8fafc' : 'transparent',
              transition: 'background 0.15s ease'
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span style={{ color: '#334155', fontWeight: '700' }}>1. Pendapatan / Omset</span>
              <span style={{ color: '#64748b', display: 'flex', alignItems: 'center' }}>
                {showSalesDetail ? <ChevronUp size={15} /> : <ChevronDown size={15} />}
              </span>
            </div>
            <strong style={{ color: '#0f172a' }}>{formatRupiah(lr.totalOmset)}</strong>
          </div>

          {/* Sub-item ringkas */}
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

          {/* ── Drilldown Rincian Toko (Accordion Content) ── */}
          {showSalesDetail && (
            <div style={{
              background: '#f8fafc',
              borderRadius: '12px',
              padding: '10px',
              margin: '2px 0 6px 0',
              border: '1px solid #e2e8f0',
              display: 'flex',
              flexDirection: 'column',
              gap: '8px'
            }}>
              <div style={{ fontSize: '11px', fontWeight: '700', color: '#1e293b', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <Store size={13} className="text-primary" /> Rincian Penjualan per Toko Marketplace:
              </div>
              {storeBreakdown.length > 0 ? (
                storeBreakdown.map((st, idx) => (
                  <div key={idx} style={{
                    background: '#ffffff',
                    borderRadius: '8px',
                    padding: '8px 10px',
                    border: '1px solid #e2e8f0',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '4px'
                  }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <span style={{ fontWeight: '700', color: '#0f172a', fontSize: '11.5px' }}>
                        {st.storeName}
                        <span style={{ fontSize: '9.5px', marginLeft: '6px', background: '#f1f5f9', color: '#475569', padding: '1px 5px', borderRadius: '4px' }}>
                          {st.channelName}
                        </span>
                      </span>
                      <strong style={{ color: '#2563eb', fontSize: '12px' }}>{formatRupiah(st.net)}</strong>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', fontSize: '10px', color: '#64748b', paddingTop: '2px' }}>
                      <div>Pesanan: <strong style={{ color: '#334155' }}>{st.ordersCount}</strong></div>
                      <div>Omset: <strong style={{ color: '#334155' }}>{formatRupiah(st.omset)}</strong></div>
                      <div>Admin: <strong style={{ color: '#dc2626' }}>-{formatRupiah(st.fee)}</strong></div>
                    </div>
                  </div>
                ))
              ) : (
                <div style={{ fontSize: '11px', color: '#94a3b8', fontStyle: 'italic' }}>
                  Tidak ada data rincian toko.
                </div>
              )}
            </div>
          )}

          {/* ── 2. HPP Modal Pokok Produk (Accordion Trigger) ── */}
          <div 
            onClick={() => setShowHppDetail(!showHppDetail)}
            style={{ 
              display: 'flex', 
              justifyContent: 'space-between', 
              alignItems: 'center',
              padding: '6px 4px', 
              borderBottom: '1px solid #f1f5f9',
              paddingTop: '6px',
              cursor: 'pointer',
              borderRadius: '8px',
              background: showHppDetail ? '#fef2f2' : 'transparent',
              transition: 'background 0.15s ease'
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span style={{ color: '#334155', fontWeight: '700' }}>2. HPP Modal Pokok Produk</span>
              <span style={{ color: '#64748b', display: 'flex', alignItems: 'center' }}>
                {showHppDetail ? <ChevronUp size={15} /> : <ChevronDown size={15} />}
              </span>
            </div>
            <strong style={{ color: '#4f46e5' }}>- {formatRupiah(lr.totalHpp)}</strong>
          </div>

          {/* ── Drilldown HPP Modal (Accordion Content) ── */}
          {showHppDetail && (
            <div style={{
              background: '#fef2f2',
              borderRadius: '12px',
              padding: '10px',
              margin: '2px 0 6px 0',
              border: '1px solid #fecaca',
              display: 'flex',
              flexDirection: 'column',
              gap: '8px'
            }}>
              <div style={{ fontSize: '11px', fontWeight: '700', color: '#991b1b', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <Receipt size={13} className="text-danger" /> Beban HPP per Toko & Margin:
              </div>
              {storeBreakdown.map((st, idx) => {
                const storeMargin = st.net - st.hpp;
                return (
                  <div key={idx} style={{
                    background: '#ffffff',
                    borderRadius: '8px',
                    padding: '8px 10px',
                    border: '1px solid #fee2e2',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '2px'
                  }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <span style={{ fontWeight: '600', color: '#0f172a', fontSize: '11px' }}>
                        {st.storeName}
                      </span>
                      <strong style={{ color: '#b91c1c', fontSize: '11.5px' }}>- {formatRupiah(st.hpp)}</strong>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10px', color: '#64748b' }}>
                      <span>Dana Cair: {formatRupiah(st.net)}</span>
                      <span>Margin: <strong style={{ color: storeMargin >= 0 ? '#059669' : '#dc2626' }}>{formatRupiah(storeMargin)}</strong></span>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* ── 3. Potongan Platform Fee ── */}
          <div style={{ display: 'flex', justifyContent: 'space-between', padding: '6px 4px', borderBottom: '1px solid #f1f5f9' }}>
            <span style={{ color: '#334155', fontWeight: '600' }}>3. Potongan Marketplace</span>
            <strong style={{ color: '#d97706' }}>- {formatRupiah(lr.marketplaceFee)}</strong>
          </div>

          {/* ── Subtotal Laba Kotor ── */}
          <div style={{ 
            display: 'flex', 
            justifyContent: 'space-between', 
            padding: '8px 10px', 
            background: '#f0f9ff', 
            borderRadius: '10px',
            border: '1px solid #bae6fd',
            margin: '2px 0'
          }}>
            <strong style={{ color: '#0369a1' }}>= Laba Kotor (Gross Profit)</strong>
            <strong style={{ color: '#0284c7' }}>{formatRupiah(lr.labaKotor)} ({lr.grossMarginPct}%)</strong>
          </div>

          {/* ── 4. Beban Operasional Header ── */}
          <div style={{ display: 'flex', justifyContent: 'space-between', padding: '6px 4px', borderBottom: '1px solid #f1f5f9', paddingTop: '6px' }}>
            <span style={{ color: '#334155', fontWeight: '700' }}>4. Beban Operasional / Pengeluaran</span>
            <strong style={{ color: '#ef4444' }}>- {formatRupiah(lr.totalOperasional)}</strong>
          </div>

          {/* ── Kategori Pengeluaran (Tiap Kategori Bisa Di-Tap untuk Muncul Transaksinya) ── */}
          {lr.expenseCategories && lr.expenseCategories.length > 0 ? (
            lr.expenseCategories.map((exp, idx) => {
              const isExpanded = expandedExpenseCat === exp.name;
              const hasItems = exp.items && exp.items.length > 0;

              return (
                <div key={idx} style={{ display: 'flex', flexDirection: 'column' }}>
                  
                  {/* Category Header Row (Tap to expand) */}
                  <div 
                    onClick={() => {
                      if (hasItems) {
                        setExpandedExpenseCat(isExpanded ? null : exp.name);
                      }
                    }}
                    style={{ 
                      padding: '6px 8px 6px 12px', 
                      display: 'flex', 
                      justifyContent: 'space-between', 
                      alignItems: 'center',
                      fontSize: '11.5px', 
                      color: '#334155',
                      borderRadius: '8px',
                      cursor: hasItems ? 'pointer' : 'default',
                      background: isExpanded ? '#f8fafc' : 'transparent',
                      border: isExpanded ? '1px solid #e2e8f0' : 'none',
                      transition: 'all 0.15s ease'
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <span style={{ fontWeight: isExpanded ? '700' : '500' }}>• {exp.name}</span>
                      {hasItems && (
                        <span style={{ color: '#64748b', display: 'flex', alignItems: 'center' }}>
                          {isExpanded ? <ChevronUp size={13} /> : <ChevronDown size={13} />}
                        </span>
                      )}
                    </div>
                    <strong style={{ color: '#ef4444' }}>{formatRupiah(exp.amount)}</strong>
                  </div>

                  {/* Category Items Drilldown (Accordion Content) */}
                  {isExpanded && hasItems && (
                    <div style={{
                      background: '#f8fafc',
                      borderRadius: '8px',
                      padding: '8px',
                      margin: '2px 0 6px 12px',
                      border: '1px solid #e2e8f0',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '6px'
                    }}>
                      <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', paddingBottom: '2px' }}>
                        Daftar Catatan Transaksi {exp.name}:
                      </div>
                      {exp.items.map((it, itIdx) => (
                        <div key={itIdx} style={{
                          background: '#ffffff',
                          borderRadius: '6px',
                          padding: '6px 8px',
                          border: '1px solid #e2e8f0',
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center'
                        }}>
                          <div style={{ display: 'flex', flexDirection: 'column', gap: '1px' }}>
                            <span style={{ fontWeight: '600', color: '#0f172a', fontSize: '11px' }}>
                              {it.title}
                            </span>
                            <div style={{ display: 'flex', gap: '6px', alignItems: 'center', fontSize: '9.5px', color: '#64748b' }}>
                              <span>{it.date}</span>
                              {it.paymentSource && (
                                <span style={{
                                  background: it.paymentSource === 'kas_besar' ? '#dbeafe' : '#fef3c7',
                                  color: it.paymentSource === 'kas_besar' ? '#1e40af' : '#92400e',
                                  padding: '1px 4px',
                                  borderRadius: '3px',
                                  fontWeight: '600'
                                }}>
                                  {it.paymentSource.replace('_', ' ')}
                                </span>
                              )}
                              {it.notes && <span style={{ fontStyle: 'italic' }}>({it.notes})</span>}
                            </div>
                          </div>
                          <strong style={{ color: '#b91c1c', fontSize: '11px', whiteSpace: 'nowrap' }}>
                            {formatRupiah(it.amount)}
                          </strong>
                        </div>
                      ))}
                    </div>
                  )}

                </div>
              );
            })
          ) : (
            <div style={{ paddingLeft: '12px', fontSize: '11px', color: '#94a3b8' }}>
              • Belum ada catatan biaya operasional di periode ini.
            </div>
          )}

          {/* ── 5. Laba Bersih Final ── */}
          <div style={{ 
            display: 'flex', 
            justifyContent: 'space-between', 
            padding: '10px 12px', 
            background: isNetProfitPositive ? '#ecfdf5' : '#fef2f2', 
            borderRadius: '12px',
            border: `1px solid ${isNetProfitPositive ? '#a7f3d0' : '#fecaca'}`,
            marginTop: '6px'
          }}>
            <strong style={{ color: '#0f172a' }}>= Laba Bersih (Net Profit)</strong>
            <strong style={{ color: isNetProfitPositive ? '#059669' : '#dc2626', fontSize: '14px' }}>
              {formatRupiah(lr.labaBersih)}
            </strong>
          </div>

        </div>
      </div>

    </div>
  );
}
