import React, { useState, useEffect } from 'react';
import { 
  User, 
  Lock, 
  KeyRound, 
  ShieldCheck, 
  CheckCircle2, 
  Eye, 
  EyeOff, 
  Save, 
  LogOut, 
  AlertCircle, 
  CheckCircle,
  Building2,
  Mail,
  Sparkles,
  Loader2
} from 'lucide-react';
import { updateUserCredentials, getCurrentOwnerUser } from '../api/authApi';

export default function ProfilePage({ profile, onLogout, onProfileUpdate }) {
  const currentUser = getCurrentOwnerUser();
  const [username, setUsername] = useState(profile?.name || currentUser?.name || '');
  const [email, setEmail] = useState(profile?.email || currentUser?.email || '');
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const [showCurrentPass, setShowCurrentPass] = useState(false);
  const [showNewPass, setShowNewPass] = useState(false);
  const [showConfirmPass, setShowConfirmPass] = useState(false);

  const [isLoading, setIsLoading] = useState(false);
  const [statusMsg, setStatusMsg] = useState(null); // { type: 'success' | 'error', text: '' }

  // Sync state if profile prop changes
  useEffect(() => {
    if (profile?.name) setUsername(profile.name);
    if (profile?.email) setEmail(profile.email);
  }, [profile]);

  const handleUpdate = async (e) => {
    e.preventDefault();
    setStatusMsg(null);

    // Validation
    if (!username.trim()) {
      setStatusMsg({ type: 'error', text: 'Nama / Username tidak boleh kosong.' });
      return;
    }

    if (newPassword) {
      if (!currentPassword) {
        setStatusMsg({ type: 'error', text: 'Masukkan kata sandi saat ini untuk memverifikasi penggantian kata sandi.' });
        return;
      }
      if (newPassword.length < 6) {
        setStatusMsg({ type: 'error', text: 'Kata sandi baru minimal 6 karakter.' });
        return;
      }
      if (newPassword !== confirmPassword) {
        setStatusMsg({ type: 'error', text: 'Konfirmasi kata sandi baru tidak cocok.' });
        return;
      }
    }

    setIsLoading(true);

    try {
      const res = await updateUserCredentials({
        username: username.trim(),
        email: email.trim(),
        current_password: currentPassword || undefined,
        new_password: newPassword || undefined
      });

      if (res.success) {
        setStatusMsg({ type: 'success', text: res.message || 'Profil berhasil diperbarui!' });
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
        if (onProfileUpdate && res.user) {
          onProfileUpdate(res.user);
        }
      } else {
        setStatusMsg({ type: 'error', text: res.message || 'Gagal memperbarui profil.' });
      }
    } catch (err) {
      setStatusMsg({ type: 'error', text: 'Terjadi kesalahan koneksi ke server.' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="mobile-body-content" style={{ paddingTop: '14px', paddingBottom: '90px' }}>
      
      {/* ─── 1. HERO PROFILE CARD ─── */}
      <div style={{
        background: 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #1e293b 100%)',
        borderRadius: '24px',
        padding: '24px 20px',
        border: '1px solid rgba(255, 255, 255, 0.1)',
        boxShadow: '0 12px 35px -10px rgba(0, 0, 0, 0.25), 0 0 25px rgba(79, 70, 229, 0.15)',
        color: '#ffffff',
        position: 'relative',
        overflow: 'hidden',
        marginBottom: '16px'
      }}>
        {/* Glow corner aura */}
        <div style={{
          position: 'absolute',
          top: '-40px',
          right: '-40px',
          width: '140px',
          height: '140px',
          borderRadius: '50%',
          background: 'radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%)',
          filter: 'blur(20px)',
          pointerEvents: 'none'
        }} />

        <div style={{ display: 'flex', alignItems: 'center', gap: '16px', position: 'relative', zIndex: 1 }}>
          {/* Avatar with Glow Badge */}
          <div style={{
            width: '64px',
            height: '64px',
            borderRadius: '20px',
            background: 'linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%)',
            color: '#ffffff',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '22px',
            fontWeight: '900',
            letterSpacing: '1px',
            boxShadow: '0 8px 24px rgba(79, 70, 229, 0.5)',
            border: '2px solid rgba(255, 255, 255, 0.2)',
            flexShrink: 0
          }}>
            {profile?.avatarInitials || 'AD'}
          </div>

          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '4px' }}>
              <span style={{ fontSize: '11px', color: '#10b981', display: 'flex', alignItems: 'center', gap: '4px', fontWeight: '600' }}>
                <span style={{ width: '6px', height: '6px', borderRadius: '50%', background: '#10b981', display: 'inline-block' }}></span>
                Aktif
              </span>
            </div>

            <h2 style={{
              fontSize: '17px',
              fontWeight: '800',
              color: '#ffffff',
              margin: '0 0 4px',
              whiteSpace: 'nowrap',
              overflow: 'hidden',
              textOverflow: 'ellipsis'
            }}>
              {profile?.name || 'Administrator'}
            </h2>

            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '11.5px', color: '#94a3b8' }}>
              <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                <Building2 size={12} color="#818cf8" />
                <span style={{ color: '#e2e8f0', fontWeight: '600' }}>{profile?.tenantName || 'Ruang Seragam'}</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* ─── 2. GANTI USERNAME & PASSWORD CARD ─── */}
      <div style={{
        background: '#ffffff',
        borderRadius: '22px',
        padding: '20px 18px',
        border: '1px solid #e2e8f0',
        boxShadow: '0 4px 20px rgba(0, 0, 0, 0.03)',
        marginBottom: '16px'
      }}>
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          marginBottom: '16px',
          paddingBottom: '12px',
          borderBottom: '1px solid #f1f5f9'
        }}>
          <div style={{
            width: '34px',
            height: '34px',
            borderRadius: '10px',
            background: 'rgba(79, 70, 229, 0.1)',
            color: '#4f46e5',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
          }}>
            <KeyRound size={18} />
          </div>
          <div>
            <h3 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
              Keamanan Akun & Kredensial
            </h3>
            <span style={{ fontSize: '11px', color: '#64748b' }}>
              Ubah username atau perbarui kata sandi
            </span>
          </div>
        </div>

        {/* Status Notification Toast */}
        {statusMsg && (
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '11px 14px',
            borderRadius: '12px',
            fontSize: '12px',
            fontWeight: '600',
            marginBottom: '16px',
            background: statusMsg.type === 'success' ? '#ecfdf5' : '#fef2f2',
            color: statusMsg.type === 'success' ? '#065f46' : '#991b1b',
            border: `1px solid ${statusMsg.type === 'success' ? '#a7f3d0' : '#fecaca'}`
          }}>
            {statusMsg.type === 'success' ? (
              <CheckCircle size={16} color="#059669" style={{ flexShrink: 0 }} />
            ) : (
              <AlertCircle size={16} color="#dc2626" style={{ flexShrink: 0 }} />
            )}
            <span>{statusMsg.text}</span>
          </div>
        )}

        <form onSubmit={handleUpdate} style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
          {/* Username / Name Input */}
          <div>
            <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
              Username / Nama Pengguna
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '12px',
              padding: '0 12px'
            }}>
              <User size={16} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type="text"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="Nama atau username Anda..."
                required
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  padding: '11px 8px',
                  fontSize: '13px',
                  fontWeight: '600',
                  color: '#0f172a'
                }}
              />
            </div>
          </div>

          {/* Email (Opsional) */}
          <div>
            <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
              Alamat Email
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '12px',
              padding: '0 12px'
            }}>
              <Mail size={16} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Alamat email aktif..."
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  padding: '11px 8px',
                  fontSize: '13px',
                  fontWeight: '600',
                  color: '#0f172a'
                }}
              />
            </div>
          </div>

          <div style={{ height: '1px', background: '#f1f5f9', margin: '4px 0' }} />

          {/* Current Password (Wajib jika ganti password) */}
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
              <label style={{ fontSize: '11.5px', fontWeight: '700', color: '#334155', margin: 0 }}>
                Kata Sandi Saat Ini
              </label>
              <span style={{ fontSize: '10px', color: '#94a3b8' }}>Wajib jika ingin ganti sandi</span>
            </div>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '12px',
              padding: '0 12px'
            }}>
              <Lock size={16} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type={showCurrentPass ? 'text' : 'password'}
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                placeholder="Masukkan kata sandi lama..."
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  padding: '11px 8px',
                  fontSize: '13px',
                  color: '#0f172a'
                }}
              />
              <button
                type="button"
                onClick={() => setShowCurrentPass(!showCurrentPass)}
                style={{ background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', padding: '4px' }}
              >
                {showCurrentPass ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
          </div>

          {/* New Password */}
          <div>
            <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
              Kata Sandi Baru
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '12px',
              padding: '0 12px'
            }}>
              <Lock size={16} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type={showNewPass ? 'text' : 'password'}
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder="Minimal 6 karakter..."
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  padding: '11px 8px',
                  fontSize: '13px',
                  color: '#0f172a'
                }}
              />
              <button
                type="button"
                onClick={() => setShowNewPass(!showNewPass)}
                style={{ background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', padding: '4px' }}
              >
                {showNewPass ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
          </div>

          {/* Confirm New Password */}
          <div>
            <label style={{ display: 'block', fontSize: '11.5px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
              Konfirmasi Kata Sandi Baru
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '12px',
              padding: '0 12px'
            }}>
              <Lock size={16} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type={showConfirmPass ? 'text' : 'password'}
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="Ulangi kata sandi baru..."
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  padding: '11px 8px',
                  fontSize: '13px',
                  color: '#0f172a'
                }}
              />
              <button
                type="button"
                onClick={() => setShowConfirmPass(!showConfirmPass)}
                style={{ background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', padding: '4px' }}
              >
                {showConfirmPass ? <EyeOff size={16} /> : <Eye size={16} />}
              </button>
            </div>
          </div>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={isLoading}
            style={{
              width: '100%',
              background: 'linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%)',
              color: '#ffffff',
              border: 'none',
              borderRadius: '14px',
              padding: '13px',
              fontSize: '13.5px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '8px',
              cursor: isLoading ? 'not-allowed' : 'pointer',
              boxShadow: '0 6px 18px rgba(79, 70, 229, 0.35)',
              marginTop: '4px',
              transition: 'all 0.2s ease',
              opacity: isLoading ? 0.75 : 1
            }}
          >
            {isLoading ? (
              <>
                <Loader2 size={16} style={{ animation: 'spin 1s linear infinite' }} />
                <span>Menyimpan...</span>
              </>
            ) : (
              <>
                <Save size={16} />
                <span>Simpan Perubahan</span>
              </>
            )}
          </button>
        </form>
      </div>

      {/* ─── 3. LOGOUT BUTTON ("Keluar") ─── */}
      <button
        type="button"
        onClick={onLogout}
        style={{
          width: '100%',
          background: '#ffffff',
          border: '1.5px solid #fecaca',
          borderRadius: '18px',
          padding: '14px',
          color: '#ef4444',
          fontSize: '14px',
          fontWeight: '700',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: '8px',
          cursor: 'pointer',
          boxShadow: '0 4px 15px rgba(239, 68, 68, 0.08)',
          transition: 'all 0.2s ease'
        }}
      >
        <LogOut size={17} color="#ef4444" />
        <span>Keluar</span>
      </button>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}

