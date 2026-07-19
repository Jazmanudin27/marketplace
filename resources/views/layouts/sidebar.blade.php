@php
    $isMasterDataActive =
        (request()->routeIs('inventory_items.*') && !request()->has('type')) ||
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
        request()->routeIs('labor_services.*');

    $isPembelianActive =
        (request()->routeIs('purchase_orders.*') && !request()->routeIs('purchase_orders.report')) ||
        request()->routeIs('purchase_returns.*') ||
        request()->routeIs('goods_receipts.*') ||
        request()->routeIs('incoming_goods.*') ||
        request()->routeIs('pembelian.goods_issue.*');

    $isProduksiActive =
        request()->routeIs('spks.*') ||
        request()->routeIs('product_recipes.*');

    $isGudangJadiActive =
        request()->routeIs('inventory.index') ||
        request()->routeIs('inventory.ledger') ||
        request()->routeIs('stock_opnames.*') ||
        request()->routeIs('inventory.stock_sync') ||
        request()->routeIs('fulfillment.*') ||
        request()->routeIs('complaints.*');

    $isFinanceActive =
        request()->routeIs('finance.reconciliation') ||
        request()->routeIs('finance.incomes.*') ||
        request()->routeIs('finance.expenses.*') ||
        request()->routeIs('finance.transfers.*');

    $isMarketingActive =
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
        request()->routeIs('purchase_orders.report') ||
        request()->routeIs('pembelian.stock_report') ||
        request()->routeIs('pembelian.report_mutation') ||
        request()->routeIs('pembelian.report_summary') ||
        request()->routeIs('pembelian.stock_card') ||
        request()->routeIs('finance.profit_loss') ||
        request()->routeIs('profit.*');
@endphp

