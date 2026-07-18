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
                                    <i class="fas fa-boxes me-1"></i> Item Produk untuk Diproduksi
                                </p>

                                <div id="item-list" class="mb-3"></div>

                                <button type="button" id="btn-add-item"
                                    class="btn btn-outline-primary btn-sm w-100 mb-4 fw-semibold">
                                    <i class="fas fa-plus-circle me-1"></i> Tambah Item Produk
                                </button>

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

    {{-- Item Card Template --}}
    <template id="item-tpl">
        <div class="card border shadow-sm mb-2 item-card" data-index="__IDX__">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span
                        class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 item-num-badge"
                        style="width:24px;height:24px;font-size:0.7rem;">__NUM__</span>
                    <div class="flex-grow-1">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6 col-12">
                                <label class="form-label form-label-sm fw-semibold mb-1">
                                    Nama Produk <span class="text-danger">*</span>
                                </label>
                                <select name="items[__IDX__][master_product_id]"
                                    class="form-select form-select-sm product-select" required style="width:100%">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}" data-price="{{ $p->price }}">
                                            {{ $p->sku ? '[' . $p->sku . '] ' : '' }}{{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-4">
                                <label class="form-label form-label-sm fw-semibold mb-1">Qty</label>
                                <input type="number" name="items[__IDX__][quantity]"
                                    class="form-control form-control-sm text-center qty-input" value="1"
                                    min="1" required>
                            </div>
                            <div class="col-md-2 col-8">
                                <label class="form-label form-label-sm fw-semibold mb-1">Harga Satuan</label>
                                <input type="number" name="items[__IDX__][price]"
                                    class="form-control form-control-sm text-end price-input" value="0"
                                    min="0" required>
                            </div>
                            <div class="col-md-2 col-12 text-md-end text-start mb-2">
                                <small class="text-muted d-md-block d-inline me-1">Subtotal:</small>
                                <span class="fw-bold text-success small item-subtotal">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 flex-shrink-0 btn-remove-item"
                        title="Hapus">
                        <i class="fas fa-times-circle fs-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let rowIndex = 0;
            let currentStep = 1;

            const fmt = n => 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID');

            // Init store select2
            $('#store_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih Toko --'
            });

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

            // Run on initial load
            updateStep2Labels();
            populateHiddenFields();

            // ===== Select2 for product selects =====
            function initProductSelect(el) {
                if ($(el).hasClass('select2-hidden-accessible')) return;
                $(el).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '-- Pilih Produk --',
                    dropdownParent: $(el).closest('.item-card')
                });
            }

            // ===== Add Item Card =====
            function addItemCard() {
                const tpl = document.getElementById('item-tpl').innerHTML;
                const html = tpl.replace(/__IDX__/g, rowIndex).replace(/__NUM__/g, rowIndex + 1);
                const $card = $(html);
                $('#item-list').append($card);
                initProductSelect($card.find('.product-select')[0]);
                rowIndex++;
                refreshRemoveButtons();
                recalc();
            }

            function ensureFirstItem() {
                if ($('.item-card').length === 0) addItemCard();
            }

            $('#btn-add-item').on('click', addItemCard);

            // Remove item
            $(document).on('click', '.btn-remove-item', function() {
                $(this).closest('.item-card').remove();
                reIndexCards();
                refreshRemoveButtons();
                recalc();
            });

            function reIndexCards() {
                $('.item-card').each(function(i) {
                    $(this).attr('data-index', i);
                    $(this).find('.item-num-badge').text(i + 1);
                    $(this).find('[name]').each(function() {
                        $(this).attr('name', $(this).attr('name').replace(/items\[\d+\]/, 'items[' +
                            i + ']'));
                    });
                });
            }

            function refreshRemoveButtons() {
                const n = $('.item-card').length;
                $('.btn-remove-item').prop('disabled', n <= 1);
            }

            // Auto price fill
            $(document).on('change', '.product-select', function() {
                const price = $(this).find('option:selected').data('price') || 0;
                $(this).closest('.item-card').find('.price-input').val(price).trigger('input');
            });

            // Recalculate subtotals
            $(document).on('input', '.qty-input, .price-input', function() {
                const card = $(this).closest('.item-card');
                const sub = parseFloat(card.find('.qty-input').val() || 0) * parseFloat(card.find(
                    '.price-input').val() || 0);
                card.find('.item-subtotal').text(fmt(sub));
                recalc();
            });

            function recalc() {
                let total = 0,
                    count = 0;
                let summaryHtml = '';
                
                $('.item-card').each(function() {
                    const selectOption = $(this).find('.product-select option:selected');
                    const name = selectOption.val() ? selectOption.text().trim() : '';
                    const qty = parseFloat($(this).find('.qty-input').val() || 0);
                    const price = parseFloat($(this).find('.price-input').val() || 0);
                    const sub = qty * price;
                    total += sub;
                    if (selectOption.val()) {
                        count++;
                        // Add item row to summary list
                        summaryHtml += `<div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-truncate text-secondary me-2" style="max-width: 180px;">${name}</span>
                            <span class="fw-semibold text-dark text-nowrap">${qty} x ${fmt(price)}</span>
                        </div>`;
                    }
                });

                $('#order-grand-total').text(fmt(total));
                $('#item-count-label').text(count + ' item');

                // Update right sidebar live summary
                $('#summary-grand-total').text(fmt(total));
                if (summaryHtml) {
                    $('#summary-items-list').html(summaryHtml);
                } else {
                    $('#summary-items-list').html('<span class="text-muted small italic">Belum ada item dipilih</span>');
                }
            }

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

            // Trigger recalc on product select changes to show in sidebar summary
            $(document).on('change', '.product-select', function() {
                recalc();
            });

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
                if (target === 3) ensureFirstItem();
            };

            window.goToReview = function() {
                if ($('.item-card').length === 0) return showWarn('Tambahkan minimal 1 item produk.');
                let allOk = true;
                $('.item-card').each(function() {
                    if (!$(this).find('.product-select').val()) allOk = false;
                });
                if (!allOk) return showWarn('Semua item harus memilih produk.');

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
                $('.item-card').each(function() {
                    const name = $(this).find('.product-select option:selected').text().trim();
                    const qty = parseFloat($(this).find('.qty-input').val() || 0);
                    const price = parseFloat($(this).find('.price-input').val() || 0);
                    const sub = qty * price;
                    total += sub;
                    html += `<div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 mb-1 small bg-white">
                <span class="fw-semibold text-truncate me-2">${name}</span>
                <span class="text-muted text-nowrap">${qty} × ${fmt(price)} = <strong class="text-primary">${fmt(sub)}</strong></span>
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
        });
    </script>
@endpush
