@extends('v2.layouts.app')

@section('title', 'Gudang Jadi V2')

@push('styles')
<style>
/* ─── Gudang Jadi V2 Custom Styles ─── */
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
            <i class="bi bi-building-gear text-primary fs-5"></i> Gudang Jadi
        </h1>
        <p class="text-muted small mb-0">Kelola riwayat mutasi barang masuk, keluar, dan penyesuaian stok produk jadi</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#2563eb; border:none; border-radius:6px;">
            <i class="bi bi-plus-lg me-1"></i> Catat Mutasi Gudang
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
    <!-- Total Transaksi Mutasi -->
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-primary border-3">
            <div class="gj-kpi-title text-primary">Total Transaksi Mutasi</div>
            <div class="gj-kpi-value text-primary">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="gj-kpi-sub">Total mutasi stok gudang jadi terdaftar</div>
            <i class="bi bi-journal-text gj-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Total Qty Masuk -->
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-success border-3">
            <div class="gj-kpi-title text-success">Total Qty Masuk</div>
            <div class="gj-kpi-value text-success">
                +{{ number_format($totalInbound, 0, ',', '.') }} PCS
            </div>
            <div class="gj-kpi-sub">Akumulasi barang masuk ke gudang</div>
            <i class="bi bi-box-arrow-in-down gj-kpi-icon text-success"></i>
        </div>
    </div>
    <!-- Total Qty Keluar -->
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-danger border-3">
            <div class="gj-kpi-title text-danger">Total Qty Keluar</div>
            <div class="gj-kpi-value text-danger">
                -{{ number_format($totalOutbound, 0, ',', '.') }} PCS
            </div>
            <div class="gj-kpi-sub">Akumulasi barang keluar dari gudang</div>
            <i class="bi bi-box-arrow-up-right gj-kpi-icon text-danger"></i>
        </div>
    </div>
</div>

{{-- ── Filter Controls ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.index') }}" class="row g-2 align-items-end">
        <!-- Search Keyword -->
        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Produk / Referensi</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Ketik nama / SKU / ref..." autocomplete="off">
            </div>
        </div>

        <!-- Filter Jenis Mutasi -->
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-semibold text-muted mb-1">Jenis Mutasi</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Semua Jenis</option>
                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Masuk (+)</option>
                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Keluar (-)</option>
                <option value="adj" {{ request('type') == 'adj' ? 'selected' : '' }}>Penyesuaian (Adj)</option>
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
        <div class="col-6 col-md-3 col-lg-3 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold flex-fill">
                <i class="bi bi-filter me-1"></i> Filter
            </button>
            @if(request()->anyFilled(['search', 'type', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.index') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Table Mutasi Stok Gudang Jadi ── --}}
<div class="v2-card shadow-sm overflow-hidden mb-4">
    <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between bg-light">
        <span class="fw-bold text-secondary small text-uppercase" style="font-size:0.73rem; letter-spacing:0.04em;">
            <i class="bi bi-list-task me-1"></i> Riwayat Mutasi Stok Gudang Jadi
        </span>
        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.7rem;">
            Total {{ $mutations->total() }} Data
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 170px;">WAKTU & USER</th>
                    <th class="py-2.5">NAMA PRODUK & SKU</th>
                    <th class="text-center py-2.5" style="width: 130px;">JENIS MUTASI</th>
                    <th class="text-center py-2.5" style="width: 110px;">JUMLAH QTY</th>
                    <th class="text-center py-2.5" style="width: 110px;">STOK AKHIR</th>
                    <th class="pe-3 py-2.5">KETERANGAN / REFERENSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $m)
                    <tr>
                        <td class="ps-3 py-2.5">
                            <div class="fw-semibold text-dark" style="font-size:0.8rem;">
                                {{ $m->created_at->format('d/m/Y H:i') }}
                            </div>
                            <div class="text-muted small" style="font-size:0.72rem;">
                                <i class="bi bi-person me-1"></i>{{ $m->user ? $m->user->name : 'Sistem' }}
                            </div>
                        </td>
                        <td class="py-2.5">
                            @if($m->masterProduct)
                                <div class="fw-bold text-dark">{{ $m->masterProduct->name }}</div>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size:0.67rem;">
                                    {{ $m->masterProduct->sku }}
                                </span>
                            @else
                                <span class="text-muted italic">Produk dihapus</span>
                            @endif
                        </td>
                        <td class="text-center py-2.5">
                            @if($m->type === 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 font-monospace" style="font-size:0.73rem;">
                                    <i class="bi bi-arrow-down-left me-1"></i>MASUK
                                </span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 font-monospace" style="font-size:0.73rem;">
                                    <i class="bi bi-arrow-up-right me-1"></i>KELUAR
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 font-monospace" style="font-size:0.73rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i>PENYESUAIAN
                                </span>
                            @endif
                        </td>
                        <td class="text-center fw-bold py-2.5" style="font-size:0.85rem;">
                            @if($m->type === 'in')
                                <span class="text-success">+{{ number_format($m->quantity) }}</span>
                            @elseif($m->type === 'out')
                                <span class="text-danger">-{{ number_format(abs($m->quantity)) }}</span>
                            @else
                                <span class="text-warning-emphasis">{{ number_format($m->quantity) }}</span>
                            @endif
                            <span class="text-muted fw-normal small" style="font-size:0.7rem;">
                                {{ $m->masterProduct ? $m->masterProduct->unit : 'PCS' }}
                            </span>
                        </td>
                        <td class="text-center py-2.5 font-monospace fw-semibold text-dark">
                            {{ number_format($m->balance_after) }}
                        </td>
                        <td class="pe-3 py-2.5 text-muted small" style="font-size:0.78rem;">
                            {{ $m->reference ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-40"></i>
                            Belum ada riwayat mutasi stok gudang jadi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mutations->hasPages())
        <div class="px-3 py-2 border-top bg-light">
            {{ $mutations->links() }}
        </div>
    @endif
</div>

@endsection
