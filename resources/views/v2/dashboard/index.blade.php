@extends('v2.layouts.app')

@section('title', 'Dashboard Executive V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-speedometer2 text-primary fs-5"></i> Dashboard Executive & Analytics
        </h1>
        <p class="v2-page-subtitle mb-0">Ringkasan performa toko marketplace, omset penjualan, dan stok barang terintegrasi</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/dashboard') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Dashboard">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <a href="{{ Route::has('orders.index') ? route('orders.index') : url('/orders') }}" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm">
            <i class="bi bi-cart-plus me-1"></i> Lihat Semua Pesanan
        </a>
    </div>
</div>

<!-- Executive Top KPI Stat Cards (4 Gradient Cards) -->
<div class="row g-3 mb-3">
    <!-- Stat 1: Omset Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-card mb-0 h-100 overflow-hidden position-relative border-0 shadow-sm" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff;">
            <div class="v2-card-body p-3 d-flex flex-column justify-content-between position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-white-50 fw-bold uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">PENJUALAN HARI INI</span>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                        <i class="bi bi-graph-up-arrow me-1"></i>Live
                    </span>
                </div>
                <div class="my-2">
                    <div class="font-monospace fw-bold" style="font-size: 1.45rem; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        Rp {{ number_format($stats['today_sales'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="text-white-50 mt-1" style="font-size: 0.72rem;">
                        Total dari {{ number_format($stats['today_orders'] ?? 0) }} pesanan masuk
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between border-top border-white border-opacity-20 pt-2 mt-1" style="font-size: 0.7rem;">
                    <span class="text-white-50">Omset Bulan Ini:</span>
                    <span class="fw-bold">Rp {{ number_format($stats['monthly_sales'] ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
            <!-- Watermark Icon -->
            <i class="bi bi-wallet2 position-absolute" style="right: -10px; bottom: -15px; font-size: 5.5rem; color: rgba(255, 255, 255, 0.12); pointer-events: none; transform: rotate(-10deg);"></i>
        </div>
    </div>

    <!-- Stat 2: Total Pesanan Masuk -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-card mb-0 h-100 overflow-hidden position-relative border-0 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff;">
            <div class="v2-card-body p-3 d-flex flex-column justify-content-between position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-white-50 fw-bold uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">TOTAL PESANAN BULAN INI</span>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                        <i class="bi bi-bag-check me-1"></i>Bulan Ini
                    </span>
                </div>
                <div class="my-2">
                    <div class="font-monospace fw-bold" style="font-size: 1.45rem; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        {{ number_format($stats['monthly_orders'] ?? 0) }} <span style="font-size: 0.85rem; font-weight: 500;">Order</span>
                    </div>
                    <div class="text-white-50 mt-1" style="font-size: 0.72rem;">
                        Rata-rata {{ $stats['monthly_orders'] > 0 ? number_format($stats['monthly_sales'] / max(1, $stats['monthly_orders']), 0, ',', '.') : 0 }} / order
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between border-top border-white border-opacity-20 pt-2 mt-1" style="font-size: 0.7rem;">
                    <span class="text-white-50">Pesanan Hari Ini:</span>
                    <span class="fw-bold">{{ number_format($stats['today_orders'] ?? 0) }} Trx</span>
                </div>
            </div>
            <!-- Watermark Icon -->
            <i class="bi bi-cart-check position-absolute" style="right: -10px; bottom: -15px; font-size: 5.5rem; color: rgba(255, 255, 255, 0.12); pointer-events: none; transform: rotate(-10deg);"></i>
        </div>
    </div>

    <!-- Stat 3: Total Master Produk -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-card mb-0 h-100 overflow-hidden position-relative border-0 shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff;">
            <div class="v2-card-body p-3 d-flex flex-column justify-content-between position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-white-50 fw-bold uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">KATALOG PRODUK</span>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                        <i class="bi bi-boxes me-1"></i>Katalog
                    </span>
                </div>
                <div class="my-2">
                    <div class="font-monospace fw-bold" style="font-size: 1.45rem; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        {{ number_format($stats['total_products'] ?? 0) }} <span style="font-size: 0.85rem; font-weight: 500;">Produk</span>
                    </div>
                    <div class="text-white-50 mt-1" style="font-size: 0.72rem;">
                        Master produk aktif terintegrasi
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between border-top border-white border-opacity-20 pt-2 mt-1" style="font-size: 0.7rem;">
                    <span class="text-white-50">Kelola Katalog:</span>
                    <a href="{{ url('/v2/produk') }}" class="text-white fw-bold text-decoration-underline">Buka Produk V2 &rarr;</a>
                </div>
            </div>
            <!-- Watermark Icon -->
            <i class="bi bi-box-seam position-absolute" style="right: -10px; bottom: -15px; font-size: 5.5rem; color: rgba(255, 255, 255, 0.12); pointer-events: none; transform: rotate(-10deg);"></i>
        </div>
    </div>

    <!-- Stat 4: Stok Menipis / Warning -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-card mb-0 h-100 overflow-hidden position-relative border-0 shadow-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff;">
            <div class="v2-card-body p-3 d-flex flex-column justify-content-between position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-white-50 fw-bold uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">PERINGATAN STOK</span>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>Penting
                    </span>
                </div>
                <div class="my-2">
                    <div class="font-monospace fw-bold" style="font-size: 1.45rem; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        {{ number_format($stats['low_stock_count'] ?? 0) }} <span style="font-size: 0.85rem; font-weight: 500;">Item Low</span>
                    </div>
                    <div class="text-white-50 mt-1" style="font-size: 0.72rem;">
                        Stok di bawah ambang batas minimal
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between border-top border-white border-opacity-20 pt-2 mt-1" style="font-size: 0.7rem;">
                    <span class="text-white-50">Stok Gudang:</span>
                    <a href="{{ Route::has('inventory_items.index') ? route('inventory_items.index') : url('/inventory-items') }}" class="text-white fw-bold text-decoration-underline">Cek Gudang &rarr;</a>
                </div>
            </div>
            <!-- Watermark Icon -->
            <i class="bi bi-exclamation-octagon position-absolute" style="right: -10px; bottom: -15px; font-size: 5.5rem; color: rgba(255, 255, 255, 0.12); pointer-events: none; transform: rotate(-10deg);"></i>
        </div>
    </div>
</div>

<!-- Marketplace Store Hub Integrasi -->
<div class="v2-card mb-3">
    <div class="v2-card-header bg-light">
        <h6 class="v2-card-title d-flex align-items-center gap-2">
            <i class="bi bi-shop text-primary"></i> Status Hub Integrasi Toko Marketplace
        </h6>
        <a href="{{ Route::has('stores.index') ? route('stores.index') : url('/marketplace/stores') }}" class="btn btn-sm btn-link p-0 text-decoration-none" style="font-size: 0.75rem;">Kelola Toko &rarr;</a>
    </div>
    <div class="v2-card-body p-2">
        <div class="row g-2">
            @forelse($connectedStores as $store)
                <div class="col-12 col-md-4 col-xl-3">
                    <div class="p-2 border rounded d-flex align-items-center justify-content-between bg-white shadow-xs">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            @php
                                $chCode = strtolower($store->channel->code ?? '');
                            @endphp
                            @if(str_contains($chCode, 'shopee'))
                                <span class="badge bg-danger text-white p-1 rounded"><i class="bi bi-bag-fill fs-6"></i></span>
                            @elseif(str_contains($chCode, 'tiktok'))
                                <span class="badge bg-dark text-white p-1 rounded"><i class="bi bi-tiktok fs-6"></i></span>
                            @elseif(str_contains($chCode, 'lazada'))
                                <span class="badge bg-primary text-white p-1 rounded"><i class="bi bi-shop fs-6"></i></span>
                            @else
                                <span class="badge bg-secondary text-white p-1 rounded"><i class="bi bi-store fs-6"></i></span>
                            @endif
                            <div class="overflow-hidden">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.78rem;" title="{{ $store->store_name }}">{{ $store->store_name }}</div>
                                <div class="text-muted" style="font-size: 0.68rem;">{{ $store->channel->name ?? 'Marketplace' }}</div>
                            </div>
                        </div>
                        <div>
                            @if($store->status === 'connected')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                    <span class="status-dot active"></span>Connected
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                    Disconnect
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-2 text-muted" style="font-size: 0.78rem;">
                    Belum ada toko marketplace terhubung. <a href="{{ Route::has('stores.index') ? route('stores.index') : url('/marketplace/stores') }}" class="text-primary fw-bold">Hubungkan Toko Sekarang</a>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Main Section: Recent Orders & Low Stock Items -->
<div class="row g-3">
    <!-- Left Column: Recent Orders (70%) -->
    <div class="col-12 col-xl-8">
        <div class="v2-card mb-0 shadow-sm border">
            <div class="v2-card-header bg-light">
                <h6 class="v2-card-title d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-primary"></i> Transaksi & Pesanan Masuk Terkini
                </h6>
                <a href="{{ Route::has('orders.index') ? route('orders.index') : url('/orders') }}" class="btn btn-sm btn-v2-secondary py-0.5 px-2" style="font-size: 0.72rem;">Semua Order</a>
            </div>
            <div class="v2-card-body p-0">
                <div class="v2-table-responsive">
                    <table class="v2-table align-middle">
                        <thead>
                            <tr>
                                <th>NO. ORDER</th>
                                <th>PELANGGAN</th>
                                <th>TOKO / CHANNEL</th>
                                <th class="text-end">TOTAL TRX</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-center">TANGGAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $ord)
                                <tr>
                                    <td>
                                        <code class="text-primary font-monospace fw-bold" style="font-size: 0.75rem;">{{ $ord->order_number ?? $ord->order_sn }}</code>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 0.78rem;">{{ $ord->customer_name ?? 'Pelanggan Marketplace' }}</div>
                                        <div class="text-muted" style="font-size: 0.68rem;">{{ $ord->customer_phone ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.68rem;">
                                            <i class="bi bi-shop me-1 text-primary"></i>{{ $ord->store->store_name ?? 'Toko Direct' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-primary font-monospace" style="font-size: 0.8rem;">
                                            Rp {{ number_format($ord->total_amount ?? 0, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $st = strtolower($ord->order_status ?? 'completed');
                                        @endphp
                                        @if(str_contains($st, 'completed') || str_contains($st, 'finish') || str_contains($st, 'selesai'))
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.68rem;">Selesai</span>
                                        @elseif(str_contains($st, 'paid') || str_contains($st, 'ready') || str_contains($st, 'proses'))
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.68rem;">Diproses</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 0.68rem;">{{ ucfirst($ord->order_status ?? 'Pending') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-muted" style="font-size: 0.72rem;">
                                        {{ \Carbon\Carbon::parse($ord->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                                        Belum ada transaksi terdaftar hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Low Stock Warning (30%) -->
    <div class="col-12 col-xl-4">
        <div class="v2-card mb-0 shadow-sm border h-100">
            <div class="v2-card-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-20">
                <h6 class="v2-card-title d-flex align-items-center gap-2 text-warning-emphasis">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i> Peringatan Stok Menipis
                </h6>
                <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-outline-warning py-0.5 px-2" style="font-size: 0.72rem;">Cek Semua</a>
            </div>
            <div class="v2-card-body p-2">
                <div class="list-group list-group-flush">
                    @forelse($lowStockProducts as $prod)
                        <div class="list-group-item px-1 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <div class="overflow-hidden me-2">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.78rem;" title="{{ $prod->name }}">{{ $prod->name }}</div>
                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                    <span class="sku-badge">{{ $prod->sku }}</span>
                                    <span class="text-muted" style="font-size: 0.68rem;">Min: {{ $prod->min_stock ?? 5 }} {{ $prod->unit }}</span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge bg-danger text-white font-monospace px-2 py-1" style="font-size: 0.75rem;">
                                    {{ $prod->stock }} {{ $prod->unit }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                            <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                            Stok semua barang dalam kondisi aman.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
