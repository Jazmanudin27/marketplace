@extends('layouts.app')
@section('title', 'Edit Purchase Order')
@section('page-title', 'Edit Purchase Order')

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('purchase_orders.update', $purchaseOrder) }}" method="POST" id="po-form">
            @csrf
            @method('PUT')
            
            <div class="card border rounded shadow-sm bg-white mb-4">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-file-invoice text-primary me-2"></i>Informasi Purchase Order</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select select2" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Departemen Pemesan <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select select2" required>
                                <option value="">-- Pilih Departemen --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $purchaseOrder->department_id == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Tanggal PO <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" class="form-control" value="{{ $purchaseOrder->po_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Catatan Tambahan</label>
                            <input type="text" name="notes" class="form-control" value="{{ $purchaseOrder->notes }}" placeholder="Instruksi pengiriman, dll.">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border rounded shadow-sm bg-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fas fa-cubes text-primary me-2"></i>Daftar Item Pembelian</h5>
                    </div>

                    {{-- Search product to add --}}
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Cari & Pilih Item</label>
                            <select id="product-picker" class="form-select select2">
                                <option value="">-- Cari Nama atau SKU --</option>
                                <optgroup label="Bahan Baku & Kemasan">
                                    @foreach($materials as $material)
                                        <option value="{{ $material->id }}" data-type="material" data-sku="{{ $material->sku }}" data-name="{{ $material->name }}" data-price="{{ $material->cost_price ?: 0 }}">
                                            [{{ $material->sku ?: '-' }}] {{ $material->name }} (Bahan/Kemasan - Harga: Rp {{ number_format($material->cost_price, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Inventory & ATK">
                                    @foreach($inventoryItems as $inv)
                                        <option value="{{ $inv->id }}" data-type="inventory" data-sku="{{ $inv->sku }}" data-name="{{ $inv->name }}" data-price="{{ $inv->cost_price ?: 0 }}">
                                            [{{ $inv->sku ?: '-' }}] {{ $inv->name }} (Inventory - Harga: Rp {{ number_format($inv->cost_price, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" id="btn-add-product" class="btn btn-outline-primary w-100 fw-semibold">
                                <i class="fas fa-plus me-1"></i> Tambah ke List
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive rounded border">
                        <table class="table table-striped table-hover align-middle mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>NAMA BARANG</th>
                                    <th style="width: 150px;">JUMLAH (QTY)</th>
                                    <th style="width: 200px;">HARGA BELI SATUAN (Rp)</th>
                                    <th class="text-end" style="width: 200px;">SUBTOTAL</th>
                                    <th class="text-center" style="width: 80px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                @php $itemIndex = 0; @endphp
                                @foreach($purchaseOrder->items as $item)
                                    @php
                                        $sku = $item->item_sku;
                                        $name = $item->item_name;
                                        $type = 'product';
                                        $itemId = $item->master_product_id;
                                        if ($item->material_id) {
                                            $type = 'material';
                                            $itemId = $item->material_id;
                                        } elseif ($item->inventory_item_id) {
                                            $type = 'inventory';
                                            $itemId = $item->inventory_item_id;
                                        }
                                    @endphp
                                    <tr id="row-item-{{ $type }}-{{ $itemId }}" class="item-row">
                                        <td class="font-monospace">{{ $sku }}</td>
                                        <td class="text-dark fw-semibold">{{ $name }} <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 text-uppercase">{{ $type }}</span>
                                            <input type="hidden" name="items[{{ $itemIndex }}][item_id]" value="{{ $itemId }}" class="item-id-input" data-type="{{ $type }}">
                                            <input type="hidden" name="items[{{ $itemIndex }}][item_type]" value="{{ $type }}">
                                            <input type="hidden" name="items[{{ $itemIndex }}][received_quantity]" value="{{ $item->received_quantity }}">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $itemIndex }}][quantity]" class="form-control form-control-sm qty-input" value="{{ $item->quantity }}" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $itemIndex }}][unit_price]" class="form-control form-control-sm price-input" value="{{ $item->unit_price }}" min="0" step="0.01" required>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark subtotal-display">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-id="{{ $itemId }}" data-type="{{ $type }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @php $itemIndex++; @endphp
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end text-dark">GRAND TOTAL</td>
                                    <td class="text-end text-success font-monospace" id="grand-total-display">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary px-4">Kembali</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">Perbarui Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let itemIndex = {{ $itemIndex }};
    
    // Add product button handler
    document.getElementById('btn-add-product').addEventListener('click', function () {
        const picker = document.getElementById('product-picker');
        const selected = picker.options[picker.selectedIndex];
        
        if (!picker.value) {
            alert('Silakan pilih item terlebih dahulu.');
            return;
        }

        const id = picker.value;
        const type = selected.getAttribute('data-type');
        const sku = selected.getAttribute('data-sku') || '-';
        const name = selected.getAttribute('data-name');
        const price = parseFloat(selected.getAttribute('data-price') || 0);

        // Check if item is already in the list
        if (document.querySelector(`.item-row input[value="${id}"][class="item-id-input"][data-type="${type}"]`)) {
            alert('Item ini sudah ada di dalam daftar list.');
            return;
        }

        // Remove empty row helper if exists
        const emptyRow = document.getElementById('empty-row');
        if (emptyRow) emptyRow.remove();

        const tbody = document.getElementById('items-body');
        const tr = document.createElement('tr');
        tr.id = `row-item-${type}-${id}`;
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="font-monospace">${sku}</td>
            <td class="text-dark fw-semibold">${name} <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1 text-uppercase">${type}</span>
                <input type="hidden" name="items[${itemIndex}][item_id]" value="${id}" class="item-id-input" data-type="${type}">
                <input type="hidden" name="items[${itemIndex}][item_type]" value="${type}">
                <input type="hidden" name="items[${itemIndex}][received_quantity]" value="0">
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm price-input" value="${price}" min="0" step="0.01" required>
            </td>
            <td class="text-end font-monospace fw-bold text-dark subtotal-display">Rp ${price.toLocaleString('id-ID')}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-id="${id}" data-type="${type}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        itemIndex++;

        // Reset selector
        $('#product-picker').val('').trigger('change');

        // Bind events
        tr.querySelector('.qty-input').addEventListener('input', calculateRowTotal);
        tr.querySelector('.price-input').addEventListener('input', calculateRowTotal);
        tr.querySelector('.btn-remove-item').addEventListener('click', function () {
            tr.remove();
            checkEmptyTable();
            calculateGrandTotal();
        });

        calculateGrandTotal();
    });

    // Bind original rows
    document.querySelectorAll('.item-row').forEach(row => {
        row.querySelector('.qty-input').addEventListener('input', calculateRowTotal);
        row.querySelector('.price-input').addEventListener('input', calculateRowTotal);
        row.querySelector('.btn-remove-item').addEventListener('click', function () {
            row.remove();
            checkEmptyTable();
            calculateGrandTotal();
        });
    });

    function calculateRowTotal() {
        const tr = this.closest('tr');
        const qty = parseInt(tr.querySelector('.qty-input').value || 0);
        const price = parseFloat(tr.querySelector('.price-input').value || 0);
        const subtotal = qty * price;
        tr.querySelector('.subtotal-display').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseInt(row.querySelector('.qty-input').value || 0);
            const price = parseFloat(row.querySelector('.price-input').value || 0);
            total += qty * price;
        });
        document.getElementById('grand-total-display').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function checkEmptyTable() {
        const tbody = document.getElementById('items-body');
        if (tbody.querySelectorAll('.item-row').length === 0) {
            tbody.innerHTML = `
                <tr id="empty-row">
                    <td colspan="6" class="text-center text-muted py-4">Belum ada item produk yang ditambahkan. Silakan pilih produk di atas.</td>
                </tr>
            `;
        }
    }
});
</script>
@endsection
