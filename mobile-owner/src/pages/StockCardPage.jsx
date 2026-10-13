import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { Search, RefreshCw, Package, X, ChevronRight, Layers, Tag, Building2, Filter, Box, CreditCard, ShoppingBag } from 'lucide-react';
import { fetchSupplierStockCard, fetchConsignmentResources } from '../api/supplierConsignmentApi';

export default function StockCardPage({ 
  currentOwnerUser, 
  onOpenTitipan, 
  onOpenStockCard, 
  onOpenPayments, 
  onOpenSales,
  onBack 
}) {
  const [items, setItems] = useState([]);
  const [summary, setSummary] = useState({
    totalProducts: 0,
    totalRemainingQty: 0,
    totalSoldQty: 0,
    totalRemainingValue: 0,
    totalSoldValue: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedSupplierId, setSelectedSupplierId] = useState('');
  const [suppliersList, setSuppliersList] = useState([]);
  const [selectedProduct, setSelectedProduct] = useState(null);

  // Load suppliers list
  useEffect(() => {
    fetchConsignmentResources()
      .then(res => {
        if (res && res.suppliers) setSuppliersList(res.suppliers);
      })
      .catch(() => {});
  }, []);

  const loadStockCard = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSupplierStockCard({
        search,
        supplier_id: selectedSupplierId || undefined
      });
      if (res && res.data) {
        setItems(res.data);
        if (res.summary) setSummary(res.summary);
      }
    } catch (err) {
      console.warn('Failed to load owner stock card:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadStockCard();
  }, [search, selectedSupplierId]);

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title & Refresh */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#1e293b', margin: 0 }}>
              Kartu Stok Konsinyasi
            </h2>
            <span style={{
              background: '#e0e7ff',
              color: '#4338ca',
              fontSize: '10px',
              fontWeight: '800',
              padding: '2px 7px',
              borderRadius: '6px'
            }}>
              Owner
            </span>
          </div>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '3px 0 0' }}>
            Rekap sisa stok, barang terjual, dan modal barang konsinyasi
          </p>
        </div>

        <button
          onClick={loadStockCard}
          disabled={isLoading}
          style={{
            background: '#ffffff',
            border: '1px solid #cbd5e1',
            borderRadius: '12px',
            width: '36px',
            height: '36px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: '#475569',
            cursor: 'pointer',
            boxShadow: '0 2px 6px rgba(0,0,0,0.03)'
          }}
        >
          <RefreshCw size={15} style={{ animation: isLoading ? 'spin 1s linear infinite' : 'none' }} />
        </button>
      </div>

      {/* Quick Navigation Pills */}
      <div style={{ display: 'flex', gap: '8px', overflowX: 'auto', paddingBottom: '2px' }}>
        {onOpenTitipan && (
          <button
            onClick={onOpenTitipan}
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
            <Box size={13} color="#f59e0b" />
            <span>Titipan</span>
          </button>
        )}

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
          <Layers size={13} color="#38bdf8" />
          <span>Stok</span>
        </button>

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
            <span>Pembayaran</span>
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
            <ShoppingBag size={13} color="#2563eb" />
            <span>Penjualan</span>
          </button>
        )}
      </div>

      {/* KPI 4 Cards: Sisa Stok, Terjual, Total Stok (Rp), Total Terjual (Rp) */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
        {/* 1. Sisa Stok */}
        <div style={{
          background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #a7f3d0'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#047857', textTransform: 'uppercase' }}>
            Sisa Stok
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#065f46', margin: '4px 0' }}>
            {Number(summary.totalRemainingQty || 0).toLocaleString('id-ID')} PCS
          </div>
          <div style={{ fontSize: '10px', color: '#059669' }}>Stok fisik di toko</div>
        </div>

        {/* 2. Terjual */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          borderLeft: '4px solid #3b82f6',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#1d4ed8', textTransform: 'uppercase' }}>
            Terjual
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#1e40af', margin: '4px 0' }}>
            {Number(summary.totalSoldQty || 0).toLocaleString('id-ID')} PCS
          </div>
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Total barang laku</div>
        </div>

        {/* 3. Total Stok (Rp) */}
        <div style={{
          background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #fde68a'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
            Total Stok (Rp)
          </div>
          <div style={{ fontSize: '15px', fontWeight: '900', color: '#92400e', fontFamily: 'monospace', margin: '4px 0' }}>
            Rp {Number(summary.totalRemainingValue || 0).toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '10px', color: '#b45309' }}>Nilai sisa modal stok</div>
        </div>

        {/* 4. Total Terjual (Rp) */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          borderLeft: '4px solid #10b981',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#047857', textTransform: 'uppercase' }}>
            Total Terjual (Rp)
          </div>
          <div style={{ fontSize: '15px', fontWeight: '900', color: '#065f46', fontFamily: 'monospace', margin: '4px 0' }}>
            Rp {Number(summary.totalSoldValue || 0).toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '10px', color: '#94a3b8' }}>Nilai modal barang terjual</div>
        </div>
      </div>

      {/* Filter Supplier (Owner Specific) */}
      {suppliersList.length > 0 && (
        <div style={{
          background: '#ffffff',
          border: '1px solid #cbd5e1',
          borderRadius: '14px',
          padding: '6px 12px',
          display: 'flex',
          alignItems: 'center',
          gap: '8px'
        }}>
          <Building2 size={16} color="#64748b" />
          <select
            value={selectedSupplierId}
            onChange={(e) => setSelectedSupplierId(e.target.value)}
            style={{
              border: 'none',
              outline: 'none',
              fontSize: '12px',
              fontWeight: '600',
              color: '#334155',
              width: '100%',
              background: 'transparent',
              cursor: 'pointer'
            }}
          >
            <option value="">Semua Supplier Titipan</option>
            {suppliersList.map(s => (
              <option key={s.id} value={s.id}>
                {s.supplier_name || s.name}
              </option>
            ))}
          </select>
        </div>
      )}

      {/* Search Input */}
      <div style={{
        background: '#ffffff',
        border: '1px solid #cbd5e1',
        borderRadius: '14px',
        padding: '8px 14px',
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
      }}>
        <Search size={16} color="#94a3b8" />
        <input
          type="text"
          placeholder="Cari nama produk atau SKU..."
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

      {/* Product Stock List */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
        {isLoading ? (
          <div style={{ textAlign: 'center', padding: '40px', color: '#94a3b8', fontSize: '13px' }}>
            Memuat kartu stok konsinyasi...
          </div>
        ) : items.length === 0 ? (
          <div style={{
            background: '#ffffff',
            borderRadius: '18px',
            padding: '36px 16px',
            textAlign: 'center',
            border: '1px solid #e2e8f0',
            color: '#94a3b8'
          }}>
            <Package size={34} style={{ margin: '0 auto 10px', opacity: 0.35 }} />
            <div style={{ fontSize: '14px', fontWeight: '700', color: '#64748b' }}>Belum ada data barang</div>
            <div style={{ fontSize: '11.5px', marginTop: '4px' }}>Data persediaan akan tampil saat ada barang masuk.</div>
          </div>
        ) : (
          items.map((prod, idx) => {
            const sold = (prod.totalSold || 0);
            const remaining = (prod.sisaStok || 0);

            return (
              <div
                key={prod.masterProductId ? `${prod.masterProductId}_${idx}` : idx}
                onClick={() => setSelectedProduct(prod)}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '18px',
                  padding: '14px 16px',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '10px',
                  cursor: 'pointer',
                  transition: 'transform 0.15s ease'
                }}
              >
                {/* Header: Nama Produk & Arrow */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div style={{ flex: 1, paddingRight: '8px' }}>
                    <div style={{ fontWeight: '800', fontSize: '14px', color: '#1e293b', lineHeight: '1.3' }}>
                      {prod.productName}
                    </div>
                    <div style={{ fontSize: '11px', color: '#64748b', fontFamily: 'monospace', fontWeight: '700', marginTop: '3px' }}>
                      SKU: {prod.sku}
                    </div>
                    {prod.supplierName && (
                      <div style={{ fontSize: '10.5px', color: '#6366f1', fontWeight: '700', marginTop: '2px' }}>
                        Supplier: {prod.supplierName}
                      </div>
                    )}
                  </div>
                  <div style={{
                    background: '#f8fafc',
                    border: '1px solid #e2e8f0',
                    borderRadius: '10px',
                    width: '30px',
                    height: '30px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#64748b'
                  }}>
                    <ChevronRight size={16} />
                  </div>
                </div>

                {/* Sisa Stok & Terjual Grid */}
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: '1fr 1fr',
                  gap: '8px',
                  background: '#f8fafc',
                  border: '1px solid #f1f5f9',
                  borderRadius: '12px',
                  padding: '10px 12px'
                }}>
                  <div>
                    <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                      Sisa Stok
                    </div>
                    <div style={{ fontSize: '14px', fontWeight: '900', color: remaining > 0 ? '#059669' : '#dc2626', marginTop: '2px' }}>
                      {remaining} PCS
                    </div>
                  </div>

                  <div>
                    <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                      Terjual
                    </div>
                    <div style={{ fontSize: '14px', fontWeight: '900', color: '#2563eb', marginTop: '2px' }}>
                      {sold} PCS
                    </div>
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Modal Detail Produk */}
      {selectedProduct && typeof document !== 'undefined' && createPortal(
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0, 0, 0, 0.75)',
          backdropFilter: 'blur(6px)',
          WebkitBackdropFilter: 'blur(6px)',
          display: 'flex',
          alignItems: 'flex-end',
          justifyContent: 'center',
          zIndex: 99999,
          animation: 'fadeIn 0.2s ease-out'
        }}>
          <div style={{
            background: '#ffffff',
            width: '100%',
            maxWidth: '480px',
            borderRadius: '24px 24px 0 0',
            boxShadow: '0 -10px 25px rgba(0,0,0,0.2)',
            display: 'flex',
            flexDirection: 'column',
            maxHeight: '90vh',
            animation: 'slideUp 0.25s ease-out',
            overflow: 'hidden'
          }}>
            {/* Header Modal */}
            <div style={{
              background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
              padding: '16px 20px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'flex-start'
            }}>
              <div>
                <span style={{
                  background: 'rgba(245, 158, 11, 0.2)',
                  color: '#fbbf24',
                  fontSize: '10.5px',
                  fontWeight: '800',
                  padding: '2px 8px',
                  borderRadius: '6px',
                  display: 'inline-block',
                  marginBottom: '6px',
                  letterSpacing: '0.5px'
                }}>
                  DETAIL KARTU STOK OWNER
                </span>
                <h3 style={{ fontSize: '16px', fontWeight: '800', color: '#f8fafc', margin: 0, lineHeight: '1.3' }}>
                  {selectedProduct.productName}
                </h3>
                <div style={{ fontSize: '11px', color: '#94a3b8', fontFamily: 'monospace', marginTop: '4px' }}>
                  SKU: {selectedProduct.sku}
                </div>
              </div>

              <button
                onClick={() => setSelectedProduct(null)}
                style={{
                  background: 'rgba(255, 255, 255, 0.1)',
                  border: 'none',
                  borderRadius: '50%',
                  width: '32px',
                  height: '32px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#cbd5e1',
                  cursor: 'pointer'
                }}
              >
                <X size={17} />
              </button>
            </div>

            {/* Modal Body */}
            <div style={{ padding: '20px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '14px' }}>
              {/* Box Harga Modal */}
              <div style={{
                background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
                border: '1px solid #fde68a',
                borderRadius: '16px',
                padding: '14px 16px',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}>
                <div>
                  <div style={{ fontSize: '11px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
                    Harga Modal (HPP Titipan)
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: '900', color: '#92400e', fontFamily: 'monospace', marginTop: '2px' }}>
                    Rp {Number(selectedProduct.costPrice || selectedProduct.avgCostPrice || 0).toLocaleString('id-ID')}
                  </div>
                </div>
                <div style={{
                  background: '#fef08a',
                  color: '#854d0e',
                  fontSize: '11px',
                  fontWeight: '800',
                  padding: '4px 10px',
                  borderRadius: '10px'
                }}>
                  / {selectedProduct.unit || 'PCS'}
                </div>
              </div>

              {/* Rincian Stok & Terjual */}
              <div style={{
                background: '#ffffff',
                border: '1px solid #e2e8f0',
                borderRadius: '16px',
                padding: '14px',
                display: 'flex',
                flexDirection: 'column',
                gap: '12px'
              }}>
                <div style={{ fontSize: '11.5px', fontWeight: '800', color: '#1e293b', textTransform: 'uppercase' }}>
                  Rekap Kuantitas Fisik
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '13px' }}>
                  <span style={{ color: '#64748b' }}>Sisa Stok di Toko:</span>
                  <span style={{ fontWeight: '900', color: (selectedProduct.sisaStok || 0) > 0 ? '#059669' : '#dc2626' }}>
                    {selectedProduct.sisaStok || 0} PCS
                  </span>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '13px' }}>
                  <span style={{ color: '#64748b' }}>Jumlah Terjual:</span>
                  <span style={{ fontWeight: '900', color: '#2563eb' }}>
                    {selectedProduct.totalSold || 0} PCS
                  </span>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '13px', borderTop: '1px solid #f1f5f9', paddingTop: '8px' }}>
                  <span style={{ color: '#64748b' }}>Total Barang Diterima:</span>
                  <span style={{ fontWeight: '800', color: '#1e293b' }}>
                    {selectedProduct.totalReceived || 0} PCS
                  </span>
                </div>
              </div>

              {/* Rincian Nilai Rupiah Modal */}
              <div style={{
                background: '#f8fafc',
                border: '1px solid #e2e8f0',
                borderRadius: '16px',
                padding: '14px',
                display: 'flex',
                flexDirection: 'column',
                gap: '10px'
              }}>
                <div style={{ fontSize: '11.5px', fontWeight: '800', color: '#1e293b', textTransform: 'uppercase' }}>
                  Akumulasi Nilai Modal (HPP)
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '13px' }}>
                  <span style={{ color: '#64748b' }}>Nilai Sisa Stok (Rp):</span>
                  <span style={{ fontWeight: '900', color: '#d97706', fontFamily: 'monospace' }}>
                    Rp {Number(selectedProduct.totalValueRemaining || 0).toLocaleString('id-ID')}
                  </span>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '13px' }}>
                  <span style={{ color: '#64748b' }}>Nilai Terjual (Rp):</span>
                  <span style={{ fontWeight: '900', color: '#059669', fontFamily: 'monospace' }}>
                    Rp {Number(selectedProduct.totalValueSold || 0).toLocaleString('id-ID')}
                  </span>
                </div>
              </div>
            </div>

            {/* Footer Modal */}
            <div style={{
              padding: '14px 20px calc(14px + env(safe-area-inset-bottom, 12px))',
              background: '#ffffff',
              borderTop: '1px solid #e2e8f0',
              display: 'flex',
              justifyContent: 'flex-end'
            }}>
              <button
                onClick={() => setSelectedProduct(null)}
                style={{
                  width: '100%',
                  padding: '11px',
                  borderRadius: '14px',
                  border: 'none',
                  background: 'linear-gradient(135deg, #1e293b, #0f172a)',
                  color: '#ffffff',
                  fontWeight: '700',
                  fontSize: '13px',
                  cursor: 'pointer'
                }}
              >
                Tutup
              </button>
            </div>
          </div>
        </div>,
        document.body
      )}

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        @keyframes slideUp {
          from { transform: translateY(100%); }
          to { transform: translateY(0); }
        }
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
      `}</style>
    </div>
  );
}
