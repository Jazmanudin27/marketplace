@extends('v2.layouts.app')

@section('title', 'Master Produk V2')

@section('content')
<!-- Page Header -->
<div class="v2-page-header">
    <div>
        <h1 class="v2-page-title">Master Produk V2</h1>
        <p class="v2-page-subtitle">Kelola katalog master produk perusahaan dengan tampilan V2 yang bersih & responsif.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('master.products.index') }}" class="btn-v2-secondary">
            <i class="bi bi-box-arrow-up-right"></i> Tampilan Produk V1
        </a>
        <button class="btn-v2-primary">
            <i class="bi bi-plus-lg"></i> Tambah Produk Baru
        </button>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="v2-card mb-4">
    <div class="v2-card-body p-3">
        <form action="{{ url('/v2/produk') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama produk, SKU, barcode..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="brand_id" class="form-select">
                    <option value="">Semua Brand</option>
                    @foreach($brands ?? [] as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-v2-primary w-100 justify-content-center">Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'brand_id']))
                    <a href="{{ url('/v2/produk') }}" class="btn btn-v2-secondary" title="Reset Filter"><i class="bi bi-x-circle"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Products Table Card -->
<div class="v2-card">
    <div class="v2-card-header">
        <h5 class="v2-card-title"><i class="bi bi-box-seam me-2 text-primary"></i> Daftar Master Produk</h5>
        <span class="badge bg-indigo-subtle text-indigo fw-bold px-3 py-1">Total {{ $products->total() ?? 0 }} Produk</span>
    </div>
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive border-0">
            <table class="v2-table">
                <thead>
                    <tr>
                        <th>SKU & Info Produk</th>
                        <th>Kategori / Brand</th>
                        <th>Harga Modal (HPP)</th>
                        <th>Harga Jual</th>
                        <th>Stok Gudang</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-body-secondary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; flex-shrink: 0;">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" class="rounded-3 object-fit-cover" style="width: 44px; height: 44px;">
                                        @else
                                            <i class="bi bi-box-seam text-secondary fs-5"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-body" style="font-size: 0.9rem;">{{ $product->name }}</div>
                                        <small class="text-muted font-monospace">SKU: {{ $product->sku ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $product->category->name ?? '-' }}</div>
                                <small class="text-muted">{{ $product->brand->name ?? '-' }}</small>
                            </td>
                            <td class="text-muted">Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</td>
                            <td class="fw-bold text-primary">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @if(($product->stock ?? 0) <= ($product->min_stock ?? 5))
                                    <span class="v2-badge v2-badge-danger"><i class="bi bi-exclamation-triangle"></i> {{ $product->stock ?? 0 }} {{ $product->unit ?? 'pcs' }}</span>
                                @else
                                    <span class="v2-badge v2-badge-success"><i class="bi bi-check-circle"></i> {{ $product->stock ?? 0 }} {{ $product->unit ?? 'pcs' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_active ?? true)
                                    <span class="v2-badge v2-badge-success">Aktif</span>
                                @else
                                    <span class="v2-badge v2-badge-warning">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('master.products.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill me-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data produk master yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($products, 'hasPages') && $products->hasPages())
        <div class="v2-card-footer p-3 border-top">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
