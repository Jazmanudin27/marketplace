@extends('layouts.app')

@section('title', 'Marketplace Affiliate Tracker')
@section('page-title', 'Marketplace Affiliate Tracker')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
@php
    // TikTok Stats
    $ttOrders = $affiliates->sum('total_orders');
    $ttRevenue = $affiliates->sum('total_revenue');
    $ttCommission = $affiliates->sum('total_commission');
    $ttCreators = $affiliates->count();

    // Shopee Stats
    $shOrders = $shopeeAffiliates->sum('total_orders');
    $shRevenue = $shopeeAffiliates->sum('total_revenue');
    $shDiscounts = $shopeeAffiliates->sum('total_discounts');
    $shInfluencers = $shopeeAffiliates->count();
@endphp

<!-- Statistics Overview Tabs -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border border-start border-4 border-dark shadow-sm">
            <div class="card-body p-3 py-2.5">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge bg-dark text-white text-uppercase" style="font-size: .6rem; letter-spacing: .5px;">TikTok Shop Affiliate</span>
                        <h4 class="fw-extrabold text-dark mb-0 mt-1.5">Rp {{ number_format($ttRevenue, 0, ',', '.') }}</h4>
                        <div class="text-muted small mt-1" style="font-size: .75rem;">
                            {{ $ttCreators }} Kreator | {{ $ttOrders }} Pesanan | Komisi: Rp{{ number_format($ttCommission, 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="bg-dark bg-opacity-10 text-dark rounded-3 d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="bi bi-tiktok fs-4"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border border-start border-4 border-danger shadow-sm">
            <div class="card-body p-3 py-2.5">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge bg-danger bg-opacity-10 text-danger text-uppercase" style="font-size: .6rem; letter-spacing: .5px;">Shopee UTM Affiliate</span>
                        <h4 class="fw-extrabold text-danger mb-0 mt-1.5">Rp {{ number_format($shRevenue, 0, ',', '.') }}</h4>
                        <div class="text-muted small mt-1" style="font-size: .75rem;">
                            {{ $shInfluencers }} Campaign | {{ $shOrders }} Pesanan | Diskon: Rp{{ number_format($shDiscounts, 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="bg-danger bg-opacity-10 text-danger rounded-3 d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="bi bi-shop fs-4"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    
    {{-- ══ LEFT COLUMN: TABS FOR PLATFORMS ══ --}}
    <div class="col-lg-8">
        
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <ul class="nav nav-tabs card-header-tabs" id="affiliateTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark border-0 py-2.5 px-3" id="tiktok-tab" data-bs-toggle="tab" data-bs-target="#tiktok" type="button" role="tab" aria-controls="tiktok" aria-selected="true">
                            <i class="bi bi-tiktok me-1.5"></i> TikTok Affiliate Creators
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-secondary border-0 py-2.5 px-3" id="shopee-tab" data-bs-toggle="tab" data-bs-target="#shopee" type="button" role="tab" aria-controls="shopee" aria-selected="false">
                            <i class="bi bi-bag-check me-1.5 text-danger"></i> Shopee UTM Campaign
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content" id="affiliateTabContent">
                
                {{-- 📍 TAB 1: TIKTOK CREATORS --}}
                <div class="tab-pane fade show active" id="tiktok" role="tabpanel" aria-labelledby="tiktok-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light border-top-0">
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

                {{-- 📍 TAB 2: SHOPEE UTM INFLUENCERS --}}
                <div class="tab-pane fade" id="shopee" role="tabpanel" aria-labelledby="shopee-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light border-top-0">
                                <tr class="text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.5px; font-weight:700;">
                                    <th class="px-3 py-2.5">UTM Keyword / Campaign</th>
                                    <th class="px-3 py-2.5">Total Orders</th>
                                    <th class="px-3 py-2.5">Total Omzet</th>
                                    <th class="px-3 py-2.5">Diskon Voucher</th>
                                    <th class="px-3 py-2.5">Rasio AOV</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shopeeAffiliates as $shAff)
                                    @php
                                        $aov = $shAff->total_orders > 0 ? $shAff->total_revenue / $shAff->total_orders : 0;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center"
                                                    style="width:28px;height:28px;flex-shrink:0;">
                                                    <i class="bi bi-link-45deg small"></i>
                                                </span>
                                                <strong class="text-dark">{{ $shAff->shopee_utm_keyword }}</strong>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-dark fw-semibold">
                                            {{ number_format($shAff->total_orders) }} pesanan
                                        </td>
                                        <td class="px-3 py-3 fw-bold text-success">
                                            Rp {{ number_format($shAff->total_revenue, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3 text-danger fw-semibold">
                                            Rp {{ number_format($shAff->total_discounts, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold rounded-pill px-2.5 py-1">
                                                Rp {{ number_format($aov, 0, ',', '.') }} AOV
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-link-45deg d-block fs-1 mb-2 opacity-25"></i>
                                            <div class="fw-bold text-dark small mb-1">Belum Ada Data Shopee UTM Affiliate</div>
                                            <div class="small">Data akan terpopulasi otomatis saat order Shopee yang memiliki link UTM ditarik.</div>
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

    {{-- ══ RIGHT COLUMN: RECENT INTEGRATED ORDERS ══ --}}
    <div class="col-lg-4">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-clock-history text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Pesanan Affiliate Terbaru</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            Real-time order TikTok & Shopee
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
                                <span class="badge {{ $order->store->channel->code === 'shopee' ? 'bg-danger' : 'bg-dark' }} text-white px-2 py-0.5 rounded" style="font-size:.7rem;">
                                    {{ $order->store->store_name }}
                                </span>
                            </div>
                            
                            @if($order->tiktok_creator_name)
                                <div class="d-flex align-items-center gap-1.5 mt-2 bg-light p-1.5 rounded-3">
                                    <span class="badge bg-dark rounded-circle px-1.5 py-0.5 text-uppercase" style="font-size:.55rem;">
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
                            @elseif($order->shopee_utm_keyword)
                                <div class="d-flex align-items-center gap-1.5 mt-2 bg-light p-1.5 rounded-3">
                                    <span class="badge bg-danger rounded-circle px-1.5 py-0.5 text-uppercase" style="font-size:.55rem;">
                                        Shopee
                                    </span>
                                    <div style="font-size:.76rem;">
                                        UTM: <strong class="text-danger">{{ $order->shopee_utm_keyword }}</strong>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:.78rem;">
                                    <div class="text-muted">Potongan: <span class="text-danger fw-semibold">Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span></div>
                                    <div class="fw-bold text-success">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</div>
                                </div>
                            @endif
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

<!-- Bootstrap 5 JS support for tabs -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var triggerTabList = [].slice.call(document.querySelectorAll('#affiliateTab button'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })
    });
</script>
@endsection
