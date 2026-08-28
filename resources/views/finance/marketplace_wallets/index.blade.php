@extends('layouts.app')

@section('title', 'Saldo Dompet Marketplace')
@section('page-title', 'Saldo Dompet Marketplace')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- Header/Title Card --}}
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-wallet me-2"></i>Saldo Dompet Marketplace
                        </h6>
                        <p class="text-muted mb-0 small mt-1">
                            Pantau saldo berjalan dan mutasi dana dari toko Shopee & TikTok yang terintegrasi secara real-time.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Alert --}}
            @foreach(['success','error','info'] as $type)
                @if(session($type))
                    <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-4 rounded-3" role="alert">
                        <i class="fas fa-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
                        {!! session($type) !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            @endforeach

            {{-- Card Grid --}}
            <div class="row g-3">
                @forelse($storeBalances as $sb)
                    @php
                        $store = $sb['store'];
                        $balance = $sb['balance'];
                        $isShopee = $store->channel->code === 'shopee';
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm rounded-3 h-100">
                            {{-- Mini Card Header styled like Users page --}}
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                                <span class="badge {{ $isShopee ? 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25' : 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25' }} px-2 py-1 rounded-pill small fw-semibold">
                                    @if($isShopee)
                                        <i class="fas fa-shopping-cart me-1 text-orange"></i> Shopee
                                    @else
                                        <i class="fab fa-tiktok me-1 text-dark"></i> TikTok
                                    @endif
                                </span>
                                <span class="badge bg-{{ $store->status === 'connected' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $store->status === 'connected' ? 'success' : 'danger' }} border border-{{ $store->status === 'connected' ? 'success' : 'danger' }} border-opacity-25 rounded-pill small">
                                    {{ $store->status === 'connected' ? 'Terhubung' : 'Terputus' }}
                                </span>
                            </div>

                            {{-- Card Body --}}
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold text-dark mb-1">{{ $store->store_name }}</h6>
                                <p class="text-muted font-monospace small mb-3">ID: {{ $store->marketplace_store_id }}</p>

                                {{-- Balance Area --}}
                                <div class="bg-light border rounded-3 p-3 mb-3 mt-auto">
                                    @if($balance['success'])
                                        <span class="text-muted d-block small mb-1" style="font-size: 0.72rem;">
                                            {{ !empty($balance['is_estimated']) ? 'ESTIMASI DANA CAIR (7 HARI)' : 'SALDO DOMPET BERJALAN' }}
                                        </span>
                                        <h4 class="fw-bold mb-1 text-dark font-monospace">
                                            Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}
                                        </h4>
                                        @if(!$isShopee)
                                            <small class="text-muted d-block" style="font-size: 0.68rem;">
                                                <i class="fas fa-info-circle text-info"></i> Estimasi total dana cair transaksi.
                                            </small>
                                        @else
                                            <div class="d-flex justify-content-between border-top pt-2 mt-2 text-secondary small" style="font-size: 0.75rem;">
                                                <span>Dapat Ditarik:</span>
                                                <strong class="text-success font-monospace">Rp {{ number_format($balance['withdraw_balance'], 0, ',', '.') }}</strong>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-danger small py-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            Gagal mengambil saldo: <br>
                                            <span class="fw-semibold text-break">{{ Str::limit($balance['error_message'], 60) }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Action Button --}}
                                @if($store->status === 'connected')
                                    <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-primary btn-sm w-100 rounded-2 py-2 fw-semibold">
                                        <i class="fas fa-history me-1.5"></i> Lihat Mutasi Dompet
                                    </a>
                                @else
                                    <button class="btn btn-secondary btn-sm w-100 rounded-2 py-2 fw-semibold" disabled>
                                        <i class="fas fa-plug me-1.5"></i> Offline
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card border shadow-sm rounded-3 py-5 text-center">
                            <div class="card-body">
                                <i class="fas fa-wallet fs-1 text-muted opacity-50 mb-3"></i>
                                <h5 class="fw-bold text-dark">Belum ada toko yang terhubung</h5>
                                <p class="text-muted small mb-0">Hubungkan toko Shopee/TikTok Anda di pengaturan integrasi.</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .text-orange {
            color: #ff5722 !important;
        }
    </style>
@endsection
