@extends('layouts.app')

@section('title', 'Target & Tim Marketing')

@section('content')
<div class="container-fluid px-4 py-4 bg-light">
    
    <!-- Header Section -->
    <div class="mb-4">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-semibold mb-1">
            <i class="bi bi-bullseye me-1"></i> MODUL MARKETING
        </span>
        <h3 class="fw-bold text-dark mb-1">Target & Tim Marketing</h3>
        <p class="text-secondary small mb-1">
            Kelola alokasi toko marketplace, target penjualan Qty, dan insentif komisi rupiah per-Qty.
        </p>
        <div class="text-primary small">
            <i class="bi bi-info-circle me-1"></i>Realisasi dihitung khusus pesanan <strong>Selesai / Dilepas (Completed)</strong> berdasarkan <strong>Tanggal Diterima (`completed_at`)</strong>. Pesanan Retur/Refund & Batal otomatis dikecualikan.
        </div>
    </div>

    <!-- Simple KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Tim Marketing</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalTeams) }} <span class="fs-6 fw-normal text-muted">Tim</span></h4>
                        <span class="badge bg-success bg-opacity-10 text-success mt-2 fw-medium rounded-pill px-2 py-1">
                            <i class="bi bi-check-circle me-1"></i>{{ number_format($activeTeams) }} Aktif
                        </span>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Toko Terhubung</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalStoresLinked) }} <span class="fs-6 fw-normal text-muted">Toko</span></h4>
                        <span class="text-muted small mt-2 d-block">
                            Dari total {{ $stores->count() }} toko ERP
                        </span>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-shop fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Target Qty (Aktif)</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalTargetQty) }} <span class="fs-6 fw-normal text-muted">Qty</span></h4>
                        <span class="text-success small mt-2 d-block fw-semibold">
                            Realisasi: {{ number_format($totalActualQty) }} Qty
                        </span>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-crosshair fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Insentif Realisasi</span>
                        <h4 class="fw-bold text-success mb-0">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</h4>
                        <span class="text-muted small mt-2 d-block">
                            (Actual Qty × Rp/Qty)
                        </span>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Header & Quick Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 mt-4">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-funnel-fill small"></i>
            </div>
            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">Filter Periode</h5>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#excludeProductsModal" style="font-size: 0.8rem;">
                <i class="bi bi-slash-circle fs-6"></i>
                <span>Pengecualian Komisi</span>
            </button>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createTeamModal" style="font-size: 0.8rem;">
                <i class="bi bi-plus-lg fs-6"></i>
                <span>Tambah Tim Baru</span>
            </button>
        </div>
    </div>

    <!-- Filter Card Bar -->
    <div class="card border-0 rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <form id="filterForm" action="{{ route('marketing.teams.index') }}" method="GET" class="row g-2 align-items-end">

                <!-- Filter Bulan & Tahun (Opsional) -->
                <div class="col-12 col-md-6 col-lg-5">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="bi bi-calendar3 me-1 text-primary"></i>Filter Periode Target (Bulan & Tahun)
                        <span class="text-muted fw-normal">(opsional)</span>
                    </label>
                    <div class="row g-1">
                        <div class="col-7">
                            <select id="filterMonth" name="month" class="form-select form-select-sm">
                                <option value="">-- Semua Bulan --</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-5">
                            <select id="filterYear" name="year" class="form-select form-select-sm">
                                <option value="">-- Semua Tahun --</option>
                                @foreach($availableYears as $yr)
                                    <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>
                                        {{ $yr }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tombol Action -->
                <div class="col-12 col-md-6 col-lg-7 text-md-end d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                        <i class="bi bi-search me-1"></i> Terapkan Filter
                    </button>
                    @if(request()->hasAny(['month', 'year', 'search']))
                        <a href="{{ route('marketing.teams.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- Info Mode Filter Aktif --}}
            <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2 flex-wrap">
                <small class="text-muted fw-medium">Status Acuan Realisasi:</small>
                @if($hasExplicitMonthYear)
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1" style="font-size:0.75rem;">
                        <i class="bi bi-calendar3 me-1"></i>
                        Filter Periode:
                        {{ request('month') ? date('F', mktime(0,0,0,request('month'),1)) : 'Semua Bulan' }}
                        {{ request('year') ? request('year') : '' }}
                    </span>
                @else
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1" style="font-size:0.75rem;">
                        <i class="bi bi-lock-fill me-1"></i>
                        Tanggal Dana Cair Terkunci Otomatis Sesuai Pengaturan Masing-Masing Tim
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert Flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4" role="alert">
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

    <!-- Main List Card (No Scroll) -->
    <div class="card border-0 rounded-3 shadow-sm bg-white">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-primary"></i>
                <span>Daftar Tim & Target</span>
            </h6>
            <form action="{{ route('marketing.teams.index') }}" method="GET" class="d-flex gap-2">
                @if(request()->filled('month'))
                    <input type="hidden" name="month" value="{{ request('month') }}">
                @endif
                @if(request()->filled('year'))
                    <input type="hidden" name="year" value="{{ request('year') }}">
                @endif
                <div class="input-group input-group-sm rounded-pill border">
                    <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-0 shadow-none ps-1" placeholder="Cari tim..." value="{{ request('search') }}">
                </div>
            </form>
        </div>

        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase fw-semibold">
                <tr>
                    <th class="ps-4 py-3">#</th>
                    <th class="py-3"><i class="bi bi-people me-1 text-primary"></i>Tim & Toko Terhubung</th>
                    <th class="py-3 text-end"><i class="bi bi-box-seam me-1 text-primary"></i>Target Qty</th>
                    <th class="py-3 text-end"><i class="bi bi-currency-dollar me-1 text-primary"></i>Rupiah / Qty</th>
                    <th class="py-3 text-end"><i class="bi bi-wallet2 me-1 text-primary"></i>Total Insentif</th>
                    <th class="py-3 text-center"><i class="bi bi-bar-chart-line me-1 text-primary"></i>Progress Qty</th>
                    <th class="py-3 text-center"><i class="bi bi-toggle-on me-1 text-primary"></i>Status</th>
                    <th class="py-3 pe-4 text-end"><i class="bi bi-gear me-1 text-primary"></i>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teams as $index => $team)
                    @php
                        $actQty = $team->custom_actual_qty ?? $team->actual_qty;
                        $totRew = $team->custom_total_reward ?? $team->total_reward;
                        $pct    = $team->custom_progress_percent ?? $team->qty_progress_percent;
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

                        <!-- Target Qty -->
                        <td class="text-end py-3">
                            <span class="fw-bold text-dark fs-6">{{ number_format($team->target_qty) }}</span>
                            <span class="text-muted small d-block">Qty</span>
                        </td>

                        <!-- Rupiah per Qty -->
                        <td class="text-end py-3">
                            <span class="fw-bold text-primary">Rp {{ number_format($team->reward_per_qty, 0, ',', '.') }}</span>
                            <span class="text-muted small d-block">/ Qty</span>
                        </td>

                        <!-- Total Insentif -->
                        <td class="text-end py-3">
                            <span class="fw-bold text-success">Rp {{ number_format($totRew, 0, ',', '.') }}</span>
                            <span class="text-muted small d-block">({{ number_format($actQty) }} Qty × Rp {{ number_format($team->reward_per_qty, 0, ',', '.') }})</span>
                        </td>

                        <!-- Progress Bar -->
                        <td class="py-3 px-3">
                            @php
                                $barClass = 'bg-danger';
                                if ($pct >= 100) $barClass = 'bg-success';
                                elseif ($pct >= 50) $barClass = 'bg-warning';
                            @endphp
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-dark"><i class="bi bi-box-seam text-secondary me-1"></i>{{ number_format($actQty) }} / {{ number_format($team->target_qty) }}</span>
                                <span class="badge bg-light text-dark border rounded-pill"><i class="bi bi-graph-up-arrow text-primary me-1"></i>{{ $pct }}%</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 8px;">
                                <div class="progress-bar {{ $barClass }} rounded-pill" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
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

                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-semibold small text-dark">Target Qty (Jumlah Pesanan) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" name="target_qty" class="form-control" value="{{ old('target_qty', $team->target_qty) }}" min="0" required placeholder="1000">
                                                    <span class="input-group-text bg-light text-muted">Qty</span>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="form-label fw-semibold small text-dark">Rupiah per Qty (Insentif/Komisi) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light text-muted">Rp</span>
                                                    <input type="number" name="reward_per_qty" class="form-control" value="{{ old('reward_per_qty', $team->reward_per_qty) }}" min="0" required placeholder="1000">
                                                    <span class="input-group-text bg-light text-muted">/ Qty</span>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold small text-dark mb-1">Pilih Toko Terhubung</label>
                                                <div class="card border rounded-3 p-3 bg-light">
                                                    <div class="row g-2">
                                                        @php $selectedStoreIds = $team->stores->pluck('id')->toArray(); @endphp
                                                        @forelse($stores as $st)
                                                            <div class="col-12 col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="edit_st_{{ $team->id }}_{{ $st->id }}" {{ in_array($st->id, $selectedStoreIds) ? 'checked' : '' }}>
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

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small text-dark">Target Qty (Jumlah Pesanan) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="target_qty" class="form-control" value="{{ old('target_qty', 1000) }}" min="0" required placeholder="1000">
                                <span class="input-group-text bg-light text-muted">Qty</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small text-dark">Rupiah per Qty (Insentif/Komisi) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="reward_per_qty" class="form-control" value="{{ old('reward_per_qty', 1000) }}" min="0" required placeholder="1000">
                                <span class="input-group-text bg-light text-muted">/ Qty</span>
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
