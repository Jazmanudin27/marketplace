@extends('layouts.app')
@section('title', 'Heatmap Waktu Order dari Iklan')
@section('page-title', 'Heatmap Waktu Order Iklan')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Dashboard Iklan
    </a>
@endsection

@section('content')
    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('marketing.ads.heatmap') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-secondary text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.5px;">Platform</label>
                        <select name="platform" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">🌐 Semua Platform</option>
                            <option value="shopee" {{ ($platform ?? '') === 'shopee' ? 'selected' : '' }}>🟠 Shopee Ads</option>
                            <option value="tiktok" {{ ($platform ?? '') === 'tiktok' ? 'selected' : '' }}>⚫ TikTok Ads</option>
                            <option value="meta"   {{ ($platform ?? '') === 'meta'   ? 'selected' : '' }}>📘 Meta Ads</option>
                            <option value="google" {{ ($platform ?? '') === 'google' ? 'selected' : '' }}>🔴 Google Ads</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm fw-semibold text-secondary text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.5px;">Campaign</label>
                        <select name="campaign_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Campaign</option>
                            @foreach($campaigns as $c)
                                <option value="{{ $c->id }}" {{ ($campaignId ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ strtoupper($c->adsAccount->platform) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Legend --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2 px-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Heatmap Order per Jam & Hari (berdasarkan data historis iklan)</h6>
        </div>
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <small class="text-muted fw-semibold">Intensitas:</small>
                <div class="d-flex align-items-center gap-1">
                    <div class="rounded" style="width:16px;height:16px;background:rgba(13,110,253,.05);border:1px solid #dee2e6;"></div>
                    <small class="text-muted">0</small>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <div class="rounded" style="width:16px;height:16px;background:rgba(13,110,253,.3);"></div>
                    <small class="text-muted">Rendah</small>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <div class="rounded" style="width:16px;height:16px;background:rgba(13,110,253,.6);"></div>
                    <small class="text-muted">Sedang</small>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <div class="rounded" style="width:16px;height:16px;background:rgba(13,110,253,1);"></div>
                    <small class="text-muted">Tinggi</small>
                </div>
                @if($maxOrders === 0)
                    <span class="badge bg-warning text-dark ms-2">Belum ada data order yang teratribusi ke iklan</span>
                @endif
            </div>

            {{-- Heatmap Grid --}}
            <div class="overflow-auto">
                <table class="table table-sm table-bordered mb-0" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th class="text-center bg-light" style="width:55px;font-size:.7rem;">Hari \ Jam</th>
                            @for($h = 0; $h <= 23; $h++)
                                <th class="text-center bg-light p-1" style="min-width:36px;font-size:.68rem;font-weight:600;">
                                    {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $idx => $dayName)
                            @php $dow = $idx + 1; @endphp
                            <tr>
                                <td class="text-center fw-bold bg-light" style="font-size:.75rem;">{{ $dayName }}</td>
                                @for($h = 0; $h <= 23; $h++)
                                    @php
                                        $cell     = $matrix[$dow][$h];
                                        $orders   = $cell['orders'];
                                        $revenue  = $cell['revenue'];
                                        $intensity = $maxOrders > 0 ? $orders / $maxOrders : 0;
                                        $alpha    = max(0.04, $intensity);
                                        $textColor = $intensity > 0.6 ? '#fff' : '#212529';
                                        $bgColor  = "rgba(13,110,253,{$alpha})";
                                    @endphp
                                    <td class="text-center p-0"
                                        style="background:{{ $bgColor }};cursor:{{ $orders > 0 ? 'pointer' : 'default' }};height:32px;font-size:.65rem;"
                                        title="{{ $dayName }}, {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00 — {{ $orders }} order | Rp {{ number_format($revenue, 0, ',', '.') }}">
                                        @if($orders > 0)
                                            <span style="color:{{ $textColor }};font-weight:600;">{{ $orders }}</span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Insights --}}
    @if($maxOrders > 0)
    <div class="row g-3">
        {{-- Best Hours Chart --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2 text-success"></i>Total Order per Jam (Semua Hari)</h6>
                </div>
                <div class="card-body p-3">
                    <div style="height:220px;"><canvas id="hourlyChart"></canvas></div>
                </div>
            </div>
        </div>
        {{-- Best Days Chart --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-warning"></i>Total Order per Hari</h6>
                </div>
                <div class="card-body p-3">
                    <div style="height:220px;"><canvas id="dailyChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const matrix = @json($matrix);
        const days   = @json($days);

        // Hourly totals (sum across all days)
        const hourlyTotals = Array(24).fill(0);
        for (const [dow, hours] of Object.entries(matrix)) {
            for (const [h, cell] of Object.entries(hours)) {
                hourlyTotals[parseInt(h)] += cell.orders;
            }
        }

        // Daily totals
        const dailyTotals = Array(7).fill(0);
        for (const [dow, hours] of Object.entries(matrix)) {
            let sum = 0;
            for (const cell of Object.values(hours)) sum += cell.orders;
            dailyTotals[parseInt(dow) - 1] = sum;
        }

        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: Array.from({length:24}, (_,i) => i.toString().padStart(2,'0')+':00'),
                datasets: [{
                    label: 'Total Order',
                    data: hourlyTotals,
                    backgroundColor: hourlyTotals.map(v => {
                        const max = Math.max(...hourlyTotals);
                        const ratio = max > 0 ? v / max : 0;
                        return `rgba(25,135,84,${Math.max(0.2, ratio)})`;
                    }),
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { ticks: { font: { size: 9 } } } }
            }
        });

        new Chart(document.getElementById('dailyChart'), {
            type: 'bar',
            data: {
                labels: days,
                datasets: [{
                    label: 'Total Order',
                    data: dailyTotals,
                    backgroundColor: dailyTotals.map(v => {
                        const max = Math.max(...dailyTotals);
                        const ratio = max > 0 ? v / max : 0;
                        return `rgba(255,193,7,${Math.max(0.3, ratio)})`;
                    }),
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    });
    </script>
    @endpush
    @endif
@endsection
