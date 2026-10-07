@extends('v2.layouts.app')

@section('title', $activeTab === 'dilepas' ? 'Laporan Penjualan Dilepas (Dana Cair)' : 'Laporan Rekap Penjualan')

@push('styles')
<style>
/* ─── Metric Cards Matching Screenshot ─── */
.kpi-metric-card {
    border-radius: 10px;
    padding: 14px 18px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.kpi-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.kpi-metric-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}
.kpi-metric-val {
    font-size: 1.25rem;
    font-weight: 800;
    margin-bottom: 0;
    line-height: 1.2;
}
.kpi-circle-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Card 1: Green Net Released */
.card-metric-green {
    background: #dcfce7 !important;
    border: none;
    border-left: 5px solid #16a34a !important;
}
.card-metric-green .kpi-metric-title,
.card-metric-green .kpi-metric-val {
    color: #15803d;
}
.card-metric-green .kpi-circle-icon {
    background: #16a34a;
    color: #ffffff;
}

/* Card 2: Blue Gross Revenue */
.card-metric-blue {
    background: #dbeafe !important;
    border: none;
    border-left: 5px solid #2563eb !important;
}
.card-metric-blue .kpi-metric-title,
.card-metric-blue .kpi-metric-val {
    color: #1d4ed8;
}
.card-metric-blue .kpi-circle-icon {
    background: #2563eb;
    color: #ffffff;
}

/* Card 3: Red Refund */
.card-metric-red {
    background: #fee2e2 !important;
    border: none;
    border-left: 5px solid #dc2626 !important;
}
.card-metric-red .kpi-metric-title,
.card-metric-red .kpi-metric-val {
    color: #b91c1c;
}
.card-metric-red .kpi-circle-icon {
    background: #dc2626;
    color: #ffffff;
}

/* Card 4: Yellow Fee */
.card-metric-yellow {
    background: #fef3c7 !important;
    border: none;
    border-left: 5px solid #d97706 !important;
}
.card-metric-yellow .kpi-metric-title,
.card-metric-yellow .kpi-metric-val {
    color: #451a03;
}
.card-metric-yellow .kpi-circle-icon {
    background: #f59e0b;
    color: #1e293b;
}

