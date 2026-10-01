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

/* ── Modal & Table Styling ── */
.gj-modal-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}
.gj-hero-card-in {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: 12px;
}
.gj-avatar-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.78rem;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            Mutasi Barang Masuk (Gudang Jadi)
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create', ['type' => 'in']) }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none;">
            + Catat Penerimaan Masuk
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach (['success', 'error', 'info'] as $type)
    @if (session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Transaksi -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-primary border-3">
            <div class="bm-kpi-title text-primary">Total Transaksi</div>
            <div class="bm-kpi-value text-primary">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bm-kpi-sub">Total mutasi masuk ke gudang jadi</div>
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
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.masuk') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Nama Produk / SKU / Ref</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, SKU, ref..." value="{{ request('search') }}">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Pilih Produk Master</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">Semua Produk Master</option>
                @foreach ($products as $p)
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
            @if (request()->anyFilled(['search', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.masuk') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card p-3 p-md-4 shadow-sm mb-4">
    <div class="table-responsive border rounded-3 overflow-hidden bg-white">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO</th>
                    <th class="py-2.5" style="width: 170px;">TANGGAL & WAKTU</th>
                    <th class="py-2.5">PRODUK MASTER & SKU</th>
                    <th class="text-center py-2.5" style="width: 150px;">QTY MASUK</th>
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
                            @if ($m->masterProduct)
                                <div class="fw-bold text-dark">{{ $m->masterProduct->name }}</div>
                                <span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">
                                    SKU: {{ $m->masterProduct->sku }}
                                </span>
                            @else
                                <span class="text-muted italic">— (Produk Master Tidak Ditemukan)</span>
                            @endif
                        </td>
                        <td class="text-center py-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold">
                                +{{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                            </span>
                        </td>
                        <td class="text-center py-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size: 0.68rem;">
                                Disetujui
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
                            Belum ada riwayat penerimaan barang masuk yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($mutations->hasPages())
        <div class="pt-3 border-top mt-3">
            {{ $mutations->links() }}
        </div>
    @endif
</div>

{{-- ── Premium Detail Modals ── --}}
@foreach ($mutations as $m)
    @php
        $qtyBefore = ($m->balance_after ?? 0) - $m->quantity;
        $userName = $m->user->name ?? 'Sistem / Admin';
        $userInitial = strtoupper(substr($userName, 0, 2));
    @endphp
    <div class="modal fade" id="detailModal{{ $m->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $m->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <!-- Modal Header -->
                <div class="modal-header gj-modal-header py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div>
                            <h6 class="modal-title fw-bold mb-0 text-white" id="detailModalLabel{{ $m->id }}" style="letter-spacing: 0.02em;">
                                RINCIAN MUTASI BARANG MASUK #{{ $m->id }}
                            </h6>
                            <span class="text-white-50 small" style="font-size: 0.72rem;">Portal ERP V2 Gudang Jadi</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4 bg-white" style="font-size: 0.84rem;">
                    <!-- Top Hero Card Banner -->
                    <div class="gj-hero-card-in p-3 mb-3.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="text-success text-uppercase fw-semibold d-block small" style="font-size: 0.68rem; letter-spacing: 0.05em;">JUMLAH PENERIMAAN STOK</span>
                            <div class="fw-bold text-success" style="font-size: 1.7rem; line-height: 1.1;">
                                +{{ number_format($m->quantity, 0, ',', '.') }} <span style="font-size: 1rem;">{{ $m->masterProduct->unit ?? 'PCS' }}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-semibold shadow-sm mb-1 d-inline-block" style="font-size: 0.72rem;">
                                Disetujui (Approved)
                            </span>
                            <div class="text-secondary small fw-semibold" style="font-size: 0.75rem;">
                                Tanggal: {{ $m->created_at ? $m->created_at->format('d/m/Y H:i') : '-' }} WIB
                            </div>
                        </div>
                    </div>

                    <!-- Produk Master Info Card -->
                    <div class="card border rounded-3 p-3 mb-3 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-muted fw-bold small uppercase" style="font-size: 0.68rem; letter-spacing: 0.04em;">PRODUK MASTER</span>
                            <span class="badge bg-white text-dark border font-monospace px-2 py-0.5" style="font-size: 0.7rem;">
                                SKU: {{ $m->masterProduct->sku ?? '-' }}
                            </span>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1">
                            {{ $m->masterProduct->name ?? '-' }}
                        </div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            Satuan Utama: <b>{{ $m->masterProduct->unit ?? 'PCS' }}</b>
                        </div>
                    </div>

                    <!-- Stats Grid Comparison -->
                    <div class="row g-2 mb-3.5">
                        <div class="col-4">
                            <div class="border rounded-3 p-2.5 text-center bg-white shadow-xs">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;">STOK SEBELUM</span>
                                <span class="fw-bold text-secondary fs-6">{{ number_format($qtyBefore, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-2.5 text-center bg-success bg-opacity-10 border-success border-opacity-25 shadow-xs">
                                <span class="text-success d-block small fw-semibold mb-1" style="font-size: 0.7rem;">MUTASI MASUK</span>
                                <span class="fw-bold text-success fs-6">+{{ number_format($m->quantity, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-2.5 text-center bg-white shadow-xs">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;">STOK SESUDAH</span>
                                <span class="fw-bold text-dark fs-6">{{ number_format($m->balance_after ?? 0, 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan & Referensi Box -->
                    <div class="mb-3.5">
                        <label class="text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">KETERANGAN / REFERENSI DOKUMEN</label>
                        <div class="p-2.5 border rounded-3 bg-light text-dark font-monospace" style="font-size: 0.8rem; word-break: break-word;">
                            {{ $m->reference ?: 'Tidak ada catatan referensi tambahan.' }}
                        </div>
                    </div>

                    <!-- Petugas Operasional Card -->
                    <div class="d-flex align-items-center justify-content-between p-2.5 border rounded-3 bg-light">
                        <div class="d-flex align-items-center gap-2">
                            <div class="gj-avatar-circle shadow-sm">
                                {{ $userInitial }}
                            </div>
                            <div>
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Operator Pencatat</span>
                                <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ $userName }}</span>
                            </div>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1" style="font-size: 0.68rem;">
                            Log System Activity
                        </span>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light py-2.5 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
