<div class="dashboard-card mb-3 py-3">
    <form method="GET" action="{{ route('products.index') }}" id="filterProdukForm">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label form-label-sm fw-semibold mb-1">
                    <i class="fas fa-layer-group text-muted me-1"></i>Filter Channel
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
            <div class="col-md-4">
                <label class="form-label form-label-sm fw-semibold mb-1">
                    <i class="fas fa-store text-muted me-1"></i>Filter Akun / Toko
                </label>
                <select name="store_id" class="form-select form-select-sm">
                    <option value="">-- Semua Toko --</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                            {{ $store->store_name }} ({{ $store->channel->name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i>Terapkan
                </button>
                @if (request()->anyFilled(['channel_id', 'store_id']))
                    <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                @endif
            </div>
            <div class="col-md ms-auto text-end">
                <span class="text-muted" style="font-size:0.75rem;">
                    Menampilkan <strong class="text-white">{{ $products->total() }}</strong> produk
                </span>
            </div>
        </div>
    </form>
</div>

{{-- Tabel Produk --}}
<div class="dashboard-card">
    <div class="card-header-line d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-box-open me-2 text-primary"></i>Daftar Master Produk</h5>
            <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">
                Kelola produk, harga, stok, dan koneksi marketplace
            </p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-plus me-1"></i>Tambah Produk
        </a>
    </div>

    <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
        <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
            <thead>
                <tr>
                    <th>SKU VARIASI</th>
                    <th>NAMA BARANG</th>
                    <th>SKU INDUK</th>
                    <th>KATEGORI / MERK</th>
                    <th class="text-center">VARIASI</th>
                    <th class="text-end">HARGA (HPP / JUAL)</th>
                    <th class="text-center">STOK</th>
                    <th class="text-center">STATUS</th>
                    <th>MARKETPLACE</th>
                    <th class="text-center" style="width:110px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <code class="text-primary font-monospace"
                                style="font-size:0.75rem;">{{ $product->sku }}</code>
                        </td>
                        <td>
                            <div class="text-white fw-semibold text-wrap lh-sm"
                                style="max-width: 220px; font-size: 0.78rem;" title="{{ $product->name }}">
                                {{ $product->name }}
                            </div>
                            @if ($product->sub_kategori)
                                <div class="text-muted mt-0.5" style="font-size: 0.68rem;">{{ $product->sub_kategori }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if ($product->sku_induk)
                                <code class="text-muted font-monospace"
                                    style="font-size: 0.72rem;">{{ $product->sku_induk }}</code>
                            @else
                                <span class="text-muted opacity-50">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="lh-sm">
                                <div class="text-white-50" style="font-size: 0.75rem;">
                                    {{ $product->category->name ?? '—' }}
                                </div>
                                <div class="text-muted mt-0.5" style="font-size: 0.68rem;">
                                    @if ($product->brand)
                                        <span class="text-muted">Merk:</span> <span
                                            class="text-white-50">{{ $product->brand->name }}</span>
                                    @else
                                        <span class="text-muted">Merk:</span> <span
                                            class="text-muted opacity-50">—</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="lh-sm">
                                <div style="font-size: 0.72rem;">
                                    <span class="text-muted">Sz:</span> <strong
                                        class="text-white font-monospace">{{ $product->ukuran ?? '—' }}</strong>
                                </div>
                                <div class="mt-1" style="font-size: 0.72rem;">
                                    <span class="text-muted">Wrn:</span> <strong
                                        class="text-white-50">{{ $product->warna ?? '—' }}</strong>
                                </div>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="lh-sm">
                                <div style="font-size: 0.72rem;">
                                    <span class="text-muted">HPP:</span> <span
                                        class="font-monospace text-muted">{{ $product->cost_price ? 'Rp ' . number_format($product->cost_price, 0, ',', '.') : '—' }}</span>
                                </div>
                                <div class="mt-1" style="font-size: 0.75rem;">
                                    <span class="text-muted" style="font-size: 0.72rem;">Jual:</span> <strong
                                        class="font-monospace text-info">Rp
                                        {{ number_format($product->price, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span
                                class="badge {{ $product->stock <= $product->min_stock ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} font-monospace"
                                style="font-size: 0.75rem; padding: 3px 8px; font-weight: 600;">
                                {{ number_format($product->stock) }}
                            </span>
                            @if ($product->stock <= $product->min_stock)
                                <div class="text-danger mt-1 fw-bold"
                                    style="font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.02em;">stok
                                    rendah</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($product->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle"
                                    style="font-size: 0.68rem; padding: 3px 6px;">Aktif</span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle"
                                    style="font-size: 0.68rem; padding: 3px 6px;">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            @if ($product->marketplaceProducts->isEmpty())
                                <span
                                    class="badge bg-secondary-subtle text-muted border border-secondary-subtle rounded-pill"
                                    style="font-size: 0.68rem; padding: 3px 8px;">
                                    <i class="fas fa-unlink me-1"></i> Belum Terhubung
                                </span>
                            @else
                                <div class="d-flex flex-wrap gap-1" style="max-width: 180px;">
                                    @foreach ($product->marketplaceProducts->unique('store_id') as $mp)
                                        @php
                                            $chCode = $mp->store->channel->code ?? '';
                                            $badgeStyle = '';
                                            if ($chCode === 'shopee') {
                                                $badgeStyle =
                                                    'background-color: rgba(238, 77, 45, 0.08); color: #ff5722; border: 1px solid rgba(238, 77, 45, 0.2);';
                                            } elseif ($chCode === 'tiktok') {
                                                $badgeStyle =
                                                    'background-color: rgba(255, 255, 255, 0.06); color: #eaeaea; border: 1px solid rgba(255, 255, 255, 0.15);';
                                            } else {
                                                $badgeStyle =
                                                    'background-color: rgba(0, 177, 86, 0.08); color: #10b981; border: 1px solid rgba(0, 177, 86, 0.2);';
                                            }
                                        @endphp
                                        <span class="badge d-inline-flex align-items-center gap-1 rounded-pill"
                                            style="font-size: 0.65rem; padding: 3px 8px; font-weight: 500; {{ $badgeStyle }}">
                                            @if ($chCode === 'shopee')
                                                <i class="fab fa-shopify"></i>
                                            @elseif ($chCode === 'tiktok')
                                                <i class="fab fa-tiktok"></i>
                                            @else
                                                <i class="fas fa-store"></i>
                                            @endif
                                            {{ $mp->store->store_name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('products.edit', $product->id) }}"
                                    class="btn btn-warning btn-action-sm" title="Edit Produk">
                                    <i class="fas fa-pen"></i>
                                </a>
                                @php
                                    $linkedStoreIds = $product->marketplaceProducts->pluck('store_id')->unique();
                                    $isFullyPublished =
                                        $connectedStoresCount > 0 && $linkedStoreIds->count() >= $connectedStoresCount;
                                @endphp
                                @if ($isFullyPublished)
                                    <button class="btn btn-action-sm btn-success" disabled
                                        title="Sudah terhubung semua" style="opacity: 0.65;">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                @else
                                    <a href="{{ route('products.publish', $product->id) }}"
                                        class="btn btn-primary btn-action-sm" title="Publish ke Marketplace">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="fas fa-box-open d-block mb-2 opacity-25" style="font-size:2rem;"></i>
                            Belum ada produk.
                            <a href="{{ route('products.create') }}" class="text-primary">Tambah produk pertama</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($products->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted" style="font-size:0.75rem;">
                Halaman {{ $products->currentPage() }} dari {{ $products->lastPage() }}
                &mdash; {{ $products->total() }} total produk
            </span>
            {{ $products->links() }}
        </div>
    @endif
</div>
