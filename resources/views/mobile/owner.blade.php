@extends('layouts.mobile')

@section('title', 'Owner Dashboard')
@section('header-title', 'Owner Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <!-- Today Revenue -->
    <div class="col-6">
        <div class="card border border-start border-4 border-success shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <small class="text-muted d-block">Omset Hari Ini</small>
                    <h3 class="mt-1 mb-0 fw-bold text-success fs-5">
                        Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-2 text-end text-muted small">
                    <i class="fas fa-calendar-day text-success"></i> Realtime
                </div>
            </div>
        </div>
    </div>

    <!-- Month Revenue -->
    <div class="col-6">
        <div class="card border border-start border-4 border-primary shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <small class="text-muted d-block">Omset Bulan Ini</small>
                    <h3 class="mt-1 mb-0 fw-bold text-primary fs-5">
                        Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-2 text-end text-muted small">
                    <i class="fas fa-chart-bar text-primary"></i> Target aktif
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Valuation -->
    <div class="col-6">
        <div class="card border border-start border-4 border-info shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <small class="text-muted d-block">Nilai Total Stok</small>
                    <h3 class="mt-1 mb-0 fw-bold text-info fs-5">
                        Rp {{ number_format($totalStockValue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-2 text-end text-muted small">
                    <i class="fas fa-boxes text-info"></i> HPP Aset
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Count -->
    <div class="col-6">
        <div class="card border border-start border-4 border-danger shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <small class="text-muted d-block">Stok Menipis</small>
                    <h3 class="mt-1 mb-0 fw-bold text-danger fs-5">
                        {{ $lowStockCount }} <span class="text-muted small fw-normal">Produk</span>
                    </h3>
                </div>
                <div class="mt-2 text-end text-muted small">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Butuh Produksi
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Card -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-shopping-bag me-2"></i> Pesanan Terbaru
        </h6>
        <span class="badge bg-secondary">Limit 5</span>
    </div>
    
    <div class="card-body p-3">
        <div class="d-flex flex-column gap-3">
            @forelse($recentOrders as $order)
                <div class="d-flex justify-content-between align-items-center pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <div class="fw-bold text-dark small">{{ $order->buyer_name }}</div>
                        <div class="d-flex gap-1 align-items-center mt-1">
                            <span class="badge bg-light text-dark border">
                                {{ $order->store->store_name }}
                            </span>
                            <span class="text-muted small">•</span>
                            <span class="text-muted small">{{ $order->order_date->format('H:i') }}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success small">
                            Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mt-1 small">
                            {{ $order->order_status }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-3 text-muted small">
                    Tidak ada pesanan masuk hari ini.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Low Stock Warnings Card -->
<div class="card border shadow-sm">
    <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> Peringatan Stok Limit
        </h6>
    </div>
    
    <div class="card-body p-3">
        <div class="d-flex flex-column gap-3">
            @forelse($lowStockProducts as $product)
                <div class="d-flex justify-content-between align-items-center pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <div class="fw-bold text-dark small">{{ $product->name }}</div>
                        <small class="text-muted d-block mt-0.5">
                            SKU: <code class="text-primary font-monospace">{{ $product->sku }}</code>
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-danger">
                            Stok: {{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                        </span>
                        <div class="text-muted small mt-1">
                            Min: {{ $product->min_stock }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-3 text-muted small">
                    Semua stok produk aman!
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
