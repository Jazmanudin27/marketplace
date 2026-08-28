@extends('layouts.app')

@section('title', 'Saldo Marketplace')

@section('content')
<div class="container-fluid p-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fas fa-wallet text-primary"></i>
                Saldo Dompet Marketplace
            </h4>
            <p class="text-muted mb-0 small">Pantau saldo berjalan dan mutasi dana dari toko Shopee & TikTok yang terintegrasi secara real-time.</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @foreach(['success','error','info'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="fas fa-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2 fs-5"></i>
            <div>{!! session($type) !!}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    @endforeach

    <div class="row g-4">
        @forelse($storeBalances as $sb)
            @php
                $store = $sb['store'];
                $balance = $sb['balance'];
                $isShopee = $store->channel->code === 'shopee';
                $logoClass = $isShopee ? 'text-orange' : 'text-dark';
                $logoIcon = $isShopee ? 'fa-shopping-bag' : 'fa-music'; // Shopee vs TikTok style logo icon
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        {{-- Top channel label --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge {{ $isShopee ? 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25' : 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25' }} px-3 py-1.5 rounded-pill small fw-semibold">
                                @if($isShopee)
                                    <i class="fas fa-shopping-cart me-1 text-orange"></i> Shopee
                                @else
                                    <i class="fab fa-tiktok me-1 text-dark"></i> TikTok Shop
                                @endif
                            </span>
                            <span class="badge bg-{{ $store->status === 'connected' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $store->status === 'connected' ? 'success' : 'danger' }} border border-{{ $store->status === 'connected' ? 'success' : 'danger' }} border-opacity-25 rounded-pill small">
                                {{ $store->status === 'connected' ? 'Terhubung' : 'Terputus' }}
                            </span>
                        </div>

                        {{-- Store Name --}}
                        <h5 class="fw-bold text-dark mb-1">{{ $store->store_name }}</h5>
                        <p class="text-muted font-monospace small mb-4">ID Toko: {{ $store->marketplace_store_id }}</p>

                        {{-- Balance content --}}
                        <div class="bg-light rounded-3 p-3 mb-4 mt-auto">
                            @if($balance['success'])
                                <span class="text-muted d-block small mb-1">
                                    {{ !empty($balance['is_estimated']) ? 'ESTIMASI DANA CAIR (7 HARI)' : 'SALDO DOMPET BERJALAN' }}
                                </span>
                                <h3 class="fw-bold mb-2 text-dark font-monospace">
                                    Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}
                                </h3>
                                @if(!$isShopee)
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <i class="fas fa-info-circle text-info"></i>
                                        Estimasi total dana cair transaksi 7 hari terakhir.
                                    </small>
                                @else
                                    <div class="d-flex justify-content-between border-top pt-2 mt-2 text-secondary small">
                                        <span>Dapat Ditarik:</span>
                                        <strong class="text-success font-monospace">Rp {{ number_format($balance['withdraw_balance'], 0, ',', '.') }}</strong>
                                    </div>
                                @endif
                            @else
                                <div class="text-danger small py-2">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    Gagal mengambil data saldo: <br>
                                    <span class="fw-semibold text-break">{{ $balance['error_message'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Button --}}
                        @if($store->status === 'connected')
                            <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-primary w-100 rounded-2 py-2 fw-semibold mt-auto">
                                <i class="fas fa-history me-1.5"></i> Lihat Mutasi Dompet
                            </a>
                        @else
                            <button class="btn btn-secondary w-100 rounded-2 py-2 fw-semibold mt-auto" disabled>
                                <i class="fas fa-plug me-1.5"></i> Hubungkan Kembali Toko
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 py-5 text-center">
                    <div class="card-body">
                        <i class="fas fa-wallet fs-1 text-muted opacity-50 mb-3"></i>
                        <h5 class="fw-bold text-dark">Belum ada toko yang terhubung</h5>
                        <p class="text-muted small mb-0">Hubungkan toko Shopee atau TikTok Anda terlebih dahulu di menu pengaturan integrasi.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

</div>

<style>
    .text-orange {
        color: #ff5722 !important;
    }
</style>
@endsection
