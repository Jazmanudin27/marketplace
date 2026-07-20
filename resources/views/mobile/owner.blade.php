@extends('layouts.mobile')

@section('title', 'Owner Dashboard')
@section('header-title', 'Owner Dashboard')

@section('styles')
<style>
    /* Custom premium styling for Owner Light Dashboard */
    body {
        background-color: #f8fafc !important;
    }
    
    .dashboard-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
    }
    
    .card-icon-bg {
        position: absolute;
        top: -10px;
        right: -10px;
        font-size: 3rem;
        opacity: 0.06;
        transform: rotate(15deg);
        transition: all 0.3s ease;
    }

    .dashboard-card:hover .card-icon-bg {
        transform: rotate(5deg) scale(1.1);
        opacity: 0.1;
    }

    .card-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
    }

    .card-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 6px;
        margin-bottom: 0;
    }

    .card-footer-text {
        font-size: 0.72rem;
        font-weight: 600;
        margin-top: 12px;
    }

    /* List item styles */
    .list-item-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .list-item-custom:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .badge-premium {
        font-size: 0.65rem;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-light-grey {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .badge-success-light {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .badge-indigo-light {
        background: #e0e7ff;
        color: #4f46e5;
        border: 1px solid rgba(79, 70, 229, 0.15);
    }

    .badge-danger-light {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.15);
    }
</style>
@endsection

@section('content')
    <!-- Stats Grid -->
    <div class="row g-3 mb-4">
        <!-- Today Revenue -->
        <div class="col-6">
            <div class="dashboard-card h-100 p-3 d-flex flex-column justify-content-between" style="min-height: 115px;">
                <div class="card-icon-bg text-success">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <span class="card-label">Omset Hari Ini</span>
                    <h3 class="card-value text-success">
                        Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="card-footer-text text-success d-flex align-items-center gap-1">
                    <i class="fas fa-calendar-day"></i> Hari ini
                </div>
            </div>
        </div>

        <!-- Month Revenue -->
        <div class="col-6">
            <div class="dashboard-card h-100 p-3 d-flex flex-column justify-content-between" style="min-height: 115px;">
                <div class="card-icon-bg text-primary">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <span class="card-label">Omset Bulan Ini</span>
                    <h3 class="card-value text-primary">
                        Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="card-footer-text text-primary d-flex align-items-center gap-1">
                    <i class="fas fa-chart-bar"></i> Bulan aktif
                </div>
            </div>
        </div>

        <!-- Stock Valuation -->
        <div class="col-6">
            <div class="dashboard-card h-100 p-3 d-flex flex-column justify-content-between" style="min-height: 115px;">
                <div class="card-icon-bg text-info">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <span class="card-label">Nilai Total Stok</span>
                    <h3 class="card-value text-info">
                        Rp {{ number_format($totalStockValue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="card-footer-text text-info d-flex align-items-center gap-1">
                    <i class="fas fa-warehouse"></i> HPP Aset
                </div>
            </div>
        </div>

        <!-- Low Stock Count -->
        <div class="col-6">
            <div class="dashboard-card h-100 p-3 d-flex flex-column justify-content-between" style="min-height: 115px;">
                <div class="card-icon-bg text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <span class="card-label">Stok Menipis</span>
                    <h3 class="card-value text-danger">
                        {{ $lowStockCount }} <span class="text-muted small fw-normal">Produk</span>
                    </h3>
                </div>
                <div class="card-footer-text text-danger d-flex align-items-center gap-1">
                    <i class="fas fa-shield-halved"></i> Restock
                </div>
            </div>
        </div>

        <!-- Spend Iklan Bulan Ini -->
        <div class="col-6">
            <div class="dashboard-card h-100 p-3 d-flex flex-column justify-content-between" style="min-height: 115px;">
                <div class="card-icon-bg text-warning">
                    <i class="fas fa-ad"></i>
                </div>
                <div>
                    <span class="card-label">Biaya Iklan (Spend)</span>
                    <h3 class="card-value text-warning" style="color: #d97706 !important;">
                        Rp {{ number_format($monthSpend, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="card-footer-text text-warning d-flex align-items-center gap-1" style="color: #d97706 !important;">
                    <i class="fas fa-wallet"></i> Bulan ini
                </div>
            </div>
        </div>

        <!-- ROAS Iklan Bulan Ini -->
        <div class="col-6">
            <div class="dashboard-card h-100 p-3 d-flex flex-column justify-content-between" style="min-height: 115px;">
                <div class="card-icon-bg text-success">
                    <i class="fas fa-crosshairs"></i>
                </div>
                <div>
                    <span class="card-label">Rata-Rata ROAS</span>
                    <h3 class="card-value text-success" style="color: #059669 !important;">
                        {{ number_format($monthRoas, 2) }}x
                    </h3>
                </div>
                <div class="card-footer-text text-success d-flex align-items-center gap-1" style="color: #059669 !important;">
                    <i class="fas fa-bullseye"></i> ROI Target
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Card -->
    <div class="dashboard-card mb-4 overflow-hidden">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fas fa-shopping-bag text-primary"></i> Pesanan Terbaru
            </h6>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size: 0.65rem; font-weight: 700;">Limit 5</span>
        </div>

        <div class="card-body p-3">
            <div class="d-flex flex-column gap-3">
                @forelse($recentOrders as $order)
                    <div class="list-item-custom">
                        <div>
                            <div class="fw-bold text-dark small">{{ $order->buyer_name }}</div>
                            <div class="d-flex gap-1 align-items-center mt-1">
                                <span class="badge badge-premium badge-light-grey">
                                    {{ $order->store->store_name }}
                                </span>
                                <span class="text-muted small">•</span>
                                <span class="text-muted small" style="font-size: 0.7rem;">
                                    {{ $order->order_date->format('H:i') }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success small">
                                Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                            </div>
                            @php
                                $badgeClass = 'badge-light-grey';
                                if ($order->order_status === 'COMPLETED' || $order->order_status === 'DELIVERED') {
                                    $badgeClass = 'badge-success-light';
                                } elseif ($order->order_status === 'READY_TO_SHIP' || $order->order_status === 'SHIPPED') {
                                    $badgeClass = 'badge-indigo-light';
                                } elseif ($order->order_status === 'CANCELLED') {
                                    $badgeClass = 'badge-danger-light';
                                }
                            @endphp
                            <span class="badge badge-premium {{ $badgeClass }} mt-1 d-inline-block">
                                {{ str_replace('_', ' ', $order->order_status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="fas fa-check-circle text-success opacity-50 fs-4 mb-2 d-block"></i>
                        Tidak ada pesanan masuk hari ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Low Stock Warnings Card -->
    <div class="dashboard-card mb-4 overflow-hidden">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-danger d-flex align-items-center gap-2">
                <i class="fas fa-circle-exclamation text-danger"></i> Peringatan Stok Limit
            </h6>
        </div>

        <div class="card-body p-3">
            <div class="d-flex flex-column gap-3">
                @forelse($lowStockProducts as $product)
                    <div class="list-item-custom">
                        <div>
                            <div class="fw-bold text-dark small">{{ $product->name }}</div>
                            <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">
                                SKU: <code class="font-monospace text-primary bg-light px-1 py-0.5 rounded">{{ $product->sku }}</code>
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-premium badge-danger-light">
                                Stok: {{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                            </span>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                Min: {{ $product->min_stock }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">
                        <i class="fas fa-circle-check text-success opacity-50 fs-4 mb-2 d-block"></i>
                        Semua stok produk aman!
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
