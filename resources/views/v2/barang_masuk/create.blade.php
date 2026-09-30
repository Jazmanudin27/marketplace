@extends('v2.layouts.app')

@section('title', 'Catat Penerimaan Barang Masuk V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-in-down text-success fs-5"></i> Catat Penerimaan Barang Masuk
        </h1>
    </div>
    <div>
        <a href="{{ route('v2.barang_masuk.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ── Error Notification ── --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Gagal menyimpan data:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('v2.barang_masuk.store') }}" method="POST" id="createGoodsReceiptForm">
    @csrf

    <div class="row g-3">
        {{-- Kiri: Form Identitas Dokumen --}}
        <div class="col-12 col-lg-3">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                    <i class="bi bi-file-earmark-text text-primary me-1.5"></i> Informasi Penerimaan
                </h6>

                <!-- Tanggal Penerimaan -->
                <div class="mb-1">
                    <label class="form-label small fw-semibold text-dark mb-1">Tanggal Penerimaan <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" class="form-control form-control-sm @error('receipt_date') is-invalid @enderror" value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Sumber Penerimaan -->
                <div class="mb-1">
                    <label class="form-label small fw-semibold text-dark mb-1">Sumber Penerimaan <span class="text-danger">*</span></label>
                    <select name="source" id="sourceSelect" class="form-select form-select-sm @error('source') is-invalid @enderror" required>
                        <option value="pembelian" {{ old('source') === 'pembelian' ? 'selected' : '' }}>Pembelian (Supplier)</option>
                        <option value="produksi" {{ old('source') === 'produksi' ? 'selected' : '' }}>Produksi Internal</option>
                        <option value="percetakan" {{ old('source') === 'percetakan' ? 'selected' : '' }}>Percetakan / Subkon</option>
                        <option value="lain_lain" {{ old('source') === 'lain_lain' ? 'selected' : '' }}>Lain-lain / Penyesuaian</option>
                    </select>
                </div>

                <!-- Supplier (Required for Pembelian) -->
                <div class="mb-1" id="supplierWrapper">
                    <label class="form-label small fw-semibold text-dark mb-1">Supplier <span class="text-danger" id="supplierRequiredTag">*</span></label>
                    <select name="supplier_id" id="supplierSelect" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }} {{ $sup->company_name ? "({$sup->company_name})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Catatan -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Catatan / No. Surat Jalan</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Masukkan nomor SJ supplier atau catatan penerimaan...">{{ old('notes') }}</textarea>
                </div>

                <!-- Total Summary Box -->
                <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding: 10px 12px;">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                        <span>Total Qty Item:</span>
                        <strong id="totalQtyDisplay" class="text-dark">0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">Total Nilai:</span>
                        <strong id="totalAmountDisplay" class="text-success fs-6 fw-bold font-monospace">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Form Item Penerimaan --}}
        <div class="col-12 col-lg-9">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-boxes text-success me-1.5"></i> Rincian Barang yang Diterima
                    </h6>
                    <button type="button" class="btn btn-sm text-white fw-semibold" id="btnAddRow" style="background:#16a34a; border:none;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                    </button>
                </div>

                <div class="table-responsive flex-grow-1 mb-2">
                    <table class="table table-bordered align-middle" id="itemsTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th style="width: 45%;">PILIH BARANG <span class="text-danger">*</span></th>
                                <th style="width: 12%;" class="text-center">QTY <span class="text-danger">*</span></th>
                                <th style="width: 20%;" class="text-end">HARGA SATUAN (RP)</th>
                                <th style="width: 19%;" class="text-end">SUBTOTAL (RP)</th>
                                <th style="width: 4%;" class="text-center"><i class="bi bi-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <!-- Dynamic rows appended via Javascript -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="{{ route('v2.barang_masuk.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4" style="background:#16a34a; border:none;">
                        <i class="bi bi-save me-1"></i> Simpan Penerimaan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inventoryItems = @json($inventoryItems);
    const tbody = document.getElementById('itemsTableBody');
    const btnAddRow = document.getElementById('btnAddRow');

    function createRow(itemId = '', qty = 1, price = 0) {
        const tr = document.createElement('tr');
        
        let optionsHtml = '<option value="">-- Pilih Barang Master --</option>';
        inventoryItems.forEach(item => {
            const selected = (item.id == itemId) ? 'selected' : '';
            optionsHtml += `<option value="${item.id}" data-price="${item.cost_price || 0}" data-unit="${item.unit || ''}" ${selected}>[${item.sku || 'BRG'}] ${item.name} (${item.unit})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select name="items[${tbody.children.length}][item_id]" class="form-select form-select-sm item-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" name="items[${tbody.children.length}][quantity]" class="form-control form-control-sm text-center qty-input" min="0.01" step="any" value="${qty}" required>
            </td>
            <td>
                <input type="number" name="items[${tbody.children.length}][unit_price]" class="form-control form-control-sm text-end price-input" min="0" step="any" value="${price}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end fw-bold font-monospace subtotal-display" readonly value="Rp 0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1.5 remove-row-btn" title="Hapus Baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        if (window.initV2Select2) {
            window.initV2Select2(tr);
        }

        // Bind events for this row
        const select = tr.querySelector('.item-select');
        const qtyInput = tr.querySelector('.qty-input');
        const priceInput = tr.querySelector('.price-input');
        const removeBtn = tr.querySelector('.remove-row-btn');

        const handleSelectChange = function() {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.price) {
                if (parseFloat(priceInput.value) === 0 || !priceInput.value) {
                    priceInput.value = parseFloat(selectedOpt.dataset.price);
                }
            }
            calculateRowSubtotal(tr);
        };

        select.addEventListener('change', handleSelectChange);
        $(select).on('select2:select change', handleSelectChange);

        qtyInput.addEventListener('input', () => calculateRowSubtotal(tr));
        priceInput.addEventListener('input', () => calculateRowSubtotal(tr));

        removeBtn.addEventListener('click', function() {
            if (tbody.children.length > 1) {
                tr.remove();
                reindexRows();
                calculateGrandTotal();
            } else {
                alert('Penerimaan barang minimal harus memiliki 1 item.');
            }
        });

        calculateRowSubtotal(tr);
    }

    function calculateRowSubtotal(tr) {
        const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const price = parseFloat(tr.querySelector('.price-input').value) || 0;
        const subtotal = qty * price;
        tr.querySelector('.subtotal-display').value = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let totalQty = 0;
        let totalAmount = 0;

        document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const price = parseFloat(tr.querySelector('.price-input').value) || 0;
            totalQty += qty;
            totalAmount += (qty * price);
        });

        document.getElementById('totalQtyDisplay').textContent = totalQty.toLocaleString('id-ID');
        document.getElementById('totalAmountDisplay').textContent = 'Rp ' + Math.round(totalAmount).toLocaleString('id-ID');
    }

    function reindexRows() {
        Array.from(tbody.children).forEach((tr, index) => {
            tr.querySelector('.item-select').name = `items[${index}][item_id]`;
            tr.querySelector('.qty-input').name = `items[${index}][quantity]`;
            tr.querySelector('.price-input').name = `items[${index}][unit_price]`;
        });
    }

    btnAddRow.addEventListener('click', () => createRow());

    // Add initial row
    createRow();
});
</script>
@endpush
