@extends('v2.layouts.app')

@section('title', 'Penerimaan Barang Konsinyasi Baru V2')

@section('content')

    {{-- Header --}}
    <div class="v2-page-header align-items-center mb-3">
        <div>
            <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-box-seam-fill text-warning fs-5"></i> Penerimaan Barang Titipan Konsinyasi
            </h1>
            <p class="text-muted small mb-0">Catat transaksi masuk barang titipan supplier ke persediaan gudang master</p>
        </div>
        <div>
            <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold"
                style="background:#64748b; border:none;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Notifications --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert"
            style="border-radius:10px;">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert"
            style="border-radius:10px;">
            <i class="bi bi-exclamation-triangle me-2"></i><strong>Gagal Menyimpan:</strong>
            <ul class="mb-0 mt-1 ps-3 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('supplier_consignments.store') }}" method="POST" id="consignment-form">
        @csrf
        <div class="row g-3">
            {{-- Kiri: Form Header & Informasi Transaksi --}}
            <div class="col-12 col-lg-3">
                <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle text-warning me-1.5"></i> Informasi Transaksi
                    </h6>

                    {{-- No Referensi --}}
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">No. Referensi Konsinyasi</label>
                        <input type="text" class="form-control form-control-sm bg-light font-monospace fw-bold"
                            value="{{ $refNumber }}" readonly>
                        <div class="form-text" style="font-size:0.7rem;">Nomor otomatis generate sistem</div>
                    </div>

                    {{-- Supplier --}}
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Supplier Penitip Barang <span
                                class="text-danger">*</span></label>
                        <select name="supplier_id"
                            class="form-select form-select-sm select2 @error('supplier_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} {{ $supplier->phone ? '(' . $supplier->phone . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal --}}
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Tanggal Penerimaan <span
                                class="text-danger">*</span></label>
                        <input type="date" name="consignment_date"
                            class="form-control form-control-sm @error('consignment_date') is-invalid @enderror"
                            value="{{ old('consignment_date', date('Y-m-d')) }}" required>
                    </div>

                    {{-- Catatan --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                            placeholder="Contoh: Titipan Celana SMA L 100 Pcs...">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Summary Box --}}
                    <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding:10px 12px;">
                        <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                            <span>Total Qty:</span>
                            <strong id="big-total-qty" class="text-dark">0 PCS</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-muted">Total Modal HPP:</span>
                            <strong id="big-total-hpp" class="text-warning-emphasis font-monospace fw-bold"
                                style="font-size:0.92rem;">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Potensi Profit:</span>
                            <strong id="big-total-profit" class="text-success font-monospace">+Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: Form Item Penerimaan (Gaya Barang Masuk V2) --}}
            <div class="col-12 col-lg-9">
                <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-boxes text-warning me-1.5"></i> Rincian Barang Titipan (Konsinyasi)
                        </h6>
                        <button type="button" class="btn btn-sm text-white fw-semibold" id="btnAddRow"
                            style="background:#f59e0b; border:none;">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="table-responsive flex-grow-1 mb-2" style="overflow-x: hidden; overflow-y: visible;">
                        <table class="table table-bordered align-middle m-0" id="itemsTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th style="width: 38%;">PILIH BARANG <span class="text-danger">*</span></th>
                                    <th style="width: 10%;" class="text-center">QTY <span class="text-danger">*</span></th>
                                    <th style="width: 16%;" class="text-end">HARGA TITIP (HPP) <span class="text-danger">*</span></th>
                                    <th style="width: 16%;" class="text-end">HARGA JUAL TOKO <span class="text-danger">*</span></th>
                                    <th style="width: 15%;" class="text-end">SUBTOTAL HPP</th>
                                    <th style="width: 5%;" class="text-center"><i class="bi bi-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Dynamic rows appended via Javascript -->
                            </tbody>
                        </table>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm text-white fw-semibold"
                            style="background:#64748b; border:none;">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4 shadow-sm"
                            style="background:#f59e0b; border:none;">
                            <i class="bi bi-save me-1"></i> Simpan Transaksi
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
            const products = @json($products ?? []);
            const tbody = document.getElementById('itemsTableBody');
            const btnAddRow = document.getElementById('btnAddRow');

            function createRow(productId = '', qty = 100, costPrice = 0, sellingPrice = 0) {
                const tr = document.createElement('tr');
                const rowIndex = tbody.children.length;

                let optionsHtml = '<option value="">-- Pilih Barang Master --</option>';
                products.forEach(p => {
                    const selected = (p.id == productId) ? 'selected' : '';
                    optionsHtml += `<option value="${p.id}" data-cost="${p.cost_price || 0}" data-price="${p.price || 0}" data-unit="${p.unit || 'PCS'}" ${selected}>[${p.sku || 'BRG'}] ${p.name} (Stok: ${p.stock || 0})</option>`;
                });

                tr.innerHTML = `
                    <td>
                        <select name="items[${rowIndex}][master_product_id]" class="form-select form-select-sm product-select" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][qty_received]" class="form-control form-control-sm text-center qty-input fw-bold" min="1" step="1" value="${qty}" required>
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][unit_cost_price]" class="form-control form-control-sm text-end cost-input font-monospace fw-bold" min="0" step="any" value="${costPrice}" required>
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][unit_selling_price]" class="form-control form-control-sm text-end price-input font-monospace fw-bold" min="0" step="any" value="${sellingPrice}" required>
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

                const select = tr.querySelector('.product-select');
                const qtyInput = tr.querySelector('.qty-input');
                const costInput = tr.querySelector('.cost-input');
                const priceInput = tr.querySelector('.price-input');
                const removeBtn = tr.querySelector('.remove-row-btn');

                const handleSelectChange = function() {
                    const selectedOpt = select.options[select.selectedIndex];
                    if (selectedOpt && selectedOpt.dataset.cost) {
                        costInput.value = parseFloat(selectedOpt.dataset.cost) || 0;
                    }
                    if (selectedOpt && selectedOpt.dataset.price) {
                        priceInput.value = parseFloat(selectedOpt.dataset.price) || 0;
                    }
                    calculateRowSubtotal(tr);
                };

                select.addEventListener('change', handleSelectChange);
                $(select).on('select2:select change', handleSelectChange);

                qtyInput.addEventListener('input', () => calculateRowSubtotal(tr));
                costInput.addEventListener('input', () => calculateRowSubtotal(tr));
                priceInput.addEventListener('input', () => calculateRowSubtotal(tr));

                removeBtn.addEventListener('click', function() {
                    if (tbody.children.length > 1) {
                        tr.remove();
                        reindexRows();
                        calculateGrandTotal();
                    } else {
                        alert('Penerimaan barang konsinyasi minimal harus memiliki 1 item.');
                    }
                });

                calculateRowSubtotal(tr);
            }

            function reindexRows() {
                Array.from(tbody.children).forEach((tr, index) => {
                    tr.querySelector('.product-select').name = `items[${index}][master_product_id]`;
                    tr.querySelector('.qty-input').name = `items[${index}][qty_received]`;
                    tr.querySelector('.cost-input').name = `items[${index}][unit_cost_price]`;
                    tr.querySelector('.price-input').name = `items[${index}][unit_selling_price]`;
                });
            }

            function calculateRowSubtotal(tr) {
                const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
                const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
                const subtotal = qty * cost;
                tr.querySelector('.subtotal-display').value = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
                calculateGrandTotal();
            }

            function calculateGrandTotal() {
                let totalQty = 0;
                let totalHpp = 0;
                let totalProfit = 0;

                Array.from(tbody.children).forEach(tr => {
                    const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
                    const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
                    const price = parseFloat(tr.querySelector('.price-input').value) || 0;

                    totalQty += qty;
                    totalHpp += (qty * cost);
                    totalProfit += (qty * (price - cost));
                });

                const elQty = document.getElementById('big-total-qty');
                const elHpp = document.getElementById('big-total-hpp');
                const elProfit = document.getElementById('big-total-profit');

                if (elQty) elQty.innerText = totalQty.toLocaleString() + ' PCS';
                if (elHpp) elHpp.innerText = 'Rp ' + Math.round(totalHpp).toLocaleString('id-ID');
                if (elProfit) elProfit.innerText = '+Rp ' + Math.round(totalProfit).toLocaleString('id-ID');
            }

            if (btnAddRow) {
                btnAddRow.addEventListener('click', () => createRow());
            }

            // Inisialisasi baris pertama secara otomatis
            createRow();
        });
    </script>
@endpush
