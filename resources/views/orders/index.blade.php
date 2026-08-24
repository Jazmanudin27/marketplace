@extends('layouts.app')
@section('title', 'Daftar Pesanan')
@section('page-title', 'Manajemen Pesanan')
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

    /* ── Page Wrapper ── */
    .shopee-page {
        background: var(--shopee-bg);
        min-height: 100vh;
        padding: 0;
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
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .shopee-page-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--shopee-text);
        letter-spacing: -0.01em;
    }
    .shopee-page-header .header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    /* ── Main Card ── */
    .shopee-card {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
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
    .shopee-tabs::-webkit-scrollbar { display: none; }
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
    .shopee-filter-bar .filter-row {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        flex-wrap: wrap;
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
        box-shadow: 0 0 0 2px rgba(238,77,45,0.12);
        outline: none;
    }
    .shopee-filter-group.w-order { width: 200px; }
    .shopee-filter-group.w-select { width: 150px; }
    .shopee-filter-group.w-date { width: 130px; }

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
    .btn-shopee-outline {
        background: #fff;
        color: var(--shopee-orange);
        border: 1px solid var(--shopee-orange);
        border-radius: 3px;
        padding: 4px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        height: 32px;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn-shopee-outline:hover {
        background: var(--shopee-orange-light);
        color: var(--shopee-orange);
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
    .shopee-table tbody tr:last-child {
        border-bottom: none;
    }
    .shopee-table td {
        padding: 12px 12px;
        vertical-align: middle;
        color: var(--shopee-text);
    }

    /* ── Product Cell ── */
    .product-cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .order-id-link {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--shopee-orange);
        text-decoration: none;
        font-family: 'Courier New', monospace;
    }
    .order-id-link:hover {
        color: var(--shopee-orange-hover);
        text-decoration: underline;
    }
    .sku-tag {
        font-size: 0.7rem;
        color: #666;
        font-family: 'Courier New', monospace;
        background: #f5f5f5;
        border-radius: 2px;
        padding: 1px 5px;
        display: inline-block;
    }
    .buyer-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ee4d2d, #ff8c5a);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* ── Channel Badges ── */
    .badge-shopee  { background: linear-gradient(135deg,#ee4d2d,#ff6b35); color:#fff; }
    .badge-tiktok  { background: linear-gradient(135deg,#000,#111827); color:#fff; border:1px solid #374151; }
    .badge-lazada  { background: linear-gradient(135deg,#0f146d,#1a237e); color:#fff; }
    .badge-tokopedia { background: linear-gradient(135deg,#03ac0e,#10b981); color:#fff; }
    .badge-offline { background: linear-gradient(135deg,#475569,#64748b); color:#fff; }
    .channel-badge {
        font-size: 0.68rem;
        font-weight: 700;
        border-radius: 2px;
        padding: 2px 7px;
        display: inline-block;
    }

    /* ── Status Badges ── */
    .status-badge {
        font-size: 0.7rem;
        font-weight: 600;
        border-radius: 20px;
        padding: 3px 10px;
        display: inline-block;
        white-space: nowrap;
    }
    .status-pending     { background:#fff7e6; color:#d46b08; border:1px solid #ffd591; }
    .status-toship      { background:#fff2e8; color:#d4380d; border:1px solid #ffbb96; }
    .status-shipped     { background:#e6f4ff; color:#096dd9; border:1px solid #91caff; }
    .status-completed   { background:#f6ffed; color:#389e0d; border:1px solid #b7eb8f; }
    .status-cancelled   { background:#fff1f0; color:#cf1322; border:1px solid #ffa39e; }
    .status-default     { background:#fafafa; color:#595959; border:1px solid #d9d9d9; }

    /* ── Deadline Badge ── */
    .deadline-overdue   { background:#fff1f0; color:#cf1322; border:1px solid #ffa39e; border-radius:3px; padding:2px 7px; font-size:0.7rem; font-weight:600; }
    .deadline-urgent    { background:#fffbe6; color:#d4b106; border:1px solid #ffe58f; border-radius:3px; padding:2px 7px; font-size:0.7rem; font-weight:600; }
    .deadline-safe      { background:#f6ffed; color:#389e0d; border:1px solid #b7eb8f; border-radius:3px; padding:2px 7px; font-size:0.7rem; font-weight:600; }

    /* ── Print / Packing badges ── */
    .meta-badge {
        font-size: 0.65rem;
        font-weight: 600;
        border-radius: 2px;
        padding: 1px 6px;
        display: inline-block;
    }

    /* ── Action Buttons in table ── */
    .btn-tbl {
        font-size: 0.72rem;
        font-weight: 600;
        border-radius: 2px;
        padding: 4px 10px;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        text-decoration: none;
    }
    .btn-tbl-primary {
        background: var(--shopee-orange);
        color: #fff;
    }
    .btn-tbl-primary:hover { background: var(--shopee-orange-hover); color:#fff; }
    .btn-tbl-outline {
        background: #fff;
        color: var(--shopee-orange);
        border: 1px solid var(--shopee-orange) !important;
    }
    .btn-tbl-outline:hover { background: var(--shopee-orange-light); }
    .btn-tbl-yellow {
        background: #fff7e6;
        color: #d46b08;
        border: 1px solid #ffd591 !important;
    }
    .btn-tbl-yellow:hover { background: #ffe7ba; }
    .btn-tbl-blue {
        background: #e6f4ff;
        color: #096dd9;
        border: 1px solid #91caff !important;
    }
    .btn-tbl-blue:hover { background: #bae0ff; }

    /* ── Urgent Alert ── */
    .shopee-alert {
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    /* ── Notification Banner (di atas tab) ── */
    .shopee-notif-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 18px;
        background: linear-gradient(90deg, #fff4f2 0%, #fff8f6 100%);
        border-bottom: 1px solid #ffd4cc;
    }
    .shopee-notif-banner .notif-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .shopee-notif-banner .notif-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ee4d2d, #ff8c5a);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(238,77,45,0.3);
    }
    .shopee-notif-banner .notif-icon i {
        color: #fff;
        font-size: 0.85rem;
    }
    .shopee-notif-banner .notif-text strong {
        font-size: 0.88rem;
        color: #333;
        font-weight: 700;
    }
    .shopee-notif-banner .notif-text span {
        font-size: 0.78rem;
        color: #888;
        display: block;
        margin-top: 1px;
    }
    .notif-count-pill {
        background: #ee4d2d;
        color: #fff;
        font-weight: 800;
        font-size: 1.1rem;
        border-radius: 8px;
        padding: 4px 14px;
        letter-spacing: -0.5px;
        box-shadow: 0 2px 8px rgba(238,77,45,0.35);
        white-space: nowrap;
    }
    .notif-urgent-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff1f0;
        color: #cf1322;
        border: 1px solid #ffa39e;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    /* ── Sub-Tab Status Pesanan ── */
    .shopee-sub-tabs {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 10px 16px;
        border-bottom: 1px solid var(--shopee-border);
        background: #fafafa;
        flex-wrap: wrap;
        gap: 8px;
    }
    .sub-tabs-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #555;
        white-space: nowrap;
        margin-right: 4px;
    }
    .sub-tab-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #555;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .sub-tab-pill:hover {
        border-color: var(--shopee-orange);
        color: var(--shopee-orange);
        text-decoration: none;
    }
    .sub-tab-pill.active {
        background: var(--shopee-orange);
        border-color: var(--shopee-orange);
        color: #fff;
        box-shadow: 0 2px 6px rgba(238,77,45,0.3);
    }
    .sub-tab-pill .pill-count {
        background: rgba(255,255,255,0.3);
        border-radius: 10px;
        padding: 0 5px;
        font-size: 0.7rem;
        font-weight: 700;
        min-width: 18px;
        text-align: center;
    }
    .sub-tab-pill:not(.active) .pill-count {
        background: #f0f0f0;
        color: #666;
    }

    /* ── Pagination ── */
    .shopee-pagination {
        padding: 12px 16px;
        border-top: 1px solid var(--shopee-border);
        background: #fafafa;
    }

    /* ── Empty state ── */
    .shopee-empty {
        padding: 60px 20px;
        text-align: center;
        color: #aaa;
    }
    .shopee-empty i {
        font-size: 3rem;
        opacity: 0.25;
        display: block;
        margin-bottom: 12px;
    }

    .print-status-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    @media (min-width: 768px) {
        .print-status-group {
            border-left: 1px solid var(--shopee-border);
            padding-left: 16px;
        }
    }
    @media (max-width: 767.98px) {
        .print-status-group {
            border-top: 1px solid var(--shopee-border);
            padding-top: 8px;
            width: 100%;
        }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .shopee-filter-group.w-order,
        .shopee-filter-group.w-select,
        .shopee-filter-group.w-date { width: 100%; }
        .shopee-page-header { flex-direction: column; gap: 8px; align-items: flex-start; }
    }
</style>

<div class="shopee-page">

    {{-- ── Page Header ── --}}
    <div class="shopee-page-header">
        <h5><i class="fas fa-shopping-cart me-2" style="color: var(--shopee-orange);"></i>Pesanan Saya</h5>
        <div class="header-actions">
            @can('orders.export')
                <a href="{{ route('orders.export', request()->all()) }}" class="btn-shopee-ghost">
                    <i class="fas fa-file-export"></i> Export CSV
                </a>
            @endcan
            <button type="submit" form="mass-print-form" class="btn-shopee-ghost">
                <i class="fas fa-print"></i> Cetak Massal
            </button>
        </div>
    </div>

    {{-- ── Main Card ── --}}
    <div class="shopee-card">

        {{-- ── Status Tabs ── --}}
        @php
            $currentStatus = request('status', '');
            $tabStatuses = [
                ''              => ['label' => 'Semua',         'icon' => 'fas fa-list',         'countKey' => '__all__'],
                'UNPAID'        => ['label' => 'Belum Bayar',   'icon' => 'fas fa-credit-card',  'countKey' => 'UNPAID'],
                'READY_TO_SHIP' => ['label' => 'Perlu Dikirim', 'icon' => 'fas fa-box',          'countKey' => 'READY_TO_SHIP'],
                'SHIPPED'       => ['label' => 'Dikirim',       'icon' => 'fas fa-truck',        'countKey' => 'SHIPPED'],
                'COMPLETED'     => ['label' => 'Selesai',       'icon' => 'fas fa-check-circle', 'countKey' => 'COMPLETED'],
                'CANCELLED'     => ['label' => 'Dibatalkan',    'icon' => 'fas fa-times-circle', 'countKey' => 'CANCELLED'],
            ];
        @endphp
        <div class="shopee-tabs" role="tablist">
            @foreach($tabStatuses as $tabKey => $tabInfo)
                @php
                    $tabUrl   = route('orders.index', array_merge(request()->except(['status', 'page']), $tabKey !== '' ? ['status' => $tabKey] : []));
                    $isActive = $currentStatus === $tabKey;
                    $count    = $tabCounts[$tabInfo['countKey']] ?? 0;
                    // Tab "Perlu Dikirim" pakai badge merah agar lebih mencolok
                    $badgeStyle = ($tabKey === 'READY_TO_SHIP' && $count > 0)
                        ? 'background:#ee4d2d; color:#fff;'
                        : ($count > 0 ? 'background:#ee4d2d; color:#fff;' : 'background:#e5e7eb; color:#888;');
                @endphp
                <a class="shopee-tab {{ $isActive ? 'active' : '' }}"
                   href="{{ $tabUrl }}"
                   role="tab">
                    <i class="{{ $tabInfo['icon'] }}" style="font-size:0.8rem;"></i>
                    {{ $tabInfo['label'] }}
                    @if($count > 0)
                        <span class="tab-count" style="{{ $badgeStyle }}">
                            {{ $count > 999 ? '999+' : $count }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- ── Sub-Tabs: Status Pesanan & Status Cetak ── --}}
        @php
            $currentProcess = request('process_status', '');
            $subTabItems = [
                ''           => ['label' => 'Semua',          'countKey' => '__all__'],
                'to_process' => ['label' => 'Perlu diproses', 'countKey' => 'to_process'],
                'processed'  => ['label' => 'Telah diproses', 'countKey' => 'processed'],
            ];

            $currentPrint = request('print_status', '');
            $printTabItems = [
                ''          => ['label' => 'Semua',          'countKey' => '__all__'],
                'unprinted' => ['label' => 'Belum di Print', 'countKey' => 'unprinted'],
                'printed'   => ['label' => 'Sudah di Print', 'countKey' => 'printed'],
            ];
        @endphp
        <div class="shopee-sub-tabs d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <!-- Left: Status Pesanan -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="sub-tabs-label"><i class="fas fa-filter me-1"></i>Status Pesanan:</span>
                @foreach($subTabItems as $ptKey => $ptInfo)
                    @php
                        $ptUrl      = route('orders.index', array_merge(request()->except(['process_status', 'page']), $ptKey !== '' ? ['process_status' => $ptKey] : []));
                        $ptActive   = $currentProcess === $ptKey;
                        $ptCount    = $processCounts[$ptInfo['countKey']] ?? 0;
                    @endphp
                    <a href="{{ $ptUrl }}" class="sub-tab-pill {{ $ptActive ? 'active' : '' }}">
                        {{ $ptInfo['label'] }}
                        @if($ptCount > 0)
                            <span class="pill-count">{{ $ptCount > 999 ? '999+' : $ptCount }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <!-- Right: Status Cetak -->
            <div class="print-status-group ms-md-auto pe-md-2">
                <span class="sub-tabs-label"><i class="fas fa-print me-1"></i>Status Cetak:</span>
                @foreach($printTabItems as $prKey => $prInfo)
                    @php
                        $prUrl      = route('orders.index', array_merge(request()->except(['print_status', 'page']), $prKey !== '' ? ['print_status' => $prKey] : []));
                        $prActive   = $currentPrint === $prKey;
                        $prCount    = $printCounts[$prInfo['countKey']] ?? 0;
                    @endphp
                    <a href="{{ $prUrl }}" class="sub-tab-pill {{ $prActive ? 'active' : '' }}">
                        {{ $prInfo['label'] }}
                        @if($prCount > 0)
                            <span class="pill-count">{{ $prCount > 999 ? '999+' : $prCount }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Filter Bar ── --}}
        <div class="shopee-filter-bar">
            <form method="GET" action="{{ route('orders.index') }}" id="filter-form">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('process_status'))
                    <input type="hidden" name="process_status" value="{{ request('process_status') }}">
                @endif
                @if(request('print_status'))
                    <input type="hidden" name="print_status" value="{{ request('print_status') }}">
                @endif

                <div class="filter-row">
                    {{-- No. Pesanan --}}
                    <div class="shopee-filter-group w-order">
                        <label><i class="fas fa-search me-1"></i>No. Pesanan / Resi / Pembeli</label>
                        <input type="text" name="order_number" class="form-control"
                            placeholder="Cari no. pesanan, resi, pembeli..."
                            value="{{ request('order_number') }}">
                    </div>

                    {{-- Channel --}}
                    <div class="shopee-filter-group w-select">
                        <label><i class="fas fa-shopping-bag me-1"></i>Channel</label>
                        <select name="channel_id" class="form-select">
                            <option value="">Semua Channel</option>
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->id }}"
                                    {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                    {{ $channel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Toko --}}
                    <div class="shopee-filter-group w-select">
                        <label><i class="fas fa-store me-1"></i>Toko</label>
                        <select name="store_id" class="form-select">
                            <option value="">Semua Toko</option>
                            @foreach ($stores as $store)
                                @php
                                    $channelName = $store->channel->name ?? ucfirst($store->channel->code ?? 'Marketplace');
                                @endphp
                                <option value="{{ $store->id }}"
                                    {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $channelName }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dari Tanggal --}}
                    <div class="shopee-filter-group w-date">
                        <label><i class="fas fa-calendar me-1"></i>Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control"
                            value="{{ request('start_date') }}">
                    </div>

                    {{-- Sampai Tanggal --}}
                    <div class="shopee-filter-group w-date">
                        <label><i class="fas fa-calendar-check me-1"></i>Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control"
                            value="{{ request('end_date') }}">
                    </div>

                    {{-- Tombol --}}
                    <div class="shopee-filter-group" style="justify-content: flex-end; padding-bottom: 0;">
                        <label style="visibility:hidden;">.</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-shopee-primary">
                                <i class="fas fa-search"></i> Terapkan
                            </button>
                            @if (request()->anyFilled(['channel_id', 'store_id', 'start_date', 'end_date', 'order_number']))
                                <a href="{{ route('orders.index', request('status') ? ['status' => request('status')] : []) }}"
                                   class="btn-shopee-ghost">
                                    <i class="fas fa-times"></i> Atur Ulang
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>


        {{-- ── Summary Bar ── --}}
        <div class="shopee-summary-bar">
            <div class="results-count">
                <i class="fas fa-list-ul me-1"></i>
                <strong>{{ $orders->total() }}</strong> Hasil Ditemukan
                @if($orders->total() > 0)
                    &nbsp;·&nbsp; Halaman {{ $orders->currentPage() }} dari {{ $orders->lastPage() }}
                @endif
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn-shopee-ghost" id="btn-mass-ship">
                    <i class="fas fa-truck-loading"></i> Pengiriman Massal
                </button>
            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="table-responsive">
            <form id="mass-print-form" action="{{ route('orders.mass_print') }}" method="POST" target="_blank">
                @csrf
                <table class="shopee-table">
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">
                                <input type="checkbox" id="check-all" class="form-check-input" style="cursor:pointer;">
                            </th>
                            <th>PRODUK &amp; PESANAN</th>
                            <th>PEMBELI</th>
                            <th>TOKO &amp; CHANNEL</th>
                            <th style="text-align:right;">DIBAYAR PEMBELI</th>
                            <th>BATAS PENGIRIMAN</th>
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
                                $channelBadgeClass = match(true) {
                                    str_contains($channelCode, 'shopee')    => 'badge-shopee',
                                    str_contains($channelCode, 'tiktok')    => 'badge-tiktok',
                                    str_contains($channelCode, 'lazada')    => 'badge-lazada',
                                    str_contains($channelCode, 'tokopedia') => 'badge-tokopedia',
                                    default                                 => 'badge-offline',
                                };
                                $channelIcon = match(true) {
                                    str_contains($channelCode, 'shopee')    => 'fas fa-shopping-bag',
                                    str_contains($channelCode, 'tiktok')    => 'fab fa-tiktok',
                                    str_contains($channelCode, 'lazada')    => 'fas fa-store',
                                    str_contains($channelCode, 'tokopedia') => 'fas fa-shopping-cart',
                                    default                                 => 'fas fa-store-alt',
                                };

                                $orderStatusUp = strtoupper($order->order_status ?? '');
                                $statusBadgeClass = match(true) {
                                    in_array($orderStatusUp, ['UNPAID', 'PENDING'])                         => 'status-pending',
                                    in_array($orderStatusUp, ['READY_TO_SHIP', 'TO_SHIP', 'PROCESSED'])     => 'status-toship',
                                    in_array($orderStatusUp, ['SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE'])       => 'status-shipped',
                                    in_array($orderStatusUp, ['COMPLETED', 'FINISHED', 'SELESAI', 'DELIVERED']) => 'status-completed',
                                    in_array($orderStatusUp, ['CANCELLED', 'BATAL', 'IN_CANCEL'])           => 'status-cancelled',
                                    default                                                                 => 'status-default',
                                };

                                $buyerInitial = strtoupper(substr($order->buyer_name ?? 'U', 0, 1));
                            @endphp
                            <tr>
                                {{-- Checkbox --}}
                                <td style="text-align:center;">
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                        class="order-checkbox form-check-input" style="cursor:pointer;">
                                </td>

                                {{-- Produk & Pesanan --}}
                                <td>
                                    <div class="product-cell">
                                        {{-- Order ID --}}
                                        <a href="{{ route('orders.show', $order) }}" class="order-id-link">
                                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                        </a>

                                        {{-- Badges row --}}
                                        <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                            @if ($order->is_dropship)
                                                <span class="meta-badge" style="background:#fff7e6;color:#d46b08;border:1px solid #ffd591;">Dropship</span>
                                            @endif
                                            @if ($order->hasPreorderItems())
                                                <span class="meta-badge" style="background:#f3e8ff;color:#7c3aed;border:1px solid #c4b5fd;">
                                                    <i class="fas fa-clock me-1"></i>PO
                                                </span>
                                            @else
                                                <span class="meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;">
                                                    <i class="fas fa-check-circle me-1"></i>Ready
                                                </span>
                                            @endif

                                            @if ($order->spks && $order->spks->isNotEmpty())
                                                <a href="{{ route('spks.show', $order->spks->first()->id) }}"
                                                   class="meta-badge text-decoration-none"
                                                   style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                                    <i class="fas fa-tools me-1"></i>{{ $order->spks->first()->no_spk }}
                                                </a>
                                            @else
                                                <span class="meta-badge" style="background:#fafafa;color:#888;border:1px solid #ddd;">
                                                    <i class="fas fa-minus-circle me-1"></i>Belum SPK
                                                </span>
                                            @endif
                                        </div>

                                        {{-- SKU Items --}}
                                        @if($order->items->isNotEmpty())
                                            <div class="mt-1 d-flex flex-column gap-1">
                                                @foreach($order->items as $orderItem)
                                                    <span class="sku-tag">
                                                        <i class="fas fa-tag me-1 opacity-50"></i>
                                                        {{ $orderItem->sku ?? ($orderItem->masterProduct->sku ?? '-') }}
                                                        <span style="color:#aaa;">&times;{{ $orderItem->quantity }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Pembeli --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="buyer-avatar">{{ $buyerInitial }}</div>
                                        <span style="font-size:0.82rem; font-weight:600; color:#333;">
                                            {{ $order->buyer_name ?? '-' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Toko & Channel --}}
                                <td>
                                    <div style="font-size:0.82rem; font-weight:600; color:#333; margin-bottom:4px;">
                                        {{ $order->store->store_name ?? '-' }}
                                    </div>
                                    <span class="channel-badge {{ $channelBadgeClass }}">
                                        <i class="{{ $channelIcon }} me-1"></i>{{ $channelName }}
                                    </span>
                                </td>

                                {{-- Dibayar Pembeli --}}
                                <td style="text-align:right;">
                                    <div style="font-size:0.85rem; font-weight:700; color:#333; font-family:'Courier New',monospace;">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </div>
                                </td>

                                {{-- Batas Pengiriman --}}
                                <td>
                                    <div style="font-size:0.78rem; color:#555;">
                                        <div style="margin-bottom:3px;">
                                            <i class="far fa-calendar-alt text-secondary me-1"></i>
                                            <span style="font-weight:600;">{{ $order->order_date ? $order->order_date->format('d/m/Y H:i') : '-' }}</span>
                                        </div>
                                        @if ($order->ship_before_date)
                                            <div style="margin-bottom:3px;">
                                                <span style="color:#aaa;">Batas:</span>
                                                <span style="font-weight:700; color:#333; font-family:monospace;">
                                                    {{ $order->ship_before_date->format('d/m/Y H:i') }}
                                                </span>
                                            </div>
                                            @if (!in_array($orderStatusUp, ['SHIPPED', 'DELIVERED', 'COMPLETED', 'FINISHED', 'CANCELLED', 'SELESAI', 'BATAL', 'IN_CANCEL']))
                                                @if ($order->is_ship_overdue)
                                                    <span class="deadline-overdue"><i class="bi bi-exclamation-circle me-1"></i>Overdue</span>
                                                @elseif ($order->is_ship_urgent)
                                                    <span class="deadline-urgent"><i class="bi bi-clock me-1"></i>{{ $order->ship_before_date->diffForHumans() }}</span>
                                                @else
                                                    <span class="deadline-safe"><i class="bi bi-check-circle me-1"></i>{{ $order->ship_before_date->diffForHumans() }}</span>
                                                @endif
                                            @endif
                                        @endif
                                        @if ($order->completed_at && in_array($orderStatusUp, ['COMPLETED', 'FINISHED', 'SELESAI', 'DELIVERED']))
                                            <div style="margin-top:3px; font-size:0.7rem; color:#15803d; font-weight:600;">
                                                <i class="fas fa-check-double me-1"></i>Cair:
                                                <span style="font-family:monospace;">{{ $order->completed_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Jasa Kirim & Resi --}}
                                <td>
                                    <div style="font-size:0.78rem; color:#555;">
                                        <div style="font-weight:600; color:#333; margin-bottom:4px;">
                                            <i class="fas fa-truck me-1 text-secondary"></i>{{ $order->courier ?? '—' }}
                                        </div>
                                        @if (!empty($order->tracking_number))
                                            <div style="font-family:monospace; font-size:0.7rem; color:#555;" title="Nomor Resi">
                                                <i class="fas fa-barcode me-1 text-secondary"></i>
                                                <span style="font-weight:600; color:#222;">{{ $order->tracking_number }}</span>
                                            </div>
                                        @else
                                            <button type="button"
                                                class="btn-tbl btn-tbl-yellow btn-fetch-single-tracking"
                                                data-order-id="{{ $order->id }}"
                                                title="Tarik Resi dari Marketplace">
                                                <i class="fas fa-sync-alt"></i> Tarik Resi
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td style="text-align:center;">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span class="status-badge {{ $statusBadgeClass }}">
                                            {{ str_replace('_', ' ', $order->order_status) }}
                                        </span>
                                        @if ($order->order_status === 'CANCELLED' && $order->cancel_reason)
                                            <div class="text-danger text-truncate" style="max-width:110px; font-size:0.62rem;" title="{{ $order->cancel_reason }}">
                                                {{ $order->cancel_reason }}
                                            </div>
                                        @endif

                                        @if ($order->order_status !== 'CANCELLED')
                                            {{-- Print Badge --}}
                                            @if ($order->is_printed)
                                                <span class="meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;"
                                                    title="{{ $order->printed_at ? 'Print: ' . $order->printed_at->format('d/m/Y H:i') : '' }}">
                                                    <i class="fas fa-print me-1"></i>Sudah Print
                                                </span>
                                            @else
                                                <span class="meta-badge" style="background:#fafafa;color:#888;border:1px solid #ddd;">
                                                    <i class="fas fa-print me-1"></i>Belum Print
                                                </span>
                                            @endif

                                            {{-- Kemas Badge --}}
                                            @if ($order->packing_status === 'verified')
                                                <span class="meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;"
                                                    title="{{ $order->packed_at ? 'Kemas: ' . $order->packed_at->format('d/m/Y H:i') : '' }}">
                                                    <i class="fas fa-check-circle me-1"></i>Verified
                                                </span>
                                            @elseif($order->packing_status === 'packing')
                                                <span class="meta-badge" style="background:#fffbe6;color:#d4b106;border:1px solid #ffe58f;">
                                                    <i class="fas fa-box-open me-1"></i>Packing
                                                </span>
                                            @else
                                                <span class="meta-badge" style="background:#fafafa;color:#aaa;border:1px solid #eee;">
                                                    <i class="fas fa-hourglass-start me-1"></i>Menunggu
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi --}}
                                <td style="text-align:center;">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <a href="{{ route('orders.show', $order) }}" class="btn-tbl btn-tbl-blue">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        @if (in_array($orderStatusUp, ['SHIPPED', 'DELIVERED', 'COMPLETED', 'FINISHED', 'SELESAI']))
                                            <span class="meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0; padding:4px 8px;">
                                                <i class="fas fa-check-circle me-1"></i>Sudah Kirim
                                            </span>
                                        @elseif ($orderStatusUp !== 'CANCELLED')
                                            <button type="button"
                                                class="btn-tbl btn-tbl-primary btn-ship-single-order"
                                                data-order-id="{{ $order->id }}"
                                                title="Kirim Pesanan ke Marketplace">
                                                <i class="fas fa-paper-plane"></i> Kirim
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="shopee-empty">
                                        <i class="fas fa-shopping-basket"></i>
                                        <p style="font-size:0.9rem; margin:0;">Tidak ada pesanan ditemukan.</p>
                                        <p style="font-size:0.78rem; color:#bbb; margin-top:4px;">Coba ubah filter atau tab status di atas.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>

            <form id="single-tracking-form" action="" method="POST" class="d-none">
                @csrf
            </form>
        </div>

        {{-- ── Pagination ── --}}
        @if ($orders->hasPages())
            <div class="shopee-pagination">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </div>{{-- end shopee-card --}}
</div>{{-- end shopee-page --}}

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkAll   = document.getElementById('check-all');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const form       = document.getElementById('mass-print-form');
        const btnShip    = document.getElementById('btn-mass-ship');

        /* ── Check All ── */
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = checkAll.checked);
            });
        }

        /* ── Tarik Resi Single ── */
        document.querySelectorAll('.btn-fetch-single-tracking').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = this;
                const orderId = $btn.dataset.orderId;
                const originalHtml = $btn.innerHTML;

                $btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Menarik...';
                $btn.disabled = true;

                fetch(`{{ url('/orders') }}/${orderId}/tracking`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Resi Berhasil Ditarik!',
                                text: data.message,
                                timer: 2500,
                                showConfirmButton: false
                            });
                        } else {
                            alert(data.message);
                        }
                        const parentDiv = $btn.closest('div');
                        if (parentDiv && data.tracking_number) {
                            parentDiv.outerHTML = `
                                <div style="font-family:monospace; font-size:0.7rem; color:#555;" title="Nomor Resi">
                                    <i class="fas fa-barcode me-1 text-secondary"></i>
                                    <span style="font-weight:600; color:#222;">${data.tracking_number}</span>
                                </div>`;
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Tarik Resi',
                                text: data.message || 'Terjadi kesalahan saat menarik resi.',
                                confirmButtonColor: '#ee4d2d'
                            });
                        } else {
                            alert(data.message || 'Gagal menarik resi.');
                        }
                        $btn.innerHTML = originalHtml;
                        $btn.disabled = false;
                    }
                })
                .catch(err => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Tarik Resi',
                            text: 'Terjadi kesalahan koneksi: ' + err.message,
                            confirmButtonColor: '#ee4d2d'
                        });
                    } else {
                        alert('Terjadi kesalahan koneksi.');
                    }
                    $btn.innerHTML = originalHtml;
                    $btn.disabled = false;
                });
            });
        });

        /* ── Kirim Pesanan Single ── */
        document.querySelectorAll('.btn-ship-single-order').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = this;
                const orderId = $btn.dataset.orderId;
                const originalHtml = $btn.innerHTML;

                Swal.fire({
                    title: 'Kirim Pesanan ke Marketplace?',
                    text: 'Status pesanan akan diubah menjadi dikirim di Marketplace.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim Sekarang',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#ee4d2d'
                }).then(res => {
                    if (res.isConfirmed) {
                        $btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mengirim...';
                        $btn.disabled = true;

                        fetch(`{{ url('/orders') }}/${orderId}/ship`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Pesanan Berhasil Dikirim!',
                                    text: data.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                                const $flexContainer = $btn.closest('.d-flex');
                                const trackingBtn = $flexContainer ? $flexContainer.querySelector('.btn-fetch-single-tracking') : null;
                                if (data.tracking_number && trackingBtn) {
                                    const trackingDiv = trackingBtn.closest('div');
                                    if (trackingDiv) {
                                        trackingDiv.outerHTML = `
                                            <div style="font-family:monospace; font-size:0.7rem; color:#555;" title="Nomor Resi">
                                                <i class="fas fa-barcode me-1 text-secondary"></i>
                                                <span style="font-weight:600; color:#222;">${data.tracking_number}</span>
                                            </div>`;
                                    }
                                }
                                const parentDiv = $btn.closest('div');
                                if (parentDiv) {
                                    parentDiv.outerHTML = `
                                        <span class="meta-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0; padding:4px 8px;">
                                            <i class="fas fa-check-circle me-1"></i>Sudah Kirim
                                        </span>`;
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Kirim Pesanan',
                                    text: data.message || 'Terjadi kesalahan saat memproses pengiriman.',
                                    confirmButtonColor: '#ee4d2d'
                                });
                                $btn.innerHTML = originalHtml;
                                $btn.disabled = false;
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Kirim Pesanan',
                                text: 'Terjadi kesalahan koneksi: ' + err.message,
                                confirmButtonColor: '#ee4d2d'
                            });
                            $btn.innerHTML = originalHtml;
                            $btn.disabled = false;
                        });
                    }
                });
            });
        });

        /* ── Pengiriman Massal ── */
        if (btnShip) {
            btnShip.addEventListener('click', function (e) {
                e.preventDefault();
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
                    confirmButtonColor: '#ee4d2d'
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
        const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipEls.forEach(el => new bootstrap.Tooltip(el));
    });
</script>
@endpush
