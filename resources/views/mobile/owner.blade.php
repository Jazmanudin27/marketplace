@extends('layouts.mobile')

@section('title', 'Owner Dashboard')
@section('header-title', 'Owner Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="row g-3 mb-4">
        <!-- Today Revenue -->
        <div class="col-6">
            <div class="glass-card h-100 p-3 d-flex flex-column justify-content-between position-relative overflow-hidden"
                style="min-height: 110px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-10"
                    style="font-size: 3rem; transform: translate(20%, -20%);">
                    <i class="fas fa-coins text-success"></i>
                </div>
                <div>
                    <small class="text-white-50 text-uppercase fw-bold d-block"
                        style="font-size: 0.65rem; letter-spacing: 0.5px;">Omset Hari Ini</small>
                    <h3 class="mt-2 mb-0 fw-bold text-success fs-5">
                        Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-3 d-flex align-items-center gap-1 text-success-emphasis small fw-medium"
                    style="font-size: 0.7rem; color: var(--accent-green) !important;">
                    <i class="fas fa-calendar-day"></i> Hari ini
                </div>
            </div>
        </div>

        <!-- Month Revenue -->
        <div class="col-6">
            <div class="glass-card h-100 p-3 d-flex flex-column justify-content-between position-relative overflow-hidden"
                style="min-height: 110px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-10"
                    style="font-size: 3rem; transform: translate(20%, -20%);">
                    <i class="fas fa-chart-line text-indigo" style="color: #818cf8;"></i>
                </div>
                <div>
                    <small class="text-white-50 text-uppercase fw-bold d-block"
                        style="font-size: 0.65rem; letter-spacing: 0.5px;">Omset Bulan Ini</small>
                    <h3 class="mt-2 mb-0 fw-bold fs-5" style="color: #818cf8;">
                        Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-3 d-flex align-items-center gap-1 small fw-medium"
                    style="font-size: 0.7rem; color: #818cf8 !important;">
                    <i class="fas fa-chart-bar"></i> Bulan aktif
                </div>
            </div>
        </div>

        <!-- Stock Valuation -->
        <div class="col-6">
            <div class="glass-card h-100 p-3 d-flex flex-column justify-content-between position-relative overflow-hidden"
                style="min-height: 110px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-10"
                    style="font-size: 3rem; transform: translate(20%, -20%);">
                    <i class="fas fa-boxes text-info"></i>
                </div>
                <div>
                    <small class="text-white-50 text-uppercase fw-bold d-block"
                        style="font-size: 0.65rem; letter-spacing: 0.5px;">Nilai Total Stok</small>
                    <h3 class="mt-2 mb-0 fw-bold text-info fs-5">
                        Rp {{ number_format($totalStockValue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-3 d-flex align-items-center gap-1 text-info-emphasis small fw-medium"
                    style="font-size: 0.7rem; color: var(--accent-blue) !important;">
                    <i class="fas fa-warehouse"></i> HPP Aset
                </div>
            </div>
        </div>

        <!-- Low Stock Count -->
        <div class="col-6">
            <div class="glass-card h-100 p-3 d-flex flex-column justify-content-between position-relative overflow-hidden"
                style="min-height: 110px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-10"
                    style="font-size: 3rem; transform: translate(20%, -20%);">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                </div>
                <div>
                    <small class="text-white-50 text-uppercase fw-bold d-block"
                        style="font-size: 0.65rem; letter-spacing: 0.5px;">Stok Menipis</small>
                    <h3 class="mt-2 mb-0 fw-bold text-danger fs-5">
                        {{ $lowStockCount }} <span class="text-white-50 small fw-normal">Produk</span>
                    </h3>
                </div>
                <div class="mt-3 d-flex align-items-center gap-1 text-danger-emphasis small fw-medium"
                    style="font-size: 0.7rem; color: var(--accent-red) !important;">
                    <i class="fas fa-shield-alert"></i> Butuh Restock
                </div>
            </div>
        </div>

        <!-- Spend Iklan Bulan Ini -->
        <div class="col-6">
            <div class="glass-card h-100 p-3 d-flex flex-column justify-content-between position-relative overflow-hidden"
                style="min-height: 110px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-10"
                    style="font-size: 3rem; transform: translate(20%, -20%);">
                    <i class="fas fa-ad text-warning" style="color: #f59e0b;"></i>
                </div>
                <div>
                    <small class="text-white-50 text-uppercase fw-bold d-block"
                        style="font-size: 0.65rem; letter-spacing: 0.5px;">Biaya Iklan (Spend)</small>
                    <h3 class="mt-2 mb-0 fw-bold text-warning fs-5" style="color: #fbbf24;">
                        Rp {{ number_format($monthSpend, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-3 d-flex align-items-center gap-1 small fw-medium"
                    style="font-size: 0.7rem; color: #fbbf24 !important;">
                    <i class="fas fa-wallet"></i> Bulan ini
                </div>
            </div>
        </div>

        <!-- ROAS Iklan Bulan Ini -->
        <div class="col-6">
            <div class="glass-card h-100 p-3 d-flex flex-column justify-content-between position-relative overflow-hidden"
                style="min-height: 110px;">
                <div class="position-absolute top-0 end-0 p-3 opacity-10"
                    style="font-size: 3rem; transform: translate(20%, -20%);">
                    <i class="fas fa-crosshairs text-success" style="color: #10b981;"></i>
                </div>
                <div>
                    <small class="text-white-50 text-uppercase fw-bold d-block"
                        style="font-size: 0.65rem; letter-spacing: 0.5px;">Rata-Rata ROAS</small>
                    <h3 class="mt-2 mb-0 fw-bold text-success fs-5">
                        {{ number_format($monthRoas, 2) }}x
                    </h3>
                </div>
                <div class="mt-3 d-flex align-items-center gap-1 text-success-emphasis small fw-medium"
                    style="font-size: 0.7rem; color: var(--accent-green) !important;">
                    <i class="fas fa-chart-line"></i> ROI Target
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Card -->
    <div class="glass-card mb-4 overflow-hidden">
        <div
            class="card-header bg-white bg-opacity-5 d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom border-white border-opacity-10">
            <h6 class="m-0 fw-bold text-white d-flex align-items-center gap-2">
                <i class="fas fa-shopping-bag text-indigo" style="color: #818cf8;"></i> Pesanan Terbaru
            </h6>
            <span class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10"
                style="font-size: 0.65rem;">Limit 5</span>
        </div>

        <div class="card-body p-3">
            <div class="d-flex flex-column gap-3">
                @forelse($recentOrders as $order)
                    <div
                        class="d-flex justify-content-between align-items-center pb-2.5 {{ !$loop->last ? 'border-bottom border-white border-opacity-5' : '' }}">
                        <div>
                            <div class="fw-bold text-white small">{{ $order->buyer_name }}</div>
                            <div class="d-flex gap-1 align-items-center mt-1">
                                <span
                                    class="badge bg-white bg-opacity-5 text-white-50 border border-white border-opacity-10"
                                    style="font-size: 0.65rem;">
                                    {{ $order->store->store_name }}
                                </span>
                                <span class="text-white-50 small">•</span>
                                <span class="text-white-50 small"
                                    style="font-size: 0.7rem;">{{ $order->order_date->format('H:i') }}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success small">
                                Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                            </div>
                            @php
                                $badgeColor = 'bg-secondary text-white';
                                if ($order->order_status === 'COMPLETED' || $order->order_status === 'DELIVERED') {
                                    $badgeColor =
                                        'bg-success bg-opacity-15 text-success border border-success border-opacity-25';
                                } elseif (
                                    $order->order_status === 'READY_TO_SHIP' ||
                                    $order->order_status === 'SHIPPED'
                                ) {
                                    $badgeColor =
                                        'bg-primary bg-opacity-15 text-indigo border border-primary border-opacity-25';
                                } elseif ($order->order_status === 'CANCELLED') {
                                    $badgeColor =
                                        'bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25';
                                }
                            @endphp
                            <span class="badge {{ $badgeColor }} mt-1 small"
                                style="font-size: 0.65rem; text-transform: uppercase;">
                                {{ str_replace('_', ' ', $order->order_status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-white-50 small">
                        <i class="fas fa-check-circle text-success opacity-50 fs-4 mb-2 d-block"></i>
                        Tidak ada pesanan masuk hari ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Low Stock Warnings Card -->
    <div class="glass-card mb-4 overflow-hidden">
        <div
            class="card-header bg-white bg-opacity-5 d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom border-white border-opacity-10">
            <h6 class="m-0 fw-bold text-danger d-flex align-items-center gap-2">
                <i class="fas fa-exclamation-triangle text-danger"></i> Peringatan Stok Limit
            </h6>
        </div>

        <div class="card-body p-3">
            <div class="d-flex flex-column gap-3">
                @forelse($lowStockProducts as $product)
                    <div
                        class="d-flex justify-content-between align-items-center pb-2.5 {{ !$loop->last ? 'border-bottom border-white border-opacity-5' : '' }}">
                        <div>
                            <div class="fw-bold text-white small">{{ $product->name }}</div>
                            <small class="text-white-50 d-block mt-0.5" style="font-size: 0.7rem;">
                                SKU: <code class="font-monospace" style="color: #818cf8;">{{ $product->sku }}</code>
                            </small>
                        </div>
                        <div class="text-end">
                            <span
                                class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 small"
                                style="font-size: 0.65rem;">
                                Stok: {{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                            </span>
                            <div class="text-white-50 small mt-1" style="font-size: 0.75rem;">
                                Min: {{ $product->min_stock }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-white-50 small">
                        <i class="fas fa-check-circle text-success opacity-50 fs-4 mb-2 d-block"></i>
                        Semua stok produk aman!
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
