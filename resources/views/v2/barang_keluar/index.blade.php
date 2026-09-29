@extends('v2.layouts.app')

@section('title', 'Pengeluaran Barang (Barang Keluar) V2')

@push('styles')
<style>
/* ─── Barang Keluar V2 Custom Styles ─── */
.bk-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.bk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.bk-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.bk-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.bk-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.bk-kpi-icon {
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
            <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Pengeluaran Barang (Barang Keluar)
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.barang_keluar.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#dc2626; border:none;">
            <i class="bi bi-plus-lg me-1"></i> Catat Barang Keluar
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
    <!-- Total Transaksi Keluar -->
    <div class="col-12 col-md-4">
        <div class="bk-kpi-card border-start border-danger border-3">
            <div class="bk-kpi-title text-danger">Total Transaksi Keluar</div>
            <div class="bk-kpi-value text-danger">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bk-kpi-sub">Total surat pengeluaran bahan & barang</div>
            <i class="bi bi-box-arrow-up-right bk-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Total Qty Keluar -->
    <div class="col-12 col-md-4">
        <div class="bk-kpi-card border-start border-warning border-3">
            <div class="bk-kpi-title text-warning">Total Qty Dikeluarkan</div>
            <div class="bk-kpi-value text-warning">
                {{ number_format($totalItemsCount, 0, ',', '.') }} Qty (Pcs/Meter)
            </div>
            <div class="bk-kpi-sub">Akumulasi quantity bahan keluar</div>
            <i class="bi bi-boxes bk-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Total Nilai Pengeluaran -->
    <div class="col-12 col-md-4">
        <div class="bk-kpi-card border-start border-primary border-3">
            <div class="bk-kpi-title text-primary">Total Nilai Pengeluaran</div>
            <div class="bk-kpi-value text-primary">
                Rp {{ number_format($totalValue, 0, ',', '.') }}
            </div>
            <div class="bk-kpi-sub">Estimasi nilai HPP bahan yang dikeluarkan</div>
            <i class="bi bi-cash-stack bk-kpi-icon text-primary"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.barang_keluar.index') }}" class="row g-2 align-items-end">
        <!-- Pencarian -->
        <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1">No. Pengeluaran / SPK / Catatan</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor, SPK, catatan..." value="{{ request('search') }}">
        </div>

        <!-- Tujuan Departemen -->
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Tujuan Departemen</label>
            <select name="to_department_id" class="form-select form-select-sm">
                <option value="all">Semua Departemen</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('to_department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Rentang Tanggal -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Selesai</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>

        <!-- Action Buttons Inline -->
        <div class="col-12 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;" title="Terapkan Filter">
                <i class="bi bi-funnel"></i>
            </button>
            <a href="{{ route('v2.barang_keluar.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;" title="Reset Filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 220px;">NO. PENGELUARAN & TANGGAL</th>
                    <th class="py-2.5">TUJUAN DEPARTEMEN</th>
                    <th class="py-2.5">SPK / REFERENSI / CATATAN</th>
                    <th class="text-center py-2.5">TOTAL ITEM & QTY</th>
                    <th class="text-end py-2.5">TOTAL NILAI (RP)</th>
                    <th class="py-2.5">OPERATOR</th>
                    <th class="text-end pe-3 py-2.5" style="width: 150px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $m)
                    @php
                        $subtotalValue = $m->items->sum(function($item) {
                            return $item->quantity * $item->unit_price;
                        });
                        $totalQty = $m->items->sum('quantity');
                    @endphp
                    <tr>
                        <td class="ps-3 py-2.5">
                            <a href="{{ route('v2.barang_keluar.show', $m) }}" class="fw-bold text-dark text-decoration-none d-block">
                                {{ $m->mutation_number }}
                            </a>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                <i class="bi bi-calendar-event me-1"></i> {{ $m->mutation_date ? $m->mutation_date->format('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <td>
                            @if($m->toDepartment)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                    {{ strtoupper($m->toDepartment->name) }}
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.68rem;">
                                    Lain-lain / General
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($m->spk)
                                <a href="{{ Route::has('spks.show') ? route('spks.show', $m->spk) : '#' }}" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 text-decoration-none fw-bold" style="font-size:0.68rem;">
                                    <i class="bi bi-file-earmark-text me-1"></i> SPK #{{ $m->spk->no_spk }}
                                </a>
                            @endif
                            <div class="text-secondary small text-truncate" style="max-width: 220px; font-size:0.75rem;" title="{{ $m->notes }}">
                                {{ $m->notes ?: '—' }}
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                {{ $m->items->count() }} Varian ({{ number_format($totalQty, 0, ',', '.') }} Qty)
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace" style="font-size: 0.88rem;">
                            Rp {{ number_format($subtotalValue, 0, ',', '.') }}
                        </td>
                        <td>
                            <div class="fw-semibold text-dark" style="font-size: 0.78rem;">
                                {{ $m->createdBy->name ?? 'Sistem' }}
                            </div>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('v2.barang_keluar.show', $m) }}" class="btn btn-sm btn-v2-primary px-2 py-1 fw-semibold" title="Lihat Detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <form action="{{ route('v2.barang_keluar.destroy', $m) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan/menghapus pengeluaran barang {{ $m->mutation_number }}? Stok akan dikembalikan ke gudang.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm text-white px-2 py-1 fw-semibold" style="background:#dc2626; border:none;" title="Batalkan & Kembalikan Stok">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-box-arrow-up-right fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            Belum ada riwayat pengeluaran barang keluar yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $mutations->links() }}
        </div>
    @endif
</div>

@endsection
