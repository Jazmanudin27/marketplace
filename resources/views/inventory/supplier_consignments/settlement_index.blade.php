@extends('v2.layouts.app')

@section('title', 'Riwayat Setoran Pembayaran Supplier V2')

@push('styles')
<style>
/* ─── Settlement Index V2 Custom Styles ─── */
.si-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.si-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.si-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.si-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.si-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.si-kpi-icon {
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
            <i class="bi bi-cash-stack text-success fs-5"></i> Riwayat Setoran Pembayaran Supplier
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-sm btn-outline-secondary fw-semibold px-3">
            <i class="bi bi-card-checklist me-1"></i> Kartu Stok
        </a>
        <a href="{{ route('supplier_consignments.settlement.create') }}"
            class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none;">
            <i class="bi bi-plus-lg me-1"></i> Input Setoran Baru
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : 'success' }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : 'check-circle' }} me-2"></i>
            {{ session($type) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    {{-- Total Transaksi Setoran --}}
    <div class="col-12 col-md-4">
        <div class="si-kpi-card border-start border-success border-3">
            <div class="si-kpi-title text-success">Total Transaksi Setoran</div>
            <div class="si-kpi-value text-success">
                {{ number_format($settlements->total(), 0, ',', '.') }} Setoran
            </div>
            <div class="si-kpi-sub">Total bukti pembayaran ke supplier</div>
            <i class="bi bi-cash-stack si-kpi-icon text-success"></i>
        </div>
    </div>

    {{-- Total Qty Disetor --}}
    <div class="col-12 col-md-4">
        <div class="si-kpi-card border-start border-info border-3">
            <div class="si-kpi-title text-info">Total Qty Disetor</div>
            <div class="si-kpi-value text-info">
                {{ number_format($totalQtySettled, 0, ',', '.') }} PCS
            </div>
            <div class="si-kpi-sub">Akumulasi qty barang yang sudah disetor</div>
            <i class="bi bi-boxes si-kpi-icon text-info"></i>
        </div>
    </div>

    {{-- Total Nilai Setoran --}}
    <div class="col-12 col-md-4">
        <div class="si-kpi-card border-start border-primary border-3">
            <div class="si-kpi-title text-primary">Total Nilai Setoran</div>
            <div class="si-kpi-value text-primary font-monospace" style="font-size:1rem;">
                Rp {{ number_format($totalAmountSettled, 0, ',', '.') }}
            </div>
            <div class="si-kpi-sub">Akumulasi rupiah yang sudah dibayar ke supplier</div>
            <i class="bi bi-bank si-kpi-icon text-primary"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('supplier_consignments.settlement.index') }}" class="row g-2 align-items-end">
        {{-- Pencarian --}}
        <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1">No. Setoran / No. Referensi Transfer</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Cari no. setoran, ref transfer..." value="{{ request('search') }}">
        </div>

        {{-- Supplier --}}
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Supplier (Penerima)</label>
            <select name="supplier_id" class="form-select form-select-sm">
                <option value="">-- Semua Supplier --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Rentang Tanggal --}}
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Selesai</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>

        {{-- Action Buttons --}}
        <div class="col-12 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1"
                style="background:#1e293b; border:none;" title="Terapkan Filter">
                <i class="bi bi-funnel"></i>
            </button>
            <a href="{{ route('supplier_consignments.settlement.index') }}"
                class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;" title="Reset Filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Data Table ── --}}
<div class="v2-card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 200px;">NO. SETORAN & TANGGAL</th>
                    <th class="py-2.5">SUPPLIER (PENERIMA)</th>
                    <th class="py-2.5">METODE BAYAR & REF</th>
                    <th class="text-center py-2.5">TOTAL QTY</th>
                    <th class="text-end py-2.5">TOTAL SETORAN (RP)</th>
                    <th class="py-2.5">DICATAT OLEH</th>
                    <th class="text-end pe-3 py-2.5" style="width: 130px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $item)
                    <tr>
                        <td class="ps-3 py-2.5">
                            <a href="{{ route('supplier_consignments.settlement.show', $item) }}"
                                class="fw-bold text-dark text-decoration-none d-block">
                                {{ $item->settlement_number }}
                            </a>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                <i class="bi bi-calendar-event me-1"></i>
                                {{ $item->settlement_date->format('d/m/Y') }}
                            </span>
                        </td>

                        <td>
                            <div class="fw-semibold text-dark">{{ $item->supplier ? $item->supplier->name : '-' }}</div>
                            @if($item->supplier && $item->supplier->phone)
                                <span class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-telephone me-1"></i>{{ $item->supplier->phone }}
                                </span>
                            @endif
                        </td>

                        <td>
                            @if($item->payment_method === 'transfer')
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 fw-semibold mb-1" style="font-size: 0.68rem;">
                                    <i class="bi bi-credit-card me-1"></i>Transfer Bank
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-semibold mb-1" style="font-size: 0.68rem;">
                                    <i class="bi bi-cash me-1"></i>Kas / Tunai
                                </span>
                            @endif
                            @if($item->transfer_reference)
                                <div class="text-muted font-monospace" style="font-size: 0.7rem;">
                                    Ref: {{ $item->transfer_reference }}
                                </div>
                            @endif
                        </td>

                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                {{ number_format($item->total_qty_settled) }} PCS
                            </span>
                        </td>

                        <td class="text-end fw-bold text-success font-monospace" style="font-size: 0.88rem;">
                            Rp {{ number_format($item->total_amount_paid, 0, ',', '.') }}
                        </td>

                        <td>
                            <div class="fw-semibold text-dark" style="font-size: 0.78rem;">
                                {{ $item->creator ? $item->creator->name : 'Sistem' }}
                            </div>
                            <div class="text-muted" style="font-size: 0.68rem;">
                                {{ $item->created_at->format('d/m/Y H:i') }}
                            </div>
                        </td>

                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('supplier_consignments.settlement.show', $item) }}"
                                    class="btn btn-sm btn-v2-primary px-2 py-1 fw-semibold" title="Lihat Detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <form action="{{ route('supplier_consignments.settlement.destroy', $item) }}"
                                    method="POST"
                                    onsubmit="return confirm('Hapus bukti setoran {{ $item->settlement_number }}? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm text-white px-2 py-1 fw-semibold"
                                        style="background:#dc2626; border:none;" title="Hapus Riwayat">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada riwayat setoran pembayaran ke supplier.
                            <a href="{{ route('supplier_consignments.settlement.create') }}" class="d-block mt-1 fw-semibold text-success">
                                + Input Setoran Baru
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($settlements->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $settlements->links() }}
        </div>
    @endif
</div>

@endsection
