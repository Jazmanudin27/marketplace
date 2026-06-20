@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
    <style>
        /* Compact layout overrides for data-dense dashboard index */
        .card-modern .card-body {
            padding: 12px 14px !important;
        }
        .stat-card .stat-icon {
            font-size: 26px !important;
        }
        .table-modern th {
            font-size: 0.72rem !important;
            padding: 8px 12px !important;
        }
        .table-modern td {
            font-size: 0.78rem !important;
            padding: 8px 12px !important;
        }
        /* Specific Tabler style overrides */
        .welcome-card {
            background: linear-gradient(135deg, #15202f, #0f172a) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .stat-card-tabler {
            border-left: 3px solid transparent !important;
            background-color: #151f2c !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px !important;
        }
        .stat-card-blue { border-left-color: #3b82f6 !important; }
        .stat-card-green { border-left-color: #10b981 !important; }
        .stat-card-yellow { border-left-color: #f59e0b !important; }
        .stat-card-red { border-left-color: #ef4444 !important; }
        
        .stat-trend {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .trend-up {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .trend-down {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
    </style>
@endpush

@section('content')

    @php
        $hasExpiredStores = $stores->contains('status', 'expired');
    @endphp

    @if ($hasExpiredStores)
        <div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning d-flex align-items-center gap-2 p-2 px-3 mb-3"
            role="alert" style="background-color: rgba(245, 158, 11, 0.05); color: #f59e0b; border-color: rgba(245, 158, 11, 0.2);">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading fw-bold mb-1 text-warning small">Koneksi Toko Terputus!</h6>
                <p class="mb-0 small text-light opacity-75" style="font-size: 0.75rem;">Beberapa toko integrasi Anda memerlukan otorisasi ulang karena
                    token kedaluwarsa. Silakan kunjungi <a href="{{ route('stores.index') }}"
                        class="alert-link text-warning fw-bold text-decoration-underline">Kelola Toko</a> untuk
                    menhubungkannya kembali.</p>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="padding: 1rem;"></button>
        </div>
    @endif

    <!-- Welcome Card -->
    <div class="card welcome-card mb-3">
        <div class="card-body p-3 py-3">
            <div class="row align-items-center">
                <div class="col-md-8 col-sm-12">
                    <h4 class="fw-bold mb-1 text-white">Selamat Datang, {{ Auth::user()->name }} 👋</h4>
                    <p class="mb-0 text-secondary small">
                        Kelola operasional bisnis dan integrasi marketplace Anda untuk
                        <strong class="text-light">{{ Auth::user()->tenant->name }}</strong> dalam satu dashboard terpusat.
                    </p>
                </div>
                <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
                    <!-- Premium Scalable SVG illustration -->
                    <svg width="110" height="75" viewBox="0 0 120 90" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.9;">
                        <rect x="10" y="20" width="100" height="60" rx="6" fill="#111923" stroke="rgba(255,255,255,0.08)" stroke-width="1.5"/>
                        <circle cx="90" cy="50" r="16" fill="url(#gradient-blue)" />
                        <path d="M85 50L88 53L95 46" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="22" y="36" width="30" height="6" rx="3" fill="rgba(255,255,255,0.15)"/>
                        <rect x="22" y="48" width="45" height="4" rx="2" fill="rgba(255,255,255,0.08)"/>
                        <rect x="22" y="58" width="20" height="4" rx="2" fill="rgba(255,255,255,0.08)"/>
                        <path d="M15 75C25 65 35 70 45 60C55 50 65 58 75 42" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" fill="none"/>
                        <defs>
                            <linearGradient id="gradient-blue" x1="74" y1="34" x2="106" y2="66" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#3b82f6"/>
                                <stop offset="1" stop-color="#6366f1"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-3 mb-3">

        <!-- Card 1: Today's Orders -->
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card stat-card-tabler stat-card-blue h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary opacity-75 text-uppercase fw-semibold"
                                style="font-size: 0.65rem; letter-spacing: 0.05em;">Pesanan Hari Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-white" id="totalApps">{{ number_format($todayOrders) }}</h4>
                                <span class="stat-trend trend-up ms-2"><i class="bi bi-arrow-up-short"></i> 4%</span>
                            </div>
                        </div>
                        <div class="stat-icon text-primary">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Today's Revenue -->
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card stat-card-tabler stat-card-green h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary opacity-75 text-uppercase fw-semibold"
                                style="font-size: 0.65rem; letter-spacing: 0.05em;">Pendapatan Hari Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-white" style="font-size: 1.15rem;">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</h4>
                                <span class="stat-trend trend-up ms-2"><i class="bi bi-arrow-up-short"></i> 8%</span>
                            </div>
                        </div>
                        <div class="stat-icon text-success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Monthly Revenue -->
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card stat-card-tabler stat-card-yellow h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary opacity-75 text-uppercase fw-semibold"
                                style="font-size: 0.65rem; letter-spacing: 0.05em;">Pendapatan Bulan Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-white" style="font-size: 1.15rem;">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</h4>
                                <span class="stat-trend trend-down ms-2"><i class="bi bi-arrow-down-short"></i> 2%</span>
                            </div>
                        </div>
                        <div class="stat-icon text-warning">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Ready to Ship -->
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card stat-card-tabler stat-card-red h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary opacity-75 text-uppercase fw-semibold"
                                style="font-size: 0.65rem; letter-spacing: 0.05em;">Siap Dikirim</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-white" id="totalUsers">{{ number_format($pendingOrders) }}</h4>
                                <span class="stat-trend trend-down ms-2"><i class="bi bi-arrow-down-short"></i> 1%</span>
                            </div>
                        </div>
                        <div class="stat-icon text-danger">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Critical Stock Alert Card -->
    @if ($lowStockProducts->count() > 0)
        <div class="card bg-danger bg-opacity-10 border-danger border-opacity-20 mb-3 shadow-sm"
            style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
            <div class="card-body d-flex align-items-center justify-content-between gap-2 py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger bg-opacity-20 text-danger rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 32px; height: 32px; flex-shrink: 0;">
                        <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-danger mb-0 small">Stok Kritis!</h6>
                        <span class="text-light opacity-75 small" style="font-size: 0.75rem;">Terdapat <strong
                                class="text-danger">{{ $lowStockProducts->count() }}</strong> produk dengan
                            persediaan yang menipis atau habis.</span>
                    </div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-danger btn-sm px-2 py-1"
                    style="border-radius: 8px; font-size: 0.75rem;">Lihat Detail</a>
            </div>
        </div>
    @endif

    <!-- Marketplace Channels Overview -->
    <div class="mb-3">
        <h6 class="text-secondary text-uppercase fw-bold mb-2 small d-flex align-items-center gap-2"
            style="letter-spacing: 0.05em; font-size: 0.7rem;">
            <i class="bi bi-plug-fill text-primary"></i> Status Toko Marketplace
        </h6>
        <div class="row g-2">
            @forelse($stores as $store)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="card card-modern h-100 shadow-sm" style="border-radius: 8px;">
                        <div class="card-body p-2 px-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                @php
                                    $badgeClass = 'bg-secondary';
                                    if ($store->channel->code === 'shopee') {
                                        $badgeClass = 'bg-danger text-white';
                                    } elseif ($store->channel->code === 'tokopedia') {
                                        $badgeClass = 'bg-success text-white';
                                    } elseif ($store->channel->code === 'tiktok') {
                                        $badgeClass = 'bg-dark text-white';
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size: 0.6rem;">
                                    @if ($store->channel->code === 'shopee')
                                        <i class="bi bi-bag-fill me-1"></i>
                                    @elseif($store->channel->code === 'tiktok')
                                        <i class="bi bi-tiktok me-1"></i>
                                    @elseif($store->channel->code === 'tokopedia')
                                        <i class="bi bi-shop me-1"></i>
                                    @else
                                        <i class="bi bi-globe me-1"></i>
                                    @endif
                                    {{ $store->channel->name }}
                                </span>

                                @if ($store->status === 'connected')
                                    <span class="d-flex align-items-center gap-1 text-success fw-medium"
                                        style="font-size: 0.65rem;">
                                        <span class="bg-success rounded-circle" style="width: 5px; height: 5px;"></span>
                                        Aktif
                                    </span>
                                @elseif($store->status === 'expired')
                                    <span class="d-flex align-items-center gap-1 text-warning fw-medium"
                                        style="font-size: 0.65rem;">
                                        <span class="bg-warning rounded-circle" style="width: 5px; height: 5px;"></span>
                                        Expired
                                    </span>
                                @else
                                    <span class="d-flex align-items-center gap-1 text-danger fw-medium"
                                        style="font-size: 0.65rem;">
                                        <span class="bg-danger rounded-circle" style="width: 5px; height: 5px;"></span>
                                        Off
                                    </span>
                                @endif
                            </div>
                            <h6 class="fw-bold mb-1 text-truncate text-white" title="{{ $store->store_name }}"
                                style="font-size: 0.78rem;">
                                {{ $store->store_name }}</h6>
                            <div class="text-secondary small d-flex align-items-center gap-1" style="font-size: 0.7rem;">
                                <i class="bi bi-receipt"></i> {{ number_format($store->orders_count) }} Pesanan
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card card-modern text-center p-4 shadow-sm" style="border-radius: 8px;">
                        <div class="card-body py-4">
                            <i class="bi bi-plug fs-3 text-secondary mb-2"></i>
                            <p class="card-text text-secondary mb-2 small">Belum ada toko yang terhubung.</p>
                            @can('manage-stores')
                                <a href="{{ route('stores.create') }}" class="btn btn-primary btn-sm px-2"
                                    style="border-radius: 8px; font-size: 0.75rem;">Tambah Toko</a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Chart & Integrations Summary Row -->
    <div class="row g-3 mb-3">

        <!-- Column: Sales Analytics Chart -->
        <div class="col-lg-8">
            <div class="card card-modern h-100" style="border-radius: 8px;">
                <div class="card-header py-2 px-3" style="background-color: transparent;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-line text-primary fs-6"></i>
                        <span class="fw-bold" style="font-size: 0.85rem; color: #ffffff;">Grafik Pendapatan (30 Hari Terakhir)</span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: 240px; width: 100%">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column: Operational Summary -->
        <div class="col-lg-4">
            <div class="card card-modern h-100" style="border-radius: 8px;">
                <div class="card-header py-2 px-3" style="background-color: transparent;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary fs-6"></i>
                        <span class="fw-bold" style="font-size: 0.85rem; color: #ffffff;">Ringkasan Operasional</span>
                    </div>
                </div>
                <div class="card-body d-flex flex-column gap-2 justify-content-center p-3">

                    <div class="d-flex align-items-center gap-2">
                        <div class="badge bg-success bg-opacity-10 text-success p-2 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px; flex-shrink: 0;">
                            <i class="bi bi-plug fs-6"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small" style="font-size: 0.7rem; line-height: 1.1;">Koneksi
                                Integrasi</span>
                            <span class="fw-semibold text-light small" style="font-size: 0.8rem;">{{ $stores->where('status', 'connected')->count() }} dari
                                {{ $totalStores }} Toko Aktif</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px; flex-shrink: 0;">
                            <i class="bi bi-box-seam fs-6"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small" style="font-size: 0.7rem; line-height: 1.1;">Katalog Produk</span>
                            <span class="fw-semibold text-light small" style="font-size: 0.8rem;">{{ number_format($totalProducts) }} Master Produk</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px; flex-shrink: 0;">
                            <i class="bi bi-truck fs-6"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small" style="font-size: 0.7rem; line-height: 1.1;">Proses
                                Pengiriman</span>
                            <span class="fw-semibold text-light small" style="font-size: 0.8rem;">{{ number_format($pendingOrders) }} Pesanan Siap
                                Kirim</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="badge bg-info bg-opacity-10 text-info p-2 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px; flex-shrink: 0;">
                            <i class="bi bi-chat-dots fs-6"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small" style="font-size: 0.7rem; line-height: 1.1;">Layanan
                                Pelanggan</span>
                            <span class="fw-semibold text-light small" style="font-size: 0.8rem;">Inbox Chat Terhubung</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Recent Orders & Stock warning grid -->
    <div class="row g-3">

        <!-- Column: Recent Orders Table -->
        <div class="col-12 col-xxl-8">
            <div class="card card-modern h-100" style="border-radius: 8px;">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3" style="background-color: transparent;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-primary fs-6"></i>
                        <span class="fw-bold" style="font-size: 0.85rem; color: #ffffff;">Pesanan Terbaru</span>
                    </div>
                    <a href="{{ route('orders.index') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-modern align-middle mb-0">
                            <thead>
                                <tr>
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
                                        <td class="font-monospace fw-semibold text-light" style="font-size: 0.75rem;">
                                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                        </td>
                                        <td class="fw-medium text-light" style="font-size: 0.78rem;">{{ $order->buyer_name ?? '-' }}</td>
                                        <td>
                                            @php
                                                $badgeClass = 'bg-secondary';
                                                if ($order->store->channel->code === 'shopee') {
                                                    $badgeClass = 'bg-danger text-white';
                                                } elseif ($order->store->channel->code === 'tokopedia') {
                                                    $badgeClass = 'bg-success text-white';
                                                } elseif ($order->store->channel->code === 'tiktok') {
                                                    $badgeClass = 'bg-dark text-white';
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeClass }} px-2 py-1"
                                                style="font-size: 0.6rem;">
                                                {{ $order->store->store_name }}
                                            </span>
                                        </td>
                                        <td class="font-monospace fw-bold text-light" style="font-size: 0.78rem;">
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
                                            <span class="badge {{ $badgeColor }} px-2 py-1" style="font-size: 0.65rem;">
                                                {{ str_replace('_', ' ', $order->order_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">
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

        <!-- Column: Low Stock Products list -->
        <div class="col-12 col-xxl-4">
            <div class="card card-modern h-100" style="border-radius: 8px;">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3" style="background-color: transparent;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle text-warning fs-6"></i>
                        <span class="fw-bold" style="font-size: 0.85rem; color: #ffffff;">Stok Menipis</span>
                        @if ($lowStockProducts->count() > 0)
                            <span class="badge bg-danger rounded-pill px-2 py-1"
                                style="font-size: 0.6rem;">{{ $lowStockProducts->count() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('products.index') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" style="font-size: 0.78rem;">Lihat Semua</a>
                </div>
                <div class="card-body p-3 overflow-y-auto" style="max-height: 280px;">
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
                        <div
                            class="d-flex align-items-center justify-content-between py-2 border-bottom border-light-subtle" style="border-color: rgba(255,255,255,0.05) !important;">
                            <div class="flex-grow-1 min-width-0 pe-3">
                                <div class="fw-semibold text-truncate text-light" style="font-size: 0.78rem;"
                                    title="{{ $product->name }}">
                                    {{ $product->name }}</div>
                                <div class="text-secondary font-monospace" style="font-size: 0.65rem;">
                                    SKU: {{ $product->sku }}</div>
                                <div class="progress mt-1" style="height: 3px; border-radius: 10px; background-color: rgba(255,255,255,0.08);">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar"
                                        style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold fs-6 {{ $textColor }}">{{ number_format($product->stock) }}</div>
                                <div class="text-secondary" style="font-size: 0.62rem;">min
                                    {{ number_format($product->min_stock) }}</div>
                                @if ($isCritical)
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-1"
                                        style="font-size: 0.55rem; padding: 2px 4px;">HABIS</span>
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
        </div>

    </div>

@endsection

@push('scripts')
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
                    labels: @json($chartDates),
                    datasets: [{
                            label: 'Keuntungan Bersih (Escrow)',
                            data: @json($chartNet),
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
                            label: 'Total Penjualan (Kotor)',
                            data: @json($chartGross),
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
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#8a99ad',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#111923',
                            titleColor: '#ffffff',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(255, 255, 255, 0.08)',
                            borderWidth: 1,
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
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#8a99ad',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 10
                                },
                                callback: function(value, index, values) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000) + ' Jt';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value / 1000) + ' Rb';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#8a99ad',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 10
                                },
                                maxTicksLimit: 10
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
