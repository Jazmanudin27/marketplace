import React from 'react';
import { LogOut } from 'lucide-react';

export default function SupplierHeader({ user, onLogout }) {

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

      {/* Brand Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', position: 'relative', zIndex: 1 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{
            width: '40px',
            height: '40px',
            borderRadius: '12px',
            background: 'rgba(255, 255, 255, 0.08)',
            border: '1px solid rgba(255, 255, 255, 0.15)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.25)',
            padding: '5px'
          }}>
            <img
              src="/ruang-market.png"
              alt="Ruang Market"
              style={{ width: '100%', height: '100%', objectFit: 'contain' }}
            />
          </div>
          <div>
            <span style={{ fontSize: '17px', fontWeight: '800', letterSpacing: '0.2px', color: '#ffffff' }}>
              Ruang Market
            </span>
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
