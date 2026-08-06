@extends('layouts.app')
@section('title', 'Form Input Mutasi Gudang Jadi')
@section('page-title', 'Form Input Mutasi Gudang Jadi')

@section('content')
<div class="container-fluid px-0">

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            {{-- Main Form Card --}}
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
                <div class="card-header bg-white py-3.5 px-4 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2.5 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                            <i class="fas fa-boxes-stacked fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Input Mutasi Barang Gudang Jadi</h5>
                            <small class="text-secondary">Catat penyesuaian barang masuk (Inbound) atau barang keluar (Outbound) secara akurat.</small>
                        </div>
                    </div>
                    <a href="{{ route('inventory.mutations.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-3 fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat
                    </a>
                </div>

                <form action="{{ route('inventory.mutations.store') }}" method="POST" id="formMutation">
                    @csrf

                    <div class="card-body p-4">

                        {{-- Alert Error Validation --}}
                        @if ($errors->any())
                            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3">
                                <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Mohon perbaiki kesalahan berikut:</div>
                                <ul class="mb-0 ps-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- 1. Pilihan Jenis Mutasi (Masuk vs Keluar) --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark d-block mb-2">
                                1. Pilih Jenis Mutasi Barang <span class="text-danger">*</span>
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="type" id="typeIn" value="in" {{ old('type', $selectedType) === 'in' ? 'checked' : '' }} autocomplete="off">
                                    <label class="type-card type-card-in w-100 d-flex align-items-center gap-3 shadow-xs" for="typeIn">
                                        <div class="type-icon rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; transition: all 0.2s;">
                                            <i class="fas fa-arrow-down fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="type-title fw-bold fs-6 mb-0 text-success">BARANG MASUK (INBOUND)</div>
                                            <small class="text-secondary opacity-90 d-block mt-0.5" style="font-size: 0.75rem;">Hasil Produksi, Restock Supplier, Retur Pelanggan, Hadiah</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="type" id="typeOut" value="out" {{ old('type', $selectedType) === 'out' ? 'checked' : '' }} autocomplete="off">
                                    <label class="type-card type-card-out w-100 d-flex align-items-center gap-3 shadow-xs" for="typeOut">
                                        <div class="type-icon rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; transition: all 0.2s;">
                                            <i class="fas fa-arrow-up fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="type-title fw-bold fs-6 mb-0 text-danger">BARANG KELUAR (OUTBOUND)</div>
                                            <small class="text-secondary opacity-90 d-block mt-0.5" style="font-size: 0.75rem;">Barang Rusak/Cacat, Display/Sampel, Promo, Selisih Stok</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Informasi Tanggal & Kategori Alasan --}}
                        <div class="card border border-light-subtle rounded-3 bg-light bg-opacity-50 p-3 mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="mutationDate" class="form-label form-label-sm fw-bold text-dark mb-1">
                                        <i class="fas fa-calendar-day text-secondary me-1"></i>Tanggal Mutasi
                                    </label>
                                    <input type="date" name="date" id="mutationDate" class="form-control form-control-sm bg-white" value="{{ old('date', date('Y-m-d')) }}">
                                    <small class="text-muted" style="font-size: 0.7rem;">Gunakan tanggal hari ini atau pilih tanggal transaksi.</small>
                                </div>
                                <div class="col-md-8">
                                    <label for="categoryReason" class="form-label form-label-sm fw-bold text-dark mb-1">
                                        <i class="fas fa-list-check text-secondary me-1"></i>Kategori / Alasan Mutasi <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select form-select-sm bg-white" id="selectReasonPreset" style="max-width: 220px;">
                                            <option value="">-- Kategori Preset --</option>
                                        </select>
                                        <input type="text" name="category_reason" id="categoryReason" class="form-control form-control-sm bg-white" placeholder="Ketik alasan mutasi..." value="{{ old('category_reason') }}" required>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Contoh: Hasil Produksi SPK #102, Display Pameran, Barang Cacat Jahitan, Dll.</small>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Daftar Produk yang Dimutasi --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-dark mb-0">
                                    3. Daftar Barang Gudang Jadi yang Dimutasi <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 px-3 fw-semibold" id="btnAddRow">
                                    <i class="fas fa-plus me-1"></i> Tambah Baris
                                </button>
                            </div>

                            <div class="table-responsive rounded-3 border" style="overflow: visible !important;">
                                <table class="table table-hover align-middle mb-0" id="tableItems">
                                    <thead class="table-light border-bottom">
                                        <tr class="text-uppercase text-secondary small fw-bold" style="font-size: 0.72rem;">
                                            <th width="50%" class="ps-3">PILIH MASTER PRODUK</th>
                                            <th width="20%" class="text-center">STOK SEKARANG</th>
                                            <th width="18%" class="text-center">QTY MUTASI</th>
                                            <th width="12%" class="pe-3 text-center">AKSI</th>
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
                            <label for="notes" class="form-label form-label-sm fw-bold text-dark mb-1">
                                Catatan / Keterangan Tambahan (Opsional)
                            </label>
                            <textarea name="notes" id="notes" rows="2" class="form-control form-control-sm bg-white" placeholder="Tuliskan rincian atau nomor dokumen referensi jika ada...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="alert alert-info border-0 bg-info bg-opacity-10 py-3 px-3 rounded-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info bg-opacity-20 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                <i class="fas fa-sync-alt fs-5"></i>
                            </div>
                            <div class="small text-dark">
                                <strong>Sinkronisasi Otomatis Real-time:</strong> Setelah disimpan, stok produk master akan langsung diperbarui dan disinkronkan otomatis ke seluruh toko marketplace yang terhubung (Shopee, TikTok, Lazada, Tokopedia).
                            </div>
                        </div>

                    </div>

                    <div class="card-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center border-top">
                        <a href="{{ route('inventory.mutations.index') }}" class="btn btn-light border text-secondary btn-sm px-4 rounded-3 fw-semibold">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-bold shadow-sm" id="btnSubmit">
                            <i class="fas fa-save me-1.5"></i> Simpan Mutasi Gudang
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>

