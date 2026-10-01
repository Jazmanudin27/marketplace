@extends('v2.layouts.app')

@section('title', old('type', $selectedType) === 'out' ? 'Catat Mutasi Barang Keluar (Gudang Jadi)' : 'Catat Mutasi Barang Masuk (Gudang Jadi)')

@push('styles')
<style>
/* ── Mutasi Gudang Jadi V2 Create Styling ── */
.gj-badge-in {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.gj-badge-out {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.select2-container--bootstrap-5 .select2-selection--single {
    height: 31px !important;
    padding: 2px 8px !important;
    font-size: 0.8rem !important;
    width: 100% !important;
    max-width: 100% !important;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 25px !important;
    font-size: 0.8rem !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    max-width: 100% !important;
}
.select2-container {
    width: 100% !important;
    max-width: 100% !important;
}
.select2-dropdown {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}
.select2-results__option {
    white-space: normal !important;
    word-break: break-word !important;
    word-wrap: break-word !important;
    font-size: 0.78rem !important;
    padding: 6px 10px !important;
    line-height: 1.35 !important;
}
</style>
@endpush

@section('content')

@php
    $isOutbound = old('type', $selectedType) === 'out';
    $backRoute  = $isOutbound ? route('v2.gudang_jadi.keluar') : route('v2.gudang_jadi.masuk');
@endphp

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0" id="pageTitleHeading">
            @if($isOutbound)
                <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Catat Mutasi Barang Keluar (Gudang Jadi)
            @else
                <i class="bi bi-box-arrow-in-down text-success fs-5"></i> Catat Mutasi Barang Masuk (Gudang Jadi)
            @endif
        </h1>
        <p class="text-muted small mb-0">Input mutasi penambahan atau pengeluaran barang jadi secara langsung</p>
    </div>
    <div>
        <a href="{{ $backRoute }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ── Error Notification ── --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Gagal menyimpan mutasi gudang:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('v2.gudang_jadi.store') }}" method="POST" id="gudangJadiForm">
    @csrf

    <div class="row g-3">
        {{-- Kiri: Form Informasi Mutasi --}}
        <div class="col-12 col-lg-3">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-file-earmark-text text-primary me-1.5"></i> Informasi Mutasi</span>
                    <span id="typeBadgeHeader" class="badge {{ $isOutbound ? 'gj-badge-out' : 'gj-badge-in' }} px-2 py-1">
                        {{ $isOutbound ? 'Barang Keluar' : 'Barang Masuk' }}
                    </span>
                </h6>

                <!-- Jenis Mutasi -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Jenis Mutasi <span class="text-danger">*</span></label>
                    <select name="type" id="typeSelect" class="form-select form-select-sm fw-bold @error('type') is-invalid @enderror" required>
                        <option value="in" {{ old('type', $selectedType) === 'in' ? 'selected' : '' }}>🟢 Barang Masuk (+ Tambah Stok)</option>
                        <option value="out" {{ old('type', $selectedType) === 'out' ? 'selected' : '' }}>🔴 Barang Keluar (- Kurang Stok)</option>
                    </select>
                </div>

                <!-- Tanggal Mutasi -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Tanggal Mutasi <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control form-control-sm @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>

                <!-- Kategori / Alasan Mutasi -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Kategori / Alasan <span class="text-danger">*</span></label>
                    <input type="text" name="category_reason" id="categoryReasonInput" list="categoryReasonOptions" class="form-control form-control-sm @error('category_reason') is-invalid @enderror" value="{{ old('category_reason', $isOutbound ? 'Pengiriman SPK / Customer' : 'Hasil Produksi Internal') }}" placeholder="Ketik atau pilih alasan mutasi..." required>
                    <datalist id="categoryReasonOptions">
                        <option value="Hasil Produksi Internal"></option>
                        <option value="Penerimaan Subkon / Percetakan"></option>
                        <option value="Pengembalian (Retur Toko / Customer)"></option>
                        <option value="Pengiriman SPK / Customer"></option>
                        <option value="Sample Toko / Promosi"></option>
                        <option value="Penyesuaian Stok Gudang"></option>
                    </datalist>
                </div>

                <!-- Catatan / No. Referensi Dokumen -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Ref / Catatan (Opsional)</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Masukkan No. SPK, No. SJ, atau keterangan mutasi...">{{ old('notes') }}</textarea>
                </div>

                <!-- Summary Box Container -->
                <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding: 10px 12px;">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                        <span>Total Jenis Produk:</span>
                        <strong id="totalProductCountDisplay" class="text-dark">0 Jenis</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">Total Qty Mutasi:</span>
                        <strong id="totalQtyDisplay" class="fs-6 fw-bold font-monospace {{ $isOutbound ? 'text-danger' : 'text-success' }}">0 PCS</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Form Item Mutasi Gudang --}}
        <div class="col-12 col-lg-9">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-boxes text-primary me-1.5"></i> Rincian Produk Master Gudang
                    </h6>
                    <button type="button" class="btn btn-sm text-white fw-semibold" id="btnAddRow" style="background:#16a34a; border:none;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris Produk
                    </button>
                </div>

                <div class="table-responsive flex-grow-1 mb-2" style="overflow-x: hidden; overflow-y: visible;">
                    <table class="table table-bordered align-middle m-0" id="itemsTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th style="width: 46%;">PRODUK MASTER <span class="text-danger">*</span></th>
                                <th style="width: 18%;" class="text-center">STOK SAAT INI</th>
                                <th style="width: 16%;" class="text-center">QTY MUTASI <span class="text-danger">*</span></th>
                                <th style="width: 16%;" class="text-center">CATATAN (OPT)</th>
                                <th style="width: 4%;" class="text-center"><i class="bi bi-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <!-- Dynamic rows appended via JS -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="{{ $backRoute }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                        Batal
                    </a>
                    <button type="submit" id="submitBtn" class="btn btn-sm text-white fw-semibold py-1.5 px-4" style="background: {{ $isOutbound ? '#dc2626' : '#16a34a' }}; border:none;">
                        <i class="bi bi-save me-1"></i> Simpan Mutasi Gudang
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const products = @json($products);
    const selectedProductId = @json($selectedProductId);
    const tbody = document.getElementById('itemsTableBody');
    const btnAddRow = document.getElementById('btnAddRow');
    const typeSelect = document.getElementById('typeSelect');
    const submitBtn = document.getElementById('submitBtn');
    const pageTitleHeading = document.getElementById('pageTitleHeading');
    const typeBadgeHeader = document.getElementById('typeBadgeHeader');
    const totalQtyDisplay = document.getElementById('totalQtyDisplay');
    const categoryReasonInput = document.getElementById('categoryReasonInput');

    // Handle Change Type Dynamic Styling
    typeSelect.addEventListener('change', function() {
        const isOut = this.value === 'out';
        if (isOut) {
            pageTitleHeading.innerHTML = `<i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Catat Mutasi Barang Keluar (Gudang Jadi)`;
            typeBadgeHeader.className = 'badge gj-badge-out px-2 py-1';
            typeBadgeHeader.textContent = 'Barang Keluar';
            submitBtn.style.background = '#dc2626';
            totalQtyDisplay.className = 'fs-6 fw-bold font-monospace text-danger';
            if (categoryReasonInput.value === 'Hasil Produksi Internal') {
                categoryReasonInput.value = 'Pengiriman SPK / Customer';
            }
        } else {
            pageTitleHeading.innerHTML = `<i class="bi bi-box-arrow-in-down text-success fs-5"></i> Catat Mutasi Barang Masuk (Gudang Jadi)`;
            typeBadgeHeader.className = 'badge gj-badge-in px-2 py-1';
            typeBadgeHeader.textContent = 'Barang Masuk';
            submitBtn.style.background = '#16a34a';
            totalQtyDisplay.className = 'fs-6 fw-bold font-monospace text-success';
            if (categoryReasonInput.value === 'Pengiriman SPK / Customer') {
                categoryReasonInput.value = 'Hasil Produksi Internal';
            }
        }
        calculateGrandTotal();
    });

    function createRow(productId = '', qty = 1, note = '') {
        const index = tbody.children.length;
        const tr = document.createElement('tr');
        
        let optionsHtml = '<option value="">-- Pilih Produk Master --</option>';
        products.forEach(p => {
            const selected = (p.id == productId) ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" data-stock="${p.stock}" data-unit="${p.unit || 'PCS'}" ${selected}>[${p.sku || 'PROD'}] ${p.name}</option>`;
        });

        tr.innerHTML = `
            <td>
                <select name="items[${index}][product_id]" class="form-select form-select-sm product-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td class="text-center font-monospace small current-stock-display text-muted fw-semibold">
                -
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="items[${index}][quantity]" class="form-control text-center fw-bold qty-input" min="1" step="1" value="${qty}" required>
                    <span class="input-group-text bg-light px-1.5 unit-label small">PCS</span>
                </div>
            </td>
            <td>
                <input type="text" name="items[${index}][notes]" class="form-control form-control-sm note-input" placeholder="Catatan item..." value="${note}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1.5 remove-row-btn" title="Hapus Baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const select = tr.querySelector('.product-select');
        const qtyInput = tr.querySelector('.qty-input');
        const stockDisplay = tr.querySelector('.current-stock-display');
        const unitLabel = tr.querySelector('.unit-label');
        const removeBtn = tr.querySelector('.remove-row-btn');

        // Initialize Select2 if available
        if (window.initV2Select2) {
            window.initV2Select2(tr);
            $(select).on('select2:select change', function() {
                updateStockDisplay();
                calculateGrandTotal();
            });
        } else if (typeof $ !== 'undefined' && $.fn.select2) {
            const $select = $(select);
            if (!$select.parent().hasClass('select2-wrapper')) {
                $select.wrap('<div class="select2-wrapper position-relative d-block w-100" style="max-width: 100%;"></div>');
            }
            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $select.parent(),
                placeholder: '-- Pilih Produk Master --'
            }).on('change', function() {
                updateStockDisplay();
                calculateGrandTotal();
            });
        } else {
            select.addEventListener('change', function() {
                updateStockDisplay();
                calculateGrandTotal();
            });
        }

        function updateStockDisplay() {
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const stock = selectedOpt.getAttribute('data-stock') || 0;
                const unit = selectedOpt.getAttribute('data-unit') || 'PCS';
                stockDisplay.innerHTML = `<span class="badge bg-light text-dark border font-monospace px-2 py-1">${Number(stock).toLocaleString('id-ID')} ${unit}</span>`;
                unitLabel.textContent = unit;
            } else {
                stockDisplay.innerHTML = '-';
                unitLabel.textContent = 'PCS';
            }
        }

        qtyInput.addEventListener('input', calculateGrandTotal);

        removeBtn.addEventListener('click', function() {
            if (tbody.children.length > 1) {
                tr.remove();
                reindexRows();
                calculateGrandTotal();
            } else {
                alert('Mutasi gudang minimal harus memiliki 1 item produk.');
            }
        });

        updateStockDisplay();
    }

    function calculateGrandTotal() {
        let totalQty = 0;
        let productCount = 0;

        document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
            const select = tr.querySelector('.product-select');
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            if (select && select.value) {
                productCount++;
                totalQty += qty;
            }
        });

        const prefix = typeSelect.value === 'out' ? '-' : '+';
        document.getElementById('totalProductCountDisplay').textContent = productCount + ' Jenis';
        totalQtyDisplay.textContent = prefix + totalQty.toLocaleString('id-ID') + ' PCS';
    }

    function reindexRows() {
        Array.from(tbody.children).forEach((tr, index) => {
            const select = tr.querySelector('.product-select');
            const qtyInput = tr.querySelector('.qty-input');
            const noteInput = tr.querySelector('.note-input');
            if (select) select.name = `items[${index}][product_id]`;
            if (qtyInput) qtyInput.name = `items[${index}][quantity]`;
            if (noteInput) noteInput.name = `items[${index}][notes]`;
        });
    }

    btnAddRow.addEventListener('click', () => {
        createRow();
        calculateGrandTotal();
    });

    // Create initial row
    createRow(selectedProductId || '');
    calculateGrandTotal();
});
</script>
@endpush
