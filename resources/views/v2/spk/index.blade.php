@extends('v2.layouts.app')

@section('title', 'SPK Produksi V2')

@push('styles')
<style>
    /* ── SPK Produksi V2 Custom Styling ── */
    .spk-kpi-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .spk-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .spk-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .spk-item-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .spk-item-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    }
    
    .pulse-urgent {
        animation: pulse-urgent-animation 1.5s infinite;
    }
    @keyframes pulse-urgent-animation {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
        70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .sub-spk-accordion .accordion-button:not(.collapsed) {
        background-color: #f8fafc;
        color: #0f172a;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">
    <!-- Page Header & Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 rounded-pill px-2.5 py-1 fs-12 fw-bold">
                    <i class="bi bi-tools me-1"></i> MANUFAKTUR & PRODUKSI V2
                </span>
            </div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                Surat Perintah Kerja (SPK) & Marketing
            </h4>
            <p class="text-secondary fs-13 mb-0">Pantau seluruh antrian pesanan SPK, bagikan pelacakan pelanggan, dan atur prioritas Urgent.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('spks.scan_karung') }}" class="btn btn-success text-white fw-bold d-inline-flex align-items-center gap-2 shadow-sm rounded-3 px-3 py-2 fs-13">
                <i class="bi bi-upc-scan"></i> Scan Karung
            </a>
            <a href="{{ route('spks.payments.index') }}" class="btn btn-warning text-dark fw-bold d-inline-flex align-items-center gap-2 shadow-sm rounded-3 px-3 py-2 fs-13">
                <i class="bi bi-wallet2"></i> Pembayaran Produksi
            </a>
            @can('spks.create')
                <a href="{{ route('spks.create') }}" class="btn btn-primary fw-bold d-inline-flex align-items-center gap-2 shadow-sm rounded-3 px-3 py-2 fs-13">
                    <i class="bi bi-plus-lg"></i> Buat SPK Baru
                </a>
            @endcan
        </div>
    </div>

    <!-- Alert Success -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4 bg-success bg-opacity-10 text-success fw-bold" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Antrian Produksi -->
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="spk-kpi-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Total Antrian Produksi</span>
                    <div class="spk-kpi-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-boxes"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline">
                    <h3 class="fw-bold text-dark mb-0 me-2">{{ number_format($stats['total_produksi'] ?? $spks->total()) }}</h3>
                    <span class="text-muted fs-12 fw-medium">Grup SPK</span>
                </div>
            </div>
        </div>

        <!-- Pesanan Urgent -->
        @php $urgentCount = (int)($stats['total_urgent'] ?? 0); @endphp
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="spk-kpi-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Pesanan Urgent</span>
                    <div class="spk-kpi-icon {{ $urgentCount > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-warning bg-opacity-10 text-warning' }}">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <div class="d-flex align-items-baseline">
                        <h3 class="fw-bold {{ $urgentCount > 0 ? 'text-danger' : 'text-dark' }} mb-0 me-2">{{ number_format($urgentCount) }}</h3>
                        <span class="text-muted fs-12 fw-medium">SPK</span>
                    </div>
                    @if($urgentCount > 0)
                        <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 fs-11 fw-bold">Prioritas Tinggi</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Total Volume Pcs -->
        <div class="col-12 col-sm-12 col-xl-4">
            <div class="spk-kpi-card shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Total Volume Pcs</span>
                    <div class="spk-kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-layers-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline">
                    <h3 class="fw-bold text-success mb-0 me-2">{{ number_format($stats['total_pcs'] ?? 0) }}</h3>
                    <span class="text-muted fs-12 fw-medium">Pcs Barisan Produk</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <form action="{{ Route::has('v2.spk.index') ? route('v2.spk.index') : route('spks.index') }}" method="GET" class="m-0">
                @php
                    $currStage = request('stage');
                    $isUrgent = request('urgent') == '1';
                    $selectedFilter = $isUrgent ? 'urgent' : ($currStage ?: '');
                    $hasActiveFilter = !empty($selectedFilter) || request()->filled('tipe_spk') || request()->filled('search');
                @endphp
                <div class="row g-2.5 align-items-center">
                    <!-- Stage & Status Select -->
                    <div class="col-12 col-md-5 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-light-subtle text-muted">
                                <i class="bi bi-funnel text-primary"></i>
                            </span>
                            <select name="stage" class="form-select border-light-subtle bg-white text-dark fw-medium fs-13" onchange="this.form.submit()">
                                <option value="" {{ $selectedFilter === '' ? 'selected' : '' }}>🌐 Semua SPK (Semua Status)</option>
                                <option value="urgent" {{ $selectedFilter === 'urgent' ? 'selected' : '' }}>⚡ Pesanan Urgent</option>
                                <option value="draft" {{ $selectedFilter === 'draft' ? 'selected' : '' }}>📝 DRAFT (Belum Deal / Menunggu DP)</option>
                                <option value="desain" {{ $selectedFilter === 'desain' ? 'selected' : '' }}>🎨 Tahap Desain & Mockup</option>
                                <option value="pesanan_baru" {{ $selectedFilter === 'pesanan_baru' ? 'selected' : '' }}>📋 Pesanan Baru / Perencanaan</option>
                                <option value="sampling" {{ $selectedFilter === 'sampling' ? 'selected' : '' }}>⏳ Antrian & Sampling</option>
                                <option value="potong" {{ $selectedFilter === 'potong' ? 'selected' : '' }}>✂️ Tahap Pemotongan (Potong)</option>
                                <option value="sablon_bordir" {{ $selectedFilter === 'sablon_bordir' ? 'selected' : '' }}>🎨 Sablon / Bordir</option>
                                <option value="jahit" {{ $selectedFilter === 'jahit' ? 'selected' : '' }}>🪡 Tahap Jahit</option>
                                <option value="lkpk" {{ $selectedFilter === 'lkpk' ? 'selected' : '' }}>💿 Tahap LKPK (Kancing)</option>
                                <option value="qc" {{ $selectedFilter === 'qc' ? 'selected' : '' }}>🔍 Quality Control (QC)</option>
                                <option value="packing" {{ $selectedFilter === 'packing' ? 'selected' : '' }}>📦 Packing / Finishing</option>
                                <option value="selesai" {{ $selectedFilter === 'selesai' ? 'selected' : '' }}>✅ Selesai (Finished Good)</option>
                                <option value="dikirim" {{ $selectedFilter === 'dikirim' ? 'selected' : '' }}>🚀 Telah Dikirim (Shipped)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tipe SPK Filter -->
                    <div class="col-12 col-md-3 col-lg-3">
                        <select name="tipe_spk" class="form-select border-light-subtle bg-white text-dark fw-medium fs-13" onchange="this.form.submit()">
                            <option value="">🏢 Semua Tipe SPK</option>
                            <option value="stok_gudang" {{ request('tipe_spk') === 'stok_gudang' ? 'selected' : '' }}>🏬 Stok Gudang</option>
                            <option value="pesanan_pelanggan" {{ request('tipe_spk') === 'pesanan_pelanggan' ? 'selected' : '' }}>🛒 Pesanan Pelanggan</option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="col-12 col-md-{{ $hasActiveFilter ? '3' : '4' }} col-lg-{{ $hasActiveFilter ? '3' : '4' }}">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-light-subtle text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-light-subtle bg-white text-dark fs-13" placeholder="Cari SPK / Pemesan / Instansi..." value="{{ request('search') }}" onchange="this.form.submit()">
                        </div>
                    </div>

                    <!-- Reset Button -->
                    @if($hasActiveFilter)
                        <div class="col-12 col-md-1 col-lg-1 text-md-end">
                            <a href="{{ Route::has('v2.spk.index') ? route('v2.spk.index') : route('spks.index') }}" class="btn btn-outline-secondary btn-sm w-100 py-2 rounded-2 fs-12">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- SPK Production Group Cards Grid -->
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
                    $daysLeft = (int) now()->startOfDay()->diffInDays($row->deadline->startOfDay(), false);
                    if ($daysLeft < 0) {
                        $deadlineText = $row->deadline->format('d M') . ' (Lewat ' . abs($daysLeft) . 'hr)';
                        $deadlineClass = 'text-danger fw-bold';
                    } elseif ($daysLeft === 0) {
                        $deadlineText = $row->deadline->format('d M') . ' (Hari ini)';
                        $deadlineClass = 'text-warning fw-bold';
                    } else {
                        $deadlineText = $row->deadline->format('d M') . ' (' . $daysLeft . 'hr lagi)';
                        $deadlineClass = 'text-dark fw-bold';
                    }
                }
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="spk-item-card h-100 shadow-sm overflow-hidden d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header Bar -->
                        <div class="d-flex align-items-center justify-content-between px-3 py-2.5 bg-light border-bottom">
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="badge bg-dark text-white font-monospace fs-12">
                                    #{{ $row->no_produksi ?: 'NO-PROD' }}
                                </span>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0.5 fw-bold fs-11">
                                    ANTRIAN #{{ $queueNo }}
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                @if ($isDraftGroup)
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5 fw-bold fs-11">
                                        📝 DRAFT
                                    </span>
                                @endif

                                @if ($spkCount > 1)
                                    <span class="badge bg-info text-white rounded-pill px-2 py-0.5 fw-bold fs-11">
                                        📦 {{ $spkCount }} SPK
                                    </span>
                                @endif

                                @if ($isUrgentGroup)
                                    <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 fw-bold pulse-urgent fs-11">
                                        ⚡ URGENT
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-3">
                            <div class="d-flex gap-3 align-items-start mb-3">
                                <img src="{{ $mainImageUrl ?: '/assets/img/products/product1.jpg' }}" alt="Foto SPK" class="rounded-3 border" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='/assets/img/products/product1.jpg'">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h6 class="fw-bold text-dark mb-0 fs-14 text-truncate" title="{{ $row->pemesan ?: 'Pelanggan Umum' }}">
                                        {{ $row->pemesan ?: 'Pelanggan Umum' }}
                                    </h6>
                                    <span class="text-secondary fs-12 d-block text-truncate mb-1" title="{{ $row->instansi ?: 'Instansi / Umum' }}">
                                        <i class="bi bi-building me-1"></i>{{ $row->instansi ?: 'Instansi Umum' }}
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 fs-11 fw-bold">
                                            Vol: {{ number_format($totalPcsGroup) }} Pcs
                                        </span>
                                        <span class="badge bg-light text-dark border fs-11">
                                            {{ $row->tipe_spk === 'stok_gudang' ? 'Stok Gudang' : 'Pesanan Pelanggan' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- List Sub-SPKs Accordion / Items Summary -->
                            <div class="bg-light p-2.5 rounded-3 mb-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted fs-11 font-monospace text-uppercase">Tahap Produksi saat ini:</span>
                                    <span class="badge bg-primary text-white fs-11 fw-bold">
                                        {{ strtoupper($row->tahap_saat_ini ?: 'PERENCANAAN') }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center fs-12 pt-1 border-top border-secondary border-opacity-10">
                                    <span class="text-muted fs-11">Tgl Masuk: <strong>{{ $row->tanggal ? $row->tanggal->format('d/m/Y') : '-' }}</strong></span>
                                    <span class="fs-11">Deadline: <strong class="{{ $deadlineClass }}">{{ $deadlineText }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="card-footer bg-white border-top p-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-1">
                            <a href="https://wa.me/?text={{ $waText }}" target="_blank" class="btn btn-xs btn-outline-success rounded-2 py-1 px-2 fs-12" title="Kirim WA Tracking">
                                <i class="bi bi-whatsapp"></i> WA
                            </a>
                            <a href="{{ route('spks.show', $row->id) }}" class="btn btn-xs btn-outline-primary rounded-2 py-1 px-2 fs-12 fw-semibold">
                                <i class="bi bi-eye"></i> Detail SPK
                            </a>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <a href="{{ route('spks.print', $row->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary rounded-2 py-1 px-2 fs-12" title="Cetak SPK">
                                <i class="bi bi-printer"></i> Cetak
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 p-5 text-center bg-white">
                    <i class="bi bi-inbox text-muted display-4 mb-3"></i>
                    <h6 class="fw-bold text-dark mb-1">Tidak Ada Data SPK Produksi</h6>
                    <p class="text-muted fs-13 mb-0">Belum ada dokumen SPK yang dibuat atau tidak ditemukan pencarian yang cocok.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination Links -->
    <div class="d-flex justify-content-end">
        {{ $spks->links() }}
    </div>
</div>
@endsection
