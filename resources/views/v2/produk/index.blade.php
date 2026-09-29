@extends('v2.layouts.app')

@section('title', 'Master Produk V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-box-seam text-primary fs-5"></i> Katalog Master Produk V2
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola database produk master, pemetaan SKU marketplace, dan sinkronisasi stok multi-channel</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Page">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <button type="button" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm" onclick="alert('Fitur Tambah Produk Master akan segera dibuka!')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Master
        </button>
    </div>
</div>

<!-- Header Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Total Master</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['single'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Single (Utama)</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper purple">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['bundle'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Paket (Bundle)</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['ready'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Stock Ready</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper amber">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['po'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Pre-Order (PO)</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-xl-2">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper amber" style="background-color: #fee2e2; color: #ef4444;">
                <i class="bi bi-link-45deg"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num" style="color: #ef4444;">{{ number_format($counts['unlinked'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Belum Linked</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Box Compact -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2.5">
        <form method="GET" action="{{ url('/v2/produk') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <input type="text" name="name" class="form-control form-control-sm" placeholder="Cari nama produk..." value="{{ request('name') }}">
            </div>
            <div class="col-12 col-md-2">
                <input type="text" name="sku" class="form-control form-control-sm font-monospace" placeholder="Cari SKU / SKU Induk..." value="{{ request('sku') }}">
            </div>
            <div class="col-6 col-md-2">
                <select name="is_bundle" class="form-select form-select-sm">
                    <option value="">-- Semua Jenis --</option>
                    <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>Single (Biasa)</option>
                    <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>Paket (Bundle)</option>
                </select>
            </div>
            <div class="col-6 col-md-1.5 col-lg-1.5">
                <select name="is_preorder" class="form-select form-select-sm">
                    <option value="">-- Tipe --</option>
                    <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>Ready</option>
                    <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>Pre-Order</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="store_id" class="form-select form-select-sm">
                    <option value="">-- Semua Toko --</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                            {{ $st->store_name }} ({{ $st->channel->name ?? 'MP' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1.5 col-lg-1.5 d-flex align-items-center gap-1">
                <button type="submit" class="btn btn-sm btn-v2-primary w-100 justify-content-center py-1">
                    <i class="bi bi-search"></i> Cari
                </button>
                @if(request()->anyFilled(['name', 'sku', 'is_bundle', 'is_preorder', 'store_id', 'link_status']))
                    <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-v2-secondary py-1" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Main Table Card -->
<div class="v2-card mb-3">
    <div class="v2-card-header bg-light py-2">
        <h6 class="v2-card-title d-flex align-items-center gap-2">
            <i class="bi bi-list-columns-reverse text-primary"></i> Daftar Master Produk
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">
                {{ $products->total() }} Item
            </span>
        </h6>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ url('/v2/produk?link_status=unlinked') }}" class="btn btn-sm btn-v2-danger py-0.5 px-2" style="font-size: 0.7rem;">
                <i class="bi bi-exclamation-circle me-1"></i> Filter Belum Linked ({{ $counts['unlinked'] }})
            </a>
        </div>
    </div>
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th>NAMA PRODUK / MASTER</th>
                        <th>SKU & KODE</th>
                        <th class="text-end">HARGA (HPP / JUAL)</th>
                        <th class="text-center">STOK GUDANG</th>
                        <th class="text-center">TIPE / STATUS</th>
                        <th>INTEGRASI TOKO MARKETPLACE</th>
                        <th class="text-center" style="width: 90px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $prod)
                        <tr>
                            <td class="text-center text-muted" style="font-size: 0.72rem;">
                                {{ $products->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="product-avatar-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark" style="font-size: 0.8rem; line-height: 1.25;" title="{{ $prod->name }}">
                                            {{ $prod->name }}
                                        </div>
                                        <div class="text-muted d-flex align-items-center gap-2 mt-0.5" style="font-size: 0.68rem;">
                                            <span><i class="bi bi-folder2 me-1"></i>{{ $prod->category->name ?? 'Uncategorized' }}</span>
                                            @if($prod->brand)
                                                <span>• <i class="bi bi-tag me-1"></i>{{ $prod->brand->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="sku-badge">{{ $prod->sku }}</span>
                                    @if($prod->sku_induk && $prod->sku_induk !== $prod->sku)
                                        <div class="text-muted mt-0.5" style="font-size: 0.65rem;">
                                            Induk: <code class="text-secondary">{{ $prod->sku_induk }}</code>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-primary font-monospace" style="font-size: 0.78rem;">
                                    Rp {{ number_format($prod->selling_price ?? $prod->price ?? 0, 0, ',', '.') }}
                                </div>
                                @if(isset($prod->cost_price) && $prod->cost_price > 0)
                                    <div class="text-muted" style="font-size: 0.65rem;">
                                        HPP: Rp {{ number_format($prod->cost_price, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $stk = $prod->stock ?? 0;
                                    $minStk = $prod->min_stock ?? 5;
                                @endphp
                                @if($stk <= 0)
                                    <span class="badge bg-danger text-white font-monospace px-2 py-0.5" style="font-size: 0.72rem;">Habis (0)</span>
                                @elseif($stk <= $minStk)
                                    <span class="badge bg-warning text-dark font-monospace px-2 py-0.5" style="font-size: 0.72rem;">Menipis ({{ $stk }})</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-0.5" style="font-size: 0.72rem;">{{ $stk }} {{ $prod->unit ?? 'pcs' }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if($prod->is_bundle)
                                        <span class="v2-badge v2-badge-warning" style="font-size: 0.62rem;">
                                            <i class="bi bi-diagram-3 me-0.5"></i>Bundle
                                        </span>
                                    @else
                                        <span class="v2-badge v2-badge-primary" style="font-size: 0.62rem;">
                                            <i class="bi bi-box me-0.5"></i>Single
                                        </span>
                                    @endif

                                    @if($prod->is_preorder)
                                        <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5" style="font-size: 0.6rem;">PO</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.6rem;">Ready</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $uniqueStores = $prod->marketplaceProducts->unique(function($mp) {
                                        return ($mp->store_id ?? $mp->store->store_name ?? '') . '_' . ($mp->marketplace_sku ?? $mp->sku ?? '');
                                    });
                                    $linkedStoresCount = $uniqueStores->count();
                                    $storeData = [];
                                    if ($linkedStoresCount > 0) {
                                        foreach ($uniqueStores as $mp) {
                                            $storeData[] = [
                                                'store_name' => $mp->store->store_name ?? 'Toko Marketplace',
                                                'channel_name' => $mp->store->channel->name ?? 'Marketplace',
                                                'channel_code' => strtolower($mp->store->channel->code ?? ''),
                                                'marketplace_sku' => $mp->marketplace_sku ?? $mp->sku ?? '-',
                                                'status' => $mp->status ?? 'active'
                                            ];
                                        }
                                    }
                                @endphp
                                @if($linkedStoresCount > 0)
                                    <button type="button" class="btn btn-sm btn-v2-success py-0.5 px-2 text-nowrap show-store-modal"
                                            style="font-size: 0.68rem;"
                                            data-name="{{ $prod->name }}"
                                            data-sku="{{ $prod->sku }}"
                                            data-stores="{{ json_encode($storeData) }}">
                                        <i class="bi bi-check-circle-fill me-1"></i>Terhubung ({{ $linkedStoresCount }} Toko)
                                    </button>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-x-circle me-1"></i>Belum Terhubung
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <a href="{{ route('v2.produk.edit', $prod->id) }}" class="btn-action-icon btn-action-edit" title="Edit Master Produk V2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('v2.produk.destroy', $prod->id) }}" method="POST" class="d-inline m-0 p-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-action-icon btn-action-delete confirm-delete" title="Hapus Produk" data-name="{{ $prod->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                                <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                Tidak ditemukan data master produk yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
        <div class="v2-card-footer bg-light p-2 border-top">
            <div class="d-flex align-items-center justify-content-between">
                <div class="text-muted" style="font-size: 0.72rem;">
                    Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari total {{ $products->total() }} produk
                </div>
                <div>
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal Detail Toko Terhubung -->
<div class="modal fade" id="storeDetailModal" tabindex="-1" aria-labelledby="storeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="storeDetailModalLabel">
                    <i class="bi bi-shop text-primary"></i> Detail Integrasi Toko Marketplace
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="p-3 bg-primary-subtle border-bottom border-primary-subtle">
                <div class="fw-bold text-primary" id="modalProductName" style="font-size: 0.88rem;"></div>
                <div class="text-muted font-monospace mt-0.5" id="modalProductSku" style="font-size: 0.72rem;"></div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-2">TOKO MARKETPLACE</th>
                                <th class="py-2">CHANNEL</th>
                                <th class="py-2">SKU MARKETPLACE</th>
                                <th class="text-center py-2 pe-3">STATUS INTEGRASI</th>
                            </tr>
                        </thead>
                        <tbody id="modalStoreTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Tutup Modal</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Show Store Detail Modal
    $(document).on('click', '.show-store-modal', function() {
        var name = $(this).data('name');
        var sku = $(this).data('sku');
        var stores = $(this).data('stores');

        $('#modalProductName').text(name);
        $('#modalProductSku').text('SKU Master: ' + sku);

        var html = '';
        if (stores && stores.length > 0) {
            stores.forEach(function(st) {
                var chCode = (st.channel_code || '').toLowerCase();
                var chBadge = '';

                if (chCode.indexOf('shopee') !== -1) {
                    chBadge = '<span class="badge bg-danger text-white"><i class="bi bi-bag-fill me-1"></i>Shopee</span>';
                } else if (chCode.indexOf('tiktok') !== -1) {
                    chBadge = '<span class="badge bg-dark text-white"><i class="bi bi-tiktok me-1"></i>TikTok Shop</span>';
                } else if (chCode.indexOf('lazada') !== -1) {
                    chBadge = '<span class="badge bg-primary text-white"><i class="bi bi-shop me-1"></i>Lazada</span>';
                } else {
                    chBadge = '<span class="badge bg-secondary text-white"><i class="bi bi-store me-1"></i>' + (st.channel_name || 'Marketplace') + '</span>';
                }

                html += '<tr>' +
                    '<td class="ps-3 py-2.5"><div class="fw-bold text-dark">' + st.store_name + '</div></td>' +
                    '<td class="py-2.5">' + chBadge + '</td>' +
                    '<td class="py-2.5"><code class="text-primary font-monospace">' + st.marketplace_sku + '</code></td>' +
                    '<td class="text-center py-2.5 pe-3"><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Connected</span></td>' +
                    '</tr>';
            });
        } else {
            html = '<tr><td colspan="4" class="text-center py-3 text-muted">Belum ada toko terhubung.</td></tr>';
        }

        $('#modalStoreTableBody').html(html);
        var storeModal = new bootstrap.Modal(document.getElementById('storeDetailModal'));
        storeModal.show();
    });

    $(document).on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var name = $(this).data('name') || 'Produk';
        Swal.fire({
            title: 'Hapus Master Produk?',
            text: 'Apakah Anda yakin ingin menghapus master produk "' + name + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@endpush
@endsection
