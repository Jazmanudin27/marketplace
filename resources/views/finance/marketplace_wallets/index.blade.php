@extends('layouts.app')

@section('title', 'Saldo Dompet Marketplace')
@section('page-title', 'Saldo Dompet Marketplace')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Tabel Utama ─────────────────────────────────────── --}}
            <div class="card border shadow-sm">
                {{-- Header --}}
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

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @foreach(['success','error','info'] as $type)
                        @if(session($type))
                            <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3" role="alert">
                                <i class="fas fa-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
                                {!! session($type) !!}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                    @endforeach

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase small font-monospace text-muted">
                                    <th class="text-center" style="width: 50px;">No</th>
                                    <th>Platform</th>
                                    <th>Nama Toko</th>
                                    <th>ID Toko</th>
                                    <th class="text-end">Saldo Dompet</th>
                                    <th class="text-end">Dapat Ditarik</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($storeBalances as $i => $sb)
                                    @php
                                        $store = $sb['store'];
                                        $balance = $sb['balance'];
                                        $isShopee = $store->channel->code === 'shopee';
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="badge {{ $isShopee ? 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25' : 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25' }} px-2.5 py-1.5 rounded-pill small fw-semibold">
                                                @if($isShopee)
                                                    <i class="fas fa-shopping-cart me-1 text-orange"></i> Shopee
                                                @else
                                                    <i class="fab fa-tiktok me-1 text-dark"></i> TikTok Shop
                                                @endif
                                            </span>
                                        </td>
                                        <td class="fw-semibold text-dark small">{{ $store->store_name }}</td>
                                        <td class="font-monospace small text-muted">{{ $store->marketplace_store_id }}</td>
                                        <td class="text-end font-monospace small fw-bold">
                                            @if($balance['success'])
                                                Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}
                                                @if(!$isShopee)
                                                    <span class="text-muted d-block" style="font-size: 0.7rem; font-weight: normal;">(Estimasi 7 Hari)</span>
                                                @endif
                                            @else
                                                <span class="text-danger" title="{{ $balance['error_message'] }}">
                                                    <i class="fas fa-exclamation-triangle"></i> Error
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace small fw-semibold text-success">
                                            @if($balance['success'])
                                                @if($isShopee)
                                                    Rp {{ number_format($balance['withdraw_balance'], 0, ',', '.') }}
                                                @else
                                                    Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $store->status === 'connected' ? 'success' : 'danger' }} bg-opacity-10 text-{{ $store->status === 'connected' ? 'success' : 'danger' }} border border-{{ $store->status === 'connected' ? 'success' : 'danger' }} border-opacity-25 rounded-pill small">
                                                {{ $store->status === 'connected' ? 'Terhubung' : 'Terputus' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($store->status === 'connected')
                                                <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-primary btn-sm px-3 rounded-2" title="Detail Mutasi">
                                                    <i class="fas fa-history me-1"></i> Mutasi
                                                </a>
                                            @else
                                                <button class="btn btn-secondary btn-sm px-3 rounded-2" disabled title="Toko terputus">
                                                    <i class="fas fa-plug"></i> Offline
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4 small">
                                            <i class="fas fa-wallet me-2 opacity-50"></i>
                                            Belum ada toko Shopee/TikTok yang terhubung.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-orange {
            color: #ff5722 !important;
        }
    </style>
@endsection
