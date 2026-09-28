import React, { useState, useEffect } from 'react';
import { Menu, Clock, Bell, Building, LogOut } from 'lucide-react';

const Header = () => {
  const [timeString, setTimeString] = useState('');

  useEffect(() => {
    const updateClock = () => {
      const now = new Date();
      const options = { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
      setTimeString(now.toLocaleDateString('id-ID', options).replace(/\./g, ':'));
    };
    updateClock();
    const interval = setInterval(updateClock, 1000);
    return () => clearInterval(interval);
  }, []);

  return (
    <header className="v2-header">
      <div className="d-flex align-items-center gap-3">
        <button className="btn btn-sm btn-outline-secondary py-1 px-2 d-lg-none" type="button">
          <Menu size={16} />
        </button>

        {/* Realtime Date & Time Indicator */}
        <div className="text-muted d-none d-md-flex align-items-center gap-2 fw-medium" style={{ fontSize: '0.78rem' }}>
          <Clock size={14} />
          <span>{timeString || '28 September 2026 • 14:49:27'}</span>
        </div>
      </div>

      {/* Header Actions */}
      <div className="v2-header-actions d-flex align-items-center gap-2">
        {/* Notification Bell */}
        <div className="dropdown">
          <button className="v2-icon-btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
            <Bell size={15} />
            <span className="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
          </button>
        </div>

        {/* School / Tenant Badge Pill */}
        <div className="dropdown">
          <button className="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 py-1 px-2 rounded-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style={{ fontSize: '0.78rem', background: '#ffffff' }}>
            <div className="bg-success text-white rounded d-flex align-items-center justify-content-center fw-bold" style={{ width: '20px', height: '20px', fontSize: '0.65rem' }}>
              <Building size={12} />
            </div>
            <span className="fw-bold text-dark">Ruang Seragam</span>
          </button>
          <ul className="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1" style={{ fontSize: '0.78rem' }}>
            <li className="px-3 py-1 border-bottom">
              <div className="fw-bold text-dark">Ruang Seragam Admin</div>
              <div className="text-muted" style={{ fontSize: '0.7rem' }}>admin@ruangseragam.com</div>
            </li>
            <li>
              <a href="/logout" className="dropdown-item text-danger fw-semibold py-1">
                <LogOut size={14} className="me-1" /> Keluar / Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </header>
  );
};

export default Header;
