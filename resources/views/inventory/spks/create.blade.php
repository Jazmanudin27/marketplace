@extends('layouts.app')
@section('title', 'Buat SPK Baru')
@section('page-title', 'Buat SPK Baru')

@section('content')
    <div class="container-fluid py-3">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                    <div
                        class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2"></i>Buat SPK Baru</h5>
                            <small class="text-white-50">Isi data produksi, rancangan pakaian, pembagian tugas penjahit, dan
                                visual desain.</small>
                        </div>
                        <a href="{{ route('spks.index') }}" class="btn btn-sm btn-light fw-semibold px-3">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>

                    <form action="{{ route('spks.store') }}" method="POST" enctype="multipart/form-data" class="m-0"
                        id="spkForm">
                        @csrf

                        <div class="card-body p-4">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0 small">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Informasi SPK Header --}}
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">No. Produksi</label>
                                    <input type="text" name="no_produksi" class="form-control form-control-sm"
                                        placeholder="Contoh: JN26148" value="{{ old('no_produksi') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">No. SPK</label>
                                    <input type="text" class="form-control form-control-sm bg-light" readonly
                                        value="[Otomatis Digenerate Sistem]">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Tanggal Order <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="tanggal" class="form-control form-control-sm" required
                                        value="{{ old('tanggal', date('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Tanggal Jatuh Tempo / Deadline <span
                                            class="text-danger">*</span></label>
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
                                        placeholder="Contoh: 0852-4828-5020" value="{{ old('no_hp_pemesan') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small">Instansi</label>
                                    <input type="text" name="instansi" class="form-control form-control-sm"
                                        placeholder="Contoh: Nusantara Seragam" value="{{ old('instansi') }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Atribut / Aksesoris / Catatan
                                        Tambahan</label>
                                    <textarea name="tambahan" class="form-control form-control-sm" rows="3"
                                        placeholder="Misal: Atribut & Aksesoris Tambahan, Bordir Logo: 46 Pcs, Kancing Emas...">{{ old('tambahan') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Upload Foto Desain / Pola Baju</label>
                                    <input type="file" name="image" class="form-control form-control-sm"
                                        accept="image/*">
                                    <small class="text-muted small mt-1 d-block">Mendukung format JPEG, PNG, JPG (maks
                                        4MB).</small>
                                </div>
                            </div>

                            {{-- Detail Produk --}}
                            <div class="border-top pt-4 mt-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-boxes text-primary me-2"></i>Detail Item Produk
                                    &amp; Tugas Penjahit</h6>
                                <div class="rounded border mb-3">
                                    <table class="table table-striped table-bordered align-middle mb-0" id="itemsTable">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase text-muted">
                                                <th class="col-4">Nama Produk / Pilih Katalog</th>
                                                <th class="col-2">SKU Induk</th>
                                                <th class="col-2">SKU Varian</th>
                                                <th class="col-1">Ukuran</th>
                                                <th class="col-1">Qty</th>
                                                <th class="col-1">Tukang Jahit</th>
                                                <th class="col-1 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- Rows added dynamically by jQuery --}}
                                        </tbody>
                                    </table>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold py-2 mb-3"
                                    id="btnAddRow">
                                    <i class="fas fa-plus-circle me-1"></i> Tambah Item Baru
                                </button>
                            </div>
                        </div>

                        <div class="card-footer bg-light py-3 px-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary px-3">Batal</a>
                            <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">Simpan SPK</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- JSON catalog data to help jQuery auto-fill product details --}}
    <script>
        const catalogProducts = @json($products);
        const tailorsList = @json($tailors);
    </script>

    {{-- jQuery Scripts --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let rowIndex = 0;

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function generateTailorOptions() {
            let options = '<option value="">-- Pilih Penjahit --</option>';
            tailorsList.forEach(function(e) {
                const name = escapeHtml(e.name);
                options += '<option value="' + name + '">' + name + '</option>';
            });
            return options;
        }

        function addRow() {
            const tailorOpts = generateTailorOptions();
            const rowHtml = `
            <tr id="row-${rowIndex}">
                <td>
                    <div class="position-relative">
                        <input type="text" name="items[${rowIndex}][name]" class="form-control form-control-sm item-name" required placeholder="Ketik nama / SKU produk..." autocomplete="off">
                        <div class="suggestions-box position-absolute bg-white border rounded shadow-sm w-100 d-none" style="z-index: 1050; max-height: 180px; overflow-y: auto;"></div>
                    </div>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][sku_induk]" class="form-control form-control-sm item-sku-induk" placeholder="Contoh: LPJ">
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][sku]" class="form-control form-control-sm item-sku" placeholder="Contoh: LPJ-M">
                </td>
                <td>
                    <select name="items[${rowIndex}][size]" class="form-select form-select-sm item-size">
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L" selected>L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                        <option value="3XL">3XL</option>
                        <option value="All Size">All Size</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][qty]" class="form-control form-control-sm text-center" required min="1" value="1">
                </td>
                <td>
                    <select name="items[${rowIndex}][tailor]" class="form-select form-select-sm">
                        ${tailorOpts}
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;
            $('#itemsTable tbody').append(rowHtml);
            rowIndex++;
        }

        $(document).ready(function() {
            // Add first row on load
            addRow();

            // Add row on button click
            $('#btnAddRow').on('click', function() {
                addRow();
            });

            // Remove row on click
            $(document).on('click', '.btn-remove-row', function() {
                $(this).closest('tr').remove();
            });

            // Search product auto-suggest
            $(document).on('input focus', '.item-name', function() {
                const $input = $(this);
                const $row = $input.closest('tr');
                const $box = $row.find('.suggestions-box');
                const q = $input.val().trim().toLowerCase();

                $box.empty();

                if (q.length === 0) {
                    $box.addClass('d-none');
                    return;
                }

                // Filter catalog
                const matches = catalogProducts.filter(function(p) {
                    const name = (p.name || '').toLowerCase();
                    const sku = (p.sku || '').toLowerCase();
                    const skuInduk = (p.sku_induk || '').toLowerCase();
                    return name.indexOf(q) !== -1 || sku.indexOf(q) !== -1 || skuInduk.indexOf(
                        q) !== -1;
                }).slice(0, 10);

                if (matches.length === 0) {
                    $box.append(
                        '<div class="p-2 text-muted text-center italic small">Produk tidak ditemukan</div>'
                    );
                } else {
                    matches.forEach(function(p) {
                        const name = escapeHtml(p.name);
                        const sku = escapeHtml(p.sku || 'N/A');
                        const skuInduk = escapeHtml(p.sku_induk || 'N/A');

                        const $item = $(
                            '<div class="p-2 border-bottom suggestion-item" style="cursor: pointer;">' +
                            '<div class="fw-bold text-dark small">' + name + '</div>' +
                            '<div class="text-muted small" style="font-size: 10px;">SKU: ' +
                            sku + ' | Induk: ' + skuInduk + '</div>' +
                            '</div>');

                        $item.on('click', function() {
                            $input.val(p.name);
                            $row.find('.item-sku').val(p.sku || '');
                            $row.find('.item-sku-induk').val(p.sku_induk || '');

                            if (p.ukuran) {
                                const $sizeSelect = $row.find('.item-size');
                                const targetSize = p.ukuran.toUpperCase();
                                let sizeExists = false;

                                $sizeSelect.find('option').each(function() {
                                    if ($(this).val().toUpperCase() ===
                                        targetSize) {
                                        $sizeSelect.val($(this).val());
                                        sizeExists = true;
                                        return false;
                                    }
                                });

                                if (!sizeExists) {
                                    $sizeSelect.append('<option value="' + p.ukuran + '">' +
                                        p.ukuran + '</option>');
                                    $sizeSelect.val(p.ukuran);
                                }
                            }

                            $box.addClass('d-none');
                        });

                        $box.append($item);
                    });
                }

                $box.removeClass('d-none');
            });

            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).hasClass('item-name')) {
                    $('.suggestions-box').addClass('d-none');
                }
            });

            // Highlight suggestions on hover
            $(document).on('mouseenter', '.suggestion-item', function() {
                $(this).addClass('bg-light');
            }).on('mouseleave', '.suggestion-item', function() {
                $(this).removeClass('bg-light');
            });
        });
    </script>
@endsection
