@extends('v2.layouts.app')

@section('title', 'Data Master Produk')

@section('content')
<!-- Page Header Compact (Like Reference Image 2) -->
<div class="v2-page-header align-items-start">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-box-seam text-primary fs-5"></i> Data Master Produk & Barang
        </h1>
        <p class="v2-page-subtitle">Total {{ $products->total() ?? 0 }} produk terdaftar dalam sistem</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn-v2-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
        </button>
    </div>
</div>

<!-- Compact Filter Bar (Like Image 2 Filter Bar) -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2">
        <form action="{{ url('/v2/produk') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" style="font-size: 0.8rem;"></i>
                    <input type="text" name="search" class="form-control form-control-sm ps-4" placeholder="Cari berdasarkan nama produk, SKU, atau barcode..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-sm btn-v2-primary py-1 px-3">Filter</button>
                <a href="{{ url('/v2/produk') }}" class="btn btn-sm btn-v2-secondary py-1 px-2" title="Refresh / Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Data Master Table (Like Reference Image 2 Table) -->
<div class="v2-card">
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">NO</th>
                        <th>NAMA PRODUK & SKU</th>
                        <th>KATEGORI / BRAND</th>
                        <th class="text-end">HARGA MODAL (HPP)</th>
                        <th class="text-end">HARGA JUAL</th>
                        <th class="text-center">STOK</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center" style="width: 80px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        <tr>
                            <td class="text-center text-muted fw-bold">{{ $products->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                                <div class="text-muted" style="font-size: 0.725rem;">SKU: {{ $product->sku ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-body">{{ $product->category->name ?? '-' }}</div>
                                <div class="text-muted" style="font-size: 0.725rem;">{{ $product->brand->name ?? '-' }}</div>
                            </td>
                            <td class="text-end text-muted">Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if(($product->stock ?? 0) <= ($product->min_stock ?? 5))
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">{{ $product->stock ?? 0 }} {{ $product->unit ?? 'pcs' }}</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">{{ $product->stock ?? 0 }} {{ $product->unit ?? 'pcs' }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($product->is_active ?? true)
                                    <span class="text-success fw-semibold" style="font-size: 0.75rem;">Aktif</span>
                                @else
                                    <span class="text-muted fw-semibold" style="font-size: 0.75rem;">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <!-- Edit Square Icon Button (Blue) -->
                                    <a href="{{ Route::has('products.edit') ? route('products.edit', $product->id) : url('/products/'.$product->id.'/edit') }}" class="btn-action-icon btn-action-edit" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <!-- Delete Square Icon Button (Red) -->
                                    <button type="button" class="btn-action-icon btn-action-delete" title="Hapus" onclick="if(confirm('Hapus produk ini?')) { document.getElementById('delete-prod-{{ $product->id }}').submit(); }">
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
                            <td colspan="8" class="text-center py-4 text-muted">
                                Belum ada data produk terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($products, 'hasPages') && $products->hasPages())
        <div class="p-2 border-top d-flex justify-content-end">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
