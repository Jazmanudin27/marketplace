@extends('layouts.app_v2')

@section('title', 'Dashboard V2 Next-Gen')

@section('content')
<!-- Page Header -->
<div class="v2-page-header">
    <div>
        <h1 class="v2-page-title">Dashboard Overview V2</h1>
        <p class="v2-page-subtitle">Selamat datang di antarmuka ERP Next-Gen terbaru. Pantau ringkasan performa toko dan order real-time.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="btn-v2-secondary">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Tampilan Lama (V1)
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
                    <i class="bi bi-sparkles me-1 text-warning"></i> DESAIN UI BARU V2
                </span>
                <h3 class="fw-bold mb-2 text-white" style="font-family: 'Outfit', sans-serif;">Tampilan ERP Baru Dari Scratch</h3>
                <p class="mb-0 text-white text-opacity-80" style="max-width: 620px; font-size: 0.9rem;">
                    Tampilan ini dirancang ulang dari awal dengan sistem desain V2 yang modern, bersih, dan cepat. Seluruh file lama Anda tetap utuh 100% dan aman tanpa ada yang terhapus!
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

<!-- Main Row: Quick Shortcuts & Stores Overview -->
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
                    <!-- Shopee -->
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

                    <!-- TikTok Shop -->
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

                    <!-- Lazada -->
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
                <h5 class="v2-card-title"><i class="bi bi-lightning-charge me-2 text-warning"></i> Akses Cepat</h5>
            </div>
            <div class="v2-card-body d-flex flex-column gap-2">
                <a href="{{ route('orders.index') }}" class="btn btn-v2-secondary justify-content-start py-2">
                    <i class="bi bi-cart-check text-primary fs-5"></i>
                    <div class="text-start">
                        <div class="fw-bold">Pesanan Marketplace</div>
                        <small class="text-muted">Cek & proses pesanan toko</small>
                    </div>
                </a>

                @if(Route::has('fulfillment.scan'))
                <a href="{{ route('fulfillment.scan') }}" class="btn btn-v2-secondary justify-content-start py-2">
                    <i class="bi bi-qr-code-scan text-success fs-5"></i>
                    <div class="text-start">
                        <div class="fw-bold">Scan Packing & Resi</div>
                        <small class="text-muted">Fulfillment barcode scanner</small>
                    </div>
                </a>
                @endif

                @if(Route::has('inventory.mutations.index'))
                <a href="{{ route('inventory.mutations.index') }}" class="btn btn-v2-secondary justify-content-start py-2">
                    <i class="bi bi-boxes text-info fs-5"></i>
                    <div class="text-start">
                        <div class="fw-bold">Stok & Gudang</div>
                        <small class="text-muted">Kelola mutasi & stok opname</small>
                    </div>
                </a>
                @endif

                <a href="{{ route('reports.index') }}" class="btn btn-v2-secondary justify-content-start py-2">
                    <i class="bi bi-file-earmark-bar-graph text-warning fs-5"></i>
                    <div class="text-start">
                        <div class="fw-bold">Laporan Penjualan</div>
                        <small class="text-muted">Ekspor data & rekapan keuangan</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="v2-card">
    <div class="v2-card-header">
        <h5 class="v2-card-title"><i class="bi bi-clock-history me-2 text-info"></i> Pesanan Terbaru</h5>
        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">Lihat Semua Order &rarr;</a>
    </div>
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive border-0">
            <table class="v2-table">
                <thead>
                    <tr>
                        <th>Channel & Order ID</th>
                        <th>Tanggal</th>
                        <th>Nama Pembeli</th>
                        <th>Total Belanja</th>
                        <th>Status Pesanan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders ?? [] as $order)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if(strtolower($order->channel ?? '') == 'shopee')
                                        <span class="badge bg-danger">Shopee</span>
                                    @elseif(strtolower($order->channel ?? '') == 'tiktok')
                                        <span class="badge bg-dark">TikTok</span>
                                    @else
                                        <span class="badge bg-primary">{{ ucfirst($order->channel ?? 'Marketplace') }}</span>
                                    @endif
                                    <span class="fw-bold">{{ $order->order_number ?? $order->order_id }}</span>
                                </div>
                            </td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order->buyer_name ?? 'Pelanggan Marketplace' }}</div>
                                <small class="text-muted">{{ $order->store->name ?? '-' }}</small>
                            </td>
                            <td class="fw-bold">Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @if(in_array(strtolower($order->status ?? ''), ['completed', 'finished', 'selesai']))
                                    <span class="v2-badge v2-badge-success"><i class="bi bi-check-circle"></i> Selesai</span>
                                @elseif(in_array(strtolower($order->status ?? ''), ['unpaid', 'belum bayar']))
                                    <span class="v2-badge v2-badge-warning"><i class="bi bi-clock"></i> Belum Bayar</span>
                                @elseif(in_array(strtolower($order->status ?? ''), ['cancelled', 'batal']))
                                    <span class="v2-badge v2-badge-danger"><i class="bi bi-x-circle"></i> Batal</span>
                                @else
                                    <span class="v2-badge v2-badge-primary"><i class="bi bi-truck"></i> {{ ucfirst($order->status ?? 'Diproses') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('orders.index', ['search' => $order->order_number ?? $order->order_id]) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                                    <i class="bi bi-eye me-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                Belum ada pesanan terbaru saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
