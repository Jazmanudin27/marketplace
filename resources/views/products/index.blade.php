@extends('layouts.app')
@section('title', 'Master Produk')
@section('page-title', 'Master Produk')
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

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-4" id="mainTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-produk" data-bs-toggle="tab" data-bs-target="#panel-produk"
                type="button" role="tab" aria-controls="panel-produk" aria-selected="true">
                <i class="fas fa-box-open me-1"></i> Master Produk
                <span class="badge bg-secondary ms-1">{{ $products->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-riwayat" data-bs-toggle="tab" data-bs-target="#panel-riwayat" type="button"
                role="tab" aria-controls="panel-riwayat" aria-selected="false">
                <i class="fas fa-history me-1"></i> Riwayat Publikasi
                @php $pendingCount = $publicationLogs->whereIn('status', ['pending','processing'])->count(); @endphp
                @if ($pendingCount > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                @else
                    <span class="badge bg-secondary ms-1">{{ $publicationLogs->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-pemetaan" data-bs-toggle="tab" data-bs-target="#panel-pemetaan" type="button"
                role="tab" aria-controls="panel-pemetaan" aria-selected="false">
                <i class="fas fa-map-signs me-1"></i> Pemetaan Kategori
                <span class="badge bg-secondary ms-1">{{ $categoryMappings->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabContent">

        {{-- ===================== TAB 1: MASTER PRODUK ===================== --}}
        <div class="tab-pane fade show active" id="panel-produk" role="tabpanel" aria-labelledby="tab-produk">
            <!-- Filter Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('products.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary mb-1">Filter Chanel</label>
                            <select name="channel_id" class="form-select form-select-sm form-select-dark">
                                <option value="">Semua Chanel</option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->id }}"
                                        {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary mb-1">Filter Akun / Toko</label>
                            <select name="store_id" class="form-select form-select-sm form-select-dark">
                                <option value="">Semua Akun / Toko</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}"
                                        {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }} ({{ $store->channel->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1" style="height: 31px;">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            @if (request()->anyFilled(['channel_id', 'store_id']))
                                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary"
                                    style="height: 31px; display: flex; align-items: center; justify-content: center; padding: 0 10px;"
                                    title="Reset Filter">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-box-open me-2 text-primary"></i>Daftar Master Produk
                    </h6>
                    <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Tambah Produk
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">SKU Variasi</th>
                                    <th>Nama Barang</th>
                                    <th>SKU Induk</th>
                                    <th>Kategori</th>
                                    <th>Sub Kategori</th>
                                    <th>Ukuran</th>
                                    <th>Warna</th>
                                    <th>HPP Produk</th>
                                    <th>Harga Jual</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th>Marketplace</th>
                                    <th class="pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td class="ps-3">
                                            <code class="text-primary">{{ $product->sku }}</code>
                                        </td>
                                        <td class="fw-semibold">{{ $product->name }}</td>
                                        <td>
                                            @if ($product->sku_induk)
                                                <code class="text-secondary">{{ $product->sku_induk }}</code>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $product->category->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $product->sub_kategori ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                                style="font-size: 0.75rem;">{{ $product->ukuran ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark-emphasis"
                                                style="font-size: 0.75rem;">{{ $product->warna ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="font-monospace">Rp
                                                {{ number_format($product->cost_price, 0, ',', '.') }}</span>
                                        </td>
                                        <td>
                                            <span class="font-monospace">Rp
                                                {{ number_format($product->price, 0, ',', '.') }}</span>
                                        </td>
                                        <td>
                                            <span
                                                class="fw-bold font-monospace {{ $product->stock <= $product->min_stock ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($product->stock) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($product->is_active)
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success border-opacity-25">Aktif</span>
                                            @else
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($product->marketplaceProducts->isEmpty())
                                                <span class="badge bg-secondary" style="font-size: 0.7rem;">Belum
                                                    Terhubung</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
                                                    @foreach ($product->marketplaceProducts->unique('store_id') as $mp)
                                                        <span
                                                            class="channel-badge channel-{{ $mp->store->channel->code ?? '' }}"
                                                            style="font-size: 0.68rem; padding: 0.15rem 0.45rem; line-height: 1;">
                                                            @if (($mp->store->channel->code ?? '') === 'shopee')
                                                                <i class="fab fa-shopify"></i>
                                                            @elseif(($mp->store->channel->code ?? '') === 'tiktok')
                                                                <i class="fab fa-tiktok"></i>
                                                            @endif
                                                            {{ $mp->store->store_name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="pe-3">
                                            <div class="d-flex gap-1 align-items-center">
                                                <a href="{{ route('products.edit', $product->id) }}"
                                                    class="btn btn-sm btn-warning text-white">
                                                    <i class="fas fa-pencil-alt"></i> Edit
                                                </a>
                                                @php
                                                    $linkedStoreIds = $product->marketplaceProducts
                                                        ->pluck('store_id')
                                                        ->unique();
                                                    $isFullyPublished =
                                                        $connectedStoresCount > 0 &&
                                                        $linkedStoreIds->count() >= $connectedStoresCount;
                                                @endphp
                                                @if ($isFullyPublished)
                                                    <button class="btn btn-sm btn-outline-success" disabled>
                                                        <i class="fas fa-check-circle"></i> Terhubung
                                                    </button>
                                                @else
                                                    <a href="{{ route('products.publish', $product->id) }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="fas fa-cloud-upload-alt"></i> Publish
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-secondary py-5">
                                            <i class="fas fa-box-open d-block mb-2"
                                                style="font-size: 2rem; opacity: 0.3;"></i>
                                            Belum ada produk
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($products->hasPages())
                    <div class="card-footer border-top-0 py-3">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ===================== TAB 2: RIWAYAT PUBLIKASI ===================== --}}
        <div class="tab-pane fade" id="panel-riwayat" role="tabpanel" aria-labelledby="tab-riwayat">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-history me-2 text-primary"></i>Riwayat Publikasi
                    </h6>
                    <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
                <div class="card-body p-0">
                    @if ($publicationLogs->isEmpty())
                        <div class="text-center py-5 text-secondary">
                            <i class="fas fa-inbox d-block mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                            <small>Belum ada riwayat publikasi. Silakan publish produk terlebih dahulu.</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Produk</th>
                                        <th>Toko</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                        <th>Waktu</th>
                                        <th class="pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($publicationLogs as $log)
                                        <tr>
                                            <td class="ps-3">
                                                <code class="text-secondary small">{{ $log->id }}</code>
                                            </td>
                                            <td>
                                                <div class="fw-semibold small">{{ $log->masterProduct->name ?? '-' }}
                                                </div>
                                                <code class="text-secondary"
                                                    style="font-size: 0.72rem;">{{ $log->masterProduct->sku ?? '' }}</code>
                                            </td>
                                            <td>
                                                @if ($log->store)
                                                    <span
                                                        class="channel-badge channel-{{ $log->store->channel->code ?? '' }}"
                                                        style="font-size: 0.68rem;">
                                                        @if (($log->store->channel->code ?? '') === 'shopee')
                                                            <i class="fab fa-shopify"></i>
                                                        @elseif(($log->store->channel->code ?? '') === 'tiktok')
                                                            <i class="fab fa-tiktok"></i>
                                                        @endif
                                                        {{ $log->store->store_name }}
                                                    </span>
                                                @else
                                                    <span class="text-secondary">-</span>
                                                @endif
                                            </td>
                                            <td style="max-width: 160px;">
                                                <div class="text-truncate small" title="{{ $log->category_name }}">
                                                    {{ $log->category_name ?? '-' }}
                                                </div>
                                                @if ($log->category_id)
                                                    <code class="text-secondary" style="font-size: 0.68rem;">ID:
                                                        {{ $log->category_id }}</code>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($log->status === 'pending')
                                                    <span
                                                        class="badge bg-warning-subtle text-warning border border-warning border-opacity-25">
                                                        <i class="fas fa-clock me-1"></i>Menunggu
                                                    </span>
                                                @elseif ($log->status === 'processing')
                                                    <span
                                                        class="badge bg-info-subtle text-info border border-info border-opacity-25">
                                                        <i class="fas fa-spinner fa-spin me-1"></i>Diproses
                                                    </span>
                                                @elseif ($log->status === 'success')
                                                    <span
                                                        class="badge bg-success-subtle text-success border border-success border-opacity-25">
                                                        <i class="fas fa-check-circle me-1"></i>Berhasil
                                                    </span>
                                                @else
                                                    <span
                                                        class="badge bg-danger-subtle text-danger border border-danger border-opacity-25">
                                                        <i class="fas fa-times-circle me-1"></i>Gagal
                                                    </span>
                                                @endif
                                            </td>
                                            <td style="max-width: 200px;">
                                                @if ($log->status === 'success' && $log->marketplace_product_id)
                                                    <span class="font-monospace text-success" style="font-size: 0.72rem;">
                                                        <i class="fas fa-link me-1"></i>ID:
                                                        {{ $log->marketplace_product_id }}
                                                    </span>
                                                @elseif ($log->status === 'failed' && $log->error_message)
                                                    <span class="text-danger small" title="{{ $log->error_message }}">
                                                        <i
                                                            class="fas fa-exclamation-circle me-1"></i>{{ Str::limit($log->error_message, 80) }}
                                                    </span>
                                                @elseif ($log->status === 'pending' || $log->status === 'processing')
                                                    <span class="text-secondary small">Queue worker sedang
                                                        memproses...</span>
                                                @else
                                                    <span class="text-secondary">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-secondary small text-nowrap">
                                                    {{ $log->created_at->diffForHumans() }}
                                                </span>
                                            </td>
                                            <td class="pe-3">
                                                @if ($log->status === 'failed')
                                                    <form action="{{ route('products.publish.retry', $log->id) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary"
                                                            title="Coba ulang publikasi">
                                                            <i class="fas fa-redo me-1"></i>Retry
                                                        </button>
                                                    </form>
                                                @elseif ($log->status === 'success')
                                                    <span class="text-success small">✓ Selesai</span>
                                                @else
                                                    <span class="text-secondary small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== TAB 3: PEMETAAN KATEGORI ===================== --}}
        <div class="tab-pane fade" id="panel-pemetaan" role="tabpanel" aria-labelledby="tab-pemetaan">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-sitemap me-2 text-primary"></i>Pemetaan Kategori
                    </h6>
                    <p class="mb-0 text-secondary small mt-1">
                        Pemetaan kategori produk lokal ke kategori marketplace. Saat publish, sistem akan otomatis memilih
                        kategori yang sudah dipetakan.
                    </p>
                </div>
                <div class="card-body p-0">
                    @if ($categoryMappings->isEmpty())
                        <div class="text-center py-5 text-secondary">
                            <i class="fas fa-map-marked-alt d-block mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                            <small>Belum ada pemetaan kategori. Aktifkan "Simpan Pemetaan" saat publish produk untuk
                                menyimpan otomatis.</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Kategori Lokal</th>
                                        <th>Toko</th>
                                        <th>Kategori Marketplace</th>
                                        <th>ID Marketplace</th>
                                        <th class="pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categoryMappings as $mapping)
                                        <tr>
                                            <td class="ps-3 fw-semibold">{{ $mapping->category->name ?? '-' }}</td>
                                            <td>
                                                @if ($mapping->store)
                                                    <span
                                                        class="channel-badge channel-{{ $mapping->store->channel->code ?? '' }}"
                                                        style="font-size: 0.68rem;">
                                                        @if (($mapping->store->channel->code ?? '') === 'shopee')
                                                            <i class="fab fa-shopify"></i>
                                                        @elseif(($mapping->store->channel->code ?? '') === 'tiktok')
                                                            <i class="fab fa-tiktok"></i>
                                                        @endif
                                                        {{ $mapping->store->store_name }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $mapping->marketplace_category_name ?? '-' }}</td>
                                            <td>
                                                <code
                                                    class="text-secondary small">{{ $mapping->marketplace_category_id }}</code>
                                            </td>
                                            <td class="pe-3">
                                                <form
                                                    action="{{ route('products.mappings.category.destroy', $mapping->id) }}"
                                                    method="POST" onsubmit="return confirm('Hapus pemetaan ini?')"
                                                    style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Restore active tab from URL hash
            const hash = window.location.hash.replace('#', '');
            const tabMap = {
                produk: 'tab-produk',
                riwayat: 'tab-riwayat',
                pemetaan: 'tab-pemetaan'
            };
            if (hash && tabMap[hash]) {
                const trigger = document.getElementById(tabMap[hash]);
                if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
            }

            // Save tab to hash on change
            document.querySelectorAll('#mainTab button[data-bs-toggle="tab"]').forEach(btn => {
                btn.addEventListener('shown.bs.tab', function(e) {
                    const target = e.target.getAttribute('data-bs-target').replace('#panel-', '');
                    window.location.hash = target;
                });
            });

            // Auto-refresh riwayat tab if there are pending/processing jobs
            const pendingCount = {{ $publicationLogs->whereIn('status', ['pending', 'processing'])->count() }};
            if (pendingCount > 0) {
                console.log('[Queue] ' + pendingCount + ' job(s) masih berjalan, auto-refresh dalam 10 detik...');
                setTimeout(function() {
                    const riwayatPanel = document.getElementById('panel-riwayat');
                    if (riwayatPanel && riwayatPanel.classList.contains('show')) {
                        location.reload();
                    }
                }, 10000);
            }
        });
    </script>

@endsection
