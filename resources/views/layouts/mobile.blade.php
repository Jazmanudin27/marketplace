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

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-main);
            margin: 0;
            padding-bottom: 90px; /* Space for bottom nav */
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
            background: rgba(10, 15, 29, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-card);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-tag {
            font-size: 0.75rem;
            background: rgba(79, 70, 229, 0.15);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            color: #c7d2fe;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Bottom Navigation Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 75px;
            background: rgba(18, 24, 41, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-top: 1px solid var(--border-card);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            padding-bottom: env(safe-area-inset-bottom);
        }

        .nav-item-custom {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.7rem;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
            padding: 8px 12px;
            border-radius: 12px;
        }

        .nav-item-custom i {
            font-size: 1.35rem;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }

        .nav-item-custom.active {
            color: #818cf8;
            background: rgba(79, 70, 229, 0.08);
        }

        .nav-item-custom.active i {
            transform: translateY(-2px);
            color: #818cf8;
            text-shadow: 0 0 10px rgba(129, 140, 248, 0.6);
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

    <!-- Header Bar -->
    <header class="mobile-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-cubes me-2 text-indigo" style="color:#818cf8; font-size:1.35rem;"></i>
            <h1>@yield('header-title', 'ERP Mobile')</h1>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="user-tag">{{ Auth::user()->role }}</span>
            <form action="{{ route('logout') }}" method="POST" class="m-0" id="logout-form">
                @csrf
                <button type="submit" class="btn btn-link text-danger p-0" style="font-size: 1.15rem;" title="Keluar">
                    <i class="fas fa-sign-out-alt"></i>
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
        @php
            $role = Auth::user()->role;
            $currentRoute = Route::currentRouteName();
        @endphp

        <!-- Tab Owner -->
        @if(in_array($role, ['admin', 'owner', 'finance']))
            <a href="{{ route('mobile.owner') }}" class="nav-item-custom {{ $currentRoute === 'mobile.owner' ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Owner</span>
            </a>
        @endif

        <!-- Tab Gudang -->
        @if(in_array($role, ['admin', 'warehouse', 'gudang']))
            <a href="{{ route('mobile.gudang') }}" class="nav-item-custom {{ $currentRoute === 'mobile.gudang' ? 'active' : '' }}">
                <i class="fas fa-warehouse"></i>
                <span>Gudang</span>
            </a>
            <a href="{{ route('mobile.gudang.scan') }}" class="nav-item-custom {{ $currentRoute === 'mobile.gudang.scan' ? 'active' : '' }}">
                <i class="fas fa-barcode"></i>
                <span>Scan SKU</span>
            </a>
        @endif

        <!-- Tab Produksi -->
        @if(in_array($role, ['admin', 'production', 'produksi']))
            <a href="{{ route('mobile.produksi') }}" class="nav-item-custom {{ $currentRoute === 'mobile.produksi' ? 'active' : '' }}">
                <i class="fas fa-tools"></i>
                <span>Produksi</span>
            </a>
        @endif
    </nav>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
</body>
</html>
