@extends('layouts.app')
@section('title', 'Publish ke Marketplace')
@section('page-title', 'Publish Produk ke Marketplace')

@section('content')
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger shadow-sm mb-4 py-2 px-3">
                <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Terjadi Kesalahan</h6>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Informasi Produk Master --}}
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-box-open me-2 text-info"></i> Informasi Produk Master</h6>
                <small class="text-muted d-block">Detail spesifikasi produk lokal yang akan dipublikasikan</small>
            </div>

            <div class="card-body p-3">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        @if (!empty($fallbackImageUrl))
                            <div class="position-relative border border-secondary border-opacity-10 rounded p-1 bg-light d-flex align-items-center justify-content-center">
                                <img src="{{ $fallbackImageUrl }}" alt="{{ $product->name }}" class="img-fluid rounded img-thumbnail">
                                @if (empty($product->image_url))
                                    <span class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white text-center py-1 small">Gambar Sinkronisasi</span>
                                @endif
                            </div>
                        @else
                            <div class="border border-dashed border-secondary border-opacity-25 rounded d-flex flex-column align-items-center justify-content-center text-muted bg-light p-3">
                                <i class="fas fa-image fs-4 mb-1"></i>
                                <small class="text-muted">Tidak ada foto</small>
                            </div>
                        @endif
                    </div>
                    <div class="col">
                        <h5 class="mb-2 fw-bold text-primary">{{ $product->name }}</h5>
                        <div class="row g-2">
                            <div class="col-sm-6 col-md-3">
                                <small class="text-muted d-block">SKU</small>
                                <span class="font-monospace fw-bold small text-dark">{{ $product->sku }}</span>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <small class="text-muted d-block">Harga Jual</small>
                                <span class="fw-bold text-success small">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <small class="text-muted d-block">Stok Lokal</small>
                                <span class="fw-bold text-dark small">{{ number_format($product->stock) }} pcs</span>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <small class="text-muted d-block">Berat & Dimensi</small>
                                <span class="fw-bold text-dark small">
                                    {{ $product->weight ?? '-' }} kg /
                                    @if ($product->length || $product->width || $product->height)
                                        {{ $product->length ?? 0 }}x{{ $product->width ?? 0 }}x{{ $product->height ?? 0 }} cm
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Form --}}
        <form id="publish-form" action="{{ route('products.publish.store', $product) }}" method="POST">
            @csrf

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-share-square me-2 text-info"></i> Pilih Toko Tujuan & Kategori</h6>
                    <small class="text-muted d-block">Upload produk lokal ini ke channel toko marketplace terpilih</small>
                </div>

                <div class="card-body p-3">
                    <p class="text-muted small mb-4">
                        Pilih toko marketplace yang ingin Anda upload produk baru ini. Untuk Shopee & TikTok, cari kategori
                        langsung dari database marketplace.
                        <strong class="text-primary">Aktifkan "Simpan Pemetaan"</strong> agar kategori ini otomatis dipilih
                        ulang untuk produk dengan kategori yang sama.
                    </p>

                    @forelse($stores as $store)
                        @php
                            $isMapped = in_array($store->id, $mappedStoreIds ?? []);
                            $isProcessing = in_array($store->id, $processingStoreIds ?? []);
                            $isShopee = $store->channel->code === 'shopee';
                            $isTiktok = $store->channel->code === 'tiktok';
                            $existingMapping = $categoryMappings[$store->id] ?? null;
                        @endphp

                        <div class="card mb-3 border {{ $isMapped || $isProcessing ? 'opacity-75' : 'shadow-sm' }}">
                            <div class="card-body p-3">
                                {{-- Top row: checkbox + store name --}}
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                                    <div class="form-check m-0 d-flex align-items-center">
                                        @if ($isMapped)
                                            <span class="text-success fs-5 me-2"><i class="fas fa-check-circle"></i></span>
                                        @elseif ($isProcessing)
                                            <span class="text-warning fs-5 me-2"><i class="fas fa-spinner fa-spin"></i></span>
                                        @else
                                            <input type="checkbox" name="stores[]" value="{{ $store->id }}"
                                                id="store_{{ $store->id }}"
                                                class="form-check-input store-checkbox me-2 fs-5 cursor-pointer m-0"
                                                data-store="{{ $store->id }}">
                                        @endif

                                        <label class="form-check-label d-flex flex-column"
                                            for="{{ $isMapped || $isProcessing ? '' : 'store_' . $store->id }}">
                                            <span class="fw-bold text-dark fs-6">
                                                {{ $store->store_name }}
                                                @if ($isMapped)
                                                    <small class="badge bg-success ms-2">Terhubung</small>
                                                @elseif ($isProcessing)
                                                    <small class="badge bg-warning text-dark ms-2">Sedang Diproses</small>
                                                @endif
                                            </span>
                                            <span class="d-inline-block mt-1">
                                                @if ($isShopee)
                                                    <span class="badge bg-danger"><i class="fab fa-shopify me-1"></i> Shopee</span>
                                                @elseif ($isTiktok)
                                                    <span class="badge bg-dark"><i class="fab fa-tiktok me-1"></i> TikTok</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $store->channel->name }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    </div>

                                    <div class="ms-auto">
                                        @if ($isMapped)
                                            <span class="text-muted small"><i class="fas fa-link me-1"></i> Terhubung & Sinkron</span>
                                        @elseif ($isProcessing)
                                            <span class="text-warning small"><i class="fas fa-spinner fa-spin me-1"></i> Sedang Diproses...</span>
                                        @elseif ($existingMapping)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                                <i class="fas fa-magic me-1"></i> Pemetaan Tersimpan
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Input Kategori --}}
                                @if (!$isMapped && !$isProcessing)
                                    <div class="category-input-area border-top pt-3 mt-2"
                                        id="cat_area_{{ $store->id }}">
                                        <label class="form-label form-label-sm fw-semibold mb-2">
                                            @if ($isShopee || $isTiktok)
                                                <i class="fas fa-search me-1 text-muted"></i> Cari Kategori
                                                {{ ucfirst($store->channel->code) }} <span class="text-danger">*</span>
                                            @else
                                                <i class="fas fa-folder-open me-1 text-muted"></i> ID Kategori
                                                {{ $store->channel->name }} <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if ($existingMapping)
                                            @php
                                                $mappedCatIdRaw = $existingMapping['marketplace_category_id'] ?? '';
                                                $mappedCatId = $mappedCatIdRaw;
                                                $mappedSizeChartId = '';
                                                if (strpos((string)$mappedCatIdRaw, '|') !== false) {
                                                    $parts = explode('|', (string)$mappedCatIdRaw);
                                                    $mappedCatId = $parts[0];
                                                    $mappedSizeChartId = $parts[1];
                                                }
                                            @endphp
                                            <div class="alert alert-info py-2 px-3 mb-2 d-flex align-items-center flex-wrap gap-2 border-0 bg-primary bg-opacity-10 text-primary-emphasis">
                                                <i class="fas fa-bookmark text-primary"></i>
                                                <span class="small me-auto text-dark">
                                                    Pemetaan Tersimpan:
                                                    <strong>{{ $existingMapping['marketplace_category_name'] }}</strong>
                                                    <span class="text-muted">(ID: {{ $mappedCatId }})</span>
                                                    @if ($mappedSizeChartId)
                                                        <span class="badge bg-secondary ms-1 small text-white">Size Chart ID: {{ $mappedSizeChartId }}</span>
                                                    @endif
                                                </span>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary px-3 rounded-3"
                                                        onclick="useMapping({{ $store->id }}, '{{ $mappedCatId }}', '{{ addslashes($existingMapping['marketplace_category_name']) }}', '{{ $mappedSizeChartId }}')">
                                                        <i class="fas fa-check me-1"></i> Gunakan
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3"
                                                        onclick="ignoreMapping({{ $store->id }})">
                                                        Pilih Lain
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
 
                                        @if (in_array($store->channel->code, ['shopee', 'tiktok']))
                                            {{-- Shopee/Tiktok Category Picker --}}
                                            <div class="shopee-category-picker position-relative"
                                                data-store-id="{{ $store->id }}"
                                                data-channel="{{ $store->channel->code }}">
                                                <input type="hidden" name="categories[{{ $store->id }}]"
                                                    id="cat_id_{{ $store->id }}"
                                                    value="{{ $mappedCatId ?? '' }}"
                                                    {{ $existingMapping ? '' : 'required' }}>
                                                <input type="hidden" name="category_names[{{ $store->id }}]"
                                                    id="cat_name_{{ $store->id }}"
                                                    value="{{ $existingMapping['marketplace_category_name'] ?? '' }}">
 
                                                {{-- Search Input Wrapper --}}
                                                <div class="input-group input-group-sm {{ $existingMapping ? 'd-none' : '' }}" id="cat_search_wrapper_{{ $store->id }}">
                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                    <input type="text" id="cat_search_{{ $store->id }}"
                                                        class="form-control form-control-sm shopee-cat-search"
                                                        placeholder="Ketik nama kategori ({{ ucfirst($store->channel->code) }})..."
                                                        autocomplete="off" data-store-id="{{ $store->id }}">
                                                    <button type="button" id="cat_clear_{{ $store->id }}"
                                                        class="btn btn-outline-secondary btn-sm shopee-cat-clear d-none"><i class="fas fa-times"></i></button>
                                                </div>
 
                                                {{-- Selected Badge --}}
                                                <div id="cat_selected_{{ $store->id }}"
                                                    class="shopee-cat-selected mt-2 {{ $existingMapping ? '' : 'd-none' }}">
                                                    <span class="badge bg-primary text-white p-2 d-inline-flex align-items-center rounded-3">
                                                        <i class="fas fa-check-circle me-1 text-success bg-white rounded-circle"></i>
                                                        <span id="cat_selected_name_{{ $store->id }}">{{ $existingMapping['marketplace_category_name'] ?? '' }}</span>
                                                        <span id="cat_selected_id_{{ $store->id }}"
                                                            class="opacity-75 ms-1 font-monospace">{{ $existingMapping ? '(ID: ' . $mappedCatId . ')' : '' }}</span>
                                                        <button type="button"
                                                            class="btn btn-link p-0 text-white ms-2 text-decoration-none small"
                                                            onclick="clearCategorySelection({{ $store->id }}, '{{ $store->channel->code }}')"><i
                                                                class="fas fa-pen"></i> Ubah</button>
                                                    </span>
                                                </div>
 
                                                {{-- Dropdown list --}}
                                                <div id="cat_dropdown_{{ $store->id }}"
                                                    class="list-group shadow rounded border mt-1 position-absolute w-100 overflow-y-auto d-none">
                                                </div>
                                            </div>
 
                                            {{-- Shopee-specific Size Chart Template ID --}}
                                            @if ($store->channel->code === 'shopee')
                                                <div class="mt-3">
                                                    <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                                        <i class="fas fa-ruler-combined text-muted me-1"></i> ID Template Size Chart Shopee (Opsional)
                                                    </label>
                                                    <input type="number" name="size_chart_ids[{{ $store->id }}]" 
                                                        class="form-control form-control-sm rounded-3" 
                                                        placeholder="Contoh ID Template: 123456789"
                                                        value="{{ $mappedSizeChartId ?? '' }}">
                                                    <small class="text-muted d-block mt-1">
                                                        Wajib diisi jika kategori produk Shopee mewajibkan tabel ukuran (seperti Pakaian Anak/Fashion). Dapatkan ID dari Seller Centre Shopee (Kelola Ukuran).
                                                    </small>
                                                </div>
                                            @endif

                                            {{-- Save Mapping checkbox --}}
                                            @if ($product->category_id)
                                                <div class="form-check mt-3 small">
                                                    <input type="checkbox" name="save_mapping[{{ $store->id }}]"
                                                        id="save_mapping_{{ $store->id }}" value="1"
                                                        class="form-check-input" {{ $existingMapping ? 'checked' : '' }}>
                                                    <label for="save_mapping_{{ $store->id }}"
                                                        class="form-check-label text-muted">
                                                        <i class="fas fa-bookmark me-1 text-primary"></i> Simpan pemetaan
                                                        kategori ini untuk kategori produk lokal: <strong
                                                            class="text-dark">"{{ $product->category->name ?? 'ini' }}"</strong>
                                                    </label>
                                                </div>
                                            @endif
                                        @else
                                            {{-- Other platforms: Manual Input --}}
                                            <input type="hidden" name="category_names[{{ $store->id }}]"
                                                id="cat_name_{{ $store->id }}" value="Manual Category">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                                                <input type="number" name="categories[{{ $store->id }}]"
                                                    id="category_{{ $store->id }}" class="form-control form-control-sm"
                                                    placeholder="Contoh ID Kategori: 602001" required>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted border border-dashed rounded bg-light">
                            <i class="fas fa-store-slash fs-1 mb-2"></i>
                            <p class="mb-3">Belum ada toko marketplace yang terhubung.</p>
                            <a href="{{ route('stores.index') }}" class="btn btn-primary btn-sm rounded-3">Hubungkan Toko</a>
                        </div>
                    @endforelse

                    @if ($stores->count() > 0)
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm px-4 rounded-3">Batal</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3">
                                <i class="fas fa-paper-plane me-1"></i> Kirim ke Antrean Publikasi
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            const API_URLS = {
                'shopee': '{{ route('shopee.categories') }}',
                'tiktok': '{{ route('tiktok.categories') }}'
            };
            const categoriesCache = {
                'shopee': null,
                'tiktok': null
            };
            const loadingPromises = {
                'shopee': null,
                'tiktok': null
            };

            function loadAllCategories(channel) {
                if (categoriesCache[channel] !== null) {
                    return Promise.resolve(categoriesCache[channel]);
                }
                if (loadingPromises[channel]) {
                    return loadingPromises[channel];
                }

                const url = API_URLS[channel];
                if (!url) return Promise.resolve([]);

                loadingPromises[channel] = $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    cache: false
                }).then(function(data) {
                    if (data.success) {
                        categoriesCache[channel] = data.data;
                        console.log(`[${channel} Cat] Loaded`, categoriesCache[channel].length,
                            'categories');
                        return categoriesCache[channel];
                    } else {
                        throw new Error(data.message || 'Gagal memuat kategori');
                    }
                });

                return loadingPromises[channel];
            }

            function filterCategories(channel, query) {
                const all = categoriesCache[channel];
                if (!all) return [];
                const q = query.toLowerCase().trim();
                if (!q) return [];
                return all.filter(c => c.name.toLowerCase().includes(q)).slice(0, 60);
            }

            function renderDropdown($dropdown, categories, query, channel) {
                $dropdown.empty();
                if (!query || query.trim() === '') {
                    $dropdown.html(
                        '<div class="text-center text-muted py-3 small"><i class="fas fa-keyboard me-2"></i> Ketik nama kategori untuk mencari...</div>'
                    );
                    return;
                }
                if (categories.length === 0) {
                    $dropdown.html(
                        '<div class="text-center text-muted py-3 small"><i class="fas fa-search me-2"></i> Tidak ditemukan untuk "<strong>' +
                        query + '</strong>"</div>');
                    return;
                }

                const countText = categories.length + ' kategori ditemukan' + (categoriesCache[channel] ? ' dari ' +
                    categoriesCache[channel].length + ' total' : '');
                $dropdown.append('<div class="list-group-item bg-light text-muted py-1 px-3 border-bottom small">' +
                    countText + '</div>');

                categories.forEach(function(cat) {
                    const regex = new RegExp('(' + query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')',
                        'gi');
                    const highlighted = cat.name.replace(regex,
                        '<mark class="bg-warning bg-opacity-25 p-0">$1</mark>');

                    const $option = $('<div>')
                        .addClass('list-group-item list-group-item-action cat-option')
                        .attr('data-id', cat.id)
                        .attr('data-name', cat.name)
                        .html(highlighted + '<span class="d-block text-muted small">ID: ' + cat.id + '</span>');

                    $dropdown.append($option);
                });
            }

            function selectCategory(storeId, id, name) {
                $('#cat_id_' + storeId).val(id).prop('required', false);
                $('#cat_name_' + storeId).val(name);
                $('#cat_search_' + storeId).val('');
                $('#cat_dropdown_' + storeId).addClass('d-none');
                $('#cat_clear_' + storeId).addClass('d-none');
                $('#cat_search_wrapper_' + storeId).addClass('d-none');

                $('#cat_selected_name_' + storeId).text(name);
                $('#cat_selected_id_' + storeId).text('(ID: ' + id + ')');
                $('#cat_selected_' + storeId).removeClass('d-none');
            }

            window.clearCategorySelection = function(storeId, channel) {
                $('#cat_id_' + storeId).val('').prop('required', true);
                $('#cat_name_' + storeId).val('');
                $('#cat_selected_' + storeId).addClass('d-none');
                $('#cat_search_wrapper_' + storeId).removeClass('d-none');
                $('#cat_search_' + storeId).focus();
            };

            window.useMapping = function(storeId, catId, catName, sizeChartId) {
                selectCategory(storeId, catId, catName);
                if (sizeChartId) {
                    $('input[name="size_chart_ids[' + storeId + ']"]').val(sizeChartId);
                } else {
                    $('input[name="size_chart_ids[' + storeId + ']"]').val('');
                }
                $('#save_mapping_' + storeId).prop('checked', true);
                $('#store_' + storeId).prop('checked', true);
            };

            window.ignoreMapping = function(storeId) {
                $('#cat_id_' + storeId).val('').prop('required', true);
                $('#cat_selected_' + storeId).addClass('d-none');
                $('#cat_search_wrapper_' + storeId).removeClass('d-none');
                $('#cat_search_' + storeId).focus();
            };

            // Initialize Picker events
            $('.shopee-category-picker').each(function() {
                const $picker = $(this);
                const storeId = $picker.data('store-id');
                const channel = $picker.data('channel');

                const $searchInput = $('#cat_search_' + storeId);
                const $dropdown = $('#cat_dropdown_' + storeId);
                const $clearBtn = $('#cat_clear_' + storeId);
                let debounceTimer = null;

                $clearBtn.on('click', function() {
                    $searchInput.val('').focus();
                    $clearBtn.addClass('d-none');
                    if (categoriesCache[channel]) {
                        renderDropdown($dropdown, [], '', channel);
                    }
                    $dropdown.addClass('d-none');
                });

                $searchInput.on('focus', function() {
                    $dropdown.removeClass('d-none');
                    const val = $(this).val();
                    if (val) {
                        $clearBtn.removeClass('d-none');
                    } else {
                        $clearBtn.addClass('d-none');
                    }
                    if (!categoriesCache[channel]) {
                        $dropdown.html(
                            '<div class="text-center text-muted py-3 small"><i class="fas fa-spinner fa-spin me-2"></i> Memuat kategori...</div>'
                        );
                        loadAllCategories(channel)
                            .then(function(categories) {
                                renderDropdown($dropdown, filterCategories(channel, $searchInput
                                    .val()), $searchInput.val(), channel);
                            })
                            .catch(function(err) {
                                $dropdown.html(
                                    '<div class="text-center text-danger py-3 small"><i class="fas fa-exclamation-triangle me-2"></i> Gagal memuat kategori</div>'
                                );
                            });
                    } else {
                        renderDropdown($dropdown, filterCategories(channel, $searchInput.val()),
                            $searchInput.val(), channel);
                    }
                });

                $searchInput.on('input', function() {
                    clearTimeout(debounceTimer);
                    const val = $(this).val();
                    if (val) {
                        $clearBtn.removeClass('d-none');
                    } else {
                        $clearBtn.addClass('d-none');
                    }
                    debounceTimer = setTimeout(function() {
                        if (!categoriesCache[channel]) return;
                        $dropdown.removeClass('d-none');
                        renderDropdown($dropdown, filterCategories(channel, val), val,
                            channel);
                    }, 180);
                });

                $dropdown.on('click', '.cat-option', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    selectCategory(storeId, id, name);
                    $('#store_' + storeId).prop('checked', true);
                });

                $searchInput.on('keydown', function(e) {
                    if ($dropdown.hasClass('d-none')) return;
                    const $options = $dropdown.find('.cat-option');
                    const $active = $dropdown.find('.cat-option.active');
                    let idx = $options.index($active);

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        $active.removeClass('active');
                        idx = Math.min(idx + 1, $options.length - 1);
                        const $next = $options.eq(idx).addClass('active');
                        if ($next.length) {
                            $next[0].scrollIntoView({
                                block: 'nearest'
                            });
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        $active.removeClass('active');
                        idx = Math.max(idx - 1, 0);
                        const $prev = $options.eq(idx).addClass('active');
                        if ($prev.length) {
                            $prev[0].scrollIntoView({
                                block: 'nearest'
                            });
                        }
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if ($active.length) $active.click();
                    } else if (e.key === 'Escape') {
                        $dropdown.addClass('d-none');
                    }
                });
            });

            // Close dropdowns on outside click
            $(document).on('click', function(e) {
                $('.shopee-category-picker').each(function() {
                    const storeId = $(this).data('store-id');
                    const $wrapper = $('#cat_search_wrapper_' + storeId);
                    const $dropdown = $('#cat_dropdown_' + storeId);
                    if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0 && !$dropdown
                        .is(e.target) && $dropdown.has(e.target).length === 0) {
                        $dropdown.addClass('d-none');
                    }
                });
            });

            // Form submit validation
            $('#publish-form').on('submit', function(e) {
                const $checked = $('.store-checkbox:checked');
                if ($checked.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Toko Belum Dipilih',
                        text: 'Pilih minimal satu toko untuk dipublikasikan!'
                    });
                    e.preventDefault();
                    return;
                }

                let valid = true;
                let errorMsg = '';
                $checked.each(function() {
                    const storeId = $(this).data('store');
                    const catId = $('#cat_id_' + storeId).val();
                    const manualCatId = $('#category_' + storeId).val();

                    if ($('#cat_id_' + storeId).length && !catId) {
                        errorMsg = 'Pilih kategori terlebih dahulu untuk toko yang dicentang!';
                        valid = false;
                        return false; // break loop
                    }
                    if ($('#category_' + storeId).length && !manualCatId) {
                        errorMsg = 'Masukkan ID Kategori terlebih dahulu untuk toko yang dicentang!';
                        valid = false;
                        return false; // break loop
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Lengkapi Kategori',
                        text: errorMsg
                    });
                } else {
                    const $btn = $(this).find('button[type="submit"]');
                    $btn.prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin me-1"></i> Mengirim ke Antrean...');
                }
            });

            // Preload categories in background
            $('.shopee-category-picker').each(function() {
                const channel = $(this).data('channel');
                loadAllCategories(channel).catch(() => {});
            });
        });
    </script>
@endsection
