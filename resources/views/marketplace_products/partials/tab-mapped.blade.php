{{-- ── Filter Card ────────────────────────────────────────── --}}
<div class="dashboard-card mb-3 py-3">
    <form method="GET" action="{{ route('marketplace_products.index') }}">
        @if (request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label form-label-sm fw-semibold mb-1">
                    <i class="fas fa-tag text-muted me-1"></i>Nama Barang
                </label>
                <input type="text" name="name" class="form-control form-control-sm"
                    placeholder="Cari nama barang..." value="{{ request('name') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label form-label-sm fw-semibold mb-1">
                    <i class="fas fa-barcode text-muted me-1"></i>SKU
                </label>
                <input type="text" name="sku" class="form-control form-control-sm"
                    placeholder="Cari SKU..." value="{{ request('sku') }}">
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label form-label-sm fw-semibold mb-1">
                    <i class="fas fa-layer-group text-muted me-1"></i>Channel
                </label>
                <select name="channel_id" class="form-select form-select-sm">
                    <option value="">Semua Channel</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->id }}"
                            {{ request('channel_id') == $channel->id ? 'selected' : '' }}>{{ $channel->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label form-label-sm fw-semibold mb-1">
                    <i class="fas fa-store text-muted me-1"></i>Toko
                </label>
                <select name="store_id" class="form-select form-select-sm">
                    <option value="">Semua Toko</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                            {{ $store->store_name }} ({{ $store->channel->name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                @if (request()->anyFilled(['name', 'sku', 'channel_id', 'store_id']))
                    <a href="{{ route('marketplace_products.index', request()->only('status')) }}"
                        class="btn btn-secondary btn-sm" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- ── Main Table Card ────────────────────────────────────── --}}
<div class="dashboard-card">
    <div class="card-header-line d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-link text-primary me-2"></i>Daftar Produk Sudah Ditautkan
            </h5>
            <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">Kelola singkronisasi stok dan pengaturan produk yang terhubung ke Master Product</p>
        </div>
        <span class="text-muted small">
            Menampilkan {{ $marketplaceProducts->firstItem() ?? 0 }}-{{ $marketplaceProducts->lastItem() ?? 0 }} dari
            {{ $marketplaceProducts->total() }} produk
        </span>
    </div>

    <div class="table-responsive rounded border border-secondary border-opacity-10">
        <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">SKU Marketplace</th>
                    <th>Nama Produk</th>
                    <th>Harga Jual</th>
                    <th class="text-center">Stok</th>
                    <th>Status Master</th>
                    <th>Toko / Channel</th>
                    <th class="pe-3 text-center" style="width: 250px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marketplaceProducts as $product)
                    <tr>
                        {{-- SKU --}}
                        <td class="ps-3">
                            @if ($product->marketplace_sku)
                                <code class="text-primary font-monospace" style="font-size:0.75rem;">{{ $product->marketplace_sku }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- Marketplace Product details --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                        class="rounded border border-secondary border-opacity-10"
                                        style="width: 40px; height: 40px; object-fit: cover; background-color: rgba(255, 255, 255, 0.05);">
                                @else
                                    <div class="rounded border border-secondary border-opacity-10 d-flex align-items-center justify-content-center bg-dark text-muted"
                                        style="width: 40px; height: 40px; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.02);">
                                        <i class="fas fa-image"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-white" style="font-size:0.82rem;">{{ $product->name }}</div>
                                    <div class="text-secondary extra-small mt-0.5">ID: {{ $product->marketplace_product_id }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Harga --}}
                        <td class="font-monospace" style="font-size:0.78rem;">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </td>

                        {{-- Stok --}}
                        <td class="text-center font-monospace" style="font-size:0.78rem;">
                            <span class="fw-bold text-white">{{ number_format($product->stock) }}</span>
                            @if ($product->masterProduct && $product->sync_stock && $product->safety_stock > 0)
                                <div class="text-muted extra-small mt-0.5" style="font-size: 0.65rem;">
                                    (Master: {{ $product->masterProduct->stock }} | Safety: {{ $product->safety_stock }})
                                </div>
                            @endif
                        </td>

                        {{-- Status Master --}}
                        <td>
                            @if ($product->masterProduct)
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 align-self-start" style="font-size:0.68rem;">
                                        <i class="fas fa-link me-1"></i>Tertaut
                                    </span>
                                    <span class="text-light small fw-medium" style="font-size: 0.78rem;">
                                        {{ $product->masterProduct->name }}
                                    </span>
                                    @if ($product->sync_stock)
                                        <span class="text-info extra-small" style="font-size: 0.68rem;">
                                            <i class="fas fa-sync-alt me-1"></i>Sync Aktif (Safety: {{ $product->safety_stock }})
                                        </span>
                                    @else
                                        <span class="text-muted extra-small" style="font-size: 0.68rem;">
                                            <i class="fas fa-sync-alt-slash me-1"></i>Sync Mati
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        {{-- Store & Channel --}}
                        <td>
                            <div class="fw-semibold text-white" style="font-size:0.8rem;">{{ $product->store->store_name }}</div>
                            <div class="mt-1">
                                <span class="channel-badge channel-{{ $product->store->channel->code ?? '' }}"
                                    style="font-size: 0.65rem; padding: 0.12rem 0.4rem; line-height: 1.4;">
                                    @if (($product->store->channel->code ?? '') === 'shopee')
                                        <i class="fab fa-shopify"></i>
                                    @elseif(($product->store->channel->code ?? '') === 'tiktok')
                                        <i class="fab fa-tiktok"></i>
                                    @endif
                                    {{ $product->store->channel->name }}
                                </span>
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="pe-3 text-center">
                            <div class="d-flex gap-1 justify-content-center align-items-center">
                                <button type="button" class="btn btn-secondary btn-action-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#settingsModal-{{ $product->id }}"
                                    title="Pengaturan Stok">
                                    <i class="fas fa-cog text-warning"></i>
                                </button>

                                <a href="{{ route('products.publish', $product->masterProduct->id) }}"
                                    class="btn btn-secondary btn-action-sm" title="Salin Toko">
                                    <i class="fas fa-copy text-primary"></i>
                                </a>

                                <form action="{{ route('marketplace_products.unlink', $product->id) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('Batal tautkan produk marketplace ini dari Master Product?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-action-sm" title="Batal Tautkan">
                                        <i class="fas fa-link-slash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Modal Pengaturan Stok -->
                            <div class="modal fade" id="settingsModal-{{ $product->id }}" tabindex="-1"
                                aria-labelledby="settingsModalLabel-{{ $product->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content"
                                        style="background: var(--bg-card); border: 1px solid var(--border); text-align: left;">
                                        <div class="modal-header"
                                            style="border-bottom: 1px solid var(--border);">
                                            <h5 class="modal-title fw-bold text-primary"
                                                id="settingsModalLabel-{{ $product->id }}">
                                                <i class="fas fa-cog me-2"></i> Pengaturan Stok
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form
                                            action="{{ route('marketplace_products.update_settings', $product->id) }}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body py-3">
                                                <div class="fw-semibold text-white fs-6 mb-3">
                                                    {{ $product->name }}
                                                </div>
                                                <hr style="border-color: var(--border);">
                                                <div class="mb-3">
                                                    <label
                                                        class="form-label d-block fw-semibold text-secondary mb-2" style="font-size:0.8rem;">Sinkronisasi Stok</label>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="sync_stock"
                                                            id="syncStock-{{ $product->id }}" value="1"
                                                            {{ $product->sync_stock ? 'checked' : '' }}
                                                            style="cursor: pointer;">
                                                        <label class="form-check-label text-light"
                                                            for="syncStock-{{ $product->id }}"
                                                            style="cursor: pointer; font-size:0.8rem;">
                                                            Otomatis sinkronkan stok dari Master Product
                                                        </label>
                                                    </div>
                                                    <div class="form-text mt-1"
                                                        style="font-size: 0.75rem; color: var(--text-muted);">
                                                        Jika dinonaktifkan, perubahan stok Master Product tidak
                                                        akan didorong ke marketplace ini.
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="safetyStock-{{ $product->id }}"
                                                        class="form-label fw-semibold text-secondary mb-2" style="font-size:0.8rem;">Stok Pengaman (Safety Stock)</label>
                                                    <input type="number"
                                                        class="form-control form-control-sm form-control-dark"
                                                        name="safety_stock"
                                                        id="safetyStock-{{ $product->id }}" min="0"
                                                        value="{{ $product->safety_stock ?? 0 }}" required>
                                                    <div class="form-text mt-1"
                                                        style="font-size: 0.75rem; color: var(--text-muted);">
                                                        Stok yang dikirim ke toko = <strong>Stok Master
                                                            ({{ $product->masterProduct->stock ?? 0 }}) - Stok
                                                            Pengaman</strong>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer"
                                                style="border-top: 1px solid var(--border);">
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                    data-bs-dismiss="modal">Batal</button>
                                                <button type="submit"
                                                    class="btn btn-primary btn-sm">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="fas fa-box-open d-block mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                            Belum ada produk marketplace yang sudah ditautkan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($marketplaceProducts->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted" style="font-size: 0.75rem;">
                Halaman {{ $marketplaceProducts->currentPage() }} dari {{ $marketplaceProducts->lastPage() }}
                &mdash; {{ $marketplaceProducts->total() }} total produk
            </span>
            {{ $marketplaceProducts->links() }}
        </div>
    @endif
</div>
