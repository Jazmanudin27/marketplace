@extends('v2.layouts.app')

@section('title', 'Input Pesanan Dropship / Lazada')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-truck text-info fs-5"></i> Input Pesanan Dropship / Lazada
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Form sederhana pencatatan pesanan dropship atau lazada dengan input pelanggan manual.</p>
    </div>
    <div>
        <a href="{{ route('v2.dropship.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ── Error Notification ── --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Terjadi Kesalahan:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('v2.dropship.store') }}" method="POST" id="dropshipForm">
    @csrf
    <div class="row g-4">
        {{-- Kiri: Informasi Utama Pesanan --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column" style="border: 1px solid #e2e8f0 !important;">
                <h6 class="fw-bold text-dark border-bottom pb-3 mb-3.5 d-flex align-items-center gap-2" style="font-size:0.95rem;">
                    <i class="bi bi-info-circle text-primary"></i> Data Utama Pesanan
                </h6>

                <!-- No. Nota (Otomatis) -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1.5">No. Nota / Referensi</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace fw-bold py-2 px-2.5" value="{{ $orderNumber }}" readonly>
                </div>

                <!-- Status / Saluran: Dropship atau Lazada -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1.5">
                        Saluran Pesanan <span class="text-danger">*</span>
                    </label>
                    <select name="channel_type" id="channel_type" class="form-select form-select-sm fw-bold py-2 px-2.5 @error('channel_type') is-invalid @enderror" onchange="onChannelChange(this.value)" required>
                        <option value="dropship" {{ old('channel_type', $defaultChannel) === 'dropship' ? 'selected' : '' }}>Dropship</option>
                        <option value="lazada" {{ old('channel_type', $defaultChannel) === 'lazada' ? 'selected' : '' }}>Lazada</option>
                    </select>
                </div>

                <!-- Tanggal Pesanan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1.5">
                        Tanggal Pesanan <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="order_date" class="form-control form-control-sm py-2 px-2.5 @error('order_date') is-invalid @enderror" value="{{ old('order_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Nama Pelanggan (Input Manual) -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1.5">
                        Nama Pelanggan (Input Manual) <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="customer_name" class="form-control form-control-sm py-2 px-2.5 @error('customer_name') is-invalid @enderror" placeholder="Contoh: Gilang / Toko Berkah" value="{{ old('customer_name') }}" required>
                </div>

                <!-- No. Telp / HP Pelanggan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1.5">No. HP / WhatsApp (Opsional)</label>
                    <input type="text" name="customer_phone" class="form-control form-control-sm py-2 px-2.5" placeholder="Contoh: 08123456789" value="{{ old('customer_phone') }}">
                </div>

                <!-- Keterangan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1.5">Keterangan / Catatan</label>
                    <textarea name="notes" class="form-control form-control-sm py-2 px-2.5" rows="2" placeholder="Catatan pesanan, alamat, nomor resi, dll...">{{ old('notes') }}</textarea>
                </div>

                <!-- Pembayaran Awal (Opsional) -->
                <div class="mb-3 border rounded-3 p-3 bg-light bg-opacity-50">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label small fw-bold text-dark mb-0 cursor-pointer" for="togglePayment">
                            <i class="bi bi-wallet2 text-success me-1"></i> Catat Pembayaran Awal
                        </label>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="togglePayment" onchange="togglePaymentSection(this.checked)">
                        </div>
                    </div>
                    
                    <div id="paymentFields" style="display: none;" class="mt-2.5 pt-2.5 border-top">
                        <div class="mb-2">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <button type="button" class="btn btn-sm btn-outline-success active" id="btnPayFull" onclick="setPayOption('full')">
                                    <i class="bi bi-check-circle me-1"></i>Lunas Langsung
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnPayCustom" onclick="setPayOption('custom')">
                                    <i class="bi bi-pie-chart me-1"></i>DP / Sebagian
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Nominal Dibayar (Rp)</label>
                            <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" class="form-control form-control-sm font-monospace fw-bold" placeholder="0">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Metode</label>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="tunai">Tunai / Cash</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Tujuan Kas</label>
                                <select name="payment_destination" class="form-select form-select-sm">
                                    <option value="Kas Tunai">Kas Tunai</option>
                                    @foreach($bankAccounts ?? [] as $bank)
                                        <option value="{{ $bank->bank_name }} - {{ $bank->account_number }}">
                                            {{ $bank->bank_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label small fw-semibold text-secondary mb-1">No. Ref / Bukti</label>
                            <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-001">
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Box -->
                <div class="p-3.5 bg-light rounded-3 border mt-auto">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-1.5">
                        <span>Total Kuantitas:</span>
                        <strong id="summary-total-qty" class="text-dark font-monospace">0 PCS</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">Total Tagihan:</span>
                        <strong id="summary-total-amount" class="text-success fs-5 fw-bold font-monospace">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Detail Produk, Qty, Harga, Jumlah --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column" style="border: 1px solid #e2e8f0 !important;">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3.5 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5 d-flex align-items-center gap-2" style="font-size:0.95rem;">
                            <i class="bi bi-box-seam text-success"></i> Rincian Produk Pesanan
                        </h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Pilih produk, tentukan kuantitas, harga satuan, dan jumlah akan otomatis dihitung.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary py-1.5 px-3.5 fw-semibold rounded-2 shadow-2xs" onclick="addRowItem()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Baris Produk
                    </button>
                </div>

                <div class="table-responsive flex-grow-1 mb-3.5">
                    <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable" style="font-size: 0.83rem;">
                        <thead class="bg-light text-secondary text-uppercase fw-semibold" style="font-size: 0.72rem;">
                            <tr>
                                <th class="p-2.5" style="width: 44%;">PRODUK</th>
                                <th class="p-2.5 text-center" style="width: 14%;">QTY</th>
                                <th class="p-2.5 text-end" style="width: 18%;">HARGA (RP)</th>
                                <th class="p-2.5 text-end" style="width: 18%;">JUMLAH (RP)</th>
                                <th class="p-2.5 text-center" style="width: 6%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            {{-- Baris item akan dimasukkan via JavaScript --}}
                        </tbody>
                    </table>
                </div>

                {{-- Action Footer --}}
                <div class="d-flex justify-content-between align-items-center pt-3.5 border-top mt-auto flex-wrap gap-2">
                    <span class="small text-muted">
                        Pastikan data produk dan harga telah sesuai sebelum menyimpan.
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('v2.dropship.index') }}" class="btn btn-sm btn-outline-secondary px-4 py-1.5 fw-semibold">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm btn-success px-4 py-1.5 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    const masterProducts = @json($products);
    let rowIndex = 0;

    function addRowItem(productId = null, qty = 1, price = null) {
        rowIndex++;
        const tbody = document.getElementById('itemsTableBody');

        const tr = document.createElement('tr');
        tr.id = 'row_' + rowIndex;

        let optionsHtml = '<option value="">-- Pilih Produk Master --</option>';
        masterProducts.forEach(p => {
            const isSelected = (productId && p.id == productId) ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" ${isSelected} data-price="${p.price}" data-lazada-price="${p.lazada_dropship_price || p.lazada_price || p.price}" data-shopee-price="${p.shopee_dropship_price || p.price}" data-stock="${p.stock}">[${p.sku}] ${p.name} (Stok: ${p.stock})</option>`;
        });

        tr.innerHTML = `
            <td class="p-2">
                <select name="items[${rowIndex}][master_product_id]" class="form-select form-select-sm item-product-select select2-item" onchange="onProductSelect(${rowIndex}, this)" required>
                    ${optionsHtml}
                </select>
            </td>
            <td class="p-2 text-center">
                <input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm text-center item-qty" value="${qty}" min="1" oninput="calculateSubtotal(${rowIndex})" required>
            </td>
            <td class="p-2 text-end">
                <input type="number" name="items[${rowIndex}][unit_price]" class="form-control form-control-sm text-end font-monospace item-price" value="${price !== null ? price : 0}" min="0" step="100" oninput="calculateSubtotal(${rowIndex})" required>
            </td>
            <td class="p-2 text-end font-monospace fw-bold text-success item-subtotal" id="subtotal_${rowIndex}" style="font-size:0.88rem;">
                Rp 0
            </td>
            <td class="p-2 text-center">
                <button type="button" class="btn btn-xs btn-outline-danger py-1 px-1.5 rounded" title="Hapus Baris" onclick="removeRowItem(${rowIndex})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        if (window.initV2Select2) {
            window.initV2Select2(tr);
        } else if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $(tr).find('.item-product-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih Produk Master --'
            });
        }

        const selectElem = tr.querySelector('.item-product-select');
        const handleProductChange = function() {
            onProductSelect(rowIndex, selectElem);
        };
        selectElem.addEventListener('change', handleProductChange);
        if (typeof jQuery !== 'undefined') {
            $(selectElem).on('select2:select change', handleProductChange);
        }

        if (productId) {
            calculateSubtotal(rowIndex);
        } else {
            calculateGrandTotal();
        }
    }

    function removeRowItem(idx) {
        const row = document.getElementById('row_' + idx);
        if (row) {
            const selectElem = row.querySelector('.item-product-select');
            if (selectElem && typeof jQuery !== 'undefined' && $(selectElem).hasClass('select2-hidden-accessible')) {
                $(selectElem).select2('destroy');
            }
            row.remove();
            calculateGrandTotal();
        }
    }

    function onProductSelect(idx, selectElem) {
        const selectedOpt = selectElem.options[selectElem.selectedIndex];
        if (!selectedOpt || !selectedOpt.value) return;

        const channel = document.getElementById('channel_type').value;
        let price = parseFloat(selectedOpt.dataset.price || 0);

        if (channel === 'lazada') {
            price = parseFloat(selectedOpt.dataset.lazadaPrice || price);
        } else {
            price = parseFloat(selectedOpt.dataset.shopeePrice || price);
        }

        const tr = document.getElementById('row_' + idx);
        if (tr) {
            const priceInput = tr.querySelector('.item-price');
            if (priceInput) {
                priceInput.value = Math.round(price);
            }
            calculateSubtotal(idx);
        }
    }

    function onChannelChange(channel) {
        // Update harga pada baris yang sudah dipilih jika masih default
        document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
            const select = tr.querySelector('.item-product-select');
            if (select && select.value) {
                const opt = select.options[select.selectedIndex];
                let price = parseFloat(opt.dataset.price || 0);
                if (channel === 'lazada') {
                    price = parseFloat(opt.dataset.lazadaPrice || price);
                } else {
                    price = parseFloat(opt.dataset.shopeePrice || price);
                }
                const priceInput = tr.querySelector('.item-price');
                if (priceInput) priceInput.value = Math.round(price);
                const idx = tr.id.replace('row_', '');
                calculateSubtotal(idx);
            }
        });
    }

    function calculateSubtotal(idx) {
        const tr = document.getElementById('row_' + idx);
        if (!tr) return;

        const qty = parseFloat(tr.querySelector('.item-qty')?.value || 0);
        const price = parseFloat(tr.querySelector('.item-price')?.value || 0);
        const subtotal = Math.max(0, qty * price);

        const subtotalElem = document.getElementById('subtotal_' + idx);
        if (subtotalElem) {
            subtotalElem.innerText = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
        }

        calculateGrandTotal();
    }

    let payOption = 'full';
    let currentGrandTotal = 0;

    function togglePaymentSection(show) {
        const fields = document.getElementById('paymentFields');
        if (fields) {
            fields.style.display = show ? 'block' : 'none';
        }
        if (show) {
            syncPaidAmount();
        } else {
            const paidInput = document.getElementById('paid_amount');
            if (paidInput) paidInput.value = '';
        }
    }

    function setPayOption(opt) {
        payOption = opt;
        const btnFull = document.getElementById('btnPayFull');
        const btnCustom = document.getElementById('btnPayCustom');
        const paidInput = document.getElementById('paid_amount');

        if (opt === 'full') {
            btnFull?.classList.add('active');
            btnCustom?.classList.remove('active');
            if (paidInput) {
                paidInput.value = currentGrandTotal;
                paidInput.readOnly = true;
            }
        } else {
            btnCustom?.classList.add('active');
            btnFull?.classList.remove('active');
            if (paidInput) {
                paidInput.readOnly = false;
                paidInput.focus();
            }
        }
    }

    function syncPaidAmount() {
        if (payOption === 'full') {
            const paidInput = document.getElementById('paid_amount');
            if (paidInput) {
                paidInput.value = currentGrandTotal;
            }
        }
    }

    function calculateGrandTotal() {
        let totalAmount = 0;
        let totalQty = 0;

        document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.item-qty')?.value || 0);
            const price = parseFloat(tr.querySelector('.item-price')?.value || 0);
            totalQty += qty;
            totalAmount += (qty * price);
        });

        currentGrandTotal = Math.round(totalAmount);

        const qtyElem = document.getElementById('summary-total-qty');
        if (qtyElem) qtyElem.innerText = totalQty.toLocaleString('id-ID') + ' PCS';

        const amountElem = document.getElementById('summary-total-amount');
        if (amountElem) amountElem.innerText = 'Rp ' + currentGrandTotal.toLocaleString('id-ID');

        const isChecked = document.getElementById('togglePayment')?.checked;
        if (isChecked) {
            syncPaidAmount();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Buat 1 baris awal jika kosong
        addRowItem();

        document.getElementById('dropshipForm').addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('#itemsTableBody tr');
            if (rows.length === 0) {
                e.preventDefault();
                alert('Silakan tambahkan minimal 1 baris produk.');
                return false;
            }
        });
    });
</script>
@endpush
