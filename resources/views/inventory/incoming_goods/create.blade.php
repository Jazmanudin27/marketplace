@extends('layouts.app')
@section('title', 'Barang Masuk')
@section('page-title', 'Barang Masuk')

@section('content')
    <div class="row">
        <div class="col-md-12">





            <form action="{{ route('incoming_goods.store') }}" method="POST" id="incomingForm">
                @csrf

                <!-- HEADER SECTION -->
                <div class="pos-card mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label-pos">Sumber Penerimaan</label>
                                    <select id="source-type" name="source_type"
                                        class="form-select form-select-sm form-select-dark">
                                        <option value="supplier">Pembelian dari Supplier</option>
                                        <option value="return">Retur Pembeli (Shopee/TikTok/dll)</option>
                                        <option value="other">Produksi / Lainnya</option>
                                    </select>

                                    <div class="supplier-fields">
                                        <label class="form-label-pos">Supplier</label>
                                        <select id="supplier-select" name="supplier_id"
                                            class="form-select form-select-sm form-select-dark" style="width: 100%;">
                                            <option value="">-- Pilih Supplier --</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" data-contact="{{ $supplier->phone }}"
                                                    data-address="{{ $supplier->address }}">
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <label class="form-label-pos">Waktu Masuk</label>
                                    <input type="datetime-local" name="incoming_date"
                                        class="form-control form-control-sm form-control-dark" required
                                        value="{{ old('incoming_date', now()->format('Y-m-d\TH:i')) }}">

                                    <div class="supplier-fields">
                                        <label class="form-label-pos">Tanggal Jatuh Tempo</label>
                                        <input type="date" name="due_date"
                                            class="form-control form-control-sm form-control-dark"
                                            title="Tanggal Jatuh Tempo"
                                            value="{{ old('due_date', now()->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pos">Nomor Referensi / Catatan</label>
                                    <input type="text" name="reference"
                                        class="form-control form-control-sm form-control-dark" required
                                        placeholder="Nomor PO / Referensi Faktur"
                                        value="{{ old('reference', 'INV-' . date('Ymd') . '-' . rand(100, 999)) }}">

                                    <div class="return-fields" style="display: none;">
                                        <label class="form-label-pos">Nomor Pesanan / Resi</label>
                                        <input type="text" name="order_id"
                                            class="form-control form-control-sm form-control-dark"
                                            placeholder="Contoh: 230510XXXXXX">
                                    </div>

                                    <div class="supplier-fields">
                                        <label class="form-label-pos">Alamat Supplier</label>
                                        <input type="text" id="supplier-address" name="notes"
                                            class="form-control form-control-sm form-control-dark"
                                            placeholder="Keterangan Tambahan / Alamat">

                                        <label class="form-label-pos">No HP Supplier</label>
                                        <input type="text" id="supplier-contact" name="contact"
                                            class="form-control form-control-sm form-control-dark" placeholder="No HP">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="blue-total-box h-100">
                                <i class="fas fa-shopping-cart"></i>
                                <div class="total-label">Total Barang Masuk</div>
                                <div class="total-amount" id="display-grand-total">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ITEMS SECTION -->
                <div class="pos-card">

                    <!-- Quick Entry Bar -->
                    <div class="p-3 mb-4 rounded entry-bar-box">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label-pos">Pilih Barang</label>
                                <select id="entry-product"
                                    class="form-select form-select-sm form-select-dark product-select" style="width: 100%;">
                                    <option value="">-- Ketik Nama/SKU Barang --</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-sku="{{ $product->sku }}"
                                            data-name="{{ $product->name }}" data-unit="{{ $product->unit ?? 'PCS' }}"
                                            data-cost="{{ $product->cost_price }}">
                                            {{ $product->sku }} - {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label-pos">Satuan</label>
                                <input type="text" id="entry-unit"
                                    class="form-control form-control-sm form-control-dark text-center" readonly disabled>
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label-pos">Jumlah</label>
                                <input type="number" id="entry-qty"
                                    class="form-control form-control-sm form-control-dark text-right" min="1"
                                    placeholder="0">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label-pos">Harga Modal</label>
                                <input type="number" id="entry-cost"
                                    class="form-control form-control-sm form-control-dark text-right" min="0"
                                    step="0.01" placeholder="0">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label-pos">Potongan (Rp)</label>
                                <input type="number" id="entry-discount"
                                    class="form-control form-control-sm form-control-dark text-right" min="0"
                                    step="0.01" placeholder="0" value="0">
                            </div>
                            <div class="col-8 col-md-2">
                                <label class="form-label-pos">Total</label>
                                <input type="text" id="entry-total"
                                    class="form-control form-control-sm form-control-dark text-right fw-bold" readonly
                                    disabled placeholder="Rp 0">
                            </div>
                            <div class="col-4 col-md-1 d-grid">
                                <button type="button" id="btn-add-item" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Shopping Cart Table -->
                    <div class="table-responsive">
                        <table class="pos-table" style="width: 100%; text-align: left;" id="cart-table">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">No.</th>
                                    <th width="12%">Kode</th>
                                    <th width="23%">Nama</th>
                                    <th width="8%" class="text-center">Satuan</th>
                                    <th width="10%" class="text-center">Jumlah</th>
                                    <th width="12%" class="text-right">Harga</th>
                                    <th width="10%" class="text-right">Pot. (Rp)</th>
                                    <th width="15%" class="text-right">Total</th>
                                    <th width="5%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <!-- Rows will be added here by JS -->
                                <tr id="empty-row">
                                    <td colspan="9" class="text-center text-muted" style="padding: 30px;">Belum ada
                                        barang yang ditambahkan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Summary Footer -->
                    <div class="pos-summary text-right">
                        <div style="text-align: right;">Total QTY: <span id="summary-qty"
                                style="color: var(--primary); font-size: 1.1rem; margin-left: 10px;">0</span></div>
                        <div style="text-align: right;">Total Potongan: <span id="summary-discount"
                                style="color: var(--danger); font-size: 1.1rem; margin-left: 10px;">Rp 0</span></div>
                        <div style="text-align: right;">Grand Total: <span id="summary-total"
                                style="color: var(--success); font-size: 1.1rem; margin-left: 10px;">Rp 0</span></div>
                    </div>

                    <!-- Hidden form inputs container -->
                    <div id="hidden-inputs-container"></div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm me-1">Batal</a>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-save"></i> Simpan Barang Masuk
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let cartItems = [];

            // Initialize Select2
            $('#entry-product').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownCssClass: 'dark-dropdown',
                placeholder: '-- Ketik Nama/SKU --'
            });

            $('#supplier-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownCssClass: 'dark-dropdown',
                placeholder: '-- Pilih Supplier --',
                allowClear: true
            });

            // Supplier Auto-fill
            $('#supplier-select').on('change', function() {
                const selected = $(this).find('option:selected');
                if (selected.val()) {
                    $('#supplier-contact').val(selected.data('contact'));
                    $('#supplier-address').val(selected.data('address'));
                } else {
                    $('#supplier-contact').val('');
                    $('#supplier-address').val('');
                }
            });

            // Source Type Toggle
            $('#source-type').on('change', function() {
                const val = $(this).val();
                if (val === 'supplier') {
                    $('.supplier-fields').show();
                    $('.return-fields').hide();
                } else if (val === 'return') {
                    $('.supplier-fields').hide();
                    $('.return-fields').show();
                } else {
                    $('.supplier-fields').hide();
                    $('.return-fields').hide();
                }
            });

            // DOM Elements
            const elProduct = $('#entry-product');
            const elUnit = document.getElementById('entry-unit');
            const elQty = document.getElementById('entry-qty');
            const elCost = document.getElementById('entry-cost');
            const elDiscount = document.getElementById('entry-discount');
            const elTotal = document.getElementById('entry-total');

            // Formatter
            const formatRp = (num) => {
                return 'Rp ' + parseFloat(num).toLocaleString('id-ID');
            };

            // Calculate Entry Total
            const calcEntryTotal = () => {
                const qty = parseFloat(elQty.value) || 0;
                const cost = parseFloat(elCost.value) || 0;
                const disc = parseFloat(elDiscount.value) || 0;
                const total = (qty * cost) - disc;
                elTotal.value = formatRp(total > 0 ? total : 0);
            };

            // Event Listeners for Entry Inputs
            elQty.addEventListener('input', calcEntryTotal);
            elCost.addEventListener('input', calcEntryTotal);
            elDiscount.addEventListener('input', calcEntryTotal);

            // Product Selection Event
            elProduct.on('change', function() {
                const selected = $(this).find('option:selected');
                if (selected.val()) {
                    elUnit.value = selected.data('unit');
                    elCost.value = selected.data('cost');
                    if (!elQty.value) elQty.value = 1;
                    elDiscount.value = 0;
                    calcEntryTotal();
                    // Focus to qty
                    elQty.focus();
                } else {
                    elUnit.value = '';
                    elCost.value = '';
                    elQty.value = '';
                    elDiscount.value = '';
                    elTotal.value = '';
                }
            });

            // Add to Cart Logic
            document.getElementById('btn-add-item').addEventListener('click', function() {
                const selected = elProduct.find('option:selected');
                const id = selected.val();

                if (!id) {
                    alert('Pilih barang terlebih dahulu!');
                    return;
                }

                const qty = parseFloat(elQty.value);
                if (!qty || qty <= 0) {
                    alert('Jumlah barang harus lebih dari 0!');
                    return;
                }

                const cost = parseFloat(elCost.value) || 0;
                const disc = parseFloat(elDiscount.value) || 0;
                const total = (qty * cost) - disc;

                const item = {
                    id: id,
                    sku: selected.data('sku'),
                    name: selected.data('name'),
                    unit: selected.data('unit'),
                    qty: qty,
                    cost: cost,
                    disc: disc,
                    total: total > 0 ? total : 0
                };

                // Check if product already exists in cart, then update it instead of duplicate
                const existingIndex = cartItems.findIndex(i => i.id === id);
                if (existingIndex > -1) {
                    cartItems[existingIndex].qty += qty;
                    cartItems[existingIndex].disc += disc;
                    cartItems[existingIndex].total = (cartItems[existingIndex].qty * cartItems[
                        existingIndex].cost) - cartItems[existingIndex].disc;
                } else {
                    cartItems.push(item);
                }

                // Reset Entry Form
                elProduct.val(null).trigger('change');
                elUnit.value = '';
                elQty.value = '';
                elCost.value = '';
                elDiscount.value = '';
                elTotal.value = '';
                elProduct.focus();

                renderCart();
            });

            // Enter key to add
            document.getElementById('purchase-form').addEventListener('keypress', function(e) {
                // Prevent form submit on Enter, unless it's on the submit button
                if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                    e.preventDefault();
                    // If focus is in one of the entry inputs, click add
                    if (['entry-qty', 'entry-cost', 'entry-discount'].includes(e.target.id)) {
                        document.getElementById('btn-add-item').click();
                    }
                }
            });

            // Render Cart Table
            const renderCart = () => {
                const tbody = document.getElementById('cart-body');
                const container = document.getElementById('hidden-inputs-container');

                tbody.innerHTML = '';
                container.innerHTML = '';

                let sumQty = 0;
                let sumDisc = 0;
                let grandTotal = 0;

                if (cartItems.length === 0) {
                    tbody.innerHTML =
                        '<tr id="empty-row"><td colspan="9" class="text-center text-muted" style="padding: 30px;">Belum ada barang yang ditambahkan</td></tr>';
                } else {
                    cartItems.forEach((item, index) => {
                        sumQty += item.qty;
                        sumDisc += item.disc;
                        grandTotal += item.total;

                        // Table Row
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                        <td class="text-center">${index + 1}</td>
                        <td>${item.sku}</td>
                        <td>${item.name}</td>
                        <td class="text-center">${item.unit}</td>
                        <td class="text-center"><strong>${item.qty}</strong></td>
                        <td class="text-right">${formatRp(item.cost)}</td>
                        <td class="text-right">${formatRp(item.disc)}</td>
                        <td class="text-right"><strong>${formatRp(item.total)}</strong></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn-delete-item" data-index="${index}" style="padding: 0.1rem 0.5rem;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                        tbody.appendChild(tr);

                        // Hidden Inputs for Form Submission
                        container.innerHTML +=
                            `<input type="hidden" name="products[]" value="${item.id}">`;
                        container.innerHTML +=
                            `<input type="hidden" name="quantities[]" value="${item.qty}">`;
                        // Calculate real net cost price after discount to update master product correctly
                        const netCost = item.qty > 0 ? (item.total / item.qty) : item.cost;
                        container.innerHTML +=
                            `<input type="hidden" name="cost_prices[]" value="${netCost}">`;
                    });
                }

                // Update Summaries
                document.getElementById('summary-qty').textContent = sumQty;
                document.getElementById('summary-discount').textContent = formatRp(sumDisc);
                document.getElementById('summary-total').textContent = formatRp(grandTotal);
                document.getElementById('display-grand-total').textContent = formatRp(grandTotal);
            };

            // Delete Item Logic
            document.getElementById('cart-table').addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-delete-item');
                if (btn) {
                    const index = btn.getAttribute('data-index');
                    cartItems.splice(index, 1);
                    renderCart();
                }
            });

            // Prevent submission if empty
            document.getElementById('purchase-form').addEventListener('submit', function(e) {
                if (cartItems.length === 0) {
                    e.preventDefault();
                    alert('Tabel rincian barang masih kosong. Silakan tambah barang terlebih dahulu!');
                }
            });
        });
    </script>
@endpush
