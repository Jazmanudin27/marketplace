@php
    $currentRoute = request()->path();
    $user = Auth::user();
    $tenant = $user?->tenant;

    $isDataMasterActive =
        request()->is('v2/produk*') ||
        request()->is('v2/marketplace-produk*') ||
        request()->is('v2/produk-marketplace*') ||
        request()->is('marketplace-products*') ||
        request()->is('v2/kategori*') ||
        request()->is('v2/brand*') ||
        request()->is('v2/supplier*') ||
        request()->is('inventory-items*') ||
        request()->is('v2/barang*');

    $isMarketingActive =
        request()->is('v2/pesanan*') ||
        request()->is('v2/penjualan-offline*') ||
        request()->is('v2/retur*') ||
        request()->is('v2/spk*') ||
        request()->is('v2/laporan*') ||
        request()->is('v2/target-komisi*') ||
        request()->routeIs('v2.target_komisi.*') ||
        request()->is('marketing/teams*') ||
        request()->routeIs('marketing.teams.*') ||
        request()->is('spks*') ||
        request()->is('orders*') ||
        request()->is('returns*') ||
        request()->is('offline-sales*') ||
        request()->is('reports/released-sales*') ||
        request()->is('reports/sales*');

    $isPembelianActive =
        request()->is('v2/barang-masuk*') ||
        request()->is('v2/barang-keluar*') ||
        request()->is('pembelian*');

    $isGudangJadiActive =
        request()->is('v2/gudang-jadi*') ||
        request()->is('v2/stock-opname*') ||
        request()->is('v2/scanner-gudang*') ||
        request()->is('v2/reports/stock*') ||
        request()->is('v2/laporan-stok*') ||
        request()->is('v2/laporan-gudang*') ||
        request()->routeIs('v2.gudang_jadi.*') ||
        request()->routeIs('v2.scanner_gudang.*') ||
        request()->routeIs('v2.stock_opname.*') ||
        request()->routeIs('v2.reports.stock*') ||
        request()->routeIs('v2.laporan_gudang.*');

    $lowStockAlertCount = 0;
    if (auth()->check() && auth()->user()->tenant_id) {
        try {
            $lowStockAlertCount = \Illuminate\Support\Facades\Cache::remember(
                'low_stock_count_' . auth()->user()->tenant_id,
                60,
                function () {
                    return \App\Models\MasterProduct::where('tenant_id', auth()->user()->tenant_id)
                        ->where('stock', '<', 10)
                        ->count();
                }
            );
        } catch (\Throwable $e) {}
    }

    $isTitipanActive =
        request()->is('v2/titipan*') ||
        request()->is('supplier-consignments*');

    $isKeuanganActive =
        request()->is('v2/laba-rugi*') ||
        request()->is('v2/mutasi-keuangan*') ||
        request()->is('v2/saldo-marketplace*') ||
        request()->is('finance*');

    $isSettingsActive =
        request()->is('v2/users*') ||
        request()->is('v2/pengguna*') ||
        request()->is('v2/toko*') ||
        request()->is('stores*') ||
        request()->is('users*') ||
        request()->is('settings*');
@endphp

