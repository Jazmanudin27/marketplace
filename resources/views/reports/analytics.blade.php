@extends('layouts.app')
@section('title', 'Analitik Inventori & Deadstock')
@section('page-title', 'Analitik Inventori & Peramalan')

@section('content')

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-3">

        {{-- KPI 1: Deadstock Qty --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(239,68,68,.15),rgba(239,68,68,.05)); border-color:rgba(239,68,68,.3);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-boxes me-1"></i> Produk Deadstock
                </div>
                <div class="fw-bold fs-4 text-danger">
                    {{ number_format($totalDeadstockItems) }} <span class="fs-6 fw-normal text-muted">item</span>
                </div>
                <div class="small text-muted mt-1">
                    Tanpa penjualan &ge; {{ $deadstockDays }} hari
                </div>
            </div>
        </div>

        {{-- KPI 2: Deadstock Value --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05)); border-color:rgba(245,158,11,.3);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-money-bill-wave me-1"></i> Estimasi Nilai Mengendap
                </div>
                <div class="fw-bold fs-4 text-warning text-nowrap">
                    Rp {{ number_format($totalDeadstockValue, 0, ',', '.') }}
                </div>
                <div class="small text-muted mt-1">
                    Berdasarkan nilai HPP produk
                </div>
            </div>
        </div>

        {{-- KPI 3: Reorder Alerts --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(99,102,241,.05)); border-color:rgba(99,102,241,.3);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-exclamation-triangle me-1"></i> Peringatan Reorder
                </div>
                <div class="fw-bold fs-4" style="color:#818cf8;">
                    {{ number_format($totalReorderAlerts) }} <span class="fs-6 fw-normal text-muted">produk</span>
                </div>
                <div class="small text-muted mt-1">
                    Stok habis atau kritis &le; 7 hari
                </div>
            </div>
        </div>

        {{-- KPI 4: Target Coverage --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(16,185,129,.05)); border-color:rgba(16,185,129,.3);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-calendar-alt me-1"></i> Target Coverage
                </div>
                <div class="fw-bold fs-4 text-success">
                    {{ $targetCoverage }} <span class="fs-6 fw-normal text-muted">Hari</span>
                </div>
                <div class="small text-muted mt-1">
                    Periode restock terencana
                </div>
            </div>
        </div>

    </div>

    {{-- Filter Form --}}
    <div class="dashboard-card mb-3 py-3">
        <form method="GET" action="{{ route('reports.analytics') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Kriteria Deadstock (Hari Tanpa
                        Penjualan)</label>
                    <select name="deadstock_days" class="form-select form-select-sm">
                        <option value="30" {{ $deadstockDays == 30 ? 'selected' : '' }}>&ge; 30 Hari (Lambat Terjual)
                        </option>
                        <option value="60" {{ $deadstockDays == 60 ? 'selected' : '' }}>&ge; 60 Hari (Sangat Lambat)
                        </option>
                        <option value="90" {{ $deadstockDays == 90 ? 'selected' : '' }}>&ge; 90 Hari (Deadstock
                            Standard)</option>
                        <option value="120" {{ $deadstockDays == 120 ? 'selected' : '' }}>&ge; 120 Hari (Kritis/Mati)
                        </option>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Target Ketersediaan Restock
                        (Coverage)</label>
                    <select name="target_coverage" class="form-select form-select-sm">
                        <option value="15" {{ $targetCoverage == 15 ? 'selected' : '' }}>15 Hari Ketersediaan</option>
                        <option value="30" {{ $targetCoverage == 30 ? 'selected' : '' }}>30 Hari Ketersediaan
                            (Standard)</option>
                        <option value="45" {{ $targetCoverage == 45 ? 'selected' : '' }}>45 Hari Ketersediaan</option>
                        <option value="60" {{ $targetCoverage == 60 ? 'selected' : '' }}>60 Hari Ketersediaan (Musiman)
                        </option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    @if ($deadstockDays != 90 || $targetCoverage != 30)
                        <a href="{{ route('reports.analytics') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Tab Switcher --}}
    <ul class="nav nav-tabs mb-3" id="analyticsTab" role="tablist"
        style="border-bottom:1px solid rgba(255,255,255,0.08) !important;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="deadstock-tab" data-bs-toggle="tab" data-bs-target="#deadstock-pane"
                type="button" role="tab" aria-controls="deadstock-pane" aria-selected="true" style="font-weight:600;">
                <i class="fas fa-boxes me-2 text-danger"></i> Detektor Deadstock
                <span class="badge bg-danger ms-1" style="font-size:0.7rem;">{{ $deadstockProducts->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="forecast-tab" data-bs-toggle="tab" data-bs-target="#forecast-pane" type="button"
                role="tab" aria-controls="forecast-pane" aria-selected="false" style="font-weight:600;">
                <i class="fas fa-chart-line me-2 text-success"></i> Peramalan & Restock Planner
            </button>
        </li>
    </ul>

    <div class="tab-content" id="analyticsTabContent">

        {{-- TAB 1: DETEKTOR DEADSTOCK --}}
        <div class="tab-pane fade show active" id="deadstock-pane" role="tabpanel" aria-labelledby="deadstock-tab">
            <div class="dashboard-card">
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <i class="fas fa-skull-crossbones text-danger"></i> Daftar Stok Mati / Mengendap
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Menampilkan produk dengan stok tersedia yang tidak terjual selama minimal {{ $deadstockDays }}
                            hari
                        </p>
                    </div>
                </div>
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">SKU</th>
                                <th>Nama Produk</th>
                                <th class="text-end" style="width:13%">Stok Saat Ini</th>
                                <th class="text-end" style="width:15%">Harga Beli (HPP)</th>
                                <th class="text-end" style="width:15%">Total Nilai HPP</th>
                                <th class="text-end" style="width:15%">Penjualan Terakhir</th>
                                <th class="text-center pe-3" style="width:15%">Hari Tanpa Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deadstockProducts as $p)
                                @php
                                    $totalHpp = $p['stock'] * $p['cost_price'];
                                    $daysVal = $p['days_since_last_sale'];
                                    $rowStyle = '';
                                    if ($daysVal >= 120) {
                                        $rowStyle = 'background: rgba(239, 68, 68, 0.08) !important;';
                                    } elseif ($daysVal >= 90) {
                                        $rowStyle = 'background: rgba(245, 158, 11, 0.06) !important;';
                                    }
                                @endphp
                                <tr style="{{ $rowStyle }}">
                                    <td class="ps-3 font-monospace fw-semibold text-primary">{{ $p['sku'] }}</td>
                                    <td class="text-white fw-semibold">{{ $p['name'] }}</td>
                                    <td class="text-end font-monospace fw-bold text-white">
                                        {{ number_format($p['stock']) }}</td>
                                    <td class="text-end font-monospace text-light">Rp
                                        {{ number_format($p['cost_price'], 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace fw-bold text-warning">Rp
                                        {{ number_format($totalHpp, 0, ',', '.') }}</td>
                                    <td class="text-end small text-muted">
                                        {{ $p['last_sale_date'] ? $p['last_sale_date']->format('d M Y') : 'Belum Pernah' }}
                                    </td>
                                    <td class="text-center pe-3">
                                        <span
                                            class="badge {{ $daysVal >= 90 ? 'badge-danger' : 'badge-warning' }} px-2 py-1">
                                            {{ number_format($daysVal) }} hari
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-smile fa-3x text-success mb-3 d-block opacity-50"></i>
                                        <div class="fw-bold text-white mb-1">Luar Biasa! Tidak ada deadstock terdeteksi.
                                        </div>
                                        <div class="small">Semua produk Anda mengalami perputaran dalam
                                            {{ $deadstockDays }} hari terakhir.</div>
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
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <i class="fas fa-magic text-success"></i> Rencana Pembelian & Peramalan Restock
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Menentukan sisa hari ketersediaan stok produk dan kuantitas restock optimal untuk target
                            {{ $targetCoverage }} hari mendatang
                        </p>
                    </div>
                </div>
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">SKU</th>
                                <th>Nama Produk</th>
                                <th class="text-end" style="width:12%">Stok Saat Ini</th>
                                <th class="text-end" style="width:12%">Terjual (30 Hari)</th>
                                <th class="text-end" style="width:16%">Laju Harian (Run Rate)</th>
                                <th class="text-center" style="width:16%">Sisa Ketersediaan</th>
                                <th class="text-end pe-3" style="width:18%">Rekomendasi Restock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($forecastProducts as $p)
                                @php
                                    $cover = $p['days_of_cover'];
                                    $rec = $p['recommended_qty'];
                                    $rowStyle = '';

                                    // Determine severity style based on cover days
                                    if ($p['stock'] == 0 && $p['sold_30'] > 0) {
                                        $coverText = 'Habis (Ada Permintaan)';
                                        $badgeClass = 'badge-danger';
                                        $rowStyle = 'background: rgba(239, 68, 68, 0.08) !important;';
                                    } elseif ($p['stock'] == 0) {
                                        $coverText = 'Habis';
                                        $badgeClass = 'badge-secondary';
                                    } elseif ($cover <= 7) {
                                        $coverText = number_format($cover, 1) . ' Hari';
                                        $badgeClass = 'badge-danger';
                                        $rowStyle = 'background: rgba(239, 68, 68, 0.06) !important;';
                                    } elseif ($cover <= 15) {
                                        $coverText = number_format($cover, 1) . ' Hari';
                                        $badgeClass = 'badge-warning';
                                        $rowStyle = 'background: rgba(245, 158, 11, 0.04) !important;';
                                    } elseif ($cover == PHP_INT_MAX) {
                                        $coverText = 'Tidak Bergerak';
                                        $badgeClass = 'badge-secondary';
                                    } else {
                                        $coverText = number_format($cover) . ' Hari';
                                        $badgeClass = 'badge-success';
                                    }
                                @endphp
                                <tr style="{{ $rowStyle }}">
                                    <td class="ps-3 font-monospace fw-semibold text-primary">{{ $p['sku'] }}</td>
                                    <td class="text-white fw-semibold">{{ $p['name'] }}</td>
                                    <td class="text-end font-monospace fw-bold text-white">
                                        {{ number_format($p['stock']) }}</td>
                                    <td class="text-end font-monospace text-light">{{ number_format($p['sold_30']) }}</td>
                                    <td class="text-end font-monospace text-muted">
                                        {{ number_format($p['run_rate'], 2) }} / hari</td>
                                    <td class="text-center">
                                        <span class="badge {{ $badgeClass }} px-2 py-1">
                                            {{ $coverText }}
                                        </span>
                                    </td>
                                    <td
                                        class="text-end font-monospace fw-bold pe-3 {{ $rec > 0 ? 'text-success' : 'text-muted' }}">
                                        @if ($rec > 0)
                                            <i class="fas fa-plus-circle me-1 text-success"></i>
                                            +{{ number_format($rec) }} pcs
                                        @else
                                            <span class="fw-normal opacity-50">Stok Cukup</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-box-open fa-3x mb-3 d-block opacity-25"></i>
                                        <div class="fw-semibold text-light mb-1">Belum ada produk terdaftar untuk
                                            dianalisis.</div>
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
