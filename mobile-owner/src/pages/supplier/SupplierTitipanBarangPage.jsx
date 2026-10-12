import React, { useState, useEffect } from 'react';
import { Search, Filter, Calendar, Barcode, Eye, Box, Package, RefreshCw, CheckCircle2, Clock, ChevronRight, Sparkles } from 'lucide-react';
import { fetchSupplierConsignments } from '../../api/supplierConsignmentApi';
import SupplierTitipanDetailModal from './SupplierTitipanDetailModal';
import SupplierBarcodeModal from './SupplierBarcodeModal';

export default function SupplierTitipanBarangPage({ currentSupplierUser }) {
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
      {/* Title Header with Modern Glass Accent */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: 0, letterSpacing: '-0.2px' }}>
              Titipan Barang
            </h2>
            <span style={{
              background: '#fef3c7',
              color: '#d97706',
              fontSize: '10px',
              fontWeight: '800',
              padding: '2px 7px',
              borderRadius: '6px'
            }}>
              Konsinyasi
            </span>
          </div>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '3px 0 0' }}>
            Riwayat penerimaan & pemantauan stok titipan
          </p>
        </div>

        <button
          onClick={loadData}
          disabled={isLoading}
          style={{
            background: '#ffffff',
            color: '#475569',
            border: '1px solid #e2e8f0',
            borderRadius: '12px',
            padding: '8px 12px',
            fontSize: '12px',
            fontWeight: '700',
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            cursor: 'pointer',
            boxShadow: '0 2px 5px rgba(0,0,0,0.03)'
          }}
        >
          <RefreshCw size={14} style={{ animation: isLoading ? 'spin 1s linear infinite' : 'none' }} />
          <span>Refresh</span>
        </button>
      </div>

      {/* KPI Cards: Clean & High Contrast */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
        {/* Total Transaksi */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
          borderRadius: '16px',
          padding: '13px 14px',
          border: '1px solid #e2e8f0',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
            Total Transaksi
          </div>
          <div style={{ fontSize: '19px', fontWeight: '900', color: '#1e293b', margin: '3px 0 1px' }}>
            {kpi.totalTransaksi || consignments.length} <span style={{ fontSize: '12px', fontWeight: '700', color: '#64748b' }}>Nota</span>
          </div>
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Surat jalan tercatat</div>
        </div>

        {/* Total Qty Masuk */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%)',
          borderRadius: '16px',
          padding: '13px 14px',
          border: '1px solid #bbf7d0',
          boxShadow: '0 2px 8px rgba(16, 185, 129, 0.05)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#047857', textTransform: 'uppercase' }}>
            Total Qty Masuk
          </div>
          <div style={{ fontSize: '19px', fontWeight: '900', color: '#065f46', margin: '3px 0 1px' }}>
            {Number(kpi.totalQtyReceived || 0).toLocaleString('id-ID')} <span style={{ fontSize: '12px', fontWeight: '700', color: '#059669' }}>PCS</span>
          </div>
          <div style={{ fontSize: '10px', color: '#059669' }}>Akumulasi diterima</div>
        </div>

        {/* Sisa Stok Di Toko */}
        <div style={{
          background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
          borderRadius: '16px',
          padding: '13px 14px',
          border: '1px solid #fde68a'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
            Sisa Stok Toko
          </div>
          <div style={{ fontSize: '19px', fontWeight: '900', color: '#b45309', margin: '3px 0 1px' }}>
            {Number(kpi.totalSisaStok || 0).toLocaleString('id-ID')} <span style={{ fontSize: '12px', fontWeight: '700', color: '#d97706' }}>PCS</span>
          </div>
          <div style={{ fontSize: '10px', color: '#b45309' }}>Belum terjual</div>
        </div>

        {/* Total Terjual */}
        <div style={{
          background: 'linear-gradient(135deg, #ffffff 0%, #eff6ff 100%)',
          borderRadius: '16px',
          padding: '13px 14px',
          border: '1px solid #bfdbfe',
          boxShadow: '0 2px 8px rgba(59, 130, 246, 0.05)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#1d4ed8', textTransform: 'uppercase' }}>
            Total Terjual
          </div>
          <div style={{ fontSize: '19px', fontWeight: '900', color: '#1e40af', margin: '3px 0 1px' }}>
            {Number(kpi.totalQtySold || 0).toLocaleString('id-ID')} <span style={{ fontSize: '12px', fontWeight: '700', color: '#3b82f6' }}>PCS</span>
          </div>
          <div style={{ fontSize: '10px', color: '#2563eb' }}>Keluar etalase</div>
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
            placeholder="Cari no. referensi / catatan..."
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
      </div>

      {/* Filter Drawer Card */}
      {showFilterDrawer && (
        <div style={{
          background: '#ffffff',
          border: '1px solid #e2e8f0',
          borderRadius: '16px',
          padding: '14px',
          display: 'flex',
          flexDirection: 'column',
          gap: '12px',
          boxShadow: '0 6px 16px rgba(0,0,0,0.05)'
        }}>
          <div style={{ fontSize: '12px', fontWeight: '800', color: '#1e293b' }}>
            Filter Rentang Tanggal
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
            <div>
              <label style={{ fontSize: '10.5px', color: '#64748b', display: 'block', marginBottom: '4px' }}>Dari Tanggal</label>
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                style={{
                  width: '100%',
                  padding: '7px 10px',
                  borderRadius: '10px',
                  border: '1px solid #cbd5e1',
                  fontSize: '12px',
                  color: '#1e293b'
                }}
              />
            </div>
            <div>
              <label style={{ fontSize: '10.5px', color: '#64748b', display: 'block', marginBottom: '4px' }}>Sampai Tanggal</label>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                style={{
                  width: '100%',
                  padding: '7px 10px',
                  borderRadius: '10px',
                  border: '1px solid #cbd5e1',
                  fontSize: '12px',
                  color: '#1e293b'
                }}
              />
            </div>
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '4px' }}>
            {(dateFrom || dateTo || statusFilter) && (
              <button
                onClick={() => { setDateFrom(''); setDateTo(''); setStatusFilter(''); }}
                style={{
                  padding: '6px 12px',
                  borderRadius: '10px',
                  border: 'none',
                  background: '#f1f5f9',
                  color: '#475569',
                  fontSize: '11.5px',
                  fontWeight: '700',
                  cursor: 'pointer'
                }}
              >
                Reset
              </button>
            )}
            <button
              onClick={() => setShowFilterDrawer(false)}
              style={{
                padding: '6px 14px',
                borderRadius: '10px',
                border: 'none',
                background: '#1e293b',
                color: '#ffffff',
                fontSize: '11.5px',
                fontWeight: '700',
                cursor: 'pointer'
              }}
            >
              Terapkan
            </button>
          </div>
        </div>
      )}

      {/* Consignment List */}
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
            <div style={{ fontSize: '11.5px', marginTop: '4px', color: '#64748b' }}>
              Data tanda terima konsinyasi Anda akan tampil di halaman ini.
            </div>
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
                  padding: '15px 16px',
                  boxShadow: '0 3px 10px rgba(0,0,0,0.02)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '12px'
                }}
              >
                {/* Header Card */}
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
                    <div style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
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
                    {status === 'approved' ? 'Disetujui' : 'Pending'}
                  </span>
                </div>

                {/* Items Summary Snippet */}
                {items.length > 0 && (
                  <div style={{
                    background: '#f8fafc',
                    borderRadius: '12px',
                    padding: '9px 12px',
                    fontSize: '11.5px',
                    color: '#475569',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '5px'
                  }}>
                    {items.slice(0, 2).map((it, idx) => (
                      <div key={it.id || idx} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontWeight: '600', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '200px' }}>
                          • {it.productName || it.product_name}
                        </span>
                        <span style={{ fontWeight: '800', color: '#0f172a' }}>
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
                  background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
                  border: '1px solid #fef08a',
                  borderRadius: '12px',
                  padding: '9px 10px',
                  textAlign: 'center'
                }}>
                  <div>
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Qty Titip</div>
                    <div style={{ fontSize: '13px', fontWeight: '900', color: '#1e293b', marginTop: '2px' }}>{totalQty} PCS</div>
                  </div>
                  <div>
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Sisa Toko</div>
                    <div style={{ fontSize: '13px', fontWeight: '900', color: '#d97706', marginTop: '2px' }}>{sisaStok} PCS</div>
                  </div>
                  <div>
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Nilai Modal</div>
                    <div style={{ fontSize: '12.5px', fontWeight: '900', color: '#1e293b', fontFamily: 'monospace', marginTop: '2px' }}>
                      Rp {Number(totalHpp).toLocaleString('id-ID')}
                    </div>
                  </div>
                </div>

                {/* Action Buttons Toolbar: Barcode & Detail */}
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  paddingTop: '8px',
                  borderTop: '1px solid #f1f5f9'
                }}>
                  <button
                    onClick={() => setBarcodeModalConsignment(c)}
                    style={{
                      background: '#ecfdf5',
                      border: '1px solid #a7f3d0',
                      color: '#059669',
                      borderRadius: '10px',
                      padding: '7px 12px',
                      fontSize: '11.5px',
                      fontWeight: '700',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '5px',
                      cursor: 'pointer'
                    }}
                  >
                    <Barcode size={14} />
                    <span>Cetak Barcode</span>
                  </button>

                  <button
                    onClick={() => setSelectedConsignment(c)}
                    style={{
                      background: 'linear-gradient(135deg, #1e293b, #0f172a)',
                      color: '#ffffff',
                      border: 'none',
                      borderRadius: '10px',
                      padding: '8px 14px',
                      fontSize: '12px',
                      fontWeight: '700',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '5px',
                      cursor: 'pointer'
                    }}
                  >
                    <Eye size={14} />
                    <span>Lihat Detail</span>
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
