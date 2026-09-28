@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Card (Compact Portal Style) -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; min-width: 44px;">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0.5 text-dark" style="letter-spacing: -0.2px;">Riwayat Setoran Pembayaran Supplier</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 small fw-semibold" style="font-size: 0.7rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Bukti Setoran
                            </span>
                            <span class="text-muted small" style="font-size: 0.78rem;">Kelola daftar bukti pembayaran setoran barang titipan/konsinyasi kepada supplier</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5">
                        <i class="bi bi-card-checklist"></i> Kartu Stok Supplier
                    </a>
                    <a href="{{ route('supplier_consignments.settlement.create') }}" class="btn btn-success btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 shadow-sm">
                        <i class="bi bi-plus-circle-fill"></i> Input Setoran Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-3 py-2 px-3 small" role="alert">
            <i class="bi bi-check-circle-fill me-1.5"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3 py-2 px-3 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1.5"></i>{{ session('error') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Section Card Container -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <!-- Section Header Bar -->
        <div class="card-header bg-white p-3 border-bottom" style="border-color: #f1f5f9 !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt fs-5 text-success"></i>
                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Daftar Riwayat Pembayaran Setoran Titipan</h6>
                </div>
            </div>
        </div>

        <div class="card-body p-3">
            <!-- Filter Bar Form -->
            <form method="GET" action="{{ route('supplier_consignments.settlement.index') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Cari Setoran</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="No. Setoran / No. Ref..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Supplier (Penerima)</label>
                        <select name="supplier_id" class="form-select form-select-sm">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Tanggal Akhir</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-1.5">
                        <button type="submit" class="btn btn-success btn-sm w-100 rounded-2 fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="{{ route('supplier_consignments.settlement.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2.5 d-flex align-items-center justify-content-center gap-1" title="Muat Ulang / Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-lg-inline small">Muat Ulang</span>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Table Container (table-sm) -->
            <div class="table-responsive rounded-2 border" style="border-color: #e2e8f0 !important;">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                    <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="ps-2.5 text-center text-muted fw-bold text-uppercase py-2" style="width: 45px; font-size: 0.7rem; letter-spacing: 0.4px;">NO</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 18%; font-size: 0.7rem; letter-spacing: 0.4px;">NO. SETORAN</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 14%; font-size: 0.7rem; letter-spacing: 0.4px;">TANGGAL SETORAN</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 25%; font-size: 0.7rem; letter-spacing: 0.4px;">SUPPLIER</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 15%; font-size: 0.7rem; letter-spacing: 0.4px;">METODE BAYAR</th>
                            <th class="text-center text-muted fw-bold text-uppercase py-2" style="width: 10%; font-size: 0.7rem; letter-spacing: 0.4px;">TOTAL QTY</th>
                            <th class="text-end text-muted fw-bold text-uppercase py-2" style="width: 15%; font-size: 0.7rem; letter-spacing: 0.4px;">TOTAL SETORAN</th>
                            <th class="text-center pe-2.5 text-muted fw-bold text-uppercase py-2" style="width: 10%; font-size: 0.7rem; letter-spacing: 0.4px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $index => $item)
                            <tr>
                                <td class="ps-2.5 text-center text-muted fw-bold py-1.5" style="font-size: 0.8rem;">{{ $settlements->firstItem() + $index }}</td>
                                <td class="py-1.5">
                                    <a href="{{ route('supplier_consignments.settlement.show', $item) }}" class="fw-bold text-decoration-none text-success" style="font-size: 0.83rem;">
                                        {{ $item->settlement_number }}
                                    </a>
                                </td>
                                <td class="text-dark fw-semibold py-1.5" style="font-size: 0.8rem;">
                                    {{ $item->settlement_date->format('d/m/Y') }}
                                </td>
                                <td class="py-1.5">
                                    <div class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $item->supplier ? $item->supplier->name : '-' }}</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-telephone me-1"></i>{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}
                                    </small>
                                </td>
                                <td class="py-1.5">
                                    @if($item->payment_method === 'transfer')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.72rem;">
                                            <i class="bi bi-credit-card me-1"></i>Transfer Bank
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.72rem;">
                                            <i class="bi bi-cash me-1"></i>Kas / Tunai
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center py-1.5">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.74rem;">
                                        {{ number_format($item->total_qty_settled) }} PCS
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success font-monospace py-1.5" style="font-size: 0.82rem;">
                                    Rp {{ number_format($item->total_amount_paid, 0, ',', '.') }}
                                </td>
                                <td class="text-center pe-2.5 py-1.5">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('supplier_consignments.settlement.show', $item) }}" class="btn btn-outline-success btn-sm py-0 px-1.5" title="Lihat Bukti Setoran">
                                            <i class="bi bi-eye" style="font-size: 0.78rem;"></i>
                                        </a>
                                        <form action="{{ route('supplier_consignments.settlement.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bukti setoran {{ $item->settlement_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1.5" title="Hapus Riwayat">
                                                <i class="bi bi-trash" style="font-size: 0.78rem;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                    Belum ada riwayat setoran pembayaran ke supplier. Silakan klik <b>Input Setoran Baru</b>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($settlements->hasPages())
            <div class="card-footer bg-white border-top-0 py-2 px-3 small">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
