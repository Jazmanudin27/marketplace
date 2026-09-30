@extends('v2.layouts.app')

@section('title', 'Catat Pengeluaran Barang Keluar V2')

@section('content')

    {{-- ── Page Header ── --}}
    <div class="v2-page-header align-items-center mb-3">
        <div>
            <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Catat Pengeluaran Barang (Keluar)
            </h1>
        </div>
        <div>
            <a href="{{ route('v2.barang_keluar.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold"
                style="background:#64748b; border:none;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- ── Alert Error ── --}}
    @if (session('error') || $errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert"
            style="border-radius:10px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>{{ session('error') ?? 'Gagal menyimpan pengeluaran barang:' }}</strong>
            @if ($errors->any())
                <ul class="mb-0 mt-1 ps-3 small">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('v2.barang_keluar.store') }}" method="POST" id="createGoodsIssueForm">
        @csrf

        <div class="row g-3">
            {{-- Kiri: Form Header Transaksi --}}
            <div class="col-12 col-lg-3">
                <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                        <i class="bi bi-file-earmark-text text-danger me-1.5"></i> Informasi Pengeluaran
                    </h6>

                    <!-- Tanggal Transaksi -->
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-dark mb-1">Tanggal Transaksi <span
                                class="text-danger">*</span></label>
                        <input type="date" name="mutation_date"
                            class="form-control form-control-sm @error('mutation_date') is-invalid @enderror"
                            value="{{ old('mutation_date', date('Y-m-d')) }}" required>
                    </div>

                    <!-- Tujuan Departemen -->
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-dark mb-1">Tujuan Pengeluaran <span
                                class="text-danger">*</span></label>
                        <select name="tujuan" class="form-select form-select-sm @error('tujuan') is-invalid @enderror"
                            required>
                            <option value="produksi" {{ old('tujuan') === 'produksi' ? 'selected' : '' }}>Departemen
                                Produksi</option>
                            <option value="percetakan" {{ old('tujuan') === 'percetakan' ? 'selected' : '' }}>Departemen
                                Percetakan / Printing</option>
                            <option value="retur" {{ old('tujuan') === 'retur' ? 'selected' : '' }}>Retur / Pengembalian
                                Barang</option>
                            @foreach ($departments as $dept)
                                @if (!in_array(strtolower($dept->name), ['produksi', 'percetakan', 'retur']))
                                    <option value="{{ $dept->id }}" {{ old('tujuan') == $dept->id ? 'selected' : '' }}>
                                        Departemen {{ $dept->name }}</option>
                                @endif
                            @endforeach
                            <option value="lain_lain" {{ old('tujuan') === 'lain_lain' ? 'selected' : '' }}>Lain-lain /
                                Pemakaian Bebas</option>
                        </select>
                    </div>

                    <!-- Catatan / Referensi -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan SPK</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                            placeholder="Masukkan alasan pengeluaran atau nomor referensi SPK...">{{ old('notes') }}</textarea>
                    </div>

                    <!-- Total Summary Box -->
                    <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding: 10px 12px;">
                        <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                            <span>Total Qty Barang:</span>
                            <strong id="totalQtyDisplay" class="text-dark">0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark small">Total Nilai HPP:</span>
                            <strong id="totalAmountDisplay" class="text-danger fs-6 fw-bold font-monospace">Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: Form Item Barang Keluar --}}
            <div class="col-12 col-lg-9">
                <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-boxes text-danger me-1.5"></i> Rincian Barang yang Dikeluarkan
                        </h6>
                        <button type="button" class="btn btn-sm text-white fw-semibold" id="btnAddRow"
                            style="background:#dc2626; border:none;">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="table-responsive flex-grow-1 mb-2">
                        <table class="table table-bordered align-middle" id="itemsTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th style="width: 45%;">PILIH BARANG <span class="text-danger">*</span></th>
                                    <th style="width: 15%;" class="text-center">SISA STOK</th>
                                    <th style="width: 15%;" class="text-center">QTY KELUAR <span
                                            class="text-danger">*</span></th>
                                    <th style="width: 21%;" class="text-end">ESTIMASI HPP (RP)</th>
                                    <th style="width: 4%;" class="text-center"><i class="bi bi-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Dynamic rows appended via Javascript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('v2.barang_keluar.index') }}" class="btn btn-sm text-white fw-semibold"
                            style="background:#64748b; border:none;">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4"
                            style="background:#dc2626; border:none;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Simpan Pengeluaran Barang
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
            const tbody = document.getElementById('itemsTableBody');
            const btnAddRow = document.getElementById('btnAddRow');

            function createRow(itemId = '', qty = 1) {
                const tr = document.createElement('tr');

                let optionsHtml = '<option value="">-- Pilih Barang Stok --</option>';
                inventoryItems.forEach(item => {
                    const selected = (item.id == itemId) ? 'selected' : '';
                    optionsHtml +=
                        `<option value="${item.id}" data-stock="${item.stock || 0}" data-price="${item.cost_price || 0}" data-unit="${item.unit || ''}" ${selected}>[${item.sku || 'BRG'}] ${item.name} (Stok: ${item.stock} ${item.unit})</option>`;
                });

                tr.innerHTML = `
            <td>
                <select name="items[${tbody.children.length}][item_id]" class="form-select form-select-sm item-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border px-2 py-1 fw-bold stock-display">-</span>
            </td>
            <td>
                <input type="number" name="items[${tbody.children.length}][quantity]" class="form-control form-control-sm text-center qty-input" min="0.01" step="any" value="${qty}" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end font-monospace subtotal-display" readonly value="Rp 0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1.5 remove-row-btn" title="Hapus Baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

                tbody.appendChild(tr);

                if (window.initV2Select2) {
                    window.initV2Select2(tr);
                }

                // Bind events
                const select = tr.querySelector('.item-select');
                const qtyInput = tr.querySelector('.qty-input');
                const removeBtn = tr.querySelector('.remove-row-btn');

                const handleSelectChange = function() {
                    calculateRowSubtotal(tr);
                };

                select.addEventListener('change', handleSelectChange);
                $(select).on('select2:select change', handleSelectChange);

                qtyInput.addEventListener('input', () => calculateRowSubtotal(tr));

                removeBtn.addEventListener('click', function() {
                    if (tbody.children.length > 1) {
                        tr.remove();
                        reindexRows();
                        calculateGrandTotal();
                    } else {
                        alert('Pengeluaran barang minimal harus memiliki 1 item.');
                    }
                });

                calculateRowSubtotal(tr);
            }

            function calculateRowSubtotal(tr) {
                const select = tr.querySelector('.item-select');
                const qtyInput = tr.querySelector('.qty-input');
                const stockDisplay = tr.querySelector('.stock-display');
                const subtotalDisplay = tr.querySelector('.subtotal-display');

                const selectedOpt = select.options[select.selectedIndex];
                let maxStock = 0;
                let price = 0;
                let unit = '';

                if (selectedOpt && selectedOpt.dataset.stock) {
                    maxStock = parseFloat(selectedOpt.dataset.stock) || 0;
                    price = parseFloat(selectedOpt.dataset.price) || 0;
                    unit = selectedOpt.dataset.unit || '';
                    stockDisplay.textContent = maxStock.toLocaleString('id-ID') + ' ' + unit;
                    stockDisplay.className = maxStock > 0 ?
                        'badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold' :
                        'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-bold';
                } else {
                    stockDisplay.textContent = '-';
                    stockDisplay.className = 'badge bg-light text-dark border px-2 py-1 fw-bold';
                }

                const qty = parseFloat(qtyInput.value) || 0;
                const subtotal = qty * price;
                subtotalDisplay.value = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');

                if (maxStock > 0 && qty > maxStock) {
                    qtyInput.classList.add('is-invalid');
                } else {
                    qtyInput.classList.remove('is-invalid');
                }

                calculateGrandTotal();
            }

            function calculateGrandTotal() {
                let totalQty = 0;
                let totalAmount = 0;

                document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
                    const select = tr.querySelector('.item-select');
                    const selectedOpt = select.options[select.selectedIndex];
                    const price = selectedOpt && selectedOpt.dataset.price ? parseFloat(selectedOpt.dataset
                        .price) : 0;
                    const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;

                    totalQty += qty;
                    totalAmount += (qty * price);
                });

                document.getElementById('totalQtyDisplay').textContent = totalQty.toLocaleString('id-ID');
                document.getElementById('totalAmountDisplay').textContent = 'Rp ' + Math.round(totalAmount)
                    .toLocaleString('id-ID');
            }

            function reindexRows() {
                Array.from(tbody.children).forEach((tr, index) => {
                    tr.querySelector('.item-select').name = `items[${index}][item_id]`;
                    tr.querySelector('.qty-input').name = `items[${index}][quantity]`;
                });
            }

            btnAddRow.addEventListener('click', () => createRow());

            // Initial row
            createRow();
        });
    </script>
@endpush
