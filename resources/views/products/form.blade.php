@extends('layouts.app')
@section('title', isset($product->id) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', isset($product->id) ? 'Edit Master Produk' : 'Tambah Master Produk')

@section('content')
    @php
        $isLinked = isset($product) && $product->marketplaceProducts()->exists();
    @endphp
    <div class="container-fluid px-0">
        <div class="row">
            <div class="col-md-12">
                <div class="card border shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                @if (isset($product->id))
                                    <i class="fas fa-edit me-2 text-info"></i> Edit Produk — <span
                                        class="text-info font-monospace">{{ $product->sku }}</span>
                                @else
                                    <i class="fas fa-plus-circle me-2 text-info"></i> Tambah Produk Baru
                                @endif
                            </h6>
                            <small class="text-muted d-block">
                                {{ isset($product->id) ? 'Perbarui informasi dan rincian produk master' : 'Buat data produk master baru' }}
                            </small>
                        </div>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm px-3 rounded-3">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body p-3">

                    {{-- ── Form ────────────────────────────────────────────── --}}
                    <form id="product-form"
                        action="{{ isset($product->id) ? route('products.update', $product) : route('products.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($product->id))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-sm fw-semibold">SKU VARIASI <small
                                        class="text-muted fw-normal">(Kosongkan untuk generate otomatis)</small></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="sku" id="sku" class="form-control form-control-sm"
                                        value="{{ old('sku', $product->sku ?? '') }}"
                                        {{ isset($product->id) ? 'disabled' : '' }}
                                        placeholder="Contoh: SKU-001 (atau biarkan kosong)">
                                </div>
                                @error('sku')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sku_induk" class="form-label form-label-sm fw-semibold">SKU INDUK</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                    <input type="text" id="sku_induk" name="sku_induk"
                                        class="form-control form-control-sm"
                                        value="{{ old('sku_induk', $product->sku_induk ?? '') }}"
                                        placeholder="Contoh: SKU-PARENT-001">
                                </div>
                                @error('sku_induk')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label form-label-sm fw-semibold">NAMA BARANG <span
                                    class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" id="name" name="name" class="form-control form-control-sm"
                                    value="{{ old('name', $product->name ?? '') }}" required minlength="30"
                                    placeholder="Nama barang lengkap (min. 30 karakter)">
                            </div>
                            <div id="name-counter" class="form-text font-weight-semibold"></div>
                            @error('name')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label form-label-sm fw-semibold">Harga Jual (Rp) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                    <input type="text" id="price" name="price"
                                        class="form-control form-control-sm formatted-number-input"
                                        value="{{ old('price', isset($product->price) ? (int) $product->price : '') }}"
                                        required placeholder="0">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#priceCalculatorModal">
                                        <i class="fas fa-calculator me-1"></i> Hitung Profit
                                    </button>
                                </div>
                                @error('price')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cost_price" class="form-label form-label-sm fw-semibold">HPP PRODUK / Harga
                                    Modal (Rp)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-wallet"></i></span>
                                    <input type="text" id="cost_price" name="cost_price"
                                        class="form-control form-control-sm formatted-number-input"
                                        value="{{ old('cost_price', isset($product->cost_price) ? (int) $product->cost_price : '') }}"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="stock" class="form-label form-label-sm fw-semibold">Stok Fisik <span
                                        class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                                    <input type="number" id="stock" name="stock"
                                        class="form-control form-control-sm {{ $isLinked ? 'bg-light text-muted readonly-by-link' : '' }}"
                                        value="{{ old('stock', $product->stock ?? 0) }}" min="0" required
                                        placeholder="0" {{ $isLinked ? 'readonly' : '' }}>
                                </div>
                                @if ($isLinked)
                                    <div class="form-text text-warning small mt-1">
                                        <i class="fas fa-lock me-1"></i> Stok dikunci karena produk sudah terhubung.
                                    </div>
                                @endif
                                @error('stock')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="min_stock" class="form-label form-label-sm fw-semibold">Stok Minimum</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                    <input type="number" id="min_stock" name="min_stock"
                                        class="form-control form-control-sm"
                                        value="{{ old('min_stock', $product->min_stock ?? 0) }}" min="0"
                                        placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="is_active" class="form-label form-label-sm fw-semibold">STATUS</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                    <select id="is_active" name="is_active" class="form-select form-select-sm select2">
                                        <option value="1"
                                            {{ old('is_active', $product->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif
                                        </option>
                                        <option value="0"
                                            {{ old('is_active', $product->is_active ?? 1) == 0 ? 'selected' : '' }}>
                                            Nonaktif
                                        </option>
                                    </select>
                                </div>
                                @error('is_active')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="is_preorder" name="is_preorder"
                                        value="1"
                                        {{ old('is_preorder', $product->is_preorder ?? 0) == 1 ? 'checked' : '' }}>
                                    <label class="form-label form-label-sm fw-semibold ms-2" for="is_preorder">Jadikan
                                        Produk Pre-Order (PO)</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="is_bundle" name="is_bundle"
                                        value="1"
                                        {{ old('is_bundle', $product->is_bundle ?? 0) == 1 ? 'checked' : '' }}>
                                    <label class="form-label form-label-sm fw-semibold ms-2" for="is_bundle">Jadikan
                                        Produk Set / Bundling (Paket)</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3" id="preorder_days_wrapper"
                                style="display: {{ old('is_preorder', $product->is_preorder ?? 0) == 1 ? 'block' : 'none' }};">
                                <label for="preorder_days" class="form-label form-label-sm fw-semibold">Estimasi Waktu
                                    Produksi (Hari)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="number" id="preorder_days" name="preorder_days"
                                        class="form-control form-control-sm"
                                        value="{{ old('preorder_days', $product->preorder_days ?? 7) }}" min="1"
                                        placeholder="7">
                                </div>
                                @error('preorder_days')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label form-label-sm fw-semibold">KATEGORI</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                    <select id="category_id" name="category_id"
                                        class="form-select form-select-sm select2">
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('category_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sub_kategori" class="form-label form-label-sm fw-semibold">SUB
                                    KATEGORI</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                                    <input type="text" id="sub_kategori" name="sub_kategori"
                                        class="form-control form-control-sm"
                                        value="{{ old('sub_kategori', $product->sub_kategori ?? '') }}"
                                        placeholder="Contoh: Kaos Polos">
                                </div>
                                @error('sub_kategori')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="brand_id" class="form-label form-label-sm fw-semibold">Merk / Brand</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-certificate"></i></span>
                                    <select id="brand_id" name="brand_id" class="form-select form-select-sm select2">
                                        <option value="">-- Pilih Merk --</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}"
                                                {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('brand_id')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="ukuran" class="form-label form-label-sm fw-semibold">UKURAN</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                    <input type="text" id="ukuran" name="ukuran"
                                        class="form-control form-control-sm"
                                        value="{{ old('ukuran', $product->ukuran ?? '') }}"
                                        placeholder="Contoh: L, XL, 39, 40">
                                </div>
                                @error('ukuran')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="warna" class="form-label form-label-sm fw-semibold">WARNA</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-palette"></i></span>
                                    <input type="text" id="warna" name="warna"
                                        class="form-control form-control-sm"
                                        value="{{ old('warna', $product->warna ?? '') }}"
                                        placeholder="Contoh: Hitam, Putih, Navy">
                                </div>
                                @error('warna')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="unit" class="form-label form-label-sm fw-semibold">Satuan</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-cube"></i></span>
                                    <input type="text" id="unit" name="unit"
                                        class="form-control form-control-sm"
                                        value="{{ old('unit', $product->unit ?? '') }}" placeholder="pcs, kg, box...">
                                </div>
                                @error('unit')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="weight" class="form-label form-label-sm fw-semibold">Berat (kg) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-weight"></i></span>
                                    <input type="number" step="0.001" id="weight" name="weight"
                                        class="form-control form-control-sm"
                                        value="{{ old('weight', $product->weight ?? 0.1) }}" required
                                        placeholder="0.100">
                                </div>
                                @error('weight')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-sm fw-semibold">Gambar Produk</label>

                                {{-- Mode Toggle --}}
                                <div class="btn-group w-100 mb-2" role="group">
                                    <button type="button" id="img-tab-upload" class="btn btn-primary btn-sm">
                                        <i class="fas fa-upload me-1"></i> Upload File
                                    </button>
                                    <button type="button" id="img-tab-url" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-link me-1"></i> Pakai URL
                                    </button>
                                </div>

                                {{-- Upload Mode --}}
                                <div id="img-mode-upload" class="mb-2">
                                    <div id="img-drop-zone"
                                        class="border border-2 border-secondary rounded p-4 text-center cursor-pointer"
                                        style="cursor: pointer; border-style: dashed !important;">
                                        <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2 d-block"></i>
                                        <div class="fw-semibold text-secondary small">Klik atau seret gambar ke sini</div>
                                        <small class="text-muted d-block">JPG, PNG, WEBP — maks 5 MB</small>
                                        <div id="img-filename" class="mt-2 text-primary small" style="display: none;">
                                        </div>
                                    </div>
                                    <input type="file" id="image_file" name="image_file"
                                        accept="image/jpeg,image/png,image/webp" class="d-none">
                                    @error('image_file')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- URL Mode --}}
                                <div id="img-mode-url" class="mb-2" style="display: none;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                                        <input type="text" id="image_url" name="image_url"
                                            class="form-control form-control-sm"
                                            value="{{ old('image_url', $product->image_url ?? '') }}"
                                            placeholder="https://example.com/gambar.jpg">
                                    </div>
                                    @error('image_url')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Live Preview --}}
                                <div id="img-preview-wrap" class="mt-2"
                                    style="display: {{ $product->image_url ?? '' ? 'block' : 'none' }};">
                                    <div class="text-muted small mb-1"><i class="fas fa-eye me-1"></i> Preview</div>
                                    <div class="position-relative d-inline-block">
                                        <img id="img-preview" src="{{ $product->image_url ?? '' }}"
                                            class="img-thumbnail"
                                            style="max-width: 180px; max-height: 180px; object-fit: cover; display: block;">
                                        <button type="button" id="btn-clear-img"
                                            class="btn btn-danger btn-sm rounded-circle position-absolute top-0 start-100 translate-middle p-0"
                                            style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="length" class="form-label form-label-sm fw-semibold">Panjang (cm)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                                    <input type="number" step="0.1" id="length" name="length"
                                        class="form-control form-control-sm"
                                        value="{{ old('length', $product->length ?? '') }}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="width" class="form-label form-label-sm fw-semibold">Lebar (cm)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                    <input type="number" step="0.1" id="width" name="width"
                                        class="form-control form-control-sm"
                                        value="{{ old('width', $product->width ?? '') }}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="height" class="form-label form-label-sm fw-semibold">Tinggi (cm)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                    <input type="number" step="0.1" id="height" name="height"
                                        class="form-control form-control-sm"
                                        value="{{ old('height', $product->height ?? '') }}" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label form-label-sm fw-semibold">Deskripsi Produk</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text align-items-start pt-2"><i
                                        class="fas fa-align-left"></i></span>
                                <textarea id="description" name="description" class="form-control form-control-sm" rows="4"
                                    placeholder="Deskripsi lengkap produk...">{{ old('description', $product->description ?? '') }}</textarea>
                            </div>
                            @error('description')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ── KOMPONEN BUNDLING / SET ── --}}
                        <div id="bundle-components-section" class="border rounded-3 p-3 mb-3 bg-light" style="display: {{ old('is_bundle', $product->is_bundle ?? 0) == 1 ? 'block' : 'none' }}; border-color: #cbd5e1 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-cubes text-success me-2"></i>Komponen Produk Set / Paket</h6>
                                <button type="button" class="btn btn-xs btn-outline-success fw-semibold" id="btn-add-component-row">
                                    <i class="fas fa-plus me-1"></i> Tambah Produk
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle bg-white rounded shadow-sm mb-0" id="table-components" style="font-size: 12px;">
                                    <thead>
                                        <tr class="small text-uppercase text-muted" style="font-size:10px">
                                            <th style="width: 70%" class="ps-2">Nama Produk / SKU Komponen</th>
                                            <th style="width: 20%">Jumlah (Qty)</th>
                                            <th style="width: 10%" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($product) && $product->is_bundle && $product->components->count() > 0)
                                            @foreach($product->components as $cIdx => $comp)
                                                <tr class="component-row">
                                                    <td class="ps-2">
                                                        <select name="components[{{ $cIdx }}][child_id]" class="form-select form-select-sm select-component-item" required>
                                                            <option value=""></option>
                                                            @foreach($allProducts as $ap)
                                                                <option value="{{ $ap->id }}" data-sku="{{ $ap->sku }}" {{ $comp->id == $ap->id ? 'selected' : '' }}>
                                                                    {{ $ap->name }} ({{ $ap->sku }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="components[{{ $cIdx }}][quantity]" class="form-control form-control-sm text-center" min="1" value="{{ $comp->pivot->quantity }}" required>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-link text-danger btn-remove-component-row"><i class="fas fa-trash-alt"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size:10px;">
                                <i class="fas fa-info-circle me-1"></i> Stok produk set ini akan dihitung otomatis oleh sistem mengikuti jumlah terkecil dari produk anak penyusunnya.
                            </small>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm px-4">Batal</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-save me-1"></i>
                                {{ isset($product->id) ? 'Simpan Perubahan' : 'Simpan Produk' }}
                            </button>
                        </div>
                    </form>

                    @if (isset($product->id))
                        <div class="mt-4 pt-3 border-top">
                            <form action="{{ route('products.destroy', $product) }}" method="POST"
                                onsubmit="return confirm('Hapus produk ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-3">
                                    <i class="fas fa-trash me-1"></i> Hapus Produk Ini
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            @if (isset($product->id))
                <div class="col-md-12 mt-4">
                    <div class="card border shadow-sm">
                        <div class="card-header bg-purple bg-opacity-10 border-bottom py-3 px-4 d-flex align-items-center gap-2" style="background:#f3e8ff">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#8b5cf6">
                                <i class="fas fa-flask text-white"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Formulasi Resep &amp; Jasa Ahli (BOM)</h6>
                                <small class="text-muted">Tentukan takaran bahan baku/kemasan serta biaya jasa tenaga ahli/QC untuk HPP otomatis.</small>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('products.save_recipe', $product) }}" method="POST">
                                @csrf
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label for="batch_qty" class="form-label fw-semibold small text-muted">Kapasitas Batch Standard (Qty)</label>
                                        <div class="input-group">
                                            <input type="number" name="batch_qty" id="batch_qty" class="form-control" min="1" value="{{ $recipe ? $recipe->batch_qty : 1 }}" required>
                                            <span class="input-group-text">{{ $product->unit ?: 'pcs' }}</span>
                                        </div>
                                        <div class="form-text text-muted small">Biaya resep dihitung proporsional per unit berdasarkan total kapasitas satu batch ini.</div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <!-- Kiri: Bahan Baku & Kemasan -->
                                    <div class="col-lg-7 border-end">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-boxes me-2 text-primary"></i>1. Bahan Baku &amp; Kemasan (BOM)</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btn-add-bom-row">
                                                <i class="fas fa-plus me-1"></i> Tambah Bahan
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle" id="table-bom">
                                                <thead>
                                                    <tr class="small text-uppercase text-muted" style="font-size:11px">
                                                        <th style="width: 55%">Bahan / Item</th>
                                                        <th style="width: 30%">Qty Dibutuhkan</th>
                                                        <th style="width: 15%" class="text-center"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if($recipe && $recipe->items->count() > 0)
                                                        @foreach($recipe->items as $idx => $rItem)
                                                            <tr class="bom-row">
                                                                <td>
                                                                    <select name="items[{{ $idx }}][inventory_item_id]" class="form-select select-bom-item" required>
                                                                        <option value=""></option>
                                                                        @foreach($inventoryItems as $item)
                                                                            <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" {{ $rItem->inventory_item_id == $item->id ? 'selected' : '' }}>
                                                                                {{ $item->name }} ({{ $item->sku }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number" step="0.0001" name="items[{{ $idx }}][quantity]" class="form-control" value="{{ (float)$rItem->quantity }}" min="0.0001" required>
                                                                        <span class="input-group-text span-bom-unit" style="font-size:10px">{{ $rItem->inventoryItem->unit }}</span>
                                                                    </div>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-bom-row"><i class="fas fa-trash-alt"></i></button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Kanan: Template Jasa Ahli -->
                                    <div class="col-lg-5">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-cog me-2 text-warning"></i>2. Template Jasa Ahli &amp; QC</h6>
                                            <button type="button" class="btn btn-sm btn-outline-warning fw-semibold" id="btn-add-labor-row">
                                                <i class="fas fa-plus me-1"></i> Tambah Jasa
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle" id="table-labor">
                                                <thead>
                                                    <tr class="small text-uppercase text-muted" style="font-size:11px">
                                                        <th style="width: 55%">Nama Jasa / QC</th>
                                                        <th style="width: 35%">Biaya Default (Rp)</th>
                                                        <th style="width: 10%" class="text-center"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if($recipe && $recipe->labors->count() > 0)
                                                        @foreach($recipe->labors as $idx => $rLabor)
                                                            <tr class="labor-row">
                                                                <td>
                                                                    <input type="text" name="labors[{{ $idx }}][service_name]" class="form-control form-control-sm" value="{{ $rLabor->service_name }}" placeholder="Misal: QC, Operator" required>
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="labors[{{ $idx }}][default_cost]" class="form-control form-control-sm" value="{{ (int)$rLabor->default_cost }}" min="0" required>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-labor-row"><i class="fas fa-trash-alt"></i></button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-success fw-bold px-4">
                                        <i class="fas fa-save me-1"></i> Simpan Resep &amp; Jasa Ahli
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Kalkulator Harga Shopee -->
    <div class="modal fade" id="priceCalculatorModal" tabindex="-1" aria-labelledby="priceCalculatorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger bg-opacity-10 border-bottom">
                    <h5 class="modal-title text-danger fw-bold" id="priceCalculatorModalLabel">
                        <i class="fas fa-calculator me-2"></i> KALKULATOR HARGA & PROFIT SHOPEE (DETAIL)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Input Column -->
                        <div class="col-md-5 border-end d-flex flex-column">
                            <div class="overflow-auto pe-2" style="max-height: 70vh;">

                                <!-- Pengaturan Informasi -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-info-circle me-1 text-primary"></i> Pengaturan Informasi
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label extra-small text-secondary mb-1">Nama SKU INDUK</label>
                                        <input type="text" id="calc-sku"
                                            class="form-control form-control-sm bg-light" readonly>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">COGS / HPP
                                                (Rp)</label>
                                            <input type="text" id="calc-hpp"
                                                class="form-control form-control-sm calc-number-format"
                                                placeholder="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Biaya Operasional /
                                                OPEX (Rp)</label>
                                            <input type="text" id="calc-opex"
                                                class="form-control form-control-sm calc-number-format"
                                                placeholder="0" value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Persentase Biaya Marketing -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-bullhorn me-1 text-primary"></i> Persentase Biaya Marketing
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Target ROAS (GMV
                                                Max)</label>
                                            <input type="number" step="0.1" id="calc-roas-target"
                                                class="form-control form-control-sm" value="20">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Komisi Affiliate
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-affiliate-pct"
                                                class="form-control form-control-sm" value="10">
                                        </div>
                                    </div>
                                </div>

                                <!-- Promosi Seller -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-tags me-1 text-primary"></i> Promosi Seller
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Voucher Toko
                                                (Rp)</label>
                                            <input type="text" id="calc-voucher-toko"
                                                class="form-control form-control-sm calc-number-format"
                                                placeholder="0" value="10.000">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Voucher Produk
                                                (Rp)</label>
                                            <input type="text" id="calc-voucher-produk"
                                                class="form-control form-control-sm calc-number-format"
                                                placeholder="0" value="5.000">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label extra-small text-secondary mb-1">Potongan Lain / DLL
                                            (Rp)</label>
                                        <input type="text" id="calc-voucher-dll"
                                            class="form-control form-control-sm calc-number-format"
                                            placeholder="0" value="0">
                                    </div>
                                </div>

                                <!-- Co-Fund Voucher -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-handshake me-1 text-primary"></i> Co-Fund Voucher (Voucher
                                        Bersama)
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Diskon Voucher
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-cofund-pct"
                                                class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Batas Maks. Seller
                                                (Rp)</label>
                                            <input type="text" id="calc-cofund-max-seller"
                                                class="form-control form-control-sm calc-number-format"
                                                value="0">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Ditanggung Platform
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-cofund-plat-pct"
                                                class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Ditanggung Penjual
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-cofund-seller-pct"
                                                class="form-control form-control-sm" value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Persentase Biaya Marketplace -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-percentage me-1 text-primary"></i> Persentase Biaya Marketplace
                                    </div>
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <label class="form-label extra-small text-secondary mb-0">Biaya Administrasi
                                                Dasar</label>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-admin-pct"
                                                    class="form-control text-end" value="8.25">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-mall-active">
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-mall-active">Biaya Layanan Mall</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-mall-pct"
                                                    class="form-control text-end" value="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-premi-active"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-premi-active">Premi</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-premi-pct"
                                                    class="form-control text-end" value="0.50">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <label class="form-label extra-small text-secondary mb-0">Biaya Per Pesanan
                                                (Rp)</label>
                                        </div>
                                        <div class="col-5">
                                            <input type="text" id="calc-pesanan-fee"
                                                class="form-control form-control-sm text-end calc-number-format"
                                                value="1.250">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <label class="form-label extra-small text-secondary mb-0">Hemat Biaya Kirim
                                                (Rp)</label>
                                        </div>
                                        <div class="col-5">
                                            <input type="text" id="calc-hemat-ongkir"
                                                class="form-control form-control-sm text-end calc-number-format"
                                                value="350">
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-7">
                                            <label class="form-label extra-small text-secondary mb-0">Biaya Logistik
                                                (Rp)</label>
                                        </div>
                                        <div class="col-5">
                                            <input type="text" id="calc-logistik-fee"
                                                class="form-control form-control-sm text-end calc-number-format"
                                                value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Persentase Program Pemasaran -->
                                <div class="card bg-transparent border-0 mb-2">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-shopping-basket me-1 text-primary"></i> Persentase Program
                                        Pemasaran
                                    </div>

                                    <!-- Gratis Ongkir Xtra -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-ongkir"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-ongkir">Gratis Ongkir Xtra</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-ongkir-pct"
                                                    class="form-control text-end" value="7.50">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Promo Xtra -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-promo"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-promo">Promo Xtra</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-promo-pct"
                                                    class="form-control text-end" value="4.50">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Promo Xtra+ (Plus) -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-promo-plus"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-promo-plus">Promo Xtra+ (Plus)</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-promo-plus-pct"
                                                    class="form-control text-end" value="2.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Xtra -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-live"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-live">Live Xtra</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-live-pct"
                                                    class="form-control text-end" value="2.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Video Xtra -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-video">
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-video">Video Xtra</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-video-pct"
                                                    class="form-control text-end" value="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pre-Order -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-preorder"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-preorder">Pre-Order</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-preorder-pct"
                                                    class="form-control text-end" value="3.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Spaylater 3 Bulan -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="calc-prog-spaylater3"
                                                    checked>
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-spaylater3">Spaylater 3 Bulan</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-spaylater3-pct"
                                                    class="form-control text-end" value="2.50">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Spaylater 6 Bulan -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-7">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox"
                                                    id="calc-prog-spaylater6">
                                                <label class="form-check-label extra-small text-secondary"
                                                    for="calc-prog-spaylater6">Spaylater 6 Bulan</label>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" id="calc-prog-spaylater6-pct"
                                                    class="form-control text-end" value="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Output Column -->
                        <div class="col-md-7 d-flex flex-column ps-md-4 mt-3 mt-md-0">
                            <div class="overflow-auto pe-2" style="max-height: 70vh;">

                                <!-- Input Harga Jual Uji (CORE) -->
                                <div class="p-3 rounded mb-3 border border-danger border-opacity-25 bg-danger bg-opacity-10">
                                    <label class="form-label small fw-bold text-danger mb-2"><i
                                            class="fas fa-coins me-1 text-warning"></i> HARGA JUAL UJI (IDR)</label>
                                    <div class="input-group">
                                        <span
                                            class="input-group-text font-monospace fw-bold">Rp</span>
                                        <input type="text" id="calc-price"
                                            class="form-control form-control-lg text-end font-monospace fw-bold calc-number-format"
                                            placeholder="0" value="385.900">
                                    </div>
                                </div>

                                <!-- Perhitungan Harga Jual -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-calculator me-1 text-success"></i> Perhitungan Harga Jual
                                    </div>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tbody>
                                            <tr>
                                                <td class="text-secondary ps-0">Tampil Harga di Pembeli</td>
                                                <td>
                                                    <div class="input-group input-group-sm d-inline-flex"
                                                        style="width: 80px;">
                                                        <input type="number" id="calc-promo-pct"
                                                            class="form-control p-1 text-end"
                                                            value="20">
                                                        <span class="input-group-text p-1">%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold font-monospace text-warning"
                                                    id="res-price-buyer-rp">Rp 0</td>
                                            </tr>
                                            <tr>
                                                <td class="text-secondary ps-0">Harga Coret</td>
                                                <td>
                                                    <div class="input-group input-group-sm d-inline-flex"
                                                        style="width: 80px;">
                                                        <input type="number" id="calc-coret-pct"
                                                            class="form-control p-1 text-end"
                                                            value="50">
                                                        <span class="input-group-text p-1">%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold font-monospace text-secondary text-decoration-line-through"
                                                    id="res-coret-price-rp">Rp 0</td>
                                            </tr>
                                            <tr>
                                                <td class="text-secondary ps-0">Strategi Harga (MarkUp)</td>
                                                <td>
                                                    <div class="input-group input-group-sm d-inline-flex"
                                                        style="width: 80px;">
                                                        <input type="number" id="calc-markup-pct"
                                                            class="form-control p-1 text-end"
                                                            value="10">
                                                        <span class="input-group-text p-1">%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold font-monospace text-info"
                                                    id="res-markup-price-rp">Rp 0</td>
                                            </tr>
                                            <tr class="border-top">
                                                <td class="text-secondary ps-0 pt-2">BEP ADS (Break Even Point ROAS)</td>
                                                <td></td>
                                                <td class="text-end fw-bold font-monospace text-danger pt-2"
                                                    id="res-bep-ads-val">0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Hasil Penghitungan (Profit Summary) -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-chart-pie me-1 text-success"></i> Ringkasan Profit Margin
                                    </div>

                                    <!-- Organic -->
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-2 border border-success border-opacity-25 bg-success bg-opacity-10"
                                        id="box-organic">
                                        <div>
                                            <div class="fw-bold small text-dark">Profit Organik</div>
                                            <div class="text-muted extra-small">Murni non-iklan
                                                / non-affiliate</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success font-monospace" id="val-organic-profit">Rp 0
                                            </div>
                                            <span class="badge bg-success-subtle text-success extra-small"
                                                id="val-organic-pct">0%</span>
                                        </div>
                                    </div>

                                    <!-- Affiliate -->
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-2 border border-primary border-opacity-25 bg-primary bg-opacity-10"
                                        id="box-affiliate">
                                        <div>
                                            <div class="fw-bold small text-dark">Profit Affiliate</div>
                                            <div class="text-muted extra-small">Potongan Komisi
                                                & PPN Affiliate</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold font-monospace text-primary" id="val-affiliate-profit">Rp 0</div>
                                            <span class="badge bg-primary-subtle text-primary extra-small"
                                                id="val-affiliate-pct">0%</span>
                                        </div>
                                    </div>

                                    <!-- Ads & Affiliate -->
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-3 border border-warning border-opacity-25 bg-warning bg-opacity-10"
                                        id="box-ads">
                                        <div>
                                            <div class="fw-bold small text-dark">Profit Ads & Affiliate</div>
                                            <div class="text-muted extra-small">Potongan Iklan
                                                GMV Max & Affiliate</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-warning font-monospace" id="val-ads-profit">Rp 0
                                            </div>
                                            <span class="badge bg-warning-subtle text-warning extra-small"
                                                id="val-ads-pct">0%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Target Pasang ROAS Table -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-crosshairs me-1 text-warning"></i> Target Pasang ROAS : GMV Max
                                        ROAS
                                    </div>
                                    <table
                                        class="table table-sm table-bordered mb-0 small font-monospace">
                                        <thead>
                                            <tr class="table-light text-center text-muted">
                                                <th>Ket.</th>
                                                <th>ROAS</th>
                                                <th>Biaya Iklan %</th>
                                                <th>Biaya Iklan Rp</th>
                                                <th>Profit Bersih %</th>
                                                <th>Profit Bersih Rp</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center">Aman / Akselerasi ROAS</td>
                                                <td class="text-center text-success fw-bold" id="res-roas-aman">0.00</td>
                                                <td class="text-center text-danger" id="res-ads-pct-aman">0.00%</td>
                                                <td class="text-end" id="res-ads-rp-aman">Rp 0</td>
                                                <td class="text-center text-success" id="res-profit-pct-aman">0.00%</td>
                                                <td class="text-end text-success fw-bold" id="res-profit-rp-aman">Rp 0
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-center">Setelah Akselerasi (-30%)</td>
                                                <td class="text-center text-warning fw-bold" id="res-roas-akselerasi">0.00
                                                </td>
                                                <td class="text-center text-danger" id="res-ads-pct-akselerasi">0.00%</td>
                                                <td class="text-end" id="res-ads-rp-akselerasi">Rp 0</td>
                                                <td class="text-center text-warning" id="res-profit-pct-akselerasi">0.00%
                                                </td>
                                                <td class="text-end text-warning fw-bold" id="res-profit-rp-akselerasi">Rp
                                                    0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- BEP (Break Even Point) Kalkulator -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-balance-scale me-1 text-danger"></i> BEP (Break Even Point)
                                        Kalkulator
                                    </div>
                                    <table
                                        class="table table-sm table-bordered mb-0 small font-monospace">
                                        <thead>
                                            <tr class="table-light text-center text-muted">
                                                <th>Ket.</th>
                                                <th>ROAS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center">Aman / Akselerasi ROAS</td>
                                                <td class="text-center text-danger fw-bold" id="res-bep-roas-aman">0.00
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-center">Setelah Akselerasi (-30%)</td>
                                                <td class="text-center text-warning fw-bold" id="res-bep-roas-akselerasi">
                                                    0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Rincian Biaya (Detail) -->
                                <div class="card bg-transparent border-0 mb-3">
                                    <div class="text-uppercase fw-bold text-dark small mb-2 pb-1 border-bottom">
                                        <i class="fas fa-list-ul me-1 text-secondary"></i> Rincian Biaya Lengkap
                                    </div>
                                    <div class="p-3 rounded border bg-light bg-opacity-50">
                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace"
                                            style="font-size: 0.85rem;">
                                            <span class="text-success fw-bold"><i
                                                    class="fas fa-arrow-circle-right me-1"></i> Total Pendapatan Organik
                                                 (Omset)</span>
                                            <span class="text-success fw-bold" id="res-net-revenue">Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace border-bottom pb-2"
                                            style="font-size: 0.85rem;">
                                            <span class="text-danger fw-bold"><i class="fas fa-minus-circle me-1"></i>
                                                Total Biaya Marketplace</span>
                                            <span class="text-danger fw-bold" id="res-total-fees">Rp 0</span>
                                        </div>

                                        <!-- Sub details -->
                                        <div class="extra-small ps-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-secondary">Pajak Penghasilan (PPh) (0.50%)</span>
                                                <span class="font-monospace" id="res-pph-val">Rp 0</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-secondary">Biaya Operasional (OPEX)</span>
                                                <span class="font-monospace" id="res-opex-val">Rp 0</span>
                                            </div>

                                            <!-- Admin Fees Breakdown -->
                                            <div class="mb-2">
                                                <div
                                                    class="d-flex justify-content-between fw-bold text-dark border-bottom mb-1">
                                                    <span>Total Biaya Administrasi</span>
                                                    <span id="res-admin-fees-total">Rp 0</span>
                                                </div>
                                                <div class="ps-2 text-muted extra-small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Biaya Administrasi Dasar</span>
                                                        <span id="res-admin-fee-base">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Biaya Layanan Mall</span>
                                                        <span id="res-mall-fee">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Premi</span>
                                                        <span id="res-premi-fee">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Biaya Per Pesanan</span>
                                                        <span id="res-pesanan-fee-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Program Hemat Biaya Kirim</span>
                                                        <span id="res-hemat-ongkir-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Biaya Logistik</span>
                                                        <span id="res-logistik-fee-val">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Marketing Programs Breakdown -->
                                            <div class="mb-2">
                                                <div
                                                    class="d-flex justify-content-between fw-bold text-dark border-bottom mb-1">
                                                    <span>Total Biaya Pemasaran</span>
                                                    <span id="res-marketing-fees-total">Rp 0</span>
                                                </div>
                                                <div class="ps-2 text-muted extra-small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Gratis Ongkir Xtra</span>
                                                        <span id="res-prog-ongkir-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Promo Xtra</span>
                                                        <span id="res-prog-promo-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Promo Xtra+ (Plus)</span>
                                                        <span id="res-prog-promo-plus-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Live Xtra</span>
                                                        <span id="res-prog-live-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Video Xtra</span>
                                                        <span id="res-prog-video-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Pre-Order</span>
                                                        <span id="res-prog-preorder-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Spaylater 3 Bulan</span>
                                                        <span id="res-prog-spaylater3-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Spaylater 6 Bulan</span>
                                                        <span id="res-prog-spaylater6-val">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Total Biaya Marketing (Ads + Affiliate) -->
                                            <div class="mb-2">
                                                <div
                                                    class="d-flex justify-content-between fw-bold text-dark border-bottom mb-1">
                                                    <span>Total Biaya Marketing (Ads + Affiliate)</span>
                                                    <span id="res-ads-marketing-total">Rp 0</span>
                                                </div>
                                                <div class="ps-2 text-muted extra-small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Ads/Iklan GMV Max</span>
                                                        <span id="res-ads-base">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>PPN Ads (12.00%)</span>
                                                        <span id="res-ppn-ads">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Affiliate</span>
                                                        <span id="res-affiliate-base">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>PPN Affiliate (0.50%)</span>
                                                        <span id="res-ppn-aff">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Co-Found Voucher Breakdown -->
                                            <div class="mb-0">
                                                <div
                                                    class="d-flex justify-content-between fw-bold text-dark border-bottom mb-1">
                                                    <span>Total Biaya Co-Found</span>
                                                    <span id="res-cofund-fees-total">Rp 0</span>
                                                </div>
                                                <div class="ps-2 text-muted extra-small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Ditanggung Platform (Diskon Pembeli)</span>
                                                        <span id="res-cofund-plat-val">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Ditanggung Penjual (Beban Seller)</span>
                                                        <span id="res-cofund-seller-val">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Apply Button -->
                            <div class="mt-auto pt-3 border-top">
                                <div class="row g-2">
                                    <div class="col-8">
                                        <button type="button" id="btn-apply-calc-price"
                                            class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                            <i class="fas fa-check-circle me-1"></i> Gunakan Harga Jual Ini
                                        </button>
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100 py-2"
                                            data-bs-dismiss="modal">Batal</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Select2 initialization
            $('.select2').each(function() {
                const select = $(this);
                const parent = select.parent();
                parent.css('position', 'relative');
                select.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: parent
                });
            });

            // If product already has a URL (edit mode), default to URL tab
            const existingUrl = '{{ $product->image_url ?? '' }}';
            if (existingUrl && (existingUrl.startsWith('http://') || existingUrl.startsWith('https://'))) {
                switchImgMode('url');
            }
            // If locally stored image, stay on upload tab but show preview
            if (existingUrl && existingUrl.startsWith('/storage/')) {
                updateImgPreviewSrc(existingUrl);
            }

            // Initialize Preorder Days visibility
            togglePreorderDays($('#is_preorder').is(':checked'));
            $('#is_preorder').on('change', function() {
                togglePreorderDays(this.checked);
            });

            // Initialize Bundle Components visibility
            function toggleBundleSection(isChecked) {
                const section = $('#bundle-components-section');
                const stockInput = $('#stock');
                if (isChecked) {
                    section.show();
                    stockInput.val(0).prop('readonly', true).addClass('bg-light text-muted');
                    if (!$('#stock-bundle-note').length) {
                        stockInput.parent().after('<div id="stock-bundle-note" class="form-text text-success small mt-1"><i class="fas fa-magic me-1"></i> Stok dihitung otomatis dari produk anak.</div>');
                    }
                    $('.form-text.text-warning').hide();
                } else {
                    section.hide();
                    if (!stockInput.hasClass('readonly-by-link')) {
                        stockInput.prop('readonly', false).removeClass('bg-light text-muted');
                    }
                    $('#stock-bundle-note').remove();
                    $('.form-text.text-warning').show();
                }
            }

            toggleBundleSection($('#is_bundle').is(':checked'));
            $('#is_bundle').on('change', function() {
                toggleBundleSection(this.checked);
            });

            // Dynamic Component row indexing
            let componentRowIndex = {{ isset($product) && $product->is_bundle && $product->components->count() > 0 ? $product->components->count() : 0 }};

            $('#btn-add-component-row').on('click', function() {
                let optionsHtml = '<option value=""></option>';
                @if(isset($allProducts))
                    @foreach($allProducts as $ap)
                        optionsHtml += `<option value="{{ $ap->id }}" data-sku="{{ $ap->sku }}">{{ $ap->name }} ({{ $ap->sku }})</option>`;
                    @endforeach
                @endif

                const rowHtml = `
                    <tr class="component-row">
                        <td>
                            <select name="components[\${componentRowIndex}][child_id]" class="form-select form-select-sm select-component-item" required>
                                \${optionsHtml}
                            </select>
                        </td>
                        <td>
                            <input type="number" name="components[\${componentRowIndex}][quantity]" class="form-control form-control-sm text-center" min="1" value="1" required>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-component-row"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                `;

                $('#table-components tbody').append(rowHtml);
                initSelect2ForComponent($('#table-components tbody tr:last-child'));
                componentRowIndex++;
            });

            function initSelect2ForComponent(row) {
                row.find('.select-component-item').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Produk Komponen —',
                    allowClear: true,
                    dropdownParent: $('#table-components')
                });
            }

            $(document).on('click', '.btn-remove-component-row', function() {
                $(this).closest('.component-row').remove();
            });

            // Initialize existing select2 for components
            $('.select-component-item').select2({
                theme: 'bootstrap-5',
                placeholder: '— Pilih Produk Komponen —',
                allowClear: true,
                dropdownParent: $('#table-components')
            });

            // Initialize name counter on page load
            const nameInput = $('#name');
            if (nameInput.length) {
                updateNameCounter(nameInput.val());
                nameInput.on('input', function() {
                    updateNameCounter($(this).val());
                });
            }

            // Initialize & Format number inputs
            $('.formatted-number-input').each(function() {
                const input = $(this);
                let val = input.value || input.val();
                if (val && val.includes('.')) {
                    const parsed = parseFloat(val);
                    if (!isNaN(parsed)) {
                        input.val(Math.round(parsed).toString());
                    }
                }
                formatNumberWithSeparator(this);
            });

            // Format on typing
            $(document).on('input', '.formatted-number-input', function() {
                formatNumberWithSeparator(this);
            });

            // Restrict input to digits only
            $(document).on('keydown', '.formatted-number-input', function(e) {
                if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                    (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return;
                }
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode >
                        105)) {
                    e.preventDefault();
                }
            });

            // Strip dots before form submit so the server receives raw numeric values
            $('#product-form').on('submit', function() {
                $('.formatted-number-input').each(function() {
                    const input = $(this);
                    input.val(input.val().replace(/\./g, ''));
                });
            });

            // Image mode events
            $('#img-tab-upload').on('click', function() {
                switchImgMode('upload');
            });
            $('#img-tab-url').on('click', function() {
                switchImgMode('url');
            });

            // Image file upload triggers
            $('#img-drop-zone').on('click', function() {
                $('#image_file').trigger('click');
            });

            $('#image_file').on('change', function() {
                handleImgFile(this);
            });

            // Drag and drop events
            $('#img-drop-zone').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('border-primary bg-dark').removeClass('border-secondary');
            }).on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('border-primary bg-dark').addClass('border-secondary');
            }).on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('border-primary bg-dark').addClass('border-secondary');
                const files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $('#image_file')[0].files = files;
                    handleImgFile($('#image_file')[0]);
                }
            });

            $('#image_url').on('input', function() {
                updateImgPreview($(this).val());
            });

            $('#btn-clear-img').on('click', function() {
                clearImg();
            });

            // ── Modal Calculator Logic ─────────────────────────────────────────
            const modalEl = $('#priceCalculatorModal');
            if (modalEl.length) {
                modalEl.on('show.bs.modal', function() {
                    const currentHpp = cleanNumber($('#cost_price').val());
                    const currentPrice = cleanNumber($('#price').val());
                    const skuVal = $('#sku').val() || $('input[name="sku"]').val() || $('#name').val() ||
                        'PRODUK';

                    $('#calc-sku').val(skuVal);
                    $('#calc-hpp').val(currentHpp > 0 ? formatNumberID(currentHpp) : '');
                    $('#calc-price').val(currentPrice > 0 ? formatNumberID(currentPrice) : '');

                    calculateMarketplaceProfit();
                });

                // Format numbers inside calculator
                $(document).on('input', '.calc-number-format', function() {
                    const clean = this.value.replace(/[^0-9]/g, '');
                    this.value = clean ? formatNumberID(parseInt(clean)) : '';
                    calculateMarketplaceProfit();
                });

                // Listen to inputs for calculation
                const inputIds = [
                    'calc-hpp', 'calc-opex', 'calc-price', 'calc-roas-target', 'calc-affiliate-pct',
                    'calc-voucher-toko', 'calc-voucher-produk', 'calc-voucher-dll',
                    'calc-cofund-pct', 'calc-cofund-max-seller', 'calc-cofund-plat-pct',
                    'calc-cofund-seller-pct',
                    'calc-admin-pct', 'calc-mall-pct', 'calc-premi-pct', 'calc-pesanan-fee',
                    'calc-hemat-ongkir',
                    'calc-logistik-fee',
                    'calc-prog-ongkir-pct', 'calc-prog-promo-pct', 'calc-prog-promo-plus-pct',
                    'calc-prog-live-pct',
                    'calc-prog-video-pct', 'calc-prog-preorder-pct', 'calc-prog-spaylater3-pct',
                    'calc-prog-spaylater6-pct',
                    'calc-promo-pct', 'calc-coret-pct', 'calc-markup-pct'
                ];
                inputIds.forEach(id => {
                    $(`#${id}`).on('input', calculateMarketplaceProfit);
                });

                const changeIds = [
                    'calc-mall-active', 'calc-premi-active',
                    'calc-prog-ongkir', 'calc-prog-promo', 'calc-prog-promo-plus', 'calc-prog-live',
                    'calc-prog-video', 'calc-prog-preorder', 'calc-prog-spaylater3', 'calc-prog-spaylater6'
                ];
                changeIds.forEach(id => {
                    $(`#${id}`).on('change', calculateMarketplaceProfit);
                });

                // Apply price from calculator
                $('#btn-apply-calc-price').on('click', function() {
                    const calculatedPrice = cleanNumber($('#calc-price').val());
                    const priceInput = $('#price');
                    if (priceInput.length) {
                        priceInput.val(calculatedPrice > 0 ? formatNumberID(calculatedPrice) : '');
                        const modal = bootstrap.Modal.getInstance(modalEl[0]);
                        if (modal) modal.hide();
                    }
                });
            }
        });

        // ── Number formatting helper ───────────────────────────────────────────
        function formatNumberWithSeparator(input) {
            const value = input.value;
            const cursorPosition = input.selectionStart;

            // Count how many digits exist before the cursor position
            const beforeCursorDigitsOnly = value.substring(0, cursorPosition).replace(/[^0-9]/g, '').length;

            const cleanValue = value.replace(/[^0-9]/g, '');
            if (cleanValue === '') {
                input.value = '';
                return;
            }

            const formatted = new Intl.NumberFormat('id-ID').format(cleanValue);
            input.value = formatted;

            // Find corresponding cursor position in the formatted string
            let newCursorPosition = 0;
            let digitsFound = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/[0-9]/.test(formatted[i])) {
                    digitsFound++;
                }
                if (digitsFound === beforeCursorDigitsOnly) {
                    newCursorPosition = i + 1;
                    break;
                }
            }

            // Set cursor position back correctly
            input.setSelectionRange(newCursorPosition, newCursorPosition);
        }

        // ── Name character counter ──────────────────────────────────────────────
        function updateNameCounter(val) {
            const len = val.length;
            const min = 30;
            const el = $('#name-counter');
            if (!el.length) return;
            if (len === 0) {
                el.text('');
                return;
            }
            if (len < min) {
                el.attr('class', 'form-text text-danger')
                    .html('<i class="fas fa-exclamation-circle me-1"></i> ' + len + ' / ' + min +
                        ' karakter minimum — kurang ' + (min - len) + ' karakter lagi');
            } else {
                el.attr('class', 'form-text text-success')
                    .html('<i class="fas fa-check-circle me-1"></i> ' + len + ' karakter — memenuhi syarat');
            }
        }

        function switchImgMode(mode) {
            const uploadMode = $('#img-mode-upload');
            const urlMode = $('#img-mode-url');
            const tabUpload = $('#img-tab-upload');
            const tabUrl = $('#img-tab-url');

            if (mode === 'upload') {
                uploadMode.show();
                urlMode.hide();

                tabUpload.addClass('btn-primary').removeClass('btn-outline-secondary');
                tabUrl.addClass('btn-outline-secondary').removeClass('btn-primary');

                // Clear the URL field so it doesn't override the upload
                $('#image_url').val('');
            } else {
                uploadMode.hide();
                urlMode.show();

                tabUpload.addClass('btn-outline-secondary').removeClass('btn-primary');
                tabUrl.addClass('btn-primary').removeClass('btn-outline-secondary');

                // Clear file input so it doesn't override the URL
                $('#image_file').val('');

                // Trigger URL preview
                const urlVal = $('#image_url').val();
                if (urlVal) updateImgPreview(urlVal);
            }
        }

        function handleImgFile(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);

            const filenameEl = $('#img-filename');
            filenameEl.text('📎 ' + file.name + ' (' + sizeMB + ' MB)').show();

            const reader = new FileReader();
            reader.onload = function(e) {
                updateImgPreviewSrc(e.target.result);
            };
            reader.readAsDataURL(file);
        }

        function updateImgPreview(url) {
            if (!url || url.trim() === '') {
                $('#img-preview-wrap').hide();
                return;
            }
            updateImgPreviewSrc(url);
        }

        function updateImgPreviewSrc(src) {
            const preview = $('#img-preview');
            const wrap = $('#img-preview-wrap');
            preview.attr('src', src);
            wrap.show();
            preview.on('error', function() {
                wrap.hide();
            });
        }

        function clearImg() {
            // Clear inputs
            $('#image_file').val('');
            $('#image_url').val('');
            // Hide preview
            $('#img-preview-wrap').hide();
            // Reset filename label
            $('#img-filename').text('').hide();
        }

        function togglePreorderDays(isChecked) {
            const wrapper = $('#preorder_days_wrapper');
            if (isChecked) {
                wrapper.show();
            } else {
                wrapper.hide();
            }
        }

        // ── Calculator Helper Functions ──────────────────────────────────────────
        function cleanNumber(val) {
            if (!val) return 0;
            return parseFloat(val.toString().replace(/[^0-9]/g, '')) || 0;
        }

        function formatNumberID(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function formatCurrency(val) {
            if (val === 0) return 'Rp -';
            const formatted = formatNumberID(Math.abs(val));
            return val < 0 ? `-Rp ${formatted}` : `Rp ${formatted}`;
        }

        function calculateMarketplaceProfit() {
            const hpp = cleanNumber($('#calc-hpp').val());
            const price = cleanNumber($('#calc-price').val());
            const opex = cleanNumber($('#calc-opex').val());

            // Promosi Seller
            const voucherToko = cleanNumber($('#calc-voucher-toko').val());
            const voucherProduk = cleanNumber($('#calc-voucher-produk').val());
            const voucherDll = cleanNumber($('#calc-voucher-dll').val());
            const totalVoucher = voucherToko + voucherProduk + voucherDll;

            // Co-Fund Voucher
            const cofundPct = parseFloat($('#calc-cofund-pct').val()) || 0;
            const cofundMaxSeller = cleanNumber($('#calc-cofund-max-seller').val());
            const cofundPlatPct = parseFloat($('#calc-cofund-plat-pct').val()) || 0;
            const cofundSellerPct = parseFloat($('#calc-cofund-seller-pct').val()) || 0;

            const baseCofundDisc = price * (cofundPct / 100);
            const totalCofundDisc = cofundMaxSeller > 0 ? Math.min(baseCofundDisc, cofundMaxSeller) : baseCofundDisc;
            const cofundSellerCost = Math.round(totalCofundDisc * (cofundSellerPct / 100));
            const cofundPlatDisc = Math.round(totalCofundDisc * (cofundPlatPct / 100));

            // Biaya Administrasi
            const adminPct = parseFloat($('#calc-admin-pct').val()) || 0;

            let mallPct = 0;
            if ($('#calc-mall-active').is(':checked')) {
                mallPct = parseFloat($('#calc-mall-pct').val()) || 0;
            }

            let premiPct = 0;
            if ($('#calc-premi-active').is(':checked')) {
                premiPct = parseFloat($('#calc-premi-pct').val()) || 0;
            }

            const pesananFee = cleanNumber($('#calc-pesanan-fee').val());
            const hematOngkir = cleanNumber($('#calc-hemat-ongkir').val());
            const logistikFee = cleanNumber($('#calc-logistik-fee').val());

            const adminFeeBase = Math.round(price * (adminPct / 100));
            const mallFee = Math.round(price * (mallPct / 100));
            const premiFee = Math.round(price * (premiPct / 100));

            const totalAdminCost = adminFeeBase + mallFee + premiFee + pesananFee + hematOngkir + logistikFee;

            // Program Pemasaran
            let progOngkir = 0;
            if ($('#calc-prog-ongkir').is(':checked')) {
                progOngkir = Math.round(price * (parseFloat($('#calc-prog-ongkir-pct').val()) || 0) / 100);
            }

            let progPromo = 0;
            if ($('#calc-prog-promo').is(':checked')) {
                progPromo = Math.round(price * (parseFloat($('#calc-prog-promo-pct').val()) || 0) / 100);
            }

            let progPromoPlus = 0;
            if ($('#calc-prog-promo-plus').is(':checked')) {
                progPromoPlus = Math.round(price * (parseFloat($('#calc-prog-promo-plus-pct').val()) || 0) / 100);
            }

            let progLive = 0;
            if ($('#calc-prog-live').is(':checked')) {
                progLive = Math.round(price * (parseFloat($('#calc-prog-live-pct').val()) || 0) / 100);
            }

            let progVideo = 0;
            if ($('#calc-prog-video').is(':checked')) {
                progVideo = Math.round(price * (parseFloat($('#calc-prog-video-pct').val()) || 0) / 100);
            }

            let progPreorder = 0;
            if ($('#calc-prog-preorder').is(':checked')) {
                progPreorder = Math.round(price * (parseFloat($('#calc-prog-preorder-pct').val()) || 0) / 100);
            }

            let progSpaylater3 = 0;
            if ($('#calc-prog-spaylater3').is(':checked')) {
                progSpaylater3 = Math.round(price * (parseFloat($('#calc-prog-spaylater3-pct').val()) || 0) / 100);
            }

            let progSpaylater6 = 0;
            if ($('#calc-prog-spaylater6').is(':checked')) {
                progSpaylater6 = Math.round(price * (parseFloat($('#calc-prog-spaylater6-pct').val()) || 0) / 100);
            }

            const totalMarketingProgCost = progOngkir + progPromo + progPromoPlus + progLive + progVideo + progPreorder +
                progSpaylater3 + progSpaylater6;

            // Total Biaya (Admin + Pemasaran)
            const totalFees = totalAdminCost + totalMarketingProgCost;

            // Total Pendapatan Organik (Omset)
            const netRevenue = price - totalFees;

            // PPh
            const pph = Math.round(netRevenue * 0.005);

            // 1. Profit Organik
            const organicProfit = netRevenue - hpp - totalVoucher - pph - opex;
            const organicPct = price > 0 ? ((organicProfit / price) * 100).toFixed(2) : 0;

            // 2. Profit Affiliate
            const affiliatePct = parseFloat($('#calc-affiliate-pct').val()) || 0;
            const commission = Math.round(price * (affiliatePct / 100));
            const ppnAff = Math.round(commission * 0.005);
            const affiliateCost = commission + ppnAff;

            const affiliateProfit = organicProfit - affiliateCost;
            const affiliatePctResult = price > 0 ? ((affiliateProfit / price) * 100).toFixed(2) : 0;

            // 3. Profit Ads & Affiliate
            const roasTarget = parseFloat($('#calc-roas-target').val()) || 20;
            const adsPct = roasTarget > 0 ? (100 / roasTarget) : 0;
            const adsBase = Math.round(price * (adsPct / 100));
            const ppnAds = Math.round(adsBase * 0.12);
            const adsCost = adsBase + ppnAds;

            const adsProfit = affiliateProfit - adsCost;
            const adsPctResult = price > 0 ? ((adsProfit / price) * 100).toFixed(2) : 0;

            // Total Biaya Marketing (Ads + PPN + Affiliate)
            const totalMarketingCost = adsBase + ppnAds + commission + ppnAff;

            // Perhitungan Harga Jual section
            const buyerPromoPct = parseFloat($('#calc-promo-pct').val()) || 20;
            const buyerPrice = Math.round(price * (1 - buyerPromoPct / 100)) - totalVoucher - cofundPlatDisc;

            const coretPct = parseFloat($('#calc-coret-pct').val()) || 50;
            const coretPrice = coretPct < 100 ? Math.round(price / (1 - coretPct / 100)) : 0;

            const markupPct = parseFloat($('#calc-markup-pct').val()) || 10;
            const markupPrice = markupPct < 100 ? Math.round(price / (1 - markupPct / 100)) : 0;

            const bepAdsROAS = affiliateProfit > 0 ? (price / affiliateProfit).toFixed(2) : '0.00';

            // Target Pasang ROAS
            const roasAman = (price / affiliateProfit * 1.70);
            const adsPctAman = roasAman > 0 ? (1 / roasAman) * 100 : 0;
            const adsRpAman = Math.round(price * (adsPctAman / 100));
            const profitRpAman = affiliateProfit - adsRpAman;
            const profitPctAman = price > 0 ? ((profitRpAman / price) * 100).toFixed(2) : '0.00';

            const roasAkselerasi = roasAman * 0.70;
            const adsPctAkselerasi = roasAkselerasi > 0 ? (1 / roasAkselerasi) * 100 : 0;
            const adsRpAkselerasi = Math.round(price * (adsPctAkselerasi / 100));
            const profitRpAkselerasi = affiliateProfit - adsRpAkselerasi;
            const profitPctAkselerasi = price > 0 ? ((profitRpAkselerasi / price) * 100).toFixed(2) : '0.00';

            // BEP ROAS
            const bepRoasAman = profitRpAman > 0 ? (price / profitRpAman).toFixed(2) : '0.00';
            const bepRoasAkselerasi = profitRpAkselerasi > 0 ? (price / profitRpAkselerasi).toFixed(2) : '0.00';

            // UI Rendering
            // Perhitungan Harga Jual
            $('#res-price-buyer-rp').text(formatCurrency(buyerPrice));
            $('#res-coret-price-rp').text(formatCurrency(coretPrice));
            $('#res-markup-price-rp').text(formatCurrency(markupPrice));
            $('#res-bep-ads-val').text(bepAdsROAS);

            // Rincian Biaya
            $('#res-net-revenue').text(formatCurrency(netRevenue));
            $('#res-total-fees').text(formatCurrency(totalFees));
            $('#res-pph-val').text(formatCurrency(pph));
            $('#res-opex-val').text(formatCurrency(opex));

            $('#res-admin-fees-total').text(formatCurrency(totalAdminCost));
            $('#res-admin-fee-base').text(formatCurrency(adminFeeBase));
            $('#res-mall-fee').text(formatCurrency(mallFee));
            $('#res-premi-fee').text(formatCurrency(premiFee));
            $('#res-pesanan-fee-val').text(formatCurrency(pesananFee));
            $('#res-hemat-ongkir-val').text(formatCurrency(hematOngkir));
            $('#res-logistik-fee-val').text(formatCurrency(logistikFee));

            $('#res-marketing-fees-total').text(formatCurrency(totalMarketingProgCost));
            $('#res-prog-ongkir-val').text(formatCurrency(progOngkir));
            $('#res-prog-promo-val').text(formatCurrency(progPromo));
            $('#res-prog-promo-plus-val').text(formatCurrency(progPromoPlus));
            $('#res-prog-live-val').text(formatCurrency(progLive));
            $('#res-prog-video-val').text(formatCurrency(progVideo));
            $('#res-prog-preorder-val').text(formatCurrency(progPreorder));
            $('#res-prog-spaylater3-val').text(formatCurrency(progSpaylater3));
            $('#res-prog-spaylater6-val').text(formatCurrency(progSpaylater6));

            $('#res-ads-marketing-total').text(formatCurrency(totalMarketingCost));
            $('#res-ads-base').text(formatCurrency(adsBase));
            $('#res-ppn-ads').text(formatCurrency(ppnAds));
            $('#res-affiliate-base').text(formatCurrency(commission));
            $('#res-ppn-aff').text(formatCurrency(ppnAff));

            $('#res-cofund-fees-total').text(formatCurrency(cofundSellerCost));
            $('#res-cofund-plat-val').text(formatCurrency(cofundPlatDisc));
            $('#res-cofund-seller-val').text(formatCurrency(cofundSellerCost));

            // Target Pasang ROAS Table
            $('#res-roas-aman').text(isFinite(roasAman) ? roasAman.toFixed(2) : '0.00');
            $('#res-ads-pct-aman').text(adsPctAman.toFixed(2) + '%');
            $('#res-ads-rp-aman').text(formatCurrency(adsRpAman));
            $('#res-profit-pct-aman').text(profitPctAman + '%');
            $('#res-profit-rp-aman').text(formatCurrency(profitRpAman));

            $('#res-roas-akselerasi').text(isFinite(roasAkselerasi) ? roasAkselerasi.toFixed(2) : '0.00');
            $('#res-ads-pct-akselerasi').text(adsPctAkselerasi.toFixed(2) + '%');
            $('#res-ads-rp-akselerasi').text(formatCurrency(adsRpAkselerasi));
            $('#res-profit-pct-akselerasi').text(profitPctAkselerasi + '%');
            $('#res-profit-rp-akselerasi').text(formatCurrency(profitRpAkselerasi));

            // BEP ROAS Table
            $('#res-bep-roas-aman').text(bepRoasAman);
            $('#res-bep-roas-akselerasi').text(bepRoasAkselerasi);

            function updateProfitBox(boxId, valId, pctId, profit, pct) {
                const box = $(`#${boxId}`);
                const valEl = $(`#${valId}`);
                const pctEl = $(`#${pctId}`);
                if (!box.length || !valEl.length || !pctEl.length) return;

                valEl.text(formatCurrency(profit));
                pctEl.text(pct + '%');

                if (profit >= 0) {
                    valEl.attr('class', 'fw-bold text-success font-monospace');
                    pctEl.attr('class', 'badge bg-success-subtle text-success small');
                    box.css({
                        'background': 'rgba(16, 185, 129, 0.05)',
                        'border-color': 'rgba(16, 185, 129, 0.2)'
                    });
                } else {
                    valEl.attr('class', 'fw-bold text-danger font-monospace');
                    pctEl.attr('class', 'badge bg-danger-subtle text-danger small');
                    box.css({
                        'background': 'rgba(239, 68, 68, 0.05)',
                        'border-color': 'rgba(239, 68, 68, 0.2)'
                    });
                }
            }

            updateProfitBox('box-organic', 'val-organic-profit', 'val-organic-pct', organicProfit, organicPct);
            updateProfitBox('box-affiliate', 'val-affiliate-profit', 'val-affiliate-pct', affiliateProfit,
                affiliatePctResult);
            updateProfitBox('box-ads', 'val-ads-profit', 'val-ads-pct', adsProfit, adsPctResult);
        }

        // JS Code for BOM & Labor Rows
        @if(isset($product->id))
                let bomRowIndex = {{ $recipe && $recipe->items->count() > 0 ? $recipe->items->count() : 0 }};
                let laborRowIndex = {{ $recipe && $recipe->labors->count() > 0 ? $recipe->labors->count() : 0 }};

                $('#btn-add-bom-row').on('click', function() {
                    let optionsHtml = '<option value=""></option>';
                    @foreach($inventoryItems as $item)
                        optionsHtml += `<option value="{{ $item->id }}" data-unit="{{ $item->unit }}">{{ $item->name }} ({{ $item->sku }})</option>`;
                    @endforeach

                    const rowHtml = `
                        <tr class="bom-row">
                            <td>
                                <select name="items[\${bomRowIndex}][inventory_item_id]" class="form-select select-bom-item" required>
                                    \${optionsHtml}
                                </select>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.0001" name="items[\${bomRowIndex}][quantity]" class="form-control" min="0.0001" value="1" required>
                                    <span class="input-group-text span-bom-unit" style="font-size:10px">—</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger btn-remove-bom-row"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;

                    $('#table-bom tbody').append(rowHtml);
                    initSelect2ForBom($('#table-bom tbody tr:last-child'));
                    bomRowIndex++;
                });

                function initSelect2ForBom(row) {
                    row.find('.select-bom-item').select2({
                        theme: 'bootstrap-5',
                        placeholder: '— Pilih Bahan —',
                        allowClear: true,
                        dropdownParent: $('#table-bom')
                    });
                }

                $(document).on('change', '.select-bom-item', function() {
                    const row = $(this).closest('.bom-row');
                    const selected = $(this).find('option:selected');
                    if (selected.val()) {
                        row.find('.span-bom-unit').text(selected.data('unit'));
                    } else {
                        row.find('.span-bom-unit').text('—');
                    }
                });

                $(document).on('click', '.btn-remove-bom-row', function() {
                    $(this).closest('.bom-row').remove();
                });

                $('#btn-add-labor-row').on('click', function() {
                    const rowHtml = `
                        <tr class="labor-row">
                            <td>
                                <input type="text" name="labors[\${laborRowIndex}][service_name]" class="form-control form-control-sm" placeholder="Misal: QC, Operator" required>
                            </td>
                            <td>
                                <input type="number" name="labors[\${laborRowIndex}][default_cost]" class="form-control form-control-sm" min="0" value="0" required>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger btn-remove-labor-row"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;
                    $('#table-labor tbody').append(rowHtml);
                    laborRowIndex++;
                });

                $(document).on('click', '.btn-remove-labor-row', function() {
                    $(this).closest('.labor-row').remove();
                });

                // Initialize existing select2 for BOM rows
                $('.select-bom-item').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Bahan —',
                    allowClear: true,
                    dropdownParent: $('#table-bom')
                });
            @endif
    </script>
@endpush
