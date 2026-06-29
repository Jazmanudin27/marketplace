@extends('layouts.app')

@section('title', 'Dashboard Keputusan Iklan')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold">Dashboard Keputusan Iklan</h1>
            <p class="text-muted mb-0">Hubungkan budget iklan ke penjualan riil, pantau ROAS, dan ambil keputusan optimasi budget.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('marketing.ads.logs') }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Input Biaya Harian
            </a>
            <a href="{{ route('marketing.ads.campaigns') }}" class="btn btn-outline-primary fw-semibold">
                <i class="bi bi-gear me-1"></i> Atur Target Campaign
            </a>
        </div>
    </div>

    <!-- Alert / Recommendations -->
    @if(count($recommendations) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border border-warning shadow-sm">
                    <div class="card-header bg-warning bg-opacity-10 d-flex align-items-center justify-content-between py-2.5 px-3">
                        <span class="fw-bold text-warning-emphasis">
                            <i class="bi bi-cpu-fill me-2"></i> Rekomendasi Optimasi Otomatis (Semi-Auto)
                        </span>
                        <span class="badge bg-warning text-dark">{{ count($recommendations) }} Rekomendasi</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            @foreach($recommendations as $rec)
                                <div class="col-md-6">
                                    <div class="p-3 rounded border bg-light d-flex align-items-start justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-{{ $rec['campaign']->adsAccount->platform === 'meta' ? 'primary' : ($rec['campaign']->adsAccount->platform === 'google' ? 'danger' : 'dark') }} text-uppercase" style="font-size:0.65rem;">
                                                    {{ $rec['campaign']->adsAccount->platform }}
                                                </span>
                                                <strong class="text-dark">{{ $rec['campaign']->name }}</strong>
                                            </div>
                                            <div class="text-muted small mt-1">
                                                {{ $rec['issue'] }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            @if($rec['action_code'] === 'pause')
                                                <form action="{{ route('marketing.ads.toggle', $rec['campaign']->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold">
                                                        <i class="bi bi-pause-circle me-1"></i> Jeda Iklan
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-sm btn-success fw-semibold" onclick="alert('Skala budget di Meta/Google disimulasikan: Budget dinaikkan sebesar +20% di platform pengiklan.')">
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
            </div>
        </div>
    @endif

    <!-- Overall Statistics -->
    <div class="row g-3 mb-4">
        <!-- Ad Spend -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded bg-danger bg-opacity-10 text-danger p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Biaya Iklan (Spend)</span>
                        <h4 class="fw-bold mt-1 mb-0 text-dark">Rp {{ number_format($totalSpend, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <!-- Attributed Revenue -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Omset dari Iklan (Attributed)</span>
                        <h4 class="fw-bold mt-1 mb-0 text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <!-- Actual ROAS -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Rata-rata ROAS</span>
                        <h4 class="fw-bold mt-1 mb-0 text-primary">{{ number_format($overallRoas, 2) }}x</h4>
                    </div>
                </div>
            </div>
        </div>
        <!-- Conversions -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center gap-3">
                    <div class="rounded bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-cart-check fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Jumlah Closing (Orders)</span>
                        <h4 class="fw-bold mt-1 mb-0 text-dark">{{ $totalConversions }} Order</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Spend vs Revenue -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-bezier2 me-2 text-primary"></i> Tren Investasi Iklan vs Omset Penjualan (30 Hari Terakhir)</h6>
                </div>
                <div class="card-body p-4">
                    <div style="height: 300px;">
                        <canvas id="adsPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products Sold via Ads -->
        <div class="col-lg-4">
            <div class="card border shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-star-fill me-2 text-warning"></i> Produk Paling "Laku Iklan"</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-3">
                        @forelse($topProducts as $idx => $prod)
                            <div class="d-flex align-items-center justify-content-between pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded bg-light text-muted d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                                        #{{ $idx + 1 }}
                                    </div>
                                    <div>
                                        <strong class="text-dark small d-block">{{ $prod->product_name }}</strong>
                                        <small class="text-muted font-monospace">SKU: {{ $prod->sku }}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold">{{ $prod->total_qty }} pcs</span>
                                    <div class="text-muted small mt-1">Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted small">
                                Belum ada penjualan teratribusi iklan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaigns Performance Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-table me-2 text-primary"></i> Laporan ROAS & Progress Target per Campaign</h6>
                    <a href="{{ route('marketing.ads.campaigns') }}" class="btn btn-sm btn-outline-primary fw-semibold">Kelola Campaign</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Campaign</th>
                                    <th>Platform</th>
                                    <th>Total Spend</th>
                                    <th>Total Omset</th>
                                    <th>ROAS Riil</th>
                                    <th>Target ROAS</th>
                                    <th>Status Target</th>
                                    <th>Iklan Platform</th>
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
                                    <tr>
                                        <td>
                                            <strong class="text-dark">{{ $camp->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $camp->adsAccount->platform === 'meta' ? 'primary' : ($camp->adsAccount->platform === 'google' ? 'danger' : 'dark') }} text-uppercase" style="font-size:0.7rem;">
                                                {{ $camp->adsAccount->platform }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($spend, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($rev, 0, ',', '.') }}</td>
                                        <td>
                                            <strong class="text-{{ $spend > 0 ? ($roas >= $targetRoas ? 'success' : 'danger') : 'muted' }}">
                                                {{ number_format($roas, 2) }}x
                                            </strong>
                                        </td>
                                        <td>{{ number_format($targetRoas, 2) }}x</td>
                                        <td>
                                            @if($spend <= 0)
                                                <span class="badge bg-secondary">No Data</span>
                                            @elseif($roas >= $targetRoas)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    <i class="bi bi-check-circle-fill me-1"></i> AMAN / UNTUNG
                                                </span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> BOROS
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('marketing.ads.toggle', $camp->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @if($camp->status === 'ACTIVE')
                                                    <button type="submit" class="btn btn-sm btn-outline-success fw-semibold" title="Jeda Iklan">
                                                        <i class="bi bi-play-fill me-1"></i> Aktif
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary fw-semibold" title="Aktifkan Iklan">
                                                        <i class="bi bi-pause-fill me-1"></i> Jeda
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
    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-link-45deg me-2 text-primary"></i> Atribusi Pesanan Manual (Tautkan Order ke Iklan)</h6>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small">Hubungkan penjualan baru dari marketplace ke campaign ads secara manual jika tidak teratribusi otomatis oleh UTM pixel.</p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal Order</th>
                                    <th>No Invoice</th>
                                    <th>Toko</th>
                                    <th>Pembeli</th>
                                    <th>Nominal Bersih (Net)</th>
                                    <th>Pilih Campaign Iklan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unattributedOrders as $ord)
                                    <tr>
                                        <td>{{ $ord->order_date->format('d/m/Y H:i') }}</td>
                                        <td><strong class="text-dark">{{ $ord->invoice_number }}</strong></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $ord->store->store_name }}</span>
                                        </td>
                                        <td>{{ $ord->buyer_name }}</td>
                                        <td><strong class="text-success">Rp {{ number_format($ord->net_amount, 0, ',', '.') }}</strong></td>
                                        <td>
                                            <form action="{{ route('marketing.ads.attribute') }}" method="POST" class="d-flex gap-2 align-items-center">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $ord->id }}">
                                                <select name="ads_campaign_id" class="form-select form-select-sm w-auto" required>
                                                    <option value="">-- Pilih Campaign --</option>
                                                    @foreach($campaigns as $cp)
                                                        <option value="{{ $cp->id }}">{{ $cp->name }} ({{ strtoupper($cp->adsAccount->platform) }})</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">Tautkan</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('adsPerformanceChart').getContext('2d');
        
        let gradientSpend = ctx.createLinearGradient(0, 0, 0, 220);
        gradientSpend.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
        gradientSpend.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

        let gradientRev = ctx.createLinearGradient(0, 0, 0, 220);
        gradientRev.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
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
                        borderWidth: 2
                    },
                    {
                        label: 'Omset Hasil Iklan (Revenue)',
                        data: @json($chartRevenue),
                        borderColor: '#10b981',
                        backgroundColor: gradientRev,
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
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
