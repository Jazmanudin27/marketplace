@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-box-seam text-primary me-2"></i>Penerimaan Barang Jadi Konsinyasi</h3>
            <p class="text-muted small mb-0">Kelola penerimaan barang titipan/outsourcing dari supplier & penambahan stok master produk.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-outline-info shadow-sm">
                <i class="bi bi-card-checklist me-1"></i> Kartu Stok & Mutasi Konsinyasi
            </a>
            <a href="{{ route('supplier_consignments.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Input Barang Titipan Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('supplier_consignments.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-select-sm form-control border-start-0" placeholder="No. Referensi / Ref..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
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
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved (Stok Bertambah)</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Batal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>No. Referensi</th>
                        <th>Tanggal Penerimaan</th>
                        <th>Supplier</th>
                        <th class="text-center">Total Qty Received</th>
                        <th class="text-end">Total HPP / Modal Titip</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($consignments as $index => $item)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $consignments->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('supplier_consignments.show', $item) }}" class="fw-bold text-decoration-none text-primary">
                                    {{ $item->reference_number }}
                                </a>
                            </td>
                            <td>{{ $item->consignment_date->format('d M Y') }}</td>
                            <td>
                                <span class="fw-semibold">{{ $item->supplier ? $item->supplier->name : '-' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill px-3">{{ number_format($item->total_qty_received) }} PCS</span>
                            </td>
                            <td class="text-end fw-semibold text-dark">
                                Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                    <i class="bi bi-check-circle me-1"></i>Selesai (Stok Bertambah)
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="{{ route('supplier_consignments.show', $item) }}" class="btn btn-sm btn-outline-secondary" title="Detail">
                                    <i class="bi bi-eye me-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data penerimaan barang jadi konsinyasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consignments->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $consignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
