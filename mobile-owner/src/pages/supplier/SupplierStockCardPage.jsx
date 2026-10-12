import React, { useState, useEffect } from 'react';
import { Layers, Search, RefreshCw, Box, AlertCircle, TrendingUp, DollarSign, Package } from 'lucide-react';
import { fetchSupplierStockCard } from '../../api/supplierConsignmentApi';

export default function SupplierStockCardPage({ currentSupplierUser }) {
  const [items, setItems] = useState([]);
  const [summary, setSummary] = useState({
    totalProducts: 0,
    totalRemainingQty: 0,
    totalSoldQty: 0,
    totalRemainingValue: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  const loadStockCard = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSupplierStockCard({
        search,
        supplier_id: currentSupplierUser?.supplier_id
      });
      if (res && res.data) {
        setItems(res.data);
        if (res.summary) setSummary(res.summary);
      }
    } catch (err) {
      console.warn('Failed to load stock card:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadStockCard();
  }, [search]);

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title & Refresh */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h2 style={{ fontSize: '17px', fontWeight: '800', color: '#1e293b', margin: 0 }}>
            Kartu Stok Persediaan
          </h2>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '2px 0 0' }}>
            Rekap sisa barang titipan, penjualan & nilai aset di gudang
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

      {/* KPI 4 Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
        {/* Total Produk */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase' }}>
            Produk Titipan
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#1e293b', margin: '4px 0' }}>
            {summary.totalProducts || items.length} SKU
          </div>
          <div style={{ fontSize: '10.5px', color: '#94a3b8' }}>Variasi barang aktif</div>
        </div>

        {/* Sisa Stok */}
        <div style={{
          background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #a7f3d0'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#047857', textTransform: 'uppercase' }}>
            Sisa Stok Gudang
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#065f46', margin: '4px 0' }}>
            {Number(summary.totalRemainingQty || 0).toLocaleString('id-ID')} PCS
          </div>
          <div style={{ fontSize: '10.5px', color: '#059669' }}>Tersedia di etalase</div>
        </div>

        {/* Terjual */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase' }}>
            Total Terjual
          </div>
          <div style={{ fontSize: '18px', fontWeight: '900', color: '#3b82f6', margin: '4px 0' }}>
            {Number(summary.totalSoldQty || 0).toLocaleString('id-ID')} PCS
          </div>
          <div style={{ fontSize: '10.5px', color: '#94a3b8' }}>Keluar via marketplace</div>
        </div>

        {/* Nilai Sisa Aset */}
        <div style={{
          background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #fde68a'
        }}>
          <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#b45309', textTransform: 'uppercase' }}>
            Nilai Sisa Aset
          </div>
          <div style={{ fontSize: '15.5px', fontWeight: '900', color: '#92400e', fontFamily: 'monospace', margin: '4px 0' }}>
            Rp {Number(summary.totalRemainingValue || 0).toLocaleString('id-ID')}
          </div>
          <div style={{ fontSize: '10.5px', color: '#b45309' }}>Estimasi modal sisa</div>
        </div>
      </div>

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
          placeholder="Cari nama barang atau SKU..."
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
            Memuat kartu stok titipan...
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
            <div style={{ fontSize: '14px', fontWeight: '700', color: '#64748b' }}>Belum ada data barang titipan</div>
            <div style={{ fontSize: '11.5px', marginTop: '4px' }}>Catat penerimaan titipan baru di menu Titipan Barang.</div>
          </div>
        ) : (
          items.map((prod, idx) => {
            const total = (prod.totalReceived || 0);
            const sold = (prod.totalSold || 0);
            const remaining = (prod.sisaStok || 0);
            const soldPercent = total > 0 ? Math.min(100, Math.round((sold / total) * 100)) : 0;

            return (
              <div
                key={prod.masterProductId ? `${prod.masterProductId}_${idx}` : idx}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '18px',
                  padding: '14px 16px',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '8px'
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <div style={{ fontWeight: '800', fontSize: '13.5px', color: '#1e293b' }}>
                      {prod.productName}
                    </div>
                    <div style={{ fontSize: '11px', color: '#64748b', fontFamily: 'monospace', fontWeight: '700' }}>
                      SKU: {prod.sku} • {prod.supplierName}
                    </div>
                  </div>
                  <span style={{
                    background: remaining > 0 ? '#ecfdf5' : '#fee2e2',
                    color: remaining > 0 ? '#059669' : '#dc2626',
                    fontSize: '11px',
                    fontWeight: '800',
                    padding: '3px 8px',
                    borderRadius: '8px'
                  }}>
                    {remaining > 0 ? `${remaining} PCS Ada` : 'Habis'}
                  </span>
                </div>

                {/* Progress Penjualan */}
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b', marginBottom: '3px' }}>
                    <span>Terjual: <strong>{sold}</strong> dari {total} PCS</span>
                    <span><strong>{soldPercent}%</strong></span>
                  </div>
                  <div style={{ width: '100%', height: '6px', background: '#f1f5f9', borderRadius: '4px', overflow: 'hidden' }}>
                    <div style={{
                      width: `${soldPercent}%`,
                      height: '100%',
                      background: 'linear-gradient(90deg, #10b981, #059669)',
                      borderRadius: '4px'
                    }} />
                  </div>
                </div>

                {/* Harga & Nilai Modal Sisa */}
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  background: '#f8fafc',
                  borderRadius: '10px',
                  padding: '8px 12px',
                  fontSize: '11.5px',
                  marginTop: '2px'
                }}>
                  <span style={{ color: '#64748b' }}>
                    HPP: Rp {Number(prod.avgCostPrice || 0).toLocaleString('id-ID')}
                  </span>
                  <span style={{ fontWeight: '800', color: '#1e293b' }}>
                    Nilai Sisa: <strong style={{ color: '#d97706', fontFamily: 'monospace' }}>Rp {Number(prod.totalValueRemaining || 0).toLocaleString('id-ID')}</strong>
                  </span>
                </div>
              </div>
            );
          })
        )}
      </div>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
