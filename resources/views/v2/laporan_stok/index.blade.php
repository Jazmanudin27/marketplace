@extends('v2.layouts.app')

@section('title', 'Laporan Stok Barang (Gudang & Marketplace) V2')

@push('styles')
<style>
/* ─── Modern V2 Stock Report Styles ─── */
.stock-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.stock-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}
.stock-kpi-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 4px;
}
.stock-kpi-val {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
}
.stock-kpi-sub {
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 4px;
}
.stock-kpi-icon {
    position: absolute;
    right: 14px;
    top: 14px;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

/* Filter Card */
.stock-filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.stock-filter-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Data Table Styling */
.stock-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}
.stock-table {
    margin-bottom: 0;
    font-size: 0.82rem;
}
.stock-table thead th {
    background: #0f172a;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 10px 12px;
    vertical-align: middle;
    border-color: #334155;
    white-space: nowrap;
}
.stock-table thead th.th-warehouse {
    background: #047857;
    color: #ffffff;
}
.stock-table thead th.th-store {
    background: #0369a1;
    color: #ffffff;
}
.stock-table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-color: #f1f5f9;
}
.stock-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Discrepancy Cell */
.cell-diff {
    background-color: #fee2e2 !important;
    color: #b91c1c !important;
    font-weight: 800 !important;
    border-left: 2px solid #ef4444;
}
.cell-diff-text {
    color: #dc2626 !important;
    font-weight: 800;
}
.cell-match-text {
    color: #0369a1;
    font-weight: 700;
}

/* Action Button */
.btn-sync-single {
    padding: 3px 8px;
    font-size: 0.72rem;
    font-weight: 700;
    border-radius: 6px;
    border: none;
    background: #ef4444;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-sync-single:hover {
    background: #dc2626;
    transform: scale(1.03);
}
.btn-sync-single:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    transform: none;
}

