@php
    $currentRoute = request()->path();
    $user = Auth::user();
    $tenant = $user?->tenant;
@endphp

<aside class="v2-sidebar" id="v2Sidebar">
    <!-- Sidebar Brand Header -->
    <div class="v2-sidebar-brand">
        <div class="v2-brand-icon">
            P
        </div>
        <div>
            <div class="v2-brand-name">PORTAL <span class="text-primary" style="font-size: 0.7rem;">SYSTEM</span></div>
            <div class="v2-brand-subtitle">ASPARTECH ERP V2</div>
        </div>
    </div>

    <!-- Active Tenant Badge -->
    <div class="px-3 py-2 border-bottom border-secondary border-opacity-10 bg-black bg-opacity-20 d-flex align-items-center gap-2"
        style="font-size: 0.72rem;">
        <i class="bi bi-building text-primary"></i>
        <div class="overflow-hidden">
            <div class="text-light fw-bold text-truncate" title="{{ $tenant->name ?? 'Ruang Seragam' }}">
                {{ $tenant->name ?? 'Ruang Seragam' }}</div>
            <div class="text-success fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-circle-fill me-1"
                    style="font-size: 0.45rem;"></i>Online</div>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="v2-sidebar-nav">
        <!-- Main Navigation -->
        <div class="v2-nav-section-title">MAIN MENU</div>
        <div class="v2-nav-item">
            <a href="{{ url('/v2/dashboard') }}"
                class="v2-nav-link {{ request()->is('v2') || request()->is('v2/dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Data Master -->
        <div class="v2-nav-section-title mt-2">DATA MASTER</div>
        @php
            $isDataMasterActive =
                request()->is('v2/produk*') ||
                request()->is('v2/kategori*') ||
                request()->is('v2/brand*') ||
                request()->is('inventory-items*');
        @endphp
        <div class="v2-nav-item v2-nav-dropdown {{ $isDataMasterActive ? 'show' : '' }}">
            <a href="javascript:void(0)"
                class="v2-nav-link v2-dropdown-toggle d-flex align-items-center justify-content-between {{ $isDataMasterActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#dataMasterSubmenu"
                aria-expanded="{{ $isDataMasterActive ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-folder-fill"></i>
                    <span>Data Master</span>
                </div>
                <i class="bi bi-chevron-down v2-dropdown-arrow ms-auto" style="font-size: 0.65rem;"></i>
            </a>
            <div class="collapse v2-submenu {{ $isDataMasterActive ? 'show' : '' }}" id="dataMasterSubmenu">
                <div class="v2-submenu-inner">
                    <a href="{{ url('/v2/produk') }}"
                        class="v2-submenu-link {{ request()->is('v2/produk*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam me-1.5"></i>
                        <span>Master Produk</span>
                    </a>
                    <a href="{{ url('/v2/kategori-brand') }}"
                        class="v2-submenu-link {{ request()->is('v2/kategori*') || request()->is('v2/brand*') ? 'active' : '' }}">
                        <i class="bi bi-tags me-1.5"></i>
                        <span>Kategori & Varian</span>
                    </a>
                    <a href="{{ url('/v2/barang') }}"
                        class="v2-submenu-link {{ request()->is('v2/barang*') || request()->is('inventory-items*') ? 'active' : '' }}">
                        <i class="bi bi-boxes me-1.5"></i>
                        <span>Data Barang</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Marketplace & Sales -->
        <div class="v2-nav-section-title mt-2">MARKETPLACE & SALES</div>
        <div class="v2-nav-item">
            <a href="{{ Route::has('orders.index') ? route('orders.index') : url('/orders') }}"
                class="v2-nav-link d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cart-check-fill"></i>
                    <span>Pesanan Masuk</span>
                </div>
                <span class="badge bg-danger rounded-pill py-0.5 px-1.5" style="font-size: 0.62rem;">Live</span>
            </a>
        </div>
        <div class="v2-nav-item">
            <a href="{{ url('/v2/toko') }}"
                class="v2-nav-link {{ request()->is('v2/toko*') || request()->is('stores*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Toko Terhubung</span>
            </a>
        </div>
        <div class="v2-nav-item">
            <a href="{{ url('/v2/marketplace-produk') }}"
                class="v2-nav-link {{ request()->is('v2/marketplace-produk*') || request()->is('v2/produk-marketplace*') || request()->is('marketplace-products*') ? 'active' : '' }}">
                <i class="bi bi-tags-fill"></i>
                <span>Produk Marketplace</span>
            </a>
        </div>
        <div class="v2-nav-item">
            <a href="{{ Route::has('stock_sync.index') ? route('stock_sync.index') : url('/v2/produk') }}"
                class="v2-nav-link">
                <i class="bi bi-arrow-repeat"></i>
                <span>Sinkronisasi Stok</span>
            </a>
        </div>

        <!-- HRD & Keuangan -->
        <div class="v2-nav-section-title mt-2">KEUANGAN & HRD</div>
        <div class="v2-nav-item">
            <a href="{{ Route::has('reports.income_statement') ? route('reports.income_statement') : url('/reports') }}"
                class="v2-nav-link">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Laba Rugi & Keuangan</span>
            </a>
        </div>
        <div class="v2-nav-item">
            <a href="{{ Route::has('employees.index') ? route('employees.index') : (Route::has('hrd.employees.index') ? route('hrd.employees.index') : url('/employees')) }}"
                class="v2-nav-link">
                <i class="bi bi-people-fill"></i>
                <span>Karyawan & Payroll</span>
            </a>
        </div>
    </nav>

    <!-- Sidebar User Footer -->
    <div class="v2-sidebar-footer">
        <div class="v2-user-card">
            <div class="v2-user-avatar">
                {{ strtoupper(substr($user->name ?? 'A', 0, 2)) }}
            </div>
            <div class="overflow-hidden">
                <div class="v2-user-name" title="{{ $user->name ?? 'User' }}">{{ $user->name ?? 'User Admin' }}</div>
                <div class="v2-user-role text-truncate">{{ $user->email ?? 'admin@erp.com' }}</div>
            </div>
        </div>
    </div>
</aside>
