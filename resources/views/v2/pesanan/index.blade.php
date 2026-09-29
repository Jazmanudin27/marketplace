@extends('v2.layouts.app')

@section('title', 'Pesanan Masuk V2')

@push('styles')
<style>
/* ─── Pesanan-specific overrides ─────────────────────────────────── */
.psr-tab-bar {
    display: flex;
    overflow-x: auto;
    scrollbar-width: none;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    gap: 0;
}
.psr-tab-bar::-webkit-scrollbar { display: none; }

.psr-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 18px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #6b7280;
    white-space: nowrap;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: color .15s, border-color .15s;
    position: relative;
}
.psr-tab:hover { color: #3b82f6; text-decoration: none; }
.psr-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
    font-weight: 600;
    background: #eff6ff;
}
.psr-tab .tab-badge {
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 1px 6px;
    background: #3b82f6;
    color: #fff;
    min-width: 18px;
    text-align: center;
    line-height: 1.5;
}
.psr-tab:not(.active) .tab-badge {
    background: #e5e7eb;
    color: #6b7280;
}
.psr-tab.active .tab-badge { background: #2563eb; }

/* process sub-tabs */
.psr-sub-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
}
.psr-sub-label { font-size: 0.74rem; font-weight: 600; color: #6b7280; white-space: nowrap; }
.psr-sub-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 12px;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 500;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    text-decoration: none;
    transition: all .15s;
    white-space: nowrap;
}
.psr-sub-pill:hover { border-color: #3b82f6; color: #3b82f6; text-decoration: none; }
.psr-sub-pill.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
    box-shadow: 0 2px 6px rgba(59,130,246,.3);
}
.psr-sub-pill .pill-n {
    background: rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 0 5px;
    font-size: 0.68rem;
    font-weight: 700;
}
.psr-sub-pill:not(.active) .pill-n { background: #f0f0f0; color: #555; }

/* filter bar */
.psr-filter-bar {
    padding: 10px 14px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.psr-filter-bar .form-label { font-size: 0.72rem; font-weight: 600; color: #6b7280; margin-bottom: 3px; }
.psr-filter-bar .form-control,
.psr-filter-bar .form-select {
    font-size: 0.79rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    padding: 5px 9px;
    height: 31px;
}
.psr-filter-bar .form-control:focus,
.psr-filter-bar .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59,130,246,.15);
}

/* summary bar */
.psr-summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.79rem;
    color: #6b7280;
}

