import React, { useState, useEffect, useMemo } from 'react';
import { 
  Store, 
  Search, 
  X, 
  Filter, 
  CheckCircle2, 
  Clock, 
  AlertCircle, 
  Package, 
  Receipt, 
  ChevronRight, 
  QrCode, 
  CreditCard, 
  Calendar, 
  User, 
  Building2, 
  Phone, 
  RefreshCw,
  ArrowRight,
  Boxes,
  Layers,
  Banknote
} from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { fetchOfflineSales, fetchOfflineSaleDetail } from '../api/metricsApi';

export default function PenjualanOfflinePage({ onOpenScanKemasan, onOpenScanSpk }) {
  const [sales, setSales] = useState([]);
  const [summary, setSummary] = useState({
    totalOmset: 0,
    totalTerbayar: 0,
    totalPiutang: 0,
    totalCount: 0,
    unpackedCount: 0,
    poSpkCount: 0,
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [activeTab, setActiveTab] = useState('ALL'); // 'ALL', 'UNPACKED', 'PIUTANG', 'PO_SPK', 'COMPLETED'
  const [selectedSaleId, setSelectedSaleId] = useState(null);
  const [saleDetail, setSaleDetail] = useState(null);
  const [isLoadingDetail, setIsLoadingDetail] = useState(false);

  // Load Sales Data
  const loadData = async (showRefresh = false) => {
    if (showRefresh) setIsRefreshing(true);
    else setIsLoading(true);

    try {
      const res = await fetchOfflineSales({
        search: searchTerm,
        filterType: activeTab,
        limit: 50
      });

      if (res && res.success) {
        setSales(res.data || []);
        if (res.summary) setSummary(res.summary);
      }
    } catch (err) {
      console.warn('Failed to load offline sales:', err);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [activeTab]);

  // Handle Search submit / enter
  const handleSearchSubmit = (e) => {
    e.preventDefault();
    loadData();
  };

  // Open Detail
  const handleOpenDetail = async (id) => {
    setSelectedSaleId(id);
    setIsLoadingDetail(true);
    try {
      const res = await fetchOfflineSaleDetail(id);
      if (res && res.success) {
        setSaleDetail(res.data);
      }
    } catch (err) {
      console.warn('Failed to load sale detail:', err);
    } finally {
      setIsLoadingDetail(false);
    }
  };

  const handleCloseDetail = () => {
    setSelectedSaleId(null);
    setSaleDetail(null);
  };

  // Status Helpers
  const getStatusBadge = (status) => {
    switch (status) {
      case 'completed':
        return { text: 'Selesai', bg: '#dcfce7', color: '#15803d', icon: CheckCircle2 };
      case 'spk_processing':
        return { text: 'SPK Diproses', bg: '#dbeafe', color: '#1d4ed8', icon: Clock };
      case 'pending_spk':
        return { text: 'Belum Buat SPK', bg: '#ffedd5', color: '#c2410c', icon: AlertCircle };
      case 'waiting_dp':
        return { text: 'Menunggu DP', bg: '#fef9c3', color: '#a16207', icon: Clock };
      case 'cancelled':
        return { text: 'Batal', bg: '#fee2e2', color: '#b91c1c', icon: AlertCircle };
      default:
        return { text: status || 'Pending', bg: '#f1f5f9', color: '#475569', icon: Clock };
    }
  };

  const getPackingBadge = (pStatus) => {
    switch (pStatus) {
      case 'verified':
      case 'packed':
        return { text: 'Sudah Dikemas', bg: '#ecfdf5', color: '#047857' };
      case 'packing':
        return { text: 'Sedang Dikemas', bg: '#eff6ff', color: '#2563eb' };
      default:
        return { text: 'Belum Dikemas', bg: '#f8fafc', color: '#64748b' };
    }
  };

  return (
    <div className="subpage-container" style={{ paddingBottom: '90px' }}>
      {/* 1. Header Banner & KPI Summary Cards */}
      <div style={{ padding: '16px 18px 8px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
          <div>
            <h2 style={{ fontSize: '20px', fontWeight: '800', color: '#0f172a', margin: '0' }}>
              PO
            </h2>
          </div>
          <button
            onClick={() => loadData(true)}
            disabled={isRefreshing || isLoading}
            style={{
              background: '#f1f5f9',
              border: 'none',
              borderRadius: '10px',
              padding: '8px 12px',
              color: '#334155',
              fontSize: '12px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              cursor: 'pointer'
            }}
          >
            <RefreshCw size={14} className={isRefreshing ? 'animate-spin' : ''} />
            <span>Segarkan</span>
          </button>
        </div>

        {/* KPI Mini Metric Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '10px', marginBottom: '14px' }}>
          {/* Card Omset */}
          <div style={{ background: '#ffffff', borderRadius: '14px', padding: '12px 14px', border: '1px solid #e2e8f0', boxShadow: '0 2px 6px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#059669', marginBottom: '4px' }}>
              <Banknote size={15} />
              <span style={{ fontSize: '11px', fontWeight: '700', color: '#64748b' }}>Total Omset Offline</span>
            </div>
            <div style={{ fontSize: '16px', fontWeight: '800', color: '#0f172a' }}>
              {formatRupiah(summary.totalOmset)}
            </div>
            <div style={{ fontSize: '10.5px', color: '#10b981', marginTop: '2px', fontWeight: '600' }}>
              Terbayar: {formatRupiah(summary.totalTerbayar)}
            </div>
          </div>

          {/* Card Piutang */}
          <div style={{ background: '#ffffff', borderRadius: '14px', padding: '12px 14px', border: '1px solid #e2e8f0', boxShadow: '0 2px 6px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#dc2626', marginBottom: '4px' }}>
              <CreditCard size={15} />
              <span style={{ fontSize: '11px', fontWeight: '700', color: '#64748b' }}>Sisa Piutang Nota</span>
            </div>
            <div style={{ fontSize: '16px', fontWeight: '800', color: summary.totalPiutang > 0 ? '#dc2626' : '#10b981' }}>
              {summary.totalPiutang > 0 ? formatRupiah(summary.totalPiutang) : 'Lunas (Rp 0)'}
            </div>
            <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '2px' }}>
              {summary.totalCount} total transaksi
            </div>
          </div>

          {/* Card Belum Kemas */}
          <div style={{ background: '#ffffff', borderRadius: '14px', padding: '12px 14px', border: '1px solid #e2e8f0', boxShadow: '0 2px 6px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#ea580c', marginBottom: '4px' }}>
              <Package size={15} />
              <span style={{ fontSize: '11px', fontWeight: '700', color: '#64748b' }}>Belum Dikemas</span>
            </div>
            <div style={{ fontSize: '16px', fontWeight: '800', color: summary.unpackedCount > 0 ? '#ea580c' : '#059669' }}>
              {summary.unpackedCount} <span style={{ fontSize: '11px', fontWeight: '600', color: '#64748b' }}>Nota</span>
            </div>
            <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '2px' }}>
              Perlu verifikasi fisik
            </div>
          </div>

          {/* Card PO SPK */}
          <div style={{ background: '#ffffff', borderRadius: '14px', padding: '12px 14px', border: '1px solid #e2e8f0', boxShadow: '0 2px 6px rgba(0,0,0,0.03)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#7c3aed', marginBottom: '4px' }}>
              <QrCode size={15} />
              <span style={{ fontSize: '11px', fontWeight: '700', color: '#64748b' }}>Pre-Order SPK</span>
            </div>
            <div style={{ fontSize: '16px', fontWeight: '800', color: '#7c3aed' }}>
              {summary.poSpkCount} <span style={{ fontSize: '11px', fontWeight: '600', color: '#64748b' }}>Pesanan PO</span>
            </div>
            <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '2px' }}>
              Jalur produksi jahit
            </div>
          </div>
        </div>

        {/* 2. Search Bar */}
        <form onSubmit={handleSearchSubmit} style={{ position: 'relative', marginBottom: '12px' }}>
          <Search size={16} style={{ position: 'absolute', left: '12px', top: '12px', color: '#94a3b8' }} />
          <input
            type="text"
            placeholder="Cari No. Nota, Pembeli, Instansi..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{
              width: '100%',
              padding: '10px 38px 10px 36px',
              borderRadius: '12px',
              border: '1px solid #cbd5e1',
              fontSize: '13px',
              fontWeight: '600',
              color: '#0f172a',
              background: '#ffffff',
              boxSizing: 'border-box',
              outline: 'none'
            }}
          />
          {searchTerm && (
            <button
              type="button"
              onClick={() => {
                setSearchTerm('');
                setTimeout(() => loadData(), 50);
              }}
              style={{
                position: 'absolute',
                right: '10px',
                top: '10px',
                background: 'transparent',
                border: 'none',
                color: '#94a3b8',
                cursor: 'pointer',
                padding: '2px'
              }}
            >
              <X size={16} />
            </button>
          )}
        </form>

        {/* 3. Filter Tabs (Horizontal Scroll) */}
        <div 
          style={{ 
            display: 'flex', 
            gap: '8px', 
            overflowX: 'auto', 
            paddingBottom: '6px',
            scrollbarWidth: 'none',
            msOverflowStyle: 'none'
          }}
        >
          {[
            { id: 'ALL', label: 'Semua Nota' },
            { id: 'UNPACKED', label: 'Belum Kemas', badge: summary.unpackedCount },
            { id: 'PIUTANG', label: 'Ada Piutang', badge: summary.totalPiutang > 0 ? '!' : null },
            { id: 'PO_SPK', label: 'Pre-Order SPK', badge: summary.poSpkCount },
            { id: 'COMPLETED', label: 'Selesai Lunas' },
          ].map((tab) => {
            const isActive = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                style={{
                  padding: '7px 14px',
                  borderRadius: '20px',
                  border: isActive ? '1px solid #0284c7' : '1px solid #e2e8f0',
                  background: isActive ? '#0284c7' : '#ffffff',
                  color: isActive ? '#ffffff' : '#475569',
                  fontSize: '12px',
                  fontWeight: '700',
                  whiteSpace: 'nowrap',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                  boxShadow: isActive ? '0 2px 8px rgba(2, 132, 199, 0.25)' : 'none'
                }}
              >
                <span>{tab.label}</span>
                {tab.badge !== null && tab.badge !== undefined && tab.badge > 0 && (
                  <span
                    style={{
                      background: isActive ? '#ffffff' : '#e0f2fe',
                      color: isActive ? '#0284c7' : '#0369a1',
                      borderRadius: '10px',
                      padding: '1px 6px',
                      fontSize: '10px',
                      fontWeight: '800'
                    }}
                  >
                    {tab.badge}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* 4. Sales Cards List */}
      <div style={{ padding: '6px 18px' }}>
        {isLoading ? (
          <div style={{ textAlign: 'center', padding: '40px 0', color: '#94a3b8' }}>
            <RefreshCw size={24} className="animate-spin" style={{ margin: '0 auto 10px', display: 'block' }} />
            <span style={{ fontSize: '13px', fontWeight: '600' }}>Memuat data penjualan offline...</span>
          </div>
        ) : sales.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '48px 16px', background: '#ffffff', borderRadius: '16px', border: '1px dashed #cbd5e1' }}>
            <Receipt size={40} style={{ color: '#cbd5e1', margin: '0 auto 12px' }} />
            <h4 style={{ fontSize: '15px', fontWeight: '800', color: '#1e293b', margin: '0 0 4px' }}>
              Tidak Ada Transaksi Ditemukan
            </h4>
            <p style={{ fontSize: '12px', color: '#64748b', margin: 0 }}>
              {searchTerm ? `Pencarian "${searchTerm}" tidak cocok dengan nota manapun.` : 'Belum ada data penjualan offline pada filter ini.'}
            </p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {sales.map((sale) => {
              const st = getStatusBadge(sale.status);
              const StIcon = st.icon;
              const pack = getPackingBadge(sale.packing_status);
              const sisa = Math.max(0, parseFloat(sale.remaining_amount) || 0);
              const isPackedAll = (sale.total_packed_qty || 0) >= (sale.total_items_qty || 1) && (sale.total_items_qty || 0) > 0;

              return (
                <div
                  key={sale.id}
                  onClick={() => handleOpenDetail(sale.id)}
                  style={{
                    background: '#ffffff',
                    borderRadius: '16px',
                    border: '1px solid #e2e8f0',
                    padding: '14px',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
                    cursor: 'pointer',
                    transition: 'transform 0.15s ease'
                  }}
                >
                  {/* Card Header: No Nota & Tanggal */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '8px' }}>
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <span style={{ fontSize: '13.5px', fontWeight: '800', fontFamily: 'monospace', color: '#0f172a' }}>
                          #{sale.sale_number}
                        </span>
                        {sale.is_po ? (
                          <span style={{ fontSize: '10px', fontWeight: '800', background: '#ede9fe', color: '#6d28d9', padding: '1.5px 6px', borderRadius: '4px' }}>
                            PO SPK
                          </span>
                        ) : null}
                      </div>
                      <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px', display: 'flex', alignItems: 'center', gap: '4px' }}>
                        <Calendar size={12} />
                        <span>{sale.sold_at ? String(sale.sold_at).slice(0, 16) : 'Hari ini'}</span>
                        <span>&bull;</span>
                        <span>{sale.store_name || 'Toko Fisik'}</span>
                      </div>
                    </div>

                    {/* Status Badge */}
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' }}>
                      <span
                        style={{
                          fontSize: '11px',
                          fontWeight: '700',
                          background: st.bg,
                          color: st.color,
                          padding: '3px 8px',
                          borderRadius: '8px',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px'
                        }}
                      >
                        <StIcon size={12} />
                        {st.text}
                      </span>
                    </div>
                  </div>

                  {/* Customer Info */}
                  <div style={{ background: '#f8fafc', borderRadius: '10px', padding: '8px 10px', marginBottom: '10px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <User size={13} style={{ color: '#64748b' }} />
                        <span style={{ fontSize: '12px', fontWeight: '700', color: '#1e293b' }}>
                          {sale.buyer_name || 'Pelanggan Umum (Walk-in)'}
                        </span>
                      </div>
                      {sale.buyer_phone && (
                        <span style={{ fontSize: '11px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '3px' }}>
                          <Phone size={11} /> {sale.buyer_phone}
                        </span>
                      )}
                    </div>
                    {sale.institution_name && (
                      <div style={{ fontSize: '11px', color: '#0369a1', marginTop: '3px', display: 'flex', alignItems: 'center', gap: '4px' }}>
                        <Building2 size={11} />
                        <span style={{ fontWeight: '600' }}>{sale.institution_name}</span>
                      </div>
                    )}
                  </div>

                  {/* Items Preview */}
                  {sale.items && sale.items.length > 0 && (
                    <div style={{ marginBottom: '10px' }}>
                      <div style={{ fontSize: '11.5px', color: '#334155', fontWeight: '600' }}>
                        {sale.items.slice(0, 2).map((it) => `${it.product_name || 'Produk'} (${it.quantity} pcs)`).join(', ')}
                        {sale.items.length > 2 ? ` +${sale.items.length - 2} item lainnya` : ''}
                      </div>
                    </div>
                  )}

                  {/* Packing Progress Bar & Badge */}
                  <div style={{ marginBottom: '10px', background: '#f1f5f9', padding: '8px 10px', borderRadius: '8px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11px', marginBottom: '4px' }}>
                      <span style={{ color: '#475569', fontWeight: '600' }}>Verifikasi Kemasan Fisik:</span>
                      <span style={{ fontWeight: '800', color: isPackedAll ? '#15803d' : '#0369a1' }}>
                        {sale.total_packed_qty || 0} / {sale.total_items_qty || 0} Pcs ({sale.total_items_qty > 0 ? Math.round(((sale.total_packed_qty || 0) / sale.total_items_qty) * 100) : 0}%)
                      </span>
                    </div>
                    <div style={{ height: '6px', background: '#e2e8f0', borderRadius: '999px', overflow: 'hidden' }}>
                      <div
                        style={{
                          height: '100%',
                          width: `${sale.total_items_qty > 0 ? Math.min(100, Math.round(((sale.total_packed_qty || 0) / sale.total_items_qty) * 100)) : 0}%`,
                          background: isPackedAll ? '#10b981' : '#0ea5e9',
                          borderRadius: '999px',
                          transition: 'width 0.3s ease'
                        }}
                      />
                    </div>
                  </div>

                  {/* Footer: Tagihan, Piutang & Action Arrow */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', paddingTop: '8px', borderTop: '1px solid #f1f5f9' }}>
                    <div>
                      <div style={{ fontSize: '10.5px', color: '#64748b' }}>Total Tagihan:</div>
                      <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a' }}>
                        {formatRupiah(sale.grand_total)}
                      </div>
                    </div>

                    <div style={{ textAlign: 'right' }}>
                      <div style={{ fontSize: '10.5px', color: '#64748b' }}>Status Pembayaran:</div>
                      <div style={{ fontSize: '13px', fontWeight: '800', color: sisa > 0 ? '#dc2626' : '#15803d' }}>
                        {sisa > 0 ? `Sisa: ${formatRupiah(sisa)}` : 'LUNAS'}
                      </div>
                    </div>

                    <div style={{ color: '#94a3b8' }}>
                      <ChevronRight size={18} />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* 5. Detail Bottom Sheet / Modal */}
      {selectedSaleId && (
        <div
          onClick={handleCloseDetail}
          style={{
            position: 'fixed',
            inset: 0,
            background: 'rgba(15, 23, 42, 0.6)',
            zIndex: 9999,
            display: 'flex',
            alignItems: 'flex-end',
            justifyContent: 'center',
            backdropFilter: 'blur(3px)'
          }}
        >
          <div
            onClick={(e) => e.stopPropagation()}
            style={{
              background: '#ffffff',
              width: '100%',
              maxWidth: '600px',
              borderTopLeftRadius: '24px',
              borderTopRightRadius: '24px',
              maxHeight: '90vh',
              overflowY: 'auto',
              padding: '20px 18px 36px',
              boxShadow: '0 -10px 40px rgba(0,0,0,0.15)',
              position: 'relative'
            }}
          >
            {/* Handle Drag */}
            <div style={{ width: '40px', height: '4px', background: '#cbd5e1', borderRadius: '999px', margin: '0 auto 16px' }} />

            {/* Modal Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '14px' }}>
              <div>
                <span style={{ fontSize: '11px', fontWeight: '800', color: '#0284c7', textTransform: 'uppercase' }}>
                  DETAIL NOTA PENJUALAN
                </span>
                <h3 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: '2px 0 0' }}>
                  #{saleDetail?.sale_number || 'Loading...'}
                </h3>
              </div>
              <button
                onClick={handleCloseDetail}
                style={{
                  background: '#f1f5f9',
                  border: 'none',
                  borderRadius: '50%',
                  width: '32px',
                  height: '32px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: 'pointer'
                }}
              >
                <X size={16} />
              </button>
            </div>

            {isLoadingDetail || !saleDetail ? (
              <div style={{ textAlign: 'center', padding: '40px 0', color: '#94a3b8' }}>
                <RefreshCw size={24} className="animate-spin" style={{ margin: '0 auto 10px', display: 'block' }} />
                <span>Memuat rincian nota...</span>
              </div>
            ) : (
              <div>
                {/* Status Badges Row */}
                <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '14px' }}>
                  {(() => {
                    const st = getStatusBadge(saleDetail.status);
                    const StIcon = st.icon;
                    return (
                      <span style={{ fontSize: '11px', fontWeight: '700', background: st.bg, color: st.color, padding: '4px 10px', borderRadius: '8px', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                        <StIcon size={12} /> {st.text}
                      </span>
                    );
                  })()}
                  {(() => {
                    const pk = getPackingBadge(saleDetail.packing_status);
                    return (
                      <span style={{ fontSize: '11px', fontWeight: '700', background: pk.bg, color: pk.color, padding: '4px 10px', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                        <Package size={12} style={{ marginRight: '4px', verticalAlign: '-1px' }} />
                        {pk.text}
                      </span>
                    );
                  })()}
                  {saleDetail.is_po ? (
                    <span style={{ fontSize: '11px', fontWeight: '800', background: '#ede9fe', color: '#6d28d9', padding: '4px 10px', borderRadius: '8px' }}>
                      Pre-Order Jahit SPK
                    </span>
                  ) : null}
                </div>

                {/* Top Quick Actions (Tanpa Scroll) */}
                <div style={{ display: 'flex', gap: '8px', marginBottom: '14px' }}>
                  {onOpenScanKemasan && (
                    <button
                      onClick={() => {
                        handleCloseDetail();
                        onOpenScanKemasan();
                      }}
                      style={{
                        flex: 1,
                        padding: '10px 14px',
                        borderRadius: '10px',
                        border: 'none',
                        background: 'linear-gradient(135deg, #0284c7, #0369a1)',
                        color: '#ffffff',
                        fontSize: '12.5px',
                        fontWeight: '700',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px',
                        boxShadow: '0 2px 8px rgba(2, 132, 199, 0.25)'
                      }}
                    >
                      <Package size={16} />
                      <span>Kemas Offline</span>
                    </button>
                  )}
                  {saleDetail.is_po && onOpenScanSpk && (
                    <button
                      onClick={() => {
                        handleCloseDetail();
                        onOpenScanSpk();
                      }}
                      style={{
                        flex: 1,
                        padding: '10px 14px',
                        borderRadius: '10px',
                        border: 'none',
                        background: 'linear-gradient(135deg, #7c3aed, #6d28d9)',
                        color: '#ffffff',
                        fontSize: '12.5px',
                        fontWeight: '700',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px',
                        boxShadow: '0 2px 8px rgba(124, 58, 237, 0.25)'
                      }}
                    >
                      <QrCode size={16} />
                      <span>Scan SPK</span>
                    </button>
                  )}
                </div>

                {/* Customer & Nota Info Card */}
                <div style={{ background: '#f8fafc', borderRadius: '12px', padding: '12px 14px', marginBottom: '16px', border: '1px solid #e2e8f0' }}>
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px', fontSize: '11.5px' }}>
                    <div>
                      <span style={{ color: '#64748b', display: 'block' }}>Pelanggan:</span>
                      <strong style={{ color: '#0f172a' }}>{saleDetail.buyer_name || 'Walk-in'}</strong>
                      {saleDetail.institution_name && (
                        <div style={{ color: '#0369a1', fontSize: '10.5px' }}>{saleDetail.institution_name}</div>
                      )}
                    </div>
                    <div>
                      <span style={{ color: '#64748b', display: 'block' }}>No. HP / WA:</span>
                      <strong style={{ color: '#0f172a' }}>{saleDetail.buyer_phone || '—'}</strong>
                    </div>
                    <div>
                      <span style={{ color: '#64748b', display: 'block' }}>Toko & Kasir:</span>
                      <span style={{ color: '#334155' }}>{saleDetail.store_name || 'Toko'} &bull; {saleDetail.cashier_name || 'Kasir'}</span>
                    </div>
                    <div>
                      <span style={{ color: '#64748b', display: 'block' }}>Waktu Transaksi:</span>
                      <span style={{ color: '#334155' }}>{saleDetail.sold_at ? String(saleDetail.sold_at).slice(0, 16) : '—'}</span>
                    </div>
                  </div>
                </div>

                {/* Linked SPK Alert (if Pre-Order) */}
                {saleDetail.spks && saleDetail.spks.length > 0 && (
                  <div style={{ background: '#faf5ff', border: '1px solid #e9d5ff', borderRadius: '12px', padding: '12px', marginBottom: '16px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#7c3aed', fontWeight: '800', fontSize: '12px', marginBottom: '6px' }}>
                      <QrCode size={16} />
                      <span>Terhubung dengan {saleDetail.spks.length} SPK Produksi</span>
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                      {saleDetail.spks.map((spk) => (
                        <div key={spk.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11px', background: '#ffffff', padding: '6px 10px', borderRadius: '6px', border: '1px solid #f3e8ff' }}>
                          <span style={{ fontFamily: 'monospace', fontWeight: '800', color: '#581c87' }}>#{spk.no_spk}</span>
                          <span style={{ color: '#64748b' }}>{spk.kategori_produk || 'Umum'}</span>
                          <span style={{ fontWeight: '700', color: '#7c3aed' }}>{spk.status || 'Proses'}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Items List */}
                <div style={{ marginBottom: '16px' }}>
                  <h4 style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                    Daftar Produk ({saleDetail.items?.length || 0} Baris Item)
                  </h4>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                    {saleDetail.items?.map((item) => (
                      <div
                        key={item.id}
                        style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          padding: '10px 12px',
                          borderRadius: '10px',
                          border: '1px solid #e2e8f0',
                          background: (item.packed_quantity || 0) >= item.quantity ? '#f0fdf4' : '#ffffff'
                        }}
                      >
                        <div style={{ flex: 1, minWidth: 0, paddingRight: '8px' }}>
                          <div style={{ fontSize: '12.5px', fontWeight: '700', color: '#0f172a', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                            {item.product_name}
                          </div>
                          <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '2px', fontFamily: 'monospace' }}>
                            SKU: {item.sku || '—'} &bull; {formatRupiah(item.price)}
                          </div>
                          <div style={{ fontSize: '11px', marginTop: '3px', color: (item.packed_quantity || 0) >= item.quantity ? '#15803d' : '#ea580c', fontWeight: '600' }}>
                            Kemas: {item.packed_quantity || 0} / {item.quantity} pcs
                          </div>
                        </div>

                        <div style={{ textAlign: 'right', flexShrink: 0 }}>
                          <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
                            {formatRupiah(item.subtotal || item.price * item.quantity)}
                          </div>
                          <div style={{ fontSize: '10px', color: '#64748b' }}>
                            {item.quantity} pcs
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Financial Summary */}
                <div style={{ background: '#f8fafc', borderRadius: '12px', padding: '12px 14px', marginBottom: '16px', border: '1px solid #e2e8f0' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', marginBottom: '6px', color: '#64748b' }}>
                    <span>Subtotal Produk:</span>
                    <span>{formatRupiah(saleDetail.subtotal)}</span>
                  </div>
                  {parseFloat(saleDetail.discount_amount) > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', marginBottom: '6px', color: '#dc2626' }}>
                      <span>Diskon:</span>
                      <span>-{formatRupiah(saleDetail.discount_amount)}</span>
                    </div>
                  )}
                  {parseFloat(saleDetail.tax_amount) > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', marginBottom: '6px', color: '#64748b' }}>
                      <span>Pajak:</span>
                      <span>+{formatRupiah(saleDetail.tax_amount)}</span>
                    </div>
                  )}
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '14px', fontWeight: '800', color: '#0f172a', borderTop: '1px solid #e2e8f0', paddingTop: '8px', marginBottom: '6px' }}>
                    <span>Grand Total:</span>
                    <span>{formatRupiah(saleDetail.grand_total)}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#059669', marginBottom: '6px' }}>
                    <span>Sudah Dibayar:</span>
                    <span>{formatRupiah(saleDetail.paid_amount)}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', fontWeight: '800', color: saleDetail.remaining_amount > 0 ? '#dc2626' : '#15803d', borderTop: '1px dashed #e2e8f0', paddingTop: '6px' }}>
                    <span>Sisa Piutang:</span>
                    <span>{saleDetail.remaining_amount > 0 ? formatRupiah(saleDetail.remaining_amount) : 'LUNAS (Rp 0)'}</span>
                  </div>
                </div>

                {/* Payments Log */}
                {saleDetail.payments && saleDetail.payments.length > 0 && (
                  <div style={{ marginBottom: '16px' }}>
                    <h4 style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                      Histori Pembayaran ({saleDetail.payments.length})
                    </h4>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                      {saleDetail.payments.map((p) => (
                        <div key={p.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#f0fdf4', padding: '8px 10px', borderRadius: '8px', border: '1px solid #bbf7d0', fontSize: '11.5px' }}>
                          <div>
                            <strong style={{ color: '#166534' }}>{formatRupiah(p.amount)}</strong>
                            <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                              {p.payment_date || '—'} &bull; {p.payment_method} ({p.payment_destination || 'Kas'})
                            </div>
                          </div>
                          {p.reference_number && (
                            <span style={{ fontSize: '10px', color: '#64748b', fontFamily: 'monospace' }}>
                              {p.reference_number}
                            </span>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Bottom Action Buttons */}
                <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                  <button
                    onClick={handleCloseDetail}
                    style={{
                      flex: 1,
                      padding: '12px',
                      borderRadius: '12px',
                      border: '1px solid #cbd5e1',
                      background: '#ffffff',
                      color: '#475569',
                      fontSize: '13px',
                      fontWeight: '700',
                      cursor: 'pointer'
                    }}
                  >
                    Tutup
                  </button>
                  {saleDetail.is_po && onOpenScanSpk && (
                    <button
                      onClick={() => {
                        handleCloseDetail();
                        onOpenScanSpk();
                      }}
                      style={{
                        flex: 1,
                        padding: '12px',
                        borderRadius: '12px',
                        border: 'none',
                        background: 'linear-gradient(135deg, #7c3aed, #6d28d9)',
                        color: '#ffffff',
                        fontSize: '13px',
                        fontWeight: '700',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px'
                      }}
                    >
                      <QrCode size={15} />
                      <span>Scan SPK</span>
                    </button>
                  )}
                  {onOpenScanKemasan && (
                    <button
                      onClick={() => {
                        handleCloseDetail();
                        onOpenScanKemasan();
                      }}
                      style={{
                        flex: 1,
                        padding: '12px',
                        borderRadius: '12px',
                        border: 'none',
                        background: 'linear-gradient(135deg, #0284c7, #0369a1)',
                        color: '#ffffff',
                        fontSize: '13px',
                        fontWeight: '700',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px'
                      }}
                    >
                      <Package size={15} />
                      <span>Kemas Offline</span>
                    </button>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
