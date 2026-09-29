@extends('v2.layouts.app')

@section('title', 'Toko Terhubung V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-shop text-primary fs-5"></i> Toko Terhubung (Integrasi Marketplace Hub)
        </h1>
        <p class="v2-page-subtitle mb-0">Hubungkan dan kelola sinkronisasi otomatis produk, stok, dan pesanan multi-channel secara terpusat.</p>
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
            <h6 class="alert-heading fw-bold mb-1 text-dark" style="font-size: 0.85rem;">Koneksi Toko Membutuhkan Otorisasi Ulang!</h6>
            <p class="mb-0 text-muted">
                Beberapa toko mengalami masa aktif token kedaluwarsa. Silakan klik tombol 
                <strong class="text-dark">Edit / Relink</strong> untuk menyegarkan token koneksi otorisasi secara aman.
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
                <span class="v2-stat-lbl">Total Toko Aktif</span>
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
                <span class="v2-stat-lbl">Koneksi Terhubung Live</span>
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
                <span class="v2-stat-lbl">Butuh Otorisasi Ulang</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar Compact -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2.5 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-1.5 overflow-auto w-100 w-md-auto py-1">
            <button type="button" class="btn btn-sm btn-v2-primary py-1 px-3 filter-channel-btn active" data-channel="all">
                Semua Channel ({{ $totalCount }})
            </button>
            <button type="button" class="btn btn-sm btn-v2-secondary py-1 px-2.5 filter-channel-btn" data-channel="shopee">
                <i class="bi bi-bag-fill me-1 text-danger"></i> Shopee
            </button>
            <button type="button" class="btn btn-sm btn-v2-secondary py-1 px-2.5 filter-channel-btn" data-channel="tiktok">
                <i class="bi bi-tiktok me-1"></i> TikTok Shop
            </button>
            <button type="button" class="btn btn-sm btn-v2-secondary py-1 px-2.5 filter-channel-btn" data-channel="tokopedia">
                <i class="bi bi-shop me-1 text-success"></i> Tokopedia
            </button>
            <button type="button" class="btn btn-sm btn-v2-secondary py-1 px-2.5 filter-channel-btn" data-channel="lazada">
                <i class="bi bi-bag me-1 text-primary"></i> Lazada
            </button>
        </div>
        <div class="w-100 w-md-auto position-relative" style="min-width: 220px;">
            <input type="text" id="searchStoreInput" class="form-control form-control-sm ps-4" placeholder="Cari toko / Shop ID...">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted" style="font-size: 0.75rem;"></i>
        </div>
    </div>
</div>

<!-- Grid Cards Toko Modern -->
<div class="row g-3 mb-3" id="storeGridContainer">
    @forelse($stores as $store)
        @php
            $chCode = strtolower($store->channel->code ?? '');
            $bannerClass = match($chCode) {
                'shopee'    => 'shopee',
                'tiktok'    => 'tiktok',
                'tokopedia' => 'tokopedia',
                'lazada'    => 'lazada',
                default     => 'default',
            };
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
            $syncBtnClass = match($chCode) {
                'shopee'    => 'btn-sync-shopee',
                'tiktok'    => 'btn-sync-tiktok',
                'tokopedia' => 'btn-sync-tokopedia',
                'lazada'    => 'btn-sync-lazada',
                default     => 'btn-sync-default',
            };
            $linkedCount = $store->marketplaceProducts ? $store->marketplaceProducts->count() : 0;
        @endphp
        <div class="col-12 col-sm-6 col-lg-4 store-card-item" data-channel="{{ $chCode }}" data-name="{{ strtolower($store->store_name) }}" data-shopid="{{ strtolower($store->marketplace_store_id) }}">
            <div class="v2-store-card h-100 d-flex flex-column">
                <!-- Header Top Color Accent Bar -->
                <div class="v2-store-banner {{ $bannerClass }}"></div>

                <div class="p-3.5 d-flex flex-column h-100">
                    
                    <!-- Header Card Toko: Logo, Title, Status -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            @if ($store->logo_url)
                                <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="rounded-3 border shadow-sm flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                            @else
                                <div class="rounded-3 d-flex align-items-center justify-content-center {{ $logoBgClass }} shadow-sm flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                    <i class="{{ $iconClass }}"></i>
                                </div>
                            @endif
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">{{ $store->channel->name ?? 'Marketplace' }}</h6>
                                <span class="text-muted" style="font-size: 0.68rem;"><i class="bi bi-shield-check text-success me-1"></i>Official Channel</span>
                            </div>
                        </div>

                        <div>
                            @if ($store->status === 'connected')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <span class="pulse-dot-green me-1"></span>Terhubung
                                </span>
                            @elseif ($store->status === 'expired')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Expired
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.68rem;">
                                    <i class="bi bi-x-circle me-1"></i>Terputus
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Store Name & Shop ID Code -->
                    <div class="mb-3">
                        <h5 class="fw-bold text-dark mb-1" style="font-size: 0.95rem; line-height: 1.25;">{{ $store->store_name }}</h5>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <div class="d-flex align-items-center gap-1.5 text-muted" style="font-size: 0.72rem;">
                                <span>Shop ID / Code:</span>
                                <code class="text-primary font-monospace bg-light px-2 py-0.5 rounded border" style="font-size: 0.7rem;">{{ $store->marketplace_store_id }}</code>
                            </div>
                            <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;" title="Jumlah produk marketplace terhubung">
                                <i class="bi bi-link-45deg me-0.5 text-primary"></i> {{ $linkedCount }} Linked
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons Footer -->
                    <div class="mt-auto pt-2.5 border-top d-flex align-items-center gap-2">
                        @if($chCode === 'shopee')
                            <form action="{{ route('shopee.sync_products', $store->id) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $syncBtnClass }} w-100 py-1.5 px-3 fw-bold d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-repeat"></i> Sync Produk
                                </button>
                            </form>
                        @elseif($chCode === 'tiktok' || $chCode === 'tokopedia')
                            <form action="{{ route('tiktok.sync_products', $store->id) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $syncBtnClass }} w-100 py-1.5 px-3 fw-bold d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-repeat"></i> Sync Produk
                                </button>
                            </form>
                        @elseif($chCode === 'lazada')
                            <form action="{{ route('lazada.sync_products', $store->id) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $syncBtnClass }} w-100 py-1.5 px-3 fw-bold d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-repeat"></i> Sync Produk
                                </button>
                            </form>
                        @else
                            <a href="{{ url('/v2/produk') }}" class="btn btn-sm {{ $syncBtnClass }} flex-grow-1 py-1.5 px-3 fw-bold d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;">
                                <i class="bi bi-box-seam"></i> Lihat Produk
                            </a>
                        @endif

                        <a href="{{ url('/v2/toko/' . $store->id . '/edit') }}" class="btn-action-icon btn-action-edit shadow-sm" title="Pengaturan Toko">
                            <i class="bi bi-gear-fill"></i>
                        </a>

                        <form action="{{ url('/v2/toko/' . $store->id) }}" method="POST" class="d-inline confirm-delete-store m-0 p-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action-icon btn-action-delete shadow-sm" title="Hapus Toko" onclick="return confirm('Apakah Anda yakin ingin menghapus toko ini?')">
                                <i class="bi bi-trash-fill text-white"></i>
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

@push('scripts')
<script>
$(document).ready(function() {
    // Channel Filter Buttons
    $('.filter-channel-btn').on('click', function() {
        $('.filter-channel-btn').removeClass('active btn-v2-primary').addClass('btn-v2-secondary');
        $(this).addClass('active btn-v2-primary').removeClass('btn-v2-secondary');

        const channel = $(this).data('channel');
        filterGrid();
    });

    // Live Search Input
    $('#searchStoreInput').on('keyup', function() {
        filterGrid();
    });

    function filterGrid() {
        const activeChannel = $('.filter-channel-btn.active').data('channel') || 'all';
        const searchVal = $('#searchStoreInput').val().toLowerCase().trim();

        $('.store-card-item').each(function() {
            const itemChannel = $(this).data('channel');
            const itemName = $(this).data('name') || '';
            const itemShopId = $(this).data('shopid') || '';

            const matchesChannel = (activeChannel === 'all') || (itemChannel === activeChannel);
            const matchesSearch = (searchVal === '') || (itemName.indexOf(searchVal) !== -1) || (itemShopId.indexOf(searchVal) !== -1);

            if (matchesChannel && matchesSearch) {
                $(this).removeClass('d-none');
            } else {
                $(this).addClass('d-none');
            }
        });
    }
});
</script>
@endpush
