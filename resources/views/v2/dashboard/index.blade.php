@extends('v2.layouts.app')

@section('title', 'Dashboard Portal V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-grid text-primary fs-5"></i> Dashboard Overview V2
        </h1>
        <p class="v2-page-subtitle">Selamat datang di Portal ERP V2. Pantau ringkasan performa toko dan pesanan secara ringkas & cepat.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="btn-v2-secondary">
            <i class="bi bi-box-arrow-up-right"></i> Mode ERP V1
        </a>
        <button class="btn-v2-primary" onclick="location.reload()">
            <i class="bi bi-arrow-repeat me-1"></i> Refresh Data
        </button>
    </div>
</div>

<!-- Stat Cards Grid Compact -->
<div class="row g-2 mb-3">
    <!-- Stat 1: Total Omset -->
    <div class="col-xl-3 col-sm-6">
        <div class="v2-card v2-stat-card">
            <div>
                <div class="v2-stat-label">TOTAL OMSET BULAN INI</div>
                <div class="v2-stat-value text-primary">Rp {{ number_format($totalOmset ?? 0, 0, ',', '.') }}</div>
                <small class="text-success fw-semibold" style="font-size: 0.68rem;"><i class="bi bi-arrow-up-short"></i> +12.5% vs bulan lalu</small>
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
                <div class="v2-stat-label">TOTAL PESANAN</div>
                <div class="v2-stat-value">{{ number_format($totalOrdersCount ?? 0) }} Order</div>
                <small class="text-success fw-semibold" style="font-size: 0.68rem;"><i class="bi bi-arrow-up-short"></i> +8.2% minggu ini</small>
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
                <div class="v2-stat-label">PERLU DIPROSES</div>
                <div class="v2-stat-value text-warning">{{ number_format($pendingOrdersCount ?? 0) }}</div>
                <small class="text-warning fw-semibold" style="font-size: 0.68rem;"><i class="bi bi-clock-history"></i> Siap Packing</small>
            </div>
            <div class="v2-stat-icon warning">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <!-- Stat 4: Toko Terhubung -->
    <div class="col-xl-3 col-sm-6">
        <div class="v2-card v2-stat-card">
            <div>
                <div class="v2-stat-label">TOKO TERHUBUNG</div>
                <div class="v2-stat-value">{{ number_format($totalStoresCount ?? 0) }} Toko</div>
                <small class="text-info fw-semibold" style="font-size: 0.68rem;">Shopee, TikTok, Lazada</small>
            </div>
            <div class="v2-stat-icon info">
                <i class="bi bi-shop"></i>
            </div>
        </div>
    </div>
</div>

<!-- Stores Overview & Recent Orders Table -->
<div class="row g-3 mb-3">
    <!-- Active Channel Stores Overview -->
    <div class="col-lg-7">
        <div class="v2-card h-100">
            <div class="v2-card-header">
                <h5 class="v2-card-title"><i class="bi bi-shop me-1 text-primary"></i> Performa Toko Marketplace</h5>
                <a href="{{ route('stores.index') }}" class="btn btn-sm btn-link p-0 text-primary text-decoration-none fw-semibold" style="font-size: 0.75rem;">Kelola Toko &rarr;</a>
            </div>
            <div class="v2-card-body p-2">
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="p-2 rounded-2 border h-100" style="background: var(--v2-bg-body);">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-danger fw-bold" style="font-size: 0.65rem;">Shopee</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.6rem;">Aktif</span>
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">Rp {{ number_format($shopeeOmset ?? 0, 0, ',', '.') }}</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $shopeeOrdersCount ?? 0 }} Total Order</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-2 rounded-2 border h-100" style="background: var(--v2-bg-body);">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-dark text-white fw-bold" style="font-size: 0.65rem;">TikTok Shop</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.6rem;">Aktif</span>
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">Rp {{ number_format($tiktokOmset ?? 0, 0, ',', '.') }}</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $tiktokOrdersCount ?? 0 }} Total Order</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-2 rounded-2 border h-100" style="background: var(--v2-bg-body);">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-primary fw-bold" style="font-size: 0.65rem;">Lazada</span>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.6rem;">Sync</span>
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">Rp {{ number_format($lazadaOmset ?? 0, 0, ',', '.') }}</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $lazadaOrdersCount ?? 0 }} Total Order</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts Panel -->
    <div class="col-lg-5">
        <div class="v2-card h-100">
            <div class="v2-card-header">
                <h5 class="v2-card-title"><i class="bi bi-lightning-charge me-1 text-warning"></i> Akses V2</h5>
            </div>
            <div class="v2-card-body p-2 d-flex flex-column gap-2">
                <a href="{{ url('/v2/produk') }}" class="btn btn-v2-secondary justify-content-start py-1 px-2">
                    <i class="bi bi-box-seam text-primary fs-6 me-2"></i>
                    <div class="text-start">
                        <div class="fw-bold" style="font-size: 0.78rem;">Data Master Produk V2</div>
                        <small class="text-muted" style="font-size: 0.68rem;">Tampilan daftar produk serba compact</small>
                    </div>
                </a>

                <a href="{{ route('orders.index') }}" class="btn btn-v2-secondary justify-content-start py-1 px-2">
                    <i class="bi bi-cart-check text-success fs-6 me-2"></i>
                    <div class="text-start">
                        <div class="fw-bold" style="font-size: 0.78rem;">Pesanan Marketplace</div>
                        <small class="text-muted" style="font-size: 0.68rem;">Proses order Shopee, TikTok, Lazada</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Compact Table -->
<div class="v2-card">
    <div class="v2-card-header">
        <h5 class="v2-card-title"><i class="bi bi-clock-history me-1 text-info"></i> Pesanan Terbaru</h5>
        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-link p-0 text-primary text-decoration-none fw-semibold" style="font-size: 0.75rem;">Lihat Semua &rarr;</a>
    </div>
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table">
                <thead>
                    <tr>
                        <th>CHANNEL & ORDER ID</th>
                        <th>TANGGAL</th>
                        <th>NAMA PEMBELI</th>
                        <th class="text-end">TOTAL BELANJA</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center" style="width: 70px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders ?? [] as $order)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if(strtolower($order->channel ?? '') == 'shopee')
                                        <span class="badge bg-danger" style="font-size: 0.65rem;">Shopee</span>
                                    @elseif(strtolower($order->channel ?? '') == 'tiktok')
                                        <span class="badge bg-dark" style="font-size: 0.65rem;">TikTok</span>
                                    @else
                                        <span class="badge bg-primary" style="font-size: 0.65rem;">{{ ucfirst($order->channel ?? 'Marketplace') }}</span>
                                    @endif
                                    <span class="fw-bold text-dark">{{ $order->order_number ?? $order->order_id }}</span>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order->buyer_name ?? 'Pelanggan Marketplace' }}</div>
                                <small class="text-muted" style="font-size: 0.7rem;">{{ $order->store->name ?? '-' }}</small>
                            </td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if(in_array(strtolower($order->status ?? ''), ['completed', 'finished', 'selesai']))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Selesai</span>
                                @elseif(in_array(strtolower($order->status ?? ''), ['unpaid', 'belum bayar']))
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.68rem;">Belum Bayar</span>
                                @elseif(in_array(strtolower($order->status ?? ''), ['cancelled', 'batal']))
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">Batal</span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">{{ ucfirst($order->status ?? 'Diproses') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('orders.index', ['search' => $order->order_number ?? $order->order_id]) }}" class="btn-action-icon btn-action-edit" title="Detail">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">
                                Belum ada data pesanan terbaru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
