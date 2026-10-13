import React, { useState, useEffect, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { 
  Search, RotateCcw, Package, ShieldCheck, Clock, CheckCircle2, 
  ChevronLeft, ChevronRight, X, AlertCircle, Check, HelpCircle, 
  CheckCircle, RefreshCw 
} from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../utils/formatters';
import { fetchReturMasuk, submitReturQc } from '../api/metricsApi';

const STATUS_STYLE = (s) => {
  const v = String(s || '').toUpperCase();
  if (v.includes('REQUESTED')) return { bg: '#fef3c7', color: '#b45309', label: 'Diajukan' };
  if (v.includes('PROCESSING') || v.includes('ACCEPTED') || v.includes('JUDGING')) return { bg: '#dbeafe', color: '#1d4ed8', label: 'Diproses' };
  if (v.includes('REFUNDED') || v.includes('COMPLETED') || v.includes('SUCCESS') || v.includes('CLOSED')) return { bg: '#dcfce7', color: '#15803d', label: 'Selesai' };
  if (v.includes('CANCEL') || v.includes('REJECT')) return { bg: '#fee2e2', color: '#b91c1c', label: 'Ditolak/Batal' };
  return { bg: '#f1f5f9', color: '#475569', label: v || '-' };
};

export default function ReturMasukPage() {
  const [stats, setStats] = useState({ total: 0, pendingQc: 0, alreadyQc: 0, requested: 0, completed: 0, totalRefund: 0 });
  const [data, setData] = useState([]);
  const [stores, setStores] = useState([]);
  const [statuses, setStatuses] = useState([]);
  const [pagination, setPagination] = useState({ page: 1, lastPage: 1, total: 0 });
  const [isLoading, setIsLoading] = useState(true);

  const [search, setSearch] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [storeId, setStoreId] = useState('');
  const [status, setStatus] = useState('');
  const [qcTab, setQcTab] = useState(''); // '', '0', '1'
  const [page, setPage] = useState(1);

  // QC Modal State
  const [qcModalData, setQcModalData] = useState(null);
  const [qcItemsState, setQcItemsState] = useState({});
  const [qcNotes, setQcNotes] = useState('');
  const [isSubmittingQc, setIsSubmittingQc] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);

  const loadData = useCallback(async () => {
    setIsLoading(true);
    const res = await fetchReturMasuk({ search, storeId, status, isRestocked: qcTab, page });
    if (res?.success) {
      setStats(res.stats || {});
      setData(res.data || []);
      setStores(res.filters?.stores || []);
      setStatuses(res.filters?.statuses || []);
      setPagination(res.pagination || { page: 1, lastPage: 1, total: 0 });
    }
    setIsLoading(false);
  }, [search, storeId, status, qcTab, page]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  useEffect(() => {
    const t = setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const showToast = (text, type = 'success') => {
    setToastMessage({ text, type });
    setTimeout(() => setToastMessage(null), 3500);
  };

  const openQcModal = (retur) => {
    const initialItems = {};
    (retur.items || []).forEach((it, idx) => {
      const key = it.id || it.orderItemId || `item_${idx}`;
      initialItems[key] = {
        id: it.id,
        orderItemId: it.orderItemId,
        inspection_status: it.inspectionStatus === 'DEFECTIVE' ? 'DEFECTIVE' : 'GOOD',
        inspection_notes: it.inspectionNotes || '',
      };
    });
    setQcItemsState(initialItems);
    setQcNotes(retur.inspectionNotes || '');
    setQcModalData(retur);
  };

  const handleItemStatusChange = (itemKey, newStatus) => {
    setQcItemsState(prev => ({
      ...prev,
      [itemKey]: {
        ...(prev[itemKey] || {}),
        inspection_status: newStatus
      }
    }));
  };

  const handleItemNoteChange = (itemKey, note) => {
    setQcItemsState(prev => ({
      ...prev,
      [itemKey]: {
        ...(prev[itemKey] || {}),
        inspection_notes: note
      }
    }));
  };

  const handleSubmitQc = async () => {
    if (!qcModalData) return;
    setIsSubmittingQc(true);

    const itemsPayload = Object.values(qcItemsState).map(it => ({
      id: it.id,
      orderItemId: it.orderItemId,
      inspection_status: it.inspection_status || 'GOOD',
      inspection_notes: it.inspection_notes || '',
    }));

    const hasGood = itemsPayload.some(it => it.inspection_status === 'GOOD');
    const overallStatus = hasGood ? 'GOOD' : 'DEFECTIVE';

    const res = await submitReturQc(qcModalData.id, {
      items: itemsPayload,
      inspection_status: overallStatus,
      inspection_notes: qcNotes,
    });

    setIsSubmittingQc(false);
    if (res?.success) {
      showToast(res.message || 'Pemeriksaan QC berhasil disimpan.');
      setQcModalData(null);
      loadData();
    } else {
      showToast(res?.message || 'Gagal menyimpan QC.', 'error');
    }
  };

  const qcTabs = [
    { id: '', label: 'Semua', count: stats.total },
    { id: '0', label: 'Belum QC', count: stats.pendingQc },
    { id: '1', label: 'Sudah QC', count: stats.alreadyQc },
  ];

  const selectStyle = {
    flex: 1, minWidth: 0, padding: '8px 10px', fontSize: '12px', borderRadius: '10px',
    border: '1px solid #e2e8f0', background: '#fff', color: '#334155', outline: 'none'
  };

  return (
    <div className="mobile-body-content" style={{ paddingTop: '14px', paddingBottom: '90px' }}>
      {/* Toast Notification */}
      {toastMessage && (
        <div style={{
          position: 'fixed', top: '16px', left: '16px', right: '16px', zIndex: 99999,
          background: toastMessage.type === 'error' ? '#ef4444' : '#10b981',
          color: '#fff', padding: '12px 16px', borderRadius: '12px', fontSize: '12.5px',
          fontWeight: '600', boxShadow: '0 8px 24px rgba(0,0,0,0.18)',
          display: 'flex', alignItems: 'center', gap: '8px'
        }}>
          {toastMessage.type === 'error' ? <AlertCircle size={18} /> : <CheckCircle2 size={18} />}
          <span>{toastMessage.text}</span>
        </div>
      )}

      {/* KPI */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px', marginBottom: '12px' }}>
        {[
          { label: 'Total Retur', value: stats.total, icon: RotateCcw, color: '#dc2626', bg: '#fef2f2' },
          { label: 'Pengajuan Baru', value: stats.requested, icon: Clock, color: '#d97706', bg: '#fffbeb' },
          { label: 'Belum QC Gudang', value: stats.pendingQc, icon: Package, color: '#7c3aed', bg: '#f5f3ff' },
          { label: 'Selesai / Closed', value: stats.completed, icon: CheckCircle2, color: '#16a34a', bg: '#f0fdf4' },
        ].map((k) => {
          const Icon = k.icon;
          return (
            <div key={k.label} style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '14px', padding: '10px 12px', display: 'flex', alignItems: 'center', gap: '10px' }}>
              <div style={{ width: '34px', height: '34px', borderRadius: '10px', background: k.bg, color: k.color, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <Icon size={17} />
              </div>
              <div>
                <div style={{ fontSize: '10.5px', color: '#64748b' }}>{k.label}</div>
                <div style={{ fontSize: '16px', fontWeight: '800', color: '#0f172a' }}>{k.value || 0}</div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Search */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '8px', background: '#fff', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '9px 12px', marginBottom: '8px' }}>
        <Search size={15} color="#94a3b8" />
        <input
          type="text"
          placeholder="Cari No. Retur / Invoice..."
          value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          style={{ border: 'none', outline: 'none', background: 'transparent', width: '100%', fontSize: '12.5px' }}
        />
      </div>

      {/* Filter Toko & Status */}
      <div style={{ display: 'flex', gap: '8px', marginBottom: '10px' }}>
        <select value={storeId} onChange={(e) => { setStoreId(e.target.value); setPage(1); }} style={selectStyle}>
          <option value="">Semua Toko</option>
          {stores.map(s => <option key={s.id} value={s.id}>{s.name} ({s.channel || 'MP'})</option>)}
        </select>
        <select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} style={selectStyle}>
          <option value="">Semua Status</option>
          {statuses.map(s => <option key={s} value={s}>{s}</option>)}
        </select>
      </div>

      {/* QC Tabs */}
      <div style={{ display: 'flex', gap: '6px', marginBottom: '12px' }}>
        {qcTabs.map(t => {
          const active = qcTab === t.id;
          return (
            <button
              key={t.id || 'all'}
              onClick={() => { setQcTab(t.id); setPage(1); }}
              style={{
                padding: '6px 12px', borderRadius: '999px', fontSize: '11.5px', fontWeight: '700', cursor: 'pointer',
                border: active ? 'none' : '1px solid #e2e8f0',
                background: active ? '#dc2626' : '#fff',
                color: active ? '#fff' : '#475569'
              }}
            >
              {t.label} <span style={{ opacity: 0.85 }}>({t.count || 0})</span>
            </button>
          );
        })}
      </div>

      {/* List */}
      {isLoading ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {[1, 2, 3].map(i => <div key={i} style={{ background: '#fff', borderRadius: '16px', border: '1px solid #e2e8f0', height: '120px' }} />)}
        </div>
      ) : data.length === 0 ? (
        <div style={{ textAlign: 'center', padding: '40px 20px', background: '#fff', borderRadius: '16px', border: '1px solid #e2e8f0', color: '#94a3b8', fontSize: '12.5px' }}>
          <RotateCcw size={32} style={{ margin: '0 auto 8px' }} />
          Tidak ada data retur.
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {data.map(r => {
            const st = STATUS_STYLE(r.status);
            const badge = getChannelBadge(r.channelCode || r.channelName);
            const isGood = r.inspectionStatus === 'GOOD';

            return (
              <div key={r.id} style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: '16px', padding: '12px 14px', boxShadow: '0 2px 6px rgba(0,0,0,0.03)' }}>
                {/* Header */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '8px' }}>
                  <div style={{ minWidth: 0 }}>
                    <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#2563eb' }}>{r.returnSn}</div>
                    <div style={{ fontSize: '10.5px', color: '#64748b', marginTop: '1px' }}>
                      Invoice: <strong style={{ color: '#0f172a' }}>{r.invoiceNumber}</strong>
                    </div>
                  </div>
                  <span className={`store-pill ${badge.class}`}>{r.storeName}</span>
                </div>

                {/* Items */}
                <div style={{ marginTop: '10px', display: 'flex', flexDirection: 'column', gap: '6px' }}>
                  {(r.items || []).slice(0, 3).map((it, idx) => (
                    <div key={idx} style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                      {it.image ? (
                        <img src={it.image} alt="" style={{ width: '34px', height: '34px', borderRadius: '8px', objectFit: 'cover', border: '1px solid #f1f5f9' }} />
                      ) : (
                        <div style={{ width: '34px', height: '34px', borderRadius: '8px', background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                          <Package size={15} color="#94a3b8" />
                        </div>
                      )}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: '11.5px', fontWeight: '700', color: '#0f172a', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{it.name}</div>
                        <div style={{ fontSize: '10px', color: '#94a3b8' }}>{it.sku}{it.variant ? ` • ${it.variant}` : ''}</div>
                      </div>
                      <span style={{ fontSize: '11px', fontWeight: '800', color: '#dc2626' }}>x{it.qty}</span>
                    </div>
                  ))}
                  {(r.items || []).length > 3 && (
                    <span style={{ fontSize: '10.5px', color: '#64748b' }}>+{r.items.length - 3} item lainnya</span>
                  )}
                </div>

                {/* Reason */}
                {r.reason && (
                  <div style={{ marginTop: '8px', fontSize: '11px', color: '#b91c1c', background: '#fef2f2', padding: '6px 9px', borderRadius: '8px' }}>
                    Alasan: {r.reason}
                  </div>
                )}

                {/* Footer Info */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '10px', paddingTop: '8px', borderTop: '1px dashed #e2e8f0' }}>
                  <div>
                    <div style={{ fontSize: '10px', color: '#64748b' }}>Refund</div>
                    <div style={{ fontSize: '13px', fontWeight: '800', color: '#dc2626' }}>{formatRupiah(r.refundAmount)}</div>
                  </div>
                  <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' }}>
                    <span style={{ fontSize: '10px', fontWeight: '700', background: st.bg, color: st.color, padding: '2px 8px', borderRadius: '999px' }}>
                      {st.label}
                    </span>
                    <span style={{
                      fontSize: '10px', fontWeight: '700', padding: '2px 8px', borderRadius: '999px',
                      display: 'inline-flex', alignItems: 'center', gap: '3px',
                      background: r.isRestocked ? (isGood ? '#ecfdf5' : '#fef2f2') : '#f5f3ff',
                      color: r.isRestocked ? (isGood ? '#059669' : '#dc2626') : '#7c3aed'
                    }}>
                      <ShieldCheck size={11} />
                      {r.isRestocked ? (isGood ? 'QC Layak Jual (Restock)' : 'QC Cacat / Rusak') : 'Belum QC'}
                    </span>
                  </div>
                </div>

                <div style={{ fontSize: '10px', color: '#94a3b8', marginTop: '6px', marginBottom: '8px' }}>
                  {r.dateFormatted} • Pembeli: {r.buyerName}
                </div>

                {/* Tombol Terima Barang (QC) */}
                {!r.isRestocked ? (
                  <button
                    type="button"
                    onClick={() => openQcModal(r)}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      borderRadius: '10px',
                      background: 'linear-gradient(135deg, #059669, #047857)',
                      color: '#fff',
                      border: 'none',
                      fontSize: '12px',
                      fontWeight: '700',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      boxShadow: '0 2px 6px rgba(5,150,105,0.25)',
                      cursor: 'pointer'
                    }}
                  >
                    <CheckCircle size={15} /> Terima Barang (QC)
                  </button>
                ) : (
                  <button
                    type="button"
                    onClick={() => openQcModal(r)}
                    style={{
                      width: '100%',
                      padding: '7px 12px',
                      borderRadius: '10px',
                      background: '#f8fafc',
                      color: '#475569',
                      border: '1px solid #cbd5e1',
                      fontSize: '11.5px',
                      fontWeight: '600',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '6px',
                      cursor: 'pointer'
                    }}
                  >
                    <ShieldCheck size={14} color="#059669" /> Lihat / Edit Hasil QC
                  </button>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Pagination */}
      {pagination.lastPage > 1 && (
        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '12px', marginTop: '14px' }}>
          <button
            disabled={page <= 1}
            onClick={() => setPage(p => Math.max(1, p - 1))}
            style={{ border: '1px solid #e2e8f0', background: '#fff', borderRadius: '10px', padding: '6px 10px', opacity: page <= 1 ? 0.4 : 1 }}
          >
            <ChevronLeft size={15} />
          </button>
          <span style={{ fontSize: '12px', color: '#475569' }}>
            Hal {pagination.page} / {pagination.lastPage}
          </span>
          <button
            disabled={page >= pagination.lastPage}
            onClick={() => setPage(p => Math.min(pagination.lastPage, p + 1))}
            style={{ border: '1px solid #e2e8f0', background: '#fff', borderRadius: '10px', padding: '6px 10px', opacity: page >= pagination.lastPage ? 0.4 : 1 }}
          >
            <ChevronRight size={15} />
          </button>
        </div>
      )}

      {/* ── MODAL TERIMA BARANG (QC INSPEKSI GUDANG) ── */}
      {qcModalData && typeof document !== 'undefined' && createPortal(
        <div className="modal-overlay">
          <div style={{
            background: '#fff', width: '100%', maxWidth: '520px',
            maxHeight: '88vh', borderTopLeftRadius: '22px', borderTopRightRadius: '22px',
            display: 'flex', flexDirection: 'column', overflow: 'hidden',
            boxShadow: '0 -10px 40px rgba(0, 0, 0, 0.35)', animation: 'slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1)'
          }}>
            {/* Modal Header */}
            <div style={{
              padding: '14px 16px', background: 'linear-gradient(135deg, #059669, #047857)',
              color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between'
            }}>
              <div>
                <div style={{ fontSize: '14px', fontWeight: '800', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <ShieldCheck size={18} /> Terima Barang & QC Gudang
                </div>
                <div style={{ fontSize: '11px', opacity: 0.9, marginTop: '2px' }}>
                  {qcModalData.returnSn} • {qcModalData.storeName}
                </div>
              </div>
              <button
                type="button"
                onClick={() => setQcModalData(null)}
                style={{ background: 'rgba(255,255,255,0.2)', border: 'none', color: '#fff', width: '28px', height: '28px', borderRadius: '999px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}
              >
                <X size={16} />
              </button>
            </div>

            {/* Modal Body */}
            <div style={{ padding: '14px 16px', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {/* Info Ringkas */}
              <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '12px', padding: '10px 12px', fontSize: '11.5px', color: '#475569' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                  <span>Invoice:</span>
                  <strong style={{ color: '#0f172a' }}>{qcModalData.invoiceNumber}</strong>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                  <span>Pembeli:</span>
                  <strong style={{ color: '#0f172a' }}>{qcModalData.buyerName}</strong>
                </div>
                {qcModalData.reason && (
                  <div style={{ marginTop: '6px', paddingTop: '6px', borderTop: '1px dashed #e2e8f0', color: '#dc2626' }}>
                    <strong>Alasan Retur:</strong> {qcModalData.reason}
                  </div>
                )}
              </div>

              {qcModalData.isRestocked && (
                <div style={{ background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: '10px', padding: '8px 12px', fontSize: '11px', color: '#166534', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <CheckCircle size={14} />
                  <span>Barang ini sudah pernah di-QC sebelumnya. Anda dapat memperbarui hasil pemeriksaan.</span>
                </div>
              )}

              {/* Items List */}
              <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>
                Pemeriksaan Kondisi Item ({qcModalData.items?.length || 0}):
              </div>

              {(qcModalData.items || []).map((it, idx) => {
                const key = it.id || it.orderItemId || `item_${idx}`;
                const cur = qcItemsState[key] || { inspection_status: 'GOOD', inspection_notes: '' };
                const isGood = cur.inspection_status === 'GOOD';

                return (
                  <div key={key} style={{
                    border: '1px solid #e2e8f0', borderRadius: '14px', padding: '12px',
                    background: '#fff', boxShadow: '0 1px 4px rgba(0,0,0,0.02)'
                  }}>
                    {/* Item Name */}
                    <div style={{ display: 'flex', gap: '8px', alignItems: 'center', marginBottom: '10px' }}>
                      {it.image ? (
                        <img src={it.image} alt="" style={{ width: '36px', height: '36px', borderRadius: '8px', objectFit: 'cover' }} />
                      ) : (
                        <div style={{ width: '36px', height: '36px', borderRadius: '8px', background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                          <Package size={16} color="#94a3b8" />
                        </div>
                      )}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: '12px', fontWeight: '700', color: '#0f172a' }}>{it.name}</div>
                        <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                          SKU: {it.sku} {it.variant ? `• ${it.variant}` : ''} • Qty: <strong style={{ color: '#dc2626' }}>{it.qty} pcs</strong>
                        </div>
                      </div>
                    </div>

                    {/* Status Options Toggle */}
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '8px' }}>
                      <button
                        type="button"
                        onClick={() => handleItemStatusChange(key, 'GOOD')}
                        style={{
                          padding: '8px 10px', borderRadius: '10px', fontSize: '11.5px', fontWeight: '700',
                          border: isGood ? '2px solid #059669' : '1px solid #e2e8f0',
                          background: isGood ? '#ecfdf5' : '#f8fafc',
                          color: isGood ? '#065f46' : '#64748b',
                          display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '5px',
                          cursor: 'pointer'
                        }}
                      >
                        <Check size={14} color={isGood ? '#059669' : '#94a3b8'} />
                        Layak Jual
                      </button>

                      <button
                        type="button"
                        onClick={() => handleItemStatusChange(key, 'DEFECTIVE')}
                        style={{
                          padding: '8px 10px', borderRadius: '10px', fontSize: '11.5px', fontWeight: '700',
                          border: !isGood ? '2px solid #dc2626' : '1px solid #e2e8f0',
                          background: !isGood ? '#fef2f2' : '#f8fafc',
                          color: !isGood ? '#991b1b' : '#64748b',
                          display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '5px',
                          cursor: 'pointer'
                        }}
                      >
                        <X size={14} color={!isGood ? '#dc2626' : '#94a3b8'} />
                        Cacat / Rusak
                      </button>
                    </div>

                    <div style={{ fontSize: '10.5px', color: isGood ? '#059669' : '#b91c1c', marginBottom: '6px', fontWeight: '600' }}>
                      {isGood ? '✓ Stok gudang master & marketplace otomatis bertambah' : '✗ Tidak menambah stok (barang rusak/cacat)'}
                    </div>

                    {/* Catatan Item */}
                    <input
                      type="text"
                      placeholder="Catatan fisik (mis: Segel utuh / ada noda)..."
                      value={cur.inspection_notes || ''}
                      onChange={(e) => handleItemNoteChange(key, e.target.value)}
                      style={{
                        width: '100%', padding: '7px 10px', borderRadius: '8px',
                        border: '1px solid #cbd5e1', fontSize: '11.5px', outline: 'none', background: '#fff'
                      }}
                    />
                  </div>
                );
              })}

              {/* Catatan Tambahan Global */}
              <div>
                <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  Catatan Umum Pemeriksaan:
                </label>
                <textarea
                  rows={2}
                  placeholder="Catatan rangkuman hasil penerimaan barang retur..."
                  value={qcNotes}
                  onChange={(e) => setQcNotes(e.target.value)}
                  style={{
                    width: '100%', padding: '8px 10px', borderRadius: '10px',
                    border: '1px solid #cbd5e1', fontSize: '12px', outline: 'none',
                    resize: 'none', fontFamily: 'inherit'
                  }}
                />
              </div>
            </div>

            {/* Modal Footer Actions */}
            <div style={{
              padding: '12px 16px max(24px, env(safe-area-inset-bottom, 24px))', borderTop: '1px solid #e2e8f0', background: '#f8fafc',
              display: 'flex', gap: '10px'
            }}>
              <button
                type="button"
                onClick={() => setQcModalData(null)}
                disabled={isSubmittingQc}
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
                onClick={handleSubmitQc}
                disabled={isSubmittingQc}
                style={{
                  flex: 2, padding: '10px', borderRadius: '10px', border: 'none',
                  background: isSubmittingQc ? '#94a3b8' : 'linear-gradient(135deg, #059669, #047857)',
                  color: '#fff', fontSize: '12.5px', fontWeight: '700', cursor: isSubmittingQc ? 'not-allowed' : 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px',
                  boxShadow: '0 2px 8px rgba(5,150,105,0.3)'
                }}
              >
                {isSubmittingQc ? (
                  <>
                    <RefreshCw size={15} className="spin-animate" /> Menyimpan...
                  </>
                ) : (
                  <>
                    <ShieldCheck size={16} /> Simpan Hasil QC
                  </>
                )}
              </button>
            </div>
          </div>
        </div>,
        document.body
      )}
    </div>
  );
}
