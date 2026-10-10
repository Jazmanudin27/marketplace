<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal Login ASPARTECH ERP">
    <title>Masuk | ASPARTECH ERP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #6366f1;
            --dark-bg: #090d16;
            --dark-surface: #0f172a;
            --card-bg: rgba(15, 23, 42, 0.72);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --text-subtle: #64748b;
            --radius-xl: 20px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body.auth-body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow & Grid Background */
        .ambient-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(110px);
            opacity: 0.4;
            animation: floatOrb 20s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, #4f46e5 0%, rgba(79, 70, 229, 0) 70%);
            top: -120px;
            left: 20%;
        }

        .orb-2 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, #0ea5e9 0%, rgba(14, 165, 233, 0) 70%);
            bottom: -100px;
            right: 25%;
            animation-delay: -6s;
        }

        .grid-pattern {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 80%);
        }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 30px) scale(1.08); }
            100% { transform: translate(-30px, 50px) scale(0.95); }
        }

        /* Top Bar */
        .auth-header {
            position: relative;
            z-index: 10;
            padding: 24px 36px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .status-badge {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.15); box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Centered Main Container */
        .auth-container {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 24px;
            flex: 1;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-xl);
            padding: 42px 36px;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 
                0 24px 50px rgba(0, 0, 0, 0.45),
                0 0 1px 1px rgba(255, 255, 255, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.45s ease-out;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0ea5e9, #4f46e5, #8b5cf6);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Brand & Card Header */
        .card-brand-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #0ea5e9, #4f46e5);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.4px;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Error Alert */
        .auth-error-alert {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            color: #fca5a5;
            font-size: 13px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.35s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 8px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(2, 6, 23, 0.6);
            border: 1.5px solid rgba(255, 255, 255, 0.09);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .input-box:focus-within {
            border-color: #6366f1;
            background: rgba(2, 6, 23, 0.85);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .input-icon-left {
            position: absolute;
            left: 15px;
            color: #64748b;
            font-size: 15px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .input-box:focus-within .input-icon-left {
            color: #818cf8;
        }

        .styled-input {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: #ffffff;
            font-size: 14px;
            font-family: inherit;
            padding: 13px 14px 13px 44px;
        }

        .styled-input::placeholder {
            color: #475569;
        }

        .toggle-pwd-btn {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 12px 14px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            outline: none;
        }

        .toggle-pwd-btn:hover {
            color: #cbd5e1;
        }

        /* Options Row */
        .form-row-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 22px 0 24px;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-input {
            appearance: none;
            -webkit-appearance: none;
            width: 17px;
            height: 17px;
            background: rgba(2, 6, 23, 0.7);
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            border-radius: 5px;
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            margin: 0;
        }

        .checkbox-input:checked {
            background: #4f46e5;
            border-color: #4f46e5;
            box-shadow: 0 0 8px rgba(79, 70, 229, 0.4);
        }

        .checkbox-input:checked::after {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 9px;
            color: #ffffff;
            position: absolute;
        }

        .checkbox-label {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        .custom-checkbox:hover .checkbox-label {
            color: #e2e8f0;
        }

        .helper-link {
            font-size: 12.5px;
            font-weight: 600;
            color: #818cf8;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .helper-link:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-submit-v2 {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border: none;
            border-radius: var(--radius-md);
            padding: 13px 18px;
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.32);
            outline: none;
        }

        .btn-submit-v2:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 12px 26px rgba(79, 70, 229, 0.45);
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%);
        }

        .btn-submit-v2:active {
            transform: translateY(1px);
        }

        .btn-submit-v2:disabled {
            background: #334155;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 640px) {
            .auth-card {
                padding: 34px 24px;
            }

            .auth-header {
                padding: 16px 20px;
            }
        }
    </style>
</head>

<body class="auth-body">

    <!-- Ambient Glow Background -->
    <div class="ambient-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="grid-pattern"></div>
    </div>

    <!-- Minimal Header -->
    <header class="auth-header">
        <div class="status-badge">
            <span class="pulse-dot"></span>
            <span>Sistem Online</span>
        </div>
    </header>

    <!-- Centered Form Container -->
    <main class="auth-container">
        <div class="auth-card">
            <div class="card-brand-header">
                <div class="brand-logo-icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <h1 class="brand-title">ASPARTECH ERP</h1>
                <p class="brand-subtitle">Masuk untuk mengakses sistem</p>
            </div>

            <!-- Error Notification -->
            @if ($errors->any())
                <div class="auth-error-alert" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('login.post') }}" method="POST" id="login-form" autocomplete="on">
                @csrf

                <!-- Identifier -->
                <div class="form-group">
                    <label for="login" class="form-label">Email atau Username</label>
                    <div class="input-box">
                        <i class="fas fa-user input-icon-left"></i>
                        <input 
                            type="text" 
                            id="login" 
                            name="login" 
                            class="styled-input" 
                            placeholder="Masukkan email atau username"
                            value="{{ old('login') }}" 
                            required 
                            autofocus 
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <div class="input-box">
                        <i class="fas fa-lock input-icon-left"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="styled-input" 
                            placeholder="Masukkan kata sandi" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-pwd-btn" id="toggle-pwd-btn" title="Lihat Kata Sandi" aria-label="Lihat Kata Sandi">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-row-options">
                    <label class="custom-checkbox" for="remember">
                        <input type="checkbox" id="remember" name="remember" class="checkbox-input" {{ old('remember') ? 'checked' : '' }}>
                        <span class="checkbox-label">Ingat sesi saya</span>
                    </label>
                    <a href="#" class="helper-link" onclick="alert('Silakan hubungi Super Admin untuk mereset kata sandi Anda.'); return false;">
                        Lupa sandi?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="btn-login-submit" class="btn-submit-v2">
                    <span id="btn-text">Masuk</span>
                    <i class="fas fa-arrow-right" id="btn-arrow"></i>
                </button>
            </form>
        </div>
    </main>

    <!-- Centered Form Container -->

    <!-- Interactive Scripts -->
    <script>
        // Password Visibility Toggle
        const toggleBtn = document.getElementById('toggle-pwd-btn');
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        if (toggleBtn && pwdInput && eyeIcon) {
            toggleBtn.addEventListener('click', function() {
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    pwdInput.type = 'password';
                    eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }

        // Form Submit Loading Feedback
        const loginForm = document.getElementById('login-form');
        const submitBtn = document.getElementById('btn-login-submit');
        const btnText = document.getElementById('btn-text');
        const btnArrow = document.getElementById('btn-arrow');

        if (loginForm && submitBtn) {
            loginForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                if (btnText) btnText.textContent = 'Memverifikasi...';
                if (btnArrow) {
                    btnArrow.classList.remove('fa-arrow-right');
                    btnArrow.classList.add('fa-spinner', 'fa-spin');
                }
            });
        }
    </script>
</body>

</html>
