@extends('v2.layouts.app')

@section('title', 'Input Penjualan Offline (POS Toko) V2')

@push('styles')
<style>
/* ─── V2 POS Input Custom Styles ─── */
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
            <i class="bi bi-shop text-primary fs-5"></i> Input Transaksi Kasir POS (Penjualan Offline V2)
        </h1>
        <p class="text-muted small mb-0">Kelola entri transaksi kasir toko harian & pemotongan stok otomatis</p>
    </div>
    <div>
        <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Nota
        </a>
    </div>
</div>

<form action="{{ route('v2.penjualan_offline.store') }}" method="POST" id="posForm">
    @csrf

    <div class="row g-3 mb-4">
        {{-- ── Left Column: Product Selection & Cart Items (70%) ── --}}
        <div class="col-lg-8">
            <!-- Add Item Form Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2.5 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-cart-plus text-primary me-1.5"></i> Pilih Produk Kasir POS
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Cari / Pilih Produk Master</label>
                            <select id="select_product" class="form-select form-select-sm">
                                <option value="">-- Pilih Produk Master --</option>
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
                            <input type="number" id="input_qty" value="1" min="1" class="form-control form-control-sm text-center font-monospace fw-bold">
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
                                    Keranjang kasir masih kosong. Silakan tambah produk di atas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Right Column: Customer Info & Payment Details (30%) ── --}}
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
                        <i class="bi bi-person text-info me-1.5"></i> Data Pembeli & Nota
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Pelanggan Terdaftar (Opsional)</label>
                        <select name="customer_id" id="customer_id" class="form-select form-select-sm">
                            <option value="">-- Pelanggan Walk-in (Umum) --</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" data-phone="{{ $cust->phone }}" data-name="{{ $cust->name }}">
                                    {{ $cust->name }} {{ $cust->phone ? '('.$cust->phone.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Pembeli</label>
                        <input type="text" name="buyer_name" id="buyer_name" class="form-control form-control-sm" placeholder="Contoh: Bpk. Ahmad">
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">No. Telepon / WA</label>
                        <input type="text" name="buyer_phone" id="buyer_phone" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary mb-1">Catatan Nota POS</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Catatan transaksi..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-2.5 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-cash-stack text-success me-1.5"></i> Pembayaran Kasir
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Metode Pembayaran</label>
                        <select name="payment_method" id="payment_method" class="form-select form-select-sm fw-semibold">
                            <option value="tunai" selected>Tunai / Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS / E-Wallet</option>
                            <option value="piutang">Piutang / Tempo Kredit</option>
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Diskon Nota (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text py-0 px-2 text-muted small">Rp</span>
                            <input type="text" name="discount_amount" id="discount_amount" value="0" class="form-control form-control-sm text-end font-monospace fw-semibold" placeholder="0">
                        </div>
                    </div>

                    <div class="mb-2.5">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-secondary mb-0">Jumlah Uang Dibayar (Rp)</label>
                            <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none small fw-semibold" id="btn_exact_amount">Uang Pas</button>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text py-0 px-2 text-success small fw-bold">Rp</span>
                            <input type="text" name="paid_amount" id="paid_amount" value="0" class="form-control form-control-sm text-end font-monospace fw-bold fs-6 border-success text-success" placeholder="0">
                        </div>
                        <div class="d-flex gap-1 mt-1.5 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1.5 quick-cash-btn" data-amt="50000" style="font-size: 0.72rem;">50.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1.5 quick-cash-btn" data-amt="100000" style="font-size: 0.72rem;">100.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1.5 quick-cash-btn" data-amt="200000" style="font-size: 0.72rem;">200.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1.5 quick-cash-btn" data-amt="500000" style="font-size: 0.72rem;">500.000</button>
                        </div>
                    </div>

                    <div class="p-2.5 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-secondary" id="label_change">Kembalian / Sisa Piutang:</span>
                            <span class="fw-bold font-monospace fs-6 text-dark" id="display_change">Rp 0</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm py-2.5" style="background:#16a34a; border:none;">
                        <i class="bi bi-check-circle-fill me-1.5"></i> Simpan Transaksi POS
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let cart = [];

    const selectProduct = document.getElementById('select_product');
    const inputQty = document.getElementById('input_qty');
    const inputPrice = document.getElementById('input_price');
    const btnAddItem = document.getElementById('btn_add_item');
    const cartBody = document.getElementById('cart_body');
    const cartCount = document.getElementById('cart_count');

    const discountInput = document.getElementById('discount_amount');
    const paidInput = document.getElementById('paid_amount');
    const btnExactAmount = document.getElementById('btn_exact_amount');
    const displayGrandTotal = document.getElementById('display_grand_total');
    const displaySubtotal = document.getElementById('display_subtotal');
    const displayDiscount = document.getElementById('display_discount');
    const displayChange = document.getElementById('display_change');
    const labelChange = document.getElementById('label_change');
    const posForm = document.getElementById('posForm');

    // ── Helper: Format Angka Pemisah Ribuan (Titik) ──
    function formatRupiah(val) {
        if (val === null || val === undefined || val === '') return '0';
        let clean = String(val).replace(/[^0-9]/g, '');
        if (!clean) return '0';
        return parseInt(clean, 10).toLocaleString('id-ID');
    }

    // ── Helper: Parse Angka Murni dari Format Ribuan ──
    function parseRupiah(val) {
        if (val === null || val === undefined || val === '') return 0;
        let clean = String(val).replace(/[^0-9]/g, '');
        return clean ? parseInt(clean, 10) : 0;
    }

    // ── Event Formatter Dinamis Pada Input ──
    function applyRupiahFormatEvent(inputElem, onUpdateCallback) {
        inputElem.addEventListener('input', function() {
            let num = parseRupiah(this.value);
            this.value = num > 0 ? formatRupiah(num) : (this.value === '' ? '' : '0');
            if (onUpdateCallback) onUpdateCallback(num);
        });
        inputElem.addEventListener('blur', function() {
            if (!this.value.trim()) this.value = '0';
        });
    }

    applyRupiahFormatEvent(inputPrice);
    applyRupiahFormatEvent(discountInput, calculateTotals);
    applyRupiahFormatEvent(paidInput, calculateTotals);

    // Otomatis isi harga saat produk master dipilih & bisa langsung diedit
    selectProduct.addEventListener('change', function() {
        const opt = selectProduct.options[selectProduct.selectedIndex];
        if (opt && opt.value) {
            let rawPrice = parseFloat(opt.dataset.price) || 0;
            inputPrice.value = formatRupiah(rawPrice);
            inputQty.focus();
        } else {
            inputPrice.value = '0';
        }
    });

    // Quick Cash buttons
    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let amt = parseInt(this.dataset.amt) || 0;
            let currentPaid = parseRupiah(paidInput.value);
            paidInput.value = formatRupiah(currentPaid + amt);
            calculateTotals();
        });
    });

    // Tombol Uang Pas
    if (btnExactAmount) {
        btnExactAmount.addEventListener('click', function() {
            let subtotal = 0;
            cart.forEach(item => { subtotal += (item.qty * item.price); });
            let discount = parseRupiah(discountInput.value);
            let grandTotal = Math.max(0, subtotal - discount);
            paidInput.value = formatRupiah(grandTotal);
            calculateTotals();
        });
    }

    btnAddItem.addEventListener('click', function() {
        const opt = selectProduct.options[selectProduct.selectedIndex];
        if (!opt || !opt.value) {
            alert('Silakan pilih produk terlebih dahulu.');
            return;
        }

        const prodId = parseInt(opt.value);
        const prodName = opt.dataset.name;
        const prodSku = opt.dataset.sku;
        const qty = parseInt(inputQty.value) || 1;
        const price = parseRupiah(inputPrice.value);

        const existing = cart.find(item => item.id === prodId);
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
                    </td>
                    <td class="text-center">
                        <input type="number" name="items[${index}][quantity]" value="${item.qty}" min="1"
                               class="form-control form-control-sm text-center font-monospace fw-bold cart-qty-input" data-index="${index}" style="width: 70px; margin: 0 auto;">
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
            inp.addEventListener('change', function() {
                const idx = parseInt(this.dataset.index);
                const val = parseInt(this.value) || 1;
                cart[idx].qty = val;
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

    // Bersihkan titik ribuan sebelum submit agar validasi server menerima angka bersih
    if (posForm) {
        posForm.addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Keranjang kasir masih kosong!');
                return false;
            }
            document.querySelectorAll('.cart-price-input').forEach(inp => {
                inp.value = parseRupiah(inp.value);
            });
            discountInput.value = parseRupiah(discountInput.value);
            paidInput.value = parseRupiah(paidInput.value);
        });
    }

    document.getElementById('customer_id').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('buyer_name').value = opt.dataset.name || '';
            document.getElementById('buyer_phone').value = opt.dataset.phone || '';
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
</script>
@endpush

@endsection
