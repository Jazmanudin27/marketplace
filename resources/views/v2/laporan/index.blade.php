@extends('v2.layouts.app')

@section('title', $activeTab === 'dilepas' ? 'Laporan Penjualan Dilepas (Dana Cair)' : 'Laporan Rekap Penjualan')

@push('styles')
<style>
/* Filter Card Styling */
.filter-box-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.filter-box-header {
    background: #f0fdf4;
    border-bottom: 1px solid #dcfce7;
    padding: 12px 18px;
}
.filter-box-header-blue {
    background: #eff6ff;
    border-bottom: 1px solid #dbeafe;
    padding: 12px 18px;
}

/* Form Group Spacing */
.filter-form-group {
    margin-bottom: 1.25rem;
}
.filter-form-group label {
    display: block;
    margin-bottom: 0.45rem;
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.3;
}
.filter-form-group .form-select,
.filter-form-group .form-control {
    padding: 0.45rem 0.75rem;
    font-size: 0.84rem;
    border-radius: 6px;
}

/* Nav Tabs Styling */
.v2-tab-nav {
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}
.v2-tab-item {
    padding: 10px 20px;
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
    color: #16a34a;
    border-bottom-color: #16a34a;
}
.v2-tab-item.active-blue {
    color: #2563eb;
    border-bottom-color: #2563eb;
}
</style>
@endpush

@section('content')

{{-- ── Navigation Tabs ── --}}
<div class="v2-tab-nav">
    <a href="{{ route('v2.laporan.index', ['tab' => 'dilepas']) }}" class="v2-tab-item {{ $activeTab === 'dilepas' ? 'active' : '' }}">
        <i class="bi bi-cash-stack fs-6"></i>
        <span>Laporan Penjualan Dilepas (Dana Cair)</span>
    </a>
    <a href="{{ route('v2.laporan.index', ['tab' => 'semua']) }}" class="v2-tab-item {{ $activeTab === 'semua' ? 'active-blue' : '' }}">
        <i class="bi bi-receipt-cutoff fs-6"></i>
        <span>Laporan Rekap Penjualan (Semua)</span>
    </a>
</div>

