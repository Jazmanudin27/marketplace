import React, { useState, useEffect } from 'react';
import { Target, Users, Award, TrendingUp, CheckCircle2, ChevronRight, Store, DollarSign } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import { fetchTargetKomisi } from '../api/metricsApi';

export default function TargetKomisiPage({ initialMetrics }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setLoading(true);
      const res = await fetchTargetKomisi();
      if (res && res.success) {
        setData(res);
      }
      setLoading(false);
    }
    load();
  }, []);

  const summary = data?.summary || {
    totalTargetMargin: initialMetrics?.targetMonthlyMargin || 0,
    totalTargetQty: 0,
    totalActualMargin: initialMetrics?.actualMonthlyMargin || 0,
    totalActualQty: 0,
    totalReward: initialMetrics?.totalEarnedCommission || 0,
    progressPercent: initialMetrics?.targetProgressPercent || 0,
    activeTeamsCount: 0
  };

  const teams = data?.teams || [];

  return (
    <div className="mobile-body-content" style={{ paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      
      {/* KPI Header Card (Total Target vs Realisasi) */}
      <div style={{
        background: 'linear-gradient(135deg, #0284c7, #0369a1)',
        borderRadius: '24px',
        padding: '20px',
        color: '#ffffff',
        boxShadow: '0 10px 25px rgba(2, 132, 199, 0.35)',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Target size={18} color="#e0f2fe" />
            <span style={{ fontSize: '13px', fontWeight: '700', color: '#e0f2fe' }}>Target Margin & Komisi ERP</span>
          </div>
          <span style={{ 
            fontSize: '11px', 
            background: 'rgba(255, 255, 255, 0.2)', 
            padding: '3px 9px', 
            borderRadius: '999px', 
            fontWeight: '800' 
          }}>
            {summary.activeTeamsCount} Tim Aktif
          </span>
        </div>

        <div>
          <span style={{ fontSize: '11px', color: '#bae6fd' }}>Realisasi Margin Bulan Ini</span>
          <div style={{ fontSize: '26px', fontWeight: '800', letterSpacing: '-0.5px' }}>
            {formatRupiah(summary.totalActualMargin)}
          </div>
        </div>

        {/* Progress Bar */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11.5px', color: '#e0f2fe', fontWeight: '600' }}>
            <span>Target: {formatRupiah(summary.totalTargetMargin)}</span>
            <span style={{ fontWeight: '800' }}>{summary.progressPercent}% Tercapai</span>
          </div>
          <div style={{ width: '100%', height: '8px', background: 'rgba(0, 0, 0, 0.2)', borderRadius: '999px', overflow: 'hidden' }}>
            <div style={{ 
              width: `${Math.min(100, summary.progressPercent)}%`, 
              height: '100%', 
              background: '#38bdf8', 
              borderRadius: '999px' 
            }} />
          </div>
        </div>

        <div style={{
          display: 'grid',
          gridTemplateColumns: '1fr 1fr',
          gap: '8px',
          paddingTop: '10px',
          borderTop: '1px solid rgba(255, 255, 255, 0.15)'
        }}>
          <div>
            <span style={{ fontSize: '10.5px', color: '#bae6fd' }}>Total Komisi Dicapai:</span>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#fef08a' }}>
              {formatRupiah(summary.totalReward)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#bae6fd' }}>Total Qty Produk:</span>
            <div style={{ fontSize: '14px', fontWeight: '800', color: '#ffffff' }}>
              {summary.totalActualQty.toLocaleString('id-ID')} pcs
            </div>
          </div>
        </div>
      </div>

      {/* Daftar Tim Marketing */}
      <div>
        <div className="section-title-wrap" style={{ marginBottom: '10px' }}>
          <h3 className="section-title">
            <span>👥 Tim Marketing & Realisasi Komisi</span>
          </h3>
          <span style={{ fontSize: '11.5px', color: '#64748b', fontWeight: '600' }}>
            {teams.length} Tim
          </span>
        </div>

        {loading ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {[1, 2].map((i) => (
              <div key={i} style={{ background: '#ffffff', borderRadius: '18px', padding: '16px', border: '1px solid #e2e8f0' }}>
                <div className="skeleton-shimmer skeleton-text" style={{ width: '130px', height: '16px', marginBottom: '8px' }} />
                <div className="skeleton-shimmer skeleton-text" style={{ width: '100%', height: '12px' }} />
              </div>
            ))}
          </div>
        ) : teams.length === 0 ? (
          <div style={{ background: '#ffffff', borderRadius: '18px', padding: '30px', textAlign: 'center', border: '1px solid #e2e8f0', color: '#94a3b8', fontSize: '12.5px' }}>
            Belum ada tim marketing yang dikonfigurasi di ERP.
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {teams.map((team) => (
              <div 
                key={team.id}
                style={{
                  background: '#ffffff',
                  borderRadius: '18px',
                  padding: '16px',
                  border: '1px solid #e2e8f0',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.03)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '10px'
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <h4 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', margin: '0 0 2px' }}>
                      {team.name}
                    </h4>
                    <span style={{ fontSize: '11px', color: '#64748b' }}>
                      {team.description || 'Target Periode Berjalan'}
                    </span>
                  </div>
                  <span style={{
                    fontSize: '10px',
                    fontWeight: '800',
                    background: team.isActive ? '#ecfdf5' : '#f1f5f9',
                    color: team.isActive ? '#059669' : '#64748b',
                    padding: '2px 8px',
                    borderRadius: '999px'
                  }}>
                    {team.isActive ? 'Aktif' : 'Nonaktif'}
                  </span>
                </div>

                {/* Progress bar per tim */}
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11.5px', marginBottom: '4px' }}>
                    <span style={{ color: '#64748b' }}>
                      Realisasi: <strong style={{ color: '#0f172a' }}>{formatRupiah(team.actualMargin)}</strong>
                    </span>
                    <span style={{ fontWeight: '800', color: '#0284c7' }}>
                      {team.progressPercent}%
                    </span>
                  </div>
                  <div style={{ width: '100%', height: '6px', background: '#f1f5f9', borderRadius: '999px', overflow: 'hidden' }}>
                    <div style={{ 
                      width: `${Math.min(100, team.progressPercent)}%`, 
                      height: '100%', 
                      background: team.progressPercent >= 100 ? '#10b981' : '#0ea5e9', 
                      borderRadius: '999px' 
                    }} />
                  </div>
                  <div style={{ fontSize: '10.5px', color: '#94a3b8', marginTop: '3px' }}>
                    Target: {formatRupiah(team.targetMargin)} • {team.actualQty} / {team.targetQty || 0} pcs
                  </div>
                </div>

                {/* Komisi reward */}
                <div style={{
                  background: '#f8fafc',
                  padding: '8px 12px',
                  borderRadius: '12px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  border: '1px solid #f1f5f9'
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <Award size={14} color="#d97706" />
                    <span style={{ fontSize: '11px', color: '#64748b', fontWeight: '600' }}>
                      Komisi Diperoleh:
                    </span>
                  </div>
                  <strong style={{ fontSize: '12.5px', color: '#059669' }}>
                    {formatRupiah(team.rewardEarned)}
                  </strong>
                </div>

                {/* Linked Stores */}
                {team.stores && team.stores.length > 0 && (
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px', paddingTop: '2px' }}>
                    {team.stores.map((s) => (
                      <span key={s.id} style={{ 
                        fontSize: '9.5px', 
                        background: '#eff6ff', 
                        color: '#1d4ed8', 
                        padding: '2px 6px', 
                        borderRadius: '6px',
                        fontWeight: '600'
                      }}>
                        🏪 {s.name} ({s.channel})
                      </span>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
