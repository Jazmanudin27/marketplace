import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Menu, Clock, Bell, Building, LogOut, UserCheck } from 'lucide-react';

const Header = () => {
  const [timeString, setTimeString] = useState('');
  const [user, setUser] = useState({
    name: 'Admin',
    email: 'admin@aspartech.com',
    tenant_name: 'Marketplace Store',
    role: 'admin'
  });

  const navigate = useNavigate();

  useEffect(() => {
    const updateClock = () => {
      const now = new Date();
      const options = { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
      setTimeString(now.toLocaleDateString('id-ID', options).replace(/\./g, ':'));
    };
    updateClock();
    const interval = setInterval(updateClock, 1000);

    // Load user data from localStorage
    try {
      const savedUser = localStorage.getItem('v2_user');
      if (savedUser) {
        setUser(JSON.parse(savedUser));
      }
    } catch (e) {
      console.error('Failed to parse v2_user:', e);
    }

    return () => clearInterval(interval);
  }, []);

  const handleLogout = (e) => {
    e.preventDefault();
    localStorage.removeItem('v2_token');
    localStorage.removeItem('v2_user');
    navigate('/login', { replace: true });
  };

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
            <span className="fw-bold text-dark">{user.tenant_name || user.name || 'Ruang Seragam'}</span>
          </button>
          <ul className="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1" style={{ fontSize: '0.78rem' }}>
            <li className="px-3 py-1 border-bottom">
              <div className="fw-bold text-dark">{user.name || 'User'}</div>
              <div className="text-muted" style={{ fontSize: '0.7rem' }}>{user.email || ''}</div>
              <span className="badge bg-primary-subtle text-primary text-uppercase mt-1" style={{ fontSize: '0.6rem' }}>
                {user.role || 'user'}
              </span>
            </li>
            <li>
              <button 
                type="button" 
                onClick={handleLogout} 
                className="dropdown-item text-danger fw-semibold py-1 d-flex align-items-center"
              >
                <LogOut size={14} className="me-2" /> Keluar / Logout
              </button>
            </li>
          </ul>
        </div>
      </div>
    </header>
  );
};

export default Header;
