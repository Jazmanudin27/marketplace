import React, { useState, useEffect } from 'react';
import { Search, RotateCcw, Package, ShieldCheck, Clock, CheckCircle2, ChevronLeft, ChevronRight } from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../utils/formatters';
import { fetchReturMasuk } from '../api/metricsApi';

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

  useEffect(() => {
    let alive = true;
    (async () => {
      setIsLoading(true);
      const res = await fetchReturMasuk({ search, storeId, status, isRestocked: qcTab, page });
      if (!alive) return;
      if (res?.success) {
        setStats(res.stats || {});
        setData(res.data || []);
        setStores(res.filters?.stores || []);
        setStatuses(res.filters?.statuses || []);
        setPagination(res.pagination || { page: 1, lastPage: 1, total: 0 });
      }
      setIsLoading(false);
    })();
    return () => { alive = false; };
  }, [search, storeId, status, qcTab, page]);

  useEffect(() => {
    const t = setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 400);
    return () => clearTimeout(t);
  }, [searchInput]);

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

                {/* Footer */}
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
                      background: r.isRestocked ? '#ecfdf5' : '#f5f3ff',
                      color: r.isRestocked ? '#059669' : '#7c3aed'
                    }}>
                      <ShieldCheck size={11} />
                      {r.isRestocked ? `QC ${r.inspectionStatus === 'GOOD' ? 'Layak Jual' : (r.inspectionStatus || 'Selesai')}` : 'Belum QC'}
                    </span>
                  </div>
                </div>

                <div style={{ fontSize: '10px', color: '#94a3b8', marginTop: '6px' }}>
                  {r.dateFormatted} • Pembeli: {r.buyerName}
                </div>
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
    </div>
  );
}
