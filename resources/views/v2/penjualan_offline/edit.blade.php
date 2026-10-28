@extends('v2.layouts.app')

@section('title', 'Edit Transaksi POS #' . $offlineSale->sale_number)

@push('styles')
<style>
/* ─── V2 POS Edit Custom Styles ─── */
.pos-grand-card {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #ffffff;
    border-radius: 10px;
    padding: 16px;
}
.pos-grand-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0.8;
}
.pos-grand-amount {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1.2;
    color: #38bdf8;
    font-family: 'Courier New', monospace;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-pencil-square text-warning fs-5"></i> Edit Transaksi Kasir POS #{{ $offlineSale->sale_number }}
        </h1>
        <p class="text-muted small mb-0">Perbarui item belanja, informasi instansi / toko, atau rincian pembayaran nota</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.penjualan_offline.show', $offlineSale->id) }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="bi bi-arrow-left me-1"></i> Batal &amp; Kembali
        </a>
    </div>
</div>

@if ($offlineSale->is_po && $offlineSale->spks()->exists())
    <div class="alert alert-info border d-flex align-items-center gap-2 mb-3 py-2 px-3 shadow-sm rounded-3">
        <i class="bi bi-info-circle-fill text-info fs-5"></i>
        <div class="small">
            <strong>Catatan SPK Produksi:</strong> Transaksi PO ini memiliki <strong>{{ $offlineSale->spks()->count() }} SPK</strong> aktif. Jika Anda mengubah daftar item atau kuantitas, pastikan untuk memeriksa SPK terkait di antrean produksi.
        </div>
    </div>
@endif

<form action="{{ route('v2.penjualan_offline.update', $offlineSale->id) }}" method="POST" id="posEditForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="is_po" value="{{ $offlineSale->is_po ? '1' : '0' }}">

    <div class="row g-3 mb-4">
        {{-- ── Left Column: Product Selection & Cart Items (65%) ── --}}
        <div class="col-lg-8">
            <!-- Add Item Form Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2.5 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-cart-plus text-primary me-1.5"></i> Tambah / Pilih Produk
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Cari / Pilih Produk Master</label>
                            <select id="select_product" class="form-select form-select-sm select2" data-placeholder="-- Ketik Nama Produk atau SKU --">
                                <option value="">-- Ketik Nama Produk atau SKU --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}"
                                            data-name="{{ $prod->name }}"
                                            data-sku="{{ $prod->sku }}"
                                            data-price="{{ $prod->selling_price ?? $prod->price ?? 0 }}"
                                            data-stock="{{ $prod->stock ?? 0 }}">
                                        {{ $prod->name }} [SKU: {{ $prod->sku ?: '-' }}] - Stok: {{ $prod->stock }} pcs - Rp {{ number_format($prod->selling_price ?? $prod->price ?? 0, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Qty</label>
                            <input type="text" id="input_qty" value="1" inputmode="numeric" class="form-control form-control-sm text-end font-monospace fw-bold number-separator">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Harga Satuan</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-2 text-muted small">Rp</span>
                                <input type="text" id="input_price" value="0" class="form-control form-control-sm text-end font-monospace fw-semibold" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btn_add_item" class="btn btn-primary btn-sm w-100 fw-semibold">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Table Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt text-success me-1.5"></i> Keranjang Belanja Nota (<span id="cart_count">0</span> Item)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size: 0.83rem;" id="cartTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 38%;">Produk / SKU</th>
                                <th class="text-center" style="width: 14%;">Qty</th>
                                <th class="text-end" style="width: 24%;">Harga Satuan</th>
                                <th class="text-end" style="width: 19%;">Subtotal</th>
                                <th class="text-center" style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="cart_body">
                            <tr id="empty_cart_row">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Keranjang kasir masih kosong.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Right Column: Customer Info & Payment Details (35%) ── --}}
        <div class="col-lg-4">
            <!-- Grand Total Display Card -->
            <div class="pos-grand-card mb-3 shadow-sm">
                <div class="pos-grand-label">TOTAL NOTA TAGIHAN</div>
                <div class="pos-grand-amount" id="display_grand_total">Rp 0</div>
                <div class="d-flex justify-content-between mt-2 pt-2 border-top border-secondary border-opacity-50 small opacity-75">
                    <span>Subtotal: <strong id="display_subtotal">Rp 0</strong></span>
                    <span>Diskon: <strong id="display_discount">Rp 0</strong></span>
                </div>
            </div>

            <!-- Customer & Transaction Details Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2.5 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-person text-info me-1.5"></i> Data Pembeli &amp; Nota
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="bi bi-shop me-1 text-primary"></i> Toko / Cabang Asal
                        </label>
                        @php
                            $selectedStoreId = old('store_id', $offlineSale->store_id);
                            $selectedStoreName = $offlineSale->store->store_name ?? null;
                        @endphp
                        <select name="store_id" id="store_id" class="form-select form-select-sm">
                            <option value="">-- Pilih Toko (Default / Toko Utama) --</option>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ ($selectedStoreId == $st->id || ($selectedStoreName && $selectedStoreName === $st->store_name)) ? 'selected' : '' }}>
                                    {{ $st->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Pelanggan Terdaftar (Opsional)</label>
                        <select name="customer_id" id="customer_id" class="form-select form-select-sm">
                            <option value="">-- Pelanggan Walk-in (Umum) --</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" data-phone="{{ $cust->phone }}" data-name="{{ $cust->name }}"
                                    {{ $offlineSale->customer_id == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->name }} {{ $cust->phone ? '('.$cust->phone.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Pembeli</label>
                        <input type="text" name="buyer_name" id="buyer_name" class="form-control form-control-sm"
                               value="{{ $offlineSale->buyer_name }}" placeholder="Contoh: Bpk. Ahmad">
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">No. Telepon / WA</label>
                        <input type="text" name="buyer_phone" id="buyer_phone" class="form-control form-control-sm"
                               value="{{ $offlineSale->buyer_phone }}" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Instansi / Toko Pembeli</label>
                        <input type="text" name="institution_name" id="institution_name" class="form-control form-control-sm"
                               value="{{ $offlineSale->institution_name }}" placeholder="Contoh: PT Sinar Harapan / Toko Barokah / Sekolah ABC">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Catatan Nota POS</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Catatan transaksi...">{{ $offlineSale->notes }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Card -->
            <!-- Diskon & Nilai Tagihan -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-2.5 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-tag text-primary me-1.5"></i> Diskon & Ringkasan Tagihan
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Diskon Nota (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text py-0 px-2 text-muted small">Rp</span>
                            <input type="text" name="discount_amount" id="discount_amount"
                                   value="{{ number_format($offlineSale->discount_amount, 0, ',', '.') }}"
                                   class="form-control form-control-sm text-end font-monospace fw-semibold" placeholder="0">
                        </div>
                        <div class="form-text text-muted small" style="font-size: 0.72rem;">Potongan diskon langsung untuk total nota belanja.</div>
                    </div>

                    <!-- Status Pembayaran Saat Ini (Read-Only) -->
                    <div class="p-2.5 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom">
                            <span class="small text-muted fw-semibold">Status Bayar:</span>
                            <span class="badge {{ $offlineSale->is_paid ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $offlineSale->is_paid ? 'LUNAS' : 'BELUM LUNAS' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted">Total Terbayar:</span>
                            <span class="font-monospace fw-bold text-success">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-secondary" id="label_change">Sisa Piutang:</span>
                            <span id="display_change" class="fw-bold font-monospace fs-6 text-danger">Rp {{ number_format($offlineSale->remaining_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Hidden input to maintain paid_amount reference for calculation & submit -->
                    <input type="hidden" name="paid_amount" id="paid_amount" value="{{ (int)$offlineSale->paid_amount }}">

                    <!-- Notice / Shortcut ke Show -->
                    <div class="alert alert-info py-2 px-2.5 small mb-3 d-flex align-items-start gap-2" style="font-size: 0.76rem;">
                        <i class="bi bi-info-circle-fill text-primary mt-0.5"></i>
                        <div>
                            Pencatatan pembayaran kasir atau cicilan dilakukan di <strong>Menu Detail Nota (Show)</strong> agar tercatat otomatis ke kas/bank & jurnal keuangan.
                        </div>
                    </div>

                    <a href="{{ route('v2.penjualan_offline.show', $offlineSale->id) }}" class="btn btn-outline-primary btn-sm w-100 fw-semibold py-1.5 mb-3">
                        <i class="bi bi-receipt me-1"></i> Buka Detail & Catat Pembayaran
                    </a>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning py-2 fw-bold text-dark shadow-sm">
                            <i class="bi bi-check2-circle me-1.5"></i> Simpan Perubahan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi awal cart dengan item transaksi yang sudah ada
    const initialItems = @json($initialCartItems ?? []);

    let cart = initialItems || [];

    const selectProduct = document.getElementById('select_product');
    const inputQty = document.getElementById('input_qty');
    const inputPrice = document.getElementById('input_price');
    const btnAddItem = document.getElementById('btn_add_item');
    const cartBody = document.getElementById('cart_body');
    const cartCount = document.getElementById('cart_count');
    const discountInput = document.getElementById('discount_amount');
    const paidInput = document.getElementById('paid_amount');
    const displaySubtotal = document.getElementById('display_subtotal');
    const displayDiscount = document.getElementById('display_discount');
    const displayGrandTotal = document.getElementById('display_grand_total');
    const displayChange = document.getElementById('display_change');
    const labelChange = document.getElementById('label_change');
    const btnExactAmount = document.getElementById('btn_exact_amount');
    const posEditForm = document.getElementById('posEditForm');

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(Math.round(number || 0));
    }

    function parseRupiah(str) {
        if (!str) return 0;
        const clean = str.toString().replace(/[^0-9]/g, '');
        return parseFloat(clean) || 0;
    }

    function onProductSelected() {
        const opt = selectProduct.options[selectProduct.selectedIndex];
        if (opt && opt.value) {
            const price = parseFloat(opt.dataset.price) || 0;
            inputPrice.value = formatRupiah(price);
            inputQty.value = 1;
        } else {
            inputPrice.value = '0';
        }
    }

    selectProduct.addEventListener('change', onProductSelected);
    if (typeof jQuery !== 'undefined') {
        $('#select_product').on('select2:select change', onProductSelected);
    }

    inputQty.addEventListener('input', function() {
        this.value = formatRupiah(parseRupiah(this.value));
    });

    inputPrice.addEventListener('input', function() {
        this.value = formatRupiah(parseRupiah(this.value));
    });

    discountInput.addEventListener('input', function() {
        this.value = formatRupiah(parseRupiah(this.value));
        calculateTotals();
    });

    if (paidInput && paidInput.type !== 'hidden') {
        paidInput.addEventListener('input', function() {
            this.value = formatRupiah(parseRupiah(this.value));
            calculateTotals();
        });
    }

    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const amt = parseFloat(this.dataset.amt) || 0;
            if (paidInput) {
                paidInput.value = formatRupiah(amt);
                calculateTotals();
            }
        });
    });

    if (btnExactAmount) {
        btnExactAmount.addEventListener('click', function() {
            let subtotal = 0;
            cart.forEach(item => { subtotal += (item.qty * item.price); });
            const discount = discountInput ? parseRupiah(discountInput.value) : 0;
            const grandTotal = Math.max(0, subtotal - discount);
            if (paidInput) paidInput.value = formatRupiah(grandTotal);
            calculateTotals();
        });
    }

    btnAddItem.addEventListener('click', function() {
        const prodId = selectProduct.value;
        if (!prodId) {
            alert('Silakan pilih produk terlebih dahulu!');
            return;
        }

        const opt = selectProduct.options[selectProduct.selectedIndex];
        const prodName = opt.dataset.name;
        const prodSku = opt.dataset.sku;
        const qty = parseInt(inputQty.value) || 1;
        const price = parseRupiah(inputPrice.value);

        const existing = cart.find(item => item.id == prodId);
        if (existing) {
            existing.qty += qty;
            existing.price = price;
        } else {
            cart.push({
                id: prodId,
                name: prodName,
                sku: prodSku,
                qty: qty,
                price: price
            });
        }

        renderCart();
        selectProduct.value = '';
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('#select_product').val('').trigger('change');
        }
        inputQty.value = 1;
        inputPrice.value = '0';
        selectProduct.focus();
    });

    function renderCart() {
        if (cart.length === 0) {
            cartBody.innerHTML = `
                <tr id="empty_cart_row">
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Keranjang kasir masih kosong. Silakan tambah produk di atas.
                    </td>
                </tr>
            `;
            cartCount.innerText = '0';
            calculateTotals();
            return;
        }

        let html = '';
        let count = 0;

        cart.forEach((item, index) => {
            count += item.qty;
            const subtotal = item.qty * item.price;
            html += `
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold text-dark">${escapeHtml(item.name)}</div>
                        <small class="text-muted font-monospace">SKU: ${escapeHtml(item.sku || '-')}</small>
                        <input type="hidden" name="items[${index}][master_product_id]" value="${item.id}">
                        <input type="hidden" name="items[${index}][product_name]" value="${escapeHtml(item.name)}">
                        <input type="hidden" name="items[${index}][sku]" value="${escapeHtml(item.sku || '')}">
                    </td>
                    <td class="text-center">
                        <input type="text" name="items[${index}][quantity]" value="${formatRupiah(item.qty)}" inputmode="numeric"
                               class="form-control form-control-sm text-end font-monospace fw-bold cart-qty-input number-separator" data-index="${index}" style="width: 75px; margin: 0 auto;">
                    </td>
                    <td class="text-end">
                        <div class="input-group input-group-sm" style="width: 140px; margin-left: auto;">
                            <span class="input-group-text py-0 px-1.5 text-muted small">Rp</span>
                            <input type="text" name="items[${index}][unit_price]" value="${formatRupiah(item.price)}"
                                   class="form-control form-control-sm text-end font-monospace fw-semibold cart-price-input" data-index="${index}">
                        </div>
                    </td>
                    <td class="text-end font-monospace fw-bold text-dark pe-3">
                        Rp ${formatRupiah(subtotal)}
                    </td>
                    <td class="text-center pe-3">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1.5 btn-remove-item" data-index="${index}" title="Hapus Item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        cartBody.innerHTML = html;
        cartCount.innerText = count.toString();

        // Event listener ubah Qty di tabel
        document.querySelectorAll('.cart-qty-input').forEach(inp => {
            inp.addEventListener('input', function() {
                const idx = parseInt(this.dataset.index);
                const val = parseRupiah(this.value);
                this.value = formatRupiah(val);
                cart[idx].qty = val > 0 ? val : 1;
                calculateTotals();
            });
            inp.addEventListener('blur', function() {
                renderCart();
            });
        });

        // Event listener ubah Harga Satuan di tabel dengan pemisah ribuan
        document.querySelectorAll('.cart-price-input').forEach(inp => {
            inp.addEventListener('input', function() {
                const idx = parseInt(this.dataset.index);
                const val = parseRupiah(this.value);
                this.value = formatRupiah(val);
                cart[idx].price = val;
                calculateTotals();
            });
            inp.addEventListener('blur', function() {
                renderCart();
            });
        });

        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                cart.splice(idx, 1);
                renderCart();
            });
        });

        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += (item.qty * item.price);
        });

        const discount = parseRupiah(discountInput.value);
        const grandTotal = Math.max(0, subtotal - discount);
        const paid = parseRupiah(paidInput.value);
        const diff = paid - grandTotal;

        displaySubtotal.innerText = 'Rp ' + formatRupiah(subtotal);
        displayDiscount.innerText = 'Rp ' + formatRupiah(discount);
        displayGrandTotal.innerText = 'Rp ' + formatRupiah(grandTotal);

        if (diff >= 0) {
            labelChange.innerText = 'Kembalian Tunai:';
            displayChange.className = 'fw-bold font-monospace fs-6 text-success';
            displayChange.innerText = 'Rp ' + formatRupiah(diff);
        } else {
            labelChange.innerText = 'Sisa Piutang:';
            displayChange.className = 'fw-bold font-monospace fs-6 text-danger';
            displayChange.innerText = 'Rp ' + formatRupiah(Math.abs(diff));
        }
    }

    // Bersihkan titik ribuan sebelum submit agar validasi server menerima angka numerik
    if (posEditForm) {
        posEditForm.addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Keranjang kasir masih kosong!');
                return false;
            }
            document.querySelectorAll('.cart-qty-input').forEach(inp => {
                inp.value = parseRupiah(inp.value);
            });
            document.querySelectorAll('.cart-price-input').forEach(inp => {
                inp.value = parseRupiah(inp.value);
            });
            discountInput.value = parseRupiah(discountInput.value);
            paidInput.value = parseRupiah(paidInput.value);
        });
    }

    function onCustomerSelected() {
        const custSelect = document.getElementById('customer_id');
        if (!custSelect) return;
        const opt = custSelect.options[custSelect.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('buyer_name').value = opt.dataset.name || '';
            document.getElementById('buyer_phone').value = opt.dataset.phone || '';
        }
    }

    const custSelectElem = document.getElementById('customer_id');
    if (custSelectElem) {
        custSelectElem.addEventListener('change', onCustomerSelected);
        if (typeof jQuery !== 'undefined') {
            $('#customer_id').on('select2:select change', onCustomerSelected);
        }
    }

    // Pastikan Select2 terinisialisasi pada seluruh dropdown .select2
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        if (window.initV2Select2) {
            window.initV2Select2();
        } else {
            $('select.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Initial render cart on page load
    renderCart();
});
</script>
@endpush

@endsection
