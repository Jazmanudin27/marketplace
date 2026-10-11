import React, { useState, useEffect } from 'react';
import { Store, Key, Bell, LogOut, Calendar, Clock } from 'lucide-react';
import { formatIndonesianDate } from '../../utils/formatters';

export default function Header({ profile, onOpenNotif, onLogout, unreadCount = 0 }) {
  const [currentDate, setCurrentDate] = useState(formatIndonesianDate());

  // Real-time dynamic clock ticking every second like a real mobile app
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentDate(formatIndonesianDate(new Date()));
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Dynamic greeting based on time of day
  const getGreeting = () => {
    const hours = new Date().getHours();
    if (hours >= 4 && hours < 11) return '🌅 Selamat Pagi,';
    if (hours >= 11 && hours < 15) return '☀️ Selamat Siang,';
    if (hours >= 15 && hours < 18) return '🌤️ Selamat Sore,';
    return '🌙 Selamat Malam,';
  };

  return (
    <header className="owner-header">
      {/* Top Navbar */}
      <div className="header-top-bar">
        <div className="brand-pill-owner" title="Portal Khusus Owner" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <img src="/ruang-market.png" alt="Ruang Market" style={{ width: '22px', height: '22px', objectFit: 'contain' }} />
          <span className="brand-pill-title" style={{ fontSize: '15px', fontWeight: '800' }}>Ruang Market</span>
        </div>

        <div className="header-actions">
          <button 
            type="button" 
            className="action-circle-btn" 
            onClick={onOpenNotif}
            title="Notifikasi Masuk"
          >
            <Bell size={17} />
            {unreadCount > 0 && (
              <span 
                style={{
                  position: 'absolute',
                  top: '-3px',
                  right: '-3px',
                  background: '#ef4444',
                  color: '#ffffff',
                  fontSize: '10px',
                  fontWeight: '800',
                  borderRadius: '999px',
                  minWidth: '18px',
                  height: '18px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  padding: '0 4px',
                  border: '2px solid #1e1b4b',
                  boxShadow: '0 2px 6px rgba(239, 68, 68, 0.4)'
                }}
              >
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
            )}
          </button>

          <button 
            type="button" 
            className="action-circle-btn btn-danger-circle" 
            onClick={onLogout}
            title="Keluar"
          >
            <LogOut size={16} />
          </button>
        </div>
      </div>

      {/* Owner Profile Card */}
      <div className="owner-profile-card">
        <div className="owner-avatar">
          {profile?.avatarInitials || 'DS'}
          <span className="online-indicator-dot" title="Sistem V2 Online"></span>
        </div>

        <div className="owner-info">
          <span className="greeting-text">{getGreeting()}</span>
          <h2 className="owner-name">{profile?.name || 'Dina Saparinda, S.Kom'}</h2>
          
          <div className="datetime-pill">
            <Calendar size={11} />
            <span>{currentDate.fullText}</span>
          </div>
        </div>
      </div>
    </header>
  );
}
