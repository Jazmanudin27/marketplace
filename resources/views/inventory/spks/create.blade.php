@extends('layouts.app')
@section('title', 'Buat SPK Baru')
@section('page-title', 'Buat SPK Baru')

@section('content')
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
            <div>
                <h5 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2"></i>Buat SPK Baru</h5>
                <small class="text-white-50">Isi data produksi, rancangan, dan rincian biaya produksi per item.</small>
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
                        <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                {{-- ── Header SPK ─────────────────────────────────── --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold small">No. Produksi</label>
                        <input type="text" name="no_produksi" class="form-control form-control-sm"
                            placeholder="Contoh: JN26148" value="{{ old('no_produksi') }}">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold small">No. SPK</label>
                        <input type="text" class="form-control form-control-sm bg-light" readonly
                            value="[Otomatis Digenerate Sistem]">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold small">Tanggal Order <span class="text-danger">*</span></label>
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
                        <small class="text-muted d-block mt-1">JPEG/PNG/JPG, maks 4MB</small>
                    </div>
                </div>

                {{-- ── Detail Item Produk ─────────────────────────── --}}
                <div class="border-top pt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">
                            <i class="fas fa-boxes text-primary me-2"></i>Detail Item Produk &amp; Rincian Biaya HPP
                        </h6>
                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3" id="btnAddRow">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Item
                        </button>
                    </div>

                    <div id="itemsContainer">
                        {{-- Rows injected by jQuery --}}
                    </div>
                </div>

            </div>
            <div class="card-footer bg-light py-3 px-4 d-flex justify-content-end gap-2">
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
    const tailorsList     = @json($tailors);
</script>

<script>
let rowIndex = 0;

function escapeHtml(t) {
    if (!t) return '';
    return String(t)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function tailorOpts() {
    let o = '<option value="">— Pilih Penjahit —</option>';
    tailorsList.forEach(e => { o += `<option value="${escapeHtml(e.name)}">${escapeHtml(e.name)}</option>`; });
    return o;
}

// ——— Hitung HPP per baris ———
function recalcHpp(card) {
    const fields = [
        'jasa_konveksi','jasa_potong','jasa_printing','jasa_jahit','jasa_labsas',
        'biaya_kain','biaya_sbs','biaya_pitta',
        'biaya_kancing','biaya_kancing_kait','biaya_karet','biaya_plastik','biaya_string',
        'biaya_bordir','biaya_servis','biaya_finishing','biaya_pengiriman'
    ];
    let total = 0;
    fields.forEach(f => { total += parseFloat($(card).find(`[data-field="${f}"]`).val()) || 0; });
    // tambah extras
    $(card).find('.extra-nominal').each(function() { total += parseFloat($(this).val()) || 0; });
    const qty = parseInt($(card).find('.item-qty').val()) || 1;
    $(card).find('.hpp-per-pcs').text('Rp ' + total.toLocaleString('id-ID'));
    $(card).find('.hpp-subtotal').text('Rp ' + (total * qty).toLocaleString('id-ID'));
}

// ——— Buat Card Item Baru ———
function addRow() {
    const idx = rowIndex++;
    const html = `
    <div class="card border mb-3 item-card" id="item-card-${idx}">
        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold small text-primary">Item #<span class="item-num">${idx+1}</span></span>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row py-0 px-2">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <div class="card-body p-3">

            {{-- ── Identitas ── --}}
            <div class="row g-2 mb-2">
                <div class="col-md-4 position-relative">
                    <label class="form-label small fw-semibold mb-1">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="items[${idx}][name]" class="form-control form-control-sm item-name" required
                        placeholder="Cari nama / SKU produk..." autocomplete="off">
                    <div class="suggestions-box position-absolute bg-white border rounded shadow-sm w-100 d-none"
                        style="z-index:1050;max-height:180px;overflow-y:auto;top:100%;left:0;"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">SKU Induk</label>
                    <input type="text" name="items[${idx}][sku_induk]" class="form-control form-control-sm item-sku-induk" placeholder="Induk">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">SKU Varian</label>
                    <input type="text" name="items[${idx}][sku]" class="form-control form-control-sm item-sku" placeholder="Varian">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Ukuran</label>
                    <select name="items[${idx}][size]" class="form-select form-select-sm item-size">
                        <option value="S">S</option><option value="M">M</option>
                        <option value="L" selected>L</option><option value="XL">XL</option>
                        <option value="XXL">XXL</option><option value="3XL">3XL</option>
                        <option value="All Size">All Size</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">Qty <span class="text-danger">*</span></label>
                    <input type="number" name="items[${idx}][qty]" class="form-control form-control-sm item-qty text-center" required min="1" value="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Alur Kerja</label>
                    <select name="items[${idx}][alur_proses]" class="form-select form-select-sm">
                        <option value="Langsung Jahit">Langsung Jahit</option>
                        <option value="Printing -> Jahit">Printing → Jahit</option>
                        <option value="Sablon/Bordir -> Jahit">Sablon/Bordir → Jahit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tukang Jahit</label>
                    <select name="items[${idx}][tailor]" class="form-select form-select-sm">
                        ${tailorOpts()}
                    </select>
                </div>
            </div>

            {{-- ── Rincian Biaya ── --}}
            <div class="border rounded p-2 bg-light mt-2">
                <p class="small fw-bold text-secondary mb-2 text-uppercase" style="font-size:10px; letter-spacing:.5px;">
                    <i class="fas fa-calculator me-1"></i>Rincian Biaya HPP / Pcs
                </p>

                {{-- Biaya Jasa --}}
                <p class="small fw-semibold text-primary mb-1">Biaya Jasa</p>
                <div class="row g-2 mb-2">
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Jasa Konveksi</label>
                        <input type="number" name="items[${idx}][jasa_konveksi]" data-field="jasa_konveksi" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Jasa Potong</label>
                        <input type="number" name="items[${idx}][jasa_potong]" data-field="jasa_potong" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Jasa Printing</label>
                        <input type="number" name="items[${idx}][jasa_printing]" data-field="jasa_printing" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Jasa Jahit</label>
                        <input type="number" name="items[${idx}][jasa_jahit]" data-field="jasa_jahit" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Jasa Labsas</label>
                        <input type="number" name="items[${idx}][jasa_labsas]" data-field="jasa_labsas" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                </div>

                {{-- Biaya Bahan --}}
                <p class="small fw-semibold text-success mb-1">Biaya Bahan</p>
                <div class="row g-2 mb-2">
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Kebutuhan Kain (m)</label>
                        <input type="number" name="items[${idx}][kebutuhan_kain]" data-field="kebutuhan_kain" class="form-control form-control-sm text-end" step="0.01" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Biaya Kain</label>
                        <input type="number" name="items[${idx}][biaya_kain]" data-field="biaya_kain" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">SBS (Resleting)</label>
                        <input type="number" name="items[${idx}][biaya_sbs]" data-field="biaya_sbs" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Pitta</label>
                        <input type="number" name="items[${idx}][biaya_pitta]" data-field="biaya_pitta" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                </div>

                {{-- Komponen Kecil --}}
                <p class="small fw-semibold text-warning mb-1">Komponen Kecil</p>
                <div class="row g-2 mb-2">
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Kancing</label>
                        <input type="number" name="items[${idx}][biaya_kancing]" data-field="biaya_kancing" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Kancing Kait</label>
                        <input type="number" name="items[${idx}][biaya_kancing_kait]" data-field="biaya_kancing_kait" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Karet</label>
                        <input type="number" name="items[${idx}][biaya_karet]" data-field="biaya_karet" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Plastik</label>
                        <input type="number" name="items[${idx}][biaya_plastik]" data-field="biaya_plastik" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">String/Tali</label>
                        <input type="number" name="items[${idx}][biaya_string]" data-field="biaya_string" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                </div>

                {{-- Finishing --}}
                <p class="small fw-semibold text-danger mb-1">Finishing</p>
                <div class="row g-2 mb-2">
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Bordir/Logo</label>
                        <input type="number" name="items[${idx}][biaya_bordir]" data-field="biaya_bordir" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Servis</label>
                        <input type="number" name="items[${idx}][biaya_servis]" data-field="biaya_servis" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Finishing</label>
                        <input type="number" name="items[${idx}][biaya_finishing]" data-field="biaya_finishing" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                    <div class="col"><label class="form-label mb-0" style="font-size:10px;">Biaya Pengiriman</label>
                        <input type="number" name="items[${idx}][biaya_pengiriman]" data-field="biaya_pengiriman" class="form-control form-control-sm cost-field text-end" min="0" value="0"></div>
                </div>

                {{-- Biaya Tambahan Dinamis --}}
                <div class="extras-container mt-1">
                    <p class="small fw-semibold text-secondary mb-1">Biaya Tambahan Lainnya</p>
                    <div class="extras-list"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 btn-add-extra" style="font-size:11px;">
                        <i class="fas fa-plus me-1"></i> Tambah Biaya Lain
                    </button>
                </div>

                {{-- Total HPP --}}
                <div class="d-flex justify-content-end align-items-center mt-3 gap-4">
                    <div class="text-end">
                        <div class="text-muted" style="font-size:11px;">HPP / Pcs</div>
                        <div class="fw-bold text-success fs-6 hpp-per-pcs">Rp 0</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size:11px;">Subtotal (× Qty)</div>
                        <div class="fw-bold text-primary fs-6 hpp-subtotal">Rp 0</div>
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
    $('.item-card').each(function(i) { $(this).find('.item-num').text(i + 1); });
}

// ——— Tambah baris extra ke card ———
function addExtraRow(card) {
    const idx = $(card).attr('id').replace('item-card-', '');
    const eIdx = $(card).find('.extra-row').length;
    const html = `
    <div class="row g-1 mb-1 extra-row align-items-center">
        <div class="col">
            <input type="text" name="items[${idx}][extras][${eIdx}][keterangan]"
                class="form-control form-control-sm" placeholder="Nama biaya tambahan...">
        </div>
        <div class="col-auto" style="width:130px;">
            <input type="number" name="items[${idx}][extras][${eIdx}][nominal]"
                class="form-control form-control-sm extra-nominal text-end" min="0" value="0" placeholder="0">
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
$(document).ready(function () {

    // First row
    addRow();

    // Tambah item baru
    $('#btnAddRow').on('click', function () { addRow(); });

    // Hapus item
    $(document).on('click', '.btn-remove-row', function () {
        if ($('.item-card').length <= 1) { alert('Minimal 1 item harus ada.'); return; }
        $(this).closest('.item-card').remove();
        renumberItems();
    });

    // Tambah extra
    $(document).on('click', '.btn-add-extra', function () {
        addExtraRow($(this).closest('.item-card'));
    });

    // Hapus extra
    $(document).on('click', '.btn-remove-extra', function () {
        const card = $(this).closest('.item-card');
        $(this).closest('.extra-row').remove();
        recalcHpp(card);
    });

    // Hitung HPP saat biaya berubah
    $(document).on('input change', '.cost-field, .extra-nominal, .item-qty', function () {
        recalcHpp($(this).closest('.item-card'));
    });

    // ——— Autocomplete nama produk ———
    $(document).on('input focus', '.item-name', function () {
        const $input = $(this);
        const $card  = $input.closest('.item-card');
        const $box   = $card.find('.suggestions-box');
        const q      = $input.val().trim().toLowerCase();

        $box.empty();
        if (q.length === 0) { $box.addClass('d-none'); return; }

        const matches = catalogProducts.filter(p =>
            (p.name||'').toLowerCase().includes(q) ||
            (p.sku||'').toLowerCase().includes(q) ||
            (p.sku_induk||'').toLowerCase().includes(q)
        ).slice(0, 10);

        if (!matches.length) {
            $box.append('<div class="p-2 text-muted text-center small">Produk tidak ditemukan</div>');
        } else {
            matches.forEach(p => {
                const $item = $('<div class="p-2 border-bottom suggestion-item" style="cursor:pointer;">' +
                    `<div class="fw-bold small">${escapeHtml(p.name)}</div>` +
                    `<div class="text-muted" style="font-size:10px;">SKU: ${escapeHtml(p.sku||'—')} | Induk: ${escapeHtml(p.sku_induk||'—')}</div>` +
                    '</div>');
                $item.on('click', function () {
                    $input.val(p.name);
                    $card.find('.item-sku').val(p.sku || '');
                    $card.find('.item-sku-induk').val(p.sku_induk || '');
                    // Isi biaya kain dari cost_price
                    if (p.cost_price) {
                        $card.find('[data-field="biaya_kain"]').val(parseFloat(p.cost_price) || 0);
                    }
                    // Set ukuran
                    if (p.ukuran) {
                        const $sel = $card.find('.item-size');
                        const t    = p.ukuran.toUpperCase();
                        let found  = false;
                        $sel.find('option').each(function () {
                            if ($(this).val().toUpperCase() === t) { $sel.val($(this).val()); found = true; return false; }
                        });
                        if (!found) { $sel.append(`<option value="${p.ukuran}">${p.ukuran}</option>`); $sel.val(p.ukuran); }
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
    $(document).on('click', function (e) {
        if (!$(e.target).hasClass('item-name')) { $('.suggestions-box').addClass('d-none'); }
    });

    // Hover suggestions
    $(document).on('mouseenter', '.suggestion-item', function () { $(this).addClass('bg-light'); })
               .on('mouseleave', '.suggestion-item', function () { $(this).removeClass('bg-light'); });
});
</script>
@endsection
