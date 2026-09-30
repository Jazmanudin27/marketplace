@extends('v2.layouts.app')

@section('title', 'Input Barang Titipan Baru V2')

@push('styles')
    <style>
        /* Select2 fix: dropdown tetap di dalam container */
        #quick-input-bar {
            position: relative;
        }

        #quick-input-bar .select2-container {
            width: 100% !important;
            min-width: 0 !important;
        }

        #quick-input-bar .select2-container .select2-selection {
            min-height: 31px;
        }

        #quick-input-bar .select2-dropdown {
            min-width: 320px;
            max-width: 500px;
        }
    </style>
@endpush

@section('content')

    {{-- ── Page Header ── --}}
    <div class="v2-page-header align-items-center mb-3">
        <div>
            <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-box-seam text-warning fs-5"></i> Input Barang Titipan Baru
            </h1>
        </div>
        <div>
            <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold"
                style="background:#64748b; border:none;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- ── Error Notification ── --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert"
            style="border-radius:10px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Gagal menyimpan data:</strong>
            <ul class="mb-0 mt-1 ps-3 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('supplier_consignments.store') }}" method="POST" id="consignment-form">
        @csrf

        <div class="row g-3">
            {{-- Kiri: Informasi Konsinyasi --}}
            <div class="col-12 col-lg-3">
                <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-file-earmark-text text-warning me-1.5"></i> Informasi Konsinyasi
                    </h6>

                    {{-- No. Referensi --}}
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">No. Referensi</label>
                        <input type="text"
                            class="form-control form-control-sm bg-light fw-bold font-monospace text-warning-emphasis"
                            value="{{ $refNumber }}" readonly>
                    </div>

                    {{-- Supplier --}}
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Supplier (Pemilik Barang) <span
                                class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_select"
                            class="form-select form-select-sm @error('supplier_id') is-invalid @enderror"
                            data-placeholder="-- Pilih Supplier Penitip --" required>
                            <option value="">-- Pilih Supplier Penitip --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} {{ $supplier->phone ? '(' . $supplier->phone . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal --}}
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Tanggal Penerimaan <span
                                class="text-danger">*</span></label>
                        <input type="date" name="consignment_date"
                            class="form-control form-control-sm @error('consignment_date') is-invalid @enderror"
                            value="{{ old('consignment_date', date('Y-m-d')) }}" required>
                    </div>

                    {{-- Catatan --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                            placeholder="Contoh: Titipan Celana SMA L 100 Pcs...">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Summary Box --}}
                    <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding:10px 12px;">
                        <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                            <span>Total Qty:</span>
                            <strong id="big-total-qty" class="text-dark">0 PCS</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-muted">Total Modal HPP:</span>
                            <strong id="big-total-hpp" class="text-warning-emphasis font-monospace fw-bold"
                                style="font-size:0.92rem;">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Potensi Profit:</span>
                            <strong id="big-total-profit" class="text-success font-monospace">+Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: Keranjang Barang --}}
            <div class="col-12 col-lg-9">
                <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                    {{-- Quick Input Bar --}}
                    <div class="border-bottom pb-2 mb-1">
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-barcode text-warning me-1.5"></i> Tambah Barang ke Keranjang
                            <small class="text-muted fw-normal ms-1" style="font-size:0.72rem;">(tekan Enter untuk langsung
                                tambah)</small>
                        </h6>
                        <div class="row g-2 align-items-end" id="quick-input-bar">
                            <div class="col-5 select2-wrapper position-relative">
                                <label class="form-label small text-muted mb-1 text-truncate d-block">Cari / Pilih Barang
                                    <span class="text-danger">*</span></label>
                                <select id="quick_product_select" class="form-select form-select-sm w-100 no-select2"
                                    data-placeholder="-- Ketik Nama / SKU Barang --">
                                    <option value="">-- Ketik Nama / SKU Barang --</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label class="form-label small text-muted mb-1 text-truncate d-block">Qty (PCS) <span
                                        class="text-danger">*</span></label>
                                <input type="number" id="quick_qty"
                                    class="form-control form-control-sm text-center fw-bold" value="100" min="1">
                            </div>
                            <div class="col-2">
                                <label class="form-label small text-muted mb-1 text-truncate d-block">Harga Titip (HPP)
                                    <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text text-muted border-end-0 px-2">Rp</span>
                                    <input type="text" id="quick_cost_display"
                                        class="form-control border-start-0 fw-bold" value="80.000"
                                        oninput="formatRupiahQuick(this)">
                                    <input type="hidden" id="quick_cost_raw" value="80000">
                                </div>
                            </div>
                            <div class="col-2">
                                <label class="form-label small text-muted mb-1 text-truncate d-block">Harga Jual Toko <span
                                        class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text text-muted border-end-0 px-2">Rp</span>
                                    <input type="text" id="quick_price_display"
                                        class="form-control border-start-0 fw-bold" value="100.000"
                                        oninput="formatRupiahQuick(this)">
                                    <input type="hidden" id="quick_price_raw" value="100000">
                                </div>
                            </div>
                            <div class="col-1">
                                <button type="button" id="btn-add-to-cart"
                                    class="btn btn-sm w-100 text-white fw-bold shadow-sm d-flex align-items-center justify-content-center"
                                    style="background:#f59e0b; border:none; height:31px;"
                                    title="Tambah ke Keranjang (Enter)">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tabel Keranjang --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark small"><i class="bi bi-cart3 text-warning me-1.5"></i> Daftar Item
                            (Keranjang)</span>
                        <span
                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-semibold"
                            id="cart-item-count" style="font-size:0.72rem;">0 Item</span>
                    </div>

                    <div class="table-responsive flex-grow-1 mb-2">
                        <table class="table table-hover align-middle" id="cart-table" style="font-size:0.8rem;">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="ps-2 py-2">NO</th>
                                    <th class="py-2">SKU</th>
                                    <th class="py-2">NAMA BARANG</th>
                                    <th class="text-center py-2">QTY</th>
                                    <th class="text-end py-2">HPP</th>
                                    <th class="text-end py-2">HARGA JUAL</th>
                                    <th class="text-end py-2">SUBTOTAL HPP</th>
                                    <th class="text-center py-2"><i class="bi bi-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="cart-tbody"></tbody>
                        </table>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm text-white fw-semibold"
                            style="background:#64748b; border:none;">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4 shadow-sm"
                            style="background:#f59e0b; border:none;">
                            <i class="bi bi-save me-1"></i> Simpan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        let cart = [];
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
            $('#quick_product_select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Ketik Nama / SKU Barang --',
                allowClear: true,
                dropdownParent: $('#quick_product_select').parent(),
                ajax: {
                    url: "{{ route('supplier_consignments.search_products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            }).on('select2:select', function(e) {
                selectedProductData = e.params.data;
                if (selectedProductData) {
                    if (selectedProductData.cost_price > 0) {
                        $('#quick_cost_raw').val(selectedProductData.cost_price);
                        $('#quick_cost_display').val(Math.round(selectedProductData.cost_price)
                            .toLocaleString('id-ID'));
                    }
                    if (selectedProductData.price > 0) {
                        $('#quick_price_raw').val(selectedProductData.price);
                        $('#quick_price_display').val(Math.round(selectedProductData.price).toLocaleString(
                            'id-ID'));
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
        });

        function addToCart() {
            if (!selectedProductData || !selectedProductData.id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Silakan pilih produk master terlebih dahulu.',
                    confirmButtonColor: '#f59e0b'
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
                    qty,
                    cost,
                    price
                });
            }

            $('#quick_product_select').val(null).trigger('change');
            selectedProductData = null;
            $('#quick_qty').val(100);
            renderCartTable();
            $('#quick_product_select').select2('open');
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCartTable();
        }

        function updateCartQty(index, newQty) {
            const qty = parseInt(newQty, 10);
            if (!isNaN(qty) && qty > 0) cart[index].qty = qty;
            renderCartTable();
        }

        function renderCartTable() {
            const tbody = document.getElementById('cart-tbody');
            tbody.innerHTML = '';
            let totalQty = 0,
                totalHpp = 0,
                totalProfit = 0;

            if (cart.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">
            <i class="bi bi-cart-x fs-3 d-block mb-2 text-secondary opacity-50"></i>
            Keranjang kosong. Pilih barang dan tekan <b>Enter</b> atau tombol <b>+</b>.
        </td></tr>`;
            } else {
                cart.forEach((item, index) => {
                    const subtotalHpp = item.qty * item.cost;
                    const profit = item.qty * (item.price - item.cost);
                    totalQty += item.qty;
                    totalHpp += subtotalHpp;
                    totalProfit += profit;

                    tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td class="ps-2 text-muted fw-semibold">${index + 1}</td>
                    <td><span class="badge bg-light text-secondary border font-monospace" style="font-size:0.68rem;">${item.sku}</span></td>
                    <td class="fw-semibold text-dark">${item.name}
                        <input type="hidden" name="items[${index}][master_product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${index}][unit_cost_price]" value="${item.cost}">
                        <input type="hidden" name="items[${index}][unit_selling_price]" value="${item.price}">
                    </td>
                    <td class="text-center">
                        <input type="number" name="items[${index}][qty_received]" class="form-control form-control-sm text-center fw-bold mx-auto" style="width:90px;" value="${item.qty}" min="1" onchange="updateCartQty(${index}, this.value)">
                    </td>
                    <td class="text-end text-muted font-monospace">Rp ${Math.round(item.cost).toLocaleString('id-ID')}</td>
                    <td class="text-end text-muted font-monospace">Rp ${Math.round(item.price).toLocaleString('id-ID')}</td>
                    <td class="text-end fw-bold text-dark font-monospace">Rp ${subtotalHpp.toLocaleString('id-ID')}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 py-0 px-1" onclick="removeFromCart(${index})">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>
            `);
                });
            }

            document.getElementById('cart-item-count').innerText = cart.length + ' Item';
            document.getElementById('big-total-qty').innerText = totalQty.toLocaleString() + ' PCS';
            document.getElementById('big-total-hpp').innerText = 'Rp ' + totalHpp.toLocaleString('id-ID');
            document.getElementById('big-total-profit').innerText = '+Rp ' + totalProfit.toLocaleString('id-ID');
        }
    </script>
@endpush
