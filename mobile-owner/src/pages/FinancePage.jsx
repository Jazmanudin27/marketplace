import React, { useState } from 'react';
import { Wallet, ArrowDownRight, ArrowUpRight, ShieldCheck, Target, CreditCard, Award, ChevronRight, BarChart2 } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';
import TargetKomisiPage from './TargetKomisiPage';
import LabaRugiPage from './LabaRugiPage';

export default function FinancePage({ metrics, onOpenTargetKomisi, initialSubTab = 'ringkasan' }) {
  const [subTab, setSubTab] = useState(initialSubTab); // 'ringkasan' | 'target_komisi' | 'laba_rugi'

  return (
    <div className="mobile-body-content" style={{ paddingTop: '14px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      
      {/* Segmented Top Tabs */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: '1fr 1fr 1fr',
        background: '#e2e8f0',
        padding: '3px',
        borderRadius: '14px',
        gap: '4px'
      }}>
        <button
          type="button"
          onClick={() => setSubTab('ringkasan')}
          style={{
            border: 'none',
            background: subTab === 'ringkasan' ? '#ffffff' : 'transparent',
            color: subTab === 'ringkasan' ? '#0f172a' : '#64748b',
            fontWeight: subTab === 'ringkasan' ? '800' : '600',
            fontSize: '11px',
            padding: '8px 4px',
            borderRadius: '11px',
            cursor: 'pointer',
            boxShadow: subTab === 'ringkasan' ? '0 2px 6px rgba(0,0,0,0.08)' : 'none',
            transition: 'all 0.2s ease',
            whiteSpace: 'nowrap'
          }}
        >
          💰 Kas & Ringkasan
        </button>
        <button
          type="button"
          onClick={() => setSubTab('target_komisi')}
          style={{
            border: 'none',
            background: subTab === 'target_komisi' ? '#2563eb' : 'transparent',
            color: subTab === 'target_komisi' ? '#ffffff' : '#64748b',
            fontWeight: subTab === 'target_komisi' ? '800' : '600',
            fontSize: '11px',
            padding: '8px 4px',
            borderRadius: '11px',
            cursor: 'pointer',
            boxShadow: subTab === 'target_komisi' ? '0 2px 6px rgba(37,99,235,0.25)' : 'none',
            transition: 'all 0.2s ease',
            whiteSpace: 'nowrap'
          }}
        >
          🎯 Target & Komisi V2
        </button>
        <button
          type="button"
          onClick={() => setSubTab('laba_rugi')}
          style={{
            border: 'none',
            background: subTab === 'laba_rugi' ? '#ffffff' : 'transparent',
            color: subTab === 'laba_rugi' ? '#0f172a' : '#64748b',
            fontWeight: subTab === 'laba_rugi' ? '800' : '600',
            fontSize: '11px',
            padding: '8px 4px',
            borderRadius: '11px',
            cursor: 'pointer',
            boxShadow: subTab === 'laba_rugi' ? '0 2px 6px rgba(0,0,0,0.08)' : 'none',
            transition: 'all 0.2s ease',
            whiteSpace: 'nowrap'
          }}
        >
          📊 Laba Rugi
        </button>
      </div>

      {/* SUB-TAB 1: TARGET & KOMISI V2 */}
      {subTab === 'target_komisi' && (
        <TargetKomisiPage initialMetrics={metrics} />
      )}

      {/* SUB-TAB 2: LABA RUGI */}
      {subTab === 'laba_rugi' && (
        <LabaRugiPage />
      )}

      {/* SUB-TAB 3: RINGKASAN KAS & METRIK */}
      {subTab === 'ringkasan' && (
        <>
          {/* Main Cash Balance Card */}
          <div style={{
            background: 'linear-gradient(135deg, #1e1b4b, #312e81)',
            borderRadius: '24px',
            padding: '20px',
            color: '#ffffff',
            boxShadow: '0 12px 28px rgba(30, 27, 75, 0.35)',
            display: 'flex',
            flexDirection: 'column',
            gap: '12px'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span style={{ fontSize: '12px', color: '#cbd5e1' }}>Total Saldo Kas Operasional</span>
              <CreditCard size={18} color="#38bdf8" />
            </div>

            <div style={{ fontSize: '26px', fontWeight: '800', letterSpacing: '-0.5px' }}>
              {formatRupiah(metrics?.cashBalance ?? 0)}
            </div>

            <div style={{
              display: 'grid',
              gridTemplateColumns: '1fr 1fr',
              gap: '8px',
              paddingTop: '10px',
              borderTop: '1px solid rgba(255, 255, 255, 0.12)'
            }}>
              <div>
                <span style={{ fontSize: '10.5px', color: '#94a3b8' }}>Dana Escrow Marketplace:</span>
                <div style={{ fontSize: '13px', fontWeight: '700', color: '#38bdf8' }}>
                  {formatRupiah(metrics?.receivableEscrow ?? 0)}
                </div>
              </div>
              <div>
                <span style={{ fontSize: '10.5px', color: '#94a3b8' }}>Omset Hari Ini:</span>
                <div style={{ fontSize: '13px', fontWeight: '700', color: '#34d399' }}>
                  {formatRupiah(metrics?.todayOmset ?? 0)}
                </div>
              </div>
            </div>
          </div>

          {/* Monthly Target Progress Card with Quick Switch to V2 */}
          <div style={{
            background: '#ffffff',
            borderRadius: '20px',
            padding: '18px',
            border: '1px solid #e2e8f0',
            boxShadow: '0 4px 12px rgba(0,0,0,0.04)',
            display: 'flex',
            flexDirection: 'column',
            gap: '10px'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Target size={18} color="#2563eb" />
                <h4 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', margin: 0 }}>Target Margin Bulan Ini</h4>
              </div>
              <span style={{ fontSize: '12px', fontWeight: '800', color: '#2563eb' }}>
                {metrics?.targetProgressPercent ?? 0}%
              </span>
            </div>

            {/* Progress Bar */}
            <div style={{ width: '100%', height: '8px', background: '#e2e8f0', borderRadius: '999px', overflow: 'hidden' }}>
              <div style={{ 
                width: `${Math.min(100, metrics?.targetProgressPercent ?? 0)}%`, 
                height: '100%', 
                background: 'linear-gradient(90deg, #2563eb, #0ea5e9)',
                borderRadius: '999px' 
              }}></div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#64748b', marginTop: '2px' }}>
              <span>Realisasi: <strong style={{ color: '#0f172a' }}>{formatRupiah(metrics?.actualMonthlyMargin ?? 0)}</strong></span>
              <span>Target: {formatRupiah(metrics?.targetMonthlyMargin ?? 0)}</span>
            </div>

            <button
              type="button"
              onClick={() => setSubTab('target_komisi')}
              style={{
                marginTop: '6px',
                background: 'linear-gradient(135deg, #eff6ff, #dbeafe)',
                color: '#1d4ed8',
                borderRadius: '12px',
                padding: '10px 14px',
                fontSize: '12px',
                fontWeight: '800',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                border: '1px solid #bfdbfe'
              }}
            >
              <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Award size={15} color="#2563eb" /> Buka Detail Target & Komisi Penjualan V2
              </span>
              <ChevronRight size={15} />
            </button>
          </div>

          {/* Quick Summary Metrics Grid */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
            <div style={{ background: '#ffffff', borderRadius: '18px', padding: '14px', border: '1px solid #e2e8f0' }}>
              <span style={{ fontSize: '11px', color: '#64748b' }}>HPP Hari Ini</span>
              <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', marginTop: '4px' }}>
                {formatRupiah(metrics?.todayHpp ?? 0)}
              </div>
            </div>

            <div style={{ background: '#ffffff', borderRadius: '18px', padding: '14px', border: '1px solid #e2e8f0' }}>
              <span style={{ fontSize: '11px', color: '#64748b' }}>Laba Bersih Hari Ini</span>
              <div style={{ fontSize: '15px', fontWeight: '800', color: '#16a34a', marginTop: '4px' }}>
                {formatRupiah(metrics?.todayMargin ?? 0)}
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
