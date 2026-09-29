@extends('v2.layouts.app')

@section('title', 'Produk Marketplace V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-tags-fill text-primary fs-5"></i> Produk Marketplace
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola pemetaan SKU, sinkronisasi stok, dan integrasi produk dari semua toko marketplace Anda.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.marketplace_produk.print_report', request()->all()) }}" target="_blank"
            class="btn btn-sm btn-v2-secondary py-1.5 px-2.5 shadow-xs" title="Cetak Laporan Produk & Stok">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </a>

        <form action="{{ route('v2.marketplace_produk.bulk_promote') }}" method="POST" class="d-inline m-0"
            onsubmit="return confirm('Jadikan semua produk marketplace yang belum ditautkan sebagai Master Product baru? (SKU kosong akan otomatis dibuatkan acak)');">
            @csrf
            <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-xs">
                <i class="bi bi-stars me-1"></i> Jadikan Master (Masal)
            </button>
        </form>
    </div>
</div>

<!-- Alerts -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-4">
        <div class="v2-stat-widget widget-blue">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($totalCount) }}</span>
                <span class="v2-stat-lbl">Total Produk Marketplace</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="v2-stat-widget {{ $unmappedCount > 0 ? 'widget-amber' : 'widget-purple' }}">
            <div class="v2-stat-icon-wrapper {{ $unmappedCount > 0 ? 'amber' : 'purple' }}">
                <i class="bi bi-link-45deg"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($unmappedCount) }}</span>
                <span class="v2-stat-lbl">Belum Ditautkan (Perlu Action)</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="v2-stat-widget widget-green">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($mappedCount) }}</span>
                <span class="v2-stat-lbl">Sudah Ditautkan ke Master</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="v2-card shadow-sm border rounded-3 overflow-hidden mb-4">
    <!-- Navigation Tabs -->
    <div class="bg-light border-bottom px-3 pt-2">
        <ul class="nav nav-tabs border-0" id="marketplaceTab" role="tablist">
            <li class="nav-item">
                <a href="{{ route('v2.marketplace_produk.index', request()->except(['status', 'page'])) }}"
                    class="nav-link py-2 px-3 fw-bold {{ !request('status') ? 'active bg-white text-primary border-bottom-0' : 'text-muted' }}" style="font-size: 0.78rem;">
                    <i class="bi bi-grid-fill me-1"></i> Semua Produk
                    <span class="badge {{ !request('status') ? 'bg-primary' : 'bg-secondary' }} ms-1.5">{{ number_format($totalCount) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('v2.marketplace_produk.index', array_merge(request()->except('page'), ['status' => 'unmapped'])) }}"
                    class="nav-link py-2 px-3 fw-bold {{ request('status') === 'unmapped' ? 'active bg-white text-warning border-bottom-0' : 'text-muted' }}" style="font-size: 0.78rem;">
                    <i class="bi bi-link-45deg me-1"></i> Belum Ditautkan
                    <span class="badge bg-warning text-dark ms-1.5">{{ number_format($unmappedCount) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('v2.marketplace_produk.index', array_merge(request()->except('page'), ['status' => 'mapped'])) }}"
                    class="nav-link py-2 px-3 fw-bold {{ request('status') === 'mapped' ? 'active bg-white text-success border-bottom-0' : 'text-muted' }}" style="font-size: 0.78rem;">
                    <i class="bi bi-check-circle-fill me-1"></i> Sudah Ditautkan
                    <span class="badge bg-success ms-1.5">{{ number_format($mappedCount) }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Filter Form -->
    <div class="p-3 bg-light bg-opacity-50 border-bottom">
        <form method="GET" action="{{ route('v2.marketplace_produk.index') }}">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label mb-1 text-muted fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-search me-1"></i>Nama Produk
                    </label>
                    <input type="text" name="name" class="form-control form-control-sm"
                        placeholder="Cari nama barang..." value="{{ request('name') }}" style="font-size: 0.78rem;">
                </div>
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label mb-1 text-muted fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-upc-scan me-1"></i>SKU Marketplace
                    </label>
                    <input type="text" name="sku" class="form-control form-control-sm"
                        placeholder="Cari SKU..." value="{{ request('sku') }}" style="font-size: 0.78rem;">
                </div>
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label mb-1 text-muted fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-globe me-1"></i>Channel
                    </label>
                    <select name="channel_id" class="form-select form-select-sm" style="font-size: 0.78rem;">
                        <option value="">Semua Channel</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}" {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                {{ $channel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label mb-1 text-muted fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-shop me-1"></i>Toko
                    </label>
                    <select name="store_id" class="form-select form-select-sm" style="font-size: 0.78rem;">
                        <option value="">Semua Toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }} ({{ $store->channel->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label mb-1 text-muted fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-clock me-1"></i>Tipe PO
                    </label>
                    <select name="po_status" class="form-select form-select-sm" style="font-size: 0.78rem;">
                        <option value="">Semua (PO & Reguler)</option>
                        <option value="po" {{ request('po_status') === 'po' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                        <option value="non_po" {{ request('po_status') === 'non_po' ? 'selected' : '' }}>📦 Reguler (Non PO)</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label mb-1 text-muted fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-arrow-repeat me-1"></i>Status Stok
                    </label>
                    <select name="sync_status" class="form-select form-select-sm" style="font-size: 0.78rem;">
                        <option value="">Semua Status Stok</option>
                        <option value="match" {{ request('sync_status') === 'match' ? 'selected' : '' }}>✅ Stok Sinkron</option>
                        <option value="diff" {{ request('sync_status') === 'diff' ? 'selected' : '' }}>⚠️ Stok Berbeda</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-2 pt-1">
                    <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-3">
                        <i class="bi bi-funnel me-1"></i> Terapkan Filter
                    </button>
                    @if (request()->anyFilled(['name', 'sku', 'channel_id', 'store_id', 'po_status', 'sync_status']))
                        <a href="{{ route('v2.marketplace_produk.index', request()->only('status')) }}"
                            class="btn btn-sm btn-v2-secondary py-1.5 px-3" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
        <table class="table v2-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 38%;">NAMA PRODUK</th>
                    <th style="width: 13%;">HARGA JUAL</th>
                    <th style="width: 8%;" class="text-center">STOK</th>
                    <th style="width: 17%;">STATUS MASTER</th>
                    <th style="width: 14%;">TOKO / CHANNEL</th>
                    <th style="width: 10%;" class="text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marketplaceProducts as $product)
                    @php
                        $chCode = strtolower($product->store->channel->code ?? '');
                        $chBadge = match($chCode) {
                            'shopee'    => 'bg-danger text-white',
                            'tiktok'    => 'bg-dark text-white',
                            'tokopedia' => 'bg-success text-white',
                            'lazada'    => 'bg-primary text-white',
                            default     => 'bg-secondary text-white',
                        };
                    @endphp
                    <tr>
                        <!-- Nama Produk & Thumbnail -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                        class="rounded border flex-shrink-0 cursor-pointer"
                                        style="width: 36px; height: 36px; object-fit: cover;"
                                        onclick="showImageModal('{{ $product->image_url }}', '{{ addslashes($product->name) }}')"
                                        title="Klik untuk perbesar">
                                @else
                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                        style="width: 36px; height: 36px;">
                                        <i class="bi bi-image" style="font-size: 0.85rem;"></i>
                                    </div>
                                @endif
                                <div class="overflow-hidden">
                                    <div class="fw-semibold text-dark text-truncate" style="font-size: 0.78rem; max-width: 320px;" title="{{ $product->name }}">
                                        {{ $product->name }}
                                    </div>
                                    <div class="text-muted d-flex align-items-center gap-1" style="font-size: 0.68rem;">
                                        @if ($product->marketplace_sku)
                                            <i class="bi bi-upc-scan text-primary" style="font-size: 0.65rem;"></i>
                                            <code class="font-monospace text-primary" style="font-size: 0.68rem;">{{ $product->marketplace_sku }}</code>
                                        @else
                                            <span class="text-secondary fst-italic">Tanpa SKU</span>
                                        @endif
                                        @if($product->isPreOrder())
                                            <span class="badge bg-warning text-dark px-1 py-0" style="font-size: 0.58rem;">PO</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Harga Jual -->
                        <td>
                            <div class="fw-bold font-monospace text-dark" style="font-size: 0.78rem;">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </div>
                        </td>

                        <!-- Stok -->
                        <td class="text-center font-monospace">
                            <span class="fw-bold text-dark" style="font-size: 0.82rem;">
                                {{ number_format($product->stock) }}
                            </span>
                            @if ($product->masterProduct && $product->sync_stock && $product->safety_stock > 0)
                                <div class="text-muted" style="font-size: 0.62rem;">
                                    (Master: {{ $product->masterProduct->stock }} | Safety: {{ $product->safety_stock }})
                                </div>
                            @endif
                        </td>

                        <!-- Status Master -->
                        <td>
                            @if ($product->masterProduct)
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle align-self-start px-2 py-0.5" style="font-size: 0.65rem;">
                                        <i class="bi bi-link-45deg me-1"></i>Tertaut ke Master
                                    </span>
                                    <div class="fw-medium text-dark text-truncate" style="font-size: 0.72rem; max-width: 170px;" title="{{ $product->masterProduct->name }}">
                                        {{ $product->masterProduct->name }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1" style="font-size: 0.65rem;">
                                        @if ($product->sync_stock)
                                            <span class="badge bg-info-subtle text-info border px-1 py-0" title="Sync Stok Aktif">
                                                <i class="bi bi-arrow-repeat me-0.5"></i>Stok
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border px-1 py-0">No Sync</span>
                                        @endif

                                        @if ($product->sync_price)
                                            <span class="badge bg-primary-subtle text-primary border px-1 py-0" title="Sync Harga Aktif">
                                                <i class="bi bi-cash me-0.5"></i>Harga
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                    <i class="bi bi-unlink me-1"></i>Belum Ditautkan
                                </span>
                            @endif
                        </td>

                        <!-- Toko / Channel -->
                        <td>
                            <div class="fw-semibold text-dark text-truncate" style="font-size: 0.75rem;" title="{{ $product->store->store_name }}">
                                {{ $product->store->store_name }}
                            </div>
                            <span class="badge {{ $chBadge }} mt-0.5 px-1.5 py-0.5" style="font-size: 0.62rem;">
                                {{ $product->store->channel->name ?? 'Marketplace' }}
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td class="text-center">
                            @if (!$product->masterProduct)
                                <div class="d-flex align-items-center justify-content-center gap-1.5">
                                    <form action="{{ route('v2.marketplace_produk.promote', $product->id) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn-action-icon btn-action-view rounded-2"
                                            onclick="return confirm('Jadikan produk ini sebagai Master Product baru?');"
                                            title="Jadikan Master Product">
                                            <i class="bi bi-star-fill text-white"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('v2.marketplace_produk.destroy', $product->id) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-icon btn-action-delete rounded-2"
                                            onclick="return confirm('Hapus produk marketplace ini dari daftar ERP?');"
                                            title="Hapus Produk">
                                            <i class="bi bi-trash text-white"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center gap-1.5">
                                    @if(Route::has('products.publish'))
                                        <a href="{{ route('products.publish', $product->masterProduct->id) }}"
                                            class="btn-action-icon btn-action-view rounded-2" title="Salin ke Toko Lain">
                                            <i class="bi bi-copy text-white"></i>
                                        </a>
                                    @endif

                                    <form action="{{ route('v2.marketplace_produk.unlink', $product->id) }}" method="POST" class="d-inline m-0"
                                        onsubmit="return confirm('Batal tautkan produk marketplace ini dari Master Product?');">
                                        @csrf
                                        <button type="submit" class="btn-action-icon btn-action-delete rounded-2" title="Batal Tautkan">
                                            <i class="bi bi-link-45deg text-white"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <span class="fw-semibold">Tidak ada data produk marketplace yang sesuai.</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer / Pagination -->
    <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="text-muted" style="font-size: 0.75rem;">
            Menampilkan {{ $marketplaceProducts->firstItem() ?? 0 }} - {{ $marketplaceProducts->lastItem() ?? 0 }}
            dari total {{ number_format($marketplaceProducts->total()) }} produk
        </span>
        <div>
            {{ $marketplaceProducts->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Modal Zoom Image -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 px-3 bg-light">
                <h6 class="modal-title fw-bold text-dark text-truncate" id="imagePreviewTitle" style="font-size: 0.85rem;">Gambar Produk</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="imagePreviewSrc" src="" alt="Preview" class="img-fluid rounded border shadow-sm" style="max-height: 420px; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(src, title) {
    document.getElementById('imagePreviewSrc').src = src;
    document.getElementById('imagePreviewTitle').innerText = title;
    const myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    myModal.show();
}
</script>
@endsection
