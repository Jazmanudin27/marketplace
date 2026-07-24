@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Top Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white overflow-hidden position-relative">
        <div class="card-body p-4 position-relative z-1">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 mb-2 fw-semibold" style="letter-spacing: 0.5px;">
                        <i class="bi bi-box-seam me-1"></i> MODUL KONSINYASI SUPPLIER
                    </span>
                    <h2 class="fw-bold mb-1">Input Penerimaan Barang Konsinyasi</h2>
                    <p class="mb-0 text-white-50">Tambahkan persediaan barang titipan/outsourcing dari supplier ke dalam stok master produk dengan cepat & aman.</p>
                </div>
                <a href="{{ route('supplier_consignments.index') }}" class="btn btn-light rounded-pill px-4 shadow-sm text-primary fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="position-absolute end-0 bottom-0 opacity-10 pe-4 pb-2 d-none d-md-block pointer-events-none">
            <i class="bi bi-boxes" style="font-size: 10rem;"></i>
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

    <form action="{{ route('supplier_consignments.store') }}" method="POST" id="consignment-form">
        @csrf
        <div class="row g-4">
            <!-- 1. Header Info Card (Col-12 Full Width) -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                            <h6 class="fw-bold mb-0 text-dark">Informasi Supplier & Penerimaan</h6>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted text-uppercase">No. Referensi Konsinyasi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash text-muted"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 fw-bold text-primary" value="{{ $refNumber }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Supplier (Pemilik Barang) <span class="text-danger">*</span></label>
                                <select name="supplier_id" id="supplier_select" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Supplier Penitip --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }} ({{ $supplier->phone ?: 'No Contact' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Penerimaan <span class="text-danger">*</span></label>
                                <input type="date" name="consignment_date" class="form-control @error('consignment_date') is-invalid @enderror" value="{{ old('consignment_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="p-2 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25 w-100 text-center">
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">Status Awal</small>
                                    <span class="badge bg-warning text-dark fw-bold">PENDING APPROVAL</span>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Catatan / Keterangan Titipan</label>
                                <input type="text" name="notes" class="form-control" placeholder="Contoh: Titipan Celana SMA L 100 Pcs disetor tgl 01 tiap bulan..." value="{{ old('notes') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Rincian Produk Titipan (Card-Based Item Blocks with Col-12 Product Name) -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
                            <h6 class="fw-bold mb-0 text-dark">Daftar Barang Titipan (Nama Produk Col-12)</h6>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" id="btn-add-item">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Item Barang Baru
                        </button>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-50">
                        <div id="item-blocks-container" class="d-flex flex-column gap-3">
                            <!-- Dynamic Item Cards -->
                        </div>
                    </div>

                    <!-- Summary Cards & Submit Bar -->
                    <div class="card-footer bg-white border-top-0 p-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-lg-9 col-md-8">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Total Qty Masuk</small>
                                            <h4 class="fw-bold text-primary mb-0 mt-1" id="summary-total-qty">0 PCS</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Total HPP Modal (Setoran)</small>
                                            <h4 class="fw-bold text-dark mb-0 mt-1" id="summary-total-hpp">Rp 0</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25">
                                            <small class="text-success fw-bold text-uppercase d-block" style="font-size: 0.7rem;">Potensi Profit Toko</small>
                                            <h4 class="fw-bold text-success mb-0 mt-1" id="summary-total-profit">+Rp 0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4 text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-3 fw-bold shadow w-100 fs-6">
                                    <i class="bi bi-check-circle me-1"></i> Simpan Penerimaan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template Card Item (Col-12 Product Name + Detail Row Below) -->
<template id="item-card-template">
    <div class="card border border-light-subtle shadow-sm rounded-4 item-card position-relative bg-white overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill">
                    <i class="bi bi-box-seam me-1"></i> Item Barang #{ITEM_NUMBER}
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-pill px-3 py-1 btn-remove-item" onclick="removeItemCard(this)">
                    <i class="bi bi-trash me-1"></i> Hapus Item
                </button>
            </div>

            <!-- Baris 1: Nama Produk Master (Col-12 FULL WIDTH) -->
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold text-dark mb-1">
                        Cari & Pilih Produk Master (Nama Panjang / SKU) <span class="text-danger">*</span>
                    </label>
                    <select name="items[{INDEX}][master_product_id]" class="form-select product-select-ajax w-100" required>
                        <option value="">-- Ketik Nama / SKU Barang (Pencarian Panjang 20.000 Data) --</option>
                    </select>
                </div>

                <!-- Baris 2: Detail Qty, HPP, Harga Jual, & Subtotal -->
                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Qty Diterima (PCS) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="items[{INDEX}][qty_received]" class="form-control fw-bold qty-input text-center fs-6" value="100" min="1" required oninput="calculateTotals()">
                        <span class="input-group-text bg-light text-muted">PCS</span>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Harga Titip / HPP <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                        <input type="text" class="form-control fw-semibold cost-display-input fs-6" value="80.000" required oninput="formatRupiahInput(this); calculateTotals();">
                        <input type="hidden" name="items[{INDEX}][unit_cost_price]" class="cost-raw-input" value="80000">
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Harga Jual Toko <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                        <input type="text" class="form-control fw-semibold price-display-input fs-6" value="100.000" required oninput="formatRupiahInput(this); calculateTotals();">
                        <input type="hidden" name="items[{INDEX}][unit_selling_price]" class="price-raw-input" value="100000">
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Subtotal Modal HPP</label>
                    <div class="p-2 bg-light rounded-3 border text-end">
                        <span class="fw-bold text-dark fs-6 subtotal-hpp-cell">Rp 8.000.000</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

@endsection

@push('scripts')
<script>
let rowIndex = 0;

function formatRupiahInput(elem) {
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

function initSelect2(elem) {
    $(elem).select2({
        theme: 'bootstrap-5',
        placeholder: '-- Ketik Nama Panjang / SKU Produk (20rb Data) --',
        allowClear: true,
        width: '100%',
        dropdownParent: $(elem).parent(),
        ajax: {
            url: "{{ route('supplier_consignments.search_products') }}",
            dataType: 'json',
            delay: 300,
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
        const data = e.params.data;
        const card = this.closest('.item-card');
        if (data) {
            const costDisplay = card.querySelector('.cost-display-input');
            const costRaw     = card.querySelector('.cost-raw-input');
            const priceDisplay= card.querySelector('.price-display-input');
            const priceRaw    = card.querySelector('.price-raw-input');

            if (costDisplay && data.cost_price > 0) {
                costRaw.value = data.cost_price;
                costDisplay.value = Math.round(data.cost_price).toLocaleString('id-ID');
            }
            if (priceDisplay && data.price > 0) {
                priceRaw.value = data.price;
                priceDisplay.value = Math.round(data.price).toLocaleString('id-ID');
            }
        }
        calculateTotals();
    });
}

function addRow() {
    const template = document.getElementById('item-card-template').innerHTML;
    const itemNumber = document.querySelectorAll('.item-card').length + 1;
    let rowHtml = template.replaceAll('{INDEX}', rowIndex);
    rowHtml = rowHtml.replaceAll('{ITEM_NUMBER}', itemNumber);
    
    const container = document.getElementById('item-blocks-container');
    container.insertAdjacentHTML('beforeend', rowHtml);
    
    const newCard = container.lastElementChild;
    const newSelect = newCard.querySelector('.product-select-ajax');
    initSelect2(newSelect);

    rowIndex++;
    updateItemNumbers();
    calculateTotals();
}

function removeItemCard(btn) {
    const cards = document.querySelectorAll('.item-card');
    if (cards.length <= 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Minimal satu item produk konsinyasi harus diisikan.',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }
    btn.closest('.item-card').remove();
    updateItemNumbers();
    calculateTotals();
}

function updateItemNumbers() {
    document.querySelectorAll('.item-card').forEach((card, idx) => {
        const badge = card.querySelector('.badge');
        if (badge) {
            badge.innerHTML = `<i class="bi bi-box-seam me-1"></i> Item Barang #${idx + 1}`;
        }
    });
}

function calculateTotals() {
    let totalQty = 0;
    let totalHpp = 0;
    let totalProfit = 0;

    document.querySelectorAll('.item-card').forEach(card => {
        const qty   = parseFloat(card.querySelector('.qty-input')?.value || 0);
        const cost  = parseFloat(card.querySelector('.cost-raw-input')?.value || 0);
        const price = parseFloat(card.querySelector('.price-raw-input')?.value || 0);
        
        const subtotalHpp = (qty * cost);
        const profit = qty * (price - cost);

        const subtotalCell = card.querySelector('.subtotal-hpp-cell');
        if (subtotalCell) {
            subtotalCell.innerText = 'Rp ' + subtotalHpp.toLocaleString('id-ID');
        }

        totalQty += qty;
        totalHpp += subtotalHpp;
        totalProfit += profit;
    });

    document.getElementById('summary-total-qty').innerText = totalQty.toLocaleString() + ' PCS';
    document.getElementById('summary-total-hpp').innerText = 'Rp ' + totalHpp.toLocaleString('id-ID');
    document.getElementById('summary-total-profit').innerText = '+Rp ' + totalProfit.toLocaleString('id-ID');
}

$(document).ready(function() {
    $('#supplier_select').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Supplier Penitip --'
    });

    addRow(); // Tambahkan baris pertama secara otomatis
});
</script>
@endpush
