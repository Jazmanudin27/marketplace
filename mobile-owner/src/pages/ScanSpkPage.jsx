import React, { useState, useEffect, useRef } from 'react';
import { 
  QrCode, Search, FileText, CheckCircle2, Clock, AlertCircle, 
  ChevronRight, ArrowRight, User, Package, RefreshCw, X, ShieldCheck, 
  Check, Calendar, Flame
} from 'lucide-react';
import { lookupScanSpk, submitScanSpkPickup } from '../api/metricsApi';

export default function ScanSpkPage() {
  const [codeInput, setCodeInput] = useState('');
  const [activeCode, setActiveCode] = useState('');
  const [isSearching, setIsSearching] = useState(false);

  // Loaded State
  const [spkData, setSpkData] = useState(null);
  const [activeSpks, setActiveSpks] = useState([]);
  const [recentPickups, setRecentPickups] = useState([]);

  // Modal / Pickup State
  const [pickupModalItem, setPickupModalItem] = useState(null);
  const [pickupQty, setPickupQty] = useState(1);
  const [pickupReceiver, setPickupReceiver] = useState('');
  const [pickupNotes, setPickupNotes] = useState('');
  const [isSubmittingPickup, setIsSubmittingPickup] = useState(false);

  const [toast, setToast] = useState(null);
  const inputRef = useRef(null);

  const showToast = (text, type = 'success') => {
    setToast({ text, type });
    setTimeout(() => setToast(null), 3500);
  };

  // Initial load: fetch active SPKs & recent pickups
  const loadInitial = async () => {
    setIsSearching(true);
    const res = await lookupScanSpk('');
    setIsSearching(false);
    if (res?.success) {
      setActiveSpks(res.activeSpks || []);
      setRecentPickups(res.recentPickups || []);
    }
  };

  useEffect(() => {
    loadInitial();
  }, []);

  const handleLookup = async (codeToSearch) => {
    const code = (codeToSearch !== undefined ? codeToSearch : codeInput).trim();
    if (!code) {
      setSpkData(null);
      loadInitial();
      return;
    }

    setActiveCode(code);
    setIsSearching(true);
    const res = await lookupScanSpk(code);
    setIsSearching(false);

    if (res?.success && res.spk) {
      setSpkData(res);
      // Auto open pickup modal if item specifically matched
      if (res.matchedItemId) {
        const matched = (res.items || []).find(it => it.id === res.matchedItemId);
        if (matched && matched.sisaQty > 0) {
          openPickupModal(matched);
        }
      }
    } else {
      setSpkData(null);
      showToast(res?.message || `SPK dengan kode "${code}" tidak ditemukan.`, 'error');
    }
  };

  const openPickupModal = (item) => {
    setPickupModalItem(item);
    setPickupQty(Math.min(1, item.sisaQty || 1));
    setPickupReceiver(localStorage.getItem('owner_user_name') || 'Petugas Gudang');
    setPickupNotes(`Penerimaan hasil jahit SPK #${spkData?.spk?.noSpk || ''}`);
  };

  const handleExecutePickup = async () => {
    if (!pickupModalItem || !spkData?.spk) return;
    setIsSubmittingPickup(true);

    const res = await submitScanSpkPickup({
      spk_id: spkData.spk.id,
      item_id: pickupModalItem.id,
      qty: pickupQty,
      nama_pengambil: pickupReceiver,
      catatan: pickupNotes
    });

    setIsSubmittingPickup(false);
    if (res?.success) {
      showToast(res.message);
      setPickupModalItem(null);
      // Refresh current SPK
      handleLookup(spkData.spk.noSpk);
    } else {
      showToast(res?.message || 'Gagal menyimpan penerimaan SPK.', 'error');
    }
  };

  return (
    <div className="mobile-body-content" style={{ paddingTop: '14px', paddingBottom: '90px' }}>
      {/* Toast */}
      {toast && (
        <div style={{
          position: 'fixed', top: '16px', left: '16px', right: '16px', zIndex: 99999,
          background: toast.type === 'error' ? '#ef4444' : '#10b981',
          color: '#fff', padding: '12px 16px', borderRadius: '12px', fontSize: '12.5px',
          fontWeight: '600', boxShadow: '0 8px 24px rgba(0,0,0,0.18)',
          display: 'flex', alignItems: 'center', gap: '8px'
        }}>
          {toast.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle2 size={18} />}
          <span>{toast.text}</span>
        </div>
      )}

      {/* Viewfinder / Input Box */}
      <div style={{
        background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px',
        padding: '16px', marginBottom: '14px', boxShadow: '0 2px 10px rgba(0,0,0,0.03)'
      }}>
        <div style={{
          border: '2px dashed #cbd5e1', borderRadius: '14px', padding: '18px 12px',
          background: 'linear-gradient(180deg, #faf5ff, #f3e8ff)', textAlign: 'center',
          marginBottom: '14px'
        }}>
          <div style={{
            width: '46px', height: '46px', borderRadius: '12px', background: '#ede9fe',
            color: '#7c3aed', display: 'flex', alignItems: 'center', justifyContent: 'center',
            margin: '0 auto 8px'
          }}>
            <QrCode size={24} />
          </div>
          <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
            Pemindai Barcode / QR SPK Produksi
          </div>
          <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
            Scan QR label SPK, stiker karung, atau ketik No. SPK
          </div>
        </div>

        {/* Input & Search Form */}
        <form onSubmit={(e) => { e.preventDefault(); handleLookup(); }} style={{ display: 'flex', gap: '8px' }}>
          <div style={{
            flex: 1, display: 'flex', alignItems: 'center', gap: '8px',
            background: '#f8fafc', border: '1px solid #cbd5e1', borderRadius: '12px',
            padding: '8px 12px'
          }}>
            <Search size={16} color="#94a3b8" />
            <input
              ref={inputRef}
              type="text"
              placeholder="Scan QR / No. SPK (e.g. SPK-2026-001)..."
              value={codeInput}
              onChange={(e) => setCodeInput(e.target.value)}
              style={{
                border: 'none', outline: 'none', background: 'transparent',
                width: '100%', fontSize: '13px', fontWeight: '600', color: '#0f172a'
              }}
            />
          </div>
          <button
            type="submit"
            disabled={isSearching}
            style={{
              padding: '0 16px', borderRadius: '12px', border: 'none',
              background: 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
              color: '#fff', fontSize: '12.5px', fontWeight: '700',
              cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px'
            }}
          >
            {isSearching ? <RefreshCw size={14} className="spin-animate" /> : 'Cari'}
          </button>
        </form>
      </div>

      {/* SPK DETAIL VIEW */}
      {spkData?.spk ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {/* Card Header SPK */}
          <div style={{
            background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px',
            padding: '14px', boxShadow: '0 2px 8px rgba(0,0,0,0.03)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '8px' }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <span style={{ fontSize: '14px', fontWeight: '800', color: '#7c3aed' }}>
                    {spkData.spk.noSpk}
                  </span>
                  {spkData.spk.isUrgent && (
                    <span style={{
                      fontSize: '9.5px', fontWeight: '800', background: '#fef2f2', color: '#dc2626',
                      padding: '2px 6px', borderRadius: '4px', display: 'flex', alignItems: 'center', gap: '2px'
                    }}>
                      <Flame size={10} /> URGENT
                    </span>
                  )}
                </div>
                <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                  No. Produksi: <strong style={{ color: '#0f172a' }}>{spkData.spk.noProduksi}</strong>
                </div>
              </div>

              <span style={{
                fontSize: '10px', fontWeight: '800', background: '#ede9fe', color: '#6d28d9',
                padding: '3px 8px', borderRadius: '999px'
              }}>
                {spkData.spk.tahapSaatIni}
              </span>
            </div>

            {/* Client / Order Info */}
            <div style={{
              marginTop: '10px', paddingTop: '10px', borderTop: '1px solid #f1f5f9',
              display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', fontSize: '11px'
            }}>
              <div>
                <span style={{ color: '#94a3b8' }}>Pemesan:</span>
                <div style={{ fontWeight: '700', color: '#0f172a' }}>{spkData.spk.pemesan}</div>
                {spkData.spk.instansi !== '-' && (
                  <div style={{ fontSize: '10px', color: '#64748b' }}>{spkData.spk.instansi}</div>
                )}
              </div>
              <div>
                <span style={{ color: '#94a3b8' }}>Deadline:</span>
                <div style={{ fontWeight: '700', color: '#dc2626', display: 'flex', alignItems: 'center', gap: '3px' }}>
                  <Calendar size={11} /> {spkData.spk.deadlineFormatted}
                </div>
              </div>
            </div>

            {/* Progress Bar Penerimaan */}
            <div style={{ marginTop: '12px', paddingTop: '10px', borderTop: '1px dashed #e2e8f0' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', marginBottom: '4px' }}>
                <span style={{ color: '#64748b' }}>Progres Penerimaan Barang:</span>
                <strong style={{ color: spkData.progress.allCompleted ? '#059669' : '#7c3aed' }}>
                  {spkData.progress.totalDiambil} / {spkData.progress.totalTarget} pcs ({spkData.progress.percentComplete}%)
                </strong>
              </div>
              <div style={{ height: '7px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden' }}>
                <div style={{
                  height: '100%', width: `${spkData.progress.percentComplete}%`,
                  background: spkData.progress.allCompleted ? '#10b981' : 'linear-gradient(90deg, #8b5cf6, #7c3aed)',
                  transition: 'width 0.3s'
                }} />
              </div>
            </div>
          </div>

          {/* Items List */}
          <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
            <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
              Daftar Item SPK ({spkData.items.length}):
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              {spkData.items.map((it) => {
                const isComplete = it.isCompleted;
                const isSelected = spkData.matchedItemId === it.id;

                return (
                  <div
                    key={it.id}
                    style={{
                      border: isSelected ? '2px solid #8b5cf6' : '1px solid #e2e8f0',
                      borderRadius: '14px', padding: '12px', background: isSelected ? '#faf5ff' : '#fff',
                      boxShadow: '0 1px 4px rgba(0,0,0,0.02)'
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '8px' }}>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: '12.5px', fontWeight: '700', color: '#0f172a' }}>
                          {it.namaProduk}
                        </div>
                        <div style={{ fontSize: '11px', color: '#64748b', marginTop: '1px' }}>
                          Ukuran: <strong style={{ color: '#7c3aed' }}>{it.ukuran}</strong> • SKU: {it.sku}
                        </div>
                      </div>

                      <span style={{
                        fontSize: '10px', fontWeight: '800', padding: '2px 8px', borderRadius: '999px',
                        background: isComplete ? '#ecfdf5' : '#fffbeb',
                        color: isComplete ? '#059669' : '#d97706'
                      }}>
                        {isComplete ? 'Lengkap' : `Sisa ${it.sisaQty} pcs`}
                      </span>
                    </div>

                    {/* Quantity Stats Bar */}
                    <div style={{
                      display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '6px',
                      marginTop: '8px', paddingTop: '8px', borderTop: '1px solid #f1f5f9', textAlign: 'center', fontSize: '10.5px'
                    }}>
                      <div style={{ background: '#f8fafc', padding: '5px', borderRadius: '6px' }}>
                        <span style={{ color: '#64748b' }}>Target:</span> <strong>{it.quantity}</strong>
                      </div>
                      <div style={{ background: '#f8fafc', padding: '5px', borderRadius: '6px' }}>
                        <span style={{ color: '#059669' }}>Diterima:</span> <strong>{it.qtyDiambil}</strong>
                      </div>
                      <div style={{ background: '#f8fafc', padding: '5px', borderRadius: '6px' }}>
                        <span style={{ color: '#dc2626' }}>Sisa:</span> <strong>{it.sisaQty}</strong>
                      </div>
                    </div>

                    {/* Button Scan Terima */}
                    {!isComplete ? (
                      <button
                        type="button"
                        onClick={() => openPickupModal(it)}
                        style={{
                          marginTop: '8px', width: '100%', padding: '8px', borderRadius: '10px',
                          border: 'none', background: 'linear-gradient(135deg, #059669, #047857)',
                          color: '#fff', fontSize: '12px', fontWeight: '700', cursor: 'pointer',
                          display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '5px',
                          boxShadow: '0 2px 6px rgba(5,150,105,0.25)'
                        }}
                      >
                        <ShieldCheck size={14} /> Terima Barang Masuk Gudang (+in Stok)
                      </button>
                    ) : (
                      <div style={{
                        marginTop: '8px', padding: '6px', borderRadius: '8px', background: '#ecfdf5',
                        color: '#059669', fontSize: '11px', fontWeight: '700', textAlign: 'center',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '4px'
                      }}>
                        <Check size={13} /> Kuota item sudah diterima seluruhnya
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {/* Riwayat Penerimaan SPK Ini */}
          {spkData.recentPickups?.length > 0 && (
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
              <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                Riwayat Penerimaan SPK Ini ({spkData.recentPickups.length}):
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                {spkData.recentPickups.map((p) => (
                  <div
                    key={p.id}
                    style={{
                      padding: '8px 10px', borderRadius: '10px', background: '#f8fafc',
                      border: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between',
                      alignItems: 'center', fontSize: '11px'
                    }}
                  >
                    <div>
                      <div style={{ fontWeight: '700', color: '#0f172a' }}>
                        +{p.qty_diambil} pcs • {p.nama_produk} ({p.ukuran})
                      </div>
                      <div style={{ fontSize: '10px', color: '#64748b' }}>
                        Penerima: {p.nama_pengambil} • {p.dateFormatted}
                      </div>
                    </div>
                    <span style={{ fontSize: '10px', fontWeight: '700', color: '#059669', background: '#ecfdf5', padding: '2px 6px', borderRadius: '4px' }}>
                      Sukses
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Tombol Reset / Scan Lain */}
          <button
            type="button"
            onClick={() => { setSpkData(null); setCodeInput(''); loadInitial(); }}
            style={{
              padding: '11px', borderRadius: '12px', border: '1px solid #cbd5e1',
              background: '#fff', color: '#475569', fontSize: '12.5px', fontWeight: '700',
              cursor: 'pointer', textAlign: 'center'
            }}
          >
            ← Scan SPK Lainnya
          </button>
        </div>
      ) : (
        /* Default View: Active SPKs & Global Recent Pickups */
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {/* Active SPK List */}
          <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
            <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a', marginBottom: '10px' }}>
              📋 SPK Aktif Sedang Dikerjakan ({activeSpks.length}):
            </div>

            {activeSpks.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '20px', color: '#94a3b8', fontSize: '12px' }}>
                Tidak ada SPK aktif saat ini.
              </div>
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {activeSpks.map((s) => (
                  <div
                    key={s.id}
                    onClick={() => handleLookup(s.no_spk)}
                    style={{
                      padding: '10px 12px', borderRadius: '12px', background: '#f8fafc',
                      border: '1px solid #e2e8f0', cursor: 'pointer', display: 'flex',
                      justifyContent: 'space-between', alignItems: 'center'
                    }}
                  >
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <span style={{ fontSize: '12px', fontWeight: '800', color: '#7c3aed' }}>
                          {s.no_spk}
                        </span>
                        {s.is_urgent && (
                          <span style={{ fontSize: '9px', fontWeight: '800', background: '#fef2f2', color: '#dc2626', padding: '1px 5px', borderRadius: '4px' }}>
                            URGENT
                          </span>
                        )}
                      </div>
                      <div style={{ fontSize: '11px', color: '#0f172a', fontWeight: '600', marginTop: '2px' }}>
                        {s.pemesan} {s.instansi ? `• ${s.instansi}` : ''}
                      </div>
                      <div style={{ fontSize: '10px', color: '#64748b', marginTop: '1px' }}>
                        Target: {s.total_qty} pcs • Deadline: {s.deadlineFormatted}
                      </div>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px', color: '#94a3b8' }}>
                      <span style={{ fontSize: '10px', fontWeight: '700', color: '#6d28d9', background: '#ede9fe', padding: '2px 7px', borderRadius: '999px' }}>
                        {s.tahap_saat_ini}
                      </span>
                      <ChevronRight size={15} />
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Recent Global Pickups */}
          {recentPickups.length > 0 && (
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '14px' }}>
              <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a', marginBottom: '8px' }}>
                ⚡ Riwayat Penerimaan SPK Terakhir:
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                {recentPickups.map((p) => (
                  <div
                    key={p.id}
                    style={{
                      padding: '8px 10px', borderRadius: '10px', background: '#f8fafc',
                      border: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between',
                      alignItems: 'center', fontSize: '11px'
                    }}
                  >
                    <div>
                      <div style={{ fontWeight: '700', color: '#0f172a' }}>
                        {p.no_spk} • +{p.qty_diambil} pcs {p.nama_produk} ({p.ukuran})
                      </div>
                      <div style={{ fontSize: '10px', color: '#64748b' }}>
                        Penerima: {p.nama_pengambil} • {p.dateFormatted}
                      </div>
                    </div>
                    <span style={{ fontSize: '10px', fontWeight: '800', color: '#059669' }}>
                      +in Stok
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* ── MODAL TERIMA BARANG SPK ── */}
      {pickupModalItem && (
        <div style={{
          position: 'fixed', inset: 0, zIndex: 9999,
          background: 'rgba(15, 23, 42, 0.65)', backdropFilter: 'blur(3px)',
          display: 'flex', alignItems: 'flex-end', justifyContent: 'center'
        }}>
          <div style={{
            background: '#fff', width: '100%', maxWidth: '520px',
            borderTopLeftRadius: '22px', borderTopRightRadius: '22px',
            display: 'flex', flexDirection: 'column', overflow: 'hidden',
            boxShadow: '0 -8px 30px rgba(0,0,0,0.25)', animation: 'slideUp 0.25s ease'
          }}>
            {/* Header */}
            <div style={{
              padding: '14px 16px', background: 'linear-gradient(135deg, #059669, #047857)',
              color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between'
            }}>
              <div>
                <div style={{ fontSize: '14px', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <ShieldCheck size={18} /> Terima Barang Masuk Gudang
                </div>
                <div style={{ fontSize: '11px', opacity: 0.9, marginTop: '2px' }}>
                  SPK #{spkData?.spk?.noSpk} • {pickupModalItem.namaProduk} ({pickupModalItem.ukuran})
                </div>
              </div>
              <button
                type="button"
                onClick={() => setPickupModalItem(null)}
                style={{ background: 'rgba(255,255,255,0.2)', border: 'none', color: '#fff', width: '28px', height: '28px', borderRadius: '999px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
              >
                <X size={16} />
              </button>
            </div>

            {/* Body */}
            <div style={{ padding: '16px', display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div style={{ background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: '12px', padding: '10px 12px', fontSize: '11.5px', color: '#166534' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                  <span>Sisa Kuota Belum Diterima:</span>
                  <strong>{pickupModalItem.sisaQty} pcs</strong>
                </div>
                <div style={{ fontSize: '10.5px', color: '#059669' }}>
                  ✓ Stok master barang jadi dan marketplace akan otomatis bertambah saat disimpan.
                </div>
              </div>

              {/* Qty Input */}
              <div>
                <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  Jumlah Barang Diterima (Pcs):
                </label>
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                  <button
                    type="button"
                    onClick={() => setPickupQty(q => Math.max(1, q - 1))}
                    style={{ width: '38px', height: '38px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#fff', fontSize: '16px', fontWeight: '700', cursor: 'pointer' }}
                  >
                    -
                  </button>
                  <input
                    type="number"
                    max={pickupModalItem.sisaQty}
                    min={1}
                    value={pickupQty}
                    onChange={(e) => setPickupQty(Math.min(pickupModalItem.sisaQty, Math.max(1, parseInt(e.target.value || '1', 10))))}
                    style={{
                      flex: 1, height: '38px', textAlign: 'center', borderRadius: '10px',
                      border: '1px solid #cbd5e1', fontSize: '15px', fontWeight: '800', color: '#0f172a'
                    }}
                  />
                  <button
                    type="button"
                    onClick={() => setPickupQty(q => Math.min(pickupModalItem.sisaQty, q + 1))}
                    style={{ width: '38px', height: '38px', borderRadius: '10px', border: '1px solid #cbd5e1', background: '#fff', fontSize: '16px', fontWeight: '700', cursor: 'pointer' }}
                  >
                    +
                  </button>
                </div>
                <div style={{ display: 'flex', gap: '6px', marginTop: '6px' }}>
                  <button
                    type="button"
                    onClick={() => setPickupQty(pickupModalItem.sisaQty)}
                    style={{
                      padding: '4px 10px', borderRadius: '6px', border: '1px solid #bbf7d0',
                      background: '#ecfdf5', color: '#059669', fontSize: '10.5px', fontWeight: '700', cursor: 'pointer'
                    }}
                  >
                    Terima Semua ({pickupModalItem.sisaQty} pcs)
                  </button>
                </div>
              </div>

              {/* Penerima */}
              <div>
                <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  Nama Petugas Penerima Gudang:
                </label>
                <input
                  type="text"
                  placeholder="Nama petugas penerima..."
                  value={pickupReceiver}
                  onChange={(e) => setPickupReceiver(e.target.value)}
                  style={{
                    width: '100%', padding: '8px 10px', borderRadius: '10px',
                    border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none'
                  }}
                />
              </div>

              {/* Catatan */}
              <div>
                <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  Catatan Penerimaan (Opsional):
                </label>
                <input
                  type="text"
                  placeholder="Contoh: Jahitan rapi, kemasan karung 1..."
                  value={pickupNotes}
                  onChange={(e) => setPickupNotes(e.target.value)}
                  style={{
                    width: '100%', padding: '8px 10px', borderRadius: '10px',
                    border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none'
                  }}
                />
              </div>
            </div>

            {/* Actions */}
            <div style={{
              padding: '12px 16px', borderTop: '1px solid #e2e8f0', background: '#f8fafc',
              display: 'flex', gap: '10px'
            }}>
              <button
                type="button"
                onClick={() => setPickupModalItem(null)}
                disabled={isSubmittingPickup}
                style={{
                  flex: 1, padding: '10px', borderRadius: '10px', border: '1px solid #cbd5e1',
                  background: '#fff', color: '#475569', fontSize: '12.5px', fontWeight: '700',
                  cursor: 'pointer'
                }}
              >
                Batal
              </button>

              <button
                type="button"
                onClick={handleExecutePickup}
                disabled={isSubmittingPickup}
                style={{
                  flex: 2, padding: '10px', borderRadius: '10px', border: 'none',
                  background: isSubmittingPickup ? '#94a3b8' : 'linear-gradient(135deg, #059669, #047857)',
                  color: '#fff', fontSize: '12.5px', fontWeight: '700', cursor: isSubmittingPickup ? 'not-allowed' : 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px',
                  boxShadow: '0 2px 8px rgba(5,150,105,0.3)'
                }}
              >
                {isSubmittingPickup ? (
                  <>
                    <RefreshCw size={15} className="spin-animate" /> Menyimpan...
                  </>
                ) : (
                  <>
                    <Check size={16} /> Konfirmasi Terima Barang
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
