@extends('v2.layouts.app')

@section('title', 'Data Barang & Histori Opname V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-boxes text-primary fs-5"></i> Data Barang & Opname
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola master data barang operasional, bahan baku, kemasan, serta histori stock opname</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ url('/v2/barang?tab=' . $activeTab) }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Page">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <button type="button" class="btn btn-sm btn-outline-warning py-1.5 px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#importOpnameModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Stok Opname
        </button>
        <button type="button" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createBarangModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Barang
        </button>
    </div>
</div>

<!-- Alerts -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> Terjadi kesalahan input. Periksa kembali form anda.
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-blue">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total_items']) }}</span>
                <span class="v2-stat-lbl">Total Item Barang</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-green">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-scissors"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total_bahan']) }}</span>
                <span class="v2-stat-lbl">Item Bahan Baku</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-purple">
            <div class="v2-stat-icon-wrapper purple">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total_kemasan']) }}</span>
                <span class="v2-stat-lbl">Item Kemasan</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-amber">
            <div class="v2-stat-icon-wrapper amber">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['low_stock']) }}</span>
                <span class="v2-stat-lbl">Stok Menipis / Habis</span>
            </div>
        </div>
    </div>
</div>

<!-- Nav Tabs Segmented Bar -->
<div class="d-flex align-items-center mb-3">
    <div class="p-1 rounded-3 shadow-sm border d-inline-flex gap-1" style="background-color: #f1f5f9;">
        <a href="{{ url('/v2/barang?tab=items') }}" 
           class="btn btn-sm px-3.5 py-2 fw-bold rounded-2 d-flex align-items-center gap-2 {{ $activeTab === 'items' ? 'bg-primary text-white shadow-sm' : 'text-secondary bg-transparent' }}"
           style="font-size: 0.84rem; text-decoration: none; transition: all 0.2s ease-in-out;">
            <i class="bi bi-boxes fs-6"></i>
            <span>Data Barang</span>
            <span class="badge {{ $activeTab === 'items' ? 'bg-white text-primary' : 'bg-secondary text-white' }} rounded-pill px-2" style="font-size: 0.72rem;">
                {{ number_format($counts['total_items']) }}
            </span>
        </a>
        
        <a href="{{ url('/v2/barang?tab=opname') }}" 
           class="btn btn-sm px-3.5 py-2 fw-bold rounded-2 d-flex align-items-center gap-2 {{ $activeTab === 'opname' ? 'bg-warning text-dark shadow-sm' : 'text-secondary bg-transparent' }}"
           style="font-size: 0.84rem; text-decoration: none; transition: all 0.2s ease-in-out;">
            <i class="bi bi-clock-history fs-6"></i>
            <span>Histori Opname</span>
            <span class="badge {{ $activeTab === 'opname' ? 'bg-dark text-warning' : 'bg-secondary text-white' }} rounded-pill px-2" style="font-size: 0.72rem;">
                Opname Log
            </span>
        </a>
    </div>
</div>