<div class="d-flex flex-column p-3 bg-primary text-white w-100" id="sidebar">

    <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
        <div class="bg-white text-primary rounded p-2 d-flex align-items-center justify-content-center shadow-sm"
            style="width: 38px; height: 38px;">
            <i class="bi bi-rocket-takeoff-fill fs-5"></i>
        </div>
        <div class="lh-sm">
            <h6 class="mb-0 fw-bold text-white">ASPARTECH</h6>
            <small class="text-white text-opacity-75 text-uppercase fw-bold">ERP Marketplace</small>
        </div>
    </div>

    <!-- Tenant Card -->
    @if (Auth::user()->isSuperAdmin())
        @php
            $tenants = \App\Models\Tenant::orderBy('name')->get();
        @endphp
        <div class="card border border-white border-opacity-10 p-2 mb-3 bg-white bg-opacity-10 text-white">
            <div class="small fw-bold text-white text-opacity-75 mb-1 text-uppercase" style="font-size: 0.75rem;">Pilih
                Perusahaan</div>
            <form action="{{ route('switch-tenant') }}" method="POST" id="switch-tenant-form">
                @csrf
                <select name="tenant_id" class="form-select form-select-sm text-truncate fw-semibold"
                    onchange="document.getElementById('switch-tenant-form').submit()">
                    @foreach ($tenants as $t)
                        <option value="{{ $t->id }}" {{ Auth::user()->tenant_id == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            <div class="lh-sm mt-1 small">
                <small class="text-white text-opacity-75 d-block">
                    Role:
                    {{ Auth::user()->roles->first() ? ucfirst(Auth::user()->roles->first()->name) : ucfirst(Auth::user()->role) }}
                    (Super Admin)
                </small>
            </div>
        </div>
    @else
        <div class="card border border-white border-opacity-10 p-2 mb-3 bg-white bg-opacity-10 text-white">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white text-primary rounded p-2 d-flex align-items-center justify-content-center fw-bold"
                    style="width: 32px; height: 32px;">
                    {{ strtoupper(substr(Auth::user()->tenant->name, 0, 1)) }}
                </div>
                <div class="lh-sm overflow-hidden small">
                    <span class="d-block fw-semibold text-white text-truncate"
                        title="{{ Auth::user()->tenant->name }}">{{ Auth::user()->tenant->name }}</span>
                    <small class="text-white text-opacity-75 d-block">
                        {{ Auth::user()->roles->first() ? ucfirst(Auth::user()->roles->first()->name) : ucfirst(Auth::user()->role) }}
                    </small>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Menu -->
    <div class="nav flex-column nav-pills gap-1 small">

        <!-- UTAMA -->
        <div class="text-uppercase text-muted fw-bold mb-1 mt-2 small">Utama</div>

        <!-- 1. Dashboard -->
        <a href="{{ route('dashboard') }}"
            class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active text-white' : 'text-dark' }}">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. Kelola Toko -->
        @can('manage-stores')
            <a href="{{ route('stores.index') }}"
                class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stores.*') ? 'active text-white' : 'text-dark' }}">
                <i class="bi bi-shop"></i>
                <span>Kelola Toko</span>
            </a>
        @endcan

        <!-- 3. Master Produk -->
        @can('products.index')
            <a href="{{ route('products.index') }}"
                class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('products.*') ? 'active text-white' : 'text-dark' }}">
                <i class="bi bi-box-seam"></i>
                <span>Master Produk</span>
            </a>
        @endcan

        <!-- 4. Marketplace Produk -->
        @can('marketplace-products.index')
            <a href="{{ route('marketplace_products.index') }}"
                class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('marketplace_products.*') ? 'active text-white' : 'text-dark' }}">
                <i class="bi bi-cloud-upload"></i>
                <span>Marketplace Produk</span>
            </a>
        @endcan

        <div class="text-uppercase text-muted fw-bold mb-1 mt-3 small">Modul ERP</div>

        <!-- 5. DATA MASTER -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'categories.index',
                        'brands.index',
                        'suppliers.index',
                        'customers.index',
                        'employees.index',
                        'users.index',
                        'settings.tenant.edit',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isMasterDataActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseMasterData" role="button"
                    aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}" aria-controls="collapseMasterData">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-database"></i>
                        <span>Data Master</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isMasterDataActive ? 'show' : '' }}" id="collapseMasterData">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @if (auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin')
                            <a href="{{ route('departments.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('departments.*') ? 'active text-white' : 'text-secondary' }}">Departemen</a>
                            <a href="{{ route('inventory_items.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('inventory_items.*') && !request()->has('type') ? 'active text-white' : 'text-secondary' }}">Master
                                Barang</a>
                        @endif
                        @can('categories.index')
                            <a href="{{ route('categories.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('categories.*') ? 'active text-white' : 'text-secondary' }}">Kategori</a>
                        @endcan
                        @can('brands.index')
                            <a href="{{ route('brands.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('brands.*') ? 'active text-white' : 'text-secondary' }}">Merk</a>
                        @endcan
                        @can('suppliers.index')
                            <a href="{{ route('suppliers.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('suppliers.*') ? 'active text-white' : 'text-secondary' }}">Supplier</a>
                        @endcan
                        @can('customers.index')
                            <a href="{{ route('customers.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('customers.*') ? 'active text-white' : 'text-secondary' }}">Pelanggan</a>
                        @endcan
                        @can('employees.index')
                            <a href="{{ route('employees.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('employees.*') ? 'active text-white' : 'text-secondary' }}">Karyawan</a>
                            <a href="{{ route('tailors.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('tailors.*') ? 'active text-white' : 'text-secondary' }}">Tukang Jahit</a>
                            <a href="{{ route('labor_services.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('labor_services.*') ? 'active text-white' : 'text-secondary' }}">Jasa Produksi</a>
                            <a href="{{ route('production-statuses.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('production-statuses.*') ? 'active text-white' : 'text-secondary' }}">Status Produksi</a>
                        @endcan
                        @can('users.index')
                            <a href="{{ route('users.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('users.*') ? 'active text-white' : 'text-secondary' }}">Pengguna</a>
                            <a href="{{ route('roles.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('roles.*') ? 'active text-white' : 'text-secondary' }}">Hak
                                Akses</a>
                        @endcan
                        @can('settings.tenant.edit')
                            <a href="{{ route('settings.tenant.edit') }}"
                                class="nav-link py-1 {{ request()->routeIs('settings.tenant.*') ? 'active text-white' : 'text-secondary' }}">Perusahaan</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- 6. KEUANGAN -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'finance.reconciliation.index',
                        'finance.incomes.index',
                        'finance.expenses.index',
                        'finance.transfers.index',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isFinanceActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseFinance" role="button"
                    aria-expanded="{{ $isFinanceActive ? 'true' : 'false' }}" aria-controls="collapseFinance">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack"></i>
                        <span>Keuangan</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="collapseFinance">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('finance.reconciliation.index')
                            <a href="{{ route('finance.reconciliation') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.reconciliation') ? 'active text-white' : 'text-secondary' }}">Rekonsiliasi</a>
                        @endcan
                        @can('finance.incomes.index')
                            <a href="{{ route('finance.incomes.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.incomes.*') ? 'active text-white' : 'text-secondary' }}">Pemasukan
                                Lain</a>
                        @endcan
                        @can('finance.expenses.index')
                            <a href="{{ route('finance.expenses.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.expenses.*') ? 'active text-white' : 'text-secondary' }}">Pengeluaran
                                & Biaya</a>
                        @endcan
                        @can('finance.transfers.index')
                            <a href="{{ route('finance.transfers.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.transfers.*') ? 'active text-white' : 'text-secondary' }}">Transfer
                                Dana</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- 7. PEMBELIAN -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'purchase-orders.index',
                        'goods-receipts.index',
                        'goods-issues.index',
                        'purchase-returns.index',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isPembelianActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapsePembelian" role="button"
                    aria-expanded="{{ $isPembelianActive ? 'true' : 'false' }}" aria-controls="collapsePembelian">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cart"></i>
                        <span>Pembelian</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isPembelianActive ? 'show' : '' }}" id="collapsePembelian">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('purchase-orders.index')
                            <a href="{{ route('purchase_orders.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('purchase_orders.*') && !request()->routeIs('purchase_orders.report') ? 'active text-white' : 'text-secondary' }}">Purchase
                                Order</a>
                        @endcan
                        @can('goods-receipts.index')
                            <a href="{{ route('goods_receipts.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('goods_receipts.*') ? 'active text-white' : 'text-secondary' }}">Penerimaan
                                Barang</a>
                        @endcan
                        @can('goods-issues.index')
                            <a href="{{ route('pembelian.goods_issue.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('pembelian.goods_issue.*') ? 'active text-white' : 'text-secondary' }}">Pengeluaran
                                Barang</a>
                        @endcan
                        @can('purchase-returns.index')
                            <a href="{{ route('purchase_returns.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('purchase_returns.*') ? 'active text-white' : 'text-secondary' }}">Retur
                                Pembelian</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- 8. PRODUKSI -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['spks.index', 'product-recipes.index']))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isProduksiActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseProduksi" role="button"
                    aria-expanded="{{ $isProduksiActive ? 'true' : 'false' }}" aria-controls="collapseProduksi">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-hammer"></i>
                        <span>Produksi</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isProduksiActive ? 'show' : '' }}" id="collapseProduksi">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('spks.index')
                            <a href="{{ route('spks.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('spks.*') ? 'active text-white' : 'text-secondary' }}">Surat Perintah Kerja (SPK)</a>
                        @endcan
                        @can('product-recipes.index')
                            <a href="{{ route('product_recipes.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('product_recipes.*') ? 'active text-white' : 'text-secondary' }}">Formula Produk (BOM)</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- 9. GUDANG JADI -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'inventory.index',
                        'stock-opnames.index',
                        'inventory.stock_sync',
                        'fulfillment.index',
                        'complaints.index',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isGudangJadiActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseGudangJadi" role="button"
                    aria-expanded="{{ $isGudangJadiActive ? 'true' : 'false' }}" aria-controls="collapseGudangJadi">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-boxes"></i>
                        <span>Gudang Jadi</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isGudangJadiActive ? 'show' : '' }}" id="collapseGudangJadi">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('inventory.index')
                            <a href="{{ route('inventory.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.ledger') ? 'active text-white' : 'text-secondary' }}">Stok
                                Gudang Jadi</a>
                        @endcan
                        @can('stock-opnames.index')
                            <a href="{{ route('stock_opnames.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('stock_opnames.*') ? 'active text-white' : 'text-secondary' }}">Opname
                                Stok Jadi</a>
                        @endcan
                        @can('inventory.stock_sync')
                            <a href="{{ route('inventory.stock_sync') }}"
                                class="nav-link py-1 {{ request()->routeIs('inventory.stock_sync') ? 'active text-white' : 'text-secondary' }}">Sinkronisasi
                                Stok</a>
                        @endcan
                        @can('fulfillment.index')
                            <a href="{{ route('fulfillment.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('fulfillment.*') ? 'active text-white' : 'text-secondary' }}">Kemas
                                Pesanan (Scan)</a>
                        @endcan
                        @can('complaints.index')
                            <a href="{{ route('complaints.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('complaints.*') ? 'active text-white' : 'text-secondary' }}">Pengaduan
                                Barang</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- 10. MARKETING -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'orders.index',
                        'orders.create',
                        'offline-sales.index',
                        'returns.index',
                        'chats.index',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isMarketingActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseMarketing" role="button"
                    aria-expanded="{{ $isMarketingActive ? 'true' : 'false' }}" aria-controls="collapseMarketing">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone"></i>
                        <span>Marketing</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isMarketingActive ? 'show' : '' }}" id="collapseMarketing">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('orders.index')
                            <a href="{{ route('orders.index') }}"
                                class="nav-link py-1 d-flex align-items-center justify-content-between pe-3 {{ (request()->routeIs('orders.*') && !request()->routeIs('orders.create')) ? 'active text-white' : 'text-secondary' }}">
                                <span>Pesanan Masuk</span>
                                @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                                    <span class="badge bg-danger rounded-pill small">{{ $pendingOrdersCount }}</span>
                                @endif
                            </a>
                        @endcan
                        @can('orders.create')
                            <a href="{{ route('orders.create') }}"
                                class="nav-link py-1 {{ request()->routeIs('orders.create') ? 'active text-white' : 'text-secondary' }}">
                                <span>Input Pesanan Manual</span>
                            </a>
                        @endcan
                        @can('offline-sales.index')
                            <a href="{{ route('offline_sales.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('offline_sales.*') ? 'active text-white' : 'text-secondary' }}">Penjualan
                                Offline</a>
                        @endcan
                        @can('returns.index')
                            <a href="{{ route('returns.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('returns.*') ? 'active text-white' : 'text-secondary' }}">Pesanan
                                Retur</a>
                        @endcan
                        @can('chats.index')
                            <a href="{{ route('chats.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('chats.*') ? 'active text-white' : 'text-secondary' }}">Inbox
                                Chat</a>
                        @endcan

                        @if (auth()->user()->isSuperAdmin() ||
                                auth()->user()->role === 'admin' ||
                                auth()->user()->hasAnyPermission(['view-financial-reports', 'manage-finance']))
                            <a href="{{ route('marketing.ads.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('marketing.ads.*') ? 'active text-white' : 'text-secondary' }}">Dashboard
                                Keputusan</a>
                            <a href="{{ route('marketing.flash_sales.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('marketing.flash_sales.*') ? 'active text-white' : 'text-secondary' }}">Flash
                                Sale</a>
                            <a href="{{ route('marketing.tiered_discounts.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('marketing.tiered_discounts.*') ? 'active text-white' : 'text-secondary' }}">Diskon
                                Bertingkat</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- 11. HRD (Kepegawaian) -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasPermissionTo('manage-employees'))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isHrdActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseHrd" role="button"
                    aria-expanded="{{ $isHrdActive ? 'true' : 'false' }}" aria-controls="collapseHrd">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge"></i>
                        <span>Kepegawaian (HRD)</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isHrdActive ? 'show' : '' }}" id="collapseHrd">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('manage-employees')
                            <a href="{{ route('employees.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('employees.*') ? 'active text-white' : 'text-secondary' }}">Daftar
                                Karyawan</a>
                        @endcan
                        @if (auth()->user()->isSuperAdmin() ||
                                auth()->user()->role === 'admin' ||
                                auth()->user()->hasAnyPermission(['view-attendance', 'manage-employees']))
                            <a href="{{ route('hr.attendance.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.attendance.*') ? 'active text-white' : 'text-secondary' }}">Presensi
                                / Absensi</a>
                        @endif
                        @can('manage-employees')
                            <a href="{{ route('hr.leaves.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.leaves.*') ? 'active text-white' : 'text-secondary' }}">Pengajuan
                                Izin & Cuti</a>
                            <a href="{{ route('hr.overtime.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.overtime.*') ? 'active text-white' : 'text-secondary' }}">Lembur
                                / Overtime</a>
                            <a href="{{ route('hr.cash-advances.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.cash-advances.*') ? 'active text-white' : 'text-secondary' }}">Kasbon
                                (Cash Advance)</a>
                            <a href="{{ route('hr.payroll.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.payroll.*') ? 'active text-white' : 'text-secondary' }}">Gaji
                                / Payroll</a>
                            <a href="{{ route('hr.allowance-types.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.allowance-types.*') ? 'active text-white' : 'text-secondary' }}">Master
                                Tunjangan</a>
                            <a href="{{ route('hr.late-penalties.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.late-penalties.*') ? 'active text-white' : 'text-secondary' }}">Aturan
                                Terlambat</a>
                            <a href="{{ route('hr.holidays.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('hr.holidays.*') ? 'active text-white' : 'text-secondary' }}">Hari
                                Libur</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif


        <!-- ========================================================================= -->
        <!-- DEDICATED LAPORAN ACCORDION -->
        <!-- ========================================================================= -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'view-financial-reports',
                        'view-warehouse-reports',
                        'manage-incoming-goods',
                        'manage-inventory',
                        'manage-products',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isLaporanActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseLaporan" role="button"
                    aria-expanded="{{ $isLaporanActive ? 'true' : 'false' }}" aria-controls="collapseLaporan">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Laporan / Analitik</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isLaporanActive ? 'show' : '' }}" id="collapseLaporan">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        
                        <!-- Laporan Keuangan -->
                        @can('view-financial-reports')
                            <div class="text-uppercase text-white text-opacity-50 fw-bold ms-2 mt-2 mb-1"
                                style="font-size: 0.65rem;">Laporan Keuangan</div>
                            <a href="{{ route('finance.profit_loss') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.profit_loss') ? 'active text-white' : 'text-secondary' }}">Laba Rugi</a>
                            <a href="{{ route('profit.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('profit.index') ? 'active text-white' : 'text-secondary' }}">Profit Pesanan</a>
                            <a href="{{ route('profit.margin') }}"
                                class="nav-link py-1 {{ request()->routeIs('profit.margin') ? 'active text-white' : 'text-secondary' }}">Margin Produk Aktual</a>
                            <a href="{{ route('reports.product_margins') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.product_margins') ? 'active text-white' : 'text-secondary' }}">Margin Master Produk</a>
                            <a href="{{ route('reports.store_sales') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.store_sales') ? 'active text-white' : 'text-secondary' }}">Laporan Toko & Salur</a>
                            <a href="{{ route('reports.reseller_receivables') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.reseller_receivables') ? 'active text-white' : 'text-secondary' }}">Saldo & Piutang</a>
                            <a href="{{ route('reports.inventory_turnover') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.inventory_turnover') ? 'active text-white' : 'text-secondary' }}">Perputaran Stok</a>
                        @endcan

                        <!-- Laporan Gudang Jadi -->
                        @can('view-warehouse-reports')
                            <div class="text-uppercase text-white text-opacity-50 fw-bold ms-2 mt-2 mb-1"
                                style="font-size: 0.65rem;">Laporan Gudang Jadi</div>
                            <a href="{{ route('reports.summary') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.summary*') ? 'active text-white' : 'text-secondary' }}">Rekap Persediaan</a>
                            <a href="{{ route('reports.stock') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.stock*') ? 'active text-white' : 'text-secondary' }}">Stok Barang Jadi</a>
                            <a href="{{ route('reports.ledger') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.ledger*') ? 'active text-white' : 'text-secondary' }}">Kartu Stok Jadi</a>
                            <a href="{{ route('reports.opname') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.opname*') ? 'active text-white' : 'text-secondary' }}">Riwayat Opname</a>
                            <a href="{{ route('reports.analytics') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.analytics*') ? 'active text-white' : 'text-secondary' }}">Analitik Inventori</a>
                        @endcan

                        <!-- Laporan Produksi -->
                        @can('view-warehouse-reports')
                            <div class="text-uppercase text-white text-opacity-50 fw-bold ms-2 mt-2 mb-1"
                                style="font-size: 0.65rem;">Laporan Produksi</div>
                            <a href="{{ route('reports.production_hpp') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.production_hpp*') ? 'active text-white' : 'text-secondary' }}">HPP Produksi</a>
                        @endcan

                        <!-- Laporan Pembelian & Stok Bahan -->
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['manage-incoming-goods', 'manage-inventory']))
                            <div class="text-uppercase text-white text-opacity-50 fw-bold ms-2 mt-2 mb-1"
                                style="font-size: 0.65rem;">Laporan Bahan & Pembelian</div>
                            @can('manage-incoming-goods')
                                <a href="{{ route('purchase_orders.report') }}"
                                    class="nav-link py-1 {{ request()->routeIs('purchase_orders.report') ? 'active text-white' : 'text-secondary' }}">Laporan Pembelian</a>
                            @endcan
                            @can('manage-inventory')
                                <a href="{{ route('pembelian.stock_report') }}"
                                    class="nav-link py-1 {{ request()->routeIs('pembelian.stock_report') || request()->routeIs('pembelian.print_stock_report') ? 'active text-white' : 'text-secondary' }}">Laporan Stok Bahan</a>
                                <a href="{{ route('pembelian.report_mutation') }}"
                                    class="nav-link py-1 {{ request()->routeIs('pembelian.report_mutation') || request()->routeIs('pembelian.print_report_mutation') ? 'active text-white' : 'text-secondary' }}">Laporan Mutasi Bahan</a>
                                <a href="{{ route('pembelian.report_summary') }}"
                                    class="nav-link py-1 {{ request()->routeIs('pembelian.report_summary') || request()->routeIs('pembelian.print_report_summary') ? 'active text-white' : 'text-secondary' }}">Rekap Persediaan Bahan</a>
                                <a href="{{ route('pembelian.stock_card') }}"
                                    class="nav-link py-1 {{ request()->routeIs('pembelian.stock_card') || request()->routeIs('pembelian.print_stock_card') ? 'active text-white' : 'text-secondary' }}">Kartu Stok Bahan</a>
                            @endcan
                        @endif

                        <!-- Laporan Master Produk -->
                        @can('manage-products')
                            <div class="text-uppercase text-white text-opacity-50 fw-bold ms-2 mt-2 mb-1"
                                style="font-size: 0.65rem;">Laporan Produk</div>
                            <a href="{{ route('reports.master_product') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.master_product*') ? 'active text-white' : 'text-secondary' }}">Laporan Master Produk</a>
                        @endcan

                    </div>
                </div>
            </div>
        @endif


        <!-- BANTUAN -->
        <div class="text-uppercase text-muted fw-bold mb-1 mt-3 small">Bantuan</div>
        <a href="{{ route('faq.index') }}"
            class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('faq.*') ? 'active text-white' : 'text-dark' }}">
            <i class="bi bi-question-circle"></i>
            <span>Panduan & FAQ</span>
        </a>

    </div>

    <!-- Footer/Logout -->
    <div class="mt-4 pt-3 border-top w-100">
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit"
                class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 py-2 rounded-3">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>

</div>
