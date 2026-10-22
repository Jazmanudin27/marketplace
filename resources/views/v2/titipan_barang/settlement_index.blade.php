@extends('v2.layouts.app')

@section('title', 'Riwayat Setoran Supplier V2')

@push('styles')
<style>
.titipan-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.titipan-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.titipan-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.titipan-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.titipan-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.titipan-kpi-icon {
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
            <i class="bi bi-cash-stack text-success fs-5"></i> Riwayat Setoran Pembayaran ke Supplier
        </h1>
        <p class="text-muted small mb-0 mt-0.5" style="font-size: 0.78rem;">Daftar transaksi bukti pembayaran setoran hasil penjualan barang titipan / konsinyasi kepada supplier</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 rounded-2 fw-semibold">
            <i class="bi bi-card-checklist me-1"></i> Kartu Stok Supplier
        </a>
        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm btn-outline-primary py-1.5 px-3 rounded-2 fw-semibold">
            <i class="bi bi-box-seam me-1"></i> Penerimaan Titipan
        </a>
        <a href="{{ route('v2.titipan_barang.settlement.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none; border-radius:6px;">
            <i class="bi bi-plus-lg me-1"></i> Form Setoran Baru
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <div class="col-12 col-md-4">
        <div class="titipan-kpi-card border-start border-primary border-3">
            <div class="titipan-kpi-title text-primary">Total Transaksi Setoran</div>
            <div class="titipan-kpi-value text-dark">
                {{ number_format($settlements->total(), 0, ',', '.') }} Transaksi
            </div>
            <div class="titipan-kpi-sub">Jumlah bukti pembayaran setoran tercatat</div>
            <i class="bi bi-receipt titipan-kpi-icon text-primary"></i>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="titipan-kpi-card border-start border-info border-3">
            <div class="titipan-kpi-title text-info">Total Qty Dialokasikan</div>
            <div class="titipan-kpi-value text-info font-monospace">
                {{ number_format($totalQtySettled, 0, ',', '.') }} PCS
            </div>
            <div class="titipan-kpi-sub">Total pcs barang konsinyasi disetorkan</div>
            <i class="bi bi-boxes titipan-kpi-icon text-info"></i>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="titipan-kpi-card border-start border-success border-3">
            <div class="titipan-kpi-title text-success">Total Akumulasi Pembayaran</div>
            <div class="titipan-kpi-value text-success font-monospace">
                Rp {{ number_format($totalAmountSettled, 0, ',', '.') }}
            </div>
            <div class="titipan-kpi-sub">Uang disetorkan ke supplier & tercatat di keuangan</div>
            <i class="bi bi-cash-coin titipan-kpi-icon text-success"></i>
        </div>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('v2.titipan_barang.settlement.index') }}">
            <div class="row g-2.5 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Cari No. Setoran / Ref</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="STR-... / No. Ref..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm select2">
                        <option value="">-- Semua Supplier --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Tanggal Mulai</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Tanggal Akhir</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex gap-1.5">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'supplier_id', 'date_from', 'date_to']))
                        <a href="{{ route('v2.titipan_barang.settlement.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Main Table Card ── --}}
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-3 py-2.5" style="width: 17%;">NO. SETORAN</th>
                        <th class="py-2.5" style="width: 10%;">TANGGAL</th>
                        <th class="py-2.5" style="width: 20%;">SUPPLIER</th>
                        <th class="py-2.5" style="width: 18%;">METODE PEMBAYARAN</th>
                        <th class="py-2.5 text-center" style="width: 10%;">QTY DISETOR</th>
                        <th class="py-2.5 text-end" style="width: 15%;">TOTAL PEMBAYARAN</th>
                        <th class="pe-3 py-2.5 text-center" style="width: 10%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlements as $settlement)
                        <tr>
                            <td class="ps-3 py-2.5">
                                <a href="{{ route('v2.titipan_barang.settlement.show', $settlement) }}" class="fw-bold text-primary font-monospace text-decoration-none" style="font-size: 0.85rem;">
                                    {{ $settlement->settlement_number }}
                                </a>
                                @if($settlement->reference_number)
                                    <div class="text-muted small font-monospace" style="font-size: 0.7rem;">Ref: {{ $settlement->reference_number }}</div>
                                @endif
                            </td>
                            <td class="py-2.5">
                                <span class="text-dark">{{ $settlement->settlement_date ? $settlement->settlement_date->format('d/m/Y') : '-' }}</span>
                            </td>
                            <td class="py-2.5">
                                <div class="fw-semibold text-dark">{{ $settlement->supplier ? $settlement->supplier->name : 'Supplier' }}</div>
                                @if($settlement->supplier && $settlement->supplier->phone)
                                    <div class="text-muted small" style="font-size: 0.7rem;"><i class="bi bi-telephone me-1"></i>{{ $settlement->supplier->phone }}</div>
                                @endif
                            </td>
                            <td class="py-2.5">
                                <span class="badge {{ $settlement->payment_method === 'transfer' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-2 py-0.5" style="font-size: 0.68rem;">
                                    <i class="bi {{ $settlement->payment_method === 'transfer' ? 'bi-bank' : 'bi-cash' }} me-1"></i>
                                    {{ $settlement->payment_method === 'transfer' ? 'Transfer Bank' : 'Tunai / Kas' }}
                                </span>
                                @if($settlement->bankAccount)
                                    <div class="text-muted small mt-0.5" style="font-size: 0.7rem;">
                                        {{ $settlement->bankAccount->bank_name }} - {{ $settlement->bankAccount->account_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-2.5 text-center font-monospace">
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                    {{ number_format($settlement->total_qty_settled) }} PCS
                                </span>
                            </td>
                            <td class="py-2.5 text-end font-monospace fw-bold text-success" style="font-size: 0.88rem;">
                                Rp {{ number_format($settlement->total_amount_paid, 0, ',', '.') }}
                            </td>
                            <td class="pe-3 py-2.5 text-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('v2.titipan_barang.settlement.show', $settlement) }}" class="btn btn-xs btn-outline-primary py-1 px-2 rounded" title="Lihat Bukti Setoran">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('supplier_consignments.settlement.destroy', $settlement) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi bukti setoran {{ $settlement->settlement_number }} ini? Catatan pengeluaran kas terkait juga akan dihapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2 rounded" title="Hapus Bukti Setoran">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada riwayat transaksi setoran pembayaran supplier yang sesuai dengan filter.
                                <div class="mt-2">
                                    <a href="{{ route('v2.titipan_barang.settlement.create') }}" class="btn btn-sm btn-success fw-semibold py-1 px-3">
                                        <i class="bi bi-plus-lg me-1"></i> Buat Setoran Baru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($settlements->hasPages())
        <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                Menampilkan {{ $settlements->firstItem() ?? 0 }} - {{ $settlements->lastItem() ?? 0 }} dari {{ $settlements->total() }} data
            </span>
            <div>
                {{ $settlements->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@endsection