/* Card 5: Cyan Orders */
.card-metric-cyan {
    background: #cffafe !important;
    border: none;
    border-left: 5px solid #0891b2 !important;
}
.card-metric-cyan .kpi-metric-title,
.card-metric-cyan .kpi-metric-val {
    color: #0e7490;
}
.card-metric-cyan .kpi-circle-icon {
    background: #06b6d4;
    color: #ffffff;
}

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
    padding: 10px 16px;
}
.filter-box-header-blue {
    background: #eff6ff;
    border-bottom: 1px solid #dbeafe;
    padding: 10px 16px;
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

{{-- ── 5 Metric Cards (Matching Screenshot) ── --}}
<div class="row g-2.5 mb-3">
    <!-- 1. Total Dana Dilepas (Net) -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-metric-card card-metric-green">
            <div>
                <div class="kpi-metric-title">TOTAL DANA DILEPAS (NET)</div>
                <h5 class="kpi-metric-val">Rp {{ number_format($summary['net_released'], 0, ',', '.') }}</h5>
            </div>
            <div class="kpi-circle-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>
    </div>

    <!-- 2. Total Omset Kotor (Gross) -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-metric-card card-metric-blue">
            <div>
                <div class="kpi-metric-title">TOTAL OMSET KOTOR (GROSS)</div>
                <h5 class="kpi-metric-val">Rp {{ number_format($summary['gross_revenue'], 0, ',', '.') }}</h5>
            </div>
            <div class="kpi-circle-icon">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>

    <!-- 3. Refund / Retur -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-metric-card card-metric-red">
            <div>
                <div class="kpi-metric-title">REFUND / RETUR</div>
                <h5 class="kpi-metric-val">Rp {{ number_format($summary['total_refunds'] ?? 0, 0, ',', '.') }}</h5>
            </div>
            <div class="kpi-circle-icon">
                <i class="bi bi-arrow-counterclockwise"></i>
            </div>
        </div>
    </div>

    <!-- 4. Potongan MP -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-metric-card card-metric-yellow">
            <div>
                <div class="kpi-metric-title">POTONGAN MP</div>
                <h5 class="kpi-metric-val">Rp {{ number_format($summary['marketplace_fee'], 0, ',', '.') }}</h5>
            </div>
            <div class="kpi-circle-icon">
                <i class="bi bi-percent"></i>
            </div>
        </div>
    </div>

    <!-- 5. Transaksi Selesai -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="kpi-metric-card card-metric-cyan">
            <div>
                <div class="kpi-metric-title">TRANSAKSI</div>
                <h5 class="kpi-metric-val">{{ number_format($summary['total_orders'], 0, ',', '.') }} Order</h5>
            </div>
            <div class="kpi-circle-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
        </div>
    </div>
</div>

{{-- ── TAB 1: LAPORAN PENJUALAN DILEPAS (DANA CAIR) ── --}}
@if($activeTab === 'dilepas')
<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="filter-box-card">
            <div class="filter-box-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.85rem;">
                    <i class="bi bi-funnel-fill text-success"></i>
                    <span>Filter Penjualan Dilepas (Escrow Released)</span>
                </h6>
            </div>
            <div class="p-3">
                <form id="releasedFilterForm" action="{{ route('reports.released_sales.print') }}" method="GET" target="_blank">
                    <input type="hidden" name="tab" value="dilepas">

                    {{-- Format Laporan --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-bold text-success small" style="font-size:0.75rem;">Format Laporan Penjualan Dilepas</label>
                        <select name="report_format" class="form-select form-select-sm border-success fw-bold text-success bg-success bg-opacity-10">
                            <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Laporan Per Produk (Dilepas)</option>
                            <option value="ringkasan_penghasilan" {{ $reportFormat === 'ringkasan_penghasilan' ? 'selected' : '' }}>📄 Laporan Ringkasan Penghasilan & Biaya Escrow (Format Shopee/Marketplace)</option>
                            <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Laporan Per Channel Marketplace (Dilepas)</option>
                            <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Laporan Detail Transaksi (Dilepas)</option>
                            <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Laporan Per Tanggal (Dilepas)</option>
                        </select>
                    </div>

                    {{-- Kategori Produk --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Merk --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Merk</label>
                        <select name="brand_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Toko Marketplace --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Toko Marketplace</label>
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
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label mb-1 fw-semibold text-success small" style="font-size:0.75rem;">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal (Dilepas / Cair)
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-success" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1 fw-semibold text-success small" style="font-size:0.75rem;">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal (Dilepas / Cair)
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-success" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Action Buttons Matching Screenshot --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
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
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.85rem;">
                    <i class="bi bi-funnel-fill text-primary"></i>
                    <span>Filter Rekap Penjualan (Semua)</span>
                </h6>
            </div>
            <div class="p-3">
                <form id="salesFilterForm" action="{{ route('reports.sales.print') }}" method="GET" target="_blank">
                    <input type="hidden" name="tab" value="semua">

                    {{-- Format Laporan Penjualan --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-bold text-primary small" style="font-size:0.75rem;">Format Laporan Penjualan</label>
                        <select name="report_format" class="form-select form-select-sm border-primary fw-bold text-primary bg-primary bg-opacity-10">
                            <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Laporan Per Produk</option>
                            <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Laporan Per Channel / Saluran</option>
                            <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Laporan Detail Transaksi</option>
                            <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Laporan Per Tanggal</option>
                            <option value="per_kategori_pelanggan" {{ $reportFormat === 'per_kategori_pelanggan' ? 'selected' : '' }}>👥 Laporan Per Kategori Pelanggan</option>
                        </select>
                    </div>

                    {{-- Kategori Produk --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Merk --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Merk</label>
                        <select name="brand_id" class="form-select form-select-sm v2-input">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipe PO --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Tipe Pre-Order (PO)</label>
                        <select name="po_status" class="form-select form-select-sm v2-input">
                            <option value="">Semua Tipe (PO & Reguler)</option>
                            <option value="1" {{ $poStatus === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                            <option value="0" {{ $poStatus === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                        </select>
                    </div>

                    {{-- Toko Marketplace --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Toko Marketplace</label>
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
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Kategori Pelanggan (Master Data)</label>
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
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Tipe Penjualan Dropship</label>
                        <select name="is_dropship" class="form-select form-select-sm v2-input">
                            <option value="all" {{ ($dropshipFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Transaksi (Dropship & Non-Dropship)</option>
                            <option value="1" {{ ($dropshipFilter ?? '') === '1' ? 'selected' : '' }}>🚚 Khusus Penjualan Dropship</option>
                            <option value="0" {{ ($dropshipFilter ?? '') === '0' ? 'selected' : '' }}>🛍️ Khusus Penjualan Non-Dropship</option>
                        </select>
                    </div>

                    {{-- Status Transaksi --}}
                    <div class="mb-2.5">
                        <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Status Transaksi</label>
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
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label mb-1 fw-semibold text-primary small" style="font-size:0.75rem;">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal Order
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-primary" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1 fw-semibold text-primary small" style="font-size:0.75rem;">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal Order
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-primary" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Action Buttons Matching Screenshot --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
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
