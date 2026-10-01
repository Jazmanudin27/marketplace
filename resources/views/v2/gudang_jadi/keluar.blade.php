@extends('v2.layouts.app')

@section('title', 'Mutasi Barang Keluar - Gudang Jadi V2')

@push('styles')
<style>
.gj-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.gj-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.gj-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.gj-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.gj-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.gj-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Mutasi Barang Keluar (Gudang Jadi)
        </h1>
        <p class="text-muted small mb-0">Riwayat dan pencatatan pengeluaran / pengurangan stok barang keluar dari gudang jadi</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create', ['type' => 'out']) }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#dc2626; border:none; border-radius:6px;">
            <i class="bi bi-plus-lg me-1"></i> Catat Mutasi Keluar
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Transaksi Keluar -->
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-danger border-3">
            <div class="gj-kpi-title text-danger">Total Transaksi Keluar</div>
            <div class="gj-kpi-value text-danger">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="gj-kpi-sub">Total catatan barang keluar dari gudang</div>
            <i class="bi bi-journal-x gj-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Total Qty Keluar -->
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-danger border-3">
            <div class="gj-kpi-title text-danger">Total Qty Keluar</div>
            <div class="gj-kpi-value text-danger">
                -{{ number_format($totalOutboundQty, 0, ',', '.') }} PCS
            </div>
            <div class="gj-kpi-sub">Jumlah fisik unit produk yang keluar</div>
            <i class="bi bi-box-arrow-up-right gj-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Variasi Produk -->
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-primary border-3">
            <div class="gj-kpi-title text-primary">Variasi Produk</div>
            <div class="gj-kpi-value text-primary">
                {{ number_format($totalUniqueProducts, 0, ',', '.') }} SKU
            </div>
            <div class="gj-kpi-sub">Jumlah produk berbeda yang dimutasi keluar</div>
            <i class="bi bi-boxes gj-kpi-icon text-primary"></i>
        </div>
    </div>
</div>

{{-- ── Filter Controls ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.keluar') }}" class="row g-2 align-items-end">
        <!-- Search Keyword -->
        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Produk / Referensi</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Ketik nama / SKU / ref..." autocomplete="off">
            </div>
        </div>

        <!-- Filter Produk Master -->
        <div class="col-12 col-md-3 col-lg-3">
            <label class="form-label small fw-semibold text-muted mb-1">Pilih Produk</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">Semua Produk Master</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                        [{{ $p->sku }}] {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter Tanggal Mulai -->
        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label small fw-semibold text-muted mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
        </div>

        <!-- Filter Tanggal Selesai -->
        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label small fw-semibold text-muted mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
        </div>

        <!-- Submit & Reset Buttons -->
        <div class="col-12 col-md-1 col-lg-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-danger px-3 fw-semibold flex-fill">
                <i class="bi bi-filter me-1"></i> Filter
            </button>
            @if(request()->anyFilled(['search', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.keluar') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Table Mutasi Barang Keluar ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-4">
    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-list-task text-danger"></i> Riwayat Mutasi Barang Keluar
        </div>
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1">
            Total {{ $mutations->total() }} Data
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="table-light text-secondary">
                <tr>
                    <th style="width: 50px;" class="text-center">NO</th>
                    <th style="width: 150px;">TANGGAL & WAKTU</th>
                    <th>PRODUK MASTER</th>
                    <th>KATEGORI / CATATAN REFERENSI</th>
                    <th style="width: 140px;" class="text-center">QTY KELUAR</th>
                    <th style="width: 150px;">PETUGAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $index => $m)
                    <tr>
                        <td class="text-center text-muted fw-semibold">
                            {{ $mutations->firstItem() + $index }}
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $m->created_at ? $m->created_at->format('d/m/Y') : '-' }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $m->created_at ? $m->created_at->format('H:i') : '-' }} WIB</div>
                        </td>
                        <td>
                            @if($m->masterProduct)
                                <div class="fw-bold text-dark">{{ $m->masterProduct->name }}</div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">SKU: {{ $m->masterProduct->sku }}</div>
                            @else
                                <span class="text-muted font-italic">(Produk Tidak Ditemukan)</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-dark">{{ $m->reference ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger px-2.5 py-1 fw-bold fs-6">
                                -{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1.5">
                                <i class="bi bi-person-circle text-muted"></i>
                                <span class="fw-semibold text-dark">{{ $m->user->name ?? 'Sistem / Admin' }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                            Belum ada riwayat mutasi barang keluar gudang jadi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="p-3 border-top bg-light">
            {{ $mutations->links() }}
        </div>
    @endif
</div>

@endsection
