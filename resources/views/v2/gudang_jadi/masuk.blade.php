@extends('v2.layouts.app')

@section('title', 'Mutasi Barang Masuk - Gudang Jadi V2')

@push('styles')
<style>
/* ─── Gudang Jadi V2 Custom Styles ─── */
.bm-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.bm-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.bm-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.bm-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.bm-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.bm-kpi-icon {
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
            <i class="bi bi-box-arrow-in-down text-success fs-5"></i> Mutasi Barang Masuk (Gudang Jadi)
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create', ['type' => 'in']) }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none;">
            <i class="bi bi-plus-lg me-1"></i> Catat Penerimaan Masuk
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
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
    <!-- Total Penerimaan -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-primary border-3">
            <div class="bm-kpi-title text-primary">Total Transaksi</div>
            <div class="bm-kpi-value text-primary">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bm-kpi-sub">Total mutasi masuk ke gudang jadi</div>
            <i class="bi bi-box-arrow-in-down bm-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Barang Masuk Disetujui -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-success border-3">
            <div class="bm-kpi-title text-success">Status Disetujui</div>
            <div class="bm-kpi-value text-success">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bm-kpi-sub">Stok telah resmi bertambah di gudang</div>
            <i class="bi bi-check-circle-fill bm-kpi-icon text-success"></i>
        </div>
    </div>
    <!-- Total Qty Masuk -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-warning border-3">
            <div class="bm-kpi-title text-warning">Total Qty Masuk</div>
            <div class="bm-kpi-value text-warning">
                +{{ number_format($totalInboundQty, 0, ',', '.') }} PCS
            </div>
            <div class="bm-kpi-sub">Jumlah unit barang produk masuk</div>
            <i class="bi bi-boxes bm-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Variasi Produk -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-dark border-3">
            <div class="bm-kpi-title text-dark">Variasi Produk</div>
            <div class="bm-kpi-value text-dark">
                {{ number_format($totalUniqueProducts, 0, ',', '.') }} SKU
            </div>
            <div class="bm-kpi-sub">Varian produk master yang dimutasi</div>
            <i class="bi bi-tags-fill bm-kpi-icon text-dark"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.masuk') }}" class="row g-2 align-items-end">
        <!-- Pencarian -->
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Nama Produk / SKU / Ref</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, SKU, ref..." value="{{ request('search') }}">
        </div>

        <!-- Pilih Produk Master -->
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Pilih Produk Master</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">Semua Produk Master</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                        [{{ $p->sku }}] {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Tanggal Mulai -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
        </div>

        <!-- Tanggal Selesai -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
        </div>

        <!-- Action Buttons Inline -->
        <div class="col-12 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;" title="Terapkan Filter">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request()->anyFilled(['search', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.masuk') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 50px;">NO</th>
                    <th class="py-2.5" style="width: 170px;">TANGGAL & WAKTU</th>
                    <th class="py-2.5">PRODUK MASTER & SKU</th>
                    <th class="py-2.5">KATEGORI / ALASAN / CATATAN</th>
                    <th class="text-center py-2.5" style="width: 150px;">QTY MASUK</th>
                    <th class="text-center py-2.5" style="width: 140px;">STATUS</th>
                    <th class="pe-3 py-2.5" style="width: 160px;">PETUGAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $index => $m)
                    <tr>
                        <td class="ps-3 py-2.5 text-center text-muted fw-semibold">
                            {{ $mutations->firstItem() + $index }}
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $m->created_at ? $m->created_at->format('d/m/Y') : '-' }}</div>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                <i class="bi bi-clock me-1"></i> {{ $m->created_at ? $m->created_at->format('H:i') : '-' }} WIB
                            </span>
                        </td>
                        <td>
                            @if($m->masterProduct)
                                <div class="fw-bold text-dark">{{ $m->masterProduct->name }}</div>
                                <span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">
                                    SKU: {{ $m->masterProduct->sku }}
                                </span>
                            @else
                                <span class="text-muted italic">— (Produk Master Tidak Ditemukan)</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-secondary fw-semibold" style="font-size: 0.78rem;">
                                {{ $m->reference ?? '-' }}
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold fs-6">
                                +{{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                            </span>
                        </td>
                        <td class="pe-3">
                            <div class="d-flex align-items-center gap-1.5">
                                <i class="bi bi-person-circle text-secondary"></i>
                                <span class="fw-semibold text-dark" style="font-size: 0.78rem;">{{ $m->user->name ?? 'Sistem / Admin' }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-box-arrow-in-down fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            Belum ada riwayat penerimaan barang masuk yang sesuai dengan filter.
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
