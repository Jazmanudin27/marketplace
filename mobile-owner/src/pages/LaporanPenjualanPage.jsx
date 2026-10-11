import React, { useState, useEffect } from 'react';
import { 
  DollarSign, 
  Receipt, 
  Filter,
  Download,
  Printer,
  CalendarCheck,
  X,
  FileSpreadsheet,
  CheckCircle2
} from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../utils/formatters';
import { fetchLaporanPenjualan } from '../api/metricsApi';

export default function LaporanPenjualanPage({ onSelectOrder }) {
  // Navigation Tabs: 'dilepas' vs 'semua'
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

  // Modal Cetak Rekap
  const [isPrintModalOpen, setIsPrintModalOpen] = useState(false);

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

  const handleCetakClick = () => {
    loadReport().then(() => {
      setIsPrintModalOpen(true);
    });
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

  const formatNameLabel = {
    per_produk: 'Laporan Per Produk',
    ringkasan_penghasilan: 'Laporan Ringkasan Penghasilan & Biaya Escrow',
    per_channel: 'Laporan Per Channel Marketplace',
    detail: 'Laporan Detail Transaksi',
    per_tanggal: 'Laporan Per Tanggal'
  }[reportFormat] || 'Laporan Penjualan';

  return (
    <div className="mobile-body-content" style={{ paddingBottom: '90px' }}>
      {/* ── 1. DUA NAV TABS PERSIS ERP ── */}
      <div style={{
        display: 'flex',
        borderBottom: '2px solid #e2e8f0',
        marginBottom: '16px',
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
        {/* Header Card (Tanpa tombol preset hari ini/7 hari/bulan ini) */}
        <div style={{
          background: themeBgLight,
          borderBottom: `1px solid ${themeBorder}`,
          padding: '12px 16px',
          display: 'flex',
          alignItems: 'center',
          gap: '8px'
        }}>
          <Filter size={15} color={themeColor} />
          <span style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
            {isDilepas ? 'Filter Penjualan Dilepas (Escrow Released)' : 'Filter Rekap Penjualan (Semua)'}
          </span>
        </div>

        {/* Body Form */}
        <div style={{ padding: '16px 18px' }}>
          {/* Format Laporan */}
          <div style={{ marginBottom: '14px' }}>
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
            <div style={{ marginBottom: '14px' }}>
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
          <div style={{ marginBottom: '14px' }}>
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
              <option value="">- Pilih -</option>
              {storeOptions.map(st => (
                <option key={st.id} value={st.id}>
                  {st.name} ({st.channel || 'Marketplace'})
                </option>
              ))}
            </select>
          </div>

          {/* Rentang Tanggal 2 Kolom Sesuai Screenshot */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '16px' }}>
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
                <span>Dari Tanggal (Dilepas / Cair)</span>
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
                <span>Sampai Tanggal (Dilepas / Cair)</span>
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

          {/* Tombol Action (Cetak Rekap) */}
          <div style={{
            display: 'flex',
            alignItems: 'center',
            paddingTop: '12px',
            borderTop: '1px solid #f1f5f9'
          }}>
            <button
              onClick={handleCetakClick}
              style={{
                width: '100%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                padding: '10px 18px',
                borderRadius: '10px',
                border: 'none',
                background: themeColor,
                color: '#ffffff',
                fontSize: '13px',
                fontWeight: '800',
                cursor: 'pointer',
                boxShadow: isDilepas ? '0 3px 8px rgba(22, 163, 74, 0.3)' : '0 3px 8px rgba(37, 99, 235, 0.3)'
              }}
            >
              <Printer size={16} />
              <span>{isLoading ? 'Memuat...' : 'Cetak Rekap'}</span>
            </button>
          </div>
        </div>
      </div>

      {/* ── 3. MODAL CETAK REKAP LAPORAN (VIEW PRINT RESMI) ── */}
      {isPrintModalOpen && (
        <div className="modal-overlay" onClick={() => setIsPrintModalOpen(false)}>
          <div 
            className="bottom-sheet-card" 
            onClick={(e) => e.stopPropagation()}
            style={{ maxHeight: '92vh', padding: '16px 18px 30px' }}
          >
            <div className="sheet-handle"></div>

            {/* Modal Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
              <div>
                <h3 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a' }}>
                  {formatNameLabel}
                </h3>
                <p style={{ fontSize: '11px', color: '#64748b' }}>
                  Periode: {dateFrom} s/d {dateTo}
                </p>
              </div>
              <div style={{ display: 'flex', gap: '8px' }}>
                <button
                  onClick={() => window.print()}
                  style={{
                    background: themeColor,
                    border: 'none',
                    borderRadius: '8px',
                    padding: '6px 12px',
                    color: '#fff',
                    fontSize: '11px',
                    fontWeight: '700',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                    cursor: 'pointer'
                  }}
                >
                  <Printer size={12} />
                  <span>Print</span>
                </button>
                <button 
                  onClick={() => setIsPrintModalOpen(false)}
                  style={{ background: '#f1f5f9', border: 'none', borderRadius: '50%', width: '30px', height: '30px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                  <X size={15} color="#64748b" />
                </button>
              </div>
            </div>

            {/* Ringkasan Header Cetak */}
            <div style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '10px',
              padding: '10px 12px',
              display: 'grid',
              gridTemplateColumns: 'repeat(3, 1fr)',
              gap: '6px',
              fontSize: '11px',
              marginBottom: '12px'
            }}>
              <div>
                <span style={{ color: '#64748b' }}>Omset:</span>
                <div style={{ fontWeight: '800', color: '#0f172a' }}>{formatRupiah(summary.omset)}</div>
              </div>
              <div>
                <span style={{ color: '#64748b' }}>HPP:</span>
                <div style={{ fontWeight: '800', color: '#dc2626' }}>{formatRupiah(summary.hpp)}</div>
              </div>
              <div style={{ textAlign: 'right' }}>
                <span style={{ color: '#64748b' }}>Margin:</span>
                <div style={{ fontWeight: '800', color: '#059669' }}>{formatRupiah(summary.margin)} ({summary.marginPct}%)</div>
              </div>
            </div>

            {/* Konten Rekap Berdasarkan Format */}
            <div style={{ overflowY: 'auto', maxHeight: '60vh', display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {reportFormat === 'per_produk' && (
                byProduct.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
                    Tidak ada data produk pada periode ini.
                  </div>
                ) : (
                  byProduct.map((p, idx) => (
                    <div key={idx} style={{ background: '#fff', border: '1px solid #f1f5f9', borderRadius: '10px', padding: '10px 12px' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                        <div>
                          <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>{p.name}</div>
                          <span style={{ fontSize: '10px', color: '#64748b' }}>SKU: {p.sku}</span>
                        </div>
                        <span style={{ fontSize: '11px', fontWeight: '800', color: themeColor }}>{p.quantity} pcs</span>
                      </div>
                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10.5px', marginTop: '6px', paddingTop: '6px', borderTop: '1px dashed #f1f5f9' }}>
                        <span>Omset: {formatRupiah(p.omset)}</span>
                        <span>HPP: {formatRupiah(p.hpp)}</span>
                        <strong style={{ color: '#059669' }}>Margin: {formatRupiah(p.margin)}</strong>
                      </div>
                    </div>
                  ))
                )
              )}

              {reportFormat === 'per_channel' && (
                byChannel.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
                    Tidak ada data channel pada periode ini.
                  </div>
                ) : (
                  byChannel.map((ch, idx) => (
                    <div key={idx} style={{ background: '#fff', border: '1px solid #f1f5f9', borderRadius: '10px', padding: '10px 12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <div>
                        <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>{ch.channelName}</div>
                        <span style={{ fontSize: '10px', color: '#64748b' }}>{ch.count} order • {ch.quantity} pcs</span>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>{formatRupiah(ch.omset)}</div>
                        <span style={{ fontSize: '10.5px', color: '#059669', fontWeight: '700' }}>Margin: {formatRupiah(ch.margin)}</span>
                      </div>
                    </div>
                  ))
                )
              )}

              {reportFormat === 'per_tanggal' && (
                byDate.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
                    Tidak ada data tanggal pada periode ini.
                  </div>
                ) : (
                  byDate.map((d, idx) => (
                    <div key={idx} style={{ background: '#fff', border: '1px solid #f1f5f9', borderRadius: '10px', padding: '10px 12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <div>
                        <div style={{ fontSize: '12px', fontWeight: '700', color: '#0f172a' }}>{d.date}</div>
                        <span style={{ fontSize: '10px', color: '#64748b' }}>{d.count} pesanan</span>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>{formatRupiah(d.omset)}</div>
                        <span style={{ fontSize: '10.5px', color: '#059669', fontWeight: '700' }}>Margin: {formatRupiah(d.margin)}</span>
                      </div>
                    </div>
                  ))
                )
              )}

              {(reportFormat === 'detail' || reportFormat === 'ringkasan_penghasilan') && (
                transactions.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
                    Tidak ada transaksi pada periode ini.
                  </div>
                ) : (
                  transactions.map((tx) => (
                    <div 
                      key={tx.id} 
                      onClick={() => onSelectOrder && onSelectOrder(tx)}
                      style={{ background: '#fff', border: '1px solid #f1f5f9', borderRadius: '10px', padding: '10px 12px', cursor: 'pointer' }}
                    >
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontSize: '11px', fontWeight: '700', color: '#0f172a' }}>{tx.invoiceNumber}</span>
                        <span style={{ fontSize: '10px', background: '#ecfdf5', color: '#059669', padding: '2px 6px', borderRadius: '4px', fontWeight: '700' }}>
                          {tx.storeName}
                        </span>
                      </div>
                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10.5px', marginTop: '6px', color: '#64748b' }}>
                        <span>Omset: {formatRupiah(tx.omset)}</span>
                        <span>Dilepas: {formatRupiah(tx.releasedValue)}</span>
                        <strong style={{ color: '#059669' }}>Laba: {formatRupiah(tx.margin)}</strong>
                      </div>
                    </div>
                  ))
                )
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
