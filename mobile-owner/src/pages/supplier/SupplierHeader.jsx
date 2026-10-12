import React from 'react';
import { LogOut, Box, ShieldCheck, Bell } from 'lucide-react';

export default function SupplierHeader({ user, onLogout }) {
  const name = user?.name || 'Mitra Supplier';
  const tenantName = user?.tenantName || user?.tenant_name || 'Ruang Seragam ERP';
  const initials = user?.avatar_initials || (name ? name.slice(0, 2).toUpperCase() : 'SP');

  return (
    <div style={{
      background: 'linear-gradient(135deg, #1e1b4b 0%, #0f172a 60%, #172554 100%)',
      padding: '20px 18px 18px',
      color: '#ffffff',
      borderBottomLeftRadius: '24px',
      borderBottomRightRadius: '24px',
      boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 0 15px rgba(245, 158, 11, 0.15)',
      position: 'relative',
      overflow: 'hidden'
    }}>
      {/* Ambient gold glow */}
      <div style={{
        position: 'absolute',
        top: '-40px',
        right: '-30px',
        width: '180px',
        height: '180px',
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(245, 158, 11, 0.22) 0%, transparent 70%)',
        filter: 'blur(30px)',
        pointerEvents: 'none'
      }} />

      {/* Brand & Badge Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', position: 'relative', zIndex: 1 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{
            width: '42px',
            height: '42px',
            borderRadius: '13px',
            background: 'linear-gradient(135deg, #f59e0b, #d97706)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            boxShadow: '0 4px 12px rgba(245, 158, 11, 0.4)'
          }}>
            <Box size={22} color="#ffffff" />
          </div>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span style={{ fontSize: '15px', fontWeight: '800', letterSpacing: '0.3px', color: '#ffffff' }}>
                PORTAL SUPPLIER
              </span>
              <span style={{
                background: 'rgba(245, 158, 11, 0.2)',
                border: '1px solid rgba(245, 158, 11, 0.4)',
                color: '#fbbf24',
                fontSize: '9.5px',
                fontWeight: '700',
                padding: '2px 6px',
                borderRadius: '6px',
                textTransform: 'uppercase'
              }}>
                Konsinyasi
              </span>
            </div>
            <div style={{ fontSize: '11.5px', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '2px' }}>
              <ShieldCheck size={12} color="#10b981" />
              <span>{name} • Mitra Resmi {tenantName}</span>
            </div>
          </div>
        </div>

        {/* Action Logout */}
        <button
          onClick={onLogout}
          title="Keluar"
          style={{
            background: 'rgba(239, 68, 68, 0.15)',
            border: '1px solid rgba(239, 68, 68, 0.3)',
            borderRadius: '12px',
            width: '38px',
            height: '38px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            color: '#f87171',
            transition: 'all 0.2s'
          }}
        >
          <LogOut size={17} />
        </button>
      </div>
    </div>
  );
}
