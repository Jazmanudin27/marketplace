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
                                    <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#showReceiptModal-{{ $receipt->id }}" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    {{-- Modal Detail Penerimaan Barang --}}
                                    <div class="modal fade text-start" id="showReceiptModal-{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content border-0 shadow-lg rounded-3">
                                                <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#10b981,#059669)">
                                                    <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2">
                                                        <i class="fas fa-truck"></i> Detail Penerimaan Barang #{{ $receipt->receipt_number }}
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3 mb-4">
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <small class="text-muted d-block fw-semibold mb-1">No. Penerimaan</small>
                                                                <span class="font-monospace fw-bold text-success fs-6">{{ $receipt->receipt_number }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <small class="text-muted d-block fw-semibold mb-1">Tanggal & Status</small>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="fw-bold text-dark">{{ $receipt->receipt_date ? $receipt->receipt_date->format('d F Y') : '—' }}</span>
                                                                    <span class="badge bg-{{ $receipt->status_badge }} text-uppercase">{{ $receipt->status_label }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <small class="text-muted d-block fw-semibold mb-1">Supplier / Toko</small>
                                                                <span class="fw-bold text-dark">
                                                                    {{ $receipt->supplier ? $receipt->supplier->name : '— (Toko Umum)' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <small class="text-muted d-block fw-semibold mb-1">Departemen Tujuan</small>
                                                                <span class="fw-bold text-dark">
                                                                    {{ $receipt->department ? $receipt->department->name : 'Umum' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @if($receipt->purchaseOrder)
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3">
                                                                <small class="text-primary fw-bold d-block mb-1">PO Referensi</small>
                                                                <span class="fw-bold text-primary">{{ $receipt->purchaseOrder->po_number }}</span>
                                                            </div>
                                                        </div>
                                                        @endif
                                                        <div class="col-md-{{ $receipt->purchaseOrder ? '6' : '12' }}">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <small class="text-muted d-block fw-semibold mb-1">Operator / Petugas</small>
                                                                <span class="fw-bold text-dark">{{ $receipt->createdBy->name ?? 'System' }}</span>
                                                                @if($receipt->approvedBy)
                                                                    <small class="text-muted d-block mt-1">Disetujui: <strong>{{ $receipt->approvedBy->name }}</strong> ({{ $receipt->approved_at ? $receipt->approved_at->format('d/m/Y H:i') : '' }})</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if($receipt->notes)
                                                        <div class="col-12">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <small class="text-muted d-block fw-semibold mb-1">Catatan</small>
                                                                <span class="small text-muted">{{ $receipt->notes }}</span>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div>

                                                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes me-2 text-success"></i>Barang yang Diterima ({{ number_format($receipt->items->count()) }} Item)</h6>
                                                    <div class="table-responsive border rounded-2 mb-3">
                                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
                                                            <thead class="table-light">
                                                                <tr class="text-muted text-uppercase">
                                                                    <th class="py-2 px-3">Barang / SKU</th>
                                                                    <th class="text-center">Tipe</th>
                                                                    <th class="text-center">Qty Diterima</th>
                                                                    <th class="text-end">Harga Satuan</th>
                                                                    <th class="text-end px-3">Subtotal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($receipt->items as $item)
                                                                    <tr>
                                                                        <td class="px-3 py-2">
                                                                            <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                                                                            <div class="font-monospace text-muted" style="font-size:11px;">{{ $item->item_sku }}</div>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-secondary rounded-pill" style="font-size:10px;">
                                                                                {{ ucfirst($item->inventoryItem->type ?? 'raw') }}
                                                                            </span>
                                                                        </td>
                                                                        <td class="text-center fw-bold text-success">
                                                                            +{{ number_format($item->quantity) }} {{ $item->inventoryItem->unit ?? 'pcs' }}
                                                                        </td>
                                                                        <td class="font-monospace text-end text-muted">
                                                                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                                                        </td>
                                                                        <td class="font-monospace text-end fw-bold text-dark px-3">
                                                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="table-light">
                                                                <tr>
                                                                    <td colspan="4" class="text-end fw-bold px-3 py-2">TOTAL PENERIMAAN</td>
                                                                    <td class="font-monospace text-end fw-bold text-success px-3 py-2">
                                                                        Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                    @if($receipt->status === 'pending')
                                                        <div class="alert alert-warning py-2 px-3 small d-flex justify-content-between align-items-center mb-0">
                                                            <div>
                                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                                Barang belum masuk ke stok persediaan.
                                                            </div>
                                                            <form action="{{ route('goods_receipts.approve', $receipt) }}" method="POST" class="m-0">
                                                                @csrf
                                                                <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                                                                    <i class="fas fa-check-circle me-1"></i> Setujui & Masukkan Stok
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer bg-light py-2 px-3">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($receipt->status === 'pending')
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
