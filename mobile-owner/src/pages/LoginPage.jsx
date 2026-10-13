import React, { useState } from 'react';
import { User, Lock, Eye, EyeOff, ArrowRight, AlertCircle, Loader2 } from 'lucide-react';
import { loginOwner } from '../api/authApi';
import { showAlert } from '../utils/alerts';

export default function LoginPage({ onLoginSuccess }) {
  const [loginInput, setLoginInput] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [focusedInput, setFocusedInput] = useState(null);

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
      justifyContent: 'center',
      alignItems: 'center',
      background: 'radial-gradient(circle at 50% 15%, rgba(79, 70, 229, 0.32) 0%, transparent 60%), radial-gradient(circle at 85% 85%, rgba(6, 182, 212, 0.18) 0%, transparent 50%), #070d1e',
      color: '#ffffff',
      padding: '24px 20px',
      boxSizing: 'border-box',
      position: 'relative',
      overflow: 'hidden'
    }}>
      {/* Ambient background glow ring */}
      <div style={{
        position: 'absolute',
        top: '-70px',
        left: '50%',
        transform: 'translateX(-50%)',
        width: '320px',
        height: '320px',
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(99, 102, 241, 0.28) 0%, transparent 70%)',
        filter: 'blur(45px)',
        pointerEvents: 'none'
      }} />

      {/* Main Glassmorphism Card */}
      <div style={{
        width: '100%',
        maxWidth: '410px',
        background: 'rgba(15, 23, 42, 0.78)',
        backdropFilter: 'blur(28px)',
        WebkitBackdropFilter: 'blur(28px)',
        border: '1px solid rgba(255, 255, 255, 0.12)',
        borderRadius: '30px',
        padding: '36px 24px 30px',
        boxShadow: '0 25px 60px -15px rgba(0, 0, 0, 0.75), 0 0 35px rgba(79, 70, 229, 0.18)',
        boxSizing: 'border-box',
        position: 'relative',
        zIndex: 1
      }}>
        {/* Brand Emblem */}
        <div style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          marginBottom: '30px'
        }}>
          <div style={{
            width: '74px',
            height: '74px',
            borderRadius: '22px',
            background: 'linear-gradient(135deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.03))',
            border: '1.5px solid rgba(255, 255, 255, 0.22)',
            boxShadow: '0 12px 28px rgba(79, 70, 229, 0.35)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '12px',
            boxSizing: 'border-box',
            marginBottom: '14px'
          }}>
            <img 
              src="/ruang-market.png" 
              alt="Logo" 
              style={{ 
                width: '100%', 
                height: '100%', 
                objectFit: 'contain',
                filter: 'drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3))'
              }} 
            />
          </div>

          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
          }}>
            <span style={{
              fontWeight: '900',
              fontSize: '18px',
              letterSpacing: '0.4px',
              color: '#ffffff'
            }}>
              RUANG <span style={{
                background: 'linear-gradient(135deg, #818cf8, #38bdf8)',
                WebkitBackgroundClip: 'text',
                WebkitTextFillColor: 'transparent'
              }}>MARKET</span>
            </span>
          </div>
        </div>

        {/* Error Alert Box */}
        {errorMessage && (
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            background: 'rgba(239, 68, 68, 0.14)',
            border: '1px solid rgba(239, 68, 68, 0.35)',
            borderRadius: '16px',
            padding: '12px 14px',
            color: '#fca5a5',
            fontSize: '12.5px',
            fontWeight: '500',
            marginBottom: '20px'
          }}>
            <AlertCircle size={18} style={{ flexShrink: 0, color: '#f87171' }} />
            <span>{errorMessage}</span>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
          {/* Email / Username Input */}
          <div>
            <label style={{
              display: 'block',
              fontSize: '11px',
              fontWeight: '700',
              letterSpacing: '0.4px',
              color: '#94a3b8',
              textTransform: 'uppercase',
              marginBottom: '7px'
            }}>
              Email atau Username
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: focusedInput === 'login' ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.04)',
              border: focusedInput === 'login' ? '1.5px solid #6366f1' : '1.5px solid rgba(255, 255, 255, 0.1)',
              boxShadow: focusedInput === 'login' ? '0 0 16px rgba(99, 102, 241, 0.35)' : 'none',
              borderRadius: '16px',
              padding: '4px 14px',
              transition: 'all 0.25s ease'
            }}>
              <div style={{
                width: '32px',
                height: '32px',
                borderRadius: '10px',
                background: focusedInput === 'login' ? 'rgba(99, 102, 241, 0.25)' : 'rgba(255, 255, 255, 0.06)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: '8px',
                flexShrink: 0,
                transition: 'all 0.25s ease'
              }}>
                <User size={16} color={focusedInput === 'login' ? '#a5b4fc' : '#64748b'} />
              </div>
              <input
                type="text"
                placeholder="Masukkan email terdaftar..."
                value={loginInput}
                onChange={(e) => setLoginInput(e.target.value)}
                onFocus={() => setFocusedInput('login')}
                onBlur={() => setFocusedInput(null)}
                required
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  color: '#ffffff',
                  fontSize: '14px',
                  fontWeight: '500',
                  padding: '12px 4px',
                  fontFamily: 'inherit'
                }}
              />
            </div>
          </div>

          {/* Password Input */}
          <div>
            <label style={{
              display: 'block',
              fontSize: '11px',
              fontWeight: '700',
              letterSpacing: '0.4px',
              color: '#94a3b8',
              textTransform: 'uppercase',
              marginBottom: '7px'
            }}>
              Kata Sandi
            </label>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              background: focusedInput === 'password' ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.04)',
              border: focusedInput === 'password' ? '1.5px solid #6366f1' : '1.5px solid rgba(255, 255, 255, 0.1)',
              boxShadow: focusedInput === 'password' ? '0 0 16px rgba(99, 102, 241, 0.35)' : 'none',
              borderRadius: '16px',
              padding: '4px 14px',
              transition: 'all 0.25s ease'
            }}>
              <div style={{
                width: '32px',
                height: '32px',
                borderRadius: '10px',
                background: focusedInput === 'password' ? 'rgba(99, 102, 241, 0.25)' : 'rgba(255, 255, 255, 0.06)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: '8px',
                flexShrink: 0,
                transition: 'all 0.25s ease'
              }}>
                <Lock size={16} color={focusedInput === 'password' ? '#a5b4fc' : '#64748b'} />
              </div>
              <input
                type={showPassword ? 'text' : 'password'}
                placeholder="Masukkan kata sandi..."
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                onFocus={() => setFocusedInput('password')}
                onBlur={() => setFocusedInput(null)}
                required
                style={{
                  width: '100%',
                  background: 'transparent',
                  border: 'none',
                  outline: 'none',
                  color: '#ffffff',
                  fontSize: '14px',
                  fontWeight: '500',
                  padding: '12px 4px',
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
                  padding: '6px',
                  display: 'flex',
                  alignItems: 'center',
                  transition: 'color 0.2s ease'
                }}
              >
                {showPassword ? <EyeOff size={18} color="#94a3b8" /> : <Eye size={18} color="#64748b" />}
              </button>
            </div>
          </div>

          {/* Remember Me & Forgot Password */}
          <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            fontSize: '12px',
            color: '#94a3b8',
            paddingTop: '2px'
          }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer', userSelect: 'none' }}>
              <input
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                style={{
                  width: '16px',
                  height: '16px',
                  borderRadius: '5px',
                  accentColor: '#4f46e5',
                  cursor: 'pointer'
                }}
              />
              <span style={{ color: '#cbd5e1', fontWeight: '500' }}>Ingat sesi saya</span>
            </label>

            <span 
              onClick={() => showAlert({
                icon: 'info',
                title: 'Lupa Kata Sandi',
                text: 'Silakan hubungi Super Admin untuk bantuan reset kata sandi akun Anda.',
                confirmButtonText: 'Tutup'
              })}
              style={{ 
                color: '#818cf8', 
                fontWeight: '600', 
                cursor: 'pointer',
                transition: 'color 0.2s ease'
              }}
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
              background: 'linear-gradient(135deg, #4f46e5 0%, #2563eb 50%, #06b6d4 100%)',
              color: '#ffffff',
              border: 'none',
              borderRadius: '16px',
              padding: '15px',
              fontSize: '14.5px',
              fontWeight: '700',
              letterSpacing: '0.3px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '10px',
              cursor: isLoading ? 'not-allowed' : 'pointer',
              boxShadow: '0 10px 26px -4px rgba(79, 70, 229, 0.55), 0 4px 10px rgba(6, 182, 212, 0.3)',
              marginTop: '4px',
              transition: 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)',
              opacity: isLoading ? 0.8 : 1
            }}
          >
            {isLoading ? (
              <>
                <Loader2 size={18} style={{ animation: 'spin 1s linear infinite' }} />
                <span>Memverifikasi...</span>
              </>
            ) : (
              <>
                <span>Masuk ke Akun</span>
                <ArrowRight size={17} />
              </>
            )}
          </button>

        </form>
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
