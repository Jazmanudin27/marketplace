<!-- Sidebar V2 Component (Compact Dense Style like Reference Image 2) -->
<aside class="v2-sidebar" id="v2Sidebar">
    <!-- Brand Header -->
    <div class="v2-sidebar-brand">
        <div class="v2-brand-icon bg-primary">
            <i class="bi bi-grid-fill"></i>
        </div>
        <div class="v2-brand-text">
            <span class="v2-brand-name">PORTAL</span>
            <span class="v2-brand-subtitle">ASPARTECH SYSTEM</span>
        </div>
    </div>

    <!-- Tenant Badge Box (Like Image 2 Sidebar Top Card) -->
    @if (Auth::user() && Auth::user()->tenant)
        <div class="px-2 pt-2">
            <div class="p-2 rounded-2 d-flex align-items-center gap-2" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08);">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.7rem; flex-shrink: 0;">
                    <i class="bi bi-building"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white text-truncate fw-bold" style="font-size: 0.75rem;">{{ Auth::user()->tenant->name }}</div>
                    <div class="badge bg-primary text-uppercase" style="font-size: 0.6rem; padding: 0.1rem 0.35rem;">
                        {{ Auth::user()->roles->first()?->name ?? Auth::user()->role ?? 'ADMIN' }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Items -->
    <div class="v2-sidebar-nav">
        <!-- Main Menu -->
        <div class="v2-nav-section-title">MAIN MENU</div>

        <div class="v2-nav-item">
            <a href="{{ url('/v2/dashboard') }}" class="v2-nav-link {{ request()->is('v2/dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Data Master -->
        <div class="v2-nav-section-title">DATA MASTER</div>

        <div class="v2-nav-item">
            <a href="{{ url('/v2/produk') }}" class="v2-nav-link {{ request()->is('v2/produk*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Data Produk</span>
            </a>
        </div>

        <div class="v2-nav-item">
            <a href="{{ Route::has('products.index') ? route('products.index') : url('/products') }}" class="v2-subnav-link text-white-50 px-3 py-1" style="font-size: 0.725rem;">
                <i class="bi bi-circle me-1" style="font-size: 0.5rem;"></i> Master Produk (V1)
            </a>
        </div>

        <!-- Marketplace & Sales -->
        <div class="v2-nav-section-title">MARKETPLACE & SALES</div>

        <div class="v2-nav-item">
            <a href="{{ route('orders.index') }}" class="v2-nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i>
                <span>Pesanan Marketplace</span>
            </a>
        </div>

        <div class="v2-nav-item">
            <a href="{{ route('stores.index') }}" class="v2-nav-link {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Toko Marketplace</span>
            </a>
        </div>

        <!-- Mode Toggle Section -->
        <div class="v2-nav-section-title">SISTEM & MODE</div>
        <div class="v2-nav-item">
            <a href="{{ route('dashboard') }}" class="v2-nav-link text-warning">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Kembali ke ERP V1</span>
            </a>
        </div>
    </div>

    <!-- User Profile Footer Card -->
    <div class="v2-sidebar-footer">
        @if(Auth::user())
        <div class="v2-user-card">
            <div class="v2-user-avatar">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
            <div class="v2-user-info">
                <div class="v2-user-name">{{ Auth::user()->name }}</div>
                <div class="v2-user-role">{{ Auth::user()->email }}</div>
            </div>
        </div>
        @endif
    </div>
</aside>
