{{-- Main Table Card --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-info bg-opacity-10 d-flex align-items-center justify-content-between py-2 px-3">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-store text-info me-2"></i>Daftar Semua Produk Marketplace
            </h6>
            <small class="text-muted">Kelola seluruh produk yang terdaftar di akun marketplace Anda</small>
        </div>
        <span class="text-muted small">
            Menampilkan {{ $marketplaceProducts->firstItem() ?? 0 }}-{{ $marketplaceProducts->lastItem() ?? 0 }}
            dari {{ $marketplaceProducts->total() }} produk
        </span>
    </div>
    <div class="card-body p-0">
        {{-- Filter Card --}}
        <div class="card border-0 shadow-sm mb-3 border-top-0 rounded-top-0">
            <div class="card-body py-2 px-3">
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
                                        {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}</option>
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
                                    <option value="{{ $store->id }}"
                                        {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }} ({{ $store->channel->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            @if (request()->anyFilled(['name', 'sku', 'channel_id', 'store_id']))
                                <a href="{{ route('marketplace_products.index', request()->only('status')) }}"
                                    class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">SKU Marketplace</th>
                        <th>Nama Produk</th>
                        <th>Harga Jual</th>
                        <th class="text-center">Stok</th>
                        <th>Status Master</th>
                        <th>Toko / Channel</th>
                        <th class="pe-3 text-center" style="width:250px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketplaceProducts as $product)
                        <tr>
                            <td class="ps-3">
                                @if ($product->marketplace_sku)
                                    <code
                                        class="text-primary font-monospace small">{{ $product->marketplace_sku }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="rounded border" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                            style="width:40px;height:40px;flex-shrink:0;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold small">{{ $product->name }}</div>
                                        <div class="text-muted" style="font-size:0.7rem;">ID:
                                            {{ $product->marketplace_product_id }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="font-monospace small">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>

                            <td class="text-center font-monospace small">
                                <span class="fw-bold">{{ number_format($product->stock) }}</span>
                                @if ($product->masterProduct && $product->sync_stock && $product->safety_stock > 0)
                                    <div class="text-muted" style="font-size:0.65rem;">
                                        (Master: {{ $product->masterProduct->stock }} | Safety:
                                        {{ $product->safety_stock }})
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if ($product->masterProduct)
                                    <div class="d-flex flex-column gap-1">
                                        <span
                                            class="badge bg-success-subtle text-success border border-success border-opacity-25 align-self-start small">
                                            <i class="fas fa-link me-1"></i>Tertaut
                                        </span>
                                        <span class="small fw-medium">{{ $product->masterProduct->name }}</span>
                                        @if ($product->sync_stock)
                                            <span class="text-info small">
                                                <i class="fas fa-sync-alt me-1"></i>Sync Aktif (Safety:
                                                {{ $product->safety_stock }})
                                            </span>
                                        @else
                                            <span class="text-muted small"><i class="fas fa-ban me-1"></i>Sync
                                                Mati</span>
                                        @endif
                                    </div>
                                @else
                                    <span
                                        class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25 small">
                                        <i class="fas fa-unlink me-1"></i>Belum Ditautkan
                                    </span>
                                @endif
                            </td>

                            <td class="small">
                                <div class="fw-semibold">{{ $product->store->store_name }}</div>
                                <span class="badge bg-secondary bg-opacity-75 mt-1" style="font-size:0.65rem;">
                                    {{ $product->store->channel->name }}
                                </span>
                            </td>

                            <td class="pe-3 text-center">
                                @if (!$product->masterProduct)
                                    <div class="d-flex gap-1 justify-content-center align-items-center">
                                        <form action="{{ route('marketplace_products.promote', $product->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm py-0 px-2"
                                                onclick="return confirm('Jadikan produk ini sebagai Master Product baru?');"
                                                title="Jadikan Master" style="font-size:0.72rem;">
                                                <i class="fas fa-star text-warning me-1"></i>Master
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-info btn-sm py-0 px-2"
                                            onclick="document.getElementById('link-form-{{ $product->id }}').classList.toggle('d-none')"
                                            title="Tautkan ke Master" style="font-size:0.72rem;">
                                            <i class="fas fa-link me-1"></i>Tautkan
                                        </button>
                                        <form
                                            action="{{ route('marketplace_products.clone_and_publish', $product->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-2"
                                                title="Salin ke Toko Lain" style="font-size:0.72rem;">
                                                <i class="fas fa-copy me-1"></i>Salin ke Toko Lain
                                            </button>
                                        </form>
                                    </div>
                                    <form id="link-form-{{ $product->id }}"
                                        action="{{ route('marketplace_products.link', $product->id) }}"
                                        method="POST" class="d-none mt-2 p-2 border rounded text-start">
                                        @csrf
                                        <select name="master_product_id" class="form-select form-select-sm mb-2"
                                            required>
                                            <option value="">-- Pilih Master --</option>
                                            @foreach ($masterProducts as $master)
                                                <option value="{{ $master->id }}">{{ $master->name }} (SKU:
                                                    {{ $master->sku }})</option>
                                            @endforeach
                                        </select>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success btn-sm flex-grow-1"
                                                style="font-size:0.72rem;">Simpan</button>
                                            <button type="button" class="btn btn-secondary btn-sm flex-grow-1"
                                                style="font-size:0.72rem;"
                                                onclick="document.getElementById('link-form-{{ $product->id }}').classList.add('d-none')">Batal</button>
                                        </div>
                                    </form>
                                @else
                                    <div class="d-flex gap-1 justify-content-center align-items-center">
                                        <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#settingsModal-{{ $product->id }}"
                                            title="Pengaturan Stok" style="font-size:0.72rem;">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <a href="{{ route('products.publish', $product->masterProduct->id) }}"
                                            class="btn btn-outline-secondary btn-sm py-0 px-2" title="Salin Toko"
                                            style="font-size:0.72rem;">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <form action="{{ route('marketplace_products.unlink', $product->id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Batal tautkan produk marketplace ini dari Master Product?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2"
                                                title="Batal Tautkan" style="font-size:0.72rem;">
                                                <i class="fas fa-link-slash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Modal Pengaturan Stok -->
                                    <div class="modal fade" id="settingsModal-{{ $product->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold text-primary">
                                                        <i class="fas fa-cog me-2"></i>Pengaturan Stok
                                                    </h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form
                                                    action="{{ route('marketplace_products.update_settings', $product->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body py-3">
                                                        <div class="fw-semibold mb-3 small">{{ $product->name }}</div>
                                                        <hr>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Sinkronisasi
                                                                Stok</label>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="sync_stock"
                                                                    id="syncStock-{{ $product->id }}" value="1"
                                                                    {{ $product->sync_stock ? 'checked' : '' }}>
                                                                <label class="form-check-label small"
                                                                    for="syncStock-{{ $product->id }}">
                                                                    Otomatis sinkronkan stok dari Master Product
                                                                </label>
                                                            </div>
                                                            <div class="form-text small">Jika dinonaktifkan, perubahan
                                                                stok Master Product tidak akan didorong ke marketplace
                                                                ini.</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="safetyStock-{{ $product->id }}"
                                                                class="form-label fw-semibold small">Stok Pengaman
                                                                (Safety Stock)</label>
                                                            <input type="number" class="form-control form-control-sm"
                                                                name="safety_stock"
                                                                id="safetyStock-{{ $product->id }}" min="0"
                                                                value="{{ $product->safety_stock ?? 0 }}" required>
                                                            <div class="form-text small">
                                                                Stok yang dikirim ke toko = <strong>Stok Master
                                                                    ({{ $product->masterProduct->stock ?? 0 }}) - Stok
                                                                    Pengaman</strong>.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm"
                                                            data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit"
                                                            class="btn btn-primary btn-sm">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-box-open d-block mb-2 opacity-25" style="font-size:2rem;"></i>
                                Belum ada produk marketplace
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($marketplaceProducts->hasPages())
            <div class="d-flex justify-content-between align-items-center px-3 py-2">
                <span class="text-muted small">
                    Halaman {{ $marketplaceProducts->currentPage() }} dari {{ $marketplaceProducts->lastPage() }}
                    &mdash; {{ $marketplaceProducts->total() }} total produk
                </span>
                {{ $marketplaceProducts->links() }}
            </div>
        @endif
    </div>
</div>
