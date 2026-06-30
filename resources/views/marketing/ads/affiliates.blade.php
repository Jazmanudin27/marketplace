@extends('layouts.app')

@section('title', 'TikTok Shop Affiliate Tracker')
@section('page-title', 'TikTok Shop Affiliate Tracker')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
@php
    $totalOrders = $affiliates->sum('total_orders');
    $totalRevenue = $affiliates->sum('total_revenue');
    $totalCommission = $affiliates->sum('total_commission');
    $activeCreators = $affiliates->count();
@endphp

<!-- Statistics Overview -->
<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary fw-bold text-uppercase" style="font-size: .65rem; letter-spacing: .5px;">Kreator Aktif</span>
                        <h4 class="fw-extrabold text-dark mb-0 mt-1">{{ number_format($activeCreators) }}</h4>
                    </div>
                    <span class="bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary fw-bold text-uppercase" style="font-size: .65rem; letter-spacing: .5px;">Pesanan Affiliate</span>
                        <h4 class="fw-extrabold text-dark mb-0 mt-1">{{ number_format($totalOrders) }}</h4>
                    </div>
                    <span class="bg-success bg-opacity-10 text-success rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="bi bi-cart-check-fill fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary fw-bold text-uppercase" style="font-size: .65rem; letter-spacing: .5px;">Omzet Kemitraan</span>
                        <h4 class="fw-extrabold text-success mb-0 mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                    </div>
                    <span class="bg-success bg-opacity-10 text-success rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary fw-bold text-uppercase" style="font-size: .65rem; letter-spacing: .5px;">Total Komisi</span>
                        <h4 class="fw-extrabold text-danger mb-0 mt-1">Rp {{ number_format($totalCommission, 0, ',', '.') }}</h4>
                    </div>
                    <span class="bg-danger bg-opacity-10 text-danger rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="bi bi-percent fs-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    
    {{-- ══ LEFT COLUMN: TOP CREATORS PERFORMANCE ══ --}}
    <div class="col-lg-8">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-award-fill text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Performa Kreator / Affiliate TikTok</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            Diurutkan berdasarkan total omzet yang dihasilkan
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.5px; font-weight:700;">
                                <th class="px-3 py-2.5">Kreator</th>
                                <th class="px-3 py-2.5">TikTok ID</th>
                                <th class="px-3 py-2.5">Total Orders</th>
                                <th class="px-3 py-2.5">Total Omzet</th>
                                <th class="px-3 py-2.5">Komisi Dibayar</th>
                                <th class="px-3 py-2.5">Rasio ROI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($affiliates as $aff)
                                @php
                                    $roi = $aff->total_commission > 0 ? $aff->total_revenue / $aff->total_commission : 0;
                                @endphp
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="bg-dark bg-opacity-10 text-dark rounded-circle d-inline-flex align-items-center justify-content-center"
                                                style="width:28px;height:28px;flex-shrink:0;">
                                                <i class="bi bi-tiktok small"></i>
                                            </span>
                                            <strong class="text-dark">{{ $aff->tiktok_creator_name }}</strong>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <code class="text-secondary small">{{ $aff->tiktok_creator_id }}</code>
                                    </td>
                                    <td class="px-3 py-3 text-dark fw-semibold">
                                        {{ number_format($aff->total_orders) }} pesanan
                                    </td>
                                    <td class="px-3 py-3 fw-bold text-success">
                                        Rp {{ number_format($aff->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-danger fw-semibold">
                                        Rp {{ number_format($aff->total_commission, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold rounded-pill px-2.5 py-1">
                                            {{ number_format($roi, 1) }}x ROI
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-people d-block fs-1 mb-2 opacity-25"></i>
                                        <div class="fw-bold text-dark small mb-1">Belum Ada Data Penjualan Affiliate</div>
                                        <div class="small">Lakukan sync order dari toko TikTok Shop Anda untuk menarik data kreator.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ RIGHT COLUMN: RECENT AFFILIATE ORDERS ══ --}}
    <div class="col-lg-4">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-clock-history text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Pesanan Terbaru dari Affiliate</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            Real-time order tiktok shop affiliate
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 480px; overflow-y: auto;">
                    @forelse($recentOrders as $order)
                        <div class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <strong class="text-dark d-block" style="font-size:.82rem;">{{ $order->invoice_number }}</strong>
                                    <small class="text-muted" style="font-size:.73rem;">{{ $order->order_date->format('d/m/Y H:i') }}</small>
                                </div>
                                <span class="badge bg-light text-dark border px-2 py-0.5 rounded" style="font-size:.7rem;">
                                    {{ $order->store->store_name }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 mt-2 bg-light p-1.5 rounded-3">
                                <span class="badge bg-dark rounded-circle px-1.5 py-0.5 text-uppercase" style="font-size:.6rem;">
                                    TikTok
                                </span>
                                <div style="font-size:.76rem;">
                                    Kreator: <strong class="text-primary">{{ $order->tiktok_creator_name }}</strong>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:.78rem;">
                                <div class="text-muted">Komisi: <span class="text-danger fw-semibold">Rp {{ number_format($order->affiliate_commission, 0, ',', '.') }}</span></div>
                                <div class="fw-bold text-success">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            Tidak ada pesanan terbaru.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
