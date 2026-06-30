@extends('layouts.app')

@section('title', 'Dashboard Keputusan Iklan')
@section('page-title', 'Dashboard Keputusan Iklan')

@section('topbar-actions')
    <!-- Sync Shopee -->
    <form action="{{ route('marketing.ads.shopee.sync') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-light text-primary fw-bold px-3 me-2">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Iklan Shopee
        </button>
    </form>
    <!-- Sync TikTok -->
    <form action="{{ route('marketing.ads.tiktok.sync') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-light text-primary fw-bold px-3 me-2">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Iklan TikTok
        </button>
    </form>
    <a href="{{ route('marketing.ads.logs') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-plus-circle me-1"></i> Input Biaya Harian
    </a>
    <a href="{{ route('marketing.ads.campaigns') }}" class="btn btn-sm btn-outline-light fw-bold px-3 ms-2">
        <i class="bi bi-gear me-1"></i> Atur Target Campaign
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">

        <!-- Alert / Recommendations -->
        @if(count($recommendations) > 0)
            <div class="card border shadow-sm mb-3">
                <div class="card-header bg-warning bg-opacity-10 d-flex align-items-center justify-content-between p-3 border-bottom">
                    <span class="fw-bold text-warning-emphasis d-flex align-items-center gap-2">
                        <i class="bi bi-cpu-fill fs-5 text-warning"></i> Rekomendasi Optimasi Otomatis (Semi-Auto)
                    </span>
                    <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill small fw-semibold">{{ count($recommendations) }} Rekomendasi</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        @foreach($recommendations as $rec)
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 border border-light-subtle bg-light bg-opacity-50 d-flex align-items-center justify-content-between">
                                    <div class="pe-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            @php
                                                $pf = $rec['campaign']->adsAccount->platform;
                                                $pfBadge = 'bg-secondary';
                                                if ($pf === 'meta') $pfBadge = 'bg-primary';
                                                elseif ($pf === 'google') $pfBadge = 'bg-danger';
                                                elseif ($pf === 'tiktok') $pfBadge = 'bg-dark';
                                            @endphp
                                            <span class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded" style="font-size:0.6rem; letter-spacing:0.5px;">
                                                {{ $pf }}
                                            </span>
                                            <strong class="text-dark small">{{ $rec['campaign']->name }}</strong>
                                        </div>
                                        <div class="text-muted small mt-1.5" style="font-size: 0.8rem;">
                                            <i class="bi bi-exclamation-circle me-1 text-{{ $rec['severity'] }}"></i>{{ $rec['issue'] }}
                                        </div>
                                    </div>
                                    <div>
                                        @if($rec['action_code'] === 'pause')
                                            <form action="{{ route('marketing.ads.toggle', $rec['campaign']->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-3 py-1.5 rounded-3 fw-semibold text-nowrap">
                                                    <i class="bi bi-pause-circle me-1"></i> Jeda Iklan
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-success px-3 py-1.5 rounded-3 fw-semibold text-nowrap" onclick="alert('Skala budget di Meta/Google disimulasikan: Budget dinaikkan sebesar +20% di platform pengiklan.')">
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
                                    <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($totalSpend, 0, ',', '.') }}</h5>
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
                                    <h5 class="fw-bold mb-0 text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h5>
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
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bezier2 me-2 text-primary"></i> Tren Investasi Iklan vs Omset Penjualan (30 Hari Terakhir)</h6>
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
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-star-fill me-2 text-warning"></i> Produk Paling "Laku Iklan"</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-column gap-3">
                            @forelse($topProducts as $idx => $prod)
                                <div class="d-flex align-items-center justify-content-between pb-2.5 {{ !$loop->last ? 'border-bottom border-light' : '' }}">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                            {{ $idx + 1 }}
                                        </div>
                                        <div>
                                            <strong class="text-dark small d-block">{{ $prod->product_name }}</strong>
                                            <small class="text-muted font-monospace" style="font-size:0.75rem;">SKU: {{ $prod->sku }}</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold small px-2.5 py-1">{{ $prod->total_qty }} pcs</span>
                                        <div class="text-muted mt-1" style="font-size:0.75rem;">Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}</div>
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
                    <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-table me-2 text-info"></i> Laporan ROAS & Progress Target per Campaign</h6>
                        <a href="{{ route('marketing.ads.campaigns') }}" class="btn btn-sm btn-outline-primary rounded-3 fw-semibold">Kelola Campaign</a>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase fs-7 text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem;">
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
                                            $targetRoas = (float)$camp->target_roas;
                                        @endphp
                                        <tr class="border-bottom border-light">
                                            <td class="px-3 py-3">
                                                <strong class="text-dark">{{ $camp->name }}</strong>
                                            </td>
                                            <td class="px-3 py-3">
                                                @php
                                                    $pf = $camp->adsAccount->platform;
                                                    $pfBadge = 'bg-secondary';
                                                    if ($pf === 'meta') $pfBadge = 'bg-primary';
                                                    elseif ($pf === 'google') $pfBadge = 'bg-danger';
                                                    elseif ($pf === 'tiktok') $pfBadge = 'bg-dark';
                                                @endphp
                                                <span class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded" style="font-size:0.65rem;">
                                                    {{ $pf }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">Rp {{ number_format($spend, 0, ',', '.') }}</td>
                                            <td class="px-3 py-3">Rp {{ number_format($rev, 0, ',', '.') }}</td>
                                            <td class="px-3 py-3">
                                                <strong class="text-{{ $spend > 0 ? ($roas >= $targetRoas ? 'success' : 'danger') : 'muted' }}">
                                                    {{ number_format($roas, 2) }}x
                                                </strong>
                                            </td>
                                            <td class="px-3 py-3">{{ number_format($targetRoas, 2) }}x</td>
                                            <td class="px-3 py-3">
                                                @if($spend <= 0)
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1 rounded">No Data</span>
                                                @elseif($roas >= $targetRoas)
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded">
                                                        <i class="bi bi-check-circle-fill me-1"></i> AMAN / UNTUNG
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 rounded">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> BOROS
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-end">
                                                <form action="{{ route('marketing.ads.toggle', $camp->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @if($camp->status === 'ACTIVE')
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-3 fw-semibold px-2.5 py-1">
                                                            <i class="bi bi-play-fill"></i> Aktif
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-3 fw-semibold px-2.5 py-1">
                                                            <i class="bi bi-pause-fill"></i> Jeda
                                                        </button>
                                                    @endif
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                Belum ada Campaign iklan terdaftar. Klik "Atur Target Campaign" untuk menambahkan.
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

        <!-- Manual Order Attribution Panel -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-link-45deg me-2 text-primary"></i> Atribusi Pesanan Manual (Tautkan Order ke Iklan)</h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small">Hubungkan penjualan baru dari marketplace ke campaign ads secara manual jika tidak teratribusi otomatis oleh UTM pixel.</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                                <thead class="table-light text-uppercase fs-7 text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem;">
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
                                    @forelse($unattributedOrders as $ord)
                                        <tr class="border-bottom border-light">
                                            <td class="px-3 py-3">{{ $ord->order_date->format('d/m/Y H:i') }}</td>
                                            <td class="px-3 py-3"><strong class="text-dark">{{ $ord->invoice_number }}</strong></td>
                                            <td class="px-3 py-3">
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded">{{ $ord->store->store_name }}</span>
                                            </td>
                                            <td class="px-3 py-3">{{ $ord->buyer_name }}</td>
                                            <td class="px-3 py-3"><strong class="text-success">Rp {{ number_format($ord->net_amount, 0, ',', '.') }}</strong></td>
                                            <td class="px-3 py-3 text-end">
                                                <form action="{{ route('marketing.ads.attribute') }}" method="POST" class="d-flex gap-2 align-items-center justify-content-end">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $ord->id }}">
                                                    <select name="ads_campaign_id" class="form-select form-select-sm rounded-3 w-auto border-secondary border-opacity-25" required style="font-size: 0.8rem; padding: 0.35rem 2rem 0.35rem 0.75rem;">
                                                        <option value="">-- Pilih Campaign --</option>
                                                        @foreach($campaigns as $cp)
                                                            <option value="{{ $cp->id }}">{{ $cp->name }} ({{ strtoupper($cp->adsAccount->platform) }})</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary rounded-3 px-3 py-1.5 fw-semibold" style="font-size: 0.8rem;">Tautkan</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                Tidak ada pesanan tak teratribusi baru. Semua pesanan sudah ditautkan ke campaign.
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
                datasets: [
                    {
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
