@extends('layouts.app')
@section('title', 'Buat SPK Baru')
@section('page-title', 'Buat SPK Baru')

@section('content')
<div class="mx-auto" style="max-width:960px">
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
            <div>
                <h5 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2"></i>Buat SPK Baru</h5>
                <small class="text-white-50">Isi data produksi, rancangan pakaian, pembagian tugas penjahit, dan visual desain.</small>
            </div>
            <a href="{{ route('spks.index') }}" class="btn btn-sm btn-light fw-semibold px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <form action="{{ route('spks.store') }}" method="POST" enctype="multipart/form-data" class="m-0" id="spkForm">
            @csrf
            
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Informasi SPK Header --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">No. Produksi</label>
                        <input type="text" name="no_produksi" class="form-control form-control-sm" placeholder="Contoh: JN26148" value="{{ old('no_produksi') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">No. SPK</label>
                        <input type="text" class="form-control form-control-sm bg-light" readonly value="[Otomatis Digenerate Sistem]">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tanggal Order <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" required value="{{ old('tanggal', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tanggal Jatuh Tempo / Deadline <span class="text-danger">*</span></label>
                        <input type="date" name="deadline" class="form-control form-control-sm" required value="{{ old('deadline', date('Y-m-d', strtotime('+14 days'))) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Nama Pemesan</label>
                        <input type="text" name="pemesan" class="form-control form-control-sm" placeholder="Contoh: Ibu Yanti" value="{{ old('pemesan') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">No. HP Pemesan</label>
                        <input type="text" name="no_hp_pemesan" class="form-control form-control-sm" placeholder="Contoh: 0852-4828-5020" value="{{ old('no_hp_pemesan') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Instansi</label>
                        <input type="text" name="instansi" class="form-control form-control-sm" placeholder="Contoh: Nusantara Seragam" value="{{ old('instansi') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Atribut / Aksesoris / Catatan Tambahan</label>
                        <textarea name="tambahan" class="form-control form-control-sm" rows="3" placeholder="Misal: Atribut & Aksesoris Tambahan, Bordir Logo: 46 Pcs, Kancing Emas...">{{ old('tambahan') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Upload Foto Desain / Pola Baju</label>
                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                        <small class="text-muted small mt-1 d-block">Mendukung format JPEG, PNG, JPG (maks 4MB).</small>
                    </div>
                </div>

                {{-- Detail Produk --}}
                <div class="border-top pt-4 mt-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-boxes text-primary me-2"></i>Detail Item Produk &amp; Tugas Penjahit</h6>
                    <div class="rounded border mb-3">
                        <table class="table table-striped table-bordered align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                    <th style="width: 35%;">Nama Produk / Pilih Katalog</th>
                                    <th style="width: 15%;">SKU Induk</th>
                                    <th style="width: 15%;">SKU Varian</th>
                                    <th style="width: 10%;">Ukuran</th>
                                    <th style="width: 8%;">Qty</th>
                                    <th style="width: 12%;">Tukang Jahit</th>
                                    <th style="width: 5%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Rows added dynamically by Javascript --}}
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold py-2 mb-3" onclick="addRow()">
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

{{-- JSON catalog data to help javascript auto-fill product details --}}
<script>
    const catalogProducts = @json($products);
    const tailorsList = @json($tailors);
</script>

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

    function addRow() {
        const tbody = document.querySelector('#itemsTable tbody');
        const tr = document.createElement('tr');
        tr.id = `row-${rowIndex}`;
        
        let tailorOptions = '<option value="">-- Pilih Tukang Jahit --</option>';
        tailorsList.forEach(e => {
            const escapedName = escapeHtml(e.name);
            tailorOptions += `<option value="${escapedName}">${escapedName}</option>`;
        });

        tr.innerHTML = `
            <td>
                <div class="position-relative">
                    <input type="text" name="items[${rowIndex}][name]" class="form-control form-control-sm item-name" required placeholder="Ketik nama / SKU untuk mencari..." oninput="searchProduct(${rowIndex}, this)" onfocus="searchProduct(${rowIndex}, this)" autocomplete="off">
                    <div class="suggestions-box position-absolute bg-white border rounded shadow-sm w-100 d-none" style="z-index: 1050; max-height: 180px; overflow-y: auto; font-size: 11px; left:0; top:100%;"></div>
                </div>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][sku_induk]" class="form-control form-control-sm item-sku-induk" placeholder="e.g. LPJ">
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][sku]" class="form-control form-control-sm item-sku" placeholder="e.g. LPJ-M">
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
                    ${tailorOptions}
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${rowIndex})">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
    }

    function removeRow(index) {
        const row = document.getElementById(`row-${index}`);
        if (row) {
            row.remove();
        }
    }

    function searchProduct(index, inputEl) {
        const tr = document.getElementById(`row-${index}`);
        const box = tr.querySelector('.suggestions-box');
        const q = inputEl.value.trim().toLowerCase();

        // Clear existing suggestions
        box.innerHTML = '';

        // If search query is empty, hide suggestions box
        if (q.length === 0) {
            box.classList.add('d-none');
            return;
        }

        // Filter products
        const matches = catalogProducts.filter(p => {
            const name = (p.name || '').toLowerCase();
            const sku = (p.sku || '').toLowerCase();
            const skuInduk = (p.sku_induk || '').toLowerCase();
            return name.includes(q) || sku.includes(q) || skuInduk.includes(q);
        }).slice(0, 10); // Limit to top 10 results

        if (matches.length === 0) {
            const div = document.createElement('div');
            div.className = 'p-2 text-muted text-center italic';
            div.innerText = 'Produk tidak ditemukan';
            box.appendChild(div);
        } else {
            matches.forEach(p => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'p-2 border-bottom suggestion-item cursor-pointer hover:bg-light';
                itemDiv.style.cursor = 'pointer';
                itemDiv.innerHTML = `
                    <div class="fw-bold">${escapeHtml(p.name)}</div>
                    <div class="text-muted" style="font-size:10px;">SKU: ${escapeHtml(p.sku || 'N/A')} | Induk: ${escapeHtml(p.sku_induk || 'N/A')}</div>
                `;
                itemDiv.onclick = function() {
                    selectProduct(index, p);
                };
                box.appendChild(itemDiv);
            });
        }

        box.classList.remove('d-none');
    }

    function selectProduct(index, product) {
        const tr = document.getElementById(`row-${index}`);
        
        tr.querySelector('.item-name').value = product.name;
        tr.querySelector('.item-sku').value = product.sku || '';
        tr.querySelector('.item-sku-induk').value = product.sku_induk || '';

        const size = product.ukuran;
        if (size) {
            const sizeSelect = tr.querySelector('.item-size');
            let sizeExists = false;
            for (let i = 0; i < sizeSelect.options.length; i++) {
                if (sizeSelect.options[i].value.toUpperCase() === size.toUpperCase()) {
                    sizeSelect.selectedIndex = i;
                    sizeExists = true;
                    break;
                }
            }
            if (!sizeExists) {
                const opt = document.createElement('option');
                opt.value = size;
                opt.text = size;
                sizeSelect.add(opt);
                sizeSelect.value = size;
            }
        }

        // Hide box
        tr.querySelector('.suggestions-box').classList.add('d-none');
    }

    // Close suggestion boxes when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('item-name')) {
            document.querySelectorAll('.suggestions-box').forEach(box => {
                box.classList.add('d-none');
            });
        }
    });

    // CSS styling helper for hover background
    const style = document.createElement('style');
    style.innerHTML = `
        .suggestion-item:hover {
            background-color: #f8fafc;
        }
    `;
    document.head.appendChild(style);

    // Initialize with 1 empty row
    window.onload = function() {
        addRow();
    }
</script>
@endsection
