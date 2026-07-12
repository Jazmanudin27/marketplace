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

        // ——— Hitung HPP per baris ———
        function recalcHpp(card) {
            let total = 0;
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

            {{-- ── Collapsible HPP Details Dynamic Panel ── --}}
            <div class="collapse mt-3 border rounded-3 p-3 bg-light" id="hppCollapse-${idx}">
                <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="small fw-bold text-secondary" style="font-size:11px;">
                            <i class="fas fa-boxes text-primary me-1"></i>Komponen Biaya Produksi (BOM &amp; Jasa)
                        </span>
                        <button type="button" class="btn btn-outline-primary btn-sm btn-add-extra py-0 px-2 fw-semibold" style="font-size:11px;">
                            <i class="fas fa-plus me-1"></i> Tambah Baris Biaya
                        </button>
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
                                        const name = (labor.service_name || '').toLowerCase();
                                        const cost = parseFloat(labor.default_cost || 0) / batchQty;

                                        if (name.includes('jahit')) {
                                            $card.find('[data-field="jasa_jahit"]').val(formatRupiah(cost));
                                        } else if (name.includes('potong')) {
                                            $card.find('[data-field="jasa_potong"]').val(formatRupiah(cost));
                                        } else if (name.includes('print') || name.includes('sablon')) {
                                            $card.find('[data-field="jasa_printing"]').val(formatRupiah(cost));
                                        } else if (name.includes('label') || name.includes('labsas')) {
                                            $card.find('[data-field="jasa_labsas"]').val(formatRupiah(cost));
                                        } else if (name.includes('konveksi')) {
                                            $card.find('[data-field="jasa_konveksi"]').val(formatRupiah(cost));
                                        } else {
                                            // Add as dynamic extra
                                            addExtraRowWithValues($card, labor.service_name, cost);
                                        }
                                    });
                                }

                                // 2. Map Materials (Bahan)
                                if (recipe.items && recipe.items.length > 0) {
                                    recipe.items.forEach(rItem => {
                                        const invItem = rItem.inventory_item;
                                        if (invItem) {
                                            const name = (invItem.name || '').toLowerCase();
                                            const itemQty = parseFloat(rItem.quantity || 0) / batchQty;
                                            const itemCost = itemQty * parseFloat(invItem.cost_price || 0);

                                            if (name.includes('kain')) {
                                                $card.find('[data-field="kebutuhan_kain"]').val(itemQty.toFixed(2));
                                                $card.find('[data-field="biaya_kain"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('resleting') || name.includes('sbs')) {
                                                $card.find('[data-field="biaya_sbs"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('pita') || name.includes('pitta')) {
                                                $card.find('[data-field="biaya_pitta"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('kancing kait')) {
                                                $card.find('[data-field="biaya_kancing_kait"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('kancing')) {
                                                $card.find('[data-field="biaya_kancing"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('karet')) {
                                                $card.find('[data-field="biaya_karet"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('plastik')) {
                                                $card.find('[data-field="biaya_plastik"]').val(formatRupiah(itemCost));
                                            } else if (name.includes('tali') || name.includes('string')) {
                                                $card.find('[data-field="biaya_string"]').val(formatRupiah(itemCost));
                                            } else {
                                                // Add as dynamic extra
                                                addExtraRowWithValues($card, 'Bahan: ' + invItem.name, itemCost);
                                            }
                                        }
                                    });
                                }
                            } else if (p.latest_costs) {
                                // AUTO-POPULATE: Ambil rincian biaya dari riwayat input SPK terakhir untuk produk ini
                                const fields = [
                                    'jasa_konveksi', 'jasa_potong', 'jasa_printing',
                                    'jasa_jahit', 'jasa_labsas',
                                    'kebutuhan_kain', 'biaya_kain', 'biaya_sbs', 'biaya_pitta',
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
