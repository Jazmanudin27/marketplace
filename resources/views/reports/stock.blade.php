@extends('layouts.app')
@section('title', 'Laporan Stok Barang')
@section('page-title', 'Laporan Stok Barang')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-boxes text-info me-2"></i>Filter Laporan Stok Barang</h6>
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
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Jenis Produk</label>
                            <select name="is_bundle" class="form-select form-select-sm">
                                <option value="">Semua Jenis (Single & BUNDLE)</option>
                                <option value="0">📦 Single (Produk Standar)</option>
                                <option value="1">🎁 BUNDLE / Paket Set</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Tipe Pre-Order (PO)</label>
                            <select name="is_preorder" class="form-select form-select-sm">
                                <option value="">Semua Tipe (PO & Reguler)</option>
                                <option value="1">⏳ Pre-Order (PO)</option>
                                <option value="0">📦 Reguler (Bukan PO)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Cari Nama / SKU</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci nama produk atau SKU...">
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock">
                                <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                    Sembunyikan / Hilangkan Produk Stok 0
                                </label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-print me-1"></i> Cetak Laporan Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
