@extends('layouts.app')
@section('title', 'Daftar Retur Pembelian')
@section('page-title', 'Retur Pembelian')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:linear-gradient(135deg,#ef4444,#dc2626)">
                    <i class="fas fa-undo-alt text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Retur Pembelian</h5>
                    <div class="text-muted small">Pengembalian barang ke supplier</div>
                </div>
            </div>
            <a href="{{ route('purchase_returns.create') }}" class="btn btn-danger fw-semibold btn-sm px-3">
                <i class="fas fa-plus me-1"></i> Buat Retur Baru
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted">Cari No. Retur</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="RTN-2026...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Dikirim</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-search me-1"></i> Filter</button>
                @if(request()->anyFilled(['search','supplier_id','status']))
                    <a href="{{ route('purchase_returns.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#fef2f2">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">No. Retur</th>
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-center" style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $retur)
                        <tr>
                            <td class="font-monospace fw-bold text-dark px-3 py-3" style="font-size:13px">{{ $retur->return_number }}</td>
                            <td>
                                <a href="{{ route('purchase_orders.show', $retur->purchase_order_id) }}" class="text-primary small fw-semibold">
                                    {{ $retur->purchaseOrder->po_number ?? '-' }}
                                </a>
                            </td>
                            <td class="small fw-semibold text-dark">{{ $retur->supplier->name ?? '-' }}</td>
                            <td class="small text-muted">{{ $retur->return_date->format('d M Y') }}</td>
                            <td class="small text-muted" style="max-width:180px">
                                <div class="text-truncate" title="{{ $retur->reason }}">{{ $retur->reason }}</div>
                            </td>
                            <td class="font-monospace text-end fw-bold text-dark small">
                                Rp {{ number_format($retur->total_amount, 0, ',', '.') }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $retur->status_badge }} py-1 px-2 small text-uppercase">
                                    {{ $retur->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('purchase_returns.show', $retur) }}" class="btn btn-info btn-sm text-white" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($retur->status === 'draft')
                                        <form action="{{ route('purchase_returns.destroy', $retur) }}" method="POST"
                                            onsubmit="return confirm('Hapus retur ini? Stok akan dikembalikan.')">
                                            @csrf @method('DELETE')
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
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-undo-alt fa-2x mb-3 opacity-25 d-block"></i>
                                Belum ada retur pembelian yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $returns->links() }}</div>
    </div>
</div>
@endsection