{{-- ── TAB 1: LAPORAN PENJUALAN DILEPAS (DANA CAIR) ── --}}
@if($activeTab === 'dilepas')
<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="filter-box-card">
            <div class="filter-box-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.88rem;">
                    <i class="bi bi-funnel-fill text-success"></i>
                    <span>Filter Penjualan Dilepas (Escrow Released)</span>
                </h6>
            </div>
            <div class="p-3.5" style="padding: 20px;">
                <form id="releasedFilterForm" action="{{ route('reports.released_sales.print') }}" method="GET" target="_blank">
                    <input type="hidden" name="tab" value="dilepas">

                    {{-- Format Laporan --}}
                    <div class="filter-form-group">
                        <label class="form-label text-success fw-bold">Format Laporan Penjualan Dilepas</label>
                        <select name="report_format" class="form-select form-select-sm border-success fw-bold text-success bg-success bg-opacity-10">
                            <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Laporan Per Produk (Dilepas)</option>
                            <option value="ringkasan_penghasilan" {{ $reportFormat === 'ringkasan_penghasilan' ? 'selected' : '' }}>📄 Laporan Ringkasan Penghasilan & Biaya Escrow (Format Shopee/Marketplace)</option>
                            <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Laporan Per Channel Marketplace (Dilepas)</option>
                            <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Laporan Detail Transaksi (Dilepas)</option>
                            <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Laporan Per Tanggal (Dilepas)</option>
                        </select>
                    </div>

                    {{-- Kategori Produk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Merk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Merk</label>
                        <select name="brand_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Toko Marketplace --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Toko Marketplace</label>
                        <select name="store_id" class="form-select form-select-sm v2-input">
                            <option value="" {{ empty($storeId) ? 'selected' : '' }}>🛒 Semua Toko Marketplace</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ (isset($storeId) && $storeId == $store->id) ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $store->channel->name ?? 'Marketplace' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="row g-2.5 filter-form-group mb-4">
                        <div class="col-6">
                            <label class="form-label text-success">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal (Dilepas / Cair)
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-success" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-success">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal (Dilepas / Cair)
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-success" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Action Buttons Matching Screenshot --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-1.5" onclick="exportReleasedCsv()">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                        </button>
                        <button type="submit" class="btn btn-sm btn-success fw-bold px-3 py-1.5 text-white" style="background:#16a34a; border:none;">
                            <i class="bi bi-printer-fill me-1"></i> Cetak Rekap
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ── TAB 2: LAPORAN REKAP PENJUALAN (SEMUA STATUS) ── --}}
@else
<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="filter-box-card">
            <div class="filter-box-header-blue d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.88rem;">
                    <i class="bi bi-funnel-fill text-primary"></i>
                    <span>Filter Rekap Penjualan (Semua)</span>
                </h6>
            </div>
            <div class="p-3.5" style="padding: 20px;">
                <form id="salesFilterForm" action="{{ route('reports.sales.print') }}" method="GET" target="_blank">
                    <input type="hidden" name="tab" value="semua">

                    {{-- Format Laporan Penjualan --}}
                    <div class="filter-form-group">
                        <label class="form-label text-primary fw-bold">Format Laporan Penjualan</label>
                        <select name="report_format" class="form-select form-select-sm border-primary fw-bold text-primary bg-primary bg-opacity-10">
                            <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Laporan Per Produk</option>
                            <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Laporan Per Channel / Saluran</option>
                            <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Laporan Detail Transaksi</option>
                            <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Laporan Per Tanggal</option>
                            <option value="per_kategori_pelanggan" {{ $reportFormat === 'per_kategori_pelanggan' ? 'selected' : '' }}>👥 Laporan Per Kategori Pelanggan</option>
                        </select>
                    </div>

                    {{-- Kategori Produk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Merk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Merk</label>
                        <select name="brand_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipe PO --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Tipe Pre-Order (PO)</label>
                        <select name="po_status" class="form-select form-select-sm v2-input">
                            <option value="">Semua Tipe (PO & Reguler)</option>
                            <option value="1" {{ $poStatus === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                            <option value="0" {{ $poStatus === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                        </select>
                    </div>

                    {{-- Toko Marketplace --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Toko Marketplace</label>
                        <select name="store_id" class="form-select form-select-sm v2-input">
                            <option value="" {{ empty($storeId) ? 'selected' : '' }}>🛒 Semua Toko Marketplace</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ (isset($storeId) && $storeId == $store->id) ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $store->channel->name ?? 'Marketplace' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kategori Pelanggan --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Kategori Pelanggan (Master Data)</label>
                        <select name="customer_category" class="form-select form-select-sm v2-input">
                            <option value="all" {{ $customerCat === 'all' ? 'selected' : '' }}>Semua Kategori Pelanggan</option>
                            @foreach ($customerCategories as $catVal)
                                @php
                                    $label = $customerCategoryLabels[$catVal] ?? ucfirst($catVal);
                                @endphp
                                <option value="{{ $catVal }}" {{ $customerCat === $catVal ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipe Dropship --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Tipe Penjualan Dropship</label>
                        <select name="is_dropship" class="form-select form-select-sm v2-input">
                            <option value="all" {{ ($dropshipFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Transaksi (Dropship & Non-Dropship)</option>
                            <option value="1" {{ ($dropshipFilter ?? '') === '1' ? 'selected' : '' }}>🚚 Khusus Penjualan Dropship</option>
                            <option value="0" {{ ($dropshipFilter ?? '') === '0' ? 'selected' : '' }}>🛍️ Khusus Penjualan Non-Dropship</option>
                        </select>
                    </div>

                    {{-- Status Transaksi --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Status Transaksi</label>
                        <select name="status" class="form-select form-select-sm v2-input">
                            <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status Transaksi (Default: Tanpa Batal)</option>
                            <option value="completed" {{ ($statusFilter ?? '') === 'completed' ? 'selected' : '' }}>✅ Selesai / Completed</option>
                            <option value="shipped" {{ ($statusFilter ?? '') === 'shipped' ? 'selected' : '' }}>🚚 Sedang Dikirim / Shipped</option>
                            <option value="processing" {{ ($statusFilter ?? '') === 'processing' ? 'selected' : '' }}>📦 Sedang Diproses / Ready to Ship</option>
                            <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>⏳ Menunggu Pembayaran / Unpaid</option>
                            <option value="returned" {{ ($statusFilter ?? '') === 'returned' ? 'selected' : '' }}>🔄 Retur / Dikembalikan</option>
                            <option value="cancelled" {{ ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' }}>❌ Dibatalkan / Cancelled</option>
                        </select>
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="row g-2.5 filter-form-group mb-4">
                        <div class="col-6">
                            <label class="form-label text-primary">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal Order
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-primary" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-primary">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal Order
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-primary" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Action Buttons Matching Screenshot --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 py-1.5" onclick="exportSalesCsv()">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 py-1.5 text-white" style="background:#2563eb; border:none;">
                            <i class="bi bi-printer-fill me-1"></i> Cetak Rekap
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function exportReleasedCsv() {
    const form = document.getElementById('releasedFilterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = "{{ route('reports.released_sales.export') }}?" + params;
}

function exportSalesCsv() {
    const form = document.getElementById('salesFilterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = "{{ route('reports.sales.export') }}?" + params;
}
</script>
@endpush

@endsection
