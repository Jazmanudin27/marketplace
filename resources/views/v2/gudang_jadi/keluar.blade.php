@extends('v2.layouts.app')

@section('title', 'Mutasi Barang Keluar - Gudang Jadi V2')

@push('styles')
<style>
/* ─── Gudang Jadi V2 Custom Styles ─── */
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
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            Mutasi Barang Keluar (Gudang Jadi)
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create', ['type' => 'out']) }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#dc2626; border:none;">
            + Catat Barang Keluar
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
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
    <!-- Total Transaksi Keluar -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-danger border-3">
            <div class="bk-kpi-title text-danger">Total Transaksi Keluar</div>
            <div class="bk-kpi-value text-danger">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bk-kpi-sub">Total pengeluaran barang dari gudang</div>
        </div>
    </div>
    <!-- Total Qty Dikeluarkan -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-warning border-3">
            <div class="bk-kpi-title text-warning">Total Qty Dikeluarkan</div>
            <div class="bk-kpi-value text-warning">
                -{{ number_format($totalOutboundQty, 0, ',', '.') }} PCS
            </div>
            <div class="bk-kpi-sub">Akumulasi quantity produk dikeluarkan</div>
        </div>
    </div>
    <!-- Variasi Produk -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-primary border-3">
            <div class="bk-kpi-title text-primary">Variasi Produk Master</div>
            <div class="bk-kpi-value text-primary">
                {{ number_format($totalUniqueProducts, 0, ',', '.') }} SKU
            </div>
            <div class="bk-kpi-sub">Jumlah varian produk master</div>
        </div>
    </div>
    <!-- Status Pengeluaran -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-dark border-3">
            <div class="bk-kpi-title text-dark">Status Pengeluaran</div>
            <div class="bk-kpi-value text-dark">
                Tercatat
            </div>
            <div class="bk-kpi-sub">Pencatatan mutasi gudang terkontrol</div>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.keluar') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Nama Produk / SKU / Ref</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, SKU, ref..." value="{{ request('search') }}">
        </div>

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

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
        </div>

        <div class="col-12 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.keluar') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card shadow-sm overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO</th>
                    <th class="py-2.5" style="width: 170px;">TANGGAL & WAKTU</th>
                    <th class="py-2.5">PRODUK MASTER & SKU</th>
                    <th class="text-center py-2.5" style="width: 150px;">QTY KELUAR</th>
                    <th class="text-center py-2.5" style="width: 130px;">STATUS</th>
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
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold">
                                -{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                            </span>
                        </td>
                        <td class="text-center py-2">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size: 0.68rem;">
                                Dikeluarkan
                            </span>
                        </td>
                        <td class="text-end pe-3 py-2">
                            <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 fw-semibold" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#detailModal{{ $m->id }}">
                                Detail
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Belum ada riwayat pengeluaran barang keluar yang sesuai dengan filter.
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
                        Detail Mutasi Barang Keluar #{{ $m->id }}
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
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-0.5">Barang Keluar (-)</span>
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
                                <span class="fw-bold text-danger fs-5">-{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}</span>
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