/* table */
.psr-table { width: 100%; border-collapse: collapse; font-size: 0.79rem; }
.psr-table thead tr { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
.psr-table thead th {
    padding: 9px 11px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.psr-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
.psr-table tbody tr:hover { background: #f0f7ff; }
.psr-table tbody tr:last-child { border-bottom: none; }
.psr-table td { padding: 10px 11px; vertical-align: middle; color: #374151; }

/* order id */
.order-link {
    font-size: 0.78rem;
    font-weight: 700;
    color: #2563eb;
    text-decoration: none;
    font-family: 'Courier New', monospace;
}
.order-link:hover { color: #1d4ed8; text-decoration: underline; }


/* channel badges */
.ch-badge {
    font-size: 0.65rem; font-weight: 700;
    border-radius: 3px; padding: 2px 6px;
    display: inline-block; white-space: nowrap;
}
.ch-shopee   { background: linear-gradient(135deg,#ee4d2d,#ff6b35); color:#fff; }
.ch-tiktok   { background: linear-gradient(135deg,#000,#1f2937); color:#fff; }
.ch-lazada   { background: linear-gradient(135deg,#0f146d,#1a237e); color:#fff; }
.ch-tokopedia{ background: linear-gradient(135deg,#03ac0e,#10b981); color:#fff; }
.ch-offline  { background: linear-gradient(135deg,#475569,#64748b); color:#fff; }

/* status badges */
.psr-status {
    font-size: 0.68rem; font-weight: 600;
    border-radius: 999px; padding: 2px 9px;
    display: inline-block; white-space: nowrap;
}
.st-pending   { background:#fff7e6; color:#d46b08; border:1px solid #ffd591; }
.st-toship    { background:#fff2e8; color:#d4380d; border:1px solid #ffbb96; }
.st-shipped   { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.st-completed { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.st-cancelled { background:#fff1f0; color:#be123c; border:1px solid #fecdd3; }
.st-default   { background:#f9fafb; color:#6b7280; border:1px solid #e5e7eb; }

/* deadline */
.dl-overdue { background:#fff1f0; color:#be123c; border:1px solid #fecdd3; border-radius:4px; padding:2px 7px; font-size:.68rem; font-weight:600; }
.dl-urgent  { background:#fffbeb; color:#b45309; border:1px solid #fde68a; border-radius:4px; padding:2px 7px; font-size:.68rem; font-weight:600; }
.dl-safe    { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:4px; padding:2px 7px; font-size:.68rem; font-weight:600; }

/* meta badges */
.v2-meta-badge {
    font-size: 0.63rem; font-weight: 600;
    border-radius: 3px; padding: 1px 6px;
    display: inline-block;
}

/* action btn */
.psr-btn {
    font-size: 0.7rem; font-weight: 600;
    border-radius: 5px; padding: 4px 10px;
    display: inline-flex; align-items: center; gap: 4px;
    text-decoration: none; white-space: nowrap; cursor: pointer; border: none;
    transition: all .15s;
}
.psr-btn-primary   { background:#2563eb; color:#fff; }
.psr-btn-primary:hover { background:#1d4ed8; color:#fff; }
.psr-btn-outline   { background:#fff; color:#2563eb; border:1px solid #2563eb; }
.psr-btn-outline:hover { background:#eff6ff; }
.psr-btn-ghost     { background:#f9fafb; color:#374151; border:1px solid #e5e7eb; }
.psr-btn-ghost:hover { background:#f3f4f6; border-color:#9ca3af; }

/* pagination */
.psr-pagination { padding:10px 14px; border-top:1px solid #e5e7eb; background:#f9fafb; }

/* empty */
.psr-empty { padding:60px 20px; text-align:center; color:#9ca3af; }
.psr-empty i { font-size:3rem; opacity:.2; display:block; margin-bottom:12px; }

/* urgent banner */
.psr-urgent-banner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 10px 16px;
    background: linear-gradient(90deg,#eff6ff,#f0fdf4);
    border-bottom: 1px solid #bfdbfe;
}

</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-cart-check-fill text-primary fs-5"></i> Pesanan Masuk
        </h1>
        <p class="v2-page-subtitle mb-0">Monitor & kelola semua pesanan marketplace secara real-time dalam satu panel terpadu</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/pesanan') }}" class="btn btn-sm btn-v2-secondary py-1 px-2" title="Refresh">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        @can('orders.export')
        <a href="{{ route('orders.export', request()->all()) }}" class="btn btn-sm btn-v2-secondary py-1 px-3">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
        </a>
        @endcan
        <button type="submit" form="mass-print-form" class="btn btn-sm btn-v2-primary py-1 px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak Massal
        </button>
    </div>
</div>


{{-- ── Urgent Banner ── --}}
@if($toProcessCount > 0)
<div class="v2-card mb-3 p-0 overflow-hidden shadow-sm" style="border-left: 3px solid #3b82f6;">
    <div class="psr-urgent-banner">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                 style="width:36px; height:36px; background: linear-gradient(135deg,#2563eb,#3b82f6); flex-shrink:0;">
                <i class="bi bi-exclamation-circle-fill text-white" style="font-size:1rem;"></i>
            </div>
            <div>
                <div class="fw-bold text-dark" style="font-size:0.85rem;">Ada <span class="text-primary">{{ $toProcessCount }}</span> pesanan menunggu proses</div>
                <div class="text-muted" style="font-size:0.72rem;">Segera cetak resi atau proses pesanan ini agar tidak melewati batas pengiriman.</div>
            </div>
        </div>
        <a href="{{ url('/v2/pesanan?process_status=to_process') }}" class="psr-btn psr-btn-primary" style="flex-shrink:0;">
            <i class="bi bi-arrow-right-circle"></i> Proses Sekarang
        </a>
    </div>
</div>
@endif

{{-- ── Main Card ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden">

    {{-- Status Tabs --}}
    @php
        $currentStatus = request('status', '');
        $tabStatuses = [
            '' => ['label' => 'Semua', 'icon' => 'bi bi-list-ul', 'countKey' => '__all__'],
            'READY_TO_SHIP' => ['label' => 'Perlu Dikirim', 'icon' => 'bi bi-box-seam', 'countKey' => 'READY_TO_SHIP'],
            'SHIPPED'       => ['label' => 'Dikirim', 'icon' => 'bi bi-truck', 'countKey' => 'SHIPPED'],
            'COMPLETED'     => ['label' => 'Selesai', 'icon' => 'bi bi-check-circle', 'countKey' => 'COMPLETED'],
            'TO_RETURN'     => ['label' => 'Pengembalian', 'icon' => 'bi bi-arrow-counterclockwise', 'countKey' => 'TO_RETURN'],
            'CANCELLED'     => ['label' => 'Dibatalkan', 'icon' => 'bi bi-x-circle', 'countKey' => 'CANCELLED'],
        ];
    @endphp
    <div class="psr-tab-bar" role="tablist">
        @foreach($tabStatuses as $tabKey => $tabInfo)
            @php
                $tabUrl = route('v2.pesanan.index', array_merge(
                    request()->except(['status','page']),
                    $tabKey !== '' ? ['status' => $tabKey] : [],
                ));
                $isActive = $currentStatus === $tabKey;
                $count    = $tabCounts[$tabInfo['countKey']] ?? 0;
            @endphp
            <a class="psr-tab {{ $isActive ? 'active' : '' }}" href="{{ $tabUrl }}" role="tab">
                <i class="{{ $tabInfo['icon'] }}" style="font-size:.8rem;"></i>
                {{ $tabInfo['label'] }}
                @if($count > 0)
                    <span class="tab-badge">{{ $count > 999 ? '999+' : $count }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Process Sub-Tabs --}}
    @php
        $currentProcess = request('process_status', '');
        $subTabs = [
            '' => ['label' => 'Semua', 'countKey' => '__all__'],
            'to_process' => ['label' => 'Perlu Diproses', 'countKey' => 'to_process'],
            'processed'  => ['label' => 'Telah Diproses',  'countKey' => 'processed'],
        ];
    @endphp
    <div class="psr-sub-bar">
        <span class="psr-sub-label"><i class="bi bi-funnel me-1"></i>Status:</span>
        @foreach($subTabs as $ptKey => $ptInfo)
            @php
                $ptUrl    = route('v2.pesanan.index', array_merge(
                    request()->except(['process_status','page']),
                    $ptKey !== '' ? ['process_status' => $ptKey] : [],
                ));
                $ptActive = $currentProcess === $ptKey;
                $ptCount  = $processCounts[$ptInfo['countKey']] ?? 0;
            @endphp
            <a href="{{ $ptUrl }}" class="psr-sub-pill {{ $ptActive ? 'active' : '' }}">
                {{ $ptInfo['label'] }}
                @if($ptCount > 0)
                    <span class="pill-n">{{ $ptCount > 999 ? '999+' : $ptCount }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Filter Bar --}}
    <div class="psr-filter-bar">
        <form method="GET" action="{{ route('v2.pesanan.index') }}" id="psr-filter-form">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('process_status'))
                <input type="hidden" name="process_status" value="{{ request('process_status') }}">
            @endif
            <div class="row g-2 align-items-end">
                {{-- Cari --}}
                <div class="col-12 col-md-3">
                    <label class="form-label"><i class="bi bi-search me-1"></i>No. Pesanan / Resi / Pembeli</label>
                    <input type="text" name="order_number" class="form-control"
                           placeholder="Cari no. pesanan, resi, nama pembeli..."
                           value="{{ request('order_number') }}">
                </div>
                {{-- Channel --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-shop me-1"></i>Channel</label>
                    <select name="channel_id" class="form-select no-select2">
                        <option value="">Semua Channel</option>
                        @foreach($channels as $ch)
                            <option value="{{ $ch->id }}" {{ request('channel_id') == $ch->id ? 'selected' : '' }}>
                                {{ $ch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Toko --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-building me-1"></i>Toko</label>
                    <select name="store_id" class="form-select no-select2">
                        <option value="">Semua Toko</option>
                        @foreach($stores as $store)
                            @php $chName = $store->channel->name ?? ucfirst($store->channel->code ?? 'MP'); @endphp
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }} ({{ $chName }})
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Tanggal --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-calendar me-1"></i>Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                {{-- Tombol --}}
                <div class="col-12 col-md-1 d-flex gap-2">
                    <button type="submit" class="psr-btn psr-btn-primary" style="height:31px; padding:0 14px;">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->anyFilled(['channel_id','store_id','start_date','end_date','order_number']))
                        <a href="{{ route('v2.pesanan.index', request('status') ? ['status' => request('status')] : []) }}"
                           class="psr-btn psr-btn-ghost" style="height:31px; padding:0 10px;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Summary Bar --}}
    <div class="psr-summary-bar">
        <div>
            <i class="bi bi-list-ul me-1"></i>
            <strong class="text-dark">{{ $orders->total() }}</strong> Hasil Ditemukan
            @if($orders->total() > 0)
                &nbsp;·&nbsp; Halaman {{ $orders->currentPage() }} dari {{ $orders->lastPage() }}
            @endif
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="psr-btn psr-btn-ghost" id="btn-mass-ship" style="font-size:0.72rem; height:28px; padding:0 10px;">
                <i class="bi bi-truck"></i> Pengiriman Massal
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <form id="mass-print-form" action="{{ route('orders.mass_print') }}" method="POST" target="_blank">
            @csrf
            <table class="psr-table">
                <thead>
                    <tr>
                        <th style="width:36px; text-align:center;">
                            <input type="checkbox" id="check-all" class="form-check-input" style="cursor:pointer;">
                        </th>
                        <th>PRODUK &amp; PESANAN</th>
                        <th>TOKO &amp; CHANNEL</th>
                        <th style="text-align:right;">DIBAYAR</th>
                        <th>TANGGAL &amp; BATAS KIRIM</th>
                        <th>JASA KIRIM &amp; RESI</th>
                        <th style="text-align:center;">STATUS</th>
                        <th style="text-align:center;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $channelCode = strtolower($order->store?->channel?->code ?? '');
                            $channelName = $order->store?->channel?->name ?? 'Offline';
                            $chBadgeClass = match(true) {
                                str_contains($channelCode, 'shopee')    => 'ch-shopee',
                                str_contains($channelCode, 'tiktok')    => 'ch-tiktok',
                                str_contains($channelCode, 'lazada')    => 'ch-lazada',
                                str_contains($channelCode, 'tokopedia') => 'ch-tokopedia',
                                default => 'ch-offline',
                            };
                            $chIcon = match(true) {
                                str_contains($channelCode, 'shopee')    => 'bi bi-bag-fill',
                                str_contains($channelCode, 'tiktok')    => 'bi bi-camera-video-fill',
                                str_contains($channelCode, 'lazada')    => 'bi bi-shop',
                                str_contains($channelCode, 'tokopedia') => 'bi bi-cart-fill',
                                default => 'bi bi-shop',
                            };

                            $orderStatusUp = strtoupper($order->order_status ?? '');
                            $stBadgeClass = match(true) {
                                in_array($orderStatusUp, ['UNPAID','PENDING'])                         => 'st-pending',
                                in_array($orderStatusUp, ['READY_TO_SHIP','TO_SHIP','PROCESSED','PROCESSING','PROSES','RETRY_SHIP','TO_RETRY_LOGISTICS']) => 'st-toship',
                                in_array($orderStatusUp, ['SHIPPED','IN_TRANSIT','TO_RECEIVE','TO_CONFIRM_RECEIVE','DELIVERED']) => 'st-shipped',
                                in_array($orderStatusUp, ['COMPLETED','FINISHED','SELESAI'])           => 'st-completed',
                                in_array($orderStatusUp, ['CANCELLED','BATAL','IN_CANCEL'])            => 'st-cancelled',
                                default => 'st-default',
                            };

                        @endphp
                        <tr>
                            {{-- Checkbox --}}
                            <td style="text-align:center;">
                                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                    class="order-checkbox form-check-input" style="cursor:pointer;"
                                    data-order-number="{{ $order->invoice_number ?? ($order->order_marketplace_id ?? '#'.$order->id) }}"
                                    data-tracking="{{ $order->tracking_number ?? '' }}">
                            </td>

                            {{-- Produk & Pesanan --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <a href="#" class="order-link psr-detail-trigger"
                                       data-order-id="{{ $order->id }}"
                                       data-url="{{ route('orders.show', $order->id) }}?modal=1">
                                        {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                    </a>
                                    {{-- Meta badges --}}
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        @if($order->is_dropship)
                                            <span class="v2-meta-badge" style="background:#fff7e6;color:#d46b08;border:1px solid #ffd591;">Dropship</span>
                                        @endif
                                        @if($order->hasPreorderItems())
                                            <span class="v2-meta-badge" style="background:#f5f3ff;color:#7c3aed;border:1px solid #c4b5fd;">
                                                <i class="bi bi-clock me-1"></i>PO
                                            </span>
                                        @else
                                            <span class="v2-meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;">
                                                <i class="bi bi-check-circle-fill me-1"></i>Ready
                                            </span>
                                        @endif
                                        @if($order->spks && $order->spks->isNotEmpty())
                                            <a href="{{ route('spks.show', $order->spks->first()->id) }}"
                                               class="v2-meta-badge text-decoration-none"
                                               style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                                <i class="bi bi-tools me-1"></i>{{ $order->spks->first()->no_spk }}
                                            </a>
                                        @else
                                            <span class="v2-meta-badge" style="background:#f9fafb;color:#9ca3af;border:1px solid #e5e7eb;">
                                                <i class="bi bi-dash-circle me-1"></i>Belum SPK
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Toko & Channel --}}
                            <td>
                                <div style="font-size:0.8rem; font-weight:600; color:#111827; margin-bottom:4px;">
                                    {{ $order->store->store_name ?? '-' }}
                                </div>
                                <span class="ch-badge {{ $chBadgeClass }}">
                                    <i class="{{ $chIcon }} me-1"></i>{{ $channelName }}
                                </span>
                            </td>

                            {{-- Dibayar --}}
                            <td style="text-align:right;">
                                <div style="font-size:0.83rem; font-weight:700; color:#111827; font-family:'Courier New',monospace;">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </div>
                            </td>

                            {{-- Tanggal & Batas Kirim --}}
                            <td>
                                <div style="font-size:0.75rem; color:#6b7280;">
                                    <div class="mb-1">
                                        <i class="bi bi-calendar-event text-secondary me-1"></i>
                                        <span style="font-weight:600;">{{ $order->order_date ? $order->order_date->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                    @if($order->ship_before_date)
                                        <div class="mb-1">
                                            <span style="color:#9ca3af;">Batas:</span>
                                            <span style="font-weight:700; color:#111827; font-family:monospace;">
                                                {{ $order->ship_before_date->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                        @if(!in_array($orderStatusUp, ['SHIPPED','DELIVERED','COMPLETED','FINISHED','CANCELLED','SELESAI','BATAL','IN_CANCEL']))
                                            @if($order->is_ship_overdue)
                                                <span class="dl-overdue"><i class="bi bi-exclamation-circle me-1"></i>Overdue</span>
                                            @elseif($order->is_ship_urgent)
                                                <span class="dl-urgent"><i class="bi bi-clock me-1"></i>{{ $order->ship_before_date->diffForHumans() }}</span>
                                            @else
                                                <span class="dl-safe"><i class="bi bi-check-circle me-1"></i>{{ $order->ship_before_date->diffForHumans() }}</span>
                                            @endif
                                        @endif
                                    @endif
                                    @if($order->completed_at && in_array($orderStatusUp, ['COMPLETED','FINISHED','SELESAI','DELIVERED']))
                                        <div class="mt-1" style="font-size:0.68rem; color:#15803d; font-weight:600;">
                                            <i class="bi bi-check2-all me-1"></i>Cair:
                                            <span style="font-family:monospace;">{{ $order->completed_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Jasa Kirim & Resi --}}
                            <td>
                                <div style="font-size:0.75rem; color:#6b7280;">
                                    <div style="font-weight:600; color:#111827; margin-bottom:3px;">
                                        <i class="bi bi-truck text-secondary me-1"></i>{{ $order->courier ?? '—' }}
                                    </div>
                                    @if(!empty($order->tracking_number))
                                        <div style="font-family:monospace; font-size:0.68rem;" title="Nomor Resi">
                                            <i class="bi bi-upc-scan text-secondary me-1"></i>
                                            <span style="font-weight:600; color:#111827;">{{ $order->tracking_number }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size:0.65rem;" title="Resi otomatis ditarik saat cetak">
                                            <i class="bi bi-lightning-charge text-warning me-1"></i>Otomatis saat cetak
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Status --}}
                            <td style="text-align:center;">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span class="psr-status {{ $stBadgeClass }}">
                                        {{ str_replace('_', ' ', $order->order_status) }}
                                    </span>
                                    @if($order->order_status === 'CANCELLED' && $order->cancel_reason)
                                        <div class="text-danger text-truncate" style="max-width:100px; font-size:0.6rem;"
                                             title="{{ $order->cancel_reason }}">
                                            {{ $order->cancel_reason }}
                                        </div>
                                    @endif

                                    @if($order->order_status !== 'CANCELLED')
                                        {{-- Print Badge --}}
                                        @if($order->is_printed && !empty(trim($order->tracking_number ?? '')) && trim($order->tracking_number) !== '-')
                                            <span class="v2-meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;"
                                                  title="{{ $order->printed_at ? 'Print: '.$order->printed_at->format('d/m/Y H:i') : '' }}">
                                                <i class="bi bi-printer-fill me-1"></i>Sudah Print
                                            </span>
                                        @else
                                            <span class="v2-meta-badge" style="background:#f9fafb;color:#9ca3af;border:1px solid #e5e7eb;">
                                                <i class="bi bi-printer me-1"></i>Belum Print
                                            </span>
                                        @endif

                                        {{-- Kemas Badge --}}
                                        @if($order->packing_status === 'verified')
                                            <span class="v2-meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;"
                                                  title="{{ $order->packed_at ? 'Kemas: '.$order->packed_at->format('d/m/Y H:i') : '' }}">
                                                <i class="bi bi-patch-check-fill me-1"></i>Verified
                                            </span>
                                        @elseif($order->packing_status === 'packing')
                                            <span class="v2-meta-badge" style="background:#fffbe6;color:#b45309;border:1px solid #fde68a;">
                                                <i class="bi bi-box2-heart me-1"></i>Packing
                                            </span>
                                        @else
                                            <span class="v2-meta-badge" style="background:#f9fafb;color:#d1d5db;border:1px solid #f3f4f6;">
                                                <i class="bi bi-hourglass me-1"></i>Menunggu
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td style="text-align:center;">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if(!in_array($orderStatusUp, ['UNPAID','PENDING','CANCELLED','BATAL','IN_CANCEL']))
                                        <a href="{{ route('orders.print', $order->id) }}" target="_blank"
                                           class="psr-btn psr-btn-outline" title="Cetak Resi / Label Pengiriman">
                                            <i class="bi bi-printer"></i> Cetak Resi
                                        </a>
                                    @endif
                                    @if(in_array($orderStatusUp, ['SHIPPED','DELIVERED','COMPLETED','FINISHED','SELESAI']))
                                        <span class="v2-meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0; padding:4px 8px;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Sudah Kirim
                                        </span>
                                    @elseif(in_array($orderStatusUp, ['UNPAID','PENDING','CANCELLED','BATAL','IN_CANCEL']))
                                        <span class="text-muted" style="font-size:0.7rem;">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="psr-empty">
                                    <i class="bi bi-basket"></i>
                                    <p style="font-size:0.9rem; margin:0; font-weight:600;">Tidak ada pesanan ditemukan.</p>
                                    <p style="font-size:0.78rem; color:#d1d5db; margin-top:4px;">Coba ubah filter atau tab status di atas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </form>

        <form id="single-tracking-form" action="" method="POST" class="d-none">@csrf</form>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
        <div class="psr-pagination">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>{{-- end v2-card --}}

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll  = document.getElementById('check-all');
    const checkboxes = document.querySelectorAll('.order-checkbox');
    const form      = document.getElementById('mass-print-form');
    const btnShip   = document.getElementById('btn-mass-ship');

    /* ── Check All ── */
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
        });
    }

    /* ── Validasi Cetak Massal ── */
    if (form) {
        form.addEventListener('submit', function (e) {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Pesanan',
                    text: 'Pilih minimal satu pesanan dengan mencentang kotak untuk dicetak.',
                    confirmButtonColor: '#2563eb'
                });
                return false;
            }
        });
    }

    /* ── Pengiriman Massal ── */
    if (btnShip) {
        btnShip.addEventListener('click', function () {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) {
                Swal.fire('Pilih Pesanan', 'Pilih minimal satu pesanan dengan mencentang checkbox.', 'warning');
                return;
            }
            Swal.fire({
                title: 'Kirim Pesanan Massal?',
                text: `Anda akan memproses pengiriman untuk ${checked.length} pesanan terpilih ke Marketplace.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim Sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb'
            }).then(res => {
                if (res.isConfirmed) {
                    form.action = "{{ route('orders.mass_ship') }}";
                    form.removeAttribute('target');
                    form.submit();
                }
            });
        });
    }

    /* ── Tooltips ── */
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => new bootstrap.Tooltip(el));

    /* ── Modal Detail Pesanan ── */
    const detailModal    = new bootstrap.Modal(document.getElementById('psrDetailModal'));
    const detailBody     = document.getElementById('psrDetailBody');
    const detailTitle    = document.getElementById('psrDetailTitle');
    const detailSpinner  = document.getElementById('psrDetailSpinner');

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.psr-detail-trigger');
        if (!trigger) return;
        e.preventDefault();

        const url   = trigger.dataset.url;
        const label = trigger.textContent.trim();

        detailTitle.textContent = label;
        detailBody.innerHTML    = '';
        detailSpinner.classList.remove('d-none');
        detailModal.show();

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                detailSpinner.classList.add('d-none');
                detailBody.innerHTML = html;
            })
            .catch(() => {
                detailSpinner.classList.add('d-none');
                detailBody.innerHTML = '<div class="text-center text-danger py-4"><i class="bi bi-exclamation-circle fs-3 d-block mb-2"></i>Gagal memuat detail pesanan.</div>';
            });
    });
});
</script>

{{-- ── Modal Detail Pesanan ── --}}
<div class="modal fade" id="psrDetailModal" tabindex="-1" aria-labelledby="psrDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:10px; overflow:hidden;">
            <div class="modal-header py-2 px-3" style="background:linear-gradient(135deg,#1e3a5f,#2563eb); border:none;">
                <h6 class="modal-title text-white fw-bold d-flex align-items-center gap-2 mb-0" style="font-size:0.88rem;">
                    <i class="bi bi-receipt"></i>
                    <span id="psrDetailTitle">Detail Pesanan</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="min-height:200px;">
                <div id="psrDetailSpinner" class="d-flex align-items-center justify-content-center py-5">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width:2rem;height:2rem;"></div>
                        <div class="text-muted" style="font-size:0.8rem;">Memuat detail pesanan...</div>
                    </div>
                </div>
                <div id="psrDetailBody"></div>
            </div>
        </div>
    </div>
</div>

@endpush
