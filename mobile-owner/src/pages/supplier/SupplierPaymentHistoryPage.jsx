import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { Search, Filter, Calendar, CreditCard, ChevronRight, RefreshCw, X, Receipt, CheckCircle2, DollarSign } from 'lucide-react';
import { fetchSupplierSettlements } from '../../api/supplierConsignmentApi';

export default function SupplierPaymentHistoryPage({ currentSupplierUser }) {
  const [settlements, setSettlements] = useState([]);
  const [kpi, setKpi] = useState({
    totalTransaksi: 0,
    totalQtySettled: 0,
    totalAmountPaid: 0
  });
  const [isLoading, setIsLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [showFilterDrawer, setShowFilterDrawer] = useState(false);

  // Detail Modal
  const [selectedSettlement, setSelectedSettlement] = useState(null);

  const loadData = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSupplierSettlements({
        search,
        date_from: dateFrom,
        date_to: dateTo,
        payment_method: paymentMethod,
        supplier_id: currentSupplierUser?.supplier_id
      });
      if (res && res.data) {
        setSettlements(res.data);
        if (res.kpi) setKpi(res.kpi);
      }
    } catch (err) {
      console.warn('Failed to load settlements:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [search, dateFrom, dateTo, paymentMethod]);

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title & Refresh */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: 0, letterSpacing: '-0.2px' }}>
              Histori Pembayaran
            </h2>
            <span style={{
              background: '#ecfdf5',
              color: '#059669',
              fontSize: '10px',
              fontWeight: '800',
              padding: '2px 7px',
              borderRadius: '6px'
            }}>
              Setoran
            </span>
          </div>
          <p style={{ fontSize: '11.5px', color: '#64748b', margin: '3px 0 0' }}>
            Rekap transfer hasil bagi hasil penjualan titipan
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

      {/* KPI Cards: Total Disetor, Total Kuantitas, Total Nota */}
      <div style={{ display: 'grid', gridTemplateColumns: '1.2fr 1fr', gap: '10px' }}>
        {/* Total Nominal Disetor */}
        <div style={{
          background: 'linear-gradient(135deg, #059669 0%, #047857 100%)',
          borderRadius: '18px',
          padding: '14px 16px',
          color: '#ffffff',
          boxShadow: '0 6px 16px rgba(5, 150, 105, 0.25)',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'space-between'
        }}>
          <div>
            <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#a7f3d0', textTransform: 'uppercase', letterSpacing: '0.4px' }}>
              Total Dana Diterima
            </div>
            <div style={{ fontSize: '20px', fontWeight: '900', fontFamily: 'monospace', margin: '6px 0 2px' }}>
              Rp {Number(kpi.totalAmountPaid || 0).toLocaleString('id-ID')}
            </div>
          </div>
          <div style={{ fontSize: '10.5px', color: '#d1fae5', display: 'flex', alignItems: 'center', gap: '4px' }}>
            <CheckCircle2 size={12} />
            <span>Lunas disetorkan ke rekening</span>
          </div>
        </div>

        {/* Total Qty Settled & Transaksi */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
          <div style={{
            background: '#ffffff',
            border: '1px solid #e2e8f0',
            borderRadius: '14px',
            padding: '9px 12px',
            boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
          }}>
            <div style={{ fontSize: '10px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
              Barang Lunas
            </div>
            <div style={{ fontSize: '15px', fontWeight: '900', color: '#1e293b', marginTop: '2px' }}>
              {Number(kpi.totalQtySettled || 0).toLocaleString('id-ID')} PCS
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
              Total Bukti Setor
            </div>
            <div style={{ fontSize: '15px', fontWeight: '900', color: '#d97706', marginTop: '2px' }}>
              {kpi.totalTransaksi || settlements.length} Bukti
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
            placeholder="Cari no. bukti setoran / catatan..."
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
            background: (dateFrom || dateTo || paymentMethod) ? '#ecfdf5' : '#ffffff',
            border: (dateFrom || dateTo || paymentMethod) ? '1px solid #10b981' : '1px solid #cbd5e1',
            color: (dateFrom || dateTo || paymentMethod) ? '#047857' : '#475569',
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
            Filter Tanggal & Metode Bayar
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

          <div>
            <label style={{ fontSize: '10.5px', color: '#64748b', display: 'block', marginBottom: '4px' }}>Metode Bayar</label>
            <select
              value={paymentMethod}
              onChange={(e) => setPaymentMethod(e.target.value)}
              style={{
                width: '100%',
                padding: '8px 10px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '12px',
                color: '#1e293b',
                background: '#ffffff'
              }}
            >
              <option value="">Semua Metode</option>
              <option value="transfer">Transfer Bank</option>
              <option value="cash">Tunai / Cash</option>
            </select>
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '4px' }}>
            {(dateFrom || dateTo || paymentMethod) && (
              <button
                onClick={() => { setDateFrom(''); setDateTo(''); setPaymentMethod(''); }}
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

      {/* List Setoran Pembayaran */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
        {isLoading ? (
          <div style={{ textAlign: 'center', padding: '40px', color: '#94a3b8', fontSize: '13px' }}>
            Memuat histori pembayaran...
          </div>
        ) : settlements.length === 0 ? (
          <div style={{
            background: '#ffffff',
            borderRadius: '20px',
            padding: '36px 16px',
            textAlign: 'center',
            border: '1px solid #e2e8f0',
            color: '#94a3b8'
          }}>
            <Receipt size={36} style={{ margin: '0 auto 10px', opacity: 0.35, color: '#10b981' }} />
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#1e293b' }}>Belum Ada Riwayat Pembayaran</div>
            <div style={{ fontSize: '11.5px', marginTop: '4px', color: '#64748b' }}>
              Bukti transfer & pelunasan barang konsinyasi akan tercatat di sini.
            </div>
          </div>
        ) : (
          settlements.map((s) => {
            const items = s.items || [];

            return (
              <div
                key={s.id}
                onClick={() => setSelectedSettlement(s)}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '18px',
                  padding: '14px 16px',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '10px',
                  cursor: 'pointer'
                }}
              >
                {/* Header: No Bukti & Nominal */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <div style={{
                      fontFamily: 'monospace',
                      fontWeight: '800',
                      fontSize: '12.5px',
                      color: '#059669',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '5px'
                    }}>
                      <Receipt size={14} color="#10b981" />
                      <span>{s.settlementNumber}</span>
                    </div>
                    <div style={{ fontSize: '11px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '3px' }}>
                      <Calendar size={12} color="#94a3b8" />
                      <span>{s.settlementDateFormatted || s.settlementDate}</span>
                    </div>
                  </div>

                  <div style={{ textAlign: 'right' }}>
                    <div style={{ fontSize: '15px', fontWeight: '900', color: '#047857', fontFamily: 'monospace' }}>
                      Rp {Number(s.totalAmountPaid || 0).toLocaleString('id-ID')}
                    </div>
                    <span style={{
                      background: 'rgba(16, 185, 129, 0.12)',
                      color: '#059669',
                      fontSize: '10px',
                      fontWeight: '800',
                      padding: '2px 6px',
                      borderRadius: '6px',
                      display: 'inline-block',
                      marginTop: '3px',
                      textTransform: 'uppercase'
                    }}>
                      {s.paymentMethod}
                    </span>
                  </div>
                </div>

                {/* Ringkasan Item & Kuantitas */}
                <div style={{
                  background: '#f8fafc',
                  border: '1px solid #f1f5f9',
                  borderRadius: '12px',
                  padding: '9px 12px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  fontSize: '11.5px'
                }}>
                  <span style={{ color: '#64748b' }}>
                    Total Disetor: <strong style={{ color: '#1e293b' }}>{s.totalQtySettled} PCS</strong> ({items.length} jenis)
                  </span>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '2px', color: '#2563eb', fontWeight: '700' }}>
                    <span>Detail</span>
                    <ChevronRight size={14} />
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Modal Detail Setoran */}
      {selectedSettlement && typeof document !== 'undefined' && createPortal(
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
            maxHeight: '88vh',
            animation: 'slideUp 0.25s ease-out',
            overflow: 'hidden'
          }}>
            {/* Header Modal */}
            <div style={{
              background: 'linear-gradient(135deg, #064e3b 0%, #022c22 100%)',
              padding: '16px 20px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'flex-start'
            }}>
              <div>
                <span style={{
                  background: 'rgba(52, 211, 153, 0.2)',
                  color: '#34d399',
                  fontSize: '10.5px',
                  fontWeight: '800',
                  padding: '2px 8px',
                  borderRadius: '6px',
                  display: 'inline-block',
                  marginBottom: '6px',
                  letterSpacing: '0.5px'
                }}>
                  RINCIAN BUKTI PEMBAYARAN
                </span>
                <h3 style={{ fontSize: '16px', fontWeight: '800', color: '#f8fafc', margin: 0, fontFamily: 'monospace' }}>
                  {selectedSettlement.settlementNumber}
                </h3>
                <div style={{ fontSize: '11px', color: '#a7f3d0', marginTop: '3px' }}>
                  Tanggal: {selectedSettlement.settlementDateFormatted || selectedSettlement.settlementDate}
                </div>
              </div>

              <button
                onClick={() => setSelectedSettlement(null)}
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
              {/* Total Card */}
              <div style={{
                background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
                border: '1px solid #a7f3d0',
                borderRadius: '16px',
                padding: '14px 16px',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}>
                <div>
                  <div style={{ fontSize: '10.5px', fontWeight: '700', color: '#047857', textTransform: 'uppercase' }}>
                    Total Dana Disetorkan
                  </div>
                  <div style={{ fontSize: '20px', fontWeight: '900', color: '#065f46', fontFamily: 'monospace', marginTop: '2px' }}>
                    Rp {Number(selectedSettlement.totalAmountPaid || 0).toLocaleString('id-ID')}
                  </div>
                </div>
                <div style={{
                  background: '#bbf7d0',
                  color: '#065f46',
                  fontSize: '11px',
                  fontWeight: '800',
                  padding: '4px 10px',
                  borderRadius: '10px'
                }}>
                  {selectedSettlement.totalQtySettled} PCS
                </div>
              </div>

              {/* Rincian Akun & Bank */}
              <div style={{
                background: '#f8fafc',
                border: '1px solid #e2e8f0',
                borderRadius: '14px',
                padding: '12px 14px',
                fontSize: '12px',
                display: 'flex',
                flexDirection: 'column',
                gap: '6px'
              }}>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: '#64748b' }}>Metode:</span>
                  <span style={{ fontWeight: '700', color: '#1e293b', textTransform: 'uppercase' }}>{selectedSettlement.paymentMethod}</span>
                </div>
                {selectedSettlement.bankAccount && (
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ color: '#64748b' }}>Rekening Tujuan:</span>
                    <span style={{ fontWeight: '700', color: '#1e293b' }}>{selectedSettlement.bankAccount}</span>
                  </div>
                )}
                {selectedSettlement.referenceNumber && selectedSettlement.referenceNumber !== '-' && (
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ color: '#64748b' }}>No. Referensi:</span>
                    <span style={{ fontWeight: '700', color: '#1e293b', fontFamily: 'monospace' }}>{selectedSettlement.referenceNumber}</span>
                  </div>
                )}
                {selectedSettlement.notes && (
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span style={{ color: '#64748b' }}>Catatan:</span>
                    <span style={{ fontWeight: '600', color: '#1e293b' }}>{selectedSettlement.notes}</span>
                  </div>
                )}
              </div>

              {/* List Produk yang disetor */}
              <div>
                <div style={{ fontSize: '12px', fontWeight: '800', color: '#1e293b', marginBottom: '8px', textTransform: 'uppercase' }}>
                  Daftar Produk Disetor ({selectedSettlement.items?.length || 0})
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  {(selectedSettlement.items || []).map((it, idx) => (
                    <div
                      key={it.id || idx}
                      style={{
                        background: '#ffffff',
                        border: '1px solid #e2e8f0',
                        borderRadius: '12px',
                        padding: '10px 12px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                      }}
                    >
                      <div>
                        <div style={{ fontWeight: '700', fontSize: '13px', color: '#1e293b' }}>
                          {it.productName}
                        </div>
                        <div style={{ fontSize: '11px', color: '#64748b', fontFamily: 'monospace', marginTop: '2px' }}>
                          SKU: {it.sku} • {it.qtySettled} PCS @ Rp {Number(it.unitCostPrice).toLocaleString('id-ID')}
                        </div>
                      </div>

                      <div style={{ fontWeight: '900', color: '#047857', fontFamily: 'monospace', fontSize: '13px' }}>
                        Rp {Number(it.subtotal || (it.qtySettled * it.unitCostPrice)).toLocaleString('id-ID')}
                      </div>
                    </div>
                  ))}
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
                onClick={() => setSelectedSettlement(null)}
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
