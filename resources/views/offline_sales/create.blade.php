@extends('layouts.app')

@section('title', 'Transaksi Baru — Penjualan Offline')
@section('page-title', 'Transaksi Baru')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- HEADER --}}
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded border border-success border-opacity-10 d-flex align-items-center justify-content-center"
                        style="width:48px;height:48px;font-size:1.25rem;">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Transaksi Penjualan Baru</h4>
                        <p class="text-muted mb-0 small">Mulai pencatatan transaksi kasir / POS langsung</p>
                    </div>
                </div>
                <a href="{{ route('offline_sales.index') }}" class="btn btn-secondary btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <form id="offline-form" action="{{ route('offline_sales.store') }}" method="POST">
                @csrf
                <div class="row g-4">

                    {{-- LEFT: Daftar Item --}}
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-box me-2 text-primary"></i>Pilih Produk
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                {{-- Pencarian produk --}}
                                <div class="mb-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border"><i class="fas fa-search"></i></span>
                                        <input type="text" id="product-search"
                                            class="form-control form-control-sm border"
                                            placeholder="Cari nama produk atau SKU...">
                                    </div>
                                </div>

                                {{-- Product list (scrollable) --}}
                                <div id="product-list" class="border rounded mb-3"
                                    style="max-height:300px;overflow-y:auto;">
                                    @foreach ($products as $product)
                                        @php
                                            $resellerPrice =
                                                $product->reseller_price && $product->reseller_price > 0
                                                    ? $product->reseller_price
                                                    : $product->price;
                                        @endphp
                                        <div class="product-row d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                                            style="cursor:pointer;transition:.15s;" data-id="{{ $product->id }}"
                                            data-name="{{ $product->name }}" data-sku="{{ $product->sku }}"
                                            data-price="{{ $product->price }}" data-reseller-price="{{ $resellerPrice }}"
                                            data-stock="{{ $product->stock }}">
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                                <div class="text-muted small font-monospace">{{ $product->sku }} &bull;
                                                    <span class="text-secondary fw-semibold">Stok: {{ $product->stock }}
                                                        {{ $product->unit }}</span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="price-normal-display fw-bold text-success text-nowrap font-monospace">
                                                    Rp
                                                    {{ number_format($product->price, 0, ',', '.') }}</div>
                                                <div class="price-dropship-display fw-bold text-warning text-nowrap font-monospace"
                                                    style="display:none;">
                                                    <span class="badge bg-warning text-dark me-1"
                                                        style="font-size:0.65rem;">DROPSHIP</span>
                                                    Rp {{ number_format($resellerPrice, 0, ',', '.') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Cart items --}}
                                <h6 class="fw-bold text-dark mb-2 mt-4"><i
                                        class="fas fa-shopping-cart me-2 text-primary"></i>Keranjang Belanja</h6>
                                <div id="cart-empty"
                                    class="text-center py-5 text-muted rounded border border-dashed bg-light">
                                    <i class="fas fa-shopping-cart fa-2x mb-1 d-block opacity-25"></i>
                                    Belum ada produk yang dipilih
                                </div>

                                <div class="table-responsive rounded border mt-3" id="cart-table" style="display:none;">
                                    <table class="table table-sm table-bordered table-striped align-middle mb-0 text-dark">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">PRODUK</th>
                                                <th style="width:75px" class="text-center">PROMO</th>
                                                <th style="width:110px" class="text-center">QTY</th>
                                                <th style="width:130px" class="text-end">HARGA SATUAN</th>
                                                <th style="width:130px" class="text-end">SUBTOTAL</th>
                                                <th style="width:50px" class="text-center">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Detail Transaksi --}}
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-receipt me-2 text-success"></i>Detail
                                    Pembayaran</h6>
                            </div>
                            <div class="card-body p-3">
                                {{-- 1. Pelanggan / Pembeli (Master Data) --}}
                                <div class="mb-3" id="customer-select-wrapper">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label form-label-sm text-muted fw-semibold mb-0">Pelanggan /
                                            Pembeli (Master Data)</label>
                                        <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none fw-bold small text-primary"
                                            data-bs-toggle="modal" data-bs-target="#modalCreateCustomer">
                                            <i class="fas fa-plus-circle me-1"></i>+ Pelanggan Baru
                                        </button>
                                    </div>
                                    <select name="customer_id" id="customer-select"
                                        class="form-select form-select-sm select2" style="width: 100%;">
                                        <option value="">-- Pelanggan Umum --</option>
                                        @foreach ($customers as $cust)
                                            <option value="{{ $cust->id }}" data-name="{{ $cust->name }}"
                                                data-phone="{{ $cust->phone }}" data-address="{{ $cust->address }}"
                                                data-tags="{{ $cust->tags }}" data-balance="{{ $cust->balance ?? 0 }}">
                                                {{ $cust->name }} {{ $cust->phone ? '(' . $cust->phone . ')' : '' }}
                                                {{ $cust->tags ? '[' . $cust->tags . ']' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted" id="buyer-name-label">Nama
                                        Pembeli</label>
                                    <input type="text" name="buyer_name" id="buyer-name-input"
                                        class="form-control form-control-sm" placeholder="Pelanggan Umum">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted" id="buyer-phone-label">No. HP
                                        Pembeli</label>
                                    <input type="text" name="buyer_phone" id="buyer-phone-input"
                                        class="form-control form-control-sm" placeholder="0812...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted">Alamat Pelanggan</label>
                                    <textarea name="buyer_address" id="buyer-address-input" class="form-control form-control-sm" rows="2"
                                        placeholder="Alamat lengkap pelanggan..."></textarea>
                                </div>

                                <!-- Hidden fields for dropship (auto-filled if customer is dropship type) -->
                                <input type="hidden" name="is_dropship" id="is-dropship-toggle" value="0">
                                <input type="hidden" name="dropshipper_name" id="dropshipper-name-input"
                                    value="">
                                <input type="hidden" name="dropshipper_phone" id="dropshipper-phone-input"
                                    value="">

                                <hr class="my-3">

                                {{-- 2. Subtotal & diskon --}}
                                <div class="d-flex justify-content-between mb-2 align-items-center text-dark">
                                    <span class="text-muted small">Subtotal</span>
                                    <span class="fw-semibold font-monospace" id="display-subtotal">Rp 0</span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted">Diskon (Rp)</label>
                                    <input type="text" name="discount_amount" id="discount-input"
                                        class="form-control form-control-sm" value="0">
                                    <span id="reseller-info-badge" class="badge bg-success text-white mt-1 w-100 py-1"
                                        style="display: none; font-size: 0.7rem; white-space: normal;"></span>
                                </div>
                                <div
                                    class="d-flex justify-content-between mb-3 p-3 bg-success bg-opacity-10 border border-success border-opacity-10 rounded">
                                    <span class="fw-bold text-dark small align-self-center">GRAND TOTAL</span>
                                    <span class="fw-extrabold text-success fs-5 font-monospace"
                                        id="display-grand-total">Rp
                                        0</span>
                                </div>

                                <hr class="my-3">

                                {{-- 3. Pembayaran --}}
                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted fw-semibold">Metode Pembayaran <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment-method-select"
                                        class="form-select form-select-sm select2 fw-semibold text-dark"
                                        style="width: 100%;" required>
                                        @foreach (\App\Models\OfflineSale::PAYMENT_METHODS as $key => $label)
                                            <option value="{{ $key }}" {{ $key === 'tunai' ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3" id="paid-amount-section">
                                    <label class="form-label form-label-sm text-muted">Uang Diterima (Rp)</label>
                                    <input type="text" name="paid_amount" id="paid-input"
                                        class="form-control form-control-sm fw-bold font-monospace text-dark"
                                        value="0" required>
                                </div>
                                <div class="mb-3 p-3 text-center rounded bg-primary bg-opacity-10 border border-primary border-opacity-10"
                                    id="change-section">
                                    <div class="text-muted small">Kembalian</div>
                                    <div class="fw-extrabold fs-4 text-primary font-monospace" id="display-change">Rp 0
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted">Catatan</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2"
                                        placeholder="Tulis catatan transaksi jika ada..."></textarea>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-sm py-2 fw-semibold"
                                        id="btn-submit" disabled>
                                        <i class="fas fa-check-circle me-2"></i>Selesaikan Transaksi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH PELANGGAN BARU (MASTER DATA) -->
    <div class="modal fade" id="modalCreateCustomer" tabindex="-1" aria-labelledby="modalCreateCustomerLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2 bg-light">
                    <h6 class="modal-title fw-bold text-dark" id="modalCreateCustomerLabel"><i
                            class="fas fa-user-plus me-2 text-primary"></i>Tambah Master Pelanggan Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-quick-customer">
                    @csrf
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label form-label-sm text-muted fw-semibold">Nama Pelanggan <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm text-muted fw-semibold">No. Handphone / WhatsApp</label>
                            <input type="text" name="phone" class="form-control form-control-sm"
                                placeholder="Contoh: 08123456789">
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm text-muted fw-semibold">Kategori / Tag</label>
                            <select name="tags" class="form-select form-select-sm">
                                <option value="Umum">Umum / Retail</option>
                                <option value="Reseller">Reseller</option>
                                <option value="Dropship">Dropship</option>
                                <option value="VIP">VIP</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm text-muted fw-semibold">Alamat Lengkap</label>
                            <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Alamat pelanggan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btn-save-customer">Simpan & Pilih
                            Pelanggan</button>
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
            let isDropshipCustomer = false; // Otomatis true jika pelanggan bertag dropship/reseller

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

                // Update harga semua item di keranjang
                Object.keys(cartItems).forEach(id => {
                    cartItems[id].price = active ? cartItems[id].dropship_price : cartItems[id]
                        .normal_price;
                });

                renderCart();
            }

            function triggerCustomerSelectChange() {
                const selectedOption = $('#customer-select').find('option:selected');
                const customerId = $('#customer-select').val();

                if (customerId) {
                    const name = selectedOption.data('name');
                    const phone = selectedOption.data('phone');
                    const address = selectedOption.data('address');
                    const tags = String(selectedOption.data('tags') || '');

                    // Check if Reseller / Dropshipper berdasarkan tag
                    if (tags.toLowerCase().includes('reseller') || tags.toLowerCase().includes('dropship')) {
                        // Aktifkan mode dropship otomatis
                        setDropshipMode(true);
                        $('#dropshipper-name-input').val(name);
                        $('#dropshipper-phone-input').val(phone || '');

                        // Buyer fields kosong untuk diisi (pelanggan akhir dari dropshipper)
                        $('#buyer-name-input').val('').prop('readonly', false);
                        $('#buyer-phone-input').val('').prop('readonly', false);
                        $('#buyer-address-input').val('').prop('readonly', false);

                        $('#buyer-name-label').html('Nama Pembeli <span class="text-danger">*</span>');
                        $('#buyer-phone-label').html('No. HP Pembeli <span class="text-danger">*</span>');
                        $('#buyer-name-input').prop('required', true);
                        $('#buyer-phone-input').prop('required', true);

                        // Terapkan diskon reseller
                        updateResellerDiscount();
                        $('#reseller-info-badge').html(
                                '<i class="fas fa-percent me-1"></i> Diskon Reseller 10% diterapkan otomatis')
                            .show();
                    } else {
                        // Pelanggan biasa — harga normal
                        setDropshipMode(false);
                        $('#dropshipper-name-input').val('');
                        $('#dropshipper-phone-input').val('');

                        $('#buyer-name-input').val(name).prop('readonly', true);
                        $('#buyer-phone-input').val(phone || '').prop('readonly', true);
                        $('#buyer-address-input').val(address || '').prop('readonly', true);

                        $('#buyer-name-label').html('Nama Pembeli');
                        $('#buyer-phone-label').html('No. HP Pembeli');
                        $('#buyer-name-input').prop('required', false);
                        $('#buyer-phone-input').prop('required', false);

                        $('#discount-input').val('0');
                        $('#reseller-info-badge').hide();
                    }
                } else {
                    // Pelanggan Umum — reset ke harga normal
                    setDropshipMode(false);
                    $('#dropshipper-name-input').val('');
                    $('#dropshipper-phone-input').val('');

                    $('#buyer-name-input').val('').prop('readonly', false);
                    $('#buyer-phone-input').val('').prop('readonly', false);
                    $('#buyer-address-input').val('').prop('readonly', false);

                    $('#buyer-name-label').html('Nama Pembeli');
                    $('#buyer-phone-label').html('No. HP Pembeli');
                    $('#buyer-name-input').prop('required', false);
                    $('#buyer-phone-input').prop('required', false);

                    $('#discount-input').val('0');
                    $('#reseller-info-badge').hide();
                }
                recalculate();
            }

            $('#customer-select').on('change', function() {
                triggerCustomerSelectChange();
            });

            $('#buyer-name-input, #buyer-phone-input').on('input', function() {
                recalculate();
            });

            // Format number helpers
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

            // Pencarian produk (di-limit 5 item yang tampil)
            $('#product-search').on('input', function() {
                const q = $(this).val().toLowerCase();
                let matchCount = 0;
                $('.product-row').each(function() {
                    const name = ($(this).attr('data-name') || '').toLowerCase();
                    const sku = ($(this).attr('data-sku') || '').toLowerCase();
                    if (name.includes(q) || sku.includes(q)) {
                        if (matchCount < 5) {
                            $(this).addClass('d-flex').removeClass('d-none');
                            matchCount++;
                        } else {
                            $(this).addClass('d-none').removeClass('d-flex');
                        }
                    } else {
                        $(this).addClass('d-none').removeClass('d-flex');
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
                            const labelText =
                                `${c.name} ${c.phone ? '(' + c.phone + ')' : ''} ${c.tags ? '[' + c.tags + ']' : ''}`;
                            const newOption = new Option(labelText, c.id, true, true);

                            $(newOption).attr('data-name', c.name);
                            $(newOption).attr('data-phone', c.phone || '');
                            $(newOption).attr('data-address', c.address || '');
                            $(newOption).attr('data-tags', c.tags || '');
                            $(newOption).attr('data-balance', c.balance || 0);

                            $('#customer-select').append(newOption).trigger('change');
                            $('#modalCreateCustomer').modal('hide');
                            $('#form-quick-customer')[0].reset();
                            alert('Pelanggan berhasil ditambahkan ke Data Master!');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('Simpan & Pilih Pelanggan');
                        let errMsg = 'Gagal menyimpan pelanggan.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        alert(errMsg);
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

                const activePrice = isDropshipCustomer ? resellerPrice : normalPrice;

                if (cartItems[id]) {
                    if (cartItems[id].qty >= stock) {
                        alert('Stok tidak mencukupi! Maks: ' + stock);
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
                        qty: 1,
                        is_promo: false
                    };
                }
                renderCart();
            });

            // Checkbox Promo (Beli 1 Gratis 1 / Rp 0)
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

            // Ubah qty langsung di input
            $(document).on('change', '.qty-input', function() {
                const id = $(this).data('id');
                setQty(id, $(this).val());
            });

            // Hapus item dari keranjang
            $(document).on('click', '.btn-remove', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                removeItem(id);
            });

            // Input diskon dengan format pemisah ribuan
            $('#discount-input').on('input', function() {
                let formatted = formatNumberInput($(this).val());
                $(this).val(formatted);
                recalculate();
            });

            // Input Uang Diterima dengan format pemisah ribuan
            $('#paid-input').on('input', function() {
                let formatted = formatNumberInput($(this).val());
                let paid = unformatNumber(formatted);
                // Jika melebihi total, reset ke total
                if (grandTotal > 0 && paid > grandTotal) {
                    paid = grandTotal;
                    formatted = grandTotal.toLocaleString('id-ID');
                }
                $(this).val(formatted);
                recalculate();
            });

            // Pilih metode pembayaran via Select Option
            $('#payment-method-select').on('change', function() {
                const key = $(this).val();

                if (key === 'piutang') {
                    $('#paid-input').val('0').prop('readonly', false);
                    $('#change-section').css('opacity', '1');
                } else if (key !== 'tunai') {
                    $('#paid-input').val(grandTotal.toLocaleString('id-ID')).prop('readonly', true);
                    $('#change-section').css('opacity', '.4');
                } else {
                    $('#paid-input').val('0').prop('readonly', false);
                    $('#change-section').css('opacity', '1');
                }
                recalculate();
            });

            // Clean number formatting before submitting form so Laravel validation passes
            $('#offline-form').on('submit', function() {
                $('#discount-input').val(unformatNumber($('#discount-input').val()));
                $('#paid-input').val(unformatNumber($('#paid-input').val()));
            });

            function changeQty(id, delta) {
                if (!cartItems[id]) return;
                const newQty = cartItems[id].qty + delta;
                if (newQty <= 0) {
                    removeItem(id);
                    return;
                }
                if (newQty > cartItems[id].stock) {
                    alert('Stok tidak mencukupi! Maks: ' + cartItems[id].stock);
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
                if (qty > cartItems[id].stock) {
                    alert('Stok tidak mencukupi!');
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
                const subtotal = Object.values(cartItems).reduce((s, i) => s + i.qty * (i.is_promo ? 0 : i.price),
                    0);
                const discount = unformatNumber($('#discount-input').val());
                grandTotal = Math.max(0, subtotal - discount);

                const method = $('#payment-method-select').val();

                if (method && method !== 'tunai' && method !== 'piutang') {
                    $('#paid-input').val(grandTotal.toLocaleString('id-ID'));
                }

                const paid = unformatNumber($('#paid-input').val());
                const change = Math.max(0, paid - grandTotal);

                $('#display-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
                $('#display-grand-total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
                $('#display-change').text('Rp ' + change.toLocaleString('id-ID'));

                // Validasi submit button
                let isValid = Object.keys(cartItems).length > 0;
                if (method === 'tunai' && paid < grandTotal) {
                    isValid = false;
                }

                // Custom validation for piutang
                if (method === 'piutang') {
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
                    const effectivePrice = item.is_promo ? 0 : item.price;
                    const subtotal = item.qty * effectivePrice;
                    const tr = $('<tr></tr>');

                    const priceDisplay = item.is_promo ?
                        `<div class="text-end small font-monospace"><span class="badge bg-danger text-white">PROMO (Rp 0)</span></div>
                           <div class="text-end text-muted text-decoration-line-through" style="font-size:.7rem;">Rp ${item.price.toLocaleString('id-ID')}</div>` :
                        `<div class="text-end small text-nowrap text-muted font-monospace">Rp ${item.price.toLocaleString('id-ID')}</div>`;

                    tr.html(`
                        <td class="ps-3">
                            <div class="fw-semibold text-dark small">${item.name}</div>
                            <div class="text-muted" style="font-size:.72rem;">${item.sku || ''}</div>
                            <input type="hidden" name="items[${idx}][master_product_id]" value="${item.id}">
                            <input type="hidden" name="items[${idx}][unit_price]" value="${effectivePrice}">
                            <input type="hidden" name="items[${idx}][quantity]" id="qty-hidden-${item.id}" value="${item.qty}">
                        </td>
                        <td class="text-center align-middle">
                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                <input class="form-check-input promo-checkbox" type="checkbox" data-id="${item.id}" ${item.is_promo ? 'checked' : ''} style="cursor:pointer;" title="Jadikan Gratis (Buy 1 Get 1 / Promo)">
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm" style="max-width:95px;margin:auto;">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-minus" data-id="${item.id}">-</button>
                                <input type="number" class="form-control form-control-sm text-center p-1 qty-input" data-id="${item.id}" value="${item.qty}" min="1" max="${item.stock}" style="width:40px;">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-plus" data-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td class="text-end text-nowrap">${priceDisplay}</td>
                        <td class="text-end fw-bold text-nowrap ${item.is_promo ? 'text-danger' : 'text-success'} font-monospace">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-id="${item.id}" style="padding: 2px 8px; font-size: 0.75rem;">
                                <i class="fas fa-trash"></i>
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
                const isReseller = tags.toLowerCase().includes('reseller') || tags.toLowerCase().includes(
                    'dropship');

                if (isReseller) {
                    const subtotal = Object.values(cartItems).reduce((s, i) => s + i.qty * (i.is_promo ? 0 : i
                        .price), 0);
                    const discount = Math.round(subtotal * 0.1);
                    $('#discount-input').val(discount.toLocaleString('id-ID'));
                }
            }

            // Initialize Select2 with search
            $('.select2').select2({
                width: '100%'
            });

            // Initialize on load
            triggerCustomerSelectChange();
            recalculate();

            // Limit product list to 5 initially
            $('#product-search').trigger('input');
        });
    </script>
@endpush

@push('styles')
    <style>
        .product-row {
            font-size: 0.78rem;
        }

        .product-row:hover {
            background: rgba(0, 0, 0, .03);
        }

        .btn-selected {
            background: rgba(13, 110, 253, 0.1) !important;
            color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        #product-list::-webkit-scrollbar {
            width: 6px;
        }

        #product-list::-webkit-scrollbar-track {
            background: transparent;
        }

        #product-list::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.08);
            border-radius: 10px;
        }

        #product-list::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.16);
        }
    </style>
@endpush
