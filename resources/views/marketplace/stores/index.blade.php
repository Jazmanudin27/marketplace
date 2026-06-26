@extends('layouts.app')
@section('title', 'Kelola Toko')
@section('page-title', 'Kelola Toko Marketplace')

@section('content')
    @php 
        $hasExpiredStores = $stores->contains('status', 'expired'); 
        $connectedCount = $stores->where('status', 'connected')->count();
        $expiredCount = $stores->where('status', 'expired')->count();
        $totalCount = $stores->count();
    @endphp

    <!-- Header & Info Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Integrasi Marketplace</h4>
            <p class="text-muted mb-0 small">Hubungkan dan sinkronkan produk, stok, dan pesanan secara terpusat dari semua toko Anda.</p>
        </div>
        <a href="{{ route('stores.create') }}" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm">
            <i class="fas fa-plus-circle me-2"></i>Tambah Toko Baru
        </a>
    </div>

    <!-- Alert Expired Stores -->
    @if ($hasExpiredStores)
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-3 p-3 mb-4 rounded-3" role="alert">
            <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3">
                <i class="fas fa-exclamation-triangle fs-5"></i>
            </div>
            <div>
                <h6 class="alert-heading fw-bold mb-1 text-dark">Koneksi Toko Membutuhkan Tindakan Anda!</h6>
                <p class="mb-0 text-muted small">
                    Ada beberapa toko yang token koneksinya telah kedaluwarsa atau terputus. Silakan klik tombol 
                    <strong class="text-dark">Hubungkan Ulang</strong> pada toko tersebut agar sinkronisasi produk dan pesanan berjalan kembali secara otomatis.
                </p>
            </div>
        </div>
    @endif

    <!-- Quick Stats Section -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.25rem;">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Toko</div>
                        <h4 class="fw-bold mb-0">{{ $totalCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.25rem;">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Toko Terhubung</div>
                        <h4 class="fw-bold mb-0 text-success">{{ $connectedCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 {{ $expiredCount > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary' }} d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.25rem;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Butuh Koneksi Ulang</div>
                        <h4 class="fw-bold mb-0 {{ $expiredCount > 0 ? 'text-danger' : '' }}">{{ $expiredCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stores Grid -->
    <div class="row g-3">
        @forelse($stores as $store)
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 rounded-3">
                    <div class="card-body p-3 d-flex flex-column h-100">
                        
                        <!-- Header Card: Logo, Badge Status, Setting -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                @php
                                    $logoBgClass = match($store->channel->code) {
                                        'shopee'    => 'bg-danger bg-opacity-10 text-danger',
                                        'tiktok'    => 'bg-dark bg-opacity-10 text-dark',
                                        'tokopedia' => 'bg-success bg-opacity-10 text-success',
                                        default     => 'bg-secondary bg-opacity-10 text-secondary',
                                    };
                                    $iconClass = match($store->channel->code) {
                                        'shopee'    => 'fas fa-shopping-bag',
                                        'tiktok'    => 'fab fa-tiktok',
                                        'tokopedia' => 'fas fa-store',
                                        default     => 'fas fa-globe',
                                    };
                                @endphp
                                <div class="rounded-3 d-flex align-items-center justify-content-center {{ $logoBgClass }} shadow-sm" style="width: 46px; height: 46px; font-size: 1.25rem;">
                                    <i class="{{ $iconClass }}"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark" style="font-size:0.9rem;">{{ $store->channel->name }}</h6>
                                    <span class="text-muted" style="font-size:0.7rem;">Official Channel</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                @if ($store->status === 'connected')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-circle me-1 small"></i>Terhubung
                                    </span>
                                @elseif ($store->status === 'expired')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-exclamation-triangle me-1 small"></i>Expired
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-times-circle me-1 small"></i>Terputus
                                    </span>
                                @endif
                                
                                <a href="{{ route('stores.edit', $store->id) }}"
                                   class="btn btn-sm btn-outline-secondary border-0 p-1 rounded-circle d-flex align-items-center justify-content-center"
                                   style="width: 28px; height: 28px;"
                                   title="Pengaturan Toko">
                                    <i class="fas fa-cog text-muted" style="font-size: 0.85rem;"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Store Name & Marketplace ID -->
                        <div class="mb-3">
                            <h5 class="fw-bold text-dark mb-1" style="font-size:1.05rem;">{{ $store->store_name }}</h5>
                            <div class="d-flex align-items-center gap-2 text-muted" style="font-size:0.75rem;">
                                <i class="fas fa-fingerprint"></i>
                                <span>ID: <code class="text-secondary font-monospace">{{ $store->marketplace_store_id }}</code></span>
                                <button class="btn btn-link btn-sm p-0 border-0 text-muted text-decoration-none lh-1" onclick="copyToClipboard('{{ $store->marketplace_store_id }}', this)" title="Salin ID Toko">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Token Expiration Info -->
                        @if($store->status === 'connected' && $store->token_expires_at)
                            <div class="bg-light p-2 rounded-3 mb-3 mt-auto" style="font-size: 0.72rem;">
                                <div class="text-muted d-flex align-items-center gap-1">
                                    <i class="far fa-clock"></i>
                                    <span>Token aktif s/d:</span>
                                </div>
                                <div class="fw-bold text-dark-emphasis mt-1">
                                    {{ $store->token_expires_at->translatedFormat('d F Y, H:i') }}
                                </div>
                            </div>
                        @else
                            <div class="mt-auto"></div>
                        @endif

                        <!-- Footer Actions: Sync / Reconnect -->
                        @if ($store->status === 'connected')
                            <hr class="my-2 opacity-50">
                            <div class="d-flex gap-2 mt-2">
                                @php
                                    $syncProductRoute = match($store->channel->code) {
                                        'shopee'    => route('shopee.sync_products', $store->id),
                                        'tiktok'    => route('tiktok.sync_products', $store->id),
                                        'tokopedia' => route('tokopedia.sync_products', $store->id),
                                        default     => null,
                                    };
                                    $syncOrderRoute = match($store->channel->code) {
                                        'shopee'    => route('shopee.sync_orders', $store->id),
                                        'tiktok'    => route('tiktok.sync_orders', $store->id),
                                        'tokopedia' => route('tokopedia.sync_orders', $store->id),
                                        default     => null,
                                    };
                                @endphp

                                @if ($syncProductRoute)
                                    <form action="{{ $syncProductRoute }}" method="POST" class="flex-fill m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success w-100 py-2 rounded-3 fw-semibold"
                                            onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin me-1\'></i> Sync...'; this.disabled=true; this.form.submit();">
                                            <i class="fas fa-box-open me-1"></i>Tarik Produk
                                        </button>
                                    </form>
                                @endif

                                @if ($syncOrderRoute)
                                    <form action="{{ $syncOrderRoute }}" method="POST" class="flex-fill m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100 py-2 rounded-3 fw-semibold"
                                            onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin me-1\'></i> Sync...'; this.disabled=true; this.form.submit();">
                                            <i class="fas fa-shopping-bag me-1"></i>Tarik Pesanan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if ($store->status === 'expired')
                            <hr class="my-2 opacity-50">
                            <div class="mt-2">
                                @php
                                    $reconnectUrl = match($store->channel->code) {
                                        'shopee'    => route('shopee.authorize'),
                                        'tiktok'    => route('tiktok.auth'),
                                        'tokopedia' => route('tiktok.auth', ['channel' => 'tokopedia']),
                                        default     => null,
                                    };
                                @endphp
                                @if ($reconnectUrl)
                                    <a href="{{ $reconnectUrl }}" class="btn btn-warning btn-sm w-100 py-2 rounded-3 fw-semibold shadow-sm">
                                        <i class="fas fa-sync-alt me-1"></i>Hubungkan Ulang
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
                    <div class="bg-light d-inline-flex p-4 rounded-circle mb-3 text-muted">
                        <i class="fas fa-plug fa-3x opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Belum Ada Toko Terhubung</h5>
                    <p class="text-muted small mx-auto mb-4" style="max-width: 400px;">
                        Tambahkan toko marketplace Anda sekarang untuk memulai sinkronisasi produk, stok, dan pesanan secara otomatis.
                    </p>
                    <a href="{{ route('stores.create') }}" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm">
                        <i class="fas fa-plus me-1"></i>Hubungkan Toko Pertama
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Scripts -->
    <script>
        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                const icon = element.querySelector('i');
                icon.className = 'fas fa-check text-success';
                setTimeout(() => {
                    icon.className = 'fas fa-copy';
                }, 1500);
            }).catch(err => {
                console.error('Gagal menyalin text: ', err);
            });
        }
    </script>
@endsection
