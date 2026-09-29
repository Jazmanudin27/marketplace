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
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.78rem;
    }
    .rtr-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.68rem;
        letter-spacing: 0.03em;
        padding: 10px 14px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .rtr-table td {
        padding: 12px 14px;
        vertical-align: top;
        color: #1f2937;
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

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-arrow-counterclockwise text-primary fs-5"></i> Pesanan Retur
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola retur barang dari marketplace, inspek fisik gudang, dan buat pesanan pengganti.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form action="{{ route('v2.retur.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-v2-secondary py-1 px-3" onclick="this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span>Menarik Retur...'; this.disabled=true; this.form.submit();">
                <i class="bi bi-arrow-repeat text-primary me-1"></i> Sinkronkan Retur
            </button>
        </form>
    </div>
</div>

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
                                @php
                                    $stOptIndo = match(strtoupper((string)$st)) {
                                        'REQUESTED', 'NEW_REQUEST' => 'Pengajuan Baru',
                                        'PROCESSING', 'IN_PROCESSING', 'IN_PROCESS' => 'Sedang Diproses',
                                        'BUYER_SHIPPED_ITEM', 'SHIPPED', 'TO_RECEIVED' => 'Dikirim Pembeli',
                                        'CLOSED' => 'Retur Selesai',
                                        'COMPLETED', 'SUCCESS', 'FINISHED' => 'Selesai',
                                        'REFUNDED', 'REFUND_SUCCESS' => 'Dana Dikembalikan',
                                        'CANCELLED', 'REJECTED', 'REFUND_REJECTED', 'CANCEL' => 'Ditolak / Dibatalkan',
                                        'ACCEPTED', 'APPROVED', 'SELLER_AGREE' => 'Disetujui',
                                        'JUDGING' => 'Dalam Penilaian MP',
                                        default => str_replace('_', ' ', (string)$st)
                                    };
                                @endphp
                                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                                    {{ $stOptIndo }}
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
        </div>

        {{-- Table Grouped Order Style --}}
        <div class="table-responsive">
            <table class="rtr-table">
                <thead>
                    <tr>
                        <th style="width: 42%;">PRODUK DIRETUR &amp; ALASAN</th>
                        <th style="width: 18%; text-align: center;">REFUND &amp; STATUS MARKETPLACE</th>
                        <th style="width: 24%; text-align: center;">INSPEKSI GUDANG (QC)</th>
                        <th style="width: 16%; text-align: center;">AKSI</th>
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

                            // Fallback item fetching if marketplace retur item relationship is empty
                            $displayItems = $ret->items;
                            $isFallback = false;
                            if ($displayItems->isEmpty() && $ret->order && $ret->order->items) {
                                $displayItems = $ret->order->items;
                                $isFallback = true;
                            }

                            // Indonesian reason format
                            $reasonRaw = $ret->reason;
                            $reasonText = match(strtoupper(trim((string)$reasonRaw))) {
                                'CHANGE_MIND', 'CHANGE_OF_MIND' => 'Berubah Pikiran',
                                'WRONG_ITEM', 'WRONG_PRODUCT', 'WRONG_SPEC' => 'Salah Kirim Produk',
                                'ITEM_MISSING', 'MISSING_ITEM', 'MISSING_QUANTITY' => 'Barang / Komponen Kurang',
                                'DIFFERENT_DESCRIPTION', 'PRODUCT DOESN\'T MATCH DESCRIPTION', 'PRODUCT DOESNT MATCH DESCRIPTION', 'NOT_AS_DESCRIBED' => 'Tidak Sesuai Deskripsi',
                                'DEFECTIVE_ITEM', 'DAMAGED_ITEM', 'DAMAGE_ITEM', 'DAMAGED', 'PHYSICAL_DAMAGE' => 'Barang Cacat / Rusak',
                                'NOT_RECEIVED', 'PARCEL_NOT_RECEIVED' => 'Barang Tidak Diterima',
                                'EXPIRED' => 'Barang Kadaluwarsa',
                                'MUTUAL_AGREE' => 'Kesepakatan Bersama',
                                'SUSPECT_FAKE', 'FAKE_ITEM' => 'Dugaan Produk Palsu',
                                'FUNCTIONAL_DEFECT' => 'Fungsi Produk Bermasalah',
                                default => str_replace(['_', '-'], ' ', (string)$reasonRaw ?? '-')
                            };

                            // Indonesian marketplace status format
                            $statusRaw = strtoupper((string)($ret->status ?? 'REQUESTED'));
                            $statusIndo = match($statusRaw) {
                                'REQUESTED', 'NEW_REQUEST' => 'Pengajuan Baru',
                                'PROCESSING', 'IN_PROCESSING', 'IN_PROCESS' => 'Sedang Diproses',
                                'BUYER_SHIPPED_ITEM', 'SHIPPED', 'TO_RECEIVED' => 'Dikirim Pembeli',
                                'CLOSED' => 'Retur Selesai',
                                'COMPLETED', 'SUCCESS', 'FINISHED' => 'Selesai',
                                'REFUNDED', 'REFUND_SUCCESS' => 'Dana Dikembalikan',
                                'CANCELLED', 'REJECTED', 'REFUND_REJECTED', 'CANCEL' => 'Ditolak / Dibatalkan',
                                'ACCEPTED', 'APPROVED', 'SELLER_AGREE' => 'Disetujui',
                                'JUDGING' => 'Dalam Penilaian MP',
                                default => str_replace('_', ' ', $statusRaw)
                            };
                        @endphp

                        {{-- Card Header Strip --}}
                        <tr style="background-color: #f8fafc; border-top: 2px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                            <td colspan="4" class="py-2 px-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 0.74rem;">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="rtr-badge {{ $chClass }} font-monospace">
                                            <i class="bi bi-shop me-1"></i>{{ strtoupper($chName) }}
                                        </span>
                                        <span class="fw-bold text-dark">
                                            <i class="bi bi-building me-1 text-secondary"></i>{{ $ret->store->store_name ?? 'Toko' }}
                                        </span>
                                        <span class="text-muted">|</span>
                                        <span class="text-muted">
                                            <i class="bi bi-person me-1"></i><strong>Pembeli:</strong> {{ $ret->order->buyer_name ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 flex-wrap font-monospace">
                                        <div>
                                            <span class="text-muted">SN Retur:</span>
                                            <span class="fw-bold text-primary me-1">{{ $ret->return_sn }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">No. Pesanan:</span>
                                            @if($ret->order)
                                                <a href="{{ route('v2.pesanan.index', ['order_number' => $ret->order->invoice_number]) }}" class="fw-bold text-dark text-decoration-none" title="Lihat Pesanan Asli">
                                                    {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                                </a>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock me-1"></i>{{ $ret->created_at ? $ret->created_at->format('d/m/Y H:i') : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Card Body --}}
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            {{-- Produk & Alasan --}}
                            <td class="align-top" style="padding: 12px 16px;">
                                <div class="d-flex flex-column gap-2 mb-2">
                                    @forelse($displayItems as $item)
                                        @php
                                            $orderItem = $isFallback ? $item : ($item->orderItem ?? null);
                                            $prodName = 'Barang Retur';
                                            $imgUrl = null;
                                            $sku = null;
                                            $variant = null;
                                            $qty = $isFallback ? ($item->quantity ?? 1) : ($item->quantity ?? 1);

                                            if ($orderItem) {
                                                $mpProduct = $orderItem->marketplaceProduct ?? null;
                                                $prodName = $mpProduct ? $mpProduct->name : ($orderItem->product_name ?? 'Barang Retur');
                                                $imgUrl = $orderItem->product_image ?? null;
                                                $sku = $orderItem->sku ?? ($mpProduct->sku ?? null);
                                                $variant = $orderItem->variant_name ?? null;
                                            }
                                        @endphp
                                        <div class="p-2 rounded-3 bg-white border border-slate-200 shadow-2xs d-flex align-items-start gap-2.5">
                                            <div class="position-relative border rounded-2 overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; background-color: #f8fafc;">
                                                @if($imgUrl)
                                                    <img src="{{ $imgUrl }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                                                @else
                                                    <div class="d-flex align-items-center justify-content-center h-100 w-100 text-muted" style="background-color: #f1f5f9;">
                                                        <i class="bi bi-box-seam fs-5 text-secondary"></i>
                                                    </div>
                                                @endif
                                                <span class="position-absolute bottom-0 end-0 bg-primary text-white px-1.5 font-monospace fw-bold" style="font-size: 0.62rem; border-top-left-radius: 5px;">
                                                    {{ $qty }}x
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-bold text-dark text-truncate-2" style="font-size: 0.8rem; line-height: 1.35; color: #1e293b;">
                                                    {{ $prodName }}
                                                </div>
                                                <div class="mt-1 d-flex flex-wrap align-items-center gap-1.5" style="font-size: 0.68rem;">
                                                    @if($sku)
                                                        <span class="badge bg-light text-dark border px-1.5 py-0.5" style="font-weight: 500;">
                                                            <i class="bi bi-barcode text-muted me-1"></i>SKU: {{ $sku }}
                                                        </span>
                                                    @endif
                                                    @if($variant)
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-1.5 py-0.5" style="font-weight: 500;">
                                                            Variasi: {{ $variant }}
                                                        </span>
                                                    @endif
                                                    @if($isFallback)
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-1.5 py-0.5" title="Diambil dari data pesanan asli">Item Pesanan</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted small">Detail produk tidak tersedia</div>
                                    @endforelse
                                </div>

                                @if($ret->reason)
                                    <div class="p-2 rounded-3 border d-flex align-items-center gap-2 mt-2" style="background: linear-gradient(135deg, #fff1f2, #fef2f2); border-color: #fecaca !important;">
                                        <div class="rounded-circle bg-danger bg-opacity-15 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 24px; height: 24px;">
                                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 0.72rem;"></i>
                                        </div>
                                        <div class="overflow-hidden" style="font-size: 0.72rem;">
                                            <span class="text-danger fw-bold">Alasan Retur:</span>
                                            <span class="text-dark fw-semibold me-1">{{ $reasonText }}</span>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            {{-- Refund & Status --}}
                            <td style="text-align: center;" class="align-top">
                                <div class="fw-bold text-dark font-monospace mb-1.5" style="font-size: 0.9rem;">
                                    Rp {{ number_format($ret->refund_amount ?? 0, 0, ',', '.') }}
                                </div>
                                <div>
                                    <span class="rtr-badge rtr-badge-status" title="Status Marketplace: {{ $statusRaw }}">
                                        {{ $statusIndo }}
                                    </span>
                                </div>
                            </td>

                            {{-- QC / Inspeksi Gudang --}}
                            <td style="text-align: center;" class="align-top">
                                @if($ret->is_restocked)
                                    @if($ret->inspection_status === 'GOOD')
                                        <span class="rtr-badge rtr-badge-qc-good mb-1">
                                            <i class="bi bi-check-circle-fill"></i> Layak Jual (Masuk Stok)
                                        </span>
                                    @else
                                        <span class="rtr-badge rtr-badge-qc-defective mb-1">
                                            <i class="bi bi-x-circle-fill"></i> Cacat / Rusak
                                        </span>
                                    @endif
                                    @if($ret->inspection_notes)
                                        <div class="text-muted text-truncate mx-auto" style="max-width: 140px; font-size: 0.68rem;" title="{{ $ret->inspection_notes }}">
                                            Catatan: {{ $ret->inspection_notes }}
                                        </div>
                                    @endif
                                @else
                                    <span class="rtr-badge rtr-badge-qc-pending">
                                        <i class="bi bi-hourglass-split"></i> Belum QC Gudang
                                    </span>
                                @endif

                                @if($ret->replacement_order_id)
                                    <div class="mt-1.5">
                                        <span class="badge bg-info text-white" style="font-size: 0.65rem;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Pesanan Pengganti Dikirim
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td style="text-align: center;" class="align-top">
                                <div class="d-flex flex-column align-items-center gap-1.5">
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
                                                <i class="bi bi-box-arrow-right"></i> Kirim Pengganti
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

                                            @foreach($displayItems as $rItem)
                                                @php
                                                    $rOrderItem = $isFallback ? $rItem : ($rItem->orderItem ?? null);
                                                    $rItemName = $rOrderItem ? ($rOrderItem->marketplaceProduct->name ?? ($rOrderItem->product_name ?? 'Barang Retur')) : 'Barang Retur';
                                                    $rItemId = $isFallback ? $rItem->id : $rItem->id;
                                                @endphp
                                                <div class="border rounded p-3 mb-3 bg-white shadow-sm">
                                                    <div class="fw-bold text-primary mb-2" style="font-size:0.83rem;">
                                                        {{ $rItem->quantity ?? 1 }}x {{ $rItemName }}
                                                    </div>

                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold mb-1">Kondisi Fisik Goods:</label>
                                                            <div class="d-flex gap-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" 
                                                                           name="items[{{ $rItemId }}][inspection_status]" 
                                                                           id="status_good_{{ $rItemId }}" 
                                                                           value="GOOD" 
                                                                           {{ ($rItem->inspection_status ?? 'GOOD') === 'GOOD' ? 'checked' : '' }}
                                                                           {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                                    <label class="form-check-label text-success fw-bold" for="status_good_{{ $rItemId }}">
                                                                        <i class="bi bi-check-circle me-1"></i>Layak Jual (Tambah Stok)
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" 
                                                                           name="items[{{ $rItemId }}][inspection_status]" 
                                                                           id="status_def_{{ $rItemId }}" 
                                                                           value="DEFECTIVE" 
                                                                           {{ ($rItem->inspection_status ?? '') === 'DEFECTIVE' ? 'checked' : '' }}
                                                                           {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                                    <label class="form-check-label text-danger fw-bold" for="status_def_{{ $rItemId }}">
                                                                        <i class="bi bi-x-circle me-1"></i>Cacat / Rusak
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold mb-1">Catatan QC:</label>
                                                            <input type="text" name="items[{{ $rItemId }}][inspection_notes]" 
                                                                   class="form-control form-control-sm" 
                                                                   placeholder="Contoh: Plastik terbuka, barang dalam kondisi baru..." 
                                                                   value="{{ $rItem->inspection_notes ?? '' }}"
                                                                   {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                        </div>

                                                        <div class="col-md-12">
                                                            <label class="form-label fw-bold mb-1">Upload Foto Bukti Fisik (Opsional):</label>
                                                            <input type="file" name="items[{{ $rItemId }}][photo]" 
                                                                   class="form-control form-control-sm" accept="image/*"
                                                                   {{ $ret->is_restocked ? 'disabled' : '' }}>
                                                            @if(!empty($rItem->inspection_photo))
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
                                                    @foreach($displayItems as $rItem)
                                                        @php
                                                            $rOrderItem = $isFallback ? $rItem : ($rItem->orderItem ?? null);
                                                            $rItemName = $rOrderItem ? ($rOrderItem->marketplaceProduct->name ?? ($rOrderItem->product_name ?? 'Barang Retur')) : 'Barang Retur';
                                                        @endphp
                                                        <li>{{ $rItem->quantity ?? 1 }}x {{ $rItemName }}</li>
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
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <div class="fw-bold" style="font-size:0.9rem;">Tidak Ada Data Retur</div>
                                <div style="font-size:0.75rem;">Belum ada pesanan retur yang sesuai dengan filter pencarian Anda.</div>
                            </td>
                        </tr>
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

@endsection
