@extends('layouts.app')

@section('title', 'Input Pesanan Manual (PO)')
@section('page-title', 'Input Pesanan Manual (PO)')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-file-invoice me-2"></i>Formulir Input Pesanan Manual
                        </h5>
                        <small class="text-white-50">Pesanan manual akan tercatat dengan status Ready to Ship untuk antrean
                            SPK</small>
                    </div>
                    <a href="{{ route('production_orders.requirements') }}"
                        class="btn btn-outline-light btn-sm px-3 fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <form action="{{ route('orders.store') }}" method="POST" id="manual-order-form">
                    @csrf
                    <div class="card-body p-4">
                        <!-- Section 1: Informasi Toko & No Pesanan -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label form-label-sm fw-semibold">Akun / Toko Tujuan <span
                                        class="text-danger">*</span></label>
                                <select name="store_id" class="form-select form-select-sm select2" required
                                    style="width: 100%;">
                                    <option value="">-- Pilih Toko --</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}">
                                            {{ $store->store_name }} ({{ $store->channel->name ?? 'Marketplace' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm fw-semibold">No. Invoice / No. Pesanan (PO) <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="invoice_number" class="form-control form-control-sm" required
                                    placeholder="Contoh: INV/2026/0012">
                            </div>
                        </div>

                        <!-- Section 2: Informasi Pembeli -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label form-label-sm fw-semibold">Nama Pembeli <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="buyer_name" class="form-control form-control-sm" required
                                    placeholder="Nama lengkap pembeli">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm fw-semibold">No. HP Pembeli</label>
                                <input type="text" name="buyer_phone" class="form-control form-control-sm"
                                    placeholder="Contoh: 08123456789">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-label-sm fw-semibold">Alamat Pengiriman</label>
                                <textarea name="shipping_address" class="form-control form-control-sm" rows="2"
                                    placeholder="Alamat lengkap tujuan pengiriman..."></textarea>
                            </div>
                        </div>

                        <hr class="my-4 text-muted">

                        <!-- Section 3: Item Pesanan -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-boxes me-2 text-primary"></i>Daftar Item Produk Dipesan
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btn-add-item">
                                <i class="fas fa-plus me-1"></i> Tambah Item
                            </button>
                        </div>

                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm table-bordered table-striped align-middle mb-0"
                                id="table-order-items">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                        <th class="text-center" style="width: 5%">No</th>
                                        <th style="width: 50%">Nama Produk Master</th>
                                        <th class="text-center" style="width: 12%">Qty (PCS)</th>
                                        <th class="text-end" style="width: 15%">Harga Satuan (Rp)</th>
                                        <th class="text-end" style="width: 15%">Subtotal (Rp)</th>
                                        <th class="text-center" style="width: 8%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item-row">
                                        <td class="text-center text-muted small row-num">1</td>
                                        <td>
                                            <select name="items[0][master_product_id]"
                                                class="form-select form-select-sm product-select select2" required
                                                style="width:100%">
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}" data-price="{{ $p->price }}">
                                                        {{ $p->sku ? '[' . $p->sku . '] ' : '' }}{{ $p->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][quantity]"
                                                class="form-control form-control-sm text-center qty-input" value="1"
                                                min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][price]"
                                                class="form-control form-control-sm text-end price-input" value="0"
                                                min="0" required>
                                        </td>
                                        <td class="text-end font-monospace small fw-bold row-subtotal">Rp 0</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row"
                                                disabled>
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="4" class="text-end">GRAND TOTAL:</td>
                                        <td class="text-end font-monospace text-success fs-6" id="order-grand-total">Rp 0
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-light py-3 px-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('production_orders.requirements') }}"
                            class="btn btn-sm btn-secondary px-3">Batal</a>
                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Simpan Pesanan (PO)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let rowIndex = 1;

            // Initialize select2 on dynamic select elements
            function initSelect2(element) {
                if ($(element).hasClass('select2-hidden-accessible')) {
                    return;
                }
                $(element).select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $(element).parent()
                });
            }

            // Init first row select
            initSelect2('.product-select');

            $('#btn-add-item').on('click', function() {
                const tbody = $('#table-order-items tbody');
                const newRow = $(`
            <tr class="item-row">
                <td class="text-center text-muted small row-num">${rowIndex + 1}</td>
                <td>
                    <select name="items[${rowIndex}][master_product_id]" class="form-select form-select-sm product-select" required style="width:100%">
                        <option value="">-- Pilih Produk --</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->sku ? '[' . $p->sku . '] ' : '' }}{{ $p->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm text-center qty-input" value="1" min="1" required>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][price]" class="form-control form-control-sm text-end price-input" value="0" min="0" required>
                </td>
                <td class="text-end font-monospace small fw-bold row-subtotal">Rp 0</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `);
                tbody.append(newRow);

                // Init select2 for new row dropdown
                initSelect2(newRow.find('.product-select'));

                rowIndex++;
                updateRowNumbers();
                recalculateTotals();
            });

            $(document).on('click', '.btn-remove-row', function() {
                $(this).closest('tr').remove();
                updateRowNumbers();
                recalculateTotals();
            });

            $(document).on('change', '.product-select', function() {
                const selected = $(this).find('option:selected');
                const price = parseFloat(selected.data('price') || 0);
                $(this).closest('tr').find('.price-input').val(price);
                recalculateTotals();
            });

            $(document).on('input', '.qty-input, .price-input', function() {
                recalculateTotals();
            });

            function updateRowNumbers() {
                $('#table-order-items tbody tr').each(function(index) {
                    $(this).find('.row-num').text(index + 1);
                });

                // Enable/Disable delete button depending on row count
                const rowCount = $('#table-order-items tbody tr').length;
                if (rowCount <= 1) {
                    $('.btn-remove-row').prop('disabled', true);
                } else {
                    $('.btn-remove-row').prop('disabled', false);
                }
            }

            function recalculateTotals() {
                let grandTotal = 0;

                $('#table-order-items tbody tr').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val() || 0);
                    const price = parseFloat($(this).find('.price-input').val() || 0);
                    const subtotal = qty * price;

                    $(this).find('.row-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
                    grandTotal += subtotal;
                });

                $('#order-grand-total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
            }
        });
    </script>
@endpush
