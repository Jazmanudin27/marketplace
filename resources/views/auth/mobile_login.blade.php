<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Mobile | ERP App</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --secondary: #0ea5e9;
        }

        body {
            background-color: #0f172a;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            margin: 0;
            color: #f8fafc;
        }

        .mobile-card {
            width: 100%;
            max-width: 420px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            padding: 28px 24px;
            position: relative;
            overflow: hidden;
        }

        .mobile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 26px;
            margin: 0 auto 16px auto;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 0.5px;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 24px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .input-group-text {
            background-color: #0f172a;
            border-color: #334155;
            color: #64748b;
        }

        .form-control {
            background-color: #0f172a;
            border-color: #334155;
            color: #ffffff;
            font-size: 14px;
            padding: 12px 14px;
            border-radius: 10px;
        }

        .form-control:focus {
            background-color: #0f172a;
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
        }

        .btn-mobile-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 700;
            font-size: 15px;
            padding: 14px;
            border-radius: 12px;
            border: none;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease;
        }

        .btn-mobile-submit:active {
            transform: scale(0.98);
        }

        .badge-portal {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(14, 165, 233, 0.3);
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 16px;
        }

        .footer-link {
            font-size: 12px;
            color: #64748b;
            text-align: center;
            margin-top: 20px;
        }

        .footer-link a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="mobile-card">
        <div class="text-center">
            <span class="badge-portal"><i class="fas fa-mobile-alt me-1"></i> ERP MOBILE PORTAL</span>
            <div class="brand-icon">
                <i class="fas fa-mobile-screen-button"></i>
            </div>
            <h1 class="brand-title">Login Mobile ERP</h1>
            <p class="brand-subtitle">Masukkan akun Anda untuk akses Dasbor Mobile</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small border-0 mb-3" style="background-color: rgba(225, 29, 72, 0.2); color: #fecdd3;">
                <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('mobile.login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="login" class="form-label">Email atau Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" id="login" name="login" class="form-control" value="{{ old('login') }}" required placeholder="email@domain.com atau username" autofocus autocomplete="username">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Kata Sandi / Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
                    <button class="btn btn-outline-secondary input-group-text" type="button" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" style="background-color: #0f172a; border-color: #334155;">
                    <label class="form-check-label small text-muted" for="remember">
                        Ingat Saya
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-mobile-submit">
                <i class="fas fa-right-to-bracket me-2"></i> MASUK DASBOR MOBILE
            </button>
        </form>

        <div class="footer-link">
            Memakai Komputer Desktop? <a href="{{ route('login') }}">Login Versi Web</a>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
