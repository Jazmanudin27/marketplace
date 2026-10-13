import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X, Plus, Trash2, Save, Box, Info, AlertTriangle, Loader2 } from 'lucide-react';
import { fetchConsignmentResources } from '../../api/supplierConsignmentApi';
import { showToast, showAlert } from '../../utils/alerts';

export default function SupplierTitipanFormModal({
  initialData = null,
  currentSupplierUser = null,
  onClose,
  onSaved
}) {
  const isEditing = Boolean(initialData && initialData.id);

  const [isLoadingMeta, setIsLoadingMeta] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [availableProducts, setAvailableProducts] = useState([]);
  const [availableSuppliers, setAvailableSuppliers] = useState([]);

  // Form State
  const [refNumber, setRefNumber] = useState(initialData?.referenceNumber || initialData?.reference_number || '');
  const [supplierId, setSupplierId] = useState(() => {
    if (initialData?.supplierId || initialData?.supplier_id) {
      return String(initialData.supplierId || initialData.supplier_id);
    }
    return '';
  });
  const [consignmentDate, setConsignmentDate] = useState(() => {
    if (initialData?.consignmentDate) return initialData.consignmentDate.slice(0, 10);
    return new Date().toISOString().slice(0, 10);
  });
  const [notes, setNotes] = useState(initialData?.notes || '');

  // Dynamic Items
  const [items, setItems] = useState(() => {
    if (initialData?.items && initialData.items.length > 0) {
      return initialData.items.map(it => ({
        id: it.id || Date.now() + Math.random(),
        master_product_id: String(it.masterProductId || it.master_product_id || ''),
        product_name: it.productName || it.product_name || '',
        sku: it.sku || '',
        qty_received: it.qtyReceived || it.qty_received || 10,
        unit_cost_price: it.unitCostPrice || it.unit_cost_price || 0,
        unit_selling_price: it.unitSellingPrice || it.unit_selling_price || 0,
        notes: it.notes || ''
      }));
    }
    return [
      {
        id: Date.now(),
        master_product_id: '',
        product_name: '',
        sku: '',
        qty_received: 50,
        unit_cost_price: 0,
        unit_selling_price: 0,
        notes: ''
      }
    ];
  });

  // Load Resources on Mount
  useEffect(() => {
    async function loadMeta() {
      setIsLoadingMeta(true);
      try {
        const res = await fetchConsignmentResources();
        if (res) {
          if (res.products) setAvailableProducts(res.products);
          if (res.suppliers) setAvailableSuppliers(res.suppliers);
          if (!refNumber && res.autoRefNumber) setRefNumber(res.autoRefNumber);

          // If logged in as supplier, auto-match supplierId if exists
          if (!supplierId) {
            if (currentSupplierUser?.supplier_id) {
              setSupplierId(String(currentSupplierUser.supplier_id));
            } else if (res.suppliers?.length > 0) {
              // Match by name or default to first
              const matched = res.suppliers.find(s => 
                s.name.toLowerCase().includes((currentSupplierUser?.name || '').toLowerCase())
              );
              setSupplierId(String(matched ? matched.id : res.suppliers[0].id));
            }
          }
        }
      } catch (err) {
        console.warn('Failed loading consignment resources:', err);
      } finally {
        setIsLoadingMeta(false);
      }
    }
    loadMeta();
  }, []);

  // Handle Product Change on Row
  const handleProductChange = (index, prodId) => {
    const selectedProd = availableProducts.find(p => String(p.id) === String(prodId));
    setItems(prev => {
      const updated = [...prev];
      updated[index].master_product_id = prodId;
      if (selectedProd) {
        updated[index].product_name = selectedProd.name;
        updated[index].sku = selectedProd.sku;
        if (!updated[index].unit_cost_price || updated[index].unit_cost_price === 0) {
          updated[index].unit_cost_price = selectedProd.costPrice || 0;
        }
        if (!updated[index].unit_selling_price || updated[index].unit_selling_price === 0) {
          updated[index].unit_selling_price = selectedProd.price || 0;
        }
      }
      return updated;
    });
  };

  // Add Item Row
  const handleAddRow = () => {
    setItems(prev => [
      ...prev,
      {
        id: Date.now() + Math.random(),
        master_product_id: '',
        product_name: '',
        sku: '',
        qty_received: 20,
        unit_cost_price: 0,
        unit_selling_price: 0,
        notes: ''
      }
    ]);
  };

  // Remove Item Row
  const handleRemoveRow = (index) => {
    if (items.length <= 1) {
      showToast('Minimal harus ada 1 barang titipan.', 'warning');
      return;
    }
    setItems(prev => prev.filter((_, idx) => idx !== index));
  };

  // Realtime Live Calculations
  const totalQty = items.reduce((acc, it) => acc + (parseInt(it.qty_received, 10) || 0), 0);
  const totalHpp = items.reduce((acc, it) => {
    const q = parseInt(it.qty_received, 10) || 0;
    const c = parseFloat(it.unit_cost_price) || 0;
    return acc + (q * c);
  }, 0);
  const totalSelling = items.reduce((acc, it) => {
    const q = parseInt(it.qty_received, 10) || 0;
    const s = parseFloat(it.unit_selling_price) || 0;
    return acc + (q * s);
  }, 0);
  const totalProfit = Math.max(0, totalSelling - totalHpp);

  // Submit Handler
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!supplierId) {
      showAlert({ icon: 'warning', title: 'Pilih Supplier', text: 'Supplier penitip barang wajib dipilih.' });
      return;
    }
    if (!consignmentDate) {
      showAlert({ icon: 'warning', title: 'Tanggal Kosong', text: 'Tanggal penerimaan wajib ditentukan.' });
      return;
    }

    // Validasi item
    for (let i = 0; i < items.length; i++) {
      const it = items[i];
      if (!it.master_product_id) {
        showAlert({ icon: 'warning', title: 'Barang Belum Dipilih', text: `Silakan pilih produk master pada baris ke-${i + 1}.` });
        return;
      }
      if (!it.qty_received || it.qty_received <= 0) {
        showAlert({ icon: 'warning', title: 'Qty Kurang', text: `Kuantitas baris ke-${i + 1} minimal 1 PCS.` });
        return;
      }
    }

    setIsSubmitting(true);
    try {
      const payload = {
        supplier_id: parseInt(supplierId, 10),
        reference_number: refNumber,
        consignment_date: consignmentDate,
        notes: notes.trim(),
        items: items.map(it => ({
          master_product_id: parseInt(it.master_product_id, 10),
          qty_received: parseInt(it.qty_received, 10),
          unit_cost_price: parseFloat(it.unit_cost_price || 0),
          unit_selling_price: parseFloat(it.unit_selling_price || 0),
          notes: it.notes || null,
          product_name: it.product_name,
          sku: it.sku
        }))
      };

      await onSaved(payload, isEditing ? initialData.id : null);
    } catch (err) {
      console.error('Submit consignment error:', err);
      showAlert({ icon: 'error', title: 'Gagal Menyimpan', text: err.message || 'Terjadi kesalahan sistem.' });
    } finally {
      setIsSubmitting(false);
    }
  };

  const modalContent = (
    <div className="modal-overlay" style={{
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
        maxHeight: '94vh',
        background: '#ffffff',
        borderTopLeftRadius: '28px',
        borderTopRightRadius: '28px',
        overflow: 'hidden',
        display: 'flex',
        flexDirection: 'column',
        boxShadow: '0 -10px 40px rgba(0, 0, 0, 0.5)'
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
              background: 'linear-gradient(135deg, #f59e0b, #d97706)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: '#ffffff'
            }}>
              <Box size={20} />
            </div>
            <div>
              <div style={{ fontSize: '15px', fontWeight: '800' }}>
                {isEditing ? 'Edit Penerimaan Titipan' : 'Catat Barang Titipan Baru'}
              </div>
              <div style={{ fontSize: '11px', color: '#94a3b8' }}>
                {refNumber || 'Nomor otomatis'}
              </div>
            </div>
          </div>

          <button
            onClick={onClose}
            disabled={isSubmitting}
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

        {/* Scrollable Form Body */}
        <form onSubmit={handleSubmit} style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column' }}>
          <div style={{ padding: '16px', display: 'flex', flexDirection: 'column', gap: '14px', background: '#f8fafc' }}>
            
            {/* Header Informasi */}
            <div style={{
              background: '#ffffff',
              borderRadius: '16px',
              padding: '14px',
              border: '1px solid #e2e8f0',
              display: 'flex',
              flexDirection: 'column',
              gap: '10px'
            }}>
              <div style={{ fontSize: '12px', fontWeight: '800', color: '#1e293b', textTransform: 'uppercase', letterSpacing: '0.4px', borderBottom: '1px solid #f1f5f9', paddingBottom: '6px' }}>
                Informasi Penerimaan Konsinyasi
              </div>

              {/* No Referensi & Tanggal */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div>
                  <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#475569', marginBottom: '4px' }}>
                    No. Referensi
                  </label>
                  <input
                    type="text"
                    value={refNumber}
                    onChange={(e) => setRefNumber(e.target.value)}
                    placeholder="KNS-..."
                    style={{
                      width: '100%',
                      padding: '8px 10px',
                      borderRadius: '10px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12px',
                      fontFamily: 'monospace',
                      fontWeight: '700',
                      background: '#f8fafc',
                      boxSizing: 'border-box'
                    }}
                  />
                </div>

                <div>
                  <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#475569', marginBottom: '4px' }}>
                    Tanggal Penerimaan *
                  </label>
                  <input
                    type="date"
                    value={consignmentDate}
                    onChange={(e) => setConsignmentDate(e.target.value)}
                    required
                    style={{
                      width: '100%',
                      padding: '8px 10px',
                      borderRadius: '10px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12px',
                      fontWeight: '600',
                      background: '#ffffff',
                      boxSizing: 'border-box'
                    }}
                  />
                </div>
              </div>

              {/* Supplier Selection */}
              <div>
                <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#475569', marginBottom: '4px' }}>
                  Supplier Penitip Barang *
                </label>
                <select
                  value={supplierId}
                  onChange={(e) => setSupplierId(e.target.value)}
                  required
                  style={{
                    width: '100%',
                    padding: '8px 10px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '12.5px',
                    fontWeight: '600',
                    background: '#ffffff',
                    boxSizing: 'border-box'
                  }}
                >
                  <option value="">-- Pilih Supplier --</option>
                  {availableSuppliers.map(s => (
                    <option key={s.id} value={s.id}>
                      {s.name} {s.phone ? `(${s.phone})` : ''}
                    </option>
                  ))}
                </select>
              </div>

              {/* Catatan */}
              <div>
                <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#475569', marginBottom: '4px' }}>
                  Catatan / Keterangan Titipan
                </label>
                <textarea
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  placeholder="Contoh: Titipan Gamis Rayon 100 pcs untuk Toko Shopee..."
                  rows={2}
                  style={{
                    width: '100%',
                    padding: '8px 10px',
                    borderRadius: '10px',
                    border: '1px solid #cbd5e1',
                    fontSize: '12px',
                    background: '#ffffff',
                    resize: 'none',
                    boxSizing: 'border-box'
                  }}
                />
              </div>
            </div>

            {/* Dynamic Items Section */}
            <div style={{
              background: '#ffffff',
              borderRadius: '16px',
              padding: '14px',
              border: '1px solid #e2e8f0',
              display: 'flex',
              flexDirection: 'column',
              gap: '12px'
            }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #f1f5f9', paddingBottom: '6px' }}>
                <div style={{ fontSize: '12px', fontWeight: '800', color: '#1e293b', textTransform: 'uppercase', letterSpacing: '0.4px' }}>
                  Rincian Barang Titipan ({items.length})
                </div>
                <button
                  type="button"
                  onClick={handleAddRow}
                  style={{
                    background: 'linear-gradient(135deg, #f59e0b, #d97706)',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '8px',
                    padding: '5px 10px',
                    fontSize: '11px',
                    fontWeight: '700',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                    cursor: 'pointer'
                  }}
                >
                  <Plus size={14} />
                  <span>Tambah Baris</span>
                </button>
              </div>

              {items.map((row, idx) => {
                const subHpp = (parseInt(row.qty_received, 10) || 0) * (parseFloat(row.unit_cost_price) || 0);

                return (
                  <div
                    key={row.id || idx}
                    style={{
                      background: '#f8fafc',
                      borderRadius: '14px',
                      border: '1px solid #e2e8f0',
                      padding: '12px',
                      position: 'relative'
                    }}
                  >
                    {/* Row Header */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                      <span style={{ fontSize: '11px', fontWeight: '800', color: '#f59e0b', background: '#fef3c7', padding: '2px 8px', borderRadius: '6px' }}>
                        Barang #{idx + 1}
                      </span>
                      {items.length > 1 && (
                        <button
                          type="button"
                          onClick={() => handleRemoveRow(idx)}
                          style={{
                            background: '#fee2e2',
                            color: '#ef4444',
                            border: 'none',
                            borderRadius: '6px',
                            padding: '3px 6px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center'
                          }}
                        >
                          <Trash2 size={13} />
                        </button>
                      )}
                    </div>

                    {/* Select Product */}
                    <div style={{ marginBottom: '8px' }}>
                      <label style={{ display: 'block', fontSize: '10.5px', fontWeight: '700', color: '#64748b', marginBottom: '3px' }}>
                        Pilih Produk Master *
                      </label>
                      <select
                        value={row.master_product_id}
                        onChange={(e) => handleProductChange(idx, e.target.value)}
                        required
                        style={{
                          width: '100%',
                          padding: '7px 9px',
                          borderRadius: '8px',
                          border: '1px solid #cbd5e1',
                          fontSize: '12px',
                          fontWeight: '600',
                          background: '#ffffff',
                          boxSizing: 'border-box'
                        }}
                      >
                        <option value="">-- Pilih Barang di Database --</option>
                        {availableProducts.map(p => (
                          <option key={p.id} value={p.id}>
                            [{p.sku}] {p.name} (Stok: {p.stock})
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* Inputs: Qty, HPP, Selling Price */}
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.2fr 1.2fr', gap: '6px', marginBottom: '6px' }}>
                      <div>
                        <label style={{ display: 'block', fontSize: '10px', fontWeight: '700', color: '#64748b', marginBottom: '2px' }}>
                          Qty Titip *
                        </label>
                        <input
                          type="number"
                          min="1"
                          value={row.qty_received}
                          onChange={(e) => {
                            const val = e.target.value;
                            setItems(prev => {
                              const u = [...prev];
                              u[idx].qty_received = val;
                              return u;
                            });
                          }}
                          required
                          style={{
                            width: '100%',
                            padding: '6px 8px',
                            borderRadius: '8px',
                            border: '1px solid #cbd5e1',
                            fontSize: '12px',
                            fontWeight: '700',
                            textAlign: 'center',
                            background: '#ffffff',
                            boxSizing: 'border-box'
                          }}
                        />
                      </div>

                      <div>
                        <label style={{ display: 'block', fontSize: '10px', fontWeight: '700', color: '#64748b', marginBottom: '2px' }}>
                          Harga Titip (HPP) *
                        </label>
                        <input
                          type="number"
                          min="0"
                          step="100"
                          value={row.unit_cost_price}
                          onChange={(e) => {
                            const val = e.target.value;
                            setItems(prev => {
                              const u = [...prev];
                              u[idx].unit_cost_price = val;
                              return u;
                            });
                          }}
                          required
                          style={{
                            width: '100%',
                            padding: '6px 8px',
                            borderRadius: '8px',
                            border: '1px solid #cbd5e1',
                            fontSize: '12px',
                            fontWeight: '700',
                            textAlign: 'right',
                            fontFamily: 'monospace',
                            background: '#ffffff',
                            boxSizing: 'border-box'
                          }}
                        />
                      </div>

                      <div>
                        <label style={{ display: 'block', fontSize: '10px', fontWeight: '700', color: '#64748b', marginBottom: '2px' }}>
                          Harga Jual Toko
                        </label>
                        <input
                          type="number"
                          min="0"
                          step="100"
                          value={row.unit_selling_price}
                          onChange={(e) => {
                            const val = e.target.value;
                            setItems(prev => {
                              const u = [...prev];
                              u[idx].unit_selling_price = val;
                              return u;
                            });
                          }}
                          style={{
                            width: '100%',
                            padding: '6px 8px',
                            borderRadius: '8px',
                            border: '1px solid #cbd5e1',
                            fontSize: '12px',
                            fontWeight: '700',
                            textAlign: 'right',
                            fontFamily: 'monospace',
                            background: '#ffffff',
                            boxSizing: 'border-box'
                          }}
                        />
                      </div>
                    </div>

                    {/* Subtotal Display */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '11px', paddingTop: '4px', borderTop: '1px dashed #e2e8f0' }}>
                      <span style={{ color: '#64748b' }}>Subtotal Modal HPP:</span>
                      <strong style={{ color: '#1e293b', fontFamily: 'monospace' }}>
                        Rp {Number(subHpp).toLocaleString('id-ID')}
                      </strong>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Total Summary Sticky Card */}
            <div style={{
              background: '#ffffff',
              borderRadius: '16px',
              border: '1px solid #fde68a',
              padding: '12px 14px',
              display: 'flex',
              flexDirection: 'column',
              gap: '6px'
            }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#64748b' }}>
                <span>Total Kuantitas:</span>
                <strong style={{ color: '#1e293b' }}>{totalQty} PCS</strong>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px' }}>
                <span style={{ color: '#64748b' }}>Total Nilai Modal HPP:</span>
                <strong style={{ color: '#d97706', fontFamily: 'monospace', fontSize: '14px' }}>
                  Rp {Number(totalHpp).toLocaleString('id-ID')}
                </strong>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', borderTop: '1px dashed #f1f5f9', paddingTop: '4px' }}>
                <span style={{ color: '#64748b' }}>Estimasi Profit Toko:</span>
                <strong style={{ color: '#10b981', fontFamily: 'monospace' }}>
                  +Rp {Number(totalProfit).toLocaleString('id-ID')}
                </strong>
              </div>
            </div>
          </div>

          {/* Footer Submit Buttons */}
          <div style={{
            padding: '14px 18px calc(16px + env(safe-area-inset-bottom, 12px))',
            background: '#ffffff',
            borderTop: '1px solid #e2e8f0',
            display: 'flex',
            gap: '10px'
          }}>
            <button
              type="button"
              onClick={onClose}
              disabled={isSubmitting}
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
              Batal
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              style={{
                flex: 2,
                padding: '12px',
                borderRadius: '14px',
                border: 'none',
                background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                color: '#ffffff',
                fontWeight: '700',
                fontSize: '13.5px',
                cursor: isSubmitting ? 'not-allowed' : 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '8px',
                boxShadow: '0 4px 14px rgba(245, 158, 11, 0.45)',
                opacity: isSubmitting ? 0.75 : 1
              }}
            >
              {isSubmitting ? (
                <>
                  <Loader2 size={16} style={{ animation: 'spin 1s linear infinite' }} />
                  <span>Menyimpan ke Gudang...</span>
                </>
              ) : (
                <>
                  <Save size={16} />
                  <span>{isEditing ? 'Simpan Perubahan' : 'Simpan Transaksi Titipan'}</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );

  return typeof document !== 'undefined' ? createPortal(modalContent, document.body) : modalContent;
}
