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
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── Header SPK ─────────────────────────────────── --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold small">No. Produksi</label>
                            <input type="text" class="form-control form-control-sm bg-light text-muted" readonly
                                value="[Otomatis: JN{{ date('ym') }}xxx]">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold small">No. SPK</label>
                            <input type="text" class="form-control form-control-sm bg-light text-muted" readonly
                                value="[Otomatis Digenerate Sistem]">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold small">Tanggal Order <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" required
                                value="{{ old('tanggal', date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-semibold small">Deadline <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" class="form-control form-control-sm" required
                                value="{{ old('deadline', date('Y-m-d', strtotime('+14 days'))) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Nama Pemesan</label>
                            <input type="text" name="pemesan" class="form-control form-control-sm"
                                placeholder="Contoh: Ibu Yanti" value="{{ old('pemesan') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">No. HP Pemesan</label>
                            <input type="text" name="no_hp_pemesan" class="form-control form-control-sm"
                                placeholder="0852-xxxx-xxxx" value="{{ old('no_hp_pemesan') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Instansi</label>
                            <input type="text" name="instansi" class="form-control form-control-sm"
                                placeholder="Contoh: Nusantara Seragam" value="{{ old('instansi') }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Aksesoris / Atribut Tambahan</label>
                            <textarea name="tambahan" class="form-control form-control-sm" rows="2"
                                placeholder="Misal: Bordir Logo 46 Pcs, Kancing Emas...">{{ old('tambahan') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Upload Foto Desain</label>
                            <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                            <small class="text-muted d-block mt-1" style="font-size:10px;">JPEG/PNG/JPG, maks 4MB</small>
                        </div>
                    </div>

                    {{-- ── Detail Item Produk ─────────────────────────── --}}
                    <div class="border-top pt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-boxes text-primary me-2"></i>Daftar Item Produk &amp; Tugas Produksi
                            </h6>
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm" id="btnAddRow">
                                <i class="fas fa-plus-circle me-1"></i> Tambah Item Baru
                            </button>
                        </div>

                        <div id="itemsContainer">
                            {{-- Dynamic cards will be generated here --}}
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

        // ——— Hitung HPP per baris ———
        function recalcHpp(card) {
            const fields = [
                'jasa_konveksi', 'jasa_potong', 'jasa_printing', 'jasa_jahit', 'jasa_labsas',
                'biaya_kain', 'biaya_sbs', 'biaya_pitta',
                'biaya_kancing', 'biaya_kancing_kait', 'biaya_karet', 'biaya_plastik', 'biaya_string',
                'biaya_bordir', 'biaya_servis', 'biaya_finishing', 'biaya_pengiriman'
            ];
            let total = 0;
            fields.forEach(f => {
                let val = $(card).find(`[data-field="${f}"]`).val();
                if (f !== 'kebutuhan_kain') {
                    total += getCleanNumber(val);
                }
            });
            // tambah extras
            $(card).find('.extra-nominal').each(function() {
                total += getCleanNumber($(this).val());
            });
            const qty = parseInt($(card).find('.item-qty').val()) || 1;
            $(card).find('.hpp-per-pcs').text('Rp ' + total.toLocaleString('id-ID'));
            $(card).find('.hpp-subtotal').text('Rp ' + (total * qty).toLocaleString('id-ID'));
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
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1">SKU Induk</label>
                    <input type="text" name="items[${idx}][sku_induk]" class="form-control form-control-sm item-sku-induk" placeholder="Induk">
                </div>
                <div class="col-md-2 col-6">
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
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-semibold mb-1">Tukang Jahit</label>
                    <select name="items[${idx}][tailor]" class="form-select form-select-sm">
                        ${tailorOpts()}
                    </select>
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

            {{-- ── Collapsible HPP Details Tabbed Panel ── --}}
            <div class="collapse mt-3 border rounded-3 p-3 bg-light" id="hppCollapse-${idx}">
                
                {{-- Tabs Nav --}}
                <ul class="nav nav-pills nav-fill mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link nav-link-sm py-1 fw-bold small active" data-bs-toggle="pill" data-bs-target="#jasaTab-${idx}" type="button" role="tab"><i class="fas fa-cut me-1 text-primary"></i> 1. Biaya Jasa</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link nav-link-sm py-1 fw-bold small" data-bs-toggle="pill" data-bs-target="#bahanTab-${idx}" type="button" role="tab"><i class="fas fa-scroll me-1 text-success"></i> 2. Bahan &amp; Aksesoris</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link nav-link-sm py-1 fw-bold small" data-bs-toggle="pill" data-bs-target="#finishingTab-${idx}" type="button" role="tab"><i class="fas fa-gift me-1 text-danger"></i> 3. Finishing &amp; Lainnya</button>
                    </li>
                </ul>

                {{-- Tab Content --}}
                <div class="tab-content bg-white p-3 rounded-3 border border-light-subtle shadow-sm">
                    
                    {{-- Tab 1: Jasa --}}
                    <div class="tab-pane fade show active" id="jasaTab-${idx}" role="tabpanel">
                        <div class="row row-cols-2 row-cols-md-5 g-2">
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Jasa Konveksi</label>
                                <input type="text" name="items[${idx}][jasa_konveksi]" data-field="jasa_konveksi" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Jasa Potong</label>
                                <input type="text" name="items[${idx}][jasa_potong]" data-field="jasa_potong" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Jasa Printing</label>
                                <input type="text" name="items[${idx}][jasa_printing]" data-field="jasa_printing" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Jasa Jahit</label>
                                <input type="text" name="items[${idx}][jasa_jahit]" data-field="jasa_jahit" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Jasa Labsas</label>
                                <input type="text" name="items[${idx}][jasa_labsas]" data-field="jasa_labsas" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Bahan --}}
                    <div class="tab-pane fade" id="bahanTab-${idx}" role="tabpanel">
                        <div class="row row-cols-2 row-cols-md-5 g-2">
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Kebutuhan Kain (m)</label>
                                <input type="number" name="items[${idx}][kebutuhan_kain]" data-field="kebutuhan_kain" class="form-control form-control-sm text-end" step="0.01" min="0" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Biaya Kain</label>
                                <input type="text" name="items[${idx}][biaya_kain]" data-field="biaya_kain" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">SBS (Resleting)</label>
                                <input type="text" name="items[${idx}][biaya_sbs]" data-field="biaya_sbs" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Pitta</label>
                                <input type="text" name="items[${idx}][biaya_pitta]" data-field="biaya_pitta" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Kancing</label>
                                <input type="text" name="items[${idx}][biaya_kancing]" data-field="biaya_kancing" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Kancing Kait</label>
                                <input type="text" name="items[${idx}][biaya_kancing_kait]" data-field="biaya_kancing_kait" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Karet</label>
                                <input type="text" name="items[${idx}][biaya_karet]" data-field="biaya_karet" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Plastik</label>
                                <input type="text" name="items[${idx}][biaya_plastik]" data-field="biaya_plastik" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">String / Tali</label>
                                <input type="text" name="items[${idx}][biaya_string]" data-field="biaya_string" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: Finishing & Tambahan --}}
                    <div class="tab-pane fade" id="finishingTab-${idx}" role="tabpanel">
                        <div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Bordir / Logo</label>
                                <input type="text" name="items[${idx}][biaya_bordir]" data-field="biaya_bordir" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Servis</label>
                                <input type="text" name="items[${idx}][biaya_servis]" data-field="biaya_servis" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Finishing M</label>
                                <input type="text" name="items[${idx}][biaya_finishing]" data-field="biaya_finishing" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                            <div class="col">
                                <label class="form-label mb-0 small text-muted" style="font-size:10px;">Biaya Pengiriman</label>
                                <input type="text" name="items[${idx}][biaya_pengiriman]" data-field="biaya_pengiriman" class="form-control form-control-sm cost-field rupiah-mask text-end" value="0">
                            </div>
                        </div>

                        {{-- Dinamis Extras --}}
                        <div class="extras-container pt-3 border-top">
                            <p class="small fw-bold text-secondary mb-2" style="font-size:11px;">
                                <i class="fas fa-plus-circle text-primary me-1"></i>Biaya / Jasa Tambahan Dinamis
                            </p>
                            <div class="extras-list mb-2"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-add-extra py-1" style="font-size:11px;">
                                <i class="fas fa-plus me-1"></i> Tambah Biaya Lain
                            </button>
                        </div>
                    </div>

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
            const eIdx = $(card).find('.extra-row').length;
            const html = `
    <div class="row g-1 mb-1 extra-row align-items-center">
        <div class="col">
            <input type="text" name="items[${idx}][extras][${eIdx}][keterangan]"
                class="form-control form-control-sm" placeholder="Keterangan biaya tambahan...">
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
            $(card).find('.extras-list').append(html);
        }

        // ——— jQuery Ready ———
        $(document).ready(function() {

            // First row
            addRow();

            // Tambah item baru
            $('#btnAddRow').on('click', function() {
                addRow();
            });

            // Hapus item
            $(document).on('click', '.btn-remove-row', function() {
                if ($('.item-card').length <= 1) {
                    alert('Minimal 1 item harus ada.');
                    return;
                }
                $(this).closest('.item-card').remove();
                renumberItems();
            });

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

                            // AUTO-POPULATE: Ambil rincian biaya dari riwayat input SPK terakhir untuk produk ini
                            if (p.latest_costs) {
                                const fields = [
                                    'jasa_konveksi', 'jasa_potong', 'jasa_printing',
                                    'jasa_jahit', 'jasa_labsas',
                                    'biaya_kain', 'biaya_sbs', 'biaya_pitta',
                                    'biaya_kancing', 'biaya_kancing_kait',
                                    'biaya_karet', 'biaya_plastik', 'biaya_string',
                                    'biaya_bordir', 'biaya_servis', 'biaya_finishing',
                                    'biaya_pengiriman'
                                ];
                                fields.forEach(f => {
                                    let val = p.latest_costs[f] || 0;
                                    let $inp = $card.find(`[data-field="${f}"]`);
                                    if (f === 'kebutuhan_kain') {
                                        $inp.val(val);
                                    } else {
                                        $inp.val(formatRupiah(val));
                                    }
                                });
                            } else {
                                // Reset all HPP fields to 0 if no history
                                $card.find('.cost-field').val('0');
                                $card.find('[data-field="kebutuhan_kain"]').val('0');

                                // Default biaya kain dari Master Product catalog cost_price jika ada
                                if (p.cost_price) {
                                    $card.find('[data-field="biaya_kain"]').val(
                                        formatRupiah(parseFloat(p.cost_price) || 0));
                                }
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
