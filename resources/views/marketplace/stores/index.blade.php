@extends('layouts.app')
@section('title', 'Kelola Toko')
@section('page-title', 'Kelola Toko Marketplace')

@section('content')
    <div class="container-fluid p-0">
        @php
            $hasExpiredStores = $stores->contains('status', 'expired');
        @endphp

        @if ($hasExpiredStores)
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4" role="alert"
                style="background-color: rgba(245, 158, 11, 0.12); border-left: 4px solid #f59e0b !important;">
                <i class="fas fa-exclamation-triangle fs-4 text-warning flex-shrink-0"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1 text-warning" style="font-size: 0.9rem;">Koneksi Toko Kedaluwarsa!
                    </h6>
                    <p class="mb-0 small text-muted">Ada toko terhubung yang membutuhkan tindakan Anda. Token integrasi toko
                        tersebut telah kedaluwarsa (expired) atau koneksinya terputus dari pihak marketplace. Harap klik
                        <strong>Hubungkan Ulang</strong> pada toko tersebut agar sinkronisasi produk, pesanan, dan chat
                        kembali berjalan normal.
                    </p>
                </div>
            </div>
        @endif

        <div class="dashboard-card mb-4">
            <div class="card-header-line d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0"><i class="fas fa-plug text-primary me-2"></i> Toko Terhubung</h3>
                <a href="{{ route('stores.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Tambah Toko
                </a>
            </div>

            <div class="row g-3">
                @forelse($stores as $store)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="dashboard-card h-100 p-3"
                            style="background: linear-gradient(135deg, rgba(255,255,255,0.015), rgba(255,255,255,0.005)); border: 1px solid rgba(255, 255, 255, 0.08);">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    @if ($store->channel->code === 'shopee')
                                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2"
                                            style="width: 36px; height: 36px; background-color: rgba(238, 77, 45, 0.15) !important; color: #ee4d2d !important; font-size: 0.85rem;">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                    @elseif($store->channel->code === 'tiktok')
                                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2"
                                            style="width: 36px; height: 36px; background-color: rgba(255, 255, 255, 0.08) !important; color: #ffffff !important; font-size: 0.85rem;">
                                            <i class="fab fa-tiktok"></i>
                                        </div>
                                    @elseif($store->channel->code === 'tokopedia')
                                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2"
                                            style="width: 36px; height: 36px; background-color: rgba(3, 172, 14, 0.15) !important; color: #03ac0e !important; font-size: 0.85rem;">
                                            <i class="fas fa-store"></i>
                                        </div>
                                    @else
                                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2"
                                            style="width: 36px; height: 36px; background-color: rgba(108, 117, 125, 0.15) !important; color: #adb5bd !important; font-size: 0.85rem;">
                                            <i class="fas fa-globe"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-0 fw-bold text-white" style="font-size: 0.85rem;">
                                            {{ $store->channel->name }}</h6>
                                        <small class="text-muted small" style="font-size: 0.7rem;">Marketplace</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1.5">
                                    @if ($store->status === 'connected')
                                        <span class="badge badge-success px-1.5 py-0.5"
                                            style="font-size: 0.65rem;">Terhubung</span>
                                    @elseif ($store->status === 'expired')
                                        <span class="badge badge-warning px-1.5 py-0.5 text-dark"
                                            style="font-size: 0.65rem;" title="Token kedaluwarsa & gagal refresh otomatis">
                                            <i class="fas fa-exclamation-triangle" style="font-size: 0.6rem;"></i> Expired
                                        </span>
                                    @else
                                        <span class="badge badge-danger px-1.5 py-0.5"
                                            style="font-size: 0.65rem;">Terputus</span>
                                    @endif

                                    <a href="{{ route('stores.edit', $store->id) }}"
                                        class="text-muted btn btn-xs btn-outline-secondary border-0 p-1 lh-1"
                                        title="Pengaturan Toko">
                                        <i class="fas fa-cog" style="font-size: 0.75rem;"></i>
                                    </a>
                                </div>
                            </div>

                            <h6 class="fw-bold text-white mb-0.5 mt-2" style="font-size: 0.92rem;">{{ $store->store_name }}
                            </h6>
                            <p class="text-muted mb-3" style="font-size: 0.72rem;">
                                <i class="fas fa-fingerprint me-1 text-secondary"></i> ID: <span
                                    class="mono">{{ $store->marketplace_store_id }}</span>
                            </p>

                            @if ($store->status === 'connected')
                                <hr class="border-secondary border-opacity-25 my-2.5">
                                <div class="d-flex gap-1.5">
                                    @if ($store->channel->code === 'shopee')
                                        <form action="{{ route('shopee.sync_products', $store->id) }}" method="POST"
                                            class="flex-fill">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-outline-success btn-xs w-100 d-flex justify-content-center align-items-center gap-1 py-1.5"
                                                style="font-size: 0.72rem;"
                                                onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-box-open" style="font-size: 0.7rem;"></i> Tarik Produk
                                            </button>
                                        </form>

                                        <form action="{{ route('shopee.sync_orders', $store->id) }}" method="POST"
                                            class="flex-fill">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-outline-primary btn-xs w-100 d-flex justify-content-center align-items-center gap-1 py-1.5"
                                                style="font-size: 0.72rem;"
                                                onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-shopping-bag" style="font-size: 0.7rem;"></i> Tarik Pesanan
                                            </button>
                                        </form>
                                    @elseif($store->channel->code === 'tiktok')
                                        <form action="{{ route('tiktok.sync_products', $store->id) }}" method="POST"
                                            class="flex-fill">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-outline-success btn-xs w-100 d-flex justify-content-center align-items-center gap-1 py-1.5"
                                                style="font-size: 0.72rem;"
                                                onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-box-open" style="font-size: 0.7rem;"></i> Tarik Produk
                                            </button>
                                        </form>
                                        <form action="{{ route('tiktok.sync_orders', $store->id) }}" method="POST"
                                            class="flex-fill">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-outline-primary btn-xs w-100 d-flex justify-content-center align-items-center gap-1 py-1.5"
                                                style="font-size: 0.72rem;"
                                                onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-shopping-bag" style="font-size: 0.7rem;"></i> Tarik Pesanan
                                            </button>
                                        </form>
                                    @elseif($store->channel->code === 'tokopedia')
                                        <form action="{{ route('tokopedia.sync_products', $store->id) }}" method="POST"
                                            class="flex-fill">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-outline-success btn-xs w-100 d-flex justify-content-center align-items-center gap-1 py-1.5"
                                                style="font-size: 0.72rem;"
                                                onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-box-open" style="font-size: 0.7rem;"></i> Tarik Produk
                                            </button>
                                        </form>
                                        <form action="{{ route('tokopedia.sync_orders', $store->id) }}" method="POST"
                                            class="flex-fill">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-outline-primary btn-xs w-100 d-flex justify-content-center align-items-center gap-1 py-1.5"
                                                style="font-size: 0.72rem;"
                                                onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-shopping-bag" style="font-size: 0.7rem;"></i> Tarik
                                                Pesanan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            @if ($store->status === 'expired')
                                <hr class="border-secondary border-opacity-25 my-2.5">
                                <div class="mt-2">
                                    @if ($store->channel->code === 'shopee')
                                        <a href="{{ route('shopee.authorize') }}"
                                            class="btn btn-warning btn-xs w-100 text-dark fw-bold d-flex justify-content-center align-items-center gap-1.5 py-1.5 shadow-sm"
                                            style="font-size: 0.72rem;">
                                            <i class="fas fa-sync" style="font-size: 0.7rem;"></i> Reconnect
                                        </a>
                                    @elseif ($store->channel->code === 'tiktok')
                                        <a href="{{ route('tiktok.auth') }}"
                                            class="btn btn-warning btn-xs w-100 text-dark fw-bold d-flex justify-content-center align-items-center gap-1.5 py-1.5 shadow-sm"
                                            style="font-size: 0.72rem;">
                                            <i class="fas fa-sync" style="font-size: 0.7rem;"></i> Reconnect
                                        </a>
                                    @elseif ($store->channel->code === 'tokopedia')
                                        <a href="{{ route('tiktok.auth', ['channel' => 'tokopedia']) }}"
                                            class="btn btn-warning btn-xs w-100 text-dark fw-bold d-flex justify-content-center align-items-center gap-1.5 py-1.5 shadow-sm"
                                            style="font-size: 0.72rem;">
                                            <i class="fas fa-sync" style="font-size: 0.7rem;"></i> Reconnect
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-plug fa-3x text-muted opacity-25"></i>
                            </div>
                            <h5 class="text-white fw-bold">Belum Ada Toko Terhubung</h5>
                            <p class="text-muted small mb-4">Tambahkan toko marketplace Anda sekarang untuk mulai
                                sinkronisasi produk dan pesanan secara otomatis.</p>
                            <a href="{{ route('stores.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Hubungkan Toko Pertama
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
