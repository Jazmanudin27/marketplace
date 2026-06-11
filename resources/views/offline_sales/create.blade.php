@extends('layouts.app')

@section('title', 'Transaksi Baru — Penjualan Offline')
@section('page-title', 'Transaksi Baru')

@section('content')
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="background:transparent;padding:0;">
                <li class="breadcrumb-item"><a href="{{ route('offline_sales.index') }}" class="text-decoration-none">Penjualan
                        Offline</a></li>
                <li class="breadcrumb-item active">Transaksi Baru</li>
            </ol>
        </nav>

        <form id="offline-form" action="{{ route('offline_sales.store') }}" method="POST">
            @csrf
            <div class="row g-4">

                {{-- LEFT: Daftar Item --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><i class="fas fa-box me-2 text-primary"></i>Pilih Produk</h5>
                        </div>
                        <div class="card-body">

                            {{-- Pencarian produk --}}
                            <div class="mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text form-control-dark"><i class="fas fa-search"></i></span>
                                    <input type="text" id="product-search" class="form-control form-control-sm form-control-dark"
                                        placeholder="Cari nama produk atau SKU...">
                                </div>
                            </div>

                            {{-- Product list (scrollable) --}}
                            <div id="product-list"
                                style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;margin-bottom:1rem;">
                                @foreach ($products as $product)
                                    <div class="product-row d-flex align-items-center justify-content-between px-3 py-2"
                                        style="border-bottom:1px solid var(--border);cursor:pointer;transition:.15s;"
                                        data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                        data-sku="{{ $product->sku }}" data-price="{{ $product->price }}"
                                        data-stock="{{ $product->stock }}">
                                        <div>
                                            <div class="fw-600">{{ $product->name }}</div>
                                            <div class="text-muted small">{{ $product->sku }} &bull; Stok:
                                                {{ $product->stock }} {{ $product->unit }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-700 text-success text-nowrap">Rp
                                                {{ number_format($product->price, 0, ',', '.') }}</div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-1">
                                                <i class="fas fa-plus"></i> Tambah
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Cart items --}}
                            <h6 class="fw-600 mb-3"><i class="fas fa-shopping-cart me-2"></i>Keranjang</h6>
                            <div id="cart-empty" class="text-center py-4 text-muted"
                                style="border:1px dashed var(--border);border-radius:8px;">
                                <i class="fas fa-shopping-cart fa-2x mb-2 d-block opacity-25"></i>
                                Belum ada produk dipilih
                            </div>
                            <table class="table table-sm align-middle mb-0" id="cart-table" style="display:none;">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th style="width:120px" class="text-center">Qty</th>
                                        <th style="width:140px" class="text-end">Harga Satuan</th>
                                        <th style="width:130px" class="text-end">Subtotal</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Detail Transaksi --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2 text-success"></i>Detail Pembayaran</h5>
                        </div>
                        <div class="card-body">

                            {{-- Subtotal & diskon --}}
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-600" id="display-subtotal">Rp 0</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-500">Diskon (Rp)</label>
                                <input type="text" name="discount_amount" id="discount-input"
                                    class="form-control form-control-sm form-control-dark" value="0">
                            </div>
                            <div class="d-flex justify-content-between mb-3 p-3"
                                style="background:rgba(16,185,129,.1);border-radius:8px;border:1px solid rgba(16,185,129,.2);">
                                <span class="fw-700">GRAND TOTAL</span>
                                <span class="fw-800 text-success fs-5" id="display-grand-total">Rp 0</span>
                            </div>

                            <hr>

                            {{-- Pembayaran --}}
                            <div class="mb-3">
                                <label class="form-label small fw-500">Metode Pembayaran <span
                                        class="text-danger">*</span></label>
                                <div class="d-grid gap-2" id="payment-buttons">
                                    @foreach (\App\Models\OfflineSale::PAYMENT_METHODS as $key => $label)
                                        <input type="radio" name="payment_method" id="pm-{{ $key }}"
                                            value="{{ $key }}" class="d-none"
                                            {{ $key === 'tunai' ? 'checked' : '' }}>
                                        <label for="pm-{{ $key }}"
                                            class="btn btn-sm btn-outline-secondary py-1 payment-btn {{ $key === 'tunai' ? 'active btn-selected' : '' }}">
                                            @if ($key === 'tunai')
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                            @elseif($key === 'transfer')
                                                <i class="fas fa-university me-2"></i>
                                            @elseif($key === 'qris')
                                                <i class="fas fa-qrcode me-2"></i>
                                            @else
                                                <i class="fas fa-credit-card me-2"></i>
                                            @endif
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3" id="paid-amount-section">
                                        <label class="form-label small fw-500">Uang Diterima (Rp)</label>
                                        <input type="text" name="paid_amount" id="paid-input"
                                            class="form-control form-control-sm form-control-dark fw-700" value="0" required>
                                    </div>
                            <div class="mb-4 p-3 text-center" style="background:rgba(99,102,241,.1);border-radius:8px;"
                                id="change-section">
                                <div class="text-muted small">Kembalian</div>
                                <div class="fw-800 fs-4 text-primary" id="display-change">Rp 0</div>
                            </div>

                            <hr>

                            {{-- Pembeli (opsional) --}}
                            <div class="mb-3">
                                <label class="form-label small fw-500">Nama Pembeli (Opsional)</label>
                                <input type="text" name="buyer_name" class="form-control form-control-sm form-control-dark"
                                    placeholder="Pelanggan Umum">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-500">No. HP Pembeli</label>
                                <input type="text" name="buyer_phone" class="form-control form-control-sm form-control-dark"
                                    placeholder="0812...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-500">Catatan</label>
                                <textarea name="notes" class="form-control form-control-sm form-control-dark" rows="2" placeholder="Opsional..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-sm" id="btn-submit" disabled>
                                    <i class="fas fa-check-circle me-2"></i>Selesaikan Transaksi
                                </button>
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
            let cartItems = {};
            let grandTotal = 0;

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

            // Pencarian produk
            $('#product-search').on('input', function() {
                const q = $(this).val().toLowerCase();
                $('.product-row').each(function() {
                    const name = ($(this).data('name') || '').toLowerCase();
                    const sku = ($(this).data('sku') || '').toLowerCase();
                    if (name.includes(q) || sku.includes(q)) {
                        $(this).show();
                    } else {
                        $(this).hide();
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

                $('.payment-btn').removeClass('active btn-selected btn-success').addClass(
                    'btn-outline-secondary');
                $(this).addClass('active btn-selected').removeClass('btn-outline-secondary');

                if (key !== 'tunai') {
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

                if (method && method !== 'tunai') {
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
                        <td>
                            <div class="fw-600 small">${item.name}</div>
                            <div class="text-muted" style="font-size:.72rem;">${item.sku || ''}</div>
                            <input type="hidden" name="items[${idx}][master_product_id]" value="${item.id}">
                            <input type="hidden" name="items[${idx}][unit_price]" value="${item.price}">
                            <input type="hidden" name="items[${idx}][quantity]" id="qty-hidden-${item.id}" value="${item.qty}">
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm" style="max-width:90px;margin:auto;">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-minus" data-id="${item.id}">-</button>
                                <input type="number" class="form-control form-control-sm form-control-dark text-center p-1 qty-input" data-id="${item.id}" value="${item.qty}" min="1" max="${item.stock}" style="width:40px;">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-plus" data-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td class="text-end small text-nowrap">Rp ${item.price.toLocaleString('id-ID')}</td>
                        <td class="text-end fw-700 text-nowrap">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-id="${item.id}">
                                <i class="fas fa-times"></i>
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
            recalculate();
        });
    </script>
@endpush

@push('styles')
    <style>
        .product-row:hover {
            background: rgba(255, 255, 255, .05);
        }

        .btn-selected {
            background: var(--primary) !important;
            color: #fff !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 10px rgba(108, 99, 255, 0.4);
        }
    </style>
@endpush
