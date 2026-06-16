@extends('layouts.app')
@section('title', 'Manajemen Inventory')
@section('page-title', 'Inventory & Kartu Stok')

@section('content')
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-0">
            <h5 class="card-title mb-0 fw-bold text-primary">
                <i class="fas fa-boxes me-2"></i>Pemantauan Stok Produk
            </h5>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-3 align-items-center">
                <div class="col-md-6">
                    <form action="{{ route('inventory.index') }}" method="GET" class="d-flex gap-2">
                        <input type="text" name="search" placeholder="Cari SKU atau Nama Produk..." value="{{ request('search') }}"
                            class="form-control form-control-sm">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="{{ route('stock_opnames.create') }}" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="fas fa-clipboard-check me-1"></i> Mulai Stock Opname
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0">SKU</th>
                            <th class="border-0">Nama Produk</th>
                            <th class="border-0">Kategori</th>
                            <th class="text-end border-0">Harga Modal</th>
                            <th class="text-end border-0">Harga Jual</th>
                            <th class="text-center border-0">Stok Tersedia</th>
                            <th class="text-center pe-3 border-0">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="mono small ps-3">{{ $product->sku }}</td>
                                <td class="fw-bold">{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? '-' }}</td>
                                <td class="mono text-end">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                                <td class="mono text-end">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td class="mono fw-bold text-center {{ $product->stock <= $product->min_stock ? 'text-danger' : 'text-success' }}"
                                    style="font-size: 1.1rem;">
                                    {{ number_format($product->stock) }}
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('inventory.ledger', $product->id) }}" class="btn btn-outline-primary btn-sm px-2.5">
                                        <i class="fas fa-clipboard-list me-1"></i> Kartu Stok
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada produk</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
