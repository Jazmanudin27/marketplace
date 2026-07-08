{{-- Partial: tabel item retur dari sebuah PO --}}
<div class="border rounded-2 overflow-hidden">
    <div class="py-2 px-3" style="background:#fef2f2">
        <span class="fw-semibold small text-danger">
            <i class="fas fa-boxes me-1"></i>Item dari PO: {{ $po->po_number }} — {{ $po->supplier->name }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr class="small text-uppercase text-muted">
                    <th class="py-2 px-3">Barang</th>
                    <th class="text-center">Dipesan</th>
                    <th class="text-center">Diterima</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center" style="min-width:110px">Qty Retur</th>
                    <th class="text-center" style="min-width:80px">Sertakan?</th>
                </tr>
            </thead>
            <tbody>
                @foreach($po->items as $index => $item)
                    @php
                        $hasReceived = $item->received_quantity > 0;
                    @endphp
                    <tr>
                        <td class="px-3 py-3">
                            <div class="fw-semibold text-dark small">{{ $item->item_name }}</div>
                            <div class="font-monospace text-muted" style="font-size:11px">{{ $item->item_sku }}</div>
                            @if($item->inventoryItem)
                                <span class="badge" style="font-size:10px;background:#e0f2fe;color:#0369a1">
                                    {{ $item->inventoryItem->type }} · {{ $item->inventoryItem->unit }}
                                </span>
                            @endif
                            {{-- Hidden fields --}}
                            <input type="hidden" name="items[{{ $index }}][item_id]"
                                value="{{ $item->inventory_item_id ?: $item->master_product_id }}">
                            <input type="hidden" name="items[{{ $index }}][item_type]"
                                value="{{ $item->inventory_item_id ? 'inventory' : 'product' }}">
                        </td>
                        <td class="text-center text-dark small fw-bold">{{ number_format($item->quantity) }}</td>
                        <td class="text-center small">
                            @if($item->received_quantity > 0)
                                <span class="badge bg-success">{{ number_format($item->received_quantity) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="font-monospace text-end small text-muted">
                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}">
                        </td>
                        <td class="text-center py-2 px-2">
                            <input type="number" name="items[{{ $index }}][quantity]"
                                class="form-control form-control-sm text-center qty-retur"
                                style="width:90px;margin:auto"
                                data-index="{{ $index }}"
                                min="0" max="{{ $item->received_quantity }}"
                                value="0">
                            <div class="text-muted" style="font-size:10px">Maks: {{ $item->received_quantity }}</div>
                        </td>
                        <td class="text-center">
                            <div class="form-check d-flex justify-content-center">
                                <input class="form-check-input item-check" type="checkbox"
                                    data-index="{{ $index }}" checked>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
// Sync checkbox → disable/enable qty input
document.querySelectorAll('.item-check').forEach(function(chk) {
    chk.addEventListener('change', function() {
        const idx = this.dataset.index;
        const input = document.querySelector(`input[name="items[${idx}][quantity]"]`);
        if (!this.checked) {
            input.value = 0;
            input.disabled = true;
        } else {
            input.disabled = false;
        }
    });
});
</script>
