@extends('employee.layout')

@section('title', 'Login Karyawan — Presensi & Keuangan')

@section('content')
<div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-md-5 col-lg-4">
        <!-- Logo/Header Section -->
        <div class="text-center mb-4">
            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="fas fa-users-cog fs-3"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Portal Karyawan</h3>
            <p class="text-muted small">Presensi & Informasi Kepegawaian</p>
        </div>

        <!-- Login Card -->
        <div class="card border shadow-sm rounded-3">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 mb-3" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('employee.login.post') }}">
                    @csrf

                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label text-dark small fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">
                                <i class="fas fa-user"></i>
                            </span>
                            <input
                                id="username"
                                type="text"
                                name="username"
                                placeholder="Masukkan username Anda"
                                value="{{ old('username') }}"
                                class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </div>
                        @error('username')
                            <span class="invalid-feedback d-block small mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label text-dark small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Masukkan password Anda"
                                class="form-control"
                                autocomplete="current-password"
                                required
                            >
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check mb-4">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label text-muted small">Ingat saya di perangkat ini</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2" id="btn-login">
                        <span class="fw-bold">Masuk</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-3 border-top">
                <small class="text-muted d-block" style="font-size: 0.72rem;">Hubungi Admin/HRD perusahaan Anda jika mengalami kendala login</small>
                <small class="text-muted fw-semibold mt-1 d-block" style="font-size: 0.75rem;">Dikelola oleh Jazmanudin</small>
            </div>
        </div>
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