</div>

{{-- Data & Script untuk JavaScript --}}
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
       ===================================================== */
    function createProductAutocomplete(rowIndex, preProduct = null) {
        const wrapper = document.createElement('div');
        wrapper.className = 'autocomplete-wrapper position-relative';

        const input = document.createElement('input');
        input.type        = 'text';
        input.className   = 'form-control form-control-sm bg-white';
        input.placeholder = 'Ketik nama / SKU produk...';
        input.autocomplete = 'off';
        input.required    = true;

        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = `items[${rowIndex}][product_id]`;
        hidden.required = true;

        const dropdown = document.createElement('ul');
        dropdown.className = 'autocomplete-dropdown list-unstyled mb-0 shadow-md border rounded-3 bg-white position-absolute w-100';
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
                        <strong class="text-dark">${escHtml(p.name)}</strong>
                    </span>
                    <span class="badge bg-light text-dark border ms-2">Stok: ${p.stock}</span>`;
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
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
            }, 280);
        });

        input.addEventListener('focus', () => {
            const tr = wrapper.closest('tr');
            if (tr) tr.style.zIndex = '1050';
            if (input.value.trim().length >= 1 && !hidden.value) {
                input.dispatchEvent(new Event('input'));
            }
        });

        input.addEventListener('blur', () => {
            setTimeout(() => { 
                dropdown.style.display = 'none';
                const tr = wrapper.closest('tr');
                if (tr) tr.style.zIndex = '1';
            }, 200);
            if (!hidden.value) input.value = '';
        });

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

        if (selectedType === 'out') typeOut.checked = true;
        else typeIn.checked = true;

        function updateReasonPresets() {
            const isOut   = typeOut.checked;
            const presets = isOut ? reasonsOut : reasonsIn;
            selectReasonPreset.innerHTML = '<option value="">-- Kategori Preset --</option>';
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

            const tdProduct = document.createElement('td');
            tdProduct.className = 'ps-3';
            const { wrapper } = createProductAutocomplete(rowIndex, preProduct);
            tdProduct.appendChild(wrapper);

            const tdStock = document.createElement('td');
            tdStock.className = 'text-center align-middle';
            tdStock.innerHTML = `<span class="badge bg-light text-dark font-monospace border current-stock-badge px-2.5 py-1" style="font-size:0.8rem;">0</span>`;

            const tdQty = document.createElement('td');
            tdQty.className = 'text-center align-middle';
            tdQty.innerHTML = `<input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm text-center input-qty font-monospace bg-white" min="1" value="1" required>`;

            const tdAction = document.createElement('td');
            tdAction.className = 'text-center align-middle pe-3';
            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = 'btn btn-outline-danger btn-sm rounded-2 btn-remove-row py-0.5 px-2';
            btnRemove.title = 'Hapus Baris';
            btnRemove.innerHTML = '<i class="fas fa-trash-can"></i>';
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

        addRow(PRE_SELECTED);
    });
</script>

<style>
    .type-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        background-color: #ffffff;
    }
    .type-card:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
    }
    .btn-check:checked + .type-card-in {
        border-color: #10b981 !important;
        background-color: rgba(16, 185, 129, 0.08) !important;
    }
    .btn-check:checked + .type-card-in .type-title {
        color: #047857 !important;
    }
    .btn-check:checked + .type-card-in .type-icon {
        background-color: #10b981 !important;
        color: #ffffff !important;
    }
    .btn-check:checked + .type-card-out {
        border-color: #ef4444 !important;
        background-color: rgba(239, 68, 68, 0.08) !important;
    }
    .btn-check:checked + .type-card-out .type-title {
        color: #b91c1c !important;
    }
    .btn-check:checked + .type-card-out .type-icon {
        background-color: #ef4444 !important;
        color: #ffffff !important;
    }
    #tableItems tr {
        position: relative;
        z-index: 1;
    }
    .autocomplete-wrapper {
        position: relative;
    }
    .autocomplete-dropdown {
        z-index: 99999 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #cbd5e1 !important;
        margin-top: 4px;
    }
    .autocomplete-item {
        color: #1e293b;
        background-color: #ffffff;
    }
    .autocomplete-item:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .autocomplete-dropdown::-webkit-scrollbar {
        width: 5px;
    }
    .autocomplete-dropdown::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>
@endsection
