@extends('v2.layouts.app')

@section('title', 'Marketing & Pengiriman (SPK Produksi)')

@section('content')
    <div class="container-fluid px-3 py-3">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 bg-success bg-opacity-10 text-success fw-bold"
                role="alert">
                <i class="fas fa-check-circle me-2 fs-5 align-middle"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- COMPACT TOP BAR & ACTIONS (BTN-SM) --}}
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <h5 class="fw-bold text-dark mb-0">SPK Produksi</h5>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ Route::has('v2.spk.scan_karung') ? route('v2.spk.scan_karung') : route('spks.scan_karung') }}"
                    class="btn btn-sm btn-success text-white px-2.5 py-1.5 rounded-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1.5" style="background: linear-gradient(135deg, #059669, #10b981);">
                    <i class="fas fa-barcode"></i>
                    <span>Scan Karung</span>
                </a>
                <a href="{{ Route::has('v2.spk.payments') ? route('v2.spk.payments') : route('spks.payments.index') }}"
                    class="btn btn-sm btn-warning text-dark px-2.5 py-1.5 rounded-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1.5">
                    <i class="fas fa-wallet text-dark"></i>
                    <span>Pembayaran Produksi</span>
                </a>
                @can('spks.create')
                    <a href="{{ Route::has('v2.spk.create') ? route('v2.spk.create') : route('spks.create') }}"
                        class="btn btn-sm btn-primary px-2.5 py-1.5 rounded-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1.5">
                        <i class="fas fa-plus"></i>
                        <span>Buat SPK Baru</span>
                    </a>
                @endcan
            </div>
        </div>

        {{-- SPK PRODUCTION STATUS TABS & FILTER CARD (PESANAN STYLE) --}}
        {{-- SPK PRODUCTION STATUS & FILTER CARD --}}
        <div class="card border border-light-subtle shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
            @php
                $currentStage = request('urgent') == '1' ? 'urgent' : request('stage', '');
                $currTipe = request('tipe_spk', '');
                $currDeadline = request('deadline_filter', '');
                $stageOptions = [
                    ''              => ['label' => 'Semua Status SPK', 'countKey' => '__all__'],
                    'urgent'        => ['label' => '⚡ Urgent', 'countKey' => 'urgent'],
                    'in_progress'   => ['label' => '⚙️ Sedang Diproses', 'countKey' => 'in_progress'],
                    'potong'        => ['label' => '✂️ Potong', 'countKey' => 'potong'],
                    'sablon_bordir' => ['label' => '🎨 Sablon/Bordir', 'countKey' => 'sablon_bordir'],
                    'jahit'         => ['label' => '🪡 Jahit', 'countKey' => 'jahit'],
                    'lkpk'          => ['label' => '💿 LKPK (Kancing)', 'countKey' => 'lkpk'],
                    'qc'            => ['label' => '🔍 QC', 'countKey' => 'qc'],
                    'packing'       => ['label' => '📦 Packing', 'countKey' => 'packing'],
                    'desain'        => ['label' => '🖌️ Desain & Mockup', 'countKey' => 'desain'],
                    'pesanan_baru'  => ['label' => '📋 Pesanan Baru / Rencana', 'countKey' => 'pesanan_baru'],
                    'sampling'      => ['label' => '⏳ Sampling', 'countKey' => 'sampling'],
                    'draft'         => ['label' => '📝 Draft (Belum Deal)', 'countKey' => 'draft'],
                    'selesai'       => ['label' => '✅ Selesai (Finished)', 'countKey' => 'selesai'],
                    'dikirim'       => ['label' => '🚚 Dikirim (Shipped)', 'countKey' => 'dikirim'],
                    'batal'         => ['label' => '❌ Dibatalkan (Batal)', 'countKey' => 'batal'],
                ];
                $hasActiveExtra = request()->filled('search') || request()->filled('date_from') || request()->filled('date_to') || !empty($currentStage) || request()->filled('tipe_spk') || request()->filled('deadline_filter');
            @endphp

            {{-- Filter Form --}}
            <div class="spk-filter-bar p-3">
                <form action="{{ route('v2.spk.index') }}" method="GET" class="m-0" id="spk-filter-form">
                    {{-- Row 1: Status Tahap, Tipe SPK, Deadline --}}
                    <div class="row g-2 align-items-end mb-2">
                        {{-- Select Stage (Dropdown pengganti tab) --}}
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-funnel text-primary me-1"></i>Tahap / Status SPK</label>
                            <select name="stage_select" id="stage_select" class="form-select form-control" style="cursor: pointer;"
                                onchange="handleStageSelectChange(this.value)">
                                @foreach($stageOptions as $stKey => $stInfo)
                                    @php
                                        $cnt = $tabCounts[$stInfo['countKey']] ?? 0;
                                        $isSelected = $currentStage === $stKey;
                                    @endphp
                                    <option value="{{ $stKey }}" {{ $isSelected ? 'selected' : '' }}>
                                        {{ $stInfo['label'] }} ({{ $cnt }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="stage" id="filter_stage_hidden" value="{{ $currentStage !== 'urgent' ? $currentStage : '' }}">
                            <input type="hidden" name="urgent" id="filter_urgent_hidden" value="{{ $currentStage === 'urgent' ? '1' : '' }}">
                        </div>

                        {{-- Select Tipe SPK --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-box-seam me-1"></i>Tipe SPK</label>
                            <select name="tipe_spk" class="form-select form-control" style="cursor: pointer;" onchange="this.form.submit()">
                                <option value="" {{ empty($currTipe) ? 'selected' : '' }}>Semua Tipe</option>
                                <option value="pesanan_pelanggan" {{ $currTipe === 'pesanan_pelanggan' ? 'selected' : '' }}>🛒 Pesanan Pelanggan</option>
                                <option value="stok_gudang" {{ $currTipe === 'stok_gudang' ? 'selected' : '' }}>🏬 Stok Gudang</option>
                            </select>
                        </div>

                        {{-- Select Deadline --}}
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-bold text-dark"><i class="bi bi-clock-history me-1"></i>Filter Deadline</label>
                            <select name="deadline_filter" class="form-select form-control" style="cursor: pointer;" onchange="this.form.submit()">
                                <option value="" {{ empty($currDeadline) ? 'selected' : '' }}>Semua Deadline</option>
                                <option value="overdue" {{ $currDeadline === 'overdue' ? 'selected' : '' }}>🔥 Lewat Deadline</option>
                                <option value="near" {{ $currDeadline === 'near' ? 'selected' : '' }}>⚡ Mendekati (≤ 3 Hari)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Pencarian Cepat, Tanggal SPK, Deadline Hingga, Tombol Filter --}}
                    <div class="row g-2 align-items-end">
                        {{-- Search Input (col-4) --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label"><i class="bi bi-search me-1"></i>Pencarian Cepat</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari No. SPK, No. Produksi, Pemesan, Produk..."
                                value="{{ request('search') }}">
                        </div>

                        {{-- Date From (col-3) --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label"><i class="bi bi-calendar3 me-1"></i>Dari Tgl SPK</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        {{-- Date To (col-3) --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Deadline Hingga</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>

                        {{-- Action Buttons (col-2) --}}
                        <div class="col-12 col-md-2 d-flex gap-1.5">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1 rounded-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-sm" style="height: 31px;">
                                <i class="bi bi-funnel"></i>
                                <span>Filter</span>
                            </button>
                            @if($hasActiveExtra)
                                <a href="{{ route('v2.spk.index') }}" class="btn btn-sm btn-outline-secondary px-2.5 rounded-2 d-inline-flex align-items-center justify-content-center gap-1" style="height: 31px;" title="Reset Semua Filter">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Summary bar --}}
            <div class="spk-summary-bar">
                <div>
                    <span>Menampilkan <strong class="text-dark">{{ $spks->total() }}</strong> kelompok produksi</span>
                    @if(!empty($currentStage))
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">
                            Filter Tahap: {{ $stageOptions[$currentStage]['label'] ?? ucfirst($currentStage) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function handleStageSelectChange(val) {
                const stageInp = document.getElementById('filter_stage_hidden');
                const urgentInp = document.getElementById('filter_urgent_hidden');
                if (val === 'urgent') {
                    stageInp.value = '';
                    urgentInp.value = '1';
                } else {
                    stageInp.value = val;
                    urgentInp.value = '';
                }
                document.getElementById('spk-filter-form').submit();
            }
        </script>

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

                    $firstSpk = $spkGroup->first() ?? $row;
                    $mainImageUrl =
                        $firstSpk->image_url ??
                        ($firstSpk->items->pluck('masterProduct.image_url')->filter()->first() ??
                            $spkGroup->pluck('image_url')->filter()->first());

                    $trackingUrl = route('spks.customer_track', $row->no_produksi ?: $row->id);
                    $waText = rawurlencode(
                        'Halo ' .
                            ($row->pemesan ?: 'Pelanggan') .
                            ', berikut link tracking status produksi SPK ' .
                            ($row->no_produksi ?: $row->no_spk) .
                            ': ' .
                            $trackingUrl,
                    );

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

                                        @php
                                            $groupTargetPcs = (int) $spkGroup->sum(fn($s) => $s->items->sum('quantity'));
                                            $groupDiambilPcs = (int) $spkGroup->sum(fn($s) => $s->items->sum(fn($it) => $it->qty_diambil));
                                            $groupSisaPcs = max(0, $groupTargetPcs - $groupDiambilPcs);
                                        @endphp
                                        {{-- Total Volume Pcs Pill & Receiving Summary Badges --}}
                                        <div class="fw-bold text-dark d-flex align-items-center gap-1 flex-wrap mt-1">
                                            <span
                                                class="badge rounded-pill px-2.5 py-1 text-white fw-extrabold d-inline-flex align-items-center gap-1 shadow-2xs"
                                                style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); font-size: 11px;">
                                                <i class="fas fa-tshirt" style="font-size: 9.5px;"></i>
                                                <span>{{ number_format($totalPcsGroup) }} Pcs</span>
                                            </span>
                                            <span class="badge rounded-pill px-2 py-0.5 fw-bold"
                                                style="font-size: 9.5px; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;" title="Total Sudah Diterima">
                                                <i class="fas fa-check-circle me-0.5"></i>Terima: {{ number_format($groupDiambilPcs) }}
                                            </span>
                                            @if($groupSisaPcs > 0)
                                                <span class="badge rounded-pill px-2 py-0.5 fw-bold"
                                                    style="font-size: 9.5px; background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;" title="Sisa Belum Diterima">
                                                    <i class="fas fa-clock me-0.5"></i>Belum: {{ number_format($groupSisaPcs) }}
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-success text-white px-2 py-0.5 fw-bold" style="font-size: 9.5px;">
                                                    <i class="fas fa-check-double me-0.5"></i>Lengkap
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- SUB-SPK BREAKDOWN CONTAINER --}}
                                <div
                                    class="bg-light bg-opacity-60 rounded-3 p-2 my-2 border border-light-subtle shadow-2xs">
                                    @foreach ($spkGroup as $subSpk)
                                        @php
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

                                            $subTargetPcs = (int) $subSpk->items->sum('quantity');
                                            $subDiambilPcs = (int) $subSpk->items->sum(fn($it) => $it->qty_diambil);
                                            $subSisaPcs = max(0, $subTargetPcs - $subDiambilPcs);
                                        @endphp
                                        <div class="bg-white rounded-3 p-2 mb-1.5 border border-light-subtle shadow-2xs">
                                            {{-- Line 1: Full Kategori badge & Stage Badge --}}
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
                                            {{-- Line 2: Qty Target, Diterima & Belum Diterima --}}
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mt-1 pt-1 border-top border-light-subtle"
                                                style="font-size: 10.5px;">
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    <span class="fw-extrabold text-primary" title="Target SPK Ini">
                                                        {{ number_format($subTargetPcs) }} Pcs
                                                    </span>
                                                    <span class="badge rounded-2 px-1.5 py-0.5 fw-bold"
                                                        style="font-size: 9px; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;" title="Sudah Diterima">
                                                        ✓ Terima: {{ number_format($subDiambilPcs) }}
                                                    </span>
                                                    @if($subSisaPcs > 0)
                                                        <span class="badge rounded-2 px-1.5 py-0.5 fw-bold"
                                                            style="font-size: 9px; background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;" title="Sisa Belum Diterima">
                                                            ⏳ Belum: {{ number_format($subSisaPcs) }}
                                                        </span>
                                                    @else
                                                        <span class="badge rounded-2 bg-success text-white px-1.5 py-0.5 fw-bold" style="font-size: 9px;">
                                                            ✓ Lengkap
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="text-muted fw-semibold text-truncate ms-auto"
                                                    style="font-size: 9.5px;" title="{{ $subSpk->variant_summary }}">
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

                            {{-- PAYMENT STATUS BAR --}}
                            <div class="d-flex justify-content-between align-items-center bg-light bg-opacity-75 px-2.5 py-1.5 rounded-3 border border-light-subtle mb-2">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="fas fa-wallet text-warning" style="font-size: 11px;"></i>
                                    <span class="text-muted fw-bold" style="font-size: 10.5px;">Ongkos Produksi:</span>
                                </div>
                                <div>
                                    {!! $row->production_payment_status_badge !!}
                                </div>
                            </div>

                            {{-- ACTION BUTTONS TOOLBAR --}}
                            <div class="d-flex flex-column gap-2 mt-auto">
                                {{-- Row 1: Detail/Edit, Lihat Track Customer & Kirim WA --}}
                                <div class="row g-1">
                                    <div class="col-4">
                                        <a href="{{ Route::has('v2.spk.show') ? route('v2.spk.show', $row) : route('spks.show', $row) }}"
                                            class="btn btn-sm btn-outline-secondary w-100 rounded-3 fw-bold py-1.5 bg-white text-dark border-opacity-25 d-inline-flex align-items-center justify-content-center gap-1 hover-shadow"
                                            style="font-size: 0.75rem;" title="Edit Detail SPK">
                                            <i class="far fa-eye text-primary"></i>
                                            <span>Detail</span>
                                        </a>
                                    </div>
                                    <div class="col-4">
                                        <a href="{{ route('spks.customer_track', $row->no_produksi ?: $row->id) }}" target="_blank"
                                            class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1 transition-all hover-shadow"
                                            style="font-size: 0.75rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"
                                            title="Buka Halaman Tracking Customer di Tab Baru">
                                            <i class="fas fa-external-link-alt text-primary"></i>
                                            <span>Tracking</span>
                                        </a>
                                    </div>
                                    <div class="col-4">
                                        <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                                            class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1 transition-all hover-shadow"
                                            style="font-size: 0.75rem; background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;"
                                            title="Bagikan Tautan Pelacakan ke Pelanggan via WhatsApp">
                                            <i class="fab fa-whatsapp fs-6 text-success"></i>
                                            <span>Kirim WA</span>
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
                                        @if($row->is_batal || strtolower($row->tahap_saat_ini ?? '') === 'batal')
                                            <button type="button"
                                                class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1.5 transition-all"
                                                style="font-size: 0.8rem; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; opacity: 0.85;"
                                                data-bs-toggle="modal" data-bs-target="#modalBatalInfo{{ $row->id }}"
                                                title="Lihat Alasan Pembatalan">
                                                <i class="fas fa-ban"></i>
                                                <span>Dibatalkan</span>
                                            </button>
                                        @else
                                            <button type="button"
                                                class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1.5 text-danger transition-all hover-shadow"
                                                style="font-size: 0.8rem; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;"
                                                data-bs-toggle="modal" data-bs-target="#modalBatalSpk{{ $row->id }}"
                                                title="Batalkan SPK Ini">
                                                <i class="fas fa-ban"></i>
                                                <span>Batal SPK</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Modal Batalkan SPK & Info Batal --}}
                    @if(!$row->is_batal && strtolower($row->tahap_saat_ini ?? '') !== 'batal')
                        <div class="modal fade text-start" id="modalBatalSpk{{ $row->id }}" tabindex="-1" aria-labelledby="modalBatalSpkLabel{{ $row->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <form action="{{ Route::has('v2.spk.batal') ? route('v2.spk.batal', $row) : route('spks.batal', $row) }}" method="POST">
                                        @csrf
                                        <div class="modal-header border-0 pb-0">
                                            <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="modalBatalSpkLabel{{ $row->id }}">
                                                <i class="fas fa-exclamation-triangle"></i> Batalkan SPK {{ $row->no_produksi ?: $row->no_spk }}
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body py-3">
                                            <div class="alert alert-warning border-0 rounded-3 small mb-3">
                                                <i class="fas fa-info-circle me-1"></i> Data SPK <strong>tidak dihapus permanen</strong>, status akan dialihkan ke <strong>Dibatalkan</strong> dan alokasi stok bahan dikembalikan ke gudang.
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-dark">Alasan Pembatalan <span class="text-danger">*</span></label>
                                                <textarea name="alasan_batal" class="form-control rounded-3" rows="3" placeholder="Tuliskan alasan pembatalan SPK ini (contoh: Klien membatalkan pesanan, salah input ukuran/produk, dll)..." required minlength="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light rounded-3 fw-semibold px-3" data-bs-dismiss="modal">Kembali</button>
                                            <button type="submit" class="btn btn-danger rounded-3 fw-bold px-3">
                                                <i class="fas fa-ban me-1"></i> Konfirmasi Batal SPK
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="modal fade text-start" id="modalBatalInfo{{ $row->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                            <i class="fas fa-ban"></i> Informasi Pembatalan SPK
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <div class="p-3 bg-light rounded-3">
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Waktu Pembatalan:</small>
                                                <strong>{{ $row->batal_at ? $row->batal_at->format('d M Y H:i') : '-' }}</strong>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Dibatalkan Oleh:</small>
                                                <strong>{{ $row->dibatalkanOleh->name ?? 'User' }}</strong>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Alasan Pembatalan:</small>
                                                <div class="text-danger fw-semibold mt-1">{{ $row->alasan_batal ?: 'Tidak ada alasan tercatat.' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-secondary rounded-3 px-3" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-12 py-5">
                    <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                        <div class="card-body py-5">
                            <div class="mb-3 text-muted opacity-30">
                                <i class="fas fa-clipboard-list fa-4x"></i>
                            </div>
                            <h5 class="fw-extrabold text-dark mb-1">Tidak Ada Data SPK Produksi</h5>
                            <p class="text-muted small mb-4">Belum ada SPK yang sesuai dengan filter atau kata kunci pencarian Anda.</p>
                            @can('spks.create')
                                <a href="{{ Route::has('v2.spk.create') ? route('v2.spk.create') : route('spks.create') }}"
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
                    <span class="text-muted small fs-7"><i class="fas fa-info-circle me-1"></i>Foto desain utama dari SPK 1</span>
                    <a id="imageDownloadBtn" href="" download target="_blank"
                        class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                        <i class="fas fa-download me-1"></i> Buka Original
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
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

            // Horizontal scroll with mouse wheel for SPK Tab Bar & Sub Bar
            document.querySelectorAll('.spk-tab-bar, .spk-sub-bar').forEach(function(el) {
                el.addEventListener('wheel', function(e) {
                    if (e.deltaY !== 0 && el.scrollWidth > el.clientWidth) {
                        e.preventDefault();
                        el.scrollLeft += e.deltaY;
                    }
                }, { passive: false });
            });

            // Auto-scroll active tab into view
            const activeTab = document.querySelector('.spk-tab.active');
            if (activeTab) {
                activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });
    </script>
@endpush

@push('styles')
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

        /* ─── SPK Tab Bar (Pesanan Style) ─── */
        .spk-tab-bar {
            display: flex;
            overflow-x: auto;
            flex-wrap: nowrap;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            gap: 0;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
            -webkit-overflow-scrolling: touch;
        }
        .spk-tab-bar::-webkit-scrollbar {
            height: 5px;
        }
        .spk-tab-bar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        .spk-tab-bar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        .spk-tab-bar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .spk-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 11px 16px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
            white-space: nowrap;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: color .15s, border-color .15s, background .15s;
            position: relative;
            flex-shrink: 0;
        }
        .spk-tab:hover { color: #2563eb; text-decoration: none; background: #f8fafc; }
        .spk-tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            font-weight: 700;
            background: #eff6ff;
        }
        .spk-tab .tab-badge {
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 1px 7px;
            background: #e2e8f0;
            color: #475569;
            min-width: 18px;
            text-align: center;
            line-height: 1.5;
        }
        .spk-tab.active .tab-badge {
            background: #2563eb;
            color: #ffffff;
        }
        .spk-tab .tab-badge.badge-urgent {
            background: #ef4444;
            color: #ffffff;
        }

        /* sub-bar */
        .spk-sub-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            overflow-x: auto;
            flex-wrap: nowrap;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
            -webkit-overflow-scrolling: touch;
        }
        .spk-sub-bar::-webkit-scrollbar {
            height: 4px;
        }
        .spk-sub-bar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        .spk-sub-bar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        .spk-sub-bar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .spk-sub-label { font-size: 0.74rem; font-weight: 600; color: #64748b; white-space: nowrap; flex-shrink: 0; }
        .spk-sub-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .spk-sub-pill:hover { border-color: #2563eb; color: #2563eb; text-decoration: none; }
        .spk-sub-pill.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(37,99,235,.25);
        }

        /* filter bar */
        .spk-filter-bar {
            padding: 10px 14px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }
        .spk-filter-bar .form-label { font-size: 0.72rem; font-weight: 600; color: #64748b; margin-bottom: 3px; }
        .spk-filter-bar .form-control {
            font-size: 0.79rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            padding: 5px 9px;
            height: 31px;
        }
        .spk-filter-bar .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37,99,235,.15);
        }

        /* summary bar */
        .spk-summary-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: #f8fafc;
            font-size: 0.79rem;
            color: #64748b;
        }
    </style>
@endpush
