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
        <!-- Background Decorative Element -->
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
            <!-- Left Panel: Supplier & Tanggal (Step 1) -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                            <h6 class="fw-bold mb-0 text-dark">Informasi Supplier & Tanggal</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase">No. Referensi Konsinyasi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-start-0 fw-bold text-primary" value="{{ $refNumber }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Supplier (Pemilik Barang) <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="supplier_select" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Supplier Penitip --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} ({{ $supplier->phone ?: 'No Phone' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Tanggal Penerimaan <span class="text-danger">*</span></label>
                            <input type="date" name="consignment_date" class="form-control @error('consignment_date') is-invalid @enderror" value="{{ old('consignment_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Catatan / Keterangan</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="Contoh: Titipan Celana SMA L 100 Pcs disetor tgl 01 tiap bulan...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="p-3 bg-light rounded-3 border border-dashed mt-4">
                            <small class="text-muted d-block lh-sm">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                Setelah disetujui (Approved), stok produk master akan otomatis bertambah dan riwayat mutasi stok tercatat.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Items Table & Live Summary (Step 2) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
                            <h6 class="fw-bold mb-0 text-dark">Rincian Produk Titipan</h6>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" id="btn-add-item">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris Barang
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="table-items">
                                <thead class="bg-light text-muted small text-uppercase fw-bold">
                                    <tr>
                                        <th class="ps-4" style="width: 40%;">Cari Produk Master (Fast AJAX) <span class="text-danger">*</span></th>
                                        <th style="width: 15%;">Qty (PCS) <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Harga Titip (HPP) <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Harga Jual Toko <span class="text-danger">*</span></th>
                                        <th class="text-center pe-3" style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="item-rows">
                                    <!-- Dynamic Select2 Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary Box & Submit -->
                    <div class="card-footer bg-white border-top-0 p-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <div class="p-3 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25 d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.75rem;">Total Barang Titipan</small>
                                        <h4 class="fw-bold text-primary mb-0" id="summary-total-qty">0 PCS</h4>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.75rem;">Total HPP Modal</small>
                                        <h4 class="fw-bold text-dark mb-0" id="summary-total-hpp">Rp 0</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm w-100">
                                    <i class="bi bi-check-circle me-1"></i> Simpan Penerimaan Konsinyasi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template Row -->
<template id="row-template">
    <tr class="item-row">
        <td class="ps-4 py-3">
            <select name="items[{INDEX}][master_product_id]" class="form-select product-select-ajax" required>
                <option value="">-- Ketik Nama / SKU Barang (Searching 20rb Data) --</option>
            </select>
        </td>
        <td class="py-3">
            <input type="number" name="items[{INDEX}][qty_received]" class="form-control qty-input" value="100" min="1" required oninput="calculateTotals()">
        </td>
        <td class="py-3">
            <input type="number" name="items[{INDEX}][unit_cost_price]" class="form-control cost-input" value="80000" min="0" step="1000" required oninput="calculateTotals()">
        </td>
        <td class="py-3">
            <input type="number" name="items[{INDEX}][unit_selling_price]" class="form-control price-input" value="100000" min="0" step="1000" required oninput="calculateTotals()">
        </td>
        <td class="text-center pe-3 py-3">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-row" onclick="removeRow(this)" title="Hapus Baris">
                <i class="bi bi-trash fs-5"></i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script>
let rowIndex = 0;

function initSelect2(elem) {
    $(elem).select2({
        theme: 'bootstrap-5',
        placeholder: '-- Ketik Nama / SKU Produk --',
        allowClear: true,
        dropdownParent: $(elem).parent(),
        ajax: {
            url: "{{ route('supplier_consignments.search_products') }}",
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term // kata kunci pencarian dari user
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
        const row = this.closest('tr');
        if (data) {
            const costInput = row.querySelector('.cost-input');
            const priceInput = row.querySelector('.price-input');
            if (costInput && data.cost_price > 0) {
                costInput.value = data.cost_price;
            }
            if (priceInput && data.price > 0) {
                priceInput.value = data.price;
            }
        }
        calculateTotals();
    });
}

function addRow() {
    const template = document.getElementById('row-template').innerHTML;
    const rowHtml = template.replaceAll('{INDEX}', rowIndex);
    const container = document.getElementById('item-rows');
    container.insertAdjacentHTML('beforeend', rowHtml);
    
    const newSelect = container.querySelectorAll('.product-select-ajax')[container.querySelectorAll('.product-select-ajax').length - 1];
    initSelect2(newSelect);

    rowIndex++;
    calculateTotals();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#item-rows tr');
    if (rows.length <= 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Minimal satu baris produk konsinyasi harus diisikan.',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }
    btn.closest('tr').remove();
    calculateTotals();
}

function calculateTotals() {
    let totalQty = 0;
    let totalHpp = 0;

    document.querySelectorAll('#item-rows tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value || 0);
        const cost = parseFloat(row.querySelector('.cost-input')?.value || 0);
        totalQty += qty;
        totalHpp += (qty * cost);
    });

    document.getElementById('summary-total-qty').innerText = totalQty.toLocaleString() + ' PCS';
    document.getElementById('summary-total-hpp').innerText = 'Rp ' + totalHpp.toLocaleString('id-ID');
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
