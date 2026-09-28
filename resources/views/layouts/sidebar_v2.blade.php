@php
    $isMasterDataActive =
        (request()->routeIs('inventory_items.*') && !request()->has('type')) ||
        request()->routeIs('bank-accounts.*') ||
        request()->routeIs('finance-categories.*') ||
        request()->routeIs('departments.*') ||
        request()->routeIs('categories.*') ||
        request()->routeIs('brands.*') ||
        request()->routeIs('suppliers.*') ||
        request()->routeIs('customers.*') ||
        request()->routeIs('users.*') ||
        request()->routeIs('roles.*') ||
        request()->routeIs('settings.tenant.*') ||
        request()->routeIs('tailors.*') ||
        request()->routeIs('production-statuses.*') ||
        request()->routeIs('production-stages.*') ||
        request()->routeIs('labor_services.*');

    $isPembelianActive =
        (request()->routeIs('purchase_orders.*') && !request()->routeIs('purchase_orders.report')) ||
        request()->routeIs('purchase_returns.*') ||
        request()->routeIs('goods_receipts.*') ||
        request()->routeIs('supplier_payables.*') ||
        request()->routeIs('supplier_consignments.*') ||
        request()->routeIs('incoming_goods.*') ||
        request()->routeIs('inventory_items.*') ||
        request()->routeIs('pembelian.goods_issue.*');

    $isProduksiActive =
        request()->routeIs('spks.*') ||
        request()->routeIs('product_recipes.*');

    $isGudangJadiActive =
        request()->routeIs('inventory.mutations.*') ||
        request()->routeIs('stock_opnames.*') ||
        request()->routeIs('fulfillment.*');

    $isFinanceActive =
        request()->routeIs('finance.mutations.*') ||
        request()->routeIs('finance.marketplace_wallets.*') ||
        request()->routeIs('finance.incomes.*') ||
        request()->routeIs('finance.expenses.*') ||
        request()->routeIs('finance.transfers.*');

    $isMarketingActive =
        request()->routeIs('marketing.teams.*') ||
        request()->routeIs('marketing.ads.*') ||
        request()->routeIs('marketing.flash_sales.*') ||
        request()->routeIs('marketing.tiered_discounts.*') ||
        request()->routeIs('chats.*') ||
        request()->routeIs('stores.*') ||
        request()->routeIs('orders.*') ||
        request()->routeIs('returns.*') ||
        request()->routeIs('offline_sales.*');

    $isHrdActive = request()->routeIs('hr.*') || request()->routeIs('employees.*');

    $isLaporanActive =
        request()->routeIs('reports.*') ||
        request()->routeIs('marketplace_products.print_report') ||
        request()->routeIs('purchase_orders.report') ||
        request()->routeIs('pembelian.stock_report') ||
        request()->routeIs('pembelian.report_mutation') ||
        request()->routeIs('pembelian.report_summary') ||
        request()->routeIs('pembelian.stock_card') ||
        request()->routeIs('finance.profit_loss') ||
        request()->routeIs('profit.*');
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
            <span class="v2-brand-subtitle">ERP NEXT-GEN</span>
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
        <div class="v2-nav-section-title">Menu Utama</div>

        <!-- Dashboard -->
        <div class="v2-nav-item">
            <a href="{{ route('dashboard') }}" class="v2-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Dashboard V2 (Preview New UI) -->
        <div class="v2-nav-item">
            <a href="{{ url('/dashboard-v2') }}" class="v2-nav-link {{ request()->is('dashboard-v2') ? 'active' : '' }}">
                <i class="bi bi-stars"></i>
                <span>Dashboard V2 (Baru)</span>
                <span class="badge bg-indigo text-white ms-auto" style="font-size: 0.62rem; background: #6366f1;">NEW</span>
            </a>
        </div>

        <!-- Master Data Accordion -->
        <div class="v2-nav-item">
            <a class="v2-nav-link {{ $isMasterDataActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#masterDataMenu" role="button" aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}">
                <i class="bi bi-database"></i>
                <span>Master Data</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 0.75rem;"></i>
            </a>
            <div class="collapse {{ $isMasterDataActive ? 'show' : '' }}" id="masterDataMenu">
                @if(Route::has('master.products.index'))
                <a href="{{ route('master.products.index') }}" class="v2-subnav-link {{ request()->routeIs('master.products.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Product Master
                </a>
                @endif
                @if(Route::has('categories.index'))
                <a href="{{ route('categories.index') }}" class="v2-subnav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Kategori Product
                </a>
                @endif
                @if(Route::has('brands.index'))
                <a href="{{ route('brands.index') }}" class="v2-subnav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Brand / Merk
                </a>
                @endif
                @if(Route::has('suppliers.index'))
                <a href="{{ route('suppliers.index') }}" class="v2-subnav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Data Supplier
                </a>
                @endif
                @if(Route::has('customers.index'))
                <a href="{{ route('customers.index') }}" class="v2-subnav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Data Pelanggan
                </a>
                @endif
                @if(Route::has('users.index'))
                <a href="{{ route('users.index') }}" class="v2-subnav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Pengguna / Users
                </a>
                @endif
            </div>
        </div>

        <!-- Section: Marketplace & Penjualan -->
        <div class="v2-nav-section-title">Marketplace & Sales</div>

        <div class="v2-nav-item">
            <a href="{{ route('stores.index') }}" class="v2-nav-link {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Toko Marketplace</span>
            </a>
        </div>

        <div class="v2-nav-item">
            <a href="{{ route('orders.index') }}" class="v2-nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i>
                <span>Pesanan Marketplace</span>
            </a>
        </div>

        @if(Route::has('returns.index'))
        <div class="v2-nav-item">
            <a href="{{ route('returns.index') }}" class="v2-nav-link {{ request()->routeIs('returns.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i>
                <span>Retur Pesanan</span>
            </a>
        </div>
        @endif

        @if(Route::has('offline_sales.index'))
        <div class="v2-nav-item">
            <a href="{{ route('offline_sales.index') }}" class="v2-nav-link {{ request()->routeIs('offline_sales.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i>
                <span>Penjualan Offline</span>
            </a>
        </div>
        @endif

        <!-- Section: Inventori & Stock -->
        <div class="v2-nav-section-title">Stok & Gudang</div>

        <div class="v2-nav-item">
            <a class="v2-nav-link {{ $isGudangJadiActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#gudangMenu" role="button" aria-expanded="{{ $isGudangJadiActive ? 'true' : 'false' }}">
                <i class="bi bi-box-seam"></i>
                <span>Manajemen Stok</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 0.75rem;"></i>
            </a>
            <div class="collapse {{ $isGudangJadiActive ? 'show' : '' }}" id="gudangMenu">
                @if(Route::has('inventory.mutations.index'))
                <a href="{{ route('inventory.mutations.index') }}" class="v2-subnav-link {{ request()->routeIs('inventory.mutations.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Mutasi Stok
                </a>
                @endif
                @if(Route::has('stock_opnames.index'))
                <a href="{{ route('stock_opnames.index') }}" class="v2-subnav-link {{ request()->routeIs('stock_opnames.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Stock Opname
                </a>
                @endif
                @if(Route::has('fulfillment.scan'))
                <a href="{{ route('fulfillment.scan') }}" class="v2-subnav-link {{ request()->routeIs('fulfillment.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Packing & Fulfillment
                </a>
                @endif
            </div>
        </div>

        <!-- Section: Finance -->
        <div class="v2-nav-section-title">Keuangan</div>

        <div class="v2-nav-item">
            <a class="v2-nav-link {{ $isFinanceActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#financeMenu" role="button" aria-expanded="{{ $isFinanceActive ? 'true' : 'false' }}">
                <i class="bi bi-wallet2"></i>
                <span>Finance & Kas</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 0.75rem;"></i>
            </a>
            <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="financeMenu">
                @if(Route::has('finance.incomes.index'))
                <a href="{{ route('finance.incomes.index') }}" class="v2-subnav-link {{ request()->routeIs('finance.incomes.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Pemasukan Kas
                </a>
                @endif
                @if(Route::has('finance.expenses.index'))
                <a href="{{ route('finance.expenses.index') }}" class="v2-subnav-link {{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Pengeluaran Kas
                </a>
                @endif
                @if(Route::has('finance.marketplace_wallets.index'))
                <a href="{{ route('finance.marketplace_wallets.index') }}" class="v2-subnav-link {{ request()->routeIs('finance.marketplace_wallets.*') ? 'active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Saldo Wallet Toko
                </a>
                @endif
            </div>
        </div>

        <!-- Section: Reports -->
        <div class="v2-nav-section-title">Laporan & Analitik</div>

        <div class="v2-nav-item">
            <a href="{{ route('reports.index') }}" class="v2-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i>
                <span>Pusat Laporan</span>
            </a>
        </div>
        @if(Route::has('profit.index'))
        <div class="v2-nav-item">
            <a href="{{ route('profit.index') }}" class="v2-nav-link {{ request()->routeIs('profit.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Analisis Labarugi</span>
            </a>
        </div>
        @endif
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
