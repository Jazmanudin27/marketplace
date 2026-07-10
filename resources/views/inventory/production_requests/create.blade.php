@extends('layouts.app')

@section('title', 'Buat Permintaan Produksi')
@section('page-title', 'Buat Permintaan Produksi')

@section('content')
<div class="mx-auto" style="max-width:860px">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">

        {{-- Header --}}
        <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
            <div>
                <h5 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2"></i>Buat Permintaan Produksi</h5>
                <small class="text-white-50">Pengajuan produksi baru untuk stok gudang atau pemenuhan PO pelanggan</small>
            </div>
            <a href="{{ route('production_requests.index') }}" class="btn btn-sm btn-light fw-semibold px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- Progress Steps --}}
        <div class="bg-white border-bottom px-4 pt-3 pb-2">
            <div class="d-flex align-items-center justify-content-center">

                {{-- Step 1 --}}
                <div class="d-flex flex-column align-items-center" id="step-dot-1">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-primary border-primary text-white"
                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-1">
                        <i class="fas fa-list fa-sm"></i>
                    </div>
                    <span class="text-uppercase fw-semibold text-primary mt-1" style="font-size:0.65rem;letter-spacing:0.06em;">Tipe</span>
                </div>

                <div class="flex-grow-1 border-top border-2 mx-2 mb-3" id="conn-1" style="max-width:80px;"></div>

                {{-- Step 2 --}}
                <div class="d-flex flex-column align-items-center" id="step-dot-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-light border-secondary text-secondary"
                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-2">
                        <i class="fas fa-user-tag fa-sm"></i>
                    </div>
                    <span class="text-uppercase fw-semibold text-secondary mt-1" id="step-label-2" style="font-size:0.65rem;letter-spacing:0.06em;">Pengaju & Detail</span>
                </div>

                <div class="flex-grow-1 border-top border-2 mx-2 mb-3 border-secondary" id="conn-2" style="max-width:80px;"></div>

                {{-- Step 3 --}}
                <div class="d-flex flex-column align-items-center" id="step-dot-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-light border-secondary text-secondary"
                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-3">
                        <i class="fas fa-boxes fa-sm"></i>
                    </div>
                    <span class="text-uppercase fw-semibold text-secondary mt-1" id="step-label-3" style="font-size:0.65rem;letter-spacing:0.06em;">Produk</span>
                </div>

                <div class="flex-grow-1 border-top border-2 mx-2 mb-3 border-secondary" id="conn-3" style="max-width:80px;"></div>

                {{-- Step 4 --}}
                <div class="d-flex flex-column align-items-center" id="step-dot-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold border border-2 bg-light border-secondary text-secondary"
                        style="width:40px;height:40px;font-size:0.95rem;" id="step-icon-4">
                        <i class="fas fa-clipboard-check fa-sm"></i>
                    </div>
                    <span class="text-uppercase fw-semibold text-secondary mt-1" id="step-label-4" style="font-size:0.65rem;letter-spacing:0.06em;">Review</span>
                </div>

            </div>
        </div>

        <form action="{{ route('production_requests.store') }}" method="POST" id="manual-order-form">
            @csrf

            <div class="card-body p-4">

                {{-- ========== STEP 1: Tipe Permintaan ========== --}}
                <div id="step-1">
                    <p class="text-uppercase text-muted fw-semibold mb-3 small">
                        <i class="fas fa-list me-1"></i> Tipe Permintaan Produksi
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Tipe Permintaan Produksi <span class="text-danger">*</span></label>
                            <select name="request_type" id="request_type" class="form-select form-select-sm" required>
                                <option value="Stok Gudang Jadi" selected>Stok Gudang Jadi (Untuk Persediaan)</option>
                                <option value="PO Pelanggan">PO Pelanggan (Pesanan Khusus Pelanggan)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">No. Invoice / Permintaan</label>
                            <input type="text" class="form-control form-control-sm bg-light" readonly value="[Otomatis Digenerate Sistem]">
                            <span class="text-muted small" style="font-size: 0.72rem;">Nomor permintaan produksi akan otomatis terbit dengan format <strong>REQ-YYYYMMDD-XXXX</strong></span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToStep(2)">
                            Selanjutnya <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ========== STEP 2: Pengaju & Detail ========== --}}
                <div id="step-2" class="d-none">
                    <p class="text-uppercase text-muted fw-semibold mb-3 small">
                        <i class="fas fa-user-tie me-1"></i> Pengaju Permintaan
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Departemen yang Mengajukan <span class="text-danger">*</span></label>
                            <select name="department_name" id="department_name" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Departemen --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Customer Details Form - Shown only for PO Pelanggan --}}
                    <div id="customer_section" class="d-none border-top pt-3 mt-3">
                        <p class="text-uppercase text-primary fw-bold mb-3 small">
                            <i class="fas fa-user-tag me-1"></i> Detail Pembeli / Pelanggan PO
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nama Pelanggan <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" id="cust_name" class="form-control form-control-sm" placeholder="Nama lengkap pelanggan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">No. HP Pelanggan</label>
                                <input type="text" name="customer_phone" id="cust_phone" class="form-control form-control-sm" placeholder="Contoh: 08123456789">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Alamat Pengiriman <span class="text-danger">*</span></label>
                                <textarea name="shipping_address" id="cust_address" class="form-control form-control-sm" rows="3" placeholder="Alamat lengkap pengiriman..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToStep(3)">
                            Selanjutnya <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ========== STEP 3: Item Produk ========== --}}
                <div id="step-3" class="d-none">
                    <p class="text-uppercase text-muted fw-semibold mb-3 small">
                        <i class="fas fa-boxes me-1"></i> Item Produk untuk Diproduksi
                    </p>

                    <div id="item-list" class="mb-3"></div>

                    <button type="button" id="btn-add-item"
                        class="btn btn-outline-primary btn-sm w-100 mb-4 fw-semibold">
                        <i class="fas fa-plus-circle me-1"></i> Tambah Item Produk
                    </button>

                    <div class="alert alert-primary d-flex justify-content-between align-items-center py-2 px-3 mb-4">
                        <div>
                            <div class="text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:0.06em;">Estimasi Nilai</div>
                            <div id="order-grand-total" class="fw-bold fs-5 mb-0">Rp 0</div>
                        </div>
                        <span class="badge bg-primary rounded-pill" id="item-count-label">0 item</span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" onclick="goToStep(2)">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToReview()">
                            Review Permintaan <i class="fas fa-eye ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ========== STEP 4: Review & Submit ========== --}}
                <div id="step-4" class="d-none">
                    <p class="text-uppercase text-muted fw-semibold mb-3 small">
                        <i class="fas fa-clipboard-check me-1"></i> Ringkasan & Konfirmasi
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border bg-light h-100">
                                <div class="card-body py-3 px-3">
                                    <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:0.65rem;letter-spacing:0.06em;">
                                        <i class="fas fa-file-invoice me-1"></i>Informasi Permintaan
                                    </p>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted ps-0" style="width:40%">Tipe Permintaan</td>
                                            <td class="fw-bold text-primary" id="rev-type">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted ps-0">No. Invoice</td>
                                            <td class="text-muted font-monospace" id="rev-invoice">[Otomatis Generated]</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border bg-light h-100">
                                <div class="card-body py-3 px-3">
                                    <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:0.65rem;letter-spacing:0.06em;">
                                        <i class="fas fa-user me-1"></i>Pengaju & Tujuan
                                    </p>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted ps-0" style="width:30%">Departemen</td>
                                            <td class="fw-bold text-success" id="rev-buyer">-</td>
                                        </tr>
                                        <tr class="po-details-row d-none">
                                            <td class="text-muted ps-0">Pelanggan PO</td>
                                            <td class="fw-semibold" id="rev-customer-detail">-</td>
                                        </tr>
                                        <tr class="po-details-row d-none">
                                            <td class="text-muted ps-0">Alamat Kirim</td>
                                            <td class="fw-semibold" id="rev-address" style="word-break:break-word;">-</td>
                                        </tr>
                                        <tr class="stock-details-row">
                                            <td class="text-muted ps-0">Tujuan</td>
                                            <td class="fw-semibold text-secondary">Gudang Jadi (Masuk Stok)</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border bg-light mb-3">
                        <div class="card-body py-3 px-3">
                            <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:0.65rem;letter-spacing:0.06em;">
                                <i class="fas fa-boxes me-1"></i>Item Permintaan Produksi
                            </p>
                            <div id="rev-items" class="mb-2"></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold text-muted small">Total Estimasi</span>
                                <span class="fw-bold fs-5 text-success" id="rev-grand-total">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" onclick="goToStep(3)">
                            <i class="fas fa-arrow-left me-1"></i> Edit Pesanan
                        </button>
                        <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
                            <i class="fas fa-paper-plane me-1"></i> Konfirmasi & Ajukan Permintaan
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Item Card Template --}}
<template id="item-tpl">
    <div class="card border mb-2 item-card" data-index="__IDX__">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-start gap-2">
                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center mt-1 flex-shrink-0 item-num-badge"
                    style="width:24px;height:24px;font-size:0.7rem;">__NUM__</span>
                <div class="flex-grow-1">
                    <div class="row g-2 align-items-end">
                        <div class="col-12">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                Nama Produk <span class="text-danger">*</span>
                            </label>
                            <select name="items[__IDX__][master_product_id]"
                                class="form-select form-select-sm product-select" required style="width:100%">
                                <option value="">-- Pilih Produk --</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" data-price="{{ $p->price }}">
                                        {{ $p->sku ? '[' . $p->sku . '] ' : '' }}{{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5 col-md-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">Qty</label>
                            <input type="number" name="items[__IDX__][quantity]"
                                class="form-control form-control-sm text-center qty-input" value="1" min="1" required>
                        </div>
                        <div class="col-7 col-md-5">
                            <label class="form-label form-label-sm fw-semibold mb-1">Harga Satuan (Rp)</label>
                            <input type="number" name="items[__IDX__][price]"
                                class="form-control form-control-sm text-end price-input" value="0" min="0" required>
                        </div>
                        <div class="col-md-4 d-none d-md-block">
                            <label class="form-label form-label-sm text-muted mb-1">Subtotal</label>
                            <div class="fw-bold text-success small item-subtotal">Rp 0</div>
                        </div>
                    </div>
                    <div class="d-flex d-md-none justify-content-end mt-1">
                        <small class="text-muted me-1">Subtotal:</small>
                        <span class="fw-bold text-success small item-subtotal">Rp 0</span>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 mt-1 flex-shrink-0 btn-remove-item" title="Hapus">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let rowIndex = 0;
    let currentStep = 1;

    const fmt = n => 'Rp ' + parseFloat(n || 0).toLocaleString('id-ID');

    // No store init

    // Conditional toggle customer info section on Tipe Permintaan change
    $('#request_type').on('change', function () {
        let type = $(this).val();
        if (type === 'PO Pelanggan') {
            $('#customer_section').removeClass('d-none');
            $('#cust_name').prop('required', true);
            $('#cust_address').prop('required', true);
        } else {
            $('#customer_section').addClass('d-none');
            $('#cust_name').prop('required', false).val('');
            $('#cust_phone').val('');
            $('#cust_address').prop('required', false).val('');
        }
    });

    // ===== Select2 for product selects =====
    function initProductSelect(el) {
        if ($(el).hasClass('select2-hidden-accessible')) return;
        $(el).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Pilih Produk --',
            dropdownParent: $(el).closest('.item-card')
        });
    }

    // ===== Add Item Card =====
    function addItemCard() {
        const tpl = document.getElementById('item-tpl').innerHTML;
        const html = tpl.replace(/__IDX__/g, rowIndex).replace(/__NUM__/g, rowIndex + 1);
        const $card = $(html);
        $('#item-list').append($card);
        initProductSelect($card.find('.product-select')[0]);
        rowIndex++;
        refreshRemoveButtons();
        recalc();
    }

    function ensureFirstItem() {
        if ($('.item-card').length === 0) addItemCard();
    }

    $('#btn-add-item').on('click', addItemCard);

    // Remove item
    $(document).on('click', '.btn-remove-item', function () {
        $(this).closest('.item-card').remove();
        reIndexCards();
        refreshRemoveButtons();
        recalc();
    });

    function reIndexCards() {
        $('.item-card').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('.item-num-badge').text(i + 1);
            $(this).find('[name]').each(function () {
                $(this).attr('name', $(this).attr('name').replace(/items\[\d+\]/, 'items[' + i + ']'));
            });
        });
    }

    function refreshRemoveButtons() {
        const n = $('.item-card').length;
        $('.btn-remove-item').prop('disabled', n <= 1);
    }

    // Auto price fill
    $(document).on('change', '.product-select', function () {
        const price = $(this).find('option:selected').data('price') || 0;
        $(this).closest('.item-card').find('.price-input').val(price).trigger('input');
    });

    // Recalculate subtotals
    $(document).on('input', '.qty-input, .price-input', function () {
        const card = $(this).closest('.item-card');
        const sub = parseFloat(card.find('.qty-input').val() || 0) * parseFloat(card.find('.price-input').val() || 0);
        card.find('.item-subtotal').text(fmt(sub));
        recalc();
    });

    function recalc() {
        let total = 0, count = 0;
        $('.item-card').each(function () {
            const qty = parseFloat($(this).find('.qty-input').val() || 0);
            const price = parseFloat($(this).find('.price-input').val() || 0);
            total += qty * price;
            if ($(this).find('.product-select').val()) count++;
        });
        $('#order-grand-total').text(fmt(total));
        $('#item-count-label').text(count + ' item');
    }

    // ===== Step Navigation =====
    window.goToStep = function (target) {
        if (target > currentStep && !validateStep(currentStep)) return;

        // Update step visuals
        for (let i = 1; i <= 4; i++) {
            const $icon = $('#step-icon-' + i);
            const $label = $('#step-label-' + i);

            $icon.removeClass('bg-primary border-primary text-white bg-success border-success bg-light border-secondary text-secondary');
            $label && $label.removeClass('text-primary text-success text-secondary');

            if (i < target) {
                // Done
                $icon.addClass('bg-success border-success text-white');
                $label.addClass('text-success');
            } else if (i === target) {
                // Active
                $icon.addClass('bg-primary border-primary text-white');
                $label.addClass('text-primary');
            } else {
                // Pending
                $icon.addClass('bg-light border-secondary text-secondary');
                $label.addClass('text-secondary');
            }
        }

        // Update connector colors
        for (let i = 1; i <= 3; i++) {
            const $conn = $('#conn-' + i);
            $conn.removeClass('border-primary border-success border-secondary');
            $conn.addClass(i < target ? 'border-success' : 'border-secondary');
        }

        // Show / hide panels
        $('#step-1, #step-2, #step-3, #step-4').addClass('d-none');
        $('#step-' + target).removeClass('d-none');
        currentStep = target;

        $('html, body').animate({ scrollTop: $('.card').first().offset().top - 16 }, 280);
        if (target === 3) ensureFirstItem();
    };

    window.goToReview = function () {
        if ($('.item-card').length === 0) return showWarn('Tambahkan minimal 1 item produk.');
        let allOk = true;
        $('.item-card').each(function () { if (!$(this).find('.product-select').val()) allOk = false; });
        if (!allOk) return showWarn('Semua item harus memilih produk.');

        let type = $('#request_type').val();
        $('#rev-type').text(type);
        $('#rev-buyer').text($('#department_name').val() || '-');

        if (type === 'PO Pelanggan') {
            $('.po-details-row').removeClass('d-none');
            $('.stock-details-row').addClass('d-none');
            $('#rev-customer-detail').text($('#cust_name').val() + ' (' + ($('#cust_phone').val() || '-') + ')');
            $('#rev-address').text($('#cust_address').val() || '-');
        } else {
            $('.po-details-row').addClass('d-none');
            $('.stock-details-row').removeClass('d-none');
        }

        let html = '', total = 0;
        $('.item-card').each(function () {
            const name = $(this).find('.product-select option:selected').text().trim();
            const qty = parseFloat($(this).find('.qty-input').val() || 0);
            const price = parseFloat($(this).find('.price-input').val() || 0);
            const sub = qty * price;
            total += sub;
            html += `<div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 mb-1 small bg-white">
                <span class="fw-semibold text-truncate me-2">${name}</span>
                <span class="text-muted text-nowrap">${qty} × ${fmt(price)} = <strong class="text-primary">${fmt(sub)}</strong></span>
            </div>`;
        });
        $('#rev-items').html(html);
        $('#rev-grand-total').text(fmt(total));

        goToStep(4);
    };

    function validateStep(step) {
        if (step === 1) {
            // Step 1 is request type only, always valid
        }
        if (step === 2) {
            if (!$('#department_name').val()) { showWarn('Pilih departemen pengaju terlebih dahulu.'); return false; }
            
            let type = $('#request_type').val();
            if (type === 'PO Pelanggan') {
                if (!$('#cust_name').val().trim()) { showWarn('Isi nama pelanggan.'); return false; }
                if (!$('#cust_address').val().trim()) { showWarn('Isi alamat pengiriman pelanggan.'); return false; }
            }
        }
        return true;
    }

    function showWarn(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning', title: 'Perhatian', text: msg,
                confirmButtonColor: '#0d6efd', timer: 3500, confirmButtonText: 'OK',
                customClass: { popup: 'border border-light-subtle shadow-sm' }
            });
        } else { alert(msg); }
    }
});
</script>
@endpush
