import React, { useState, useEffect } from 'react';
import { Search, Filter, Calendar, TrendingUp, RefreshCw, ShoppingBag, CheckCircle2, Package } from 'lucide-react';
import { fetchSupplierSalesHistory } from '../../api/supplierConsignmentApi';

export default function SupplierSalesHistoryPage({ currentSupplierUser }) {
  const [sales, setSales] = useState([]);
  const [kpi, setKpi] = useState({
    totalTransaksi: 0,
    totalQtySold: 0,
    totalCostValue: 0
  });
  const [isLoading, setIsLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [showFilterDrawer, setShowFilterDrawer] = useState(false);

  const loadData = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSupplierSalesHistory({
        search,
        date_from: dateFrom,
        date_to: dateTo,
        supplier_id: currentSupplierUser?.supplier_id
      });
      if (res && res.data) {
        setSales(res.data);
        if (res.kpi) setKpi(res.kpi);
      }
    } catch (err) {
      console.warn('Failed to load sales history:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [search, dateFrom, dateTo]);

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title & Refresh */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: 0, letterSpacing: '-0.2px' }}>
              Histori Penjualan
            </h2>
            <span style={{
              background: '#eff6ff',
              color: '#2563eb',
              fontSize: '10px',
              fontWeight: '800',
              padding: '2px 7px',
              borderRadius: '6px'
            }}>
              Order Toko
            </span>
          </div>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '3px 0 0' }}>
            Rincian barang titipan yang laku terjual via marketplace
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

      {/* KPI Cards: Total Qty Terjual & Total Nilai Modal Terjual */}
      <div style={{ display: 'grid', gridTemplateColumns: '1.2fr 1fr', gap: '10px' }}>
        {/* Total Nilai HPP Terjual */}
        <div style={{
          background: 'linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%)',
          borderRadius: '18px',
          padding: '14px 16px',
          color: '#ffffff',
          boxShadow: '0 6px 16px rgba(29, 78, 216, 0.25)',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'space-between'
        }}>
          <div>
            <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#bfdbfe', textTransform: 'uppercase', letterSpacing: '0.4px' }}>
              Hak Bagi Hasil (Modal)
            </div>
            <div style={{ fontSize: '20px', fontWeight: '900', fontFamily: 'monospace', margin: '6px 0 2px' }}>
              Rp {Number(kpi.totalCostValue || 0).toLocaleString('id-ID')}
            </div>
          </div>
          <div style={{ fontSize: '10.5px', color: '#dbeafe', display: 'flex', alignItems: 'center', gap: '4px' }}>
            <TrendingUp size={12} />
            <span>Akumulasi modal barang keluar</span>
          </div>
        </div>

        {/* Total Qty Sold & Total Order */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
          <div style={{
            background: '#ffffff',
            border: '1px solid #e2e8f0',
            borderRadius: '14px',
            padding: '9px 12px',
            boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
          }}>
            <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
              Qty Terjual
            </div>
            <div style={{ fontSize: '15px', fontWeight: '900', color: '#1e293b', marginTop: '2px' }}>
              {Number(kpi.totalQtySold || 0).toLocaleString('id-ID')} PCS
            </div>
          </div>

          <div style={{
            background: '#ffffff',
            border: '1px solid #e2e8f0',
            borderRadius: '14px',
            padding: '9px 12px',
            boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
          }}>
            <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
              Total Transaksi
            </div>
            <div style={{ fontSize: '15px', fontWeight: '900', color: '#2563eb', marginTop: '2px' }}>
              {kpi.totalTransaksi || sales.length} Order
            </div>
          </div>
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
            placeholder="Cari no invoice / nama produk / SKU..."
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
            background: (dateFrom || dateTo) ? '#eff6ff' : '#ffffff',
            border: (dateFrom || dateTo) ? '1px solid #3b82f6' : '1px solid #cbd5e1',
            color: (dateFrom || dateTo) ? '#1d4ed8' : '#475569',
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

      {/* Filter Drawer */}
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
            Filter Rentang Tanggal Penjualan
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
            {(dateFrom || dateTo) && (
              <button
                onClick={() => { setDateFrom(''); setDateTo(''); }}
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

      {/* List Penjualan Terpotong */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
        {isLoading ? (
          <div style={{ textAlign: 'center', padding: '40px', color: '#94a3b8', fontSize: '13px' }}>
            Memuat histori penjualan...
          </div>
        ) : sales.length === 0 ? (
          <div style={{
            background: '#ffffff',
            borderRadius: '20px',
            padding: '36px 16px',
            textAlign: 'center',
            border: '1px solid #e2e8f0',
            color: '#94a3b8'
          }}>
            <ShoppingBag size={36} style={{ margin: '0 auto 10px', opacity: 0.35, color: '#3b82f6' }} />
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#1e293b' }}>Belum Ada Barang Terjual</div>
            <div style={{ fontSize: '11.5px', marginTop: '4px', color: '#64748b' }}>
              Pesanan marketplace yang menggunakan stok titipan Anda akan muncul di sini.
            </div>
          </div>
        ) : (
          sales.map((item, idx) => (
            <div
              key={item.id ? `${item.id}_${idx}` : idx}
              style={{
                background: '#ffffff',
                border: '1px solid #e2e8f0',
                borderRadius: '16px',
                padding: '13px 15px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
                display: 'flex',
                flexDirection: 'column',
                gap: '8px'
              }}
            >
              {/* Header: No Invoice & Waktu */}
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                <div>
                  <div style={{ fontSize: '12px', fontWeight: '800', color: '#2563eb', fontFamily: 'monospace' }}>
                    {item.invoiceNumber}
                  </div>
                  <div style={{ fontSize: '10.5px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '2px' }}>
                    <Calendar size={11} color="#94a3b8" />
                    <span>{item.dateFormatted}</span>
                  </div>
                </div>

                <span style={{
                  background: 'rgba(16, 185, 129, 0.12)',
                  color: '#059669',
                  fontSize: '10px',
                  fontWeight: '800',
                  padding: '2px 7px',
                  borderRadius: '6px'
                }}>
                  Laku Terjual
                </span>
              </div>

              {/* Produk & SKU */}
              <div style={{
                background: '#f8fafc',
                borderRadius: '10px',
                padding: '8px 10px',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}>
                <div style={{ flex: 1, paddingRight: '8px' }}>
                  <div style={{ fontWeight: '700', fontSize: '13px', color: '#1e293b', lineHeight: '1.3' }}>
                    {item.productName}
                  </div>
                  <div style={{ fontSize: '10.5px', color: '#64748b', fontFamily: 'monospace', marginTop: '2px' }}>
                    SKU: {item.sku}
                  </div>
                </div>

                <div style={{ textAlign: 'right' }}>
                  <div style={{ fontSize: '13px', fontWeight: '900', color: '#1e293b' }}>
                    {item.qty} {item.unit}
                  </div>
                  <div style={{ fontSize: '10px', color: '#64748b' }}>
                    @ Rp {Number(item.unitCostPrice).toLocaleString('id-ID')}
                  </div>
                </div>
              </div>

              {/* Footer: Subtotal Modal */}
              <div style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                paddingTop: '6px',
                borderTop: '1px solid #f1f5f9',
                fontSize: '11.5px'
              }}>
                <span style={{ color: '#64748b' }}>Nilai Modal Supplier:</span>
                <span style={{ fontWeight: '900', color: '#047857', fontFamily: 'monospace', fontSize: '13px' }}>
                  Rp {Number(item.subtotalCost || (item.qty * item.unitCostPrice)).toLocaleString('id-ID')}
                </span>
              </div>
            </div>
          ))
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
