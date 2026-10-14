import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { 
  Search, RefreshCw, Package, X, ChevronRight, Layers, AlertTriangle, 
  ArrowDownLeft, ArrowUpRight, SlidersHorizontal, CheckCircle2, XCircle, 
  Clock, User, BarChart3, ShieldAlert, Sparkles, Box
} from 'lucide-react';
import { fetchFinishedGoodsStockCard, fetchProductStockMovements } from '../api/stockCardApi';

export default function StockCardPage({ onBack }) {
  const [items, setItems] = useState([]);
  const [summary, setSummary] = useState({
    totalProducts: 0,
    totalStock: 0,
    lowStockCount: 0,
    emptyStockCount: 0,
    readyStockCount: 0,
    totalAssetValue: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all'); // 'all', 'low', 'empty', 'ready'

  // Modal Detail Kartu Stok & Riwayat Mutasi
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [movements, setMovements] = useState([]);
  const [isLoadingMovements, setIsLoadingMovements] = useState(false);

  const loadStockData = async () => {
    setIsLoading(true);
    try {
      const res = await fetchFinishedGoodsStockCard({
        search,
        status: statusFilter,
        limit: 100
      });
      if (res && res.success) {
        setItems(res.data || []);
        if (res.summary) setSummary(res.summary);
      }
    } catch (err) {
      console.warn('Failed to load finished goods stock card:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadStockData();
  }, [search, statusFilter]);

  // Buka detail kartu stok produk
  const handleOpenProductCard = async (product) => {
    setSelectedProduct(product);
    setIsLoadingMovements(true);
    setMovements([]);
    try {
      const res = await fetchProductStockMovements(product.id);
      if (res && res.success) {
        setMovements(res.movements || []);
      }
    } catch (err) {
      console.warn('Failed to load movements:', err);
    } finally {
      setIsLoadingMovements(false);
    }
  };

  const handleCloseModal = () => {
    setSelectedProduct(null);
    setMovements([]);
  };

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px', maxWidth: '600px', margin: '0 auto' }}>
      
      {/* ── Header Title & Refresh ── */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: 0, letterSpacing: '-0.3px' }}>
              Kartu Stok Gudang Jadi
            </h2>
            <span style={{
              background: '#dbeafe',
              color: '#1d4ed8',
              fontSize: '10px',
              fontWeight: '800',
              padding: '2px 8px',
              borderRadius: '6px',
              textTransform: 'uppercase'
            }}>
              Monitoring
            </span>
          </div>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '3px 0 0' }}>
            Monitoring stok fisik siap kirim, sisa stok & kartu mutasi produk
          </p>
        </div>

        <button
          onClick={loadStockData}
          disabled={isLoading}
          style={{
            background: '#ffffff',
            border: '1px solid #cbd5e1',
            borderRadius: '12px',
            width: '38px',
            height: '38px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: '#475569',
            cursor: 'pointer',
            boxShadow: '0 2px 6px rgba(0,0,0,0.03)'
          }}
          title="Segarkan Data"
        >
          <RefreshCw size={16} style={{ animation: isLoading ? 'spin 1s linear infinite' : 'none' }} />
        </button>
      </div>

      {/* ── KPI Ringkasan Metrik Gudang Jadi ── */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
        {/* 1. Total Stok Fisik */}
        <div style={{
          background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
          borderRadius: '16px',
          padding: '13px 14px',
          border: '1px solid #bfdbfe'
        }}>
          <div style={{ fontSize: '10px', fontWeight: '800', color: '#1d4ed8', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
            Total Fisik Gudang
          </div>
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#1e3a8a', margin: '4px 0' }}>
            {Number(summary.totalStock || 0).toLocaleString('id-ID')} <span style={{ fontSize: '12px', fontWeight: '700' }}>PCS</span>
          </div>
          <div style={{ fontSize: '10.5px', color: '#2563eb' }}>
            {Number(summary.totalProducts || 0).toLocaleString('id-ID')} Produk / SKU
          </div>
        </div>

        {/* 2. Ready Stock */}
        <div 
          onClick={() => setStatusFilter(statusFilter === 'ready' ? 'all' : 'ready')}
          style={{
            background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
            borderRadius: '16px',
            padding: '13px 14px',
            border: '1px solid #bbf7d0',
            borderLeft: '4px solid #10b981',
            cursor: 'pointer'
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div style={{ fontSize: '10px', fontWeight: '800', color: '#15803d', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
              Stock Ready
            </div>
            <CheckCircle2 size={13} color="#16a34a" />
          </div>
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#166534', margin: '4px 0' }}>
            {Number(summary.readyCount || 0).toLocaleString('id-ID')} <span style={{ fontSize: '12px', fontWeight: '700' }}>SKU</span>
          </div>
          <div style={{ fontSize: '10.5px', color: '#15803d' }}>
            Produk Reguler Siap Jual
          </div>
        </div>

        {/* 3. Pre-Order (PO) */}
        <div 
          onClick={() => setStatusFilter(statusFilter === 'po' ? 'all' : 'po')}
          style={{
            background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
            borderRadius: '16px',
            padding: '13px 14px',
            border: '1px solid #fde68a',
            borderLeft: '4px solid #f59e0b',
            cursor: 'pointer'
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div style={{ fontSize: '10px', fontWeight: '800', color: '#b45309', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
              Pre-Order (PO)
            </div>
            <Clock size={13} color="#d97706" />
          </div>
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#b45309', margin: '4px 0' }}>
            {Number(summary.poCount || 0).toLocaleString('id-ID')} <span style={{ fontSize: '12px', fontWeight: '700' }}>SKU</span>
          </div>
          <div style={{ fontSize: '10.5px', color: '#b45309' }}>
            Produk Custom / PO
          </div>
        </div>

        {/* 4. Nilai Aset Stok */}
        <div style={{
          background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
          borderRadius: '16px',
          padding: '13px 14px',
          border: '1px solid #cbd5e1'
        }}>
          <div style={{ fontSize: '10px', fontWeight: '800', color: '#475569', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
            Nilai Aset Stok (HPP)
          </div>
          <div style={{ fontSize: '15px', fontWeight: '900', color: '#0f172a', fontFamily: 'monospace', margin: '4px 0' }}>
            Rp {Number(summary.totalAssetValue || 0).toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '10.5px', color: '#64748b' }}>
            Estimasi modal persediaan
          </div>
        </div>
      </div>

      {/* ── Search Bar ── */}
      <div style={{
        position: 'relative',
        display: 'flex',
        alignItems: 'center',
        background: '#ffffff',
        borderRadius: '14px',
        border: '1px solid #cbd5e1',
        boxShadow: '0 2px 6px rgba(0,0,0,0.02)',
        overflow: 'hidden'
      }}>
        <div style={{ paddingLeft: '14px', display: 'flex', alignItems: 'center' }}>
          <Search size={16} color="#64748b" />
        </div>
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Cari nama barang, SKU, atau barcode..."
          style={{
            width: '100%',
            padding: '11px 12px',
            border: 'none',
            outline: 'none',
            fontSize: '12.5px',
            color: '#1e293b',
            background: 'transparent'
          }}
        />
        {search && (
          <button
            onClick={() => setSearch('')}
            style={{
              background: 'none',
              border: 'none',
              paddingRight: '12px',
              cursor: 'pointer',
              color: '#94a3b8',
              display: 'flex',
              alignItems: 'center'
            }}
          >
            <X size={15} />
          </button>
        )}
      </div>

      {/* ── Filter Tabs ── */}
      <div style={{ display: 'flex', gap: '6px', overflowX: 'auto', paddingBottom: '2px' }}>
        {[
          { key: 'all', label: 'Semua Stok' },
          { key: 'ready', label: `Stock Ready (${summary.readyCount || 0})` },
          { key: 'po', label: `Pre-Order / PO (${summary.poCount || 0})` },
          { key: 'low', label: `Menipis (<10) ${summary.lowStockCount > 0 ? `(${summary.lowStockCount})` : ''}` },
          { key: 'empty', label: `Stok 0 ${summary.emptyStockCount > 0 ? `(${summary.emptyStockCount})` : ''}` }
        ].map((tab) => {
          const isActive = statusFilter === tab.key;
          return (
            <button
              key={tab.key}
              onClick={() => setStatusFilter(tab.key)}
              style={{
                background: isActive ? '#0f172a' : '#ffffff',
                color: isActive ? '#ffffff' : '#475569',
                border: isActive ? 'none' : '1px solid #e2e8f0',
                borderRadius: '10px',
                padding: '7px 13px',
                fontSize: '11.5px',
                fontWeight: isActive ? '800' : '600',
                cursor: 'pointer',
                flexShrink: 0,
                transition: 'all 0.15s ease'
              }}
            >
              {tab.label}
            </button>
          );
        })}
      </div>

      {/* ── Daftar Produk Gudang Jadi ── */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
        {isLoading ? (
          <div style={{ padding: '40px 0', textAlign: 'center', color: '#64748b' }}>
            <RefreshCw size={24} style={{ animation: 'spin 1s linear infinite', margin: '0 auto 10px', display: 'block', color: '#3b82f6' }} />
            <span style={{ fontSize: '12px', fontWeight: '600' }}>Memuat data stok gudang jadi...</span>
          </div>
        ) : items.length === 0 ? (
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            padding: '36px 20px',
            textAlign: 'center',
            border: '1px dashed #cbd5e1'
          }}>
            <Package size={36} color="#94a3b8" style={{ margin: '0 auto 10px' }} />
            <h4 style={{ fontSize: '14px', fontWeight: '700', color: '#1e293b', margin: '0 0 4px' }}>
              Tidak Ada Produk Ditemukan
            </h4>
            <p style={{ fontSize: '11.5px', color: '#64748b', margin: 0 }}>
              {search ? 'Coba ubah kata kunci pencarian SKU atau nama barang' : 'Belum ada produk di kategori filter ini'}
            </p>
          </div>
        ) : (
          items.map((prod) => {
            const isLow = prod.stock > 0 && prod.stock < 10;
            const isEmpty = prod.stock <= 0;
            
            return (
              <div
                key={prod.id}
                onClick={() => handleOpenProductCard(prod)}
                style={{
                  background: '#ffffff',
                  borderRadius: '16px',
                  padding: '12px 14px',
                  border: isLow ? '1px solid #fde68a' : isEmpty ? '1px solid #fecaca' : '1px solid #e2e8f0',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.02)',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '12px',
                  cursor: 'pointer',
                  transition: 'transform 0.1s ease',
                  position: 'relative'
                }}
              >
                {/* Thumbnail Gambar Produk */}
                <div style={{
                  width: '46px',
                  height: '46px',
                  borderRadius: '12px',
                  background: '#f1f5f9',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  flexShrink: 0,
                  overflow: 'hidden',
                  border: '1px solid #e2e8f0'
                }}>
                  {prod.imageUrl ? (
                    <img src={prod.imageUrl} alt={prod.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    <Package size={22} color="#94a3b8" />
                  )}
                </div>

                {/* Info Produk */}
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '2px' }}>
                    <span style={{
                      background: '#f1f5f9',
                      color: '#334155',
                      fontSize: '9.5px',
                      fontWeight: '800',
                      fontFamily: 'monospace',
                      padding: '1.5px 6px',
                      borderRadius: '4px'
                    }}>
                      {prod.sku}
                    </span>
                    {prod.isPreorder ? (
                      <span style={{
                        background: '#fef3c7',
                        color: '#92400e',
                        border: '1px solid #fde68a',
                        fontSize: '9px',
                        fontWeight: '800',
                        padding: '1px 5px',
                        borderRadius: '4px'
                      }}>
                        PO
                      </span>
                    ) : (
                      <span style={{
                        background: '#dcfce7',
                        color: '#15803d',
                        border: '1px solid #bbf7d0',
                        fontSize: '9px',
                        fontWeight: '800',
                        padding: '1px 5px',
                        borderRadius: '4px'
                      }}>
                        READY
                      </span>
                    )}
                    {prod.barcode && (
                      <span style={{ fontSize: '9.5px', color: '#94a3b8' }}>
                        #{prod.barcode}
                      </span>
                    )}
                  </div>

                  <h4 style={{
                    fontSize: '12.5px',
                    fontWeight: '700',
                    color: '#0f172a',
                    margin: '0 0 3px',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis'
                  }}>
                    {prod.name}
                  </h4>

                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '11px', color: '#64748b' }}>
                    <span>HPP: <strong>Rp {Number(prod.costPrice || 0).toLocaleString('id-ID')}</strong></span>
                    <span>·</span>
                    <span>Nilai: <strong style={{ color: '#0f172a' }}>Rp {Number(prod.assetValue || 0).toLocaleString('id-ID')}</strong></span>
                  </div>
                </div>

                {/* Sisa Stok Badge */}
                <div style={{ textAlign: 'right', flexShrink: 0, display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '2px' }}>
                  <span style={{
                    background: isEmpty ? '#fef2f2' : isLow ? '#fffbeb' : '#ecfdf5',
                    color: isEmpty ? '#dc2626' : isLow ? '#b45309' : '#047857',
                    border: isEmpty ? '1px solid #fecaca' : isLow ? '1px solid #fde68a' : '1px solid #a7f3d0',
                    fontSize: '12px',
                    fontWeight: '900',
                    padding: '3px 8px',
                    borderRadius: '8px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}>
                    {isEmpty ? '0' : Number(prod.stock).toLocaleString('id-ID')} {prod.unit || 'PCS'}
                  </span>
                  
                  <span style={{ fontSize: '9.5px', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '2px' }}>
                    Kartu Stok <ChevronRight size={11} />
                  </span>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* ── Modal Pop-up Kartu Stok & Riwayat Mutasi Barang Jadi ── */}
      {selectedProduct && createPortal(
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(3px)',
          display: 'flex',
          alignItems: 'flex-end',
          justifyContent: 'center',
          zIndex: 9999
        }}>
          <div style={{
            background: '#ffffff',
            width: '100%',
            maxWidth: '560px',
            maxHeight: '90vh',
            borderRadius: '24px 24px 0 0',
            overflow: 'hidden',
            display: 'flex',
            flexDirection: 'column',
            boxShadow: '0 -8px 30px rgba(0,0,0,0.15)',
            animation: 'slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1)'
          }}>
            {/* Header Modal */}
            <div style={{
              padding: '16px 18px',
              borderBottom: '1px solid #e2e8f0',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              background: '#f8fafc'
            }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <span style={{
                    background: '#e0e7ff',
                    color: '#4338ca',
                    fontSize: '10px',
                    fontWeight: '800',
                    padding: '1.5px 6px',
                    borderRadius: '4px'
                  }}>
                    BIN CARD / KARTU STOK
                  </span>
                  <span style={{ fontSize: '11px', fontWeight: '700', color: '#475569', fontFamily: 'monospace' }}>
                    {selectedProduct.sku}
                  </span>
                </div>
                <h3 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', margin: '4px 0 0' }}>
                  {selectedProduct.name}
                </h3>
              </div>

              <button
                onClick={handleCloseModal}
                style={{
                  background: '#ffffff',
                  border: '1px solid #cbd5e1',
                  borderRadius: '50%',
                  width: '32px',
                  height: '32px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#64748b',
                  cursor: 'pointer'
                }}
              >
                <X size={16} />
              </button>
            </div>

            {/* Quick Summary Product in Modal */}
            <div style={{
              padding: '12px 18px',
              background: '#ffffff',
              borderBottom: '1px solid #f1f5f9',
              display: 'grid',
              gridTemplateColumns: '1fr 1fr 1fr',
              gap: '8px',
              textAlign: 'center'
            }}>
              <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '10px' }}>
                <div style={{ fontSize: '10px', fontWeight: '700', color: '#64748b' }}>SISA STOK</div>
                <div style={{ fontSize: '15px', fontWeight: '900', color: '#0f172a' }}>
                  {Number(selectedProduct.stock).toLocaleString('id-ID')} {selectedProduct.unit || 'PCS'}
                </div>
              </div>

              <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '10px' }}>
                <div style={{ fontSize: '10px', fontWeight: '700', color: '#64748b' }}>HARGA MODAL (HPP)</div>
                <div style={{ fontSize: '13px', fontWeight: '800', color: '#047857' }}>
                  Rp {Number(selectedProduct.costPrice || 0).toLocaleString('id-ID')}
                </div>
              </div>

              <div style={{ padding: '8px', background: '#f8fafc', borderRadius: '10px' }}>
                <div style={{ fontSize: '10px', fontWeight: '700', color: '#64748b' }}>TOTAL ASET</div>
                <div style={{ fontSize: '13px', fontWeight: '800', color: '#1e40af' }}>
                  Rp {Number(selectedProduct.assetValue || (selectedProduct.stock * selectedProduct.costPrice) || 0).toLocaleString('id-ID')}
                </div>
              </div>
            </div>

            {/* Sub-header riwayat mutasi */}
            <div style={{ padding: '10px 18px', background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span style={{ fontSize: '11px', fontWeight: '800', color: '#475569', textTransform: 'uppercase' }}>
                Riwayat Mutasi Keluar & Masuk
              </span>
              <span style={{ fontSize: '10.5px', color: '#64748b' }}>
                {movements.length} Transaksi Terakhir
              </span>
            </div>

            {/* List Riwayat Pergerakan Stok */}
            <div style={{ padding: '10px 18px 24px', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {isLoadingMovements ? (
                <div style={{ padding: '40px 0', textAlign: 'center', color: '#64748b' }}>
                  <RefreshCw size={20} style={{ animation: 'spin 1s linear infinite', margin: '0 auto 8px', display: 'block', color: '#3b82f6' }} />
                  <span style={{ fontSize: '11.5px' }}>Mengambil riwayat kartu stok...</span>
                </div>
              ) : movements.length === 0 ? (
                <div style={{ padding: '30px 0', textAlign: 'center', color: '#94a3b8' }}>
                  <Package size={28} style={{ margin: '0 auto 6px', display: 'block', opacity: 0.5 }} />
                  <div style={{ fontSize: '12px', fontWeight: '600' }}>Belum ada catatan mutasi stok</div>
                  <p style={{ fontSize: '10.5px', margin: '2px 0 0' }}>Perubahan stok akan otomatis tercatat di kartu stok ini</p>
                </div>
              ) : (
                movements.map((mov) => {
                  const isOut = mov.type === 'out';
                  const isIn = mov.type === 'in';
                  const isAdj = mov.type === 'adj';

                  return (
                    <div
                      key={mov.id}
                      style={{
                        padding: '10px 12px',
                        background: '#ffffff',
                        border: '1px solid #e2e8f0',
                        borderRadius: '12px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: '10px'
                      }}
                    >
                      {/* Badge Tipe Mutasi */}
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0 }}>
                        <div style={{
                          width: '32px',
                          height: '32px',
                          borderRadius: '8px',
                          background: isOut ? '#fef2f2' : isIn ? '#ecfdf5' : '#f0f9ff',
                          color: isOut ? '#ef4444' : isIn ? '#10b981' : '#0284c7',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          flexShrink: 0
                        }}>
                          {isOut ? <ArrowUpRight size={16} /> : isIn ? <ArrowDownLeft size={16} /> : <SlidersHorizontal size={14} />}
                        </div>

                        <div style={{ minWidth: 0 }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                            <span style={{
                              fontSize: '9.5px',
                              fontWeight: '800',
                              color: isOut ? '#dc2626' : isIn ? '#047857' : '#0284c7',
                              textTransform: 'uppercase'
                            }}>
                              {isOut ? 'BARANG KELUAR' : isIn ? 'BARANG MASUK' : 'PENYESUAIAN (OPNAME)'}
                            </span>
                            <span style={{ fontSize: '9px', color: '#94a3b8' }}>·</span>
                            <span style={{ fontSize: '9.5px', color: '#64748b' }}>
                              {mov.createdAt ? new Date(mov.createdAt).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) : '-'}
                            </span>
                          </div>

                          <div style={{
                            fontSize: '11px',
                            fontWeight: '600',
                            color: '#1e293b',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            marginTop: '2px'
                          }}>
                            {mov.reference}
                          </div>

                          {mov.userName && (
                            <div style={{ fontSize: '9.5px', color: '#94a3b8', marginTop: '1px' }}>
                              PIC: {mov.userName}
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Qty & Balance After */}
                      <div style={{ textAlign: 'right', flexShrink: 0 }}>
                        <div style={{
                          fontSize: '13px',
                          fontWeight: '800',
                          color: isOut ? '#dc2626' : isIn ? '#15803d' : '#0284c7'
                        }}>
                          {mov.quantity > 0 ? `+${mov.quantity}` : mov.quantity}
                        </div>
                        <div style={{ fontSize: '9.5px', color: '#64748b', marginTop: '1px' }}>
                          Sisa: <strong>{mov.balanceAfter}</strong>
                        </div>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </div>,
        document.body
      )}

      {/* Animation Styles */}
      <style>{`
        @keyframes slideUp {
          from { transform: translateY(100%); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
        }
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
