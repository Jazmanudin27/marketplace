<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'ERP Mobile')</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        @if(request()->routeIs('mobile.owner*'))
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --border-card: rgba(0, 0, 0, 0.08);
            
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.15);
            --accent-green: #059669;
            --accent-yellow: #d97706;
            --accent-red: #dc2626;
            --accent-blue: #0284c7;
            
            --text-main: #0f172a;
            --text-muted: #64748b;
        }
        @else
        :root {
            --bg-primary: #0a0f1d;
            --bg-secondary: #121829;
            --bg-card: rgba(26, 32, 53, 0.7);
            --border-card: rgba(255, 255, 255, 0.08);
            
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.4);
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --accent-red: #ef4444;
            --accent-blue: #0ea5e9;
            
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
        }
        @endif

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-main);
            margin: 0;
            padding-bottom: @if(in_array(Auth::user()->role ?? '', ['production', 'produksi', 'admin_produksi'])) 20px @else 110px @endif; /* Enhanced space for floating bottom nav */
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        /* Glassmorphism card utility */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:active {
            transform: scale(0.98);
        }

        /* Header Bar */
        .mobile-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: @if(request()->routeIs('mobile.owner*')) rgba(255, 255, 255, 0.85) @else rgba(10, 15, 29, 0.85) @endif;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-card);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-header h1 {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
            background: @if(request()->routeIs('mobile.owner*')) linear-gradient(135deg, #4f46e5, #3b82f6) @else linear-gradient(135deg, #a5b4fc, #818cf8) @endif;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-tag {
            font-size: 0.7rem;
            background: rgba(79, 70, 229, 0.15);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            color: #c7d2fe;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Slide-out Mobile Sidebar Drawer */
        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: #111726;
            border-right: 1px solid var(--border-card);
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1100;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }

        .mobile-sidebar.show {
            left: 0;
        }

        .mobile-sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(5, 8, 16, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: none;
            z-index: 1090;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .mobile-sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-card);
            margin-bottom: 20px;
        }

        .mobile-sidebar-header h4 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .mobile-tenant-badge {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .mobile-tenant-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .mobile-tenant-info {
            overflow: hidden;
            line-height: 1.3;
        }

        .mobile-tenant-name {
            font-weight: 600;
            color: white;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mobile-tenant-role {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .mobile-sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mobile-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .mobile-sidebar-menu a:hover,
        .mobile-sidebar-menu a.active {
            color: white;
            background: rgba(79, 70, 229, 0.15);
            border-left: 3px solid #818cf8;
            padding-left: 13px;
        }

        .mobile-sidebar-menu i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .mobile-sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--border-card);
        }

        /* Floating colorful premium bottom nav */
        .bottom-nav {
            position: fixed;
            bottom: 16px;
            left: 14px;
            right: 14px;
            height: 70px;
            background: @if(request()->routeIs('mobile.owner*')) rgba(255, 255, 255, 0.94) @else rgba(15, 23, 42, 0.92) @endif;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid @if(request()->routeIs('mobile.owner*')) rgba(226, 232, 240, 0.9) @else rgba(255, 255, 255, 0.1) @endif;
            border-radius: 22px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: @if(request()->routeIs('mobile.owner*')) 0 10px 30px -5px rgba(0, 0, 0, 0.08), 0 4px 18px rgba(79, 70, 229, 0.07) @else 0 16px 36px rgba(0, 0, 0, 0.6), 0 0 25px rgba(99, 102, 241, 0.18) @endif;
            padding: 0 6px;
        }

        .nav-item-custom {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.65rem;
            font-weight: 500;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            padding: 4px 0 2px;
            flex: 1;
            height: 100%;
        }

        .nav-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 2px;
            transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
            background: var(--nav-bg-idle);
            color: var(--nav-color);
        }

        .nav-item-custom span {
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: -0.2px;
            color: @if(request()->routeIs('mobile.owner*')) #64748b @else #94a3b8 @endif;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .nav-item-custom:active .nav-icon-box {
            transform: scale(0.9);
        }

        /* Active item status */
        .nav-item-custom.active .nav-icon-box {
            background: var(--nav-gradient);
            color: #ffffff !important;
            transform: translateY(-4px) scale(1.06);
            box-shadow: 0 6px 16px var(--nav-glow);
        }

        .nav-item-custom.active span {
            color: var(--nav-color) !important;
            font-weight: 700;
            transform: translateY(-1px);
        }

        /* Micro active indicator dot */
        .nav-item-custom.active::after {
            content: '';
            position: absolute;
            bottom: 3px;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--nav-color);
            box-shadow: 0 0 8px var(--nav-color);
            animation: navPulse 2s infinite ease-in-out;
        }

        @keyframes navPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.6; }
        }

        /* Theme colors per menu item */
        /* Home: Royal Indigo */
        .nav-item-custom.nav-home {
            --nav-color: #4f46e5;
            --nav-bg-idle: rgba(79, 70, 229, 0.1);
            --nav-gradient: linear-gradient(135deg, #6366f1, #4338ca);
            --nav-glow: rgba(79, 70, 229, 0.45);
        }

        /* Sales: Emerald Green */
        .nav-item-custom.nav-sales {
            --nav-color: #059669;
            --nav-bg-idle: rgba(16, 185, 129, 0.12);
            --nav-gradient: linear-gradient(135deg, #10b981, #047857);
            --nav-glow: rgba(16, 185, 129, 0.45);
        }

        /* Stok Produk: Amber Gold */
        .nav-item-custom.nav-stok-produk {
            --nav-color: #d97706;
            --nav-bg-idle: rgba(245, 158, 11, 0.12);
            --nav-gradient: linear-gradient(135deg, #f59e0b, #b45309);
            --nav-glow: rgba(245, 158, 11, 0.45);
        }

        /* Stok Barang: Purple Violet */
        .nav-item-custom.nav-stok-barang {
            --nav-color: #8b5cf6;
            --nav-bg-idle: rgba(139, 92, 246, 0.12);
            --nav-gradient: linear-gradient(135deg, #a78bfa, #6d28d9);
            --nav-glow: rgba(139, 92, 246, 0.45);
        }

        /* Gudang: Cyan / Sky */
        .nav-item-custom.nav-gudang {
            --nav-color: #0284c7;
            --nav-bg-idle: rgba(2, 132, 199, 0.12);
            --nav-gradient: linear-gradient(135deg, #38bdf8, #0369a1);
            --nav-glow: rgba(2, 132, 199, 0.45);
        }

        /* Scan: Rose / Crimson */
        .nav-item-custom.nav-scan {
            --nav-color: #e11d48;
            --nav-bg-idle: rgba(225, 29, 72, 0.12);
            --nav-gradient: linear-gradient(135deg, #fb7185, #be123c);
            --nav-glow: rgba(225, 29, 72, 0.45);
        }

        /* Produksi: Vibrant Coral */
        .nav-item-custom.nav-produksi {
            --nav-color: #ea580c;
            --nav-bg-idle: rgba(234, 88, 12, 0.12);
            --nav-gradient: linear-gradient(135deg, #fb923c, #c2410c);
            --nav-glow: rgba(234, 88, 12, 0.45);
        }

        /* High contrast adjustments for dark mode */
        @if(!request()->routeIs('mobile.owner*'))
        .nav-item-custom.nav-home { --nav-color: #818cf8; --nav-bg-idle: rgba(129, 140, 248, 0.15); }
        .nav-item-custom.nav-sales { --nav-color: #34d399; --nav-bg-idle: rgba(52, 211, 153, 0.15); }
        .nav-item-custom.nav-stok-produk { --nav-color: #fbbf24; --nav-bg-idle: rgba(251, 191, 36, 0.15); }
        .nav-item-custom.nav-stok-barang { --nav-color: #c084fc; --nav-bg-idle: rgba(192, 132, 252, 0.15); }
        .nav-item-custom.nav-gudang { --nav-color: #38bdf8; --nav-bg-idle: rgba(56, 189, 248, 0.15); }
        .nav-item-custom.nav-scan { --nav-color: #fb7185; --nav-bg-idle: rgba(251, 113, 133, 0.15); }
        .nav-item-custom.nav-produksi { --nav-color: #fb923c; --nav-bg-idle: rgba(251, 146, 60, 0.15); }
        @endif


        /* Custom badge */
        .status-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: var(--accent-yellow);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .status-producing {
            background: rgba(14, 165, 233, 0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(14, 165, 233, 0.3);
        }
        
        .status-completed {
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: var(--accent-red);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Buttons styling */
        .btn-premium {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--primary-glow);
            transition: all 0.2s ease;
        }

        .btn-premium:active {
            transform: scale(0.97);
            box-shadow: 0 2px 8px var(--primary-glow);
        }

        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-card);
            color: var(--text-main);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .btn-secondary-custom:active {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Form input custom styling */
        .custom-input {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 12px 16px;
            color: white;
            transition: all 0.3s ease;
        }

        .custom-input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: #818cf8;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
            outline: none;
            color: white;
        }

        /* Alerts and notices */
        .alert-premium {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
    </style>
    @yield('styles')
</head>
<body>
    @php
        $role = Auth::user()->role;
        $isOwnerActive = request()->routeIs('mobile.owner') && !request()->routeIs('mobile.owner.sales*') && !request()->routeIs('mobile.owner.penjualan_offline*') && !request()->routeIs('mobile.owner.stok_produk*') && !request()->routeIs('mobile.owner.stok_barang*');
        $isSalesActive = request()->routeIs('mobile.owner.sales*') || request()->routeIs('mobile.owner.penjualan_offline*');
        $isStokProdukActive = request()->routeIs('mobile.owner.stok_produk*');
        $isStokBarangActive = request()->routeIs('mobile.owner.stok_barang*');
        
        $isGudangActive = request()->routeIs('mobile.gudang') || request()->routeIs('mobile.gudang.adjust_stock') || request()->routeIs('mobile.gudang.request_production');
        $isScanActive = request()->routeIs('mobile.gudang.scan*');
        $isProduksiActive = request()->routeIs('mobile.produksi*');
    @endphp

    <!-- Drawer Sidebar and overlay removed for mobile -->

    <!-- Header Bar -->
    <header class="mobile-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-cubes me-2" style="color: @if(request()->routeIs('mobile.owner*')) #4f46e5 @else #818cf8 @endif; font-size:1.25rem;"></i>
            <h1 style="@if(request()->routeIs('mobile.owner*')) color: #0f172a; @endif">@yield('header-title', 'ERP Mobile')</h1>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="user-tag" style="@if(request()->routeIs('mobile.owner*')) background: rgba(79, 70, 229, 0.08); border: 1px solid rgba(79, 70, 229, 0.2); color: #4f46e5; @endif">{{ Auth::user()->role }}</span>
            <form action="{{ route('mobile.logout') }}" method="POST" class="m-0" id="logout-form">
                @csrf
                <button type="submit" class="btn p-0 text-danger border-0 bg-transparent ms-2" title="Logout" style="outline: none; box-shadow: none;">
                    <i class="fas fa-sign-out-alt" style="font-size: 1.2rem;"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- Content Area -->
    <main class="container py-3">
        @if(session('success'))
            <div class="alert alert-premium alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="padding: 1rem 1rem; font-size: 0.75rem;"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3 bg-opacity-10 text-danger border-danger border-opacity-20" role="alert" style="border-radius: 12px; font-size:0.85rem;">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="padding: 1rem 1rem; font-size: 0.75rem;"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Bottom Navigation Bar (Hidden for dedicated production roles) -->
    @if(!in_array($role, ['production', 'produksi', 'admin_produksi']))
        <nav class="bottom-nav">
            <!-- Tab Owner -->
            @if(in_array($role, ['admin', 'owner', 'finance']))
                <a href="{{ route('mobile.owner') }}" class="nav-item-custom nav-home {{ $isOwnerActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-home"></i></div>
                    <span>Home</span>
                </a>
                <a href="{{ route('mobile.owner.sales') }}" class="nav-item-custom nav-sales {{ $isSalesActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-shopping-cart"></i></div>
                    <span>Penjualan</span>
                </a>
                <a href="{{ route('mobile.owner.stok_produk') }}" class="nav-item-custom nav-stok-produk {{ $isStokProdukActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-box-open"></i></div>
                    <span>Stok Produk</span>
                </a>
                <a href="{{ route('mobile.owner.stok_barang') }}" class="nav-item-custom nav-stok-barang {{ $isStokBarangActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-scroll"></i></div>
                    <span>Stok Barang</span>
                </a>
            @endif

            <!-- Tab Gudang (For non-owner roles) -->
            @if(!in_array($role, ['owner']) && in_array($role, ['admin', 'warehouse', 'gudang']))
                <a href="{{ route('mobile.gudang') }}" class="nav-item-custom nav-gudang {{ $isGudangActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-warehouse"></i></div>
                    <span>Gudang</span>
                </a>
                <a href="{{ route('mobile.gudang.scan') }}" class="nav-item-custom nav-scan {{ $isScanActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-barcode"></i></div>
                    <span>Scan SKU</span>
                </a>
            @endif

            <!-- Tab Produksi (For non-owner roles) -->
            @if(!in_array($role, ['owner']) && in_array($role, ['admin', 'production', 'produksi', 'admin_produksi']))
                <a href="{{ route('mobile.produksi') }}" class="nav-item-custom nav-produksi {{ $isProduksiActive ? 'active' : '' }}">
                    <div class="nav-icon-box"><i class="fas fa-tools"></i></div>
                    <span>Produksi</span>
                </a>
            @endif
        </nav>
    @endif

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
    
    <!-- Drawer Toggle Script removed -->
    @yield('scripts')
</body>
</html>
