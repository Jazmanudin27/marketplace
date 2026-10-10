import React, { useState } from 'react';
import { Store, User, Lock, Eye, EyeOff, ArrowRight, ShieldCheck, AlertCircle } from 'lucide-react';
import { loginOwner } from '../api/authApi';

export default function LoginPage({ onLoginSuccess }) {
  const [loginInput, setLoginInput] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!loginInput || !password) {
      setErrorMessage('Silakan isi email/username dan kata sandi.');
      return;
    }

    setIsLoading(true);
    setErrorMessage('');

    try {
      const res = await loginOwner({ login: loginInput, password });
      if (res.success) {
        onLoginSuccess(res.user);
      } else {
        setErrorMessage(res.message || 'Email atau kata sandi tidak cocok.');
      }
    } catch (err) {
      setErrorMessage('Terjadi kendala koneksi ke server.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'space-between',
      background: 'linear-gradient(175deg, #0b1938 0%, #1e1b4b 45%, #0f172a 100%)',
      color: '#ffffff',
      padding: '32px 24px 28px',
      boxSizing: 'border-box'
    }}>
      {/* Top Brand & Header */}
      <div>
        {/* Brand Pill */}
        <div style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: '8px',
          background: 'rgba(255, 255, 255, 0.08)',
          border: '1px solid rgba(255, 255, 255, 0.15)',
          padding: '6px 14px',
          borderRadius: '999px',
          marginBottom: '28px'
        }}>
          <Store size={16} color="#38bdf8" />
          <span style={{ fontWeight: '800', fontSize: '13px', letterSpacing: '-0.2px' }}>ASPARTECH</span>
          <span style={{
            background: 'linear-gradient(135deg, #f59e0b, #ea580c)',
            fontSize: '9.5px',
            fontWeight: '800',
            padding: '2px 6px',
            borderRadius: '999px',
            textTransform: 'uppercase'
          }}>OWNER PRO</span>
        </div>

        {/* Title & Subtitle */}
        <h1 style={{
          fontSize: '28px',
          fontWeight: '800',
          letterSpacing: '-0.5px',
          marginBottom: '8px',
          lineHeight: '1.2'
        }}>
          Masuk ke <br />
          <span style={{
            background: 'linear-gradient(135deg, #38bdf8, #818cf8)',
            WebkitBackgroundClip: 'text',
            WebkitTextFillColor: 'transparent'
          }}>Portal Owner</span>
        </h1>

        <p style={{
          fontSize: '13px',
          color: '#94a3b8',
          lineHeight: '1.5',
          marginBottom: '28px'
        }}>
          Akses ringkasan transaksi, omset, scanner gudang, dan target komisi langsung dari HP Anda.
        </p>

        {/* Error Alert Box */}
        {errorMessage && (
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            background: 'rgba(239, 68, 68, 0.15)',
            border: '1px solid rgba(239, 68, 68, 0.35)',
            borderRadius: '14px',
            padding: '12px 14px',
            color: '#fca5a5',
            fontSize: '12.5px',
            marginBottom: '20px'
          }}>
            <AlertCircle size={18} style={{ flexShrink: 0 }} />
            <span>{errorMessage}</span>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
          {/* Email / Username Input */}
          <div>
            <label style={{
              display: 'block',
              fontSize: '12px',
              fontWeight: '600',
              color: '#cbd5e1',
              marginBottom: '7px'
            }}>
              Email atau Username
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: 'rgba(255, 255, 255, 0.06)',
              border: '1.5px solid rgba(255, 255, 255, 0.12)',
              borderRadius: '14px',
              padding: '0 14px',
              transition: 'border 0.2s ease'
            }}>
              <User size={18} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type="text"
                placeholder="Masukkan email terdaftar..."
                value={loginInput}
                onChange={(e) => setLoginInput(e.target.value)}
                required
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  color: '#ffffff',
                  fontSize: '14px',
                  padding: '14px 10px',
                  fontFamily: 'inherit'
                }}
              />
            </div>
          </div>

          {/* Password Input */}
          <div>
            <label style={{
              display: 'block',
              fontSize: '12px',
              fontWeight: '600',
              color: '#cbd5e1',
              marginBottom: '7px'
            }}>
              Kata Sandi
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: 'rgba(255, 255, 255, 0.06)',
              border: '1.5px solid rgba(255, 255, 255, 0.12)',
              borderRadius: '14px',
              padding: '0 14px'
            }}>
              <Lock size={18} color="#64748b" style={{ flexShrink: 0 }} />
              <input
                type={showPassword ? 'text' : 'password'}
                placeholder="Masukkan kata sandi..."
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  color: '#ffffff',
                  fontSize: '14px',
                  padding: '14px 10px',
                  fontFamily: 'inherit'
                }}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                style={{
                  background: 'none',
                  border: 'none',
                  color: '#64748b',
                  cursor: 'pointer',
                  padding: '4px',
                  display: 'flex',
                  alignItems: 'center'
                }}
              >
                {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </div>

          {/* Remember Me Option */}
          <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            fontSize: '12px',
            color: '#94a3b8'
          }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                style={{
                  width: '16px',
                  height: '16px',
                  accentColor: '#4f46e5',
                  cursor: 'pointer'
                }}
              />
              <span>Ingat sesi saya</span>
            </label>

            <span 
              onClick={() => alert('Silakan hubungi Super Admin untuk reset kata sandi.')}
              style={{ color: '#818cf8', fontWeight: '600', cursor: 'pointer' }}
            >
              Lupa sandi?
            </span>
          </div>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={isLoading}
            style={{
              width: '100%',
              background: 'linear-gradient(135deg, #4f46e5 0%, #2563eb 100%)',
              color: '#ffffff',
              border: 'none',
              borderRadius: '16px',
              padding: '15px',
              fontSize: '14.5px',
              fontWeight: '700',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '8px',
              cursor: isLoading ? 'not-allowed' : 'pointer',
              boxShadow: '0 8px 24px rgba(79, 70, 229, 0.4)',
              marginTop: '8px',
              transition: 'all 0.2s ease',
              opacity: isLoading ? 0.75 : 1
            }}
          >
            <span>{isLoading ? 'Memverifikasi...' : 'Masuk ke Dashboard'}</span>
            <ArrowRight size={17} />
          </button>
        </form>
      </div>

      {/* Footer Security Badge */}
      <div style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '6px',
        color: '#64748b',
        fontSize: '11.5px',
        marginTop: '32px'
      }}>
        <ShieldCheck size={14} color="#10b981" />
        <span>Terkoneksi API Database Pengguna (ERP Marketplace)</span>
      </div>
    </div>
  );
}
