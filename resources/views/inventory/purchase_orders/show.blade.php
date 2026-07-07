@extends('layouts.app')
@section('title', 'Detail Purchase Order')
@section('page-title', 'Detail Purchase Order')

@section('content')
<div class="row g-3">
    {{-- Left: Informasi PO --}}
    <div class="col-md-4">
        <div class="card border rounded shadow-sm bg-white p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-file-invoice text-primary me-2"></i>Informasi PO</h6>
                <span class="badge bg-{{ $purchaseOrder->status_badge }} text-uppercase py-2 px-3 small">{{ $purchaseOrder->status_label }}</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-secondary small">Nomor PO</span>
                <span class="font-monospace fw-bold small text-dark">{{ $purchaseOrder->po_number }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-secondary small">Tanggal PO</span>
                <span class="fw-semibold small text-dark">{{ $purchaseOrder->po_date->format('d M Y') }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-secondary small">Supplier</span>
                <span class="fw-semibold small text-dark text-end">{{ $purchaseOrder->supplier->name }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-secondary small">Departemen</span>
                <span class="fw-semibold small text-dark text-end">{{ $purchaseOrder->department ? $purchaseOrder->department->name : '-' }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-secondary small">Total Nominal</span>
                <span class="font-monospace text-success fw-bold small">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="py-2">
                <span class="text-secondary small d-block mb-1">Catatan</span>
                <p class="text-dark small mb-0">{{ $purchaseOrder->notes ?? '-' }}</p>
            </div>
        </div>

        {{-- Aksi Perubahan Status --}}
        @if($purchaseOrder->status !== 'received')
            <div class="card border rounded shadow-sm bg-white p-3">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-cog text-secondary me-2"></i>Perbarui Status</h6>
                
                @if($purchaseOrder->status === 'draft')
                    <form action="{{ route('purchase_orders.update_status', $purchaseOrder) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="status" value="ordered">
                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-semibold">
                            <i class="fas fa-paper-plane me-1"></i> Rilis PO ke Supplier (Ordered)
                        </button>
                    </form>
                @endif

                @if(in_array($purchaseOrder->status, ['draft', 'ordered', 'partially_received']))
                    <form action="{{ route('purchase_orders.update_status', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Batalkan Purchase Order ini?')">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-2 fw-semibold">
                            <i class="fas fa-times-circle me-1"></i> Batalkan PO (Cancel)
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    {{-- Right: Daftar Item --}}
    <div class="col-md-8">
        <div class="card border rounded shadow-sm bg-white overflow-hidden">
            <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-cubes text-primary me-2"></i>Daftar Item Barang PO</h6>
                <div>
                    <a href="{{ route('purchase_orders.print', $purchaseOrder) }}" target="_blank" class="btn btn-outline-primary btn-sm py-1">
                        <i class="fas fa-print me-1"></i> Cetak PO
                    </a>
                </div>
            </div>

            <div class="card-body p-3">
                <div class="table-responsive rounded border">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th>SKU</th>
                                <th>NAMA BARANG</th>
                                <th class="text-center">QTY PESAN</th>
                                <th class="text-center">QTY DITERIMA</th>
                                <th class="text-end">HARGA SATUAN</th>
                                <th class="text-end">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $item)
                                @php
                                    $sku = $item->item_sku;
                                    $name = $item->item_name;
                                    $type = 'product';
                                    if ($item->material_id) {
                                        $type = 'material';
                                    } elseif ($item->inventory_item_id) {
                                        $type = 'inventory';
                                    }
                                @endphp
                                <tr>
                                    <td class="font-monospace text-dark">{{ $sku }}</td>
                                    <td class="fw-semibold text-dark">{{ $name }} <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 text-uppercase small">{{ $type }}</span></td>
                                    <td class="text-center text-dark">{{ $item->quantity }}</td>
                                    <td class="text-center">
                                        @if($item->received_quantity >= $item->quantity)
                                            <span class="badge bg-success py-1 px-2">{{ $item->received_quantity }} (Lengkap)</span>
                                        @elseif($item->received_quantity > 0)
                                            <span class="badge bg-warning text-dark py-1 px-2">{{ $item->received_quantity }} (Kurang)</span>
                                        @else
                                            <span class="text-muted small">0 / {{ $item->quantity }}</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace text-end text-muted">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="font-monospace text-end fw-bold text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold font-monospace">
                            <tr>
                                <td colspan="5" class="text-end text-dark">GRAND TOTAL</td>
                                <td class="text-end text-success">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
