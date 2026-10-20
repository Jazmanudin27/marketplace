@extends('v2.layouts.app')

@section('title', 'Catat Pengeluaran Stok Titipan Barang (Konsinyasi) V2')

@push('styles')
<style>
.reason-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    background: #ffffff;
}
.reason-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.reason-card.active {
    border-color: #dc2626;
    background: #fef2f2;
}
.reason-card input[type="radio"] {
    display: none;
}
.preview-kpi-badge {
    font-size: 0.95rem;
    font-weight: 700;
    font-family: monospace;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Catat Pengeluaran Stok Titipan
        </h1>
        <p class="text-muted small mb-0">Input pemotongan stok barang titipan konsinyasi yang lupa discan atau dikeluarkan secara manual</p>
    </div>
    <div>
        <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
        </a>
    </div>
</div>

{{-- ── Alerts ── --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm rounded-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm rounded-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Gagal Menyimpan:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('v2.titipan_barang.deductions.store') }}" method="POST" id="deduction-form">
    @csrf

    <div class="row g-3">
        {{-- ── KOLOM KIRI: Form Input Utama ── --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-boxes text-danger"></i> 1. Pilih Batch & Produk Barang Titipan
                </h6>

                {{-- Filter Supplier Cepat --}}
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted mb-1">Filter Berdasarkan Supplier (Opsional)</label>
                        <select id="supplier_filter" class="form-select form-select-sm select2">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ $preselectedItem && $preselectedItem->consignment->supplier_id == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Pilih Batch Titipan & Produk <span class="text-danger">*</span>
                        </label>
                        <select name="supplier_consignment_item_id" id="supplier_consignment_item_id" class="form-select form-select-sm select2" required>
                            <option value="">-- Pilih Batch Titipan / Produk --</option>
                            @foreach($availableItems as $item)
                                @php
                                    $p = $item->masterProduct;
                                    $c = $item->consignment;
                                    $s = $c ? $c->supplier : null;
                                    $isSelected = old('supplier_consignment_item_id', $preselectedItemId) == $item->id;
                                @endphp
                                <option value="{{ $item->id }}"
                                    data-supplier-id="{{ $c ? $c->supplier_id : '' }}"
                                    data-sisa="{{ $item->sisa_stok }}"
                                    data-received="{{ $item->qty_received }}"
                                    data-sold="{{ (int)($item->qty_sold ?? 0) }}"
                                    data-hpp="{{ (float)$item->unit_cost_price }}"
                                    data-product-name="{{ $p ? $p->name : 'Produk' }}"
                                    data-sku="{{ $p ? $p->sku : '-' }}"
                                    data-batch="{{ $c ? $c->reference_number : '-' }}"
                                    data-supplier-name="{{ $s ? $s->name : '-' }}"
                                    data-product-id="{{ $item->master_product_id }}"
                                    {{ $isSelected ? 'selected' : '' }}>
                                    [{{ $c ? $c->reference_number : '-' }}] {{ $p ? $p->name : '-' }} (SKU: {{ $p ? $p->sku : '-' }}) — Sisa: {{ $item->sisa_stok }} pcs
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Detail Box Produk Terpilih --}}
                <div id="selected_item_info" class="p-2.5 rounded-2 bg-light border mb-3 d-none">
                    <div class="row align-items-center g-2">
                        <div class="col-12 col-md-7">
                            <div class="small text-muted" style="font-size:0.7rem;">BATCH KONSINYASI:</div>
                            <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                <span class="font-monospace text-primary" id="info_batch">-</span>
                                <span class="text-muted">|</span>
                                <span class="text-secondary" id="info_supplier">-</span>
                            </div>
                            <div class="fw-semibold text-dark mt-1" id="info_product_name">-</div>
                            <div class="small font-monospace text-muted mt-0.5" id="info_sku">SKU: -</div>
                        </div>
                        <div class="col-12 col-md-5 text-md-end">
                            <div class="d-inline-flex flex-column align-items-md-end">
                                <div class="small text-muted" style="font-size:0.7rem;">SISA STOK TITIPAN SAAT INI</div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fw-bold fs-6 mt-0.5" id="info_sisa_badge">
                                    0 PCS
                                </span>
                                <small class="text-muted mt-1" id="info_hpp_text">HPP: Rp 0 / pcs</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Jumlah Pengeluaran (Qty) --}}
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Jumlah Pengeluaran (Qty) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" onclick="adjustQty(-1)"><i class="bi bi-dash"></i></button>
                            <input type="number" name="quantity" id="quantity" class="form-control text-center fw-bold fs-6" value="{{ old('quantity', 1) }}" min="1" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="adjustQty(1)"><i class="bi bi-plus"></i></button>
                            <span class="input-group-text bg-light text-muted small">PCS</span>
                        </div>
                        <div id="qty_warning" class="text-danger small mt-1 d-none fw-semibold">
                            <i class="bi bi-exclamation-circle me-1"></i>Jumlah melebihi sisa stok titipan yang tersedia!
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Tanggal Pengeluaran</label>
                        <input type="datetime-local" name="deduction_date" class="form-control form-control-sm" value="{{ old('deduction_date', now()->format('Y-m-d\TH:i')) }}">
                        <div class="form-text text-muted" style="font-size: 0.72rem;">Waktu aktual barang titipan dikeluarkan/terjual</div>
                    </div>
                </div>
            </div>

            {{-- ── CARD 2: Alasan Pengeluaran & Hubungkan Pesanan ── --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-question-circle text-danger"></i> 2. Alasan Pengeluaran & Pesanan Terkait
                </h6>

                {{-- Pilihan Alasan (Radio Cards) --}}
                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="reason-card d-block h-100 {{ old('reason', 'unscanned_order') == 'unscanned_order' ? 'active' : '' }}">
                            <input type="radio" name="reason" value="unscanned_order" {{ old('reason', 'unscanned_order') == 'unscanned_order' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-qr-code-scan text-warning fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Terlewat Scan</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Pesanan kemas lupa discan</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="reason-card d-block h-100 {{ old('reason') == 'manual_adjustment' ? 'active' : '' }}">
                            <input type="radio" name="reason" value="manual_adjustment" {{ old('reason') == 'manual_adjustment' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-sliders text-info fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Koreksi Manual</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Penyesuaian stok selisih</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="reason-card d-block h-100 {{ old('reason') == 'return_supplier' ? 'active' : '' }}">
                            <input type="radio" name="reason" value="return_supplier" {{ old('reason') == 'return_supplier' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-return-left text-danger fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Retur ke Supplier</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Barang dikembalikan</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="reason-card d-block h-100 {{ old('reason') == 'damaged' ? 'active' : '' }}">
                            <input type="radio" name="reason" value="damaged" {{ old('reason') == 'damaged' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-x-octagon text-secondary fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Rusak / Hilang</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Cacat fisik / susut</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="reason-card d-block h-100 {{ old('reason') == 'direct_sale' ? 'active' : '' }}">
                            <input type="radio" name="reason" value="direct_sale" {{ old('reason') == 'direct_sale' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shop text-primary fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Penjualan Langsung</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Offline / Toko fisik / Sample</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="reason-card d-block h-100 {{ old('reason') == 'other' ? 'active' : '' }}">
                            <input type="radio" name="reason" value="other" {{ old('reason') == 'other' ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-three-dots text-dark fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Lainnya</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Keperluan internal lainnya</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Hubungkan ke Order Marketplace (Jika Terlewat Scan) --}}
                <div id="order_linkage_box" class="p-3 rounded-2 border bg-light-subtle mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-bold text-dark mb-0">
                            <i class="bi bi-link-45deg text-primary me-1"></i> Hubungkan ke Nomor Pesanan / Invoice Marketplace
                        </label>
                        <span class="badge bg-light text-muted border" style="font-size:0.68rem;">Opsional</span>
                    </div>
                    <p class="text-muted small mb-2" style="font-size:0.75rem;">
                        Pilih pesanan marketplace yang lupa discan titipan barangnya saat packing, agar data pemenuhan pesanan (fulfillment) tercatat otomatis.
                    </p>

                    <select name="order_id" id="order_id" class="form-select form-select-sm" style="width: 100%;">
                        @if($preselectedOrder)
                            <option value="{{ $preselectedOrder->id }}" selected>
                                {{ $preselectedOrder->invoice_number ?: $preselectedOrder->order_marketplace_id }} [{{ $preselectedOrder->store ? $preselectedOrder->store->store_name : 'Toko' }}] - {{ $preselectedOrder->buyer_name ?: 'Customer' }}
                            </option>
                        @else
                            <option value="">-- Ketik No. Invoice / No. Resi / Nama Pembeli untuk mencari --</option>
                        @endif
                    </select>

                    <div id="order_preview_card" class="mt-2 p-2 rounded-2 bg-white border d-none">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-bold text-dark small" id="order_preview_invoice">-</span>
                                <span class="badge bg-light text-secondary border ms-1" id="order_preview_store">-</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border" id="order_preview_status">-</span>
                        </div>
                        <div class="text-muted small mt-1" style="font-size:0.72rem;">
                            Pembeli: <span class="text-dark fw-semibold" id="order_preview_buyer">-</span> | 
                            Item Pesanan: <span class="text-dark" id="order_preview_items">-</span>
                        </div>
                    </div>
                </div>

                {{-- Referensi & Catatan Tambahan --}}
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">No. Referensi / No. Resi / Surat Jalan</label>
                        <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="Contoh: RESI-SPX0123... / SJ-2026-001" value="{{ old('reference_no') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">Catatan / Keterangan</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Contoh: Terlewat scan saat packing shift sore..." value="{{ old('notes') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── KOLOM KANAN: Opsi Stok & Ringkasan ── --}}
        <div class="col-12 col-lg-4">
            {{-- Card Opsi Pengurangan Stok Gudang Master --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2 d-flex align-items-center gap-1.5">
                    <i class="bi bi-shield-check text-warning"></i> Opsi Sinkronisasi Gudang Master
                </h6>

                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="deduct_master_stock" id="deduct_master_stock" value="1" {{ old('deduct_master_stock') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-dark small" for="deduct_master_stock">
                        Kurangi juga Stok Fisik Gudang Master?
                    </label>
                </div>

                <div class="alert alert-light border rounded-2 p-2.5 mt-2 mb-0" style="font-size: 0.72rem; line-height: 1.45;">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle text-info me-1"></i>Petunjuk:</div>
                    <ul class="mb-0 ps-3 text-muted">
                        <li><strong>JANGAN CENTANG (Rekomendasi)</strong> jika barang ini keluar karena pesanan marketplace yang sudah selesai dipacking/diverifikasi scanner gudang. Stok master sudah terpotong otomatis saat scan order tersebut.</li>
                        <li><strong>CENTANG</strong> jika barang ini benar-benar dikeluarkan fisik dari gudang di luar pesanan (seperti retur ke supplier, barang rusak/hilang, atau penjualan langsung offline).</li>
                    </ul>
                </div>
            </div>

            {{-- Card Ringkasan Preview --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-1.5">
                    <i class="bi bi-calculator text-primary"></i> Ringkasan Pengeluaran
                </h6>

                <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom small">
                    <span class="text-muted">Produk Terpilih:</span>
                    <span class="fw-bold text-dark text-end text-truncate ms-2" style="max-width: 170px;" id="summary_product">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom small">
                    <span class="text-muted">Sisa Stok Titipan Awal:</span>
                    <span class="fw-bold text-dark" id="summary_initial_stock">0 PCS</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom small">
                    <span class="text-muted text-danger fw-semibold">Jumlah Keluar:</span>
                    <span class="fw-bold text-danger font-monospace fs-6" id="summary_qty">-0 PCS</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom small">
                    <span class="text-muted">Estimasi Sisa Akhir:</span>
                    <span class="fw-bold text-success font-monospace" id="summary_remaining_stock">0 PCS</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 mt-1">
                    <span class="fw-bold text-dark small">Total Nilai HPP:</span>
                    <span class="fw-bold text-primary font-monospace fs-6" id="summary_hpp">Rp 0</span>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <button type="submit" id="btn_submit" class="btn btn-danger py-2 fw-semibold text-white shadow-sm" style="border-radius: 6px;">
                        <i class="bi bi-check-circle me-1"></i> Simpan Pengeluaran Stok
                    </button>
                    <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="btn btn-light py-2 text-muted fw-semibold border">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemSelect     = document.getElementById('supplier_consignment_item_id');
    const supplierFilter = document.getElementById('supplier_filter');
    const qtyInput       = document.getElementById('quantity');
    const qtyWarning     = document.getElementById('qty_warning');
    const btnSubmit      = document.getElementById('btn_submit');

    // Radio Cards Selection
    document.querySelectorAll('.reason-card').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('.reason-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                handleReasonChange(radio.value);
            }
        });
    });

    function handleReasonChange(val) {
        const linkageBox = document.getElementById('order_linkage_box');
        const deductMasterCheck = document.getElementById('deduct_master_stock');
        if (val === 'unscanned_order') {
            linkageBox.classList.remove('d-none');
            deductMasterCheck.checked = false; // By default unscanned orders don't need double master stock deduction
        } else if (val === 'return_supplier' || val === 'damaged') {
            deductMasterCheck.checked = true; // Physical removal usually deducts physical inventory
        }
    }

    // Filter Items by Supplier
    if (supplierFilter) {
        supplierFilter.addEventListener('change', function () {
            const selectedSup = this.value;
            const options = itemSelect.querySelectorAll('option');
            let firstMatched = false;

            options.forEach(opt => {
                if (!opt.value) return;
                const optSup = opt.getAttribute('data-supplier-id');
                if (!selectedSup || optSup === selectedSup) {
                    opt.style.display = '';
                    if (!firstMatched) {
                        // Keep current if still matches
                    }
                } else {
                    opt.style.display = 'none';
                    if (opt.selected) {
                        opt.selected = false;
                        itemSelect.value = '';
                    }
                }
            });
            updateItemDetails();
        });
    }

    // Item Selection Change
    itemSelect.addEventListener('change', updateItemDetails);
    qtyInput.addEventListener('input', calculateSummary);

    function updateItemDetails() {
        const opt = itemSelect.options[itemSelect.selectedIndex];
        const infoBox = document.getElementById('selected_item_info');

        if (!opt || !opt.value) {
            infoBox.classList.add('d-none');
            calculateSummary();
            return;
        }

        infoBox.classList.remove('d-none');
        document.getElementById('info_batch').textContent = opt.getAttribute('data-batch') || '-';
        document.getElementById('info_supplier').textContent = opt.getAttribute('data-supplier-name') || '-';
        document.getElementById('info_product_name').textContent = opt.getAttribute('data-product-name') || '-';
        document.getElementById('info_sku').textContent = 'SKU: ' + (opt.getAttribute('data-sku') || '-');

        const sisa = parseInt(opt.getAttribute('data-sisa') || '0', 10);
        document.getElementById('info_sisa_badge').textContent = sisa.toLocaleString('id-ID') + ' PCS';

        const hpp = parseFloat(opt.getAttribute('data-hpp') || '0');
        document.getElementById('info_hpp_text').textContent = 'HPP: Rp ' + hpp.toLocaleString('id-ID') + ' / pcs';

        qtyInput.setAttribute('max', sisa);
        calculateSummary();
    }

    function calculateSummary() {
        const opt = itemSelect.options[itemSelect.selectedIndex];
        const sisa = opt && opt.value ? parseInt(opt.getAttribute('data-sisa') || '0', 10) : 0;
        const hpp  = opt && opt.value ? parseFloat(opt.getAttribute('data-hpp') || '0') : 0;
        const prod = opt && opt.value ? opt.getAttribute('data-product-name') : '-';

        let qty = parseInt(qtyInput.value || '0', 10);
        if (qty < 1) qty = 1;

        const estRemaining = sisa - qty;
        const totalHpp = qty * hpp;

        document.getElementById('summary_product').textContent = prod;
        document.getElementById('summary_initial_stock').textContent = sisa.toLocaleString('id-ID') + ' PCS';
        document.getElementById('summary_qty').textContent = '-' + qty.toLocaleString('id-ID') + ' PCS';
        document.getElementById('summary_remaining_stock').textContent = Math.max(0, estRemaining).toLocaleString('id-ID') + ' PCS';
        document.getElementById('summary_hpp').textContent = 'Rp ' + totalHpp.toLocaleString('id-ID');

        if (opt && opt.value && qty > sisa) {
            qtyWarning.classList.remove('d-none');
            btnSubmit.disabled = true;
        } else {
            qtyWarning.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    }

    window.adjustQty = function (delta) {
        let current = parseInt(qtyInput.value || '1', 10);
        current += delta;
        if (current < 1) current = 1;
        qtyInput.value = current;
        calculateSummary();
    };

    // Initialize state
    updateItemDetails();

    // Select2 Order Search with AJAX
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('#order_id').select2({
            placeholder: '-- Ketik No. Invoice / Resi / Nama Pembeli --',
            allowClear: true,
            ajax: {
                url: '{{ route("v2.titipan_barang.deductions.search_orders") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            minimumInputLength: 2
        }).on('select2:select', function (e) {
            const data = e.params.data;
            const preview = document.getElementById('order_preview_card');
            if (data) {
                preview.classList.remove('d-none');
                document.getElementById('order_preview_invoice').textContent = data.invoice_number || '-';
                document.getElementById('order_preview_store').textContent = data.store_name || '-';
                document.getElementById('order_preview_status').textContent = data.status || '-';
                document.getElementById('order_preview_buyer').textContent = data.buyer_name || '-';
                document.getElementById('order_preview_items').textContent = data.items_summary || '-';
            }
        }).on('select2:clear', function () {
            document.getElementById('order_preview_card').classList.add('d-none');
        });
    }
});
</script>
@endpush
