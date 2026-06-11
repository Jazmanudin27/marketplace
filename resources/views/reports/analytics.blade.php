@extends('layouts.app')
@section('title', 'Analitik Inventori & Deadstock')
@section('page-title', 'Analitik Inventori & Peramalan')

@section('content')

{{-- KPI Summary Cards --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem;">

    {{-- KPI 1: Deadstock Qty --}}
    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(239,68,68,.15),rgba(239,68,68,.05)); border-color:rgba(239,68,68,.3);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-boxes"></i> Produk Deadstock
        </div>
        <div style="font-size:1.6rem; font-weight:800; color:#f87171;">
            {{ number_format($totalDeadstockItems) }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-secondary);">item</span>
        </div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">
            Tanpa penjualan &ge; {{ $deadstockDays }} hari
        </div>
    </div>

    {{-- KPI 2: Deadstock Value --}}
    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05)); border-color:rgba(245,158,11,.3);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-money-bill-wave"></i> Estimasi Nilai Mengendap
        </div>
        <div style="font-size:1.4rem; font-weight:800; color:#fbbf24; white-space:nowrap;">
            Rp {{ number_format($totalDeadstockValue, 0, ',', '.') }}
        </div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">
            Berdasarkan nilai HPP produk
        </div>
    </div>

    {{-- KPI 3: Reorder Alerts --}}
    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(99,102,241,.05)); border-color:rgba(99,102,241,.3);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-exclamation-triangle"></i> Peringatan Reorder
        </div>
        <div style="font-size:1.6rem; font-weight:800; color:#818cf8;">
            {{ number_format($totalReorderAlerts) }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-secondary);">produk</span>
        </div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">
            Stok habis atau kritis &le; 7 hari
        </div>
    </div>

    {{-- KPI 4: Target Coverage --}}
    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(16,185,129,.05)); border-color:rgba(16,185,129,.3);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-calendar-alt"></i> Target Coverage
        </div>
        <div style="font-size:1.6rem; font-weight:800; color:#34d399;">
            {{ $targetCoverage }} <span style="font-size:0.9rem; font-weight:400; color:var(--text-secondary);">Hari</span>
        </div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">
            Periode restock terencana
        </div>
    </div>

</div>

{{-- Filter Form --}}
<div class="dashboard-card" style="margin-bottom:1.25rem;">
    <form method="GET" action="{{ route('reports.analytics') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;">
        <div style="flex:1; min-width:180px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Kriteria Deadstock (Hari Tanpa Penjualan)</label>
            <select name="deadstock_days" class="form-select form-select-sm" style="background:var(--bg-card); color:var(--text-primary); border-color:var(--border);">
                <option value="30" {{ $deadstockDays == 30 ? 'selected' : '' }}>&ge; 30 Hari (Lambat Terjual)</option>
                <option value="60" {{ $deadstockDays == 60 ? 'selected' : '' }}>&ge; 60 Hari (Sangat Lambat)</option>
                <option value="90" {{ $deadstockDays == 90 ? 'selected' : '' }}>&ge; 90 Hari (Deadstock Standard)</option>
                <option value="120" {{ $deadstockDays == 120 ? 'selected' : '' }}>&ge; 120 Hari (Kritis/Mati)</option>
            </select>
        </div>
        <div style="flex:1; min-width:180px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Target Ketersediaan Restock (Coverage)</label>
            <select name="target_coverage" class="form-select form-select-sm" style="background:var(--bg-card); color:var(--text-primary); border-color:var(--border);">
                <option value="15" {{ $targetCoverage == 15 ? 'selected' : '' }}>15 Hari Ketersediaan</option>
                <option value="30" {{ $targetCoverage == 30 ? 'selected' : '' }}>30 Hari Ketersediaan (Standard)</option>
                <option value="45" {{ $targetCoverage == 45 ? 'selected' : '' }}>45 Hari Ketersediaan</option>
                <option value="60" {{ $targetCoverage == 60 ? 'selected' : '' }}>60 Hari Ketersediaan (Musiman)</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary" style="padding:.45rem 1rem;">
                <i class="fas fa-filter"></i> Terapkan Analitik
            </button>
        </div>
        <div>
            <a href="{{ route('reports.analytics') }}" class="btn btn-sm btn-outline-secondary" style="padding:.45rem 1rem;">
                <i class="fas fa-undo"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- Tab Switcher --}}
