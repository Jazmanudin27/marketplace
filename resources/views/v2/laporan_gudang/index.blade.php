@extends('v2.layouts.app')

@section('title', 'Laporan Gudang Jadi')

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
    padding: 10px 16px;
}
.filter-box-header-blue {
    background: #eff6ff;
    border-bottom: 1px solid #dbeafe;
    padding: 10px 16px;
}
.filter-box-header-amber {
    background: #fffbeb;
    border-bottom: 1px solid #fef3c7;
    padding: 10px 16px;
}

/* Form Group Spacing */
.filter-form-group {
    margin-bottom: 0.95rem;
}
.filter-form-group label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.25;
}
.filter-form-group .form-select,
.filter-form-group .form-control {
    padding: 0.4rem 0.75rem;
    font-size: 0.84rem;
    border-radius: 6px;
}

/* Nav Tabs Styling matching Laporan Marketing */
.v2-tab-nav {
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
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
.v2-tab-item.active-amber {
    color: #d97706;
    border-bottom-color: #d97706;
}
</style>
@endpush

@section('content')

{{-- ── Navigation Tabs ── --}}
<div class="v2-tab-nav">
    <a href="{{ route('v2.laporan_gudang.index', ['tab' => 'rekap']) }}" class="v2-tab-item {{ $activeTab === 'rekap' ? 'active' : '' }}">
        <i class="bi bi-file-earmark-spreadsheet fs-6"></i>
        <span>Rekap Persediaan Gudang</span>
    </a>
    <a href="{{ route('v2.laporan_gudang.index', ['tab' => 'kartu_stok']) }}" class="v2-tab-item {{ $activeTab === 'kartu_stok' ? 'active-blue' : '' }}">
        <i class="bi bi-card-checklist fs-6"></i>
        <span>Kartu Stok Barang Jadi</span>
    </a>
    <a href="{{ route('v2.laporan_gudang.index', ['tab' => 'perputaran']) }}" class="v2-tab-item {{ $activeTab === 'perputaran' ? 'active-amber' : '' }}">
        <i class="bi bi-arrow-repeat fs-6"></i>
        <span>Perputaran Stok (Inventory Turnover)</span>
    </a>
    <a href="{{ route('v2.reports.stock') }}" class="v2-tab-item">
        <i class="bi bi-boxes fs-6"></i>
        <span>Stok Barang (Live View)</span>
    </a>
</div>

{{-- ── TAB 1: REKAP PERSEDIAAN GUDANG ── --}}
@if($activeTab === 'rekap')
<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="filter-box-card">
            <div class="filter-box-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.86rem;">
                    <i class="bi bi-funnel-fill text-success"></i>
                    <span>Filter Laporan Rekap Persediaan</span>
                </h6>
            </div>
            <div class="p-3.5" style="padding: 16px 18px;">
                <form action="{{ route('reports.summary.print') }}" method="GET" target="_blank">
                    {{-- Jenis Produk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-success fw-bold">Jenis Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm border-success fw-bold text-success bg-success bg-opacity-10">
                            <option value="">Semua Jenis (Single & BUNDLE)</option>
                            <option value="0">📦 Single (Produk Standar)</option>
                            <option value="1">🎁 BUNDLE / Paket Set</option>
                        </select>
                    </div>

                    {{-- Tipe PO --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Tipe Pre-Order (PO)</label>
                        <select name="po_status" class="form-select form-select-sm v2-input">
                            <option value="">Semua Tipe (PO & Reguler)</option>
                            <option value="1">⏳ Pre-Order (PO)</option>
                            <option value="0">📦 Reguler (Bukan PO)</option>
                        </select>
                    </div>

                    {{-- Kategori Produk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm v2-input">
                            <option value="">-- Semua Kategori --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Brand / Merek --}}
                    <div class="filter-form-group">
                        <label class="form-label text-muted">Brand / Merek</label>
                        <select name="brand_id" class="form-select form-select-sm v2-input">
                            <option value="">-- Semua Brand --</option>
                            @foreach ($brands as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="row g-2 filter-form-group mb-3">
                        <div class="col-6">
                            <label class="form-label text-success">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal
                            </label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-success" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-success">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal
                            </label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-success" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Switch Sembunyikan Stok 0 --}}
                    <div class="filter-form-group mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" checked>
                            <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                Sembunyikan produk stok 0 tanpa mutasi
                            </label>
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="d-flex justify-content-end align-items-center pt-2.5 border-top">
                        <button type="submit" class="btn btn-sm btn-success fw-bold px-3 py-1.5 text-white" style="background:#16a34a; border:none;">
                            <i class="bi bi-printer-fill me-1"></i> Cetak Rekap Persediaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ── TAB 2: KARTU STOK BARANG JADI ── --}}
@elseif($activeTab === 'kartu_stok')
<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="filter-box-card">
            <div class="filter-box-header-blue d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.86rem;">
                    <i class="bi bi-funnel-fill text-primary"></i>
                    <span>Filter Kartu Stok Barang Jadi</span>
                </h6>
            </div>
            <div class="p-3.5" style="padding: 16px 18px;">
                <form action="{{ route('reports.ledger.print') }}" method="GET" target="_blank">
                    {{-- Pilih Produk --}}
                    <div class="filter-form-group">
                        <label class="form-label text-primary fw-bold">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="product_id" id="ledger_product_id" class="form-select form-select-sm select2" required>
                            <option value="">-- Cari & Pilih Produk (SKU / Nama) --</option>
                            @foreach ($products as $prod)
                                <option value="{{ $prod->id }}">
                                    [{{ $prod->sku }}] {{ $prod->name }} (Stok: {{ $prod->stock }}) {{ $prod->is_bundle ? ' [BUNDLE]' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-info-circle text-primary me-1"></i>Mutasi stok fisik paket bundle dicatat pada kartu stok produk penyusunnya.
                        </div>
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="row g-2 filter-form-group mb-3">
                        <div class="col-6">
                            <label class="form-label text-primary">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal
                            </label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-primary" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-primary">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal
                            </label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-primary" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="d-flex justify-content-end align-items-center pt-2.5 border-top">
                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 py-1.5 text-white" style="background:#2563eb; border:none;">
                            <i class="bi bi-printer-fill me-1"></i> Cetak Kartu Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ── TAB 3: PERPUTARAN STOK (INVENTORY TURNOVER) ── --}}
@else
<div class="row justify-content-start">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="filter-box-card">
            <div class="filter-box-header-amber d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size:0.86rem;">
                    <i class="bi bi-arrow-repeat text-warning"></i>
                    <span>Analisis Laju Perputaran Stok (Turnover & DSI)</span>
                </h6>
            </div>
            <div class="p-3.5" style="padding: 16px 18px;">
                <form action="{{ route('reports.inventory_turnover') }}" method="GET">
                    {{-- Deskripsi --}}
                    <div class="alert alert-warning py-2 px-3 small mb-3 border-0" style="background:#fef3c7; border-radius:8px; color:#92400e;">
                        <i class="bi bi-lightbulb-fill me-1"></i> Menghitung Total HPP Keluar (COGS), Nilai Rata-rata Persediaan, Rasio Inventory Turnover, dan Days Sales in Inventory (DSI).
                    </div>

                    {{-- Rentang Tanggal --}}
                    <div class="row g-2 filter-form-group mb-3">
                        <div class="col-6">
                            <label class="form-label text-warning" style="color:#b45309 !important;">
                                <i class="bi bi-calendar-check me-1"></i>Dari Tanggal
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-warning" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-warning" style="color:#b45309 !important;">
                                <i class="bi bi-calendar-check me-1"></i>Sampai Tanggal
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-warning" value="{{ $dateTo }}">
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="d-flex justify-content-end align-items-center pt-2.5 border-top">
                        <button type="submit" class="btn btn-sm btn-warning fw-bold px-3 py-1.5 text-dark" style="background:#f59e0b; border:none;">
                            <i class="bi bi-calculator me-1"></i> Hitung Laju Perputaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        if ($('#ledger_product_id').length) {
            $('#ledger_product_id').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Cari & Pilih Produk (SKU / Nama) --',
                allowClear: true,
                width: '100%',
                templateResult: formatProductOption,
                templateSelection: formatProductSelection
            });
        }

        function formatProductOption(state) {
            if (!state.id) return state.text;
            var text = state.text.trim();
            // Match [SKU] Name (Stok: X)
            var match = text.match(/^\[(.*?)\]\s*(.*?)\s*\(Stok:\s*(\d+)\)(.*)$/);
            if (match) {
                var sku = match[1];
                var name = match[2];
                var stock = match[3];
                var extra = match[4];
                return $(
                    '<div class="d-flex align-items-center justify-content-between py-1">' +
                        '<div class="d-flex flex-column" style="max-width: 80%;">' +
                            '<span class="fw-semibold text-dark text-truncate" style="font-size: 0.8rem;">' + name + (extra ? ' <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0 px-1" style="font-size:0.65rem;">BUNDLE</span>' : '') + '</span>' +
                            '<span class="font-monospace text-muted" style="font-size: 0.72rem;"><i class="bi bi-upc me-1 opacity-50"></i>' + sku + '</span>' +
                        '</div>' +
                        '<span class="badge bg-light text-secondary border px-2 py-1 font-monospace" style="font-size: 0.72rem;">Stok: ' + stock + '</span>' +
                    '</div>'
                );
            }
            return state.text;
        }

        function formatProductSelection(state) {
            return state.text;
        }
    });
</script>
@endpush