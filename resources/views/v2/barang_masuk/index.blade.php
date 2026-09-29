@extends('v2.layouts.app')

@section('title', 'Pemasukan Barang (Barang Masuk) V2')

@push('styles')
<style>
/* ─── Barang Masuk V2 Custom Styles ─── */
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
            <i class="bi bi-box-arrow-in-down text-success fs-5"></i> Pemasukan Barang (Barang Masuk)
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.barang_masuk.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none;">
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
    <!-- Total Transaksi -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-primary border-3">
            <div class="bm-kpi-title text-primary">Total Penerimaan</div>
            <div class="bm-kpi-value text-primary">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bm-kpi-sub">Total surat jalan / dokumen masuk</div>
            <i class="bi bi-box-arrow-in-down bm-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Barang Masuk Disetujui -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-success border-3">
            <div class="bm-kpi-title text-success">Disetujui (Approved)</div>
            <div class="bm-kpi-value text-success">
                {{ number_format($totalApproved, 0, ',', '.') }} Transaksi
            </div>
            <div class="bm-kpi-sub">Stok sudah resmi masuk ke gudang</div>
            <i class="bi bi-check-circle-fill bm-kpi-icon text-success"></i>
        </div>
    </div>
    <!-- Menunggu Approval -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-warning border-3">
            <div class="bm-kpi-title text-warning">Menunggu Approval</div>
            <div class="bm-kpi-value text-warning">
                {{ number_format($totalPending, 0, ',', '.') }} Transaksi
            </div>
            <div class="bm-kpi-sub">Perlu konfirmasi stok masuk</div>
            <i class="bi bi-hourglass-split bm-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Total Nilai Pemasukan -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bm-kpi-card border-start border-dark border-3">
            <div class="bm-kpi-title text-dark">Total Nilai Pemasukan</div>
            <div class="bm-kpi-value text-dark">
                Rp {{ number_format($totalValue, 0, ',', '.') }}
            </div>
            <div class="bm-kpi-sub">Akumulasi nominal pembelian & penerimaan</div>
            <i class="bi bi-cash-stack bm-kpi-icon text-dark"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.barang_masuk.index') }}" class="row g-2 align-items-end">
        <!-- Pencarian -->
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">No. Penerimaan / Supplier / Catatan</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor, supplier..." value="{{ request('search') }}">
        </div>

        <!-- Sumber -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Sumber Penerimaan</label>
            <select name="source" class="form-select form-select-sm">
                <option value="all">Semua Sumber</option>
                <option value="pembelian" {{ request('source') === 'pembelian' ? 'selected' : '' }}>Pembelian</option>
                <option value="produksi" {{ request('source') === 'produksi' ? 'selected' : '' }}>Produksi</option>
                <option value="percetakan" {{ request('source') === 'percetakan' ? 'selected' : '' }}>Percetakan</option>
                <option value="lain_lain" {{ request('source') === 'lain_lain' ? 'selected' : '' }}>Lain-lain</option>
            </select>
        </div>

        <!-- Status -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="all">Semua Status</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending (Belum Approved)</option>
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
            <a href="{{ route('v2.barang_masuk.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;" title="Reset Filter">
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
                    <th class="ps-3 py-2.5" style="width: 220px;">NO. PENERIMAAN & TANGGAL</th>
                    <th class="py-2.5">SUMBER & DEPARTEMEN</th>
                    <th class="py-2.5">SUPPLIER</th>
                    <th class="text-center py-2.5">TOTAL ITEM & QTY</th>
                    <th class="text-end py-2.5">TOTAL NILAI (RP)</th>
                    <th class="text-center py-2.5">STATUS</th>
                    <th class="text-end pe-3 py-2.5" style="width: 170px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $rc)
                    @php
                        $sourceBadgeClass = match($rc->source) {
                            'pembelian'  => 'bg-primary-subtle text-primary border-primary-subtle',
                            'produksi'   => 'bg-success-subtle text-success border-success-subtle',
                            'percetakan' => 'bg-info-subtle text-info border-info-subtle',
                            default      => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                        };
                        $totalQtyCount = $rc->items->sum('quantity');
                    @endphp
                    <tr>
                        <td class="ps-3 py-2.5">
                            <a href="{{ route('v2.barang_masuk.show', $rc) }}" class="fw-bold text-dark text-decoration-none d-block">
                                {{ $rc->receipt_number }}
                            </a>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                <i class="bi bi-calendar-event me-1"></i> {{ $rc->receipt_date ? $rc->receipt_date->format('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $sourceBadgeClass }} border px-2 py-0.5 rounded-pill mb-1" style="font-size: 0.65rem;">
                                {{ strtoupper($rc->source_label) }}
                            </span>
                            <div class="text-secondary small fw-semibold" style="font-size: 0.74rem;">
                                {{ $rc->department ? $rc->department->name : 'Gudang Utama / Umum' }}
                            </div>
                        </td>
                        <td>
                            @if($rc->supplier)
                                <div class="fw-semibold text-dark">{{ $rc->supplier->name }}</div>
                                <span class="text-muted" style="font-size: 0.7rem;">Supplier Resmi</span>
                            @else
                                <span class="text-muted italic">— (Non-Supplier / Internal)</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                {{ $rc->items->count() }} Item ({{ number_format($totalQtyCount, 0, ',', '.') }} Qty)
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace" style="font-size: 0.88rem;">
                            Rp {{ number_format($rc->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($rc->status === 'approved')
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                    <i class="bi bi-hourglass-split me-1"></i> Menunggu Approval
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('v2.barang_masuk.show', $rc) }}" class="btn btn-sm btn-v2-primary px-2 py-1 fw-semibold" title="Lihat Detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                @if($rc->status === 'pending')
                                    <form action="{{ route('v2.barang_masuk.approve', $rc) }}" method="POST" onsubmit="return confirm('Setujui penerimaan barang {{ $rc->receipt_number }} dan tambahkan stok ke gudang?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm text-white px-2 py-1 fw-semibold" style="background:#16a34a; border:none;" title="Setujui & Masukkan Stok">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('v2.barang_masuk.destroy', $rc) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan/menghapus penerimaan {{ $rc->receipt_number }}? Stok akan ditarik kembali jika sudah approved.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm text-white px-2 py-1 fw-semibold" style="background:#dc2626; border:none;" title="Hapus / Batalkan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            Belum ada riwayat penerimaan barang masuk yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($receipts->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $receipts->links() }}
        </div>
    @endif
</div>

@endsection
