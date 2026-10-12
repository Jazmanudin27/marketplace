import React from 'react';
import { createPortal } from 'react-dom';
import { X, Printer, Barcode, CheckCircle2, Box } from 'lucide-react';

export default function OwnerBarcodeModal({ consignment, onClose }) {
  if (!consignment) return null;

  const items = consignment.items || [];
  const refNum = consignment.referenceNumber || consignment.reference_number || 'KNS-001';
  const supplierName = consignment.supplierName || consignment.supplier_name || 'Supplier Titipan';

  const handlePrint = () => {
    window.print();
  };

  const modalContent = (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      background: 'rgba(0, 0, 0, 0.75)',
      backdropFilter: 'blur(10px)',
      WebkitBackdropFilter: 'blur(10px)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 99999,
      padding: '16px',
      boxSizing: 'border-box'
    }}>
      <div style={{
        width: '100%',
        maxWidth: '430px',
        maxHeight: '90vh',
        background: '#ffffff',
        borderRadius: '24px',
        overflow: 'hidden',
        display: 'flex',
        flexDirection: 'column',
        boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)'
      }}>
        {/* Header Modal */}
        <div style={{
          background: 'linear-gradient(135deg, #1e293b, #0f172a)',
          color: '#ffffff',
          padding: '16px 20px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          borderBottom: '1px solid rgba(255, 255, 255, 0.1)'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div style={{
              width: '36px',
              height: '36px',
              borderRadius: '10px',
              background: 'rgba(245, 158, 11, 0.2)',
              border: '1px solid rgba(245, 158, 11, 0.4)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#fbbf24'
            }}>
              <Barcode size={20} />
            </div>
            <div>
              <div style={{ fontSize: '14px', fontWeight: '800' }}>Cetak Stiker Barcode</div>
              <div style={{ fontSize: '11px', color: '#94a3b8' }}>{refNum} • {supplierName}</div>
            </div>
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

        {/* Content Labels */}
        <div style={{
          flex: 1,
          overflowY: 'auto',
          padding: '16px',
          background: '#f8fafc',
          display: 'flex',
          flexDirection: 'column',
          gap: '12px'
        }}>
          <div style={{
            background: '#fef3c7',
            border: '1px solid #fde68a',
            borderRadius: '12px',
            padding: '10px 14px',
            fontSize: '11.5px',
            color: '#92400e',
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
          }}>
            <Box size={16} />
            <span>Label stiker barcode siap ditempel ke kemasan/plastik barang titipan supplier.</span>
          </div>

          {items.map((item, idx) => (
            <div
              key={item.id || idx}
              style={{
                background: '#ffffff',
                border: '1.5px dashed #cbd5e1',
                borderRadius: '16px',
                padding: '14px',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                boxShadow: '0 2px 8px rgba(0,0,0,0.03)'
              }}
            >
              <div style={{ width: '100%', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid #f1f5f9', paddingBottom: '6px', marginBottom: '8px' }}>
                <span style={{ fontSize: '10.5px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase' }}>
                  {supplierName}
                </span>
                <span style={{ fontSize: '10.5px', fontWeight: '800', color: '#f59e0b', background: '#fef3c7', padding: '1px 6px', borderRadius: '4px' }}>
                  TITIPAN #{idx + 1}
                </span>
              </div>

              <div style={{ fontWeight: '800', fontSize: '13px', color: '#1e293b', textAlign: 'center', marginBottom: '2px' }}>
                {item.productName || item.product_name}
              </div>

              <div style={{ fontSize: '11px', color: '#64748b', fontFamily: 'monospace', fontWeight: '700', marginBottom: '8px' }}>
                SKU: {item.sku || 'BRG'}
              </div>

              {/* Barcode Simulated Visual SVG */}
              <div style={{
                background: '#ffffff',
                padding: '8px 16px',
                borderRadius: '8px',
                border: '1px solid #e2e8f0',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                marginBottom: '6px'
              }}>
                <svg width="220" height="42" viewBox="0 0 220 42">
                  <rect x="0" y="0" width="3" height="40" fill="#000" />
                  <rect x="6" y="0" width="2" height="40" fill="#000" />
                  <rect x="11" y="0" width="4" height="40" fill="#000" />
                  <rect x="18" y="0" width="2" height="40" fill="#000" />
                  <rect x="23" y="0" width="5" height="40" fill="#000" />
                  <rect x="31" y="0" width="2" height="40" fill="#000" />
                  <rect x="36" y="0" width="3" height="40" fill="#000" />
                  <rect x="42" y="0" width="6" height="40" fill="#000" />
                  <rect x="51" y="0" width="2" height="40" fill="#000" />
                  <rect x="56" y="0" width="4" height="40" fill="#000" />
                  <rect x="63" y="0" width="3" height="40" fill="#000" />
                  <rect x="69" y="0" width="2" height="40" fill="#000" />
                  <rect x="74" y="0" width="5" height="40" fill="#000" />
                  <rect x="82" y="0" width="3" height="40" fill="#000" />
                  <rect x="88" y="0" width="2" height="40" fill="#000" />
                  <rect x="93" y="0" width="4" height="40" fill="#000" />
                  <rect x="100" y="0" width="6" height="40" fill="#000" />
                  <rect x="109" y="0" width="2" height="40" fill="#000" />
                  <rect x="114" y="0" width="3" height="40" fill="#000" />
                  <rect x="120" y="0" width="5" height="40" fill="#000" />
                  <rect x="128" y="0" width="2" height="40" fill="#000" />
                  <rect x="133" y="0" width="4" height="40" fill="#000" />
                  <rect x="140" y="0" width="3" height="40" fill="#000" />
                  <rect x="146" y="0" width="6" height="40" fill="#000" />
                  <rect x="155" y="0" width="2" height="40" fill="#000" />
                  <rect x="160" y="0" width="4" height="40" fill="#000" />
                  <rect x="167" y="0" width="3" height="40" fill="#000" />
                  <rect x="173" y="0" width="5" height="40" fill="#000" />
                  <rect x="181" y="0" width="2" height="40" fill="#000" />
                  <rect x="186" y="0" width="3" height="40" fill="#000" />
                  <rect x="192" y="0" width="6" height="40" fill="#000" />
                  <rect x="201" y="0" width="2" height="40" fill="#000" />
                  <rect x="206" y="0" width="4" height="40" fill="#000" />
                  <rect x="213" y="0" width="4" height="40" fill="#000" />
                </svg>
                <span style={{ fontSize: '11px', fontFamily: 'monospace', fontWeight: '700', letterSpacing: '2px', color: '#1e293b' }}>
                  {item.sku || 'BRG'}
                </span>
              </div>

              <div style={{ width: '100%', display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11.5px', color: '#475569', marginTop: '4px' }}>
                <span>Jumlah Titip: <strong>{item.qtyReceived || item.qty_received} PCS</strong></span>
                <span>HPP: <strong>Rp {Number(item.unitCostPrice || item.unit_cost_price || 0).toLocaleString('id-ID')}</strong></span>
              </div>
            </div>
          ))}
        </div>

        {/* Footer Actions */}
        <div style={{
          padding: '14px 20px',
          background: '#ffffff',
          borderTop: '1px solid #e2e8f0',
          display: 'flex',
          gap: '10px'
        }}>
          <button
            onClick={onClose}
            style={{
              flex: 1,
              padding: '12px',
              borderRadius: '14px',
              border: '1px solid #cbd5e1',
              background: '#f8fafc',
              color: '#475569',
              fontWeight: '700',
              fontSize: '13px',
              cursor: 'pointer'
            }}
          >
            Tutup
          </button>
          <button
            onClick={handlePrint}
            style={{
              flex: 1.5,
              padding: '12px',
              borderRadius: '14px',
              border: 'none',
              background: 'linear-gradient(135deg, #f59e0b, #d97706)',
              color: '#ffffff',
              fontWeight: '700',
              fontSize: '13px',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              boxShadow: '0 4px 12px rgba(245, 158, 11, 0.4)'
            }}
          >
            <Printer size={16} />
            <span>Cetak Thermal / PDF</span>
          </button>
        </div>
      </div>
    </div>
  );

  return typeof document !== 'undefined' ? createPortal(modalContent, document.body) : modalContent;
}
