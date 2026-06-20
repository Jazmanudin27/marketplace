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
                        <h4 class="mb-0 text-white fw-bold">Transaksi Penjualan Baru</h4>
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
                        <div class="dashboard-card mb-1">
                            <div class="card-header-line">
                                <h5 class="mb-0 text-white"><i class="fas fa-box me-2 text-primary"></i>Pilih Produk</h5>
                            </div>

                            {{-- Pencarian produk --}}
                            <div class="mb-1">
                                <div class="input-group input-group-sm">
                                    <span
                                        class="input-group-text bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"><i
                                            class="fas fa-search"></i></span>
                                    <input type="text" id="product-search"
                                        class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                        placeholder="Cari nama produk atau SKU...">
                                </div>
                            </div>

                            {{-- Product list (scrollable) --}}
                            <div id="product-list"
                                style="max-height:300px;overflow-y:auto;border:1px solid rgba(255,255,255,0.08);border-radius:8px;margin-bottom:1rem;">
                                @foreach ($products as $product)
                                    <div class="product-row d-flex align-items-center justify-content-between px-3 py-2"
                                        style="border-bottom:1px solid rgba(255,255,255,0.08);cursor:pointer;transition:.15s;"
                                        data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                        data-sku="{{ $product->sku }}" data-price="{{ $product->price }}"
                                        data-stock="{{ $product->stock }}">
                                        <div>
                                            <div class="fw-semibold text-white">{{ $product->name }}</div>
                                            <div class="text-muted small font-monospace">{{ $product->sku }} &bull; <span
                                                    class="text-warning-emphasis">Stok: {{ $product->stock }}
                                                    {{ $product->unit }}</span></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success text-nowrap font-monospace">Rp
                                                {{ number_format($product->price, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Cart items --}}
                            <h6 class="fw-semibold text-white mb-1 mt-4"><i
                                    class="fas fa-shopping-cart me-2 text-primary"></i>Keranjang Belanja</h6>
                            <div id="cart-empty"
                                class="text-center py-5 text-muted rounded border border-dashed border-secondary border-opacity-25 bg-dark bg-opacity-10">
                                <i class="fas fa-shopping-cart fa-2x mb-1 d-block opacity-25"></i>
                                Belum ada produk yang dipilih
                            </div>

                            <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3"
                                id="cart-table" style="display:none;">
                                <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">PRODUK</th>
                                            <th style="width:140px" class="text-center">QTY</th>
                                            <th style="width:140px" class="text-end">HARGA SATUAN</th>
                                            <th style="width:140px" class="text-end">SUBTOTAL</th>
                                            <th style="width:50px" class="text-center">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Detail Transaksi --}}
                    <div class="col-lg-4">
                        <div class="dashboard-card mb-1">
                            <div class="card-header-line">
                                <h5 class="mb-0 text-white"><i class="fas fa-receipt me-2 text-success"></i>Detail
                                    Pembayaran</h5>
                            </div>

                            {{-- Subtotal & diskon --}}
                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span class="text-muted small">Subtotal</span>
                                <span class="fw-semibold text-white font-monospace" id="display-subtotal">Rp 0</span>
                            </div>
                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted">Diskon (Rp)</label>
                                <input type="text" name="discount_amount" id="discount-input"
                                    class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                    value="0">
                            </div>
                            <div class="d-flex justify-content-between mb-1 p-3"
                                style="background:rgba(16,185,129,.1);border-radius:10px;border:1px solid rgba(16,185,129,.2);">
                                <span class="fw-bold text-white small align-self-center">GRAND TOTAL</span>
                                <span class="fw-extrabold text-success fs-5 font-monospace" id="display-grand-total">Rp
                                    0</span>
                            </div>

                            <hr style="border-color:rgba(255,255,255,0.08); margin:1.25rem 0;">

                            {{-- Pembayaran --}}
                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted">Metode Pembayaran <span
                                        class="text-danger">*</span></label>
                                <div class="d-grid gap-2" id="payment-buttons">
                                    @foreach (\App\Models\OfflineSale::PAYMENT_METHODS as $key => $label)
                                        <input type="radio" name="payment_method" id="pm-{{ $key }}"
                                            value="{{ $key }}" class="d-none"
                                            {{ $key === 'tunai' ? 'checked' : '' }}>
                                        <label for="pm-{{ $key }}"
                                            class="btn btn-sm btn-outline-secondary py-2 text-start payment-btn {{ $key === 'tunai' ? 'active btn-selected' : '' }}">
                                            @if ($key === 'tunai')
                                                <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                            @elseif($key === 'transfer')
                                                <i class="fas fa-university me-2 text-primary"></i>
                                            @elseif($key === 'qris')
                                                <i class="fas fa-qrcode me-2 text-warning"></i>
                                            @else
                                                <i class="fas fa-credit-card me-2 text-info"></i>
                                            @endif
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-1" id="paid-amount-section">
                                <label class="form-label form-label-sm text-muted">Uang Diterima (Rp)</label>
                                <input type="text" name="paid_amount" id="paid-input"
                                    class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25 fw-bold font-monospace"
                                    value="0" required>
                            </div>
                            <div class="mb-4 p-3 text-center rounded"
                                style="background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.2);"
                                id="change-section">
                                <div class="text-muted small">Kembalian</div>
                                <div class="fw-extrabold fs-4 text-primary font-monospace" id="display-change">Rp 0</div>
                            </div>

                            <hr style="border-color:rgba(255,255,255,0.08); margin:1.25rem 0;">

                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted d-block">Tipe Pelanggan</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="customer_type" id="customer_type_registered" value="registered" checked>
                                    <label class="form-check-label text-white small" for="customer_type_registered">Terdaftar</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="customer_type" id="customer_type_manual" value="manual">
                                    <label class="form-check-label text-white small" for="customer_type_manual">Pelanggan Baru</label>
                                </div>
                            </div>

                            <div class="mb-1" id="customer-select-wrapper">
                                <label class="form-label form-label-sm text-muted">Pelanggan / Pembeli (Opsional)</label>
                                <select name="customer_id" id="customer-select"
                                    class="form-select form-select-sm select2" style="width: 100%;">
                                    <option value="">-- Pelanggan Umum --</option>
                                    @foreach ($customers as $cust)
                                        <option value="{{ $cust->id }}" data-name="{{ $cust->name }}"
                                            data-phone="{{ $cust->phone }}" data-address="{{ $cust->address }}">
                                            {{ $cust->name }} {{ $cust->phone ? '(' . $cust->phone . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted" id="buyer-name-label">Nama Pembeli</label>
                                <input type="text" name="buyer_name" id="buyer-name-input"
                                    class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                    placeholder="Pelanggan Umum">
                            </div>
                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted" id="buyer-phone-label">No. HP Pembeli</label>
                                <input type="text" name="buyer_phone" id="buyer-phone-input"
                                    class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                    placeholder="0812...">
                            </div>
                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted">Alamat Pelanggan</label>
                                <textarea name="buyer_address" id="buyer-address-input"
                                    class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                    rows="2" placeholder="Alamat lengkap pelanggan..."></textarea>
                            </div>
                            <div class="mb-1">
                                <label class="form-label form-label-sm text-muted">Catatan</label>
                                <textarea name="notes"
                                    class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                    rows="2" placeholder="Tulis catatan transaksi jika ada..."></textarea>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-sm py-2 fw-semibold" id="btn-submit"
                                    disabled>
                                    <i class="fas fa-check-circle me-2"></i>Selesaikan Transaksi
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let cartItems = {};
            let grandTotal = 0;

            function toggleCustomerType(type) {
                if (type === 'registered') {
                    $('#customer-select-wrapper').show();
                    $('#buyer-name-input').prop('readonly', true);
                    $('#buyer-phone-input').prop('readonly', true);
                    $('#buyer-address-input').prop('readonly', true);
                    
                    // Remove required markers
                    $('#buyer-name-label').html('Nama Pembeli');
                    $('#buyer-phone-label').html('No. HP Pembeli');
                    $('#buyer-name-input').prop('required', false);
                    $('#buyer-phone-input').prop('required', false);
                    
                    triggerCustomerSelectChange();
                } else {
                    $('#customer-select-wrapper').hide();
                    
                    // Reset customer select
                    $('#customer-select').val('').trigger('change.select2');
                    
                    $('#buyer-name-input').val('').prop('readonly', false);
                    $('#buyer-phone-input').val('').prop('readonly', false);
                    $('#buyer-address-input').val('').prop('readonly', false);
                    
                    // Add required markers
                    $('#buyer-name-label').html('Nama Pembeli <span class="text-danger">*</span>');
                    $('#buyer-phone-label').html('No. HP Pembeli <span class="text-danger">*</span>');
                    $('#buyer-name-input').prop('required', true);
                    $('#buyer-phone-input').prop('required', true);
                }
                recalculate();
            }

            function triggerCustomerSelectChange() {
                const selectedOption = $('#customer-select').find('option:selected');
                const customerId = $('#customer-select').val();

                if (customerId) {
                    const name = selectedOption.data('name');
                    const phone = selectedOption.data('phone');
                    const address = selectedOption.data('address');

                    $('#buyer-name-input').val(name).prop('readonly', true);
                    $('#buyer-phone-input').val(phone || '').prop('readonly', true);
                    $('#buyer-address-input').val(address || '').prop('readonly', true);
                } else {
                    // Pelanggan Umum
                    $('#buyer-name-input').val('').prop('readonly', true);
                    $('#buyer-phone-input').val('').prop('readonly', true);
                    $('#buyer-address-input').val('').prop('readonly', true);
                }
                recalculate();
            }

            $('input[name="customer_type"]').on('change', function() {
                toggleCustomerType($(this).val());
            });

            $('#customer-select').on('change', function() {
                if ($('input[name="customer_type"]:checked').val() === 'registered') {
                    triggerCustomerSelectChange();
                }
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

            // Tambah produk ke keranjang
            $(document).on('click', '.product-row', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const sku = $(this).data('sku');
                const price = parseFloat($(this).data('price'));
                const stock = parseInt($(this).data('stock'));

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
                        price,
                        stock,
                        qty: 1
                    };
                }
                renderCart();
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
                $(this).val(formatted);
                recalculate();
            });

            // Pilih metode pembayaran
            $(document).on('click', '.payment-btn', function() {
                const key = $(this).attr('for').replace('pm-', '');
                $(`#pm-${key}`).prop('checked', true);

                $('.payment-btn').removeClass('active btn-selected').addClass(
                    'btn-outline-secondary');
                $(this).addClass('active btn-selected').removeClass('btn-outline-secondary');

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
                const subtotal = Object.values(cartItems).reduce((s, i) => s + i.qty * i.price, 0);
                const discount = unformatNumber($('#discount-input').val());
                grandTotal = Math.max(0, subtotal - discount);

                const method = $('input[name="payment_method"]:checked').val();

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
                    const custType = $('input[name="customer_type"]:checked').val();
                    const custVal = $('#customer-select').val();
                    const nameVal = $.trim($('#buyer-name-input').val());
                    const phoneVal = $.trim($('#buyer-phone-input').val());

                    if (custType === 'registered' && !custVal) {
                        isValid = false;
                    } else if (custType === 'manual' && (!nameVal || !phoneVal)) {
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
                    const subtotal = item.qty * item.price;
                    const tr = $('<tr></tr>');
                    tr.html(`
                        <td class="ps-3">
                            <div class="fw-semibold text-white small">${item.name}</div>
                            <div class="text-muted" style="font-size:.72rem;">${item.sku || ''}</div>
                            <input type="hidden" name="items[${idx}][master_product_id]" value="${item.id}">
                            <input type="hidden" name="items[${idx}][unit_price]" value="${item.price}">
                            <input type="hidden" name="items[${idx}][quantity]" id="qty-hidden-${item.id}" value="${item.qty}">
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm" style="max-width:95px;margin:auto;">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-minus" data-id="${item.id}">-</button>
                                <input type="number" class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25 text-center p-1 qty-input" data-id="${item.id}" value="${item.qty}" min="1" max="${item.stock}" style="width:40px;">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-plus" data-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td class="text-end small text-nowrap text-white-50 font-monospace">Rp ${item.price.toLocaleString('id-ID')}</td>
                        <td class="text-end fw-bold text-nowrap text-success font-monospace">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-id="${item.id}" style="padding: 2px 8px; font-size: 0.75rem;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `);
                    tbody.append(tr);
                    idx++;
                });

                recalculate();
            }

            // Initialize on load
            // Set payment button to active tunai
            $('.payment-btn[for="pm-tunai"]').addClass('active btn-selected').removeClass('btn-outline-secondary');
            
            // Set initial customer type toggle
            toggleCustomerType('registered');
            
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
            background: rgba(255, 255, 255, .03);
        }

        .btn-selected {
            background: rgba(99, 102, 241, 0.2) !important;
            color: #fff !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.35);
        }

        #product-list::-webkit-scrollbar {
            width: 6px;
        }

        #product-list::-webkit-scrollbar-track {
            background: transparent;
        }

        #product-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
        }

        #product-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.16);
        }
    </style>
@endpush
