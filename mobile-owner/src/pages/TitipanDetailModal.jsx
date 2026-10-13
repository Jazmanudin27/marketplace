import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X, Calendar, User, FileText, CheckCircle2, Clock, Package, AlertCircle, CreditCard, ArrowDownRight, DollarSign } from 'lucide-react';
import { fetchSupplierConsignmentDetail } from '../api/supplierConsignmentApi';

export default function TitipanDetailModal({
  consignment,
  onClose
}) {
  const [activeTab, setActiveTab] = useState('items'); // 'items' | 'deductions' | 'payments'
  const [liveData, setLiveData] = useState(consignment);
  const [isLoadingDetail, setIsLoadingDetail] = useState(false);

  useEffect(() => {
    if (consignment?.id) {
      setIsLoadingDetail(true);
      fetchSupplierConsignmentDetail(consignment.id)
        .then((data) => {
          if (data) setLiveData(data);
        })
        .catch((err) => {
          console.warn('[OwnerModal] Fetch detail error:', err);
        })
        .finally(() => {
          setIsLoadingDetail(false);
        });
    }
  }, [consignment?.id]);

  if (!consignment) return null;

  const activeConsignment = liveData || consignment;
  const items = activeConsignment.items || [];
  const deductions = activeConsignment.deductions || [];
  const settlements = activeConsignment.settlements || [];
  const refNum = activeConsignment.referenceNumber || activeConsignment.reference_number || 'KNS-001';
  const supName = activeConsignment.supplierName || activeConsignment.supplier_name || 'Supplier Titipan';
  const supPhone = activeConsignment.supplierPhone || activeConsignment.supplier_phone || '';
  const dateStr = activeConsignment.consignmentDateFormatted || activeConsignment.consignmentDate || '-';
  const notes = activeConsignment.notes || '';
  const status = activeConsignment.status || 'approved';

  const totalQty = activeConsignment.totalQtyReceived || activeConsignment.total_qty_received || 0;
  const totalSold = activeConsignment.totalQtySold || activeConsignment.total_qty_sold || 0;
  const sisaStok = activeConsignment.sisaStok !== undefined ? activeConsignment.sisaStok : Math.max(0, totalQty - totalSold);
  const totalHpp = activeConsignment.totalAmountHpp || activeConsignment.total_amount_hpp || 0;
  const totalPaid = activeConsignment.totalAmountPaid !== undefined ? activeConsignment.totalAmountPaid : 0;
  const sisaPembayaran = activeConsignment.sisaPembayaran !== undefined ? activeConsignment.sisaPembayaran : Math.max(0, totalHpp - totalPaid);
  const paymentStatus = activeConsignment.paymentStatus || (totalPaid >= totalHpp && totalHpp > 0 ? 'paid' : totalPaid > 0 ? 'partial' : 'unpaid');
  const paymentStatusLabel = activeConsignment.paymentStatusLabel || (paymentStatus === 'paid' ? 'Lunas' : paymentStatus === 'partial' ? 'Sebagian' : 'Belum Lunas');

  const modalContent = (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      background: 'rgba(0, 0, 0, 0.75)',
      backdropFilter: 'blur(8px)',
      WebkitBackdropFilter: 'blur(8px)',
      display: 'flex',
      alignItems: 'flex-end',
      justifyContent: 'center',
      zIndex: 99999,
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
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
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
                {status === 'approved' ? 'Disetujui' : 'Pending'}
              </span>

              {/* Status Pembayaran Badge */}
              <span style={{
                background: paymentStatus === 'paid' ? 'rgba(16, 185, 129, 0.25)' : paymentStatus === 'partial' ? 'rgba(245, 158, 11, 0.25)' : 'rgba(239, 68, 68, 0.25)',
                color: paymentStatus === 'paid' ? '#34d399' : paymentStatus === 'partial' ? '#fbbf24' : '#f87171',
                border: paymentStatus === 'paid' ? '1px solid rgba(16, 185, 129, 0.5)' : paymentStatus === 'partial' ? '1px solid rgba(245, 158, 11, 0.5)' : '1px solid rgba(239, 68, 68, 0.5)',
                fontSize: '10.5px',
                fontWeight: '800',
                padding: '2px 8px',
                borderRadius: '6px',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '4px'
              }}>
                {paymentStatus === 'paid' ? <CheckCircle2 size={12} /> : paymentStatus === 'partial' ? <Clock size={12} /> : <AlertCircle size={12} />}
                {paymentStatusLabel}
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

        {/* Status Finansial & Pembayaran Banner */}
        <div style={{
          background: 'linear-gradient(135deg, #047857 0%, #065f46 100%)',
          padding: '12px 18px',
          color: '#ffffff',
          display: 'grid',
          gridTemplateColumns: 'repeat(3, 1fr)',
          gap: '8px',
          textAlign: 'center'
        }}>
          <div>
            <div style={{ fontSize: '9.5px', color: '#a7f3d0', fontWeight: '700', textTransform: 'uppercase' }}>Total Nilai</div>
            <div style={{ fontSize: '13px', fontWeight: '900', color: '#ffffff', fontFamily: 'monospace', marginTop: '2px' }}>
              Rp {Number(totalHpp).toLocaleString('id-ID')}
            </div>
          </div>
          <div>
            <div style={{ fontSize: '9.5px', color: '#6ee7b7', fontWeight: '700', textTransform: 'uppercase' }}>Terbayar</div>
            <div style={{ fontSize: '13px', fontWeight: '900', color: '#34d399', fontFamily: 'monospace', marginTop: '2px' }}>
              Rp {Number(totalPaid).toLocaleString('id-ID')}
            </div>
          </div>
          <div>
            <div style={{ fontSize: '9.5px', color: '#fde68a', fontWeight: '700', textTransform: 'uppercase' }}>Sisa Pembayaran</div>
            <div style={{
              fontSize: '13px',
              fontWeight: '900',
              color: sisaPembayaran > 0 ? '#fca5a5' : '#34d399',
              fontFamily: 'monospace',
              marginTop: '2px'
            }}>
              Rp {Number(sisaPembayaran).toLocaleString('id-ID')}
            </div>
          </div>
        </div>

        {/* KPI Mini Bar Stok */}
        <div style={{
          background: '#f8fafc',
          borderBottom: '1px solid #e2e8f0',
          padding: '10px 18px',
          display: 'grid',
          gridTemplateColumns: 'repeat(3, 1fr)',
          gap: '8px',
          textAlign: 'center'
        }}>
          <div style={{ background: '#ffffff', padding: '6px 4px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
            <div style={{ fontSize: '9.5px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>Total Titip</div>
            <div style={{ fontSize: '13px', fontWeight: '800', color: '#1e293b' }}>{totalQty} PCS</div>
          </div>
          <div style={{ background: '#ffffff', padding: '6px 4px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
            <div style={{ fontSize: '9.5px', color: '#10b981', fontWeight: '700', textTransform: 'uppercase' }}>Terjual / Keluar</div>
            <div style={{ fontSize: '13px', fontWeight: '800', color: '#10b981' }}>{totalSold} PCS</div>
          </div>
          <div style={{ background: '#ffffff', padding: '6px 4px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
            <div style={{ fontSize: '9.5px', color: '#f59e0b', fontWeight: '700', textTransform: 'uppercase' }}>Sisa Di Toko</div>
            <div style={{ fontSize: '13px', fontWeight: '800', color: '#f59e0b' }}>{sisaStok} PCS</div>
          </div>
        </div>

        {/* Tab Toggle (Barang, Terjual, Pembayaran) */}
        <div style={{ display: 'flex', borderBottom: '1px solid #e2e8f0', background: '#ffffff' }}>
          <button
            onClick={() => setActiveTab('items')}
            style={{
              flex: 1,
              padding: '11px 4px',
              border: 'none',
              background: 'transparent',
              fontSize: '11.5px',
              fontWeight: activeTab === 'items' ? '800' : '600',
              color: activeTab === 'items' ? '#f59e0b' : '#64748b',
              borderBottom: activeTab === 'items' ? '2.5px solid #f59e0b' : '2.5px solid transparent',
              cursor: 'pointer'
            }}
          >
            Barang ({items.length})
          </button>
          <button
            onClick={() => setActiveTab('deductions')}
            style={{
              flex: 1,
              padding: '11px 4px',
              border: 'none',
              background: 'transparent',
              fontSize: '11.5px',
              fontWeight: activeTab === 'deductions' ? '800' : '600',
              color: activeTab === 'deductions' ? '#f59e0b' : '#64748b',
              borderBottom: activeTab === 'deductions' ? '2.5px solid #f59e0b' : '2.5px solid transparent',
              cursor: 'pointer'
            }}
          >
            Terjual ({deductions.length})
          </button>
          <button
            onClick={() => setActiveTab('payments')}
            style={{
              flex: 1,
              padding: '11px 4px',
              border: 'none',
              background: 'transparent',
              fontSize: '11.5px',
              fontWeight: activeTab === 'payments' ? '800' : '600',
              color: activeTab === 'payments' ? '#059669' : '#64748b',
              borderBottom: activeTab === 'payments' ? '2.5px solid #059669' : '2.5px solid transparent',
              cursor: 'pointer'
            }}
          >
            Pembayaran ({settlements.length})
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
          ) : activeTab === 'deductions' ? (
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
          ) : (
            /* ActiveTab === 'payments' */
            settlements.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '36px 12px', color: '#94a3b8', fontSize: '12.5px' }}>
                <CreditCard size={28} style={{ margin: '0 auto 8px', opacity: 0.4, color: '#f59e0b' }} />
                <div style={{ fontWeight: '800', color: '#1e293b' }}>Belum Ada Setoran Pembayaran</div>
                <div style={{ fontSize: '11px', marginTop: '4px', color: '#64748b' }}>
                  Sisa tagihan titipan untuk nota ini: <strong style={{ color: '#dc2626' }}>Rp {Number(sisaPembayaran).toLocaleString('id-ID')}</strong>.
                </div>
                <div style={{ fontSize: '10.5px', marginTop: '6px', color: '#94a3b8' }}>
                  Pembayaran akan tercatat saat admin/owner memproses setoran bagi hasil konsinyasi.
                </div>
              </div>
            ) : (
              settlements.map((st, sIdx) => (
                <div
                  key={st.id || sIdx}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #bbf7d0',
                    borderRadius: '14px',
                    padding: '12px 14px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '6px',
                    boxShadow: '0 2px 6px rgba(16, 185, 129, 0.05)'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                      <span style={{
                        background: '#ecfdf5',
                        color: '#059669',
                        fontSize: '10px',
                        fontWeight: '800',
                        padding: '2px 6px',
                        borderRadius: '6px'
                      }}>
                        Transfer Sukses
                      </span>
                      <span style={{ fontSize: '11px', fontWeight: '800', color: '#1e293b', fontFamily: 'monospace' }}>
                        {st.settlementNumber || 'SETTLEMENT'}
                      </span>
                    </div>
                    <span style={{ fontSize: '13px', fontWeight: '900', color: '#059669', fontFamily: 'monospace' }}>
                      +Rp {Number(st.subtotal || 0).toLocaleString('id-ID')}
                    </span>
                  </div>

                  <div style={{ fontSize: '11.5px', color: '#334155', fontWeight: '600' }}>
                    {st.productName} • <span style={{ color: '#059669', fontWeight: '800' }}>{st.qtySettled} PCS</span>
                  </div>

                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '10.5px', color: '#64748b', borderTop: '1px dashed #e2e8f0', paddingTop: '5px' }}>
                    <span>Tgl: {st.dateFormatted || '-'}</span>
                    <span>Metode: {st.paymentMethod ? st.paymentMethod.toUpperCase() : 'TRANSFER'} {st.paymentRef ? `(${st.paymentRef})` : ''}</span>
                  </div>
                </div>
              ))
            )
          )}

          {/* Total Modal & Rincian Pembayaran Box */}
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            border: '1px solid #e2e8f0',
            padding: '12px 16px',
            display: 'flex',
            flexDirection: 'column',
            gap: '8px',
            marginTop: '4px',
            boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
                Total Nilai (Modal HPP)
              </div>
              <div style={{ fontSize: '13.5px', fontWeight: '900', color: '#1e293b', fontFamily: 'monospace' }}>
                Rp {Number(totalHpp).toLocaleString('id-ID')}
              </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div style={{ fontSize: '11px', color: '#059669', fontWeight: '700' }}>
                Sudah Dibayar (Lunas)
              </div>
              <div style={{ fontSize: '13.5px', fontWeight: '900', color: '#059669', fontFamily: 'monospace' }}>
                Rp {Number(totalPaid).toLocaleString('id-ID')}
              </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderTop: '1px dashed #cbd5e1', paddingTop: '6px' }}>
              <div>
                <div style={{ fontSize: '11.5px', color: '#dc2626', fontWeight: '800' }}>
                  Sisa Pembayaran
                </div>
                <div style={{ fontSize: '10px', color: '#64748b' }}>
                  Status: <strong style={{ color: paymentStatus === 'paid' ? '#059669' : paymentStatus === 'partial' ? '#d97706' : '#dc2626' }}>{paymentStatusLabel}</strong>
                </div>
              </div>
              <div style={{
                fontSize: '15.5px',
                fontWeight: '900',
                color: sisaPembayaran > 0 ? '#dc2626' : '#059669',
                fontFamily: 'monospace'
              }}>
                Rp {Number(sisaPembayaran).toLocaleString('id-ID')}
              </div>
            </div>
          </div>
        </div>

        {/* Footer Actions */}
        <div style={{
          padding: '14px 18px calc(16px + env(safe-area-inset-bottom, 12px))',
          background: '#ffffff',
          borderTop: '1px solid #e2e8f0',
          display: 'flex'
        }}>
          <button
            onClick={onClose}
            style={{
              width: '100%',
              padding: '12px 20px',
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

      <style>{`
        @keyframes slideUp {
          from { transform: translateY(100%); }
          to { transform: translateY(0); }
        }
      `}</style>
    </div>
  );

  return typeof document !== 'undefined' ? createPortal(modalContent, document.body) : modalContent;
}
