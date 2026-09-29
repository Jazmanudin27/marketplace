@extends('v2.layouts.app')

@section('title', 'Saldo Dompet Marketplace')

@push('styles')
<style>
/* ─── Saldo Marketplace V2 Custom Styles ─── */
.smk-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.smk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
}
.smk-kpi-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 6px;
}
.smk-kpi-value {
    font-size: 1.45rem;
    font-weight: 800;
    line-height: 1.25;
    margin-bottom: 4px;
}
.smk-kpi-sub {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 4px;
}
.smk-kpi-icon {
    position: absolute;
    right: 18px;
    bottom: 16px;
    font-size: 2.4rem;
    opacity: 0.14;
    pointer-events: none;
}

/* Store Balance Card */
.store-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.store-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
}
.store-card-header {
    background: #ffffff;
    padding: 16px 18px;
    border-bottom: 1px solid #f1f5f9;
}
.channel-icon-shopee {
    background: linear-gradient(135deg, #ee4d2d, #ff6b35);
    color: #ffffff;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}
.channel-icon-tiktok {
    background: linear-gradient(135deg, #000000, #1f2937);
    color: #ffffff;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}
.channel-icon-default {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #ffffff;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}
.balance-display-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 16px;
}

/* Button Hover Transitions */
.btn-smk-pending {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
    padding: 9px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.15s ease;
}
.btn-smk-pending:hover {
    background: #fef3c7;
    color: #92400e;
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
        <a href="{{ route('v2.saldo_marketplace.index', ['refresh' => 1]) }}" class="btn btn-sm py-2 px-3 shadow-sm fw-semibold text-white" style="background:#1e293b; border:none; border-radius:8px;" title="Ambil ulang saldo real-time langsung dari API Shopee & TikTok">
            <i class="bi bi-arrow-repeat me-1.5"></i> Refresh Saldo Real-Time
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
<div class="row g-3 mb-4">
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
            <div class="smk-kpi-title text-warning">Total Saldo Tertahan (Akan Dilepas)</div>
            <div class="smk-kpi-value text-warning">
                Rp {{ number_format($totalPendingBalance, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">{{ number_format($totalPendingCount, 0, ',', '.') }} pesanan aktif belum selesai / pending settlement</div>
            <i class="bi bi-hourglass-split smk-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Estimasi Total Dana -->
    <div class="col-12 col-md-4">
        <div class="smk-kpi-card border-start border-success border-4">
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
                    <div class="d-flex align-items-center overflow-hidden">
                        @if($channelCode === 'shopee')
                            <div class="channel-icon-shopee">
                                <i class="bi bi-bag-fill fs-5"></i>
                            </div>
                        @elseif($channelCode === 'tiktok')
                            <div class="channel-icon-tiktok">
                                <i class="bi bi-tiktok fs-5"></i>
                            </div>
                        @else
                            <div class="channel-icon-default">
                                <i class="bi bi-shop fs-5"></i>
                            </div>
                        @endif

                        <div class="overflow-hidden">
                            <h6 class="mb-0 fw-bold text-dark text-truncate" title="{{ $store->store_name }}" style="font-size: 0.95rem;">
                                {{ $store->store_name }}
                            </h6>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5" style="font-size: 0.65rem; font-weight: 700;">
                                    {{ strtoupper($channelCode) }}
                                </span>
                                <span class="text-muted" style="font-size: 0.73rem;">
                                    #{{ $store->marketplace_store_id }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Toko -->
                    <div class="ps-2 flex-shrink-0">
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
                <div class="p-3.5 flex-grow-1 d-flex flex-column" style="padding: 18px;">
                    <div class="balance-display-box mb-3 mt-auto">
                        <!-- Saldo Dompet -->
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                                SALDO DAPAT DITARIK
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.65rem; font-weight: 700;">
                                Siap Tarik
                            </span>
                        </div>
                        <h4 class="fw-extrabold mb-1 text-dark" style="font-size: 1.4rem; font-weight: 800;">
                            Rp {{ number_format($balance['withdraw_balance'] ?? $balance['current_balance'], 0, ',', '.') }}
                        </h4>
                        @if(($balance['current_balance'] ?? 0) != ($balance['withdraw_balance'] ?? 0))
                            <div class="d-flex justify-content-between text-secondary small mb-1" style="font-size: 0.73rem;">
                                <span>Total Saldo Akun:</span>
                                <strong class="text-dark">Rp {{ number_format($balance['current_balance'], 0, ',', '.') }}</strong>
                            </div>
                        @endif

                        <!-- Saldo Pending / Tertahan -->
                        <div class="border-top border-dashed pt-3 mt-3" style="border-top: 1px dashed #cbd5e1;">
                            <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.8rem;">
                                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-dark text-decoration-none fw-semibold d-flex align-items-center gap-1.5" title="Lihat rincian pesanan pending">
                                    <i class="bi bi-hourglass-split text-warning me-1"></i> <span>Saldo Tertahan:</span>
                                </a>
                                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="text-decoration-none">
                                    <strong class="text-warning fw-bold" style="font-size: 0.92rem;">
                                        Rp {{ number_format($balance['pending_balance'], 0, ',', '.') }}
                                    </strong>
                                </a>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.74rem;">
                                <span>Pesanan berjalan:</span>
                                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-semibold text-decoration-none">
                                    {{ $balance['pending_count'] }} Pesanan <i class="bi bi-chevron-right ms-0.5"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Total Estimasi -->
                        <div class="d-flex justify-content-between align-items-center border-top pt-2.5 mt-2.5 text-secondary" style="border-top: 1px solid #e2e8f0; font-size: 0.8rem;">
                            <span class="fw-bold text-dark">Estimasi Total Dana:</span>
                            <strong class="text-primary fw-bold" style="font-size: 0.95rem;">
                                Rp {{ number_format($balance['total_estimated'], 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    @if($store->status === 'connected')
                        <div class="d-flex flex-column gap-2 mt-auto" style="gap: 10px;">
                            <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn-smk-pending">
                                <i class="bi bi-list-ul me-1"></i> Rincian Saldo Tertahan ({{ $balance['pending_count'] }})
                            </a>
                            <div class="d-flex gap-2">
                                <a href="{{ route('v2.saldo_marketplace.mutasi', $store) }}" class="btn btn-sm btn-v2-primary flex-grow-1 py-2 fw-semibold d-flex align-items-center justify-content-center gap-1.5" style="border-radius: 8px;">
                                    <i class="bi bi-clock-history"></i> Lihat Mutasi Dompet
                                </a>
                                <a href="{{ route('v2.saldo_marketplace.sync', [$store, 'days' => 60]) }}" class="btn btn-sm py-2 px-2.5 fw-semibold d-flex align-items-center justify-content-center" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius: 8px;" title="Sinkronkan saldo toko ini" onclick="return confirm('Tarik data mutasi terbaru dari toko {{ $store->store_name }}?')">
                                    <i class="bi bi-arrow-repeat fs-6"></i>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2 mt-auto" style="gap: 10px;">
                            <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn-smk-pending">
                                <i class="bi bi-list-ul me-1"></i> Rincian Saldo Tertahan ({{ $balance['pending_count'] }})
                            </a>
                            <button class="btn btn-sm py-2 fw-semibold d-flex align-items-center justify-content-center gap-1.5" style="background:#f1f5f9; color:#94a3b8; border:1px solid #cbd5e1; border-radius: 8px;" disabled>
                                <i class="bi bi-plug"></i> Offline
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
