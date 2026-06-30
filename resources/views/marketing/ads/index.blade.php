@extends('layouts.app')

@section('title', 'Dashboard Keputusan Iklan')
@section('page-title', 'Dashboard Keputusan Iklan')

@section('topbar-actions')
    <form action="{{ route('marketing.ads.sync') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-light text-primary fw-bold px-3 me-2">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Semua Iklan
        </button>
    </form>
    <a href="{{ route('marketing.ads.logs') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-plus-circle me-1"></i> Input Biaya Harian
    </a>
    <a href="{{ route('marketing.ads.audiences') }}" class="btn btn-sm btn-light text-primary fw-bold px-3 ms-2">
        <i class="bi bi-people me-1"></i> TikTok Audience
    </a>
    <a href="{{ route('marketing.ads.budget_rules') }}" class="btn btn-sm btn-light text-primary fw-bold px-3 ms-2">
        <i class="bi bi-alarm me-1"></i> Smart Budget Rules
    </a>
    <a href="{{ route('marketing.ads.affiliates') }}" class="btn btn-sm btn-light text-primary fw-bold px-3 ms-2">
        <i class="bi bi-award me-1"></i> TikTok Affiliate Tracker
    </a>
    <a href="{{ route('marketing.ads.live_sessions') }}" class="btn btn-sm btn-light text-primary fw-bold px-3 ms-2">
        <i class="bi bi-broadcast me-1"></i> TikTok LIVE Tracker
    </a>
    <a href="{{ route('marketing.ads.campaigns') }}" class="btn btn-sm btn-outline-light fw-bold px-3 ms-2">
        <i class="bi bi-gear me-1"></i> Atur Target Campaign
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">

            <!-- Unread Budget Alerts -->
            @if(isset($unreadAlerts) && $unreadAlerts->count() > 0)
                <div class="alert alert-danger border shadow-sm rounded-3 mb-3 d-flex flex-column gap-2" role="alert">
                    <div class="d-flex align-items-center justify-content-between">
                        <strong class="d-flex align-items-center gap-2 text-danger-emphasis">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            Batas Anggaran & ROAS Terlampaui ({{ $unreadAlerts->count() }} Alert Baru)
                        </strong>
                        <a href="{{ route('marketing.ads.budget_rules') }}" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3" style="font-size:.72rem;">
                            Kelola Budget Rules
                        </a>
                    </div>
                    <div class="row g-2 mt-1">
                        @foreach($unreadAlerts as $alert)
                            <div class="col-12 col-md-6">
                                <div class="bg-white bg-opacity-75 p-2 rounded-3 border border-danger border-opacity-25 d-flex align-items-start justify-content-between gap-2">
                                    <div style="font-size: .8rem;">
                                        <span class="badge bg-danger rounded-pill text-uppercase px-2 py-0.5 me-1" style="font-size: .65rem;">
                                            {{ strtoupper($alert->campaign->adsAccount->platform) }}
                                        </span>
                                        <strong class="text-dark">{{ $alert->campaign->name }}</strong>
                                        <div class="text-muted mt-1">{{ $alert->message }}</div>
                                    </div>
                                    <form action="{{ route('marketing.ads.budget_alerts.read', $alert->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary border-0 p-1" title="Tandai Dibaca">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Alert / Recommendations -->
            @if (count($recommendations) > 0)
                <div class="card border shadow-sm mb-3">
                    <div
                        class="card-header bg-warning bg-opacity-10 d-flex align-items-center justify-content-between p-3 border-bottom">
                        <span class="fw-bold text-warning-emphasis d-flex align-items-center gap-2">
                            <i class="bi bi-cpu-fill fs-5 text-warning"></i> Rekomendasi Optimasi Otomatis (Semi-Auto)
                        </span>
                        <span
                            class="badge bg-warning text-dark px-3 py-1.5 rounded-pill small fw-semibold">{{ count($recommendations) }}
                            Rekomendasi</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            @foreach ($recommendations as $rec)
                                <div class="col-md-6">
                                    <div
                                        class="p-3 rounded-3 border border-light-subtle bg-light bg-opacity-50 d-flex align-items-center justify-content-between">
                                        <div class="pe-2">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @php
                                                    $pf = $rec['campaign']->adsAccount->platform;
                                                    $pfBadge = 'bg-secondary';
                                                    if ($pf === 'meta') {
                                                        $pfBadge = 'bg-primary';
                                                    } elseif ($pf === 'google') {
                                                        $pfBadge = 'bg-danger';
                                                    } elseif ($pf === 'tiktok') {
                                                        $pfBadge = 'bg-dark';
                                                    }
                                                @endphp
                                                <span class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded"
                                                    style="font-size:0.6rem; letter-spacing:0.5px;">
                                                    {{ $pf }}
                                                </span>
                                                <strong class="text-dark small">{{ $rec['campaign']->name }}</strong>
                                            </div>
                                            <div class="text-muted small mt-1.5" style="font-size: 0.8rem;">
                                                <i
                                                    class="bi bi-exclamation-circle me-1 text-{{ $rec['severity'] }}"></i>{{ $rec['issue'] }}
                                            </div>
                                        </div>
                                        <div>
                                            @if ($rec['action_code'] === 'pause')
                                                <form action="{{ route('marketing.ads.toggle', $rec['campaign']->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger px-3 py-1.5 rounded-3 fw-semibold text-nowrap">
                                                        <i class="bi bi-pause-circle me-1"></i> Jeda Iklan
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    class="btn btn-sm btn-success px-3 py-1.5 rounded-3 fw-semibold text-nowrap"
                                                    onclick="alert('Skala budget di Meta/Google disimulasikan: Budget dinaikkan sebesar +20% di platform pengiklan.')">
                                                    <i class="bi bi-arrow-up-circle me-1"></i> Naikan Budget
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Overall Statistics -->
            <div class="row g-3 mb-3">
                <!-- Ad Spend -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body p-3 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-secondary text-uppercase fw-bold">Total Biaya Iklan</small>
                                    <div class="d-flex align-items-center mt-1">
                                        <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($totalSpend, 0, ',', '.') }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="fs-2 text-danger opacity-75">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Attributed Revenue -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body p-3 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-secondary text-uppercase fw-bold">Omset Teratribusi</small>
                                    <div class="d-flex align-items-center mt-1">
                                        <h5 class="fw-bold mb-0 text-success">Rp
                                            {{ number_format($totalRevenue, 0, ',', '.') }}</h5>
                                    </div>
                                </div>
                                <div class="fs-2 text-success opacity-75">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Actual ROAS -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border shadow-sm h-100 bg-primary text-white">
                        <div class="card-body p-3 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold">Rata-rata ROAS</small>
                                    <div class="d-flex align-items-center mt-1">
                                        <h5 class="fw-bold mb-0 text-white">{{ number_format($overallRoas, 2) }}x</h5>
                                    </div>
                                </div>
                                <div class="fs-2 text-white opacity-75">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Conversions -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body p-3 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-secondary text-uppercase fw-bold">Jumlah Closing</small>
                                    <div class="d-flex align-items-center mt-1">
                                        <h5 class="fw-bold mb-0 text-dark">{{ $totalConversions }} Order</h5>
                                    </div>
                                </div>
                                <div class="fs-2 text-warning opacity-75">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart & Top Products -->
            <div class="row g-3 mb-3">
                <!-- Chart: Spend vs Revenue -->
                <div class="col-lg-8">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bezier2 me-2 text-primary"></i> Tren
                                Investasi Iklan vs Omset Penjualan (30 Hari Terakhir)</h6>
                        </div>
                        <div class="card-body p-3">
                            <div style="height: 300px;">
                                <canvas id="adsPerformanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products Sold via Ads -->
                <div class="col-lg-4">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-warning bg-opacity-10 p-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-star-fill me-2 text-warning"></i> Produk
                                Paling "Laku Iklan"</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-column gap-3">
                                @forelse($topProducts as $idx => $prod)
                                    <div
                                        class="d-flex align-items-center justify-content-between pb-2.5 {{ !$loop->last ? 'border-bottom border-light' : '' }}">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center fw-bold"
                                                style="width: 36px; height: 36px; font-size: 0.85rem;">
                                                {{ $idx + 1 }}
                                            </div>
                                            <div>
                                                <strong class="text-dark small d-block">{{ $prod->product_name }}</strong>
                                                <small class="text-muted font-monospace" style="font-size:0.75rem;">SKU:
                                                    {{ $prod->sku }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold small px-2.5 py-1">{{ $prod->total_qty }}
                                                pcs</span>
                                            <div class="text-muted mt-1" style="font-size:0.75rem;">Rp
                                                {{ number_format($prod->total_revenue, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5 text-muted small">
                                        <i class="bi bi-info-circle fs-3 d-block mb-2 opacity-50"></i>
                                        Belum ada penjualan teratribusi iklan.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaigns Performance Table -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border shadow-sm">
                        <div
                            class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-table me-2 text-info"></i> Laporan ROAS &
                                Progress Target per Campaign</h6>
                            <a href="{{ route('marketing.ads.campaigns') }}"
                                class="btn btn-sm btn-outline-primary rounded-3 fw-semibold">Kelola Campaign</a>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase fs-7 text-muted"
                                        style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                        <tr>
                                            <th class="border-0 px-3 py-3">Nama Campaign</th>
                                            <th class="border-0 px-3 py-3">Platform</th>
                                            <th class="border-0 px-3 py-3">Total Spend</th>
                                            <th class="border-0 px-3 py-3">Total Omset</th>
                                            <th class="border-0 px-3 py-3">ROAS Riil</th>
                                            <th class="border-0 px-3 py-3">Target ROAS</th>
                                            <th class="border-0 px-3 py-3">Status Target</th>
                                            <th class="border-0 px-3 py-3 text-end">Iklan Platform</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($campaigns as $camp)
                                            @php
                                                $spend = $camp->total_spend;
                                                $rev = $camp->total_revenue;
                                                $roas = $camp->actual_roas;
                                                $targetRoas = (float) $camp->target_roas;
                                            @endphp
                                            <tr class="border-bottom border-light">
                                                <td class="px-3 py-3">
                                                    <strong class="text-dark">{{ $camp->name }}</strong>
                                                </td>
                                                <td class="px-3 py-3">
                                                    @php
                                                        $pf = $camp->adsAccount->platform;
                                                        $pfBadge = 'bg-secondary';
                                                        if ($pf === 'meta') {
                                                            $pfBadge = 'bg-primary';
                                                        } elseif ($pf === 'google') {
                                                            $pfBadge = 'bg-danger';
                                                        } elseif ($pf === 'tiktok') {
                                                            $pfBadge = 'bg-dark';
                                                        }
                                                    @endphp
                                                    <span
                                                        class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded"
                                                        style="font-size:0.65rem;">
                                                        {{ $pf }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3">Rp {{ number_format($spend, 0, ',', '.') }}</td>
                                                <td class="px-3 py-3">Rp {{ number_format($rev, 0, ',', '.') }}</td>
                                                <td class="px-3 py-3">
                                                    <strong
                                                        class="text-{{ $spend > 0 ? ($roas >= $targetRoas ? 'success' : 'danger') : 'muted' }}">
                                                        {{ number_format($roas, 2) }}x
                                                    </strong>
                                                </td>
                                                <td class="px-3 py-3">{{ number_format($targetRoas, 2) }}x</td>
                                                <td class="px-3 py-3">
                                                    @if ($spend <= 0)
                                                        <span
                                                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1 rounded">No
                                                            Data</span>
                                                    @elseif($roas >= $targetRoas)
                                                        <span
                                                            class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded">
                                                            <i class="bi bi-check-circle-fill me-1"></i> AMAN / UNTUNG
                                                        </span>
                                                    @else
                                                        <span
                                                            class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 rounded">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> BOROS
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3 text-end">
                                                    <form action="{{ route('marketing.ads.toggle', $camp->id) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @if ($camp->status === 'ACTIVE')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-success rounded-3 fw-semibold px-2.5 py-1">
                                                                <i class="bi bi-play-fill"></i> Aktif
                                                            </button>
                                                        @else
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-secondary rounded-3 fw-semibold px-2.5 py-1">
                                                                <i class="bi bi-pause-fill"></i> Jeda
                                                            </button>
                                                        @endif
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">
                                                    Belum ada Campaign iklan terdaftar. Klik "Atur Target Campaign" untuk
                                                    menambahkan.
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

            {{-- TikTok & Meta Catalog Feed Link --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="bi bi-rss-fill"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">TikTok & Meta Ads Catalog Feed (XML)</h6>
                                <small class="text-muted" style="font-size:0.75rem;">
                                    Copy URL di bawah dan tempelkan di TikTok Shop Catalog Manager atau Facebook Commerce Manager.
                                </small>
                            </div>
                        </div>
                        <div class="input-group input-group-sm w-auto flex-grow-1" style="max-width: 480px;">
                            <input type="text" class="form-control bg-light rounded-start-3" id="catalogFeedUrl" 
                                value="{{ route('marketing.ads.catalog_feed', Auth::user()->tenant_id) }}" readonly>
                            <button class="btn btn-primary rounded-end-3" type="button" onclick="navigator.clipboard.writeText(document.getElementById('catalogFeedUrl').value); alert('Link berhasil disalin ke clipboard!');">
                                <i class="bi bi-clipboard"></i> Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manual Order Attribution Panel (Auto-Attribution Upgraded) --}}
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border shadow-sm">
                        {{-- Header --}}
                        <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center"
                            style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#06b6d4);">
                                    <i class="bi bi-robot text-white" style="font-size:1rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">Atribusi Pesanan ke Campaign Iklan</h6>
                                    <small class="text-muted" style="font-size:0.72rem;">
                                        Otomatis berjalan setiap jam &bull; Tersisa
                                        <span class="fw-bold text-danger">{{ count($unattributedOrders) }}</span>
                                        pesanan belum terhubung
                                    </small>
                                </div>
                            </div>
                            <form action="{{ route('marketing.ads.auto_attribute') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                    class="btn btn-sm fw-bold px-3 py-1.5 rounded-3 d-flex align-items-center gap-1"
                                    style="background:linear-gradient(135deg,#3b82f6,#06b6d4);color:#fff;font-size:0.82rem;border:none;">
                                    <i class="bi bi-lightning-charge-fill"></i> Jalankan Sekarang
                                </button>
                            </form>
                        </div>

                        <div class="card-body p-3" style="zoom:90%">
                            {{-- Cara kerja sistem --}}
                            <div class="alert alert-light border border-info border-opacity-25 rounded-3 py-2 px-3 mb-3"
                                style="font-size:0.8rem;">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-info-circle-fill text-info mt-0.5"></i>
                                    <div>
                                        <strong class="text-dark">Cara Kerja Atribusi Otomatis (3 Lapisan):</strong>
                                        <div class="d-flex flex-wrap gap-2 mt-1.5">
                                            <span class="badge rounded-pill px-2.5 py-1"
                                                style="background:#eff6ff;color:#1d4ed8;font-size:0.72rem;">
                                                <i class="bi bi-1-circle-fill me-1"></i> UTM Tracking
                                            </span>
                                            <span class="text-muted">→</span>
                                            <span class="badge rounded-pill px-2.5 py-1"
                                                style="background:#f0fdf4;color:#15803d;font-size:0.72rem;">
                                                <i class="bi bi-2-circle-fill me-1"></i> Default Campaign Toko
                                            </span>
                                            <span class="text-muted">→</span>
                                            <span class="badge rounded-pill px-2.5 py-1"
                                                style="background:#fef3c7;color:#92400e;font-size:0.72rem;">
                                                <i class="bi bi-3-circle-fill me-1"></i> Cocokkan Platform (Shopee/TikTok)
                                            </span>
                                        </div>
                                        <div class="text-muted mt-1">
                                            Atur <a href="{{ route('marketing.ads.campaigns') }}"
                                                class="text-primary fw-semibold text-decoration-none">Default Campaign per
                                                Toko</a>
                                            untuk meningkatkan akurasi atribusi.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabel pesanan belum teratribusi (Manual Fallback) --}}
                            @if (count($unattributedOrders) > 0)
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-hand-index me-1"></i>
                                    Pesanan di bawah ini tidak dapat dicocokkan otomatis — tautkan secara manual:
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0"
                                        style="font-size:0.85rem;">
                                        <thead class="table-light text-uppercase fs-7 text-muted"
                                            style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                            <tr>
                                                <th class="border-0 px-3 py-3">Tanggal Order</th>
                                                <th class="border-0 px-3 py-3">No Invoice</th>
                                                <th class="border-0 px-3 py-3">Toko</th>
                                                <th class="border-0 px-3 py-3">Pembeli</th>
                                                <th class="border-0 px-3 py-3">Nominal Bersih</th>
                                                <th class="border-0 px-3 py-3 text-end">Pilih Campaign Iklan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($unattributedOrders as $ord)
                                                <tr class="border-bottom border-light">
                                                    <td class="px-3 py-3">{{ $ord->order_date->format('d/m/Y H:i') }}</td>
                                                    <td class="px-3 py-3">
                                                        <strong class="text-dark">{{ $ord->invoice_number }}</strong>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <span class="badge bg-light text-dark border px-2 py-1 rounded">
                                                            {{ $ord->store->store_name }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-3">{{ $ord->buyer_name }}</td>
                                                    <td class="px-3 py-3">
                                                        <strong class="text-success">
                                                            Rp {{ number_format($ord->net_amount, 0, ',', '.') }}
                                                        </strong>
                                                    </td>
                                                    <td class="px-3 py-3 text-end">
                                                        <form action="{{ route('marketing.ads.attribute') }}"
                                                            method="POST"
                                                            class="d-flex gap-2 align-items-center justify-content-end">
                                                            @csrf
                                                            <input type="hidden" name="order_id"
                                                                value="{{ $ord->id }}">
                                                            <select name="ads_campaign_id"
                                                                class="form-select form-select-sm rounded-3 w-auto border-secondary border-opacity-25"
                                                                required
                                                                style="font-size: 0.8rem; padding: 0.35rem 2rem 0.35rem 0.75rem;">
                                                                <option value="">-- Pilih Campaign --</option>
                                                                @foreach ($campaigns as $cp)
                                                                    <option value="{{ $cp->id }}">
                                                                        {{ $cp->name }}
                                                                        ({{ strtoupper($cp->adsAccount->platform) }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit"
                                                                class="btn btn-sm btn-primary rounded-3 px-3 py-1.5 fw-semibold"
                                                                style="font-size: 0.8rem;">Tautkan</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3"
                                        style="width:56px;height:56px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);">
                                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Semua Pesanan Sudah Teratribusi!</h6>
                                    <p class="text-muted small mb-0">
                                        Tidak ada pesanan yang perlu ditautkan secara manual saat ini.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('adsPerformanceChart').getContext('2d');

            let gradientSpend = ctx.createLinearGradient(0, 0, 0, 220);
            gradientSpend.addColorStop(0, 'rgba(239, 68, 68, 0.15)');
            gradientSpend.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

            let gradientRev = ctx.createLinearGradient(0, 0, 0, 220);
            gradientRev.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
            gradientRev.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                            label: 'Biaya Iklan (Spend)',
                            data: @json($chartSpend),
                            borderColor: '#ef4444',
                            backgroundColor: gradientSpend,
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'Omset Hasil Iklan (Revenue)',
                            data: @json($chartRevenue),
                            borderColor: '#10b981',
                            backgroundColor: gradientRev,
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
