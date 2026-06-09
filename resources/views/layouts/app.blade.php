<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERP Marketplace - Kelola semua toko marketplace Anda dalam satu dashboard terpusat">
    <title>@yield('title', 'Dashboard') | ERP Marketplace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>

<body>
    <div class="app-wrapper">
        {{-- SIDEBAR --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-store-alt"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">ERP</span>
                    <span class="brand-sub">Marketplace</span>
                </div>
            </div>

            <div class="tenant-badge">
                <div class="tenant-avatar">{{ strtoupper(substr(Auth::user()->tenant->name, 0, 1)) }}</div>
                <div class="tenant-info">
                    <div class="tenant-name">{{ Auth::user()->tenant->name }}</div>
                    <div class="tenant-role">{{ ucfirst(Auth::user()->role) }}</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">UTAMA</div>
                <a href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('orders.index') }}"
                    class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pesanan</span>
                    @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                        <span class="nav-badge">{{ $pendingOrdersCount }}</span>
                    @endif
                </a>

                <div class="nav-section-title">PRODUK & STOK</div>
                <a href="{{ route('products.index') }}"
                    class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i>
                    <span>Master Produk</span>
                </a>
                <a href="{{ route('categories.index') }}"
                    class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <i class="fas fa-folder"></i>
                    <span>Master Kategori</span>
                </a>
                <a href="{{ route('brands.index') }}"
                    class="nav-item {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                    <i class="fas fa-certificate"></i>
                    <span>Master Merk</span>
                </a>
                <a href="{{ route('marketplace_products.index') }}"
                    class="nav-item {{ request()->routeIs('marketplace_products.*') ? 'active' : '' }}">
                    <i class="fas fa-link"></i>
                    <span>Produk Marketplace</span>
                </a>

                <div class="nav-section-title">INTEGRASI</div>
                <a href="{{ route('stores.index') }}"
                    class="nav-item {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                    <i class="fas fa-plug"></i>
                    <span>Kelola Toko</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="main-content">
            <header class="topbar">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                <div class="topbar-right">
                    <div class="topbar-user">
                        <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                        <span>{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </header>

            <div class="page-content">
                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
    @stack('scripts')
</body>

</html>
