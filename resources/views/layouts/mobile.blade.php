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
            padding-bottom: 110px; /* Enhanced space for floating bottom nav */
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

        /* Floating premium bottom nav */
        .bottom-nav {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            height: 68px;
            background: @if(request()->routeIs('mobile.owner*')) rgba(255, 255, 255, 0.9) @else rgba(18, 24, 41, 0.85) @endif;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: @if(request()->routeIs('mobile.owner*')) 0 10px 30px rgba(0, 0, 0, 0.08) @else 0 10px 30px rgba(0, 0, 0, 0.5) @endif;
            padding: 0 10px;
        }

        .nav-item-custom {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.65rem;
            font-weight: 600;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            padding: 8px 0;
            flex: 1;
            height: 100%;
            justify-content: center;
        }

        .nav-item-custom i {
            font-size: 1.25rem;
            margin-bottom: 2px;
            transition: all 0.25s ease;
        }

        .nav-item-custom.active {
            color: @if(request()->routeIs('mobile.owner*')) #4f46e5 @else #818cf8 @endif;
        }

        .nav-item-custom.active i {
            transform: translateY(-4px);
            color: @if(request()->routeIs('mobile.owner*')) #4f46e5 @else #818cf8 @endif;
            @if(!request()->routeIs('mobile.owner*'))
            text-shadow: 0 0 12px rgba(129, 140, 248, 0.8);
            @endif
        }

        /* Floating active bar/indicator dot */
        .nav-item-custom.active::after {
            content: '';
            position: absolute;
            bottom: 6px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: @if(request()->routeIs('mobile.owner*')) #4f46e5 @else #818cf8 @endif;
            @if(!request()->routeIs('mobile.owner*'))
            box-shadow: 0 0 8px #818cf8;
            @endif
        }

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
        $isOwnerActive = request()->routeIs('mobile.owner*');
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
            <form action="{{ route('logout') }}" method="POST" class="m-0" id="logout-form">
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

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav">
        <!-- Tab Owner -->
        @if(in_array($role, ['admin', 'owner', 'finance']))
            <a href="{{ route('mobile.owner') }}" class="nav-item-custom {{ $isOwnerActive ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Owner</span>
            </a>
        @endif

        <!-- Tab Gudang -->
        @if(in_array($role, ['admin', 'warehouse', 'gudang']))
            <a href="{{ route('mobile.gudang') }}" class="nav-item-custom {{ $isGudangActive ? 'active' : '' }}">
                <i class="fas fa-warehouse"></i>
                <span>Gudang</span>
            </a>
            <a href="{{ route('mobile.gudang.scan') }}" class="nav-item-custom {{ $isScanActive ? 'active' : '' }}">
                <i class="fas fa-barcode"></i>
                <span>Scan SKU</span>
            </a>
        @endif

        <!-- Tab Produksi -->
        @if(in_array($role, ['admin', 'production', 'produksi']))
            <a href="{{ route('mobile.produksi') }}" class="nav-item-custom {{ $isProduksiActive ? 'active' : '' }}">
                <i class="fas fa-tools"></i>
                <span>Produksi</span>
            </a>
        @endif
    </nav>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Drawer Toggle Script removed -->
    @yield('scripts')
</body>
</html>
