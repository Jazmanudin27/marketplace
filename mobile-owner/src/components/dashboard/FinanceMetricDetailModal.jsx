import React from 'react';
import { X, DollarSign, Percent, RotateCcw, Box, TrendingUp, Info, CheckCircle2 } from 'lucide-react';
import { formatRupiah } from '../../utils/formatters';

export default function FinanceMetricDetailModal({ metricType, metrics, onClose, onOpenOrders }) {
  if (!metricType || !metrics) return null;

  const omset = metrics?.todayOmset ?? 0;
  const biayaAdmin = metrics?.todayMarketplaceFee ?? 0;
  const refund = metrics?.todayRefund ?? 0;
  const hpp = metrics?.todayHpp ?? 0;
  const margin = metrics?.todayMargin ?? 0;
  const count = metrics?.ordersCompletedToday ?? 0;

  // Nilai bersih yang dilepas ke rekening/escrow
  const netReleased = Math.max(0, omset - biayaAdmin - refund);

  const configs = {
    omset: {
      title: 'Detail Penjualan Hari Ini',
      subtitle: 'Akumulasi omset bruto pesanan selesai hari ini',
      value: formatRupiah(omset),
      icon: DollarSign,
      gradient: 'linear-gradient(135deg, #10b981, #059669)',
      textColor: '#059669',
      breakdowns: [
        { label: 'Pesanan Selesai Hari Ini', val: `${count} Order` },
        { label: 'Rata-rata Nilai Order', val: count > 0 ? formatRupiah(Math.round(omset / count)) : 'Rp 0' },
        { label: 'Status Data', val: 'Sinkron Database (Completed/Selesai)' }
      ],
      desc: 'Total nilai transaksi bruto dari pembeli sebelum dipotong biaya admin marketplace dan refund.'
    },
    admin: {
      title: 'Detail Biaya Admin Marketplace',
      subtitle: 'Potongan komisi & fee platform (Shopee, TikTok, Tokopedia)',
      value: formatRupiah(biayaAdmin),
      icon: Percent,
      gradient: 'linear-gradient(135deg, #f59e0b, #d97706)',
      textColor: '#d97706',
      breakdowns: [
        { label: 'Persentase Efektif Fee', val: omset > 0 ? `${((biayaAdmin / omset) * 100).toFixed(1)}% dari Omset` : '0%' },
        { label: 'Dasar Pemotongan', val: 'Fee Layanan, Bebas Ongkir & Transaksi' },
        { label: 'Metode Perhitungan', val: 'Otomatis dari settlement resmi channel' }
      ],
      desc: 'Seluruh biaya administrasi yang dipotong langsung oleh sistem marketplace saat dana dilepas.'
    },
    refund: {
      title: 'Detail Refund & Retur',
      subtitle: 'Nilai pesanan dikembalikan atau dibatalkan hari ini',
      value: formatRupiah(refund),
      icon: RotateCcw,
      gradient: 'linear-gradient(135deg, #ef4444, #dc2626)',
      textColor: '#dc2626',
      breakdowns: [
        { label: 'Status Retur', val: `${metrics?.ordersReturned ?? 0} Pesanan Bermasalah` },
        { label: 'Dampak Omset', val: 'Langsung memotong saldo omset bruto' },
        { label: 'Tindakan', val: 'Perlu konfirmasi fisik barang di gudang' }
      ],
      desc: 'Nilai uang pembeli yang dikembalikan akibat komplain pesanan atau pengembalian barang.'
    },
    hpp: {
      title: 'Detail HPP (Harga Pokok Penjualan)',
      subtitle: 'Total modal pokok stok barang yang terjual hari ini',
      value: formatRupiah(hpp),
      icon: Box,
      gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)',
      textColor: '#4f46e5',
      breakdowns: [
        { label: 'Acuan Biaya', val: 'Master Produk (Cost Price / HPP Rata-rata)' },
        { label: 'Rasio HPP thd Omset', val: omset > 0 ? `${((hpp / omset) * 100).toFixed(1)}%` : '0%' },
        { label: 'Pengurangan Stok', val: 'Tercatat otomatis di mutasi stok gudang' }
      ],
      desc: 'Modal pokok inventori dari seluruh produk pesanan yang selesai dan dilepas hari ini.'
    },
    margin: {
      title: 'Detail Margin (Laba Bersih)',
      subtitle: 'Keuntungan bersih murni operasional hari ini',
      value: formatRupiah(margin),
      icon: TrendingUp,
      gradient: 'linear-gradient(135deg, #0ea5e9, #0284c7)',
      textColor: '#0284c7',
      breakdowns: [
        { label: 'Penjualan Bruto', val: `+ ${formatRupiah(omset)}` },
        { label: 'Biaya Admin Marketplace', val: `- ${formatRupiah(biayaAdmin)}` },
        { label: 'Refund / Retur', val: `- ${formatRupiah(refund)}` },
        { label: 'Dana Bersih Dilepas (Net Cair)', val: formatRupiah(netReleased) },
        { label: 'HPP Modal Pokok', val: `- ${formatRupiah(hpp)}` },
        { label: 'Margin Bersih Akhir', val: formatRupiah(margin), isHighlight: true }
      ],
      desc: 'Laba bersih yang dihitung dari: Dana Bersih Dilepas (Omset - Admin - Refund) dikurangi HPP Modal Pokok.'
    }
  };

  const current = configs[metricType] || configs.margin;
  const IconComponent = current.icon;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="bottom-sheet-card" onClick={(e) => e.stopPropagation()}>
        <div className="sheet-handle"></div>

        {/* Modal Header */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{
              width: '42px',
              height: '42px',
              borderRadius: '14px',
              background: current.gradient,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#ffffff',
              boxShadow: '0 4px 12px rgba(0,0,0,0.12)'
            }}>
              <IconComponent size={20} strokeWidth={2.4} />
            </div>
            <div>
              <h3 style={{ fontSize: '15.5px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                {current.title}
              </h3>
              <p style={{ fontSize: '11px', color: '#64748b', margin: '2px 0 0' }}>
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

        {/* Big Total Card */}
        <div style={{
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          borderRadius: '18px',
          padding: '16px',
          textAlign: 'center',
          marginBottom: '16px'
        }}>
          <span style={{ fontSize: '11px', fontWeight: '600', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.4px' }}>
            Total {current.title.replace('Detail ', '')}
          </span>
          <div style={{ fontSize: '24px', fontWeight: '800', color: current.textColor, marginTop: '4px' }}>
            {current.value}
          </div>
        </div>

        {/* Breakdown Items List */}
        <div style={{
          background: '#ffffff',
          border: '1px solid #e2e8f0',
          borderRadius: '18px',
          padding: '14px 16px',
          display: 'flex',
          flexDirection: 'column',
          gap: '10px',
          marginBottom: '16px'
        }}>
          <span style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>
            Rincian & Komponen Perhitungan
          </span>

          {current.breakdowns.map((item, idx) => (
            <div 
              key={idx} 
              style={{ 
                display: 'flex', 
                justifyContent: 'space-between', 
                alignItems: 'center',
                padding: item.isHighlight ? '8px 10px' : '4px 0',
                background: item.isHighlight ? '#ecfdf5' : 'transparent',
                borderRadius: item.isHighlight ? '10px' : '0',
                borderTop: idx > 0 && !item.isHighlight ? '1px dashed #f1f5f9' : 'none'
              }}
            >
              <span style={{ fontSize: '12px', color: item.isHighlight ? '#047857' : '#64748b', fontWeight: item.isHighlight ? '800' : '500' }}>
                {item.label}
              </span>
              <strong style={{ fontSize: '12.5px', color: item.isHighlight ? '#047857' : '#0f172a' }}>
                {item.val}
              </strong>
            </div>
          ))}
        </div>

        {/* Explanatory Footer */}
        <div style={{ 
          display: 'flex', 
          alignItems: 'flex-start', 
          gap: '8px', 
          background: '#eff6ff', 
          border: '1px solid #bfdbfe',
          padding: '12px', 
          borderRadius: '14px', 
          marginBottom: '18px' 
        }}>
          <Info size={16} color="#2563eb" style={{ flexShrink: 0, marginTop: '2px' }} />
          <p style={{ fontSize: '11.5px', color: '#1e40af', margin: 0, lineHeight: 1.45 }}>
            {current.desc}
          </p>
        </div>

        {/* Action Button */}
        <button
          type="button"
          onClick={() => {
            onClose();
            if (onOpenOrders) onOpenOrders('COMPLETED');
          }}
          style={{
            width: '100%',
            background: 'linear-gradient(135deg, #1d4ed8, #2563eb)',
            color: '#ffffff',
            border: 'none',
            borderRadius: '16px',
            padding: '13px',
            fontSize: '13px',
            fontWeight: '800',
            cursor: 'pointer',
            boxShadow: '0 6px 16px rgba(37, 99, 235, 0.3)'
          }}
        >
          Lihat Transaksi Terkait ({count} Order)
        </button>
      </div>
    </div>
  );
}
