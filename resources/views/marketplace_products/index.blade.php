@extends('layouts.app')
@section('title', 'Produk Marketplace')
@section('page-title', 'Produk Marketplace')

@section('content')
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Status Tabs Row --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold text-white"><i class="fas fa-store text-primary me-2"></i>Produk Marketplace</h4>
            <span class="badge bg-secondary py-1.5 px-3 rounded-pill">{{ $marketplaceProducts->total() }} Item</span>
        </div>

        <div class="bg-dark p-1.5 rounded-pill d-inline-flex border" style="border-color: var(--border) !important;">
            <a href="{{ route('marketplace_products.index', request()->except(['status', 'page'])) }}"
                class="btn rounded-pill px-4 btn-sm fw-semibold {{ !request('status') ? 'btn-primary shadow-sm text-white' : 'btn-link text-secondary text-decoration-none' }}">
                Semua
            </a>
            <a href="{{ route('marketplace_products.index', array_merge(request()->except('page'), ['status' => 'unmapped'])) }}"
                class="btn rounded-pill px-4 btn-sm fw-semibold {{ request('status') === 'unmapped' ? 'btn-primary shadow-sm text-white' : 'btn-link text-secondary text-decoration-none' }}">
                Belum Ditautkan
            </a>
            <a href="{{ route('marketplace_products.index', array_merge(request()->except('page'), ['status' => 'mapped'])) }}"
                class="btn rounded-pill px-4 btn-sm fw-semibold {{ request('status') === 'mapped' ? 'btn-primary shadow-sm text-white' : 'btn-link text-secondary text-decoration-none' }}">
                Sudah Ditautkan
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('marketplace_products.index') }}" class="row g-3 align-items-end">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Nama Barang</label>
                    <input type="text" name="name" class="form-control form-control-sm form-control-dark"
                        placeholder="Cari nama barang..." value="{{ request('name') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">SKU</label>
                    <input type="text" name="sku" class="form-control form-control-sm form-control-dark"
                        placeholder="Cari SKU..." value="{{ request('sku') }}">
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Channel</label>
                    <select name="channel_id" class="form-select form-select-sm form-select-dark">
                        <option value="">Semua Channel</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}"
                                {{ request('channel_id') == $channel->id ? 'selected' : '' }}>{{ $channel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Toko</label>
                    <select name="store_id" class="form-select form-select-sm form-select-dark">
                        <option value="">Semua Toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" style="height: 31px;">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    @if (request()->anyFilled(['name', 'sku', 'channel_id', 'store_id']))
                        <a href="{{ route('marketplace_products.index', request()->only('status')) }}"
                            class="btn btn-sm btn-outline-secondary"
                            style="height: 31px; display: flex; align-items: center; justify-content: center; padding: 0 10px;"
                            title="Reset Filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h6 class="mb-0 fw-semibold">
                <i class="fas fa-store me-2 text-primary"></i>Daftar Produk Marketplace
            </h6>
            <span class="text-muted small">
                Menampilkan {{ $marketplaceProducts->firstItem() ?? 0 }}-{{ $marketplaceProducts->lastItem() ?? 0 }} dari {{ $marketplaceProducts->total() }} produk
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 15%;">Toko / Channel</th>
                            <th>Nama Produk Marketplace</th>
                            <th>SKU</th>
                            <th>Harga Jual</th>
                            <th class="text-center" style="width: 10%;">Stok</th>
                            <th>Status Master</th>
                            <th class="pe-3 text-center" style="width: 20%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($marketplaceProducts as $product)
                            <tr>
                                {{-- Store & Channel --}}
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $product->store->store_name }}</div>
                                    <div class="mt-1">
                                        <span class="channel-badge channel-{{ $product->store->channel->code ?? '' }}" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; line-height: 1;">
                                            @if (($product->store->channel->code ?? '') === 'shopee')
                                                <i class="fab fa-shopify"></i>
                                            @elseif(($product->store->channel->code ?? '') === 'tiktok')
                                                <i class="fab fa-tiktok"></i>
                                            @endif
                                            {{ $product->store->channel->name }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Marketplace Product details --}}
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                class="rounded shadow-sm border border-secondary"
                                                style="width: 45px; height: 45px; object-fit: cover; background-color: rgba(255, 255, 255, 0.05);">
                                        @else
                                            <div class="rounded border border-secondary d-flex align-items-center justify-content-center bg-dark text-muted shadow-sm"
                                                style="width: 45px; height: 45px; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.02);">
                                                <i class="fas fa-image fa-lg"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $product->name }}</div>
                                            <div class="text-secondary small mt-0.5" style="font-size: 0.75rem;">ID: {{ $product->marketplace_product_id }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- SKU --}}
                                <td>
                                    <code class="text-primary">{{ $product->marketplace_sku ?? '-' }}</code>
                                </td>

                                {{-- Harga --}}
                                <td>
                                    <span class="font-monospace">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                </td>

                                {{-- Stok --}}
                                <td class="text-center">
                                    <span class="fw-bold font-monospace">{{ number_format($product->stock) }}</span>
                                    @if ($product->masterProduct && $product->sync_stock && $product->safety_stock > 0)
                                        <div class="text-muted small mt-0.5" style="font-size: 0.7rem;">
                                            (Master: {{ $product->masterProduct->stock }} | Safety: {{ $product->safety_stock }})
                                        </div>
                                    @endif
                                </td>

                                {{-- Status Master --}}
                                <td>
                                    @if ($product->masterProduct)
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 align-self-start">
                                                <i class="fas fa-link me-1"></i>Tertaut
                                            </span>
                                            <span class="text-light small fw-medium" style="font-size: 0.8rem;">
                                                {{ $product->masterProduct->name }}
                                            </span>
                                            @if ($product->sync_stock)
                                                <span class="text-info small" style="font-size: 0.72rem;">
                                                    <i class="fas fa-sync-alt me-1"></i>Sync Aktif (Safety: {{ $product->safety_stock }})
                                                </span>
                                            @else
                                                <span class="text-muted small" style="font-size: 0.72rem;">
                                                    <i class="fas fa-sync-alt-slash me-1"></i>Sync Mati
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25">
                                            <i class="fas fa-unlink me-1"></i>Belum Ditautkan
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="pe-3 text-center">
                                    @if (!$product->masterProduct)
                                        <div class="d-flex gap-1 justify-content-center align-items-center">
                                            <!-- Jadikan Master -->
                                            <form action="{{ route('marketplace_products.promote', $product->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary"
                                                    onclick="return confirm('Jadikan produk ini sebagai Master Product baru?');">
                                                    <i class="fas fa-star"></i> Jadikan Master
                                                </button>
                                            </form>

                                            <!-- Tautkan ke Master -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="document.getElementById('link-form-{{ $product->id }}').classList.toggle('d-none')">
                                                <i class="fas fa-link"></i> Tautkan
                                            </button>

                                            <!-- Salin ke Toko Lain -->
                                            <form action="{{ route('marketplace_products.clone_and_publish', $product->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-copy"></i> Salin Toko
                                                </button>
                                            </form>
                                        </div>

                                        <form id="link-form-{{ $product->id }}"
                                            action="{{ route('marketplace_products.link', $product->id) }}"
                                            method="POST"
                                            class="d-none mt-2 p-2 border rounded text-start"
                                            style="background: rgba(255, 255, 255, 0.02); border-color: var(--border) !important;">
                                            @csrf
                                            <select name="master_product_id"
                                                class="form-select form-select-sm form-select-dark mb-2" required>
                                                <option value="">-- Pilih Master --</option>
                                                @foreach ($masterProducts as $master)
                                                    <option value="{{ $master->id }}">{{ $master->name }} (SKU: {{ $master->sku }})</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-success btn-sm flex-grow-1 py-0.5" style="font-size: 0.75rem;">Simpan</button>
                                                <button type="button" class="btn btn-secondary btn-sm flex-grow-1 py-0.5" style="font-size: 0.75rem;"
                                                    onclick="document.getElementById('link-form-{{ $product->id }}').classList.add('d-none')">Batal</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="d-flex gap-1 justify-content-center align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#settingsModal-{{ $product->id }}">
                                                <i class="fas fa-cog"></i> Pengaturan
                                            </button>

                                            <a href="{{ route('products.publish', $product->masterProduct->id) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-copy"></i> Salin Toko
                                            </a>

                                            <form action="{{ route('marketplace_products.unlink', $product->id) }}"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Batal tautkan produk marketplace ini dari Master Product?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-link-slash"></i> Batal Tautkan
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal Pengaturan Stok -->
                                        <div class="modal fade" id="settingsModal-{{ $product->id }}" tabindex="-1"
                                            aria-labelledby="settingsModalLabel-{{ $product->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content"
                                                    style="background: var(--bg-card); border: 1px solid var(--border); text-align: left;">
                                                    <div class="modal-header" style="border-bottom: 1px solid var(--border);">
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
                                                                <label class="form-label d-block fw-semibold text-secondary mb-2">Sinkronisasi Stok</label>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="sync_stock" id="syncStock-{{ $product->id }}"
                                                                        value="1"
                                                                        {{ $product->sync_stock ? 'checked' : '' }}
                                                                        style="cursor: pointer;">
                                                                    <label class="form-check-label text-light"
                                                                        for="syncStock-{{ $product->id }}"
                                                                        style="cursor: pointer;">
                                                                        Otomatis sinkronkan stok dari Master Product
                                                                    </label>
                                                                </div>
                                                                <div class="form-text mt-1" style="font-size: 0.75rem; color: var(--text-muted);">
                                                                    Jika dinonaktifkan, perubahan stok Master Product tidak akan didorong ke marketplace ini.
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="safetyStock-{{ $product->id }}" class="form-label fw-semibold text-secondary mb-2">Stok Pengaman (Safety Stock)</label>
                                                                <input type="number" class="form-control form-control-sm form-control-dark"
                                                                    name="safety_stock" id="safetyStock-{{ $product->id }}" min="0"
                                                                    value="{{ $product->safety_stock ?? 0 }}" required>
                                                                <div class="form-text mt-1" style="font-size: 0.75rem; color: var(--text-muted);">
                                                                    Stok yang dikirim ke toko = <strong>Stok Master ({{ $product->masterProduct->stock ?? 0 }}) - Stok Pengaman</strong>.
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-top: 1px solid var(--border);">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
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
                                <td colspan="7" class="text-center text-secondary py-5">
                                    <i class="fas fa-box-open d-block mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                    Belum ada produk marketplace
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($marketplaceProducts->hasPages())
            <div class="card-footer border-top-0 py-3 d-flex justify-content-center">
                {{ $marketplaceProducts->links() }}
            </div>
        @endif
    </div>
@endsection
