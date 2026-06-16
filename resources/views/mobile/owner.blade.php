@extends('layouts.mobile')

@section('title', 'Owner Dashboard')
@section('header-title', 'Owner Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <!-- Today Revenue -->
    <div class="col-6">
        <div class="glass-card p-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-left: 4px solid var(--accent-green);">
            <div>
                <span class="text-muted" style="font-size: 0.75rem;">Omset Hari Ini</span>
                <h3 class="mt-1 mb-0" style="font-size: 1.15rem; font-weight: 700; color: #10b981;">
                    Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                </h3>
            </div>
            <div class="mt-2 text-end text-muted" style="font-size: 0.65rem;">
                <i class="fas fa-calendar-day text-success"></i> Realtime
            </div>
            <!-- Glow background -->
            <div style="position: absolute; right: -20px; bottom: -20px; font-size: 4rem; opacity: 0.04; color: #10b981;">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- Month Revenue -->
    <div class="col-6">
        <div class="glass-card p-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-left: 4px solid var(--accent-blue);">
            <div>
                <span class="text-muted" style="font-size: 0.75rem;">Omset Bulan Ini</span>
                <h3 class="mt-1 mb-0" style="font-size: 1.15rem; font-weight: 700; color: var(--accent-blue);">
                    Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                </h3>
            </div>
            <div class="mt-2 text-end text-muted" style="font-size: 0.65rem;">
                <i class="fas fa-chart-bar text-info"></i> Target aktif
            </div>
            <div style="position: absolute; right: -20px; bottom: -20px; font-size: 4rem; opacity: 0.04; color: var(--accent-blue);">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- Stock Valuation -->
    <div class="col-6">
        <div class="glass-card p-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-left: 4px solid #818cf8;">
            <div>
                <span class="text-muted" style="font-size: 0.75rem;">Nilai Total Stok</span>
                <h3 class="mt-1 mb-0" style="font-size: 1.15rem; font-weight: 700; color: #a5b4fc;">
                    Rp {{ number_format($totalStockValue, 0, ',', '.') }}
                </h3>
            </div>
            <div class="mt-2 text-end text-muted" style="font-size: 0.65rem;">
                <i class="fas fa-boxes text-indigo" style="color:#818cf8;"></i> HPP Aset
            </div>
            <div style="position: absolute; right: -20px; bottom: -20px; font-size: 4rem; opacity: 0.04; color: #818cf8;">
                <i class="fas fa-archive"></i>
            </div>
        </div>
    </div>

    <!-- Low Stock Count -->
    <div class="col-6">
        <div class="glass-card p-3 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-left: 4px solid var(--accent-red);">
            <div>
                <span class="text-muted" style="font-size: 0.75rem;">Stok Menipis</span>
                <h3 class="mt-1 mb-0" style="font-size: 1.15rem; font-weight: 700; color: var(--accent-red);">
                    {{ $lowStockCount }} <span style="font-size:0.8rem; font-weight:500; color:var(--text-muted);">Produk</span>
                </h3>
            </div>
            <div class="mt-2 text-end text-muted" style="font-size: 0.65rem;">
                <i class="fas fa-exclamation-triangle text-danger"></i> Butuh Produksi
            </div>
            <div style="position: absolute; right: -20px; bottom: -20px; font-size: 4rem; opacity: 0.04; color: var(--accent-red);">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Card -->
<div class="glass-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
            <i class="fas fa-shopping-bag me-1"></i> Pesanan Terbaru
        </h4>
        <span class="badge bg-secondary" style="font-size: 0.65rem;">Limit 5</span>
    </div>
    
    <div class="d-flex flex-column gap-3">
        @forelse($recentOrders as $order)
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600;">{{ $order->buyer_name }}</div>
                    <div class="d-flex gap-1 align-items-center mt-1" style="font-size: 0.7rem; color: var(--text-muted);">
                        <span class="badge" style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid rgba(255, 255, 255, 0.1);">
                            {{ $order->store->store_name }}
                        </span>
                        <span>•</span>
                        <span>{{ $order->order_date->format('H:i') }}</span>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--accent-green);">
                        Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                    </div>
                    <span class="badge status-badge mt-1" style="font-size: 0.55rem; padding: 2px 6px; background: rgba(16, 185, 129, 0.08); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);">
                        {{ $order->order_status }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-3 text-muted" style="font-size: 0.8rem;">
                Tidak ada pesanan masuk hari ini.
            </div>
        @endforelse
    </div>
</div>

<!-- Low Stock Warnings Card -->
<div class="glass-card p-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-exclamation-triangle me-1"></i> Peringatan Stok Limit
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($lowStockProducts as $product)
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600;">{{ $product->name }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);" class="mt-0.5">
                        SKU: <span class="mono">{{ $product->sku }}</span>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--accent-red);">
                        Stok: {{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                    </div>
                    <div style="font-size: 0.65rem; color: var(--text-muted);">
                        Min: {{ $product->min_stock }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-3 text-muted" style="font-size: 0.8rem;">
                Semua stok produk aman!
            </div>
        @endforelse
    </div>
</div>
@endsection
