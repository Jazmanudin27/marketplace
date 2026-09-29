@extends('v2.layouts.app')

@section('title', 'Catat Stock Opname V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-clipboard-check text-warning fs-5"></i> Catat Stock Opname V2
        </h1>
    </div>
    <div>
        <a href="{{ route('v2.stock_opname.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ── Error Notification ── --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Gagal menyimpan Stock Opname:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('v2.stock_opname.store') }}" method="POST" id="createStockOpnameForm">
    @csrf

    <div class="row g-3">
        {{-- Kiri: Informasi Sesi Opname --}}
        <div class="col-12 col-lg-3">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                    <i class="bi bi-file-earmark-text text-warning me-1.5"></i> Informasi Opname
                </h6>

                <!-- Tanggal Opname -->
                <div class="mb-1">
                    <label class="form-label small fw-semibold text-dark mb-1">Tanggal Opname <span class="text-danger">*</span></label>
                    <input type="date" name="opname_date" class="form-control form-control-sm @error('opname_date') is-invalid @enderror" value="{{ old('opname_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Petugas PIC -->
                <div class="mb-1">
                    <label class="form-label small fw-semibold text-dark mb-1">Petugas / PIC <span class="text-danger">*</span></label>
                    <input type="text" name="pic" class="form-control form-control-sm @error('pic') is-invalid @enderror" value="{{ old('pic', Auth::user()->name) }}" placeholder="Nama Petugas Opname" required>
                </div>

                <!-- Catatan -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Catatan / Alasan Opname</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Contoh: Audit stok bulanan gudang...">{{ old('notes') }}</textarea>
                </div>

                <!-- Total Summary Box -->
                <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding: 10px 12px;">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                        <span>Jumlah Baris Item:</span>
                        <strong id="totalItemCountDisplay" class="text-dark">0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">Total Akumulasi Selisih:</span>
                        <strong id="totalDiffDisplay" class="text-warning fs-6 fw-bold font-monospace">0 Qty</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Form Rincian Barang Diopname --}}
        <div class="col-12 col-lg-9">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-boxes text-warning me-1.5"></i> Rincian Item yang Diopname
                    </h6>
                    <button type="button" class="btn btn-sm text-white fw-semibold" id="btnAddRow" style="background:#d97706; border:none;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                    </button>
                </div>

                <div class="table-responsive flex-grow-1 mb-2">
                    <table class="table table-bordered align-middle" id="itemsTable" style="font-size: 0.8rem;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th style="width: 140px;">KATEGORI</th>
                                <th style="min-width: 220px;">PILIH BARANG / PRODUK <span class="text-danger">*</span></th>
                                <th style="width: 110px;" class="text-center">STOK SISTEM</th>
                                <th style="width: 110px;" class="text-center">STOK FISIK <span class="text-danger">*</span></th>
                                <th style="width: 110px;" class="text-center">SELISIH</th>
                                <th style="width: 40px;" class="text-center"><i class="bi bi-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <!-- Dynamic rows appended via Javascript -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="{{ route('v2.stock_opname.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4" style="background:#d97706; border:none;">
                        <i class="bi bi-save me-1"></i> Simpan Stock Opname
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
    const inventoryItems = @json($inventoryItems);
    const masterProducts = @json($masterProducts);
    const tbody = document.getElementById('itemsTableBody');
    const btnAddRow = document.getElementById('btnAddRow');

    function createRow() {
        const tr = document.createElement('tr');
        const index = tbody.children.length;

        tr.innerHTML = `
            <td>
                <select name="items[${index}][item_type]" class="form-select form-select-sm item-type-select" required>
                    <option value="inventory">Master Barang</option>
                    <option value="product">Master Produk</option>
                </select>
            </td>
            <td>
                <select name="items[${index}][item_id]" class="form-select form-select-sm item-select" required>
                    <!-- Populated dynamically -->
                </select>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border px-2 py-1 fw-bold system-stock-display">0</span>
            </td>
            <td>
                <input type="number" name="items[${index}][actual_stock]" class="form-control form-control-sm text-center actual-stock-input" min="0" step="any" value="0" required>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border px-2 py-1 fw-bold diff-display">0</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1.5 remove-row-btn" title="Hapus Baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const typeSelect = tr.querySelector('.item-type-select');
        const itemSelect = tr.querySelector('.item-select');
        const actualInput = tr.querySelector('.actual-stock-input');
        const removeBtn   = tr.querySelector('.remove-row-btn');

        function populateItems() {
            const type = typeSelect.value;
            let optionsHtml = '<option value="">-- Pilih Items --</option>';

            if (type === 'inventory') {
                inventoryItems.forEach(item => {
                    optionsHtml += `<option value="${item.id}" data-stock="${item.stock || 0}" data-unit="${item.unit || ''}">[${item.sku || 'BRG'}] ${item.name} (${item.unit})</option>`;
                });
            } else {
                masterProducts.forEach(prod => {
                    optionsHtml += `<option value="${prod.id}" data-stock="${prod.stock || 0}" data-unit="Pcs">[${prod.sku || 'PRD'}] ${prod.name}</option>`;
                });
            }

            itemSelect.innerHTML = optionsHtml;
            calculateRowDiff(tr);
        }

        typeSelect.addEventListener('change', populateItems);
        itemSelect.addEventListener('change', () => calculateRowDiff(tr));
        actualInput.addEventListener('input', () => calculateRowDiff(tr));

        removeBtn.addEventListener('click', function() {
            if (tbody.children.length > 1) {
                tr.remove();
                reindexRows();
                calculateGrandSummary();
            } else {
                alert('Stock Opname minimal harus memiliki 1 item.');
            }
        });

        populateItems();
    }

    function calculateRowDiff(tr) {
        const itemSelect = tr.querySelector('.item-select');
        const actualInput = tr.querySelector('.actual-stock-input');
        const systemDisplay = tr.querySelector('.system-stock-display');
        const diffDisplay = tr.querySelector('.diff-display');

        const selectedOpt = itemSelect.options[itemSelect.selectedIndex];
        let systemStock = 0;
        let unit = '';

        if (selectedOpt && selectedOpt.dataset.stock !== undefined) {
            systemStock = parseFloat(selectedOpt.dataset.stock) || 0;
            unit = selectedOpt.dataset.unit || '';
            systemDisplay.textContent = systemStock.toLocaleString('id-ID') + ' ' + unit;
        } else {
            systemDisplay.textContent = '0';
        }

        const actualStock = parseFloat(actualInput.value) || 0;
        const diff = actualStock - systemStock;

        if (diff > 0) {
            diffDisplay.textContent = '+' + diff.toLocaleString('id-ID');
            diffDisplay.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold';
        } else if (diff < 0) {
            diffDisplay.textContent = diff.toLocaleString('id-ID');
            diffDisplay.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-bold';
        } else {
            diffDisplay.textContent = '0';
            diffDisplay.className = 'badge bg-light text-dark border px-2 py-1 fw-bold';
        }

        calculateGrandSummary();
    }

    function calculateGrandSummary() {
        let totalCount = 0;
        let totalDiff = 0;

        document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
            const itemSelect = tr.querySelector('.item-select');
            const actualInput = tr.querySelector('.actual-stock-input');
            const selectedOpt = itemSelect.options[itemSelect.selectedIndex];

            if (selectedOpt && selectedOpt.value) {
                totalCount++;
                const systemStock = parseFloat(selectedOpt.dataset.stock) || 0;
                const actualStock = parseFloat(actualInput.value) || 0;
                totalDiff += (actualStock - systemStock);
            }
        });

        document.getElementById('totalItemCountDisplay').textContent = totalCount;
        const diffSign = totalDiff > 0 ? '+' : '';
        document.getElementById('totalDiffDisplay').textContent = diffSign + totalDiff.toLocaleString('id-ID') + ' Qty';
    }

    function reindexRows() {
        Array.from(tbody.children).forEach((tr, index) => {
            tr.querySelector('.item-type-select').name = `items[${index}][item_type]`;
            tr.querySelector('.item-select').name = `items[${index}][item_id]`;
            tr.querySelector('.actual-stock-input').name = `items[${index}][actual_stock]`;
        });
    }

    btnAddRow.addEventListener('click', () => createRow());

    // Add initial row
    createRow();
});
</script>
@endpush
