import React, { useState, useEffect } from 'react';
import { X, DollarSign, Percent, RotateCcw, Box, TrendingUp, Search, Clock, ChevronRight } from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../../utils/formatters';
import { fetchOrders } from '../../api/ordersApi';

export default function FinanceMetricDetailModal({ metricType, metrics, onClose, onSelectOrder }) {
  if (!metricType || !metrics) return null;

  const [loading, setLoading] = useState(true);
  const [ordersList, setOrdersList] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');

  const omset = metrics?.todayOmset ?? metrics?.monthOmset ?? 0;
  const biayaAdmin = metrics?.todayMarketplaceFee ?? metrics?.monthMarketplaceFee ?? 0;
  const refund = metrics?.todayRefund ?? metrics?.monthRefund ?? 0;
  const hpp = metrics?.todayHpp ?? metrics?.monthHpp ?? 0;
  const margin = metrics?.todayMargin ?? metrics?.monthMargin ?? 0;

  // Header configs per metric
  const configs = {
    omset: {
      title: 'Detail Penjualan Bulan Ini',
      subtitle: 'Daftar transaksi yang selesai & dilepas bulan ini',
      value: formatRupiah(omset),
      icon: DollarSign,
      gradient: 'linear-gradient(135deg, #10b981, #059669)',
      textColor: '#059669',
      filterStatus: 'COMPLETED'
    },
    admin: {
      title: 'Detail Biaya Admin Bulan Ini',
      subtitle: 'Daftar transaksi dan potongan fee platform bulan ini',
      value: formatRupiah(biayaAdmin),
      icon: Percent,
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)',
      textColor: '#d97706',
      filterStatus: 'COMPLETED'
    },
    refund: {
      title: 'Detail Refund & Retur Bulan Ini',
      subtitle: 'Daftar pesanan retur atau dibatalkan bulan ini',
      value: formatRupiah(refund),
      icon: RotateCcw,
      gradient: 'linear-gradient(135deg, #ef4444, #dc2626)',
      textColor: '#dc2626',
      filterStatus: 'RETURNED'
    },
    hpp: {
      title: 'Detail HPP Bulan Ini',
      subtitle: 'Daftar modal pokok inventori per transaksi bulan ini',
      value: formatRupiah(hpp),
      icon: Box,
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)',
      textColor: '#4f46e5',
      filterStatus: 'COMPLETED'
    },
    margin: {
      title: 'Detail Margin (Laba Bersih) Bulan Ini',
      subtitle: 'Daftar laba bersih dan komisi per transaksi bulan ini',
      value: formatRupiah(margin),
      icon: TrendingUp,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)',
      textColor: '#0284c7',
      filterStatus: 'COMPLETED'
    }
  };

  const current = configs[metricType] || configs.omset;
  const IconComponent = current.icon;

  // Ambil transaksi riil bulan ini dari API backend
  useEffect(() => {
    let isMounted = true;
    async function loadMonthOrders() {
      setLoading(true);
      try {
        const res = await fetchOrders({
          status: current.filterStatus,
          todayOnly: true, // Param ini di backend sekarang menyaring bulan ini (>= startOfMonth)
          limit: 100
        });
        if (isMounted && res?.data) {
          setOrdersList(res.data);
        }
      } catch (err) {
        console.warn('Gagal memuat transaksi detail:', err);
      } finally {
        if (isMounted) setLoading(false);
      }
    }
    loadMonthOrders();
    return () => { isMounted = false; };
  }, [metricType]);

  const filtered = ordersList.filter(o => {
    if (!searchTerm) return true;
    const q = searchTerm.toLowerCase();
    return (
      (o.invoiceNumber && o.invoiceNumber.toLowerCase().includes(q)) ||
      (o.storeName && o.storeName.toLowerCase().includes(q)) ||
      (o.buyerName && o.buyerName.toLowerCase().includes(q))
    );
  });

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div 
        className="bottom-sheet-card" 
        onClick={(e) => e.stopPropagation()}
        style={{ maxHeight: '92vh', display: 'flex', flexDirection: 'column', padding: '16px 16px 24px' }}
      >
        <div className="sheet-handle"></div>

        {/* Modal Top Bar */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{
              width: '38px',
              height: '38px',
              borderRadius: '12px',
              background: current.gradient,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#ffffff',
              boxShadow: '0 4px 10px rgba(0,0,0,0.12)'
            }}>
              <IconComponent size={20} strokeWidth={2.4} />
            </div>
            <div>
              <h3 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                {current.title}
              </h3>
              <p style={{ fontSize: '11px', color: '#64748b', margin: '1px 0 0' }}>
                {current.subtitle}
              </p>
            </div>
          </div>

          <button 
            type="button" 
            onClick={onClose}
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
            <X size={18} color="#475569" />
          </button>
        </div>

        {/* Grand Total Strip */}
        <div style={{
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          borderRadius: '16px',
          padding: '12px 14px',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '10px'
        }}>
          <div>
            <span style={{ fontSize: '10.5px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
              Total Akumulasi Bulan Ini
            </span>
            <div style={{ fontSize: '18px', fontWeight: '800', color: current.textColor }}>
              {current.value}
            </div>
          </div>
          <span style={{ 
            fontSize: '11px', 
            background: '#ffffff', 
            padding: '4px 10px', 
            borderRadius: '999px', 
            border: '1px solid #e2e8f0', 
            fontWeight: '700', 
            color: '#334155' 
          }}>
            {ordersList.length} Transaksi
          </span>
        </div>

        {/* Search Field */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          background: '#ffffff',
          border: '1px solid #e2e8f0',
          borderRadius: '12px',
          padding: '8px 12px',
          marginBottom: '10px'
        }}>
          <Search size={15} color="#94a3b8" />
          <input 
            type="text"
            placeholder="Cari no invoice, toko, atau nama pembeli..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{
              border: 'none',
              outline: 'none',
              background: 'transparent',
              width: '100%',
              fontSize: '12px',
              fontFamily: 'inherit'
            }}
          />
        </div>

        {/* Daftar Transaksi Riil dengan 4 Komponen Lengkap: Penjualan, HPP, Admin, Margin */}
        <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '10px', paddingRight: '2px' }}>
          {loading ? (
            <div style={{ textAlign: 'center', padding: '30px', color: '#64748b', fontSize: '12px' }}>
              Memuat transaksi riil bulan ini...
            </div>
          ) : filtered.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
              Tidak ada data transaksi yang cocok untuk bulan ini.
            </div>
          ) : (
            filtered.map((order) => {
              const badge = getChannelBadge(order.channel || order.storeName);
              const orderPenjualan = order.totalAmount || order.releasedValue || 0;
              const orderHpp = order.hppModal || 0;
              const orderAdmin = order.marketplaceFee || 0;
              const orderMargin = order.margin || 0;

              return (
                <div 
                  key={order.id} 
                  onClick={() => {
                    if (onSelectOrder) {
                      onSelectOrder(order);
                    }
                  }}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #e2e8f0',
                    borderRadius: '16px',
                    padding: '12px 14px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '8px',
                    cursor: 'pointer',
                    boxShadow: '0 2px 5px rgba(0,0,0,0.03)',
                    transition: 'transform 0.15s ease'
                  }}
                >
                  {/* Row 1: Invoice, Store & Jam */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <span style={{ fontSize: '12.5px', fontWeight: '800', fontFamily: 'monospace', color: '#0f172a' }}>
                        {order.invoiceNumber}
                      </span>
                      <span className={`store-pill ${badge.class}`} style={{ fontSize: '9.5px', padding: '1.5px 6px' }}>
                        {order.storeName}
                      </span>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '10.5px', color: '#64748b' }}>
                      <Clock size={11} color="#94a3b8" />
                      <span>{order.completedAt}</span>
                      <ChevronRight size={13} color="#94a3b8" />
                    </div>
                  </div>

                  {/* Row 2: Pembeli & Qty */}
                  <div style={{ fontSize: '11px', color: '#64748b' }}>
                    Pembeli: <strong style={{ color: '#0f172a' }}>{order.buyerName}</strong> • {order.quantity} pcs
                  </div>

                  {/* Row 3: Grid 4 Komponen Finansial Lengkap (Penjualan, HPP, Admin, Margin) */}
                  <div style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(4, 1fr)',
                    gap: '6px',
                    background: '#f8fafc',
                    padding: '8px 10px',
                    borderRadius: '12px',
                    border: '1px solid #f1f5f9'
                  }}>
                    <div>
                      <span style={{ fontSize: '9.5px', color: '#64748b', display: 'block' }}>Penjualan</span>
                      <span style={{ fontSize: '11.5px', fontWeight: '800', color: '#059669' }}>
                        {formatRupiah(orderPenjualan)}
                      </span>
                    </div>

                    <div>
                      <span style={{ fontSize: '9.5px', color: '#64748b', display: 'block' }}>HPP</span>
                      <span style={{ fontSize: '11.5px', fontWeight: '800', color: '#4f46e5' }}>
                        {formatRupiah(orderHpp)}
                      </span>
                    </div>

                    <div>
                      <span style={{ fontSize: '9.5px', color: '#64748b', display: 'block' }}>Admin Fee</span>
                      <span style={{ fontSize: '11.5px', fontWeight: '800', color: '#d97706' }}>
                        {formatRupiah(orderAdmin)}
                      </span>
                    </div>

                    <div style={{ textAlign: 'right' }}>
                      <span style={{ fontSize: '9.5px', color: '#64748b', display: 'block' }}>Margin</span>
                      <span style={{ fontSize: '11.5px', fontWeight: '800', color: '#0284c7' }}>
                        {formatRupiah(orderMargin)}
                      </span>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </div>
    </div>
  );
}
