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
        <a href="{{ Route::has('v2.pesanan.index') ? route('v2.pesanan.index') : (Route::has('orders.index') ? route('orders.index') : url('/orders')) }}" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm">
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
<!-- Grafik Analitik Omset Penjualan & Filter (Bulan, Tahun, Toko) -->
<div class="v2-card mb-3 shadow-sm border rounded-3 bg-white">
    <div class="v2-card-header bg-white py-3 px-3.5 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="v2-card-title d-flex align-items-center gap-2 mb-0 text-dark fw-bold">
                <i class="bi bi-graph-up-arrow text-primary fs-5"></i> Grafik Omset Penjualan &amp; Analytics
            </h6>
            <small class="text-muted" style="font-size: 11px;">Tren penjualan harian berdasarkan filter bulan, tahun, dan toko marketplace</small>
        </div>

        {{-- Filter Form --}}
        <form method="GET" action="{{ url('/v2/dashboard') }}" class="d-flex align-items-center gap-2 flex-wrap m-0">
            {{-- Filter Bulan --}}
            <div class="d-flex align-items-center gap-1">
                <label class="form-label form-label-sm fw-semibold mb-0 text-muted" style="font-size: 11px;">Bulan:</label>
                @php
                    $monthsMap = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                <select name="month" class="form-select form-select-sm fw-semibold text-dark no-select2" style="font-size: 11.5px; width: 120px;">
                    @foreach($monthsMap as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ (int)$selectedMonth === $mNum ? 'selected' : '' }}>
                            {{ $mName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Tahun --}}
            <div class="d-flex align-items-center gap-1">
                <label class="form-label form-label-sm fw-semibold mb-0 text-muted" style="font-size: 11px;">Tahun:</label>
                <select name="year" class="form-select form-select-sm fw-semibold text-dark no-select2" style="font-size: 11.5px; width: 95px;">
                    @foreach($availableYears as $yVal)
                        <option value="{{ $yVal }}" {{ (int)$selectedYear === (int)$yVal ? 'selected' : '' }}>
                            {{ $yVal }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Toko --}}
            <div class="d-flex align-items-center gap-1">
                <label class="form-label form-label-sm fw-semibold mb-0 text-muted" style="font-size: 11px;">Toko:</label>
                <select name="store_id" class="form-select form-select-sm fw-semibold text-dark no-select2" style="font-size: 11.5px; max-width: 170px;">
                    <option value="">-- Semua Toko --</option>
                    @foreach($connectedStores as $st)
                        <option value="{{ $st->id }}" {{ (string)$selectedStore === (string)$st->id ? 'selected' : '' }}>
                            {{ $st->store_name }} ({{ $st->channel->name ?? 'Marketplace' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-sm btn-primary py-1 px-3 fw-bold shadow-2xs">
                <i class="bi bi-filter me-1"></i>Filter
            </button>
            @if(request()->has('month') || request()->has('year') || request()->has('store_id'))
                <a href="{{ url('/v2/dashboard') }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Reset Filter">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </form>
    </div>

    <div class="v2-card-body p-3">
        {{-- Periode KPI Summary Strip --}}
        <div class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center gap-2.5">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing: 0.5px;">Total Omset Periode Ini</span>
                        <h6 class="fw-extrabold text-primary font-monospace mb-0" style="font-size: 1.15rem;">
                            Rp {{ number_format($periodTotalSales, 0, ',', '.') }}
                        </h6>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center gap-2.5">
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded-3">
                        <i class="bi bi-bag-check-fill fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing: 0.5px;">Total Volume Pesanan</span>
                        <h6 class="fw-extrabold text-success font-monospace mb-0" style="font-size: 1.15rem;">
                            {{ number_format($periodTotalOrders) }} <small class="fs-6 fw-normal text-muted">Order</small>
                        </h6>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center gap-2.5">
                    <div class="p-2 bg-warning bg-opacity-10 text-warning-emphasis rounded-3">
                        <i class="bi bi-speedometer fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing: 0.5px;">Rata-rata Harian</span>
                        <h6 class="fw-extrabold text-dark font-monospace mb-0" style="font-size: 1.15rem;">
                            Rp {{ number_format($periodTotalSales / max(1, count($chartLabels)), 0, ',', '.') }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>

        {{-- Canvas Container --}}
        <div style="position: relative; height: 280px; width: 100%;">
            <canvas id="omsetChartCanvas"></canvas>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('omsetChartCanvas');
    if (!ctx) return;

    const labels = @json($chartLabels);
    const salesData = @json($chartSalesData);
    const ordersData = @json($chartOrdersData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Omset Penjualan (Rp)',
                    data: salesData,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#0284c7',
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    yAxisID: 'y'
                },
                {
                    label: 'Jumlah Pesanan (Trx)',
                    data: ordersData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.0)',
                    borderWidth: 2,
                    borderDash: [4, 4],
                    fill: false,
                    tension: 0.3,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        font: {
                            size: 11,
                            family: "'Inter', sans-serif"
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.datasetIndex === 0) {
                                label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            } else {
                                label += new Intl.NumberFormat('id-ID').format(context.raw) + ' Trx';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: { size: 10 },
                        callback: function (value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(0) + 'k';
                            }
                            return 'Rp ' + value;
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        font: { size: 10 },
                        callback: function (value) {
                            return value + ' Trx';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

