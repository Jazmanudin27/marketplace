import React, { useState } from 'react';
import { User, Lock, Mail, Phone, MapPin, Building, ShieldCheck, LogOut, CheckCircle2, AlertCircle, Loader2 } from 'lucide-react';
import { updateUserCredentials } from '../../api/authApi';
import { showToast, showAlert } from '../../utils/alerts';

export default function SupplierProfilePage({ currentUser, onLogout }) {
  const [username, setUsername] = useState(currentUser?.name || currentUser?.username || '');
  const [email, setEmail] = useState(currentUser?.email || '');
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isUpdating, setIsUpdating] = useState(false);

  const tenantName = currentUser?.tenantName || currentUser?.tenant_name || 'Ruang Seragam ERP';
  const roleName = currentUser?.role || 'Supplier';

  const handleUpdatePassword = async (e) => {
    e.preventDefault();

    if (!newPassword) {
      showToast('Masukkan kata sandi baru.', 'warning');
      return;
    }
    if (newPassword.length < 6) {
      showToast('Kata sandi baru minimal 6 karakter.', 'warning');
      return;
    }
    if (newPassword !== confirmPassword) {
      showToast('Konfirmasi kata sandi tidak cocok.', 'error');
      return;
    }
    if (!currentPassword) {
      showToast('Kata sandi saat ini wajib diisi.', 'warning');
      return;
    }

    setIsUpdating(true);
    try {
      const res = await updateUserCredentials({
        username,
        email,
        current_password: currentPassword,
        new_password: newPassword
      });

      if (res && res.success) {
        showAlert({
          icon: 'success',
          title: 'Berhasil Diperbarui',
          text: 'Kredensial login akun supplier Anda telah berhasil diperbarui.'
        });
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
      } else {
        showAlert({
          icon: 'error',
          title: 'Gagal Memperbarui',
          text: res?.message || 'Kata sandi lama tidak cocok atau terjadi kesalahan.'
        });
      }
    } catch (err) {
      showAlert({
        icon: 'error',
        title: 'Kesalahan Server',
        text: err.message || 'Gagal menghubungi server.'
      });
    } finally {
      setIsUpdating(false);
    }
  };

  return (
    <div style={{ padding: '16px 14px 90px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
      {/* Title */}
      <div>
        <h2 style={{ fontSize: '17px', fontWeight: '800', color: '#1e293b', margin: 0 }}>
          Akun & Kredensial Supplier
        </h2>
        <p style={{ fontSize: '11.5px', color: '#64748b', margin: '2px 0 0' }}>
          Kelola profil mitra, keamanan akun dan sesi login
        </p>
      </div>

      {/* Info Card */}
      <div style={{
        background: '#ffffff',
        border: '1px solid #e2e8f0',
        borderRadius: '20px',
        padding: '16px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.02)',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
          <div style={{
            width: '46px',
            height: '46px',
            borderRadius: '14px',
            background: 'linear-gradient(135deg, #f59e0b, #d97706)',
            color: '#ffffff',
            fontWeight: '800',
            fontSize: '16px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            boxShadow: '0 4px 12px rgba(245, 158, 11, 0.3)'
          }}>
            {(username || 'SP').slice(0, 2).toUpperCase()}
          </div>
          <div>
            <div style={{ fontSize: '15px', fontWeight: '800', color: '#1e293b' }}>
              {username || 'Mitra Supplier'}
            </div>
            <div style={{ fontSize: '11.5px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px' }}>
              <ShieldCheck size={13} color="#10b981" />
              <span>Role: <strong style={{ color: '#d97706', textTransform: 'capitalize' }}>{roleName}</strong></span>
            </div>
          </div>
        </div>

        <div style={{
          background: '#f8fafc',
          borderRadius: '12px',
          padding: '10px 12px',
          display: 'flex',
          flexDirection: 'column',
          gap: '6px',
          fontSize: '12px',
          color: '#475569'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Building size={14} color="#94a3b8" />
            <span>Perusahaan ERP: <strong>{tenantName}</strong></span>
          </div>
          {email && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Mail size={14} color="#94a3b8" />
              <span>Email: <strong>{email}</strong></span>
            </div>
          )}
        </div>
      </div>

      {/* Security Form */}
      <div style={{
        background: '#ffffff',
        border: '1px solid #e2e8f0',
        borderRadius: '20px',
        padding: '16px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
      }}>
        <div style={{
          fontSize: '13px',
          fontWeight: '800',
          color: '#1e293b',
          display: 'flex',
          alignItems: 'center',
          gap: '6px',
          borderBottom: '1px solid #f1f5f9',
          paddingBottom: '10px',
          marginBottom: '12px'
        }}>
          <Lock size={15} color="#6366f1" />
          <span>Ganti Kata Sandi Akun</span>
        </div>

        <form onSubmit={handleUpdatePassword} style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
          <div>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '3px' }}>
              Kata Sandi Saat Ini *
            </label>
            <input
              type="password"
              placeholder="Masukkan sandi saat ini..."
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              required
              style={{
                width: '100%',
                padding: '9px 12px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '12.5px',
                background: '#ffffff',
                boxSizing: 'border-box'
              }}
            />
          </div>

          <div>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '3px' }}>
              Kata Sandi Baru * (Min 6 Karakter)
            </label>
            <input
              type="password"
              placeholder="Masukkan sandi baru..."
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              required
              style={{
                width: '100%',
                padding: '9px 12px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '12.5px',
                background: '#ffffff',
                boxSizing: 'border-box'
              }}
            />
          </div>

          <div>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '3px' }}>
              Ulangi Kata Sandi Baru *
            </label>
            <input
              type="password"
              placeholder="Konfirmasi sandi baru..."
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              required
              style={{
                width: '100%',
                padding: '9px 12px',
                borderRadius: '10px',
                border: '1px solid #cbd5e1',
                fontSize: '12.5px',
                background: '#ffffff',
                boxSizing: 'border-box'
              }}
            />
          </div>

          <button
            type="submit"
            disabled={isUpdating}
            style={{
              marginTop: '4px',
              padding: '12px',
              borderRadius: '12px',
              border: 'none',
              background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
              color: '#ffffff',
              fontWeight: '700',
              fontSize: '13px',
              cursor: isUpdating ? 'not-allowed' : 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              boxShadow: '0 4px 12px rgba(99, 102, 241, 0.3)',
              opacity: isUpdating ? 0.75 : 1
            }}
          >
            {isUpdating ? (
              <>
                <Loader2 size={16} style={{ animation: 'spin 1s linear infinite' }} />
                <span>Memperbarui Sandi...</span>
              </>
            ) : (
              <span>Simpan Kata Sandi Baru</span>
            )}
          </button>
        </form>
      </div>

      {/* Logout Button Card */}
      <div style={{
        background: '#ffffff',
        border: '1px solid #fee2e2',
        borderRadius: '20px',
        padding: '16px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
      }}>
        <div style={{ fontSize: '12px', color: '#64748b', marginBottom: '10px' }}>
          Sesi aktif untuk akun <strong>{username}</strong>. Keluar dari aplikasi akan mengakhiri sesi saat ini.
        </div>
        <button
          onClick={onLogout}
          style={{
            width: '100%',
            padding: '12px',
            borderRadius: '12px',
            border: 'none',
            background: 'linear-gradient(135deg, #ef4444, #dc2626)',
            color: '#ffffff',
            fontWeight: '700',
            fontSize: '13.5px',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '8px',
            boxShadow: '0 4px 12px rgba(239, 68, 68, 0.3)'
          }}
        >
          <LogOut size={16} />
          <span>Keluar dari Portal Supplier</span>
        </button>
      </div>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
