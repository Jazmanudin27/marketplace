@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Banner (Pure Bootstrap 5 White Card) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-box-seam fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Penerimaan Barang Konsinyasi</h4>
                        <p class="text-muted small mb-0">Kelola daftar transaksi penerimaan barang titipan supplier dan persediaan gudang master</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-card-checklist me-1"></i> Kartu Stok Supplier
                    </a>
                    <a href="{{ route('supplier_consignments.create') }}" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="bi bi-plus-lg me-1"></i> Transaksi Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Card (Pure Bootstrap 5) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('supplier_consignments.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Cari Transaksi</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Supplier (Pemilik Barang)</label>
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
                        <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Akhir</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill"><i class="bi bi-filter"></i></button>
                        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card (Pure Bootstrap 5) -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 text-center" style="width: 50px;">NO</th>
                            <th style="width: 18%;">NO REFERENSI</th>
                            <th style="width: 14%;">TANGGAL</th>
                            <th style="width: 25%;">SUPPLIER</th>
                            <th class="text-center" style="width: 12%;">TOTAL QTY</th>
                            <th class="text-end" style="width: 15%;">TOTAL HPP MODAL</th>
                            <th class="text-center" style="width: 12%;">STATUS</th>
                            <th class="text-center pe-4" style="width: 10%;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consignments as $index => $item)
                            <tr>
                                <td class="ps-4 text-center text-muted fw-semibold">{{ $consignments->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('supplier_consignments.show', $item) }}" class="fw-bold text-decoration-none text-primary">
                                        {{ $item->reference_number }}
                                    </a>
                                </td>
                                <td class="text-muted fw-semibold">{{ $item->consignment_date->format('d-m-Y') }}</td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $item->supplier ? $item->supplier->name : '-' }}</span>
                                    <small class="text-muted">{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">
                                        {{ number_format($item->total_qty_received) }} PCS
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark font-monospace">
                                    Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success rounded-pill px-3 py-1">
                                        <i class="bi bi-check-circle me-1"></i>Selesai
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('supplier_consignments.show', $item) }}" class="btn btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" onclick="window.location.href='{{ route('supplier_consignments.show', $item) }}'" class="btn btn-outline-secondary" title="Cetak Faktur">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi Baru</b>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($consignments->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $consignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
