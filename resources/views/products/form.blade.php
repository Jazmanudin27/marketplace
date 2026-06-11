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
                            <label class="form-label fw-semibold">SKU <small class="text-muted fw-normal">(Kosongkan untuk
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
                            <label for="unit" class="form-label fw-semibold">Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-cube"></i></span>
                                <input type="text" id="unit" name="unit" class="form-control"
                                    value="{{ old('unit', $product->unit ?? '') }}" placeholder="pcs, kg, box...">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nama Produk <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                            <input type="text" id="name" name="name" class="form-control"
                                value="{{ old('name', $product->name ?? '') }}" required minlength="30"
                                placeholder="Nama produk lengkap (min. 30 karakter)"
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
                                    value="{{ old('price', $product->price ?? '') }}" required placeholder="0">
                            </div>
                            @error('price')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cost_price" class="form-label fw-semibold">Harga Modal (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-wallet"></i></span>
                                <input type="text" id="cost_price" name="cost_price"
                                    class="form-control formatted-number-input"
                                    value="{{ old('cost_price', $product->cost_price ?? '') }}" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label fw-semibold">Stok Fisik <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                                <input type="number" id="stock" name="stock" class="form-control {{ $isLinked ? 'bg-light text-muted' : '' }}"
                                    value="{{ old('stock', $product->stock ?? 0) }}" min="0" required
                                    placeholder="0"
                                    {{ $isLinked ? 'readonly' : '' }}>
                            </div>
                            @if ($isLinked)
                                <div class="form-text text-warning small mt-1">
                                    <i class="fas fa-lock me-1"></i> Stok dikunci karena produk sudah terhubung ke marketplace. Gunakan menu <strong>Stock Opname</strong> atau <strong>Barang Masuk</strong> untuk mengubah stok.
                                </div>
                            @endif
                            @error('stock')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="min_stock" class="form-label fw-semibold">Stok Minimum</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                <input type="number" id="min_stock" name="min_stock" class="form-control"
                                    value="{{ old('min_stock', $product->min_stock ?? 0) }}" min="0"
                                    placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label fw-semibold">Kategori</label>
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
                        </div>
                        <div class="col-md-6 mb-3">
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
@endsection
