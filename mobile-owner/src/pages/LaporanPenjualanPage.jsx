import React, { useState, useEffect } from 'react';
import { 
  FileText, 
  DollarSign, 
  TrendingUp, 
  Store, 
  Calendar, 
  RotateCcw, 
  Receipt, 
  Layers, 
  RefreshCw, 
  Filter,
  Download,
  Printer,
  ChevronRight,
  Package,
  CalendarCheck,
  Building2,
  Clock
} from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../utils/formatters';
import { fetchLaporanPenjualan } from '../api/metricsApi';
import OrderCard from '../components/orders/OrderCard';

export default function LaporanPenjualanPage({ onSelectOrder }) {
  // Main Navigation Tab: 'dilepas' (Laporan Penjualan Dilepas) vs 'semua' (Laporan Rekap Penjualan)
  const [reportTab, setReportTab] = useState('dilepas');

  // Filter Form State
  const [reportFormat, setReportFormat] = useState('per_produk');
  const [selectedStoreId, setSelectedStoreId] = useState('');
  const [poStatus, setPoStatus] = useState('');

  // Default dates: awal bulan s/d hari ini
  const todayStr = () => {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  };
  const startOfMonthStr = () => {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-01`;
  };

  const [dateFrom, setDateFrom] = useState(startOfMonthStr());
  const [dateTo, setDateTo] = useState(todayStr());

  // Report Data
  const [summary, setSummary] = useState({
    omset: 0,
    refund: 0,
    totalFee: 0,
    netReleased: 0,
    hpp: 0,
    margin: 0,
    marginPct: 0,
    totalOrders: 0,
    totalQty: 0
  });
  const [byStore, setByStore] = useState([]);
  const [byChannel, setByChannel] = useState([]);
  const [byDate, setByDate] = useState([]);
  const [byProduct, setByProduct] = useState([]);
  const [transactions, setTransactions] = useState([]);
  const [storeOptions, setStoreOptions] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  const loadReport = async () => {
    setIsLoading(true);
    try {
      const res = await fetchLaporanPenjualan({ 
        dateFrom, 
        dateTo, 
        storeId: selectedStoreId 
      });
      if (res && res.success) {
        setSummary(res.summary || {});
        setByStore(res.byStore || []);
        setByChannel(res.byChannel || []);
        setByDate(res.byDate || []);
        setByProduct(res.byProduct || []);
        setTransactions(res.transactions || []);
        if (res.filters?.stores) {
          setStoreOptions(res.filters.stores);
        }
      }
    } catch (err) {
      console.error('Failed to load sales report:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadReport();
  }, [dateFrom, dateTo, selectedStoreId, reportTab]);

  // Quick preset handler
  const handlePresetDate = (type) => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const today = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

    if (type === 'today') {
      setDateFrom(today);
      setDateTo(today);
    } else if (type === '7d') {
      const past = new Date();
      past.setDate(past.getDate() - 7);
      setDateFrom(`${past.getFullYear()}-${pad(past.getMonth() + 1)}-${pad(past.getDate())}`);
      setDateTo(today);
    } else if (type === 'month') {
      setDateFrom(`${now.getFullYear()}-${pad(now.getMonth() + 1)}-01`);
      setDateTo(today);
    }
  };

  // Export CSV Client-Side
  const handleExportCsv = () => {
    let csvContent = 'data:text/csv;charset=utf-8,';
    if (reportFormat === 'per_produk') {
      csvContent += 'SKU,Nama Produk,Qty Terjual,Omset Kotor,HPP,Margin Laba\n';
      byProduct.forEach(p => {
        csvContent += `"${p.sku}","${p.name.replace(/"/g, '""')}",${p.quantity},${p.omset},${p.hpp},${p.margin}\n`;
      });
    } else {
      csvContent += 'Invoice,Toko,Channel,Tanggal,Pembeli,Omset,Fee MP,Refund,Dilepas Bersih,HPP,Margin,Status\n';
      transactions.forEach(t => {
        csvContent += `"${t.invoiceNumber}","${t.storeName}","${t.channelName}","${t.orderDate}","${t.buyerName}",${t.omset},${t.marketplaceFee},${t.refund},${t.releasedValue},${t.hppModal},${t.margin},"${t.status}"\n`;
      });
    }
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Laporan_Penjualan_${reportTab}_${dateFrom}_sd_${dateTo}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const isDilepas = reportTab === 'dilepas';
  const themeColor = isDilepas ? '#16a34a' : '#2563eb';
  const themeBgLight = isDilepas ? '#f0fdf4' : '#eff6ff';
  const themeBorder = isDilepas ? '#dcfce7' : '#dbeafe';

  return (
    <div className="mobile-body-content" style={{ paddingBottom: '90px' }}>
      {/* ── 1. DUA NAV TABS PERSIS ERP ── */}
      <div style={{
        display: 'flex',
        borderBottom: '2px solid #e2e8f0',
        marginBottom: '14px',
        gap: '4px'
      }}>
        <button
          onClick={() => {
            setReportTab('dilepas');
            setReportFormat('per_produk');
          }}
          style={{
            flex: 1,
            padding: '10px 8px',
            border: 'none',
            background: 'transparent',
            borderBottom: isDilepas ? '3px solid #16a34a' : '3px solid transparent',
            color: isDilepas ? '#16a34a' : '#64748b',
            fontWeight: '700',
            fontSize: '11.5px',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '6px',
            marginBottom: '-2px',
            transition: 'all 0.15s ease'
          }}
        >
          <DollarSign size={15} />
          <span>Laporan Penjualan Dilepas (Dana Cair)</span>
        </button>

        <button
          onClick={() => {
            setReportTab('semua');
            setReportFormat('per_produk');
          }}
          style={{
            flex: 1,
            padding: '10px 8px',
            border: 'none',
            background: 'transparent',
            borderBottom: !isDilepas ? '3px solid #2563eb' : '3px solid transparent',
            color: !isDilepas ? '#2563eb' : '#64748b',
            fontWeight: '700',
            fontSize: '11.5px',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '6px',
            marginBottom: '-2px',
            transition: 'all 0.15s ease'
          }}
        >
          <Receipt size={15} />
          <span>Laporan Rekap Penjualan (Semua)</span>
        </button>
      </div>

      {/* ── 2. FILTER BOX CARD IDENTIK SCREENSHOT ERP ── */}
      <div style={{
        background: '#ffffff',
        border: '1px solid #e2e8f0',
        borderRadius: '12px',
        overflow: 'hidden',
        boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
        marginBottom: '16px'
      }}>
        {/* Header Card */}
        <div style={{
          background: themeBgLight,
          borderBottom: `1px solid ${themeBorder}`,
          padding: '10px 14px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            <Filter size={15} color={themeColor} />
            <span style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
              {isDilepas ? 'Filter Penjualan Dilepas (Escrow Released)' : 'Filter Rekap Penjualan (Semua)'}
            </span>
          </div>

          <div style={{ display: 'flex', gap: '4px' }}>
            {['today', '7d', 'month'].map((p) => (
              <button
                key={p}
                onClick={() => handlePresetDate(p)}
                style={{
                  padding: '3px 7px',
                  borderRadius: '6px',
                  border: '1px solid #cbd5e1',
                  background: '#ffffff',
                  fontSize: '9.5px',
                  fontWeight: '700',
                  color: '#475569',
                  cursor: 'pointer'
                }}
              >
                {p === 'today' ? 'Hari Ini' : p === '7d' ? '7 Hari' : 'Bulan Ini'}
              </button>
            ))}
          </div>
        </div>

        {/* Body Form */}
        <div style={{ padding: '14px 16px' }}>
          {/* Format Laporan */}
          <div style={{ marginBottom: '12px' }}>
            <label style={{ 
              display: 'block', 
              fontSize: '11px', 
              fontWeight: '700', 
              color: themeColor, 
              marginBottom: '4px' 
            }}>
              {isDilepas ? 'Format Laporan Penjualan Dilepas' : 'Format Laporan Penjualan'}
            </label>
            <select
              value={reportFormat}
              onChange={(e) => setReportFormat(e.target.value)}
              style={{
                width: '100%',
                padding: '8px 10px',
                fontSize: '12px',
                fontWeight: '700',
                color: themeColor,
                background: themeBgLight,
                border: `1.5px solid ${themeColor}`,
                borderRadius: '8px',
                outline: 'none',
                cursor: 'pointer'
              }}
            >
              {isDilepas ? (
                <>
                  <option value="per_produk">📦 Laporan Per Produk (Dilepas)</option>
                  <option value="ringkasan_penghasilan">📄 Laporan Ringkasan Penghasilan & Biaya Escrow (Shopee/TikTok)</option>
                  <option value="per_channel">🏪 Laporan Per Channel Marketplace (Dilepas)</option>
                  <option value="detail">📑 Laporan Detail Transaksi (Dilepas)</option>
                  <option value="per_tanggal">📅 Laporan Per Tanggal (Dilepas)</option>
                </>
              ) : (
                <>
                  <option value="per_produk">📦 Laporan Per Produk</option>
                  <option value="per_channel">🏪 Laporan Per Channel / Saluran</option>
                  <option value="detail">📑 Laporan Detail Transaksi</option>
                  <option value="per_tanggal">📅 Laporan Per Tanggal</option>
                </>
              )}
            </select>
          </div>

          {/* If Tab Semua: Tipe PO */}
          {!isDilepas && (
            <div style={{ marginBottom: '12px' }}>
              <label style={{ display: 'block', fontSize: '11px', fontWeight: '600', color: '#64748b', marginBottom: '4px' }}>
                Tipe Pre-Order (PO)
              </label>
              <select
                value={poStatus}
                onChange={(e) => setPoStatus(e.target.value)}
                style={{
                  width: '100%',
                  padding: '7px 10px',
                  fontSize: '12px',
                  borderRadius: '8px',
                  border: '1px solid #cbd5e1',
                  background: '#ffffff',
                  outline: 'none',
                  color: '#334155'
                }}
              >
                <option value="">Semua Tipe (PO & Reguler)</option>
                <option value="1">⏳ Pre-Order (PO)</option>
                <option value="0">📦 Reguler (Bukan PO)</option>
              </select>
            </div>
          )}

          {/* Toko Marketplace */}
          <div style={{ marginBottom: '12px' }}>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: '600', color: '#64748b', marginBottom: '4px' }}>
              Toko Marketplace
            </label>
            <select
              value={selectedStoreId}
              onChange={(e) => setSelectedStoreId(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 10px',
                fontSize: '12px',
                borderRadius: '8px',
                border: '1px solid #cbd5e1',
                background: '#ffffff',
                outline: 'none',
                color: '#334155',
                cursor: 'pointer'
              }}
            >
              <option value="">🛒 Semua Toko Marketplace</option>
              {storeOptions.map(st => (
                <option key={st.id} value={st.id}>
                  {st.name} ({st.channel || 'Marketplace'})
                </option>
              ))}
            </select>
          </div>

          {/* Rentang Tanggal 2 Kolom Sesuai Screenshot */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '14px' }}>
            <div>
              <label style={{ 
                display: 'flex', 
                alignItems: 'center', 
                gap: '4px', 
                fontSize: '11px', 
                fontWeight: '700', 
                color: themeColor, 
                marginBottom: '4px' 
              }}>
                <CalendarCheck size={12} />
                <span>Dari Tanggal</span>
              </label>
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                style={{
                  width: '100%',
                  padding: '6px 8px',
                  fontSize: '11.5px',
                  borderRadius: '6px',
                  border: `1.5px solid ${themeColor}`,
                  outline: 'none',
                  color: '#0f172a'
                }}
              />
            </div>

            <div>
              <label style={{ 
                display: 'flex', 
                alignItems: 'center', 
                gap: '4px', 
                fontSize: '11px', 
                fontWeight: '700', 
                color: themeColor, 
                marginBottom: '4px' 
              }}>
                <CalendarCheck size={12} />
                <span>Sampai Tanggal</span>
              </label>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                style={{
                  width: '100%',
                  padding: '6px 8px',
                  fontSize: '11.5px',
                  borderRadius: '6px',
                  border: `1.5px solid ${themeColor}`,
                  outline: 'none',
                  color: '#0f172a'
                }}
              />
            </div>
          </div>

          {/* Tombol Action Sesuai Screenshot (Export CSV & Cetak Rekap) */}
          <div style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            paddingTop: '10px',
            borderTop: '1px solid #f1f5f9'
          }}>
            <button
              onClick={handleExportCsv}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                padding: '7px 14px',
                borderRadius: '8px',
                border: `1.5px solid ${themeColor}`,
                background: '#ffffff',
                color: themeColor,
                fontSize: '11.5px',
                fontWeight: '700',
                cursor: 'pointer'
              }}
            >
              <Download size={13} />
              <span>Export CSV</span>
            </button>

            <button
              onClick={loadReport}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                padding: '7px 16px',
                borderRadius: '8px',
                border: 'none',
                background: themeColor,
                color: '#ffffff',
                fontSize: '11.5px',
                fontWeight: '700',
                cursor: 'pointer',
                boxShadow: isDilepas ? '0 2px 6px rgba(22, 163, 74, 0.3)' : '0 2px 6px rgba(37, 99, 235, 0.3)'
              }}
            >
              <Printer size={13} />
              <span>{isLoading ? 'Memuat...' : 'Cetak / Tampilkan'}</span>
            </button>
          </div>
        </div>
      </div>

      {/* ── 3. RINGKASAN METRIK FINANSIAL REAL-TIME ── */}
      <div style={{
        background: '#ffffff',
        border: '1px solid #e2e8f0',
        borderRadius: '14px',
        padding: '14px 16px',
        marginBottom: '16px',
        boxShadow: '0 2px 6px rgba(0,0,0,0.03)'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
          <span style={{ fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.5px', color: '#64748b', fontWeight: '700' }}>
            Ringkasan Keuangan {isDilepas ? 'Dana Cair' : 'Penjualan'}
          </span>
          <span style={{
            fontSize: '11px',
            fontWeight: '800',
            background: '#ecfdf5',
            color: '#059669',
            padding: '2px 8px',
            borderRadius: '999px'
          }}>
            Margin {summary.marginPct || 0}%
          </span>
        </div>

        {/* 6 Sub-Metrik Grid */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: '10px'
        }}>
          <div style={{ background: '#f8fafc', padding: '10px 12px', borderRadius: '10px' }}>
            <span style={{ fontSize: '10.5px', color: '#64748b' }}>Omset Penjualan:</span>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
              {formatRupiah(summary.omset || 0)}
            </div>
            <span style={{ fontSize: '9.5px', color: '#94a3b8' }}>
              {summary.totalOrders || 0} order • {summary.totalQty || 0} pcs
            </span>
          </div>

          <div style={{ background: '#f0fdf4', padding: '10px 12px', borderRadius: '10px' }}>
            <span style={{ fontSize: '10.5px', color: '#166534' }}>Dilepas Bersih:</span>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#15803d', marginTop: '2px' }}>
              {formatRupiah(summary.netReleased || 0)}
            </div>
            <span style={{ fontSize: '9.5px', color: '#16a34a' }}>Dana diterima toko</span>
          </div>

          <div style={{ background: '#fef2f2', padding: '10px 12px', borderRadius: '10px' }}>
            <span style={{ fontSize: '10.5px', color: '#991b1b' }}>Biaya Marketplace:</span>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#dc2626', marginTop: '2px' }}>
              - {formatRupiah(summary.totalFee || 0)}
            </div>
            <span style={{ fontSize: '9.5px', color: '#ef4444' }}>Admin platform</span>
          </div>

          <div style={{ background: '#fff7ed', padding: '10px 12px', borderRadius: '10px' }}>
            <span style={{ fontSize: '10.5px', color: '#9a3412' }}>Modal HPP Barang:</span>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#ea580c', marginTop: '2px' }}>
              - {formatRupiah(summary.hpp || 0)}
            </div>
            <span style={{ fontSize: '9.5px', color: '#f97316' }}>HPP bersih (dipotong refund)</span>
          </div>
        </div>

        {/* Highlight Margin Bersih */}
        <div style={{
          marginTop: '10px',
          padding: '10px 12px',
          background: isDilepas ? 'linear-gradient(135deg, #15803d, #166534)' : 'linear-gradient(135deg, #2563eb, #1d4ed8)',
          borderRadius: '10px',
          color: '#ffffff',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center'
        }}>
          <div>
            <div style={{ fontSize: '10.5px', opacity: 0.85 }}>Margin Bersih (Laba Penjualan)</div>
            <div style={{ fontSize: '17px', fontWeight: '800', marginTop: '1px' }}>
              {formatRupiah(summary.margin || 0)}
            </div>
          </div>
          <span style={{
            fontSize: '12px',
            fontWeight: '800',
            background: 'rgba(255,255,255,0.2)',
            padding: '4px 10px',
            borderRadius: '999px'
          }}>
            {summary.marginPct || 0}%
          </span>
        </div>
      </div>

      {/* ── 4. HASIL SESUAI FORMAT LAPORAN YANG DIPILIH ── */}
      <div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
          <h4 style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
            {reportFormat === 'per_produk' && `📦 Hasil: Laporan Per Produk (${byProduct.length})`}
            {reportFormat === 'per_channel' && `🏪 Hasil: Laporan Per Channel (${byChannel.length})`}
            {reportFormat === 'per_tanggal' && `📅 Hasil: Laporan Per Tanggal (${byDate.length})`}
            {(reportFormat === 'detail' || reportFormat === 'ringkasan_penghasilan') && `📑 Hasil: Rincian Transaksi (${transactions.length})`}
          </h4>
        </div>

        {isLoading ? (
          <div style={{ textAlign: 'center', padding: '40px', background: '#fff', borderRadius: '12px', color: '#94a3b8' }}>
            <RefreshCw size={24} className="animate-spin" style={{ margin: '0 auto 8px' }} />
            <p style={{ fontSize: '12px' }}>Memuat data laporan...</p>
          </div>
        ) : (
          <>
            {/* View A: Per Produk */}
            {reportFormat === 'per_produk' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {byProduct.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '12px', color: '#94a3b8', fontSize: '12px' }}>
                    Tidak ada produk terjual pada periode ini.
                  </div>
                ) : (
                  byProduct.map((p, idx) => (
                    <div 
                      key={idx}
                      style={{
                        background: '#ffffff',
                        border: '1px solid #e2e8f0',
                        borderRadius: '12px',
                        padding: '12px 14px',
                        boxShadow: '0 1px 3px rgba(0,0,0,0.02)'
                      }}
                    >
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                        <div>
                          <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
                            {p.name}
                          </div>
                          <span style={{ fontSize: '10px', color: '#64748b', background: '#f1f5f9', padding: '2px 6px', borderRadius: '4px' }}>
                            SKU: {p.sku}
                          </span>
                        </div>
                        <span style={{ fontSize: '11.5px', fontWeight: '800', color: themeColor, background: themeBgLight, padding: '3px 8px', borderRadius: '6px' }}>
                          {p.quantity} pcs
                        </span>
                      </div>

                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(3, 1fr)', 
                        gap: '6px', 
                        fontSize: '11px', 
                        marginTop: '10px',
                        paddingTop: '8px',
                        borderTop: '1px solid #f1f5f9'
                      }}>
                        <div>
                          <span style={{ color: '#64748b' }}>Omset:</span>
                          <div style={{ fontWeight: '700', color: '#0f172a' }}>{formatRupiah(p.omset)}</div>
                        </div>
                        <div>
                          <span style={{ color: '#64748b' }}>HPP:</span>
                          <div style={{ fontWeight: '700', color: '#dc2626' }}>{formatRupiah(p.hpp)}</div>
                        </div>
                        <div style={{ textAlign: 'right' }}>
                          <span style={{ color: '#64748b' }}>Margin:</span>
                          <div style={{ fontWeight: '800', color: '#059669' }}>{formatRupiah(p.margin)}</div>
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            )}

            {/* View B: Per Channel */}
            {reportFormat === 'per_channel' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {byChannel.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '12px', color: '#94a3b8', fontSize: '12px' }}>
                    Belum ada data channel pada rentang tanggal ini.
                  </div>
                ) : (
                  byChannel.map((ch, idx) => {
                    const badge = getChannelBadge(ch.channelName);
                    const pct = summary.omset > 0 ? Math.round((ch.omset / summary.omset) * 100) : 0;
                    return (
                      <div key={idx} style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '12px 14px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                          <span className={`store-pill ${badge.class}`}>{ch.channelName}</span>
                          <strong style={{ fontSize: '13px', color: '#0f172a' }}>{formatRupiah(ch.omset)}</strong>
                        </div>
                        <div style={{ width: '100%', height: '5px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden', marginBottom: '8px' }}>
                          <div style={{ width: `${pct}%`, height: '100%', background: themeColor, borderRadius: '999px' }} />
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b' }}>
                          <span>{ch.count} order • {ch.quantity} pcs</span>
                          <span>Margin: <strong style={{ color: '#059669' }}>{formatRupiah(ch.margin)}</strong></span>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            )}

            {/* View C: Per Tanggal */}
            {reportFormat === 'per_tanggal' && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {byDate.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '12px', color: '#94a3b8', fontSize: '12px' }}>
                    Belum ada data harian pada rentang tanggal ini.
                  </div>
                ) : (
                  byDate.map((d, idx) => (
                    <div key={idx} style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '10px 14px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <div>
                        <div style={{ fontSize: '12px', fontWeight: '700', color: '#0f172a' }}>{d.date}</div>
                        <div style={{ fontSize: '10px', color: '#64748b' }}>{d.count} pesanan</div>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>{formatRupiah(d.omset)}</div>
                        <div style={{ fontSize: '10.5px', color: '#059669', fontWeight: '700' }}>Margin: {formatRupiah(d.margin)}</div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            )}

            {/* View D: Detail Transaksi / Ringkasan Penghasilan */}
            {(reportFormat === 'detail' || reportFormat === 'ringkasan_penghasilan') && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {transactions.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '12px', color: '#94a3b8', fontSize: '12px' }}>
                    Tidak ada transaksi ditemukan.
                  </div>
                ) : (
                  transactions.map((tx) => (
                    <OrderCard 
                      key={tx.id} 
                      order={tx} 
                      onClick={(ord) => onSelectOrder && onSelectOrder(ord)} 
                    />
                  ))
                )}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
