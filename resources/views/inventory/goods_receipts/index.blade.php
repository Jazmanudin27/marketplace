@extends('layouts.app')
@section('title', 'Pemasukan Barang - Pembelian')
@section('page-title', 'Pemasukan Barang (Goods Receipt)')

@section('content')
<div class="container-fluid px-0">

    {{-- Top Action & Header Banner --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden bg-white">
        <div class="card-body p-4 border-start border-4 border-success">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-truck text-success me-2"></i> Pemasukan Barang (Goods Receipt)
                    </h5>
                    <p class="text-muted small mb-0">
                        Pencatatan dan pemantauan barang masuk dari Supplier atau Toko (Bahan Baku, Kemasan, ATK, Inventaris) secara PO maupun Non-PO.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('goods_receipts.create') }}" class="btn btn-success btn-sm px-3 rounded-3 fw-semibold">
                        <i class="fas fa-plus-circle me-1.5"></i> + Catat Pemasukan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-list-alt text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-dark">{{ number_format($totalTransactions) }}</div>
                        <div class="text-muted small">Total Dokumen Pemasukan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-success border-4">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-check-circle text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-success">{{ number_format($totalApproved) }} <small class="fs-6 fw-normal text-muted">Approved</small></div>
                        <div class="text-muted small">Disetujui & Masuk Stok ({{ number_format($totalPending) }} Pending)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-info border-4">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-coins text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-dark font-monospace">Rp {{ number_format($totalValue, 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Nilai Pemasukan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('goods_receipts.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-search me-1 text-muted"></i> No. Pemasukan / Catatan
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik keyword pencarian..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-store me-1 text-muted"></i> Supplier / Toko
                        </label>
                        <select name="supplier_id" class="form-select form-select-sm">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-filter me-1 text-muted"></i> Status
                        </label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>🟢 Approved</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>🟡 Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-calendar me-1 text-muted"></i> Dari Tanggal
                        </label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-calendar me-1 text-muted"></i> Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 text-end mt-2">
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="fas fa-filter me-1"></i> Terapkan Filter
                        </button>
                        @if (request()->anyFilled(['search', 'supplier_id', 'status', 'date_from', 'date_to']))
                            <a href="{{ route('goods_receipts.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                <i class="fas fa-times me-1"></i> Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr class="small text-uppercase text-muted fw-bold">
                        <th class="ps-3" style="width: 130px;">TANGGAL</th>
                        <th style="width: 170px;">NO. PENERIMAAN</th>
                        <th>SUPPLIER / TOKO</th>
                        <th style="width: 130px;">DEPARTEMEN</th>
                        <th style="width: 140px;">PO REFERENSI</th>
                        <th style="width: 120px;" class="text-center">STATUS</th>
                        <th class="text-end" style="width: 140px;">TOTAL NILAI</th>
                        <th class="text-center pe-3" style="width: 120px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td class="ps-3 text-nowrap">
                                <div class="fw-semibold text-dark small">{{ $receipt->receipt_date->format('d/m/Y') }}</div>
                                <div class="text-muted" style="font-size: 0.73rem;">{{ $receipt->source_label }}</div>
                            </td>
                            <td>
                                <code class="font-monospace fw-bold text-success small">{{ $receipt->receipt_number }}</code>
                            </td>
                            <td class="small">
                                @if($receipt->supplier)
                                    <div class="fw-bold text-dark">{{ $receipt->supplier->name }}</div>
                                @else
                                    <span class="text-muted">— (Toko Umum)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill fw-bold">
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
                            <td class="text-center">
                                <span class="badge bg-{{ $receipt->status_badge }} bg-opacity-10 text-{{ $receipt->status_badge }} border border-{{ $receipt->status_badge }} border-opacity-25 px-2.5 py-1 rounded-pill fw-bold text-uppercase">
                                    {{ $receipt->status_label }}
                                </span>
                            </td>
                            <td class="font-monospace text-end fw-bold text-dark small">
                                Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-success py-1 px-2.5 fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#showReceiptModal-{{ $receipt->id }}" title="Detail">
                                        <i class="fas fa-eye me-1"></i> Detail
                                    </button>
                                    @if($receipt->status === 'pending')
                                        <a href="{{ route('goods_receipts.edit', $receipt) }}" class="btn btn-xs btn-outline-warning text-dark py-1 px-2.5 fw-semibold rounded-2" title="Edit">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                    @endif
                                    <form action="{{ route('goods_receipts.destroy', $receipt) }}" method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus penerimaan barang ini?')" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2.5 rounded-2" title="Hapus">
                                            <i class="fas fa-trash-alt me-1"></i> Hapus
                                        </button>
                                    </form>

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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-truck d-block mb-2 opacity-25 fs-1"></i>
                                Belum ada penerimaan barang yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Menampilkan {{ $receipts->firstItem() }} - {{ $receipts->lastItem() }} dari {{ $receipts->total() }} penerimaan
                    </small>
                    {{ $receipts->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
