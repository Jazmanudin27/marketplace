@extends('v2.layouts.app')

@section('title', 'Penerimaan Barang Konsinyasi (Titipan Supplier) V2')

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
            <i class="bi bi-box-seam text-warning fs-5"></i> Penerimaan Barang Titipan (Konsinyasi)
        </h1>
        <p class="text-muted small mb-0">Kelola daftar transaksi penerimaan barang titipan supplier dan persediaan gudang master</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 rounded-2 fw-semibold">
            <i class="bi bi-card-checklist me-1"></i> Kartu Stok Supplier
        </a>
        <a href="{{ route('supplier_consignments.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#f59e0b; border:none; border-radius:6px;">
            <i class="bi bi-plus-lg me-1"></i> Transaksi Baru
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
        <div class="titipan-kpi-card border-start border-warning border-3">
            <div class="titipan-kpi-title text-warning">Total Transaksi</div>
            <div class="titipan-kpi-value text-dark">
                {{ number_format($consignments->total(), 0, ',', '.') }} Transaksi
            </div>
            <div class="titipan-kpi-sub">Total penerimaan konsinyasi terdaftar</div>
            <i class="bi bi-box-seam titipan-kpi-icon text-warning"></i>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="titipan-kpi-card border-start border-success border-3">
            <div class="titipan-kpi-title text-success">Total Qty Diterima</div>
            <div class="titipan-kpi-value text-success">
                {{ number_format($consignments->sum('total_qty_received'), 0, ',', '.') }} PCS
            </div>
            <div class="titipan-kpi-sub">Jumlah pcs barang titipan</div>
            <i class="bi bi-boxes titipan-kpi-icon text-success"></i>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="titipan-kpi-card border-start border-primary border-3">
            <div class="titipan-kpi-title text-primary">Total HPP Modal</div>
            <div class="titipan-kpi-value text-primary font-monospace">
                Rp {{ number_format($consignments->sum('total_amount_hpp'), 0, ',', '.') }}
            </div>
            <div class="titipan-kpi-sub">Akumulasi estimasi nilai modal barang</div>
            <i class="bi bi-cash-stack titipan-kpi-icon text-primary"></i>
        </div>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('supplier_consignments.index') }}">
            <div class="row g-2.5 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Cari Transaksi</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Supplier (Pemilik Barang)</label>
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
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="bi bi-filter"></i></button>
                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Table Card ── --}}
<div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
            <thead class="table-light text-uppercase small fw-bold text-secondary border-bottom">
                <tr>
                    <th class="ps-3 text-center" style="width: 50px;">NO</th>
                    <th style="width: 18%;">NO REFERENSI</th>
                    <th style="width: 12%;">TANGGAL</th>
                    <th style="width: 25%;">SUPPLIER</th>
                    <th class="text-center" style="width: 12%;">TOTAL QTY</th>
                    <th class="text-end" style="width: 15%;">TOTAL HPP MODAL</th>
                    <th class="text-center" style="width: 10%;">STATUS</th>
                    <th class="text-center pe-3" style="width: 12%;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consignments as $index => $item)
                    <tr>
                        <td class="ps-3 text-center text-muted fw-semibold">{{ $consignments->firstItem() + $index }}</td>
                        <td>
                            <a href="{{ route('supplier_consignments.show', $item) }}" class="fw-bold text-decoration-none text-primary">
                                {{ $item->reference_number }}
                            </a>
                        </td>
                        <td class="text-secondary fw-medium">{{ $item->consignment_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="fw-bold text-dark d-block">{{ $item->supplier ? $item->supplier->name : '-' }}</span>
                            <small class="text-muted">{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-semibold">
                                {{ number_format($item->total_qty_received) }} PCS
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace">
                            Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                <i class="bi bi-check-circle me-1"></i>Selesai
                            </span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('supplier_consignments.print_labels', $item) }}" target="_blank" class="btn btn-outline-success py-1 px-2" title="Cetak Barcode Label">
                                    <i class="bi bi-upc-scan"></i>
                                </a>
                                <a href="{{ route('supplier_consignments.show', $item) }}" class="btn btn-outline-primary py-1 px-2" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('supplier_consignments.edit', $item) }}" class="btn btn-outline-warning text-dark py-1 px-2" title="Edit Transaksi">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('supplier_consignments.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi {{ $item->reference_number }}? Stok produk master akan dikurangi kembali.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger py-1 px-2" title="Hapus Transaksi">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi Baru</b>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($consignments->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $consignments->links() }}
        </div>
    @endif
</div>
@endsection
