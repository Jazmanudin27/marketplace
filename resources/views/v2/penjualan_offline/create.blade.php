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
                            <input type="number" id="input_price" value="0" min="0" class="form-control form-control-sm text-end font-monospace">
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
                                <th class="ps-3" style="width: 40%;">Produk / SKU</th>
                                <th class="text-center" style="width: 15%;">Qty</th>
                                <th class="text-end" style="width: 20%;">Harga Satuan</th>
                                <th class="text-end" style="width: 20%;">Subtotal</th>
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
                        <input type="number" name="discount_amount" id="discount_amount" value="0" min="0" class="form-control form-control-sm font-monospace">
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Jumlah Uang Dibayar (Rp)</label>
                        <input type="number" name="paid_amount" id="paid_amount" value="0" min="0" class="form-control form-control-sm font-monospace fw-bold fs-6 border-success text-success">
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
    const displayGrandTotal = document.getElementById('display_grand_total');
    const displaySubtotal = document.getElementById('display_subtotal');
    const displayDiscount = document.getElementById('display_discount');
    const displayChange = document.getElementById('display_change');
    const labelChange = document.getElementById('label_change');

    selectProduct.addEventListener('change', function() {
        const opt = selectProduct.options[selectProduct.selectedIndex];
        if (opt && opt.value) {
            inputPrice.value = opt.dataset.price || 0;
        } else {
            inputPrice.value = 0;
        }
    });

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
        const price = parseFloat(inputPrice.value) || 0;

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
        inputPrice.value = 0;
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
                        <input type="number" name="items[${index}][unit_price]" value="${item.price}" min="0"
                               class="form-control form-control-sm text-end font-monospace cart-price-input" data-index="${index}" style="width: 110px; margin-left: auto;">
                    </td>
                    <td class="text-end font-monospace fw-bold text-dark pe-3">
                        Rp ${subtotal.toLocaleString('id-ID')}
                    </td>
                    <td class="text-center pe-3">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1.5 btn-remove-item" data-index="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        cartBody.innerHTML = html;
        cartCount.innerText = count.toString();

        document.querySelectorAll('.cart-qty-input').forEach(inp => {
            inp.addEventListener('change', function() {
                const idx = parseInt(this.dataset.index);
                const val = parseInt(this.value) || 1;
                cart[idx].qty = val;
                renderCart();
            });
        });

        document.querySelectorAll('.cart-price-input').forEach(inp => {
            inp.addEventListener('change', function() {
                const idx = parseInt(this.dataset.index);
                const val = parseFloat(this.value) || 0;
                cart[idx].price = val;
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

        const discount = parseFloat(discountInput.value) || 0;
        const grandTotal = Math.max(0, subtotal - discount);
        const paid = parseFloat(paidInput.value) || 0;
        const diff = paid - grandTotal;

        displaySubtotal.innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
        displayDiscount.innerText = 'Rp ' + discount.toLocaleString('id-ID');
        displayGrandTotal.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

        if (diff >= 0) {
            labelChange.innerText = 'Kembalian Tunai:';
            displayChange.className = 'fw-bold font-monospace fs-6 text-success';
            displayChange.innerText = 'Rp ' + diff.toLocaleString('id-ID');
        } else {
            labelChange.innerText = 'Sisa Piutang:';
            displayChange.className = 'fw-bold font-monospace fs-6 text-danger';
            displayChange.innerText = 'Rp ' + Math.abs(diff).toLocaleString('id-ID');
        }
    }

    discountInput.addEventListener('input', calculateTotals);
    paidInput.addEventListener('input', calculateTotals);

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
