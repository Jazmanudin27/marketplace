@extends('layouts.app')
@section('title', 'Master Produk')
@section('page-title', 'Master Produk')
@section('content')
<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-box-open"></i> Daftar Master Produk</h3>
        <a href="{{ route('products.create') }}" class="btn-primary-sm"><i class="fas fa-plus"></i> Tambah Produk</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th><th>Nama Produk</th><th>Kategori</th><th>Merk</th>
                    <th>Harga Jual</th><th>Stok</th><th>Min Stok</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="mono">{{ $product->sku }}</td>
                    <td class="fw-bold">{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td>{{ $product->brand->name ?? '-' }}</td>
                    <td class="mono">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td class="mono fw-bold {{ $product->stock <= $product->min_stock ? 'text-danger' : 'text-success' }}">
                        {{ number_format($product->stock) }}
                    </td>
                    <td class="mono text-muted">{{ number_format($product->min_stock) }}</td>
                    <td>
                        <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">
                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('products.edit', $product->id) }}" class="btn-primary-sm" style="background:var(--warning); border-color:var(--warning); color:#fff;">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">Belum ada produk</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $products->links() }}</div>
</div>
@endsection
