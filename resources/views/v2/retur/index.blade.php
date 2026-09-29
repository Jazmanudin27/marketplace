@extends('v2.layouts.app')

@section('title', 'Pesanan Retur')

@section('content')
<style>
    /* ── Custom V2 Retur Styles ── */
    .rtr-header-bar {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .rtr-tab-bar {
        display: flex;
        align-items: center;
        gap: 4px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
        padding: 0 16px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .rtr-tab-bar::-webkit-scrollbar { display: none; }

    .rtr-tab {
        padding: 12px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #6b7280;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .rtr-tab:hover { color: #2563eb; text-decoration: none; }
    .rtr-tab.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }
    .rtr-tab .tab-badge {
        font-size: 0.68rem;
        padding: 2px 7px;
        border-radius: 10px;
        background: #f3f4f6;
        color: #4b5563;
        font-weight: 700;
    }
    .rtr-tab.active .tab-badge {
        background: #eff6ff;
        color: #2563eb;
    }

    .rtr-filter-bar {
        padding: 14px 16px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    .rtr-filter-bar label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 4px;
    }
    .rtr-filter-bar .form-control,
    .rtr-filter-bar .form-select {
        font-size: 0.78rem;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        padding: 5px 10px;
        height: 32px;
    }

    .rtr-summary-bar {
        padding: 10px 16px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.78rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .rtr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
    }
    .rtr-table th {
        background: #f9fafb;
        color: #4b5563;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.68rem;
        letter-spacing: 0.03em;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    .rtr-table td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        color: #1f2937;
    }
    .rtr-table tr:hover td {
        background: #fafafa;
    }

    /* Badges */
    .rtr-badge {
        font-size: 0.68rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .rtr-badge-qc-pending { background: #fffbe6; color: #d97706; border: 1px solid #fef3c7; }
    .rtr-badge-qc-good { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .rtr-badge-qc-defective { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .rtr-badge-status {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        text-transform: uppercase;
    }

    /* Buttons */
    .rtr-btn {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.15s ease;
        text-decoration: none;
        cursor: pointer;
        border: none;
    }
    .rtr-btn-primary { background: #2563eb; color: #fff; }
    .rtr-btn-primary:hover { background: #1d4ed8; color: #fff; }
    .rtr-btn-success { background: #16a34a; color: #fff; }
    .rtr-btn-success:hover { background: #15803d; color: #fff; }
    .rtr-btn-outline { background: #fff; border: 1px solid #d1d5db; color: #374151; }
    .rtr-btn-outline:hover { background: #f3f4f6; color: #111827; }

    /* Channel badges */
    .ch-shopee { background: #fff4f2; color: #ee4d2d; border: 1px solid #ffccb6; }
    .ch-tiktok { background: #f3f4f6; color: #111827; border: 1px solid #e5e7eb; }
    .ch-lazada { background: #eff6ff; color: #0f172a; border: 1px solid #bfdbfe; }
    .ch-default { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }

    .reason-chip {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 0.7rem;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div class="container-fluid px-3 px-md-4 py-3">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 py-2.5" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3 py-2.5" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Top Header Bar --}}
    <div class="rtr-header-bar">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2" style="font-size: 1.1rem;">
                <i class="bi bi-arrow-counterclockwise text-primary"></i>
                Pesanan Retur
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.78rem;">
                Kelola retur barang dari marketplace, inspek fisik gudang, dan buat pesanan pengganti.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('v2.retur.sync') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="rtr-btn rtr-btn-outline" onclick="this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span>Menarik Retur...'; this.disabled=true; this.form.submit();">
                    <i class="bi bi-arrow-repeat text-primary"></i> Sinkronkan Retur
                </button>
            </form>
            <a href="{{ route('v2.retur.export', request()->query()) }}" class="rtr-btn rtr-btn-outline">
                <i class="bi bi-download text-success"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- Top Reason Stats Banner (If Available) --}}
    @if(isset($reasonsStats) && $reasonsStats->count() > 0)
        <div class="v2-card p-3 mb-3 shadow-sm" style="border-left: 4px solid #2563eb;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-primary fs-5"></i>
                    <span class="fw-bold text-dark" style="font-size: 0.8rem;">Top Alasan Retur Pembeli:</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($reasonsStats as $stat)
                        <div class="reason-chip">
                            <span>{{ Str::limit($stat->reason, 35) }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $stat->count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="v2-card p-0 shadow-sm overflow-hidden">

        {{-- Status Tabs --}}
        @php
            $currentIsRestocked = request('is_restocked', '');
            $tabs = [
                '' => ['label' => 'Semua Retur', 'icon' => 'bi bi-collection', 'count' => $totalReturns],
                '0' => ['label' => 'Menunggu QC / Inspeksi', 'icon' => 'bi bi-hourglass-split', 'count' => $pendingQc],
                '1' => ['label' => 'Sudah QC / Selesai', 'icon' => 'bi bi-check-circle', 'count' => $alreadyQc],
            ];
        @endphp
        <div class="rtr-tab-bar" role="tablist">
            @foreach($tabs as $tKey => $tInfo)
                @php
                    $tUrl = route('v2.retur.index', array_merge(
                        request()->except(['is_restocked','page']),
                        $tKey !== '' ? ['is_restocked' => $tKey] : []
                    ));
                    $isActive = (string)$currentIsRestocked === (string)$tKey;
                @endphp
                <a class="rtr-tab {{ $isActive ? 'active' : '' }}" href="{{ $tUrl }}">
                    <i class="{{ $tInfo['icon'] }}"></i>
                    {{ $tInfo['label'] }}
                    @if($tInfo['count'] > 0)
                        <span class="tab-badge">{{ $tInfo['count'] > 999 ? '999+' : $tInfo['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Filter Bar --}}
        <div class="rtr-filter-bar">
            <form method="GET" action="{{ route('v2.retur.index') }}">
                @if(request('is_restocked') !== null)
                    <input type="hidden" name="is_restocked" value="{{ request('is_restocked') }}">
                @endif
                <div class="row g-2 align-items-end">
                    {{-- Cari --}}
                    <div class="col-12 col-md-3">
                        <label><i class="bi bi-search me-1"></i>Pencarian</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Cari SN retur, no. invoice, order ID..."
                               value="{{ request('search') }}">
                    </div>
                    {{-- Channel --}}
                    <div class="col-6 col-md-2">
                        <label><i class="bi bi-shop me-1"></i>Channel</label>
                        <select name="channel_id" class="form-select">
                            <option value="">Semua Channel</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->id }}" {{ request('channel_id') == $ch->id ? 'selected' : '' }}>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Toko --}}
                    <div class="col-6 col-md-3">
                        <label><i class="bi bi-building me-1"></i>Toko</label>
                        <select name="store_id" class="form-select">
                            <option value="">Semua Toko</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->store_name }} ({{ $s->channel->name ?? 'MP' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Status Retur MP --}}
                    <div class="col-6 col-md-2">
                        <label><i class="bi bi-tag me-1"></i>Status Retur</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                                    {{ $st }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Tombol --}}
                    <div class="col-6 col-md-2 d-flex gap-1">
                        <button type="submit" class="rtr-btn rtr-btn-primary w-100 justify-content-center" style="height:32px;">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        @if(request()->anyFilled(['search','channel_id','store_id','status','is_restocked']))
                            <a href="{{ route('v2.retur.index') }}" class="rtr-btn rtr-btn-outline" style="height:32px;" title="Reset Filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Summary Bar --}}
        <div class="rtr-summary-bar">
            <div>
                <i class="bi bi-list-ul me-1"></i>
                <strong class="text-dark">{{ $returns->total() }}</strong> Retur Ditemukan
                @if($returns->total() > 0)
                    &nbsp;·&nbsp; Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}
                @endif
            </div>
               {{-- Table --}}
        <div class="table-responsive">
            <table class="rtr-table">
                <thead>
                    <tr>
                        <th style="width: 36px; text-align: center;">#</th>
                        <th style="min-width: 210px;">RETUR &amp; TOKO</th>
                        <th style="min-width: 230px;">BARANG DIRETUR &amp; ALASAN</th>
                        <th style="min-width: 180px; text-align: center;">STATUS &amp; REFUND</th>
                        <th style="width: 120px; text-align: center;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $index => $ret)
                        @php
                            $chCode = strtolower($ret->store?->channel?->code ?? '');
                            $chName = $ret->store?->channel?->name ?? 'Offline';
                            $chClass = match(true) {
                                str_contains($chCode, 'shopee') => 'ch-shopee',
                                str_contains($chCode, 'tiktok') => 'ch-tiktok',
                                str_contains($chCode, 'lazada') => 'ch-lazada',
                                default => 'ch-default'
                            };
                        @endphp
                        <tr>
                            <td style="text-align: center;" class="text-muted fw-bold">
                                {{ $returns->firstItem() + $index }}
                            </td>

                            {{-- Retur & Toko --}}
                            <td>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <span class="fw-bold text-primary font-monospace" style="font-size: 0.83rem;">
                                        <i class="bi bi-arrow-return-left me-0.5"></i>{{ $ret->return_sn }}
                                    </span>
                                    <span class="rtr-badge {{ $chClass }}" style="font-size:0.62rem; padding: 2px 6px;">
                                        {{ $chName }}
                                    </span>
                                </div>
                                <div class="fw-bold text-dark mt-1" style="font-size: 0.78rem;">
                                    <i class="bi bi-shop me-1 text-secondary"></i>{{ $ret->store->store_name ?? 'Toko Tidak Diketahui' }}
                                </div>
                                <div class="mt-0.5 text-muted" style="font-size: 0.7rem;">
                                    <span>Inv:</span>
                                    @if($ret->order)
                                        <a href="{{ route('v2.pesanan.index', ['order_number' => $ret->order->invoice_number]) }}" class="fw-semibold text-dark text-decoration-none" title="Lihat Pesanan Asli">
                                            {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                        </a>
                                    @else
                                        <span>-</span>
                                    @endif
                                    &nbsp;·&nbsp;
                                    <span>{{ $ret->created_at ? $ret->created_at->format('d/m/y H:i') : '-' }}</span>
                                </div>
                            </td>

                            {{-- Barang & Alasan --}}
                            <td>
                                <div class="mb-1">
                                    @foreach($ret->items as $rItem)
                                        <div class="d-flex align-items-center gap-1.5 mb-1">
                                            <span class="badge bg-secondary" style="font-size: 0.65rem;">{{ $rItem->quantity }}x</span>
                                            <span class="fw-semibold text-dark" style="font-size: 0.76rem;">
                                                {{ $rItem->orderItem->product_name ?? 'Barang Retur' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                @if($ret->reason)
                                    <div class="text-danger bg-danger bg-opacity-10 px-2 py-0.5 rounded d-inline-block mt-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                                        <strong>Alasan:</strong> {{ $ret->reason }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status & Refund --}}
                            <td style="text-align: center;">
                                <div class="fw-bold text-dark font-monospace mb-1" style="font-size: 0.85rem;">
                                    Rp {{ number_format($ret->refund_amount ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center justify-content-center gap-1">
                                    <span class="rtr-badge rtr-badge-status" title="Status Marketplace">
                                        {{ $ret->status ?? 'REQUESTED' }}
                                    </span>

                                    @if($ret->is_restocked)
                                        @if($ret->inspection_status === 'GOOD')
                                            <span class="rtr-badge rtr-badge-qc-good" title="Status QC Gudang">
                                                <i class="bi bi-check-circle-fill"></i> Layak Jual
                                            </span>
                                        @else
                                            <span class="rtr-badge rtr-badge-qc-defective" title="Status QC Gudang">
                                                <i class="bi bi-x-circle-fill"></i> Cacat
                                            </span>
                                        @endif
                                    @else
                                        <span class="rtr-badge rtr-badge-qc-pending" title="Status QC Gudang">
                                            <i class="bi bi-hourglass-split"></i> Belum QC
                                        </span>
                                    @endif
                                </div>

                                @if($ret->replacement_order_id)
                                    <div class="mt-1">
                                        <span class="badge bg-info text-white" style="font-size: 0.62rem;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Pengganti Dikirim
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td style="text-align: center;">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if(!$ret->is_restocked)
                                        <button type="button" class="rtr-btn rtr-btn-success" data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                            <i class="bi bi-clipboard-check"></i> QC Inspeksi
                                        </button>
                                    @else
                                        <button type="button" class="rtr-btn rtr-btn-outline" data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}" title="Lihat / Edit Hasil QC">
                                            <i class="bi bi-eye"></i> Detail QC
                                        </button>

                                        @if(!$ret->replacement_order_id && $ret->order)
                                            <button type="button" class="rtr-btn rtr-btn-primary" data-bs-toggle="modal" data-bs-target="#replModal-{{ $ret->id }}">
                                                <i class="bi bi-box-arrow-right"></i> Pengganti
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- ── Modal QC Inspeksi Gudang ── --}}
                        <div class="modal fade" id="qcModal-{{ $ret->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                                    <form action="{{ route('v2.retur.restock', $ret->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-header bg-primary text-white py-2.5 px-3">
                                            <h6 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" style="font-size:0.9rem;">
                                                <i class="bi bi-clipboard-check-fill"></i>
                                                Inspeksi Fisik Gudang — {{ $ret->return_sn }}
                                            </h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-3 style-scroll" style="font-size:0.8rem;">
                                            @if($ret->is_restocked)
                                                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.75rem;">
                                                    <i class="bi bi-info-circle-fill me-1"></i> Retur ini telah diperiksa sebelumnya. Anda dapat memperbarui data jika diperlukan.
                                                </div>
                                            @endif

                                            <div class="mb-3 p-2.5 bg-light rounded border">
                                                <div class="row g-2">
                                                    <div class="col-6"><strong>SN Retur:</strong> {{ $ret->return_sn }}</div>
                                                    <div class="col-6"><strong>Marketplace:</strong> {{ $ret->store->channel->name ?? 'Shopee' }}</div>
                                                    <div class="col-12"><strong>Alasan Pembeli:</strong> <span class="text-danger">{{ $ret->reason ?? '-' }}</span></div>
                                                </div>
                                            </div>

                                            <h6 class="fw-bold mb-2 text-dark" style="font-size:0.82rem;">Pemeriksaan Item Retur:</h6>

                                            @foreach($ret->items as $rItem)
                                                <div class="border rounded p-3 mb-3 bg-white shadow-sm">
                                                    <div class="fw-bold text-primary mb-2" style="font-size:0.83rem;">
                                                        {{ $rItem->quantity }}x {{ $rItem->orderItem->product_name ?? 'Barang Retur' }}
                                                    </div>

                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold mb-1">Kondisi Fisik Goods:</label>
                                                            <div class="d-flex gap-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" 
                                                                           name="items[{{ $rItem->id }}][inspection_status]" 
                                                                           id="status_good_{{ $rItem->id }}" 
                                                                           value="GOOD" 
                                                                           {{ ($rItem->inspection_status ?? 'GOOD') === 'GOOD' ? 'checked' : '' }}
                                                                           {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                                    <label class="form-check-label text-success fw-bold" for="status_good_{{ $rItem->id }}">
                                                                        <i class="bi bi-check-circle me-1"></i>Layak Jual (Tambah Stok)
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" 
                                                                           name="items[{{ $rItem->id }}][inspection_status]" 
                                                                           id="status_def_{{ $rItem->id }}" 
                                                                           value="DEFECTIVE" 
                                                                           {{ ($rItem->inspection_status ?? '') === 'DEFECTIVE' ? 'checked' : '' }}
                                                                           {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                                    <label class="form-check-label text-danger fw-bold" for="status_def_{{ $rItem->id }}">
                                                                        <i class="bi bi-x-circle me-1"></i>Cacat / Rusak
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold mb-1">Catatan QC:</label>
                                                            <input type="text" name="items[{{ $rItem->id }}][inspection_notes]" 
                                                                   class="form-control form-control-sm" 
                                                                   placeholder="Contoh: Plastik terbuka, barang dalam kondisi baru..." 
                                                                   value="{{ $rItem->inspection_notes }}"
                                                                   {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                        </div>

                                                        <div class="col-md-12">
                                                            <label class="form-label fw-bold mb-1">Upload Foto Bukti Fisik (Opsional):</label>
                                                            <input type="file" name="items[{{ $rItem->id }}][photo]" 
                                                                   class="form-control form-control-sm" accept="image/*"
                                                                   {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                            @if($rItem->inspection_photo)
                                                                <div class="mt-2">
                                                                    <a href="{{ asset($rItem->inspection_photo) }}" target="_blank" class="btn btn-outline-secondary btn-sm py-0.5 px-2" style="font-size:0.7rem;">
                                                                        <i class="bi bi-image me-1"></i>Lihat Foto Terupload
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="modal-footer py-2 px-3 bg-light">
                                            <button type="button" class="rtr-btn rtr-btn-outline" data-bs-dismiss="modal">Tutup</button>
                                            @if(!$ret->is_restocked)
                                                <button type="submit" class="rtr-btn rtr-btn-success">
                                                    <i class="bi bi-save me-1"></i>Simpan Hasil QC &amp; Update Stok
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- ── Modal Kirim Barang Pengganti ── --}}
                        @if(!$ret->replacement_order_id && $ret->order)
                            <div class="modal fade" id="replModal-{{ $ret->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                                        <form action="{{ route('v2.retur.replacement', $ret->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-primary text-white py-2.5 px-3">
                                                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" style="font-size:0.9rem;">
                                                    <i class="bi bi-box-arrow-right"></i>
                                                    Buat Pesanan Pengganti — {{ $ret->return_sn }}
                                                </h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-3" style="font-size:0.8rem;">
                                                <div class="alert alert-warning py-2.5 px-3 mb-3" style="font-size:0.76rem;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1.5"></i>
                                                    Pesanan baru akan otomatis dibuat berstatus <strong>READY_TO_SHIP</strong> (Perlu Dikirim) dengan total bayar <strong>Rp 0</strong> dan stok akan terpotong dari gudang.
                                                </div>

                                                <div class="p-2.5 bg-light rounded border mb-3">
                                                    <div><strong>Penerima:</strong> {{ $ret->order->buyer_name }} ({{ $ret->order->buyer_phone ?? '-' }})</div>
                                                    <div class="text-truncate"><strong>Alamat:</strong> {{ $ret->order->shipping_address ?? '-' }}</div>
                                                    <div><strong>Kurir:</strong> {{ $ret->order->courier ?? '-' }}</div>
                                                </div>

                                                <div class="fw-bold mb-1">Item yang Akan Dikirim Ulang:</div>
                                                <ul class="ps-3 mb-0" style="font-size:0.76rem;">
                                                    @foreach($ret->items as $rItem)
                                                        <li>{{ $rItem->quantity }}x {{ $rItem->orderItem->product_name ?? 'Barang Retur' }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <div class="modal-footer py-2 px-3 bg-light">
                                                <button type="button" class="rtr-btn rtr-btn-outline" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="rtr-btn rtr-btn-primary">
                                                    <i class="bi bi-check-circle me-1"></i>Buat Pesanan Pengganti Now
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <div class="fw-bold" style="font-size:0.9rem;">Tidak Ada Data Retur</div>
                                <div style="font-size:0.75rem;">Belum ada pesanan retur yang sesuai dengan filter pencarian Anda.</div>
                            </td>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($returns->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" style="font-size:0.75rem;">
                    Menampilkan {{ $returns->firstItem() }} - {{ $returns->lastItem() }} dari {{ $returns->total() }} retur
                </div>
                <div>
                    {{ $returns->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif

    </div>
</div>

@endsection
