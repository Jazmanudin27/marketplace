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
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #0ea5e9;
            --bg-light: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body.auth-body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Top Header */
        .auth-header {
            height: 80px;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 80px;
            box-sizing: border-box;
            width: 100%;
            z-index: 100;
        }

        .header-logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .header-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .header-logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }

        .header-help-link {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .header-help-link:hover {
            color: var(--primary);
        }

        /* Main Container */
        .auth-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 80px;
            box-sizing: border-box;
            gap: 60px;
        }

        /* Left Side: Illustration */
        .auth-left {
            flex: 1.2;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 550px;
        }

        .illustration-container {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Right Side: Form Card */
        .auth-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 440px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.02);
            box-sizing: border-box;
        }

        .auth-title {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 28px;
        }

        /* Form Layout */
        .form-group {
            margin-bottom: 24px;
            border-bottom: 1.5px solid #e2e8f0;
            transition: border-color 0.3s ease;
            position: relative;
        }

        .form-group:focus-within {
            border-color: var(--primary);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 4px;
            color: #94a3b8;
            font-size: 15px;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .form-group:focus-within .input-icon {
            color: var(--primary);
        }

        .form-input {
            width: 100%;
            border: none;
            background: transparent;
            padding: 12px 10px 12px 32px;
            color: #0f172a;
            font-size: 15px;
            outline: none;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .input-actions-right {
            position: absolute;
            right: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toggle-password {
            background: none;
            border: none;
            color: #94a3b8;
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
            color: #0f172a;
        }

        .action-separator {
            color: #cbd5e1;
            font-weight: 300;
            font-size: 14px;
            user-select: none;
        }

        .forgot-link {
            color: #4f46e5;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
            cursor: pointer;
            width: fit-content;
        }

        .check-input {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
            background: #ffffff;
            margin: 0;
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
            position: absolute;
        }

        .check-label {
            color: #64748b;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            transition: color 0.2s ease;
        }

        .form-check:hover .check-label {
            color: #0f172a;
        }

        /* Submit Button */
        .btn-auth {
            width: 100%;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 14px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
            outline: none;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15);
        }

        .btn-auth:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
        }

        .btn-auth:active {
            transform: translateY(1px);
        }

        .btn-auth:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* Alert block */
        .auth-alert {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 12px 15px;
            color: #b91c1c;
            font-size: 13.5px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-4px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(4px);
            }
        }

        /* Terms & Privacy footer */
        .auth-terms {
            margin-top: 24px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
            text-align: left;
        }

        .auth-terms a {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-terms a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        /* Bottom helper link */
        .auth-footer-link {
            margin-top: 28px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .auth-footer-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .auth-footer-link a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        /* Bottom Footer Bar (Card) */
        .auth-footer {
            height: 70px;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 80px;
            box-sizing: border-box;
            width: 100%;
            z-index: 100;
        }

        .footer-text {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-text strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* Responsive Layout */
        @media (max-width: 992px) {
            .auth-header,
            .auth-footer {
                padding: 0 40px;
            }

            .auth-main {
                padding: 40px;
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .auth-header,
            .auth-footer {
                padding: 0 20px;
                height: 60px;
            }

            .auth-main {
                flex-direction: column;
                padding: 30px 20px;
                gap: 20px;
                justify-content: center;
            }

            .auth-left {
                display: none;
                /* Hide illustration on mobile */
            }

            .auth-right {
                justify-content: center;
                width: 100%;
            }

            .auth-card {
                max-width: 100%;
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body class="auth-body">

    <!-- Top Header -->
    <header class="auth-header">
        <a href="/" class="header-logo-container">
            <div class="header-logo-icon">
                <i class="fas fa-store-alt"></i>
            </div>
            <span class="header-logo-text">ERP Marketplace</span>
        </a>
        <a href="#" class="header-help-link">
            <i class="far fa-question-circle"></i> Butuh Bantuan?
        </a>
    </header>

    <!-- Main Content Area -->
    <main class="auth-main">

        <!-- Left Side: Marketplace Illustration -->
        <div class="auth-left">
            <div class="illustration-container">
                <svg viewBox="0 0 500 400" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- Soft shadow for badges and cards -->
                        <filter id="soft-shadow" x="-10%" y="-10%" width="120%" height="120%">
                            <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#0f172a"
                                flood-opacity="0.08" />
                        </filter>
                        <!-- Glow filter for charts and connections -->
                        <filter id="neon-glow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="3" result="blur" />
                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                        </filter>
                        <!-- Gradients -->
                        <linearGradient id="screenGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#ffffff" />
                            <stop offset="100%" stop-color="#f8fafc" />
                        </linearGradient>
                        <linearGradient id="blueGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#60a5fa" />
                            <stop offset="100%" stop-color="#3b82f6" />
                        </linearGradient>
                        <linearGradient id="purpleGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#a78bfa" />
                            <stop offset="100%" stop-color="#8b5cf6" />
                        </linearGradient>
                        <linearGradient id="indigoGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#818cf8" />
                            <stop offset="100%" stop-color="#4f46e5" />
                        </linearGradient>
                        <linearGradient id="shopeeGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#ff7a59" />
                            <stop offset="100%" stop-color="#ee4d2d" />
                        </linearGradient>
                        <linearGradient id="tokpedGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#2ecc71" />
                            <stop offset="100%" stop-color="#03ac0e" />
                        </linearGradient>
                    </defs>

                    <!-- Background Soft Blobs & Grid Pattern -->
                    <circle cx="250" cy="200" r="140" fill="#eff6ff" opacity="0.6" />
                    <circle cx="120" cy="150" r="60" fill="#fef2f2" opacity="0.7" />
                    <circle cx="380" cy="280" r="80" fill="#ecfdf5" opacity="0.6" />

                    <!-- Floating Sync Lines (Dashed paths connecting to monitor) -->
                    <!-- Sync Line 1 (Shopee) -->
                    <path d="M 120 120 C 180 120, 180 180, 210 180" fill="none" stroke="#ee4d2d"
                        stroke-width="2.5" stroke-dasharray="6,6" opacity="0.7" />
                    <!-- Sync Line 2 (TikTok Shop) -->
                    <path d="M 250 80 C 250 120, 250 140, 250 150" fill="none" stroke="#475569" stroke-width="2.5"
                        stroke-dasharray="6,6" opacity="0.6" />
                    <!-- Sync Line 3 (Tokopedia) -->
                    <path d="M 380 120 C 320 120, 320 180, 290 180" fill="none" stroke="#03ac0e"
                        stroke-width="2.5" stroke-dasharray="6,6" opacity="0.7" />

                    <!-- Modern Desk Line -->
                    <line x1="80" y1="320" x2="420" y2="320" stroke="#cbd5e1"
                        stroke-width="3" stroke-linecap="round" />

                    <!-- Sleek Computer Monitor Setup -->
                    <!-- Base & Stand -->
                    <path d="M 230 320 L 270 320 L 262 270 L 238 270 Z" fill="#94a3b8" />
                    <ellipse cx="250" cy="320" rx="35" ry="5" fill="#64748b" />
                    <!-- Screen Frame -->
                    <rect x="170" y="140" width="160" height="110" rx="10" fill="#1e293b"
                        filter="url(#soft-shadow)" />
                    <rect x="174" y="144" width="152" height="96" rx="6" fill="url(#screenGrad)" />
                    <!-- Screen Stand Joint -->
                    <circle cx="250" cy="250" r="6" fill="#475569" />

                    <!-- Dashboard Elements inside Screen -->
                    <!-- Sidebar Mockup -->
                    <rect x="178" y="148" width="28" height="88" rx="3" fill="#f1f5f9" />
                    <circle cx="192" cy="158" r="5" fill="#cbd5e1" />
                    <rect x="182" y="170" width="20" height="3" rx="1.5" fill="#e2e8f0" />
                    <rect x="182" y="178" width="20" height="3" rx="1.5" fill="#e2e8f0" />
                    <rect x="182" y="186" width="20" height="3" rx="1.5" fill="#e2e8f0" />
                    <!-- Main Dashboard Window -->
                    <!-- Header Bar -->
                    <rect x="210" y="148" width="112" height="14" rx="3" fill="#ffffff" />
                    <rect x="214" y="153" width="35" height="4" rx="2" fill="#cbd5e1" />
                    <circle cx="310" cy="155" r="2.5" fill="#818cf8" />
                    <!-- Sales Chart Mockup -->
                    <rect x="210" y="166" width="112" height="42" rx="4" fill="#ffffff" />
                    <path d="M 214 200 L 230 190 L 245 195 L 260 178 L 275 188 L 290 172 L 305 180 L 318 170"
                        fill="none" stroke="url(#indigoGrad)" stroke-width="2.5" stroke-linecap="round"
                        stroke-linejoin="round" filter="url(#neon-glow)" />
                    <path
                        d="M 214 200 L 230 190 L 245 195 L 260 178 L 275 188 L 290 172 L 305 180 L 318 170 L 318 204 L 214 204 Z"
                        fill="url(#blueGrad)" opacity="0.08" />
                    <!-- Metric Cards Mockup (Left & Right) -->
                    <rect x="210" y="212" width="53" height="20" rx="3" fill="#ffffff" />
                    <rect x="214" y="216" width="20" height="3" rx="1.5" fill="#94a3b8" />
                    <rect x="214" y="222" width="30" height="5" rx="2.5" fill="url(#blueGrad)" />

                    <rect x="269" y="212" width="53" height="20" rx="3" fill="#ffffff" />
                    <rect x="273" y="216" width="20" height="3" rx="1.5" fill="#94a3b8" />
                    <rect x="273" y="222" width="25" height="5" rx="2.5" fill="url(#purpleGrad)" />

                    <!-- Floating Marketplace Badges -->
                    <!-- Shopee Badge (Left) -->
                    <g transform="translate(90, 90)" filter="url(#soft-shadow)">
                        <circle cx="30" cy="30" r="26" fill="url(#shopeeGrad)" />
                        <!-- Shopping bag SVG Icon -->
                        <path
                            d="M 30 18 c -2.2 0 -4 1.8 -4 4 l 0 2 L 34 24 l 0 -2 c 0 -2.2 -1.8 -4 -4 -4 Z M 22 26 L 38 26 L 39.5 39 c 0 2.2 -1.8 4 -4 4 L 24.5 43 c -2.2 0 -4 -1.8 -4 -4 Z"
                            fill="white" />
                    </g>
                    <!-- TikTok Shop Badge (Top Center) -->
                    <g transform="translate(220, 30)" filter="url(#soft-shadow)">
                        <circle cx="30" cy="30" r="26" fill="#09090b" />
                        <!-- TikTok music note SVG Icon -->
                        <path
                            d="M 33 16 L 33 32 c 0 3 -2.2 5 -5 5 s -5 -2.2 -5 -5 s 2.2 -5 5 -5 c .7 0 1.3 .2 1.8 .5 L 29.8 22 L 29.8 16 Z"
                            fill="white" />
                        <path d="M 30 16 c 4 0 5 3 5 3" stroke="#ff0050" stroke-width="2.5" stroke-linecap="round"
                            fill="none" transform="translate(0,0)" />
                        <path d="M 30 16 c 4 0 5 3 5 3" stroke="#00f2fe" stroke-width="2.5" stroke-linecap="round"
                            fill="none" transform="translate(-1,-1)" />
                    </g>
                    <!-- Tokopedia Badge (Right) -->
                    <g transform="translate(350, 90)" filter="url(#soft-shadow)">
                        <circle cx="30" cy="30" r="26" fill="url(#tokpedGrad)" />
                        <!-- Shop/Store SVG Icon -->
                        <path
                            d="M 18 24 L 42 24 L 44 32 L 16 32 Z M 19 32 L 19 41 c 0 1.5 1 2 2 2 l 18 0 c 1.2 0 2 -.5 2 -2 l 0 -9"
                            fill="white" />
                        <circle cx="30" cy="36" r="3" fill="#03ac0e" />
                    </g>

                    <!-- Office Props on Desk (Flat style) -->
                    <!-- Coffee Cup -->
                    <path d="M 115 320 L 115 298 A 12 12 0 0 1 127 298 L 127 320 Z" fill="#475569" />
                    <rect x="112" y="295" width="18" height="4" rx="2" fill="#64748b" />
                    <!-- Handle -->
                    <path d="M 127 302 c 3 0, 5 2, 5 5 c 0 3, -2 5, -5 5" fill="none" stroke="#64748b"
                        stroke-width="2.5" />

                    <!-- Keyboard Mockup -->
                    <rect x="180" y="278" width="60" height="4" rx="2" fill="#cbd5e1" />
                    <!-- Mouse Mockup -->
                    <rect x="250" y="278" width="10" height="4" rx="2" fill="#cbd5e1" />

                    <!-- Small Pot Plant -->
                    <path d="M 365 320 L 380 320 L 377 304 L 368 304 Z" fill="#d97706" />
                    <!-- Leaf 1 -->
                    <path d="M 372 304 C 365 292, 368 284, 370 286 C 372 288, 374 296, 372 304" fill="#10b981" />
                    <!-- Leaf 2 -->
                    <path d="M 373 304 C 380 292, 377 284, 375 286 C 373 288, 371 296, 373 304" fill="#059669" />
                </svg>
            </div>
        </div>

        <!-- Right Side: Login Form Card -->
        <div class="auth-right">
            <div class="auth-card">
                <h1 class="auth-title">Log In</h1>

                <!-- Session Alert (Unified) -->
                @if ($errors->any())
                    <div class="auth-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- UNIFIED LOGIN FORM -->
                <form action="{{ route('login.post') }}" method="POST" id="login-form">
                    @csrf

                    <!-- Username Field -->
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="text" id="login" name="login" class="form-input"
                                placeholder="Email atau Username" value="{{ old('login') }}" required autofocus>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-input"
                                placeholder="Password" required>
                            <i class="fas fa-lock input-icon"></i>

                            <div class="input-actions-right">
                                <button type="button" class="toggle-password"
                                    onclick="togglePassword('password', 'eye-password')">
                                    <i class="fas fa-eye" id="eye-password"></i>
                                </button>
                                <span class="action-separator">|</span>
                                <a href="#" class="forgot-link">Lupa?</a>
                            </div>
                        </div>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember" class="check-input">
                        <label for="remember" class="check-label">Ingat saya</label>
                    </div>

                    <!-- Submit Action -->
                    <button type="submit" id="btn-login" class="btn-auth">
                        <span>Masuk</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <!-- Terms Notice -->
                <p class="auth-terms">
                    Dengan melanjutkan, saya menyetujui <a href="{{ route('terms-of-service') }}"
                        target="_blank">Ketentuan Layanan</a>, <a href="{{ route('privacy-policy') }}"
                        target="_blank">Kebijakan Perlindungan Data</a>, dan <a href="#">Aturan Kemitraan
                        Mitra</a> ERP Marketplace.
                </p>

                <!-- Footer Help Link -->
                <div class="auth-footer-link">
                    Belum punya akun? <a href="#">Hubungi Admin</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Footer Bar (Symmetrical to Header) -->
    <footer class="auth-footer">
        <div class="footer-text">
            <span>© {{ date('Y') }} ERP Marketplace. All rights reserved.</span>
        </div>
        <div class="footer-text">
            <i class="fas fa-user-shield text-primary"></i> Dikelola oleh <strong>Jazmanudin</strong>
        </div>
    </footer>

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
