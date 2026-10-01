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

/* ── Custom Badge & Table Styles ── */
.bg-purple-subtle { background-color: #f3e8ff !important; }
.text-purple { color: #7e22ce !important; }
.border-purple-subtle { border-color: #e9d5ff !important; }

.gj-table-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    color: #ffffff !important;
}
.gj-table-header th {
    color: #f8fafc !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.04em !important;
    font-size: 0.74rem !important;
    border: none !important;
    padding: 12px 14px !important;
}
.gj-table-row {
    transition: all 0.15s ease-in-out;
}
.gj-table-row:hover {
    background-color: #f8fafc !important;
    box-shadow: inset 3px 0 0 #3b82f6;
}
.gj-qty-badge-in {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 5px 14px;
    border-radius: 50rem;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
    display: inline-block;
}
.gj-qty-badge-out {
    background: linear-gradient(135deg, #f43f5e, #e11d48);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 5px 14px;
    border-radius: 50rem;
    box-shadow: 0 2px 6px rgba(244, 63, 94, 0.25);
    display: inline-block;
}
.gj-product-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.gj-avatar-initial {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.72rem;
    flex-shrink: 0;
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
<div class="v2-card p-0 shadow-sm overflow-hidden mb-4 border">
    <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3 border border-primary border-opacity-25">
                <i class="bi bi-list-stars fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0">Riwayat Mutasi Stok Gudang Jadi</h6>
                <p class="text-muted small mb-0" style="font-size: 0.74rem;">Seluruh log pergerakan persediaan barang masuk, keluar, dan penyesuaian</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                <i class="bi bi-layers-fill me-1"></i> Total {{ number_format($mutations->total(), 0, ',', '.') }} Data
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="gj-table-header">
                <tr>
                    <th class="ps-3 text-center" style="width: 50px;">NO</th>
                    <th style="width: 150px;"><i class="bi bi-calendar3 me-1"></i> WAKTU</th>
                    <th><i class="bi bi-box-seam me-1"></i> PRODUK MASTER & SKU</th>
                    <th class="text-center" style="width: 140px;"><i class="bi bi-arrow-down-up me-1"></i> JENIS MUTASI</th>
                    <th class="text-center" style="width: 140px;"><i class="bi bi-hash me-1"></i> QTY MUTASI</th>
                    <th><i class="bi bi-bookmark-star me-1"></i> REFERENSI / CATATAN</th>
                    <th class="pe-3" style="width: 150px;"><i class="bi bi-person-badge me-1"></i> PETUGAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $index => $m)
                    @php
                        $userName = $m->user->name ?? 'Sistem';
                        $userInitial = strtoupper(substr($userName, 0, 2));
                    @endphp
                    <tr class="gj-table-row">
                        <td class="ps-3 text-center">
                            <span class="badge bg-light text-secondary border rounded-circle" style="width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">
                                {{ $mutations->firstItem() + $index }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size: 0.83rem;">
                                {{ $m->created_at ? $m->created_at->format('d/m/Y') : '-' }}
                            </div>
                            <div class="text-muted d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                <i class="bi bi-clock"></i> {{ $m->created_at ? $m->created_at->format('H:i') : '-' }} WIB
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="gj-product-icon {{ $m->type === 'in' ? 'bg-success' : ($m->type === 'out' ? 'bg-danger' : 'bg-warning') }} bg-opacity-10 {{ $m->type === 'in' ? 'text-success' : ($m->type === 'out' ? 'text-danger' : 'text-warning') }} border mt-0.5">
                                    <i class="bi {{ $m->type === 'in' ? 'bi-box-arrow-in-down' : ($m->type === 'out' ? 'bi-box-arrow-up-right' : 'bi-arrow-repeat') }}"></i>
                                </div>
                                <div class="overflow-hidden">
                                    @if($m->masterProduct)
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 320px;" title="{{ $m->masterProduct->name }}">
                                            {{ $m->masterProduct->name }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                            <span class="badge bg-slate-100 text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem; background:#f1f5f9;">
                                                <i class="bi bi-qr-code me-1 text-muted"></i>{{ $m->masterProduct->sku }}
                                            </span>
                                            <span class="text-muted" style="font-size: 0.68rem;">
                                                • Stok saat ini: <b>{{ number_format($m->masterProduct->stock, 0, ',', '.') }}</b> {{ $m->masterProduct->unit ?? 'PCS' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted italic">— (Produk Master Tidak Ditemukan)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($m->type === 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size:0.72rem;">
                                    <i class="bi bi-arrow-down-left me-1"></i>MASUK
                                </span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size:0.72rem;">
                                    <i class="bi bi-arrow-up-right me-1"></i>KELUAR
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size:0.72rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i>PENYESUAIAN
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($m->type === 'in')
                                <span class="gj-qty-badge-in">
                                    +{{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            @elseif($m->type === 'out')
                                <span class="gj-qty-badge-out">
                                    -{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            @else
                                <span class="badge bg-warning text-dark px-3 py-1.5 fw-bold" style="font-size:0.85rem;">
                                    {{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="text-secondary fw-semibold text-truncate" style="max-width: 250px; font-size: 0.78rem;" title="{{ $m->reference }}">
                                {{ $m->reference ?: '-' }}
                            </div>
                        </td>
                        <td class="pe-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gj-avatar-initial shadow-sm">
                                    {{ $userInitial }}
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 110px; font-size:0.78rem;" title="{{ $userName }}">
                                        {{ $userName }}
                                    </div>
                                    <div class="text-muted" style="font-size:0.65rem;">Petugas Gudang</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-25"></i>
                            <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Mutasi Gudang</h6>
                            <p class="small text-muted mb-0">Tidak ada riwayat mutasi stok gudang jadi yang sesuai filter.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="p-3 border-top bg-light d-flex align-items-center justify-content-between">
            <span class="text-muted small">Menampilkan {{ $mutations->firstItem() }} - {{ $mutations->lastItem() }} dari {{ $mutations->total() }} data</span>
            <div>{{ $mutations->links() }}</div>
        </div>
    @endif
</div>

@endsection
