@extends('layouts.app')
@section('title', 'Terima Barang — ' . $purchaseOrder->po_number)
@section('page-title', 'Penerimaan Barang')

@section('content')
<div class="row g-3">
    {{-- Kiri: Info PO --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
                        <i class="fas fa-truck-loading text-white small"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Penerimaan Barang</div>
                        <div class="text-muted" style="font-size:11px">Konfirmasi barang dari supplier</div>
                    </div>
                </div>

                <div class="border rounded-2 p-3 mb-3" style="background:#f8fafc">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">No. PO</span>
                        <span class="font-monospace fw-bold small text-dark">{{ $purchaseOrder->po_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">Supplier</span>
                        <span class="fw-semibold small text-dark text-end">{{ $purchaseOrder->supplier->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">Departemen</span>
                        <span class="fw-semibold small text-dark">{{ $purchaseOrder->department ? $purchaseOrder->department->name : '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted small">Status</span>
                        <span class="badge bg-{{ $purchaseOrder->status_badge }} py-1 px-2 text-uppercase small">{{ $purchaseOrder->status_label }}</span>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0" style="border-radius:10px">
                    <i class="fas fa-info-circle me-1"></i>
                    Isi qty barang yang <strong>diterima hari ini</strong>. Bisa sebagian (partial). Stok akan langsung bertambah.
                </div>
            </div>
        </div>

        <a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail PO
        </a>
    </div>

    {{-- Kanan: Form Receive --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Daftar Item — Isi Qty yang Diterima
                </h6>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        @foreach($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('purchase_orders.receive.store', $purchaseOrder) }}" method="POST" id="formReceive">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Tanggal Terima <span class="text-danger">*</span></label>
                            <input type="date" name="receive_date" class="form-control form-control-sm @error('receive_date') is-invalid @enderror"
                                value="{{ old('receive_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Catatan Penerimaan</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Mis: barang lengkap, sesuai PO"
                                value="{{ old('notes') }}">
                        </div>
                    </div>

                    <div class="table-responsive rounded-2 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:#f1f5f9">
                                <tr class="small text-uppercase text-muted">
                                    <th class="py-2 px-3">Barang</th>
                                    <th class="text-center py-2">Qty Pesan</th>
                                    <th class="text-center py-2">Sdh Diterima</th>
                                    <th class="text-center py-2">Sisa</th>
                                    <th class="text-center py-2" style="min-width:130px">Terima Sekarang <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $index => $item)
                                    @php
                                        $sisa = $item->quantity - $item->received_quantity;
                                        $isDone = $sisa <= 0;
                                    @endphp
                                    <tr class="{{ $isDone ? 'table-success' : '' }}">
                                        <td class="px-3 py-3">
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                            <div class="fw-semibold text-dark small">{{ $item->item_name }}</div>
                                            <div class="font-monospace text-muted" style="font-size:11px">{{ $item->item_sku }}</div>
                                            @if($item->inventoryItem)
                                                <span class="badge text-uppercase" style="font-size:10px;background:#e0f2fe;color:#0369a1">
                                                    {{ $item->inventoryItem->type }} · {{ $item->inventoryItem->unit }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold text-dark small">{{ number_format($item->quantity) }}</td>
                                        <td class="text-center small">
                                            @if($item->received_quantity > 0)
                                                <span class="badge bg-success">{{ number_format($item->received_quantity) }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center small">
                                            @if($isDone)
                                                <span class="badge bg-success">Lengkap</span>
                                            @else
                                                <span class="fw-bold text-warning">{{ number_format($sisa) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center py-2 px-2">
                                            @if($isDone)
                                                <span class="text-success small"><i class="fas fa-check-circle"></i> Selesai</span>
                                                <input type="hidden" name="items[{{ $index }}][received_qty]" value="0">
                                            @else
                                                <input type="number" name="items[{{ $index }}][received_qty]"
                                                    class="form-control form-control-sm text-center"
                                                    style="width:100px;margin:auto"
                                                    min="0" max="{{ $sisa }}"
                                                    value="0"
                                                    placeholder="0">
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="fillAll()">
                            <i class="fas fa-fill me-1"></i> Isi Semua Sisa Qty
                        </button>
                        <button type="submit" class="btn btn-primary fw-semibold px-4" id="btnSubmit">
                            <i class="fas fa-check-circle me-2"></i>Konfirmasi Penerimaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function fillAll() {
    document.querySelectorAll('input[name$="[received_qty]"]').forEach(function(input) {
        if (!input.readOnly && input.type === 'number') {
            input.value = input.max || 0;
        }
    });
}

document.getElementById('formReceive').addEventListener('submit', function() {
    document.getElementById('btnSubmit').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    document.getElementById('btnSubmit').disabled = true;
});
</script>
@endpush
