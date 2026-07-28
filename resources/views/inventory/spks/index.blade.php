@extends('layouts.app')
@section('title', 'Marketing & Pengiriman')
@section('page-title', 'Marketing & Pengiriman')

@section('content')
    <div class="container-fluid px-2 px-md-3 py-2">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 bg-success bg-opacity-10 text-success fw-bold"
                role="alert">
                <i class="fas fa-check-circle me-2 fs-5 align-middle"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- HEADER SECTION WITH KPI CARDS --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-extrabold text-dark mb-1 tracking-tight" style="font-size: 1.6rem;">
                    <i class="fas fa-industry me-2 text-primary"></i>Marketing &amp; Pengiriman
                </h3>
                <p class="text-muted small mb-0">Pantau seluruh antrian pesanan SPK, bagikan link pelacakan pelanggan, dan
                    atur prioritas Urgent.</p>
            </div>
            @can('spks.create')
                <div class="d-flex gap-2">
                    <a href="{{ route('spks.create') }}"
                        class="btn btn-primary fw-bold px-3.5 py-2.5 rounded-3 shadow-sm d-inline-flex align-items-center gap-2 hover-elevate">
                        <i class="fas fa-plus-circle fs-6"></i>
                        <span>Buat SPK Baru</span>
                    </a>
                </div>
            @endcan
        </div>

        {{-- KPI STATS SUMMARY CARDS --}}
        <div class="row g-3 mb-4">
            {{-- Total Produksi --}}
            <div class="col-12 col-sm-6 col-xl-4">
                <div
                    class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden h-100 position-relative border-start border-4 border-primary">
                    <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-bold small text-uppercase tracking-wider d-block mb-1">Total Antrian
                                Produksi</span>
                            <h3 class="fw-extrabold text-dark mb-0">
                                {{ number_format($stats['total_produksi'] ?? $spks->total()) }} <span
                                    class="fs-6 fw-normal text-muted">Grup</span></h3>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center"
                            style="width: 52px; height: 52px;">
                            <i class="fas fa-boxes-stacked fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Urgent Jobs --}}
            <div class="col-12 col-sm-6 col-xl-4">
                <div
                    class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden h-100 position-relative border-start border-4 border-warning">
                    <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-bold small text-uppercase tracking-wider d-block mb-1">Pesanan
                                Urgent</span>
                            <h3 class="fw-extrabold text-dark mb-0 text-danger">
                                {{ number_format($stats['total_urgent'] ?? 0) }}
                                <span class="fs-6 fw-normal text-muted">SPK</span>
                            </h3>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-15 text-warning p-3 d-flex align-items-center justify-content-center"
                            style="width: 52px; height: 52px;">
                            <i class="fas fa-bolt fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Pcs --}}
            <div class="col-12 col-sm-12 col-xl-4">
                <div
                    class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden h-100 position-relative border-start border-4 border-success">
                    <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-bold small text-uppercase tracking-wider d-block mb-1">Total Volume
                                Pcs</span>
                            <h3 class="fw-extrabold text-dark mb-0 text-success">
                                {{ number_format($stats['total_pcs'] ?? 0) }} <span
                                    class="fs-6 fw-normal text-muted">Pcs</span></h3>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center"
                            style="width: 52px; height: 52px;">
                            <i class="fas fa-tshirt fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER & SEARCH BAR CONTAINER (SELECT DROPDOWN FILTER) --}}
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-body p-3">
                <form action="{{ route('spks.index') }}" method="GET" class="m-0">
                    <div class="row g-2.5 align-items-center">

                        {{-- STAGE & STATUS SELECT DROPDOWN (COL 12 / COL MD 5) --}}
                        <div class="col-12 col-md-5 col-lg-5">
                            @php
                                $currStage = request('stage');
                                $isUrgent = request('urgent') == '1';
                                $selectedFilter = $isUrgent ? 'urgent' : ($currStage ?: '');
                            @endphp
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0 text-muted fw-bold ps-3 pe-2">
                                    <i class="fas fa-filter text-primary"></i>
                                </span>
                                <select name="stage"
                                    class="form-select form-select-sm border-0 bg-light fw-bold text-dark rounded-end pe-4"
                                    style="height: 38px; cursor: pointer;" onchange="this.form.submit()">
                                    <option value="" {{ $selectedFilter === '' ? 'selected' : '' }}>🌐 Semua SPK
                                        (Semua Status)</option>
                                    <option value="urgent" {{ $selectedFilter === 'urgent' ? 'selected' : '' }}>⚡ Pesanan
                                        Urgent</option>
                                    <option value="draft" {{ $selectedFilter === 'draft' ? 'selected' : '' }}>📝 DRAFT (Belum Deal / Menunggu DP)</option>
                                    <option value="desain" {{ $selectedFilter === 'desain' ? 'selected' : '' }}>🎨 Tahap Desain &amp; Mockup</option>
                                    <option value="pesanan_baru" {{ $selectedFilter === 'pesanan_baru' ? 'selected' : '' }}>📋 Pesanan Baru / Perencanaan</option>
                                    <option value="sampling" {{ $selectedFilter === 'sampling' ? 'selected' : '' }}>⏳
                                        Antrian &amp; Sampling</option>
                                    <option value="potong" {{ $selectedFilter === 'potong' ? 'selected' : '' }}>✂️ Tahap
                                        Pemotongan (Potong)</option>
                                    <option value="sablon_bordir"
                                        {{ $selectedFilter === 'sablon_bordir' ? 'selected' : '' }}>🎨 Sablon / Bordir
                                    </option>
                                    <option value="jahit" {{ $selectedFilter === 'jahit' ? 'selected' : '' }}>🪡 Tahap
                                        Jahit</option>
                                    <option value="lkpk" {{ $selectedFilter === 'lkpk' ? 'selected' : '' }}>💿 Tahap LKPK
                                        (Kancing)</option>
                                    <option value="qc" {{ $selectedFilter === 'qc' ? 'selected' : '' }}>🔍 Quality
                                        Control (QC)</option>
                                    <option value="packing" {{ $selectedFilter === 'packing' ? 'selected' : '' }}>📦
                                        Packing / Finishing</option>
                                    <option value="selesai" {{ $selectedFilter === 'selesai' ? 'selected' : '' }}>✅ Selesai
                                        (Finished Good)</option>
                                    <option value="dikirim" {{ $selectedFilter === 'dikirim' ? 'selected' : '' }}>🚀 Telah
                                        Dikirim (Shipped)</option>
                                </select>
                            </div>
                        </div>

                        {{-- TIPE SPK FILTER (COL 12 / COL MD 3) --}}
                        <div class="col-12 col-md-3 col-lg-3">
                            <select name="tipe_spk"
                                class="form-select form-select-sm border-0 bg-light fw-bold text-dark rounded-3"
                                style="height: 38px; cursor: pointer;" onchange="this.form.submit()">
                                <option value="">🏢 Semua Tipe SPK</option>
                                <option value="stok_gudang" {{ request('tipe_spk') === 'stok_gudang' ? 'selected' : '' }}>
                                    🏬 Stok Gudang</option>
                                <option value="pesanan_pelanggan"
                                    {{ request('tipe_spk') === 'pesanan_pelanggan' ? 'selected' : '' }}>🛒 Pesanan
                                    Pelanggan</option>
                            </select>
                        </div>

                        {{-- SEARCH BOX (COL 12 / COL MD 4) --}}
                        <div class="col-12 col-md-4 col-lg-4">
                            <div class="position-relative">
                                <i
                                    class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted opacity-75"></i>
                                <input type="text" name="search"
                                    class="form-control form-control-sm rounded-3 ps-5 pe-3 py-2 bg-light border-0 shadow-none text-dark w-100"
                                    style="height: 38px;" placeholder="Cari SPK / Pemesan / Instansi..."
                                    value="{{ request('search') }}" onchange="this.form.submit()">
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- SPK PRODUCTION GROUP CARDS GRID --}}
        <div class="row g-3.5 mb-4">
            @forelse($spks as $index => $row)
                @php
                    $queueNo = ($spks->currentPage() - 1) * $spks->perPage() + $index + 1;
                    $spkGroup = $row->sub_spks ?? collect([$row]);
                    $spkCount = $spkGroup->count();
                    $totalPcsGroup = $spkGroup->sum(fn($s) => $s->total_pcs);
                    $isUrgentGroup = $spkGroup->contains('is_urgent', true);
                    $isDraftGroup = $spkGroup->contains(
                        fn($s) => str_contains(strtoupper($s->current_stage_name), 'DRAFT') ||
                            str_contains(strtoupper($s->tahap_saat_ini ?? ''), 'DRAFT'),
                    );

                    // CRITICAL REQUIREMENT: Take main image specifically from SPK 1 (first SPK in group)
                    $firstSpk = $spkGroup->first() ?? $row;
                    $mainImageUrl =
                        $firstSpk->image_url ??
                        ($firstSpk->items->pluck('masterProduct.image_url')->filter()->first() ??
                            $spkGroup->pluck('image_url')->filter()->first());

                    $trackingUrl = route('mobile.spk.detail', $row->id);
                    $waText = rawurlencode(
                        'Halo ' .
                            ($row->pemesan ?: 'Pelanggan') .
                            ', berikut link tracking status produksi SPK ' .
                            ($row->no_produksi ?: $row->no_spk) .
                            ': ' .
                            $trackingUrl,
                    );

                    // Deadline calculation indicator
                    $deadlineText = '-';
                    $deadlineClass = 'text-muted';
                    if ($row->deadline) {
                        $daysLeft = (int) now()
                            ->startOfDay()
                            ->diffInDays($row->deadline->startOfDay(), false);
                        if ($daysLeft < 0) {
                            $deadlineText = $row->deadline->format('d M') . ' (Lewat ' . abs($daysLeft) . 'hr)';
                            $deadlineClass = 'text-danger fw-extrabold';
                        } elseif ($daysLeft === 0) {
                            $deadlineText = $row->deadline->format('d M') . ' (Hari ini)';
                            $deadlineClass = 'text-warning fw-extrabold';
                        } else {
                            $deadlineText = $row->deadline->format('d M') . ' (' . $daysLeft . 'hr lagi)';
                            $deadlineClass = 'text-dark fw-bold';
                        }
                    }
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white transition-hover position-relative spk-card"
                        style="border: 1px solid rgba(0,0,0,0.08) !important;">

                        {{-- CARD TOP HEADER BAR --}}
                        <div
                            class="d-flex align-items-center justify-content-between px-3 py-2 bg-light bg-opacity-75 border-bottom border-light-subtle flex-wrap gap-1">
                            <div class="d-flex align-items-center gap-1.5">
                                <span
                                    class="font-monospace fw-extrabold text-dark px-2 py-0.5 rounded-2 bg-white border border-slate-200 shadow-2xs"
                                    style="font-size: 12px; letter-spacing: -0.2px;">
                                    <i class="fas fa-hashtag text-primary me-0.5"
                                        style="font-size: 10px;"></i>{{ $row->no_produksi ?: 'NO-PROD' }}
                                </span>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0.5 fw-bold"
                                    style="font-size: 9.5px;">
                                    ANTRIAN #{{ $queueNo }}
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                @if ($isDraftGroup)
                                    <span
                                        class="badge bg-secondary bg-opacity-15 text-dark border border-secondary border-opacity-25 rounded-pill px-2 py-0.5 fw-bold"
                                        style="font-size: 9.5px;">
                                        📝 DRAFT (Belum Deal)
                                    </span>
                                @endif

                                @if ($spkCount > 1)
                                    <span
                                        class="badge bg-success bg-opacity-15 text-white rounded-pill px-2 py-0.5 fw-bold"
                                        style="font-size: 9.5px;">
                                        📦 {{ $spkCount }} SPK
                                    </span>
                                @endif

                                @if ($isUrgentGroup)
                                    <span id="urgent-badge-{{ $row->id }}"
                                        class="badge bg-danger text-white rounded-pill px-2 py-0.5 fw-bold pulse-urgent"
                                        style="font-size: 9.5px;">
                                        <i class="fas fa-bolt text-warning me-0.5"></i>URGENT
                                    </span>
                                @else
                                    <span id="urgent-badge-{{ $row->id }}"
                                        class="badge bg-danger text-white rounded-pill px-2 py-0.5 fw-bold pulse-urgent d-none"
                                        style="font-size: 9.5px;">
                                        <i class="fas fa-bolt text-warning me-0.5"></i>URGENT
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-3 d-flex flex-column justify-content-between">

                            <div>
                                {{-- MAIN BODY (IMAGE SPK 1 + PEMESAN & VOLUME) --}}
                                <div class="d-flex gap-3 align-items-start mb-2.5">

                                    {{-- SPK 1 Image Thumbnail Container --}}
                                    <div class="flex-shrink-0 position-relative group-image-wrapper">
                                        @if ($mainImageUrl)
                                            <div class="position-relative overflow-hidden rounded-3 border border-slate-200 bg-white shadow-2xs cursor-pointer image-preview-trigger"
                                                data-image="{{ $mainImageUrl }}"
                                                data-title="{{ $row->no_produksi ?: $row->no_spk }} - {{ $row->pemesan }}"
                                                title="Klik untuk memperbesar foto desain SPK 1">
                                                <img src="{{ $mainImageUrl }}" alt="Desain SPK 1"
                                                    class="object-fit-cover transition-scale"
                                                    style="width: 92px; height: 92px;">
                                                <div
                                                    class="image-overlay d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-search-plus text-white fs-5 opacity-90"></i>
                                                </div>
                                                <span
                                                    class="position-absolute top-0 start-0 bg-primary text-white fw-bold px-1.5 py-0.5"
                                                    style="font-size: 8px; letter-spacing: 0.3px; border-bottom-right-radius: 6px;">
                                                    SPK 1
                                                </span>
                                            </div>
                                        @else
                                            <div class="rounded-3 border border-slate-200 bg-white d-flex flex-column align-items-center justify-content-center text-muted shadow-2xs position-relative"
                                                style="width: 92px; height: 92px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                                                <i class="fas fa-tshirt fs-3 opacity-30 mb-1 text-primary"></i>
                                                <span style="font-size: 8px;" class="fw-bold text-uppercase text-muted">No
                                                    Image</span>
                                                <span
                                                    class="position-absolute top-0 start-0 bg-secondary text-white fw-bold px-1.5 py-0.5"
                                                    style="font-size: 8px; border-bottom-right-radius: 6px;">
                                                    SPK 1
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Customer & Order Info --}}
                                    <div class="flex-grow-1 min-w-0">
                                        {{-- Customer Name --}}
                                        <h6 class="fw-extrabold text-dark mb-0.5 text-truncate font-sans d-flex align-items-center gap-1"
                                            style="font-size: 0.98rem; letter-spacing: -0.2px;"
                                            title="{{ $row->pemesan }}">
                                            <i class="fas fa-user-circle text-primary opacity-80"
                                                style="font-size: 13px;"></i>
                                            <span>{{ strtoupper($row->pemesan ?: 'GUEST') }}</span>
                                        </h6>

                                        {{-- Instansi / Store --}}
                                        <div class="text-muted text-truncate mb-1.5" style="font-size: 0.78rem;">
                                            <i class="fas fa-building me-1 opacity-50 text-secondary"
                                                style="font-size: 11px;"></i>{{ $row->instansi ?: '-' }}
                                        </div>

                                        {{-- Tipe SPK Badge --}}
                                        @if (($row->tipe_spk ?? '') === 'stok_gudang' && !str_contains(strtoupper($row->pemesan ?? ''), 'STOK GUDANG'))
                                            <div class="mb-1.5">
                                                <span class="badge rounded-2 px-2 py-0.5 fw-bold"
                                                    style="font-size: 9px; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                                    🏬 Stok Gudang
                                                </span>
                                            </div>
                                        @elseif (($row->tipe_spk ?? '') !== 'stok_gudang')
                                            <div class="mb-1.5">
                                                <span class="badge rounded-2 px-2 py-0.5 fw-bold"
                                                    style="font-size: 9px; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                                    🛒 Pesanan Pelanggan
                                                </span>
                                            </div>
                                        @endif

                                        {{-- Total Volume Pcs Pill --}}
                                        <div class="fw-bold text-dark d-flex align-items-center gap-1 flex-wrap">
                                            <span
                                                class="badge rounded-pill px-2.5 py-1 text-white fw-extrabold d-inline-flex align-items-center gap-1 shadow-2xs"
                                                style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); font-size: 11px;">
                                                <i class="fas fa-tshirt" style="font-size: 9.5px;"></i>
                                                <span>{{ number_format($totalPcsGroup) }} Pcs</span>
                                            </span>
                                            <span class="text-muted fw-semibold"
                                                style="font-size: 10.5px;">({{ $spkCount }} Jenis SPK)</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- SUB-SPK BREAKDOWN CONTAINER --}}
                                <div
                                    class="bg-light bg-opacity-60 rounded-3 p-2 my-2 border border-light-subtle shadow-2xs">
                                    @foreach ($spkGroup as $subSpk)
                                        @php
                                            // Stage color mapping
                                            $stageName = strtoupper($subSpk->current_stage_name);
                                            $stageDisplayName = $stageName;
                                            $badgeBg = '#eff6ff';
                                            $badgeFg = '#2563eb';
                                            $badgeBorder = '#dbeafe';
                                            if (str_contains($stageName, 'DRAFT')) {
                                                $badgeBg = '#f1f5f9';
                                                $badgeFg = '#475569';
                                                $badgeBorder = '#cbd5e1';
                                                $stageDisplayName = '📝 DRAFT (BELUM DEAL)';
                                            } elseif (str_contains($stageName, 'POTONG')) {
                                                $badgeBg = '#e0f2fe';
                                                $badgeFg = '#0369a1';
                                                $badgeBorder = '#bae6fd';
                                            } elseif (str_contains($stageName, 'JAHIT')) {
                                                $badgeBg = '#f3e8ff';
                                                $badgeFg = '#7e22ce';
                                                $badgeBorder = '#e9d5ff';
                                            } elseif (str_contains($stageName, 'LKPK')) {
                                                $badgeBg = '#ecfdf5';
                                                $badgeFg = '#047857';
                                                $badgeBorder = '#a7f3d0';
                                            } elseif (str_contains($stageName, 'QC')) {
                                                $badgeBg = '#f0f9ff';
                                                $badgeFg = '#0284c7';
                                                $badgeBorder = '#b9e6fe';
                                            } elseif (
                                                str_contains($stageName, 'PACKING') ||
                                                str_contains($stageName, 'SELESAI')
                                            ) {
                                                $badgeBg = '#fef3c7';
                                                $badgeFg = '#b45309';
                                                $badgeBorder = '#fde68a';
                                            }
                                        @endphp
                                        <div class="bg-white rounded-3 p-2 mb-1.5 border border-light-subtle shadow-2xs">
                                            {{-- Line 1: Full Kategori badge (NO TRUNCATION) & Stage Badge --}}
                                            <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                                <span class="badge rounded-2 px-2 py-1 fw-bold text-wrap text-start"
                                                    style="font-size: 9.5px; background-color: #fce7f3; color: #be185d; border: 1px solid #fbcfe8; line-height: 1.2;">
                                                    🏷️ {{ $subSpk->kategori ?: 'SPK ' . ($loop->index + 1) }}
                                                </span>
                                                <span
                                                    class="badge rounded-2 px-1.5 py-0.5 fw-extrabold text-uppercase flex-shrink-0"
                                                    style="font-size: 9px; background-color: {{ $badgeBg }}; color: {{ $badgeFg }}; border: 1px solid {{ $badgeBorder }};">
                                                    {{ $stageDisplayName }}
                                                </span>
                                            </div>
                                            {{-- Line 2: Qty & Varian --}}
                                            <div class="d-flex justify-content-between align-items-center"
                                                style="font-size: 10.5px;">
                                                <span class="fw-extrabold text-primary">
                                                    {{ number_format($subSpk->total_pcs) }} Pcs
                                                </span>
                                                <span class="text-muted fw-semibold text-truncate ms-1"
                                                    style="font-size: 10px;" title="{{ $subSpk->variant_summary }}">
                                                    {{ $subSpk->variant_summary }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- DATES ROW (MASUK & TARGET DEADLINE) --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="bg-light bg-opacity-75 rounded-3 p-2 border border-light text-center">
                                            <span class="text-muted d-block small mb-0.5" style="font-size: 10px;">
                                                <i class="far fa-calendar-plus me-1 opacity-70"></i>Tanggal Masuk:
                                            </span>
                                            <span class="fw-bold text-dark" style="font-size: 0.8rem;">
                                                {{ $row->tanggal ? $row->tanggal->format('d M Y') : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-light bg-opacity-75 rounded-3 p-2 border border-light text-center">
                                            <span class="text-muted d-block small mb-0.5" style="font-size: 10px;">
                                                <i class="fas fa-flag-checkered me-1 opacity-70"></i>Target Deadline:
                                            </span>
                                            <span class="{{ $deadlineClass }}" style="font-size: 0.8rem;">
                                                {{ $deadlineText }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ACTION BUTTONS TOOLBAR --}}
                            <div class="d-flex flex-column gap-2 mt-auto">
                                {{-- Row 1: Detail/Edit & Link Track --}}
                                <div class="row g-2">
                                    <div class="col-6">
                                        <a href="{{ route('spks.show', $row) }}"
                                            class="btn btn-sm btn-outline-secondary w-100 rounded-3 fw-bold py-1.5 bg-white text-dark border-opacity-25 d-inline-flex align-items-center justify-content-center gap-1.5 hover-shadow"
                                            style="font-size: 0.8rem;">
                                            <i class="far fa-eye text-primary"></i>
                                            <span>Detail / Edit</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                                            class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1.5 transition-all hover-shadow"
                                            style="font-size: 0.8rem; background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;"
                                            title="Bagikan Tautan Pelacakan ke Pelanggan via WhatsApp">
                                            <i class="fab fa-whatsapp fs-6 text-success"></i>
                                            <span>Link Track</span>
                                        </a>
                                    </div>
                                </div>

                                {{-- Row 2: Cetak SPK & Hapus SPK --}}
                                <div class="row g-2">
                                    <div class="col-6">
                                        <a href="{{ route('spks.print', $row->id) }}" target="_blank"
                                            class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1.5 transition-all text-truncate hover-shadow"
                                            style="font-size: 0.8rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"
                                            title="Cetak Perintah Kerja (A4 Half-Page)">
                                            <i class="fas fa-print text-primary"></i>
                                            <span>Cetak SPK</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <form action="{{ route('spks.destroy', $row) }}" method="POST"
                                            class="m-0 w-100"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh data Produksi {{ $row->no_produksi ?: $row->no_spk }} ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1.5 text-danger transition-all hover-shadow"
                                                style="font-size: 0.8rem; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;"
                                                title="Hapus Produksi Ini">
                                                <i class="fas fa-trash-alt"></i>
                                                <span>Hapus SPK</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 py-5">
                    <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                        <div class="card-body py-5">
                            <div class="mb-3 text-muted opacity-30">
                                <i class="fas fa-clipboard-list fa-4x"></i>
                            </div>
                            <h5 class="fw-extrabold text-dark mb-1">Tidak Ada Data SPK Produksi</h5>
                            <p class="text-muted small mb-4">Belum ada SPK yang sesuai dengan filter atau kata kunci
                                pencarian Anda.</p>
                            @can('spks.create')
                                <a href="{{ route('spks.create') }}"
                                    class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-plus-circle me-1"></i> Buat SPK Baru
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        @if ($spks->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $spks->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL LIGHTBOX PREVIEW DESAIN SPK 1 --}}
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-dark text-white">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="imagePreviewTitle">
                        <i class="fas fa-image text-primary"></i> Preview Desain SPK 1
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 text-center position-relative">
                    <img id="imagePreviewSrc" src="" alt="Desain SPK Full" class="img-fluid rounded-3 shadow"
                        style="max-height: 75vh; object-fit: contain;">
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-between">
                    <span class="text-muted small fs-7"><i class="fas fa-info-circle me-1"></i>Foto desain utama dari SPK
                        1</span>
                    <a id="imageDownloadBtn" href="" download target="_blank"
                        class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                        <i class="fas fa-download me-1"></i> Buka Original
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image Preview Modal Lightbox Trigger
            const imageTriggers = document.querySelectorAll('.image-preview-trigger');
            const modalEl = document.getElementById('imagePreviewModal');
            const modalImg = document.getElementById('imagePreviewSrc');
            const modalTitle = document.getElementById('imagePreviewTitle');
            const downloadBtn = document.getElementById('imageDownloadBtn');

            if (imageTriggers.length > 0 && modalEl) {
                const previewModal = new bootstrap.Modal(modalEl);
                imageTriggers.forEach(trigger => {
                    trigger.addEventListener('click', function() {
                        const imgSrc = this.getAttribute('data-image');
                        const titleText = this.getAttribute('data-title') || 'Preview Desain SPK 1';

                        if (imgSrc) {
                            modalImg.src = imgSrc;
                            modalTitle.innerHTML =
                                `<i class="fas fa-image text-primary me-1"></i> Desain SPK 1: ${titleText}`;
                            downloadBtn.href = imgSrc;
                            previewModal.show();
                        }
                    });
                });
            }

            // AJAX Toggle Urgent Status
            const urgentBtns = document.querySelectorAll('.toggle-urgent-btn');
            urgentBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const spkId = this.getAttribute('data-id');
                    const url = this.getAttribute('data-url');
                    const btnTextEl = document.getElementById('urgent-btn-text-' + spkId);
                    const badgeEl = document.getElementById('urgent-badge-' + spkId);

                    this.disabled = true;

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.disabled = false;
                            if (data.success) {
                                if (data.is_urgent) {
                                    btnTextEl.innerText = 'URGENT';
                                    if (badgeEl) badgeEl.classList.remove('d-none');
                                } else {
                                    btnTextEl.innerText = 'AMBIL URGENT';
                                    if (badgeEl) badgeEl.classList.add('d-none');
                                }
                            }
                        })
                        .catch(err => {
                            this.disabled = false;
                            console.error(err);
                        });
                });
            });
        });
    </script>

    <style>
        .spk-card {
            zoom: 0.90;
        }

        .scrollbar-hidden::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hidden {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .transition-hover {
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .transition-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.1) !important;
        }

        .hover-shadow:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .hover-elevate {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .hover-elevate:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3) !important;
        }

        .fw-extrabold {
            font-weight: 800;
        }

        .group-image-wrapper .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.35);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .group-image-wrapper:hover .image-overlay {
            opacity: 1;
        }

        .transition-scale {
            transition: transform 0.3s ease;
        }

        .group-image-wrapper:hover .transition-scale {
            transform: scale(1.08);
        }

        @keyframes pulse-red {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                transform: scale(1.03);
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .pulse-urgent {
            animation: pulse-red 2s infinite;
        }
    </style>
@endsection
