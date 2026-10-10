import React from 'react';
import { Wallet, ArrowDownRight, ArrowUpRight, ShieldCheck, Target, CreditCard } from 'lucide-react';
import { formatRupiah } from '../utils/formatters';

export default function FinancePage({ metrics }) {
  return (
    <div className="mobile-body-content" style={{ paddingTop: '20px' }}>
      <div>
        <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>Ringkasan Keuangan</h2>
        <p style={{ fontSize: '12px', color: '#64748b' }}>Arus kas harian dan realisasi laba rugi</p>
      </div>

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
          {formatRupiah(metrics?.cashBalance || 48920000)}
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
              {formatRupiah(metrics?.receivableEscrow || 24150000)}
            </div>
          </div>
          <div>
            <span style={{ fontSize: '10.5px', color: '#94a3b8' }}>Omset Hari Ini:</span>
            <div style={{ fontSize: '13px', fontWeight: '700', color: '#34d399' }}>
              {formatRupiah(metrics?.todayOmset || 14850000)}
            </div>
          </div>
        </div>
      </div>

      {/* Monthly Target Progress Card */}
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
            <Target size={18} color="#4f46e5" />
            <h4 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a' }}>Target Margin Bulan Ini</h4>
          </div>
          <span style={{ fontSize: '12px', fontWeight: '800', color: '#4f46e5' }}>
            {metrics?.targetProgressPercent || 84.5}%
          </span>
        </div>

        {/* Progress Bar */}
        <div style={{ width: '100%', height: '8px', background: '#e2e8f0', borderRadius: '999px', overflow: 'hidden' }}>
          <div style={{ 
            width: `${Math.min(100, metrics?.targetProgressPercent || 84.5)}%`, 
            height: '100%', 
            background: 'linear-gradient(90deg, #4f46e5, #0ea5e9)',
            borderRadius: '999px' 
          }}></div>
        </div>

        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#64748b', marginTop: '2px' }}>
          <span>Realisasi: <strong style={{ color: '#0f172a' }}>{formatRupiah(metrics?.actualMonthlyMargin || 126750000)}</strong></span>
          <span>Target: {formatRupiah(metrics?.targetMonthlyMargin || 150000000)}</span>
        </div>
      </div>

      {/* Quick Summary Metrics Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
        <div style={{ background: '#ffffff', borderRadius: '18px', padding: '14px', border: '1px solid #e2e8f0' }}>
          <span style={{ fontSize: '11px', color: '#64748b' }}>HPP Hari Ini</span>
          <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', marginTop: '4px' }}>
            {formatRupiah(metrics?.todayHpp || 11610000)}
          </div>
        </div>

        <div style={{ background: '#ffffff', borderRadius: '18px', padding: '14px', border: '1px solid #e2e8f0' }}>
          <span style={{ fontSize: '11px', color: '#64748b' }}>Laba Bersih Hari Ini</span>
          <div style={{ fontSize: '15px', fontWeight: '800', color: '#16a34a', marginTop: '4px' }}>
            {formatRupiah(metrics?.todayMargin || 3240000)}
          </div>
        </div>
      </div>
    </div>
  );
}
