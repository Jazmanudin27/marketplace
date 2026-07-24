@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Page -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-pencil-square fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Edit Penerimaan Barang Konsinyasi: {{ $consignment->reference_number }}</h4>
                        <p class="text-muted small mb-0">Ubah rincian barang titipan supplier & penyesuaian otomatis stok master produk</p>
                    </div>
                </div>
                <a href="{{ route('supplier_consignments.show', $consignment) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Batal / Kembali
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan Form:</h6>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('supplier_consignments.update', $consignment) }}" method="POST" id="consignment-form">
        @csrf
        @method('PUT')

        <!-- 1. HEADER CARDS (Data Transaksi & Total Ringkasan - Pure Bootstrap 5) -->
        <div class="row g-4 mb-4">
            <!-- Left: Data Transaksi / Supplier -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold mb-0 text-primary text-uppercase small"><i class="bi bi-info-circle me-2"></i>DATA TRANSAKSI & SUPPLIER</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted text-uppercase">No. Referensi</label>
                                <input type="text" class="form-control bg-light fw-bold text-primary" value="{{ $consignment->reference_number }}" readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Supplier (Pemilik Barang) <span class="text-danger">*</span></label>
                                <select name="supplier_id" id="supplier_select" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Supplier Penitip --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $consignment->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }} ({{ $supplier->phone ?: 'No Contact' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Penerimaan <span class="text-danger">*</span></label>
                                <input type="date" name="consignment_date" class="form-control" value="{{ old('consignment_date', $consignment->consignment_date->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Catatan / Keterangan</label>
                                <input type="text" name="notes" class="form-control" placeholder="Contoh: Titipan Celana SMA L 100 Pcs disetor tgl 01..." value="{{ old('notes', $consignment->notes) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Total Summary Card (Pure Bootstrap 5 Primary Card) -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-primary text-white p-2">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-white text-primary rounded-pill px-3 py-1 mb-2 fw-bold">TOTAL RINGKASAN</span>
                            <small class="text-white text-opacity-75 d-block fw-semibold text-uppercase">Total HPP Modal (Setoran Supplier)</small>
                            <h2 class="fw-bold text-white mb-0 mt-1" id="big-total-hpp">Rp {{ number_format($consignment->total_amount_hpp, 0, ',', '.') }}</h2>
                        </div>

                        <div class="pt-3 border-top border-white border-opacity-25 mt-3">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-white text-opacity-75">Total Qty Barang:</span>
                                <span class="fw-bold text-white fs-6" id="big-total-qty">{{ number_format($consignment->total_qty_received) }} PCS</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 small">
                                <span class="text-white text-opacity-75">Potensi Keuntungan Toko:</span>
                                <span class="fw-bold text-white fs-6" id="big-total-profit">+Rp 0</span>
                            </div>
                            <button type="submit" class="btn btn-light btn-lg w-100 rounded-pill fw-bold text-primary shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. BARIS QUICK INPUT BARANG & SATUAN (Enter / Auto Add) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="fw-bold mb-0 text-dark text-uppercase small"><i class="bi bi-barcode text-primary me-2"></i>INPUT BARANG & SATUAN (TEKAN ENTER UNTUK MASUK KERANJANG)</h6>
            </div>
            <div class="card-body p-4 pt-0">
                <div class="row g-2 align-items-end" id="quick-input-bar">
                    <!-- Search Product (Col-md-5) -->
                    <div class="col-md-5 col-12">
                        <label class="form-label fw-bold small text-muted mb-1">Cari / Pilih Barang (20.000 Data) <span class="text-danger">*</span></label>
                        <select id="quick_product_select" class="form-select w-100">
                            <option value="">-- Ketik Nama / SKU Barang --</option>
                        </select>
                    </div>

                    <!-- Qty (Col-md-2) -->
                    <div class="col-md-2 col-4">
                        <label class="form-label fw-bold small text-muted mb-1">Qty (PCS) <span class="text-danger">*</span></label>
                        <input type="number" id="quick_qty" class="form-control text-center fw-bold" value="100" min="1">
                    </div>

                    <!-- Harga Titip / HPP (Col-md-2) -->
                    <div class="col-md-2 col-4">
                        <label class="form-label fw-bold small text-muted mb-1">Harga Titip (HPP) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                            <input type="text" id="quick_cost_display" class="form-control fw-bold" value="80.000" oninput="formatRupiahQuick(this)">
                            <input type="hidden" id="quick_cost_raw" value="80000">
                        </div>
                    </div>

                    <!-- Harga Jual (Col-md-2) -->
                    <div class="col-md-2 col-4">
                        <label class="form-label fw-bold small text-muted mb-1">Harga Jual Toko <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                            <input type="text" id="quick_price_display" class="form-control fw-bold" value="100.000" oninput="formatRupiahQuick(this)">
                            <input type="hidden" id="quick_price_raw" value="100000">
                        </div>
                    </div>

                    <!-- Add Button + (Col-md-1) -->
                    <div class="col-md-1 col-12">
                        <button type="button" id="btn-add-to-cart" class="btn btn-primary w-100 fw-bold shadow-sm" title="Tambah ke Keranjang (Enter)">
                            <i class="bi bi-plus-lg fs-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. TABEL DAFTAR ITEM BARANG (KERANJANG - Pure Bootstrap 5) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark text-uppercase small"><i class="bi bi-cart3 text-primary me-2"></i>DAFTAR ITEM BARANG (KERANJANG)</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold" id="cart-item-count">0 Item</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover" id="cart-table">
                        <thead class="table-light text-muted small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4" style="width: 50px;">NO</th>
                                <th style="width: 15%;">SKU</th>
                                <th style="width: 35%;">NAMA BARANG</th>
                                <th class="text-center" style="width: 10%;">QTY</th>
                                <th class="text-end" style="width: 12%;">HARGA TITIP (HPP)</th>
                                <th class="text-end" style="width: 12%;">HARGA JUAL</th>
                                <th class="text-end" style="width: 12%;">SUBTOTAL HPP</th>
                                <th class="text-center pe-4" style="width: 5%;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="cart-tbody">
                            <!-- Pre-filled via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
// Prefill existing consignment items into cart array
let cart = [
    @foreach($consignment->items as $item)
    {
        product_id: {{ $item->master_product_id }},
        sku: "{{ $item->masterProduct ? $item->masterProduct->sku : '-' }}",
        name: "{{ $item->masterProduct ? addslashes($item->masterProduct->name) : 'Produk Terhapus' }}",
        qty: {{ $item->qty_received }},
        cost: {{ $item->unit_cost_price }},
        price: {{ $item->unit_selling_price }}
    },
    @endforeach
];
let selectedProductData = null;

function formatRupiahQuick(elem) {
    let value = elem.value.replace(/[^0-9]/g, '');
    let number = parseInt(value, 10);
    if (isNaN(number)) {
        elem.value = '0';
        elem.closest('.input-group').querySelector('input[type="hidden"]').value = 0;
        return;
    }
    elem.value = number.toLocaleString('id-ID');
    elem.closest('.input-group').querySelector('input[type="hidden"]').value = number;
}

$(document).ready(function() {
    $('#supplier_select').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Supplier Penitip --'
    });

    $('#quick_product_select').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Ketik Nama / SKU Barang (20.000 Data) --',
        allowClear: true,
        width: '100%',
        ajax: {
            url: "{{ route('supplier_consignments.search_products') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 1
    }).on('select2:select', function (e) {
        selectedProductData = e.params.data;
        if (selectedProductData) {
            if (selectedProductData.cost_price > 0) {
                $('#quick_cost_raw').val(selectedProductData.cost_price);
                $('#quick_cost_display').val(Math.round(selectedProductData.cost_price).toLocaleString('id-ID'));
            }
            if (selectedProductData.price > 0) {
                $('#quick_price_raw').val(selectedProductData.price);
                $('#quick_price_display').val(Math.round(selectedProductData.price).toLocaleString('id-ID'));
            }
            $('#quick_qty').focus().select();
        }
    });

    $('#quick_qty, #quick_cost_display, #quick_price_display').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            addToCart();
        }
    });

    $('#btn-add-to-cart').on('click', function(e) {
        e.preventDefault();
        addToCart();
    });

    renderCartTable(); // Render initial cart items
});

