@extends('layouts.app')
@section('title', 'Kelola Toko')
@section('page-title', 'Kelola Toko Marketplace')
@section('content')
    <div class="container-fluid">
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
                                        <span
                                            class="badge rounded-pill {{ $store->status === 'connected' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $store->status === 'connected' ? 'Terhubung' : 'Terputus' }}
                                        </span>
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
