import React, { useState, useEffect } from 'react';
import { X, DollarSign, Percent, RotateCcw, Box, TrendingUp, Search, Store, Clock, ChevronRight } from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../../utils/formatters';
import { fetchOrders } from '../../api/ordersApi';

export default function FinanceMetricDetailModal({ metricType, metrics, onClose, onSelectOrder }) {
  if (!metricType || !metrics) return null;

  const [loading, setLoading] = useState(true);
  const [ordersList, setOrdersList] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');

  const omset = metrics?.todayOmset ?? 0;
  const biayaAdmin = metrics?.todayMarketplaceFee ?? 0;
  const refund = metrics?.todayRefund ?? 0;
  const hpp = metrics?.todayHpp ?? 0;
  const margin = metrics?.todayMargin ?? 0;

  // Konfigurasi Header Tiap Tab
  const configs = {
    omset: {
      title: 'Detail Penjualan Hari Ini',
      subtitle: 'Daftar transaksi yang selesai & dilepas hari ini',
      value: formatRupiah(omset),
      icon: DollarSign,
      gradient: 'linear-gradient(135deg, #10b981, #059669)',
      textColor: '#059669',
      filterStatus: 'COMPLETED',
      columnTitle: 'Nilai Penjualan',
      getValue: (o) => formatRupiah(o.totalAmount || o.releasedValue || 0)
    },
    admin: {
      title: 'Detail Biaya Admin Marketplace',
      subtitle: 'Daftar potongan fee resmi marketplace per order',
      value: formatRupiah(biayaAdmin),
      icon: Percent,
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)',
      textColor: '#d97706',
      filterStatus: 'COMPLETED',
      columnTitle: 'Biaya Admin',
      getValue: (o) => formatRupiah(o.marketplaceFee || 0)
    },
    refund: {
      title: 'Detail Refund & Retur',
      subtitle: 'Daftar pesanan retur atau dibatalkan hari ini',
      value: formatRupiah(refund),
      icon: RotateCcw,
      gradient: 'linear-gradient(135deg, #ef4444, #dc2626)',
      textColor: '#dc2626',
      filterStatus: 'RETURNED',
      columnTitle: 'Nilai Refund',
      getValue: (o) => formatRupiah(o.refundAmount || o.totalAmount || 0)
    },
    hpp: {
      title: 'Detail HPP (Harga Pokok Penjualan)',
      subtitle: 'Rincian modal pokok stok barang per transaksi',
      value: formatRupiah(hpp),
      icon: Box,
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)',
      textColor: '#4f46e5',
      filterStatus: 'COMPLETED',
      columnTitle: 'Modal HPP',
      getValue: (o) => formatRupiah(o.hppModal || 0)
    },
    margin: {
      title: 'Detail Margin (Laba Bersih)',
      subtitle: 'Rincian laba bersih per transaksi dilepas hari ini',
      value: formatRupiah(margin),
      icon: TrendingUp,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)',
      textColor: '#0284c7',
      filterStatus: 'COMPLETED',
      columnTitle: 'Laba Margin',
      getValue: (o) => formatRupiah(o.margin || 0)
    }
  };

  const current = configs[metricType] || configs.omset;
  const IconComponent = current.icon;

  // Ambil transaksi riil hari ini langsung dari API
  useEffect(() => {
    let isMounted = true;
    async function loadTodayOrders() {
      setLoading(true);
      try {
        const res = await fetchOrders({
          status: current.filterStatus,
          todayOnly: true,
          limit: 50
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
    loadTodayOrders();
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
        style={{ maxHeight: '90vh', display: 'flex', flexDirection: 'column', padding: '16px 16px 24px' }}
      >
        <div className="sheet-handle"></div>

        {/* Modal Header */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
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

        {/* Total Summary Strip */}
        <div style={{
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          borderRadius: '16px',
          padding: '12px 14px',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '12px'
        }}>
          <div>
            <span style={{ fontSize: '10.5px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
              Total Akumulasi
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
          marginBottom: '12px'
        }}>
          <Search size={15} color="#94a3b8" />
          <input 
            type="text"
            placeholder="Cari invoice, toko, atau pembeli..."
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

        {/* Daftar Transaksi Riil */}
        <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '8px', paddingRight: '2px' }}>
          {loading ? (
            <div style={{ textAlign: 'center', padding: '30px', color: '#64748b', fontSize: '12px' }}>
              Memuat transaksi riil database...
            </div>
          ) : filtered.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
              Tidak ada data transaksi yang cocok untuk kategori ini.
            </div>
          ) : (
            filtered.map((order) => {
              const badge = getChannelBadge(order.channel || order.storeName);
              return (
                <div 
                  key={order.id} 
                  onClick={() => {
                    if (onSelectOrder) {
                      onClose();
                      onSelectOrder(order);
                    }
                  }}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #e2e8f0',
                    borderRadius: '14px',
                    padding: '11px 13px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '6px',
                    cursor: 'pointer',
                    boxShadow: '0 2px 4px rgba(0,0,0,0.02)'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <span style={{ fontSize: '12px', fontWeight: '800', fontFamily: 'monospace', color: '#0f172a' }}>
                        {order.invoiceNumber}
                      </span>
                      <span className={`store-pill ${badge.class}`} style={{ fontSize: '9.5px', padding: '1px 6px' }}>
                        {order.storeName}
                      </span>
                    </div>
                    <div style={{ fontSize: '13px', fontWeight: '800', color: current.textColor }}>
                      {current.getValue(order)}
                    </div>
                  </div>

                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11px', color: '#64748b' }}>
                    <span>{order.buyerName} ({order.quantity} pcs)</span>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <Clock size={11} color="#94a3b8" />
                      <span>{order.completedAt}</span>
                      <ChevronRight size={13} color="#94a3b8" />
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
