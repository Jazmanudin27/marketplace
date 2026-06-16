@extends('layouts.app')
@section('title', isset($product->id) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', isset($product->id) ? 'Edit Master Produk' : 'Tambah Master Produk')

@section('content')
    @php
        $isLinked = isset($product) && $product->marketplaceProducts()->exists();
    @endphp
    <div class="container-fluid px-0">
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary mb-4">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0 fs-5 fw-bold">
                    @if (isset($product->id))
                        <i class="fas fa-edit me-2"></i> Edit Produk — <span
                            class="text-primary font-monospace">{{ $product->sku }}</span>
                    @else
                        <i class="fas fa-plus-circle me-2"></i> Tambah Produk Baru
                    @endif
                </h4>
            </div>

            <div class="card-body p-4">
                <form id="product-form"
                    action="{{ isset($product->id) ? route('products.update', $product) : route('products.store') }}"
                    method="POST" enctype="multipart/form-data">
                    @csrf
                    @if (isset($product->id))
                        @method('PUT')
                    @endif
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">SKU VARIASI <small class="text-muted fw-normal">(Kosongkan
                                    untuk
                                    generate otomatis)</small></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                <input type="text" name="sku" class="form-control"
                                    value="{{ old('sku', $product->sku ?? '') }}"
                                    {{ isset($product->id) ? 'disabled' : '' }}
                                    placeholder="Contoh: SKU-001 (atau biarkan kosong)">
                            </div>
                            @error('sku')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sku_induk" class="form-label fw-semibold">SKU INDUK</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                <input type="text" id="sku_induk" name="sku_induk" class="form-control"
                                    value="{{ old('sku_induk', $product->sku_induk ?? '') }}"
                                    placeholder="Contoh: SKU-PARENT-001">
                            </div>
                            @error('sku_induk')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">NAMA BARANG <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                            <input type="text" id="name" name="name" class="form-control"
                                value="{{ old('name', $product->name ?? '') }}" required minlength="30"
                                placeholder="Nama barang lengkap (min. 30 karakter)"
                                oninput="updateNameCounter(this.value)">
                        </div>
                        <div id="name-counter" class="form-text font-weight-semibold"></div>
                        @error('name')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label fw-semibold">Harga Jual (Rp) <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                <input type="text" id="price" name="price"
                                    class="form-control formatted-number-input"
                                    value="{{ old('price', isset($product->price) ? (int) $product->price : '') }}"
                                    required placeholder="0">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#priceCalculatorModal">
                                    <i class="fas fa-calculator me-1"></i> Hitung Profit
                                </button>
                            </div>
                            @error('price')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cost_price" class="form-label fw-semibold">HPP PRODUK / Harga Modal (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-wallet"></i></span>
                                <input type="text" id="cost_price" name="cost_price"
                                    class="form-control formatted-number-input"
                                    value="{{ old('cost_price', isset($product->cost_price) ? (int) $product->cost_price : '') }}"
                                    placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="stock" class="form-label fw-semibold">Stok Fisik <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                                <input type="number" id="stock" name="stock"
                                    class="form-control {{ $isLinked ? 'bg-light text-muted' : '' }}"
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
                            <label for="min_stock" class="form-label fw-semibold">Stok Minimum</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                <input type="number" id="min_stock" name="min_stock" class="form-control"
                                    value="{{ old('min_stock', $product->min_stock ?? 0) }}" min="0"
                                    placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="is_active" class="form-label fw-semibold">STATUS</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                <select id="is_active" name="is_active" class="form-select">
                                    <option value="1"
                                        {{ old('is_active', $product->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif
                                    </option>
                                    <option value="0"
                                        {{ old('is_active', $product->is_active ?? 1) == 0 ? 'selected' : '' }}>Nonaktif
                                    </option>
                                </select>
                            </div>
                            @error('is_active')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label fw-semibold">KATEGORI</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                <select id="category_id" name="category_id" class="form-select">
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
                            <label for="sub_kategori" class="form-label fw-semibold">SUB KATEGORI</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                                <input type="text" id="sub_kategori" name="sub_kategori" class="form-control"
                                    value="{{ old('sub_kategori', $product->sub_kategori ?? '') }}"
                                    placeholder="Contoh: Kaos Polos">
                            </div>
                            @error('sub_kategori')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="brand_id" class="form-label fw-semibold">Merk / Brand</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-certificate"></i></span>
                                <select id="brand_id" name="brand_id" class="form-select">
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
                            <label for="ukuran" class="form-label fw-semibold">UKURAN</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                <input type="text" id="ukuran" name="ukuran" class="form-control"
                                    value="{{ old('ukuran', $product->ukuran ?? '') }}"
                                    placeholder="Contoh: L, XL, 39, 40">
                            </div>
                            @error('ukuran')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="warna" class="form-label fw-semibold">WARNA</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-palette"></i></span>
                                <input type="text" id="warna" name="warna" class="form-control"
                                    value="{{ old('warna', $product->warna ?? '') }}"
                                    placeholder="Contoh: Hitam, Putih, Navy">
                            </div>
                            @error('warna')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="unit" class="form-label fw-semibold">Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-cube"></i></span>
                                <input type="text" id="unit" name="unit" class="form-control"
                                    value="{{ old('unit', $product->unit ?? '') }}" placeholder="pcs, kg, box...">
                            </div>
                            @error('unit')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="weight" class="form-label fw-semibold">Berat (kg) <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-weight"></i></span>
                                <input type="number" step="0.001" id="weight" name="weight" class="form-control"
                                    value="{{ old('weight', $product->weight ?? 0.1) }}" required placeholder="0.100">
                            </div>
                            @error('weight')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Gambar Produk</label>

                            {{-- Mode Toggle --}}
                            <div class="btn-group w-100 mb-2" role="group">
                                <button type="button" id="img-tab-upload" onclick="switchImgMode('upload')"
                                    class="btn btn-primary btn-sm">
                                    <i class="fas fa-upload me-1"></i> Upload File
                                </button>
                                <button type="button" id="img-tab-url" onclick="switchImgMode('url')"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-link me-1"></i> Pakai URL
                                </button>
                            </div>

                            {{-- Upload Mode --}}
                            <div id="img-mode-upload" class="mb-2">
                                <div id="img-drop-zone" onclick="document.getElementById('image_file').click()"
                                    ondragover="event.preventDefault(); this.className='border border-2 border-primary rounded p-4 text-center cursor-pointer bg-dark';"
                                    ondragleave="this.className='border border-2 border-secondary rounded p-4 text-center cursor-pointer bg-transparent';"
                                    ondrop="handleImgDrop(event)"
                                    class="border border-2 border-secondary rounded p-4 text-center cursor-pointer"
                                    style="cursor: pointer; border-style: dashed !important;">
                                    <i class="fas fa-cloud-upload-alt fs-2 text-muted mb-2 d-block"></i>
                                    <div class="fw-semibold text-secondary small">Klik atau seret gambar ke sini</div>
                                    <div class="text-muted extra-small" style="font-size: 0.75rem;">JPG, PNG, WEBP — maks
                                        5 MB</div>
                                    <div id="img-filename" class="mt-2 text-primary small" style="display: none;"></div>
                                </div>
                                <input type="file" id="image_file" name="image_file"
                                    accept="image/jpeg,image/png,image/webp" class="d-none"
                                    onchange="handleImgFile(this)">
                                @error('image_file')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- URL Mode --}}
                            <div id="img-mode-url" class="mb-2" style="display: none;">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                    <input type="text" id="image_url" name="image_url" class="form-control"
                                        value="{{ old('image_url', $product->image_url ?? '') }}"
                                        placeholder="https://example.com/gambar.jpg"
                                        oninput="updateImgPreview(this.value)">
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
                                    <img id="img-preview" src="{{ $product->image_url ?? '' }}" class="img-thumbnail"
                                        style="max-width: 180px; max-height: 180px; object-fit: cover; display: block;">
                                    <button type="button" onclick="clearImg()"
                                        class="btn btn-danger btn-sm rounded-circle position-absolute"
                                        style="top: -8px; right: -8px; width: 22px; height: 22px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="length" class="form-label fw-semibold">Panjang (cm)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                                <input type="number" step="0.1" id="length" name="length" class="form-control"
                                    value="{{ old('length', $product->length ?? '') }}" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="width" class="form-label fw-semibold">Lebar (cm)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                <input type="number" step="0.1" id="width" name="width" class="form-control"
                                    value="{{ old('width', $product->width ?? '') }}" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="height" class="form-label fw-semibold">Tinggi (cm)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                <input type="number" step="0.1" id="height" name="height" class="form-control"
                                    value="{{ old('height', $product->height ?? '') }}" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Deskripsi Produk</label>
                        <div class="input-group">
                            <span class="input-group-text align-items-start pt-2"><i class="fas fa-align-left"></i></span>
                            <textarea id="description" name="description" class="form-control" rows="4"
                                placeholder="Deskripsi lengkap produk...">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>
                        @error('description')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>
                            {{ isset($product->id) ? 'Simpan Perubahan' : 'Simpan Produk' }}
                        </button>
                    </div>
                </form>

                @if (isset($product->id))
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <form action="{{ route('products.destroy', $product) }}" method="POST"
                            onsubmit="return confirm('Hapus produk ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash me-1"></i> Hapus Produk Ini
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // If product already has a URL (edit mode), default to URL tab
        document.addEventListener('DOMContentLoaded', function() {
            const existingUrl = '{{ $product->image_url ?? '' }}';
            if (existingUrl && (existingUrl.startsWith('http://') || existingUrl.startsWith('https://'))) {
                switchImgMode('url');
            }
            // If locally stored image, stay on upload tab but show preview
            if (existingUrl && existingUrl.startsWith('/storage/')) {
                updateImgPreviewSrc(existingUrl);
            }

            // Initialize name counter on page load (needed for edit mode)
            const nameInput = document.getElementById('name');
            if (nameInput) updateNameCounter(nameInput.value);

            // Initialize & Format number inputs
            document.querySelectorAll('.formatted-number-input').forEach(function(input) {
                if (input.value && input.value.includes('.')) {
                    const parsed = parseFloat(input.value);
                    if (!isNaN(parsed)) {
                        input.value = Math.round(parsed).toString();
                    }
                }
                formatNumberWithSeparator(input);

                // Format on typing
                input.addEventListener('input', function() {
                    formatNumberWithSeparator(this);
                });

                // Restrict input to digits only
                input.addEventListener('keydown', function(e) {
                    if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                        (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                        (e.keyCode >= 35 && e.keyCode <= 40)) {
                        return;
                    }
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e
                            .keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            });

            // Strip dots before form submit so the server receives raw numeric values
            const form = document.getElementById('product-form');
            if (form) {
                form.addEventListener('submit', function() {
                    document.querySelectorAll('.formatted-number-input').forEach(function(input) {
                        input.value = input.value.replace(/\./g, '');
                    });
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
            const el = document.getElementById('name-counter');
            if (!el) return;
            if (len === 0) {
                el.textContent = '';
                return;
            }
            if (len < min) {
                el.className = 'form-text text-danger';
                el.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> ' + len + ' / ' + min +
                    ' karakter minimum — kurang ' + (min - len) + ' karakter lagi';
            } else {
                el.className = 'form-text text-success';
                el.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + len + ' karakter — memenuhi syarat';
            }
        }

        function switchImgMode(mode) {
            const uploadMode = document.getElementById('img-mode-upload');
            const urlMode = document.getElementById('img-mode-url');
            const tabUpload = document.getElementById('img-tab-upload');
            const tabUrl = document.getElementById('img-tab-url');

            if (mode === 'upload') {
                uploadMode.style.display = '';
                urlMode.style.display = 'none';

                tabUpload.className = 'btn btn-primary btn-sm';
                tabUrl.className = 'btn btn-outline-secondary btn-sm';

                // Clear the URL field so it doesn't override the upload
                const urlInput = document.getElementById('image_url');
                if (urlInput) urlInput.value = '';
            } else {
                uploadMode.style.display = 'none';
                urlMode.style.display = '';

                tabUpload.className = 'btn btn-outline-secondary btn-sm';
                tabUrl.className = 'btn btn-primary btn-sm';

                // Clear file input so it doesn't override the URL
                const fileInput = document.getElementById('image_file');
                if (fileInput) fileInput.value = '';

                // Trigger URL preview
                const urlInput = document.getElementById('image_url');
                if (urlInput && urlInput.value) updateImgPreview(urlInput.value);
            }
        }

        function handleImgFile(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);

            const filenameEl = document.getElementById('img-filename');
            filenameEl.textContent = '📎 ' + file.name + ' (' + sizeMB + ' MB)';
            filenameEl.style.display = 'block';

            const reader = new FileReader();
            reader.onload = function(e) {
                updateImgPreviewSrc(e.target.result);
            };
            reader.readAsDataURL(file);
        }

        function handleImgDrop(event) {
            event.preventDefault();
            const zone = document.getElementById('img-drop-zone');
            zone.className = 'border border-2 border-secondary rounded p-4 text-center cursor-pointer bg-transparent';

            const file = event.dataTransfer.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            const dt = new DataTransfer();
            dt.items.add(file);
            const input = document.getElementById('image_file');
            input.files = dt.files;
            handleImgFile(input);
        }

        function updateImgPreview(url) {
            if (!url || url.trim() === '') {
                document.getElementById('img-preview-wrap').style.display = 'none';
                return;
            }
            updateImgPreviewSrc(url);
        }

        // Wrap preview rendering helper
        function updateImgPreviewSrc(src) {
            const preview = document.getElementById('img-preview');
            const wrap = document.getElementById('img-preview-wrap');
            preview.src = src;
            wrap.style.display = 'block';
            preview.onerror = function() {
                wrap.style.display = 'none';
            };
        }

        function clearImg() {
            // Clear file input
            const fileInput = document.getElementById('image_file');
            if (fileInput) fileInput.value = '';
            // Clear URL input
            const urlInput = document.getElementById('image_url');
            if (urlInput) urlInput.value = '';
            // Hide preview
            document.getElementById('img-preview-wrap').style.display = 'none';
            // Reset filename label
            const filenameEl = document.getElementById('img-filename');
            if (filenameEl) {
                filenameEl.textContent = '';
                filenameEl.style.display = 'none';
            }
        }
    </script>

    <!-- Modal Kalkulator Harga Shopee -->
    <div class="modal fade" id="priceCalculatorModal" tabindex="-1" aria-labelledby="priceCalculatorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content"
                style="background: var(--bg-card); border: 1px solid var(--border); color: var(--text-primary);">
                <div class="modal-header"
                    style="border-bottom: 1px solid var(--border); background: rgba(238, 77, 45, 0.05);">
                    <h5 class="modal-title" id="priceCalculatorModalLabel" style="color: #ee4d2d;">
                        <i class="fas fa-calculator me-2"></i> KALKULATOR HARGA & PROFIT SHOPEE (DETAIL)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Input Column -->
                        <div class="col-md-5 border-end d-flex flex-column"
                            style="border-color: var(--border) !important;">
                            <div class="overflow-auto pe-2" style="max-height: 70vh;">

                                <!-- Pengaturan Informasi -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-info-circle me-1 text-primary"></i> Pengaturan Informasi
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label extra-small text-secondary mb-1">Nama SKU INDUK</label>
                                        <input type="text" id="calc-sku"
                                            class="form-control form-control-sm form-control-dark" readonly
                                            style="background: rgba(255,255,255,0.01);">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">COGS / HPP
                                                (Rp)</label>
                                            <input type="text" id="calc-hpp"
                                                class="form-control form-control-sm form-control-dark calc-number-format"
                                                placeholder="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Biaya Operasional /
                                                OPEX (Rp)</label>
                                            <input type="text" id="calc-opex"
                                                class="form-control form-control-sm form-control-dark calc-number-format"
                                                placeholder="0" value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Persentase Biaya Marketing -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-bullhorn me-1 text-primary"></i> Persentase Biaya Marketing
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Target ROAS (GMV
                                                Max)</label>
                                            <input type="number" step="0.1" id="calc-roas-target"
                                                class="form-control form-control-sm form-control-dark" value="20">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Komisi Affiliate
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-affiliate-pct"
                                                class="form-control form-control-sm form-control-dark" value="10">
                                        </div>
                                    </div>
                                </div>

                                <!-- Promosi Seller -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-tags me-1 text-primary"></i> Promosi Seller
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Voucher Toko
                                                (Rp)</label>
                                            <input type="text" id="calc-voucher-toko"
                                                class="form-control form-control-sm form-control-dark calc-number-format"
                                                placeholder="0" value="10.000">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Voucher Produk
                                                (Rp)</label>
                                            <input type="text" id="calc-voucher-produk"
                                                class="form-control form-control-sm form-control-dark calc-number-format"
                                                placeholder="0" value="5.000">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label extra-small text-secondary mb-1">Potongan Lain / DLL
                                            (Rp)</label>
                                        <input type="text" id="calc-voucher-dll"
                                            class="form-control form-control-sm form-control-dark calc-number-format"
                                            placeholder="0" value="0">
                                    </div>
                                </div>

                                <!-- Co-Fund Voucher -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-handshake me-1 text-primary"></i> Co-Fund Voucher (Voucher
                                        Bersama)
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Diskon Voucher
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-cofund-pct"
                                                class="form-control form-control-sm form-control-dark" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Batas Maks. Seller
                                                (Rp)</label>
                                            <input type="text" id="calc-cofund-max-seller"
                                                class="form-control form-control-sm form-control-dark calc-number-format"
                                                value="0">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Ditanggung Platform
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-cofund-plat-pct"
                                                class="form-control form-control-sm form-control-dark" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label extra-small text-secondary mb-1">Ditanggung Penjual
                                                (%)</label>
                                            <input type="number" step="0.1" id="calc-cofund-seller-pct"
                                                class="form-control form-control-sm form-control-dark" value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Persentase Biaya Marketplace -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
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
                                                    class="form-control form-control-dark text-end" value="8.25">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="0.00">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="0.50">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                class="form-control form-control-sm form-control-dark text-end calc-number-format"
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
                                                class="form-control form-control-sm form-control-dark text-end calc-number-format"
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
                                                class="form-control form-control-sm form-control-dark text-end calc-number-format"
                                                value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Persentase Program Pemasaran -->
                                <div class="card bg-transparent border-0 mb-2">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
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
                                                    class="form-control form-control-dark text-end" value="7.50">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="4.50">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="2.00">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="2.00">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="0.00">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="3.00">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="2.50">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                                    class="form-control form-control-dark text-end" value="0.00">
                                                <span class="input-group-text form-control-dark">%</span>
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
                                <div class="p-3 rounded mb-3 border border-primary"
                                    style="background: rgba(238, 77, 45, 0.03);">
                                    <label class="form-label small fw-bold text-white mb-2"><i
                                            class="fas fa-coins me-1 text-warning"></i> HARGA JUAL UJI (IDR)</label>
                                    <div class="input-group">
                                        <span
                                            class="input-group-text bg-dark border-primary text-white font-monospace fw-bold">Rp</span>
                                        <input type="text" id="calc-price"
                                            class="form-control form-control-lg bg-dark border-primary text-white text-end font-monospace fw-bold calc-number-format"
                                            placeholder="0" style="font-size: 1.35rem;" value="385.900">
                                    </div>
                                </div>

                                <!-- Perhitungan Harga Jual -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-calculator me-1 text-success"></i> Perhitungan Harga Jual
                                    </div>
                                    <table class="table table-sm table-borderless text-white mb-0 extra-small">
                                        <tbody>
                                            <tr>
                                                <td class="text-secondary ps-0">Tampil Harga di Pembeli</td>
                                                <td>
                                                    <div class="input-group input-group-sm d-inline-flex"
                                                        style="width: 80px;">
                                                        <input type="number" id="calc-promo-pct"
                                                            class="form-control form-control-dark p-1 text-end"
                                                            value="20">
                                                        <span class="input-group-text form-control-dark p-1">%</span>
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
                                                            class="form-control form-control-dark p-1 text-end"
                                                            value="50">
                                                        <span class="input-group-text form-control-dark p-1">%</span>
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
                                                            class="form-control form-control-dark p-1 text-end"
                                                            value="10">
                                                        <span class="input-group-text form-control-dark p-1">%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold font-monospace text-info"
                                                    id="res-markup-price-rp">Rp 0</td>
                                            </tr>
                                            <tr class="border-top"
                                                style="border-color: rgba(255,255,255,0.05) !important;">
                                                <td class="text-secondary ps-0 pt-2">BEP ADS (Break Even Point ROAS)</td>
                                                <td></td>
                                                <td class="text-end fw-bold font-monospace text-danger pt-2"
                                                    id="res-bep-ads-val" style="font-size: 0.9rem;">0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Hasil Penghitungan (Profit Summary) -->
                                <div class="card bg-transparent border-0 mb-4">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-chart-pie me-1 text-success"></i> Ringkasan Profit Margin
                                    </div>

                                    <!-- Organic -->
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-2 border"
                                        id="box-organic"
                                        style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2) !important;">
                                        <div>
                                            <div class="fw-bold extra-small text-white">Profit Organik</div>
                                            <div class="text-muted extra-small" style="font-size: 0.7rem;">Murni non-iklan
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
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-2 border"
                                        id="box-affiliate"
                                        style="background: rgba(139, 92, 246, 0.05); border-color: rgba(139, 92, 246, 0.2) !important;">
                                        <div>
                                            <div class="fw-bold extra-small text-white">Profit Affiliate</div>
                                            <div class="text-muted extra-small" style="font-size: 0.7rem;">Potongan Komisi
                                                & PPN Affiliate</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-purple font-monospace" id="val-affiliate-profit"
                                                style="color: #a78bfa;">Rp 0</div>
                                            <span class="badge bg-purple-subtle text-purple extra-small"
                                                id="val-affiliate-pct">0%</span>
                                        </div>
                                    </div>

                                    <!-- Ads & Affiliate -->
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-3 border"
                                        id="box-ads"
                                        style="background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2) !important;">
                                        <div>
                                            <div class="fw-bold extra-small text-white">Profit Ads & Affiliate</div>
                                            <div class="text-muted extra-small" style="font-size: 0.7rem;">Potongan Iklan
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
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-crosshairs me-1 text-warning"></i> Target Pasang ROAS : GMV Max
                                        ROAS
                                    </div>
                                    <table
                                        class="table table-sm table-bordered border-secondary text-white mb-0 extra-small font-monospace">
                                        <thead>
                                            <tr class="bg-dark text-secondary text-center">
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
                                                <td class="text-white text-center">Aman / Akselerasi ROAS</td>
                                                <td class="text-center text-success fw-bold" id="res-roas-aman">0.00</td>
                                                <td class="text-center text-danger" id="res-ads-pct-aman">0.00%</td>
                                                <td class="text-end" id="res-ads-rp-aman">Rp 0</td>
                                                <td class="text-center text-success" id="res-profit-pct-aman">0.00%</td>
                                                <td class="text-end text-success fw-bold" id="res-profit-rp-aman">Rp 0
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-white text-center">Setelah Akselerasi (-30%)</td>
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
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-balance-scale me-1 text-danger"></i> BEP (Break Even Point)
                                        Kalkulator
                                    </div>
                                    <table
                                        class="table table-sm table-bordered border-secondary text-white mb-0 extra-small font-monospace">
                                        <thead>
                                            <tr class="bg-dark text-secondary text-center">
                                                <th>Ket.</th>
                                                <th>ROAS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-white text-center">Aman / Akselerasi ROAS</td>
                                                <td class="text-center text-danger fw-bold" id="res-bep-roas-aman">0.00
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-white text-center">Setelah Akselerasi (-30%)</td>
                                                <td class="text-center text-warning fw-bold" id="res-bep-roas-akselerasi">
                                                    0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Rincian Biaya (Detail) -->
                                <div class="card bg-transparent border-0 mb-3">
                                    <div class="text-uppercase fw-bold text-white small mb-2 pb-1 border-bottom"
                                        style="border-color: rgba(255,255,255,0.08) !important;">
                                        <i class="fas fa-list-ul me-1 text-secondary"></i> Rincian Biaya Lengkap
                                    </div>
                                    <div class="p-3 rounded border"
                                        style="background: rgba(255,255,255,0.01); border-color: var(--border);">
                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace"
                                            style="font-size: 0.85rem;">
                                            <span class="text-success fw-bold"><i
                                                    class="fas fa-arrow-circle-right me-1"></i> Total Pendapatan Organik
                                                (Omset)</span>
                                            <span class="text-success fw-bold" id="res-net-revenue">Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace border-bottom pb-2"
                                            style="font-size: 0.85rem; border-color: rgba(255,255,255,0.05) !important;">
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
                                                    class="d-flex justify-content-between fw-bold text-white border-bottom border-secondary mb-1">
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
                                                    class="d-flex justify-content-between fw-bold text-white border-bottom border-secondary mb-1">
                                                    <span>Total Biaya Pemasaran</span>
                                                    <span id="res-marketing-fees-total">Rp 0</span>
                                                </div>
                                                <div class="ps-2 text-muted extra-small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Gratis Ongkir Xtra</span>
                                                        <span id="res-prog-ongkir">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Promo Xtra</span>
                                                        <span id="res-prog-promo">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Promo Xtra+ (Plus)</span>
                                                        <span id="res-prog-promo-plus">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Live Xtra</span>
                                                        <span id="res-prog-live">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Video Xtra</span>
                                                        <span id="res-prog-video">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Pre-Order</span>
                                                        <span id="res-prog-preorder">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Spaylater 3 Bulan</span>
                                                        <span id="res-prog-spaylater3">Rp 0</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Spaylater 6 Bulan</span>
                                                        <span id="res-prog-spaylater6">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Total Biaya Marketing (Ads + Affiliate) -->
                                            <div class="mb-2">
                                                <div
                                                    class="d-flex justify-content-between fw-bold text-white border-bottom border-secondary mb-1">
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
                                                    class="d-flex justify-content-between fw-bold text-white border-bottom border-secondary mb-1">
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
                            <div class="mt-auto pt-3 border-top" style="border-color: var(--border) !important;">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('priceCalculatorModal');
            if (!modalEl) return;

            // Load values when modal is shown
            modalEl.addEventListener('show.bs.modal', function() {
                const costPriceInput = document.getElementById('cost_price');
                const priceInput = document.getElementById('price');
                const skuInput = document.getElementById('sku') || document.getElementsByName('sku')[0];

                const currentHpp = costPriceInput ? cleanNumber(costPriceInput.value) : 0;
                const currentPrice = priceInput ? cleanNumber(priceInput.value) : 0;

                // Get actual SKU or default name
                const skuVal = (skuInput && skuInput.value) || document.getElementById('name')?.value ||
                    'PRODUK';
                document.getElementById('calc-sku').value = skuVal;

                document.getElementById('calc-hpp').value = currentHpp > 0 ? formatNumberID(currentHpp) :
                    '';
                document.getElementById('calc-price').value = currentPrice > 0 ? formatNumberID(
                    currentPrice) : '';

                calculateMarketplaceProfit();
            });

            // Format numbers inside calculator
            document.querySelectorAll('.calc-number-format').forEach(function(input) {
                input.addEventListener('input', function() {
                    const clean = this.value.replace(/[^0-9]/g, '');
                    this.value = clean ? formatNumberID(parseInt(clean)) : '';
                    calculateMarketplaceProfit();
                });
            });

            // Listen to inputs for calculation
            const inputIds = [
                'calc-hpp', 'calc-opex', 'calc-price', 'calc-roas-target', 'calc-affiliate-pct',
                'calc-voucher-toko', 'calc-voucher-produk', 'calc-voucher-dll',
                'calc-cofund-pct', 'calc-cofund-max-seller', 'calc-cofund-plat-pct', 'calc-cofund-seller-pct',
                'calc-admin-pct', 'calc-mall-pct', 'calc-premi-pct', 'calc-pesanan-fee', 'calc-hemat-ongkir',
                'calc-logistik-fee',
                'calc-prog-ongkir-pct', 'calc-prog-promo-pct', 'calc-prog-promo-plus-pct', 'calc-prog-live-pct',
                'calc-prog-video-pct', 'calc-prog-preorder-pct', 'calc-prog-spaylater3-pct',
                'calc-prog-spaylater6-pct',
                'calc-promo-pct', 'calc-coret-pct', 'calc-markup-pct'
            ];
            inputIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', calculateMarketplaceProfit);
            });

            const changeIds = [
                'calc-mall-active', 'calc-premi-active',
                'calc-prog-ongkir', 'calc-prog-promo', 'calc-prog-promo-plus', 'calc-prog-live',
                'calc-prog-video', 'calc-prog-preorder', 'calc-prog-spaylater3', 'calc-prog-spaylater6'
            ];
            changeIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', calculateMarketplaceProfit);
            });

            // Apply price from calculator
            document.getElementById('btn-apply-calc-price').addEventListener('click', function() {
                const calculatedPrice = cleanNumber(document.getElementById('calc-price').value);
                const priceInput = document.getElementById('price');
                if (priceInput) {
                    priceInput.value = calculatedPrice > 0 ? formatNumberID(calculatedPrice) : '';
                    // Close bootstrap modal
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            });
        });

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
            const hpp = cleanNumber(document.getElementById('calc-hpp').value);
            const price = cleanNumber(document.getElementById('calc-price').value);
            const opex = cleanNumber(document.getElementById('calc-opex').value);

            // Promosi Seller
            const voucherToko = cleanNumber(document.getElementById('calc-voucher-toko').value);
            const voucherProduk = cleanNumber(document.getElementById('calc-voucher-produk').value);
            const voucherDll = cleanNumber(document.getElementById('calc-voucher-dll').value);
            const totalVoucher = voucherToko + voucherProduk + voucherDll;

            // Co-Fund Voucher
            const cofundPct = parseFloat(document.getElementById('calc-cofund-pct').value) || 0;
            const cofundMaxSeller = cleanNumber(document.getElementById('calc-cofund-max-seller').value);
            const cofundPlatPct = parseFloat(document.getElementById('calc-cofund-plat-pct').value) || 0;
            const cofundSellerPct = parseFloat(document.getElementById('calc-cofund-seller-pct').value) || 0;

            const baseCofundDisc = price * (cofundPct / 100);
            const totalCofundDisc = cofundMaxSeller > 0 ? Math.min(baseCofundDisc, cofundMaxSeller) : baseCofundDisc;
            const cofundSellerCost = Math.round(totalCofundDisc * (cofundSellerPct / 100));
            const cofundPlatDisc = Math.round(totalCofundDisc * (cofundPlatPct / 100));

            // Biaya Administrasi
            const adminPct = parseFloat(document.getElementById('calc-admin-pct').value) || 0;

            let mallPct = 0;
            if (document.getElementById('calc-mall-active').checked) {
                mallPct = parseFloat(document.getElementById('calc-mall-pct').value) || 0;
            }

            let premiPct = 0;
            if (document.getElementById('calc-premi-active').checked) {
                premiPct = parseFloat(document.getElementById('calc-premi-pct').value) || 0;
            }

            const pesananFee = cleanNumber(document.getElementById('calc-pesanan-fee').value);
            const hematOngkir = cleanNumber(document.getElementById('calc-hemat-ongkir').value);
            const logistikFee = cleanNumber(document.getElementById('calc-logistik-fee').value);

            const adminFeeBase = Math.round(price * (adminPct / 100));
            const mallFee = Math.round(price * (mallPct / 100));
            const premiFee = Math.round(price * (premiPct / 100));

            const totalAdminCost = adminFeeBase + mallFee + premiFee + pesananFee + hematOngkir + logistikFee;

            // Program Pemasaran
            let progOngkir = 0;
            if (document.getElementById('calc-prog-ongkir').checked) {
                progOngkir = Math.round(price * (parseFloat(document.getElementById('calc-prog-ongkir-pct').value) || 0) /
                    100);
            }

            let progPromo = 0;
            if (document.getElementById('calc-prog-promo').checked) {
                progPromo = Math.round(price * (parseFloat(document.getElementById('calc-prog-promo-pct').value) || 0) /
                    100);
            }

            let progPromoPlus = 0;
            if (document.getElementById('calc-prog-promo-plus').checked) {
                progPromoPlus = Math.round(price * (parseFloat(document.getElementById('calc-prog-promo-plus-pct').value) ||
                    0) / 100);
            }

            let progLive = 0;
            if (document.getElementById('calc-prog-live').checked) {
                progLive = Math.round(price * (parseFloat(document.getElementById('calc-prog-live-pct').value) || 0) / 100);
            }

            let progVideo = 0;
            if (document.getElementById('calc-prog-video').checked) {
                progVideo = Math.round(price * (parseFloat(document.getElementById('calc-prog-video-pct').value) || 0) /
                    100);
            }

            let progPreorder = 0;
            if (document.getElementById('calc-prog-preorder').checked) {
                progPreorder = Math.round(price * (parseFloat(document.getElementById('calc-prog-preorder-pct').value) ||
                    0) / 100);
            }

            let progSpaylater3 = 0;
            if (document.getElementById('calc-prog-spaylater3').checked) {
                progSpaylater3 = Math.round(price * (parseFloat(document.getElementById('calc-prog-spaylater3-pct')
                    .value) || 0) / 100);
            }

            let progSpaylater6 = 0;
            if (document.getElementById('calc-prog-spaylater6').checked) {
                progSpaylater6 = Math.round(price * (parseFloat(document.getElementById('calc-prog-spaylater6-pct')
                    .value) || 0) / 100);
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
            const affiliatePct = parseFloat(document.getElementById('calc-affiliate-pct').value) || 0;
            const commission = Math.round(price * (affiliatePct / 100));
            const ppnAff = Math.round(commission * 0.005);
            const affiliateCost = commission + ppnAff;

            const affiliateProfit = organicProfit - affiliateCost;
            const affiliatePctResult = price > 0 ? ((affiliateProfit / price) * 100).toFixed(2) : 0;

            // 3. Profit Ads & Affiliate
            const roasTarget = parseFloat(document.getElementById('calc-roas-target').value) || 20;
            const adsPct = roasTarget > 0 ? (100 / roasTarget) : 0;
            const adsBase = Math.round(price * (adsPct / 100));
            const ppnAds = Math.round(adsBase * 0.12);
            const adsCost = adsBase + ppnAds;

            const adsProfit = affiliateProfit - adsCost;
            const adsPctResult = price > 0 ? ((adsProfit / price) * 100).toFixed(2) : 0;

            // Total Biaya Marketing (Ads + PPN + Affiliate)
            const totalMarketingCost = adsBase + ppnAds + commission + ppnAff;

            // Perhitungan Harga Jual section
            const buyerPromoPct = parseFloat(document.getElementById('calc-promo-pct').value) || 20;
            const buyerPrice = Math.round(price * (1 - buyerPromoPct / 100)) - totalVoucher - cofundPlatDisc;

            const coretPct = parseFloat(document.getElementById('calc-coret-pct').value) || 50;
            const coretPrice = coretPct < 100 ? Math.round(price / (1 - coretPct / 100)) : 0;

            const markupPct = parseFloat(document.getElementById('calc-markup-pct').value) || 10;
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
            document.getElementById('res-price-buyer-rp').textContent = formatCurrency(buyerPrice);
            document.getElementById('res-coret-price-rp').textContent = formatCurrency(coretPrice);
            document.getElementById('res-markup-price-rp').textContent = formatCurrency(markupPrice);
            document.getElementById('res-bep-ads-val').textContent = bepAdsROAS;

            // Rincian Biaya
            document.getElementById('res-net-revenue').textContent = formatCurrency(netRevenue);
            document.getElementById('res-total-fees').textContent = formatCurrency(totalFees);
            document.getElementById('res-pph-val').textContent = formatCurrency(pph);
            document.getElementById('res-opex-val').textContent = formatCurrency(opex);

            document.getElementById('res-admin-fees-total').textContent = formatCurrency(totalAdminCost);
            document.getElementById('res-admin-fee-base').textContent = formatCurrency(adminFeeBase);
            document.getElementById('res-mall-fee').textContent = formatCurrency(mallFee);
            document.getElementById('res-premi-fee').textContent = formatCurrency(premiFee);
            document.getElementById('res-pesanan-fee-val').textContent = formatCurrency(pesananFee);
            document.getElementById('res-hemat-ongkir-val').textContent = formatCurrency(hematOngkir);
            document.getElementById('res-logistik-fee-val').textContent = formatCurrency(logistikFee);

            document.getElementById('res-marketing-fees-total').textContent = formatCurrency(totalMarketingProgCost);
            document.getElementById('res-prog-ongkir').textContent = formatCurrency(progOngkir);
            document.getElementById('res-prog-promo').textContent = formatCurrency(progPromo);
            document.getElementById('res-prog-promo-plus').textContent = formatCurrency(progPromoPlus);
            document.getElementById('res-prog-live').textContent = formatCurrency(progLive);
            document.getElementById('res-prog-video').textContent = formatCurrency(progVideo);
            document.getElementById('res-prog-preorder').textContent = formatCurrency(progPreorder);
            document.getElementById('res-prog-spaylater3').textContent = formatCurrency(progSpaylater3);
            document.getElementById('res-prog-spaylater6').textContent = formatCurrency(progSpaylater6);

            document.getElementById('res-ads-marketing-total').textContent = formatCurrency(totalMarketingCost);
            document.getElementById('res-ads-base').textContent = formatCurrency(adsBase);
            document.getElementById('res-ppn-ads').textContent = formatCurrency(ppnAds);
            document.getElementById('res-affiliate-base').textContent = formatCurrency(commission);
            document.getElementById('res-ppn-aff').textContent = formatCurrency(ppnAff);

            document.getElementById('res-cofund-fees-total').textContent = formatCurrency(cofundSellerCost);
            document.getElementById('res-cofund-plat-val').textContent = formatCurrency(cofundPlatDisc);
            document.getElementById('res-cofund-seller-val').textContent = formatCurrency(cofundSellerCost);

            // Target Pasang ROAS Table
            document.getElementById('res-roas-aman').textContent = isFinite(roasAman) ? roasAman.toFixed(2) : '0.00';
            document.getElementById('res-ads-pct-aman').textContent = adsPctAman.toFixed(2) + '%';
            document.getElementById('res-ads-rp-aman').textContent = formatCurrency(adsRpAman);
            document.getElementById('res-profit-pct-aman').textContent = profitPctAman + '%';
            document.getElementById('res-profit-rp-aman').textContent = formatCurrency(profitRpAman);

            document.getElementById('res-roas-akselerasi').textContent = isFinite(roasAkselerasi) ? roasAkselerasi.toFixed(
                2) : '0.00';
            document.getElementById('res-ads-pct-akselerasi').textContent = adsPctAkselerasi.toFixed(2) + '%';
            document.getElementById('res-ads-rp-akselerasi').textContent = formatCurrency(adsRpAkselerasi);
            document.getElementById('res-profit-pct-akselerasi').textContent = profitPctAkselerasi + '%';
            document.getElementById('res-profit-rp-akselerasi').textContent = formatCurrency(profitRpAkselerasi);

            // BEP ROAS Table
            document.getElementById('res-bep-roas-aman').textContent = bepRoasAman;
            document.getElementById('res-bep-roas-akselerasi').textContent = bepRoasAkselerasi;

            function updateProfitBox(boxId, valId, pctId, profit, pct) {
                const box = document.getElementById(boxId);
                const valEl = document.getElementById(valId);
                const pctEl = document.getElementById(pctId);
                if (!box || !valEl || !pctEl) return;

                valEl.textContent = formatCurrency(profit);
                pctEl.textContent = pct + '%';

                if (profit >= 0) {
                    valEl.className = 'fw-bold text-success font-monospace';
                    pctEl.className = 'badge bg-success-subtle text-success small';
                    box.style.background = 'rgba(16, 185, 129, 0.05)';
                    box.style.borderColor = 'rgba(16, 185, 129, 0.2)';
                } else {
                    valEl.className = 'fw-bold text-danger font-monospace';
                    pctEl.className = 'badge bg-danger-subtle text-danger small';
                    box.style.background = 'rgba(239, 68, 68, 0.05)';
                    box.style.borderColor = 'rgba(239, 68, 68, 0.2)';
                }
            }

            updateProfitBox('box-organic', 'val-organic-profit', 'val-organic-pct', organicProfit, organicPct);
            updateProfitBox('box-affiliate', 'val-affiliate-profit', 'val-affiliate-pct', affiliateProfit,
                affiliatePctResult);
            updateProfitBox('box-ads', 'val-ads-profit', 'val-ads-pct', adsProfit, adsPctResult);
        }
    </script>
@endsection
