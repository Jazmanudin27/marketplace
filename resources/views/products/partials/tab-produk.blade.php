{{-- Tabel Produk --}}
<div class="card border shadow-sm">
    <div
        class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-box-open me-2 text-info"></i>Daftar Master Produk</h6>
            <small class="text-muted d-block">
                Kelola produk, harga, stok, dan koneksi marketplace
            </small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnBulkPublish" class="btn btn-outline-primary btn-sm px-3 rounded-3 d-none">
                <i class="fas fa-cloud-upload-alt me-1"></i>Publish Massal (<span id="selectedCount">0</span>)
            </button>
            <form action="{{ route('products.auto_bundle') }}" method="POST" class="d-inline" id="formAutoBundle">
                @csrf
                <button type="submit" class="btn btn-outline-purple btn-sm px-3 rounded-3 fw-semibold" style="color: #6f42c1; border-color: #6f42c1;" onclick="return confirm('Sistem akan otomatis mendeteksi produk Set/Bundling (berawalan SET-, PAKET-, atau BUNDLE-) dan mencocokkan komponennya dari produk single. Lanjutkan?')">
                    <i class="fas fa-magic me-1"></i>Auto Set / Bundling
                </button>
            </form>
            <a href="{{ route('products.bulk_price_calculator') }}" class="btn btn-outline-success btn-sm px-3 rounded-3 fw-semibold" title="Setting & Kalkulasi Harga Masal">
                <i class="fas fa-calculator me-1"></i>Kalkulator Harga Masal
            </a>
            <a href="{{ route('reports.master_product') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-3" title="Laporan & Cetak Master Produk">
                <i class="fas fa-file-alt me-1"></i>Laporan Master Produk
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3 rounded-3">
                <i class="fas fa-plus me-1"></i>Tambah Produk
            </a>
        </div>
    </div>

    <div class="card-body p-3">
        <div class="card border shadow-sm mb-3">
            <div class="card-body py-2.5 px-3">

                {{-- Quick Filter Pills: Jenis & Status --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2.5 pb-2 border-bottom">
                    <span class="fw-bold small text-muted me-1"><i class="fas fa-filter text-primary me-1"></i>Filter Cepat:</span>
                    <a href="{{ route('products.index', request()->except(['is_preorder', 'is_bundle'])) }}"
                        class="btn btn-xs rounded-pill {{ (!request()->has('is_preorder') || request('is_preorder') === '') && (!request()->has('is_bundle') || request('is_bundle') === '') ? 'btn-primary fw-bold' : 'btn-outline-secondary' }} px-3 py-1">
                        🌐 Semua Produk <span class="badge bg-white text-dark ms-1">{{ number_format($products->total()) }}</span>
                    </a>
                    
                    {{-- Filter Single vs Bundle --}}
                    <a href="{{ route('products.index', array_merge(request()->query(), ['is_bundle' => '0'])) }}"
                        class="btn btn-xs rounded-pill fw-bold px-3 py-1"
                        style="{{ request('is_bundle') === '0' ? 'background-color:#0284c7; border-color:#0284c7; color:#fff;' : 'color:#0284c7; border-color:#0284c7;' }}">
                        🏷️ Single <span class="badge bg-white text-dark ms-1">{{ number_format($singleCount ?? 0) }}</span>
                    </a>
                    <a href="{{ route('products.index', array_merge(request()->query(), ['is_bundle' => '1'])) }}"
                        class="btn btn-xs rounded-pill fw-bold px-3 py-1"
                        style="{{ request('is_bundle') === '1' ? 'background-color:#e11d48; border-color:#e11d48; color:#fff;' : 'color:#e11d48; border-color:#e11d48;' }}">
                        📦 BUNDLE / Set <span class="badge bg-white text-dark ms-1">{{ number_format($bundleCount ?? 0) }}</span>
                    </a>

                    <span class="text-muted opacity-25 mx-1">|</span>

                    {{-- Filter PO vs Ready --}}
                    <a href="{{ route('products.index', array_merge(request()->query(), ['is_preorder' => '1'])) }}"
                        class="btn btn-xs rounded-pill {{ request('is_preorder') === '1' ? 'btn-purple text-white fw-bold' : 'btn-outline-purple' }} px-3 py-1"
                        style="{{ request('is_preorder') === '1' ? 'background-color:#8b5cf6; border-color:#8b5cf6; color:#fff;' : 'color:#8b5cf6; border-color:#8b5cf6;' }}">
                        📦 Pre-Order (PO) <span class="badge bg-white text-dark ms-1">{{ number_format($poCount ?? 0) }}</span>
                    </a>
                    <a href="{{ route('products.index', array_merge(request()->query(), ['is_preorder' => '0'])) }}"
                        class="btn btn-xs rounded-pill fw-bold px-3 py-1"
                        style="{{ request('is_preorder') === '0' ? 'background-color:#16a34a; border-color:#16a34a; color:#fff;' : 'color:#15803d; border-color:#16a34a;' }}">
                        ⚡ Ready Stock <span class="badge bg-white text-dark ms-1">{{ number_format($readyCount ?? 0) }}</span>
                    </a>
                </div>

                <form method="GET" action="{{ route('products.index') }}" id="filterProdukForm">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-tag text-muted me-1"></i>Nama Barang
                            </label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Cari nama barang..." value="{{ request('name') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-barcode text-muted me-1"></i>SKU
                            </label>
                            <input type="text" name="sku" class="form-control form-control-sm" placeholder="Cari SKU..." value="{{ request('sku') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-layer-group text-muted me-1"></i>Channel
                            </label>
                            <select name="channel_id" class="form-select form-select-sm">
                                <option value="">-- Semua Channel --</option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->id }}"
                                        {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-store text-muted me-1"></i>Akun / Toko
                            </label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">-- Semua Toko --</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}"
                                        {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }} ({{ $store->channel->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-link text-muted me-1"></i>Tautan Toko
                            </label>
                            <select name="link_status" class="form-select form-select-sm">
                                <option value="">-- Semua Status --</option>
                                <option value="unlinked" {{ request('link_status') === 'unlinked' ? 'selected' : '' }}>
                                    Belum Ditautkan (0 Toko)
                                </option>
                                <option value="partial" {{ request('link_status') === 'partial' ? 'selected' : '' }}>
                                    Ditautkan Sebagian Toko
                                </option>
                                <option value="all" {{ request('link_status') === 'all' ? 'selected' : '' }}>
                                    Ditautkan Semua Toko ({{ $connectedStoresCount }} Toko)
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-boxes text-muted me-1"></i>Jenis Produk
                            </label>
                            <select name="is_bundle" class="form-select form-select-sm">
                                <option value="">-- Semua Jenis --</option>
                                <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>Single</option>
                                <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>BUNDLE / Set</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-clock text-muted me-1"></i>Tipe PO
                            </label>
                            <select name="is_preorder" class="form-select form-select-sm">
                                <option value="">-- Semua Tipe --</option>
                                <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>PO (Pre-Order)</option>
                                <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>Ready Stock</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fas fa-search me-1"></i>Terapkan
                            </button>
                            @if (request()->anyFilled(['channel_id', 'store_id', 'link_status', 'name', 'sku', 'is_preorder', 'is_bundle']))
                                <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            @endif
                        </div>
                        <div class="col-md ms-auto text-end align-self-center">
                            <small class="text-muted">
                                Menampilkan <strong class="text-dark">{{ $products->total() }}</strong> produk
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive rounded border mt-2">
            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;"><input type="checkbox" id="selectAllProducts" class="form-check-input"></th>
                        <th>SKU VARIASI</th>
                        <th>NAMA BARANG</th>
                        <th>SKU INDUK</th>
                        <th>KATEGORI / MERK</th>
                        <th class="text-center">VARIASI</th>
                        <th class="text-end">HARGA (HPP / JUAL)</th>
                        <th class="text-center">STOK</th>
                        <th class="text-center">STATUS & PO</th>
                        <th>MARKETPLACE</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-left: 4px solid {{ $product->is_preorder ? '#8b5cf6' : '#22c55e' }} !important;">
                            <td class="text-center">
                                <input type="checkbox" value="{{ $product->id }}" class="form-check-input product-select-checkbox">
                            </td>
                            <td>
                                <code class="text-primary font-monospace">{{ $product->sku }}</code>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="rounded border img-thumbnail-clickable"
                                            style="width:40px;height:40px;object-fit:cover;"
                                            data-product-name="{{ $product->name }}">
                                    @else
                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                            style="width:40px;height:40px;flex-shrink:0;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-dark fw-semibold text-wrap lh-sm" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </div>
                                        <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                            @if ($product->is_bundle)
                                                <span class="badge text-white px-2 py-0.5 fw-bold" style="background-color: #e11d48; font-size: 0.68rem;" title="Produk Bundling / Paket">
                                                    <i class="fas fa-boxes me-1"></i>BUNDLE
                                                </span>
                                            @else
                                                <span class="badge px-2 py-0.5 fw-bold" style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.68rem;">
                                                    <i class="fas fa-box me-1"></i>Single
                                                </span>
                                            @endif

                                            @if ($product->is_preorder)
                                                <span class="badge text-white px-2 py-0.5 fw-bold" style="background-color: #8b5cf6; font-size: 0.68rem;">
                                                    <i class="fas fa-clock me-1"></i>PO ({{ $product->preorder_days ?? 7 }} Hari)
                                                </span>
                                            @else
                                                <span class="badge px-2 py-0.5 fw-bold" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; font-size: 0.68rem;">
                                                    <i class="fas fa-bolt me-1"></i>Ready Stock
                                                </span>
                                            @endif
                                            @if ($product->sub_kategori)
                                                <small class="text-muted">{{ $product->sub_kategori }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($product->sku_induk)
                                    <code class="text-secondary font-monospace">{{ $product->sku_induk }}</code>
                                @else
                                    <span class="text-muted opacity-50">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="lh-sm">
                                    <div class="text-dark-50 small">
                                        {{ $product->category->name ?? '—' }}
                                    </div>
                                    <div class="text-muted mt-1 small">
                                        @if ($product->brand)
                                            <span>Merk:</span> <span
                                                class="text-dark">{{ $product->brand->name }}</span>
                                        @else
                                            <span>Merk:</span> <span class="text-muted opacity-50">—</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="lh-sm small">
                                    <div>
                                        <span class="text-muted">Sz:</span> <strong
                                            class="text-dark font-monospace">{{ $product->ukuran ?? '—' }}</strong>
                                    </div>
                                    <div class="mt-1">
                                        <span class="text-muted">Wrn:</span> <strong
                                            class="text-secondary">{{ $product->warna ?? '—' }}</strong>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="lh-sm small">
                                    <div>
                                        <span class="text-muted">HPP:</span> <span
                                            class="font-monospace text-muted">{{ $product->cost_price ? 'Rp ' . number_format($product->cost_price, 0, ',', '.') : '—' }}</span>
                                    </div>
                                    <div class="mt-1">
                                        <span class="text-muted">Jual:</span> <strong
                                            class="font-monospace text-primary">Rp
                                            {{ number_format($product->price, 0, ',', '.') }}</strong>
                                    </div>
                                    @if($product->reseller_price)
                                    <div class="mt-1">
                                        <span class="text-muted">Rsl:</span> <strong
                                            class="font-monospace text-success">Rp
                                            {{ number_format($product->reseller_price, 0, ',', '.') }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $stockBadgeClass =
                                        $product->stock <= $product->min_stock
                                            ? 'bg-danger text-white'
                                            : 'bg-success text-white';
                                @endphp
                                <span class="badge {{ $stockBadgeClass }} font-monospace">
                                    {{ number_format($product->stock) }}
                                </span>
                                @if ($product->stock <= $product->min_stock)
                                    <div class="text-danger mt-1 fw-bold text-uppercase small">stok rendah</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if ($product->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif

                                    {{-- Badge Status PO (Pre-Order) --}}
                                    <div id="po-badge-container-{{ $product->id }}">
                                        @if ($product->is_preorder)
                                            <button type="button" class="btn btn-xs p-0 border-0 btn-quick-po"
                                                data-product-id="{{ $product->id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-is-preorder="1"
                                                data-preorder-days="{{ $product->preorder_days ?? 7 }}"
                                                title="Klik untuk ubah status PO">
                                                <span class="badge text-white px-2 py-1" style="background-color: #8b5cf6; font-size: 0.68rem;">
                                                    <i class="fas fa-clock me-1"></i>PO ({{ $product->preorder_days ?? 7 }} Hari) <i class="fas fa-edit ms-1 opacity-75"></i>
                                                </span>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-xs p-0 border-0 btn-quick-po"
                                                data-product-id="{{ $product->id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-is-preorder="0"
                                                data-preorder-days="{{ $product->preorder_days ?? 7 }}"
                                                title="Klik untuk ubah status PO">
                                                <span class="badge px-2 py-1" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; font-size: 0.68rem;">
                                                    <i class="fas fa-check-circle me-1"></i>Ready <i class="fas fa-edit ms-1 opacity-50"></i>
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $validMpStores = $product->marketplaceProducts->filter(function($mp) use ($product) {
                                        return empty($mp->marketplace_sku) || strtolower(trim($mp->marketplace_sku)) === strtolower(trim($product->sku));
                                    })->unique('store_id');
                                @endphp

                                @if ($validMpStores->isEmpty())
                                    <span class="badge bg-secondary text-white rounded-pill" style="font-size: 0.68rem;">
                                        <i class="fas fa-unlink me-1"></i> Belum Terhubung
                                    </span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($validMpStores as $mp)
                                            @php
                                                $chCode = strtolower($mp->store->channel->code ?? '');
                                                $badgeClass = match(true) {
                                                    str_contains($chCode, 'shopee') => 'badge-channel-shopee',
                                                    str_contains($chCode, 'tiktok') => 'badge-channel-tiktok',
                                                    str_contains($chCode, 'lazada') => 'badge-channel-lazada',
                                                    str_contains($chCode, 'tokopedia') => 'badge-channel-tokopedia',
                                                    default => 'bg-secondary text-white',
                                                };
                                                $iconClass = match(true) {
                                                    str_contains($chCode, 'shopee') => 'fas fa-shopping-bag',
                                                    str_contains($chCode, 'tiktok') => 'fab fa-tiktok',
                                                    str_contains($chCode, 'lazada') => 'fas fa-store',
                                                    str_contains($chCode, 'tokopedia') => 'fas fa-shopping-cart',
                                                    default => 'fas fa-store',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }} d-inline-flex align-items-center gap-1 rounded-pill" style="font-size: 0.65rem;" title="SKU MP: {{ $mp->marketplace_sku ?: $product->sku }}">
                                                <i class="{{ $iconClass }}"></i>
                                                {{ $mp->store->store_name ?? 'Marketplace' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('products.edit', $product->id) }}"
                                        class="btn btn-warning btn-sm rounded-3" title="Edit Produk">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @php
                                        $linkedStoreIds = $product->marketplaceProducts->pluck('store_id')->unique();
                                        $isFullyPublished =
                                            $connectedStoresCount > 0 &&
                                            $linkedStoreIds->count() >= $connectedStoresCount;
                                    @endphp
                                    @if ($isFullyPublished)
                                        <button class="btn btn-success btn-sm rounded-3" disabled
                                            title="Sudah terhubung semua">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('products.publish', $product->id) }}"
                                            class="btn btn-primary btn-sm rounded-3" title="Publish ke Marketplace">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="fas fa-box-open d-block mb-2 opacity-25 fs-2"></i>
                                Belum ada produk.
                                <a href="{{ route('products.create') }}"
                                    class="text-primary text-decoration-underline">Tambah produk pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($products->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small">
                    Halaman {{ $products->currentPage() }} dari {{ $products->lastPage() }}
                    &mdash; {{ $products->total() }} total produk
                </span>
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Quick Edit PO -->
<div class="modal fade" id="modalQuickPo" tabindex="-1" aria-labelledby="modalQuickPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header bg-light py-2 px-3">
                <h6 class="modal-title fw-bold text-dark" id="modalQuickPoLabel">
                    <i class="fas fa-clock me-1" style="color: #8b5cf6;"></i> Pengaturan Status PO
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formQuickPo">
                @csrf
                <input type="hidden" id="quickPoProductId" name="product_id">
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <small class="text-muted d-block fw-semibold" id="quickPoProductName"></small>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="quickIsPreorder" name="is_preorder" value="1">
                        <label class="form-check-label fw-bold text-dark small" for="quickIsPreorder">
                            Jadikan Pre-Order (PO)
                        </label>
                    </div>
                    <div id="quickPreorderDaysWrapper" style="display: none;">
                        <label for="quickPreorderDays" class="form-label form-label-sm fw-semibold text-dark">
                            Estimasi Waktu PO (Hari) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control form-control-sm" id="quickPreorderDays" name="preorder_days" min="1" value="7" placeholder="Contoh: 7">
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Estimasi waktu ini otomatis digunakan saat pemrosesan pesanan.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3" id="btnSaveQuickPo">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllProducts');
        const checkboxes = document.querySelectorAll('.product-select-checkbox');
        const btnBulkPublish = document.getElementById('btnBulkPublish');
        const selectedCountSpan = document.getElementById('selectedCount');

        function updateBulkButton() {
            const checkedCount = document.querySelectorAll('.product-select-checkbox:checked').length;
            if (checkedCount > 0) {
                selectedCountSpan.textContent = checkedCount;
                btnBulkPublish.classList.remove('d-none');
            } else {
                btnBulkPublish.classList.add('d-none');
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkButton();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (!this.checked) {
                    if (selectAll) selectAll.checked = false;
                } else {
                    const allChecked = document.querySelectorAll('.product-select-checkbox:checked').length === checkboxes.length;
                    if (selectAll) selectAll.checked = allChecked;
                }
                updateBulkButton();
            });
        });

        if (btnBulkPublish) {
            btnBulkPublish.addEventListener('click', function () {
                const checkedBoxes = document.querySelectorAll('.product-select-checkbox:checked');
                if (checkedBoxes.length === 0) return;

                let url = "{{ route('products.bulk_publish') }}?";
                checkedBoxes.forEach((cb, index) => {
                    url += `ids[]=${cb.value}&`;
                });
                if (url.endsWith('&')) {
                    url = url.slice(0, -1);
                }
                window.location.href = url;
            });
        }

        const formAutoBundle = document.getElementById('formAutoBundle');
        if (formAutoBundle) {
            formAutoBundle.addEventListener('submit', function (e) {
                const checkedBoxes = document.querySelectorAll('.product-select-checkbox:checked');
                if (checkedBoxes.length > 0) {
                    checkedBoxes.forEach(cb => {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'ids[]';
                        hiddenInput.value = cb.value;
                        formAutoBundle.appendChild(hiddenInput);
                    });
                }
            });
        }

        // Quick PO Edit Modal Handlers
        $(document).on('click', '.btn-quick-po', function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            const isPreorder = $(this).data('is-preorder') == 1;
            const preorderDays = $(this).data('preorder-days') || 7;

            $('#quickPoProductId').val(productId);
            $('#quickPoProductName').text(productName);
            $('#quickIsPreorder').prop('checked', isPreorder);
            $('#quickPreorderDays').val(preorderDays);

            if (isPreorder) {
                $('#quickPreorderDaysWrapper').show();
            } else {
                $('#quickPreorderDaysWrapper').hide();
            }

            const modal = new bootstrap.Modal(document.getElementById('modalQuickPo'));
            modal.show();
        });

        $('#quickIsPreorder').on('change', function() {
            if (this.checked) {
                $('#quickPreorderDaysWrapper').slideDown(150);
            } else {
                $('#quickPreorderDaysWrapper').slideUp(150);
            }
        });

        $('#formQuickPo').on('submit', function(e) {
            e.preventDefault();
            const productId = $('#quickPoProductId').val();
            const isPreorder = $('#quickIsPreorder').is(':checked') ? 1 : 0;
            const preorderDays = $('#quickPreorderDays').val();
            const btn = $('#btnSaveQuickPo');

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: `/products/${productId}/quick-po`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    is_preorder: isPreorder,
                    preorder_days: preorderDays
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Simpan');
                    if (res.success) {
                        const modalEl = document.getElementById('modalQuickPo');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        
                        // Update badge in UI instantly
                        const container = $(`#po-badge-container-${productId}`);
                        const productName = $('#quickPoProductName').text();
                        if (res.is_preorder) {
                            const days = res.preorder_days || 7;
                            container.html(`
                                <button type="button" class="btn btn-xs p-0 border-0 btn-quick-po"
                                    data-product-id="${productId}"
                                    data-product-name="${productName}"
                                    data-is-preorder="1"
                                    data-preorder-days="${days}"
                                    title="Klik untuk ubah status PO">
                                    <span class="badge text-white px-2 py-1" style="background-color: #8b5cf6; font-size: 0.68rem;">
                                        <i class="fas fa-clock me-1"></i>PO (${days} Hari) <i class="fas fa-edit ms-1 opacity-75"></i>
                                    </span>
                                </button>
                            `);
                        } else {
                            container.html(`
                                <button type="button" class="btn btn-xs p-0 border-0 btn-quick-po"
                                    data-product-id="${productId}"
                                    data-product-name="${productName}"
                                    data-is-preorder="0"
                                    data-preorder-days="${res.preorder_days || 7}"
                                    title="Klik untuk ubah status PO">
                                    <span class="badge px-2 py-1" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; font-size: 0.68rem;">
                                        <i class="fas fa-check-circle me-1"></i>Ready <i class="fas fa-edit ms-1 opacity-50"></i>
                                    </span>
                                </button>
                            `);
                        }

                        // Tentukan icon & warna toast berdasarkan hasil sinkronisasi Shopee
                        let toastIcon = 'success';
                        if (res.shopee_fail > 0 && res.shopee_success === 0) {
                            toastIcon = 'warning';
                        } else if (res.shopee_fail > 0) {
                            toastIcon = 'warning';
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: toastIcon,
                                title: res.message,
                                showConfirmButton: false,
                                timer: res.shopee_fail > 0 ? 4000 : 2500,
                                timerProgressBar: true,
                            });
                        }
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Simpan');
                    alert(xhr.responseJSON?.message || 'Gagal memperbarui status PO');
                }
            });
        });
    });
</script>
