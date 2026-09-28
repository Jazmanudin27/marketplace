@extends('v2.layouts.app')

@section('title', 'Data Master Produk')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2" style="font-size: 1.15rem; font-weight: 700;">
            <i class="bi bi-box-seam-fill text-primary"></i> Data Master Produk & Barang
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola katalog produk, SKU, kategori, brand, harga HPP, dan stok barang</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Data">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <a href="{{ Route::has('products.create') ? route('products.create') : url('/products/create') }}" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
        </a>
    </div>
</div>

<!-- Compact Metric Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($stats['total'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Total Produk</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($stats['active'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Produk Aktif</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper amber">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($stats['low_stock'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Stok Menipis / Out</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="v2-stat-widget">
            <div class="v2-stat-icon-wrapper purple">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($stats['preorder'] ?? 0) }}</span>
                <span class="v2-stat-lbl">Pre-Order System</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar Compact -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2.5">
        <form action="{{ url('/v2/produk') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted" style="font-size: 0.8rem;"></i>
                    <input type="text" name="search" class="form-control form-control-sm ps-4" placeholder="Cari nama produk, SKU, barcode..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2.5 col-6">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2.5 col-6">
                <select name="brand_id" class="form-select form-select-sm">
                    <option value="">Semua Brand</option>
                    @foreach($brands as $br)
                        <option value="{{ $br->id }}" {{ request('brand_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1.5 col-6">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-1.5 col-6 d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-sm btn-v2-primary py-1 px-3 w-100">
                    <i class="bi bi-funnel-fill me-1" style="font-size: 0.75rem;"></i> Filter
                </button>
                <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-v2-secondary py-1 px-2" title="Reset Filter">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Master Product Table Card -->
<div class="v2-card shadow-sm border">
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 45px;">NO</th>
                        <th>INFORMASI PRODUK & SKU</th>
                        <th style="min-width: 130px;">KATEGORI & BRAND</th>
                        <th class="text-end" style="min-width: 120px;">HPP (MODAL)</th>
                        <th class="text-end" style="min-width: 120px;">HARGA JUAL</th>
                        <th class="text-center" style="min-width: 90px;">STOK</th>
                        <th class="text-center" style="min-width: 85px;">STATUS</th>
                        <th class="text-center" style="width: 80px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        <tr>
                            <td class="text-center text-muted fw-bold" style="font-size: 0.75rem;">
                                {{ method_exists($products, 'firstItem') ? $products->firstItem() + $index : $index + 1 }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" class="rounded border" style="width: 34px; height: 34px; object-fit: cover;" alt="{{ $product->name }}">
                                    @else
                                        <div class="product-avatar-icon">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    @endif
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 380px; font-size: 0.8rem;" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-0.5">
                                            <span class="sku-badge"><i class="bi bi-barcode me-1 text-muted"></i>{{ $product->sku ?? '-' }}</span>
                                            @if($product->is_preorder)
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0.5 px-1.5" style="font-size: 0.62rem;">PO</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="mb-0.5">
                                    <span class="badge bg-light text-dark border fw-medium" style="font-size: 0.7rem;">
                                        <i class="bi bi-folder2 me-1 text-primary"></i>{{ $product->category->name ?? 'Tanpa Kategori' }}
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-tag me-0.5"></i>{{ $product->brand->name ?? '-' }}
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="text-muted" style="font-size: 0.72rem;">Rp</span>
                                <span class="fw-medium text-secondary" style="font-size: 0.78rem;">{{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-primary fw-bold" style="font-size: 0.82rem;">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-center">
                                @php
                                    $stock = $product->stock ?? 0;
                                    $minStock = $product->min_stock ?? 5;
                                @endphp
                                @if($stock <= 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-x-circle-fill me-1"></i>0 {{ $product->unit ?? 'pcs' }}
                                    </span>
                                @elseif($stock <= $minStock)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $stock }} {{ $product->unit ?? 'pcs' }}
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i>{{ $stock }} {{ $product->unit ?? 'pcs' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($product->is_active ?? true)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.7rem;">
                                        <span class="status-dot active"></span>Aktif
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.7rem;">
                                        <span class="status-dot inactive"></span>Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <a href="{{ Route::has('products.edit') ? route('products.edit', $product->id) : url('/products/'.$product->id.'/edit') }}" class="btn-action-icon btn-action-edit" title="Edit Produk">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <button type="button" class="btn-action-icon btn-action-delete" title="Hapus Produk" onclick="if(confirm('Apakah Anda yakin ingin menghapus produk ini?')) { document.getElementById('delete-prod-{{ $product->id }}').submit(); }">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                    <form id="delete-prod-{{ $product->id }}" action="{{ Route::has('products.destroy') ? route('products.destroy', $product->id) : url('/products/'.$product->id) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="bi bi-inbox fs-1 d-block text-secondary mb-2 opacity-50"></i>
                                    <p class="mb-1 fw-bold text-dark" style="font-size: 0.88rem;">Belum ada data produk terdaftar</p>
                                    <p class="text-muted small mb-0">Silakan tambahkan produk baru atau ubah kata kunci pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($products, 'hasPages') && $products->hasPages())
        <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between bg-light-subtle">
            <div class="text-muted" style="font-size: 0.75rem;">
                Menampilkan <strong>{{ $products->firstItem() ?? 0 }}</strong> sampai <strong>{{ $products->lastItem() ?? 0 }}</strong> dari <strong>{{ $products->total() }}</strong> produk
            </div>
            <div>
                {{ $products->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

