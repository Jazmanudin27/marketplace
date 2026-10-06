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
                request()->is('v2/marketplace-produk*') ||
                request()->is('v2/produk-marketplace*') ||
                request()->is('marketplace-products*') ||
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
                        class="v2-submenu-link {{ request()->is('v2/produk*') && !request()->is('v2/produk-marketplace*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam me-1.5"></i>
                        <span>Master Produk</span>
                    </a>
                    <a href="{{ url('/v2/marketplace-produk') }}"
                        class="v2-submenu-link {{ request()->is('v2/marketplace-produk*') || request()->is('v2/produk-marketplace*') || request()->is('marketplace-products*') ? 'active' : '' }}">
                        <i class="bi bi-tags-fill text-primary me-1.5"></i>
                        <span>Produk Marketplace</span>
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
        @php
            $isMarketingActive =
                request()->is('v2/pesanan*') ||
                request()->is('v2/retur*') ||
                request()->is('v2/spk*') ||
                request()->is('spks*') ||
                request()->is('orders*') ||
                request()->is('returns*');
        @endphp
        <div class="v2-nav-item v2-nav-dropdown {{ $isMarketingActive ? 'show' : '' }}">
            <a href="javascript:void(0)"
                class="v2-nav-link v2-dropdown-toggle d-flex align-items-center justify-content-between {{ $isMarketingActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#marketingSubmenu"
                aria-expanded="{{ $isMarketingActive ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-megaphone-fill text-danger"></i>
                    <span>Marketing</span>
                </div>
                <i class="bi bi-chevron-down v2-dropdown-arrow ms-auto" style="font-size: 0.65rem;"></i>
            </a>
            <div class="collapse v2-submenu {{ $isMarketingActive ? 'show' : '' }}" id="marketingSubmenu">
                <div class="v2-submenu-inner">
                    <a href="{{ Route::has('v2.pesanan.index') ? route('v2.pesanan.index') : (Route::has('orders.index') ? route('orders.index') : url('/orders')) }}"
                        class="v2-submenu-link d-flex align-items-center justify-content-between {{ request()->is('v2/pesanan*') || request()->is('orders*') ? 'active' : '' }}">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cart-check-fill text-success me-1.5"></i>
                            <span>Pesanan Masuk</span>
                        </div>
                        <span class="badge bg-danger rounded-pill py-0.5 px-1.5" style="font-size: 0.62rem;">Live</span>
                    </a>
                    <a href="{{ Route::has('v2.retur.index') ? route('v2.retur.index') : (Route::has('returns.index') ? route('returns.index') : url('/returns')) }}"
                        class="v2-submenu-link {{ request()->is('v2/retur*') || request()->is('returns*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-counterclockwise text-warning me-1.5"></i>
                        <span>Pesanan Retur</span>
                    </a>
                    <a href="{{ route('v2.spk.index') }}"
                        class="v2-submenu-link {{ request()->is('v2/spk*') || request()->is('spks*') || request()->routeIs('v2.spk.*') ? 'active' : '' }}">
                        <i class="bi bi-tools text-warning me-1.5"></i>
                        <span>SPK</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pembelian & Stok -->
        <div class="v2-nav-section-title mt-2">PEMBELIAN & STOK</div>
        @php
            $isPembelianActive =
                request()->is('v2/barang-masuk*') ||
                request()->is('v2/barang-keluar*') ||
                request()->is('v2/barang*') ||
                request()->is('pembelian*');
        @endphp
        <div class="v2-nav-item v2-nav-dropdown {{ $isPembelianActive ? 'show' : '' }}">
            <a href="javascript:void(0)"
                class="v2-nav-link v2-dropdown-toggle d-flex align-items-center justify-content-between {{ $isPembelianActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#pembelianSubmenu"
                aria-expanded="{{ $isPembelianActive ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cart-plus-fill"></i>
                    <span>Pembelian & Stok</span>
                </div>
                <i class="bi bi-chevron-down v2-dropdown-arrow ms-auto" style="font-size: 0.65rem;"></i>
            </a>
            <div class="collapse v2-submenu {{ $isPembelianActive ? 'show' : '' }}" id="pembelianSubmenu">
                <div class="v2-submenu-inner">
                    <a href="{{ Route::has('v2.barang_masuk.index') ? route('v2.barang_masuk.index') : url('/v2/barang-masuk') }}"
                        class="v2-submenu-link {{ request()->is('v2/barang-masuk*') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-down text-success me-1.5"></i>
                        <span>Barang Masuk</span>
                    </a>
                    <a href="{{ Route::has('v2.barang_keluar.index') ? route('v2.barang_keluar.index') : url('/v2/barang-keluar') }}"
                        class="v2-submenu-link {{ request()->is('v2/barang-keluar*') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-up-right text-danger me-1.5"></i>
                        <span>Barang Keluar</span>
                    </a>
                    <a href="{{ url('/v2/barang') }}"
                        class="v2-submenu-link {{ request()->is('v2/barang*') || request()->is('pembelian/stock-report*') ? 'active' : '' }}">
                        <i class="bi bi-eye text-info me-1.5"></i>
                        <span>Memantau Stok</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Gudang Jadi -->
        <div class="v2-nav-section-title mt-2">GUDANG JADI</div>
        @php
            $isGudangJadiActive =
                request()->is('v2/gudang-jadi*') ||
                request()->is('v2/stock-opname*') ||
                request()->is('v2/scanner-gudang*') ||
                request()->routeIs('v2.gudang_jadi.*') ||
                request()->routeIs('v2.scanner_gudang.*') ||
                request()->routeIs('v2.stock_opname.*');
        @endphp
        <div class="v2-nav-item v2-nav-dropdown {{ $isGudangJadiActive ? 'show' : '' }}">
            <a href="javascript:void(0)"
                class="v2-nav-link v2-dropdown-toggle d-flex align-items-center justify-content-between {{ $isGudangJadiActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#gudangJadiSubmenu"
                aria-expanded="{{ $isGudangJadiActive ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right text-primary"></i>
                    <span>Mutasi Produk</span>
                </div>
                <i class="bi bi-chevron-down v2-dropdown-arrow ms-auto" style="font-size: 0.65rem;"></i>
            </a>
            <div class="collapse v2-submenu {{ $isGudangJadiActive ? 'show' : '' }}" id="gudangJadiSubmenu">
                <div class="v2-submenu-inner">
                    <a href="{{ route('v2.gudang_jadi.masuk') }}"
                        class="v2-submenu-link {{ request()->routeIs('v2.gudang_jadi.masuk') || (request()->routeIs('v2.gudang_jadi.index') && request('type') === 'in') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-down text-success me-1.5"></i>
                        <span>Barang Masuk</span>
                    </a>
                    <a href="{{ route('v2.gudang_jadi.keluar') }}"
                        class="v2-submenu-link {{ request()->routeIs('v2.gudang_jadi.keluar') || (request()->routeIs('v2.gudang_jadi.index') && request('type') === 'out') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-up-right text-danger me-1.5"></i>
                        <span>Barang Keluar</span>
                    </a>
                    <a href="{{ route('v2.gudang_jadi.index') }}"
                        class="v2-submenu-link {{ request()->routeIs('v2.gudang_jadi.index') && !request()->has('type') ? 'active' : '' }}">
                        <i class="bi bi-building-gear text-primary me-1.5"></i>
                        <span>Gudang Jadi (Semua)</span>
                    </a>
                    <a href="{{ route('v2.scanner_gudang.index') }}"
                        class="v2-submenu-link {{ request()->is('v2/scanner-gudang*') || request()->routeIs('v2.scanner_gudang.*') ? 'active' : '' }}">
                        <i class="bi bi-qr-code-scan text-success me-1.5"></i>
                        <span>Scanner Gudang</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Titipan Barang -->
        <div class="v2-nav-section-title mt-2">TITIPAN BARANG</div>
        @php
            $isTitipanActive =
                request()->is('v2/titipan*') ||
                request()->is('supplier-consignments*');
        @endphp
        <div class="v2-nav-item v2-nav-dropdown {{ $isTitipanActive ? 'show' : '' }}">
            <a href="javascript:void(0)"
                class="v2-nav-link v2-dropdown-toggle d-flex align-items-center justify-content-between {{ $isTitipanActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#titipanSubmenu"
                aria-expanded="{{ $isTitipanActive ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam-fill text-warning"></i>
                    <span>Titipan Barang</span>
                </div>
                <i class="bi bi-chevron-down v2-dropdown-arrow ms-auto" style="font-size: 0.65rem;"></i>
            </a>
            <div class="collapse v2-submenu {{ $isTitipanActive ? 'show' : '' }}" id="titipanSubmenu">
                <div class="v2-submenu-inner">
                    <a href="{{ route('v2.titipan_barang.index') }}"
                        class="v2-submenu-link {{ request()->routeIs('v2.titipan_barang.index') || request()->routeIs('v2.titipan_barang.create') || request()->routeIs('v2.titipan_barang.show') || request()->routeIs('v2.titipan_barang.edit') || request()->routeIs('supplier_consignments.index') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-down text-success me-1.5"></i>
                        <span>Penerimaan Barang Titipan</span>
                    </a>
                    <a href="{{ route('v2.titipan_barang.stock_card') }}"
                        class="v2-submenu-link {{ request()->routeIs('v2.titipan_barang.stock_card') || request()->routeIs('supplier_consignments.stock_card') ? 'active' : '' }}">
                        <i class="bi bi-card-checklist text-info me-1.5"></i>
                        <span>Kartu Stok Titipan Barang</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Keuangan -->
        <div class="v2-nav-section-title mt-2">KEUANGAN</div>
        @php
            $isKeuanganActive =
                request()->is('v2/mutasi-keuangan*') ||
                request()->is('v2/saldo-marketplace*') ||
                request()->is('reports*') ||
                request()->is('finance*');
        @endphp
        <div class="v2-nav-item v2-nav-dropdown {{ $isKeuanganActive ? 'show' : '' }}">
            <a href="javascript:void(0)"
                class="v2-nav-link v2-dropdown-toggle d-flex align-items-center justify-content-between {{ $isKeuanganActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#keuanganSubmenu"
                aria-expanded="{{ $isKeuanganActive ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cash-stack"></i>
                    <span>Keuangan</span>
                </div>
                <i class="bi bi-chevron-down v2-dropdown-arrow ms-auto" style="font-size: 0.65rem;"></i>
            </a>
            <div class="collapse v2-submenu {{ $isKeuanganActive ? 'show' : '' }}" id="keuanganSubmenu">
                <div class="v2-submenu-inner">
                    <a href="{{ route('v2.mutasi_keuangan.index') }}"
                        class="v2-submenu-link {{ request()->is('v2/mutasi-keuangan*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text text-primary me-1.5"></i>
                        <span>Mutasi Keuangan</span>
                    </a>
                    <a href="{{ route('v2.saldo_marketplace.index') }}"
                        class="v2-submenu-link {{ request()->is('v2/saldo-marketplace*') ? 'active' : '' }}">
                        <i class="bi bi-wallet2 text-warning me-1.5"></i>
                        <span>Saldo Marketplace</span>
                    </a>
                    <a href="{{ Route::has('reports.income_statement') ? route('reports.income_statement') : url('/reports') }}"
                        class="v2-submenu-link {{ request()->is('reports*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line-fill text-success me-1.5"></i>
                        <span>Laba Rugi & Keuangan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pengaturan -->
        <div class="v2-nav-section-title mt-2">PENGATURAN</div>
        <div class="v2-nav-item">
            <a href="{{ url('/v2/toko') }}"
                class="v2-nav-link d-flex align-items-center gap-2 {{ request()->is('v2/toko*') || request()->is('stores*') ? 'active' : '' }}">
                <i class="bi bi-shop text-primary"></i>
                <span>Toko Terhubung</span>
            </a>
        </div>
        <div class="v2-nav-item">
            <a href="{{ Route::has('settings.users.index') ? route('settings.users.index') : (Route::has('users.index') ? route('users.index') : url('/settings')) }}"
                class="v2-nav-link d-flex align-items-center gap-2 {{ request()->is('settings*') || request()->is('users*') ? 'active' : '' }}">
                <i class="bi bi-gear text-secondary"></i>
                <span>Pengaturan Akun</span>
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
