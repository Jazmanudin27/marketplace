@php
    $isMasterDataActive = request()->is('v2/produk*') || request()->is('v2/kategori*') || request()->is('v2/brand*') || request()->is('v2/supplier*');
    $isSalesActive = request()->is('v2/pesanan*') || request()->is('v2/toko*') || request()->is('v2/retur*');
@endphp

<!-- Sidebar V2 Component -->
<aside class="v2-sidebar" id="v2Sidebar">
    <!-- Brand Header -->
    <div class="v2-sidebar-brand">
        <div class="v2-brand-icon">
            <i class="bi bi-grid-fill"></i>
        </div>
        <div class="v2-brand-text">
            <span class="v2-brand-name">ASPARTECH</span>
            <span class="v2-brand-subtitle">ERP NEXT-GEN V2</span>
        </div>
    </div>

    <!-- Tenant / Company Selector Switcher -->
    @if (Auth::user() && Auth::user()->isSuperAdmin())
        @php
            $tenants = \App\Models\Tenant::orderBy('name')->get();
        @endphp
        <div class="px-3 pt-3">
            <div class="p-2 rounded-3" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">
                <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase;">
                    <span>PERUSAHAAN</span>
                    <span class="badge bg-primary text-white" style="font-size: 0.6rem;">SUPER ADMIN</span>
                </div>
                <form action="{{ route('switch-tenant') }}" method="POST" id="switch-tenant-form-v2">
                    @csrf
                    <select name="tenant_id" class="form-select form-select-sm bg-dark text-white border-secondary" style="font-size: 0.78rem;" onchange="document.getElementById('switch-tenant-form-v2').submit()">
                        @foreach ($tenants as $t)
                            <option value="{{ $t->id }}" {{ Auth::user()->tenant_id == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    @elseif (Auth::user() && Auth::user()->tenant)
        <div class="px-3 pt-3">
            <div class="p-2 rounded-3 d-flex align-items-center gap-2" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">
                <div class="rounded-2 bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem;">
                    {{ strtoupper(substr(Auth::user()->tenant->name, 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <div class="text-white text-truncate fw-semibold" style="font-size: 0.8rem;">{{ Auth::user()->tenant->name }}</div>
                    <div class="text-secondary" style="font-size: 0.68rem;">{{ ucfirst(Auth::user()->roles->first()?->name ?? Auth::user()->role) }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Items -->
    <div class="v2-sidebar-nav">
        <!-- Main Section -->
        <div class="v2-nav-section-title">Menu Utama (V2)</div>

        <!-- Dashboard V2 -->
        <div class="v2-nav-item">
            <a href="{{ url('/v2/dashboard') }}" class="v2-nav-link {{ request()->is('v2/dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard V2</span>
            </a>
        </div>

        <!-- Master Data Accordion -->
        <div class="v2-nav-section-title">Master Data & Produk</div>

        <div class="v2-nav-item">
            <a href="{{ url('/v2/produk') }}" class="v2-nav-link {{ request()->is('v2/produk*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Master Produk</span>
            </a>
        </div>

        <!-- Section: Marketplace & Penjualan -->
        <div class="v2-nav-section-title">Marketplace & Sales</div>

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
        <div class="v2-nav-section-title">Sistem & Mode</div>
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
