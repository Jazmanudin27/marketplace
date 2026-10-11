import React, { useState, useEffect } from 'react';
import { 
  FileText, 
  DollarSign, 
  TrendingUp, 
  Percent, 
  Store, 
  Calendar, 
  RotateCcw, 
  Receipt, 
  Layers, 
  RefreshCw, 
  Filter,
  ChevronRight,
  PackageCheck
} from 'lucide-react';
import { formatRupiah, getChannelBadge } from '../utils/formatters';
import { fetchLaporanPenjualan } from '../api/metricsApi';
import OrderCard from '../components/orders/OrderCard';

export default function LaporanPenjualanPage({ onSelectOrder }) {
  const [summary, setSummary] = useState({
    omset: 0,
    refund: 0,
    totalFee: 0,
    netReleased: 0,
    hpp: 0,
    margin: 0,
    marginPct: 0,
    totalOrders: 0,
    totalQty: 0
  });
  const [byStore, setByStore] = useState([]);
  const [byChannel, setByChannel] = useState([]);
  const [transactions, setTransactions] = useState([]);
  const [storeOptions, setStoreOptions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  // Date Filters
  const [dateRangePreset, setDateRangePreset] = useState('month'); // today, 7d, month
  const [storeFilter, setStoreFilter] = useState('');
  const [activeTab, setActiveTab] = useState('stores'); // 'stores', 'channels', 'transactions'

  const getDateParams = (preset) => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

    if (preset === 'today') {
      return { dateFrom: todayStr, dateTo: todayStr };
    } else if (preset === '7d') {
      const past7 = new Date();
      past7.setDate(past7.getDate() - 7);
      return { 
        dateFrom: `${past7.getFullYear()}-${pad(past7.getMonth() + 1)}-${pad(past7.getDate())}`, 
        dateTo: todayStr 
      };
    } else {
      // Month
      return { 
        dateFrom: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-01`, 
        dateTo: todayStr 
      };
    }
  };

  const loadReport = async (preset = dateRangePreset, sId = storeFilter) => {
    setIsLoading(true);
    try {
      const { dateFrom, dateTo } = getDateParams(preset);
      const res = await fetchLaporanPenjualan({ dateFrom, dateTo, storeId: sId });
      if (res && res.success) {
        setSummary(res.summary || {});
        setByStore(res.byStore || []);
        setByChannel(res.byChannel || []);
        setTransactions(res.transactions || []);
        if (res.filters?.stores) {
          setStoreOptions(res.filters.stores);
        }
      }
    } catch (err) {
      console.error('Failed to load sales report:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadReport(dateRangePreset, storeFilter);
  }, [dateRangePreset, storeFilter]);

  return (
    <div className="mobile-body-content" style={{ paddingBottom: '90px' }}>
      {/* Filter Presets Bar */}
      <div style={{
        display: 'flex',
        alignItems: 'center',
        gap: '6px',
        marginBottom: '12px',
        overflowX: 'auto',
        paddingBottom: '2px'
      }}>
        {[
          { id: 'today', label: 'Hari Ini' },
          { id: '7d', label: '7 Hari Terakhir' },
          { id: 'month', label: 'Bulan Ini' },
        ].map((p) => (
          <button
            key={p.id}
            onClick={() => setDateRangePreset(p.id)}
            style={{
              padding: '6px 12px',
              borderRadius: '999px',
              border: dateRangePreset === p.id ? 'none' : '1px solid #e2e8f0',
              background: dateRangePreset === p.id ? '#2563eb' : '#ffffff',
              color: dateRangePreset === p.id ? '#ffffff' : '#64748b',
              fontSize: '11px',
              fontWeight: '700',
              whiteSpace: 'nowrap',
              cursor: 'pointer'
            }}
          >
            {p.label}
          </button>
        ))}

        {/* Store Dropdown */}
        {storeOptions.length > 0 && (
          <select
            value={storeFilter}
            onChange={(e) => setStoreFilter(e.target.value)}
            style={{
              padding: '6px 10px',
              borderRadius: '999px',
              border: '1px solid #e2e8f0',
              background: '#ffffff',
              fontSize: '11px',
              fontWeight: '600',
              color: '#334155',
              outline: 'none',
              cursor: 'pointer',
              marginLeft: 'auto'
            }}
          >
            <option value="">Semua Toko</option>
            {storeOptions.map(st => (
              <option key={st.id} value={st.id}>{st.name}</option>
            ))}
          </select>
        )}
      </div>

      {/* Main KPI Hero Card */}
      <div style={{
        background: 'linear-gradient(135deg, #1e1b4b 0%, #312e81 100%)',
        borderRadius: '20px',
        padding: '18px 16px',
        color: '#ffffff',
        boxShadow: '0 8px 24px -4px rgba(49, 46, 129, 0.4)',
        marginBottom: '16px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
          <div>
            <span style={{ fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.6px', opacity: 0.8, fontWeight: '700' }}>
              Margin Bersih (Laba Penjualan)
            </span>
            <div style={{ display: 'flex', alignItems: 'baseline', gap: '8px', marginTop: '4px' }}>
              <h2 style={{ fontSize: '24px', fontWeight: '800', letterSpacing: '-0.5px' }}>
                {formatRupiah(summary.margin || 0)}
              </h2>
              <span style={{
                background: 'rgba(52, 211, 153, 0.25)',
                color: '#34d399',
                padding: '2px 8px',
                borderRadius: '999px',
                fontSize: '11px',
                fontWeight: '800'
              }}>
                {summary.marginPct || 0}%
              </span>
            </div>
          </div>

          <button
            onClick={() => loadReport()}
            style={{
              background: 'rgba(255, 255, 255, 0.15)',
              border: 'none',
              borderRadius: '10px',
              padding: '6px 10px',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              gap: '4px',
              fontSize: '11px',
              fontWeight: '700',
              cursor: 'pointer'
            }}
          >
            <RefreshCw size={12} className={isLoading ? 'animate-spin' : ''} />
            <span>Sync</span>
          </button>
        </div>

        {/* 4 Financial Sub-Blocks */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(2, 1fr)',
          gap: '8px',
          marginTop: '16px',
          paddingTop: '14px',
          borderTop: '1px solid rgba(255, 255, 255, 0.15)'
        }}>
          <div style={{ background: 'rgba(255, 255, 255, 0.08)', borderRadius: '12px', padding: '9px 12px' }}>
            <span style={{ fontSize: '10.5px', opacity: 0.8 }}>Omset Kotor:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '800', marginTop: '2px' }}>
              {formatRupiah(summary.omset || 0)}
            </div>
            <span style={{ fontSize: '9.5px', opacity: 0.7 }}>{summary.totalOrders || 0} order • {summary.totalQty || 0} pcs</span>
          </div>

          <div style={{ background: 'rgba(255, 255, 255, 0.08)', borderRadius: '12px', padding: '9px 12px' }}>
            <span style={{ fontSize: '10.5px', opacity: 0.8 }}>Dilepas Bersih:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '800', color: '#6ee7b7', marginTop: '2px' }}>
              {formatRupiah(summary.netReleased || 0)}
            </div>
            <span style={{ fontSize: '9.5px', opacity: 0.7 }}>Dana cair marketplace</span>
          </div>

          <div style={{ background: 'rgba(255, 255, 255, 0.08)', borderRadius: '12px', padding: '9px 12px' }}>
            <span style={{ fontSize: '10.5px', opacity: 0.8 }}>Fee Marketplace:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '800', color: '#fca5a5', marginTop: '2px' }}>
              - {formatRupiah(summary.totalFee || 0)}
            </div>
            <span style={{ fontSize: '9.5px', opacity: 0.7 }}>Biaya admin & promo</span>
          </div>

          <div style={{ background: 'rgba(255, 255, 255, 0.08)', borderRadius: '12px', padding: '9px 12px' }}>
            <span style={{ fontSize: '10.5px', opacity: 0.8 }}>Modal HPP:</span>
            <div style={{ fontSize: '13.5px', fontWeight: '800', color: '#fca5a5', marginTop: '2px' }}>
              - {formatRupiah(summary.hpp || 0)}
            </div>
            <span style={{ fontSize: '9.5px', opacity: 0.7 }}>Harga pokok barang</span>
          </div>
        </div>
      </div>

      {/* Segmented Sub-Tabs */}
      <div style={{
        display: 'flex',
        background: '#f1f5f9',
        padding: '3px',
        borderRadius: '12px',
        marginBottom: '14px'
      }}>
        {[
          { id: 'stores', label: `Per Toko (${byStore.length})` },
          { id: 'channels', label: `Per Channel (${byChannel.length})` },
          { id: 'transactions', label: `Detail Order (${transactions.length})` },
        ].map(t => (
          <button
            key={t.id}
            onClick={() => setActiveTab(t.id)}
            style={{
              flex: 1,
              padding: '7px 0',
              borderRadius: '9px',
              border: 'none',
              background: activeTab === t.id ? '#ffffff' : 'transparent',
              color: activeTab === t.id ? '#0f172a' : '#64748b',
              fontWeight: activeTab === t.id ? '800' : '600',
              fontSize: '11px',
              cursor: 'pointer',
              boxShadow: activeTab === t.id ? '0 1px 3px rgba(0,0,0,0.06)' : 'none'
            }}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Tab 1: Per Toko */}
      {activeTab === 'stores' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {byStore.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '16px', color: '#94a3b8', fontSize: '12px' }}>
              Belum ada data penjualan toko pada periode ini.
            </div>
          ) : (
            byStore.map((st, idx) => {
              const channelBadge = getChannelBadge(st.channelName);
              const pct = summary.omset > 0 ? Math.round((st.omset / summary.omset) * 100) : 0;
              return (
                <div 
                  key={idx}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #e2e8f0',
                    borderRadius: '14px',
                    padding: '12px 14px',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.02)'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <strong style={{ fontSize: '13px', color: '#0f172a' }}>{st.storeName}</strong>
                      <span className={`store-pill ${channelBadge.class}`}>{st.channelName}</span>
                    </div>
                    <span style={{ fontSize: '11px', fontWeight: '700', color: '#2563eb' }}>
                      {pct}% Omset
                    </span>
                  </div>

                  {/* Progress Bar */}
                  <div style={{ width: '100%', height: '5px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden', marginBottom: '10px' }}>
                    <div style={{ width: `${pct}%`, height: '100%', background: 'linear-gradient(90deg, #2563eb, #3b82f6)', borderRadius: '999px' }} />
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '6px', fontSize: '11px' }}>
                    <div>
                      <span style={{ color: '#64748b' }}>Omset:</span>
                      <div style={{ fontWeight: '700', color: '#0f172a' }}>{formatRupiah(st.omset)}</div>
                    </div>
                    <div>
                      <span style={{ color: '#64748b' }}>HPP:</span>
                      <div style={{ fontWeight: '700', color: '#dc2626' }}>{formatRupiah(st.hpp)}</div>
                    </div>
                    <div style={{ textAlign: 'right' }}>
                      <span style={{ color: '#64748b' }}>Margin:</span>
                      <div style={{ fontWeight: '800', color: '#059669' }}>{formatRupiah(st.margin)}</div>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      )}

      {/* Tab 2: Per Channel */}
      {activeTab === 'channels' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {byChannel.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '16px', color: '#94a3b8', fontSize: '12px' }}>
              Belum ada data channel pada periode ini.
            </div>
          ) : (
            byChannel.map((ch, idx) => {
              const badge = getChannelBadge(ch.channelName);
              const pct = summary.omset > 0 ? Math.round((ch.omset / summary.omset) * 100) : 0;
              return (
                <div 
                  key={idx}
                  style={{
                    background: '#ffffff',
                    border: '1px solid #e2e8f0',
                    borderRadius: '14px',
                    padding: '14px',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.02)'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                    <span className={`store-pill ${badge.class}`} style={{ fontSize: '12px' }}>
                      {ch.channelName}
                    </span>
                    <strong style={{ fontSize: '13px', color: '#0f172a' }}>
                      {formatRupiah(ch.omset)}
                    </strong>
                  </div>

                  <div style={{ width: '100%', height: '5px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden', marginBottom: '10px' }}>
                    <div style={{ width: `${pct}%`, height: '100%', background: 'linear-gradient(90deg, #10b981, #059669)', borderRadius: '999px' }} />
                  </div>

                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b' }}>
                    <span>{ch.count} transaksi • {ch.quantity} pcs</span>
                    <span>Margin: <strong style={{ color: '#059669' }}>{formatRupiah(ch.margin)}</strong></span>
                  </div>
                </div>
              );
            })
          )}
        </div>
      )}

      {/* Tab 3: Detail Transaksi Penjualan */}
      {activeTab === 'transactions' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          {transactions.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '30px', background: '#fff', borderRadius: '16px', color: '#94a3b8', fontSize: '12px' }}>
              Tidak ada riwayat transaksi ditemukan.
            </div>
          ) : (
            transactions.map((tx) => (
              <OrderCard 
                key={tx.id} 
                order={tx} 
                onClick={(ord) => onSelectOrder && onSelectOrder(ord)} 
              />
            ))
          )}
        </div>
      )}
    </div>
  );
}
