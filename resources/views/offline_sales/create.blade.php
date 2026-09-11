@extends('layouts.app')

@section('title', 'Transaksi Baru — Penjualan Offline')
@section('page-title', 'Transaksi Baru')

@section('content')
<div class="container-fluid px-0">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-cash-register text-primary me-2"></i>Transaksi Penjualan Baru
            </h4>
            <p class="text-muted small mb-0">Pencatatan transaksi kasir / POS penjualan offline langsung</p>
        </div>
        <a href="{{ route('offline_sales.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <form id="offline-form" action="{{ route('offline_sales.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- ========================================================================= --}}
        {{-- BARIS 1: DATA PELANGGAN & RINGKASAN TOTAL                                 --}}
        {{-- ========================================================================= --}}
        <div class="row g-3 mb-3">
            {{-- DATA PELANGGAN (COL-8) --}}
            <div class="col-lg-8">
                <div class="card border shadow-sm h-100">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-user text-primary me-2"></i>Data Pelanggan
                        </span>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateCustomer">
                            <i class="fas fa-plus me-1"></i>Pelanggan Baru
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            {{-- Pilih Master Pelanggan --}}
                            <div class="col-md-6" id="customer-select-wrapper">
                                <label class="form-label small fw-semibold text-secondary mb-1">
                                    Pilih Master Pelanggan <span class="text-danger">*</span>
                                </label>
                                <select name="customer_id" id="customer-select" class="form-select form-select-sm select2" style="width: 100%;" required>
                                    <option value="">-- Pilih Pelanggan (Wajib Pilih) --</option>
                                    @foreach ($customers as $cust)
                                        <option value="{{ $cust->id }}" data-name="{{ $cust->name }}"
                                            data-phone="{{ $cust->phone }}" data-address="{{ $cust->address }}"
                                            data-category="{{ $cust->category }}" data-category-label="{{ $cust->category_label }}"
                                            data-tags="{{ $cust->tags }}" data-balance="{{ $cust->balance ?? 0 }}">
                                            [{{ $cust->category_label }}] {{ $cust->name }} {{ $cust->phone ? '(' . $cust->phone . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Nama Pembeli --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1" id="buyer-name-label">Nama Pembeli</label>
                                <input type="text" name="buyer_name" id="buyer-name-input" class="form-control form-control-sm bg-light" placeholder="Otomatis dari Master Data" readonly required>
                            </div>

                            {{-- No HP --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1" id="buyer-phone-label">No. HP / WhatsApp</label>
                                <input type="text" name="buyer_phone" id="buyer-phone-input" class="form-control form-control-sm bg-light" placeholder="Otomatis dari Master Data" readonly>
                            </div>

                            {{-- Alamat --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Alamat Pelanggan</label>
                                <textarea name="buyer_address" id="buyer-address-input" class="form-control form-control-sm bg-light" rows="1" placeholder="Otomatis dari Master Data" readonly></textarea>
                            </div>

                            {{-- Instansi / Saluran / Channel (Opsional) --}}
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1">
                                    Instansi / Saluran / Channel <span class="badge bg-secondary opacity-75">Opsional</span>
                                </label>
                                <input type="text" name="institution_name" id="institution-name-input" class="form-control form-control-sm" placeholder="Contoh: Dinas Pendidikan, Sekolah ABC, PT Sinar Harapan...">
                            </div>
                        </div>

                        <!-- Hidden fields for dropship -->
                        <input type="hidden" name="is_dropship" id="is-dropship-toggle" value="0">
                        <input type="hidden" name="dropshipper_name" id="dropshipper-name-input" value="">
                        <input type="hidden" name="dropshipper_phone" id="dropshipper-phone-input" value="">

                        {{-- Section Dropship & Upload Resi --}}
                        <div id="dropship-detail-section" class="alert alert-warning border mt-3 mb-0" style="display: none;">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-warning text-dark me-2 fw-bold"><i class="fas fa-shipping-fast me-1"></i>MODE DROPSHIP</span>
                                <span class="small fw-semibold text-dark">Informasi Resi &amp; Label Pengiriman</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Jenis Jasa Kirim / Ekspedisi</label>
                                    <input type="text" name="resi_number" id="resi-number-input" class="form-control form-control-sm" placeholder="Contoh: J&amp;T, JNE, SiCepat, Cargo...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Upload Label / Dokumen Resi</label>
                                    <input type="file" name="resi_file" id="resi-file-input" class="form-control form-control-sm" accept="image/*,.pdf">
                                    <div class="form-text small">Format: JPG, PNG, PDF (Maks. 5MB)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TIPE PESANAN & TOTAL (COL-4) --}}
            <div class="col-lg-4">
                <div class="card border shadow-sm h-100">
                    <div class="card-header bg-white py-2 px-3 border-bottom">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-receipt text-success me-2"></i>Total &amp; Tipe Pesanan
                        </span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        {{-- Switch PO Produksi --}}
                        <div class="card bg-light border p-3 mb-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_po" id="is-po-switch" value="1">
                                <label class="form-check-label fw-bold text-dark small" for="is-po-switch">
                                    <i class="fas fa-hammer text-primary me-1"></i> Pre-Order / PO Produksi (SPK)
                                </label>
                            </div>
                            <div id="po-deadline-container" class="mt-2 pt-2 border-top" style="display: none;">
                                <label class="form-label small fw-semibold text-dark mb-1">Deadline SPK Produksi</label>
                                <input type="date" name="deadline" id="po-deadline-input" class="form-control form-control-sm" value="{{ now()->addDays(7)->format('Y-m-d') }}">
                            </div>
                        </div>

                        {{-- Display Total Banner --}}
                        <div class="card bg-light border text-center p-3 my-auto">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Total Pembayaran</div>
                            <div class="fs-3 fw-bold text-success font-monospace" id="display-grand-total">Rp 0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- BARIS 2: PILIH PRODUK & KERANJANG & RINGKASAN PEMBAYARAN                  --}}
        {{-- ========================================================================= --}}
        <div class="row g-3">
            {{-- PILIH PRODUK & KERANJANG BELANJA (COL-8) --}}
            <div class="col-lg-8">
                <div class="card border shadow-sm pe-none opacity-50 mb-3" id="product-section-card">
                    <div class="card-header bg-white py-2 px-3 border-bottom">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-boxes text-primary me-2"></i>Pilih Produk &amp; Keranjang Belanja
                        </span>
                    </div>
                    <div class="card-body p-3">
                        {{-- Banner Pelanggan Belum Dipilih --}}
                        <div id="customer-warning-banner" class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3">
                            <i class="fas fa-exclamation-triangle text-warning fs-5"></i>
                            <div class="small">
                                <strong>Pelanggan Belum Dipilih!</strong>
                                Silakan pilih pelanggan terlebih dahulu pada form di atas (atau klik <strong>+ Pelanggan Baru</strong>) untuk membuka pilihan produk &amp; keranjang belanja.
                            </div>
                        </div>

                        {{-- Pencarian Produk --}}
                        <div class="mb-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="product-search" class="form-control form-control-sm" placeholder="Cari nama produk atau SKU..." disabled>
                            </div>
                        </div>

                        {{-- List Produk --}}
                        <div id="product-list" class="list-group overflow-auto mb-3" style="max-height: 240px;">
                            @foreach ($products as $product)
                                @php
                                    $resellerPrice = $product->reseller_price && $product->reseller_price > 0 ? $product->reseller_price : $product->price;
                                @endphp
                                <a href="javascript:void(0)" class="list-group-item list-group-item-action product-row d-flex justify-content-between align-items-center py-2 px-3 text-decoration-none"
                                    data-id="{{ $product->id }}"
                                    data-name="{{ $product->name }}" data-sku="{{ $product->sku }}"
                                    data-price="{{ $product->price }}" data-reseller-price="{{ $resellerPrice }}"
                                    data-stock="{{ $product->stock }}" data-is-po="{{ $product->is_preorder ? 1 : 0 }}">
                                    <div>
                                        <div class="fw-semibold text-dark small">{{ $product->name }}</div>
                                        <div class="text-muted small font-monospace">
                                            {{ $product->sku }} &bull; Stok: <span class="fw-bold">{{ $product->stock }}</span> {{ $product->unit ?? 'Pcs' }}
                                        </div>
                                    </div>
                                    <div class="text-end text-nowrap">
                                        <div class="price-normal-display fw-bold text-success font-monospace small">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </div>
                                        <div class="price-dropship-display fw-bold text-warning font-monospace small" style="display: none;">
                                            <span class="badge bg-warning text-dark me-1">Dropship</span>
                                            Rp {{ number_format($resellerPrice, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {{-- Keranjang Belanja --}}
                        <div class="fw-bold text-dark mb-2 mt-4">
                            <i class="fas fa-shopping-cart text-primary me-2"></i>Keranjang Belanja
                        </div>

                        <div id="cart-empty" class="text-center py-4 text-muted border border-dashed rounded bg-light">
                            <i class="fas fa-shopping-cart fa-2x mb-2 opacity-50"></i>
                            <div class="small">Belum ada produk yang dipilih</div>
                        </div>

                        <div class="table-responsive border rounded" id="cart-table" style="display: none;">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th class="ps-3">PRODUK</th>
                                        <th class="text-center" style="width: 70px;">PROMO</th>
                                        <th class="text-center" style="width: 100px;">QTY</th>
                                        <th class="text-end" style="width: 110px;">HARGA</th>
                                        <th class="text-center" style="width: 120px;">DISKON</th>
                                        <th class="text-end" style="width: 120px;">SUBTOTAL</th>
                                        <th class="text-center" style="width: 50px;">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body" class="small"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RINGKASAN PESANAN (COL-4) --}}
            <div class="col-lg-4">
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Ringkasan Pesanan
                        </span>
                    </div>
                    <div class="card-body p-3">
                        {{-- Subtotal --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Subtotal</span>
                            <span class="fw-bold text-dark font-monospace" id="display-subtotal">Rp 0</span>
                        </div>

                        {{-- Diskon Nota --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Diskon Nota / Transaksi</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-primary fw-bold" id="btn-global-disc-toggle" style="width: 48px;">
                                    Rp
                                </button>
                                <input type="text" id="discount-input" class="form-control form-control-sm font-monospace" value="0">
                                <input type="hidden" name="discount_type" id="global-discount-type" value="fixed">
                                <input type="hidden" name="discount_value" id="global-discount-value" value="0">
                                <input type="hidden" name="discount_amount" id="global-discount-amount" value="0">
                            </div>
                            <span id="reseller-info-badge" class="badge bg-success text-white mt-1 w-100 py-1" style="display: none;"></span>
                        </div>

                        <hr class="my-3">

                        {{-- Pilihan Jenis Pembayaran: Tunai / Kredit --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">
                                <i class="fas fa-wallet me-1"></i>Jenis Pembayaran <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group w-100" role="group" id="payment-type-group">
                                <input type="radio" class="btn-check" name="payment_type" id="pay_type_tunai" value="tunai" checked autocomplete="off">
                                <label class="btn btn-outline-success btn-sm fw-bold py-2" for="pay_type_tunai">
                                    <i class="fas fa-money-bill-wave me-1"></i> Tunai (Lunas)
                                </label>

                                <input type="radio" class="btn-check" name="payment_type" id="pay_type_kredit" value="kredit" autocomplete="off">
                                <label class="btn btn-outline-danger btn-sm fw-bold py-2" for="pay_type_kredit">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> Kredit (Tempo)
                                </label>
                            </div>
                        </div>

                        {{-- Total Tagihan --}}
                        <div class="card bg-light border p-3 mb-3 text-center">
                            <div class="text-muted small fw-semibold mb-1">Total Tagihan</div>
                            <div class="fs-4 fw-bold text-dark font-monospace" id="display-order-grand-total">Rp 0</div>
                            <div class="text-muted small mt-1" id="payment-hint-text">
                                <i class="fas fa-check-circle text-success me-1"></i> Pembayaran langsung lunas (Tunai).
                            </div>
                        </div>

                        <input type="hidden" name="payment_method" id="payment-method-input" value="tunai">
                        <input type="hidden" name="paid_amount" id="paid-input" value="0">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Catatan Pesanan</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Tulis catatan transaksi jika ada..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" id="btn-submit" disabled>
                            <i class="fas fa-save me-1"></i> Simpan Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL TAMBAH PELANGGAN BARU (MASTER DATA) -->
<div class="modal fade" id="modalCreateCustomer" tabindex="-1" aria-labelledby="modalCreateCustomerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold text-dark" id="modalCreateCustomerLabel">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Tambah Master Pelanggan Baru
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quick-customer">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama pelanggan..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">No. Handphone / WhatsApp</label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="08123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Kategori Pelanggan <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required>
                            <option value="umum">Pelanggan Umum</option>
                            <option value="biasa">Pelanggan Biasa</option>
                            <option value="dropship">Pelanggan Dropship</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tag Tambahan</label>
                        <input type="text" name="tags" class="form-control form-control-sm" placeholder="Contoh: VIP, Member">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Alamat Lengkap</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Alamat lengkap..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save-customer">Simpan &amp; Pilih Pelanggan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let cartItems = {};
        let grandTotal = 0;
        let isDropshipCustomer = false;

        function setDropshipMode(active) {
            isDropshipCustomer = active;
            $('#is-dropship-toggle').val(active ? '1' : '0');

            if (active) {
                $('.price-normal-display').hide();
                $('.price-dropship-display').show();
            } else {
                $('.price-dropship-display').hide();
                $('.price-normal-display').show();
            }

            Object.keys(cartItems).forEach(id => {
                cartItems[id].price = active ? cartItems[id].dropship_price : cartItems[id].normal_price;
            });

            renderCart();
        }

        function triggerCustomerSelectChange() {
            const selectedOption = $('#customer-select').find('option:selected');
            const customerId = $('#customer-select').val();

            if (customerId) {
                $('#customer-warning-banner').slideUp(150);
                $('#product-section-card').removeClass('pe-none opacity-50');
                $('#product-search').prop('disabled', false);

                const name = selectedOption.data('name');
                const phone = selectedOption.data('phone');
                const address = selectedOption.data('address');
                const category = String(selectedOption.data('category') || '');
                const tags = String(selectedOption.data('tags') || '');

                $('#buyer-name-input').val(name).prop('readonly', true);
                $('#buyer-phone-input').val(phone || '').prop('readonly', true);
                $('#buyer-address-input').val(address || '').prop('readonly', true);

                if (category === 'dropship' || tags.toLowerCase().includes('reseller') || tags.toLowerCase().includes('dropship')) {
                    setDropshipMode(true);
                    $('#dropshipper-name-input').val(name);
                    $('#dropshipper-phone-input').val(phone || '');
                    $('#dropship-detail-section').slideDown(150);
                    updateResellerDiscount();
                    $('#reseller-info-badge').html('<i class="fas fa-percent me-1"></i> Mode Dropship Aktif — Menggunakan Harga Dropship').show();
                } else {
                    setDropshipMode(false);
                    $('#dropshipper-name-input').val('');
                    $('#dropshipper-phone-input').val('');
                    $('#dropship-detail-section').slideUp(150);
                    $('#resi-number-input').val('');
                    $('#resi-file-input').val('');
                    $('#discount-input').val('0');
                    $('#reseller-info-badge').hide();
                }
            } else {
                $('#customer-warning-banner').slideDown(150);
                $('#product-section-card').addClass('pe-none opacity-50');
                $('#product-search').val('').prop('disabled', true);

                $('#buyer-name-input').val('').prop('readonly', true);
                $('#buyer-phone-input').val('').prop('readonly', true);
                $('#buyer-address-input').val('').prop('readonly', true);

                setDropshipMode(false);
                $('#dropshipper-name-input').val('');
                $('#dropshipper-phone-input').val('');
                $('#dropship-detail-section').slideUp(150);
                $('#resi-number-input').val('');
                $('#resi-file-input').val('');
                $('#discount-input').val('0');
                $('#reseller-info-badge').hide();

                cartItems = {};
                renderCart();
            }
            recalculate();
        }

        $('#customer-select').on('change', function() {
            triggerCustomerSelectChange();
        });

        $('#buyer-name-input, #buyer-phone-input').on('input', function() {
            recalculate();
        });

        function formatNumberInput(value) {
            let clean = String(value).replace(/\D/g, '');
            if (clean === '') return '';
            return parseInt(clean, 10).toLocaleString('id-ID');
        }

        function unformatNumber(value) {
            if (!value) return 0;
            let clean = String(value).replace(/\D/g, '');
            return parseInt(clean, 10) || 0;
        }

        // Pencarian produk
        $('#product-search').on('input', function() {
            const q = $(this).val().toLowerCase();
            let matchCount = 0;
            $('.product-row').each(function() {
                const name = ($(this).attr('data-name') || '').toLowerCase();
                const sku = ($(this).attr('data-sku') || '').toLowerCase();
                if (name.includes(q) || sku.includes(q)) {
                    if (matchCount < 6) {
                        $(this).removeClass('d-none');
                        matchCount++;
                    } else {
                        $(this).addClass('d-none');
                    }
                } else {
                    $(this).addClass('d-none');
                }
            });
        });

        // AJAX quick customer creation
        $('#form-quick-customer').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btn-save-customer');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: '{{ route('customers.store') }}',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    btn.prop('disabled', false).html('Simpan & Pilih Pelanggan');
                    if (res.success && res.customer) {
                        const c = res.customer;
                        const catLabels = { 'umum': 'Pelanggan Umum', 'biasa': 'Pelanggan Biasa', 'dropship': 'Pelanggan Dropship' };
                        const categoryLabel = catLabels[c.category] || 'Pelanggan Umum';
                        const labelText = `[${categoryLabel}] ${c.name} ${c.phone ? '(' + c.phone + ')' : ''}`;
                        const newOption = new Option(labelText, c.id, true, true);

                        $(newOption).attr('data-name', c.name);
                        $(newOption).attr('data-phone', c.phone || '');
                        $(newOption).attr('data-address', c.address || '');
                        $(newOption).attr('data-category', c.category || 'umum');
                        $(newOption).attr('data-category-label', categoryLabel);
                        $(newOption).attr('data-tags', c.tags || '');
                        $(newOption).attr('data-balance', c.balance || 0);

                        $('#customer-select').append(newOption).trigger('change');
                        $('#modalCreateCustomer').modal('hide');
                        $('#form-quick-customer')[0].reset();
                        Swal.fire({
                            icon: 'success',
                            title: 'Pelanggan Disimpan!',
                            text: 'Pelanggan baru berhasil ditambahkan ke Data Master!',
                            timer: 1800,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('Simpan & Pilih Pelanggan');
                    let errMsg = 'Gagal menyimpan pelanggan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: errMsg,
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });

        // Tambah produk ke keranjang
        $(document).on('click', '.product-row', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const sku = $(this).data('sku');
            const normalPrice = parseFloat($(this).data('price'));
            const resellerPrice = parseFloat($(this).data('reseller-price'));
            const stock = parseInt($(this).data('stock'));
            const isPoMode = $('#is-po-switch').is(':checked');
            const isPreorder = parseInt($(this).data('is-po')) === 1;

            const activePrice = isDropshipCustomer ? resellerPrice : normalPrice;

            if (!isPoMode && !isPreorder && stock <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stok Produk Habis!',
                    html: `Stok untuk <strong>${name}</strong> saat ini <strong>0 Pcs</strong>.<br><br>Aktifkan switch <strong>Pre-Order / PO Produksi (SPK)</strong> di atas jika ingin membuat pesanan PO untuk produk ini!`,
                    confirmButtonText: 'Saya Mengerti',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            if (cartItems[id]) {
                if (!isPoMode && !isPreorder && cartItems[id].qty >= stock) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok Tidak Mencukupi!',
                        html: `Jumlah pesanan melebihi stok yang tersedia.<br>Maksimal stok <strong>${name}</strong> saat ini: <strong>${stock} Pcs</strong>.`,
                        confirmButtonText: 'Tutup',
                        confirmButtonColor: '#fd7e14'
                    });
                    return;
                }
                cartItems[id].qty++;
            } else {
                cartItems[id] = {
                    id,
                    name,
                    sku,
                    price: activePrice,
                    normal_price: normalPrice,
                    dropship_price: resellerPrice,
                    stock,
                    is_po: isPreorder,
                    qty: 1,
                    discount_type: 'fixed',
                    discount_value: 0,
                    is_promo: false
                };
            }
            renderCart();
        });

        // Checkbox Promo
        $(document).on('change', '.promo-checkbox', function() {
            const id = $(this).data('id');
            if (cartItems[id]) {
                cartItems[id].is_promo = $(this).is(':checked');
                renderCart();
            }
        });

        // Kurangi qty
        $(document).on('click', '.btn-minus', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            changeQty(id, -1);
        });

        // Tambah qty
        $(document).on('click', '.btn-plus', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            changeQty(id, 1);
        });

        // Ubah qty input
        $(document).on('change', '.qty-input', function() {
            const id = $(this).data('id');
            setQty(id, $(this).val());
        });

        // Hapus item
        $(document).on('click', '.btn-remove', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            removeItem(id);
        });

        // Toggle tipe diskon item (Rp / %)
        $(document).on('click', '.btn-item-disc-toggle', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            if (cartItems[id]) {
                cartItems[id].discount_type = cartItems[id].discount_type === 'percentage' ? 'fixed' : 'percentage';
                renderCart();
            }
        });

        // Input diskon item
        $(document).on('input', '.item-disc-input', function(e) {
            const id = $(this).data('id');
            if (!cartItems[id]) return;

            let valStr = $(this).val();
            if (cartItems[id].discount_type === 'percentage') {
                let num = parseFloat(valStr) || 0;
                if (num > 100) num = 100;
                if (num < 0) num = 0;
                cartItems[id].discount_value = num;
            } else {
                let num = unformatNumber(valStr);
                cartItems[id].discount_value = num;
                $(this).val(num > 0 ? num.toLocaleString('id-ID') : '0');
            }
            recalculate();
        });

        // Toggle tipe diskon global (Rp / %)
        $('#btn-global-disc-toggle').on('click', function() {
            const curType = $('#global-discount-type').val();
            const newType = curType === 'percentage' ? 'fixed' : 'percentage';
            $('#global-discount-type').val(newType);
            $(this).text(newType === 'percentage' ? '%' : 'Rp');
            if (newType === 'percentage') {
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            } else {
                $(this).removeClass('btn-primary').addClass('btn-outline-primary');
            }
            $('#discount-input').val('0');
            recalculate();
        });

        // Input diskon transaksi
        $('#discount-input').on('input', function() {
            const discType = $('#global-discount-type').val();
            if (discType === 'percentage') {
                let num = parseFloat($(this).val()) || 0;
                if (num > 100) num = 100;
                if (num < 0) num = 0;
                $(this).val(num);
            } else {
                let formatted = formatNumberInput($(this).val());
                $(this).val(formatted);
            }
            recalculate();
        });

        $('#offline-form').on('submit', function(e) {
            const isPoMode = $('#is-po-switch').is(':checked');
            if (!isPoMode) {
                for (const item of Object.values(cartItems)) {
                    if (!item.is_po && item.qty > item.stock) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Tidak Mencukupi!',
                            html: `Jumlah pesanan untuk <strong>${item.name}</strong> (${item.qty} Pcs) melebihi stok yang tersedia (${item.stock} Pcs).<br><br>Aktifkan switch <strong>Pre-Order / PO Produksi (SPK)</strong> jika transaksi ini adalah pesanan PO!`,
                            confirmButtonColor: '#fd7e14'
                        });
                        return false;
                    }
                }
            }

            const discType = $('#global-discount-type').val();
            let discVal = 0;
            if (discType === 'percentage') {
                discVal = parseFloat($('#discount-input').val()) || 0;
            } else {
                discVal = unformatNumber($('#discount-input').val());
            }
            $('#global-discount-value').val(discVal);
        });

        triggerCustomerSelectChange();

        function changeQty(id, delta) {
            if (!cartItems[id]) return;
            const newQty = cartItems[id].qty + delta;
            if (newQty <= 0) {
                removeItem(id);
                return;
            }
            const isPoMode = $('#is-po-switch').is(':checked');
            const isPreorder = cartItems[id] && cartItems[id].is_po;
            if (!isPoMode && !isPreorder && newQty > cartItems[id].stock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stok Tidak Mencukupi!',
                    html: `Jumlah pesanan melebihi stok yang tersedia.<br>Maksimal stok <strong>${cartItems[id].name}</strong> saat ini: <strong>${cartItems[id].stock} Pcs</strong>.`,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#fd7e14'
                });
                return;
            }
            cartItems[id].qty = newQty;
            renderCart();
        }

        function setQty(id, val) {
            let qty = parseInt(val);
            if (!qty || qty < 1) {
                removeItem(id);
                return;
            }
            const isPoMode = $('#is-po-switch').is(':checked');
            const isPreorder = cartItems[id] && cartItems[id].is_po;
            if (!isPoMode && !isPreorder && qty > cartItems[id].stock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stok Tidak Mencukupi!',
                    html: `Jumlah pesanan melebihi stok yang tersedia.<br>Maksimal stok <strong>${cartItems[id].name}</strong> saat ini: <strong>${cartItems[id].stock} Pcs</strong>.`,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#fd7e14'
                });
                cartItems[id].qty = cartItems[id].stock;
            } else {
                cartItems[id].qty = qty;
            }
            renderCart();
        }

        function removeItem(id) {
            delete cartItems[id];
            renderCart();
        }

        function recalculate() {
            let subtotal = 0;
            Object.values(cartItems).forEach(i => {
                const price = i.is_promo ? 0 : i.price;
                let discPerUnit = 0;
                if (!i.is_promo && i.discount_value > 0) {
                    if (i.discount_type === 'percentage') {
                        discPerUnit = (price * Math.min(100, i.discount_value)) / 100;
                    } else {
                        discPerUnit = Math.min(price, i.discount_value);
                    }
                }
                const effectivePrice = Math.max(0, price - discPerUnit);
                subtotal += i.qty * effectivePrice;
            });

            const discType = $('#global-discount-type').val();
            let discountAmount = 0;
            let discInputVal = 0;
            if (discType === 'percentage') {
                discInputVal = parseFloat($('#discount-input').val()) || 0;
                discountAmount = (subtotal * Math.min(100, discInputVal)) / 100;
            } else {
                discInputVal = unformatNumber($('#discount-input').val());
                discountAmount = Math.min(subtotal, discInputVal);
            }

            $('#global-discount-value').val(discInputVal);
            $('#global-discount-amount').val(discountAmount);

            grandTotal = Math.max(0, subtotal - discountAmount);

            $('#display-subtotal').text('Rp ' + Math.round(subtotal).toLocaleString('id-ID'));
            $('#display-grand-total').text('Rp ' + Math.round(grandTotal).toLocaleString('id-ID'));
            $('#display-order-grand-total').text('Rp ' + Math.round(grandTotal).toLocaleString('id-ID'));

            const payType = $('input[name="payment_type"]:checked').val() || 'tunai';
            if (payType === 'tunai') {
                $('#payment-method-input').val('tunai');
                $('#paid-input').val(grandTotal);
                $('#payment-hint-text').html('<i class="fas fa-check-circle text-success me-1"></i> Pembayaran langsung lunas (Tunai).');
            } else {
                $('#payment-method-input').val('piutang');
                $('#paid-input').val(0);
                $('#payment-hint-text').html('<i class="fas fa-info-circle text-primary me-1"></i> Pembayaran tempo / dapat dicicil setelah transaksi tersimpan.');
            }

            let isValid = Object.keys(cartItems).length > 0;
            const isPo = $('#is-po-switch').is(':checked');

            if (!isPo) {
                const custVal = $('#customer-select').val();
                const nameVal = $.trim($('#buyer-name-input').val());
                const phoneVal = $.trim($('#buyer-phone-input').val());

                if (!custVal && (!nameVal || !phoneVal)) {
                    isValid = false;
                }
            }

            $('#btn-submit').prop('disabled', !isValid);
        }

        function renderCart() {
            const tbody = $('#cart-body');
            const empty = $('#cart-empty');
            const table = $('#cart-table');

            if (Object.keys(cartItems).length === 0) {
                empty.show();
                table.hide();
                recalculate();
                return;
            }

            empty.hide();
            table.show();

            tbody.empty();
            let idx = 0;
            Object.values(cartItems).forEach(item => {
                const price = item.is_promo ? 0 : item.price;
                let discPerUnit = 0;
                if (!item.is_promo && item.discount_value > 0) {
                    if (item.discount_type === 'percentage') {
                        discPerUnit = (price * Math.min(100, item.discount_value)) / 100;
                    } else {
                        discPerUnit = Math.min(price, item.discount_value);
                    }
                }
                const effectivePrice = Math.max(0, price - discPerUnit);
                const subtotal = item.qty * effectivePrice;
                const tr = $('<tr></tr>');

                const priceDisplay = item.is_promo ?
                    `<div class="text-end small font-monospace"><span class="badge bg-danger text-white">PROMO (Rp 0)</span></div>
                     <div class="text-end text-muted text-decoration-line-through small">Rp ${item.price.toLocaleString('id-ID')}</div>` :
                    `<div class="text-end small text-nowrap text-dark font-monospace">Rp ${item.price.toLocaleString('id-ID')}</div>`;

                const itemDiscValDisplay = item.discount_type === 'percentage' ? item.discount_value : (item.discount_value > 0 ? item.discount_value.toLocaleString('id-ID') : '0');

                tr.html(`
                    <td class="ps-3">
                        <div class="fw-semibold text-dark small">${item.name}</div>
                        <div class="text-muted small">${item.sku || ''}</div>
                        <input type="hidden" name="items[${idx}][master_product_id]" value="${item.id}">
                        <input type="hidden" name="items[${idx}][unit_price]" value="${item.price}">
                        <input type="hidden" name="items[${idx}][quantity]" id="qty-hidden-${item.id}" value="${item.qty}">
                        <input type="hidden" name="items[${idx}][discount_type]" value="${item.discount_type}">
                        <input type="hidden" name="items[${idx}][discount_value]" value="${item.discount_value}">
                    </td>
                    <td class="text-center align-middle">
                        <div class="form-check form-switch d-flex justify-content-center m-0">
                            <input class="form-check-input promo-checkbox" type="checkbox" data-id="${item.id}" ${item.is_promo ? 'checked' : ''} title="Jadikan Gratis (Buy 1 Get 1 / Promo)">
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <div class="input-group input-group-sm mx-auto" style="width: 100px;">
                            <button type="button" class="btn btn-outline-secondary btn-minus" data-id="${item.id}">-</button>
                            <input type="number" class="form-control text-center p-1 qty-input" data-id="${item.id}" value="${item.qty}" min="1">
                            <button type="button" class="btn btn-outline-secondary btn-plus" data-id="${item.id}">+</button>
                        </div>
                    </td>
                    <td class="text-end text-nowrap align-middle">${priceDisplay}</td>
                    <td class="text-center align-middle">
                        <div class="input-group input-group-sm mx-auto" style="width: 110px;">
                            <button type="button" class="btn btn-outline-secondary btn-item-disc-toggle fw-bold" data-id="${item.id}">
                                ${item.discount_type === 'percentage' ? '%' : 'Rp'}
                            </button>
                            <input type="text" class="form-control text-end item-disc-input font-monospace p-1" data-id="${item.id}" value="${itemDiscValDisplay}">
                        </div>
                    </td>
                    <td class="text-end fw-bold text-nowrap align-middle ${item.is_promo ? 'text-danger' : 'text-success'} font-monospace">Rp ${Math.round(subtotal).toLocaleString('id-ID')}</td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove" data-id="${item.id}" title="Hapus Item">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `);
                tbody.append(tr);
                idx++;
            });

            updateResellerDiscount();
            recalculate();
        }

        function updateResellerDiscount() {
            const selectedOption = $('#customer-select').find('option:selected');
            const tags = String(selectedOption.data('tags') || '');
            const isReseller = tags.toLowerCase().includes('reseller') || tags.toLowerCase().includes('dropship');

            if (isReseller) {
                const subtotal = Object.values(cartItems).reduce((s, i) => s + i.qty * (i.is_promo ? 0 : i.price), 0);
                const discount = Math.round(subtotal * 0.1);
                $('#discount-input').val(discount.toLocaleString('id-ID'));
            }
        }

        $('.select2').select2({
            width: '100%'
        });

        $('#is-po-switch').on('change', function() {
            if ($(this).is(':checked')) {
                $('#po-deadline-container').slideDown(200);
            } else {
                $('#po-deadline-container').slideUp(200);
            }
            recalculate();
        });

        $('input[name="payment_type"]').on('change', function() {
            recalculate();
        });

        triggerCustomerSelectChange();
        recalculate();
        $('#product-search').trigger('input');
    });
</script>
@endpush
