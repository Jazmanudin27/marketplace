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
        <div class="col-12 col-md-6 col-xl-4">
            <div class="v2-card h-100 border shadow-sm rounded-3 overflow-hidden d-flex flex-column">
                <!-- Card Header: Channel Logo + Name + Status -->
                <div class="v2-card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        @if ($store->logo_url)
                            <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="rounded-circle border flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                        @else
                            <div class="rounded-circle d-flex align-items-center justify-content-center {{ $logoBgClass }} flex-shrink-0 shadow-sm" style="width: 32px; height: 32px; font-size: 0.95rem;">
                                <i class="{{ $iconClass }}"></i>
                            </div>
                        @endif
                        <div>
                            <span class="fw-bold text-dark d-block" style="font-size: 0.82rem; line-height: 1.2;">{{ $store->channel->name ?? 'Marketplace' }}</span>
                            <span class="text-muted" style="font-size: 0.65rem;">Official Store Channel</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1.5">
                        @if ($store->status === 'connected')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Terhubung
                            </span>
                        @elseif ($store->status === 'expired')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Expired
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                <i class="bi bi-x-circle-fill me-1"></i>Terputus
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Card Body: Store Details & Metrics -->
                <div class="v2-card-body p-3 d-flex flex-column flex-grow-1">
                    <div class="mb-2.5">
                        <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.95rem;" title="{{ $store->store_name }}">
                            {{ $store->store_name }}
                        </h6>
                        <div class="d-flex align-items-center gap-1.5 text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-fingerprint text-primary"></i>
                            <span>Shop ID:</span>
                            <code class="text-secondary font-monospace bg-light px-1.5 py-0.5 rounded border" style="font-size: 0.7rem;">{{ $store->marketplace_store_id }}</code>
                            <button class="btn btn-link btn-sm p-0 border-0 text-muted text-decoration-none ms-1" onclick="copyToClipboard('{{ $store->marketplace_store_id }}', this)" title="Salin ID Toko">
                                <i class="bi bi-copy" style="font-size: 0.72rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Key Metrics Grid -->
                    <div class="row g-1.5 p-2 bg-light rounded-3 border mb-3">
                        <div class="col-4 border-end text-center">
                            <div class="text-muted" style="font-size: 0.62rem;">PRODUK LINKED</div>
                            <div class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;">
                                {{ number_format($store->marketplace_products_count ?? 0) }}
                            </div>
                        </div>
                        <div class="col-4 border-end text-center">
                            <div class="text-muted" style="font-size: 0.62rem;">ORDER MASUK</div>
                            <div class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;">
                                {{ number_format($store->orders_count ?? 0) }}
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="text-muted" style="font-size: 0.62rem;">METODE KURIR</div>
                            <div class="fw-bold text-dark" style="font-size: 0.72rem;">
                                <span class="badge bg-secondary-subtle text-secondary border px-1 py-0.5" style="font-size: 0.6rem;">
                                    {{ $store->shipping_handover_method ?? 'DROP_OFF' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Token Expiry Footer Info -->
                    <div class="mt-auto pt-1">
                        @if($store->status === 'connected' && $store->token_expires_at)
                            <div class="d-flex align-items-center justify-content-between text-muted mb-2" style="font-size: 0.68rem;">
                                <span><i class="bi bi-clock me-1"></i>Token Aktif S/D:</span>
                                <span class="fw-semibold text-dark">{{ $store->token_expires_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons Footer with Spacing & Margins -->
                    <div class="pt-2.5 mt-1 border-top d-flex align-items-center justify-content-between gap-2.5 px-0.5">
                        @if ($store->status === 'connected')
                            @php
                                $syncProductRoute = match($chCode) {
                                    'shopee'    => route('shopee.sync_products', $store->id),
                                    'tiktok'    => route('tiktok.sync_products', $store->id),
                                    'tokopedia' => route('tokopedia.sync_products', $store->id),
                                    'lazada'    => route('lazada.sync_products', $store->id),
                                    default     => null,
                                };
                                $syncOrderRoute = match($chCode) {
                                    'shopee'    => route('shopee.sync_orders', $store->id),
                                    'tiktok'    => route('tiktok.sync_orders', $store->id),
                                    'tokopedia' => route('tokopedia.sync_orders', $store->id),
                                    'lazada'    => route('lazada.sync_orders', $store->id),
                                    default     => null,
                                };
                            @endphp

                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                @if ($syncProductRoute)
                                    <form action="{{ $syncProductRoute }}" method="POST" class="flex-fill m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-v2-primary w-100 py-1.5 px-2.5 justify-content-center rounded-2 shadow-xs" style="font-size: 0.72rem;" title="Tarik Produk dari Toko Ini">
                                            <i class="bi bi-box-arrow-in-down me-1"></i>Tarik Produk
                                        </button>
                                    </form>
                                @endif

                                @if ($syncOrderRoute)
                                    <form action="{{ $syncOrderRoute }}" method="POST" class="flex-fill m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-v2-success w-100 py-1.5 px-2.5 justify-content-center rounded-2 shadow-xs" style="font-size: 0.72rem; background-color: #10b981 !important; border-color: #10b981 !important; color: #ffffff !important;" title="Tarik Pesanan dari Toko Ini">
                                            <i class="bi bi-cart-download me-1"></i>Tarik Order
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @elseif ($store->status === 'expired')
                            @php
                                $reconnectUrl = match($chCode) {
                                    'shopee'    => route('shopee.authorize'),
                                    'tiktok'    => route('tiktok.auth'),
                                    'tokopedia' => route('tiktok.auth', ['channel' => 'tokopedia']),
                                    'lazada'    => route('lazada.authorize'),
                                    default     => null,
                                };
                            @endphp
                            @if ($reconnectUrl)
                                <a href="{{ $reconnectUrl }}" class="btn btn-sm btn-warning text-dark fw-bold flex-grow-1 py-1.5 px-2.5 justify-content-center rounded-2 shadow-xs me-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i>Hubungkan Ulang
                                </a>
                            @endif
                        @endif

                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                            <a href="{{ url('/v2/toko/' . $store->id . '/edit') }}" class="btn-action-icon btn-action-edit rounded-2" title="Edit Pengaturan Toko">
                                <i class="bi bi-gear text-white"></i>
                            </a>
                            
                            <form action="{{ url('/v2/toko/' . $store->id) }}" method="POST" class="d-inline m-0 p-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-icon btn-action-delete rounded-2" title="Hapus Toko" onclick="return confirm('Apakah Anda yakin ingin menghapus toko ini?')">
                                    <i class="bi bi-trash text-white"></i>
                                </button>
                            </form>
                        </div>
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

<!-- Copy to clipboard script -->
<script>
function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = element.querySelector('i');
        icon.className = 'bi bi-check-lg text-success';
        setTimeout(() => {
            icon.className = 'bi bi-copy';
        }, 1500);
    }).catch(err => {
        console.error('Gagal menyalin text: ', err);
    });
}
</script>
@endsection
