@extends('layouts.app')
@section('title', 'Kelola Toko')
@section('page-title', 'Kelola Toko Marketplace')
@section('content')
    <div class="container-fluid">
        @php
            $hasExpiredStores = $stores->contains('status', 'expired');
        @endphp

        @if ($hasExpiredStores)
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4" role="alert" style="background-color: rgba(245, 158, 11, 0.12); border-left: 4px solid #f59e0b !important;">
                <i class="fas fa-exclamation-triangle fs-4 text-warning"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1 text-warning">Koneksi Toko Kedaluwarsa!</h6>
                    <p class="mb-0 small text-muted">Ada toko terhubung yang membutuhkan tindakan Anda. Token integrasi toko tersebut telah kedaluwarsa (expired) atau koneksinya terputus dari pihak marketplace. Harap klik <strong>Hubungkan Ulang</strong> pada toko tersebut agar sinkronisasi produk, pesanan, dan chat kembali berjalan normal.</p>
                </div>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header border-bottom-0 p-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-plug text-primary me-2"></i> Toko Terhubung</h5>
                <a href="{{ route('stores.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Toko
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($stores as $store)
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($store->channel->code === 'shopee')
                                                <div class="bg-danger text-white rounded p-2"
                                                    style="background-color: #EE4D2D !important;">
                                                    <i class="fas fa-shopping-bag"></i>
                                                </div>
                                            @elseif($store->channel->code === 'tiktok')
                                                <div class="bg-dark text-white rounded p-2">
                                                    <i class="fab fa-tiktok"></i>
                                                </div>
                                            @elseif($store->channel->code === 'tokopedia')
                                                <div class="bg-success text-white rounded p-2"
                                                    style="background-color: #03AC0E !important;">
                                                    <i class="fas fa-store"></i>
                                                </div>
                                            @else
                                                <div class="bg-secondary text-white rounded p-2">
                                                    <i class="fas fa-globe"></i>
                                                </div>
                                            @endif
                                            <h6 class="mb-0 fw-bold">{{ $store->channel->name }}</h6>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($store->status === 'connected')
                                                <span class="badge rounded-pill bg-success">Terhubung</span>
                                            @elseif ($store->status === 'expired')
                                                <span class="badge rounded-pill bg-warning text-dark" title="Token kedaluwarsa & gagal refresh otomatis">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Expired
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-danger">Terputus</span>
                                            @endif
                                            
                                            <a href="{{ route('stores.edit', $store->id) }}" class="text-muted text-decoration-none" title="Pengaturan Toko">
                                                <i class="fas fa-cog"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <h5 class="card-title fw-bold mb-1">{{ $store->store_name }}</h5>
                                    <p class="card-text text-muted small mb-3">
                                        <i class="fas fa-id-card me-1"></i> ID: {{ $store->marketplace_store_id }}
                                    </p>

                                    @if ($store->status === 'connected')
                                        <hr class="text-muted">
                                        <div class="d-flex gap-2 mt-3">
                                            @if ($store->channel->code === 'shopee')
                                                <form action="{{ route('shopee.sync_products', $store->id) }}"
                                                    method="POST" class="flex-fill">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-success btn-sm w-100 d-flex justify-content-center align-items-center gap-2"
                                                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Menunggu...'; this.disabled=true; this.form.submit();">
                                                        <i class="fas fa-box-open"></i> Tarik Produk
                                                    </button>
                                                </form>

                                                <form action="{{ route('shopee.sync_orders', $store->id) }}" method="POST"
                                                    class="flex-fill">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm w-100 d-flex justify-content-center align-items-center gap-2"
                                                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Menunggu...'; this.disabled=true; this.form.submit();">
                                                        <i class="fas fa-shopping-bag"></i> Tarik Pesanan
                                                    </button>
                                                </form>
                                            @elseif($store->channel->code === 'tiktok')
                                                <form action="{{ route('tiktok.sync_products', $store->id) }}"
                                                    method="POST" class="flex-fill">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-success btn-sm w-100 d-flex justify-content-center align-items-center gap-2"
                                                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Menunggu...'; this.disabled=true; this.form.submit();">
                                                        <i class="fas fa-box-open"></i> Tarik Produk
                                                    </button>
                                                </form>
                                                <form action="{{ route('tiktok.sync_orders', $store->id) }}" method="POST"
                                                    class="flex-fill">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm w-100 d-flex justify-content-center align-items-center gap-2"
                                                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Menunggu...'; this.disabled=true; this.form.submit();">
                                                        <i class="fas fa-shopping-bag"></i> Tarik Pesanan
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($store->status === 'expired')
                                        <hr class="text-muted">
                                        <div class="mt-3">
                                            @if ($store->channel->code === 'shopee')
                                                <a href="{{ route('shopee.authorize') }}" class="btn btn-warning btn-sm w-100 text-dark fw-bold d-flex justify-content-center align-items-center gap-2 shadow-sm">
                                                    <i class="fas fa-plug"></i> Hubungkan Ulang (Reconnect)
                                                </a>
                                            @elseif ($store->channel->code === 'tiktok')
                                                <a href="{{ route('tiktok.auth') }}" class="btn btn-warning btn-sm w-100 text-dark fw-bold d-flex justify-content-center align-items-center gap-2 shadow-sm">
                                                    <i class="fas fa-plug"></i> Hubungkan Ulang (Reconnect)
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
                                <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum ada toko yang terhubung.</h5>
                                <p class="text-muted mb-4">Tambahkan toko marketplace Anda sekarang untuk mulai berjualan.
                                </p>
                                <a href="{{ route('stores.create') }}" class="btn btn-primary">
                                    Hubungkan Toko Pertama
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
