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
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            Gudang Jadi
        </h1>
        <p class="text-muted small mb-0">Kelola riwayat mutasi barang masuk, keluar, dan penyesuaian stok produk jadi</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#2563eb; border:none; border-radius:6px;">
            + Catat Mutasi Gudang
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-primary border-3">
            <div class="gj-kpi-title text-primary">Total Transaksi Mutasi</div>
            <div class="gj-kpi-value text-primary">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="gj-kpi-sub">Total mutasi stok gudang jadi terdaftar</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-success border-3">
            <div class="gj-kpi-title text-success">Total Qty Masuk</div>
            <div class="gj-kpi-value text-success">
                +{{ number_format($totalInbound, 0, ',', '.') }} PCS
            </div>
            <div class="gj-kpi-sub">Akumulasi barang masuk ke gudang</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="gj-kpi-card border-start border-danger border-3">
            <div class="gj-kpi-title text-danger">Total Qty Keluar</div>
            <div class="gj-kpi-value text-danger">
                -{{ number_format($totalOutbound, 0, ',', '.') }} PCS
            </div>
            <div class="gj-kpi-sub">Akumulasi barang keluar dari gudang</div>
        </div>
    </div>
</div>

{{-- ── Filter Controls ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Produk / Referensi</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Ketik nama / SKU / ref..." autocomplete="off">
        </div>

        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-semibold text-muted mb-1">Jenis Mutasi</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Semua Jenis</option>
                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Masuk (+)</option>
                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Keluar (-)</option>
                <option value="adj" {{ request('type') == 'adj' ? 'selected' : '' }}>Penyesuaian (Adj)</option>
            </select>
        </div>

        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label small fw-semibold text-muted mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
        </div>

        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label small fw-semibold text-muted mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
        </div>

        <div class="col-6 col-md-3 col-lg-3 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold flex-fill">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'type', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.index') }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filter">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Table Mutasi Stok Gudang Jadi ── --}}
<div class="v2-card shadow-sm overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO</th>
                    <th class="py-2.5" style="width: 160px;">TANGGAL & WAKTU</th>
                    <th class="py-2.5">PRODUK MASTER & SKU</th>
                    <th class="text-center py-2.5" style="width: 130px;">JENIS MUTASI</th>
                    <th class="text-center py-2.5" style="width: 140px;">QTY MUTASI</th>
                    <th class="text-center py-2.5" style="width: 120px;">STOK AKHIR</th>
                    <th class="text-end pe-3 py-2.5" style="width: 110px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $index => $m)
                    <tr>
                        <td class="ps-3 py-2 text-center text-muted">
                            {{ $mutations->firstItem() + $index }}
                        </td>
                        <td class="py-2">
                            <div class="fw-bold text-dark">{{ $m->created_at ? $m->created_at->format('d/m/Y') : '-' }}</div>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                {{ $m->created_at ? $m->created_at->format('H:i') : '-' }} WIB
                            </span>
                        </td>
                        <td class="py-2">
                            @if($m->masterProduct)
                                <div class="fw-bold text-dark">{{ $m->masterProduct->name }}</div>
                                <span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">
                                    SKU: {{ $m->masterProduct->sku }}
                                </span>
                            @else
                                <span class="text-muted italic">— (Produk Master Tidak Ditemukan)</span>
                            @endif
                        </td>
                        <td class="text-center py-2">
                            @if($m->type === 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-0.5 rounded-pill fw-semibold" style="font-size:0.68rem;">
                                    MASUK
                                </span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-0.5 rounded-pill fw-semibold" style="font-size:0.68rem;">
                                    KELUAR
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-0.5 rounded-pill fw-semibold" style="font-size:0.68rem;">
                                    PENYESUAIAN
                                </span>
                            @endif
                        </td>
                        <td class="text-center py-2">
                            @if($m->type === 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold">
                                    +{{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold">
                                    -{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 fw-bold">
                                    {{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center py-2 font-monospace fw-semibold text-dark">
                            {{ number_format($m->balance_after ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-end pe-3 py-2">
                            <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 fw-semibold" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#detailModal{{ $m->id }}">
                                Detail
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Belum ada riwayat mutasi stok gudang jadi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="p-2.5 border-top bg-light">
            {{ $mutations->links() }}
        </div>
    @endif
</div>

{{-- ── Modals Detail ── --}}
@foreach($mutations as $m)
    <div class="modal fade" id="detailModal{{ $m->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $m->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2.5">
                    <h6 class="modal-title fw-bold text-dark" id="detailModalLabel{{ $m->id }}">
                        Detail Mutasi Stok Gudang Jadi #{{ $m->id }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" style="font-size: 0.83rem;">
                    <div class="border rounded p-2.5 bg-light mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted d-block small">Waktu Transaksi</span>
                                <span class="fw-bold text-dark">{{ $m->created_at ? $m->created_at->format('d/m/Y H:i') : '-' }} WIB</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block small">Jenis Mutasi</span>
                                @if($m->type === 'in')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5">Barang Masuk (+)</span>
                                @elseif($m->type === 'out')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-0.5">Barang Keluar (-)</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-0.5">Penyesuaian</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold d-block mb-1">PRODUK MASTER</label>
                        <div class="fw-bold text-dark fs-6">{{ $m->masterProduct->name ?? '-' }}</div>
                        <div class="text-muted font-monospace small">SKU: {{ $m->masterProduct->sku ?? '-' }}</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="border rounded p-2 text-center bg-light">
                                <span class="text-muted d-block small mb-1">Jumlah Mutasi</span>
                                <span class="fw-bold {{ $m->type === 'in' ? 'text-success' : ($m->type === 'out' ? 'text-danger' : 'text-warning-emphasis') }} fs-5">
                                    {{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '-' : '') }}{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2 text-center bg-light">
                                <span class="text-muted d-block small mb-1">Stok Setelah Mutasi</span>
                                <span class="fw-bold text-dark fs-5">{{ number_format($m->balance_after ?? 0, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold d-block mb-1">KETERANGAN / REFERENSI</label>
                        <div class="p-2 border rounded bg-white text-dark">
                            {{ $m->reference ?: 'Tidak ada catatan referensi.' }}
                        </div>
                    </div>

                    <div>
                        <label class="text-muted small fw-semibold d-block mb-1">PETUGAS OPERASIONAL</label>
                        <div class="fw-semibold text-dark">{{ $m->user->name ?? 'Sistem / Admin' }}</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
