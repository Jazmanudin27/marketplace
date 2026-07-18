@extends('layouts.app')

@section('title', 'Input Permintaan Produksi (Manual PO)')
@section('page-title', 'Input Permintaan Produksi')

@section('content')
    <div class="container-fluid py-3">
        <form action="{{ route('orders.store') }}" method="POST" id="manual-order-form">
            @csrf

            {{-- Hidden inputs to be populated via JavaScript --}}
            <input type="hidden" name="invoice_number" id="real_invoice_number" value="" />
            <input type="hidden" name="buyer_name" id="real_buyer_name" value="" />
            <input type="hidden" name="buyer_phone" id="real_buyer_phone" value="" />
            <input type="hidden" name="shipping_address" id="real_shipping_address" value="" />

            <div class="row g-4">
                {{-- Kolom Kiri: Form Wizard --}}
                <div class="col-lg-8">
                    <div class="card border shadow-sm rounded-3 mb-4">
                        {{-- Header --}}
                        <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
                            <div>
                                <h5 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2"></i>Input Permintaan Produksi</h5>
                                <small class="text-white-50">Pesanan manual untuk diproduksi & disimpan di gudang jadi sebelum diserahkan</small>
                            </div>
                            <a href="{{ route('orders.index') }}" class="btn btn-sm btn-light fw-semibold px-3">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                        </div>

                        {{-- Progress Steps --}}
                        <div class="bg-white border-bottom px-4 pt-3 pb-2">
                            <div class="d-flex align-items-center justify-content-center">

                                {{-- Step 1 --}}
                                <div class="d-flex flex-column align-items-center" id="step-dot-1">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-primary border-primary text-white"
                                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-1">
                                        <i class="fas fa-store fa-sm"></i>
                                    </div>
                                    <span class="text-uppercase fw-semibold text-primary mt-1"
                                        style="font-size:0.65rem;letter-spacing:0.06em;">Info & Tipe</span>
                                </div>

                                <div class="flex-grow-1 border-top border-2 mx-2 mb-3" id="conn-1" style="max-width:80px;"></div>

                                {{-- Step 2 --}}
                                <div class="d-flex flex-column align-items-center" id="step-dot-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-light border-secondary text-secondary"
                                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-2">
                                        <i class="fas fa-user-tag fa-sm"></i>
                                    </div>
                                    <span class="text-uppercase fw-semibold text-secondary mt-1" id="step-label-2"
                                        style="font-size:0.65rem;letter-spacing:0.06em;">Pelanggan & Detail</span>
                                </div>

                                <div class="flex-grow-1 border-top border-2 mx-2 mb-3 border-secondary" id="conn-2"
                                    style="max-width:80px;"></div>

                                {{-- Step 3 --}}
                                <div class="d-flex flex-column align-items-center" id="step-dot-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-light border-secondary text-secondary"
                                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-3">
                                        <i class="fas fa-boxes fa-sm"></i>
                                    </div>
                                    <span class="text-uppercase fw-semibold text-secondary mt-1" id="step-label-3"
                                        style="font-size:0.65rem;letter-spacing:0.06em;">Produk</span>
                                </div>

                                <div class="flex-grow-1 border-top border-2 mx-2 mb-3 border-secondary" id="conn-3"
                                    style="max-width:80px;"></div>

                                {{-- Step 4 --}}
                                <div class="d-flex flex-column align-items-center" id="step-dot-4">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-light border-secondary text-secondary"
                                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-4">
                                        <i class="fas fa-clipboard-check fa-sm"></i>
                                    </div>
                                    <span class="text-uppercase fw-semibold text-secondary mt-1" id="step-label-4"
                                        style="font-size:0.65rem;letter-spacing:0.06em;">Review</span>
                                </div>

                            </div>
                        </div>

                        <div class="card-body p-4">

                            {{-- ========== STEP 1: Info Toko & Tipe ========== --}}
                            <div id="step-1">
                                <p class="text-uppercase text-muted fw-semibold mb-3 small">
                                    <i class="fas fa-store me-1"></i> Informasi Toko & Tipe Permintaan
                                </p>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Toko Tujuan <span
                                                class="text-danger">*</span></label>
                                        <select name="store_id" id="store_id" class="form-select form-select-sm" required
                                            style="width:100%">
                                            <option value="">-- Pilih Toko --</option>
                                            @foreach ($stores as $store)
                                                <option value="{{ $store->id }}">
                                                    {{ $store->store_name }} ({{ $store->channel->name ?? 'Marketplace' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Tipe Permintaan Produksi <span
                                                class="text-danger">*</span></label>
                                        <select id="request_type" class="form-select form-select-sm" required>
                                            <option value="Stok Gudang Jadi" selected>Stok Gudang Jadi (Untuk Persediaan)</option>
                                            <option value="PO Pelanggan">PO Pelanggan (Pesanan Khusus Pelanggan)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold small">No. Invoice / Permintaan</label>
                                        <input type="text" class="form-control form-control-sm bg-light" readonly
                                            value="[Otomatis Digenerate Sistem]">
                                        <span class="text-muted small" style="font-size: 0.72rem;">Nomor permintaan produksi akan
                                            otomatis terbit dengan format <strong>REQ-YYYYMMDD-XXXX</strong></span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToStep(2)">
                                        Selanjutnya <i class="fas fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- ========== STEP 2: Detail Pelanggan ========== --}}
                            <div id="step-2" class="d-none">
                                <p class="text-uppercase text-muted fw-semibold mb-3 small" id="step-2-title">
                                    <i class="fas fa-user-tag me-1"></i> Detail Pembeli / Pelanggan PO
                                </p>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small" id="lbl_cust_name">Nama Pelanggan <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="cust_name" class="form-control form-control-sm"
                                            placeholder="Nama lengkap pelanggan">
                                    </div>
                                    <div class="col-md-6" id="cust_phone_container">
                                        <label class="form-label fw-semibold small">No. HP Pelanggan</label>
                                        <input type="text" id="cust_phone" class="form-control form-control-sm"
                                            placeholder="Contoh: 08123456789">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small" id="lbl_cust_address">Alamat Pengiriman <span
                                                class="text-danger">*</span></label>
                                        <textarea id="cust_address" class="form-control form-control-sm" rows="3"
                                            placeholder="Alamat lengkap pengiriman..."></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold"
                                        onclick="goToStep(1)">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToStep(3)">
                                        Selanjutnya <i class="fas fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- ========== STEP 3: Item Produk ========== --}}
                            <div id="step-3" class="d-none">
                                <p class="text-uppercase text-muted fw-semibold mb-3 small">
                                    <i class="fas fa-boxes me-1"></i> Input Produk
                                </p>

                                <!-- Form Input Sementara (Single Row) -->
                                <div class="card border bg-light mb-4">
                                    <div class="card-body py-3 px-3">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-6 col-12">
                                                <label class="form-label form-label-sm fw-semibold mb-1">
                                                    Pilih Produk <span class="text-danger">*</span>
                                                </label>
                                                <select id="input_product_id" class="form-select form-select-sm" style="width:100%">
                                                    <option value="">-- Cari / Pilih Barang --</option>
                                                    @foreach ($products as $p)
                                                        <option value="{{ $p->id }}" data-price="{{ $p->price }}">
                                                            {{ $p->sku ? '[' . $p->sku . '] ' : '' }}{{ $p->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 col-4">
                                                <label class="form-label form-label-sm fw-semibold mb-1">Qty</label>
                                                <input type="number" id="input_qty" class="form-control form-control-sm text-center" value="1" min="1">
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <label class="form-label form-label-sm fw-semibold mb-1">Harga Satuan</label>
                                                <input type="number" id="input_price" class="form-control form-control-sm text-end" value="0" min="0">
                                            </div>
                                            <div class="col-md-1 col-2 text-end">
                                                <button type="button" id="btn-add-to-cart" class="btn btn-primary btn-sm w-100 fw-bold py-1">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-uppercase text-muted fw-semibold mb-2 small">
                                    <i class="fas fa-shopping-cart me-1"></i> Daftar Item yang Ditambahkan
                                </p>

                                <!-- Container Keranjang Hidden Inputs -->
                                <div id="cart-hidden-inputs"></div>

                                <!-- Tabel Daftar Item -->
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-bordered align-middle" id="cart-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-center" style="width: 5%">No</th>
                                                <th>Nama Barang</th>
                                                <th class="text-center" style="width: 15%">Qty</th>
                                                <th class="text-end" style="width: 20%">Harga</th>
                                                <th class="text-end" style="width: 20%">Subtotal</th>
                                                <th class="text-center" style="width: 10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-table-body">
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">Belum ada item barang</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-primary d-flex justify-content-between align-items-center py-2 px-3 mb-4">
                                    <div>
                                        <span class="fw-bold small text-primary-emphasis"><i class="fas fa-info-circle me-1"></i>Total Estimasi:</span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary font-monospace" id="item-count-label">0 item</span>
                                        <strong class="fs-5 text-primary ms-1" id="order-grand-total">Rp 0</strong>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold"
                                        onclick="goToStep(2)">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold"
                                        onclick="goToReview()">
                                        Review Permintaan <i class="fas fa-eye ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- ========== STEP 4: Review & Submit ========== --}}
                            <div id="step-4" class="d-none">
                                <p class="text-uppercase text-muted fw-semibold mb-3 small">
                                    <i class="fas fa-clipboard-check me-1"></i> Ringkasan & Konfirmasi
                                </p>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="card border bg-light h-100">
                                            <div class="card-body py-3 px-3">
                                                <p class="text-uppercase fw-bold text-muted mb-2"
                                                    style="font-size:0.65rem;letter-spacing:0.06em;">
                                                    <i class="fas fa-store me-1"></i>Toko & Tipe Permintaan
                                                </p>
                                                <table class="table table-sm table-borderless mb-0 small">
                                                    <tr>
                                                        <td class="text-muted ps-0" style="width:40%">Toko</td>
                                                        <td class="fw-semibold" id="rev-store">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted ps-0">Tipe Permintaan</td>
                                                        <td class="fw-bold text-primary" id="rev-type">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted ps-0">No. Invoice</td>
                                                        <td class="text-muted font-monospace" id="rev-invoice">[Otomatis
                                                            Generated]</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border bg-light h-100">
                                            <div class="card-body py-3 px-3">
                                                <p class="text-uppercase fw-bold text-muted mb-2"
                                                    style="font-size:0.65rem;letter-spacing:0.06em;">
                                                    <i class="fas fa-user me-1"></i>Pelanggan &amp; Tujuan
                                                </p>
                                                <table class="table table-sm table-borderless mb-0 small">
                                                    <tr>
                                                        <td class="text-muted ps-0" style="width:30%" id="rev-buyer-label">Nama Pelanggan</td>
                                                        <td class="fw-bold text-success" id="rev-buyer">-</td>
                                                    </tr>
                                                    <tr id="rev-phone-row">
                                                        <td class="text-muted ps-0">No. HP</td>
                                                        <td class="fw-semibold" id="rev-phone">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted ps-0" id="rev-address-label">Alamat Kirim</td>
                                                        <td class="fw-semibold" id="rev-address" style="word-break:break-word;">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border bg-light mb-3">
                                    <div class="card-body py-3 px-3">
                                        <p class="text-uppercase fw-bold text-muted mb-2"
                                            style="font-size:0.65rem;letter-spacing:0.06em;">
                                            <i class="fas fa-boxes me-1"></i>Item Permintaan Produksi
                                        </p>
                                        <div id="rev-items" class="mb-2"></div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-uppercase fw-bold text-muted small">Total Estimasi</span>
                                            <span class="fw-bold fs-5 text-success" id="rev-grand-total">Rp 0</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold"
                                        onclick="goToStep(3)">
                                        <i class="fas fa-arrow-left me-1"></i> Edit Pesanan
                                    </button>
                                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
                                        <i class="fas fa-paper-plane me-1"></i> Konfirmasi & Ajukan Permintaan
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Live Summary --}}
                <div class="col-lg-4">
                    <div class="card border shadow-sm rounded-3 position-sticky" style="top: 1.5rem;">
                        <div class="card-header bg-dark text-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i>Ringkasan Permintaan PO</h6>
                        </div>
                        <div class="card-body">
                            <!-- Toko & Tipe -->
                            <div class="mb-3 pb-3 border-bottom">
                                <span class="text-uppercase fw-bold text-muted d-block small mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">Toko &amp; Tipe</span>
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-secondary">Toko:</span>
                                    <span class="fw-semibold text-dark text-truncate text-end ps-2" id="summary-store" style="max-width: 70%;">—</span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-secondary">Tipe:</span>
                                    <span class="fw-bold text-primary" id="summary-type">—</span>
                                </div>
                            </div>

                            <!-- Detail Pelanggan -->
                            <div class="mb-3 pb-3 border-bottom" id="summary-customer-section">
                                <span class="text-uppercase fw-bold text-muted d-block small mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">Detail Pelanggan</span>
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-secondary" id="summary-name-label">Nama:</span>
                                    <span class="fw-semibold text-dark text-truncate text-end ps-2" id="summary-name" style="max-width: 70%;">—</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 small" id="summary-phone-row">
                                    <span class="text-secondary">No HP:</span>
                                    <span class="fw-semibold text-dark" id="summary-phone">—</span>
                                </div>
                                <div class="small">
                                    <span class="text-secondary d-block" id="summary-address-label">Alamat:</span>
                                    <span class="fw-semibold text-dark d-block text-break mt-1 bg-light p-2 rounded-2" id="summary-address">—</span>
                                </div>
                            </div>

                            <!-- Daftar Item -->
                            <div class="mb-3 pb-3 border-bottom">
                                <span class="text-uppercase fw-bold text-muted d-block small mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">Daftar Item</span>
                                <div id="summary-items-list" class="my-2" style="max-height: 200px; overflow-y: auto;">
                                    <span class="text-muted small italic">Belum ada item dipilih</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold text-muted small">Total Estimasi</span>
                                <h4 class="fw-bold text-success mb-0" id="summary-grand-total">Rp 0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentStep = 1;
            let cartItems = []; // Array of { id, name, qty, price }

            const fmt = n => 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID');

            // Init store select2
            $('#store_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih Toko --'
            });

            // Init product search select2
            $('#input_product_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Cari / Pilih Barang --'
            });

            // Auto price fill & focus Qty
            $('#input_product_id').on('change', function() {
                const price = $(this).find('option:selected').data('price') || 0;
                $('#input_price').val(price);
            });

            $('#input_product_id').on('select2:select', function() {
                setTimeout(function() {
                    $('#input_qty').focus().select();
                }, 50);
            });

            // Function to add item to cart
            function addItemToCart() {
                const productId = $('#input_product_id').val();
                const productName = $('#input_product_id option:selected').text().trim();
                const qty = parseInt($('#input_qty').val() || 0);
                const price = parseFloat($('#input_price').val() || 0);

                if (!productId) {
                    showWarn('Silakan pilih produk terlebih dahulu.');
                    $('#input_product_id').select2('open');
                    return;
                }
                if (qty < 1) {
                    showWarn('Quantity minimal 1.');
                    $('#input_qty').focus();
                    return;
                }

                // Check if product already exists in cart
                const existingIndex = cartItems.findIndex(item => item.id === productId);
                if (existingIndex > -1) {
                    // Update qty
                    cartItems[existingIndex].qty += qty;
                    cartItems[existingIndex].price = price;
                } else {
                    // Add new item
                    cartItems.push({
                        id: productId,
                        name: productName,
                        qty: qty,
                        price: price
                    });
                }

                // Clear input fields
                $('#input_product_id').val('').trigger('change');
                $('#input_qty').val('1');
                $('#input_price').val('0');

                // Render cart
                renderCart();

                // Focus back on search dropdown
                setTimeout(function() {
                    $('#input_product_id').select2('open');
                }, 50);
            }

            $('#btn-add-to-cart').on('click', addItemToCart);

            // Trigger add to cart on Enter inside inputs
            $('#input_qty, #input_price').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    addItemToCart();
                }
            });

            // Edit qty in cart
            $(document).on('change', '.cart-qty-edit', function() {
                const index = $(this).data('index');
                const newQty = parseInt($(this).val() || 1);
                if (newQty < 1) {
                    $(this).val(1);
                    cartItems[index].qty = 1;
                } else {
                    cartItems[index].qty = newQty;
                }
                renderCart();
            });

            // Edit price in cart
            $(document).on('change', '.cart-price-edit', function() {
                const index = $(this).data('index');
                const newPrice = parseFloat($(this).val() || 0);
                if (newPrice < 0) {
                    $(this).val(0);
                    cartItems[index].price = 0;
                } else {
                    cartItems[index].price = newPrice;
                }
                renderCart();
            });

            // Remove item from cart
            $(document).on('click', '.btn-remove-cart-item', function() {
                const index = $(this).data('index');
                cartItems.splice(index, 1);
                renderCart();
            });

            function renderCart() {
                let html = '';
                let hiddenInputs = '';
                let total = 0;
                let count = 0;
                let summaryHtml = '';

                if (cartItems.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada item barang</td></tr>';
                } else {
                    cartItems.forEach((item, index) => {
                        const subtotal = item.qty * item.price;
                        total += subtotal;
                        count += item.qty;

                        // Cart table row
                        html += `<tr>
                            <td class="text-center fw-semibold">${index + 1}</td>
                            <td class="text-wrap">${item.name}</td>
                            <td class="text-center">
                                <input type="number" class="form-control form-control-sm text-center mx-auto cart-qty-edit" data-index="${index}" value="${item.qty}" min="1" style="width: 70px;">
                            </td>
                            <td class="text-end">
                                <input type="number" class="form-control form-control-sm text-end ms-auto cart-price-edit" data-index="${index}" value="${item.price}" min="0" style="width: 110px;">
                            </td>
                            <td class="text-end fw-semibold text-success">${fmt(subtotal)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-cart-item" data-index="${index}" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>`;

                        // Hidden form inputs for submission
                        hiddenInputs += `
                            <input type="hidden" name="items[${index}][master_product_id]" value="${item.id}">
                            <input type="hidden" name="items[${index}][quantity]" value="${item.qty}">
                            <input type="hidden" name="items[${index}][price]" value="${item.price}">
                        `;

                        // Sidebar Summary row
                        summaryHtml += `<div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-truncate text-secondary me-2" style="max-width: 180px;">${item.name}</span>
                            <span class="fw-semibold text-dark text-nowrap">${item.qty} x ${fmt(item.price)}</span>
                        </div>`;
                    });
                }

                // Update table body
                $('#cart-table-body').html(html);

                // Update hidden inputs container
                $('#cart-hidden-inputs').html(hiddenInputs);

                // Update totals
                $('#order-grand-total').text(fmt(total));
                $('#item-count-label').text(cartItems.length + ' item');

                // Update right sidebar live summary
                $('#summary-grand-total').text(fmt(total));
                if (summaryHtml) {
                    $('#summary-items-list').html(summaryHtml);
                } else {
                    $('#summary-items-list').html('<span class="text-muted small italic">Belum ada item dipilih</span>');
                }
            }

            // Conditional label/placeholder changes based on request type
            function updateStep2Labels() {
                let type = $('#request_type').val();
                if (type === 'Stok Gudang Jadi') {
                    $('#step-2-title').html('<i class="fas fa-warehouse me-1"></i> Detail Pengaju &amp; Gudang');
                    $('#lbl_cust_name').html('Nama Pengaju / Penerima <span class="text-danger">*</span>');
                    $('#lbl_cust_address').html('Lokasi Penyimpanan / Deskripsi <span class="text-danger">*</span>');
                    $('#cust_name').attr('placeholder', 'Contoh: Bagian Gudang / Nama Pengaju');
                    $('#cust_address').attr('placeholder', 'Contoh: Rak B, Gudang Utama...');
                    
                    // Pre-fill if fields are empty
                    if (!$('#cust_name').val().trim()) {
                        $('#cust_name').val('INTERNAL GUDANG JADI');
                    }
                    if (!$('#cust_address').val().trim()) {
                        $('#cust_address').val('Gudang Jadi (Penyimpanan Utama)');
                    }
                    $('#cust_phone_container').addClass('d-none');
                } else {
                    $('#step-2-title').html('<i class="fas fa-user-tag me-1"></i> Detail Pembeli / Pelanggan PO');
                    $('#lbl_cust_name').html('Nama Pelanggan <span class="text-danger">*</span>');
                    $('#lbl_cust_address').html('Alamat Pengiriman <span class="text-danger">*</span>');
                    $('#cust_name').attr('placeholder', 'Nama lengkap pelanggan');
                    $('#cust_address').attr('placeholder', 'Alamat lengkap pengiriman...');
                    
                    // Clear if it was internal
                    if ($('#cust_name').val() === 'INTERNAL GUDANG JADI') {
                        $('#cust_name').val('');
                    }
                    if ($('#cust_address').val() === 'Gudang Jadi (Penyimpanan Utama)') {
                        $('#cust_address').val('');
                    }
                    $('#cust_phone_container').removeClass('d-none');
                }
            }

            $('#request_type').on('change', function() {
                updateStep2Labels();
                populateHiddenFields();
            });
            $('#store_id').on('change', populateHiddenFields);
            $('#cust_name, #cust_phone, #cust_address').on('input', populateHiddenFields);

            // ===== Update Hidden Fields before proceeding/submitting =====
            function populateHiddenFields() {
                let type = $('#request_type').val();
                let custName = $('#cust_name').val().trim();
                let custPhone = $('#cust_phone').val().trim() || '-';
                let custAddress = $('#cust_address').val().trim();

                if (type === 'Stok Gudang Jadi') {
                    if (!custName) custName = 'INTERNAL GUDANG JADI';
                    if (!custAddress) custAddress = 'Gudang Jadi (Penyimpanan Utama)';
                }

                $('#real_buyer_name').val(custName);
                $('#real_buyer_phone').val(custPhone);
                $('#real_shipping_address').val(custAddress);

                // Update right sidebar live summary
                $('#summary-store').text($('#store_id option:selected').val() ? $('#store_id option:selected').text().trim() : '—');
                $('#summary-type').text(type || '—');
                $('#summary-name').text(custName || '—');
                $('#summary-phone').text(type === 'Stok Gudang Jadi' ? '—' : (custPhone || '—'));
                $('#summary-address').text(custAddress || '—');

                if (type === 'Stok Gudang Jadi') {
                    $('#summary-name-label').text('Pengaju:');
                    $('#summary-address-label').text('Lokasi:');
                    $('#summary-phone-row').addClass('d-none');
                } else {
                    $('#summary-name-label').text('Nama:');
                    $('#summary-address-label').text('Alamat:');
                    $('#summary-phone-row').removeClass('d-none');
                }
            }

            // ===== Step Navigation =====
            window.goToStep = function(target) {
                if (target > currentStep && !validateStep(currentStep)) return;

                // Populate hidden fields as we move
                populateHiddenFields();

                // Update step visuals
                for (let i = 1; i <= 4; i++) {
                    const $icon = $('#step-icon-' + i);
                    const $label = $('#step-label-' + i);

                    $icon.removeClass(
                        'bg-primary border-primary text-white bg-success border-success bg-light border-secondary text-secondary'
                    );
                    $label && $label.removeClass('text-primary text-success text-secondary');

                    if (i < target) {
                        // Done
                        $icon.addClass('bg-success border-success text-white');
                        $label.addClass('text-success');
                    } else if (i === target) {
                        // Active
                        $icon.addClass('bg-primary border-primary text-white');
                        $label.addClass('text-primary');
                    } else {
                        // Pending
                        $icon.addClass('bg-light border-secondary text-secondary');
                        $label.addClass('text-secondary');
                    }
                }

                // Update connector colors
                for (let i = 1; i <= 3; i++) {
                    const $conn = $('#conn-' + i);
                    $conn.removeClass('border-primary border-success border-secondary');
                    $conn.addClass(i < target ? 'border-success' : 'border-secondary');
                }

                // Show / hide panels
                $('#step-1, #step-2, #step-3, #step-4').addClass('d-none');
                $('#step-' + target).removeClass('d-none');
                currentStep = target;

                $('html, body').animate({
                    scrollTop: $('.card').first().offset().top - 16
                }, 280);
            };

            window.goToReview = function() {
                if (cartItems.length === 0) return showWarn('Tambahkan minimal 1 item produk ke keranjang.');

                // Build review data
                populateHiddenFields();

                let type = $('#request_type').val();
                $('#rev-store').text($('#store_id option:selected').text().trim() || '-');
                $('#rev-type').text(type);

                let custName = $('#cust_name').val().trim();
                let custPhone = $('#cust_phone').val().trim();
                let custAddress = $('#cust_address').val().trim();

                $('#rev-buyer').text(custName || '-');
                $('#rev-address').text(custAddress || '-');

                if (type === 'PO Pelanggan') {
                    $('#rev-buyer-label').text('Nama Pelanggan');
                    $('#rev-address-label').text('Alamat Kirim');
                    $('#rev-phone-row').removeClass('d-none');
                    $('#rev-phone').text(custPhone || '-');
                } else {
                    $('#rev-buyer-label').text('Nama Pengaju');
                    $('#rev-address-label').text('Lokasi / Deskripsi');
                    $('#rev-phone-row').addClass('d-none');
                }

                let html = '',
                    total = 0;
                cartItems.forEach((item) => {
                    const sub = item.qty * item.price;
                    total += sub;
                    html += `<div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 mb-1 small bg-white">
                        <span class="fw-semibold text-truncate me-2">${item.name}</span>
                        <span class="text-muted text-nowrap">${item.qty} × ${fmt(item.price)} = <strong class="text-primary">${fmt(sub)}</strong></span>
                    </div>`;
                });
                $('#rev-items').html(html);
                $('#rev-grand-total').text(fmt(total));

                goToStep(4);
            };

            function validateStep(step) {
                if (step === 1) {
                    if (!$('#store_id').val()) {
                        showWarn('Pilih toko terlebih dahulu.');
                        return false;
                    }
                }
                if (step === 2) {
                    let type = $('#request_type').val();
                    if (!$('#cust_name').val().trim()) {
                        showWarn(type === 'PO Pelanggan' ? 'Isi nama pelanggan.' : 'Isi nama pengaju.');
                        return false;
                    }
                    if (!$('#cust_address').val().trim()) {
                        showWarn(type === 'PO Pelanggan' ? 'Isi alamat pengiriman.' : 'Isi lokasi penyimpanan.');
                        return false;
                    }
                }
                return true;
            }

            function showWarn(msg) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: msg,
                        confirmButtonColor: '#0d6efd',
                        timer: 3500,
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'border border-light-subtle shadow-sm'
                        }
                    });
                } else {
                    alert(msg);
                }
            }

            // Run initial load functions
            updateStep2Labels();
            populateHiddenFields();
        });
    </script>
@endpush
