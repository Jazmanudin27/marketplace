@extends('v2.layouts.app')

@section('title', 'Saldo Dompet Marketplace')

@push('styles')
<style>
/* ─── Saldo Marketplace V2 Custom Styles ─── */
.smk-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.smk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
}
.smk-kpi-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 4px;
}
.smk-kpi-value {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 2px;
}
.smk-kpi-sub {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 2px;
}
.smk-kpi-icon {
    position: absolute;
    right: 14px;
    bottom: 12px;
    font-size: 2.2rem;
    opacity: 0.12;
    pointer-events: none;
}

/* Store Balance Card */
.store-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.store-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}
.store-card-header {
    background: #f8fafc;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
}
.channel-icon-shopee {
    background: linear-gradient(135deg, #ee4d2d, #ff6b35);
    color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-size: 1.1rem;
}
.channel-icon-tiktok {
    background: linear-gradient(135deg, #000000, #1f2937);
    color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-size: 1.1rem;
}
.channel-icon-default {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-size: 1.1rem;
}
.balance-display-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 12px;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0 fs-5">
            <i class="bi bi-wallet2 text-primary"></i> Saldo Marketplace
        </h1>
        <div class="text-muted small">Monitor real-time saldo dompet & saldo tertahan (escrow) toko online</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.saldo_marketplace.index', ['refresh' => 1]) }}" class="btn btn-sm btn-dark shadow-sm fw-semibold" title="Ambil ulang saldo real-time langsung dari API Shopee & TikTok">
            <i class="bi bi-arrow-repeat me-1"></i> Refresh Saldo Real-Time
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 py-2 px-3 small border-0 shadow-sm" role="alert" style="border-radius:8px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-1.5"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Saldo Siap Ditarik -->
    <div class="col-12 col-md-4">
        <div class="smk-kpi-card border-start border-primary border-4">
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
        <div class="smk-kpi-card border-start border-warning border-4">
            <div class="smk-kpi-title text-warning">Total Saldo Tertahan</div>
            <div class="smk-kpi-value text-warning">
                Rp {{ number_format($totalPendingBalance, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">{{ number_format($totalPendingCount, 0, ',', '.') }} pesanan aktif belum selesai</div>
            <i class="bi bi-hourglass-split smk-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Estimasi Total Dana -->
    <div class="col-12 col-md-4">
        <div class="smk-kpi-card border-start border-success border-4">
            <div class="smk-kpi-title text-success">Estimasi Total Dana</div>
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
                    <div class="d-flex align-items-center overflow-hidden">
                        @if($channelCode === 'shopee')
                            <div class="channel-icon-shopee">
                                <i class="bi bi-bag-fill"></i>
                            </div>
                        @elseif($channelCode === 'tiktok')
                            <div class="channel-icon-tiktok">
                                <i class="bi bi-tiktok"></i>
                            </div>
                        @else
                            <div class="channel-icon-default">
                                <i class="bi bi-shop"></i>
                            </div>
                        @endif

                        <div class="overflow-hidden">
                            <h6 class="mb-0 fw-bold text-dark text-truncate" title="{{ $store->store_name }}" style="font-size: 0.88rem;">
                                {{ $store->store_name }}
                            </h6>
                            <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border rounded-pill px-2 py-0.5" style="font-size: 0.62rem; font-weight: 700;">
                                    {{ strtoupper($channelCode) }}
                                </span>
                                <span class="text-muted" style="font-size: 0.7rem;">
                                    #{{ $store->marketplace_store_id }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Toko -->
                    <div class="ps-2 flex-shrink-0">
                        @if($store->status === 'connected')
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.65rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.4rem;"></i> Terhubung
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.65rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.4rem;"></i> Terputus
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Body Toko -->
                <div class="p-3 flex-grow-1 d-flex flex-column">
                    <div class="balance-display-box mb-3 mt-auto">
                        <!-- Saldo Dompet -->
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.04em;">
                                SALDO DAPAT DITARIK
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.62rem; font-weight: 700;">
                                Siap Tarik
                            </span>
                        </div>
                        <h5 class="fw-extrabold mb-1 text-dark" style="font-size: 1.25rem; font-weight: 800;">
                            Rp {{ number_format($balance['withdraw_balance'] ?? $balance['current_balance'], 0, ',', '.') }}
                        </h5>
                        @if(($balance['current_balance'] ?? 0) != ($balance['withdraw_balance'] ?? 0))
                            <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 0.7rem;">
                                <span>Total Saldo Akun:</span>
                                <strong class="text-dark">Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}</strong>
                            </div>
                        @endif

                        <!-- Saldo Pending / Tertahan -->
                        <div class="border-top border-dashed pt-2.5 mt-2.5" style="border-top: 1px dashed #cbd5e1;">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.76rem;">
                                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-dark text-decoration-none fw-semibold d-flex align-items-center gap-1" title="Lihat rincian pesanan pending">
                                    <i class="bi bi-hourglass-split text-warning"></i> <span>Saldo Tertahan:</span>
                                </a>
                                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-decoration-none">
                                    <strong class="text-warning-emphasis fw-bold" style="font-size: 0.88rem;">
                                        Rp {{ number_format($balance['pending_balance'], 0, ',', '.') }}
                                    </strong>
                                </a>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-0.5 text-muted" style="font-size: 0.7rem;">
                                <span>Pesanan berjalan:</span>
                                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 fw-semibold text-decoration-none" style="font-size:0.65rem;">
                                    {{ $balance['pending_count'] }} Pesanan <i class="bi bi-chevron-right ms-0.5"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Total Estimasi -->
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 text-secondary" style="border-top: 1px solid #e2e8f0; font-size: 0.75rem;">
                            <span class="fw-bold text-dark">Estimasi Total Dana:</span>
                            <strong class="text-primary fw-bold" style="font-size: 0.88rem;">
                                Rp {{ number_format($balance['total_estimated'], 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    @if($store->status === 'connected')
                        <div class="d-flex flex-column gap-1.5 mt-auto">
                            <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn btn-sm btn-outline-warning text-dark-emphasis fw-semibold py-1 px-2.5 d-flex align-items-center justify-content-center gap-1" style="font-size:0.75rem; border-radius:6px;">
                                <i class="bi bi-list-ul"></i> Rincian Saldo Tertahan ({{ $balance['pending_count'] }})
                            </a>
                            <div class="d-flex gap-1.5">
                                <a href="{{ route('v2.saldo_marketplace.mutasi', $store) }}" class="btn btn-sm btn-v2-primary flex-grow-1 py-1 px-2.5 fw-semibold d-flex align-items-center justify-content-center gap-1" style="font-size:0.75rem; border-radius:6px;">
                                    <i class="bi bi-clock-history"></i> Mutasi Dompet
                                </a>
                                <a href="{{ route('v2.saldo_marketplace.sync', [$store, 'days' => 60]) }}" class="btn btn-sm btn-light border py-1 px-2 fw-semibold d-flex align-items-center justify-content-center" style="font-size:0.75rem; border-radius:6px;" title="Sinkronkan saldo toko ini" onclick="return confirm('Tarik data mutasi terbaru dari toko {{ $store->store_name }}?')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-1.5 mt-auto">
                            <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn btn-sm btn-outline-warning text-dark-emphasis fw-semibold py-1 px-2.5 d-flex align-items-center justify-content-center gap-1" style="font-size:0.75rem; border-radius:6px;">
                                <i class="bi bi-list-ul"></i> Rincian Saldo Tertahan ({{ $balance['pending_count'] }})
                            </a>
                            <button class="btn btn-sm btn-light border text-muted py-1 fw-semibold d-flex align-items-center justify-content-center gap-1" style="font-size:0.75rem; border-radius:6px;" disabled>
                                <i class="bi bi-plug"></i> Offline
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="v2-card p-4 text-center shadow-sm">
                <i class="bi bi-wallet2 fs-2 text-secondary opacity-50 mb-2 d-block"></i>
                <h6 class="fw-bold text-dark">Belum Ada Toko Terhubung</h6>
                <p class="text-muted small mb-0">Hubungkan toko Shopee/TikTok Anda di menu Toko Terhubung untuk memantau saldo dompet.</p>
            </div>
        </div>
    @endforelse
</div>

@endsection
