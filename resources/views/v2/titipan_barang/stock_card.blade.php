@extends('v2.layouts.app')

@section('title', 'Kartu Stok & Persediaan Konsinyasi Supplier V2')

@push('styles')
<style>
/* ─── Kartu Stok Konsinyasi V2 Styles ─── */
.ks-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.ks-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.ks-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.ks-kpi-value {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;
}
.ks-kpi-sub {
    font-size: 0.68rem;
    color: #9ca3af;
    margin-top: 3px;
}
.ks-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 10px;
    font-size: 1.9rem;
    opacity: 0.10;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-card-checklist text-info fs-5"></i> Kartu Stok & Persediaan Konsinyasi
        </h1>
        <p class="text-muted small mb-0 mt-1">Laporan mutasi persediaan, penjualan, sisa stok, setoran supplier, dan profit toko</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary fw-semibold px-3">
            <i class="bi bi-printer me-1"></i> Cetak
        </button>
        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm btn-outline-secondary fw-semibold px-3">
            <i class="bi bi-box-seam me-1"></i> Penerimaan Barang
        </a>
        @if($selectedSupplierId)
            <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}"
                class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none;">
                <i class="bi bi-cash-stack me-1"></i> Form Setoran Supplier
            </a>
        @endif
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:10px;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Filter Supplier (Ketik Nama / Cari Supplier) ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('supplier_consignments.stock_card') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-6 col-lg-5">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Supplier Penitip Barang</label>
            <div class="input-group input-group-sm">
                <input type="text" name="supplier" value="{{ request('supplier') }}" class="form-control" placeholder="Ketik nama supplier (contoh: PT Bandung Kain)..." autocomplete="off">
                <button type="submit" class="btn btn-primary px-3 fw-semibold">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
                @if(request()->filled('supplier') || request()->filled('supplier_id'))
                    <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                @endif
            </div>
        </div>
        @if($selectedSupplier)
            <div class="col-12 col-md-6 col-lg-7 d-flex align-items-center gap-2 flex-wrap pt-md-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fw-semibold" style="font-size:0.8rem;">
                    <i class="bi bi-building me-1"></i>Supplier: {{ $selectedSupplier->name }}
                </span>
                @if($selectedSupplier->phone)
                    <span class="badge bg-light text-secondary border px-3 py-1.5" style="font-size:0.8rem;">
                        <i class="bi bi-telephone me-1"></i>{{ $selectedSupplier->phone }}
                    </span>
                @endif
            </div>
        @endif
    </form>

    @if(!empty($searchSupplier) && $suppliers->count() > 1 && !$selectedSupplierId)
        <div class="mt-3 pt-2 border-top">
            <div class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Ditemukan {{ $suppliers->count() }} supplier yang cocok, silakan pilih salah satu:</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($suppliers as $sup)
                    <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $sup->id, 'supplier' => $sup->name]) }}" class="btn btn-sm btn-outline-primary fw-semibold">
                        <i class="bi bi-building me-1"></i>{{ $sup->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>



    {{-- ── KPI Summary Cards ── --}}
    <div class="row g-2.5 mb-3">
        {{-- Total Barang Masuk --}}
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="ks-kpi-card border-start border-primary border-3">
                <div class="ks-kpi-title text-primary">Total Masuk</div>
                <div class="ks-kpi-value text-primary">{{ number_format($totalReceivedAll) }}</div>
                <div class="ks-kpi-sub">PCS diterima</div>
                <i class="bi bi-box-arrow-in-down ks-kpi-icon text-primary"></i>
            </div>
        </div>

        {{-- Total Terjual --}}
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="ks-kpi-card border-start border-info border-3">
                <div class="ks-kpi-title text-info">Total Terjual</div>
                <div class="ks-kpi-value text-info">{{ number_format($totalSoldAll) }}</div>
                <div class="ks-kpi-sub">PCS sudah laku</div>
                <i class="bi bi-bag-check ks-kpi-icon text-info"></i>
            </div>
        </div>

        {{-- Sisa Stok Gudang --}}
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="ks-kpi-card border-start border-warning border-3">
                <div class="ks-kpi-title text-warning">Sisa Gudang</div>
                <div class="ks-kpi-value text-warning">{{ number_format($totalRemainingAll) }}</div>
                <div class="ks-kpi-sub">PCS dalam stok</div>
                <i class="bi bi-boxes ks-kpi-icon text-warning"></i>
            </div>
        </div>

        {{-- Sudah Disetorkan --}}
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="ks-kpi-card border-start border-success border-3">
                <div class="ks-kpi-title text-success">Sudah Disetor</div>
                <div class="ks-kpi-value text-success">{{ number_format($totalSettledAll) }}</div>
                <div class="ks-kpi-sub">Rp {{ number_format($totalPaidAmountAll, 0, ',', '.') }}</div>
                <i class="bi bi-cash-stack ks-kpi-icon text-success"></i>
            </div>
        </div>

        {{-- Belum Disetorkan --}}
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="ks-kpi-card border-start border-danger border-3">
                <div class="ks-kpi-title text-danger">Belum Disetor</div>
                <div class="ks-kpi-value text-danger">{{ number_format($totalUnsettledAll) }}</div>
                <div class="ks-kpi-sub">PCS perlu disetor</div>
                <i class="bi bi-exclamation-circle ks-kpi-icon text-danger"></i>
            </div>
        </div>

        {{-- Profit Toko --}}
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="ks-kpi-card border-start border-success border-3" style="background: linear-gradient(135deg,#f0fdf4,#fff);">
                <div class="ks-kpi-title text-success">Profit Toko</div>
                <div class="ks-kpi-value text-success font-monospace" style="font-size:0.95rem;">
                    Rp {{ number_format($totalProfitAll, 0, ',', '.') }}
                </div>
                <div class="ks-kpi-sub">dari selisih jual-titip</div>
                <i class="bi bi-graph-up-arrow ks-kpi-icon text-success"></i>
            </div>
        </div>
    </div>

    {{-- ── Card List Mutasi & Stok Persediaan per Produk ── --}}
    <div class="v2-card shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between bg-light">
            <span class="fw-bold text-secondary small text-uppercase" style="font-size:0.75rem; letter-spacing:0.04em;">
                <i class="bi bi-boxes text-warning me-1.5 fs-6"></i>
                Mutasi & Stok Persediaan — {{ $selectedSupplier ? strtoupper($selectedSupplier->name) : 'SEMUA SUPPLIER' }} ({{ count($reportData) }} SKU)
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size:0.75rem;">
                {{ count($reportData) }} Produk
            </span>
        </div>

        <div class="p-3 d-flex flex-column gap-3" style="background: #f8fafc;">
            @forelse($reportData as $row)
                <div class="card border border-slate-200 shadow-sm rounded-3 overflow-hidden bg-white hover-shadow transition-all">
                    {{-- Product Name & SKU Header Bar (Full 100% Width) --}}
                    <div class="p-3 border-bottom bg-light-subtle d-flex align-items-start justify-content-between gap-3 flex-wrap flex-md-nowrap">
                        <div class="d-flex align-items-start gap-2.5 flex-grow-1">
                            <div class="rounded-2 p-2 bg-primary-subtle text-primary fw-bold text-center d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;">
                                <i class="bi bi-box-seam fs-6"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1.5 lh-sm" style="font-size: 0.95rem; word-break: break-word;">
                                    {{ $row['name'] }}
                                </h6>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-0.5" style="font-size:0.72rem;">
                                        <i class="bi bi-barcode me-1"></i>SKU: {{ $row['sku'] }}
                                    </span>
                                    @if(isset($row['supplier_name']) && !$selectedSupplierId)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size:0.72rem;">
                                            <i class="bi bi-building me-1"></i>{{ $row['supplier_name'] }}
                                        </span>
                                    @endif
                                    <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size:0.7rem;">
                                        Satuan: {{ $row['unit'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Price Info Badges --}}
                        <div class="d-flex align-items-center gap-3 ps-md-2 flex-shrink-0">
                            <div class="text-end">
                                <span class="text-muted d-block small" style="font-size:0.68rem; font-weight: 600;">HARGA TITIP (HPP)</span>
                                <span class="font-monospace fw-bold text-dark" style="font-size:0.85rem;">
                                    Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="border-start ps-3 text-end">
                                <span class="text-muted d-block small" style="font-size:0.68rem; font-weight: 600;">HARGA JUAL TOKO</span>
                                <span class="font-monospace fw-bold text-primary" style="font-size:0.85rem;">
                                    Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Sub Metrics Grid --}}
                    <div class="p-3 bg-white">
                        <div class="row g-2 align-items-center" style="font-size: 0.78rem;">
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="p-2 rounded-2 bg-light border text-center">
                                    <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">MASUK</span>
                                    <span class="fw-bold text-dark d-block mt-0.5" style="font-size: 0.82rem;">
                                        {{ number_format($row['qty_received']) }} {{ $row['unit'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="p-2 rounded-2 bg-light border text-center">
                                    <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">TERJUAL</span>
                                    <span class="fw-bold text-info d-block mt-0.5" style="font-size: 0.82rem;">
                                        {{ number_format($row['qty_sold']) }} {{ $row['unit'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="p-2 rounded-2 bg-light border text-center">
                                    <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">SISA GUDANG</span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5 fw-bold mt-0.5" style="font-size: 0.78rem;">
                                        {{ number_format($row['current_stock']) }} {{ $row['unit'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="p-2 rounded-2 bg-light border text-center">
                                    <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">DISETOR</span>
                                    <span class="fw-bold text-success d-block mt-0.5" style="font-size: 0.82rem;">
                                        {{ number_format($row['qty_settled']) }} {{ $row['unit'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="p-2 rounded-2 bg-light border text-center">
                                    <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">BELUM DISETOR</span>
                                    @if($row['qty_unsettled'] > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 fw-bold mt-0.5" style="font-size: 0.78rem;">
                                            {{ number_format($row['qty_unsettled']) }} {{ $row['unit'] }}
                                        </span>
                                    @else
                                        <span class="text-muted fw-semibold d-block mt-0.5" style="font-size: 0.78rem;">0 {{ $row['unit'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="p-2 rounded-2 bg-light border text-center">
                                    <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">PROFIT TOKO</span>
                                    <span class="font-monospace fw-bold text-success d-block mt-0.5" style="font-size: 0.82rem;">
                                        +Rp {{ number_format($row['profit_total'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="v2-card p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                    Belum ada data penerimaan barang konsinyasi yang disetujui.
                </div>
            @endforelse
        </div>

        {{-- Total Summary Banner Footer --}}
        @if(count($reportData) > 0)
            <div class="p-3 bg-light border-top">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3" style="font-size: 0.85rem;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold text-dark me-1">REKAPITULASI TOTAL:</span>
                        <span class="badge bg-primary text-white px-2.5 py-1" style="font-size:0.75rem;">Masuk: {{ number_format($totalReceivedAll) }}</span>
                        <span class="badge bg-info text-white px-2.5 py-1" style="font-size:0.75rem;">Terjual: {{ number_format($totalSoldAll) }}</span>
                        <span class="badge bg-warning text-dark px-2.5 py-1" style="font-size:0.75rem;">Sisa: {{ number_format($totalRemainingAll) }}</span>
                        <span class="badge bg-success text-white px-2.5 py-1" style="font-size:0.75rem;">Disetor: {{ number_format($totalSettledAll) }}</span>
                        <span class="badge bg-danger text-white px-2.5 py-1" style="font-size:0.75rem;">Blm Setor: {{ number_format($totalUnsettledAll) }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <span class="text-muted small me-1">Hak Supplier:</span>
                            <strong class="font-monospace text-dark">Rp {{ number_format($totalPaidAmountAll, 0, ',', '.') }}</strong>
                        </div>
                        <div class="border-start ps-3">
                            <span class="text-muted small me-1">Profit Toko:</span>
                            <strong class="font-monospace text-success fs-6">+Rp {{ number_format($totalProfitAll, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
