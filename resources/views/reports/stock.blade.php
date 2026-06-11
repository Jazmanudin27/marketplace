@extends('layouts.app')
@section('title', 'Laporan Stok Barang')
@section('page-title', 'Laporan Stok Barang')

@section('content')
    <div class="row justify-content-strat">
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header-line">
                    <h3><i class="fas fa-file-invoice"></i> Filter Laporan Stok</h3>
                </div>

                <form action="{{ route('reports.stock.print') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Merk</label>
                        <select name="brand_id" class="form-select">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Produk Spesifik</label>
                        <select name="product_id" class="form-select">
                            <option value="">Semua Produk</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->sku ? '[' . $product->sku . '] ' : '' }}{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn-primary-sm"
                            style="background:var(--primary); font-size:1rem; padding: 0.5rem 1.5rem;">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
