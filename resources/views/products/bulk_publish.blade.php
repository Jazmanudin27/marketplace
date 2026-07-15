@extends('layouts.app')
@section('title', 'Publish Massal ke Marketplace')
@section('page-title', 'Publish Massal ke Marketplace')

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

        {{-- Daftar Produk Terpilih --}}
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-box-open me-2 text-info"></i> Daftar Produk Terpilih ({{ $products->count() }} Produk)</h6>
                <small class="text-muted d-block">Batch produk yang akan diduplikat secara massal</small>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 250px;">
                    <table class="table table-sm table-striped table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Stok</th>
                                <th>Kategori Lokal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $prod)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $prod->sku }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($prod->image_url)
                                                <img src="{{ $prod->image_url }}" alt="" class="rounded border" style="width:28px;height:28px;object-fit:cover;">
                                            @endif
                                            <span class="text-truncate" style="max-width: 350px;">{{ $prod->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end font-monospace">Rp {{ number_format($prod->price, 0, ',', '.') }}</td>
                                    <td class="text-center font-monospace">{{ number_format($prod->stock) }}</td>
                                    <td>{{ $prod->category->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Main Form --}}
        <form id="publish-form" action="{{ route('products.bulk_publish.store') }}" method="POST">
            @csrf

            {{-- Hidden Inputs for Product IDs --}}
            @foreach($products as $prod)
                <input type="hidden" name="product_ids[]" value="{{ $prod->id }}">
            @endforeach

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-share-square me-2 text-info"></i> Pilih Toko Tujuan & Kategori Default</h6>
                    <small class="text-muted d-block">Pilih toko dan tentukan kategori default untuk produk yang belum dipetakan</small>
                </div>

                <div class="card-body p-3">
                    <p class="text-muted small mb-4">
                        Pilih satu atau lebih toko tujuan. Untuk setiap toko terpilih, tentukan **Kategori Default**.
                        <br>
                        <strong>Cara kerja pencocokan kategori:</strong>
                        <br>
                        1. Jika produk sudah memiliki pemetaan kategori lokal ke marketplace di toko tersebut, sistem akan **otomatis menggunakan pemetaan tersebut**.
                        <br>
                        2. Jika tidak ada pemetaan, sistem akan menggunakan **Kategori Default** yang Anda pilih di bawah ini sebagai cadangan.
                    </p>

                    @forelse($stores as $store)
                        @php
                            $isShopee = $store->channel->code === 'shopee';
                            $isTiktok = $store->channel->code === 'tiktok';
                        @endphp

                        <div class="card mb-3 border shadow-sm">
                            <div class="card-body p-3">
                                {{-- Top row: checkbox + store name --}}
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                                    <div class="form-check m-0 d-flex align-items-center">
                                        <input type="checkbox" name="stores[]" value="{{ $store->id }}"
                                            id="store_{{ $store->id }}"
                                            class="form-check-input store-checkbox me-2 fs-5 cursor-pointer m-0"
                                            data-store="{{ $store->id }}">

                                        <label class="form-check-label d-flex flex-column cursor-pointer"
                                            for="store_{{ $store->id }}">
                                            <span class="fw-bold text-dark fs-6">
                                                {{ $store->store_name }}
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
                                </div>

                                {{-- Input Kategori Default --}}
                                <div class="category-input-area border-top pt-3 mt-2"
                                    id="cat_area_{{ $store->id }}">
                                    <label class="form-label form-label-sm fw-semibold mb-2">
                                        @if ($isShopee || $isTiktok)
                                            <i class="fas fa-search me-1 text-muted"></i> Kategori Default
                                            {{ ucfirst($store->channel->code) }} <span class="text-danger">*</span>
                                        @else
                                            <i class="fas fa-folder-open me-1 text-muted"></i> ID Kategori Default
                                            {{ $store->channel->name }} <span class="text-danger">*</span>
                                        @endif
                                    </label>

                                    @if (in_array($store->channel->code, ['shopee', 'tiktok']))
                                        {{-- Shopee/Tiktok Category Picker --}}
                                        <div class="shopee-category-picker position-relative"
                                            data-store-id="{{ $store->id }}"
                                            data-channel="{{ $store->channel->code }}">
                                            <input type="hidden" name="categories[{{ $store->id }}]"
                                                id="cat_id_{{ $store->id }}"
                                                value="">
                                            <input type="hidden" name="category_names[{{ $store->id }}]"
                                                id="cat_name_{{ $store->id }}"
                                                value="">

                                            {{-- Search Input Wrapper --}}
                                            <div class="input-group input-group-sm" id="cat_search_wrapper_{{ $store->id }}">
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
                                                class="shopee-cat-selected mt-2 d-none">
                                                <span class="badge bg-primary text-white p-2 d-inline-flex align-items-center rounded-3">
                                                    <i class="fas fa-check-circle me-1 text-success bg-white rounded-circle"></i>
                                                    <span id="cat_selected_name_{{ $store->id }}"></span>
                                                    <span id="cat_selected_id_{{ $store->id }}"
                                                        class="opacity-75 ms-1 font-monospace"></span>
                                                    <button type="button"
                                                        class="btn btn-link p-0 text-white ms-2 text-decoration-none small"
                                                        onclick="clearCategorySelection({{ $store->id }}, '{{ $store->channel->code }}')"><i
                                                            class="fas fa-pen"></i> Ubah</button>
                                                </span>
                                            </div>

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
                                                    placeholder="Contoh ID Template: 123456789">
                                                <small class="text-muted d-block mt-1">
                                                    Wajib diisi jika kategori produk Shopee mewajibkan tabel ukuran (seperti Pakaian Anak/Fashion). Dapatkan ID dari Seller Centre Shopee (Kelola Ukuran).
                                                </small>
                                            </div>
                                        @endif

                                        <div class="form-check mt-3 small">
                                            <input type="checkbox" name="save_mapping[{{ $store->id }}]"
                                                id="save_mapping_{{ $store->id }}" value="1"
                                                class="form-check-input" checked>
                                            <label for="save_mapping_{{ $store->id }}"
                                                class="form-check-label text-muted">
                                                <i class="fas fa-bookmark me-1 text-primary"></i> Simpan pemetaan kategori ini untuk kategori produk lokal yang sesuai.
                                            </label>
                                        </div>
                                    @else
                                        {{-- Other platforms: Manual Input --}}
                                        <input type="hidden" name="category_names[{{ $store->id }}]"
                                            id="cat_name_{{ $store->id }}" value="Manual Category">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                                            <input type="number" name="categories[{{ $store->id }}]"
                                                id="category_{{ $store->id }}" class="form-control form-control-sm"
                                                placeholder="Contoh ID Kategori: 602001">
                                        </div>
                                    @endif
                                </div>
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
                                <i class="fas fa-paper-plane me-1"></i> Mulai Publikasi Massal
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
                        errorMsg = 'Pilih kategori default terlebih dahulu untuk toko yang dicentang!';
                        valid = false;
                        return false;
                    }
                    if ($('#category_' + storeId).length && !manualCatId) {
                        errorMsg = 'Masukkan ID Kategori default terlebih dahulu untuk toko yang dicentang!';
                        valid = false;
                        return false;
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
                        '<i class="fas fa-spinner fa-spin me-1"></i> Memproses Publikasi Massal...');
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
