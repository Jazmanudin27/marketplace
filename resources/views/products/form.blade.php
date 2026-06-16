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
                                    value="{{ old('price', isset($product->price) ? (int)$product->price : '') }}" required placeholder="0">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#priceCalculatorModal">
                                    <i class="fas fa-calculator me-1"></i> Hitung Profit
                                </button>
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
                                    value="{{ old('cost_price', isset($product->cost_price) ? (int)$product->cost_price : '') }}" placeholder="0">
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

    <!-- Modal Kalkulator Harga Marketplace -->
    <div class="modal fade" id="priceCalculatorModal" tabindex="-1" aria-labelledby="priceCalculatorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--border); color: var(--text-primary);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                    <h5 class="modal-title" id="priceCalculatorModalLabel">
                        <i class="fas fa-calculator text-primary me-2"></i> Kalkulator Harga & Profit Marketplace (Model Shopee)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Input Section -->
                        <div class="col-md-6 border-end" style="border-color: var(--border) !important;">
                            <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-cog me-1"></i> Input Simulasi</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Harga Modal / HPP (Rp)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text form-control-dark"><i class="fas fa-wallet"></i></span>
                                    <input type="text" id="calc-hpp" class="form-control form-control-sm form-control-dark calc-number-format" placeholder="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Harga Jual Diuji (Rp)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text form-control-dark"><i class="fas fa-tag"></i></span>
                                    <input type="text" id="calc-price" class="form-control form-control-sm form-control-dark calc-number-format" placeholder="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Biaya Voucher Ditanggung Seller (Rp)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text form-control-dark"><i class="fas fa-gift"></i></span>
                                    <input type="text" id="calc-voucher" class="form-control form-control-sm form-control-dark calc-number-format" placeholder="0" value="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Templet Marketplace (Default Fee)</label>
                                <select id="calc-template" class="form-select form-select-sm form-select-dark">
                                    <option value="custom">Kustom (Input Manual)</option>
                                    <option value="shopee">Shopee (Admin 8.25% + Premi 0.5%)</option>
                                    <option value="tiktok">TikTok Shop (Admin 5.0%)</option>
                                    <option value="tokopedia">Tokopedia (Admin 4.5%)</option>
                                    <option value="lazada">Lazada (Admin 4.0%)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Biaya Admin (%)</label>
                                        <input type="number" step="0.01" id="calc-admin-pct" class="form-control form-control-sm form-control-dark" value="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Premi (%)</label>
                                        <input type="number" step="0.01" id="calc-premi-pct" class="form-control form-control-sm form-control-dark" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-white mb-2">Program Pemasaran (Ikut Serta)</label>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="calc-prog-ongkir" value="7.5">
                                    <label class="form-check-label small text-secondary" for="calc-prog-ongkir">Gratis Ongkir Xtra (7.5%)</label>
                                </div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="calc-prog-promo" value="4.5">
                                    <label class="form-check-label small text-secondary" for="calc-prog-promo">Promo Xtra (4.5%)</label>
                                </div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="calc-prog-live" value="2.0">
                                    <label class="form-check-label small text-secondary" for="calc-prog-live">Live Xtra (2.0%)</label>
                                </div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="calc-prog-spaylater" value="2.5">
                                    <label class="form-check-label small text-secondary" for="calc-prog-spaylater">Spaylater Tenor 3 Bln (2.5%)</label>
                                </div>
                            </div>

                            <div class="mb-1">
                                <label class="form-label small fw-semibold text-white mb-2">Promosi Toko Tambahan</label>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="calc-affiliate-active">
                                    <label class="form-check-label small text-secondary" for="calc-affiliate-active">Komisi Affiliate (10%)</label>
                                </div>
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" id="calc-ads-active">
                                    <label class="form-check-label small text-secondary" for="calc-ads-active">Iklan GMV Max (5%)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Result Section -->
                        <div class="col-md-6 d-flex flex-column justify-content-between ps-md-4 mt-3 mt-md-0">
                            <div>
                                <h6 class="fw-bold mb-3 text-success"><i class="fas fa-chart-line me-1"></i> Hasil Perhitungan</h6>
                                
                                <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-secondary">Harga Jual Diuji:</span>
                                        <span class="fw-bold font-monospace" id="res-price">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-secondary">Total Potongan Marketplace:</span>
                                        <span class="text-danger font-monospace" id="res-fees">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-secondary">Pajak PPh Final (0.5%):</span>
                                        <span class="text-danger font-monospace" id="res-pph">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-secondary">Modal & Voucher Seller:</span>
                                        <span class="text-secondary font-monospace" id="res-hpp-voucher">Rp 0</span>
                                    </div>
                                </div>

                                <h6 class="small fw-semibold text-secondary mb-2">Estimasi Laba Bersih & Margin Skenario:</h6>
                                
                                <!-- Skenario 1: Organik -->
                                <div class="d-flex align-items-center justify-content-between p-3 rounded mb-2 border" id="box-organic" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2) !important;">
                                    <div>
                                        <div class="fw-bold small text-white">1. Penjualan Organik</div>
                                        <div class="text-muted extra-small" style="font-size: 0.72rem;">Penjualan langsung (non-iklan/affiliate)</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success font-monospace" id="val-organic-profit">Rp 0</div>
                                        <span class="badge bg-success-subtle text-success small" id="val-organic-pct">0%</span>
                                    </div>
                                </div>

                                <!-- Skenario 2: Affiliate -->
                                <div class="d-flex align-items-center justify-content-between p-3 rounded mb-2 border" id="box-affiliate" style="background: rgba(139, 92, 246, 0.05); border-color: rgba(139, 92, 246, 0.2) !important;">
                                    <div>
                                        <div class="fw-bold small text-white">2. Penjualan Affiliate</div>
                                        <div class="text-muted extra-small" style="font-size: 0.72rem;">Komisi Affiliate 10% + PPN 0.5%</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-purple font-monospace" id="val-affiliate-profit" style="color: var(--purple);">Rp 0</div>
                                        <span class="badge bg-purple-subtle text-purple small" id="val-affiliate-pct">0%</span>
                                    </div>
                                </div>

                                <!-- Skenario 3: Iklan + Affiliate -->
                                <div class="d-flex align-items-center justify-content-between p-3 rounded border" id="box-ads" style="background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2) !important;">
                                    <div>
                                        <div class="fw-bold small text-white">3. Penjualan Iklan & Affiliate</div>
                                        <div class="text-muted extra-small" style="font-size: 0.72rem;">Biaya Iklan 5% (PPN 12%) + Affiliate 10%</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-warning font-monospace" id="val-ads-profit">Rp 0</div>
                                        <span class="badge bg-warning-subtle text-warning small" id="val-ads-pct">0%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="button" id="btn-apply-calc-price" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                    <i class="fas fa-check-circle me-1"></i> Gunakan Harga Jual Ini
                                </button>
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
            modalEl.addEventListener('show.bs.modal', function () {
                const costPriceInput = document.getElementById('cost_price');
                const priceInput = document.getElementById('price');
                
                const currentHpp = costPriceInput ? cleanNumber(costPriceInput.value) : 0;
                const currentPrice = priceInput ? cleanNumber(priceInput.value) : 0;
                
                document.getElementById('calc-hpp').value = currentHpp > 0 ? formatNumberID(currentHpp) : '';
                document.getElementById('calc-price').value = currentPrice > 0 ? formatNumberID(currentPrice) : '';
                
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
            document.getElementById('calc-admin-pct').addEventListener('input', calculateMarketplaceProfit);
            document.getElementById('calc-premi-pct').addEventListener('input', calculateMarketplaceProfit);
            
            document.getElementById('calc-prog-ongkir').addEventListener('change', calculateMarketplaceProfit);
            document.getElementById('calc-prog-promo').addEventListener('change', calculateMarketplaceProfit);
            document.getElementById('calc-prog-live').addEventListener('change', calculateMarketplaceProfit);
            document.getElementById('calc-prog-spaylater').addEventListener('change', calculateMarketplaceProfit);
            document.getElementById('calc-affiliate-active').addEventListener('change', calculateMarketplaceProfit);
            document.getElementById('calc-ads-active').addEventListener('change', calculateMarketplaceProfit);

            // Handle Template selection change
            document.getElementById('calc-template').addEventListener('change', function() {
                const template = this.value;
                const adminInput = document.getElementById('calc-admin-pct');
                const premiInput = document.getElementById('calc-premi-pct');
                
                if (template === 'shopee') {
                    adminInput.value = '8.25';
                    premiInput.value = '0.50';
                    document.getElementById('calc-prog-ongkir').checked = true;
                    document.getElementById('calc-prog-promo').checked = true;
                    document.getElementById('calc-prog-live').checked = true;
                    document.getElementById('calc-prog-spaylater').checked = true;
                    document.getElementById('calc-affiliate-active').checked = true;
                    document.getElementById('calc-ads-active').checked = true;
                } else if (template === 'tiktok') {
                    adminInput.value = '5.00';
                    premiInput.value = '0.00';
                    document.getElementById('calc-prog-ongkir').checked = false;
                    document.getElementById('calc-prog-promo').checked = false;
                    document.getElementById('calc-prog-live').checked = false;
                    document.getElementById('calc-prog-spaylater').checked = false;
                    document.getElementById('calc-affiliate-active').checked = false;
                    document.getElementById('calc-ads-active').checked = false;
                } else if (template === 'tokopedia') {
                    adminInput.value = '4.50';
                    premiInput.value = '0.00';
                    document.getElementById('calc-prog-ongkir').checked = false;
                    document.getElementById('calc-prog-promo').checked = false;
                    document.getElementById('calc-prog-live').checked = false;
                    document.getElementById('calc-prog-spaylater').checked = false;
                    document.getElementById('calc-affiliate-active').checked = false;
                    document.getElementById('calc-ads-active').checked = false;
                } else if (template === 'lazada') {
                    adminInput.value = '4.00';
                    premiInput.value = '0.00';
                    document.getElementById('calc-prog-ongkir').checked = false;
                    document.getElementById('calc-prog-promo').checked = false;
                    document.getElementById('calc-prog-live').checked = false;
                    document.getElementById('calc-prog-spaylater').checked = false;
                    document.getElementById('calc-affiliate-active').checked = false;
                    document.getElementById('calc-ads-active').checked = false;
                } else {
                    adminInput.value = '0.00';
                    premiInput.value = '0.00';
                }
                
                calculateMarketplaceProfit();
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
        
        function calculateMarketplaceProfit() {
            const hpp = cleanNumber(document.getElementById('calc-hpp').value);
            const price = cleanNumber(document.getElementById('calc-price').value);
            const voucher = cleanNumber(document.getElementById('calc-voucher').value);
            
            const adminPct = parseFloat(document.getElementById('calc-admin-pct').value) || 0;
            const premiPct = parseFloat(document.getElementById('calc-premi-pct').value) || 0;
            
            // Program Pemasaran
            let programPct = 0;
            if (document.getElementById('calc-prog-ongkir').checked) programPct += parseFloat(document.getElementById('calc-prog-ongkir').value);
            if (document.getElementById('calc-prog-promo').checked) programPct += parseFloat(document.getElementById('calc-prog-promo').value);
            if (document.getElementById('calc-prog-live').checked) programPct += parseFloat(document.getElementById('calc-prog-live').value);
            if (document.getElementById('calc-prog-spaylater').checked) programPct += parseFloat(document.getElementById('calc-prog-spaylater').value);
            
            let flatFee = 0;
            const template = document.getElementById('calc-template').value;
            if (template === 'shopee') {
                flatFee = 1600;
            }
            
            const feeRate = (adminPct + premiPct + programPct) / 100;
            const totalFees = Math.round(price * feeRate) + flatFee;
            const netRevenue = price - totalFees;
            const pph = Math.round(netRevenue * 0.005);
            
            // Organic
            const organicProfit = netRevenue - hpp - voucher - pph;
            const organicPct = price > 0 ? ((organicProfit / price) * 100).toFixed(2) : 0;
            
            // Affiliate
            let affiliateCost = 0;
            if (document.getElementById('calc-affiliate-active').checked) {
                const commission = Math.round(price * 0.10);
                const ppnAff = Math.round(commission * 0.005);
                affiliateCost = commission + ppnAff;
            }
            const affiliateProfit = organicProfit - affiliateCost;
            const affiliatePct = price > 0 ? ((affiliateProfit / price) * 100).toFixed(2) : 0;
            
            // Ads & Affiliate
            let adsCost = 0;
            if (document.getElementById('calc-ads-active').checked) {
                const adsBase = Math.round(price * 0.05);
                const ppnAds = Math.round(adsBase * 0.12);
                adsCost = adsBase + ppnAds;
            }
            const adsProfit = affiliateProfit - adsCost;
            const adsPct = price > 0 ? ((adsProfit / price) * 100).toFixed(2) : 0;
            
            // UI
            document.getElementById('res-price').textContent = 'Rp ' + formatNumberID(price);
            document.getElementById('res-fees').textContent = 'Rp ' + formatNumberID(totalFees);
            document.getElementById('res-pph').textContent = 'Rp ' + formatNumberID(pph);
            document.getElementById('res-hpp-voucher').textContent = 'Rp ' + formatNumberID(hpp + voucher);
            
            function updateProfitBox(boxId, valId, pctId, profit, pct) {
                const box = document.getElementById(boxId);
                const valEl = document.getElementById(valId);
                const pctEl = document.getElementById(pctId);
                
                valEl.textContent = 'Rp ' + formatNumberID(profit);
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
            updateProfitBox('box-affiliate', 'val-affiliate-profit', 'val-affiliate-pct', affiliateProfit, affiliatePct);
            updateProfitBox('box-ads', 'val-ads-profit', 'val-ads-pct', adsProfit, adsPct);
        }
    </script>
@endsection
