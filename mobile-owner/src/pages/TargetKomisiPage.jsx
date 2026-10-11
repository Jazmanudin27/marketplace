import React, { useState, useEffect } from 'react';
import { Target, Users, Award, TrendingUp, CheckCircle2, Store, Lock, Calendar, Filter, RefreshCw, ShoppingBag } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { fetchTargetKomisi } from '../api/metricsApi';

const MONTH_NAMES = [
  'Semua Bulan',
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function TargetKomisiPage({ initialMetrics }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedMonth, setSelectedMonth] = useState('');
  const [selectedYear, setSelectedYear] = useState('');

  const loadData = async (m = selectedMonth, y = selectedYear) => {
    setLoading(true);
    const res = await fetchTargetKomisi(m || null, y || null);
    if (res && res.success) {
      setData(res);
    }
    setLoading(false);
  };

  useEffect(() => {
    loadData(selectedMonth, selectedYear);
  }, [selectedMonth, selectedYear]);

  const summary = data?.summary || {
    totalTargetMargin: initialMetrics?.targetMonthlyMargin || 0,
    totalTargetQty: 0,
    totalActualMargin: initialMetrics?.actualMonthlyMargin || 0,
    totalActualQty: 0,
    totalReward: initialMetrics?.totalEarnedCommission || 0,
    progressPercent: initialMetrics?.targetProgressPercent || 0,
    totalTeams: 0,
    activeTeamsCount: 0,
    totalStoresLinked: 0,
    totalStoresCount: 0,
    availableYears: [new Date().getFullYear()],
  };

  const teams = data?.teams || [];
  const availableYears = summary.availableYears || [new Date().getFullYear()];

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      
      {/* Filter Card Compact */}
      <div style={{
        background: '#ffffff',
        borderRadius: '16px',
        padding: '12px 14px',
        border: '1px solid #e2e8f0',
        display: 'flex',
        flexDirection: 'column',
        gap: '10px',
        boxShadow: '0 2px 5px rgba(0,0,0,0.02)'
      }}>
        <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
          {/* Dropdown Bulan */}
          <div style={{ flex: 1 }}>
            <select
              value={selectedMonth}
              onChange={(e) => setSelectedMonth(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 10px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '11.5px',
                fontWeight: '600',
                color: '#1e293b',
                background: '#f8fafc',
                outline: 'none'
              }}
            >
              {MONTH_NAMES.map((name, idx) => (
                <option key={idx} value={idx === 0 ? '' : idx}>
                  {name}
                </option>
              ))}
            </select>
          </div>

          {/* Dropdown Tahun */}
          <div style={{ width: '100px' }}>
            <select
              value={selectedYear}
              onChange={(e) => setSelectedYear(e.target.value)}
              style={{
                width: '100%',
                padding: '7px 10px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '11.5px',
                fontWeight: '600',
                color: '#1e293b',
                background: '#f8fafc',
                outline: 'none'
              }}
            >
              <option value="">Semua Tahun</option>
              {availableYears.map((yr) => (
                <option key={yr} value={yr}>
                  {yr}
                </option>
              ))}
            </select>
          </div>

          {/* Refresh / Reset Button */}
          {(selectedMonth || selectedYear) ? (
            <button
              type="button"
              onClick={() => { setSelectedMonth(''); setSelectedYear(''); }}
              style={{
                border: 'none',
                background: '#fee2e2',
                color: '#ef4444',
                padding: '7px 10px',
                borderRadius: '10px',
                fontSize: '11.5px',
                fontWeight: '700',
                cursor: 'pointer'
              }}
              title="Reset Filter"
            >
              Reset
            </button>
          ) : (
            <button
              type="button"
              onClick={() => loadData()}
              style={{
                border: 'none',
                background: '#f1f5f9',
                color: '#475569',
                padding: '7px 10px',
                borderRadius: '10px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center'
              }}
              title="Refresh Data"
            >
              <RefreshCw size={14} className={loading ? 'spin-anim' : ''} />
            </button>
          )}
        </div>

        {/* Status Acuan Realisasi Banner */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '6px',
          fontSize: '10.5px',
          padding: '6px 10px',
          borderRadius: '8px',
          background: (selectedMonth || selectedYear) ? '#eff6ff' : '#ecfdf5',
          color: (selectedMonth || selectedYear) ? '#1d4ed8' : '#047857',
          fontWeight: '600'
        }}>
          {selectedMonth || selectedYear ? (
            <>
              <Calendar size={13} />
              <span>
                Filter: {selectedMonth ? MONTH_NAMES[parseInt(selectedMonth)] : 'Semua Bulan'} {selectedYear || ''}
              </span>
            </>
          ) : (
            <>
              <Lock size={13} />
              <span>Tanggal Dana Cair Terkunci Otomatis Sesuai Pengaturan Tim</span>
            </>
          )}
        </div>
      </div>

      {/* 4 ERP KPI Grid Widgets */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '10px'
      }}>
        {/* Widget 1: Tim Marketing */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          display: 'flex',
          flexDirection: 'column',
          gap: '4px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600' }}>Tim Marketing</span>
            <Users size={16} color="#3b82f6" />
          </div>
          <div style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>
            {summary.totalTeams} <small style={{ fontSize: '11px', fontWeight: '600', color: '#64748b' }}>Tim</small>
          </div>
          <span style={{ fontSize: '10px', color: '#059669', fontWeight: '700' }}>
            {summary.activeTeamsCount} Tim Aktif
          </span>
        </div>

        {/* Widget 2: Toko Terhubung */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          display: 'flex',
          flexDirection: 'column',
          gap: '4px'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600' }}>Toko Terhubung</span>
            <Store size={16} color="#10b981" />
          </div>
          <div style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>
            {summary.totalStoresLinked} <small style={{ fontSize: '11px', fontWeight: '600', color: '#64748b' }}>Toko</small>
          </div>
          <span style={{ fontSize: '10px', color: '#64748b' }}>
            Dari {summary.totalStoresCount || summary.totalStoresLinked} Toko Terdaftar
          </span>
        </div>

        {/* Widget 3: Target & Margin Realisasi */}
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          padding: '12px 14px',
          border: '1px solid #e2e8f0',
          display: 'flex',
          flexDirection: 'column',
          gap: '4px',
          gridColumn: 'span 2'
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600' }}>Target Margin (Rp)</span>
            <Target size={16} color="#0284c7" />
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline' }}>
            <div style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>
              {formatRupiah(summary.totalTargetMargin)}
            </div>
            <span style={{ fontSize: '11px', fontWeight: '800', color: '#0284c7' }}>
              {summary.progressPercent}% Tercapai
            </span>
          </div>

          {/* Progress Bar */}
          <div style={{ width: '100%', height: '6px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden', margin: '4px 0' }}>
            <div style={{
              width: `${Math.min(100, summary.progressPercent)}%`,
              height: '100%',
              background: summary.progressPercent >= 100 ? '#10b981' : '#0284c7',
              borderRadius: '999px'
            }} />
          </div>

          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10.5px' }}>
            <span style={{ color: '#059669', fontWeight: '700' }}>
              Margin Realisasi: {formatRupiah(summary.totalActualMargin)}
            </span>
            <span style={{ color: '#64748b' }}>
              {summary.totalActualQty?.toLocaleString('id-ID')} pcs
            </span>
          </div>
        </div>

        {/* Widget 4: Komisi Realisasi Banner */}
        <div style={{
          background: 'linear-gradient(135deg, #7c3aed, #6d28d9)',
          borderRadius: '16px',
          padding: '14px 16px',
          color: '#ffffff',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          gridColumn: 'span 2',
          boxShadow: '0 6px 18px rgba(124, 58, 237, 0.25)'
        }}>
          <div>
            <span style={{ fontSize: '11px', color: '#ddd6fe', fontWeight: '600' }}>Total Komisi Realisasi</span>
            <div style={{ fontSize: '20px', fontWeight: '800', color: '#fef08a' }}>
              {formatRupiah(summary.totalReward)}
            </div>
          </div>
          <Award size={28} color="#fef08a" />
        </div>
      </div>

      {/* Header List Tim */}
      <div>
        <div className="section-title-wrap" style={{ marginBottom: '10px' }}>
          <h3 className="section-title">
            <span>👥 Daftar Tim & Target ERP</span>
          </h3>
          <span style={{ fontSize: '11.5px', color: '#64748b', fontWeight: '600' }}>
            {teams.length} Tim
          </span>
        </div>

        {loading ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {[1, 2, 3].map((i) => (
              <div key={i} style={{ background: '#ffffff', borderRadius: '18px', padding: '16px', border: '1px solid #e2e8f0' }}>
                <div className="skeleton-shimmer skeleton-text" style={{ width: '130px', height: '16px', marginBottom: '8px' }} />
                <div className="skeleton-shimmer skeleton-text" style={{ width: '100%', height: '12px' }} />
              </div>
            ))}
          </div>
        ) : teams.length === 0 ? (
          <div style={{ background: '#ffffff', borderRadius: '18px', padding: '30px', textAlign: 'center', border: '1px solid #e2e8f0', color: '#94a3b8', fontSize: '12.5px' }}>
            Tidak ada tim marketing untuk periode yang dipilih.
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {teams.map((team, idx) => {
              const actMargin = team.actualMargin || 0;
              const targetMargin = team.targetMargin || 0;
              const pct = team.progressPercent || 0;
              const totRew = team.rewardEarned || 0;
              const cType = team.commissionType || 'percentage';

              let barColor = '#ef4444';
              if (pct >= 100) barColor = '#10b981';
              else if (pct >= 50) barColor = '#f59e0b';

              return (
                <div 
                  key={team.id || idx}
                  style={{
                    background: '#ffffff',
                    borderRadius: '18px',
                    padding: '16px',
                    border: '1px solid #e2e8f0',
                    boxShadow: '0 2px 6px rgba(0,0,0,0.03)',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px'
                  }}
                >
                  {/* Top Bar: Name & Lock Badge & Status */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '8px' }}>
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap', marginBottom: '3px' }}>
                        <h4 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                          {team.name}
                        </h4>
                        <span style={{
                          fontSize: '10px',
                          background: '#f1f5f9',
                          color: '#0284c7',
                          padding: '2px 8px',
                          borderRadius: '999px',
                          fontWeight: '700',
                          border: '1px solid #e2e8f0',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '3px'
                        }}>
                          <Lock size={10} /> {team.periodLabel || 'Semua Periode'}
                        </span>
                      </div>
                      {team.description && (
                        <p style={{ fontSize: '11px', color: '#64748b', margin: 0 }}>
                          {team.description}
                        </p>
                      )}
                    </div>
                    <span style={{
                      fontSize: '10px',
                      fontWeight: '800',
                      background: team.isActive ? '#ecfdf5' : '#f1f5f9',
                      color: team.isActive ? '#059669' : '#64748b',
                      padding: '2px 8px',
                      borderRadius: '999px',
                      whiteSpace: 'nowrap'
                    }}>
                      {team.isActive ? 'Aktif' : 'Nonaktif'}
                    </span>
                  </div>

                  {/* Badges Toko Terhubung */}
                  {team.stores && team.stores.length > 0 && (
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px' }}>
                      {team.stores.map((s) => {
                        const chName = (s.channel || '').toLowerCase();
                        let badgeBg = '#f1f5f9';
                        let badgeColor = '#475569';
                        if (chName.includes('shopee')) {
                          badgeBg = '#fff1f2';
                          badgeColor = '#e11d48';
                        } else if (chName.includes('tiktok')) {
                          badgeBg = '#0f172a';
                          badgeColor = '#ffffff';
                        } else if (chName.includes('tokopedia')) {
                          badgeBg = '#f0fdf4';
                          badgeColor = '#16a34a';
                        }
                        return (
                          <span key={s.id} style={{ 
                            fontSize: '10px', 
                            background: badgeBg, 
                            color: badgeColor, 
                            padding: '2px 8px', 
                            borderRadius: '999px',
                            fontWeight: '600'
                          }}>
                            🏪 {s.name}
                          </span>
                        );
                      })}
                    </div>
                  )}

                  {/* Realisasi & Target Margin Progress */}
                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11.5px', marginBottom: '4px' }}>
                      <span style={{ color: '#64748b' }}>
                        Realisasi Margin: <strong style={{ color: '#0f172a' }}>{formatRupiah(actMargin)}</strong>
                      </span>
                      <span style={{ fontWeight: '800', color: barColor }}>
                        {pct}%
                      </span>
                    </div>
                    <div style={{ width: '100%', height: '7px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden' }}>
                      <div style={{ 
                        width: `${Math.min(100, pct)}%`, 
                        height: '100%', 
                        background: barColor, 
                        borderRadius: '999px' 
                      }} />
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10.5px', color: '#94a3b8', marginTop: '4px' }}>
                      <span>Target: {formatRupiah(targetMargin)}</span>
                      <span>Qty: {team.actualQty?.toLocaleString('id-ID')} / {team.targetQty?.toLocaleString('id-ID') || 0} pcs</span>
                    </div>
                  </div>

                  {/* Skema Komisi & Total Komisi Box (Persis ERP Table) */}
                  <div style={{
                    background: '#f8fafc',
                    padding: '10px 12px',
                    borderRadius: '12px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '6px',
                    border: '1px solid #f1f5f9'
                  }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <span style={{ fontSize: '10.5px', color: '#64748b', fontWeight: '600' }}>
                        Skema Komisi:
                      </span>
                      <span style={{ fontSize: '11px', fontWeight: '700', color: '#0284c7' }}>
                        {cType === 'percentage' 
                          ? `${Number(team.commissionRate).toFixed(2)}% dari Margin`
                          : cType === 'nominal'
                          ? `${formatRupiah(team.rewardFixedNominal)} saat Capai Target`
                          : `${formatRupiah(team.rewardPerQty)} / Qty Produk`}
                        {cType === 'percentage' && team.rewardFixedNominal > 0 && ` + Bonus ${formatRupiah(team.rewardFixedNominal)}`}
                      </span>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderTop: '1px dashed #e2e8f0', paddingTop: '6px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                        <Award size={14} color="#d97706" />
                        <span style={{ fontSize: '11px', color: '#0f172a', fontWeight: '700' }}>
                          Komisi Realisasi:
                        </span>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <strong style={{ fontSize: '13px', color: '#059669' }}>
                          {formatRupiah(totRew)}
                        </strong>
                        <div style={{ fontSize: '9.5px', color: '#94a3b8' }}>
                          {cType === 'percentage' 
                            ? `(${Number(team.commissionRate).toFixed(2)}% × ${formatRupiah(actMargin)})`
                            : cType === 'nominal'
                            ? (actMargin >= targetMargin ? 'Target Tercapai' : 'Belum Capai Target')
                            : `(${team.actualQty} Qty × ${formatRupiah(team.rewardPerQty)})`}
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
