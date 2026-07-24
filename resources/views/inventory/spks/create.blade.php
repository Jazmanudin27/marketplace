@extends('layouts.app')
@section('title', 'Buat SPK Baru')
@section('page-title', 'Buat SPK Baru')

@section('content')
    <div class="container-fluid py-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div
                class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2"></i>Buat SPK Baru</h5>
                    <small class="text-white-50">Isi data produksi, rancangan, dan rincian biaya HPP per item.</small>
                </div>
                <a href="{{ route('spks.index') }}" class="btn btn-sm btn-light fw-semibold px-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('spks.store') }}" method="POST" enctype="multipart/form-data" id="spkForm">
                @csrf
                @if (isset($order))
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                @endif
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── BARIS ATAS: 3 Card Panel Sesuai Tata Letak Transaksi ── --}}
                    <div class="row g-3 mb-4">
                        {{-- CARD 1: DATA TRANSAKSI / SPK --}}
                        <div class="col-lg-4 col-md-6">
                            <div class="card border h-100 shadow-sm">
                                <div class="card-header bg-white border-bottom py-2 font-monospace fw-bold text-uppercase small text-muted">
                                    <i class="fas fa-file-invoice text-primary me-2"></i>DATA TRANSAKSI &amp; SPK
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small mb-1">No. Produksi</label>
                                            <input type="text" class="form-control form-control-sm bg-light text-muted" readonly
                                                value="[Otomatis: JN{{ date('ym') }}xxx]">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small mb-1">No. SPK</label>
                                            <input type="text" class="form-control form-control-sm bg-light text-muted" readonly
                                                value="[Otomatis]">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small mb-1">Tanggal Order <span class="text-danger">*</span></label>
                                            <input type="date" name="tanggal" class="form-control form-control-sm" required
                                                value="{{ old('tanggal', date('Y-m-d')) }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small mb-1">Deadline <span class="text-danger">*</span></label>
                                            <input type="date" name="deadline" class="form-control form-control-sm" required
                                                value="{{ old('deadline', date('Y-m-d', strtotime('+14 days'))) }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small mb-1">PIC / Pembuat SPK</label>
                                            <input type="text" class="form-control form-control-sm bg-light text-muted fw-bold" readonly
                                                value="{{ Auth::user()->name ?? '—' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small mb-1">Upload Foto Desain</label>
                                            <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                                            <small class="text-muted d-block mt-1" style="font-size:10px;">JPEG/PNG/JPG, maks 4MB</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 2: DATA PEMESAN --}}
                        <div class="col-lg-4 col-md-6">
                            <div class="card border h-100 shadow-sm">
                                <div class="card-header bg-white border-bottom py-2 font-monospace fw-bold text-uppercase small text-muted">
                                    <i class="fas fa-user-tie text-success me-2"></i>DATA PEMESAN
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small mb-1">Pelanggan / Pemesan <span class="text-danger">*</span></label>
                                            <input type="text" name="pemesan" class="form-control form-control-sm"
                                                placeholder="Contoh: Ibu Yanti" value="{{ old('pemesan', $order->buyer_name ?? '') }}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small mb-1">No HP</label>
                                            <input type="text" name="no_hp_pemesan" class="form-control form-control-sm"
                                                placeholder="0852-xxxx-xxxx" value="{{ old('no_hp_pemesan', $order->buyer_phone ?? '') }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small mb-1">Toko / Channel <span class="text-danger">*</span></label>
                                            <select name="instansi" class="form-select form-select-sm" required>
                                                <option value="">— Pilih Toko Pemesan —</option>
                                                @php 
                                                    $selectedStore = old('instansi', isset($order) && $order->store ? $order->store->store_name : '');
                                                @endphp
                                                @foreach($stores as $st)
                                                    @php $sName = $st->store_name . ($st->channel ? ' (' . $st->channel->name . ')' : ''); @endphp
                                                    <option value="{{ $st->store_name }}" {{ $selectedStore == $st->store_name ? 'selected' : '' }}>
                                                        {{ $sName }}
                                                    </option>
                                                @endforeach
                                                <option value="POS / Penjualan Offline" {{ $selectedStore == 'POS / Penjualan Offline' ? 'selected' : '' }}>POS / Penjualan Offline</option>
                                                <option value="Pesanan Direct / Whatsapp" {{ $selectedStore == 'Pesanan Direct / Whatsapp' ? 'selected' : '' }}>Pesanan Direct / Whatsapp</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small mb-1">Aksesoris / Atribut Tambahan</label>
                                            <textarea name="tambahan" class="form-control form-control-sm" rows="2"
                                                placeholder="Misal: Bordir Logo 46 Pcs, Kancing Emas...">{{ old('tambahan', isset($order) ? 'Diproduksi untuk Pesanan #' . ($order->invoice_number ?? $order->order_marketplace_id) : '') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 3: TOTAL & KALKULASI HPP SPK (White Card) --}}
                        <div class="col-lg-4 col-md-12">
                            <div class="card border h-100 shadow-sm bg-white">
                                <div class="card-header bg-white border-bottom py-2 font-monospace fw-bold text-uppercase small text-muted">
                                    <i class="fas fa-calculator text-success me-2"></i>KALKULASI HPP SPK
                                </div>
                                <div class="card-body p-3 d-flex flex-column justify-content-center">
                                    <div class="mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px;">TOTAL QTY PRODUKSI</span>
                                        <h3 class="fw-bold font-monospace text-dark mb-0" id="summaryTotalQty">0 Pcs</h3>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px;">GRAND TOTAL BIAYA SPK</span>
                                        <h4 class="fw-bold font-monospace text-primary mb-0" id="summaryGrandTotalCost">Rp 0</h4>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px;">ESTIMASI HPP PER UNIT</span>
                                        <h2 class="fw-bold font-monospace text-success mb-0" id="summaryAllocatedHpp">Rp 0 / Unit</h2>
                                        <small class="text-muted d-block mt-1" style="font-size: 10px;">(Grand Total Biaya ÷ Total Qty SPK secara otomatis)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── BARIS KEDUA: INPUT ITEM BARANG & SATUAN ── --}}
                    <div class="card border shadow-sm mb-4">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark text-uppercase small font-monospace">
                                <i class="fas fa-boxes text-primary me-2"></i>INPUT ITEM BARANG &amp; TASK PRODUKSI
                            </span>
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm" id="btnAddRow">
                                <i class="fas fa-plus-circle me-1"></i> Tambah Item Baru
                            </button>
                        </div>
                        <div class="card-body p-3">
                            <div id="itemsContainer">
                                {{-- Dynamic cards will be generated here --}}
                            </div>
                        </div>
                    </div>

                    {{-- ── BARIS KETIGA: SETTING BIAYA SPK DI AKHIR (TAMBAHAN JASA & BAHAN) ── --}}
                    <div class="card border shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-calculator text-primary me-2"></i>Setting Biaya SPK di Akhir (Tambahan Jasa &amp; Bahan / Material)</h6>
                                <small class="text-muted">Tentukan total biaya Jasa &amp; Bahan untuk 1 dokumen SPK ini. Sistem akan otomatis membagi total biaya dengan Total Qty SPK untuk menetapkan HPP per unit.</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 font-monospace fw-bold fs-7">
                                Kalkulasi HPP SPK
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-4">
                                {{-- Seksi Tambahan Jasa --}}
                                <div class="col-md-6">
                                    <div class="card border shadow-sm h-100 bg-white">
                                        <div class="card-header bg-primary bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                            <span class="fw-bold small text-primary"><i class="fas fa-user-tie me-1"></i>1. Tambahan Jasa (Jahit, QC, Finishing, Bordir, dll)</span>
                                            <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 fw-semibold" id="btnAddGlobalJasa">
                                                <i class="fas fa-plus me-1"></i> Tambah Jasa
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <div id="globalJasaContainer">
                                                {{-- Dynamic rows for global Jasa --}}
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                                <span class="small fw-semibold text-secondary">Subtotal Jasa SPK:</span>
                                                <span class="fw-bold font-monospace text-primary" id="subtotalJasaLabel">Rp 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Seksi Tambahan Bahan --}}
                                <div class="col-md-6">
                                    <div class="card border shadow-sm h-100 bg-white">
                                        <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                            <span class="fw-bold small text-info"><i class="fas fa-layer-group me-1"></i>2. Tambahan Bahan / Material (Benang, Kancing, Packing)</span>
                                            <button type="button" class="btn btn-outline-info btn-xs py-0 px-2 fw-semibold" id="btnAddGlobalBahan">
                                                <i class="fas fa-plus me-1"></i> Tambah Bahan
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <div id="globalBahanContainer">
                                                {{-- Dynamic rows for global Bahan --}}
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                                <span class="small fw-semibold text-secondary">Subtotal Bahan SPK:</span>
                                                <span class="fw-bold font-monospace text-info" id="subtotalBahanLabel">Rp 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer bg-light py-3 px-4 d-flex justify-content-end gap-2 border-0">
                    <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary px-3">Batal</a>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-1"></i> Simpan SPK
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Data JSON untuk autocomplete --}}
    <script>
        const catalogProducts = @json($products);
        const tailorsList = @json($tailors);
        const laborServicesData = @json($laborServices ?? []);
    </script>

    <script>
        let rowIndex = 0;

        function escapeHtml(t) {
            if (!t) return '';
            return String(t)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function formatRupiah(num) {
            if (!num && num !== 0) return '0';
            let val = Math.round(num).toString().replace(/[^0-9]/g, '');
            let sisa = val.length % 3;
            let rupiah = val.substr(0, sisa);
            let ribuan = val.substr(sisa).match(/\d{3}/gi);
            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return rupiah;
        }

        function getCleanNumber(str) {
            if (!str) return 0;
            let clean = String(str).replace(/\./g, '').replace(/,/g, '.');
            return parseFloat(clean) || 0;
        }

        function tailorOpts() {
            let o = '<option value="">— Pilih Penjahit —</option>';
            tailorsList.forEach(e => {
                o += `<option value="${escapeHtml(e.name)}">${escapeHtml(e.name)}</option>`;
            });
            return o;
        }

        // ——— Hitung Global SPK Costs (Jasa & Bahan) ———
        let globalJasaIndex = 0;
        let globalBahanIndex = 0;

        function getAllocatedHppPerUnit() {
            let totalQty = 0;
            $('.item-qty').each(function() {
                totalQty += parseInt($(this).val()) || 0;
            });

            let totalJasa = 0;
            $('#globalJasaContainer .global-jasa-nominal').each(function() {
                totalJasa += getCleanNumber($(this).val());
            });

            let totalBahan = 0;
            $('#globalBahanContainer .global-bahan-nominal').each(function() {
                totalBahan += getCleanNumber($(this).val());
            });

            const grandTotal = totalJasa + totalBahan;
            return totalQty > 0 ? (grandTotal / totalQty) : 0;
        }

        function recalcGlobalSpkCosts() {
            let totalQty = 0;
            $('.item-qty').each(function() {
                totalQty += parseInt($(this).val()) || 0;
            });

            let totalJasa = 0;
            $('#globalJasaContainer .global-jasa-nominal').each(function() {
                totalJasa += getCleanNumber($(this).val());
            });

            let totalBahan = 0;
            $('#globalBahanContainer .global-bahan-nominal').each(function() {
                totalBahan += getCleanNumber($(this).val());
            });

            const grandTotal = totalJasa + totalBahan;
            const allocatedPerUnit = totalQty > 0 ? Math.round(grandTotal / totalQty) : 0;

            $('#subtotalJasaLabel').text('Rp ' + formatRupiah(totalJasa));
            $('#subtotalBahanLabel').text('Rp ' + formatRupiah(totalBahan));
            $('#summaryTotalQty').text(totalQty + ' Pcs');
            $('#summaryGrandTotalCost').text('Rp ' + formatRupiah(grandTotal));
            $('#summaryAllocatedHpp').text('Rp ' + formatRupiah(allocatedPerUnit) + ' / Unit');

            // Recalculate HPP for each item card
            $('.item-card').each(function() {
                recalcHpp(this);
            });
        }

        function addGlobalJasaRow(ket = '', nom = 0) {
            const idx = globalJasaIndex++;
            const html = `
    <div class="row g-1 mb-2 global-jasa-row align-items-center">
        <div class="col">
            <input type="text" name="global_jasa[${idx}][keterangan]" class="form-control form-control-sm global-jasa-ket" 
                placeholder="Nama Jasa (misal: Jahit, QC, Bordir)" value="${escapeHtml(ket)}">
        </div>
        <div class="col-auto" style="width:140px;">
            <input type="text" name="global_jasa[${idx}][nominal]" class="form-control form-control-sm global-jasa-nominal rupiah-mask text-end" 
                placeholder="0" value="${nom > 0 ? formatRupiah(nom) : '0'}">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-global-jasa py-0 px-2 fs-7">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>`;
            $('#globalJasaContainer').append(html);
            recalcGlobalSpkCosts();
        }

        function addGlobalBahanRow(ket = '', nom = 0) {
            const idx = globalBahanIndex++;
            const html = `
    <div class="row g-1 mb-2 global-bahan-row align-items-center">
        <div class="col">
            <input type="text" name="global_bahan[${idx}][keterangan]" class="form-control form-control-sm global-bahan-ket" 
                placeholder="Nama Bahan (misal: Benang, Kancing, Packaging)" value="${escapeHtml(ket)}">
        </div>
        <div class="col-auto" style="width:140px;">
            <input type="text" name="global_bahan[${idx}][nominal]" class="form-control form-control-sm global-bahan-nominal rupiah-mask text-end" 
                placeholder="0" value="${nom > 0 ? formatRupiah(nom) : '0'}">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-global-bahan py-0 px-2 fs-7">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>`;
            $('#globalBahanContainer').append(html);
            recalcGlobalSpkCosts();
        }

        // ——— Hitung HPP per baris ———
        function recalcHpp(card) {
            let itemExtras = 0;
            $(card).find('.extra-nominal').each(function() {
                itemExtras += getCleanNumber($(this).val());
            });

            const allocatedGlobal = getAllocatedHppPerUnit();
            const qty = parseInt($(card).find('.item-qty').val()) || 1;
            const hppPerUnit = Math.round(allocatedGlobal + itemExtras);

            $(card).find('.hpp-per-pcs').text('Rp ' + formatRupiah(hppPerUnit));
            $(card).find('.hpp-subtotal').text('Rp ' + formatRupiah(hppPerUnit * qty));
        }

        // ——— Buat Card Item Baru ———
        function addRow() {
            const idx = rowIndex++;
            const html = `
    <div class="card border border-light-subtle shadow-sm mb-3 item-card" id="item-card-${idx}">
        <div class="card-header bg-light-subtle py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark small"><i class="fas fa-box text-primary me-2"></i>Item #<span class="item-num">${idx+1}</span></span>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row py-0 px-2 fs-7">
                <i class="fas fa-trash-alt me-1"></i>Hapus
            </button>
        </div>
        <div class="card-body p-3 bg-white">

            {{-- ── Identitas Utama ── --}}
            <div class="row g-2 mb-3">
                <div class="col-md-4 position-relative">
                    <label class="form-label small fw-semibold mb-1">Nama Produk / Katalog <span class="text-danger">*</span></label>
                    <input type="text" name="items[${idx}][name]" class="form-control form-control-sm item-name" required
                        placeholder="Ketik nama / SKU..." autocomplete="off">
                    <div class="suggestions-box position-absolute bg-white border rounded shadow-sm w-100 d-none"
                        style="z-index:1050;max-height:180px;overflow-y:auto;top:100%;left:0;"></div>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small fw-semibold mb-1">SKU Induk</label>
                    <input type="text" name="items[${idx}][sku_induk]" class="form-control form-control-sm item-sku-induk" placeholder="Induk">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small fw-semibold mb-1">SKU Varian</label>
                    <input type="text" name="items[${idx}][sku]" class="form-control form-control-sm item-sku" placeholder="Varian">
                </div>
                <div class="col-md-1 col-6">
                    <label class="form-label small fw-semibold mb-1">Ukuran</label>
                    <select name="items[${idx}][size]" class="form-select form-select-sm item-size">
                        <option value="S">S</option><option value="M">M</option>
                        <option value="L" selected>L</option><option value="XL">XL</option>
                        <option value="XXL">XXL</option><option value="3XL">3XL</option>
                        <option value="All Size">All Size</option>
                    </select>
                </div>
                <div class="col-md-1 col-6">
                    <label class="form-label small fw-semibold mb-1">Qty</label>
                    <input type="number" name="items[${idx}][qty]" class="form-control form-control-sm item-qty text-center" required min="1" value="1">
                </div>
            </div>

            {{-- ── Penjahit & HPP Summary ── --}}
            <div class="row g-2 align-items-center">
                <div class="col-md-8 text-end pt-3">
                    <button class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1-5 collapsed" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#hppCollapse-${idx}">
                        <i class="fas fa-calculator me-1"></i> Rincian HPP / Pcs: <strong class="text-success hpp-per-pcs ms-1">Rp 0</strong> <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    <span class="ms-3 small text-muted">Subtotal: <strong class="text-primary hpp-subtotal">Rp 0</strong></span>
                </div>
            </div>

            {{-- ── Collapsible HPP Details Dynamic Panel ── --}}
            <div class="collapse mt-3 border rounded-3 p-3 bg-light" id="hppCollapse-${idx}">
                <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="small fw-bold text-secondary" style="font-size:11px;">
                            <i class="fas fa-boxes text-primary me-1"></i>Komponen Biaya Produksi (BOM &amp; Jasa)
                        </span>
                    </div>
                    <div class="extras-list mb-2"></div>
                </div>
            </div>

        </div>
    </div>`;

            $('#itemsContainer').append(html);
            renumberItems();
        }

        // ——— Renumber item headers ———
        function renumberItems() {
            $('.item-card').each(function(i) {
                $(this).find('.item-num').text(i + 1);
            });
        }

        // ——— Tambah baris extra ke card ———
        function addExtraRow(card) {
            const idx = $(card).attr('id').replace('item-card-', '');
            const eIdx = 'ex_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            
            let optionsHtml = '<option value=""></option>';
            laborServicesData.forEach(function(item) {
                optionsHtml += `<option value="${item.name}" data-cost="${parseInt(item.default_cost) || 0}">${item.name}</option>`;
            });

            const html = `
    <div class="row g-1 mb-1 extra-row align-items-center">
        <div class="col">
            <select name="items[${idx}][extras][${eIdx}][keterangan]"
                class="form-select form-select-sm select-extra-keterangan" style="width: 100%;">
                ${optionsHtml}
            </select>
        </div>
        <div class="col-auto" style="width:130px;">
            <input type="text" name="items[${idx}][extras][${eIdx}][nominal]"
                class="form-control form-control-sm extra-nominal rupiah-mask text-end" value="0" placeholder="0">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-extra py-0 px-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>`;
            const $row = $(html);
            $(card).find('.extras-list').append($row);

            $row.find('.select-extra-keterangan').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih Jasa / Ketik Biaya...',
                tags: true,
                allowClear: true
            });
        }

        // ——— Tambah baris extra dengan nilai bawaan ———
        function addExtraRowWithValues(card, desc, val) {
            const idx = $(card).attr('id').replace('item-card-', '');
            const eIdx = 'ex_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            const html = `
    <div class="row g-1 mb-1 extra-row align-items-center">
        <div class="col">
            <input type="text" name="items[${idx}][extras][${eIdx}][keterangan]"
                class="form-control form-control-sm" placeholder="Keterangan biaya tambahan..." value="${escapeHtml(desc)}">
        </div>
        <div class="col-auto" style="width:130px;">
            <input type="text" name="items[${idx}][extras][${eIdx}][nominal]"
                class="form-control form-control-sm extra-nominal rupiah-mask text-end" value="${formatRupiah(val)}" placeholder="0">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-extra py-0 px-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>`;
            $(card).find('.extras-list').append(html);
        }

        // ——— jQuery Ready ———
        $(document).ready(function() {

            // Populate from order if set, otherwise add a single blank row
            @if(isset($order))
                @foreach($order->items as $item)
                    (function() {
                        addRow();
                        const $card = $('.item-card').last();
                        $card.find('.item-name').val("{{ $item->product_name }}");
                        $card.find('.item-sku').val("{{ $item->sku }}");
                        $card.find('.item-sku-induk').val("{{ $item->sku_induk }}");
                        $card.find('.item-qty').val("{{ $item->quantity }}");
                        
                        @if($item->ukuran)
                            const sizeVal = "{{ $item->ukuran }}";
                            const $sel = $card.find('.item-size');
                            let found = false;
                            $sel.find('option').each(function() {
                                if ($(this).val().toUpperCase() === sizeVal.toUpperCase()) {
                                    $sel.val($(this).val());
                                    found = true;
                                    return false;
                                }
                            });
                            if (!found) {
                                $sel.append(`<option value="${sizeVal}">${sizeVal}</option>`);
                                $sel.val(sizeVal);
                            }
                        @endif

                        // Auto-populate costs using matching catalog product
                        const matchedProduct = catalogProducts.find(p => p.sku === "{{ $item->sku }}");
                        if (matchedProduct) {
                            $card.find('.item-name').val(matchedProduct.name);
                            if (matchedProduct.active_recipe) {
                                const recipe = matchedProduct.active_recipe;
                                const batchQty = Math.max(1, parseInt(recipe.batch_qty) || 1);

                                // 1. Map Labors
                                if (recipe.labors && recipe.labors.length > 0) {
                                    recipe.labors.forEach(labor => {
                                        const cost = parseFloat(labor.default_cost || 0) / batchQty;
                                        addExtraRowWithValues($card, labor.service_name, cost);
                                    });
                                }

                                // 2. Map Materials
                                if (recipe.items && recipe.items.length > 0) {
                                    recipe.items.forEach(rItem => {
                                        const invItem = rItem.inventory_item;
                                        if (invItem) {
                                            const itemQty = parseFloat(rItem.quantity || 0) / batchQty;
                                            const itemCost = itemQty * parseFloat(invItem.cost_price || 0);
                                            addExtraRowWithValues($card, 'Bahan: ' + invItem.name + ' (' + itemQty.toFixed(2) + ' ' + (invItem.unit || 'm') + '/pcs)', itemCost);
                                        }
                                    });
                                }
                            } else if (matchedProduct.cost_price) {
                                addExtraRowWithValues($card, 'Bahan: Kain', parseFloat(matchedProduct.cost_price) || 0);
                            }
                        }
                        recalcHpp($card);
                    })();
                @endforeach
            @else
                addRow();
            @endif

            // Tambah item baru
            $(document).on('click', '#btnAddRow', function(e) {
                e.preventDefault();
                addRow();
                recalcGlobalSpkCosts();
            });

            // Hapus item
            $(document).on('click', '.btn-remove-row', function() {
                if ($('.item-card').length <= 1) {
                    alert('Minimal 1 item harus ada.');
                    return;
                }
                $(this).closest('.item-card').remove();
                renumberItems();
                recalcGlobalSpkCosts();
            });

            // Event handlers for Global Jasa & Bahan
            $('#btnAddGlobalJasa').on('click', function() {
                addGlobalJasaRow();
            });

            $('#btnAddGlobalBahan').on('click', function() {
                addGlobalBahanRow();
            });

            $(document).on('click', '.btn-remove-global-jasa, .btn-remove-global-bahan', function() {
                $(this).closest('.row').remove();
                recalcGlobalSpkCosts();
            });

            $(document).on('keyup input change', '.global-jasa-nominal, .global-bahan-nominal, .global-jasa-ket, .global-bahan-ket, .item-qty', function() {
                recalcGlobalSpkCosts();
            });

            // Initialize default global Jasa & Bahan rows if empty
            if ($('#globalJasaContainer .global-jasa-row').length === 0) {
                addGlobalJasaRow('Jasa Jahit', 0);
                addGlobalJasaRow('Jasa QC & Finishing', 0);
            }
            if ($('#globalBahanContainer .global-bahan-row').length === 0) {
                addGlobalBahanRow('Benang & Aksesoris', 0);
                addGlobalBahanRow('Packaging & Label', 0);
            }

            // Tambah extra
            $(document).on('click', '.btn-add-extra', function() {
                addExtraRow($(this).closest('.item-card'));
            });

            // Hapus extra
            $(document).on('click', '.btn-remove-extra', function() {
                const card = $(this).closest('.item-card');
                $(this).closest('.extra-row').remove();
                recalcHpp(card);
            });

            // Update extra nominal when a master labor service is selected
            $(document).on('change', '.select-extra-keterangan', function() {
                const row = $(this).closest('.extra-row');
                const selected = $(this).find('option:selected');
                const card = $(this).closest('.item-card');
                if (selected.val() && selected.data('cost') !== undefined) {
                    const cost = parseFloat(selected.data('cost')) || 0;
                    row.find('.extra-nominal').val(formatRupiah(cost)).trigger('change');
                }
            });

            // Format Rupiah mask as user types
            $(document).on('keyup input', '.rupiah-mask', function() {
                let clean = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(formatRupiah(clean));
            });

            // Clean inputs before submit so Laravel validation gets raw float decimals
            $('#spkForm').on('submit', function() {
                $('.rupiah-mask').each(function() {
                    let rawVal = getCleanNumber($(this).val());
                    $(this).val(rawVal);
                });
            });

            // Hitung HPP saat biaya berubah
            $(document).on('input change keyup', '.cost-field, .extra-nominal, .item-qty', function() {
                recalcHpp($(this).closest('.item-card'));
            });

            // ——— Autocomplete nama produk ———
            $(document).on('input focus', '.item-name', function() {
                const $input = $(this);
                const $card = $input.closest('.item-card');
                const $box = $card.find('.suggestions-box');
                const q = $input.val().trim().toLowerCase();

                $box.empty();
                if (q.length === 0) {
                    $box.addClass('d-none');
                    return;
                }

                const matches = catalogProducts.filter(p =>
                    (p.name || '').toLowerCase().includes(q) ||
                    (p.sku || '').toLowerCase().includes(q) ||
                    (p.sku_induk || '').toLowerCase().includes(q)
                ).slice(0, 10);

                if (!matches.length) {
                    $box.append(
                        '<div class="p-2 text-muted text-center small">Produk tidak ditemukan</div>');
                } else {
                    matches.forEach(p => {
                        const $item = $(
                            '<div class="p-2 border-bottom suggestion-item" style="cursor:pointer;">' +
                            `<div class="fw-bold small">${escapeHtml(p.name)}</div>` +
                            `<div class="text-muted" style="font-size:10px;">SKU: ${escapeHtml(p.sku||'—')} | Induk: ${escapeHtml(p.sku_induk||'—')}</div>` +
                            '</div>');
                        $item.on('click', function() {
                            $input.val(p.name);
                            $card.find('.item-sku').val(p.sku || '');
                            $card.find('.item-sku-induk').val(p.sku_induk || '');

                            // Clear existing dynamic extras first
                            $card.find('.extras-list').empty();

                            // Reset all fields to 0 first
                            $card.find('.cost-field').val('0');
                            $card.find('[data-field="kebutuhan_kain"]').val('0');

                            if (p.active_recipe) {
                                // AUTO-POPULATE: Ambil dari Resep / BOM Aktif
                                const recipe = p.active_recipe;
                                const batchQty = Math.max(1, parseInt(recipe.batch_qty) || 1);

                                // 1. Map Labors (Services)
                                if (recipe.labors && recipe.labors.length > 0) {
                                    recipe.labors.forEach(labor => {
                                        const cost = parseFloat(labor.default_cost || 0) / batchQty;
                                        addExtraRowWithValues($card, labor.service_name, cost);
                                    });
                                }

                                // 2. Map Materials (Bahan)
                                if (recipe.items && recipe.items.length > 0) {
                                    recipe.items.forEach(rItem => {
                                        const invItem = rItem.inventory_item;
                                        if (invItem) {
                                            const itemQty = parseFloat(rItem.quantity || 0) / batchQty;
                                            const itemCost = itemQty * parseFloat(invItem.cost_price || 0);
                                            addExtraRowWithValues($card, 'Bahan: ' + invItem.name + ' (' + itemQty.toFixed(2) + ' ' + (invItem.unit || 'm') + '/pcs)', itemCost);
                                        }
                                    });
                                }
                            } else if (p.latest_costs && p.latest_costs.length > 0) {
                                // AUTO-POPULATE: Ambil rincian biaya dari riwayat input SPK terakhir untuk produk ini
                                p.latest_costs.forEach(extra => {
                                    addExtraRowWithValues($card, extra.keterangan, extra.nominal);
                                });
                            } else if (p.cost_price) {
                                // Default biaya kain dari Master Product catalog cost_price jika ada
                                addExtraRowWithValues($card, 'Bahan: Kain', parseFloat(p.cost_price) || 0);
                            }

                            // Set ukuran
                            if (p.ukuran) {
                                const $sel = $card.find('.item-size');
                                const t = p.ukuran.toUpperCase();
                                let found = false;
                                $sel.find('option').each(function() {
                                    if ($(this).val().toUpperCase() === t) {
                                        $sel.val($(this).val());
                                        found = true;
                                        return false;
                                    }
                                });
                                if (!found) {
                                    $sel.append(
                                        `<option value="${p.ukuran}">${p.ukuran}</option>`
                                    );
                                    $sel.val(p.ukuran);
                                }
                            }
                            recalcHpp($card);
                            $box.addClass('d-none');
                        });
                        $box.append($item);
                    });
                }
                $box.removeClass('d-none');
            });

            // Tutup suggestions saat klik luar
            $(document).on('click', function(e) {
                if (!$(e.target).hasClass('item-name')) {
                    $('.suggestions-box').addClass('d-none');
                }
            });

            // Hover suggestions
            $(document).on('mouseenter', '.suggestion-item', function() {
                    $(this).addClass('bg-light');
                })
                .on('mouseleave', '.suggestion-item', function() {
                    $(this).removeClass('bg-light');
                });
        });
    </script>
@endsection