<!-- Horizontal Top Navigation Bar (Under Header, 100% Width) -->
<nav class="v2-top-navbar" id="v2TopNavbar">
    <div class="v2-top-navbar-container">
        <div class="v2-nav-menu-scroll">
            <ul class="v2-top-menu">
                {{-- 1. Dashboard --}}
                <li class="v2-top-item">
                    <a href="{{ url('/v2/dashboard') }}"
                       class="v2-top-link {{ request()->is('v2') || request()->is('v2/dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- 2. Data Master --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isDataMasterActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-folder-fill"></i>
                        <span>Data Master</span>
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/produk*') && !request()->is('v2/produk-marketplace*') ? 'active' : '' }}"
                               href="{{ url('/v2/produk') }}">
                                <i class="bi bi-box-seam me-2 text-primary"></i> Master Produk
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/marketplace-produk*') || request()->is('v2/produk-marketplace*') || request()->is('marketplace-products*') ? 'active' : '' }}"
                               href="{{ url('/v2/marketplace-produk') }}">
                                <i class="bi bi-tags-fill me-2 text-info"></i> Produk Marketplace
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/kategori*') || request()->is('v2/brand*') ? 'active' : '' }}"
                               href="{{ url('/v2/kategori-brand') }}">
                                <i class="bi bi-tags me-2 text-warning"></i> Kategori & Varian
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/supplier*') ? 'active' : '' }}"
                               href="{{ url('/v2/supplier') }}">
                                <i class="bi bi-truck me-2 text-secondary"></i> Master Supplier
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/barang*') || request()->is('inventory-items*') ? 'active' : '' }}"
                               href="{{ url('/v2/barang') }}">
                                <i class="bi bi-boxes me-2 text-success"></i> Data Barang
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 3. Marketplace & POS --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isMarketingActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-megaphone-fill text-danger"></i>
                        <span>Marketplace & POS</span>
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between {{ request()->is('v2/pesanan*') || request()->is('orders*') ? 'active' : '' }}"
                               href="{{ Route::has('v2.pesanan.index') ? route('v2.pesanan.index') : (Route::has('orders.index') ? route('orders.index') : url('/orders')) }}">
                                <span><i class="bi bi-cart-check-fill me-2 text-success"></i> Pesanan Masuk</span>
                                <span class="badge bg-danger rounded-pill px-1.5 py-0.5" style="font-size:0.6rem;">Live</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/penjualan-offline*') || request()->is('offline-sales*') ? 'active' : '' }}"
                               href="{{ route('v2.penjualan_offline.index') }}">
                                <i class="bi bi-shop-window me-2 text-primary"></i> Penjualan Offline
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/dropship*') || request()->routeIs('v2.dropship.*') ? 'active' : '' }}"
                               href="{{ route('v2.dropship.index') }}">
                                <i class="bi bi-truck me-2 text-info"></i> Dropship
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/retur*') || request()->is('returns*') ? 'active' : '' }}"
                               href="{{ Route::has('v2.retur.index') ? route('v2.retur.index') : (Route::has('returns.index') ? route('returns.index') : url('/returns')) }}">
                                <i class="bi bi-arrow-counterclockwise me-2 text-warning"></i> Pesanan Retur
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/spk*') || request()->is('spks*') || request()->routeIs('v2.spk.*') ? 'active' : '' }}"
                               href="{{ route('v2.spk.index') }}">
                                <i class="bi bi-tools me-2 text-warning"></i> SPK
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/laporan*') || request()->is('reports/released-sales*') || request()->is('reports/sales*') ? 'active' : '' }}"
                               href="{{ route('v2.laporan.index') }}">
                                <i class="bi bi-file-earmark-bar-graph me-2 text-info"></i> Laporan
                            </a>
                        </li>
                        @if (!\Illuminate\Support\Facades\Gate::has('v2.target_komisi.index') || auth()->user()->can('v2.target_komisi.index') || auth()->user()->isSuperAdmin() || in_array(auth()->user()->role ?? '', ['admin', 'owner']))
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/target-komisi*') || request()->routeIs('v2.target_komisi.*') || request()->is('marketing/teams*') || request()->routeIs('marketing.teams.*') ? 'active' : '' }}"
                               href="{{ Route::has('v2.target_komisi.index') ? route('v2.target_komisi.index') : (Route::has('marketing.teams.index') ? route('marketing.teams.index') : url('/v2/target-komisi')) }}">
                                <i class="bi bi-bullseye me-2 text-danger"></i> Target Komisi
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                {{-- 4. Pembelian & Stok --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isPembelianActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cart-plus-fill"></i>
                        <span>Pembelian & Stok</span>
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/barang-masuk*') ? 'active' : '' }}"
                               href="{{ Route::has('v2.barang_masuk.index') ? route('v2.barang_masuk.index') : url('/v2/barang-masuk') }}">
                                <i class="bi bi-box-arrow-in-down me-2 text-success"></i> Barang Masuk
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/barang-keluar*') ? 'active' : '' }}"
                               href="{{ Route::has('v2.barang_keluar.index') ? route('v2.barang_keluar.index') : url('/v2/barang-keluar') }}">
                                <i class="bi bi-box-arrow-up-right me-2 text-danger"></i> Barang Keluar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/barang*') || request()->is('pembelian/stock-report*') ? 'active' : '' }}"
                               href="{{ url('/v2/barang') }}">
                                <i class="bi bi-eye me-2 text-info"></i> Memantau Stok
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 5. Gudang Jadi --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isGudangJadiActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-arrow-left-right text-primary"></i>
                        <span>Gudang Jadi</span>
                        @if($lowStockAlertCount > 0)
                            <span class="badge bg-danger rounded-pill px-1.5 py-0.5 ms-1" style="font-size:0.58rem;">{{ $lowStockAlertCount }}</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('v2.gudang_jadi.*') ? 'active' : '' }}"
                               href="{{ route('v2.gudang_jadi.index') }}">
                                <i class="bi bi-arrow-left-right me-2 text-primary"></i> Mutasi Masuk & Keluar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/scanner-gudang*') || request()->routeIs('v2.scanner_gudang.*') ? 'active' : '' }}"
                               href="{{ route('v2.scanner_gudang.index') }}">
                                <i class="bi bi-qr-code-scan me-2 text-success"></i> Scanner Gudang
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between {{ request()->is('v2/reports/stock*') || request()->is('v2/laporan-stok*') || request()->routeIs('v2.reports.stock*') ? 'active' : '' }}"
                               href="{{ route('v2.reports.stock') }}">
                                <span><i class="bi bi-boxes me-2 text-info"></i> Stok Barang</span>
                                @if($lowStockAlertCount > 0)
                                    <span class="badge bg-danger rounded-pill px-1.5 py-0.5 ms-2" style="font-size:0.6rem;">{{ $lowStockAlertCount }} Low</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/gudang-jadi/laporan*') || request()->is('v2/laporan-gudang*') || request()->routeIs('v2.laporan_gudang.*') ? 'active' : '' }}"
                               href="{{ route('v2.laporan_gudang.index') }}">
                                <i class="bi bi-file-earmark-bar-graph me-2 text-info"></i> Laporan
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 6. Titipan Barang --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isTitipanActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam-fill text-warning"></i>
                        <span>Titipan Barang</span>
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('v2.titipan_barang.index') || request()->routeIs('v2.titipan_barang.create') || request()->routeIs('v2.titipan_barang.show') || request()->routeIs('v2.titipan_barang.edit') || request()->routeIs('supplier_consignments.index') ? 'active' : '' }}"
                               href="{{ route('v2.titipan_barang.index') }}">
                                <i class="bi bi-box-arrow-in-down me-2 text-success"></i> Penerimaan Barang Titipan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('v2.titipan_barang.deductions.*') || request()->routeIs('supplier_consignments.deductions.*') ? 'active' : '' }}"
                               href="{{ route('v2.titipan_barang.deductions.index') }}">
                                <i class="bi bi-clipboard-check me-2 text-warning"></i> Stok Opname Titipan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('v2.titipan_barang.stock_card') || request()->routeIs('supplier_consignments.stock_card') ? 'active' : '' }}"
                               href="{{ route('v2.titipan_barang.stock_card') }}">
                                <i class="bi bi-card-checklist me-2 text-info"></i> Kartu Stok Titipan Barang
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('v2.titipan_barang.settlement.*') || request()->routeIs('supplier_consignments.settlement.*') ? 'active' : '' }}"
                               href="{{ route('v2.titipan_barang.settlement.create') }}">
                                <i class="bi bi-cash-stack me-2 text-warning"></i> Pembayaran / Setoran Titipan
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 7. Keuangan --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isKeuanganActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cash-stack"></i>
                        <span>Keuangan</span>
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/laba-rugi*') ? 'active' : '' }}"
                               href="{{ route('v2.laba_rugi.index') }}">
                                <i class="bi bi-graph-up-arrow me-2 text-success"></i> Laba Rugi
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/mutasi-keuangan*') ? 'active' : '' }}"
                               href="{{ route('v2.mutasi_keuangan.index') }}">
                                <i class="bi bi-journal-text me-2 text-primary"></i> Mutasi Keuangan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/saldo-marketplace*') ? 'active' : '' }}"
                               href="{{ route('v2.saldo_marketplace.index') }}">
                                <i class="bi bi-wallet2 me-2 text-warning"></i> Saldo Marketplace
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 8. SETTINGS (Pengguna Sistem, Toko Terhubung, Pengaturan Akun) --}}
                <li class="v2-top-item dropdown">
                    <a href="#" class="v2-top-link dropdown-toggle {{ $isSettingsActive ? 'active' : '' }}"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill text-info"></i>
                        <span>Settings</span>
                    </a>
                    <ul class="dropdown-menu v2-top-dropdown shadow-lg">
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/users*') || request()->is('v2/pengguna*') ? 'active' : '' }}"
                               href="{{ route('v2.users.index') }}">
                                <i class="bi bi-people-fill me-2 text-info"></i> Pengguna Sistem
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('v2/toko*') || request()->is('stores*') ? 'active' : '' }}"
                               href="{{ url('/v2/toko') }}">
                                <i class="bi bi-shop me-2 text-primary"></i> Toko Terhubung
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->is('users*') || request()->is('settings*') ? 'active' : '' }}"
                               href="{{ Route::has('users.index') ? route('users.index') : url('/users') }}">
                                <i class="bi bi-person-gear me-2 text-secondary"></i> Pengaturan Akun
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
