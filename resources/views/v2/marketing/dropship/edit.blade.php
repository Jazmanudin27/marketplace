@extends('v2.layouts.app')

@section('title', 'Edit Pesanan ' . $order->order_number)

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-pencil-square text-warning fs-5"></i> Edit Pesanan: {{ $order->order_number }}
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Ubah data pelanggan, status saluran, atau rincian item produk pesanan.</p>
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

<form action="{{ route('v2.dropship.update', $order->id) }}" method="POST" id="dropshipForm">
    @csrf
    @method('PUT')
    <div class="row g-4">
        {{-- Kiri: Informasi Utama Pesanan --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column" style="border: 1px solid #e2e8f0 !important;">
                <h6 class="fw-bold text-dark border-bottom pb-3 mb-3.5 d-flex align-items-center gap-2" style="font-size:0.95rem;">
                    <i class="bi bi-info-circle text-primary"></i> Data Utama Pesanan
                </h6>

                <!-- No. Nota -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1.5">No. Nota / Referensi</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace fw-bold py-2 px-2.5" value="{{ $order->order_number }}" readonly>
                </div>

                <!-- Status / Saluran: Dropship atau Lazada -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1.5">
                        Saluran Pesanan <span class="text-danger">*</span>
                    </label>
                    <select name="channel_type" id="channel_type" class="form-select form-select-sm fw-bold py-2 px-2.5 @error('channel_type') is-invalid @enderror" onchange="onChannelChange(this.value)" required>
                        <option value="dropship" {{ old('channel_type', $order->channel_type) === 'dropship' ? 'selected' : '' }}>Dropship</option>
                        <option value="lazada" {{ old('channel_type', $order->channel_type) === 'lazada' ? 'selected' : '' }}>Lazada</option>
                    </select>
                </div>

                <!-- Tanggal Pesanan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1.5">
                        Tanggal Pesanan <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="order_date" class="form-control form-control-sm py-2 px-2.5 @error('order_date') is-invalid @enderror" value="{{ old('order_date', $order->order_date ? $order->order_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                </div>

                <!-- Nama Pelanggan (Input Manual) -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1.5">
                        Nama Pelanggan (Input Manual) <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="customer_name" class="form-control form-control-sm py-2 px-2.5 @error('customer_name') is-invalid @enderror" placeholder="Contoh: Gilang / Toko Berkah" value="{{ old('customer_name', $order->customer_name) }}" required>
                </div>

                <!-- No. Telp / HP Pelanggan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1.5">No. HP / WhatsApp (Opsional)</label>
                    <input type="text" name="customer_phone" class="form-control form-control-sm py-2 px-2.5" placeholder="Contoh: 08123456789" value="{{ old('customer_phone', $order->customer_phone) }}">
                </div>

                <!-- Keterangan -->
                <div class="mb-3.5">
                    <label class="form-label small fw-semibold text-secondary mb-1.5">Keterangan / Catatan</label>
                    <textarea name="notes" class="form-control form-control-sm py-2 px-2.5" rows="3" placeholder="Catatan pesanan, alamat, nomor resi, dll...">{{ old('notes', $order->notes) }}</textarea>
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
                        <small class="text-muted" style="font-size: 0.75rem;">Pilih produk, kuantitas, dan harga satuan.</small>
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
                            {{-- Diisi melalui JavaScript dari data pesanan yang ada --}}
                        </tbody>
                    </table>
                </div>

                {{-- Action Footer --}}
                <div class="d-flex justify-content-between align-items-center pt-3.5 border-top mt-auto flex-wrap gap-2">
                    <span class="small text-muted">
                        Perubahan kuantitas produk akan otomatis mengkoreksi stok gudang master.
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('v2.dropship.index') }}" class="btn btn-sm btn-outline-secondary px-4 py-1.5 fw-semibold">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm btn-warning text-dark px-4 py-1.5 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
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
    const existingItems  = @json($order->items);
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

        calculateSubtotal(rowIndex);
    }

    function removeRowItem(idx) {
        const row = document.getElementById('row_' + idx);
        if (row) {
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

    function calculateGrandTotal() {
        let totalAmount = 0;
        let totalQty = 0;

        document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.item-qty')?.value || 0);
            const price = parseFloat(tr.querySelector('.item-price')?.value || 0);
            totalQty += qty;
            totalAmount += (qty * price);
        });

        const qtyElem = document.getElementById('summary-total-qty');
        if (qtyElem) qtyElem.innerText = totalQty.toLocaleString('id-ID') + ' PCS';

        const amountElem = document.getElementById('summary-total-amount');
        if (amountElem) amountElem.innerText = 'Rp ' + Math.round(totalAmount).toLocaleString('id-ID');
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(it => {
                addRowItem(it.master_product_id, it.quantity, it.unit_price);
            });
        } else {
            addRowItem();
        }

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
