@extends('layouts.app')
@section('title', 'Penerimaan Barang')
@section('page-title', 'Penerimaan Barang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-truck text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Penerimaan Barang</h5>
                    <div class="text-muted small">Daftar penerimaan barang dari supplier (PO &amp; Non-PO)</div>
                </div>
            </div>
            <a href="{{ route('goods_receipts.create') }}" class="btn fw-semibold btn-sm px-3 text-white"
                style="background:linear-gradient(135deg,#10b981,#059669)">
                <i class="fas fa-plus me-1"></i> Catat Pembelian Langsung (Non-PO)
            </a>
        </div>

        {{-- Info Banner --}}
        <div class="alert py-2 px-3 small mb-4 d-flex align-items-center gap-2"
            style="background:#f0fdf4;border:1px solid #6ee7b7;color:#065f46;border-radius:10px">
            <i class="fas fa-info-circle"></i>
            <span>Setiap penerimaan barang (dari PO maupun Langsung) akan masuk sebagai draft <strong>Pending</strong>. Lakukan <strong>Approval</strong> pada detail penerimaan untuk memasukkan barang ke stok <strong>Gudang Bahan / GA</strong>.</span>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Cari No. Penerimaan</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="GR-2026...">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Sumber</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">Semua Sumber</option>
                    <option value="po"         {{ request('source') === 'po' ? 'selected' : '' }}>Penerimaan PO</option>
                    <option value="direct"     {{ request('source') === 'direct' ? 'selected' : '' }}>Pembelian Langsung</option>
                    <option value="walk_in"    {{ request('source') === 'walk_in' ? 'selected' : '' }}>Walk-in / Beli di Toko</option>
                    <option value="emergency"  {{ request('source') === 'emergency' ? 'selected' : '' }}>Pembelian Darurat</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending"    {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Approval</option>
                    <option value="approved"   {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search','source','status','date_from','date_to']))
                    <a href="{{ route('goods_receipts.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#ecfdf5">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">No. Penerimaan</th>
                        <th>Supplier</th>
                        <th>Departemen</th>
                        <th>PO Referensi</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center">Item</th>
                        <th class="text-end">Total</th>
                        <th class="text-center" style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td class="font-monospace fw-bold text-dark px-3 py-3" style="font-size:13px">
                                {{ $receipt->receipt_number }}
                            </td>
                            <td class="small">
                                @if($receipt->supplier)
                                    <div class="fw-semibold text-dark">{{ $receipt->supplier->name }}</div>
                                @else
                                    <span class="text-muted">— (Toko Umum)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background:#f0fdf4;color:#166534;font-size:11px">
                                    {{ $receipt->department ? $receipt->department->name : 'Umum' }}
                                </span>
                            </td>
                            <td>
                                @if($receipt->purchaseOrder)
                                    <a href="{{ route('purchase_orders.show', $receipt->purchase_order_id) }}" class="small fw-semibold text-primary">
                                        {{ $receipt->purchaseOrder->po_number }}
                                    </a>
                                @else
                                    <span class="text-muted small">— (Non-PO)</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $receipt->receipt_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $receipt->status_badge }} py-1 px-2 small text-uppercase">
                                    {{ $receipt->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill small">
                                    {{ $receipt->items->count() }} item
                                </span>
                            </td>
                            <td class="font-monospace text-end fw-bold text-dark small">
                                Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('goods_receipts.show', $receipt) }}"
                                        class="btn btn-info btn-sm text-white" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($receipt->status === 'pending')
                                        <form action="{{ route('goods_receipts.approve', $receipt) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menyetujui penerimaan barang ini dan memasukkannya ke stok?')"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" title="Setujui (Approve)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('goods_receipts.edit', $receipt) }}"
                                            class="btn btn-warning btn-sm text-white" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    <form action="{{ route('goods_receipts.destroy', $receipt) }}" method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus penerimaan barang ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-truck fa-2x mb-3 opacity-25 d-block"></i>
                                Belum ada penerimaan barang yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $receipts->links() }}</div>
    </div>
</div>
@endsection
