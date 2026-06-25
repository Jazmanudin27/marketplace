@php
    $isMasterDataActive =
        request()->routeIs('categories.*') ||
        request()->routeIs('brands.*') ||
        request()->routeIs('suppliers.*') ||
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

    $isHrdActive = request()->routeIs('hr.*') || request()->routeIs('employees.*');

    $isFinanceActive =
        request()->routeIs('finance.profit_loss') ||
        request()->routeIs('finance.incomes.*') ||
        request()->routeIs('finance.expenses.*') ||
        request()->routeIs('finance.transfers.*') ||
        request()->routeIs('finance.reconciliation') ||
        request()->routeIs('profit.*');
@endphp

<div class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="d-flex align-items-center gap-2.5">
            <div class="logo-box">
                <i class="bi bi-rocket-takeoff-fill logo-icon"></i>
            </div>
            <div class="lh-sm">
                <h4 class="mb-0 brand-title">ASPARTECH</h4>
                <small class="brand-subtitle">ERP Marketplace</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" id="closeSidebar" aria-label="Close"
            onclick="$('.sidebar').removeClass('show'); $('.overlay').removeClass('show');"></button>
    </div>

    <!-- Tenant Badge (Avatar and Info) -->
    <div class="tenant-badge-custom">
        <div class="tenant-card">
            <div class="tenant-avatar">
                {{ strtoupper(substr(Auth::user()->tenant->name, 0, 1)) }}
            </div>
            <div class="tenant-info">
                <div class="tenant-name" title="{{ Auth::user()->tenant->name }}">{{ Auth::user()->tenant->name }}</div>
                <div class="tenant-role">
                    {{ Auth::user()->roles->first() ? ucfirst(Auth::user()->roles->first()->name) : ucfirst(Auth::user()->role) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">

        <!-- UTAMA -->
        <div class="section-title">Utama</div>

        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>

        <!-- MASTER DATA -->
        @if (auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'manage-categories',
                        'manage-brands',
                        'manage-suppliers',
                        'manage-employees',
                        'manage-customers',
                        'manage-users',
                        'manage-products',
                        'manage-stores',
                    ]))
            <div class="section-title">Master</div>

            @if (auth()->user()->role === 'admin' ||
                    auth()->user()->hasAnyPermission([
                            'manage-categories',
                            'manage-brands',
                            'manage-suppliers',
                            'manage-employees',
                            'manage-customers',
                            'manage-users',
                        ]))
                <div>
                    <div class="dropdown-trigger {{ $isMasterDataActive ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                        data-bs-target="#collapseMasterData" role="button"
                        aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}" aria-controls="collapseMasterData">
                        <i class="bi bi-database"></i>
                        <span>Master Data</span>
                        <i class="bi bi-chevron-down ms-auto chevron" style="font-size: 0.8rem;"></i>
                    </div>
                    <div class="collapse {{ $isMasterDataActive ? 'show' : '' }}" id="collapseMasterData">
                        <div class="submenu-container">
                            @can('manage-categories')
                                <a href="{{ route('categories.index') }}"
                                    class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">Kategori</a>
                            @endcan
                            @can('manage-brands')
                                <a href="{{ route('brands.index') }}"
                                    class="{{ request()->routeIs('brands.*') ? 'active' : '' }}">Merk</a>
                            @endcan
                            @can('manage-suppliers')
                                <a href="{{ route('suppliers.index') }}"
                                    class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Supplier</a>
                            @endcan
                            @can('manage-employees')
                                <a href="{{ route('employees.index') }}"
                                    class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">Karyawan</a>
                            @endcan
                            @can('manage-customers')
                                <a href="{{ route('customers.index') }}"
                                    class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Pelanggan</a>
                            @endcan
                            @can('manage-users')
                                <a href="{{ route('users.index') }}"
                                    class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Pengguna</a>
                                <a href="{{ route('roles.index') }}"
                                    class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">Hak Akses</a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif

            @can('manage-products')
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i>
                    <span>Master Produk</span>
                </a>

                <a href="{{ route('marketplace_products.index') }}"
                    class="{{ request()->routeIs('marketplace_products.*') ? 'active' : '' }}">
                    <i class="bi bi-shop"></i>
                    <span>Marketplace Produk</span>
                </a>
            @endcan
        @endif

        <!-- TRANSAKSI -->
        @if (auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'manage-incoming-goods',
                        'manage-orders',
                        'manage-fulfillment',
                        'manage-returns',
                        'manage-offline-sales',
                        'manage-chats',
                        'manage-inventory',
                    ]))
            <div class="section-title">Transaksi</div>

            @if (auth()->user()->role === 'admin' ||
                    auth()->user()->hasAnyPermission([
                            'manage-incoming-goods',
                            'manage-orders',
                            'manage-fulfillment',
                            'manage-returns',
                            'manage-offline-sales',
                            'manage-chats',
                        ]))
                <div>
                    <div class="dropdown-trigger {{ $isTransaksiActive ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                        data-bs-target="#collapseTransaksi" role="button"
                        aria-expanded="{{ $isTransaksiActive ? 'true' : 'false' }}" aria-controls="collapseTransaksi">
                        <i class="bi bi-receipt"></i>
                        <span>Transaksi</span>
                        <i class="bi bi-chevron-down ms-auto chevron" style="font-size: 0.8rem;"></i>
                    </div>
                    <div class="collapse {{ $isTransaksiActive ? 'show' : '' }}" id="collapseTransaksi">
                        <div class="submenu-container">
                            @can('manage-incoming-goods')
                                <a href="{{ route('incoming_goods.index') }}"
                                    class="{{ request()->routeIs('incoming_goods.*') ? 'active' : '' }}">Barang Masuk</a>
                            @endcan
                            @can('manage-orders')
                                <a href="{{ route('orders.index') }}"
                                    class="{{ request()->routeIs('orders.*') ? 'active' : '' }} d-flex align-items-center justify-content-between pe-3">
                                    <span>Pesanan Masuk</span>
                                    @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                                        <span class="badge bg-danger rounded-pill"
                                            style="font-size: 0.7rem;">{{ $pendingOrdersCount }}</span>
                                    @endif
                                </a>
                            @endcan
                            @can('manage-fulfillment')
                                <a href="{{ route('fulfillment.index') }}"
                                    class="{{ request()->routeIs('fulfillment.*') ? 'active' : '' }}">Kemas Pesanan
                                    (Scan)</a>
                            @endcan
                            @can('manage-returns')
                                <a href="{{ route('returns.index') }}"
                                    class="{{ request()->routeIs('returns.*') ? 'active' : '' }}">Pesanan Retur</a>
                            @endcan
                            @can('manage-offline-sales')
                                <a href="{{ route('offline_sales.index') }}"
                                    class="{{ request()->routeIs('offline_sales.*') ? 'active' : '' }}">Penjualan
                                    Offline</a>
                            @endcan
                            @can('manage-chats')
                                <a href="{{ route('chats.index') }}"
                                    class="{{ request()->routeIs('chats.*') ? 'active' : '' }}">Inbox Chat</a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('manage-inventory'))
                @php
                    $lowStockCount = \App\Models\MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                        ->whereColumn('stock', '<=', 'min_stock')
                        ->where('is_active', true)
                        ->count();
                @endphp
                <div>
                    <div class="dropdown-trigger {{ $isInventoryActive ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                        data-bs-target="#collapseInventory" role="button"
                        aria-expanded="{{ $isInventoryActive ? 'true' : 'false' }}" aria-controls="collapseInventory">
                        <i class="bi bi-boxes"></i>
                        <span>Inventory Stok</span>
                        @if ($lowStockCount > 0)
                            <span class="badge bg-danger rounded-pill ms-2"
                                style="font-size: 0.7rem; padding: 2px 6px;">{{ $lowStockCount }}</span>
                        @endif
                        <i class="bi bi-chevron-down ms-auto chevron" style="font-size: 0.8rem;"></i>
                    </div>
                    <div class="collapse {{ $isInventoryActive ? 'show' : '' }}" id="collapseInventory">
                        <div class="submenu-container">
                            <a href="{{ route('inventory.index') }}"
                                class="{{ request()->routeIs('inventory.index') || request()->routeIs('inventory.ledger') ? 'active' : '' }}">Stok
                                Gudang</a>
                            <a href="{{ route('stock_opnames.index') }}"
                                class="{{ request()->routeIs('stock_opnames.*') ? 'active' : '' }}">Opname Stok</a>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- LAPORAN -->
        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('view-warehouse-reports'))
            <div class="section-title">Laporan</div>

            <div>
                <div class="dropdown-trigger {{ $isReportActive ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    data-bs-target="#collapseReports" role="button"
                    aria-expanded="{{ $isReportActive ? 'true' : 'false' }}" aria-controls="collapseReports">
                    <i class="bi bi-bar-chart"></i>
                    <span>Laporan Gudang</span>
                    <i class="bi bi-chevron-down ms-auto chevron" style="font-size: 0.8rem;"></i>
                </div>
                <div class="collapse {{ $isReportActive ? 'show' : '' }}" id="collapseReports">
                    <div class="submenu-container">
                        <a href="{{ route('reports.summary') }}"
                            class="{{ request()->routeIs('reports.summary*') ? 'active' : '' }}">Rekap Persediaan</a>
                        <a href="{{ route('reports.stock') }}"
                            class="{{ request()->routeIs('reports.stock*') ? 'active' : '' }}">Stok Barang</a>
                        <a href="{{ route('reports.ledger') }}"
                            class="{{ request()->routeIs('reports.ledger*') ? 'active' : '' }}">Kartu Stok</a>
                        <a href="{{ route('reports.opname') }}"
                            class="{{ request()->routeIs('reports.opname*') ? 'active' : '' }}">Riwayat Opname</a>
                        <a href="{{ route('reports.analytics') }}"
                            class="{{ request()->routeIs('reports.analytics*') ? 'active' : '' }}">Analitik
                            Inventori</a>
                    </div>
                </div>
            </div>
        @endif

        <!-- HRD -->
        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('manage-employees'))
            <div class="section-title">HRD & Kepegawaian</div>

            <div>
                <div class="dropdown-trigger {{ $isHrdActive ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    data-bs-target="#collapseHrd" role="button"
                    aria-expanded="{{ $isHrdActive ? 'true' : 'false' }}" aria-controls="collapseHrd">
                    <i class="bi bi-person-badge"></i>
                    <span>Kepegawaian (HRD)</span>
                    <i class="bi bi-chevron-down ms-auto chevron" style="font-size: 0.8rem;"></i>
                </div>
                <div class="collapse {{ $isHrdActive ? 'show' : '' }}" id="collapseHrd">
                    <div class="submenu-container">
                        @can('manage-employees')
                            <a href="{{ route('employees.index') }}"
                                class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">Daftar Karyawan</a>
                        @endcan
                        @if (auth()->user()->role === 'admin' ||
                                auth()->user()->hasAnyPermission(['view-attendance', 'manage-employees']))
                            <a href="{{ route('hr.attendance.index') }}"
                                class="{{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">Presensi /
                                Absensi</a>
                        @endif
                        @can('manage-employees')
                            <a href="{{ route('hr.leaves.index') }}"
                                class="{{ request()->routeIs('hr.leaves.*') ? 'active' : '' }}">Pengajuan Izin & Cuti</a>
                            <a href="{{ route('hr.overtime.index') }}"
                                class="{{ request()->routeIs('hr.overtime.*') ? 'active' : '' }}">Lembur / Overtime</a>
                            <a href="{{ route('hr.cash-advances.index') }}"
                                class="{{ request()->routeIs('hr.cash-advances.*') ? 'active' : '' }}">Kasbon (Cash
                                Advance)</a>
                            <a href="{{ route('hr.payroll.index') }}"
                                class="{{ request()->routeIs('hr.payroll.*') ? 'active' : '' }}">Gaji / Payroll</a>
                            <a href="{{ route('hr.allowance-types.index') }}"
                                class="{{ request()->routeIs('hr.allowance-types.*') ? 'active' : '' }}">Master
                                Tunjangan</a>
                            <a href="{{ route('hr.late-penalties.index') }}"
                                class="{{ request()->routeIs('hr.late-penalties.*') ? 'active' : '' }}">Aturan
                                Terlambat</a>
                            <a href="{{ route('hr.holidays.index') }}"
                                class="{{ request()->routeIs('hr.holidays.*') ? 'active' : '' }}">Hari Libur</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- KEUANGAN -->
        @if (auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['view-financial-reports', 'manage-finance']))
            <div class="section-title">Keuangan</div>

            <div>
                <div class="dropdown-trigger {{ $isFinanceActive ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                    data-bs-target="#collapseFinance" role="button"
                    aria-expanded="{{ $isFinanceActive ? 'true' : 'false' }}" aria-controls="collapseFinance">
                    <i class="bi bi-cash-stack"></i>
                    <span>Keuangan</span>
                    <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                </div>
                <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="collapseFinance">
                    <div class="submenu-container">
                        @can('view-financial-reports')
                            <a href="{{ route('finance.profit_loss') }}"
                                class="{{ request()->routeIs('finance.profit_loss') ? 'active' : '' }}">Laba Rugi</a>
                            <a href="{{ route('profit.index') }}"
                                class="{{ request()->routeIs('profit.*') ? 'active' : '' }}">Profit Pesanan</a>
                        @endcan
                        @can('manage-finance')
                            <a href="{{ route('finance.reconciliation') }}"
                                class="{{ request()->routeIs('finance.reconciliation') ? 'active' : '' }}">Rekonsiliasi</a>
                            <a href="{{ route('finance.incomes.index') }}"
                                class="{{ request()->routeIs('finance.incomes.*') ? 'active' : '' }}">Pemasukan Lain</a>
                            <a href="{{ route('finance.expenses.index') }}"
                                class="{{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}">Pengeluaran &
                                Biaya</a>
                            <a href="{{ route('finance.transfers.index') }}"
                                class="{{ request()->routeIs('finance.transfers.*') ? 'active' : '' }}">Transfer Dana</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- INTEGRASI -->
        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermissionTo('manage-stores'))
            <div class="section-title">Integrasi</div>
            @can('manage-stores')
                <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'active' : '' }}">
                    <i class="bi bi-plug"></i>
                    <span>Kelola Toko</span>
                </a>
            @endcan
        @endif

        <!-- BANTUAN -->
        <div class="section-title">Bantuan</div>
        <a href="{{ route('faq.index') }}" class="{{ request()->routeIs('faq.*') ? 'active' : '' }}">
            <i class="bi bi-question-circle"></i>
            <span>Panduan & FAQ</span>
        </a>

    </div>

    <!-- Footer/Logout -->
    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-logout-custom">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>

</div>
