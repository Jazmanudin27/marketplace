@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>Input Barang Jadi Konsinyasi Baru</h3>
            <p class="text-muted small mb-0">Form penerimaan barang titipan/outsourcing dari supplier ke dalam stok master produk.</p>
        </div>
        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan:</h6>
            <ul class="mb-0 ps-3">
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
            <!-- Header Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Supplier & Tanggal</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">No. Referensi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" value="{{ $refNumber }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Supplier (Pemilik Barang) <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} ({{ $supplier->contact_person ?: 'No Contact' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Tanggal Penerimaan <span class="text-danger">*</span></label>
                            <input type="date" name="consignment_date" class="form-control @error('consignment_date') is-invalid @enderror" value="{{ old('consignment_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Catatan / Keterangan</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Titipan Celana SMA L 100 Pcs disetor tgl 01 tiap bulan...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Info -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-primary"></i>Daftar Barang Titipan</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-item">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Baris Barang
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="table-items">
                                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                                    <tr>
                                        <th class="ps-3" style="width: 35%;">Produk Master <span class="text-danger">*</span></th>
                                        <th style="width: 15%;">Qty (PCS) <span class="text-danger">*</span></th>
                                        <th style="width: 22%;">Harga Titip/HPP (Rp) <span class="text-danger">*</span></th>
                                        <th style="width: 22%;">Harga Jual (Rp) <span class="text-danger">*</span></th>
                                        <th class="text-center pe-3" style="width: 6%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="item-rows">
                                    <!-- Dynamic Rows -->
                                </tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td class="ps-3">TOTAL REKAPITULASI</td>
                                        <td id="total-qty" class="text-primary fs-6">0 PCS</td>
                                        <td id="total-hpp" class="text-dark fs-6" colspan="2">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Penerimaan Konsinyasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td class="ps-3">
            <select name="items[{INDEX}][master_product_id]" class="form-select form-select-sm product-select" required onchange="onProductSelect(this)">
                <option value="">-- Pilih Produk Master --</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-price="{{ $p->price }}">
                        [{{ $p->sku }}] {{ $p->name }} (Stok: {{ $p->stock }})
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[{INDEX}][qty_received]" class="form-control form-control-sm qty-input" value="100" min="1" required oninput="calculateTotal()">
        </td>
        <td>
            <input type="number" name="items[{INDEX}][unit_cost_price]" class="form-control form-control-sm cost-input" value="80000" min="0" step="1000" required oninput="calculateTotal()">
        </td>
        <td>
            <input type="number" name="items[{INDEX}][unit_selling_price]" class="form-control form-control-sm price-input" value="100000" min="0" step="1000" required oninput="calculateTotal()">
        </td>
        <td class="text-center pe-3">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-row" onclick="removeRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
let rowIndex = 0;

function addRow() {
    const template = document.getElementById('row-template').innerHTML;
    const rowHtml = template.replaceAll('{INDEX}', rowIndex);
    document.getElementById('item-rows').insertAdjacentHTML('beforeend', rowHtml);
    rowIndex++;
    calculateTotal();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#item-rows tr');
    if (rows.length <= 1) {
        alert('Minimal satu produk harus diisikan.');
        return;
    }
    btn.closest('tr').remove();
    calculateTotal();
}

function onProductSelect(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const row = selectElem.closest('tr');
    if (selectedOption && selectedOption.dataset.cost) {
        const costInput = row.querySelector('.cost-input');
        const priceInput = row.querySelector('.price-input');
        if (costInput && parseFloat(selectedOption.dataset.cost) > 0) {
            costInput.value = parseFloat(selectedOption.dataset.cost);
        }
        if (priceInput && parseFloat(selectedOption.dataset.price) > 0) {
            priceInput.value = parseFloat(selectedOption.dataset.price);
        }
    }
    calculateTotal();
}

function calculateTotal() {
    let totalQty = 0;
    let totalHpp = 0;

    document.querySelectorAll('#item-rows tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value || 0);
        const cost = parseFloat(row.querySelector('.cost-input')?.value || 0);
        totalQty += qty;
        totalHpp += (qty * cost);
    });

    document.getElementById('total-qty').innerText = totalQty.toLocaleString() + ' PCS';
    document.getElementById('total-hpp').innerText = 'Rp ' + totalHpp.toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btn-add-item').addEventListener('click', addRow);
    addRow(); // Insert initial row
});
</script>
@endsection
