import React from 'react';
import { Store, ShieldCheck, RefreshCw, Smartphone, LogOut, CheckCircle2 } from 'lucide-react';

export default function ProfilePage({ profile, onLogout }) {
  return (
    <div className="mobile-body-content" style={{ paddingTop: '20px' }}>
      <div>
        <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a' }}>Profil Owner & Toko</h2>
        <p style={{ fontSize: '12px', color: '#64748b' }}>Pengaturan akses dan sinkronisasi channel marketplace</p>
      </div>

      {/* Profile Card */}
      <div style={{
        background: '#ffffff',
        borderRadius: '20px',
        padding: '18px',
        border: '1px solid #e2e8f0',
        display: 'flex',
        alignItems: 'center',
        gap: '14px'
      }}>
        <div style={{
          width: '52px',
          height: '52px',
          borderRadius: '16px',
          background: 'linear-gradient(135deg, #2563eb, #4f46e5)',
          color: '#ffffff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: '18px',
          fontWeight: '800'
        }}>
          {profile?.avatarInitials || 'DS'}
        </div>

        <div>
          <h3 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', marginBottom: '2px' }}>
            {profile?.name}
          </h3>
          <span style={{ fontSize: '12px', color: '#64748b' }}>
            Tenant: <strong>{profile?.tenantName}</strong>
          </span>
          <div style={{ display: 'flex', alignItems: 'center', gap: '4px', marginTop: '4px', color: '#16a34a', fontSize: '11px', fontWeight: '700' }}>
            <CheckCircle2 size={13} />
            <span>Hak Akses Super Admin (Owner)</span>
          </div>
        </div>
      </div>

      {/* Connected Channels List */}
      <div style={{
        background: '#ffffff',
        borderRadius: '20px',
        padding: '18px',
        border: '1px solid #e2e8f0'
      }}>
        <h4 style={{ fontSize: '13.5px', fontWeight: '800', color: '#0f172a', marginBottom: '12px', display: 'flex', alignItems: 'center', gap: '6px' }}>
          <Store size={16} color="#4f46e5" />
          <span>Toko Terhubung ({profile?.storesCount || 4} Toko)</span>
        </h4>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
          {profile?.connectedMarketplaces?.map((channel, i) => (
            <div key={i} style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              padding: '8px 12px',
              background: '#f8fafc',
              borderRadius: '12px',
              fontSize: '12.5px',
              fontWeight: '600',
              color: '#334155'
            }}>
              <span>{channel}</span>
              <span style={{ fontSize: '11px', color: '#16a34a', fontWeight: '700' }}>
                ● Aktif & Sinkron
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Logout Action */}
      <button
        type="button"
        onClick={onLogout}
        style={{
          width: '100%',
          background: '#fee2e2',
          border: '1px solid #fca5a5',
          borderRadius: '16px',
          padding: '14px',
          color: '#dc2626',
          fontSize: '13.5px',
          fontWeight: '700',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: '8px',
          cursor: 'pointer'
        }}
      >
        <LogOut size={16} />
        <span>Keluar dari Akun Owner</span>
      </button>
    </div>
  );
}
