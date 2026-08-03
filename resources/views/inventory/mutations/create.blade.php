@extends('layouts.app')
@section('title', 'Form Input Mutasi Gudang Jadi')
@section('page-title', 'Form Input Mutasi Gudang Jadi')

@section('content')
<div class="container-fluid px-0">

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fas fa-boxes text-primary me-2"></i> Input Mutasi Barang Gudang Jadi
                        </h5>
                        <small class="text-muted">Catat barang masuk (Inbound) atau barang keluar (Outbound) di Gudang Jadi.</small>
                    </div>
                    <a href="{{ route('inventory.mutations.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat
                    </a>
                </div>

                <form action="{{ route('inventory.mutations.store') }}" method="POST" id="formMutation">
                    @csrf

                    <div class="card-body p-4">

                        {{-- Alert Error Validation --}}
                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3 mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- 1. Pilihan Jenis Mutasi (Masuk vs Keluar) --}}
                        <div class="mb-4 p-3 rounded-3 border bg-light">
                            <label class="form-label fw-bold text-dark d-block mb-2">
                                1. Pilih Jenis Mutasi Barang <span class="text-danger">*</span>
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="type" id="typeIn" value="in" {{ old('type', $selectedType) === 'in' ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-success w-100 py-3 text-start rounded-3 d-flex align-items-center gap-3 shadow-sm" for="typeIn">
                                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width: 44px; height: 44px;">
                                            <i class="fas fa-arrow-down fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-6 mb-0 text-success">🟢 BARANG MASUK (INBOUND)</div>
                                            <small class="text-muted opacity-75">Hasil Produksi, Pembelian/Restock, Retur Pelanggan, Hadiah/Bonus</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="type" id="typeOut" value="out" {{ old('type', $selectedType) === 'out' ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-danger w-100 py-3 text-start rounded-3 d-flex align-items-center gap-3 shadow-sm" for="typeOut">
                                        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center text-danger flex-shrink-0" style="width: 44px; height: 44px;">
                                            <i class="fas fa-arrow-up fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-6 mb-0 text-danger">🔴 BARANG KELUAR (OUTBOUND)</div>
                                            <small class="text-muted opacity-75">Barang Rusak/Cacat, Display/Sampel, Promo, Pemakaian Internal, Hilang</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Informasi Tanggal & Kategori Alasan --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="mutationDate" class="form-label form-label-sm fw-semibold text-dark">
                                    Tanggal Mutasi
                                </label>
                                <input type="date" name="date" id="mutationDate" class="form-control form-control-sm" value="{{ old('date', date('Y-m-d')) }}">
                                <small class="text-muted" style="font-size: 0.72rem;">Kosongkan untuk menggunakan tanggal & waktu saat ini.</small>
                            </div>
                            <div class="col-md-8">
                                <label for="categoryReason" class="form-label form-label-sm fw-semibold text-dark">
                                    Kategori / Alasan Mutasi <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm" id="selectReasonPreset">
                                        <option value="">-- Pilih Kategori Preset --</option>
                                    </select>
                                    <input type="text" name="category_reason" id="categoryReason" class="form-control form-control-sm w-50" placeholder="Atau ketikkan alasan mutasi..." value="{{ old('category_reason') }}" required>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Contoh: Hasil Produksi SPK #102, Display Pameran, Barang Cacat Jahitan, Dll.</small>
                            </div>
                        </div>

                        {{-- 3. Daftar Produk yang Dimutasi --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-dark mb-0">
                                    3. Daftar Barang Gudang Jadi yang Dimutasi <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-3" id="btnAddRow">
                                    <i class="fas fa-plus me-1"></i> Tambah Item Baris
                                </button>
                            </div>

                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-bordered align-middle mb-0" id="tableItems">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="45%">PILIH MASTER PRODUK</th>
                                            <th width="20%" class="text-center">STOK SEKARANG</th>
                                            <th width="20%" class="text-center">QTY MUTASI</th>
                                            <th width="15%" class="text-center">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mutationRows">
                                        {{-- Rows populated via JavaScript --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 4. Catatan Keterangan Tambahan --}}
                        <div class="mb-4">
                            <label for="notes" class="form-label form-label-sm fw-semibold text-dark">
                                Catatan / Keterangan Tambahan (Opsional)
                            </label>
                            <textarea name="notes" id="notes" rows="2" class="form-control form-control-sm" placeholder="Tuliskan catatan detail jika diperlukan...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="alert alert-info border-info bg-info bg-opacity-10 py-2.5 px-3 rounded-3 d-flex align-items-center gap-2">
                            <i class="fas fa-info-circle text-info fs-5"></i>
                            <div class="small text-dark">
                                <strong>Sinkronisasi Otomatis:</strong> Setelah disimpan, stok produk master akan langsung diperbarui dan disinkronkan ke seluruh toko marketplace yang terhubung (Shopee, TikTok, Lazada, Tokopedia).
                            </div>
                        </div>

                    </div>

                    <div class="card-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center border-top">
                        <a href="{{ route('inventory.mutations.index') }}" class="btn btn-secondary btn-sm px-4 rounded-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold" id="btnSubmit">
                            <i class="fas fa-save me-1"></i> Simpan Mutasi Gudang
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>

</div>

{{-- Data untuk JavaScript --}}
<script>
    const SEARCH_URL    = "{{ route('inventory.mutations.search-products') }}";
    const PRE_SELECTED  = @json($preSelectedProduct);
    const selectedType  = @json($selectedType);

    const reasonsIn = [
        "Hasil Produksi Selesai",
        "Pembelian / Restock Supplier",
        "Retur Pelanggan (Layak Jual)",
        "Bonus / Hadiah Supplier",
        "Penyesuaian Stok Fisik Gudang",
        "Lainnya"
    ];

    const reasonsOut = [
        "Barang Rusak / Cacat Jahitan",
        "Display / Sampel Pameran",
        "Hadiah / Bonus Promosi",
        "Pemakaian Internal Kantor",
        "Barang Hilang / Selisih Gudang",
        "Lainnya"
    ];

    /* =====================================================
       AUTOCOMPLETE HELPER
       Membuat 1 baris item dengan input autocomplete produk
       ===================================================== */
    function createProductAutocomplete(rowIndex, preProduct = null) {
        const wrapper = document.createElement('div');
        wrapper.className = 'autocomplete-wrapper position-relative';

        // Input teks yang user ketik
        const input = document.createElement('input');
        input.type        = 'text';
        input.className   = 'form-control form-control-sm';
        input.placeholder = 'Ketik nama / SKU produk...';
        input.autocomplete = 'off';
        input.required    = true;

        // Hidden input yang menyimpan product_id sesungguhnya untuk POST
        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = `items[${rowIndex}][product_id]`;
        hidden.required = true;

        // Dropdown list
        const dropdown = document.createElement('ul');
        dropdown.className = 'autocomplete-dropdown list-unstyled mb-0 shadow-sm border rounded-2 bg-white position-absolute w-100';
        dropdown.style.cssText = 'top:100%;left:0;z-index:1055;max-height:220px;overflow-y:auto;display:none;';

        wrapper.appendChild(input);
        wrapper.appendChild(hidden);
        wrapper.appendChild(dropdown);

        let debounceTimer = null;
        let lastKeyword   = '';

        function renderDropdown(items) {
            dropdown.innerHTML = '';
            if (!items.length) {
                dropdown.innerHTML = '<li class="px-3 py-2 text-muted small">Produk tidak ditemukan</li>';
                dropdown.style.display = 'block';
                return;
            }
            items.forEach(p => {
                const li = document.createElement('li');
                li.className = 'autocomplete-item px-3 py-2 small d-flex justify-content-between align-items-center';
                li.style.cursor = 'pointer';
                li.innerHTML = `
                    <span>
                        <span class="text-muted font-monospace me-1">[${escHtml(p.sku)}]</span>
                        <strong>${escHtml(p.name)}</strong>
                    </span>
                    <span class="badge bg-light text-dark border ms-2">Stok: ${p.stock}</span>`;
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault(); // Prevent blur sebelum click terekam
                    selectProduct(p);
                });
                dropdown.appendChild(li);
            });
            dropdown.style.display = 'block';
        }

        function selectProduct(p) {
            input.value   = `[${p.sku}] ${p.name}`;
            hidden.value  = p.id;
            dropdown.style.display = 'none';
            // Update badge stok
            const badge = wrapper.closest('tr')?.querySelector('.current-stock-badge');
            if (badge) badge.textContent = p.stock ?? 0;
        }

        function clearSelection() {
            hidden.value = '';
            const badge = wrapper.closest('tr')?.querySelector('.current-stock-badge');
            if (badge) badge.textContent = 0;
        }

        input.addEventListener('input', () => {
            const kw = input.value.trim();
            clearSelection();
            if (kw === lastKeyword) return;
            lastKeyword = kw;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (kw.length < 1) { dropdown.style.display = 'none'; return; }
                fetch(`${SEARCH_URL}?q=${encodeURIComponent(kw)}`)
                    .then(r => r.json())
                    .then(data => renderDropdown(data))
                    .catch(() => { dropdown.style.display = 'none'; });
            }, 280); // debounce 280ms
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 1 && !hidden.value) {
                input.dispatchEvent(new Event('input'));
            }
        });

        input.addEventListener('blur', () => {
            // Delay agar mousedown pada item dropdown sempat terekam
            setTimeout(() => { dropdown.style.display = 'none'; }, 200);
            // Jika tidak ada produk terpilih, kosongkan input agar tidak menipu
            if (!hidden.value) input.value = '';
        });

        // Jika ada pre-selected product (dari query string / old value)
        if (preProduct) {
            selectProduct(preProduct);
        }

        return { wrapper, input, hidden };
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str ?? ''));
        return d.innerHTML;
    }

    /* =====================================================
       INISIALISASI HALAMAN
       ===================================================== */
    document.addEventListener('DOMContentLoaded', function () {
        const typeIn               = document.getElementById('typeIn');
        const typeOut              = document.getElementById('typeOut');
        const selectReasonPreset   = document.getElementById('selectReasonPreset');
        const categoryReasonInput  = document.getElementById('categoryReason');
        const mutationRows         = document.getElementById('mutationRows');
        const btnAddRow            = document.getElementById('btnAddRow');

        // Set tipe sesuai state saat ini
        if (selectedType === 'out') typeOut.checked = true;
        else typeIn.checked = true;

        function updateReasonPresets() {
            const isOut   = typeOut.checked;
            const presets = isOut ? reasonsOut : reasonsIn;
            selectReasonPreset.innerHTML = '<option value="">-- Pilih Kategori Preset --</option>';
            presets.forEach(reason => {
                const opt = document.createElement('option');
                opt.value = reason;
                opt.textContent = reason;
                selectReasonPreset.appendChild(opt);
            });
        }

        typeIn.addEventListener('change', updateReasonPresets);
        typeOut.addEventListener('change', updateReasonPresets);

        selectReasonPreset.addEventListener('change', function () {
            if (this.value) categoryReasonInput.value = this.value;
        });

        updateReasonPresets();

        let rowIndex = 0;

        function addRow(preProduct = null) {
            rowIndex++;
            const tr = document.createElement('tr');
            tr.id = `row-${rowIndex}`;

            // Kolom Produk (autocomplete)
            const tdProduct = document.createElement('td');
            const { wrapper } = createProductAutocomplete(rowIndex, preProduct);
            tdProduct.appendChild(wrapper);

            // Kolom Stok
            const tdStock = document.createElement('td');
            tdStock.className = 'text-center align-middle';
            tdStock.innerHTML = `<span class="badge bg-light text-dark font-monospace border current-stock-badge px-2 py-1">0</span>`;

            // Kolom Qty
            const tdQty = document.createElement('td');
            tdQty.innerHTML = `<input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm text-center input-qty font-monospace" min="1" value="1" required>`;

            // Kolom Aksi
            const tdAction = document.createElement('td');
            tdAction.className = 'text-center align-middle';
            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = 'btn btn-outline-danger btn-xs btn-remove-row';
            btnRemove.title = 'Hapus Baris';
            btnRemove.innerHTML = '<i class="fas fa-trash"></i>';
            btnRemove.addEventListener('click', () => {
                if (mutationRows.children.length > 1) {
                    tr.remove();
                } else {
                    alert('Minimal 1 produk harus dipilih!');
                }
            });
            tdAction.appendChild(btnRemove);

            tr.appendChild(tdProduct);
            tr.appendChild(tdStock);
            tr.appendChild(tdQty);
            tr.appendChild(tdAction);
            mutationRows.appendChild(tr);
        }

        btnAddRow.addEventListener('click', () => addRow());

        // Row pertama — pre-select produk jika ada (dari query string)
        addRow(PRE_SELECTED);
    });
</script>

<style>
    .autocomplete-item:hover {
        background-color: #f0f4ff;
    }
    .autocomplete-dropdown::-webkit-scrollbar {
        width: 5px;
    }
    .autocomplete-dropdown::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }
</style>
@endsection
