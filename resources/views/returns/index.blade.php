@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')
<style>
    /* ── Shopee Color Palette ── */
    :root {
        --shopee-orange: #ee4d2d;
        --shopee-orange-hover: #d73211;
        --shopee-orange-light: #fff4f2;
        --shopee-border: #e5e7eb;
        --shopee-bg: #f5f5f5;
        --shopee-text: #333333;
        --shopee-muted: #888888;
    }

    /* ── Page Header ── */
    .shopee-page-header {
        background: #fff;
        border-bottom: 1px solid var(--shopee-border);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .shopee-page-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--shopee-text);
        letter-spacing: -0.01em;
    }

    /* ── Main Card ── */
    .shopee-card {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    /* ── Status Tabs ── */
    .shopee-tabs {
        display: flex;
        border-bottom: 1px solid var(--shopee-border);
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        background: #fff;
    }

    .shopee-tabs::-webkit-scrollbar {
        display: none;
    }

    .shopee-tab {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 14px 20px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #555;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        text-decoration: none;
        transition: color 0.15s, border-color 0.15s;
        position: relative;
    }

    .shopee-tab:hover {
        color: var(--shopee-orange);
        text-decoration: none;
    }

    .shopee-tab.active {
        color: var(--shopee-orange);
        border-bottom-color: var(--shopee-orange);
        font-weight: 600;
    }

    .shopee-tab .tab-count {
        background: var(--shopee-orange);
        color: #fff;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 1px 6px;
        min-width: 18px;
        text-align: center;
        line-height: 1.4;
    }

    .shopee-tab:not(.active) .tab-count {
        background: #ddd;
        color: #666;
    }

    /* ── Filter Bar ── */
    .shopee-filter-bar {
        padding: 12px 16px;
        border-bottom: 1px solid var(--shopee-border);
        background: #fafafa;
    }

    .shopee-filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .shopee-filter-group label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #555;
        white-space: nowrap;
        margin: 0;
    }

    .shopee-filter-group .form-control,
    .shopee-filter-group .form-select {
        font-size: 0.8rem;
        border-radius: 3px;
        border: 1px solid #d1d5db;
        padding: 5px 10px;
        height: 32px;
        color: var(--shopee-text);
        background: #fff;
    }

    .shopee-filter-group .form-control:focus,
    .shopee-filter-group .form-select:focus {
        border-color: var(--shopee-orange);
        box-shadow: 0 0 0 2px rgba(238, 77, 45, 0.12);
        outline: none;
    }

    /* ── Buttons ── */
    .btn-shopee-primary {
        background: var(--shopee-orange);
        color: #fff;
        border: none;
        border-radius: 3px;
        padding: 5px 18px;
        font-size: 0.82rem;
        font-weight: 600;
        height: 32px;
        cursor: pointer;
        transition: background 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-shopee-primary:hover {
        background: var(--shopee-orange-hover);
        color: #fff;
        text-decoration: none;
    }

    .btn-shopee-ghost {
        background: transparent;
        color: #555;
        border: 1px solid #d1d5db;
        border-radius: 3px;
        padding: 4px 14px;
        font-size: 0.82rem;
        height: 32px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-shopee-ghost:hover {
        border-color: #999;
        color: #333;
        text-decoration: none;
    }

    /* ── Summary Bar ── */
    .shopee-summary-bar {
        padding: 10px 16px;
        border-bottom: 1px solid var(--shopee-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }

    .shopee-summary-bar .results-count {
        font-size: 0.82rem;
        color: var(--shopee-muted);
        font-weight: 500;
    }

    .shopee-summary-bar .results-count strong {
        color: var(--shopee-text);
    }

    /* ── Table ── */
    .shopee-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .shopee-table thead tr {
        background: #fafafa;
        border-bottom: 1px solid var(--shopee-border);
    }

    .shopee-table thead th {
        padding: 10px 12px;
        font-weight: 600;
        color: #666;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
        border-bottom: 1px solid var(--shopee-border);
    }

    .shopee-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.1s;
    }

    .shopee-table tbody tr:hover {
        background: #fffbf9;
    }

    .shopee-table td {
        padding: 12px 12px;
        vertical-align: middle;
        color: var(--shopee-text);
    }

    /* ── Badges & Other Elements ── */
    .badge-shopee {
        background-color: #FF5722 !important;
        color: #fff !important;
    }
    .badge-tiktok {
        background-color: #000000 !important;
        color: #fff !important;
        box-shadow: inset 0 0 0 1px #ff0050;
    }
    .badge-lazada {
        background-color: #0f146d !important;
        color: #fff !important;
    }
    .badge-tokopedia {
        background-color: #42b549 !important;
        color: #fff !important;
    }
    @keyframes pulse-red {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .pulse-danger-dot {
        width: 6px;
        height: 6px;
        background-color: #ef4444;
        border-radius: 50%;
        display: inline-block;
        animation: pulse-red 1.6s infinite;
    }
    .copy-btn {
        color: #6c757d;
        transition: color 0.15s ease-in-out;
    }
    .copy-btn:hover {
        color: var(--shopee-orange);
    }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

    {{-- Page Header --}}
    <div class="shopee-page-header">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-undo-alt text-primary me-2"></i>Pusat Resolusi & Retur</h5>
        </div>
        <div class="header-actions">
            <button type="submit" form="syncForm" class="btn btn-success btn-sm fw-semibold">
                <i class="fas fa-sync-alt me-1"></i> Tarik Data
            </button>
        </div>
    </div>

    {{-- Sync Form --}}
    <form id="syncForm" action="{{ route('returns.sync') }}" method="POST" class="d-none">
        @csrf
    </form>

    {{-- Alert Messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Main Shopee Card --}}
    <div class="shopee-card">
        
        {{-- Status Tabs --}}
        <div class="shopee-tabs">
            <a class="shopee-tab {{ is_null($status) && is_null($isRestocked) ? 'active' : '' }}" href="{{ route('returns.index') }}">
                Semua
                <span class="tab-count">{{ $totalReturns }}</span>
            </a>
            <a class="shopee-tab {{ $isRestocked === '0' ? 'active' : '' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['is_restocked' => '0', 'status' => null])) }}">
                Dalam Pengecekan (Belum QC)
                <span class="tab-count">{{ $pendingQc }}</span>
            </a>
            <a class="shopee-tab {{ $isRestocked === '1' ? 'active' : '' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['is_restocked' => '1', 'status' => null])) }}">
                Sudah QC (Selesai QC)
                <span class="tab-count">{{ $alreadyQc }}</span>
            </a>
            <a class="shopee-tab {{ $status === 'REQUESTED' ? 'active' : '' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['status' => 'REQUESTED', 'is_restocked' => null])) }}">
                Pengajuan Baru
                <span class="tab-count">{{ $newRequested }}</span>
            </a>
            <a class="shopee-tab {{ $status === 'CLOSED' || $status === 'COMPLETED' ? 'active' : '' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['status' => 'CLOSED', 'is_restocked' => null])) }}">
                Selesai
                <span class="tab-count">{{ $completedClosed }}</span>
            </a>
        </div>

        {{-- Filter Bar --}}
        <div class="shopee-filter-bar">
            <form method="GET" action="{{ route('returns.index') }}" class="mb-0">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if (request('is_restocked') !== null && request('is_restocked') !== '')
                    <input type="hidden" name="is_restocked" value="{{ request('is_restocked') }}">
                @endif
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-2">
                        <div class="shopee-filter-group">
                            <label><i class="fas fa-shopping-bag me-1 text-muted"></i>Channel</label>
                            <select name="channel_id" class="form-select">
                                <option value="">Semua Channel</option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->id }}" {{ $channelId == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <div class="shopee-filter-group">
                            <label><i class="fas fa-store me-1 text-muted"></i>Toko</label>
                            <select name="store_id" class="form-select">
                                <option value="">Semua Toko</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <div class="shopee-filter-group">
                            <label><i class="fas fa-info-circle me-1 text-muted"></i>Status Retur</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                @foreach ($statuses as $statVal)
                                    <option value="{{ $statVal }}" {{ $status == $statVal ? 'selected' : '' }}>
                                        {{ $statVal }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <div class="shopee-filter-group">
                            <label><i class="fas fa-search me-1 text-muted"></i>Cari Resi / Invoice</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="Resi / Invoice..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn-shopee-primary">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                        @if ($search || $channelId || $storeId || $status || ($isRestocked !== null && $isRestocked !== ''))
                            <a href="{{ route('returns.index') }}" class="btn-shopee-ghost">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Summary Bar --}}
        <div class="shopee-summary-bar">
            <div class="results-count">
                <i class="fas fa-list-ul me-1"></i>
                <strong>{{ $returns->total() }}</strong> Pengajuan Retur Ditemukan
                @if ($returns->total() > 0)
                    &nbsp;·&nbsp; Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="shopee-table">
                <thead>
                    <tr style="border-bottom: 2px solid #dee2e6;">
                        <th class="ps-3 py-3" style="width: 35%;">Produk</th>
                        <th class="py-3 text-center" style="width: 15%;">Jumlah Pengembalian Dana</th>
                        <th class="py-3 text-center" style="width: 20%;">Alasan & Status</th>
                        <th class="py-3 text-center" style="width: 15%;">Tindakan QC / Gudang</th>
                        <th class="py-3 text-center" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        @php
                            $channelCode = strtolower($ret->store->channel->code ?? '');
                            $badgeClass = 'bg-secondary';
                            if ($channelCode === 'shopee') {
                                $badgeClass = 'badge-shopee';
                            } elseif ($channelCode === 'tiktok') {
                                $badgeClass = 'badge-tiktok';
                            } elseif ($channelCode === 'lazada') {
                                $badgeClass = 'badge-lazada';
                            } elseif ($channelCode === 'tokopedia') {
                                $badgeClass = 'badge-tokopedia';
                            }
                        @endphp
                        
                        <!-- Group Header (Buyer info, Store info, Invoice links, No. Pengajuan) -->
                        <tr style="background-color: var(--shopee-orange-light); border-top: 1px solid var(--shopee-border); border-bottom: 1px solid var(--shopee-border);">
                            <td colspan="5" class="py-2 px-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $badgeClass }} px-2 py-1 font-monospace" style="font-size: 0.7rem;">
                                            <i class="fas fa-shopping-bag me-1"></i>{{ strtoupper($ret->store->channel->name ?? 'Marketplace') }}
                                        </span>
                                        <span class="text-secondary small fw-bold">
                                            <i class="fas fa-store me-1"></i>{{ $ret->store->store_name }}
                                        </span>
                                        <span class="text-muted">|</span>
                                        <span class="fw-bold text-dark small">
                                            <i class="fas fa-user me-1 text-muted text-secondary"></i>{{ $ret->order->buyer_name ?? '-' }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap align-items-center gap-3 font-monospace small" style="font-size: 0.78rem;">
                                        <div>
                                            <span class="text-muted">No. Pengajuan:</span>
                                            <span class="fw-bold text-dark">{{ $ret->return_sn }}</span>
                                            <button type="button" class="btn btn-link btn-xs p-0 text-muted copy-btn" data-clipboard-text="{{ $ret->return_sn }}" title="Salin No. Pengajuan">
                                                <i class="far fa-copy ms-1"></i>
                                            </button>
                                        </div>
                                        <div>
                                            <span class="text-muted">No. Pesanan:</span>
                                            <a href="{{ route('orders.show', $ret->order->id) }}" class="text-primary fw-bold text-decoration-none">
                                                {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                            </a>
                                            <button type="button" class="btn btn-link btn-xs p-0 text-muted copy-btn" data-clipboard-text="{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}" title="Salin No. Pesanan">
                                                <i class="far fa-copy ms-1"></i>
                                            </button>
                                        </div>
                                        @if ($ret->return_tracking_number)
                                            <div>
                                                <span class="text-muted">Resi Retur:</span>
                                                <span class="fw-bold text-secondary">{{ $ret->return_tracking_number }}</span>
                                                @if ($ret->shipping_provider)
                                                    <span class="text-muted">({{ $ret->shipping_provider }})</span>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            <i class="far fa-clock me-1"></i>{{ $ret->created_at->format('d M Y, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Group Body -->
                        <tr style="border-bottom: 2px solid #dee2e6;">
                            <!-- Column 1: Produk -->
                            <td class="ps-3 py-3 align-top">
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($ret->items as $rItem)
                                        @php
                                            $orderItem = $rItem->orderItem;
                                            $mpProduct = $orderItem->marketplaceProduct ?? null;
                                            $imgUrl = $orderItem->product_image ?? null;
                                            $sku = $orderItem->sku ?? $mpProduct->sku ?? '—';
                                            $variant = $orderItem->variant_name ?? '—';
                                        @endphp
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="position-relative border rounded overflow-hidden" style="width: 54px; height: 54px; flex-shrink: 0; background-color: #f8f9fa;">
                                                @if($imgUrl)
                                                    <img src="{{ $imgUrl }}" alt="Product Image" style="width: 100%; height: 100%; object-fit: cover;">
                                                @else
                                                    <div class="d-flex align-items-center justify-content-center h-100 w-100 text-muted" style="background-color: #f3f4f6;">
                                                        <i class="fas fa-box fs-5"></i>
                                                    </div>
                                                @endif
                                                <span class="position-absolute bottom-0 end-0 bg-dark bg-opacity-75 text-white px-1.5 py-0.25 small font-monospace fw-bold" style="font-size: 0.65rem; border-top-left-radius: 4px;">
                                                    x{{ $rItem->quantity }}
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="text-dark fw-bold text-truncate-2 small" style="font-size: 0.82rem; line-height: 1.3;">
                                                    {{ $mpProduct ? $mpProduct->name : ($orderItem->product_name ?? 'Item Tidak Ditemukan') }}
                                                </div>
                                                <div class="text-muted mt-1 d-flex flex-wrap align-items-center gap-2" style="font-size: 0.72rem;">
                                                    @if($sku && $sku !== '—')
                                                        <span>SKU: <span class="fw-medium text-secondary">{{ $sku }}</span></span>
                                                    @endif
                                                    @if($variant && $variant !== '—')
                                                        <span class="badge bg-light text-dark border px-1.5 py-0.5" style="font-size: 0.65rem;">Variasi: {{ $variant }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            
                            <!-- Column 2: Jumlah Pengembalian Dana -->
                            <td class="text-center align-top py-3">
                                @if ($ret->refund_amount > 0)
                                    <div class="text-success fw-bold font-monospace" style="font-size: 0.95rem;">
                                        Rp {{ number_format($ret->refund_amount, 0, ',', '.') }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.68rem;">Pengembalian Dana</div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            
                            <!-- Column 3: Alasan & Status -->
                            <td class="align-top py-3 px-3">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small fw-bold">
                                        {{ strtoupper($ret->status) }}
                                    </span>
                                    
                                    <div class="small text-muted text-center mt-2 p-2 rounded bg-light border border-light-subtle" style="font-size: 0.75rem; max-width: 220px; line-height: 1.4;">
                                        <i class="fas fa-quote-left text-muted me-1 small"></i>
                                        <span>{{ $ret->reason ?? 'Tidak ada alasan' }}</span>
                                        <i class="fas fa-quote-right text-muted ms-1 small"></i>
                                    </div>
                                    
                                    @php $hasProof = false; @endphp
                                    @foreach ($ret->items as $rItem)
                                        @if($rItem->inspection_photo)
                                            @php $hasProof = true; @endphp
                                        @endif
                                    @endforeach
                                    @if($hasProof)
                                        <div class="mt-2 text-center">
                                            <span class="text-muted small d-block mb-1" style="font-size: 0.68rem;">Bukti Foto QC:</span>
                                            <div class="d-flex gap-1 justify-content-center">
                                                @foreach ($ret->items as $rItem)
                                                    @if($rItem->inspection_photo)
                                                        <a href="{{ asset($rItem->inspection_photo) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-1.5" title="Lihat Foto Bukti QC">
                                                            <i class="fas fa-camera me-1"></i>Foto
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(!$ret->is_restocked && $ret->sla_deadline)
                                        @php
                                            $diffInHours = round(now()->diffInHours($ret->sla_deadline, false));
                                        @endphp
                                        <div class="mt-2 text-center">
                                            @if($diffInHours < 0)
                                                <span class="badge bg-danger text-white px-2 py-1" style="font-size: 0.7rem;">
                                                    <i class="fas fa-hourglass-end me-1"></i>SLA Habis
                                                </span>
                                            @elseif($diffInHours <= 24)
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.7rem;" title="Deadline Respons Retur">
                                                    <span class="pulse-danger-dot me-1"></span>Sisa: {{ (int) $diffInHours }} Jam
                                                </span>
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2 py-1" style="font-size: 0.7rem;" title="Deadline Respons Retur">
                                                    <i class="fas fa-clock me-1"></i>Sisa: {{ round($diffInHours / 24) }} Hari
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Column 4: Tindakan QC / Gudang -->
                            <td class="text-center align-top py-3">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    @if ($ret->is_restocked)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5 small mb-1 fw-bold">
                                            <i class="fas fa-clipboard-check me-1"></i>Sudah QC
                                        </span>
                                        @if ($ret->checkedBy)
                                            <span class="small text-muted font-monospace" style="font-size: 0.7rem;">
                                                <i class="fas fa-user-check me-1"></i>{{ $ret->checkedBy->name }}
                                            </span>
                                        @endif
                                        <span class="small text-muted font-monospace mt-1" style="font-size: 0.68rem;">
                                            {{ $ret->updated_at->format('d M Y, H:i') }}
                                        </span>
                                        
                                        @foreach($ret->items as $rItem)
                                            <div class="mt-1.5 d-flex flex-column align-items-center gap-0.5">
                                                @if ($rItem->inspection_status === 'GOOD')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                                                        <i class="fas fa-check-circle me-0.5"></i>Layak Jual
                                                    </span>
                                                @elseif ($rItem->inspection_status === 'DEFECTIVE')
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                                                        <i class="fas fa-times-circle me-0.5"></i>Rusak/Cacat
                                                    </span>
                                                @endif
                                                @if ($rItem->inspection_notes)
                                                    <span class="text-muted fst-italic text-wrap text-center" style="font-size: 0.65rem; max-width: 140px;">"{{ $rItem->inspection_notes }}"</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2.5 py-1.5 small mb-1 fw-bold">
                                            <i class="fas fa-hourglass-half me-1"></i>Belum QC
                                        </span>
                                        <span class="small text-muted text-center" style="font-size: 0.68rem; line-height: 1.3;">Perlu pemeriksaan<br>fisik barang</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Column 5: Aksi -->
                            <td class="text-center align-top py-3">
                                <div class="d-flex flex-column gap-2 align-items-center">
                                    @if ($ret->is_restocked)
                                        @if($ret->replacement_order_id)
                                            <div class="p-2 border rounded bg-light" style="font-size: 0.72rem; min-width: 130px; line-height: 1.3;">
                                                <span class="text-muted d-block mb-1" style="font-size: 0.65rem;">Order Pengganti:</span>
                                                <a href="{{ route('orders.show', $ret->replacement_order_id) }}" class="text-primary fw-bold text-decoration-none">
                                                    <i class="fas fa-external-link-alt me-1"></i>{{ $ret->replacementOrder->invoice_number ?? 'Lihat Order' }}
                                                </a>
                                            </div>
                                        @else
                                            <form action="{{ route('returns.replacement', $ret->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-xs fw-semibold px-2 py-1" style="font-size: 0.7rem;" onclick="return confirm('Apakah Anda yakin ingin membuat pesanan pengganti gratis untuk retur ini?')">
                                                    <i class="fas fa-exchange-alt me-1"></i>Kirim Pengganti
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm w-100" style="max-width: 130px;"
                                            data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                            <i class="fas fa-clipboard-check me-1"></i>Terima & QC
                                        </button>
                                    @endif
                                    <a href="{{ route('orders.show', $ret->order->id) }}" class="btn btn-outline-secondary btn-xs fw-semibold px-2 py-1 mt-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-eye me-1"></i>Lihat Order
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-5 text-muted">
                                <i class="fas fa-box-open fs-1 opacity-25 mb-3 d-block"></i>
                                Belum ada data barang retur.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($returns->hasPages())
            <div class="shopee-pagination d-flex justify-content-center">
                {{ $returns->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- Render QC Modals outside table for valid DOM structure --}}
    @foreach ($returns as $ret)
        @if (!$ret->is_restocked)
            <!-- Modal QC -->
            <div class="modal fade text-start" id="qcModal-{{ $ret->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold text-dark" id="qcModalLabel-{{ $ret->id }}">
                                <i class="fas fa-undo-alt text-primary me-2"></i>QC Retur: {{ $ret->return_sn }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('returns.restock', $ret->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.78rem;">
                                    <i class="fas fa-info-circle me-1"></i> Periksa fisik masing-masing produk di bawah ini, unggah foto bukti fisik, dan tentukan kelayakannya untuk dikembalikan ke stok aktif gudang.
                                </div>
                                
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($ret->items as $rItem)
                                        @php 
                                            $orderItem = $rItem->orderItem;
                                            $mpProduct = $orderItem ? $orderItem->marketplaceProduct : null;
                                            $itemName = $mpProduct ? $mpProduct->name : ($orderItem->product_name ?? 'Item Tidak Ditemukan');
                                        @endphp
                                        <div class="border rounded p-3 bg-light bg-opacity-50">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fas fa-box text-muted"></i>
                                                <span class="badge bg-secondary">{{ $rItem->quantity }} Pcs</span>
                                                <span class="fw-semibold small text-dark">{{ $itemName }}</span>
                                            </div>
                                            
                                            <div class="row g-2">
                                                <div class="col-12 col-md-5">
                                                    <label class="form-label fw-semibold small mb-1">Hasil Inspeksi / Kondisi:</label>
                                                    <select name="items[{{ $rItem->id }}][inspection_status]" class="form-select form-select-sm" required>
                                                        <option value="GOOD">Layak Jual / Good (Masuk Stok)</option>
                                                        <option value="DEFECTIVE">Rusak / Defective (Abaikan Stok)</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-7">
                                                    <label class="form-label fw-semibold small mb-1">Catatan (Opsional):</label>
                                                    <input type="text" name="items[{{ $rItem->id }}][inspection_notes]" class="form-control form-control-sm" placeholder="Contoh: Plastik terbuka, mulus...">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small mb-1 mt-1">Unggah Foto Bukti QC (Opsional):</label>
                                                    <input type="file" name="items[{{ $rItem->id }}][photo]" class="form-control form-control-sm" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                                    <i class="fas fa-check me-1"></i>Simpan Hasil QC
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const text = this.getAttribute('data-clipboard-text');
                navigator.clipboard.writeText(text).then(() => {
                    const icon = this.querySelector('i');
                    icon.className = 'fas fa-check text-success';
                    setTimeout(() => {
                        icon.className = 'far fa-copy';
                    }, 1500);
                });
            });
        });
    });
</script>
@endsection
