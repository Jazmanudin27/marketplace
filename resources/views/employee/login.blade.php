@extends('employee.layout')

@section('title', 'Login Karyawan — Presensi & Keuangan')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/employee-login.css') }}">
@endsection

@section('content')
<div class="login-container">
    <div class="logo-section">
        <div class="logo-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <h1>Portal Karyawan</h1>
        <p class="subtitle">Presensi & Informasi Kepegawaian</p>
    </div>

    <div class="glass-card login-card">
        @if ($errors->any())
            <div class="alert-danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('employee.login.post') }}">
            @csrf

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        placeholder="Masukkan username Anda"
                        value="{{ old('username') }}"
                        class="{{ $errors->has('username') ? 'is-invalid' : '' }}"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>
                @error('username')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Masukkan password Anda"
                        autocomplete="current-password"
                        required
                    >
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="btn-login" id="btn-login">
                <span>Masuk</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>

        <p class="footer-note">Hubungi Admin/HRD perusahaan Anda jika mengalami kendala login</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.querySelector('form').addEventListener('submit', function() {
        const btn = document.getElementById('btn-login');
        btn.querySelector('span').textContent = 'Memproses...';
        btn.disabled = true;
    });
</script>
@endsection