function addToCart() {
    if (!selectedProductData || !selectedProductData.id) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih produk master terlebih dahulu dari pencarian barang.',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }

    const qty = parseInt($('#quick_qty').val(), 10);
    const cost = parseFloat($('#quick_cost_raw').val() || 0);
    const price = parseFloat($('#quick_price_raw').val() || 0);

    if (isNaN(qty) || qty <= 0) {
        alert('Jumlah Qty harus lebih besar dari 0.');
        return;
    }

    const existingIndex = cart.findIndex(item => item.product_id === selectedProductData.id);
    if (existingIndex !== -1) {
        cart[existingIndex].qty += qty;
        cart[existingIndex].cost = cost;
        cart[existingIndex].price = price;
    } else {
        cart.push({
            product_id: selectedProductData.id,
            sku: selectedProductData.sku,
            name: selectedProductData.name,
            qty: qty,
            cost: cost,
            price: price
        });
    }

    $('#quick_product_select').val(null).trigger('change');
    selectedProductData = null;
    $('#quick_qty').val(100);
    
    renderCartTable();
    $('#quick_product_select').select2('open');
}

function removeFromCart(index) {
    if (cart.length <= 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Minimal 1 item produk harus ada di keranjang.',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }
    cart.splice(index, 1);
    renderCartTable();
}

function updateCartQty(index, newQty) {
    const qty = parseInt(newQty, 10);
    if (!isNaN(qty) && qty > 0) {
        cart[index].qty = qty;
    }
    renderCartTable();
}

function renderCartTable() {
    const tbody = document.getElementById('cart-tbody');
    tbody.innerHTML = '';

    let totalQty = 0;
    let totalHpp = 0;
    let totalProfit = 0;

    if (cart.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                    Keranjang barang masih kosong.
                </td>
            </tr>
        `;
    } else {
        cart.forEach((item, index) => {
            const subtotalHpp = item.qty * item.cost;
            const profit = item.qty * (item.price - item.cost);

            totalQty += item.qty;
            totalHpp += subtotalHpp;
            totalProfit += profit;

            const rowHtml = `
                <tr>
                    <td class="ps-4 fw-bold text-muted">${index + 1}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.sku}</span></td>
                    <td class="fw-bold text-dark">${item.name}
                        <input type="hidden" name="items[${index}][master_product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${index}][unit_cost_price]" value="${item.cost}">
                        <input type="hidden" name="items[${index}][unit_selling_price]" value="${item.price}">
                    </td>
                    <td class="text-center">
                        <input type="number" 
                               name="items[${index}][qty_received]" 
                               class="form-control form-control-sm text-center fw-bold mx-auto" 
                               style="width: 90px;" 
                               value="${item.qty}" 
                               min="1" 
                               onchange="updateCartQty(${index}, this.value)">
                    </td>
                    <td class="text-end text-muted">Rp ${Math.round(item.cost).toLocaleString('id-ID')}</td>
                    <td class="text-end text-muted">Rp ${Math.round(item.price).toLocaleString('id-ID')}</td>
                    <td class="text-end fw-bold text-dark">Rp ${subtotalHpp.toLocaleString('id-ID')}</td>
                    <td class="text-center pe-4">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeFromCart(${index})" title="Hapus">
                            <i class="bi bi-trash fs-5"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', rowHtml);
        });
    }

    document.getElementById('cart-item-count').innerText = cart.length + ' Item';
    document.getElementById('big-total-qty').innerText = totalQty.toLocaleString() + ' PCS';
    document.getElementById('big-total-hpp').innerText = 'Rp ' + totalHpp.toLocaleString('id-ID');
    document.getElementById('big-total-profit').innerText = '+Rp ' + totalProfit.toLocaleString('id-ID');
}
</script>
@endpush
