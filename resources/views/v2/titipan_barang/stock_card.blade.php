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

@if(!$selectedSupplierId)
    <div class="v2-card p-5 text-center my-4 shadow-sm">
        <div class="mb-3 text-muted">
            <i class="bi bi-search text-secondary" style="font-size: 3rem;"></i>
        </div>
        <h5 class="fw-bold text-dark">Ketik Nama Supplier</h5>
        <p class="text-muted small mb-0" style="max-width: 520px; margin: 0 auto;">
            Ketikkan nama supplier pada kolom pencarian di atas lalu tekan tombol <strong>Cari</strong> untuk menampilkan laporan Kartu Stok & Persediaan Konsinyasi.
        </p>
    </div>
@else

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

    {{-- ── Table Kartu Stok per Produk ── --}}
    <div class="v2-card shadow-sm overflow-hidden">
        <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between bg-light">
            <span class="fw-bold text-secondary small text-uppercase" style="font-size:0.73rem; letter-spacing:0.04em;">
                <i class="bi bi-table me-1"></i>
                Mutasi & Stok Persediaan — {{ $selectedSupplier ? strtoupper($selectedSupplier->name) : '' }}
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">
                {{ count($reportData) }} SKU
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-3 py-2.5">SKU & NAMA PRODUK</th>
                        <th class="text-end py-2.5">HARGA TITIP (HPP)</th>
                        <th class="text-end py-2.5">HARGA JUAL</th>
                        <th class="text-center py-2.5">MASUK</th>
                        <th class="text-center py-2.5">TERJUAL</th>
                        <th class="text-center py-2.5">SISA GUDANG</th>
                        <th class="text-center py-2.5">DISETOR</th>
                        <th class="text-center py-2.5">BLM DISETOR</th>
                        <th class="text-end py-2.5">HAK SUPPLIER</th>
                        <th class="text-end pe-3 py-2.5">PROFIT TOKO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $row)
                        <tr>
                            <td class="ps-3 py-2.5">
                                <div class="fw-semibold text-dark">{{ $row['name'] }}</div>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size:0.66rem;">
                                    {{ $row['sku'] }}
                                </span>
                            </td>
                            <td class="text-end text-muted font-monospace" style="font-size:0.8rem;">
                                Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}
                            </td>
                            <td class="text-end text-muted font-monospace" style="font-size:0.8rem;">
                                Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}
                            </td>
                            <td class="text-center fw-semibold text-dark">
                                {{ number_format($row['qty_received']) }}
                                <span class="text-muted fw-normal" style="font-size:0.72rem;">{{ $row['unit'] }}</span>
                            </td>
                            <td class="text-center fw-bold text-info">
                                {{ number_format($row['qty_sold']) }}
                                <span class="text-muted fw-normal" style="font-size:0.72rem;">{{ $row['unit'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 fw-bold" style="font-size:0.78rem;">
                                    {{ number_format($row['current_stock']) }} {{ $row['unit'] }}
                                </span>
                            </td>
                            <td class="text-center fw-bold text-success">
                                {{ number_format($row['qty_settled']) }}
                                <span class="text-muted fw-normal" style="font-size:0.72rem;">{{ $row['unit'] }}</span>
                            </td>
                            <td class="text-center fw-bold text-danger">
                                {{ number_format($row['qty_unsettled']) }}
                                <span class="text-muted fw-normal" style="font-size:0.72rem;">{{ $row['unit'] }}</span>
                            </td>
                            <td class="text-end fw-semibold text-dark font-monospace" style="font-size:0.8rem;">
                                Rp {{ number_format($row['nominal_paid'], 0, ',', '.') }}
                            </td>
                            <td class="text-end pe-3 fw-bold text-success font-monospace" style="font-size:0.8rem;">
                                +Rp {{ number_format($row['profit_total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada data barang konsinyasi untuk supplier ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($reportData) > 0)
                    <tfoot class="table-light">
                        <tr class="fw-bold border-top">
                            <td class="ps-3 text-muted small text-uppercase">TOTAL</td>
                            <td colspan="2"></td>
                            <td class="text-center text-dark">{{ number_format($totalReceivedAll) }}</td>
                            <td class="text-center text-info">{{ number_format($totalSoldAll) }}</td>
                            <td class="text-center">
                                <span class="fw-bold text-warning-emphasis">{{ number_format($totalRemainingAll) }}</span>
                            </td>
                            <td class="text-center text-success">{{ number_format($totalSettledAll) }}</td>
                            <td class="text-center text-danger">{{ number_format($totalUnsettledAll) }}</td>
                            <td class="text-end font-monospace" style="font-size:0.8rem;">
                                Rp {{ number_format($totalPaidAmountAll, 0, ',', '.') }}
                            </td>
                            <td class="text-end pe-3 font-monospace text-success" style="font-size:0.8rem;">
                                +Rp {{ number_format($totalProfitAll, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

@else
    {{-- Empty State - Belum pilih supplier --}}
    <div class="v2-card shadow-sm text-center py-5 px-3">
        <i class="bi bi-building fs-1 text-secondary opacity-40 d-block mb-3"></i>
        <h6 class="fw-semibold text-secondary mb-1">Pilih Supplier Terlebih Dahulu</h6>
        <p class="text-muted small mb-0">Pilih supplier penitip barang di atas untuk melihat kartu stok & mutasi konsinyasi.</p>
    </div>
@endif

@endsection
