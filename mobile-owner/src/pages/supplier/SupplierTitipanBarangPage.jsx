import React, { useState, useEffect } from 'react';
import { Search, Filter, Calendar, Barcode, Eye, Box, Package, RefreshCw, CheckCircle2, Clock, AlertTriangle } from 'lucide-react';
import { fetchSupplierConsignments } from '../../api/supplierConsignmentApi';
import SupplierTitipanDetailModal from './SupplierTitipanDetailModal';
import SupplierBarcodeModal from './SupplierBarcodeModal';
import { showAlert, showToast } from '../../utils/alerts';

export default function SupplierTitipanBarangPage({ currentSupplierUser, onOpenStockCard }) {
  const [consignments, setConsignments] = useState([]);
  const [kpi, setKpi] = useState({
    totalTransaksi: 0,
    totalQtyReceived: 0,
    totalAmountHpp: 0,
    totalSisaStok: 0,
    totalQtySold: 0
  });
  const [isLoading, setIsLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [showFilterDrawer, setShowFilterDrawer] = useState(false);

  // Modals
  const [selectedConsignment, setSelectedConsignment] = useState(null);
  const [barcodeModalConsignment, setBarcodeModalConsignment] = useState(null);

  const loadData = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSupplierConsignments({
        search,
        date_from: dateFrom,
        date_to: dateTo,
        status: statusFilter,
        supplier_id: currentSupplierUser?.supplier_id
      });
      if (res && res.data) {
        setConsignments(res.data);
        if (res.kpi) setKpi(res.kpi);
      }
    } catch (err) {
      console.warn('Failed to load supplier consignments:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [search, dateFrom, dateTo, statusFilter]);

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title Header */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#1e293b', margin: 0 }}>
            Titipan Barang (Konsinyasi)
          </h2>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '2px 0 0' }}>
            Data penerimaan barang titipan supplier & stok master ERP
          </p>
        </div>

        <button
          onClick={loadData}
          disabled={isLoading}
          style={{
            background: '#ffffff',
            color: '#475569',
            border: '1px solid #cbd5e1',
            borderRadius: '12px',
            padding: '8px 12px',
            fontSize: '12px',
            fontWeight: '700',
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            cursor: 'pointer',
            boxShadow: '0 1px 3px rgba(0,0,0,0.05)'
          }}
        >
          <RefreshCw size={14} className={isLoading ? 'animate-spin' : ''} />
          <span>Refresh</span>
        </button>
      </div>

      {/* KPI 4 Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
        {/* Total Transaksi */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          borderLeft: '4px solid #f59e0b',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
            Total Transaksi
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#1e293b', margin: '4px 0' }}>
            {kpi.totalTransaksi || consignments.length} Nota
          </div>
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Penerimaan terdaftar</div>
        </div>

        {/* Total Qty Diterima */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          borderLeft: '4px solid #10b981',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#047857', textTransform: 'uppercase' }}>
            Total Qty Masuk
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#065f46', margin: '4px 0' }}>
            {Number(kpi.totalQtyReceived || 0).toLocaleString('id-ID')} PCS
          </div>
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Barang dititipkan</div>
        </div>

        {/* Sisa Stok Di Toko */}
        <div style={{
          background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #fde68a'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
            Sisa Stok Toko
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#d97706', margin: '4px 0' }}>
            {Number(kpi.totalSisaStok || 0).toLocaleString('id-ID')} PCS
          </div>
          <div style={{ fontSize: '10px', color: '#b45309' }}>Belum terjual</div>
        </div>

        {/* Total Nilai HPP */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          borderLeft: '4px solid #6366f1',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#4f46e5', textTransform: 'uppercase' }}>
            Total Modal HPP
          </div>
          <div style={{ fontSize: '15.5px', fontWeight: '900', color: '#4338ca', fontFamily: 'monospace', margin: '4px 0' }}>
            Rp {Number(kpi.totalAmountHpp || 0).toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Akumulasi nilai modal</div>
        </div>
      </div>

      {/* Search & Filter Toolbar */}
      <div style={{ display: 'flex', gap: '8px' }}>
        <div style={{
          flex: 1,
          background: '#ffffff',
          border: '1px solid #cbd5e1',
          borderRadius: '14px',
          padding: '8px 12px',
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
        }}>
          <Search size={16} color="#94a3b8" />
          <input
            type="text"
            placeholder="Cari no. ref / keterangan..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            style={{
              border: 'none',
              outline: 'none',
              fontSize: '13px',
              width: '100%',
              color: '#1e293b',
              background: 'transparent'
            }}
          />
          {search && (
            <button
              onClick={() => setSearch('')}
              style={{ border: 'none', background: 'transparent', color: '#94a3b8', cursor: 'pointer', padding: 0 }}
            >
              ✕
            </button>
          )}
        </div>

        <button
          onClick={() => setShowFilterDrawer(!showFilterDrawer)}
          style={{
            background: (dateFrom || dateTo || statusFilter) ? '#fef3c7' : '#ffffff',
            border: (dateFrom || dateTo || statusFilter) ? '1px solid #f59e0b' : '1px solid #cbd5e1',
            color: (dateFrom || dateTo || statusFilter) ? '#b45309' : '#475569',
            borderRadius: '14px',
            padding: '0 12px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            fontSize: '12px',
            fontWeight: '700',
            gap: '4px'
          }}
        >
          <Filter size={15} />
          <span>Filter</span>
        </button>

        <button
          onClick={loadData}
          disabled={isLoading}
          style={{
            background: '#ffffff',
            border: '1px solid #cbd5e1',
            borderRadius: '14px',
            width: '38px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: '#475569',
            cursor: 'pointer'
          }}
        >
          <RefreshCw size={15} style={{ animation: isLoading ? 'spin 1s linear infinite' : 'none' }} />
        </button>
      </div>

      {/* Filter Extended Box */}
      {showFilterDrawer && (
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e2e8f0',
          padding: '12px 14px',
          display: 'flex',
          flexDirection: 'column',
          gap: '8px',
          boxShadow: '0 4px 12px rgba(0,0,0,0.03)'
        }}>
          <div style={{ fontSize: '11px', fontWeight: '800', color: '#64748b', textTransform: 'uppercase' }}>
            Filter Rentang Tanggal
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
            <div>
              <label style={{ display: 'block', fontSize: '10px', color: '#64748b', marginBottom: '2px' }}>Mulai</label>
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                style={{ width: '100%', padding: '6px 8px', borderRadius: '8px', border: '1px solid #cbd5e1', fontSize: '11.5px', boxSizing: 'border-box' }}
              />
            </div>
            <div>
              <label style={{ display: 'block', fontSize: '10px', color: '#64748b', marginBottom: '2px' }}>Sampai</label>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                style={{ width: '100%', padding: '6px 8px', borderRadius: '8px', border: '1px solid #cbd5e1', fontSize: '11.5px', boxSizing: 'border-box' }}
              />
            </div>
          </div>

          {(dateFrom || dateTo || statusFilter) && (
            <button
              onClick={() => { setDateFrom(''); setDateTo(''); setStatusFilter(''); }}
              style={{
                alignSelf: 'flex-end',
                background: 'none',
                border: 'none',
                color: '#ef4444',
                fontSize: '11px',
                fontWeight: '700',
                cursor: 'pointer',
                marginTop: '4px'
              }}
            >
              Reset Filter
            </button>
          )}
        </div>
      )}

      {/* List of Consignments Cards */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
        {isLoading ? (
          <div style={{ textAlign: 'center', padding: '40px', color: '#94a3b8', fontSize: '13px' }}>
            Memuat transaksi barang titipan...
          </div>
        ) : consignments.length === 0 ? (
          <div style={{
            background: '#ffffff',
            borderRadius: '20px',
            padding: '36px 16px',
            textAlign: 'center',
            border: '1px solid #e2e8f0',
            color: '#94a3b8'
          }}>
            <Box size={38} style={{ margin: '0 auto 10px', opacity: 0.35, color: '#f59e0b' }} />
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#1e293b' }}>Belum Ada Transaksi Titipan</div>
            <div style={{ fontSize: '11.5px', margin: '4px 0 16px', color: '#64748b' }}>
              Tekan tombol "Titip Baru" di kanan atas untuk mencatat penerimaan barang titipan supplier.
            </div>
            <button
              onClick={() => setFormModalState({ isOpen: true, data: null })}
              style={{
                background: 'linear-gradient(135deg, #f59e0b, #d97706)',
                color: '#ffffff',
                border: 'none',
                borderRadius: '12px',
                padding: '10px 18px',
                fontSize: '12.5px',
                fontWeight: '700',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px'
              }}
            >
              <Plus size={15} />
              <span>Tambah Penerimaan Pertama</span>
            </button>
          </div>
        ) : (
          consignments.map((c) => {
            const items = c.items || [];
            const refNum = c.referenceNumber || c.reference_number;
            const supName = c.supplierName || c.supplier_name;
            const totalQty = c.totalQtyReceived || c.total_qty_received || 0;
            const totalSold = c.totalQtySold || c.total_qty_sold || 0;
            const sisaStok = c.sisaStok !== undefined ? c.sisaStok : Math.max(0, totalQty - totalSold);
            const totalHpp = c.totalAmountHpp || c.total_amount_hpp || 0;
            const dateStr = c.consignmentDateFormatted || c.consignmentDate;
            const status = c.status || 'approved';

            return (
              <div
                key={c.id}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '20px',
                  padding: '14px 16px',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '10px',
                  transition: 'transform 0.15s ease'
                }}
              >
                {/* Card Header */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <div style={{
                      fontFamily: 'monospace',
                      fontWeight: '800',
                      fontSize: '13px',
                      color: '#d97706',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '6px'
                    }}>
                      <Box size={14} color="#f59e0b" />
                      <span>{refNum}</span>
                    </div>
                    <div style={{ fontSize: '14px', fontWeight: '800', color: '#1e293b', marginTop: '2px' }}>
                      {supName}
                    </div>
                    <div style={{ fontSize: '11px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '2px' }}>
                      <Calendar size={12} color="#94a3b8" />
                      <span>{dateStr}</span>
                    </div>
                  </div>

                  <span style={{
                    background: status === 'approved' ? 'rgba(16, 185, 129, 0.12)' : 'rgba(245, 158, 11, 0.12)',
                    color: status === 'approved' ? '#059669' : '#d97706',
                    border: status === 'approved' ? '1px solid rgba(16, 185, 129, 0.3)' : '1px solid rgba(245, 158, 11, 0.3)',
                    fontSize: '10.5px',
                    fontWeight: '800',
                    padding: '3px 8px',
                    borderRadius: '8px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}>
                    {status === 'approved' ? <CheckCircle2 size={12} /> : <Clock size={12} />}
                    {status === 'approved' ? 'Selesai' : 'Pending'}
                  </span>
                </div>

                {/* Items Summary Snippet */}
                {items.length > 0 && (
                  <div style={{
                    background: '#f8fafc',
                    borderRadius: '12px',
                    padding: '8px 12px',
                    fontSize: '11.5px',
                    color: '#475569',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '4px'
                  }}>
                    {items.slice(0, 2).map((it, idx) => (
                      <div key={it.id || idx} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontWeight: '600', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '190px' }}>
                          • {it.productName || it.product_name}
                        </span>
                        <span style={{ fontWeight: '700', color: '#1e293b' }}>
                          {it.qtyReceived || it.qty_received} PCS
                        </span>
                      </div>
                    ))}
                    {items.length > 2 && (
                      <div style={{ fontSize: '10.5px', color: '#94a3b8', fontStyle: 'italic' }}>
                        +{items.length - 2} produk lainnya...
                      </div>
                    )}
                  </div>
                )}

                {/* Metrics Breakdown Grid */}
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(3, 1fr)',
                  gap: '6px',
                  background: '#fefce8',
                  border: '1px solid #fef08a',
                  borderRadius: '12px',
                  padding: '8px 10px',
                  textAlign: 'center'
                }}>
                  <div>
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Qty Titip</div>
                    <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#1e293b' }}>{totalQty} PCS</div>
                  </div>
                  <div>
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Sisa Di Toko</div>
                    <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#d97706' }}>{sisaStok} PCS</div>
                  </div>
                  <div>
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Nilai Modal</div>
                    <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#1e293b', fontFamily: 'monospace' }}>
                      Rp {Number(totalHpp).toLocaleString('id-ID')}
                    </div>
                  </div>
                </div>

                {/* Action Buttons Toolbar */}
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  paddingTop: '6px',
                  borderTop: '1px solid #f1f5f9'
                }}>
                  <div style={{ display: 'flex', gap: '6px' }}>
                    <button
                      onClick={() => setBarcodeModalConsignment(c)}
                      title="Cetak Barcode Label"
                      style={{
                        background: '#ecfdf5',
                        border: '1px solid #a7f3d0',
                        color: '#059669',
                        borderRadius: '10px',
                        padding: '6px 10px',
                        fontSize: '11.5px',
                        fontWeight: '700',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px',
                        cursor: 'pointer'
                      }}
                    >
                      <Barcode size={14} />
                      <span>Barcode</span>
                    </button>
                  </div>

                  <button
                    onClick={() => setSelectedConsignment(c)}
                    style={{
                      background: 'linear-gradient(135deg, #1e293b, #0f172a)',
                      color: '#ffffff',
                      border: 'none',
                      borderRadius: '10px',
                      padding: '7px 12px',
                      fontSize: '11.5px',
                      fontWeight: '700',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px',
                      cursor: 'pointer'
                    }}
                  >
                    <Eye size={14} />
                    <span>Detail</span>
                  </button>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Detail Modal */}
      {selectedConsignment && (
        <SupplierTitipanDetailModal
          consignment={selectedConsignment}
          onClose={() => setSelectedConsignment(null)}
          onOpenBarcode={(cons) => setBarcodeModalConsignment(cons)}
        />
      )}

      {/* Barcode Print Modal */}
      {barcodeModalConsignment && (
        <SupplierBarcodeModal
          consignment={barcodeModalConsignment}
          onClose={() => setBarcodeModalConsignment(null)}
        />
      )}

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
