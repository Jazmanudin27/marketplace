@extends('layouts.app')
@section('title', 'Detail Retur — ' . $purchaseReturn->return_number)
@section('page-title', 'Detail Retur Pembelian')

@section('content')
<div class="row g-3">
    {{-- Kiri: Info Retur --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-undo-alt text-danger me-2"></i>Info Retur
                    </h6>
                    <span class="badge bg-{{ $purchaseReturn->status_badge }} py-2 px-3 text-uppercase small">
                        {{ $purchaseReturn->status_label }}
                    </span>
                </div>

                <div class="border rounded-2 overflow-hidden mb-3">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="background:#f8fafc">
                        <span class="text-muted small">No. Retur</span>
                        <span class="font-monospace fw-bold text-dark small">{{ $purchaseReturn->return_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small text-dark fw-semibold">{{ $purchaseReturn->return_date->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="text-muted small">PO Referensi</span>
                        <a href="{{ route('purchase_orders.show', $purchaseReturn->purchase_order_id) }}" class="small text-primary fw-semibold">
                            {{ $purchaseReturn->purchaseOrder->po_number ?? '-' }}
                        </a>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="text-muted small">Supplier</span>
                        <span class="small text-dark fw-semibold">{{ $purchaseReturn->supplier->name ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="text-muted small">Total Nilai</span>
                        <span class="font-monospace text-danger fw-bold small">Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="px-3 py-2">
                        <span class="text-muted small d-block mb-1">Alasan</span>
                        <span class="small text-dark">{{ $purchaseReturn->reason }}</span>
                    </div>
                    @if($purchaseReturn->notes)
                        <div class="px-3 py-2 border-top">
                            <span class="text-muted small d-block mb-1">Catatan</span>
                            <span class="small text-muted">{{ $purchaseReturn->notes }}</span>
                        </div>
                    @endif
                </div>

                @if($purchaseReturn->createdBy)
                    <div class="text-muted" style="font-size:11px">
                        <i class="fas fa-user me-1"></i> Dibuat oleh: <strong>{{ $purchaseReturn->createdBy->name }}</strong>
                    </div>
                @endif
            </div>
        </div>

        {{-- Update Status --}}
        @if($purchaseReturn->status !== 'sent')
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-dark mb-3 small"><i class="fas fa-cog text-secondary me-2"></i>Perbarui Status</h6>
                    @if($purchaseReturn->status === 'draft')
                        <form action="{{ route('purchase_returns.update_status', $purchaseReturn) }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-semibold">
                                <i class="fas fa-check me-1"></i> Setujui Retur
                            </button>
                        </form>
                    @endif
                    @if(in_array($purchaseReturn->status, ['draft', 'approved']))
                        <form action="{{ route('purchase_returns.update_status', $purchaseReturn) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="sent">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">
                                <i class="fas fa-paper-plane me-1"></i> Tandai Sudah Dikirim ke Supplier
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <a href="{{ route('purchase_returns.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- Kanan: Daftar Item --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-list me-2"></i>Daftar Barang Diretur
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:#f8fafc">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-4">Barang / SKU</th>
                                <th class="text-center py-2">Qty</th>
                                <th class="text-end py-2">Harga Satuan</th>
                                <th class="text-end py-2 px-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseReturn->items as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold text-dark small">{{ $item->item_name }}</div>
                                        <div class="font-monospace text-muted" style="font-size:11px">{{ $item->item_sku }}</div>
                                        @if($item->inventoryItem)
                                            <span class="badge" style="font-size:10px;background:#fee2e2;color:#dc2626">
                                                {{ ucfirst($item->inventoryItem->type) }} · {{ $item->inventoryItem->unit }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-dark small">{{ number_format($item->quantity) }}</td>
                                    <td class="font-monospace text-end text-muted small">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="font-monospace text-end fw-bold text-danger small px-4">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#fef2f2">
                            <tr>
                                <td colspan="3" class="text-end fw-bold text-dark px-4 py-3">TOTAL RETUR</td>
                                <td class="font-monospace text-end fw-bold text-danger px-4 py-3">
                                    Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
