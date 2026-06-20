<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login ke ERP Marketplace - Platform manajemen multi-channel terpusat">
    <title>Login | ERP Marketplace</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --secondary: #3b82f6;
            --bg-dark: #080c14;
            --card-bg: rgba(21, 31, 44, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body.auth-body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            margin: 0;
            padding: 20px;
        }

        /* Background floating orbs */
        .auth-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .auth-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            mix-blend-mode: screen;
            animation: floatOrb 20s infinite alternate ease-in-out;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.8) 0%, rgba(59, 130, 246, 0) 70%);
            top: -10%;
            left: -10%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.8) 0%, rgba(236, 72, 153, 0) 70%);
            bottom: -15%;
            right: -10%;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.8) 0%, rgba(20, 184, 166, 0) 70%);
            top: 35%;
            left: 45%;
            animation-delay: -10s;
        }

        @keyframes floatOrb {
            0% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(50px, -70px) scale(1.08);
            }
            100% {
                transform: translate(-40px, 60px) scale(0.92);
            }
        }

        .auth-container {
            width: 100%;
            max-width: 460px;
            z-index: 10;
            position: relative;
        }

        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4),
                        inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .auth-card:hover {
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5),
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 25px;
        }

        .auth-brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
            font-size: 26px;
            color: white;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .auth-brand:hover .auth-brand-icon {
            transform: scale(1.08) rotate(6deg);
        }

        .auth-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0 0 6px 0;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-subtitle {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 400;
            margin: 0;
        }

        /* Channel Logos */
        .channel-logos {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .channel-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .shopee-pill:hover {
            background: rgba(238, 77, 45, 0.1);
            border-color: rgba(238, 77, 45, 0.25);
            color: #ee4d2d;
            box-shadow: 0 0 10px rgba(238, 77, 45, 0.15);
        }

        .tiktok-pill:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
        }

        .tokped-pill:hover {
            background: rgba(0, 177, 86, 0.1);
            border-color: rgba(0, 177, 86, 0.25);
            color: #00b156;
            box-shadow: 0 0 10px rgba(0, 177, 86, 0.15);
        }

        /* Form Layout */
        .auth-form {
            animation: fadeInForm 0.45s ease forwards;
        }

        @keyframes fadeInForm {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            color: #cbd5e1;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .form-input {
            width: 100%;
            background: #111923;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px 42px;
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
            box-sizing: border-box;
        }

        .form-input::placeholder {
            color: #4b5563;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
            background: #131d2b;
        }

        .form-input:focus + .input-icon {
            color: var(--primary);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            outline: none;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: white;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 26px;
            cursor: pointer;
        }

        .check-input {
            appearance: none;
            -webkit-appearance: none;
            width: 17px;
            height: 17px;
            background: #111923;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
        }

        .check-input:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .check-input:checked::after {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 9px;
            color: white;
        }

        .check-label {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }

        .check-input:checked ~ .check-label,
        .form-check:hover .check-label {
            color: var(--text-main);
        }

        /* Submit Button */
        .btn-auth {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            padding: 13px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
            outline: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-auth:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-auth:active {
            transform: translateY(1px);
        }

        .btn-auth:disabled {
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.25);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* Alert block */
        .auth-alert {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 12px 15px;
            color: #f87171;
            font-size: 13px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }

        /* Demo Hints Box */
        .demo-hint {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 14px;
            padding: 15px;
            margin-top: 30px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .demo-hint p {
            margin: 0;
        }

        .demo-hint strong {
            color: var(--text-main);
        }
        
        .d-none {
            display: none !important;
        }
    </style>
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
                <p class="auth-subtitle">Kelola semua toko Anda dalam satu platform terpadu</p>
            </div>

            <div class="channel-logos">
                <div class="channel-pill shopee-pill"><i class="fas fa-shopping-bag"></i> Shopee</div>
                <div class="channel-pill tiktok-pill"><i class="fab fa-tiktok"></i> TikTok</div>
                <div class="channel-pill tokped-pill"><i class="fas fa-store"></i> Tokopedia</div>
            </div>

            <!-- Session Alert (Unified) -->
            @if ($errors->any())
                <div class="auth-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- UNIFIED LOGIN FORM -->
            <form action="{{ route('login.post') }}" method="POST" class="auth-form active" id="login-form">
                @csrf

                <div class="form-group">
                    <label for="login" class="form-label">Email Kantor atau Username Karyawan</label>
                    <div class="input-wrapper">
                        <input type="text" id="login" name="login" class="form-input"
                            placeholder="Email atau Username" value="{{ old('login') }}" required autofocus>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'eye-password')">
                            <i class="fas fa-eye" id="eye-password"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" class="check-input">
                    <label for="remember" class="check-label">Ingat saya</label>
                </div>

                <button type="submit" id="btn-login" class="btn-auth">
                    <span>Masuk ke Dashboard / Portal</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Unified Demo Hints -->
            <div class="demo-hint">
                <p><strong>Demo Login (Management):</strong><br>
                    • Admin: <code>admin@perusahaan-a.com</code> / password<br>
                    • Owner: <code>owner@perusahaan-a.com</code> / password<br>
                    • Gudang: <code>warehouse@perusahaan-a.com</code> / password
                </p>
                <hr style="border-color: rgba(255,255,255,0.06); margin: 12px 0;">
                <p><strong>Portal Karyawan:</strong><br>
                    Gunakan kredensial (Username & Password) yang dibuat oleh Admin/HRD pada halaman manajemen karyawan untuk melapor presensi.
                </p>
            </div>

        </div>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Set loading states on form submit
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('btn-login');
            btn.querySelector('span').textContent = 'Memproses Masuk...';
            btn.disabled = true;
        });
    </script>
</body>

</html>
