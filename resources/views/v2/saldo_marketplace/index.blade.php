@extends('v2.layouts.app')

@section('title', 'Saldo Dompet Marketplace')

@push('styles')
<style>
/* ─── Saldo Marketplace V2 Custom Styles ─── */
.smk-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.smk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.smk-kpi-title {
    font-size: 0.73rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.smk-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.smk-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.smk-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}

/* Store Balance Card */
.store-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.store-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}
.store-card-header {
    background: #ffffff;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.channel-icon-shopee {
    background: linear-gradient(135deg, #ee4d2d, #ff6b35);
    color: #ffffff;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.channel-icon-tiktok {
    background: linear-gradient(135deg, #000000, #1f2937);
    color: #ffffff;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.channel-icon-default {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #ffffff;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.balance-display-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-wallet2 text-primary fs-5"></i> Saldo Marketplace
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.saldo_marketplace.index', ['refresh' => 1]) }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#1e293b; border:none;" title="Ambil ulang saldo real-time langsung dari API Shopee & TikTok">
            <i class="bi bi-arrow-repeat me-1"></i> Refresh Saldo Real-Time
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Saldo Siap Ditarik -->
    <div class="col-12 col-md-4">
        <div class="smk-kpi-card border-start border-primary border-3">
            <div class="smk-kpi-title text-primary">Total Saldo Siap Ditarik</div>
            <div class="smk-kpi-value text-primary">
                Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Saldo dompet yang dapat ditarik dari seluruh toko</div>
            <i class="bi bi-wallet2 smk-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Total Saldo Tertahan -->
    <div class="col-12 col-md-4">
        <div class="smk-kpi-card border-start border-warning border-3">
            <div class="smk-kpi-title text-warning">Total Saldo Tertahan (Akan Dilepas)</div>
            <div class="smk-kpi-value text-warning">
                Rp {{ number_format($totalPendingBalance, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">{{ $totalPendingCount }} pesanan aktif belum selesai / pending settlement</div>
            <i class="bi bi-hourglass-split smk-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Estimasi Total Dana -->
    <div class="col-12 col-md-4">
        <div class="smk-kpi-card border-start border-success border-3">
            <div class="smk-kpi-title text-success">Estimasi Total Dana Marketplace</div>
            <div class="smk-kpi-value text-success">
                Rp {{ number_format($totalWalletBalance + $totalPendingBalance, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Akumulasi Saldo Dompet + Saldo Tertahan</div>
            <i class="bi bi-piggy-bank smk-kpi-icon text-success"></i>
        </div>
    </div>
</div>

{{-- ── Store Balance Grid Cards ── --}}
<div class="row g-3 mb-3">
    @forelse($storeBalances as $sb)
        @php
            $store = $sb['store'];
            $balance = $sb['balance'];
            $channelCode = strtolower($store->channel->code ?? 'other');
        @endphp
        <div class="col-12 col-md-6 col-lg-4">
            <div class="store-card h-100 d-flex flex-column shadow-sm">
                <!-- Header Toko -->
                <div class="store-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                        @if($channelCode === 'shopee')
                            <div class="channel-icon-shopee flex-shrink-0">
                                <i class="bi bi-bag-fill fs-5"></i>
                            </div>
                        @elseif($channelCode === 'tiktok')
                            <div class="channel-icon-tiktok flex-shrink-0">
                                <i class="bi bi-tiktok fs-5"></i>
                            </div>
                        @else
                            <div class="channel-icon-default flex-shrink-0">
                                <i class="bi bi-shop fs-5"></i>
                            </div>
                        @endif

                        <div class="overflow-hidden">
                            <h6 class="mb-0 fw-bold text-dark text-truncate" title="{{ $store->store_name }}" style="font-size:0.88rem;">
                                {{ $store->store_name }}
                            </h6>
                            <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5" style="font-size: 0.65rem; font-weight:600;">
                                    {{ strtoupper($channelCode) }}
                                </span>
                                <span class="text-muted" style="font-size: 0.72rem;">
                                    #{{ $store->marketplace_store_id }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Toko -->
                    <div>
                        @if($store->status === 'connected')
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Terhubung
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Terputus
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Body Toko -->
                <div class="p-3 flex-grow-1 d-flex flex-column">
                    <div class="balance-display-box mb-3 mt-auto">
                        @if($balance['success'])
                            <!-- Saldo Dompet -->
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.03em;">
                                    SALDO DAPAT DITARIK
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem; font-weight:600;">
                                    Siap Tarik
                                </span>
                            </div>
                            <h4 class="fw-bold mb-1 text-dark" style="font-size:1.25rem;">
                                Rp {{ number_format($balance['withdraw_balance'] ?? $balance['current_balance'], 0, ',', '.') }}
                            </h4>
                            @if(($balance['current_balance'] ?? 0) != ($balance['withdraw_balance'] ?? 0))
                                <div class="d-flex justify-content-between text-secondary small mb-1" style="font-size: 0.73rem;">
                                    <span>Total Saldo Akun:</span>
                                    <strong class="text-dark">Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}</strong>
                                </div>
                            @endif

                            <!-- Saldo Pending -->
                            <div class="border-top pt-2.5 mt-2.5">
                                <div class="d-flex justify-content-between align-items-center text-secondary" style="font-size: 0.78rem;">
                                    <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-dark text-decoration-none fw-semibold" title="Lihat rincian pesanan pending">
                                        <i class="bi bi-hourglass-split me-1 text-warning"></i> Saldo Tertahan:
                                    </a>
                                    <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-decoration-none">
                                        <strong class="text-warning fw-bold" style="font-size:0.85rem;">
                                            Rp {{ number_format($balance['pending_balance'], 0, ',', '.') }}
                                        </strong>
                                    </a>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.72rem;">
                                    <span>Pesanan berjalan:</span>
                                    <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 fw-semibold text-decoration-none">
                                        {{ $balance['pending_count'] }} Pesanan <i class="bi bi-chevron-right ms-0.5"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Total Estimasi -->
                            <div class="d-flex justify-content-between align-items-center border-top pt-2.5 mt-2.5 text-secondary" style="font-size: 0.78rem;">
                                <span class="fw-bold text-dark">Estimasi Total Dana:</span>
                                <strong class="text-primary fw-bold" style="font-size:0.88rem;">
                                    Rp {{ number_format($balance['total_estimated'], 0, ',', '.') }}
                                </strong>
                            </div>
                        @else
                            <div class="text-danger small py-1" style="font-size: 0.75rem;">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                Status API Marketplace: <br>
                                <span class="fw-semibold text-break">{{ Str::limit($balance['error_message'], 60) }}</span>
                            </div>
                            <div class="border-top pt-2 mt-2">
                                <div class="d-flex justify-content-between align-items-center text-secondary" style="font-size: 0.78rem;">
                                    <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-dark text-decoration-none fw-semibold">
                                        <i class="bi bi-hourglass-split me-1 text-warning"></i> Saldo Pending ERP:
                                    </a>
                                    <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-decoration-none">
                                        <strong class="text-warning fw-bold" style="font-size:0.85rem;">
                                            Rp {{ number_format($balance['pending_balance'], 0, ',', '.') }}
                                        </strong>
                                    </a>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.72rem;">
                                    <span>Pesanan berjalan:</span>
                                    <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 fw-semibold text-decoration-none">
                                        {{ $balance['pending_count'] }} Pesanan <i class="bi bi-chevron-right ms-0.5"></i>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    @if($store->status === 'connected')
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn btn-sm py-1.5 fw-semibold text-center" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                                <i class="bi bi-list-ul me-1"></i> Rincian Saldo Tertahan ({{ $balance['pending_count'] }})
                            </a>
                            <div class="d-flex gap-2">
                                <a href="{{ route('v2.saldo_marketplace.mutasi', $store) }}" class="btn btn-sm btn-v2-primary flex-grow-1 py-1.5 fw-semibold">
                                    <i class="bi bi-clock-history me-1"></i> Lihat Mutasi Dompet
                                </a>
                                <a href="{{ route('v2.saldo_marketplace.sync', [$store, 'days' => 60]) }}" class="btn btn-sm py-1.5 px-2.5 fw-semibold" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;" title="Sinkronkan saldo toko ini" onclick="return confirm('Tarik data mutasi terbaru dari toko {{ $store->store_name }}?')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn btn-sm py-1.5 fw-semibold text-center" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                                <i class="bi bi-list-ul me-1"></i> Rincian Saldo Tertahan ({{ $balance['pending_count'] }})
                            </a>
                            <button class="btn btn-sm py-1.5 fw-semibold" style="background:#f1f5f9; color:#94a3b8; border:1px solid #cbd5e1;" disabled>
                                <i class="bi bi-plug me-1"></i> Offline
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="v2-card p-5 text-center shadow-sm">
                <i class="bi bi-wallet2 fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">Belum Ada Toko Terhubung</h5>
                <p class="text-muted small mb-0">Hubungkan toko Shopee/TikTok Anda di menu Toko Terhubung untuk memantau saldo dompet.</p>
            </div>
        </div>
    @endforelse
</div>

@endsection
