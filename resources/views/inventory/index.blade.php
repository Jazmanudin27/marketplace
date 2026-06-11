@extends('layouts.app')
@section('title', 'Manajemen Inventory')
@section('page-title', 'Inventory & Kartu Stok')
@section('content')
    <div class="dashboard-card">
        <div class="card-header-line">
            <h3><i class="fas fa-boxes"></i> Pemantauan Stok Produk</h3>
        </div>

        <div
            style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <form action="{{ route('inventory.index') }}" method="GET"
                style="display: flex; gap: 0.5rem; max-width: 400px; flex: 1;">
                <input type="text" name="search" placeholder="Cari SKU atau Nama Produk..." value="{{ request('search') }}"
                    style="flex: 1; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px;">
                <button type="submit" class="btn-primary-sm"><i class="fas fa-search"></i> Cari</button>
            </form>

            <a href="{{ route('inventory.opname') }}" class="btn-primary-sm">
                <i class="fas fa-clipboard-check"></i> Mulai Stock Opname
            </a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th style="text-align: right;">Harga Modal</th>
                        <th style="text-align: right;">Harga Jual</th>
                        <th style="text-align: center;">Stok Tersedia</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td class="mono">{{ $product->sku }}</td>
                            <td class="fw-bold">{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td class="mono" style="text-align: right;">Rp
                                {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                            <td class="mono" style="text-align: right;">Rp
                                {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="mono fw-bold {{ $product->stock <= $product->min_stock ? 'text-danger' : 'text-success' }}"
                                style="text-align: center; font-size: 1.1rem;">
                                {{ number_format($product->stock) }}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('inventory.ledger', $product->id) }}" class="btn-primary-sm"><i
                                        class="fas fa-clipboard-list"></i> Kartu Stok</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding:2rem;">Belum ada produk</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;">{{ $products->links() }}</div>
    </div>
@endsection
