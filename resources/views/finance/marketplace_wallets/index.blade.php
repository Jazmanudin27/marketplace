@extends('layouts.app')

@section('title', 'Saldo Dompet Marketplace')
@section('page-title', 'Saldo Dompet Marketplace')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- Header/Title Card --}}
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom flex-wrap gap-2">
                    <div>
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-wallet me-2"></i>Saldo Dompet Marketplace
                        </h6>
                        <p class="text-muted mb-0 small mt-1">
                            Pantau saldo berjalan dan mutasi dana dari toko Shopee & TikTok yang terintegrasi secara real-time.
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('finance.marketplace_wallets.index', ['refresh' => 1]) }}" class="btn btn-outline-primary btn-sm rounded-2 fw-semibold" title="Ambil ulang saldo real-time langsung dari API Shopee & TikTok">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Saldo Real-Time
                        </a>
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

            {{-- KPI Summary Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card border rounded shadow-sm bg-white h-100 border-start border-primary border-4">
                        <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fas fa-wallet fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-muted small fw-semibold">Total Saldo Siap Ditarik</div>
                                <div class="fw-bold fs-5 text-dark font-monospace mt-0.5">
                                    Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Saldo dapat ditarik dari seluruh toko</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border rounded shadow-sm bg-white h-100 border-start border-warning border-4">
                        <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fas fa-clock fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-warning-emphasis small fw-semibold">Total Saldo Tertahan (Akan Dilepas)</div>
                                <div class="fw-bold fs-5 text-warning font-monospace mt-0.5">
                                    Rp {{ number_format($totalPendingBalance, 0, ',', '.') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ $totalPendingCount }} pesanan aktif belum selesai / to settle</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border rounded shadow-sm bg-white h-100 border-start border-success border-4">
                        <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="fas fa-coins fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-success small fw-semibold">Estimasi Total Dana Marketplace</div>
                                <div class="fw-bold fs-5 text-success font-monospace mt-0.5">
                                    Rp {{ number_format($totalWalletBalance + $totalPendingBalance, 0, ',', '.') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Akumulasi Saldo Dompet + Saldo Tertahan</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grid Kartu Saldo Per Toko --}}
            <div class="row g-3">
                @forelse($storeBalances as $sb)
                    @php
                        $store = $sb['store'];
                        $balance = $sb['balance'];
                    @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border rounded-3 shadow-sm h-100 hover-shadow transition-all bg-white">
                            {{-- Card Header: Info Toko --}}
                            <div class="card-header bg-white border-bottom py-3 px-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2.5 min-width-0">
                                    @if($store->channel->code === 'shopee')
                                        <div class="channel-icon-badge bg-orange-subtle text-orange rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="fas fa-shopping-bag fs-5"></i>
                                        </div>
                                    @elseif($store->channel->code === 'tiktok')
                                        <div class="channel-icon-badge bg-dark-subtle text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="fab fa-tiktok fs-5"></i>
                                        </div>
                                    @else
                                        <div class="channel-icon-badge bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="fas fa-store fs-5"></i>
                                        </div>
                                    @endif

                                    <div class="min-width-0">
                                        <h6 class="mb-0 fw-bold text-dark text-truncate" title="{{ $store->store_name }}">
                                            {{ $store->store_name }}
                                        </h6>
                                        <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small fw-semibold" style="font-size: 0.68rem;">
                                                {{ strtoupper($store->channel->code) }}
                                            </span>
                                            <span class="text-muted small" style="font-size: 0.72rem;">
                                                #{{ $store->marketplace_store_id }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status Indicator --}}
                                <div>
                                    @if($store->status === 'connected')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 small fw-semibold" style="font-size: 0.7rem;">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1 small fw-semibold" style="font-size: 0.7rem;">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Terputus
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="card-body p-3 d-flex flex-column">
                                {{-- Balance Area --}}
                                <div class="bg-light border rounded-3 p-3 mb-3 mt-auto">
                                    @if($balance['success'])
                                        {{-- Saldo Dompet (Siap Tarik) --}}
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                                SALDO DAPAT DITARIK
                                            </span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 small" style="font-size: 0.68rem;">
                                                Siap Tarik
                                            </span>
                                        </div>
                                        <h4 class="fw-bold mb-1 text-dark font-monospace">
                                            Rp {{ number_format($balance['withdraw_balance'] ?? $balance['current_balance'], 0, ',', '.') }}
                                        </h4>
                                        @if(($balance['current_balance'] ?? 0) != ($balance['withdraw_balance'] ?? 0))
                                            <div class="d-flex justify-content-between text-secondary small mb-1" style="font-size: 0.73rem;">
                                                <span>Total Saldo Akun:</span>
                                                <strong class="font-monospace text-dark">Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}</strong>
                                            </div>
                                        @endif

                                        {{-- Saldo Pending (Akan Dilepas) --}}
                                        <div class="border-top pt-2 mt-2">
                                            <div class="d-flex justify-content-between align-items-center text-secondary small" style="font-size: 0.78rem;">
                                                <span class="text-dark fw-semibold">
                                                    <i class="fas fa-hourglass-half me-1 text-warning"></i> Saldo Tertahan (Akan Dilepas):
                                                </span>
                                                <strong class="text-warning-emphasis font-monospace fs-6">
                                                    Rp {{ number_format($balance['pending_balance'], 0, ',', '.') }}
                                                </strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.72rem;">
                                                <span>Pesanan dalam proses/kirim:</span>
                                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.68rem;">
                                                    {{ $balance['pending_count'] }} Pesanan
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Total Estimasi Dana Toko --}}
                                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 text-secondary small" style="font-size: 0.78rem;">
                                            <span class="fw-bold text-dark">Estimasi Total Dana:</span>
                                            <strong class="text-primary font-monospace fs-6">
                                                Rp {{ number_format($balance['total_estimated'], 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    @else
                                        <div class="text-danger small py-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            Gagal mengambil saldo API: <br>
                                            <span class="fw-semibold text-break">{{ Str::limit($balance['error_message'], 60) }}</span>
                                        </div>
                                        {{-- Tetap tampilkan Saldo Pending dari database ERP --}}
                                        <div class="border-top pt-2 mt-2">
                                            <div class="d-flex justify-content-between align-items-center text-secondary small" style="font-size: 0.78rem;">
                                                <span class="text-dark fw-semibold">
                                                    <i class="fas fa-hourglass-half me-1 text-warning"></i> Saldo Pending:
                                                </span>
                                                <strong class="text-warning-emphasis font-monospace fs-6">
                                                    Rp {{ number_format($balance['pending_balance'], 0, ',', '.') }}
                                                </strong>
                                            </div>
                                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                                {{ $balance['pending_count'] }} pesanan aktif berjalan di ERP
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Action Button --}}
                                @if($store->status === 'connected')
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-primary btn-sm flex-grow-1 rounded-2 py-2 fw-semibold">
                                            <i class="fas fa-history me-1.5"></i> Lihat Mutasi Dompet
                                        </a>
                                        <a href="{{ route('finance.marketplace_wallets.sync', [$store, 'days' => 60]) }}" class="btn btn-outline-secondary btn-sm px-2.5 rounded-2 py-2" title="Tarik data mutasi & sinkronkan saldo toko ini" onclick="return confirm('Tarik data mutasi terbaru dari toko {{ $store->store_name }}?')">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                    </div>
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
