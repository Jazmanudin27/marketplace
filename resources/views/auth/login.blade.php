<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal Login ERP Marketplace V2 - Platform Manajemen Multi-Channel, Gudang, dan Keuangan Terpadu">
    <title>Login Portal ERP V2 | ASPARTECH ERP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.35);
            --secondary: #0ea5e9;
            --accent: #8b5cf6;
            --dark-bg: #090d16;
            --dark-surface: #111827;
            --dark-card: rgba(17, 24, 39, 0.85);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --text-dark: #0f172a;
            --radius-xl: 24px;
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body.auth-body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Dynamic Animated Background Mesh */
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
            filter: blur(100px);
            opacity: 0.45;
            animation: floatOrb 18s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #4f46e5 0%, rgba(79, 70, 229, 0) 70%);
            top: -100px;
            left: -100px;
            animation-duration: 22s;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #0ea5e9 0%, rgba(14, 165, 233, 0) 70%);
            bottom: -80px;
            right: 10%;
            animation-duration: 18s;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, #8b5cf6 0%, rgba(139, 92, 246, 0) 70%);
            top: 40%;
            left: 35%;
            opacity: 0.3;
            animation-duration: 25s;
            animation-delay: -10s;
        }

        .grid-pattern {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 80%);
        }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 40px) scale(1.1); }
            100% { transform: translate(-40px, 70px) scale(0.95); }
        }

        /* Top Header Navbar */
        .auth-nav {
            position: relative;
            z-index: 10;
            padding: 24px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
        }

        .brand-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
            transition: transform 0.2s ease;
        }

        .brand-pill:hover {
            transform: scale(1.02);
        }

        .brand-logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #0ea5e9, #4f46e5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-text-wrap {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-family: 'Roboto', sans-serif;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.3px;
            background: linear-gradient(120deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-sub {
            font-size: 11px;
            font-weight: 600;
            color: #38bdf8;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .nav-right-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .system-badge {
            background: rgba(79, 70, 229, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.35);
            color: #a5b4fc;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
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
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.15); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Main Container */
        .auth-container {
            position: relative;
            z-index: 10;
            flex: 1;
            display: grid;
            grid-template-columns: 1.15fr 0.95fr;
            gap: 56px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 20px 48px 48px;
            align-items: center;
        }

        /* Left Showcase Column */
        .showcase-column {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .hero-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 8px 16px;
            border-radius: 999px;
            color: #93c5fd;
            font-size: 13px;
            font-weight: 600;
            width: fit-content;
            backdrop-filter: blur(10px);
        }

        .hero-badge-tag i {
            color: #38bdf8;
        }

        .hero-heading {
            font-family: 'Roboto', sans-serif;
            font-size: 44px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.8px;
            color: #ffffff;
        }

        .hero-heading .gradient-text {
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 50%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.65;
            max-width: 580px;
        }

        /* Interactive Showcase Cards */
        .feature-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 8px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-lg);
            padding: 20px;
            backdrop-filter: blur(16px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-light), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .card-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 14px;
        }

        .icon-indigo { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.25); }
        .icon-sky { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.25); }
        .icon-emerald { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }
        .icon-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }

        .feature-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 6px;
        }

        .feature-card p {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
        }

        /* Integration Logos Row */
        .integrations-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 16px 20px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(12px);
        }

        .integrations-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            white-space: nowrap;
        }

        .channel-badges-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .channel-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            transition: all 0.2s ease;
        }

        .channel-pill:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .channel-pill img {
            width: 16px;
            height: 16px;
            object-fit: contain;
        }

        .channel-pill i {
            font-size: 14px;
        }

        /* Right Form Column */
        .auth-card-wrap {
            display: flex;
            justify-content: flex-end;
            width: 100%;
        }

        .auth-glass-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-xl);
            padding: 44px 40px;
            width: 100%;
            max-width: 480px;
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            box-shadow: 
                0 24px 60px rgba(0, 0, 0, 0.45),
                0 0 1px 1px rgba(255, 255, 255, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .auth-glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #4f46e5, #8b5cf6);
        }

        .card-header-section {
            margin-bottom: 32px;
        }

        .portal-v2-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.25);
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .auth-title {
            font-family: 'Roboto', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .auth-subtitle {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.5;
        }

        /* Form Inputs */
        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(2, 6, 23, 0.6);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            transition: all 0.25s ease;
        }

        .input-box:focus-within {
            border-color: #6366f1;
            background: rgba(2, 6, 23, 0.85);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18);
        }

        .input-icon-left {
            position: absolute;
            left: 16px;
            color: #64748b;
            font-size: 16px;
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
            font-size: 14.5px;
            font-family: 'Roboto', sans-serif;
            padding: 14px 16px 14px 46px;
            box-sizing: border-box;
        }

        .styled-input::placeholder {
            color: #475569;
        }

        .input-actions-right {
            position: absolute;
            right: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toggle-pwd-btn {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            outline: none;
        }

        .toggle-pwd-btn:hover {
            color: #cbd5e1;
        }

        /* Checkbox & Options */
        .form-row-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 26px;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-input {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
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
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.4);
        }

        .checkbox-input:checked::after {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 10px;
            color: #ffffff;
            position: absolute;
        }

        .checkbox-label {
            font-size: 13.5px;
            color: #94a3b8;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .custom-checkbox:hover .checkbox-label {
            color: #e2e8f0;
        }

        .helper-link {
            font-size: 13px;
            font-weight: 600;
            color: #818cf8;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .helper-link:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }

        /* Submit Action Button */
        .btn-submit-v2 {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 50%, #06b6d4 100%);
            border: none;
            border-radius: var(--radius-md);
            padding: 15px;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
            position: relative;
            overflow: hidden;
            outline: none;
        }

        .btn-submit-v2::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-submit-v2:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 50%, #0891b2 100%);
        }

        .btn-submit-v2:hover::before {
            left: 100%;
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

        /* Alert Notification */
        .auth-error-alert {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: var(--radius-md);
            padding: 13px 16px;
            color: #fca5a5;
            font-size: 13.5px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        /* Divider & Employee Login Link */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 26px 0 20px;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-employee-link {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            padding: 12px;
            color: #cbd5e1;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-employee-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Card Footer Notice */
        .card-terms-notice {
            margin-top: 24px;
            font-size: 11.5px;
            color: #64748b;
            text-align: center;
            line-height: 1.6;
        }

        .card-terms-notice a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .card-terms-notice a:hover {
            color: #38bdf8;
            text-decoration: underline;
        }

        /* Bottom Symmetrical Footer */
        .auth-footer {
            position: relative;
            z-index: 10;
            padding: 20px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            color: #64748b;
            font-size: 13px;
        }

        .footer-sec-left,
        .footer-sec-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .security-badge-footer {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #94a3b8;
            font-weight: 500;
        }

        .security-badge-footer i {
            color: #10b981;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1120px) {
            .auth-container {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 20px 32px 40px;
            }

            .showcase-column {
                align-items: center;
                text-align: center;
            }

            .hero-description {
                max-width: 680px;
            }

            .auth-card-wrap {
                justify-content: center;
            }

            .feature-cards-grid {
                max-width: 600px;
                width: 100%;
            }

            .integrations-bar {
                max-width: 600px;
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .auth-nav {
                padding: 16px 20px;
            }

            .auth-container {
                padding: 10px 20px 30px;
            }

            .hero-heading {
                font-size: 32px;
            }

            .feature-cards-grid {
                grid-template-columns: 1fr;
            }

            .auth-glass-card {
                padding: 32px 24px;
            }

            .auth-footer {
                flex-direction: column;
                gap: 10px;
                padding: 20px;
                text-align: center;
            }
        }
    </style>
</head>

<body class="auth-body">

    <!-- Ambient Glow & Pattern Background -->
    <div class="ambient-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="grid-pattern"></div>
    </div>

    <!-- Top Navigation Header -->
    <header class="auth-nav">
        <a href="/" class="brand-pill">
            <div class="brand-logo-icon">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="brand-text-wrap">
                <span class="brand-title">ASPARTECH ERP</span>
                <span class="brand-sub">Marketplace Hub V2</span>
            </div>
        </a>

        <div class="nav-right-actions">
            <div class="system-badge">
                <span class="pulse-dot"></span>
                <span>Sistem V2 Online</span>
            </div>
        </div>
    </header>

    <!-- Main Section -->
    <main class="auth-container">

        <!-- Left Showcase Column -->
        <section class="showcase-column">
            <div class="hero-badge-tag">
                <i class="fas fa-bolt"></i>
                <span>Enterprise Multi-Channel Architecture</span>
            </div>

            <h1 class="hero-heading">
                Kelola Seluruh Toko <br>
                <span class="gradient-text">Dalam Satu Sistem Cerdas</span>
            </h1>

            <p class="hero-description">
                Platform ERP terintegrasi untuk otomatisasi sinkronisasi stok marketplace, 
                scanner barcode gudang, SPK produksi berulang, dan mutasi rekonsiliasi keuangan tanpa jeda.
            </p>

            <!-- Feature Highlight Cards Grid -->
            <div class="feature-cards-grid">
                <div class="feature-card">
                    <div class="card-icon-wrap icon-indigo">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Sinkronisasi Multi-Channel</h3>
                    <p>Stok terpotong otomatis di Shopee, TikTok Shop, Tokopedia, dan POS saat ada transaksi.</p>
                </div>

                <div class="feature-card">
                    <div class="card-icon-wrap icon-sky">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <h3>Warehouse Scanner V2</h3>
                    <p>Validasi barcode barang pick & pack akurat anti-salah kirim pesanan massal.</p>
                </div>

                <div class="feature-card">
                    <div class="card-icon-wrap icon-emerald">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Rekonsiliasi & Laba Rugi</h3>
                    <p>Kalkulasi HPP dinamis, biaya admin marketplace, dan arus kas harian transparan.</p>
                </div>

                <div class="feature-card">
                    <div class="card-icon-wrap icon-amber">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3>Multi-Tenant & Hak Akses</h3>
                    <p>Keamanan data terisolasi dengan pembagian hak akses granular per peran divisi.</p>
                </div>
            </div>

            <!-- Connected Marketplace Integration Bar -->
            <div class="integrations-bar">
                <span class="integrations-label">Didukung:</span>
                <div class="channel-badges-wrap">
                    <div class="channel-pill">
                        <img src="{{ asset('images/logos/shopee.svg') }}" alt="Shopee" onerror="this.style.display='none'">
                        <i class="fas fa-bag-shopping" style="color: #ee4d2d;" onerror=""></i>
                        <span>Shopee</span>
                    </div>
                    <div class="channel-pill">
                        <img src="{{ asset('images/logos/tiktok-shop.svg') }}" alt="TikTok Shop" onerror="this.style.display='none'">
                        <i class="fab fa-tiktok" style="color: #ffffff;"></i>
                        <span>TikTok Shop</span>
                    </div>
                    <div class="channel-pill">
                        <img src="{{ asset('images/logos/tokopedia.svg') }}" alt="Tokopedia" onerror="this.style.display='none'">
                        <i class="fas fa-store" style="color: #03ac0e;"></i>
                        <span>Tokopedia</span>
                    </div>
                    <div class="channel-pill">
                        <i class="fas fa-truck-fast" style="color: #38bdf8;"></i>
                        <span>J&T / SPX Hub</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right Side: Login Form Glass Card -->
        <section class="auth-card-wrap">
            <div class="auth-glass-card">
                <div class="card-header-section">
                    <div class="portal-v2-tag">
                        <i class="fas fa-shield-check"></i>
                        <span>Authentication Gateway</span>
                    </div>
                    <h2 class="auth-title">Masuk ke Portal V2</h2>
                    <p class="auth-subtitle">Masukkan kredensial akun Anda untuk mengakses dashboard operasional.</p>
                </div>

                <!-- Session Alert -->
                @if ($errors->any())
                    <div class="auth-error-alert" role="alert">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- UNIFIED LOGIN FORM -->
                <form action="{{ route('login.post') }}" method="POST" id="login-form" autocomplete="on">
                    @csrf

                    <!-- Identifier Input (Email / Username) -->
                    <div class="form-group">
                        <label for="login" class="form-label">Email atau Username</label>
                        <div class="input-box">
                            <i class="fas fa-user-circle input-icon-left"></i>
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

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <div class="input-box">
                            <i class="fas fa-lock-keyhole input-icon-left"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="styled-input" 
                                placeholder="Masukkan kata sandi" 
                                required
                                autocomplete="current-password"
                            >
                            <div class="input-actions-right">
                                <button type="button" class="toggle-pwd-btn" id="toggle-pwd-btn" title="Lihat Kata Sandi" aria-label="Lihat Kata Sandi">
                                    <i class="fas fa-eye" id="eye-icon"></i>
                                </button>
                            </div>
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
                        <span id="btn-text">Masuk ke Sistem V2</span>
                        <i class="fas fa-arrow-right" id="btn-arrow"></i>
                    </button>
                </form>

                <!-- Divider & Employee Presensi Gateway Link -->
                <div class="auth-divider">
                    <span>Akses Alternatif</span>
                </div>

                <a href="{{ route('employee.login') }}" class="btn-employee-link">
                    <i class="fas fa-fingerprint" style="color: #38bdf8;"></i>
                    <span>Portal Presensi Karyawan (Self-Service)</span>
                </a>

                <!-- Notice -->
                <p class="card-terms-notice">
                    Dengan masuk, Anda mematuhi <a href="{{ route('terms-of-service') }}" target="_blank">Syarat & Ketentuan</a> 
                    serta <a href="{{ route('privacy-policy') }}" target="_blank">Kebijakan Privasi</a> ERP Marketplace.
                </p>
            </div>
        </section>

    </main>

    <!-- Bottom Footer Bar -->
    <footer class="auth-footer">
        <div class="footer-sec-left">
            <span>© {{ date('Y') }} ASPARTECH ERP. Dilindungi Hak Cipta.</span>
        </div>
        <div class="footer-sec-right">
            <div class="security-badge-footer">
                <i class="fas fa-shield-halved"></i>
                <span>Enkripsi SSL 256-Bit Aktif</span>
            </div>
        </div>
    </footer>

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
                if (btnText) btnText.textContent = 'Memverifikasi Akses...';
                if (btnArrow) {
                    btnArrow.classList.remove('fa-arrow-right');
                    btnArrow.classList.add('fa-spinner', 'fa-spin');
                }
            });
        }
    </script>
</body>

</html>
