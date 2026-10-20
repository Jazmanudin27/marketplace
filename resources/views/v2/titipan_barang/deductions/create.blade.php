@extends('v2.layouts.app')

@section('title', 'Scan Pengeluaran Stok Titipan Barang (Konsinyasi) V2')

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
.scan-input-box {
    font-size: 1.15rem;
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    background: #f0f7ff;
    transition: all 0.2s;
}
.scan-input-box:focus {
    border-color: #1d4ed8;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
}
.cart-row-flash {
    animation: flashRow 0.8s ease;
}
@keyframes flashRow {
    0% { background-color: #dcfce7; }
    100% { background-color: transparent; }
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0" style="font-size: 1.15rem;">
            <i class="bi bi-upc-scan text-danger fs-5"></i> Scan Pengeluaran Stok Titipan
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Scan barcode/SKU produk titipan secara cepat, item otomatis masuk ke keranjang</p>
    </div>
    <div>
        <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-3 fw-semibold" style="font-size: 0.78rem;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
        </a>
    </div>
</div>

{{-- ── Alerts ── --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-2.5 border-0 shadow-sm rounded-3 py-2 px-3" role="alert" style="font-size: 0.82rem;">
        <i class="bi bi-exclamation-triangle-fill me-1.5 text-danger"></i>{{ session('error') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-2.5 border-0 shadow-sm rounded-3 py-2 px-3" role="alert" style="font-size: 0.82rem;">
        <i class="bi bi-exclamation-triangle-fill me-1.5"></i><strong>Gagal Menyimpan:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('v2.titipan_barang.deductions.store') }}" method="POST" id="deduction-cart-form">
    @csrf

    <div class="row g-3">
        {{-- ── KOLOM KIRI: Scanner & Keranjang Pengeluaran ── --}}
        <div class="col-12 col-lg-8">

            {{-- ── 1. SCANNER BARCODE & FILTER SUPPLIER ── --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2.5">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                        <i class="bi bi-upc-scan text-primary"></i> 1. Scanner Barcode & Filter Supplier
                    </h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.68rem;">
                        AUTO-ADD CART
                    </span>
                </div>

                <div class="row g-2 mb-2.5">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Filter Supplier (Opsional)</label>
                        <select id="supplier_filter" class="form-select form-select-sm select2" onchange="filterAvailableProducts()">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ $preselectedItem && $preselectedItem->consignment->supplier_id == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-primary mb-1 d-flex justify-content-between" style="font-size: 0.75rem;">
                            <span><i class="bi bi-qr-code-scan me-1"></i> Scan Barcode / SKU Produk Titipan *</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1.5" style="font-size: 0.65rem;">Scanner Siap</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-upc"></i></span>
                            <input type="text" id="barcode_scanner_input" class="form-control scan-input-box" placeholder="Scan barcode/SKU di sini..." autofocus autocomplete="off">
                            <button type="button" class="btn btn-primary fw-semibold px-3" onclick="processScanInput()">
                                <i class="bi bi-arrow-return-left"></i> Enter
                            </button>
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.7rem;">
                            Arahkan scanner ke label barcode titipan atau SKU produk. Jika discan 2x, Qty otomatis bertambah jadi 2.
                        </div>
                    </div>
                </div>

                {{-- Opsi Manual Tambah jika Scanner Tidak Membaca --}}
                <div class="p-2 rounded-2 bg-light border">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-9">
                            <select id="manual_select_item" class="form-select form-select-sm select2">
                                <option value="">-- Atau Pilih Manual dari Daftar Batch Titipan --</option>
                                @foreach($availableItems as $item)
                                    @php
                                        $p = $item->masterProduct;
                                        $c = $item->consignment;
                                        $s = $c ? $c->supplier : null;
                                    @endphp
                                    <option value="{{ $item->id }}"
                                        data-supplier-id="{{ $c ? $c->supplier_id : '' }}"
                                        data-supplier-name="{{ $s ? $s->name : '-' }}"
                                        data-batch="{{ $c ? $c->reference_number : '-' }}"
                                        data-product-name="{{ $p ? $p->name : 'Produk' }}"
                                        data-sku="{{ $p ? $p->sku : '-' }}"
                                        data-sisa="{{ $item->sisa_stok }}"
                                        data-hpp="{{ (float)$item->unit_cost_price }}">
                                        [{{ $c ? $c->reference_number : '-' }}] {{ $p ? $p->name : '-' }} (SKU: {{ $p ? $p->sku : '-' }}) — Sisa: {{ $item->sisa_stok }} pcs ({{ $s ? $s->name : '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-semibold" onclick="addManualSelectedItem()">
                                <i class="bi bi-cart-plus me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Scan Feedback Banner --}}
                <div id="scan_feedback" class="mt-2 py-1.5 px-2.5 rounded-2 small d-none" style="font-size: 0.78rem;"></div>
            </div>

            {{-- ── 2. TABEL KERANJANG PENGELUARAN TITIPAN ── --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cart-check text-success fs-6"></i>
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Keranjang Pengeluaran Titipan</h6>
                        <span class="badge bg-danger text-white rounded-pill px-2 py-0.5" id="cart_badge_count" style="font-size: 0.68rem;">0 Item</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2" style="font-size: 0.72rem;" onclick="clearCart()">
                        <i class="bi bi-trash me-0.5"></i> Kosongkan
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="cart_table" style="font-size: 0.8rem;">
                        <thead class="bg-light text-muted" style="font-size: 0.72rem;">
                            <tr>
                                <th class="ps-3 py-2" style="width: 35px;">#</th>
                                <th class="py-2">PRODUK & SKU</th>
                                <th class="py-2">BATCH & SUPPLIER</th>
                                <th class="text-center py-2" style="width: 80px;">SISA</th>
                                <th class="text-center py-2" style="width: 130px;">QTY KELUAR</th>
                                <th class="text-end py-2" style="width: 110px;">SUBTOTAL HPP</th>
                                <th class="text-center pe-3 py-2" style="width: 45px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="cart_table_body">
                            <tr id="empty_cart_row">
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-upc-scan fs-3 d-block text-muted opacity-50 mb-1"></i>
                                    <div class="fw-semibold">Keranjang Masih Kosong</div>
                                    <div class="small">Scan barcode/SKU produk titipan di atas untuk menambahkan ke daftar ini.</div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-light fw-bold" id="cart_table_foot" style="display: none;">
                            <tr>
                                <td colspan="4" class="ps-3 text-end py-2">TOTAL KESELURUHAN:</td>
                                <td class="text-center py-2 text-danger fs-6" id="foot_total_qty">0 PCS</td>
                                <td class="text-end py-2 text-primary font-monospace" id="foot_total_hpp">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ── 3. ALASAN PENGELUARAN ── --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2.5 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="bi bi-question-circle text-danger"></i> 2. Alasan Pengeluaran
                </h6>

                <div class="row g-2 mb-2.5">
                    <div class="col-6 col-md-4">
                        <label class="reason-card d-block h-100 active">
                            <input type="radio" name="reason" value="unscanned_order" checked>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-qr-code-scan text-warning fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Terlewat Scan</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Pesanan kemas lupa discan</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="reason-card d-block h-100">
                            <input type="radio" name="reason" value="manual_adjustment">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-sliders text-info fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Koreksi Manual</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Penyesuaian stok selisih</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="reason-card d-block h-100">
                            <input type="radio" name="reason" value="return_supplier">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-return-left text-danger fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Retur ke Supplier</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Barang dikembalikan</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="reason-card d-block h-100">
                            <input type="radio" name="reason" value="damaged">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-x-octagon text-secondary fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Rusak / Hilang</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Cacat fisik / susut</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="reason-card d-block h-100">
                            <input type="radio" name="reason" value="direct_sale">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shop text-primary fs-5"></i>
                                <div>
                                    <div class="fw-bold small text-dark">Penjualan Langsung</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Offline / Toko fisik / Sample</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="reason-card d-block h-100">
                            <input type="radio" name="reason" value="other">
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

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.72rem;">Catatan Pengeluaran (Opsional)</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Contoh: Barang titipan Gilang keluar untuk order Shopee..." value="{{ old('notes') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.72rem;">Tanggal & Waktu Pengeluaran</label>
                        <input type="datetime-local" name="deduction_date" class="form-control form-control-sm" value="{{ old('deduction_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
            </div>

        </div>

        {{-- ── KOLOM KANAN: Opsi Gudang Master & Ringkasan ── --}}
        <div class="col-12 col-lg-4">

            {{-- Card Sinkronisasi Gudang Master --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2.5 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="bi bi-shield-check text-warning"></i> Opsi Sinkronisasi Gudang Master
                </h6>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="deduct_master_stock" id="deduct_master_stock" value="1" {{ old('deduct_master_stock') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold small text-dark" for="deduct_master_stock">
                        Kurangi juga Stok Fisik Gudang Master?
                    </label>
                </div>

                <div class="p-2.5 rounded-2 bg-light border text-muted small" style="font-size:0.72rem; line-height: 1.4;">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i>Petunjuk:</div>
                    <ul class="mb-0 ps-3">
                        <li><strong>JANGAN CENTANG (Rekomendasi)</strong> jika barang ini keluar untuk pesanan marketplace yang sudah selesai dipacking/diverifikasi scanner gudang. Stok master sudah terpotong otomatis saat scan order tersebut.</li>
                        <li class="mt-1"><strong>CENTANG</strong> jika barang ini benar-benar dikeluarkan fisik dari gudang di luar pesanan (seperti retur ke supplier, barang rusak/hilang, atau penjualan langsung offline).</li>
                    </ul>
                </div>
            </div>

            {{-- Card Ringkasan & Tombol Simpan --}}
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3 sticky-top" style="top: 80px;">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="bi bi-receipt text-danger"></i> Ringkasan Pengeluaran
                </h6>

                <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.8rem;">
                    <span class="text-muted">Total Variasi Produk:</span>
                    <span class="fw-bold text-dark" id="summary_item_count">0 Produk</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.8rem;">
                    <span class="text-muted">Total Qty Keluar:</span>
                    <span class="fw-bold text-danger fs-6" id="summary_total_qty">0 PCS</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-3" style="font-size: 0.82rem;">
                    <span class="text-muted">Total Nilai HPP:</span>
                    <span class="fw-bold text-primary font-monospace fs-6" id="summary_total_hpp">Rp 0</span>
                </div>

                <button type="submit" id="btn_submit_deduction" class="btn btn-danger w-100 py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background:#dc2626; border:none; border-radius:8px;" disabled>
                    <i class="bi bi-check-circle-fill"></i> Simpan Pengeluaran Stok
                </button>
                <div class="text-center mt-2">
                    <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="text-decoration-none small text-muted">
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
// Dataset Available Consignment Items dari Backend
const availableConsignmentItems = @json($availableItems->map(function($it) {
    $p = $it->masterProduct;
    $c = $it->consignment;
    $s = $c ? $c->supplier : null;
    return [
        'id'            => $it->id,
        'supplier_id'   => $c ? (string)$c->supplier_id : '',
        'supplier_name' => $s ? $s->name : '-',
        'batch_ref'     => $c ? $c->reference_number : '-',
        'product_id'    => $it->master_product_id,
        'product_name'  => $p ? $p->name : 'Produk',
        'sku'           => $p ? $p->sku : '-',
        'barcode'       => $p ? ($p->barcode ?: '') : '',
        'sisa_stok'     => (int)$it->sisa_stok,
        'hpp'           => (float)$it->unit_cost_price,
    ];
}));

// State Keranjang: Map by consignment_item_id => { item, quantity }
let cart = new Map();

// Audio Beep Effect via Web Audio API
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
function playBeep(success = true) {
    try {
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        if (success) {
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // High pitch cheerful beep
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.12);
        } else {
            osc.frequency.setValueAtTime(300, audioCtx.currentTime); // Low pitch warning beep
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.25);
        }
    } catch (e) {}
}

// Focus on Barcode Input on Load & click
document.addEventListener('DOMContentLoaded', function() {
    const scannerInput = document.getElementById('barcode_scanner_input');
    if (scannerInput) {
        scannerInput.focus();
        scannerInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processScanInput();
            }
        });
    }

    // Radio Card Reason switch
    document.querySelectorAll('.reason-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.reason-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
});

// Filter Available Products dropdown when Supplier Filter changes
function filterAvailableProducts() {
    const selectedSupplierId = document.getElementById('supplier_filter').value;
    const select = document.getElementById('manual_select_item');
    if (!select) return;

    for (let i = 0; i < select.options.length; i++) {
        const opt = select.options[i];
        if (!opt.value) continue;
        const supId = opt.getAttribute('data-supplier-id');
        if (!selectedSupplierId || supId === selectedSupplierId) {
            opt.style.display = '';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    }

    // Reset selection if hidden
    if (select.selectedIndex > 0 && select.options[select.selectedIndex].disabled) {
        select.selectedIndex = 0;
    }
}

// Parsing & Lookup Scanned Code
function findMatchingConsignmentItem(rawScan) {
    const scan = rawScan.trim();
    if (!scan) return null;

    const supplierFilter = document.getElementById('supplier_filter').value;

    // Filter available pool by supplier if selected
    let pool = availableConsignmentItems;
    if (supplierFilter) {
        pool = pool.filter(it => it.supplier_id === supplierFilter);
    }

    // 1. Cek Format Label Sticker: KNS|{ref}|ITEM-{id}|{sku}
    if (scan.includes('ITEM-')) {
        const parts = scan.split('|');
        for (let p of parts) {
            if (p.startsWith('ITEM-')) {
                const itemId = parseInt(p.replace('ITEM-', ''));
                const matched = pool.find(it => it.id === itemId);
                if (matched) return matched;
            }
        }
    }

    // 2. Cek Match Exact SKU (Case-Insensitive)
    const matchedBySku = pool.find(it => it.sku.toLowerCase() === scan.toLowerCase());
    if (matchedBySku) return matchedBySku;

    // 3. Cek Match Barcode Master Produk
    const matchedByBarcode = pool.find(it => it.barcode && it.barcode.toLowerCase() === scan.toLowerCase());
    if (matchedByBarcode) return matchedByBarcode;

    // 4. Cek Match Batch Ref
    const matchedByBatch = pool.find(it => it.batch_ref.toLowerCase() === scan.toLowerCase());
    if (matchedByBatch) return matchedByBatch;

    // 5. Cek jika scan cocok di pool global (supplier lain)
    if (supplierFilter) {
        const foundOther = availableConsignmentItems.find(it => 
            it.sku.toLowerCase() === scan.toLowerCase() || 
            (it.barcode && it.barcode.toLowerCase() === scan.toLowerCase())
        );
        if (foundOther) {
            showFeedback(`Produk ditemukan di Supplier: ${foundOther.supplier_name}. Ganti filter supplier jika ingin memproses batch ini.`, false);
            return null;
        }
    }

    return null;
}

// Proses Scan Input
function processScanInput() {
    const input = document.getElementById('barcode_scanner_input');
    const rawVal = input.value.trim();
    if (!rawVal) return;

    const item = findMatchingConsignmentItem(rawVal);
    if (!item) {
        playBeep(false);
        showFeedback(`Barcode / SKU "${rawVal}" tidak ditemukan dalam batch titipan aktif dengan sisa stok!`, false);
        input.value = '';
        input.focus();
        return;
    }

    addItemToCart(item);
    input.value = '';
    input.focus();
}

// Tambah Manual dari Dropdown
function addManualSelectedItem() {
    const select = document.getElementById('manual_select_item');
    const itemId = parseInt(select.value);
    if (!itemId) return;

    const item = availableConsignmentItems.find(it => it.id === itemId);
    if (item) {
        addItemToCart(item);
        select.value = '';
    }
}

// Tambah / Increment Item di Keranjang
function addItemToCart(item) {
    let currentQty = 0;
    if (cart.has(item.id)) {
        currentQty = cart.get(item.id).quantity;
    }

    const nextQty = currentQty + 1;

    // Cek batas sisa stok
    if (nextQty > item.sisa_stok) {
        playBeep(false);
        showFeedback(`Stok titipan "${item.product_name}" tidak cukup! Sisa stok batch hanya ${item.sisa_stok} pcs.`, false);
        return;
    }

    cart.set(item.id, {
        item: item,
        quantity: nextQty
    });

    playBeep(true);
    showFeedback(`✅ [${item.sku}] ${item.product_name} dimasukkan ke keranjang (Qty: ${nextQty} PCS)`, true);
    renderCart();

    // Flash row
    setTimeout(() => {
        const row = document.getElementById(`cart_row_${item.id}`);
        if (row) {
            row.classList.add('cart-row-flash');
            setTimeout(() => row.classList.remove('cart-row-flash'), 800);
        }
    }, 50);
}

// Ubah Qty langsung dari tombol +/- atau input
function updateCartQty(itemId, newQty) {
    itemId = parseInt(itemId);
    if (!cart.has(itemId)) return;

    const cartEntry = cart.get(itemId);
    const maxQty = cartEntry.item.sisa_stok;

    newQty = parseInt(newQty);
    if (isNaN(newQty) || newQty <= 0) {
        removeFromCart(itemId);
        return;
    }

    if (newQty > maxQty) {
        playBeep(false);
        alert(`Jumlah melebihi sisa stok titipan yang tersedia (${maxQty} pcs)!`);
        newQty = maxQty;
    }

    cartEntry.quantity = newQty;
    cart.set(itemId, cartEntry);
    renderCart();
}

// Hapus Item dari Keranjang
function removeFromCart(itemId) {
    itemId = parseInt(itemId);
    if (cart.has(itemId)) {
        cart.delete(itemId);
        renderCart();
    }
}

// Kosongkan Keranjang
function clearCart() {
    if (cart.size === 0) return;
    if (confirm('Kosongkan semua item dari keranjang pengeluaran?')) {
        cart.clear();
        renderCart();
        showFeedback('Keranjang telah dikosongkan.', true);
    }
}

// Render HTML Keranjang & Kalkulasi Total
function renderCart() {
    const tbody = document.getElementById('cart_table_body');
    const tfoot = document.getElementById('cart_table_foot');
    const badgeCount = document.getElementById('cart_badge_count');
    const summaryItemCount = document.getElementById('summary_item_count');
    const summaryTotalQty = document.getElementById('summary_total_qty');
    const summaryTotalHpp = document.getElementById('summary_total_hpp');
    const btnSubmit = document.getElementById('btn_submit_deduction');

    if (cart.size === 0) {
        tbody.innerHTML = `
            <tr id="empty_cart_row">
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-upc-scan fs-3 d-block text-muted opacity-50 mb-1"></i>
                    <div class="fw-semibold">Keranjang Masih Kosong</div>
                    <div class="small">Scan barcode/SKU produk titipan di atas untuk menambahkan ke daftar ini.</div>
                </td>
            </tr>
        `;
        tfoot.style.display = 'none';
        badgeCount.innerText = '0 Item';
        summaryItemCount.innerText = '0 Produk';
        summaryTotalQty.innerText = '0 PCS';
        summaryTotalHpp.innerText = 'Rp 0';
        btnSubmit.disabled = true;
        return;
    }

    let rowsHtml = '';
    let no = 1;
    let totalQty = 0;
    let totalHpp = 0;

    cart.forEach((entry, itemId) => {
        const item = entry.item;
        const qty = entry.quantity;
        const subtotalHpp = qty * item.hpp;

        totalQty += qty;
        totalHpp += subtotalHpp;

        rowsHtml += `
            <tr id="cart_row_${itemId}">
                <td class="ps-3 py-2 text-muted small">${no++}</td>
                <td class="py-2">
                    <input type="hidden" name="items[${itemId}][supplier_consignment_item_id]" value="${itemId}">
                    <div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="${item.product_name}">${item.product_name}</div>
                    <span class="badge bg-light text-secondary border font-monospace" style="font-size:0.65rem;">${item.sku}</span>
                </td>
                <td class="py-2">
                    <div class="font-monospace small text-primary fw-semibold"><i class="bi bi-box-seam me-0.5"></i>${item.batch_ref}</div>
                    <div class="text-muted small" style="font-size:0.7rem;">${item.supplier_name}</div>
                </td>
                <td class="text-center py-2">
                    <span class="badge bg-light text-dark border" style="font-size:0.7rem;">${item.sisa_stok} pcs</span>
                </td>
                <td class="text-center py-2">
                    <div class="input-group input-group-sm d-inline-flex" style="width: 110px;">
                        <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="updateCartQty(${itemId}, ${qty - 1})">-</button>
                        <input type="number" name="items[${itemId}][quantity]" class="form-control text-center fw-bold py-0 px-1" value="${qty}" min="1" max="${item.sisa_stok}" onchange="updateCartQty(${itemId}, this.value)">
                        <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="updateCartQty(${itemId}, ${qty + 1})">+</button>
                    </div>
                </td>
                <td class="text-end py-2 font-monospace fw-semibold text-primary" style="font-size: 0.8rem;">
                    Rp ${subtotalHpp.toLocaleString('id-ID')}
                </td>
                <td class="text-center pe-3 py-2">
                    <button type="button" class="btn btn-sm btn-outline-danger p-0.5 px-1.5" title="Hapus" onclick="removeFromCart(${itemId})" style="border-radius: 4px;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    tfoot.style.display = '';

    document.getElementById('foot_total_qty').innerText = `${totalQty} PCS`;
    document.getElementById('foot_total_hpp').innerText = `Rp ${totalHpp.toLocaleString('id-ID')}`;

    badgeCount.innerText = `${cart.size} Item`;
    summaryItemCount.innerText = `${cart.size} Produk`;
    summaryTotalQty.innerText = `${totalQty} PCS`;
    summaryTotalHpp.innerText = `Rp ${totalHpp.toLocaleString('id-ID')}`;
    btnSubmit.disabled = false;
}

// Feedback banner notification
function showFeedback(msg, isSuccess = true) {
    const el = document.getElementById('scan_feedback');
    if (!el) return;
    el.className = isSuccess 
        ? 'mt-2 py-1.5 px-2.5 rounded-2 small bg-success-subtle text-success border border-success-subtle d-block'
        : 'mt-2 py-1.5 px-2.5 rounded-2 small bg-danger-subtle text-danger border border-danger-subtle d-block';
    el.innerHTML = `<i class="bi ${isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-1"></i> ${msg}`;

    setTimeout(() => {
        el.className = 'd-none';
    }, 4000);
}
</script>
@endpush
