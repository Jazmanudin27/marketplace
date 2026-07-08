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
        request()->routeIs('products.*') ||
        request()->routeIs('marketplace_products.*');

    $isPembelianActive =
        request()->routeIs('purchase_orders.*') ||
        request()->routeIs('purchase_returns.*') ||
        request()->routeIs('goods_receipts.*') ||
        request()->routeIs('incoming_goods.*');

    $isGudangJadiActive =
        request()->routeIs('inventory.index') ||
        request()->routeIs('inventory.ledger') ||
        request()->routeIs('stock_opnames.*') ||
        request()->routeIs('inventory.stock_sync') ||
        request()->routeIs('fulfillment.*') ||
        request()->routeIs('complaints.*') ||
        request()->routeIs('reports.summary') ||
        request()->routeIs('reports.stock') ||
        request()->routeIs('reports.ledger') ||
        request()->routeIs('reports.opname') ||
        request()->routeIs('reports.analytics');

    $isFinanceActive =
        request()->routeIs('finance.profit_loss') ||
        request()->routeIs('finance.incomes.*') ||
        request()->routeIs('finance.expenses.*') ||
        request()->routeIs('finance.transfers.*') ||
        request()->routeIs('finance.reconciliation') ||
        request()->routeIs('profit.*') ||
        request()->routeIs('reports.store_sales') ||
        request()->routeIs('reports.reseller_receivables') ||
        request()->routeIs('reports.inventory_turnover');

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
    $isGudangBahanActive =
        (request()->routeIs('inventory_items.*') && in_array(request('type'), ['bahan_kemasan', 'bahan', 'kemasan'])) ||
        request()->routeIs('warehouse_mutations.*');
    $isGudangAtkActive =
        (request()->routeIs('inventory_items.*') &&
            in_array(request('type'), ['atk_inventaris', 'atk', 'inventaris'])) ||
        request()->routeIs('ga_mutations.*');

    $isProduksiActive =
        request()->routeIs('produksi_mutations.*') ||
        request()->routeIs('production_orders.*') ||
        request()->routeIs('mobile.produksi');
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

        <a href="{{ route('dashboard') }}"
            class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active text-white' : 'text-dark' }}">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>

        <!-- MASTER DATA -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'manage-categories',
                        'manage-brands',
                        'manage-suppliers',
                        'manage-employees',
                        'manage-customers',
                        'manage-users',
                        'manage-products',
                        'settings.tenant.edit',
                    ]))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isMasterDataActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseMasterData" role="button"
                    aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}" aria-controls="collapseMasterData">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-database"></i>
                        <span>Master Data</span>
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
                        @can('manage-categories')
                            <a href="{{ route('categories.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('categories.*') ? 'active text-white' : 'text-secondary' }}">Kategori</a>
                        @endcan
                        @can('manage-brands')
                            <a href="{{ route('brands.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('brands.*') ? 'active text-white' : 'text-secondary' }}">Merk</a>
                        @endcan
                        @can('manage-suppliers')
                            <a href="{{ route('suppliers.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('suppliers.*') ? 'active text-white' : 'text-secondary' }}">Supplier</a>
                        @endcan
                        @can('manage-customers')
                            <a href="{{ route('customers.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('customers.*') ? 'active text-white' : 'text-secondary' }}">Pelanggan</a>
                        @endcan
                        @can('manage-employees')
                            <a href="{{ route('employees.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('employees.*') ? 'active text-white' : 'text-secondary' }}">Karyawan</a>
                        @endcan
                        @can('manage-users')
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
                        @can('manage-products')
                            <a href="{{ route('products.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('products.*') ? 'active text-white' : 'text-secondary' }}">Master
                                Produk</a>
                            <a href="{{ route('marketplace_products.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('marketplace_products.*') ? 'active text-white' : 'text-secondary' }}">Marketplace
                                Produk</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- PEMBELIAN -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['manage-incoming-goods', 'manage-inventory']))
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
                        @can('manage-incoming-goods')
                            <a href="{{ route('purchase_orders.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('purchase_orders.*') && !request()->routeIs('purchase_orders.report') ? 'active text-white' : 'text-secondary' }}">Purchase
                                Order</a>
                            <a href="{{ route('purchase_orders.report') }}"
                                class="nav-link py-1 {{ request()->routeIs('purchase_orders.report') ? 'active text-white' : 'text-secondary' }}">Laporan
                                Pembelian</a>
                        @endcan
                        @can('manage-inventory')
                            <a href="{{ route('goods_receipts.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('goods_receipts.*') ? 'active text-white' : 'text-secondary' }}">Penerimaan
                                Barang</a>
                            <a href="{{ route('purchase_returns.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('purchase_returns.*') ? 'active text-white' : 'text-secondary' }}">Retur
                                Pembelian</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- GUDANG JADI -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'manage-inventory',
                        'manage-fulfillment',
                        'manage-complaints',
                        'view-warehouse-reports',
                    ]))
            @php
                $lowStockCount = \App\Models\MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                    ->whereColumn('stock', '<=', 'min_stock')
                    ->where('is_active', true)
                    ->count();
            @endphp
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isGudangJadiActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseGudangJadi" role="button"
                    aria-expanded="{{ $isGudangJadiActive ? 'true' : 'false' }}" aria-controls="collapseGudangJadi">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-boxes"></i>
                        <span>Gudang Jadi</span>
                        @if ($lowStockCount > 0)
                            <span class="badge bg-danger rounded-pill ms-1 small">{{ $lowStockCount }}</span>
                        @endif
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isGudangJadiActive ? 'show' : '' }}" id="collapseGudangJadi">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        @can('manage-inventory')
                            <a href="{{ route('inventory.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.ledger') ? 'active text-white' : 'text-secondary' }}">Stok
                                Gudang Jadi</a>
                            <a href="{{ route('stock_opnames.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('stock_opnames.*') ? 'active text-white' : 'text-secondary' }}">Opname
                                Stok Jadi</a>
                            <a href="{{ route('inventory.stock_sync') }}"
                                class="nav-link py-1 {{ request()->routeIs('inventory.stock_sync') ? 'active text-white' : 'text-secondary' }}">Sinkronisasi
                                Stok</a>
                        @endcan
                        @can('manage-fulfillment')
                            <a href="{{ route('fulfillment.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('fulfillment.*') ? 'active text-white' : 'text-secondary' }}">Kemas
                                Pesanan (Scan)</a>
                        @endcan
                        @can('manage-complaints')
                            <a href="{{ route('complaints.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('complaints.*') ? 'active text-white' : 'text-secondary' }}">Pengaduan
                                Barang</a>
                        @endcan

                        @can('view-warehouse-reports')
                            <div class="text-uppercase text-white text-opacity-50 fw-bold ms-2 mt-2 mb-1"
                                style="font-size: 0.65rem;">Laporan Gudang Jadi</div>
                            <a href="{{ route('reports.summary') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.summary*') ? 'active text-white' : 'text-secondary' }}">Rekap
                                Persediaan</a>
                            <a href="{{ route('reports.stock') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.stock*') ? 'active text-white' : 'text-secondary' }}">Stok
                                Barang</a>
                            <a href="{{ route('reports.ledger') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.ledger*') ? 'active text-white' : 'text-secondary' }}">Kartu
                                Stok</a>
                            <a href="{{ route('reports.opname') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.opname*') ? 'active text-white' : 'text-secondary' }}">Riwayat
                                Opname</a>
                            <a href="{{ route('reports.analytics') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.analytics*') ? 'active text-white' : 'text-secondary' }}">Analitik
                                Inventori</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- GUDANG BAHAN & KEMASAN -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['manage-inventory']))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isGudangBahanActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseGudangBahan" role="button"
                    aria-expanded="{{ $isGudangBahanActive ? 'true' : 'false' }}"
                    aria-controls="collapseGudangBahan">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-recycle"></i>
                        <span>Gudang Bahan & Kemasan</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isGudangBahanActive ? 'show' : '' }}" id="collapseGudangBahan">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        <a href="{{ route('warehouse_mutations.index_in') }}"
                            class="nav-link py-1 {{ request()->routeIs('warehouse_mutations.index_in') ? 'active text-white' : 'text-secondary' }}">
                            <i class="fas fa-sign-in-alt me-1"></i> Barang Masuk (WMI)
                        </a>
                        <a href="{{ route('warehouse_mutations.index_out') }}"
                            class="nav-link py-1 {{ request()->routeIs('warehouse_mutations.index_out') ? 'active text-white' : 'text-secondary' }}">
                            <i class="fas fa-sign-out-alt me-1"></i> Barang Keluar (WMO)
                        </a>
                        <a href="{{ route('warehouse_mutations.report_mutation') }}"
                            class="nav-link py-1 {{ request()->routeIs('warehouse_mutations.report_mutation') ? 'active text-white' : 'text-secondary' }}">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Laporan Mutasi
                        </a>
                        <a href="{{ route('warehouse_mutations.report_summary') }}"
                            class="nav-link py-1 {{ request()->routeIs('warehouse_mutations.report_summary') ? 'active text-white' : 'text-secondary' }}">
                            <i class="fas fa-boxes me-1"></i> Rekap Persediaan
                        </a>
                        <a href="{{ route('warehouse_mutations.stock_report') }}"
                            class="nav-link py-1 {{ request()->routeIs('warehouse_mutations.stock_report') ? 'active text-white' : 'text-secondary' }}">
                            <i class="fas fa-boxes me-1"></i> Laporan Stok Gudang
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- GUDANG LOGISTIK -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['manage-inventory']))
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isGudangAtkActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseGudangAtk" role="button"
                    aria-expanded="{{ $isGudangAtkActive ? 'true' : 'false' }}" aria-controls="collapseGudangAtk">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-building"></i>
                        <span>Gudang Logistik</span>
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isGudangAtkActive ? 'show' : '' }}" id="collapseGudangAtk">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        <a href="{{ route('ga_mutations.index_in') }}"
                            class="nav-link py-1 {{ request()->routeIs('ga_mutations.index_in') ? 'active text-white' : 'text-secondary' }}">Barang
                            Masuk</a>
                        <a href="{{ route('ga_mutations.index_out') }}"
                            class="nav-link py-1 {{ request()->routeIs('ga_mutations.index_out') ? 'active text-white' : 'text-secondary' }}">Barang
                            Keluar</a>
                        <a href="{{ route('ga_mutations.report_mutation') }}"
                            class="nav-link py-1 {{ request()->routeIs('ga_mutations.report_mutation') ? 'active text-white' : 'text-secondary' }}">Laporan
                            Mutasi</a>
                        <a href="{{ route('ga_mutations.report_summary') }}"
                            class="nav-link py-1 {{ request()->routeIs('ga_mutations.report_summary') ? 'active text-white' : 'text-secondary' }}">Rekap
                            Persediaan</a>
                        <a href="{{ route('ga_mutations.stock_report') }}"
                            class="nav-link py-1 {{ request()->routeIs('ga_mutations.stock_report') ? 'active text-white' : 'text-secondary' }}">Laporan
                            Stok</a>
                    </div>
                </div>
            </div>
        @endif

        <!-- PRODUKSI -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['manage-inventory', 'production-orders.index']))
            @php
                $produksiDept = \App\Models\Department::where('tenant_id', Auth::user()->tenant_id)
                    ->where(function($q) {
                        $q->where('name', 'like', '%produksi%')
                          ->orWhere('code', 'like', '%produksi%');
                    })
                    ->first();
                $pendingProduksiCount = 0;
                if ($produksiDept) {
                    $pendingProduksiCount = \App\Models\WarehouseMutation::where('tenant_id', Auth::user()->tenant_id)
                        ->where('type', 'out')
                        ->where('to_department_id', $produksiDept->id)
                        ->where('status', 'pending')
                        ->count();
                }
            @endphp
            <div>
                <a class="nav-link d-flex align-items-center justify-content-between text-dark {{ $isProduksiActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#collapseProduksi" role="button"
                    aria-expanded="{{ $isProduksiActive ? 'true' : 'false' }}" aria-controls="collapseProduksi">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-tools"></i>
                        <span>Produksi</span>
                        @if ($pendingProduksiCount > 0)
                            <span class="badge bg-danger rounded-pill ms-1 small">{{ $pendingProduksiCount }}</span>
                        @endif
                    </div>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse {{ $isProduksiActive ? 'show' : '' }}" id="collapseProduksi">
                    <div class="nav flex-column ms-3 mt-1 gap-1 border-start ps-2">
                        <a href="{{ route('production_orders.index') }}"
                            class="nav-link py-1 {{ request()->routeIs('production_orders.index') || request()->routeIs('production_orders.show') ? 'active text-white' : 'text-secondary' }}">Perintah Kerja (SPK)</a>
                        <a href="{{ route('production_orders.requirements') }}"
                            class="nav-link py-1 {{ request()->routeIs('production_orders.requirements') ? 'active text-white' : 'text-secondary' }}">Kebutuhan SPK (PO)</a>
                        <a href="{{ route('produksi_mutations.pending_approvals') }}"
                            class="nav-link py-1 d-flex align-items-center justify-content-between pe-3 {{ request()->routeIs('produksi_mutations.pending_approvals') ? 'active text-white' : 'text-secondary' }}">
                            <span>Penerimaan (Approval)</span>
                            @if ($pendingProduksiCount > 0)
                                <span class="badge bg-danger rounded-pill small">{{ $pendingProduksiCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('produksi_mutations.index_in') }}"
                            class="nav-link py-1 {{ request()->routeIs('produksi_mutations.index_in') ? 'active text-white' : 'text-secondary' }}">Barang Masuk</a>
                        <a href="{{ route('produksi_mutations.index_out') }}"
                            class="nav-link py-1 {{ request()->routeIs('produksi_mutations.index_out') ? 'active text-white' : 'text-secondary' }}">Barang Keluar (Penggunaan)</a>
                        <a href="{{ route('produksi_mutations.report_mutation') }}"
                            class="nav-link py-1 {{ request()->routeIs('produksi_mutations.report_mutation') ? 'active text-white' : 'text-secondary' }}">Laporan Mutasi</a>
                        <a href="{{ route('produksi_mutations.report_summary') }}"
                            class="nav-link py-1 {{ request()->routeIs('produksi_mutations.report_summary') ? 'active text-white' : 'text-secondary' }}">Rekap Persediaan</a>
                        <a href="{{ route('produksi_mutations.stock_report') }}"
                            class="nav-link py-1 {{ request()->routeIs('produksi_mutations.stock_report') ? 'active text-white' : 'text-secondary' }}">Laporan Stok</a>
                    </div>
                </div>
            </div>
        @endif

        <!-- MARKETING -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission([
                        'view-financial-reports',
                        'manage-finance',
                        'manage-chats',
                        'manage-stores',
                        'manage-orders',
                        'manage-returns',
                        'manage-offline-sales',
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
                        @can('manage-orders')
                            <a href="{{ route('orders.index') }}"
                                class="nav-link py-1 d-flex align-items-center justify-content-between pe-3 {{ request()->routeIs('orders.*') ? 'active text-white' : 'text-secondary' }}">
                                <span>Pesanan Masuk</span>
                                @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                                    <span class="badge bg-danger rounded-pill small">{{ $pendingOrdersCount }}</span>
                                @endif
                            </a>
                        @endcan
                        @can('manage-offline-sales')
                            <a href="{{ route('offline_sales.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('offline_sales.*') ? 'active text-white' : 'text-secondary' }}">Penjualan
                                Offline</a>
                        @endcan
                        @can('manage-returns')
                            <a href="{{ route('returns.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('returns.*') ? 'active text-white' : 'text-secondary' }}">Pesanan
                                Retur</a>
                        @endcan
                        @can('manage-chats')
                            <a href="{{ route('chats.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('chats.*') ? 'active text-white' : 'text-secondary' }}">Inbox
                                Chat</a>
                        @endcan
                        @can('manage-stores')
                            <a href="{{ route('stores.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('stores.*') ? 'active text-white' : 'text-secondary' }}">Kelola
                                Toko (Integrasi)</a>
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

        <!-- KEUANGAN -->
        @if (auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'admin' ||
                auth()->user()->hasAnyPermission(['view-financial-reports', 'manage-finance']))
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
                        @can('view-financial-reports')
                            <a href="{{ route('finance.profit_loss') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.profit_loss') ? 'active text-white' : 'text-secondary' }}">Laba
                                Rugi</a>
                            <a href="{{ route('profit.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('profit.index') ? 'active text-white' : 'text-secondary' }}">Profit
                                Pesanan</a>
                            <a href="{{ route('profit.margin') }}"
                                class="nav-link py-1 {{ request()->routeIs('profit.margin') ? 'active text-white' : 'text-secondary' }}">Margin Produk Aktual</a>
                            <a href="{{ route('reports.store_sales') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.store_sales') ? 'active text-white' : 'text-secondary' }}">Laporan
                                Toko & Salur</a>
                            <a href="{{ route('reports.reseller_receivables') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.reseller_receivables') ? 'active text-white' : 'text-secondary' }}">Saldo
                                & Piutang</a>
                            <a href="{{ route('reports.inventory_turnover') }}"
                                class="nav-link py-1 {{ request()->routeIs('reports.inventory_turnover') ? 'active text-white' : 'text-secondary' }}">Perputaran
                                Stok</a>
                        @endcan
                        @can('manage-finance')
                            <a href="{{ route('finance.reconciliation') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.reconciliation') ? 'active text-white' : 'text-secondary' }}">Rekonsiliasi</a>
                            <a href="{{ route('finance.incomes.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.incomes.*') ? 'active text-white' : 'text-secondary' }}">Pemasukan
                                Lain</a>
                            <a href="{{ route('finance.expenses.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.expenses.*') ? 'active text-white' : 'text-secondary' }}">Pengeluaran
                                & Biaya</a>
                            <a href="{{ route('finance.transfers.index') }}"
                                class="nav-link py-1 {{ request()->routeIs('finance.transfers.*') ? 'active text-white' : 'text-secondary' }}">Transfer
                                Dana</a>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        <!-- HRD & KEPEGAWAIAN -->
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
