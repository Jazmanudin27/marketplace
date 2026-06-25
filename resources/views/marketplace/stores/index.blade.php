@extends('layouts.app')
@section('title', 'Kelola Toko')
@section('page-title', 'Kelola Toko Marketplace')

@section('content')
    @php $hasExpiredStores = $stores->contains('status', 'expired'); @endphp

    @if ($hasExpiredStores)
        <div class="alert alert-warning d-flex align-items-center gap-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle fs-5 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1 small">Koneksi Toko Kedaluwarsa!</h6>
                <p class="mb-0 small">Ada toko terhubung yang membutuhkan tindakan Anda. Token integrasi toko tersebut telah
                    kedaluwarsa (expired) atau koneksinya terputus dari pihak marketplace. Harap klik
                    <strong>Hubungkan Ulang</strong> pada toko tersebut agar sinkronisasi produk, pesanan, dan chat kembali berjalan normal.
                </p>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-plug me-2 text-info"></i>Toko Terhubung</h6>
            <a href="{{ route('stores.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Tambah Toko
            </a>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                @forelse($stores as $store)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $iconClass = match($store->channel->code) {
                                                'shopee'    => 'fas fa-shopping-bag text-danger',
                                                'tiktok'    => 'fab fa-tiktok text-dark',
                                                'tokopedia' => 'fas fa-store text-success',
                                                default     => 'fas fa-globe text-secondary',
                                            };
                                        @endphp
                                        <div class="rounded-3 bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:36px;height:36px;">
                                            <i class="{{ $iconClass }}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small">{{ $store->channel->name }}</div>
                                            <div class="text-muted" style="font-size:0.7rem;">Marketplace</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        @if ($store->status === 'connected')
                                            <span class="badge bg-success" style="font-size:0.65rem;">Terhubung</span>
                                        @elseif ($store->status === 'expired')
                                            <span class="badge bg-warning text-dark" style="font-size:0.65rem;" title="Token kedaluwarsa">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Expired
                                            </span>
                                        @else
                                            <span class="badge bg-danger" style="font-size:0.65rem;">Terputus</span>
                                        @endif
                                        <a href="{{ route('stores.edit', $store->id) }}"
                                            class="btn btn-sm btn-outline-secondary border-0 p-1 lh-1" title="Pengaturan Toko">
                                            <i class="fas fa-cog" style="font-size:0.75rem;"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="fw-bold small mb-0">{{ $store->store_name }}</div>
                                <div class="text-muted mb-2" style="font-size:0.72rem;">
                                    <i class="fas fa-fingerprint me-1"></i>ID: <span class="font-monospace">{{ $store->marketplace_store_id }}</span>
                                </div>

                                @if ($store->status === 'connected')
                                    <hr class="my-2">
                                    <div class="d-flex gap-1">
                                        @if ($store->channel->code === 'shopee')
                                            <form action="{{ route('shopee.sync_products', $store->id) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm w-100 py-1"
                                                    style="font-size:0.72rem;"
                                                    onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                    <i class="fas fa-box-open"></i> Tarik Produk
                                                </button>
                                            </form>
                                            <form action="{{ route('shopee.sync_orders', $store->id) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm w-100 py-1"
                                                    style="font-size:0.72rem;"
                                                    onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                    <i class="fas fa-shopping-bag"></i> Tarik Pesanan
                                                </button>
                                            </form>
                                        @elseif($store->channel->code === 'tiktok')
                                            <form action="{{ route('tiktok.sync_products', $store->id) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm w-100 py-1"
                                                    style="font-size:0.72rem;"
                                                    onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                    <i class="fas fa-box-open"></i> Tarik Produk
                                                </button>
                                            </form>
                                            <form action="{{ route('tiktok.sync_orders', $store->id) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm w-100 py-1"
                                                    style="font-size:0.72rem;"
                                                    onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                    <i class="fas fa-shopping-bag"></i> Tarik Pesanan
                                                </button>
                                            </form>
                                        @elseif($store->channel->code === 'tokopedia')
                                            <form action="{{ route('tokopedia.sync_products', $store->id) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm w-100 py-1"
                                                    style="font-size:0.72rem;"
                                                    onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                    <i class="fas fa-box-open"></i> Tarik Produk
                                                </button>
                                            </form>
                                            <form action="{{ route('tokopedia.sync_orders', $store->id) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm w-100 py-1"
                                                    style="font-size:0.72rem;"
                                                    onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                    <i class="fas fa-shopping-bag"></i> Tarik Pesanan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif

                                @if ($store->status === 'expired')
                                    <hr class="my-2">
                                    <div class="mt-1">
                                        @if ($store->channel->code === 'shopee')
                                            <a href="{{ route('shopee.authorize') }}"
                                                class="btn btn-warning btn-sm w-100 fw-bold py-1"
                                                style="font-size:0.72rem;">
                                                <i class="fas fa-sync me-1"></i>Reconnect
                                            </a>
                                        @elseif ($store->channel->code === 'tiktok')
                                            <a href="{{ route('tiktok.auth') }}"
                                                class="btn btn-warning btn-sm w-100 fw-bold py-1"
                                                style="font-size:0.72rem;">
                                                <i class="fas fa-sync me-1"></i>Reconnect
                                            </a>
                                        @elseif ($store->channel->code === 'tokopedia')
                                            <a href="{{ route('tiktok.auth', ['channel' => 'tokopedia']) }}"
                                                class="btn btn-warning btn-sm w-100 fw-bold py-1"
                                                style="font-size:0.72rem;">
                                                <i class="fas fa-sync me-1"></i>Reconnect
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-plug fa-3x text-muted opacity-25 mb-3 d-block"></i>
                            <h5 class="fw-bold">Belum Ada Toko Terhubung</h5>
                            <p class="text-muted small mb-4">Tambahkan toko marketplace Anda sekarang untuk mulai sinkronisasi produk dan pesanan secara otomatis.</p>
                            <a href="{{ route('stores.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>Hubungkan Toko Pertama
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
