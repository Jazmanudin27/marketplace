<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">

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

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ time() }}">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ asset('js/app.js') }}"></script>
    <style>
        /* Select2 Dark Mode Tweaks */
        .select2-container--default .select2-selection--single {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            height: 38px;
            border-radius: 0.375rem;
            color: var(--text-primary);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-primary);
            line-height: 36px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-dropdown {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
        }

        .select2-results__option {
            color: var(--text-primary);
        }

        .select2-search--dropdown .select2-search__field {
            background-color: #ffffff !important;
            border: 1px solid var(--border);
            color: #000000 !important;
        }

        .select2-container--default .select2-results__option--selected {
            background-color: var(--primary);
            color: #fff;
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: var(--primary);
            color: #fff;
        }

        /* Fix Select2 z-index inside Bootstrap Modal */
        .select2-container--open {
            z-index: 9999 !important;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="app-wrapper">
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

        {{-- SIDEBAR --}}
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Close Sidebar">
                <i class="fas fa-times"></i>
            </button>
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
                    <div class="tenant-role">{{ Auth::user()->roles->first() ? ucfirst(Auth::user()->roles->first()->name) : ucfirst(Auth::user()->role) }}</div>
                </div>
            </div>

            <nav class="sidebar-nav">

                @php
                    $isMasterDataActive =
                        request()->routeIs('categories.*') ||
                        request()->routeIs('brands.*') ||
                        request()->routeIs('suppliers.*') ||
                        request()->routeIs('employees.*') ||
                        request()->routeIs('customers.*') ||
                        request()->routeIs('users.*');

                    $isTransaksiActive =
                        request()->routeIs('incoming_goods.*') ||
                        request()->routeIs('orders.*') ||
                        request()->routeIs('returns.*') ||
                        request()->routeIs('chats.*') ||
                        request()->routeIs('offline_sales.*') ||
                        request()->routeIs('fulfillment.*');

                    $isInventoryActive = request()->routeIs('inventory.*') || request()->routeIs('stock_opnames.*');

                    $isReportActive = request()->routeIs('reports.*');

                    $isFinanceActive =
                        request()->routeIs('finance.profit_loss') ||
                        request()->routeIs('finance.incomes.*') ||
                        request()->routeIs('finance.expenses.*') ||
                        request()->routeIs('finance.transfers.*') ||
                        request()->routeIs('finance.reconciliation') ||
                        request()->routeIs('profit.*');
                @endphp

                {{-- UTAMA --}}
                <div class="nav-section-title">UTAMA</div>

                <a href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                {{-- MASTER DATA (Admin / Users with master permissions) --}}
                @if (auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['manage-categories', 'manage-brands', 'manage-suppliers', 'manage-employees', 'manage-customers', 'manage-users', 'manage-products', 'manage-stores']))
                    <div class="nav-section-title mt-2">MASTER</div>

                    {{-- Master Data Dropdown --}}
                    @if (auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['manage-categories', 'manage-brands', 'manage-suppliers', 'manage-employees', 'manage-customers', 'manage-users']))
                        <div onclick="toggleDropdown('collapseMasterData', this)" role="button"
                            aria-controls="collapseMasterData" class="nav-item {{ $isMasterDataActive ? '' : 'collapsed' }}"
                            aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}" style="cursor: pointer;">
                            <i class="fas fa-database"></i>
                            <span>Master Data</span>
                            <i class="fas fa-chevron-down ms-auto"
                                style="font-size: 0.75rem; transition: transform 0.2s;"></i>
                        </div>
                        <div class="collapse {{ $isMasterDataActive ? 'show' : '' }}" id="collapseMasterData">
                            <div class="ps-3 pe-2 pb-1">
                                @can('manage-categories')
                                    <a href="{{ route('categories.index') }}"
                                        class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                        <i class="fas fa-folder"></i>
                                        <span>Kategori</span>
                                    </a>
                                @endcan
                                @can('manage-brands')
                                    <a href="{{ route('brands.index') }}"
                                        class="nav-item {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                                        <i class="fas fa-tags"></i>
                                        <span>Merk</span>
                                    </a>
                                @endcan
                                @can('manage-suppliers')
                                    <a href="{{ route('suppliers.index') }}"
                                        class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                        <i class="fas fa-truck"></i>
                                        <span>Supplier</span>
                                    </a>
                                @endcan
                                @can('manage-employees')
                                    <a href="{{ route('employees.index') }}"
                                        class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                                        <i class="fas fa-id-card"></i>
                                        <span>Karyawan</span>
                                    </a>
                                @endcan
                                @can('manage-customers')
                                    <a href="{{ route('customers.index') }}"
                                        class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                        <i class="fas fa-users"></i>
                                        <span>Pelanggan</span>
                                    </a>
                                @endcan
                                @can('manage-users')
                                    <a href="{{ route('users.index') }}"
                                        class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                        <i class="fas fa-user-shield"></i>
                                        <span>Pengguna</span>
                                    </a>
                                    <a href="{{ route('roles.index') }}"
                                        class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                        <i class="fas fa-user-lock"></i>
                                        <span>Hak Akses</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endif

                    {{-- Master Produk --}}
                    @can('manage-products')
                        <a href="{{ route('products.index') }}"
                            class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="fas fa-box-open"></i>
                            <span>Master Produk</span>
                        </a>

                        {{-- Marketplace Produk --}}
                        <a href="{{ route('marketplace_products.index') }}"
                            class="nav-item {{ request()->routeIs('marketplace_products.*') ? 'active' : '' }}">
                            <i class="fas fa-store"></i>
                            <span>Marketplace Produk</span>
                        </a>
                    @endcan
                @endif

                {{-- TRANSAKSI (Admin + Warehouse / Users with transaction permissions) --}}
                @if (auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['manage-incoming-goods', 'manage-orders', 'manage-fulfillment', 'manage-returns', 'manage-offline-sales', 'manage-chats', 'manage-inventory']))
                    <div class="nav-section-title mt-2">TRANSAKSI</div>

                    @if (auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['manage-incoming-goods', 'manage-orders', 'manage-fulfillment', 'manage-returns', 'manage-offline-sales', 'manage-chats']))
                        <div onclick="toggleDropdown('collapseTransaksi', this)" role="button"
                            aria-controls="collapseTransaksi" class="nav-item {{ $isTransaksiActive ? '' : 'collapsed' }}"
                            aria-expanded="{{ $isTransaksiActive ? 'true' : 'false' }}" style="cursor: pointer;">
                            <i class="fas fa-receipt"></i>
                            <span>Transaksi</span>
                            <i class="fas fa-chevron-down ms-auto"
                                style="font-size: 0.75rem; transition: transform 0.2s;"></i>
                        </div>
                        <div class="collapse {{ $isTransaksiActive ? 'show' : '' }}" id="collapseTransaksi">
                            <div class="ps-3 pe-2 pb-1">
                                @can('manage-incoming-goods')
                                    <a href="{{ route('incoming_goods.index') }}"
                                        class="nav-item {{ request()->routeIs('incoming_goods.*') ? 'active' : '' }}">
                                        <i class="fas fa-truck-loading"></i>
                                        <span>Barang Masuk</span>
                                    </a>
                                @endcan
                                @can('manage-orders')
                                    <a href="{{ route('orders.index') }}"
                                        class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Pesanan Masuk</span>
                                        @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                                            <span class="nav-badge">{{ $pendingOrdersCount }}</span>
                                        @endif
                                    </a>
                                @endcan
                                @can('manage-fulfillment')
                                    <a href="{{ route('fulfillment.index') }}"
                                        class="nav-item {{ request()->routeIs('fulfillment.*') ? 'active' : '' }}">
                                        <i class="fas fa-barcode"></i>
                                        <span>Kemas Pesanan (Scan)</span>
                                    </a>
                                @endcan
                                @can('manage-returns')
                                    <a href="{{ route('returns.index') }}"
                                        class="nav-item {{ request()->routeIs('returns.*') ? 'active' : '' }}">
                                        <i class="fas fa-undo-alt"></i>
                                        <span>Pesanan Retur</span>
                                    </a>
                                @endcan
                                @can('manage-offline-sales')
                                    <a href="{{ route('offline_sales.index') }}"
                                        class="nav-item {{ request()->routeIs('offline_sales.*') ? 'active' : '' }}">
                                        <i class="fas fa-store-slash"></i>
                                        <span>Penjualan Offline</span>
                                    </a>
                                @endcan
                                @can('manage-chats')
                                    <a href="{{ route('chats.index') }}"
                                        class="nav-item {{ request()->routeIs('chats.*') ? 'active' : '' }}">
                                        <i class="fas fa-comments"></i>
                                        <span>Inbox Chat</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endif

                    {{-- Inventory Stok Dropdown --}}
                    @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('manage-inventory'))
                        @php
                            $lowStockCount = \App\Models\MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                                ->whereColumn('stock', '<=', 'min_stock')
                                ->where('is_active', true)
                                ->count();
                        @endphp
                        <div onclick="toggleDropdown('collapseInventory', this)" role="button"
                            aria-controls="collapseInventory"
                            class="nav-item {{ $isInventoryActive ? '' : 'collapsed' }}"
                            aria-expanded="{{ $isInventoryActive ? 'true' : 'false' }}" style="cursor: pointer;">
                            <i class="fas fa-boxes"></i>
                            <span>Inventory Stok</span>
                            @if ($lowStockCount > 0)
                                <span class="nav-badge" style="background:#ef4444;">{{ $lowStockCount }}</span>
                            @endif
                            <i class="fas fa-chevron-down ms-auto"
                                style="font-size: 0.75rem; transition: transform 0.2s;"></i>
                        </div>

                        <div class="collapse {{ $isInventoryActive ? 'show' : '' }}" id="collapseInventory">
                            <div class="ps-3 pe-2 pb-1">
                                <a href="{{ route('inventory.index') }}"
                                    class="nav-item {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.ledger') ? 'active' : '' }}">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Stok Gudang</span>
                                </a>
                                <a href="{{ route('stock_opnames.index') }}"
                                    class="nav-item {{ request()->routeIs('stock_opnames.*') ? 'active' : '' }}">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Opname Stok</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- LAPORAN (Admin + Warehouse / Users with report permissions) --}}
                @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('view-warehouse-reports'))
                    <div class="nav-section-title mt-2">LAPORAN</div>

                    <div onclick="toggleDropdown('collapseReports', this)" role="button"
                        aria-controls="collapseReports" class="nav-item {{ $isReportActive ? '' : 'collapsed' }}"
                        aria-expanded="{{ $isReportActive ? 'true' : 'false' }}" style="cursor: pointer;">
                        <i class="fas fa-chart-bar"></i>
                        <span>Laporan Gudang</span>
                        <i class="fas fa-chevron-down ms-auto"
                            style="font-size: 0.75rem; transition: transform 0.2s;"></i>
                    </div>
                    <div class="collapse {{ $isReportActive ? 'show' : '' }}" id="collapseReports">
                        <div class="ps-3 pe-2 pb-1">
                            <a href="{{ route('reports.summary') }}"
                                class="nav-item {{ request()->routeIs('reports.summary*') ? 'active' : '' }}">
                                <i class="fas fa-th-list"></i>
                                <span>Rekap Persediaan</span>
                            </a>
                            <a href="{{ route('reports.stock') }}"
                                class="nav-item {{ request()->routeIs('reports.stock*') ? 'active' : '' }}">
                                <i class="fas fa-file-invoice"></i>
                                <span>Stok Barang</span>
                            </a>
                            <a href="{{ route('reports.ledger') }}"
                                class="nav-item {{ request()->routeIs('reports.ledger*') ? 'active' : '' }}">
                                <i class="fas fa-history"></i>
                                <span>Kartu Stok</span>
                            </a>
                            <a href="{{ route('reports.opname') }}"
                                class="nav-item {{ request()->routeIs('reports.opname*') ? 'active' : '' }}">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Riwayat Opname</span>
                            </a>
                            <a href="{{ route('reports.analytics') }}"
                                class="nav-item {{ request()->routeIs('reports.analytics*') ? 'active' : '' }}">
                                <i class="fas fa-brain"></i>
                                <span>Analitik Inventori</span>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- KEUANGAN (Admin + Finance / Users with finance permissions) --}}
                @if (auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['view-financial-reports', 'manage-finance']))
                    <div class="nav-section-title mt-2">KEUANGAN</div>

                    <div onclick="toggleDropdown('collapseFinance', this)" role="button"
                        aria-controls="collapseFinance" class="nav-item {{ $isFinanceActive ? '' : 'collapsed' }}"
                        aria-expanded="{{ $isFinanceActive ? 'true' : 'false' }}" style="cursor: pointer;">
                        <i class="fas fa-wallet"></i>
                        <span>Pengelolaan Keuangan</span>
                        <i class="fas fa-chevron-down ms-auto"
                            style="font-size: 0.75rem; transition: transform 0.2s;"></i>
                    </div>
                    <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="collapseFinance">
                        <div class="ps-3 pe-2 pb-1">
                            @can('view-financial-reports')
                                <a href="{{ route('finance.profit_loss') }}"
                                    class="nav-item {{ request()->routeIs('finance.profit_loss') ? 'active' : '' }}">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>Laporan Laba Rugi</span>
                                </a>
                                <a href="{{ route('profit.index') }}"
                                    class="nav-item {{ request()->routeIs('profit.*') ? 'active' : '' }}">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Laporan Profit / Pesanan</span>
                                </a>
                            @endcan
                            @can('manage-finance')
                                <a href="{{ route('finance.reconciliation') }}"
                                    class="nav-item {{ request()->routeIs('finance.reconciliation') ? 'active' : '' }}">
                                    <i class="fas fa-balance-scale"></i>
                                    <span>Rekonsiliasi Keuangan</span>
                                </a>
                                <a href="{{ route('finance.incomes.index') }}"
                                    class="nav-item {{ request()->routeIs('finance.incomes.*') ? 'active' : '' }}">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>Pemasukan Lain</span>
                                </a>
                                <a href="{{ route('finance.expenses.index') }}"
                                    class="nav-item {{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}">
                                    <i class="fas fa-arrow-down"></i>
                                    <span>Pengeluaran & Biaya</span>
                                </a>
                                <a href="{{ route('finance.transfers.index') }}"
                                    class="nav-item {{ request()->routeIs('finance.transfers.*') ? 'active' : '' }}">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>Transfer Dana</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- INTEGRASI (Admin / Users with store permissions) --}}
                @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('manage-stores'))
                    <div class="nav-section-title mt-2">INTEGRASI</div>
                    @can('manage-stores')
                        <a href="{{ route('stores.index') }}"
                            class="nav-item {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                            <i class="fas fa-plug"></i>
                            <span>Kelola Toko</span>
                        </a>
                    @endcan
                @endif

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
                    <div class="alert alert-success"
                        style="background-color: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger"
                        style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger"
                        style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // Initialize Select2 on all elements with class .form-select
            $('.form-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownCssClass: 'dark-dropdown',
                placeholder: 'Pilih opsi...'
            });
        });

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebar-overlay').classList.toggle('show');
            document.body.style.overflow = document.getElementById('sidebar').classList.contains('show') ? 'hidden' :
                'auto';
        }

        function toggleDropdown(targetId, btn) {
            const target = document.getElementById(targetId);
            const isExpanded = target.classList.contains('show');
            if (isExpanded) {
                target.classList.remove('show');
                btn.setAttribute('aria-expanded', 'false');
                btn.classList.add('collapsed');
            } else {
                target.classList.add('show');
                btn.setAttribute('aria-expanded', 'true');
                btn.classList.remove('collapsed');
            }
        }
    </script>
    @stack('scripts')
</body>

</html>
