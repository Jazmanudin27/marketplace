import React, { useState, useEffect } from 'react';
import { 
  Store, 
  Wallet, 
  Clock, 
  ArrowUpRight, 
  ArrowDownLeft, 
  RefreshCw, 
  ChevronRight, 
  Search, 
  X,
  Layers,
  ExternalLink,
  ShieldCheck,
  AlertCircle
} from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../utils/formatters';
import { fetchSaldoMarketplace, fetchSaldoMarketplaceMutasi } from '../api/metricsApi';

export default function SaldoMarketplacePage() {
  const [data, setData] = useState({
    totalWalletBalance: 0,
    totalPendingBalance: 0,
    totalPendingCount: 0,
    totalEstimated: 0,
    stores: []
  });
  const [isLoading, setIsLoading] = useState(true);
  const [selectedStore, setSelectedStore] = useState(null);
  const [mutasiList, setMutasiList] = useState([]);
  const [isMutasiLoading, setIsMutasiLoading] = useState(false);
  const [mutasiSearch, setMutasiSearch] = useState('');

  const loadData = async () => {
    setIsLoading(true);
    try {
      const res = await fetchSaldoMarketplace();
      if (res && res.data) {
        setData(res.data);
      }
    } catch (err) {
      console.error('Failed to load saldo marketplace:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleOpenMutasi = async (store) => {
    setSelectedStore(store);
    setIsMutasiLoading(true);
    setMutasiList([]);
    try {
      const res = await fetchSaldoMarketplaceMutasi(store.id);
      if (res && Array.isArray(res.data)) {
        setMutasiList(res.data);
      } else if (res && Array.isArray(res.mutations)) {
        setMutasiList(res.mutations);
      } else {
        setMutasiList([]);
      }
    } catch (err) {
      console.error('Failed to load mutasi:', err);
      setMutasiList([]);
    } finally {
      setIsMutasiLoading(false);
    }
  };

  const filteredMutasi = (Array.isArray(mutasiList) ? mutasiList : []).filter(item => {
    if (!mutasiSearch) return true;
    const q = mutasiSearch.toLowerCase();
    return (
      (item.description || '').toLowerCase().includes(q) ||
      (item.transactionId || '').toLowerCase().includes(q) ||
      (item.type || '').toLowerCase().includes(q)
    );
  });

  return (
    <div className="mobile-body-content" style={{ paddingBottom: '90px' }}>
      {/* Top Header Card */}
      <div style={{
        background: 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
        borderRadius: '20px',
        padding: '18px 16px',
        color: '#ffffff',
        boxShadow: '0 8px 20px -4px rgba(2, 132, 199, 0.35)',
        marginBottom: '16px',
        position: 'relative',
        overflow: 'hidden'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
          <div>
            <span style={{ fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.6px', opacity: 0.85, fontWeight: '700' }}>
              Estimasi Total Saldo Marketplace
            </span>
            <h2 style={{ fontSize: '24px', fontWeight: '800', marginTop: '4px', letterSpacing: '-0.5px' }}>
              {formatRupiah(data.totalEstimated || 0)}
            </h2>
          </div>
          <button
            onClick={loadData}
            style={{
              background: 'rgba(255, 255, 255, 0.18)',
              border: 'none',
              borderRadius: '10px',
              padding: '6px 10px',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              gap: '5px',
              fontSize: '11px',
              fontWeight: '700',
              cursor: 'pointer'
            }}
          >
            <RefreshCw size={12} className={isLoading ? 'animate-spin' : ''} />
            <span>Refresh</span>
          </button>
        </div>

        {/* 2 Sub-metrics: Saldo Dompet & Saldo Escrow */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: '10px',
          marginTop: '16px',
          paddingTop: '14px',
          borderTop: '1px solid rgba(255, 255, 255, 0.18)'
        }}>
          <div style={{ background: 'rgba(255, 255, 255, 0.12)', borderRadius: '12px', padding: '10px 12px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '5px', fontSize: '11px', opacity: 0.9 }}>
              <Wallet size={13} />
              <span>Dapat Ditarik</span>
            </div>
            <div style={{ fontSize: '15px', fontWeight: '800', marginTop: '4px' }}>
              {formatRupiah(data.totalWalletBalance || 0)}
            </div>
          </div>

          <div style={{ background: 'rgba(255, 255, 255, 0.12)', borderRadius: '12px', padding: '10px 12px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '5px', fontSize: '11px', opacity: 0.9 }}>
              <Clock size={13} />
              <span>Tertahan (Escrow)</span>
            </div>
            <div style={{ fontSize: '15px', fontWeight: '800', marginTop: '4px' }}>
              {formatRupiah(data.totalPendingBalance || 0)}
            </div>
            <div style={{ fontSize: '9.5px', opacity: 0.8, marginTop: '2px' }}>
              {data.totalPendingCount || 0} order berjalan
            </div>
          </div>
        </div>
      </div>

      {/* Section Title */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
        <h3 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '6px' }}>
          <Store size={16} color="#0284c7" />
          <span>Daftar Saldo Per Toko ({data.stores?.length || 0})</span>
        </h3>
      </div>

      {/* Store Cards List */}
      {isLoading ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {[1, 2, 3].map(i => (
            <div key={i} style={{ background: '#fff', borderRadius: '16px', padding: '16px', border: '1px solid #e2e8f0', minHeight: '90px' }} />
          ))}
        </div>
      ) : (data.stores || []).length === 0 ? (
        <div style={{ textAlign: 'center', padding: '40px 20px', background: '#fff', borderRadius: '16px', border: '1px solid #e2e8f0' }}>
          <Store size={36} color="#94a3b8" style={{ margin: '0 auto 10px' }} />
          <p style={{ color: '#64748b', fontSize: '13px', fontWeight: '600' }}>Belum ada toko marketplace yang terhubung.</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {data.stores.map((st) => {
            const channelBadge = getChannelBadge(st.channelCode || st.channelName);
            return (
              <div 
                key={st.id}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '16px',
                  padding: '14px 16px',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.03)'
                }}
              >
                {/* Store Header */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <div style={{
                      width: '34px',
                      height: '34px',
                      borderRadius: '10px',
                      background: st.channelCode === 'tiktok' ? '#0f172a' : '#fff1f2',
                      color: st.channelCode === 'tiktok' ? '#ffffff' : '#ea580c',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontWeight: '800',
                      fontSize: '13px'
                    }}>
                      <Store size={18} />
                    </div>
                    <div>
                      <div style={{ fontSize: '13.5px', fontWeight: '800', color: '#0f172a' }}>
                        {st.name}
                      </div>
                      <span className={`store-pill ${channelBadge.class}`} style={{ marginTop: '2px' }}>
                        {st.channelName}
                      </span>
                    </div>
                  </div>

                  <span style={{
                    fontSize: '10px',
                    fontWeight: '700',
                    background: st.status === 'connected' ? '#ecfdf5' : '#fef2f2',
                    color: st.status === 'connected' ? '#059669' : '#dc2626',
                    padding: '3px 8px',
                    borderRadius: '999px'
                  }}>
                    {st.status === 'connected' ? '● Terhubung' : 'Terputus'}
                  </span>
                </div>

                {/* Balances Row */}
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: '1fr 1fr',
                  gap: '8px',
                  background: '#f8fafc',
                  padding: '10px 12px',
                  borderRadius: '12px',
                  marginBottom: '10px'
                }}>
                  <div>
                    <span style={{ fontSize: '10.5px', color: '#64748b' }}>Saldo Siap Tarik:</span>
                    <div style={{ fontSize: '13.5px', fontWeight: '800', color: '#059669', marginTop: '2px' }}>
                      {formatRupiah(st.currentBalance || 0)}
                    </div>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    <span style={{ fontSize: '10.5px', color: '#64748b' }}>Tertahan ({st.pendingCount || 0} order):</span>
                    <div style={{ fontSize: '13.5px', fontWeight: '800', color: '#d97706', marginTop: '2px' }}>
                      {formatRupiah(st.pendingBalance || 0)}
                    </div>
                  </div>
                </div>

                {/* Footer Action */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ fontSize: '10.5px', color: '#94a3b8' }}>
                    Sync: {st.lastSyncedAt || '-'}
                  </span>
                  <button
                    onClick={() => handleOpenMutasi(st)}
                    style={{
                      background: '#f1f5f9',
                      border: 'none',
                      borderRadius: '8px',
                      padding: '6px 12px',
                      color: '#0284c7',
                      fontSize: '11px',
                      fontWeight: '700',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px',
                      cursor: 'pointer'
                    }}
                  >
                    <span>Mutasi Dompet</span>
                    <ChevronRight size={13} />
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Mutasi Dompet Modal (Bottom Sheet) */}
      {selectedStore && (
        <div className="modal-overlay" onClick={() => setSelectedStore(null)}>
          <div 
            className="bottom-sheet-card" 
            onClick={(e) => e.stopPropagation()}
            style={{ maxHeight: '90vh', padding: '16px 18px 30px' }}
          >
            <div className="sheet-handle"></div>

            {/* Modal Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
              <div>
                <h3 style={{ fontSize: '16px', fontWeight: '800', color: '#0f172a' }}>
                  Mutasi Dompet Toko
                </h3>
                <p style={{ fontSize: '11.5px', color: '#64748b' }}>
                  {selectedStore.name} ({selectedStore.channelName})
                </p>
              </div>
              <button 
                onClick={() => setSelectedStore(null)}
                style={{ background: '#f1f5f9', border: 'none', borderRadius: '50%', width: '32px', height: '32px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              >
                <X size={16} color="#64748b" />
              </button>
            </div>

            {/* Current Balance Ribbon */}
            <div style={{
              background: '#f0fdf4',
              border: '1px solid #bbf7d0',
              borderRadius: '12px',
              padding: '10px 14px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              marginBottom: '12px'
            }}>
              <span style={{ fontSize: '12px', color: '#166534', fontWeight: '600' }}>Saldo Dompet Terkini:</span>
              <strong style={{ fontSize: '15px', color: '#15803d' }}>{formatRupiah(selectedStore.currentBalance || 0)}</strong>
            </div>

            {/* Search */}
            <div style={{
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '10px',
              padding: '8px 12px',
              marginBottom: '12px'
            }}>
              <Search size={14} color="#94a3b8" />
              <input 
                type="text"
                placeholder="Cari transaksi dompet..."
                value={mutasiSearch}
                onChange={(e) => setMutasiSearch(e.target.value)}
                style={{ border: 'none', background: 'transparent', outline: 'none', width: '100%', fontSize: '12px' }}
              />
            </div>

            {/* Mutasi List */}
            <div style={{ overflowY: 'auto', maxHeight: '55vh', display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {isMutasiLoading ? (
                <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
                  Memuat riwayat transaksi dompet...
                </div>
              ) : filteredMutasi.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: '12px' }}>
                  Belum ada mutasi dompet tersimpan untuk toko ini.
                </div>
              ) : (
                filteredMutasi.map((tx) => {
                  const isIn = tx.direction === 'in';
                  return (
                    <div 
                      key={tx.id}
                      style={{
                        background: '#ffffff',
                        border: '1px solid #f1f5f9',
                        borderRadius: '12px',
                        padding: '10px 12px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <div style={{
                          width: '32px',
                          height: '32px',
                          borderRadius: '8px',
                          background: isIn ? '#ecfdf5' : '#fef2f2',
                          color: isIn ? '#059669' : '#dc2626',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center'
                        }}>
                          {isIn ? <ArrowDownLeft size={16} /> : <ArrowUpRight size={16} />}
                        </div>
                        <div>
                          <div style={{ fontSize: '12.5px', fontWeight: '700', color: '#0f172a' }}>
                            {tx.description || tx.type}
                          </div>
                          <div style={{ fontSize: '10px', color: '#94a3b8', marginTop: '1px' }}>
                            {tx.dateFormatted || tx.transactionDate} • {tx.transactionId}
                          </div>
                        </div>
                      </div>

                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontSize: '13px', fontWeight: '800', color: isIn ? '#059669' : '#dc2626' }}>
                          {isIn ? '+' : '-'}{formatRupiah(tx.amount || 0)}
                        </div>
                        {tx.currentBalance !== null && (
                          <div style={{ fontSize: '9.5px', color: '#64748b', marginTop: '1px' }}>
                            Sisa: {formatRupiah(tx.currentBalance)}
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
