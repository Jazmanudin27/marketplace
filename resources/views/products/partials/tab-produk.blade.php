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
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3 rounded-3">
            <i class="fas fa-plus me-1"></i>Tambah Produk
        </a>
    </div>

    <div class="card-body p-3">
        <div class="card border shadow-sm mb-3">
            <div class="card-body py-2 px-3">
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
                                    <option value="{{ $store->id }}"
                                        {{ request('store_id') == $store->id ? 'selected' : '' }}>
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
                        <th>SKU VARIASI</th>
                        <th>NAMA BARANG</th>
                        <th>SKU INDUK</th>
                        <th>KATEGORI / MERK</th>
                        <th class="text-center">VARIASI</th>
                        <th class="text-end">HARGA (HPP / JUAL)</th>
                        <th class="text-center">STOK</th>
                        <th class="text-center">STATUS</th>
                        <th>MARKETPLACE</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <code class="text-primary font-monospace">{{ $product->sku }}</code>
                            </td>
                            <td>
                                <div class="text-dark fw-semibold text-wrap lh-sm" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </div>
                                @if ($product->sub_kategori)
                                    <small class="text-muted d-block mt-1">{{ $product->sub_kategori }}</small>
                                @endif
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
                                @if ($product->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if ($product->marketplaceProducts->isEmpty())
                                    <span class="badge bg-secondary text-white rounded-pill">
                                        <i class="fas fa-unlink me-1"></i> Belum Terhubung
                                    </span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($product->marketplaceProducts->unique('store_id') as $mp)
                                            @php
                                                $chCode = $mp->store->channel->code ?? '';
                                                $badgeClass = 'bg-success text-white';
                                                if ($chCode === 'shopee') {
                                                    $badgeClass = 'bg-danger text-white';
                                                } elseif ($chCode === 'tiktok') {
                                                    $badgeClass = 'bg-dark text-white';
                                                }
                                            @endphp
                                            <span
                                                class="badge {{ $badgeClass }} d-inline-flex align-items-center gap-1 rounded-pill">
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
                            <td colspan="10" class="text-center text-muted py-5">
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
