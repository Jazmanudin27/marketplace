@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

    @php
        $hasExpiredStores = $stores->contains('status', 'expired');
        
        $isAdmin = auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin';
        
        $hasGeneralAccess = $isAdmin || auth()->user()->can('dashboard.index');
        $hasMarketingAccess = $isAdmin || auth()->user()->can('dashboard.marketing');
        $hasFinanceAccess = $isAdmin || auth()->user()->can('dashboard.finance');
        $hasProdPurchaseAccess = $isAdmin || auth()->user()->can('dashboard.production_purchase');
        $hasWarehouseAccess = $isAdmin || auth()->user()->can('dashboard.warehouse');
        
        // Calculate dynamic card classes for stats cards
        $visibleCards = 0;
        if ($hasMarketingAccess) $visibleCards++;
        if ($hasFinanceAccess) $visibleCards += 2;
        if ($hasWarehouseAccess) $visibleCards++;
        
        $cardCol = 'col-lg-3 col-md-6';
        if ($visibleCards == 3) $cardCol = 'col-lg-4 col-md-6';
        elseif ($visibleCards == 2) $cardCol = 'col-lg-6 col-md-6';
        elseif ($visibleCards == 1) $cardCol = 'col-lg-12 col-md-12';
    @endphp

    @if ($hasExpiredStores)
        <div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning d-flex align-items-center gap-2 p-2 px-3 mb-3"
            role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading fw-bold mb-1 text-warning small">Koneksi Toko Terputus!</h6>
                <p class="mb-0 small text-secondary">Beberapa toko integrasi Anda
                    memerlukan otorisasi ulang karena
                    token kedaluwarsa. Silakan kunjungi <a href="{{ route('stores.index') }}"
                        class="alert-link text-warning fw-bold text-decoration-underline">Kelola Toko</a> untuk
                    menhubungkannya kembali.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif



    <!-- Welcome Card -->
    <div class="card bg-primary text-white border-0 rounded-3 shadow-sm mb-3">
        <div class="card-body p-3 py-3">
            <div class="row align-items-center">
                <div class="col-md-8 col-sm-12">
                    <h4 class="fw-bold mb-1 text-white">Selamat Datang, {{ Auth::user()->name }} 👋</h4>
                    <p class="mb-0 text-white-50 small">
                        Kelola operasional bisnis dan integrasi marketplace Anda untuk
                        <strong class="text-white">{{ Auth::user()->tenant->name }}</strong> dalam satu dashboard terpusat.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    @if ($visibleCards > 0)
    <div class="row g-3 mb-3">

        @if ($hasMarketingAccess)
        <!-- Card 1: Today's Orders -->
        <div class="{{ $cardCol }}">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Pesanan Hari Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark" id="totalApps">{{ number_format($todayOrders) }}</h4>
                                <span class="text-success small fw-bold ms-2"><i class="bi bi-arrow-up-short"></i> 4%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-primary opacity-75">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($hasFinanceAccess)
        <!-- Card 2: Today's Revenue -->
        <div class="{{ $cardCol }}">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Pendapatan Hari Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</h5>
                                <span class="text-success small fw-bold ms-2"><i class="bi bi-arrow-up-short"></i> 8%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-success opacity-75">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Monthly Revenue -->
        <div class="{{ $cardCol }}">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Pendapatan Bulan Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</h5>
                                <span class="text-danger small fw-bold ms-2"><i class="bi bi-arrow-down-short"></i>
                                    2%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-warning opacity-75">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($hasWarehouseAccess)
        <!-- Card 4: Ready to Ship -->
        <div class="{{ $cardCol }}">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Siap Dikirim</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark" id="totalUsers">{{ number_format($pendingOrders) }}
                                </h4>
                                <span class="text-danger small fw-bold ms-2"><i class="bi bi-arrow-down-short"></i>
                                    1%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-danger opacity-75">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

    <!-- Dropship Stats Cards -->
    @if ($hasMarketingAccess)
    <div class="row g-3 mb-3">
        <!-- Card 1: Online Dropship Orders -->
        <div class="col-lg-4 col-md-6">
            <div class="card border shadow-sm h-100 border-start border-4 border-warning" style="background: rgba(255, 193, 7, 0.03);">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem;">Dropship Online (Marketplace)</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark">{{ number_format($onlineDropshipCount) }}</h4>
                                <span class="text-muted small ms-2" style="font-size: 0.75rem;">pesanan</span>
                            </div>
                            @if ($hasFinanceAccess)
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Omset: <span class="fw-semibold text-dark">Rp {{ number_format($onlineDropshipRevenue, 0, ',', '.') }}</span></small>
                            @endif
                        </div>
                        <div class="fs-2 text-warning opacity-75">
                            <i class="bi bi-globe2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Offline Dropship Orders -->
        <div class="col-lg-4 col-md-6">
            <div class="card border shadow-sm h-100 border-start border-4 border-info" style="background: rgba(13, 202, 240, 0.03);">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem;">Dropship Manual (Offline Sales)</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark">{{ number_format($offlineDropshipCount) }}</h4>
                                <span class="text-muted small ms-2" style="font-size: 0.75rem;">transaksi</span>
                            </div>
                            @if ($hasFinanceAccess)
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Omset: <span class="fw-semibold text-dark">Rp {{ number_format($offlineDropshipRevenue, 0, ',', '.') }}</span></small>
                            @endif
                        </div>
                        <div class="fs-2 text-info opacity-75">
                            <i class="bi bi-shop"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Dropship Ratio -->
        <div class="col-lg-4 col-md-12">
            <div class="card border shadow-sm h-100 border-start border-4 border-success" style="background: rgba(25, 135, 84, 0.03);">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 0.72rem;">Rasio Pesanan Dropship</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark">{{ $dropshipRatio }}%</h4>
                                <span class="text-muted small ms-2" style="font-size: 0.75rem;">dari total pesanan</span>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Kontribusi reseller/dropshipper</small>
                        </div>
                        <div class="fs-2 text-success opacity-75">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Critical Stock Alert Card -->
    @if ($hasWarehouseAccess && $lowStockProducts->count() > 0)
        <div class="card bg-danger-subtle border-start border-4 border-danger border-0 mb-3 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between gap-2 py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-danger mb-0 small">Stok Kritis!</h6>
                        <span class="text-dark opacity-75 small">Terdapat <strong
                                class="text-danger">{{ $lowStockProducts->count() }}</strong> produk dengan
                            persediaan yang menipis atau habis.</span>
                    </div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-3">Lihat
                    Detail</a>
            </div>
        </div>
    @endif

    <!-- Marketplace Channels Overview -->
    @if ($hasMarketingAccess)
    <div class="mb-3">
        <h6 class="text-secondary text-uppercase fw-bold mb-2 small d-flex align-items-center gap-2">
            <i class="bi bi-plug-fill text-primary"></i> Status Toko Marketplace
        </h6>
        <div class="row g-2">
            @forelse($stores as $store)
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card border rounded-3 h-100 p-2 shadow-sm d-flex flex-row align-items-center gap-2 bg-white">
                        <div class="flex-shrink-0">
                            @if ($store->channel->logo_path ?? false)
                                <img src="{{ asset($store->channel->logo_path) }}" alt="{{ $store->channel->name }}"
                                    width="28" height="28" class="rounded-circle object-fit-contain">
                            @else
                                <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold text-uppercase small"
                                    style="width: 28px; height: 28px; font-size: 10px;">
                                    {{ substr($store->channel->name ?? $store->name, 0, 2) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <span class="fw-semibold text-truncate text-dark small d-block" title="{{ $store->name }}">
                                {{ $store->name }}
                            </span>
                            <div class="text-secondary small d-flex align-items-center gap-1">
                                <i class="bi bi-receipt"></i> {{ number_format($store->orders_count) }} Pesanan
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border rounded-3 text-center p-4 shadow-sm">
                        <div class="card-body py-4">
                            <i class="bi bi-plug fs-3 text-secondary mb-2"></i>
                            <p class="card-text text-secondary mb-2 small">Belum ada toko yang terhubung.</p>
                            @can('manage-stores')
                                <a href="{{ route('stores.create') }}" class="btn btn-primary btn-sm px-2 rounded-3">Tambah
                                    Toko</a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- Chart & Integrations Summary Row -->
    @if ($hasFinanceAccess || $hasGeneralAccess)
    <div class="row g-3 mb-3">
        @if ($hasFinanceAccess)
        <!-- Column: Sales Analytics Chart -->
        <div class="col-lg-8">
            <div class="card border rounded-3 h-100">
                <div class="card-header py-2 px-3">
                    <div class="row w-100 g-2 align-items-center m-0">
                        <div class="col-sm-8 p-0">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bar-chart-line text-primary fs-6"></i>
                                <span class="fw-bold text-dark">Grafik Pendapatan & Perbandingan</span>
                            </div>
                        </div>
                        <div class="col-sm-4 p-0 d-flex justify-content-sm-end">
                            <select id="chartScope"
                                class="form-select form-select-sm w-auto bg-white text-dark border-secondary">
                                <option value="daily">Hari Ini vs Kemarin</option>
                                <option value="monthly" selected>Bulan Ini vs Bulan Lalu</option>
                                <option value="yearly">Tahun Ini vs Tahun Lalu</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="ratio ratio-21x9">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($hasGeneralAccess)
        <!-- Column: Operational Summary -->
        <div class="{{ $hasFinanceAccess ? 'col-lg-4' : 'col-lg-12' }}">
            <div class="card border rounded-3 h-100 text-dark">
                <div class="card-header py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary fs-6"></i>
                        <span class="fw-bold text-dark">Ringkasan Operasional</span>
                    </div>
                </div>
                <div class="card-body d-flex flex-column gap-2 justify-content-center p-3">

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success-subtle text-success p-2 rounded-circle lh-1">
                            <i class="bi bi-plug fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Koneksi Integrasi</span>
                            <span
                                class="fw-semibold text-secondary small">{{ $stores->where('status', 'connected')->count() }}
                                dari {{ $totalStores }} Toko Aktif</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary-subtle text-primary p-2 rounded-circle lh-1">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Katalog Produk</span>
                            <span class="fw-semibold text-secondary small">{{ number_format($totalProducts) }} Master
                                Produk</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-warning-subtle text-warning p-2 rounded-circle lh-1">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Proses Pengiriman</span>
                            <span class="fw-semibold text-secondary small">{{ number_format($pendingOrders) }} Pesanan
                                Siap Kirim</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-info-subtle text-info p-2 rounded-circle lh-1">
                            <i class="bi bi-chat-dots fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Layanan Pelanggan</span>
                            <span class="fw-semibold text-secondary small">Inbox Chat Terhubung</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

    <!-- Recent Orders & Stock warning grid -->
    @php
        $showLeftCol = $hasMarketingAccess || $hasGeneralAccess;
        $showRightCol = $hasWarehouseAccess || $hasProdPurchaseAccess;
        
        $leftColClass = $showRightCol ? 'col-12 col-xxl-8' : 'col-12 col-xxl-12';
        $rightColClass = $showLeftCol ? 'col-12 col-xxl-4' : 'col-12 col-xxl-12';
    @endphp

    @if ($showLeftCol || $showRightCol)
    <div class="row g-3 p-2">

        @if ($showLeftCol)
        <!-- Column: Recent Orders Table -->
        <div class="{{ $leftColClass }}">
            <div class="card border rounded-3 h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-primary fs-6"></i>
                        <span class="fw-bold text-dark">Pesanan Terbaru</span>
                    </div>
                    <a href="{{ route('orders.index') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-bold text-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Invoice</th>
                                    <th>Pembeli</th>
                                    <th>Toko / Channel</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td class="text-dark small">
                                            {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td class="font-monospace fw-semibold text-dark small">
                                            {{ $order->order_marketplace_id }}
                                        </td>
                                        <td class="text-dark small">
                                            {{ $order->customer_name }}
                                        </td>
                                        <td class="text-dark small">
                                            {{ $order->store->name ?? '-' }}
                                        </td>
                                        <td class="font-monospace fw-bold text-dark small">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @php
                                                $badgeColor = 'bg-secondary';
                                                if ($order->status_badge === 'success') {
                                                    $badgeColor = 'bg-success text-white';
                                                } elseif ($order->status_badge === 'warning') {
                                                    $badgeColor = 'bg-warning text-dark';
                                                } elseif ($order->status_badge === 'danger') {
                                                    $badgeColor = 'bg-danger text-white';
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeColor }} px-2 py-1">
                                                {{ str_replace('_', ' ', $order->order_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-secondary">
                                            <i
                                                class="bi bi-check-circle-fill fs-3 text-success opacity-75 mb-2 d-block"></i>
                                            <p class="mb-0 small">Belum ada pesanan terbaru</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($showRightCol)
        <!-- Column: Low Stock Products list & Cancel Reasons -->
        <div class="{{ $rightColClass }} d-flex flex-column gap-3">
            @if ($hasProdPurchaseAccess)
            <!-- Card: Stok Bahan Baku Menipis -->
            <div class="card border rounded-3">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-boxes text-danger fs-6"></i>
                        <span class="fw-bold text-dark">Stok Bahan Baku Menipis</span>
                        @if ($lowStockMaterials->count() > 0)
                            <span class="badge bg-danger rounded-pill px-2 py-1">{{ $lowStockMaterials->count() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('pembelian.stock_report') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-bold text-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-3">
                    @forelse($lowStockMaterials as $material)
                        @php
                            $pct =
                                $material->min_stock > 0
                                    ? min(100, round(($material->stock / $material->min_stock) * 100))
                                    : 0;
                            $isCritical = $material->stock == 0;
                            $barColor = $isCritical ? 'bg-danger' : ($pct <= 50 ? 'bg-warning' : 'bg-success');
                            $textColor = $isCritical ? 'text-danger' : ($pct <= 50 ? 'text-warning' : 'text-success');
                        @endphp
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                            <div class="flex-grow-1 min-width-0 pe-3">
                                <div class="fw-semibold text-truncate text-dark small" title="{{ $material->name }}">
                                    {{ $material->name }}</div>
                                <div class="text-secondary font-monospace small" style="font-size: 11px;">
                                    SKU: {{ $material->sku ?? '-' }} | Tipe: {{ ucfirst($material->type) }}</div>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar"
                                        style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold fs-5 {{ $textColor }}">{{ number_format($material->stock) }} <span style="font-size: 10px; font-weight: normal; color: #666;">{{ $material->unit }}</span></div>
                                <div class="text-secondary small">min {{ number_format($material->min_stock) }}</div>
                                @if ($isCritical)
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-1 small">HABIS</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary">
                            <i class="bi bi-check-circle-fill fs-3 text-success opacity-75 mb-2 d-block"></i>
                            <p class="mb-0 small">Semua stok bahan baku aman! ✅</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @endif

            @if ($hasWarehouseAccess)
            <!-- Card: Low Stock Finished Goods -->
            <div class="card border rounded-3">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle text-warning fs-6"></i>
                        <span class="fw-bold text-dark">Stok Menipis</span>
                        @if ($lowStockProducts->count() > 0)
                            <span class="badge bg-danger rounded-pill px-2 py-1">{{ $lowStockProducts->count() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('products.index') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-bold text-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-3">
                    @forelse($lowStockProducts as $product)
                        @php
                            $pct =
                                $product->min_stock > 0
                                    ? min(100, round(($product->stock / $product->min_stock) * 100))
                                    : 0;
                            $isCritical = $product->stock === 0;
                            $barColor = $isCritical ? 'bg-danger' : ($pct <= 50 ? 'bg-warning' : 'bg-success');
                            $textColor = $isCritical ? 'text-danger' : ($pct <= 50 ? 'text-warning' : 'text-success');
                        @endphp
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                            <div class="flex-grow-1 min-width-0 pe-3">
                                <div class="fw-semibold text-truncate text-dark small" title="{{ $product->name }}">
                                    {{ $product->name }}</div>
                                <div class="text-secondary font-monospace small">
                                    SKU: {{ $product->sku }}</div>
                                <div class="progress mt-1">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar"
                                        style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold fs-5 {{ $textColor }}">{{ number_format($product->stock) }}</div>
                                <div class="text-secondary small">min {{ number_format($product->min_stock) }}</div>
                                @if ($isCritical)
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-1 small">HABIS</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary">
                            <i class="bi bi-check-circle-fill fs-3 text-success opacity-75 mb-2 d-block"></i>
                            <p class="mb-0 small">Semua stok aman! ✅</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @endif

            @if ($hasWarehouseAccess || $hasMarketingAccess)
            <!-- Card: Top Alasan Pembatalan -->
            <div class="card border rounded-3 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-x-octagon text-danger fs-6"></i>
                        <span class="fw-bold text-dark">Top Alasan Pembatalan</span>
                    </div>
                </div>
                <div class="card-body p-3">
                    @forelse($topCancelReasons as $reason)
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                            <div class="flex-grow-1 min-width-0 pe-3">
                                <div class="fw-semibold text-truncate text-dark small" title="{{ $reason->cancel_reason }}">
                                    {{ $reason->cancel_reason }}
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold">{{ $reason->count }}x</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary text-muted">
                            <i class="bi bi-emoji-smile fs-3 text-success opacity-75 mb-2 d-block"></i>
                            <p class="mb-0 small">Belum ada pesanan yang batal! 🌟</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>
    @endif

@endsection

@push('scripts')
@if ($hasFinanceAccess)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');

            // Gradient fill for Net Profit
            let gradientNet = ctx.createLinearGradient(0, 0, 0, 220);
            gradientNet.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            gradientNet.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            // Gradient fill for Gross Revenue
            let gradientGross = ctx.createLinearGradient(0, 0, 0, 220);
            gradientGross.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
            gradientGross.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($initialChartData['labels']),
                    datasets: [{
                            label: @json($initialChartData['currentLabel']),
                            data: @json($initialChartData['current']),
                            borderColor: '#10b981',
                            backgroundColor: gradientNet,
                            borderWidth: 3,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10b981',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: @json($initialChartData['previousLabel']),
                            data: @json($initialChartData['previous']),
                            borderColor: '#3b82f6',
                            backgroundColor: gradientGross,
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3b82f6',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                color: '#475569',
                                callback: function(value) {
                                    if (value >= 1e6) return 'Rp ' + (value / 1e6).toFixed(1) + 'M';
                                    if (value >= 1e3) return 'Rp ' + (value / 1e3).toFixed(0) + 'k';
                                    return 'Rp ' + value;
                                }
                            },
                            grid: {
                                color: 'rgba(241, 245, 249, 1)',
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 10
                                },
                                maxTicksLimit: 12
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            padding: 12,
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: {
                                size: 13,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            maximumFractionDigits: 0
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // AJAX scope filtering (using jQuery to support Select2 event triggering)
            $('#chartScope').on('change', function() {
                const scope = $(this).val();
                fetch("{{ route('dashboard.chart-data') }}?scope=" + scope)
                    .then(response => response.json())
                    .then(data => {
                        revenueChart.data.labels = data.labels;

                        revenueChart.data.datasets[0].label = data.currentLabel;
                        revenueChart.data.datasets[0].data = data.current;

                        revenueChart.data.datasets[1].label = data.previousLabel;
                        revenueChart.data.datasets[1].data = data.previous;

                        revenueChart.update();
                    })
                    .catch(err => {
                        console.error('Error fetching chart data:', err);
                    });
            });
        });
    </script>
@endif
@endpush
