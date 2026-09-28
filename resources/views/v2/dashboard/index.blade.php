@extends('v2.layouts.app')

@section('title', 'Dashboard V2 Next-Gen')

@section('content')
<!-- Page Header -->
<div class="v2-page-header">
    <div>
        <h1 class="v2-page-title">Dashboard Overview V2</h1>
        <p class="v2-page-subtitle">Selamat datang di antarmuka ERP Next-Gen V2. Pantau ringkasan performa toko dan order real-time.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="btn-v2-secondary">
            <i class="bi bi-box-arrow-up-right"></i> Tampilan Lama (V1)
        </a>
        <button class="btn-v2-primary" onclick="location.reload()">
            <i class="bi bi-arrow-repeat"></i> Refresh Data
        </button>
    </div>
</div>

<!-- Welcome Banner -->
<div class="v2-card mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #ffffff; border: none;">
    <div class="v2-card-body p-4 position-relative overflow-hidden">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white bg-opacity-20 text-white mb-2 px-3 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-sparkles me-1 text-warning"></i> STRUCTURE V2 ARCHITECTURE
                </span>
                <h3 class="fw-bold mb-2 text-white" style="font-family: 'Outfit', sans-serif;">ERP V2 Clean Architecture</h3>
                <p class="mb-0 text-white text-opacity-80" style="max-width: 620px; font-size: 0.9rem;">
                    URL rapi di <code>/v2/dashboard</code>, Controller di <code>App\Http\Controllers\V2</code>, dan Blade View di <code>resources/views/v2</code>.
                </p>
            </div>
            <div class="col-lg-4 text-end d-none d-lg-block">
                <i class="bi bi-rocket-takeoff text-white opacity-25" style="font-size: 7rem; margin-top: -30px; margin-bottom: -30px; display: inline-block;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Total Omset/Penjualan -->
    <div class="col-xl-3 col-sm-6">
        <div class="v2-card v2-stat-card">
            <div>
                <div class="v2-stat-label">Total Omset Bulan Ini</div>
                <div class="v2-stat-value">Rp {{ number_format($totalOmset ?? 0, 0, ',', '.') }}</div>
                <div class="v2-stat-change up">
                    <i class="bi bi-arrow-up-short"></i> +12.5% vs bulan lalu
                </div>
            </div>
            <div class="v2-stat-icon primary">
                <i class="bi bi-currency-dollar"></i>
            </div>
        </div>
    </div>

    <!-- Stat 2: Total Pesanan -->
    <div class="col-xl-3 col-sm-6">
        <div class="v2-card v2-stat-card">
            <div>
                <div class="v2-stat-label">Total Pesanan</div>
                <div class="v2-stat-value">{{ number_format($totalOrdersCount ?? 0) }} Order</div>
                <div class="v2-stat-change up">
                    <i class="bi bi-arrow-up-short"></i> +8.2% minggu ini
                </div>
            </div>
            <div class="v2-stat-icon success">
                <i class="bi bi-cart-check"></i>
            </div>
        </div>
    </div>

    <!-- Stat 3: Pesanan Perlu Diproses -->
    <div class="col-xl-3 col-sm-6">
        <div class="v2-card v2-stat-card">
            <div>
                <div class="v2-stat-label">Perlu Diproses</div>
                <div class="v2-stat-value">{{ number_format($pendingOrdersCount ?? 0) }}</div>
                <div class="v2-stat-change down">
                    <i class="bi bi-clock-history"></i> Siap Packing
                </div>
            </div>
            <div class="v2-stat-icon warning">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <!-- Stat 4: Produk Aktif Marketplace -->
    <div class="col-xl-3 col-sm-6">
        <div class="v2-card v2-stat-card">
            <div>
                <div class="v2-stat-label">Toko Terhubung</div>
                <div class="v2-stat-value">{{ number_format($totalStoresCount ?? 0) }} Toko</div>
                <div class="v2-stat-change up">
                    <i class="bi bi-check2-all"></i> Shopee, TikTok, Lazada
                </div>
            </div>
            <div class="v2-stat-icon info">
                <i class="bi bi-shop"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Row: Stores Overview & Quick Actions -->
<div class="row g-4 mb-4">
    <!-- Active Channel Stores Overview -->
    <div class="col-lg-8">
        <div class="v2-card h-100">
            <div class="v2-card-header">
                <h5 class="v2-card-title"><i class="bi bi-shop me-2 text-primary"></i> Performa Toko Marketplace</h5>
                <a href="{{ route('stores.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">Kelola Toko &rarr;</a>
            </div>
            <div class="v2-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border h-100" style="background: var(--v2-bg-body);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-warning text-dark fw-bold" style="background: #ee4d2d !important; color: #fff !important;">Shopee</span>
                                <span class="v2-badge v2-badge-success">Aktif</span>
                            </div>
                            <h5 class="fw-bold m-0" style="font-family: 'Outfit', sans-serif;">Rp {{ number_format($shopeeOmset ?? 0, 0, ',', '.') }}</h5>
                            <small class="text-muted d-block mt-1">{{ $shopeeOrdersCount ?? 0 }} Total Order</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border h-100" style="background: var(--v2-bg-body);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-dark text-white fw-bold">TikTok Shop</span>
                                <span class="v2-badge v2-badge-success">Aktif</span>
                            </div>
                            <h5 class="fw-bold m-0" style="font-family: 'Outfit', sans-serif;">Rp {{ number_format($tiktokOmset ?? 0, 0, ',', '.') }}</h5>
                            <small class="text-muted d-block mt-1">{{ $tiktokOrdersCount ?? 0 }} Total Order</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border h-100" style="background: var(--v2-bg-body);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary text-white fw-bold" style="background: #0f146d !important;">Lazada</span>
                                <span class="v2-badge v2-badge-warning">Sync</span>
                            </div>
                            <h5 class="fw-bold m-0" style="font-family: 'Outfit', sans-serif;">Rp {{ number_format($lazadaOmset ?? 0, 0, ',', '.') }}</h5>
                            <small class="text-muted d-block mt-1">{{ $lazadaOrdersCount ?? 0 }} Total Order</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="col-lg-4">
        <div class="v2-card h-100">
            <div class="v2-card-header">
                <h5 class="v2-card-title"><i class="bi bi-lightning-charge me-2 text-warning"></i> Akses V2</h5>
            </div>
            <div class="v2-card-body d-flex flex-column gap-2">
                <a href="{{ url('/v2/produk') }}" class="btn btn-v2-secondary justify-content-start py-2">
                    <i class="bi bi-box-seam text-primary fs-5"></i>
                    <div class="text-start">
                        <div class="fw-bold">Daftar Produk V2</div>
                        <small class="text-muted">Kelola master produk di <code>/v2/produk</code></small>
                    </div>
                </a>

                <a href="{{ route('orders.index') }}" class="btn btn-v2-secondary justify-content-start py-2">
                    <i class="bi bi-cart-check text-success fs-5"></i>
                    <div class="text-start">
                        <div class="fw-bold">Pesanan Marketplace</div>
                        <small class="text-muted">Cek & proses pesanan toko</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
