@extends('v2.layouts.app')

@section('title', 'Target & Tim Marketing V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-bullseye text-primary fs-5"></i> Target & Komisi Penjualan V2
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola alokasi toko marketplace, target Margin (Rp), dan persentase komisi tim</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/target-komisi') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Page">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <button type="button" class="btn btn-sm btn-outline-danger py-1.5 px-3 shadow-sm d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#excludeProductsModal">
            <i class="bi bi-slash-circle me-1"></i> Pengecualian Komisi
        </button>
        <button type="button" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createTeamModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Tim Baru
        </button>
    </div>
</div>

<!-- Header Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-blue">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($totalTeams) }} <small class="fs-7 fw-normal text-muted">Tim</small></span>
                <span class="v2-stat-lbl">{{ number_format($activeTeams) }} Tim Aktif</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-green">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-shop"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($totalStoresLinked) }} <small class="fs-7 fw-normal text-muted">Toko</small></span>
                <span class="v2-stat-lbl">Dari {{ $stores->count() }} Toko Terdaftar</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-amber">
            <div class="v2-stat-icon-wrapper amber">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num" style="font-size: 0.95rem;">Rp {{ number_format($totalTargetMargin ?? $totalTargetValue, 0, ',', '.') }}</span>
                <span class="v2-stat-lbl text-success fw-semibold">Margin Realisasi: Rp {{ number_format($totalActualMargin ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-purple">
            <div class="v2-stat-icon-wrapper purple">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num text-success" style="font-size: 0.95rem;">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</span>
                <span class="v2-stat-lbl">Komisi Realisasi</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Box Compact -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2.5">
        <form id="filterForm" action="{{ route('marketing.teams.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama tim atau catatan..." value="{{ request('search') }}">
            </div>
            <div class="col-6 col-md-3">
                <select id="filterMonth" name="month" class="form-select form-select-sm">
                    <option value="">-- Semua Bulan --</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select id="filterYear" name="year" class="form-select form-select-sm">
                    <option value="">-- Semua Tahun --</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>
                            {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-center gap-1">
                <button type="submit" class="btn btn-sm btn-v2-primary w-100 justify-content-center py-1" title="Terapkan Filter">
                    <i class="bi bi-search me-1"></i> Terapkan Filter
                </button>
                @if(request()->anyFilled(['month', 'year', 'search']))
                    <a href="{{ route('marketing.teams.index') }}" class="btn btn-sm btn-v2-secondary py-1" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>

        {{-- Status Acuan Realisasi --}}
        <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.75rem;">
            <span class="text-muted fw-medium">Status Acuan Realisasi:</span>
            @if($hasExplicitMonthYear)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5">
                    <i class="bi bi-calendar3 me-1"></i>
                    Filter Periode:
                    {{ request('month') ? date('F', mktime(0,0,0,request('month'),1)) : 'Semua Bulan' }}
                    {{ request('year') ? request('year') : '' }}
                </span>
            @else
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5">
                    <i class="bi bi-lock-fill me-1"></i>
                    Tanggal Dana Cair Terkunci Otomatis Sesuai Pengaturan Masing-Masing Tim
                </span>
            @endif
        </div>
    </div>
</div>

<!-- Alert Flash -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-sm mb-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm mb-3" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <strong class="fw-bold">Mohon periksa kembali inputan Anda:</strong>
        </div>
        <ul class="mb-0 small ps-4">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Main Table Card -->
<div class="v2-card mb-3">
    <div class="v2-card-header bg-light py-2 d-flex align-items-center justify-content-between">
        <h6 class="v2-card-title d-flex align-items-center gap-2 m-0">
            <i class="bi bi-journal-text text-primary"></i> Daftar Tim & Target
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">
                {{ $teams->count() }} Tim
            </span>
        </h6>
    </div>

    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th>TIM & TOKO TERHUBUNG</th>
                        <th class="text-end">TARGET MARGIN (RP)</th>
                        <th class="text-end">SKEMA KOMISI</th>
                        <th class="text-end">TOTAL KOMISI</th>
                        <th class="text-center" style="min-width: 200px;">REALISASI & PROGRESS MARGIN</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center" style="width: 105px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($teams as $index => $team)
                    @php
                        $actMargin = $team->custom_actual_margin ?? $team->actual_margin;
                        $actVal    = $team->custom_actual_value ?? $team->actual_value;
                        $totRew    = $team->custom_total_reward ?? $team->total_reward;
                        $pct       = $team->custom_progress_percent ?? $team->value_progress_percent;
                        $cType     = $team->commission_type ?: 'percentage';
                    @endphp
                    <tr>
                        <td class="ps-4 text-muted fw-medium">{{ $index + 1 }}</td>
                        <td class="py-3">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                @php
                                    $transParams = [];
                                    if (request()->filled('month') || request()->filled('year')) {
                                        $transParams['month'] = request('month');
                                        $transParams['year']  = request('year');
                                    } elseif (request()->filled('date_from') && request()->filled('date_to')) {
                                        $transParams['date_from'] = request('date_from');
                                        $transParams['date_to']   = request('date_to');
                                    } elseif ($team->date_from && $team->date_to) {
                                        $transParams['date_from'] = $team->date_from instanceof \Carbon\Carbon ? $team->date_from->format('Y-m-d') : (string)$team->date_from;
                                        $transParams['date_to']   = $team->date_to instanceof \Carbon\Carbon ? $team->date_to->format('Y-m-d') : (string)$team->date_to;
                                    }
                                @endphp
                                <a href="{{ route('marketing.teams.transactions', array_merge([$team->id], $transParams)) }}" class="fw-bold text-primary text-decoration-none hover-underline">
                                    {{ $team->name }}
                                </a>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold" title="Acuan Periode Dana Cair Terkunci">
                                    <i class="bi bi-lock-fill me-1"></i>{{ $team->period_label }}
                                </span>
                            </div>
                            
                            @if($team->description)
                                <p class="text-secondary small mb-2">{{ $team->description }}</p>
                            @endif

                            <!-- Badges Toko -->
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @forelse($team->stores as $store)
                                    @php
                                        $chName = strtolower($store->channel->name ?? '');
                                        $badgeClass = 'bg-secondary text-white';
                                        $icon = 'bi-shop';
                                        if (str_contains($chName, 'shopee')) {
                                            $badgeClass = 'bg-danger text-white';
                                            $icon = 'bi-bag-check-fill';
                                        } elseif (str_contains($chName, 'tiktok')) {
                                            $badgeClass = 'bg-dark text-white';
                                            $icon = 'bi-tiktok';
                                        } elseif (str_contains($chName, 'tokopedia')) {
                                            $badgeClass = 'bg-success text-white';
                                            $icon = 'bi-shop-window';
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill px-2.5 py-1 small fw-normal">
                                        <i class="bi {{ $icon }} me-1"></i>{{ $store->store_name }}
                                    </span>
                                @empty
                                    <span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Belum ada toko dihubungkan</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- Target Margin (Rp) -->
                        <td class="text-end py-3">
                            <span class="fw-bold text-dark fs-6">Rp {{ number_format($team->target_omset, 0, ',', '.') }}</span>
                            <span class="text-muted small d-block">Target Margin (Rp)</span>
                        </td>

                        <!-- Skema Komisi -->
                        <td class="text-end py-3">
                            @if($cType === 'percentage')
                                <span class="fw-bold text-primary">{{ number_format($team->commission_rate, 2) }}%</span>
                                <span class="text-muted small d-block">dari Margin (Rp)</span>
                                @if($team->reward_fixed_nominal > 0)
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-0.5 mt-1 small" style="font-size:0.7rem;">
                                        + Bonus Rp {{ number_format($team->reward_fixed_nominal, 0, ',', '.') }}
                                    </span>
                                @endif
                            @elseif($cType === 'nominal')
                                <span class="fw-bold text-primary">Rp {{ number_format($team->reward_fixed_nominal, 0, ',', '.') }}</span>
                                <span class="text-muted small d-block">saat Target Tercapai</span>
                            @else
                                <span class="fw-bold text-primary">Rp {{ number_format($team->reward_per_qty, 0, ',', '.') }}</span>
                                <span class="text-muted small d-block">/ Qty Produk</span>
                            @endif
                        </td>

                        <!-- Total Komisi -->
                        <td class="text-end py-3">
                            <span class="fw-bold text-success fs-6">Rp {{ number_format($totRew, 0, ',', '.') }}</span>
                            @if($cType === 'percentage')
                                <span class="text-muted small d-block">({{ number_format($team->commission_rate, 2) }}% × Rp {{ number_format($actMargin, 0, ',', '.') }})</span>
                            @elseif($cType === 'nominal')
                                <span class="text-muted small d-block">{{ $actMargin >= $team->target_omset ? 'Target Tercapai' : 'Belum Capai Target' }}</span>
                            @else
                                <span class="text-muted small d-block">({{ number_format($team->custom_actual_qty ?? $team->actual_qty) }} Qty × Rp {{ number_format($team->reward_per_qty, 0, ',', '.') }})</span>
                            @endif
                        </td>

                        <!-- Realisasi & Progress Margin -->
                        <td class="py-3 px-3">
                            @php
                                $barClass = 'bg-danger';
                                if ($pct >= 100) $barClass = 'bg-success';
                                elseif ($pct >= 50) $barClass = 'bg-warning';
                            @endphp
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-cash-stack text-secondary me-1"></i>Rp {{ number_format($actMargin, 0, ',', '.') }}
                                </span>
                                <span class="badge bg-light text-dark border rounded-pill"><i class="bi bi-graph-up-arrow text-primary me-1"></i>{{ $pct }}%</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 8px;">
                                <div class="progress-bar {{ $barClass }} rounded-pill" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="text-muted small mt-1 text-center" style="font-size:0.7rem;">
                                Target: Rp {{ number_format($team->target_omset, 0, ',', '.') }}
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="text-center py-3">
                            @if($team->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">
                                    Aktif
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1">
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        <!-- Action Buttons -->
                        <td class="pe-4 text-end py-3">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical text-secondary"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 small">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('marketing.teams.transactions', [$team->id, 'month' => request('month'), 'year' => request('year'), 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}">
                                            <i class="bi bi-list-check text-info"></i> Detail Transaksi
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-bs-toggle="modal" data-bs-target="#editTeamModal{{ $team->id }}">
                                            <i class="bi bi-pencil text-primary"></i> Edit Tim & Target
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('marketing.teams.toggle_status', $team->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                                <i class="bi bi-power {{ $team->is_active ? 'text-warning' : 'text-success' }}"></i>
                                                {{ $team->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <form action="{{ route('marketing.teams.destroy', $team->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Tim {{ $team->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2">
                                                <i class="bi bi-trash"></i> Hapus Tim
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Edit Tim (No Scroll) -->
                    <div class="modal fade" id="editTeamModal{{ $team->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 rounded-3 shadow">
                                <form action="{{ route('marketing.teams.update', $team->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-bottom px-4 py-3">
                                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="bi bi-pencil-square text-primary"></i>
                                            <span>Edit Tim: {{ $team->name }}</span>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body px-4 py-3">
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-semibold small text-dark">Nama Tim Marketing <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="{{ old('name', $team->name) }}" required placeholder="Contoh: Tim Ruang">
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-semibold small text-dark">
                                                    <i class="bi bi-calendar3 text-primary me-1"></i>Bulan & Tahun Bawaan
                                                </label>
                                                <div class="row g-2">
                                                    <div class="col-7">
                                                        <select name="period_month" id="edit_month_{{ $team->id }}" class="form-select period-month-select" data-target-id="{{ $team->id }}">
                                                            @for($m = 1; $m <= 12; $m++)
                                                                <option value="{{ $m }}" {{ old('period_month', $team->period_month) == $m ? 'selected' : '' }}>
                                                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="number" name="period_year" id="edit_year_{{ $team->id }}" class="form-control period-year-input" data-target-id="{{ $team->id }}" value="{{ old('period_year', $team->period_year ?? date('Y')) }}" placeholder="Tahun">
                                                    </div>
                                                </div>
                                                <div class="form-text text-muted" style="font-size:0.72rem;">
                                                    Memilih bulan/tahun otomatis menyesuaikan tanggal cair di samping.
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-semibold small text-dark">
                                                    <i class="bi bi-lock-fill text-success me-1"></i>Tanggal Dana Cair (Dari - Sampai) <span class="text-danger">*</span>
                                                </label>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <input type="date" name="date_from" id="edit_date_from_{{ $team->id }}" class="form-control"
                                                            value="{{ old('date_from', $team->date_from ? ($team->date_from instanceof \Carbon\Carbon ? $team->date_from->format('Y-m-d') : (string)$team->date_from) : '') }}" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="date" name="date_to" id="edit_date_to_{{ $team->id }}" class="form-control"
                                                            value="{{ old('date_to', $team->date_to ? ($team->date_to instanceof \Carbon\Carbon ? $team->date_to->format('Y-m-d') : (string)$team->date_to) : '') }}" required>
                                                    </div>
                                                </div>
                                                <div class="form-text text-muted" style="font-size:0.72rem;">
                                                    <i class="bi bi-info-circle me-1"></i>Acuan terkunci tanggal pencairan (<code>completed_at</code>) pesanan tim.
                                                </div>
                                            </div>

                                            <!-- Target Margin (Rp) -->
                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-semibold small text-dark">
                                                    <i class="bi bi-graph-up-arrow text-primary me-1"></i>Target Margin (Rp) <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted">Rp</span>
                                                    <input type="number" name="target_omset" class="form-control fw-bold" value="{{ old('target_omset', $team->target_omset) }}" min="0" required placeholder="50000000">
                                                </div>
                                                <div class="form-text text-muted" style="font-size:0.72rem;">
                                                    Target margin / laba kotor rupiah (Nilai Dilepas - HPP) pesanan selesai.
                                                </div>
                                            </div>

                                            <!-- Skema Komisi -->
                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-semibold small text-dark">Tipe Skema Komisi</label>
                                                <select name="commission_type" class="form-select" id="edit_comm_type_{{ $team->id }}">
                                                    <option value="percentage" {{ old('commission_type', $team->commission_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>
                                                        Persentase Margin (% dari Margin Rp)
                                                    </option>
                                                    <option value="nominal" {{ old('commission_type', $team->commission_type) === 'nominal' ? 'selected' : '' }}>
                                                        Bonus Nominal Flat jika Target Margin Tercapai
                                                    </option>
                                                    <option value="qty" {{ old('commission_type', $team->commission_type) === 'qty' ? 'selected' : '' }}>
                                                        Per Qty Produk (Legacy)
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-semibold small text-dark">Persentase Komisi (%)</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="commission_rate" class="form-control" value="{{ old('commission_rate', $team->commission_rate) }}" min="0" placeholder="1.5">
                                                    <span class="input-group-text bg-light text-muted">%</span>
                                                </div>
                                                <div class="form-text text-muted" style="font-size:0.72rem;">
                                                    % dihitung dari total Margin (Rp) yang dicapai.
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-semibold small text-dark">Bonus Flat Capai Target (Rp)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted">Rp</span>
                                                    <input type="number" name="reward_fixed_nominal" class="form-control" value="{{ old('reward_fixed_nominal', $team->reward_fixed_nominal) }}" min="0" placeholder="0">
                                                </div>
                                                <div class="form-text text-muted" style="font-size:0.72rem;">
                                                    Bonus tambahan jika target margin (Rp) terpenuhi.
                                                </div>
                                            </div>

                                            <!-- Legacy Qty Fields in Accordion -->
                                            <div class="col-12">
                                                <div class="accordion" id="accordionEditQty_{{ $team->id }}">
                                                    <div class="accordion-item border rounded-2">
                                                        <h2 class="accordion-header">
                                                            <button class="accordion-button collapsed py-2 px-3 small text-muted bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditQty_{{ $team->id }}" aria-expanded="false">
                                                                <i class="bi bi-sliders me-2"></i>Pengaturan Tambahan Target Qty (Opsional / Legacy)
                                                            </button>
                                                        </h2>
                                                        <div id="collapseEditQty_{{ $team->id }}" class="accordion-collapse collapse" data-bs-parent="#accordionEditQty_{{ $team->id }}">
                                                            <div class="accordion-body p-3">
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <label class="form-label small text-dark">Target Qty (Pcs)</label>
                                                                        <input type="number" name="target_qty" class="form-control form-control-sm" value="{{ old('target_qty', $team->target_qty) }}" min="0">
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="form-label small text-dark">Komisi per Qty (Rp)</label>
                                                                        <input type="number" name="reward_per_qty" class="form-control form-control-sm" value="{{ old('reward_per_qty', $team->reward_per_qty) }}" min="0">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold small text-dark mb-1">Pilih Toko Terhubung</label>
                                                <div class="card border rounded-3 p-3 bg-light">
                                                    <div class="row g-2">
                                                        @php
                                                            $linkedStoreIds = $team->stores->pluck('id')->toArray();
                                                        @endphp
                                                        @forelse($stores as $st)
                                                            <div class="col-12 col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="edit_st_{{ $team->id }}_{{ $st->id }}" {{ in_array($st->id, $linkedStoreIds) ? 'checked' : '' }}>
                                                                    <label class="form-check-label small text-dark" for="edit_st_{{ $team->id }}_{{ $st->id }}">
                                                                        <strong>{{ $st->store_name }}</strong>
                                                                        @if($st->channel)
                                                                            <span class="text-muted">({{ $st->channel->name }})</span>
                                                                        @endif
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="col-12 text-muted small fst-italic">Belum ada toko terdaftar.</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold small text-dark">Catatan / Description</label>
                                                <textarea name="description" class="form-control" rows="2" placeholder="Catatan internal tim...">{{ old('description', $team->description) }}</textarea>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_active_{{ $team->id }}" {{ $team->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold small text-dark" for="edit_active_{{ $team->id }}">Status Tim Aktif</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top px-4 py-3">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                            Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="py-4">
                                <i class="bi bi-people fs-1 text-muted opacity-50 d-block mb-2"></i>
                                <h6 class="fw-semibold text-secondary">Belum Ada Tim Marketing</h6>
                                <p class="text-muted small mb-3">Klik tombol <strong>Tambah Tim Baru</strong> untuk membuat tim & target pertama Anda.</p>
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createTeamModal">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tim Baru
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal Create Tim Marketing (No Scroll) -->
<div class="modal fade" id="createTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-3 shadow">
            <form action="{{ route('marketing.teams.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle text-primary"></i>
                        <span>Tambah Tim Marketing & Target Baru</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small text-dark">Nama Tim Marketing <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Contoh: Tim Ruang, Tim Nusantara">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small text-dark">
                                <i class="bi bi-calendar3 text-primary me-1"></i>Bulan & Tahun Bawaan
                            </label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <select name="period_month" id="create_period_month" class="form-select">
                                        @for($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ old('period_month', date('n')) == $m ? 'selected' : '' }}>
                                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-5">
                                    <input type="number" name="period_year" id="create_period_year" class="form-control" value="{{ old('period_year', date('Y')) }}" placeholder="Tahun">
                                </div>
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">
                                Memilih bulan/tahun otomatis menyesuaikan tanggal cair di samping.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small text-dark">
                                <i class="bi bi-lock-fill text-success me-1"></i>Tanggal Dana Cair (Dari - Sampai) <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" name="date_from" id="create_date_from" class="form-control" value="{{ old('date_from', date('Y-m-01')) }}" required>
                                </div>
                                <div class="col-6">
                                    <input type="date" name="date_to" id="create_date_to" class="form-control" value="{{ old('date_to', date('Y-m-t')) }}" required>
                                </div>
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">
                                <i class="bi bi-info-circle me-1"></i>Acuan terkunci tanggal pencairan (<code>completed_at</code>) pesanan tim.
                            </div>
                        </div>

                        <!-- Target Margin (Rp) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small text-dark">
                                <i class="bi bi-graph-up-arrow text-primary me-1"></i>Target Margin (Rp) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="target_omset" class="form-control fw-bold" value="{{ old('target_omset', 50000000) }}" min="0" required placeholder="50000000">
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">
                                Target margin / laba kotor rupiah (Nilai Dilepas - HPP) pesanan selesai.
                            </div>
                        </div>

                        <!-- Skema Komisi -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small text-dark">Tipe Skema Komisi</label>
                            <select name="commission_type" class="form-select" id="create_comm_type">
                                <option value="percentage" {{ old('commission_type', 'percentage') === 'percentage' ? 'selected' : '' }}>
                                    Persentase Margin (% dari Margin Rp)
                                </option>
                                <option value="nominal" {{ old('commission_type') === 'nominal' ? 'selected' : '' }}>
                                    Bonus Nominal Flat jika Target Margin Tercapai
                                </option>
                                <option value="qty" {{ old('commission_type') === 'qty' ? 'selected' : '' }}>
                                    Per Qty Produk (Legacy)
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small text-dark">Persentase Komisi (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="commission_rate" class="form-control" value="{{ old('commission_rate', 1.0) }}" min="0" placeholder="1.0">
                                <span class="input-group-text bg-light text-muted">%</span>
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">
                                % dihitung langsung dari total Margin (Rp) yang dicapai.
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small text-dark">Bonus Flat Capai Target (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="reward_fixed_nominal" class="form-control" value="{{ old('reward_fixed_nominal', 0) }}" min="0" placeholder="0">
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">
                                Bonus tambahan jika target margin (Rp) terpenuhi (opsional).
                            </div>
                        </div>

                        <!-- Legacy Qty Fields in Accordion -->
                        <div class="col-12">
                            <div class="accordion" id="accordionCreateQty">
                                <div class="accordion-item border rounded-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2 px-3 small text-muted bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateQty" aria-expanded="false">
                                            <i class="bi bi-sliders me-2"></i>Pengaturan Tambahan Target Qty (Opsional / Legacy)
                                        </button>
                                    </h2>
                                    <div id="collapseCreateQty" class="accordion-collapse collapse" data-bs-parent="#accordionCreateQty">
                                        <div class="accordion-body p-3">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small text-dark">Target Qty (Pcs)</label>
                                                    <input type="number" name="target_qty" class="form-control form-control-sm" value="{{ old('target_qty', 0) }}" min="0">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small text-dark">Komisi per Qty (Rp)</label>
                                                    <input type="number" name="reward_per_qty" class="form-control form-control-sm" value="{{ old('reward_per_qty', 0) }}" min="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-dark mb-1">Pilih Toko Terhubung</label>
                            <div class="card border rounded-3 p-3 bg-light">
                                <div class="row g-2">
                                    @forelse($stores as $st)
                                        <div class="col-12 col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="create_st_{{ $st->id }}">
                                                <label class="form-check-label small text-dark" for="create_st_{{ $st->id }}">
                                                    <strong>{{ $st->store_name }}</strong>
                                                    @if($st->channel)
                                                        <span class="text-muted">({{ $st->channel->name }})</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted small fst-italic">Belum ada toko terdaftar.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="form-text small text-muted mt-1">Pilih toko yang dikelola oleh tim ini (misal: Ruang Seragam Tiktok, Ruang Seragam Shopee).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-dark">Catatan / Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Catatan internal tim...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="create_is_active" checked>
                                <label class="form-check-label fw-semibold small text-dark" for="create_is_active">Status Tim Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        Simpan Tim Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Modal Pengecualian Komisi Produk -->
    <div class="modal fade" id="excludeProductsModal" tabindex="-1" aria-labelledby="excludeProductsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form action="{{ route('marketing.teams.exclude_products') }}" method="POST">
                @csrf
                <div class="modal-content rounded-3 border-0 shadow">
                    <div class="modal-header bg-danger text-white py-3">
                        <h5 class="modal-title fw-bold" id="excludeProductsModalLabel">
                            <i class="bi bi-slash-circle me-2"></i>Pengecualian Komisi Produk
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-secondary small mb-3">
                            Centang produk di bawah ini yang <strong>tidak ingin dimasukkan</strong> ke dalam perhitungan target kuantitas (Qty) maupun komisi marketing.
                        </p>

                        <!-- Search Input -->
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="modalProductSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Cari produk berdasarkan nama atau SKU...">
                        </div>

                        <!-- Product List Container -->
                        <div class="border rounded-3" style="max-height: 400px; overflow-y: auto; background-color: #fafafa;">
                            <table class="table table-hover align-middle mb-0" id="modalProductsTable">
                                <thead class="table-light sticky-top small text-uppercase fw-semibold">
                                    <tr>
                                        <th class="ps-3 py-2 text-center" style="width: 50px;">Pilih</th>
                                        <th class="py-2">Nama Produk / SKU</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($masterProducts as $product)
                                        <tr class="modal-product-item" data-name="{{ $product->name }}" data-sku="{{ $product->sku }}">
                                            <td class="text-center ps-3">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="excluded_product_ids[]" value="{{ $product->id }}" id="chk_prod_{{ $product->id }}" {{ $product->exclude_commission ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="py-2">
                                                <label class="form-check-label d-block text-dark fw-medium small mb-0" for="chk_prod_{{ $product->id }}" style="cursor: pointer;">
                                                    {{ $product->name }}
                                                </label>
                                                <span class="text-muted" style="font-size: 0.72rem;">
                                                    SKU: <code>{{ $product->sku ?: '—' }}</code>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-4 text-muted small">
                                                Tidak ada produk master ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold shadow-sm">Simpan Pengaturan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    // Helper untuk sinkronisasi tanggal otomatis berdasarkan bulan & tahun yang dipilih
    function updateDateRangeForMonth(month, year, dateFromInput, dateToInput) {
        if (!month || !year) return;
        const m = parseInt(month, 10);
        const y = parseInt(year, 10);
        if (isNaN(m) || isNaN(y) || m < 1 || m > 12) return;

        const pad = (n) => (n < 10 ? '0' + n : '' + n);
        const firstDay = `${y}-${pad(m)}-01`;
        const lastDateObj = new Date(y, m, 0);
        const lastDay = `${y}-${pad(m)}-${pad(lastDateObj.getDate())}`;

        if (dateFromInput) dateFromInput.value = firstDay;
        if (dateToInput) dateToInput.value = lastDay;
    }

    // Modal Create auto-sync
    const createMonth    = document.getElementById('create_period_month');
    const createYear     = document.getElementById('create_period_year');
    const createDateFrom = document.getElementById('create_date_from');
    const createDateTo   = document.getElementById('create_date_to');

    if (createMonth && createYear) {
        createMonth.addEventListener('change', function () {
            updateDateRangeForMonth(createMonth.value, createYear.value, createDateFrom, createDateTo);
        });
        createYear.addEventListener('input', function () {
            updateDateRangeForMonth(createMonth.value, createYear.value, createDateFrom, createDateTo);
        });
    }

    // Modal Edit auto-sync (per modal)
    document.querySelectorAll('.period-month-select').forEach(function (select) {
        const teamId = select.getAttribute('data-target-id');
        const yearInput     = document.getElementById('edit_year_' + teamId);
        const dateFromInput = document.getElementById('edit_date_from_' + teamId);
        const dateToInput   = document.getElementById('edit_date_to_' + teamId);

        select.addEventListener('change', function () {
            if (yearInput) {
                updateDateRangeForMonth(select.value, yearInput.value, dateFromInput, dateToInput);
            }
        });
        if (yearInput) {
            yearInput.addEventListener('input', function () {
                updateDateRangeForMonth(select.value, yearInput.value, dateFromInput, dateToInput);
            });
        }
    });

    // Search filter untuk produk di modal
    const searchInput = document.getElementById('modalProductSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('.modal-product-item');
            rows.forEach(function (row) {
                const name = row.getAttribute('data-name').toLowerCase();
                const sku = row.getAttribute('data-sku').toLowerCase();
                if (name.includes(query) || sku.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
})();
</script>
@endpush