@if($activeTab === 'opname')
    {{-- ── TAB 2: HISTORI OPNAME & PENYESUAIAN STOK ── --}}
    <!-- Filter Card Opname -->
    <div class="v2-card mb-3 shadow-sm">
        <div class="v2-card-body p-2.5">
            <form method="GET" action="{{ url('/v2/barang') }}" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="opname">
                <div class="col-12 col-sm-6 col-md-6">
                    <input type="text" name="opname_search" class="form-control form-control-sm" placeholder="Cari SKU, nama barang, petugas, referensi..." value="{{ request('opname_search') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <input type="date" name="opname_date" class="form-control form-control-sm" value="{{ request('opname_date') }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1.5">
                    <button type="submit" class="btn btn-sm text-white fw-semibold w-100 justify-content-center py-1" style="background:#1e293b; border:none;">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    @if(request()->anyFilled(['opname_search', 'opname_date']))
                        <a href="{{ url('/v2/barang?tab=opname') }}" class="btn btn-sm btn-v2-secondary py-1 px-2" title="Reset Filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Opname Card (Grouped by Date & User) -->
    <div class="v2-card shadow-sm">
        <div class="v2-card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between">
            <h6 class="v2-card-title d-flex align-items-center gap-2 m-0" style="font-size:0.85rem;">
                <i class="bi bi-journal-check text-warning fs-6"></i> Histori Opname & Penyesuaian Stok
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-0.5 rounded-pill" style="font-size: 0.68rem;">
                    {{ $opnames->total() }} Sesi Audit
                </span>
            </h6>
        </div>
        <div class="v2-card-body p-0">
            <div class="v2-table-responsive">
                <table class="v2-table align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="width: 170px;">TANGGAL & WAKTU</th>
                            <th>DIINPUT OLEH</th>
                            <th>PETUGAS / REFERENSI AUDIT</th>
                            <th class="text-center" style="width: 130px;">JUMLAH SKU</th>
                            <th class="text-end" style="width: 150px;">TOTAL SELISIH QTY</th>
                            <th class="text-center" style="width: 120px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opnames as $batchIdx => $batch)
                            @php
                                $first = $batch->first();
                                $dateFormatted = $first->created_at ? $first->created_at->format('d/m/Y H:i') : '-';
                                $diffHuman = $first->created_at ? $first->created_at->diffForHumans() : '-';
                                $userName = $first->user ? $first->user->name : 'Sistem / PIC';
                                $ref = $first->reference ?? 'Penyesuaian Stok';
                                $totalSku = $batch->count();
                                $totalDiff = $batch->sum('quantity');
                                $batchModalId = 'batchOpnameModal_' . $loop->index;
                            @endphp
                            <tr>
                                <td class="text-center text-muted fw-semibold" style="font-size: 0.75rem;">
                                    {{ $opnames->firstItem() + $batchIdx }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.78rem;">{{ $dateFormatted }}</div>
                                    <div class="text-muted small" style="font-size:0.68rem;">{{ $diffHuman }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2.5 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-person-fill text-primary me-1"></i>{{ $userName }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark" style="font-size: 0.78rem;">{{ $ref }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                                        <i class="bi bi-box-seam me-1"></i>{{ $totalSku }} Item SKU
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($totalDiff > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold" style="font-size: 0.78rem;">
                                            +{{ number_format($totalDiff, 0, ',', '.') }} Pcs
                                        </span>
                                    @elseif($totalDiff < 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold" style="font-size: 0.78rem;">
                                            {{ number_format($totalDiff, 0, ',', '.') }} Pcs
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1" style="font-size: 0.78rem;">0 Pcs</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-semibold shadow-sm" 
                                            style="font-size: 0.73rem;"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#{{ $batchModalId }}">
                                        <i class="bi bi-eye me-1"></i> Show / Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <span style="font-size: 0.82rem;">Belum ada riwayat stock opname / penyesuaian stok.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($opnames->hasPages())
            <div class="v2-card-footer bg-light py-2 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted" style="font-size: 0.72rem;">
                        Menampilkan Sesi Opname {{ $opnames->firstItem() }} - {{ $opnames->lastItem() }} dari {{ $opnames->total() }} Sesi
                    </div>
                    <div>
                        {{ $opnames->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modals Detail Group Sesi Opname -->
    @foreach($opnames as $batchIdx => $batch)
        @php
            $first = $batch->first();
            $dateFormatted = $first->created_at ? $first->created_at->format('d F Y, H:i:s') : '-';
            $userName = $first->user ? $first->user->name : 'Sistem / PIC';
            $ref = $first->reference ?? 'Penyesuaian Stok';
            $totalSku = $batch->count();
            $totalDiff = $batch->sum('quantity');
            $batchModalId = 'batchOpnameModal_' . $loop->index;
        @endphp
        <div class="modal fade" id="{{ $batchModalId }}" tabindex="-1" aria-labelledby="{{ $batchModalId }}Label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
                    <div class="modal-header bg-warning text-dark py-3">
                        <h5 class="modal-title fw-bold fs-6 mb-0" id="{{ $batchModalId }}Label">
                            <i class="bi bi-clock-history me-2"></i>Detail Opname & Penyesuaian Stok
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Summary Header Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <small class="text-secondary d-block fw-semibold" style="font-size:0.72rem;">TANGGAL & WAKTU</small>
                                    <span class="fw-bold text-dark" style="font-size:0.85rem;">{{ $dateFormatted }}</span>
                                </div>
                                <div class="col-12 col-md-4">
                                    <small class="text-secondary d-block fw-semibold" style="font-size:0.72rem;">DIINPUT OLEH (USER)</small>
                                    <span class="fw-bold text-primary" style="font-size:0.85rem;"><i class="bi bi-person-circle me-1"></i>{{ $userName }}</span>
                                </div>
                                <div class="col-12 col-md-4">
                                    <small class="text-secondary d-block fw-semibold" style="font-size:0.72rem;">TOTAL ITEM & SELISIH</small>
                                    <span class="fw-bold text-dark" style="font-size:0.85rem;">{{ $totalSku }} Item SKU</span>
                                    (<span class="{{ $totalDiff >= 0 ? 'text-success' : 'text-danger' }} fw-bold">{{ $totalDiff > 0 ? '+' : '' }}{{ number_format($totalDiff, 0, ',', '.') }} Pcs</span>)
                                </div>
                                <div class="col-12 border-top pt-2 mt-2">
                                    <small class="text-secondary d-block fw-semibold" style="font-size:0.72rem;">REFERENSI / CATATAN AUDIT</small>
                                    <span class="fw-semibold text-dark" style="font-size:0.82rem;">{{ $ref }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Table List SKU dalam Sesi ini -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark mb-0 fs-7">
                                <i class="bi bi-boxes me-1 text-primary"></i> Daftar SKU Barang dalam Sesi Audit Ini
                            </h6>
                            <span class="badge bg-secondary rounded-pill">{{ $totalSku }} SKU</span>
                        </div>
                        <div class="table-responsive rounded-2 border">
                            <table class="table table-hover table-striped align-middle mb-0" style="font-size:0.8rem;">
                                <thead class="bg-light text-secondary">
                                    <tr>
                                        <th class="text-center" style="width:40px;">#</th>
                                        <th style="width:140px;">SKU</th>
                                        <th>NAMA BARANG</th>
                                        <th class="text-center" style="width:90px;">TIPE</th>
                                        <th class="text-end" style="width:110px;">SELISIH QTY</th>
                                        <th class="text-end" style="width:130px;">STOK SETELAH</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batch as $mIdx => $m)
                                        @php
                                            $sku = $m->inventoryItem->sku ?? $m->masterProduct->sku ?? '-';
                                            $name = $m->inventoryItem->name ?? $m->masterProduct->name ?? '-';
                                            $unit = $m->inventoryItem->unit ?? 'pcs';
                                            $type = $m->inventoryItem->type ?? 'Produk';
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">{{ $mIdx + 1 }}</td>
                                            <td><span class="badge bg-secondary-subtle text-dark font-monospace border px-2 py-1">{{ $sku }}</span></td>
                                            <td class="fw-semibold text-dark">{{ $name }}</td>
                                            <td class="text-center"><span class="badge bg-light text-secondary border text-uppercase" style="font-size:0.68rem;">{{ $type }}</span></td>
                                            <td class="text-end font-monospace">
                                                @if($m->quantity > 0)
                                                    <span class="text-success fw-bold">+{{ number_format($m->quantity, 0, ',', '.') }} {{ $unit }}</span>
                                                @elseif($m->quantity < 0)
                                                    <span class="text-danger fw-bold">{{ number_format($m->quantity, 0, ',', '.') }} {{ $unit }}</span>
                                                @else
                                                    <span class="text-muted">0 {{ $unit }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-dark">
                                                {{ $m->balance_after !== null ? number_format($m->balance_after, 0, ',', '.') : '-' }} {{ $unit }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button type="button" class="btn btn-sm btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@else
    {{-- ── TAB 1: DATA BARANG ── --}}
    <!-- Filter Card -->
    <div class="v2-card mb-3">
        <div class="v2-card-body p-2.5">
            <form method="GET" action="{{ url('/v2/barang') }}" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="items">
                <div class="col-12 col-sm-6 col-md-4">
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Cari nama barang..." value="{{ request('name') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <input type="text" name="sku" class="form-control form-control-sm" placeholder="Cari SKU / Kode..." value="{{ request('sku') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">-- Semua Jenis Barang --</option>
                        <option value="bahan" {{ request('type') == 'bahan' ? 'selected' : '' }}>Bahan Baku (Kain, dll)</option>
                        <option value="kemasan" {{ request('type') == 'kemasan' ? 'selected' : '' }}>Kemasan (Kardus, Plastik)</option>
                        <option value="atk" {{ request('type') == 'atk' ? 'selected' : '' }}>ATK / Peralatan</option>
                        <option value="inventaris" {{ request('type') == 'inventaris' ? 'selected' : '' }}>Inventaris / Aset</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-1.5">
                    <button type="submit" class="btn btn-sm btn-v2-primary w-100 justify-content-center py-1">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    @if(request()->anyFilled(['name', 'sku', 'type']))
                        <a href="{{ url('/v2/barang?tab=items') }}" class="btn btn-sm btn-v2-secondary py-1 px-2" title="Reset Filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="v2-card">
        <div class="v2-card-header bg-light py-2 d-flex align-items-center justify-content-between">
            <h6 class="v2-card-title d-flex align-items-center gap-2 m-0">
                <i class="bi bi-list-columns-reverse text-primary"></i> Daftar Master Data Barang
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">
                    {{ $items->total() }} Item
                </span>
            </h6>
        </div>
        <div class="v2-card-body p-0">
            <div class="v2-table-responsive">
                <table class="v2-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 35px;" class="text-center">#</th>
                            <th>SKU / KODE</th>
                            <th>NAMA BARANG</th>
                            <th class="text-center">JENIS BARANG</th>
                            <th class="text-center">SATUAN</th>
                            <th class="text-end">STOK GUDANG</th>
                            <th class="text-end">HARGA MODAL (HPP)</th>
                            <th class="text-center" style="width: 130px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $item)
                            <tr>
                                <td class="text-center text-muted" style="font-size: 0.72rem;">
                                    {{ $items->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="sku-badge">{{ $item->sku }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.8rem;">
                                        {{ $item->name }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($item->type === 'bahan')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                            <i class="bi bi-scissors me-1"></i> Bahan Baku
                                        </span>
                                    @elseif($item->type === 'kemasan')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                            <i class="bi bi-box-seam me-1"></i> Kemasan
                                        </span>
                                    @elseif($item->type === 'atk')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                            <i class="bi bi-pencil me-1"></i> ATK / Peralatan
                                        </span>
                                    @elseif($item->type === 'inventaris')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                            <i class="bi bi-building me-1"></i> Inventaris / Aset
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                            {{ ucfirst($item->type) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.68rem;">{{ $item->unit }}</span>
                                </td>
                                <td class="text-end fw-bold font-monospace" style="font-size: 0.78rem;">
                                    @if($item->stock <= 0)
                                        <span class="badge bg-danger text-white px-2 py-0.5">Habis (0)</span>
                                    @elseif($item->stock <= $item->min_stock)
                                        <span class="badge bg-warning text-dark px-2 py-0.5" title="Minimal Stok: {{ $item->min_stock }}">
                                            Menipis ({{ number_format($item->stock, 2, ',', '.') }})
                                        </span>
                                    @else
                                        <span class="text-dark">{{ number_format($item->stock, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace text-dark" style="font-size: 0.78rem;">
                                    Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <button type="button" 
                                                class="btn-action-icon btn-action-view btn-adjust-item" 
                                                title="Opname / Sesuaikan Stok"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-unit="{{ $item->unit }}"
                                                data-stock="{{ $item->stock }}">
                                            <i class="bi bi-clipboard-check text-white"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn-action-icon btn-action-edit btn-edit-item" 
                                                title="Edit Barang"
                                                data-id="{{ $item->id }}"
                                                data-sku="{{ $item->sku }}"
                                                data-name="{{ $item->name }}"
                                                data-type="{{ $item->type }}"
                                                data-unit="{{ $item->unit }}"
                                                data-min-stock="{{ $item->min_stock }}"
                                                data-cost-price="{{ number_format($item->cost_price, 0, '', '') }}">
                                            <i class="bi bi-pencil text-white"></i>
                                        </button>
                                        <form action="{{ url('/v2/barang/' . $item->id) }}" method="POST" class="d-inline form-delete-barang m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action-icon btn-action-delete" title="Hapus Barang" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                                <i class="bi bi-trash text-white"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <span style="font-size: 0.82rem;">Belum ada data barang ditemukan.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($items->hasPages())
            <div class="v2-card-footer bg-light py-2 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted" style="font-size: 0.72rem;">
                        Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }} dari {{ $items->total() }} barang
                    </div>
                    <div>
                        {{ $items->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif

<!-- Modal Import Stock Opname -->
<div class="modal fade" id="importOpnameModal" tabindex="-1" aria-labelledby="importOpnameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="importOpnameModalLabel">
                    <i class="bi bi-file-earmark-arrow-up me-2"></i>Import Stock Opname (CSV)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('v2.barang.import_opname') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0 rounded-3">
                        <i class="bi bi-info-circle me-1"></i>
                        File CSV wajib memiliki kolom header <strong>SKU</strong> dan <strong>Stok</strong> (Stok Fisik). Sistem akan otomatis menghitung selisih dan menyesuaikan stok.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Upload File CSV <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept=".csv,.txt" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Nama Petugas PIC</label>
                        <input type="text" name="pic" class="form-control form-control-sm" value="{{ Auth::user()->name }}" placeholder="Nama Petugas Audit">
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <a href="{{ route('v2.barang.opname_template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> Download Template CSV
                        </a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm btn-warning text-dark px-4 fw-semibold">
                                <i class="bi bi-upload me-1"></i> Import & Process
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Show Opname Detail -->
<div class="modal fade" id="showOpnameModal" tabindex="-1" aria-labelledby="showOpnameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="showOpnameModalLabel">
                    <i class="bi bi-info-circle me-2"></i>Detail Transaksi Stock Opname
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="border rounded-3 overflow-hidden mb-3" style="font-size: 0.8rem;">
                    <div class="d-flex justify-content-between p-2.5 border-bottom bg-light">
                        <span class="text-muted">Tanggal Opname</span>
                        <strong id="opDetailDate" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between p-2.5 border-bottom">
                        <span class="text-muted">Nama Barang</span>
                        <strong id="opDetailItem" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between p-2.5 border-bottom">
                        <span class="text-muted">SKU / Kode</span>
                        <strong id="opDetailSku" class="font-monospace text-primary">-</strong>
                    </div>
                    <div class="d-flex justify-content-between p-2.5 border-bottom">
                        <span class="text-muted">Selisih Penyesuaian Qty</span>
                        <strong id="opDetailQty" class="font-monospace">-</strong>
                    </div>
                    <div class="d-flex justify-content-between p-2.5 border-bottom">
                        <span class="text-muted">Stok Akhir Setelah Opname</span>
                        <strong id="opDetailAfter" class="font-monospace text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between p-2.5 border-bottom">
                        <span class="text-muted">Referensi / Catatan</span>
                        <strong id="opDetailRef" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between p-2.5 bg-light">
                        <span class="text-muted">Diinput Oleh</span>
                        <strong id="opDetailUser" class="text-dark">-</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-sm btn-v2-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create Barang -->
<div class="modal fade" id="createBarangModal" tabindex="-1" aria-labelledby="createBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="createBarangModalLabel">
                    <i class="bi bi-plus-circle text-primary"></i> Tambah Barang Baru
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('/v2/barang') }}" method="POST">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Kain Batik Semi Sutra / Plastik Zipper 25x35" required>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">SKU / Kode Barang</label>
                            <input type="text" name="sku" class="form-control form-control-sm" placeholder="Otomatis jika kosong">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Jenis Barang <span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="bahan">Bahan Baku (Kain, dll)</option>
                                <option value="kemasan">Kemasan (Kardus, Plastik)</option>
                                <option value="atk">ATK / Peralatan</option>
                                <option value="inventaris">Inventaris / Aset</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control form-control-sm" placeholder="meter, pcs, kg, roll, pack" value="pcs" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Minimal Stok Warning</label>
                            <input type="number" name="min_stock" class="form-control form-control-sm" value="5" min="0">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Stok Awal</label>
                            <input type="number" step="any" name="stock" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Harga Modal (HPP)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="cost_price" class="form-control form-control-sm" placeholder="0" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-primary">
                        <i class="bi bi-save me-1"></i> Simpan Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Barang -->
<div class="modal fade" id="editBarangModal" tabindex="-1" aria-labelledby="editBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="editBarangModalLabel">
                    <i class="bi bi-pencil-square text-primary"></i> Edit Data Barang
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBarangForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-3.5">
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" id="editBarangName" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">SKU / Kode Barang <span class="text-danger">*</span></label>
                            <input type="text" id="editBarangSku" name="sku" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Jenis Barang <span class="text-danger">*</span></label>
                            <select id="editBarangType" name="type" class="form-select form-select-sm" required>
                                <option value="bahan">Bahan Baku (Kain, dll)</option>
                                <option value="kemasan">Kemasan (Kardus, Plastik)</option>
                                <option value="atk">ATK / Peralatan</option>
                                <option value="inventaris">Inventaris / Aset</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label form-label-sm fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                            <input type="text" id="editBarangUnit" name="unit" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm fw-semibold">Min. Stok Warning</label>
                            <input type="number" id="editBarangMinStock" name="min_stock" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm fw-semibold">Harga Modal (HPP)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" id="editBarangCostPrice" name="cost_price" class="form-control form-control-sm" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-primary">
                        <i class="bi bi-save me-1"></i> Update Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Opname / Adjust Stock Barang -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="adjustStockModalLabel">
                    <i class="bi bi-clipboard-check text-success"></i> Opname / Penyesuaian Stok
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adjustStockForm" method="POST">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="p-2.5 bg-light rounded-3 border mb-3">
                        <div class="fw-bold text-dark" style="font-size: 0.82rem;" id="adjustItemName">-</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Stok Sekarang: <span class="fw-bold text-primary font-monospace" id="adjustItemStock">0</span> <span id="adjustItemUnit">pcs</span></div>
                    </div>
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Jumlah Penyesuaian <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="quantity" class="form-control form-control-sm" placeholder="Contoh: +10 atau -5" required>
                        <div class="form-text" style="font-size: 0.65rem;">Gunakan angka positif (+) untuk penambahan stok dan minus (-) untuk pengurangan stok.</div>
                    </div>
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Keterangan / Referensi <span class="text-danger">*</span></label>
                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Contoh: Stock Opname Bulan Ini / Rusak" required>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-success">
                        <i class="bi bi-check-lg me-1"></i> Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handler Edit Barang
    $('.btn-edit-item').on('click', function() {
        const id = $(this).data('id');
        const sku = $(this).data('sku');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const unit = $(this).data('unit');
        const minStock = $(this).data('min-stock');
        const costPrice = $(this).data('cost-price');

        $('#editBarangForm').attr('action', '{{ url("/v2/barang") }}/' + id);
        $('#editBarangSku').val(sku);
        $('#editBarangName').val(name);
        $('#editBarangType').val(type);
        $('#editBarangUnit').val(unit);
        $('#editBarangMinStock').val(minStock);
        $('#editBarangCostPrice').val(costPrice);

        $('#editBarangModal').modal('show');
    });

    // Handler Adjust Stock
    $('.btn-adjust-item').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const unit = $(this).data('unit');
        const stock = $(this).data('stock');

        $('#adjustStockForm').attr('action', '{{ url("/v2/barang") }}/' + id + '/adjust');
        $('#adjustItemName').text(name);
        $('#adjustItemStock').text(stock);
        $('#adjustItemUnit').text(unit);

        $('#adjustStockModal').modal('show');
    });

    // Handler Show Opname Detail Modal
    $('.btn-show-opname').on('click', function() {
        const date = $(this).data('date');
        const item = $(this).data('item');
        const sku = $(this).data('sku');
        const unit = $(this).data('unit');
        const qty = parseFloat($(this).data('qty')) || 0;
        const after = parseFloat($(this).data('after')) || 0;
        const ref = $(this).data('ref');
        const user = $(this).data('user');

        $('#opDetailDate').text(date);
        $('#opDetailItem').text(item);
        $('#opDetailSku').text(sku);
        $('#opDetailRef').text(ref);
        $('#opDetailUser').text(user);
        $('#opDetailAfter').text(after.toLocaleString('id-ID') + ' ' + unit);

        const qtySign = qty > 0 ? '+' : '';
        const qtyColor = qty > 0 ? 'text-success' : (qty < 0 ? 'text-danger' : 'text-muted');
        $('#opDetailQty').html(`<span class="${qtyColor}">${qtySign}${qty.toLocaleString('id-ID')} ${unit}</span>`);

        $('#showOpnameModal').modal('show');
    });
});
</script>
@endpush
