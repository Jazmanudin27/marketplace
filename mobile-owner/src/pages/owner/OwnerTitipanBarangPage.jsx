import React, { useState, useEffect } from 'react';
import { 
  Search, 
  Filter, 
  Calendar, 
  Barcode, 
  Eye, 
  Box, 
  Package, 
  RefreshCw, 
  CheckCircle2, 
  Clock, 
  ChevronRight, 
  Sparkles, 
  CreditCard, 
  AlertCircle, 
  Plus, 
  Layers, 
  TrendingUp, 
  DollarSign 
} from 'lucide-react';
import { 
  fetchSupplierConsignments, 
  createSupplierConsignment, 
  updateSupplierConsignment, 
  fetchConsignmentResources 
} from '../../api/supplierConsignmentApi';
import { showToast, showAlert } from '../../utils/alerts';
import OwnerTitipanDetailModal from './OwnerTitipanDetailModal';
import OwnerBarcodeModal from './OwnerBarcodeModal';
import OwnerTitipanFormModal from './OwnerTitipanFormModal';

export default function OwnerTitipanBarangPage({ 
  currentOwnerUser, 
  onOpenStockCard, 
  onOpenPayments, 
  onOpenSales 
}) {
  const [consignments, setConsignments] = useState([]);
  const [kpi, setKpi] = useState({
    totalTransaksi: 0,
    totalQtyReceived: 0,
    totalAmountHpp: 0,
    totalSisaStok: 0,
    totalQtySold: 0,
    totalAmountPaid: 0,
    totalSisaPembayaran: 0
  });
  const [isLoading, setIsLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [paymentFilter, setPaymentFilter] = useState(''); // '' | 'paid' | 'partial' | 'unpaid'
  const [selectedSupplierId, setSelectedSupplierId] = useState('');
  const [suppliersList, setSuppliersList] = useState([]);
  const [showFilterDrawer, setShowFilterDrawer] = useState(false);

  // Modals
  const [selectedConsignment, setSelectedConsignment] = useState(null);
  const [barcodeModalConsignment, setBarcodeModalConsignment] = useState(null);
  const [formModalData, setFormModalData] = useState(null); // null: closed, {}: create, {id, ...}: edit

  // Load suppliers list for filter
  useEffect(() => {
    fetchConsignmentResources()
      .then(res => {
        if (res && res.suppliers) setSuppliersList(res.suppliers);
      })
      .catch(() => {});
  }, []);

  const loadData = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSupplierConsignments({
        search,
        date_from: dateFrom,
        date_to: dateTo,
        status: statusFilter,
        supplier_id: selectedSupplierId || undefined
      });
      if (res && res.data) {
        setConsignments(res.data);
        if (res.kpi) setKpi(res.kpi);
      }
    } catch (err) {
      console.warn('Failed to load owner consignments:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [search, dateFrom, dateTo, statusFilter, selectedSupplierId]);

  const handleSaveConsignment = async (payload, editId) => {
    if (editId) {
      const res = await updateSupplierConsignment(editId, payload);
      if (res && res.success) {
        showToast('Transaksi titipan berhasil diperbarui.', 'success');
        setFormModalData(null);
        loadData();
      } else {
        throw new Error(res?.message || 'Gagal update konsinyasi.');
      }
    } else {
      const res = await createSupplierConsignment(payload);
      if (res && res.success) {
        showToast('Transaksi titipan baru berhasil dicatat!', 'success');
        setFormModalData(null);
        loadData();
      } else {
        throw new Error(res?.message || 'Gagal menyimpan konsinyasi.');
      }
    }
  };

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title Header with Action Buttons */}
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
              Konsinyasi Owner
            </span>
          </div>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '3px 0 0' }}>
            Kelola penerimaan stok titipan supplier & pantau tagihan
          </p>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <button
            onClick={() => setFormModalData({})}
            style={{
              background: 'linear-gradient(135deg, #f59e0b, #d97706)',
              color: '#ffffff',
              border: 'none',
              borderRadius: '12px',
              padding: '8px 12px',
              fontSize: '12px',
              fontWeight: '800',
              display: 'flex',
              alignItems: 'center',
              gap: '5px',
              cursor: 'pointer',
              boxShadow: '0 4px 10px rgba(245, 158, 11, 0.35)'
            }}
          >
            <Plus size={15} />
            <span>+ Catat Titipan</span>
          </button>

          <button
            onClick={loadData}
            disabled={isLoading}
            style={{
              background: '#ffffff',
              color: '#475569',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              width: '36px',
              height: '36px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: 'pointer',
              boxShadow: '0 2px 5px rgba(0,0,0,0.03)'
            }}
          >
            <RefreshCw size={14} style={{ animation: isLoading ? 'spin 1s linear infinite' : 'none' }} />
          </button>
        </div>
      </div>

      {/* Quick Navigation Pills: Switch between Consignment Sub-Modules */}
      <div style={{ display: 'flex', gap: '8px', overflowX: 'auto', paddingBottom: '2px' }}>
        <button
          style={{
            background: '#1e293b',
            color: '#ffffff',
            border: 'none',
            borderRadius: '10px',
            padding: '7px 14px',
            fontSize: '11.5px',
            fontWeight: '800',
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            flexShrink: 0
          }}
        >
          <Box size={13} color="#fbbf24" />
          <span>Titipan Barang</span>
        </button>

        {onOpenStockCard && (
          <button
            onClick={onOpenStockCard}
            style={{
              background: '#ffffff',
              color: '#475569',
              border: '1px solid #e2e8f0',
              borderRadius: '10px',
              padding: '7px 14px',
              fontSize: '11.5px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              cursor: 'pointer',
              flexShrink: 0
            }}
          >
            <Layers size={13} color="#0284c7" />
            <span>Kartu Stok</span>
          </button>
        )}

        {onOpenPayments && (
          <button
            onClick={onOpenPayments}
            style={{
              background: '#ffffff',
              color: '#475569',
              border: '1px solid #e2e8f0',
              borderRadius: '10px',
              padding: '7px 14px',
              fontSize: '11.5px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              cursor: 'pointer',
              flexShrink: 0
            }}
          >
            <CreditCard size={13} color="#059669" />
            <span>Histori Pembayaran</span>
          </button>
        )}

        {onOpenSales && (
          <button
            onClick={onOpenSales}
            style={{
              background: '#ffffff',
              color: '#475569',
              border: '1px solid #e2e8f0',
              borderRadius: '10px',
              padding: '7px 14px',
              fontSize: '11.5px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              cursor: 'pointer',
              flexShrink: 0
            }}
          >
            <TrendingUp size={13} color="#2563eb" />
            <span>Histori Penjualan</span>
          </button>
        )}
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
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Seluruh supplier</div>
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

      {/* Ringkasan Finansial & Status Pembayaran Titipan */}
      <div style={{
        background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
        borderRadius: '18px',
        padding: '15px 16px',
        color: '#ffffff',
        boxShadow: '0 6px 20px rgba(15, 23, 42, 0.15)',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '7px' }}>
            <div style={{
              width: '28px',
              height: '28px',
              borderRadius: '8px',
              background: 'rgba(245, 158, 11, 0.2)',
              border: '1px solid rgba(245, 158, 11, 0.4)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#fbbf24'
            }}>
              <CreditCard size={15} />
            </div>
            <div>
              <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#f8fafc' }}>
                Status Pembayaran Titipan
              </div>
              <div style={{ fontSize: '10.5px', color: '#94a3b8' }}>
                Akumulasi seluruh nota konsinyasi
              </div>
            </div>
          </div>

          <div style={{
            fontSize: '11px',
            fontWeight: '800',
            padding: '3px 9px',
            borderRadius: '20px',
            background: (kpi.totalSisaPembayaran <= 0 && kpi.totalAmountHpp > 0) ? 'rgba(16, 185, 129, 0.2)' : 'rgba(245, 158, 11, 0.2)',
            color: (kpi.totalSisaPembayaran <= 0 && kpi.totalAmountHpp > 0) ? '#34d399' : '#fbbf24',
            border: (kpi.totalSisaPembayaran <= 0 && kpi.totalAmountHpp > 0) ? '1px solid rgba(16, 185, 129, 0.4)' : '1px solid rgba(245, 158, 11, 0.4)'
          }}>
            {kpi.totalSisaPembayaran <= 0 && kpi.totalAmountHpp > 0
              ? 'Lunas Penuh'
              : kpi.totalAmountPaid > 0
                ? 'Lunas Sebagian'
                : 'Belum Ada Pembayaran'}
          </div>
        </div>

        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(3, 1fr)',
          gap: '8px',
          background: 'rgba(255, 255, 255, 0.05)',
          borderRadius: '12px',
          padding: '10px 8px',
          textAlign: 'center'
        }}>
          <div>
            <div style={{ fontSize: '10px', color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase' }}>Total Nilai</div>
            <div style={{ fontSize: '13px', fontWeight: '900', color: '#f8fafc', marginTop: '2px', fontFamily: 'monospace' }}>
              Rp {Number(kpi.totalAmountHpp || 0).toLocaleString('id-ID')}
            </div>
          </div>
          <div>
            <div style={{ fontSize: '10px', color: '#34d399', fontWeight: '700', textTransform: 'uppercase' }}>Terbayar</div>
            <div style={{ fontSize: '13px', fontWeight: '900', color: '#34d399', marginTop: '2px', fontFamily: 'monospace' }}>
              Rp {Number(kpi.totalAmountPaid || 0).toLocaleString('id-ID')}
            </div>
          </div>
          <div>
            <div style={{ fontSize: '10px', color: '#fbbf24', fontWeight: '700', textTransform: 'uppercase' }}>Sisa Bayar</div>
            <div style={{ fontSize: '13px', fontWeight: '900', color: '#fb7185', marginTop: '2px', fontFamily: 'monospace' }}>
              Rp {Number(kpi.totalSisaPembayaran || 0).toLocaleString('id-ID')}
            </div>
          </div>
        </div>

        {/* Progress Bar Pelunasan */}
        {kpi.totalAmountHpp > 0 && (
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10.5px', color: '#94a3b8', marginBottom: '4px' }}>
              <span>Tingkat Pelunasan</span>
              <span style={{ fontWeight: '800', color: '#34d399' }}>
                {Math.min(100, Math.round(((kpi.totalAmountPaid || 0) / (kpi.totalAmountHpp || 1)) * 100))}%
              </span>
            </div>
            <div style={{ height: '6px', background: 'rgba(255,255,255,0.1)', borderRadius: '3px', overflow: 'hidden' }}>
              <div style={{
                height: '100%',
                width: `${Math.min(100, Math.round(((kpi.totalAmountPaid || 0) / (kpi.totalAmountHpp || 1)) * 100))}%`,
                background: 'linear-gradient(90deg, #10b981, #34d399)',
                borderRadius: '3px',
                transition: 'width 0.4s ease'
              }} />
            </div>
          </div>
        )}
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
            placeholder="Cari no. referensi / supplier..."
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
            background: (dateFrom || dateTo || statusFilter || selectedSupplierId) ? '#fef3c7' : '#ffffff',
            border: (dateFrom || dateTo || statusFilter || selectedSupplierId) ? '1px solid #f59e0b' : '1px solid #cbd5e1',
            color: (dateFrom || dateTo || statusFilter || selectedSupplierId) ? '#b45309' : '#475569',
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

      {/* Quick Pills Filter Status Pembayaran */}
      <div style={{ display: 'flex', gap: '6px', overflowX: 'auto', paddingBottom: '2px' }}>
        {[
          { key: '', label: 'Semua Status', count: consignments.length },
          { key: 'unpaid', label: 'Belum Lunas', count: consignments.filter(c => c.paymentStatus === 'unpaid').length },
          { key: 'partial', label: 'Sebagian', count: consignments.filter(c => c.paymentStatus === 'partial').length },
          { key: 'paid', label: 'Lunas', count: consignments.filter(c => c.paymentStatus === 'paid').length },
        ].map((tab) => {
          const isActive = paymentFilter === tab.key;
          return (
            <button
              key={tab.key}
              onClick={() => setPaymentFilter(tab.key)}
              style={{
                flexShrink: 0,
                border: 'none',
                background: isActive ? '#1e293b' : '#ffffff',
                color: isActive ? '#ffffff' : '#64748b',
                padding: '6px 12px',
                borderRadius: '10px',
                fontSize: '11.5px',
                fontWeight: '700',
                display: 'flex',
                alignItems: 'center',
                gap: '5px',
                boxShadow: isActive ? '0 2px 6px rgba(30, 41, 59, 0.2)' : '0 1px 3px rgba(0,0,0,0.03)',
                cursor: 'pointer',
                transition: 'all 0.15s ease'
              }}
            >
              <span>{tab.label}</span>
              <span style={{
                background: isActive ? 'rgba(255,255,255,0.2)' : '#f1f5f9',
                color: isActive ? '#ffffff' : '#475569',
                fontSize: '10px',
                padding: '1px 6px',
                borderRadius: '6px',
                fontWeight: '800'
              }}>
                {tab.count}
              </span>
            </button>
          );
        })}
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
            Filter Konsinyasi & Rentang Tanggal
          </div>

          {/* Supplier Selector */}
          {suppliersList.length > 0 && (
            <div>
              <label style={{ fontSize: '10.5px', color: '#64748b', display: 'block', marginBottom: '4px' }}>Pilih Supplier</label>
              <select
                value={selectedSupplierId}
                onChange={(e) => setSelectedSupplierId(e.target.value)}
                style={{
                  width: '100%',
                  padding: '7px 10px',
                  borderRadius: '10px',
                  border: '1px solid #cbd5e1',
                  fontSize: '12px',
                  color: '#1e293b',
                  background: '#ffffff'
                }}
              >
                <option value="">-- Semua Supplier --</option>
                {suppliersList.map(s => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </select>
            </div>
          )}

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
            {(dateFrom || dateTo || statusFilter || selectedSupplierId) && (
              <button
                onClick={() => { setDateFrom(''); setDateTo(''); setStatusFilter(''); setSelectedSupplierId(''); }}
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
              Klik tombol "+ Catat Titipan" untuk menerima barang konsinyasi baru dari supplier.
            </div>
            <button
              onClick={() => setFormModalData({})}
              style={{
                marginTop: '14px',
                background: 'linear-gradient(135deg, #f59e0b, #d97706)',
                color: '#ffffff',
                border: 'none',
                borderRadius: '12px',
                padding: '9px 16px',
                fontSize: '12px',
                fontWeight: '800',
                cursor: 'pointer'
              }}
            >
              + Catat Penerimaan Sekarang
            </button>
          </div>
        ) : (() => {
          const displayedConsignments = consignments.filter(c => {
            if (!paymentFilter) return true;
            return c.paymentStatus === paymentFilter;
          });

          if (displayedConsignments.length === 0) {
            return (
              <div style={{
                background: '#ffffff',
                borderRadius: '16px',
                padding: '28px 16px',
                textAlign: 'center',
                border: '1px solid #e2e8f0',
                color: '#64748b',
                fontSize: '12.5px'
              }}>
                Tidak ada nota titipan dengan status pembayaran <strong>{paymentFilter === 'unpaid' ? 'Belum Lunas' : paymentFilter === 'partial' ? 'Sebagian' : 'Lunas'}</strong>.
              </div>
            );
          }

          return displayedConsignments.map((c) => {
            const items = c.items || [];
            const refNum = c.referenceNumber || c.reference_number;
            const supName = c.supplierName || c.supplier_name;
            const totalQty = c.totalQtyReceived || c.total_qty_received || 0;
            const totalSold = c.totalQtySold || c.total_qty_sold || 0;
            const sisaStok = c.sisaStok !== undefined ? c.sisaStok : Math.max(0, totalQty - totalSold);
            const totalHpp = c.totalAmountHpp || c.total_amount_hpp || 0;
            const totalPaid = c.totalAmountPaid !== undefined ? c.totalAmountPaid : 0;
            const sisaPembayaran = c.sisaPembayaran !== undefined ? c.sisaPembayaran : Math.max(0, totalHpp - totalPaid);
            const paymentStatus = c.paymentStatus || (totalPaid >= totalHpp && totalHpp > 0 ? 'paid' : totalPaid > 0 ? 'partial' : 'unpaid');
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

                  <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' }}>
                    <span style={{
                      background: status === 'approved' ? 'rgba(16, 185, 129, 0.12)' : 'rgba(245, 158, 11, 0.12)',
                      color: status === 'approved' ? '#059669' : '#d97706',
                      border: status === 'approved' ? '1px solid rgba(16, 185, 129, 0.3)' : '1px solid rgba(245, 158, 11, 0.3)',
                      fontSize: '10px',
                      fontWeight: '800',
                      padding: '2px 7px',
                      borderRadius: '6px',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '3px'
                    }}>
                      {status === 'approved' ? <CheckCircle2 size={11} /> : <Clock size={11} />}
                      {status === 'approved' ? 'Disetujui' : 'Pending'}
                    </span>

                    {/* Badge Status Pembayaran */}
                    <span style={{
                      background: paymentStatus === 'paid' ? '#ecfdf5' : paymentStatus === 'partial' ? '#fffbeb' : '#fef2f2',
                      color: paymentStatus === 'paid' ? '#059669' : paymentStatus === 'partial' ? '#b45309' : '#dc2626',
                      border: paymentStatus === 'paid' ? '1px solid #a7f3d0' : paymentStatus === 'partial' ? '1px solid #fde68a' : '1px solid #fecaca',
                      fontSize: '10px',
                      fontWeight: '800',
                      padding: '2px 7px',
                      borderRadius: '6px',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '3px'
                    }}>
                      {paymentStatus === 'paid' ? <CheckCircle2 size={11} /> : paymentStatus === 'partial' ? <Clock size={11} /> : <AlertCircle size={11} />}
                      {paymentStatus === 'paid' ? 'Lunas' : paymentStatus === 'partial' ? 'Sebagian' : 'Belum Lunas'}
                    </span>
                  </div>
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
                    <div style={{ fontSize: '9.5px', color: '#854d0e', fontWeight: '700', textTransform: 'uppercase' }}>Total</div>
                    <div style={{ fontSize: '12.5px', fontWeight: '900', color: '#1e293b', fontFamily: 'monospace', marginTop: '2px' }}>
                      Rp {Number(totalHpp).toLocaleString('id-ID')}
                    </div>
                  </div>
                </div>

                {/* Box Detail Pembayaran & Sisa Pelunasan */}
                <div style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '10px 12px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '6px'
                }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '5px', fontSize: '11px', fontWeight: '700', color: '#475569' }}>
                      <CreditCard size={13} color="#f59e0b" />
                      <span>Status Pembayaran:</span>
                    </div>
                    <span style={{
                      fontSize: '11px',
                      fontWeight: '800',
                      color: paymentStatus === 'paid' ? '#059669' : paymentStatus === 'partial' ? '#b45309' : '#dc2626'
                    }}>
                      {c.paymentStatusLabel || (paymentStatus === 'paid' ? 'Lunas' : paymentStatus === 'partial' ? 'Sebagian' : 'Belum Lunas')}
                    </span>
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', paddingTop: '4px', borderTop: '1px dashed #e2e8f0' }}>
                    <div>
                      <div style={{ fontSize: '10px', color: '#64748b' }}>Sudah Terbayar</div>
                      <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#059669', fontFamily: 'monospace' }}>
                        Rp {Number(totalPaid).toLocaleString('id-ID')}
                      </div>
                    </div>
                    <div style={{ textAlign: 'right' }}>
                      <div style={{ fontSize: '10px', color: '#64748b' }}>Sisa Pembayaran</div>
                      <div style={{
                        fontSize: '12.5px',
                        fontWeight: '800',
                        color: sisaPembayaran > 0 ? '#dc2626' : '#059669',
                        fontFamily: 'monospace'
                      }}>
                        Rp {Number(sisaPembayaran).toLocaleString('id-ID')}
                      </div>
                    </div>
                  </div>

                  {totalHpp > 0 && (
                    <div style={{ height: '4px', background: '#e2e8f0', borderRadius: '2px', overflow: 'hidden', marginTop: '2px' }}>
                      <div style={{
                        height: '100%',
                        width: `${Math.min(100, Math.round((totalPaid / totalHpp) * 100))}%`,
                        background: paymentStatus === 'paid' ? '#10b981' : '#f59e0b',
                        transition: 'width 0.3s ease'
                      }} />
                    </div>
                  )}
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
          });
        })()}
      </div>

      {/* Detail Modal */}
      {selectedConsignment && (
        <OwnerTitipanDetailModal
          consignment={selectedConsignment}
          onClose={() => setSelectedConsignment(null)}
          onOpenBarcode={(cons) => setBarcodeModalConsignment(cons)}
        />
      )}

      {/* Barcode Print Modal */}
      {barcodeModalConsignment && (
        <OwnerBarcodeModal
          consignment={barcodeModalConsignment}
          onClose={() => setBarcodeModalConsignment(null)}
        />
      )}

      {/* Form Input Modal (Create/Edit Consignment) */}
      {formModalData && (
        <OwnerTitipanFormModal
          initialData={formModalData?.id ? formModalData : null}
          currentOwnerUser={currentOwnerUser}
          onClose={() => setFormModalData(null)}
          onSaved={handleSaveConsignment}
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