<ul class="nav nav-tabs mb-3" id="analyticsTab" role="tablist" style="border-bottom:1px solid var(--border);">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="deadstock-tab" data-bs-toggle="tab" data-bs-target="#deadstock-pane" type="button" role="tab" aria-controls="deadstock-pane" aria-selected="true" style="font-weight:600;">
            <i class="fas fa-boxes me-2 text-danger"></i> Detektor Deadstock
            <span class="badge bg-danger ms-1" style="font-size:0.7rem;">{{ $deadstockProducts->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="forecast-tab" data-bs-toggle="tab" data-bs-target="#forecast-pane" type="button" role="tab" aria-controls="forecast-pane" aria-selected="false" style="font-weight:600;">
            <i class="fas fa-chart-line me-2 text-success"></i> Peramalan & Restock Planner
        </button>
    </li>
</ul>

<div class="tab-content" id="analyticsTabContent">

    {{-- TAB 1: DETEKTOR DEADSTOCK --}}
    <div class="tab-pane fade show active" id="deadstock-pane" role="tabpanel" aria-labelledby="deadstock-tab">
        <div class="dashboard-card">
            <div class="card-header-line" style="margin-bottom:1rem;">
                <h3><i class="fas fa-skull-crossbones text-danger"></i> Daftar Stok Mati / Mengendap</h3>
                <span style="font-size:0.8rem; color:var(--text-secondary);">
                    Menampilkan produk dengan stok tersedia yang tidak terjual selama minimal {{ $deadstockDays }} hari
                </span>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th style="text-align:right;">Stok Saat Ini</th>
                            <th style="text-align:right;">Harga Beli (HPP)</th>
                            <th style="text-align:right;">Total Nilai HPP</th>
                            <th style="text-align:right;">Penjualan Terakhir</th>
                            <th style="text-align:center;">Hari Tanpa Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deadstockProducts as $p)
                        @php
                            $totalHpp = $p['stock'] * $p['cost_price'];
                            $daysVal = $p['days_since_last_sale'];
                            $severityBg = $daysVal >= 120 ? 'rgba(239, 68, 68, 0.08)' : ($daysVal >= 90 ? 'rgba(245, 158, 11, 0.06)' : 'transparent');
                        @endphp
                        <tr style="background:{{ $severityBg }}">
                            <td class="mono fw-semibold text-primary">{{ $p['sku'] }}</td>
                            <td>{{ $p['name'] }}</td>
                            <td class="mono" style="text-align:right; font-weight:600;">{{ number_format($p['stock']) }}</td>
                            <td class="mono" style="text-align:right;">Rp {{ number_format($p['cost_price'], 0, ',', '.') }}</td>
                            <td class="mono" style="text-align:right; font-weight:700; color:#fbbf24;">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                            <td style="text-align:right; font-size:0.8rem; color:var(--text-secondary);">
                                {{ $p['last_sale_date'] ? $p['last_sale_date']->format('d M Y') : 'Belum Pernah' }}
                            </td>
                            <td style="text-align:center;">
                                <span class="badge bg-{{ $daysVal >= 90 ? 'danger' : 'warning' }}" style="font-size:0.78rem; padding:.3rem .6rem;">
                                    {{ number_format($daysVal) }} hari
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:4rem; color:var(--text-secondary);">
                                <i class="fas fa-smile" style="font-size:2.5rem; color:#34d399; opacity:.5; display:block; margin-bottom:1rem;"></i>
                                <strong style="color:var(--text-primary);">Luar Biasa! Tidak ada deadstock terdeteksi.</strong><br>
                                <small>Semua produk Anda mengalami perputaran dalam {{ $deadstockDays }} hari terakhir.</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: PERAMALAN & RESTOCK PLANNER --}}
    <div class="tab-pane fade" id="forecast-pane" role="tabpanel" aria-labelledby="forecast-tab">
        <div class="dashboard-card">
            <div class="card-header-line" style="margin-bottom:1rem;">
                <h3><i class="fas fa-magic text-success"></i> Rencana Pembelian & Peramalan Restock</h3>
                <span style="font-size:0.8rem; color:var(--text-secondary);">
                    Menentukan sisa hari ketersediaan stok produk dan kuantitas restock optimal untuk target {{ $targetCoverage }} hari mendatang
                </span>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th style="text-align:right;">Stok Saat Ini</th>
                            <th style="text-align:right;">Terjual (30 Hari)</th>
                            <th style="text-align:right;">Laju Harian (Run Rate)</th>
                            <th style="text-align:center;">Sisa Ketersediaan</th>
                            <th style="text-align:right; color:#34d399;">Rekomendasi Restock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forecastProducts as $p)
                        @php
                            $cover = $p['days_of_cover'];
                            $rec = $p['recommended_qty'];
                            
                            // Determine severity style based on cover days
                            if ($p['stock'] == 0 && $p['sold_30'] > 0) {
                                $coverText = 'Habis (Ada Permintaan)';
                                $badgeClass = 'danger';
                                $rowBg = 'rgba(239, 68, 68, 0.08)';
                            } elseif ($p['stock'] == 0) {
                                $coverText = 'Habis';
                                $badgeClass = 'secondary';
                                $rowBg = 'transparent';
                            } elseif ($cover <= 7) {
                                $coverText = number_format($cover, 1) . ' Hari';
                                $badgeClass = 'danger';
                                $rowBg = 'rgba(239, 68, 68, 0.06)';
                            } elseif ($cover <= 15) {
                                $coverText = number_format($cover, 1) . ' Hari';
                                $badgeClass = 'warning';
                                $rowBg = 'rgba(245, 158, 11, 0.04)';
                            } elseif ($cover == PHP_INT_MAX) {
                                $coverText = 'Tidak Bergerak';
                                $badgeClass = 'secondary';
                                $rowBg = 'transparent';
                            } else {
                                $coverText = number_format($cover) . ' Hari';
                                $badgeClass = 'success';
                                $rowBg = 'transparent';
                            }
                        @endphp
                        <tr style="background:{{ $rowBg }}">
                            <td class="mono fw-semibold text-primary">{{ $p['sku'] }}</td>
                            <td>{{ $p['name'] }}</td>
                            <td class="mono" style="text-align:right; font-weight:600;">{{ number_format($p['stock']) }}</td>
                            <td class="mono" style="text-align:right;">{{ number_format($p['sold_30']) }}</td>
                            <td class="mono text-muted" style="text-align:right;">{{ number_format($p['run_rate'], 2) }} / hari</td>
                            <td style="text-align:center;">
                                <span class="badge bg-{{ $badgeClass }}" style="font-size:0.75rem;">
                                    {{ $coverText }}
                                </span>
                            </td>
                            <td class="mono fw-bold" style="text-align:right; font-size:0.92rem; color:{{ $rec > 0 ? '#34d399' : 'var(--text-secondary)' }};">
                                @if($rec > 0)
                                    <i class="fas fa-plus-circle me-1"></i> {{ number_format($rec) }} pcs
                                @else
                                    <span style="font-weight:400; opacity:.5;">Stok Cukup</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                                Belum ada produk terdaftar untuk dianalisis.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
