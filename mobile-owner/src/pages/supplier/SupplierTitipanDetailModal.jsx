import React, { useState } from 'react';
import { X, Calendar, User, FileText, CheckCircle2, Clock, Trash2, Edit3, Barcode, Package, AlertCircle } from 'lucide-react';

export default function SupplierTitipanDetailModal({
  consignment,
  onClose,
  onEdit,
  onDelete,
  onOpenBarcode
}) {
  const [activeTab, setActiveTab] = useState('items'); // 'items' | 'deductions'

  if (!consignment) return null;

  const items = consignment.items || [];
  const deductions = consignment.deductions || [];
  const refNum = consignment.referenceNumber || consignment.reference_number || 'KNS-001';
  const supName = consignment.supplierName || consignment.supplier_name || 'Supplier Titipan';
  const supPhone = consignment.supplierPhone || consignment.supplier_phone || '';
  const dateStr = consignment.consignmentDateFormatted || consignment.consignmentDate || '-';
  const notes = consignment.notes || '';
  const status = consignment.status || 'approved';

  const totalQty = consignment.totalQtyReceived || consignment.total_qty_received || 0;
  const totalSold = consignment.totalQtySold || consignment.total_qty_sold || 0;
  const sisaStok = consignment.sisaStok !== undefined ? consignment.sisaStok : Math.max(0, totalQty - totalSold);
  const totalHpp = consignment.totalAmountHpp || consignment.total_amount_hpp || 0;

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      background: 'rgba(0, 0, 0, 0.7)',
      backdropFilter: 'blur(8px)',
      WebkitBackdropFilter: 'blur(8px)',
      display: 'flex',
      alignItems: 'flex-end',
      justifyContent: 'center',
      zIndex: 9998,
      boxSizing: 'border-box'
    }}>
      <div style={{
        width: '100%',
        maxWidth: '430px',
        maxHeight: '92vh',
        background: '#ffffff',
        borderTopLeftRadius: '28px',
        borderTopRightRadius: '28px',
        overflow: 'hidden',
        display: 'flex',
        flexDirection: 'column',
        boxShadow: '0 -10px 40px rgba(0, 0, 0, 0.4)',
        animation: 'slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1)'
      }}>
        {/* Header Drawer */}
        <div style={{
          background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
          color: '#ffffff',
          padding: '18px 20px 14px',
          position: 'relative'
        }}>
          {/* Top Notch Indicator */}
          <div style={{
            width: '40px',
            height: '4px',
            background: 'rgba(255, 255, 255, 0.25)',
            borderRadius: '4px',
            margin: '0 auto 12px'
          }} />

          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <span style={{
                background: status === 'approved' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(245, 158, 11, 0.2)',
                color: status === 'approved' ? '#34d399' : '#fbbf24',
                border: status === 'approved' ? '1px solid rgba(16, 185, 129, 0.4)' : '1px solid rgba(245, 158, 11, 0.4)',
                fontSize: '10.5px',
                fontWeight: '700',
                padding: '2px 8px',
                borderRadius: '6px',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px'
              }}>
                {status === 'approved' ? <CheckCircle2 size={12} /> : <Clock size={12} />}
                {status === 'approved' ? 'Disetujui / Selesai' : 'Pending'}
              </span>

              <span style={{ fontSize: '11px', color: '#94a3b8', fontFamily: 'monospace' }}>
                {refNum}
              </span>
            </div>

            <button
              onClick={onClose}
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

          <div style={{ fontSize: '17px', fontWeight: '800', color: '#f8fafc', marginBottom: '2px' }}>
            {supName}
          </div>
          <div style={{ fontSize: '11.5px', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '8px' }}>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
              <Calendar size={13} color="#f59e0b" /> {dateStr}
            </span>
            {supPhone && <span>• {supPhone}</span>}
          </div>
        </div>

        {/* KPI Mini Bar */}
        <div style={{
          background: '#f8fafc',
          borderBottom: '1px solid #e2e8f0',
          padding: '12px 18px',
          display: 'grid',
          gridTemplateColumns: 'repeat(3, 1fr)',
          gap: '8px',
          textAlign: 'center'
        }}>
          <div style={{ background: '#ffffff', padding: '8px 4px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
            <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Total Titip</div>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#1e293b' }}>{totalQty} PCS</div>
          </div>
          <div style={{ background: '#ffffff', padding: '8px 4px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
            <div style={{ fontSize: '10px', color: '#10b981', fontWeight: '700', textTransform: 'uppercase' }}>Terjual / Keluar</div>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#10b981' }}>{totalSold} PCS</div>
          </div>
          <div style={{ background: '#ffffff', padding: '8px 4px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
            <div style={{ fontSize: '10px', color: '#f59e0b', fontWeight: '700', textTransform: 'uppercase' }}>Sisa Di Toko</div>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#f59e0b' }}>{sisaStok} PCS</div>
          </div>
        </div>

        {/* Tab Toggle */}
        <div style={{ display: 'flex', borderBottom: '1px solid #e2e8f0', background: '#ffffff' }}>
          <button
            onClick={() => setActiveTab('items')}
            style={{
              flex: 1,
              padding: '11px',
              border: 'none',
              background: 'transparent',
              fontSize: '12.5px',
              fontWeight: activeTab === 'items' ? '800' : '600',
              color: activeTab === 'items' ? '#f59e0b' : '#64748b',
              borderBottom: activeTab === 'items' ? '2.5px solid #f59e0b' : '2.5px solid transparent',
              cursor: 'pointer'
            }}
          >
            Daftar Barang ({items.length})
          </button>
          <button
            onClick={() => setActiveTab('deductions')}
            style={{
              flex: 1,
              padding: '11px',
              border: 'none',
              background: 'transparent',
              fontSize: '12.5px',
              fontWeight: activeTab === 'deductions' ? '800' : '600',
              color: activeTab === 'deductions' ? '#f59e0b' : '#64748b',
              borderBottom: activeTab === 'deductions' ? '2.5px solid #f59e0b' : '2.5px solid transparent',
              cursor: 'pointer'
            }}
          >
            Riwayat Terjual ({deductions.length})
          </button>
        </div>

        {/* Tab Content Body */}
        <div style={{
          flex: 1,
          overflowY: 'auto',
          padding: '16px',
          background: '#f8fafc',
          display: 'flex',
          flexDirection: 'column',
          gap: '10px'
        }}>
          {notes && (
            <div style={{
              background: '#f1f5f9',
              borderRadius: '12px',
              padding: '10px 12px',
              fontSize: '11.5px',
              color: '#475569',
              display: 'flex',
              gap: '6px'
            }}>
              <FileText size={15} color="#94a3b8" style={{ flexShrink: 0, marginTop: '1px' }} />
              <div><strong>Catatan:</strong> {notes}</div>
            </div>
          )}

          {activeTab === 'items' ? (
            items.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '30px 10px', color: '#94a3b8', fontSize: '13px' }}>
                Tidak ada item barang tercatat
              </div>
            ) : (
              items.map((it, idx) => {
                const qRec = it.qtyReceived || it.qty_received || 0;
                const qSld = it.qtySold || it.qty_sold || 0;
                const sisa = it.sisaStok !== undefined ? it.sisaStok : Math.max(0, qRec - qSld);
                const cost = it.unitCostPrice || it.unit_cost_price || 0;
                const price = it.unitSellingPrice || it.unit_selling_price || 0;
                const subHpp = it.subtotalHpp || (qRec * cost);

                return (
                  <div
                    key={it.id || idx}
                    style={{
                      background: '#ffffff',
                      border: '1px solid #e2e8f0',
                      borderRadius: '16px',
                      padding: '12px 14px',
                      boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '4px' }}>
                      <div>
                        <div style={{ fontWeight: '800', fontSize: '13px', color: '#1e293b' }}>
                          {it.productName || it.product_name}
                        </div>
                        <div style={{ fontSize: '11px', color: '#64748b', fontFamily: 'monospace', fontWeight: '600' }}>
                          SKU: {it.sku || 'BRG'}
                        </div>
                      </div>
                      <span style={{
                        background: '#fef3c7',
                        color: '#b45309',
                        fontSize: '10px',
                        fontWeight: '800',
                        padding: '2px 6px',
                        borderRadius: '6px'
                      }}>
                        #{idx + 1}
                      </span>
                    </div>

                    <div style={{
                      display: 'grid',
                      gridTemplateColumns: '1fr 1fr',
                      gap: '8px',
                      background: '#f8fafc',
                      borderRadius: '10px',
                      padding: '8px 10px',
                      margin: '8px 0',
                      fontSize: '11.5px'
                    }}>
                      <div>
                        <span style={{ color: '#64748b' }}>Qty Terima: </span>
                        <strong>{qRec} PCS</strong>
                      </div>
                      <div>
                        <span style={{ color: '#64748b' }}>Terjual: </span>
                        <strong style={{ color: '#10b981' }}>{qSld} PCS</strong>
                      </div>
                      <div>
                        <span style={{ color: '#64748b' }}>Sisa Stok: </span>
                        <strong style={{ color: '#f59e0b' }}>{sisa} PCS</strong>
                      </div>
                      <div>
                        <span style={{ color: '#64748b' }}>Harga Titip (HPP): </span>
                        <strong>Rp {Number(cost).toLocaleString('id-ID')}</strong>
                      </div>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11.5px', paddingTop: '4px' }}>
                      <span style={{ color: '#64748b' }}>Harga Jual Toko: Rp {Number(price).toLocaleString('id-ID')}</span>
                      <span style={{ color: '#1e293b', fontWeight: '800' }}>
                        Subtotal: <span style={{ color: '#f59e0b', fontFamily: 'monospace' }}>Rp {Number(subHpp).toLocaleString('id-ID')}</span>
                      </span>
                    </div>
                  </div>
                );
              })
            )
          ) : (
            deductions.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '36px 12px', color: '#94a3b8', fontSize: '12.5px' }}>
                <Package size={28} style={{ margin: '0 auto 8px', opacity: 0.4 }} />
                <div>Belum ada histori pengurangan stok dari penjualan marketplace</div>
                <div style={{ fontSize: '11px', marginTop: '4px' }}>Stok akan berkurang otomatis saat pesanan dikemas / discan gudang.</div>
              </div>
            ) : (
              deductions.map((d, dIdx) => (
                <div
                  key={d.id || dIdx}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #e2e8f0',
                    borderRadius: '14px',
                    padding: '10px 14px',
                    fontSize: '12px'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                    <span style={{ fontWeight: '800', color: '#1e293b' }}>{d.invoiceNumber}</span>
                    <span style={{ color: '#ef4444', fontWeight: '800' }}>-{d.qtyDeducted} PCS</span>
                  </div>
                  <div style={{ fontSize: '11px', color: '#64748b' }}>
                    {d.dateFormatted || d.createdAt} • {d.notes || 'Scan Pengurangan Stok Pesanan'}
                  </div>
                </div>
              ))
            )
          )}

          {/* Total Modal Box */}
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            border: '1px solid #fde68a',
            padding: '12px 16px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginTop: '4px'
          }}>
            <div>
              <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                Akumulasi Nilai Modal HPP
              </div>
              <div style={{ fontSize: '11px', color: '#94a3b8' }}>
                {totalQty} PCS Barang Titipan
              </div>
            </div>
            <div style={{ fontSize: '16px', fontWeight: '900', color: '#d97706', fontFamily: 'monospace' }}>
              Rp {Number(totalHpp).toLocaleString('id-ID')}
            </div>
          </div>
        </div>

        {/* Footer Actions */}
        <div style={{
          padding: '14px 18px',
          background: '#ffffff',
          borderTop: '1px solid #e2e8f0',
          display: 'flex',
          gap: '8px'
        }}>
          <button
            onClick={() => onOpenBarcode(consignment)}
            style={{
              padding: '11px 14px',
              borderRadius: '14px',
              border: '1px solid #f59e0b',
              background: '#fffbeb',
              color: '#d97706',
              fontWeight: '700',
              fontSize: '12.5px',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              cursor: 'pointer'
            }}
          >
            <Barcode size={16} />
            <span>Label Barcode</span>
          </button>

          <button
            onClick={() => onEdit(consignment)}
            style={{
              flex: 1,
              padding: '11px',
              borderRadius: '14px',
              border: 'none',
              background: 'linear-gradient(135deg, #f59e0b, #d97706)',
              color: '#ffffff',
              fontWeight: '700',
              fontSize: '12.5px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              cursor: 'pointer'
            }}
          >
            <Edit3 size={15} />
            <span>Edit Data</span>
          </button>

          <button
            onClick={() => onDelete(consignment)}
            style={{
              padding: '11px 14px',
              borderRadius: '14px',
              border: '1px solid #fee2e2',
              background: '#fef2f2',
              color: '#ef4444',
              fontWeight: '700',
              fontSize: '12.5px',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              cursor: 'pointer'
            }}
          >
            <Trash2 size={16} />
          </button>
        </div>
      </div>

      <style>{`
        @keyframes slideUp {
          from { transform: translateY(100%); }
          to { transform: translateY(0); }
        }
      `}</style>
    </div>
  );
}
