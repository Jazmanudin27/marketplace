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
                    <div class="table-responsive rounded border mb-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-muted" style="font-size: 10px;">
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
    const tailorsList = @json($employees);
</script>

<script>
    let rowIndex = 0;

    function addRow() {
        const tbody = document.querySelector('#itemsTable tbody');
        const tr = document.createElement('tr');
        tr.id = `row-${rowIndex}`;
        tr.innerHTML = `
            <td>
                <select class="form-select form-select-sm select-catalog mb-1" onchange="autoFillProduct(${rowIndex}, this)">
                    <option value="">-- Manual (Ketik Sendiri) --</option>
                    ${catalogProducts.map(p => `<option value="${p.id}" data-sku="${p.sku || ''}" data-sku-induk="${p.sku_induk || ''}" data-name="${p.name || ''}" data-size="${p.ukuran || ''}">${p.name} (${p.sku || 'N/A'})</option>`).join('')}
                </select>
                <input type="text" name="items[${rowIndex}][name]" class="form-control form-control-sm item-name" required placeholder="Nama Produk">
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
                    <option value="">-- Pilih Tukang Jahit --</option>
                    ${tailorsList.map(e => `<option value="${e.name}">${e.name}</option>`).join('')}
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

    function autoFillProduct(index, selectEl) {
        const tr = document.getElementById(`row-${index}`);
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        
        if (selectedOption.value === "") {
            // Clear details for manual input
            tr.querySelector('.item-name').value = "";
            tr.querySelector('.item-sku').value = "";
            tr.querySelector('.item-sku-induk').value = "";
            return;
        }

        const name = selectedOption.getAttribute('data-name');
        const sku = selectedOption.getAttribute('data-sku');
        const skuInduk = selectedOption.getAttribute('data-sku-induk');
        const size = selectedOption.getAttribute('data-size');

        tr.querySelector('.item-name').value = name;
        tr.querySelector('.item-sku').value = sku;
        tr.querySelector('.item-sku-induk').value = skuInduk;
        
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
                // Add size dynamically if not standard S/M/L etc.
                const opt = document.createElement('option');
                opt.value = size;
                opt.text = size;
                sizeSelect.add(opt);
                sizeSelect.value = size;
            }
        }
    }

    // Initialize with 1 empty row
    window.onload = function() {
        addRow();
    }
</script>
@endsection
