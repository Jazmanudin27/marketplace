@extends('layouts.app')
@section('title', 'Laporan Stok Barang')
@section('page-title', 'Laporan Stok Barang')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-file-invoice text-info me-2"></i>Filter Laporan Stok</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.stock.print') }}" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Kategori</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Merk</label>
                            <select name="brand_id" class="form-select form-select-sm">
                                <option value="">Semua Merk</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label form-label-sm fw-semibold">Produk Spesifik</label>
                            <select name="product_id" class="form-select form-select-sm">
                                <option value="">Semua Produk</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->sku ? '[' . $product->sku . '] ' : '' }}{{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-print me-1"></i> Cetak Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
