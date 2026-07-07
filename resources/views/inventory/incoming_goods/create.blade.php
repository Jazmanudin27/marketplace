@extends('layouts.app')
@section('title', 'Barang Masuk')
@section('page-title', 'Barang Masuk')

@section('content')
    <form action="{{ route('incoming_goods.store') }}" method="POST" id="incomingForm">
        @csrf

        <div class="row g-3">
            {{-- Left Column: Main Form --}}
            <div class="col-md-12">

                {{-- Card 1: Informasi Penerimaan --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div
                        class="card-header bg-transparent d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:36px;height:36px;">
                                <i class="fas fa-truck-loading text-primary" style="font-size:.85rem;"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Form Penerimaan Barang</h6>
                        </div>
                        <a href="{{ route('incoming_goods.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>

                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1">Sumber Penerimaan</label>
                                <select id="source-type" name="source_type" class="form-select form-select-sm">
                                    <option value="supplier">Pembelian dari Supplier</option>
                                    <option value="return">Retur Pembeli (Shopee/TikTok/dll)</option>
                                    <option value="other">Produksi / Lainnya</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1">Departemen Penerima <span
                                        class="text-danger">*</span></label>
                                <select name="department_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Departemen --</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1">Waktu Masuk <span
                                        class="text-danger">*</span></label>
                                <input type="datetime-local" name="incoming_date" class="form-control form-control-sm"
                                    required value="{{ old('incoming_date', now()->format('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1">Nomor Referensi <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="reference" class="form-control form-control-sm" required
                                    placeholder="Referensi Faktur"
                                    value="{{ old('reference', 'INV-' . date('Ymd') . '-' . rand(100, 999)) }}">
                            </div>

                            {{-- Supplier Fields --}}
                            <div class="col-md-4 supplier-fields">
                                <label class="form-label form-label-sm fw-semibold mb-1">Supplier</label>
                                <select id="supplier-select" name="supplier_id" class="form-select form-select-sm"
                                    style="width:100%">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" data-contact="{{ $supplier->phone }}"
                                            data-address="{{ $supplier->address }}">
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 supplier-fields">
                                <label class="form-label form-label-sm fw-semibold mb-1">Hubungkan Purchase Order
                                    (PO)</label>
                                <select id="purchase-order-select" name="purchase_order_id"
                                    class="form-select form-select-sm" style="width:100%">
                                    <option value="">-- Tanpa PO (Penerimaan Manual) --</option>
                                    @foreach ($purchaseOrders as $po)
                                        <option value="{{ $po->id }}" data-supplier="{{ $po->supplier_id }}">
                                            {{ $po->po_number }} (Supplier: {{ $po->supplier->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 supplier-fields">
                                <label class="form-label form-label-sm fw-semibold mb-1">Tanggal Jatuh Tempo</label>
                                <input type="date" name="due_date" class="form-control form-control-sm"
                                    value="{{ old('due_date', now()->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-8 supplier-fields mt-2">
                                <label class="form-label form-label-sm fw-semibold mb-1">Alamat Supplier</label>
                                <input type="text" id="supplier-address" name="notes"
                                    class="form-control form-control-sm" placeholder="Keterangan Tambahan / Alamat">
                            </div>

                            <div class="col-md-4 supplier-fields mt-2">
                                <label class="form-label form-label-sm fw-semibold mb-1">No HP Supplier</label>
                                <input type="text" id="supplier-contact" name="contact"
                                    class="form-control form-control-sm" placeholder="No HP">
                            </div>

                            {{-- Return Fields --}}
                            <div class="col-md-6 return-fields" style="display:none">
                                <label class="form-label form-label-sm fw-semibold mb-1">Nomor Pesanan / Resi</label>
                                <input type="text" name="order_id" class="form-control form-control-sm"
                                    placeholder="Contoh: 230510XXXXXX">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Detail Barang --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2 px-3 border-bottom">
                        <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:36px;height:36px;">
                            <i class="fas fa-list text-primary" style="font-size:.85rem;"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Detail Barang Masuk</h6>
                    </div>

                    <div class="card-body p-3">
                        {{-- Quick Entry Bar --}}
                        <div class="bg-light border rounded-3 p-3 mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="form-label form-label-sm fw-semibold mb-1">Pilih Barang</label>
                                    <select id="entry-product" class="form-select form-select-sm product-select"
                                        style="width:100%">
                                        <option value="">-- Ketik Nama/SKU Barang --</option>
                                        <optgroup label="Bahan Baku & Kemasan" id="group-materials">
                                            @foreach ($materials as $material)
                                                <option value="{{ $material->id }}" data-type="material"
                                                    data-sku="{{ $material->sku }}" data-name="{{ $material->name }}"
                                                    data-unit="{{ $material->unit ?? 'PCS' }}"
                                                    data-cost="{{ $material->cost_price }}">
                                                    {{ $material->sku }} - {{ $material->name }} (Bahan/Kemasan)
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Inventory & ATK" id="group-inventory">
                                            @foreach ($inventoryItems as $inv)
                                                <option value="{{ $inv->id }}" data-type="inventory"
                                                    data-sku="{{ $inv->sku }}" data-name="{{ $inv->name }}"
                                                    data-unit="{{ $inv->unit ?? 'PCS' }}"
                                                    data-cost="{{ $inv->cost_price }}">
                                                    {{ $inv->sku }} - {{ $inv->name }} (ATK/Inventory)
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Produk Jadi" id="group-products">
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}" data-type="product"
                                                    data-sku="{{ $product->sku }}" data-name="{{ $product->name }}"
                                                    data-unit="{{ $product->unit ?? 'PCS' }}"
                                                    data-cost="{{ $product->cost_price }}">
                                                    {{ $product->sku }} - {{ $product->name }} (Produk)
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-4 col-md-1">
                                    <label class="form-label form-label-sm fw-semibold mb-1">Satuan</label>
                                    <input type="text" id="entry-unit"
                                        class="form-control form-control-sm text-center bg-white" readonly disabled>
                                </div>
                                <div class="col-4 col-md-1">
                                    <label class="form-label form-label-sm fw-semibold mb-1">Jumlah</label>
                                    <input type="number" id="entry-qty" class="form-control form-control-sm text-end"
                                        min="1" placeholder="0">
                                </div>
                                <div class="col-6 col-md-1">
                                    <label class="form-label form-label-sm fw-semibold mb-1">Harga Modal</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="entry-cost"
                                            class="form-control form-control-sm text-end rupiah-mask" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-6 col-md-1">
                                    <label class="form-label form-label-sm fw-semibold mb-1">Potongan</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="entry-discount"
                                            class="form-control form-control-sm text-end rupiah-mask" placeholder="0"
                                            value="0">
                                    </div>
                                </div>
                                <div class="col-8 col-md-2">
                                    <label class="form-label form-label-sm fw-semibold mb-1">Total</label>
                                    <input type="text" id="entry-total"
                                        class="form-control form-control-sm text-end fw-bold bg-white" readonly disabled
                                        placeholder="Rp 0">
                                </div>
                                <div class="col-4 col-md-1 d-grid">
                                    <button type="button" id="btn-add-item" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Cart Table --}}
                        <div class="table-responsive rounded border mb-0">
                            <table class="table table-sm table-bordered table-hover align-middle mb-0" id="cart-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:5%">No.</th>
                                        <th style="width:12%">Kode</th>
                                        <th style="width:25%">Nama</th>
                                        <th class="text-center" style="width:8%">Satuan</th>
                                        <th class="text-center" style="width:10%">Jumlah</th>
                                        <th class="text-end" style="width:13%">Harga</th>
                                        <th class="text-end" style="width:10%">Pot. (Rp)</th>
                                        <th class="text-end" style="width:12%">Total</th>
                                        <th class="text-center" style="width:5%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body">
                                    <tr id="empty-row">
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-light mb-3"
                                                style="width:56px;height:56px;">
                                                <i class="fas fa-shopping-cart fa-lg text-muted opacity-50"></i>
                                            </div>
                                            <div class="fw-semibold small">Belum ada barang yang ditambahkan</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Sticky Sidebar --}}
            <div class="col-md-12">
                <div class="position-sticky" style="top: 20px;">
                    {{-- Grand Total Box --}}
                    <div
                        class="bg-primary rounded-3 p-4 mb-3 position-relative overflow-hidden shadow-sm d-flex flex-column justify-content-center border-0">
                        <i class="fas fa-shopping-cart position-absolute text-white opacity-25"
                            style="font-size:5rem;right:15px;bottom:10px;pointer-events:none;"></i>
                        <div class="text-white-50 small text-uppercase fw-semibold mb-2" style="letter-spacing:.05em;">
                            Total Barang Masuk
                        </div>
                        <div class="text-white fw-bold" style="font-size:2rem;font-family:'Outfit',sans-serif;"
                            id="display-grand-total">
                            Rp 0
                        </div>
                    </div>

                    {{-- Summary Card --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark border-bottom">
                            <i class="fas fa-info-circle me-1"></i> Ringkasan Penerimaan
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between small py-2 border-bottom">
                                <span class="text-muted">Total QTY</span>
                                <span class="fw-bold text-primary" id="summary-qty">0</span>
                            </div>
                            <div class="d-flex justify-content-between small py-2 border-bottom">
                                <span class="text-muted">Total Potongan</span>
                                <span class="fw-bold text-danger" id="summary-discount">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between small py-2">
                                <span class="fw-semibold">Grand Total</span>
                                <span class="fw-bold text-success" id="summary-total">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons Card --}}
                    <div class="card border-0 shadow-sm p-3 bg-light bg-opacity-75">
                        <div class="d-grid gap-2">
                            <button type="submit"
                                class="btn btn-success fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2 rounded-3">
                                <i class="fas fa-save"></i> Simpan Barang Masuk
                            </button>
                            <a href="{{ route('incoming_goods.index') }}"
                                class="btn btn-outline-secondary py-2 fw-semibold rounded-3">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden form inputs container --}}
        <div id="hidden-inputs-container"></div>
    </form>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            let cartItems = [];

            // Initialize Select2
            $('#entry-product').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Ketik Nama/SKU --'
            });

            $('#supplier-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
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

            // Initialize PO select
            $('#purchase-order-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih PO Terbuka --',
                allowClear: true
            });

            // Handle PO Selection and load items via AJAX
            $('#purchase-order-select').on('change', function() {
                const poId = $(this).val();
                if (!poId) {
                    $('#supplier-select').prop('disabled', false).val('').trigger('change');
                    return;
                }

                Swal.fire({
                    title: 'Memuat Item PO...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/purchase-orders/${poId}/items`,
                    method: 'GET',
                    success: function(response) {
                        Swal.close();
                        if (response.success && response.items.length > 0) {
                            cartItems = [];
                            response.items.forEach(function(item) {
                                const remainingQty = item.ordered_quantity - item
                                    .received_quantity;
                                const qty = remainingQty > 0 ? remainingQty : 1;
                                cartItems.push({
                                    id: item.item_id,
                                    type: item.item_type,
                                    sku: item.sku,
                                    name: item.product_name,
                                    unit: 'PCS',
                                    qty: qty,
                                    cost: parseFloat(item.cost_price || 0),
                                    disc: 0,
                                    total: qty * parseFloat(item.cost_price ||
                                        0)
                                });
                            });

                            $('#supplier-select').val(response.supplier_id).trigger('change');
                            const poNumber = $('#purchase-order-select option:selected').text()
                                .trim().split(' ')[0];
                            $('input[name="reference"]').val(poNumber);

                            renderCart();
                        } else {
                            Swal.fire({
                                icon: 'info',
                                title: 'Info',
                                text: 'Tidak ada item yang perlu diterima untuk PO ini.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Gagal memuat item Purchase Order.'
                        });
                    }
                });
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

                // Reset cart when source changes
                cartItems = [];
                renderCart();

                // Clear PO selection
                $('#purchase-order-select').val('').trigger('change.select2');
                $('#supplier-select').prop('disabled', false).val('').trigger('change.select2');

                // Filter options in select2
                const entryProduct = $('#entry-product');
                entryProduct.val(null); // clear select selection

                if (val === 'supplier') {
                    // Purchase: only allow materials & inventory (disable products)
                    entryProduct.find('optgroup#group-materials, optgroup#group-inventory').prop('disabled',
                        false);
                    entryProduct.find('optgroup#group-products').prop('disabled', true);
                } else if (val === 'return') {
                    // Return: only allow products (disable materials & inventory)
                    entryProduct.find('optgroup#group-materials, optgroup#group-inventory').prop('disabled',
                        true);
                    entryProduct.find('optgroup#group-products').prop('disabled', false);
                } else {
                    // Other: allow all
                    entryProduct.find('optgroup').prop('disabled', false);
                }

                // Re-initialize select2 to reflect option disabled states
                entryProduct.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '-- Ketik Nama/SKU --'
                });
            });

            // DOM Elements
            const elProduct = $('#entry-product');
            const elUnit = $('#entry-unit');
            const elQty = $('#entry-qty');
            const elCost = $('#entry-cost');
            const elDiscount = $('#entry-discount');
            const elTotal = $('#entry-total');

            // Formatter & Parser
            const formatRp = (num) => 'Rp ' + parseFloat(num).toLocaleString('id-ID');
            const formatNumber = (num) => parseFloat(num).toLocaleString('id-ID');
            const parseRupiah = (val) => {
                if (typeof val === 'number') return val;
                if (!val) return 0;
                return parseFloat(val.replace(/\./g, '').replace(/,/g, '.')) || 0;
            };

            // Rupiah Mask Handler
            const handleRupiahInput = function(e) {
                let cursorPosition = e.target.selectionStart;
                let originalLength = e.target.value.length;
                let cleanValue = e.target.value.replace(/[^0-9]/g, '');
                if (cleanValue === '') {
                    $(e.target).val('');
                    return;
                }
                let formatted = formatNumber(cleanValue);
                $(e.target).val(formatted);

                // Adjust cursor position
                let newLength = formatted.length;
                cursorPosition = cursorPosition + (newLength - originalLength);
                e.target.setSelectionRange(cursorPosition, cursorPosition);
            };

            elCost.on('input', handleRupiahInput);
            elDiscount.on('input', handleRupiahInput);

            // Calculate Entry Total
            const calcEntryTotal = () => {
                const qty = parseFloat(elQty.val()) || 0;
                const cost = parseRupiah(elCost.val());
                const disc = parseRupiah(elDiscount.val());
                const total = (qty * cost) - disc;
                elTotal.val(formatRp(total > 0 ? total : 0));
            };

            elQty.on('input', calcEntryTotal);
            elCost.on('input', calcEntryTotal);
            elDiscount.on('input', calcEntryTotal);

            // Product Selection Event
            elProduct.on('change', function() {
                const selected = $(this).find('option:selected');
                if (selected.val()) {
                    elUnit.val(selected.data('unit'));
                    elCost.val(formatNumber(selected.data('cost') || 0));
                    if (!elQty.val()) elQty.val(1);
                    elDiscount.val(0);
                    calcEntryTotal();
                    elQty.trigger('focus');
                } else {
                    elUnit.val('');
                    elCost.val('');
                    elQty.val('');
                    elDiscount.val('');
                    elTotal.val('');
                }
            });

            // Add to Cart
            $('#btn-add-item').on('click', function() {
                const selected = elProduct.find('option:selected');
                const id = selected.val();

                if (!id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih Barang',
                        text: 'Pilih barang terlebih dahulu!'
                    });
                    return;
                }

                const qty = parseFloat(elQty.val());
                if (!qty || qty <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Jumlah Kosong',
                        text: 'Jumlah barang harus lebih dari 0!'
                    });
                    return;
                }

                const cost = parseRupiah(elCost.val()) || 0;
                const disc = parseRupiah(elDiscount.val()) || 0;
                const total = (qty * cost) - disc;

                const item = {
                    id: id,
                    type: selected.data('type'),
                    sku: selected.data('sku'),
                    name: selected.data('name'),
                    unit: selected.data('unit'),
                    qty: qty,
                    cost: cost,
                    disc: disc,
                    total: total > 0 ? total : 0
                };

                const existingIndex = cartItems.findIndex(i => i.id === id && i.type === item.type);
                if (existingIndex > -1) {
                    cartItems[existingIndex].qty += qty;
                    cartItems[existingIndex].disc += disc;
                    cartItems[existingIndex].total =
                        (cartItems[existingIndex].qty * cartItems[existingIndex].cost) - cartItems[
                            existingIndex].disc;
                } else {
                    cartItems.push(item);
                }

                elProduct.val(null).trigger('change');
                elUnit.val('');
                elQty.val('');
                elCost.val('');
                elDiscount.val('');
                elTotal.val('');
                elProduct.trigger('focus');

                renderCart();
            });

            // Enter key shortcut
            $('#incomingForm').on('keypress', function(e) {
                if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                    e.preventDefault();
                    if (['entry-qty', 'entry-cost', 'entry-discount'].includes(e.target.id)) {
                        $('#btn-add-item').trigger('click');
                    }
                }
            });

            // Render Cart Table
            const renderCart = () => {
                const tbody = $('#cart-body');
                const container = $('#hidden-inputs-container');

                tbody.empty();
                container.empty();

                let sumQty = 0;
                let sumDisc = 0;
                let grandTotal = 0;

                if (cartItems.length === 0) {
                    tbody.html(`
                        <tr id="empty-row">
                            <td colspan="9" class="text-center text-muted py-5">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-light mb-3"
                                     style="width:56px;height:56px;">
                                    <i class="fas fa-shopping-cart fa-lg text-muted opacity-50"></i>
                                </div>
                                <div class="fw-semibold small">Belum ada barang yang ditambahkan</div>
                            </td>
                        </tr>`);
                } else {
                    let tbodyHtml = '';
                    let containerHtml = '';
                    cartItems.forEach((item, index) => {
                        sumQty += item.qty;
                        sumDisc += item.disc;
                        grandTotal += item.total;

                        tbodyHtml += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td><code class="bg-light text-secondary px-1 rounded border small">${item.sku}</code></td>
                                <td class="fw-semibold small">${item.name}</td>
                                <td class="text-center"><span class="badge bg-secondary bg-opacity-25 text-secondary border">${item.unit}</span></td>
                                <td class="text-center fw-bold">${item.qty}</td>
                                <td class="text-end small font-monospace">${formatRp(item.cost)}</td>
                                <td class="text-end small font-monospace text-danger">${formatRp(item.disc)}</td>
                                <td class="text-end fw-bold small font-monospace">${formatRp(item.total)}</td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm btn-delete-item"
                                        data-index="${index}"
                                        style="padding:.15rem .45rem;">
                                        <i class="fas fa-trash" style="font-size:.65rem;"></i>
                                    </button>
                                </td>
                            </tr>`;

                        containerHtml +=
                            `<input type="hidden" name="item_types[]" value="${item.type}">`;
                        containerHtml += `<input type="hidden" name="item_ids[]" value="${item.id}">`;
                        containerHtml +=
                            `<input type="hidden" name="quantities[]" value="${item.qty}">`;
                        const netCost = item.qty > 0 ? (item.total / item.qty) : item.cost;
                        containerHtml +=
                            `<input type="hidden" name="cost_prices[]" value="${netCost}">`;
                    });
                    tbody.html(tbodyHtml);
                    container.html(containerHtml);
                }

                $('#summary-qty').text(sumQty);
                $('#summary-discount').text(formatRp(sumDisc));
                $('#summary-total').text(formatRp(grandTotal));
                $('#display-grand-total').text(formatRp(grandTotal));
            };

            // Delete Item
            $('#cart-table').on('click', '.btn-delete-item', function() {
                const index = $(this).data('index');
                cartItems.splice(index, 1);
                renderCart();
            });

            // Prevent submit if cart empty
            $('#incomingForm').on('submit', function(e) {
                if (cartItems.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tabel Kosong',
                        text: 'Tambahkan barang terlebih dahulu!'
                    });
                }
            });

            // Trigger default source type change on page load
            $('#source-type').trigger('change');
        });
    </script>
@endpush
