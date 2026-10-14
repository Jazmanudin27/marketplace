@extends('v2.layouts.app')

@section('title', 'Stok Barang')

@push('styles')
<style>
/* ─── Pesanan/Produk-style Tab, Filter & Summary Bar ─── */
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
.psr-tab .tab-badge.badge-danger-tab {
    background: #ef4444 !important;
    color: #fff !important;
}
.psr-tab .tab-badge.badge-warning-tab {
    background: #f59e0b !important;
    color: #fff !important;
}

/* Filter Bar */
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

/* Summary Bar */
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

/* Action Btn */
.psr-btn {
    font-size: 0.75rem; font-weight: 600;
    border-radius: 5px; padding: 4px 10px;
    display: inline-flex; align-items: center; gap: 4px;
    text-decoration: none; white-space: nowrap; cursor: pointer; border: none;
    transition: all .15s;
}
.psr-btn-primary { background:#2563eb; color:#fff; }
.psr-btn-primary:hover { background:#1d4ed8; color:#fff; }
.psr-btn-ghost { background:#f9fafb; color:#374151; border:1px solid #e5e7eb; }
.psr-btn-ghost:hover { background:#f3f4f6; border-color:#9ca3af; }

/* Interactive stat widget */
.v2-stat-link { text-decoration: none !important; display: block; height: 100%; }
.v2-stat-link .v2-stat-widget { cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s; height: 100%; }
.v2-stat-link .v2-stat-widget:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.v2-stat-link.active .v2-stat-widget { border: 2px solid #2563eb !important; background: #eff6ff !important; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.18) !important; }
.v2-stat-link.active .v2-stat-widget.widget-green { border-color: #10b981 !important; background: #f0fdf4 !important; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.18) !important; }
.v2-stat-link.active .v2-stat-widget.widget-purple { border-color: #8b5cf6 !important; background: #f5f3ff !important; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.18) !important; }
.v2-stat-link.active .v2-stat-widget.widget-amber { border-color: #f59e0b !important; background: #fffbeb !important; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.18) !important; }
.v2-stat-link.active .v2-stat-widget.red-theme { border-color: #ef4444 !important; background: #fef2f2 !important; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.18) !important; }

/* Store chip badges */
.store-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 7px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    white-space: nowrap;
}
.store-chip.diff-chip {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
    animation: pulseDiff 2s infinite ease-in-out;
}
@keyframes pulseDiff {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.2); }
    50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15); }
}
.channel-pill {
    font-size: 0.62rem;
    font-weight: 700;
    padding: 1px 4px;
    border-radius: 4px;
    text-transform: uppercase;
}
.channel-shopee { background: #ff5722; color: #fff; }
.channel-tiktok { background: #000; color: #fff; }
.channel-tokopedia { background: #03ac0e; color: #fff; }
.channel-lazada { background: #0f146d; color: #fff; }
.channel-default { background: #64748b; color: #fff; }
</style>
@endpush

@section('content')
<!-- Page Header Compact (Persis Master Produk) -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-boxes text-primary fs-5"></i> Stok Barang
        </h1>
        <p class="v2-page-subtitle mb-0">Pemantauan real-time stok fisik gudang vs kuota etalase marketplace, deteksi selisih stok &amp; sinkronisasi otomatis</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.reports.stock') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Halaman">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <a href="{{ route('v2.reports.stock.print', request()->query()) }}" target="_blank" class="btn btn-sm btn-v2-secondary py-1.5 px-3 shadow-sm" title="Cetak / Print Laporan">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </a>
        @if(($counts['diff'] ?? 0) > 0)
            <button type="button" class="btn btn-sm btn-danger py-1.5 px-3 shadow-sm fw-semibold d-inline-flex align-items-center gap-1.5" id="btnSyncAllBatch" title="Sync Semua Selisih ke Marketplace">
                <i class="bi bi-arrow-repeat"></i> Sync Semua Selisih ({{ number_format($counts['diff'] ?? 0) }})
            </button>
        @endif
    </div>
</div>

@php
    $currentTab = request('tab', '');
    if (!$currentTab) {
        if (request('is_bundle') === '0') $currentTab = 'single';
        elseif (request('is_bundle') === '1') $currentTab = 'bundle';
        elseif (request('is_preorder') === '0') $currentTab = 'ready';
        elseif (request('is_preorder') === '1') $currentTab = 'po';
        elseif (request('only_different') || request('only_diff')) $currentTab = 'diff';
        elseif (request('low_stock') || request('only_low')) $currentTab = 'low';
    }
    $baseParams = request()->except(['tab', 'page', 'is_bundle', 'is_preorder', 'only_different', 'only_diff', 'low_stock', 'only_low']);
@endphp

{{-- ── Banner Alert Stok Kurang Dari 10 PCS ── --}}
@if(($counts['low'] ?? 0) > 0)
    <div class="alert alert-warning border-0 border-start border-4 border-warning d-flex flex-wrap align-items-center justify-content-between p-3 mb-3 rounded-3 shadow-xs" style="background: #fffbeb; border-left-color: #f59e0b !important;">
        <div class="d-flex align-items-center gap-2.5">
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning text-dark flex-shrink-0" style="width: 36px; height: 36px; font-size: 1.1rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div class="fw-bold text-dark" style="font-size: 0.88rem;">
                    ⚠️ Peringatan Stok Gudang: Ada <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 mx-0.5" style="font-size: 0.82rem;">{{ number_format($counts['low'] ?? 0) }} produk</span> dengan stok kurang dari 10 PCS!
                </div>
                <div class="text-secondary mt-0.5" style="font-size: 0.74rem;">
                    Produk di bawah batas minimum (10 pcs) berisiko kehabisan saat ada lonjakan pesanan marketplace.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            @if($currentTab === 'low')
                <a href="{{ route('v2.reports.stock', $baseParams) }}" class="btn btn-sm btn-outline-secondary py-1 px-3 fw-medium" style="font-size: 0.76rem;">
                    <i class="bi bi-x-circle me-1"></i>Tampilkan Semua Produk
                </a>
            @else
                <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'low'])) }}" class="btn btn-sm btn-warning py-1.5 px-3 fw-bold text-dark shadow-xs" style="font-size: 0.76rem;">
                    <i class="bi bi-filter-circle-fill me-1"></i>Lihat {{ number_format($counts['low'] ?? 0) }} Produk Menipis (&lt; 10 PCS)
                </a>
            @endif
        </div>
    </div>
@endif

<!-- Header Summary Widgets (KPI Cards Persis Master Produk) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', $baseParams) }}" class="v2-stat-link {{ $currentTab === '' ? 'active' : '' }}" title="Total Master">
            <div class="v2-stat-widget widget-blue">
                <div class="v2-stat-icon-wrapper blue">
                    <i class="bi bi-boxes"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num">{{ number_format($counts['total'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Total Master</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'single'])) }}" class="v2-stat-link {{ $currentTab === 'single' ? 'active' : '' }}" title="Single Utama">
            <div class="v2-stat-widget widget-green">
                <div class="v2-stat-icon-wrapper green">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num">{{ number_format($counts['single'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Single (Utama)</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'bundle'])) }}" class="v2-stat-link {{ $currentTab === 'bundle' ? 'active' : '' }}" title="Paket Bundle">
            <div class="v2-stat-widget widget-purple">
                <div class="v2-stat-icon-wrapper purple">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num">{{ number_format($counts['bundle'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Paket (Bundle)</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'ready'])) }}" class="v2-stat-link {{ $currentTab === 'ready' ? 'active' : '' }}" title="Stock Ready">
            <div class="v2-stat-widget widget-blue">
                <div class="v2-stat-icon-wrapper blue">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num">{{ number_format($counts['ready'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Stock Ready</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'po'])) }}" class="v2-stat-link {{ $currentTab === 'po' ? 'active' : '' }}" title="Pre-Order (PO)">
            <div class="v2-stat-widget widget-amber">
                <div class="v2-stat-icon-wrapper amber">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num">{{ number_format($counts['po'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Pre-Order (PO)</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'diff'])) }}" class="v2-stat-link {{ $currentTab === 'diff' ? 'active' : '' }}" title="Selisih Stok">
            <div class="v2-stat-widget red-theme">
                <div class="v2-stat-icon-wrapper amber" style="background-color: #fee2e2; color: #ef4444;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num" style="color: #ef4444;">{{ number_format($counts['diff'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Selisih Stok</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl">
        <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'low'])) }}" class="v2-stat-link {{ $currentTab === 'low' ? 'active' : '' }}" title="Stok Gudang < 10 PCS">
            <div class="v2-stat-widget" style="background: {{ ($counts['low'] ?? 0) > 0 ? '#fffbeb' : '#fff' }}; border-color: {{ ($counts['low'] ?? 0) > 0 ? '#fde68a' : '#e5e7eb' }};">
                <div class="v2-stat-icon-wrapper amber" style="background-color: #fef3c7; color: #d97706;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num" style="color: #d97706;">{{ number_format($counts['low'] ?? 0) }}</span>
                    <span class="v2-stat-lbl">Stok &lt; 10 PCS</span>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Main Card Persis Master Produk -->
<div class="v2-card p-0 shadow-sm overflow-hidden mb-3">

    {{-- Status Tabs Bar --}}
    @php
        $stockTabs = [
            ''       => ['label' => 'Semua', 'icon' => 'bi bi-boxes', 'countKey' => 'total'],
            'single' => ['label' => 'Single (Utama)', 'icon' => 'bi bi-box-seam', 'countKey' => 'single'],
            'bundle' => ['label' => 'Paket (Bundle)', 'icon' => 'bi bi-diagram-3', 'countKey' => 'bundle'],
            'ready'  => ['label' => 'Stock Ready', 'icon' => 'bi bi-check-circle', 'countKey' => 'ready'],
            'po'     => ['label' => 'Pre-Order (PO)', 'icon' => 'bi bi-clock-history', 'countKey' => 'po'],
            'diff'   => ['label' => 'Ada Selisih', 'icon' => 'bi bi-exclamation-triangle-fill', 'countKey' => 'diff'],
            'low'    => ['label' => 'Stok < 10 PCS', 'icon' => 'bi bi-exclamation-triangle-fill', 'countKey' => 'low'],
        ];
    @endphp
    <div class="psr-tab-bar" role="tablist">
        @foreach($stockTabs as $tabKey => $tabInfo)
            @php
                $tabUrl = route('v2.reports.stock', array_merge(
                    $baseParams,
                    $tabKey !== '' ? ['tab' => $tabKey] : []
                ));
                $isActive = $currentTab === $tabKey;
                $count = $counts[$tabInfo['countKey']] ?? 0;
            @endphp
            <a class="psr-tab {{ $isActive ? 'active' : '' }}" href="{{ $tabUrl }}" role="tab">
                <i class="{{ $tabInfo['icon'] }}" style="font-size: .8rem;"></i>
                {{ $tabInfo['label'] }}
                @if($count > 0)
                    <span class="tab-badge {{ $tabKey === 'diff' ? 'badge-danger-tab' : ($tabKey === 'low' ? 'badge-warning-tab' : '') }}">
                        {{ $count > 9999 ? '9999+' : number_format($count) }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Filter Bar --}}
    <div class="psr-filter-bar">
        <form method="GET" action="{{ route('v2.reports.stock') }}" id="psr-filter-form">
            @if(request('tab'))
                <input type="hidden" name="tab" value="{{ request('tab') }}">
            @endif
            <div class="row g-2 align-items-end">
                {{-- Nama Produk --}}
                <div class="col-12 col-md-3">
                    <label class="form-label"><i class="bi bi-search me-1"></i>Nama Produk</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="Cari nama produk..."
                           value="{{ request('name') }}">
                </div>
                {{-- SKU --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-upc-scan me-1"></i>SKU / Induk</label>
                    <input type="text" name="sku" class="form-control font-monospace"
                           placeholder="Cari SKU / Induk..."
                           value="{{ request('sku') }}">
                </div>
                {{-- Kategori --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-tags me-1"></i>Kategori</label>
                    <select name="category_id" class="form-select no-select2">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
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
                        @foreach($allStores as $st)
                            @php $chName = $st->channel->name ?? 'MP'; @endphp
                            <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->store_name }} ({{ $chName }})
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Tombol --}}
                <div class="col-12 col-md-1 d-flex gap-2">
                    <button type="submit" class="psr-btn psr-btn-primary" style="height:31px; padding:0 14px;" title="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->anyFilled(['name', 'sku', 'category_id', 'channel_id', 'store_id', 'is_bundle', 'is_preorder', 'hide_zero_stock', 'only_diff', 'only_different']))
                        <a href="{{ route('v2.reports.stock', request('tab') ? ['tab' => request('tab')] : []) }}"
                           class="psr-btn psr-btn-ghost" style="height:31px; padding:0 10px;" title="Reset Filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Checkbox Toggles --}}
            <div class="d-flex align-items-center gap-4 mt-2 pt-1 border-top border-light">
                <div class="form-check form-switch mb-0" style="font-size: 0.76rem;">
                    <input class="form-check-input no-select2" type="checkbox" name="hide_zero_stock" value="1" id="filterHideZero" {{ request('hide_zero_stock') ? 'checked' : '' }} onchange="document.getElementById('psr-filter-form').submit();">
                    <label class="form-check-label fw-semibold text-secondary" for="filterHideZero">
                        Sembunyikan Stok 0
                    </label>
                </div>
                <div class="form-check form-switch mb-0" style="font-size: 0.76rem;">
                    <input class="form-check-input no-select2" type="checkbox" name="only_diff" value="1" id="filterOnlyDiff" {{ request('only_diff') || request('only_different') || $currentTab === 'diff' ? 'checked' : '' }} onchange="document.getElementById('psr-filter-form').submit();">
                    <label class="form-check-label fw-semibold text-danger" for="filterOnlyDiff">
                        Hanya Tampilkan Selisih Stok
                    </label>
                </div>
                <div class="form-check form-switch mb-0" style="font-size: 0.76rem;">
                    <input class="form-check-input no-select2" type="checkbox" name="low_stock" value="1" id="filterLowStock" {{ request('low_stock') || $currentTab === 'low' ? 'checked' : '' }} onchange="if(this.checked){ window.location='{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'low'])) }}'; } else { window.location='{{ route('v2.reports.stock', $baseParams) }}'; }">
                    <label class="form-check-label fw-semibold text-warning-emphasis" for="filterLowStock">
                        Hanya Stok &lt; 10 PCS
                    </label>
                </div>
            </div>
        </form>
    </div>

    {{-- Summary Bar --}}
    <div class="psr-summary-bar">
        <div>
            <i class="bi bi-list-ul me-1"></i>
            <strong class="text-dark">{{ number_format($products->total()) }}</strong> Produk Ditemukan
            @if($products->total() > 0)
                &nbsp;·&nbsp; Halaman {{ $products->currentPage() }} dari {{ $products->lastPage() }}
                &nbsp;·&nbsp; Total Stok Fisik: <strong class="text-primary">{{ number_format($counts['total_stock'] ?? 0) }} pcs</strong>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(($counts['low'] ?? 0) > 0)
                <a href="{{ route('v2.reports.stock', array_merge($baseParams, ['tab' => 'low'])) }}" class="badge bg-warning text-dark border border-warning px-2.5 py-1 text-decoration-none shadow-xs" style="font-size: 0.72rem;" title="Klik untuk filter produk stok < 10">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>{{ number_format($counts['low'] ?? 0) }} Stok &lt; 10 PCS
                </a>
            @endif
            @if(($counts['diff'] ?? 0) > 0)
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ number_format($counts['diff'] ?? 0) }} Produk Selisih
                </span>
            @endif
        </div>
    </div>

    {{-- Table Persis Master Produk --}}
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th>NAMA PRODUK / MASTER</th>
                        <th>KATEGORI &amp; BRAND</th>
                        <th class="text-center" style="width: 140px;">STOK GUDANG</th>
                        <th>KUOTA MARKETPLACE PER-TOKO</th>
                        <th class="text-center" style="width: 120px;">STATUS SINKRON</th>
                        <th class="text-center" style="width: 110px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $prod)
                        @php
                            $isPO        = (bool) ($prod->is_preorder);
                            $gudangStock = (int) ($prod->stock ?? 0);
                            $minStock    = (int) ($prod->min_stock ?? 5);
                            $isLowStock  = ($gudangStock < 10);

                            // Marketplace comparison: PO dikecualikan karena kuota PO tidak terikat stok fisik gudang
                            $hasDiscrepancy = false;
                            $storeItems = [];
                            foreach ($prod->marketplaceProducts as $mp) {
                                if (!$mp->store) continue;
                                $mpStock = (int) ($mp->stock ?? 0);
                                $isDiff = ($mp->sync_stock && $mpStock !== $gudangStock);
                                if ($isDiff) {
                                    $hasDiscrepancy = true;
                                }
                                $chCode = strtolower($mp->store->channel->code ?? $mp->store->channel->name ?? 'default');
                                $storeItems[] = [
                                    'store_id'     => $mp->store_id,
                                    'store_name'   => $mp->store->store_name,
                                    'channel'      => $mp->store->channel->name ?? 'MP',
                                    'channel_code' => $chCode,
                                    'stock'        => $mpStock,
                                    'is_diff'      => $isDiff,
                                    'sync_stock'   => (bool)$mp->sync_stock,
                                ];
                            }
                        @endphp
                        <tr class="{{ $hasDiscrepancy ? 'table-danger-subtle' : ($isLowStock ? 'table-warning-subtle' : '') }}" id="product-row-{{ $prod->id }}">
                            <td class="text-center text-muted" style="font-size: 0.72rem;">
                                {{ $products->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="product-avatar-icon {{ $hasDiscrepancy ? 'border-danger' : ($isLowStock ? 'border-warning' : '') }}" style="{{ $hasDiscrepancy ? 'background: #fee2e2; color: #ef4444;' : ($isLowStock ? 'background: #fef3c7; color: #d97706;' : '') }}">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.8rem; line-height: 1.25; max-width: 280px;" title="{{ $prod->name }}">
                                            {{ \Illuminate\Support\Str::limit($prod->name, 45) }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                            <span class="sku-badge py-0 px-1.5" style="font-size: 0.65rem;">{{ $prod->sku ?: ($prod->sku_induk ?: '-') }}</span>
                                            @if($prod->sku_induk && $prod->sku_induk !== $prod->sku)
                                                <span class="text-muted" style="font-size: 0.65rem;">Induk: <code class="text-secondary">{{ $prod->sku_induk }}</code></span>
                                            @endif
                                            @if($prod->is_bundle)
                                                <span class="v2-badge v2-badge-warning" style="font-size: 0.6rem; padding: 1px 4px;">
                                                    <i class="bi bi-diagram-3 me-0.5"></i>Bundle
                                                </span>
                                            @else
                                                <span class="v2-badge v2-badge-primary" style="font-size: 0.6rem; padding: 1px 4px;">Single</span>
                                            @endif
                                            @if($prod->is_preorder)
                                                <span class="badge bg-secondary-subtle text-secondary border px-1 py-0.5" style="font-size: 0.58rem;">PO</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-medium text-dark" style="font-size: 0.78rem;">
                                        <i class="bi bi-folder2 text-primary me-1"></i>{{ $prod->category->name ?? 'Tanpa Kategori' }}
                                    </div>
                                    @if($prod->brand)
                                        <div class="text-muted mt-0.5" style="font-size: 0.68rem;">
                                            <i class="bi bi-tag me-1"></i>{{ $prod->brand->name }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($gudangStock <= 0)
                                    <span class="badge bg-danger text-white font-monospace px-2 py-1 shadow-xs" style="font-size: 0.75rem;" title="Stok Kosong / Habis!">
                                        <i class="bi bi-x-circle me-1"></i>Habis (0)
                                    </span>
                                @elseif($gudangStock < 10)
                                    <span class="badge bg-warning text-dark border border-warning font-monospace px-2 py-1 shadow-xs" style="font-size: 0.75rem;" title="Peringatan: Stok kurang dari 10 PCS!">
                                        <i class="bi bi-exclamation-triangle-fill text-danger me-0.5"></i>{{ $gudangStock }} {{ $prod->unit ?? 'pcs' }}
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2.5 py-1" style="font-size: 0.78rem;">
                                        <i class="bi bi-box me-0.5"></i>{{ number_format($gudangStock) }} {{ $prod->unit ?? 'pcs' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if(count($storeItems) > 0)
                                    <div class="d-flex flex-wrap gap-1.5 align-items-center">
                                        @foreach($storeItems as $item)
                                             @php
                                                $pillClass = 'channel-default';
                                                if (str_contains($item['channel_code'], 'shopee')) $pillClass = 'channel-shopee';
                                                elseif (str_contains($item['channel_code'], 'tiktok')) $pillClass = 'channel-tiktok';
                                                elseif (str_contains($item['channel_code'], 'tokopedia')) $pillClass = 'channel-tokopedia';
                                                elseif (str_contains($item['channel_code'], 'lazada')) $pillClass = 'channel-lazada';
                                            @endphp
                                            <div class="store-chip {{ $item['is_diff'] ? 'diff-chip' : '' }}" title="{{ $item['store_name'] }} ({{ $item['channel'] }})">
                                                <span class="channel-pill {{ $pillClass }}">{{ substr($item['channel'], 0, 3) }}</span>
                                                <span class="text-truncate" style="max-width: 90px;">{{ $item['store_name'] }}:</span>
                                                <span class="font-monospace fw-bold {{ $item['is_diff'] ? 'text-danger' : 'text-primary' }}">{{ $item['stock'] }}</span>
                                                @if($item['is_diff'])
                                                    <i class="bi bi-exclamation-triangle-fill text-danger ms-0.5" title="Beda dengan gudang ({{ $gudangStock }})"></i>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.68rem;" title="Belum Terhubung Toko Marketplace">
                                        <i class="bi bi-x-circle me-1"></i>Belum Terhubung
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(count($storeItems) === 0)
                                    <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.68rem;">-</span>
                                @elseif($hasDiscrepancy)
                                    <span class="badge bg-danger text-white px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1 status-badge-target" style="font-size: 0.68rem;">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Ada Selisih
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-inline-flex align-items-center gap-1 status-badge-target" style="font-size: 0.68rem;">
                                        <i class="bi bi-check-circle-fill"></i> Sinkron
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm {{ $hasDiscrepancy ? 'btn-danger' : 'btn-outline-primary' }} btn-action-sync py-0.5 px-2"
                                            style="font-size: 0.7rem; font-weight: 600;"
                                            data-product-id="{{ $prod->id }}"
                                            data-sku="{{ $prod->sku }}"
                                            data-name="{{ $prod->name }}"
                                            title="Sync Stok Gudang ke Marketplace">
                                        <i class="bi bi-arrow-repeat me-0.5"></i> Sync
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2.2rem;"></i>
                                <h6 class="text-muted fw-bold">Tidak ada data stok produk ditemukan</h6>
                                <p class="text-muted small mb-0">Coba ubah kata kunci pencarian atau reset filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($products->hasPages())
        <div class="v2-card-footer bg-light p-2 border-top">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="text-muted" style="font-size: 0.72rem;">
                    Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari total {{ $products->total() }} produk
                </div>
                <div>
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function notify(title, text, icon = 'success') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(`${title}: ${text}`);
        }
    }

    // Sync Single Product via AJAX
    document.querySelectorAll('.btn-action-sync').forEach(btn => {
        btn.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id');
            const sku       = this.getAttribute('data-sku');
            const name      = this.getAttribute('data-name') || sku;
            const originalHtml = this.innerHTML;
            const row       = document.getElementById(`product-row-${productId}`);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            fetch(`/v2/reports/stock/${productId}/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    notify('Sinkronisasi Sukses', data.message || `Stok produk SKU ${sku} berhasil dikirim ke marketplace!`, 'success');
                    this.outerHTML = '<span class="badge bg-success text-white py-1 px-2" style="font-size: 0.68rem;"><i class="bi bi-check-circle-fill me-1"></i>Tersinkron</span>';
                    
                    if (row) {
                        row.classList.remove('table-danger-subtle');
                        const statusBadge = row.querySelector('.status-badge-target');
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-inline-flex align-items-center gap-1 status-badge-target';
                            statusBadge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Sinkron';
                        }
                        row.querySelectorAll('.store-chip').forEach(chip => {
                            chip.classList.remove('diff-chip');
                            const warnIcon = chip.querySelector('.bi-exclamation-triangle-fill');
                            if (warnIcon) warnIcon.remove();
                            const stockSpan = chip.querySelector('.font-monospace');
                            if (stockSpan && data.master_stock !== undefined) {
                                stockSpan.className = 'font-monospace fw-bold text-primary';
                                stockSpan.textContent = data.master_stock;
                            }
                        });
                    }
                } else {
                    notify('Gagal Sinkron', data.message || 'Terjadi kesalahan sistem.', data.is_po ? 'info' : 'error');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(err => {
                notify('Koneksi Error', `Gagal menghubungkan ke server untuk SKU ${sku}.`, 'error');
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });

    // Batch Sync All Discrepancy Products
    const btnSyncAll = document.getElementById('btnSyncAllBatch');
    if (btnSyncAll) {
        btnSyncAll.addEventListener('click', function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Sinkronkan Semua Selisih?',
                    text: 'Sistem akan memicu antrian push stok gudang ke seluruh toko marketplace terkait.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Sinkronkan Sekarang',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeBatchSync();
                    }
                });
            } else {
                if (confirm('Sinkronkan stok seluruh produk yang berselisih ke marketplace sekarang?')) {
                    executeBatchSync();
                }
            }
        });
    }

    function executeBatchSync() {
        if (btnSyncAll) {
            btnSyncAll.disabled = true;
            btnSyncAll.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Memproses...';
        }

        fetch('/v2/reports/stock/sync-batch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Antrian Dimulai!',
                        text: data.message || 'Push stok sedang dijalankan di antrian background.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            } else {
                notify('Gagal', data.message || 'Gagal memicu batch sync.', 'error');
                if (btnSyncAll) {
                    btnSyncAll.disabled = false;
                    btnSyncAll.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync Semua Selisih';
                }
            }
        })
        .catch(err => {
            notify('Error', 'Terjadi gangguan jaringan saat memicu sinkronisasi.', 'error');
            if (btnSyncAll) {
                btnSyncAll.disabled = false;
                btnSyncAll.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync Semua Selisih';
            }
        });
    }
});
</script>
@endpush
