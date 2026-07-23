{{-- Main Table Card --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-warning bg-opacity-10 d-flex align-items-center justify-content-between py-2 px-3">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-unlink text-warning me-2"></i>Daftar Produk Belum Ditautkan
            </h6>
            <small class="text-muted">Hubungkan produk marketplace Anda dengan Master Product agar stok
                tersinkronisasi</small>
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
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('marketplace_products.print_report', request()->all()) }}" target="_blank"
                                class="btn btn-outline-dark btn-sm" title="Cetak Laporan">
                                <i class="fas fa-print"></i>
                            </a>
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
                                        <div class="fw-semibold small">{{ $product->name }}</div>
                                        <div class="text-muted" style="font-size:0.7rem;">ID:
                                            {{ $product->marketplace_product_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-monospace small">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="text-center font-monospace small fw-bold">{{ number_format($product->stock) }}
                            </td>
                            <td>
                                <span
                                    class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25 small">
                                    <i class="fas fa-unlink me-1"></i>Belum Ditautkan
                                </span>
                            </td>
                            <td class="small">
                                <div class="fw-semibold">{{ $product->store->store_name }}</div>
                                <span class="badge bg-secondary bg-opacity-75 mt-1" style="font-size:0.65rem;">
                                    {{ $product->store->channel->name }}
                                </span>
                            </td>
                            <td class="pe-3 text-center">
                                @php
                                    $matchingMaster = $product->marketplace_sku 
                                        ? $masterProducts->firstWhere('sku', trim($product->marketplace_sku)) 
                                        : null;
                                @endphp

                                @if ($matchingMaster)
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <form action="{{ route('marketplace_products.link', $product->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="master_product_id" value="{{ $matchingMaster->id }}">
                                            <button type="submit" class="btn btn-success btn-sm py-1 px-2 fw-bold" style="font-size:0.72rem;">
                                                <i class="fas fa-link me-1"></i>Tautkan ke Master
                                            </button>
                                        </form>
                                        <span class="text-muted text-center" style="font-size:0.65rem; max-width:180px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="SKU cocok dengan Master: {{ $matchingMaster->name }}">
                                            Cocok: {{ $matchingMaster->name }}
                                        </span>
                                    </div>
                                @else
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
                                        action="{{ route('marketplace_products.link', $product->id) }}" method="POST"
                                        class="d-none mt-2 p-2 border rounded text-start">
                                        @csrf
                                        <select name="master_product_id" class="form-select form-select-sm mb-2" required>
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
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-box-open d-block mb-2 opacity-25" style="font-size:2rem;"></i>
                                Belum ada produk marketplace yang belum ditautkan
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