/* Nav Tabs Styling */
.v2-tab-nav {
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}
.v2-tab-item {
    padding: 9px 18px;
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}
.v2-tab-item:hover {
    color: #0f172a;
}
.v2-tab-item.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-3 px-3 px-lg-4">
    <!-- Breadcrumb & Page Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="{{ route('v2.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('v2.laporan.index') }}" class="text-decoration-none">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Stok Barang V2</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-boxes text-primary"></i>
                Laporan Stok Barang (Gudang &amp; Marketplace)
            </h4>
            <p class="text-muted small mb-0">Pemantauan real-time sisa stok fisik gudang vs kuota stok etalase marketplace terhubung.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if ($kpi['total_diff'] > 0)
                <button type="button" class="btn btn-sm btn-danger fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" id="btnSyncAllBatch">
                    <i class="bi bi-arrow-repeat"></i>
                    Sync Semua Selisih ({{ number_format($kpi['total_diff'], 0, ',', '.') }})
                </button>
            @endif
            <a href="{{ route('v2.reports.stock.print', request()->query()) }}" target="_blank" class="btn btn-sm btn-primary fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-printer-fill"></i>
                Cetak / Print Laporan
            </a>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="v2-tab-nav">
        <a href="{{ route('v2.laporan.index', ['tab' => 'dilepas']) }}" class="v2-tab-item">
            <i class="bi bi-cash-stack fs-6"></i>
            <span>Laporan Penjualan Dilepas (Dana Cair)</span>
        </a>
        <a href="{{ route('v2.laporan.index', ['tab' => 'semua']) }}" class="v2-tab-item">
            <i class="bi bi-receipt-cutoff fs-6"></i>
            <span>Laporan Rekap Penjualan (Semua)</span>
        </a>
        <a href="{{ route('v2.reports.stock') }}" class="v2-tab-item active">
            <i class="bi bi-boxes fs-6"></i>
            <span>Laporan Stok Barang (Gudang & Marketplace)</span>
        </a>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-2.5 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stock-kpi-card">
                <div class="stock-kpi-title">Total Produk</div>
                <div class="stock-kpi-val">{{ number_format($kpi['total_products'], 0, ',', '.') }}</div>
                <div class="stock-kpi-sub">Master SKU terdaftar</div>
                <div class="stock-kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stock-kpi-card">
                <div class="stock-kpi-title">Single (Utama)</div>
                <div class="stock-kpi-val text-primary">{{ number_format($kpi['total_single'], 0, ',', '.') }}</div>
                <div class="stock-kpi-sub">Produk satuan reguler</div>
                <div class="stock-kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-box"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stock-kpi-card">
                <div class="stock-kpi-title">Paket (Bundle)</div>
                <div class="stock-kpi-val text-purple" style="color: #7c3aed;">{{ number_format($kpi['total_bundle'], 0, ',', '.') }}</div>
                <div class="stock-kpi-sub">Bundle set / paket</div>
                <div class="stock-kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-collection"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stock-kpi-card">
                <div class="stock-kpi-title">Pre-Order (PO)</div>
                <div class="stock-kpi-val" style="color: #ea580c;">{{ number_format($kpi['total_po'], 0, ',', '.') }}</div>
                <div class="stock-kpi-sub">Produk inden jahit</div>
                <div class="stock-kpi-icon text-white" style="background: rgba(234, 88, 12, 0.1); color: #ea580c !important;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stock-kpi-card">
                <div class="stock-kpi-title">Stok Gudang</div>
                <div class="stock-kpi-val text-success">{{ number_format($kpi['total_stock'], 0, ',', '.') }}</div>
                <div class="stock-kpi-sub">Total fisik semua SKU</div>
                <div class="stock-kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stock-kpi-card">
                <div class="stock-kpi-title">Selisih Stok</div>
                <div class="stock-kpi-val {{ $kpi['total_diff'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($kpi['total_diff'], 0, ',', '.') }}
                </div>
                <div class="stock-kpi-sub">{{ $kpi['total_diff'] > 0 ? '⚠️ Beda gudang vs toko' : '✓ Semua sinkron' }}</div>
                <div class="stock-kpi-icon {{ $kpi['total_diff'] > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' }}">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="stock-filter-card mb-3">
        <div class="stock-filter-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel-fill text-primary"></i>
                <span class="fw-bold text-dark small text-uppercase">Filter Stok Barang</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('v2.reports.stock') }}" class="btn btn-sm btn-outline-secondary py-0.5 px-2" style="font-size: 0.75rem;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </a>
            </div>
        </div>
        <div class="p-3">
            <form action="{{ route('v2.reports.stock') }}" method="GET" id="stockFilterForm">
                <div class="row g-2.5">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1 small">Toko Marketplace</label>
                        <select name="store_id" class="form-select form-select-sm rounded-2">
                            <option value="">Semua Toko Marketplace</option>
                            @foreach ($allStores as $st)
                                @php
                                    $chCode = strtolower($st->channel->code ?? $st->channel->name ?? '');
                                    $chLabel = match(true) {
                                        str_contains($chCode, 'shopee') => 'Shopee',
                                        str_contains($chCode, 'tiktok') => 'TikTok',
                                        str_contains($chCode, 'lazada') => 'Lazada',
                                        str_contains($chCode, 'tokopedia') => 'Tokopedia',
                                        default => ucfirst($st->channel->name ?? 'MP')
                                    };
                                @endphp
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->store_name }} ({{ $chLabel }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1 small">Jenis Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm rounded-2">
                            <option value="">Semua Jenis</option>
                            <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single (Standar)</option>
                            <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 BUNDLE (Paket)</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1 small">Tipe PO / Ready</label>
                        <select name="is_preorder" class="form-select form-select-sm rounded-2">
                            <option value="">Semua Tipe</option>
                            <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                            <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>📦 Ready Stock</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1 small">Kategori</label>
                        <select name="category_id" class="form-select form-select-sm rounded-2">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1 small">Merk / Brand</label>
                        <select name="brand_id" class="form-select form-select-sm rounded-2">
                            <option value="">Semua Brand</option>
                            @foreach ($brands as $br)
                                <option value="{{ $br->id }}" {{ request('brand_id') == $br->id ? 'selected' : '' }}>
                                    {{ $br->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1 small">Cari Nama Produk / SKU</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm border-start-0" placeholder="Ketik SKU atau nama produk...">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 d-flex align-items-center gap-3 pt-md-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                Sembunyikan Stok 0
                            </label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="only_different" value="1" id="onlyDifferent" {{ request()->boolean('only_different') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-bold text-danger" for="onlyDifferent">
                                ⚠️ Hanya Beda Stok
                            </label>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3 d-flex align-items-center justify-content-end gap-2 pt-2 pt-lg-3">
                        <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">
                            <i class="bi bi-filter"></i> Terapkan Filter
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary px-3 fw-semibold" onclick="document.getElementById('stockFilterForm').action='{{ route('v2.reports.stock.print') }}'; document.getElementById('stockFilterForm').target='_blank'; document.getElementById('stockFilterForm').submit(); document.getElementById('stockFilterForm').action='{{ route('v2.reports.stock') }}'; document.getElementById('stockFilterForm').target='_self';">
                            <i class="bi bi-printer"></i> Cetak
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="stock-table-card">
        <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-white flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark" style="font-size: 0.9rem;">Daftar Stok Produk</span>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                    {{ number_format($products->total(), 0, ',', '.') }} SKU
                </span>
                @if(request()->boolean('only_different'))
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                        Filter: Hanya Selisih Stok
                    </span>
                @endif
            </div>
            <div class="small text-muted">
                Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ number_format($products->total(), 0, ',', '.') }} barang
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover stock-table">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th style="width: 140px;">SKU</th>
                        <th>Nama Produk</th>
                        <th style="width: 110px;" class="text-center">Tipe / PO</th>
                        <th style="width: 150px;">Toko Terhubung</th>
                        <th class="th-warehouse text-end" style="width: 100px;">Stok Gudang</th>
                        @foreach($stores as $st)
                            @php
                                $chCode = strtolower($st->channel->code ?? $st->channel->name ?? '');
                                $chShort = match(true) {
                                    str_contains($chCode, 'shopee') => 'Shopee',
                                    str_contains($chCode, 'tiktok') => 'TikTok',
                                    str_contains($chCode, 'lazada') => 'Lazada',
                                    str_contains($chCode, 'tokopedia') => 'Tokopedia',
                                    default => ucfirst($st->channel->name ?? 'MP'),
                                };
                            @endphp
                            <th class="th-store text-center" style="min-width: 110px;">
                                <div class="text-truncate" style="max-width: 130px;" title="{{ $st->store_name }}">{{ $st->short_name }}</div>
                                <span class="d-block" style="font-size: 0.65rem; opacity: 0.85; font-weight: normal;">({{ $chShort }})</span>
                            </th>
                        @endforeach
                        <th style="width: 90px;" class="text-center">Aksi Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        @php
                            $stokGudang = (int) $product->stock;
                            $hasDiscrepancy = false;
                            $discrepancyStores = [];
                        @endphp
                        <tr id="row-product-{{ $product->id }}">
                            <td class="text-center text-muted">{{ $products->firstItem() + $index }}</td>
                            <td>
                                <span class="fw-bold text-dark font-monospace" style="font-size: 0.82rem;">{{ $product->sku ?? '-' }}</span>
                                @if($product->sku_induk)
                                    <span class="d-block text-muted" style="font-size: 0.68rem;">Induk: {{ $product->sku_induk }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                <div class="d-flex align-items-center gap-1.5 mt-1" style="font-size: 0.7rem;">
                                    @if($product->category)
                                        <span class="text-muted"><i class="bi bi-tag me-0.5"></i>{{ $product->category->name }}</span>
                                    @endif
                                    @if($product->brand)
                                        <span class="text-muted">• {{ $product->brand->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($product->is_preorder)
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning" style="font-size: 0.7rem;">
                                        ⏳ PO ({{ $product->preorder_days ?: 7 }}hr)
                                    </span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size: 0.7rem;">
                                        📦 Ready
                                    </span>
                                @endif

                                @if($product->is_bundle)
                                    <span class="badge bg-purple bg-opacity-10 text-purple border d-block mt-1" style="font-size: 0.65rem; color: #7c3aed; border-color: #ddd6fe !important;">
                                        Bundle
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $connectedStores = $product->marketplaceProducts
                                        ->map(fn($mp) => $mp->store)
                                        ->filter()
                                        ->unique('id');
                                @endphp
                                @if($connectedStores->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($connectedStores as $cs)
                                            <span class="badge bg-light text-dark border py-0.5 px-1.5" style="font-size: 0.68rem;">
                                                {{ $cs->short_name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted fst-italic" style="font-size: 0.72rem;">Belum Terhubung</span>
                                @endif
                            </td>
                            <td class="text-end bg-success bg-opacity-10">
                                <span class="fw-bold text-success" style="font-size: 0.95rem;">
                                    {{ number_format($stokGudang, 0, ',', '.') }}
                                </span>
                            </td>
                            @foreach($stores as $st)
                                @php
                                    $storeMpProducts = $product->marketplaceProducts->where('store_id', $st->id);
                                    $storeStock = $storeMpProducts->isNotEmpty() ? (int) $storeMpProducts->max('stock') : 0;
                                    $isDifferent = ($storeMpProducts->isNotEmpty() && $storeStock !== $stokGudang);
                                    if ($isDifferent) {
                                        $hasDiscrepancy = true;
                                        $discrepancyStores[] = $st->short_name;
                                    }
                                @endphp
                                <td class="text-end {{ $isDifferent ? 'cell-diff' : '' }}">
                                    @if($storeMpProducts->isEmpty())
                                        <span class="text-muted" style="font-size: 0.72rem;">-</span>
                                    @else
                                        @if($isDifferent)
                                            <span class="cell-diff-text">⚠️ {{ number_format($storeStock, 0, ',', '.') }}</span>
                                        @else
                                            <span class="cell-match-text">{{ number_format($storeStock, 0, ',', '.') }}</span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">
                                @if($hasDiscrepancy)
                                    <button type="button" class="btn-sync-single btn-action-sync" data-product-id="{{ $product->id }}" data-sku="{{ $product->sku ?? '-' }}">
                                        🔄 Sync
                                    </button>
                                @else
                                    <span class="text-success fw-bold" style="font-size: 0.72rem;">
                                        <i class="bi bi-check-circle-fill"></i> Sinkron
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 7 + count($stores) }}" class="text-center py-5">
                                <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2.2rem;"></i>
                                <h6 class="text-muted fw-bold">Tidak ada data stok produk ditemukan</h6>
                                <p class="text-muted small mb-0">Coba ubah kata kunci pencarian atau reset filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-white flex-wrap gap-2">
                <div class="small text-muted">
                    Menampilkan {{ $products->firstItem() }} s/d {{ $products->lastItem() }} dari total {{ $products->total() }} produk
                </div>
                <div>
                    {{ $products->links() }}
                </div>
            </div>
        @endif
    </div>
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
            const originalHtml = this.innerHTML;

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
                    notify('Sinkronisasi Sukses', `Stok produk SKU ${sku} berhasil dikirim ke marketplace!`, 'success');
                    this.outerHTML = '<span class="text-success fw-bold" style="font-size: 0.72rem;"><i class="bi bi-check-circle-fill"></i> Terkirim</span>';
                } else {
                    notify('Gagal Sinkron', data.message || 'Terjadi kesalahan sistem.', 'error');
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
                if (confirm('Sinkronkan seluruh produk yang memiliki selisih stok ke marketplace?')) {
                    executeBatchSync();
                }
            }
        });

        function executeBatchSync() {
            btnSyncAll.disabled = true;
            const originalText = btnSyncAll.innerHTML;
            btnSyncAll.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim antrian...';

            fetch('/v2/reports/stock/sync-batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                btnSyncAll.disabled = false;
                btnSyncAll.innerHTML = originalText;
                if (data.success) {
                    notify('Batch Sync Berhasil', data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    notify('Gagal Batch Sync', data.message || 'Error.', 'error');
                }
            })
            .catch(err => {
                btnSyncAll.disabled = false;
                btnSyncAll.innerHTML = originalText;
                notify('Koneksi Error', 'Terjadi kesalahan saat memproses antrian.', 'error');
            });
        }
    }
});
</script>
@endpush
