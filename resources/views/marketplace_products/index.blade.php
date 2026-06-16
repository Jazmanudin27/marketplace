@extends('layouts.app')
@section('title', 'Produk Marketplace')
@section('page-title', 'Produk Marketplace')

@push('styles')
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03) !important;
        }

        .badge {
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .mono {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        }

        .form-control-custom {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: var(--text-primary) !important;
        }

        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2) !important;
            border-color: var(--primary) !important;
        }

        .btn-action {
            padding: 5px 12px;
            font-size: 0.82rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            justify-content: center;
        }
    </style>
@endpush

@section('content')
    {{-- Status Tabs Row --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold text-white"><i class="fas fa-store text-primary me-2"></i>Produk Marketplace</h4>
            <span
                class="badge bg-secondary-subtle text-secondary py-1.5 px-3 rounded-pill">{{ $marketplaceProducts->total() }}
                Item</span>
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
    <div class="card shadow-sm border-0 mb-4"
        style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('marketplace_products.index') }}" class="row g-3 align-items-end">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1.5">Nama Barang</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent text-muted" style="border-right: none;">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="name" class="form-control form-control-custom ps-0"
                            placeholder="Cari nama barang..." value="{{ request('name') }}" style="border-left: none;">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1.5">SKU</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent text-muted" style="border-right: none;">
                            <i class="fas fa-barcode"></i>
                        </span>
                        <input type="text" name="sku" class="form-control form-control-custom ps-0"
                            placeholder="Cari SKU..." value="{{ request('sku') }}" style="border-left: none;">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1.5">Channel</label>
                    <select name="channel_id" class="form-select form-select-sm form-control-custom">
                        <option value="">Semua Channel</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}"
                                {{ request('channel_id') == $channel->id ? 'selected' : '' }}>{{ $channel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1.5">Toko</label>
                    <select name="store_id" class="form-select form-select-sm form-control-custom">
                        <option value="">Semua Toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-semibold py-2">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    @if (request()->anyFilled(['name', 'sku', 'channel_id', 'store_id']))
                        <a href="{{ route('marketplace_products.index', request()->only('status')) }}"
                            class="btn btn-outline-secondary btn-sm px-3 py-2" title="Reset Filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card shadow-sm border-0"
        style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom"
            style="border-color: var(--border) !important;">
            <h5 class="card-title mb-0 fw-bold text-primary">
                <i class="fas fa-list-ul me-2"></i>Daftar Produk
            </h5>
            <span class="text-muted small fw-medium">Menampilkan
                {{ $marketplaceProducts->firstItem() ?? 0 }}-{{ $marketplaceProducts->lastItem() ?? 0 }} dari
                {{ $marketplaceProducts->total() }} produk</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="background: transparent;">
                <thead style="background: rgba(255, 255, 255, 0.02); border-bottom: 2px solid var(--border);">
                    <tr>
                        <th class="ps-4 border-0 text-muted"
                            style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 18%;">Toko /
                            Channel</th>
                        <th class="border-0 text-muted"
                            style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Produk Marketplace
                        </th>
                        <th class="border-0 text-muted"
                            style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 15%;">SKU /
                            Harga</th>
                        <th class="border-0 text-center text-muted"
                            style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 12%;">Stok
                        </th>
                        <th class="border-0 text-muted"
                            style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 22%;">Status
                            Master</th>
                        <th class="border-0 text-center pe-4 text-muted"
                            style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 18%;">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketplaceProducts as $product)
                        <tr style="border-bottom: 1px solid var(--border); transition: background-color 0.2s;">
                            {{-- Store & Channel --}}
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-white fs-6">{{ $product->store->store_name }}</div>
                                <div class="mt-1.5">
                                    <span class="channel-badge channel-{{ $product->store->channel->code }}">
                                        {{ $product->store->channel->name }}
                                    </span>
                                </div>
                            </td>

                            {{-- Marketplace Product details --}}
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-3">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="rounded shadow-sm border border-secondary"
                                            style="width: 50px; height: 50px; object-fit: cover; background-color: rgba(255, 255, 255, 0.05);">
                                    @else
                                        <div class="rounded border border-secondary d-flex align-items-center justify-content-center bg-dark text-muted shadow-sm"
                                            style="width: 50px; height: 50px; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.02);">
                                            <i class="fas fa-image fa-lg"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-white fs-6">{{ $product->name }}</div>
                                        <div class="text-muted small mt-1">ID: {{ $product->marketplace_product_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- SKU / Harga --}}
                            <td class="py-3">
                                <span class="mono text-muted small bg-dark px-2 py-0.5 rounded border"
                                    style="border-color: var(--border) !important;">
                                    {{ $product->marketplace_sku ?? 'Tidak ada SKU' }}
                                </span>
                                <div class="mt-2 text-danger fw-bold fs-6">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </div>
                            </td>

                            {{-- Stok --}}
                            <td class="text-center py-3">
                                <div class="fs-5 fw-extrabold text-white">{{ number_format($product->stock) }}</div>
                                @if ($product->masterProduct && $product->sync_stock && $product->safety_stock > 0)
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        (Master: {{ $product->masterProduct->stock }} | Safety:
                                        {{ $product->safety_stock }})
                                    </div>
                                @endif
                            </td>

                            {{-- Status Master with safeguards --}}
                            <td class="py-3">
                                @if ($product->masterProduct)
                                    <div class="d-flex flex-column gap-1.5">
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2.5 rounded align-self-start fw-bold">
                                            <i class="fas fa-link me-1"></i>Tertaut
                                        </span>
                                        <div class="text-white small fw-semibold">
                                            {{ $product->masterProduct->name ?? 'Master Terhapus' }}
                                        </div>
                                        @if ($product->sync_stock)
                                            <div class="text-primary small fw-semibold">
                                                <i class="fas fa-sync-alt me-1"></i>Sync Aktif (Safety:
                                                {{ $product->safety_stock }})
                                            </div>
                                        @else
                                            <div class="text-muted small">
                                                <i class="fas fa-sync-alt-slash me-1"></i>Sync Mati
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span
                                        class="badge bg-warning-subtle text-warning border border-warning-subtle py-1.5 px-3 rounded-pill fw-bold">
                                        <i class="fas fa-unlink me-1"></i>Belum Ditautkan
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="pe-4 py-3 text-center">
                                @if (!$product->masterProduct)
                                    <div class="d-flex gap-2 flex-column">
                                        <!-- Jadikan Master -->
                                        <form action="{{ route('marketplace_products.promote', $product->id) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-action w-100"
                                                onclick="return confirm('Jadikan produk ini sebagai Master Product baru?');">
                                                <i class="fas fa-star"></i> Jadikan Master
                                            </button>
                                        </form>

                                        <!-- Tautkan ke Master yang sudah ada -->
                                        <button type="button" class="btn btn-outline-light btn-action w-100"
                                            onclick="document.getElementById('link-form-{{ $product->id }}').style.display='block'">
                                            <i class="fas fa-link"></i> Tautkan...
                                        </button>

                                        <!-- Salin ke Toko Lain -->
                                        <form action="{{ route('marketplace_products.clone_and_publish', $product->id) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-action w-100"
                                                style="background-color: var(--purple) !important; border-color: var(--purple) !important;">
                                                <i class="fas fa-copy"></i> Salin ke Toko Lain
                                            </button>
                                        </form>

                                        <form id="link-form-{{ $product->id }}"
                                            action="{{ route('marketplace_products.link', $product->id) }}"
                                            method="POST"
                                            style="display: none; background: rgba(255, 255, 255, 0.03); padding: 10px; border: 1px solid var(--border); border-radius: 8px; margin-top: 5px; text-align: left;">
                                            @csrf
                                            <select name="master_product_id"
                                                class="form-select form-select-sm form-control-custom mb-2" required>
                                                <option value="">-- Pilih Master --</option>
                                                @foreach ($masterProducts as $master)
                                                    <option value="{{ $master->id }}">{{ $master->name }} (SKU:
                                                        {{ $master->sku }})</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex gap-2">
                                                <button type="submit"
                                                    class="btn btn-success btn-sm flex-grow-1 py-1">Simpan</button>
                                                <button type="button" class="btn btn-secondary btn-sm flex-grow-1 py-1"
                                                    onclick="document.getElementById('link-form-{{ $product->id }}').style.display='none'">Batal</button>
                                            </div>
                                        </form>
                                    </div>
                                @else
                                    <div class="d-flex gap-2 flex-column">
                                        <button type="button" class="btn btn-outline-primary btn-action w-100"
                                            data-bs-toggle="modal" data-bs-target="#settingsModal-{{ $product->id }}">
                                            <i class="fas fa-cog"></i> Pengaturan Stok
                                        </button>

                                        <a href="{{ route('products.publish', $product->masterProduct->id) }}"
                                            class="btn btn-secondary btn-action w-100"
                                            style="background-color: var(--purple) !important; border-color: var(--purple) !important;">
                                            <i class="fas fa-copy"></i> Salin ke Toko Lain
                                        </a>

                                        <form action="{{ route('marketplace_products.unlink', $product->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Batal tautkan produk marketplace ini dari Master Product?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-action w-100">
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
                                                    <h5 class="modal-title fw-bold"
                                                        id="settingsModalLabel-{{ $product->id }}"
                                                        style="color: var(--text-primary);">
                                                        <i class="fas fa-cog text-primary me-2"></i> Pengaturan Stok
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white"
                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form
                                                    action="{{ route('marketplace_products.update_settings', $product->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body py-4">
                                                        <div class="fw-semibold text-white fs-6 mb-3">
                                                            {{ $product->name }}
                                                        </div>
                                                        <hr style="border-color: var(--border);">
                                                        <div class="mb-4">
                                                            <label
                                                                class="form-label d-block fw-bold mb-2 text-secondary">Sinkronisasi
                                                                Stok</label>
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
                                                            <div class="form-text mt-1.5"
                                                                style="font-size: 0.8rem; color: var(--text-muted);">
                                                                Jika dinonaktifkan, perubahan stok Master Product tidak akan
                                                                didorong ke marketplace ini.
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="safetyStock-{{ $product->id }}"
                                                                class="form-label fw-bold mb-2 text-secondary">Stok
                                                                Pengaman (Safety Stock)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"
                                                                    style="background: var(--bg-card2); border: 1px solid var(--border); color: var(--text-secondary);">
                                                                    <i class="fas fa-shield-alt"></i>
                                                                </span>
                                                                <input type="number"
                                                                    class="form-control form-control-custom"
                                                                    name="safety_stock"
                                                                    id="safetyStock-{{ $product->id }}" min="0"
                                                                    value="{{ $product->safety_stock ?? 0 }}" required>
                                                            </div>
                                                            <div class="form-text mt-2"
                                                                style="font-size: 0.8rem; color: var(--text-muted);">
                                                                Jumlah stok yang ditahan sebagai pengaman di gudang
                                                                lokal.<br>
                                                                Stok yang dikirim ke toko = <strong>Stok Master
                                                                    ({{ $product->masterProduct->stock ?? 0 }}) - Stok
                                                                    Pengaman</strong>.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer"
                                                        style="border-top: 1px solid var(--border);">
                                                        <button type="button" class="btn btn-secondary btn-sm"
                                                            data-bs-dismiss="modal"
                                                            style="background: #334155; border: none; padding: 6px 12px;">Batal</button>
                                                        <button type="submit" class="btn btn-primary btn-sm"
                                                            style="background: var(--primary); border: none; padding: 6px 12px;">Simpan
                                                            Pengaturan</button>
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
                            <td colspan="6" class="text-center text-muted py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-box-open text-muted opacity-25 fa-3x mb-3"></i>
                                    <h6 class="fw-semibold">Belum Ada Data Produk Marketplace</h6>
                                    <p class="text-muted small mb-0">Silakan hubungkan toko atau tarik data produk terlebih
                                        dahulu.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($marketplaceProducts->hasPages())
            <div class="card-footer bg-transparent border-top py-3 d-flex justify-content-center"
                style="border-color: var(--border) !important;">
                {{ $marketplaceProducts->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
