<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login ke ERP Marketplace - Platform manajemen multi-channel terpusat">
    <title>Login | ERP Marketplace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="auth-body">

    <div class="auth-bg">
        <div class="auth-orb orb-1"></div>
        <div class="auth-orb orb-2"></div>
        <div class="auth-orb orb-3"></div>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <i class="fas fa-store-alt"></i>
                </div>
                <h1 class="auth-title">ERP Marketplace</h1>
                <p class="auth-subtitle">Kelola semua toko Anda dalam satu platform</p>
            </div>

            <div class="channel-logos">
                <div class="channel-pill shopee-pill"><i class="fas fa-shopping-bag"></i> Shopee</div>
                <div class="channel-pill tiktok-pill"><i class="fab fa-tiktok"></i> TikTok</div>
                <div class="channel-pill tokped-pill"><i class="fas fa-store"></i> Tokopedia</div>
            </div>

            <form action="{{ route('login.post') }}" method="POST" class="auth-form">
                @csrf

                @if ($errors->any())
                    <div class="auth-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-input"
                            placeholder="contoh@email.com" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••"
                            required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" class="check-input">
                    <label for="remember" class="check-label">Ingat saya</label>
                </div>

                <button type="submit" id="btn-login" class="btn-auth">
                    <span>Masuk ke Dashboard</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="demo-hint">
                <p><strong>Demo Login:</strong><br>
                    admin@perusahaan-a.com / password</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>
