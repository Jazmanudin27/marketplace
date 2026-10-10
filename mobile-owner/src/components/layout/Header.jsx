import React, { useState, useEffect } from 'react';
import { Store, Key, Bell, LogOut, Calendar, Clock } from 'lucide-react';
import { formatIndonesianDate } from '../../utils/formatters';

export default function Header({ profile, onOpenKeyStatus, onOpenNotif, onLogout }) {
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
        <div className="brand-pill-owner" title="Portal Khusus Owner">
          <Store size={16} color="#38bdf8" />
          <span className="brand-pill-title">ASPARTECH</span>
          <span className="pro-badge">OWNER</span>
        </div>

        <div className="header-actions">
          <button 
            type="button" 
            className="action-circle-btn" 
            onClick={onOpenKeyStatus}
            title="Koneksi & Sinkronisasi API"
          >
            <Key size={16} />
          </button>
          
          <button 
            type="button" 
            className="action-circle-btn" 
            onClick={onOpenNotif}
            title="Notifikasi Masuk"
          >
            <Bell size={16} />
            <span className="notif-badge-dot"></span>
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
