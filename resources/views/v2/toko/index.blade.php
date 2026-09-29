@extends('v2.layouts.app')

@section('title', 'Toko Terhubung V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-shop text-primary fs-5"></i> Toko Terhubung (Integrasi Marketplace)
        </h1>
        <p class="v2-page-subtitle mb-0">Hubungkan dan sinkronkan produk, stok, dan pesanan secara terpusat dari semua toko Anda.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/toko') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Page">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <a href="{{ url('/v2/toko/create') }}" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Toko Baru
        </a>
    </div>
</div>

<!-- Alerts -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($hasExpiredStores)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-3 p-3 mb-3 rounded-3" style="font-size: 0.78rem;" role="alert">
        <div class="bg-warning bg-opacity-20 text-warning p-2 rounded-3 flex-shrink-0">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        </div>
        <div>
            <h6 class="alert-heading fw-bold mb-1 text-dark" style="font-size: 0.85rem;">Koneksi Toko Membutuhkan Tindakan Anda!</h6>
            <p class="mb-0 text-muted">
                Ada beberapa toko yang token koneksinya telah kedaluwarsa atau terputus. Silakan klik tombol 
                <strong class="text-dark">Hubungkan Ulang</strong> pada toko tersebut agar sinkronisasi produk dan pesanan berjalan otomatis.
            </p>
        </div>
    </div>
@endif

<!-- Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-4">
        <div class="v2-stat-widget widget-blue">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-shop"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($totalCount) }}</span>
                <span class="v2-stat-lbl">Total Toko Master</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="v2-stat-widget widget-green">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($connectedCount) }}</span>
                <span class="v2-stat-lbl">Toko Terhubung</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="v2-stat-widget {{ $expiredCount > 0 ? 'widget-amber' : 'widget-purple' }}">
            <div class="v2-stat-icon-wrapper {{ $expiredCount > 0 ? 'amber' : 'purple' }}">
                <i class="bi bi-exclamation-circle-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($expiredCount) }}</span>
                <span class="v2-stat-lbl">Butuh Koneksi Ulang</span>
            </div>
        </div>
    </div>
</div>

<!-- Grid Cards Toko -->
<div class="row g-3 mb-3">
    @forelse($stores as $store)
        @php
            $chCode = strtolower($store->channel->code ?? '');
            $logoBgClass = match($chCode) {
                'shopee'    => 'bg-danger text-white',
                'tiktok'    => 'bg-dark text-white',
                'tokopedia' => 'bg-success text-white',
                'lazada'    => 'bg-primary text-white',
                default     => 'bg-secondary text-white',
            };
            $iconClass = match($chCode) {
                'shopee'    => 'bi bi-bag-fill',
                'tiktok'    => 'bi bi-tiktok',
                'tokopedia' => 'bi bi-shop',
                'lazada'    => 'bi bi-bag',
                default     => 'bi bi-globe',
            };
        @endphp
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="v2-card h-100 shadow-sm border">
                <div class="v2-card-body p-3 d-flex flex-column h-100">
                    
                    <!-- Header Card Toko -->
                    <div class="d-flex justify-content-between align-items-start mb-2.5">
                        <div class="d-flex align-items-center gap-2.5">
                            @if ($store->logo_url)
                                <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="rounded-3 border shadow-sm flex-shrink-0" style="width: 42px; height: 42px; object-fit: cover;">
                            @else
                                <div class="rounded-3 d-flex align-items-center justify-content-center {{ $logoBgClass }} shadow-sm flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.2rem;">
                                    <i class="{{ $iconClass }}"></i>
                                </div>
                            @endif
                            <div>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;">{{ $store->channel->name ?? 'Marketplace' }}</h6>
                                <span class="text-muted" style="font-size: 0.68rem;">Official Channel</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-1.5">
                            @if ($store->status === 'connected')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Terhubung
                                </span>
                            @elseif ($store->status === 'expired')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Expired
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                    <i class="bi bi-x-circle me-1"></i>Terputus
                                </span>
                            @endif
                            
                            <a href="{{ url('/v2/toko/' . $store->id . '/edit') }}" class="btn-action-icon btn-action-edit" title="Pengaturan Toko">
                                <i class="bi bi-gear"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Store Name & ID -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.92rem;">{{ $store->store_name }}</h6>
                        <div class="d-flex align-items-center gap-1.5 text-muted" style="font-size: 0.72rem;">
                            <span>Shop ID / Code:</span>
                            <code class="text-primary font-monospace bg-light px-1.5 py-0.5 rounded border">{{ $store->marketplace_store_id }}</code>
                        </div>
                    </div>

                    <!-- Action Buttons Footer -->
                    <div class="mt-auto pt-2 border-top d-flex gap-2">
                        @if($chCode === 'shopee')
                            <form action="{{ route('shopee.sync_products', $store->id) }}" method="POST" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 py-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i> Sync Produk
                                </button>
                            </form>
                        @elseif($chCode === 'tiktok' || $chCode === 'tokopedia')
                            <form action="{{ route('tiktok.sync_products', $store->id) }}" method="POST" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-dark w-100 py-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i> Sync Produk
                                </button>
                            </form>
                        @elseif($chCode === 'lazada')
                            <form action="{{ route('lazada.sync_products', $store->id) }}" method="POST" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100 py-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i> Sync Produk
                                </button>
                            </form>
                        @else
                            <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-v2-secondary w-100 py-1 justify-content-center" style="font-size: 0.72rem;">
                                <i class="bi bi-box-seam me-1"></i> Lihat Produk
                            </a>
                        @endif

                        <form action="{{ url('/v2/toko/' . $store->id) }}" method="POST" class="d-inline confirm-delete-store">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action-icon btn-action-delete py-1 px-2" style="width: auto; height: 100%;" title="Hapus Toko" onclick="return confirm('Apakah Anda yakin ingin menghapus toko ini?')">
                                <i class="bi bi-trash text-white"></i>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="v2-card text-center py-5">
                <i class="bi bi-shop fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <h6 class="fw-bold text-dark mb-1">Belum Ada Toko Terhubung</h6>
                <p class="text-muted small mb-3">Klik tombol di bawah ini untuk menghubungkan toko marketplace pertama Anda ke ERP.</p>
                <a href="{{ url('/v2/toko/create') }}" class="btn btn-sm btn-v2-primary py-1.5 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Toko Sekarang
                </a>
            </div>
        </div>
    @endforelse
</div>
@endsection
