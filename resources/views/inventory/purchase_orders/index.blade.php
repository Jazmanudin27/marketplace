@extends('layouts.app')
@section('title', 'Daftar Purchase Order')
@section('page-title', 'Purchase Order Supplier')

@section('content')
<div class="card border rounded shadow-sm bg-white">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-file-invoice text-primary me-2"></i>Daftar Purchase Order</h5>
            <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary btn-sm fw-semibold">
                <i class="fas fa-plus me-1"></i> Buat PO Baru
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted">Cari Nomor PO</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Contoh: PO-2026...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="ordered" {{ request('status') === 'ordered' ? 'selected' : '' }}>Ordered</option>
                    <option value="partially_received" {{ request('status') === 'partially_received' ? 'selected' : '' }}>Diterima Sebagian</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Selesai Diterima</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search', 'supplier_id', 'status']))
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-striped table-hover border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>NO. PO</th>
                        <th>SUPPLIER</th>
                        <th>DEPARTEMEN</th>
                        <th>TANGGAL PO</th>
                        <th class="text-end">TOTAL NOMINAL</th>
                        <th>STATUS</th>
                        <th class="text-center" style="width: 150px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td class="font-monospace fw-bold text-dark">{{ $po->po_number }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $po->supplier->name }}</div>
                                <div class="small text-muted">{{ $po->supplier->phone }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $po->department ? $po->department->name : '-' }}</span></td>
                            <td>{{ $po->po_date->format('d M Y') }}</td>
                            <td class="font-monospace text-end fw-semibold text-dark">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-{{ $po->status_badge }} py-2 px-3 small text-uppercase">
                                    {{ $po->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('purchase_orders.show', $po) }}" class="btn btn-info btn-sm text-white" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(in_array($po->status, ['draft', 'ordered']))
                                        <a href="{{ route('purchase_orders.edit', $po) }}" class="btn btn-warning btn-sm text-white" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    @if($po->status === 'draft')
                                        <form action="{{ route('purchase_orders.destroy', $po) }}" method="POST" onsubmit="return confirm('Hapus Purchase Order ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-file-invoice fa-2x mb-3 text-secondary opacity-25"></i>
                                <p class="mb-0 small">Belum ada Purchase Order yang tercatat.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $purchaseOrders->links() }}
        </div>
    </div>
</div>
@endsection
