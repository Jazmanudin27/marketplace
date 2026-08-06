@extends('layouts.app')
@section('title', 'Laporan Stok Barang (Gudang & Marketplace)')
@section('page-title', 'Laporan Stok Barang (Gudang & Marketplace)')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
            <div class="card-header bg-white py-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-boxes-stacked fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Filter Laporan Stok Barang (Gudang & Marketplace)</h5>
                        <small class="text-secondary">Pilih kriteria filter untuk mencetak laporan persediaan stok barang gudang & marketplace.</small>
                    </div>
                </div>
            </div>

            <form action="{{ route('reports.stock.print') }}" method="GET" target="_blank" id="stockFilterForm">
                <div class="card-body p-4">

                    <div class="row g-3">
                        {{-- Kategori --}}
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label form-label-sm fw-bold text-dark mb-1">
                                <i class="fas fa-layer-group text-secondary me-1"></i>Kategori Produk
                            </label>
                            <select name="category_id" class="form-select form-select-sm bg-white">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Merk --}}
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label form-label-sm fw-bold text-dark mb-1">
                                <i class="fas fa-tags text-secondary me-1"></i>Merk / Brand
                            </label>
                            <select name="brand_id" class="form-select form-select-sm bg-white">
                                <option value="">Semua Merk</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Toko / Marketplace --}}
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label form-label-sm fw-bold text-dark mb-1">
                                <i class="fas fa-store text-secondary me-1"></i>Toko / Marketplace
                            </label>
                            <select name="store_id" class="form-select form-select-sm bg-white">
                                <option value="">Semua Toko Marketplace</option>
                                @foreach ($stores as $st)
                                    <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                        {{ $st->store_name }} ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Jenis Produk --}}
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label form-label-sm fw-bold text-dark mb-1">
                                <i class="fas fa-box text-secondary me-1"></i>Jenis Produk
                            </label>
                            <select name="is_bundle" class="form-select form-select-sm bg-white">
                                <option value="">Semua Jenis (Single & BUNDLE)</option>
                                <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single (Produk Standar)</option>
                                <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 BUNDLE / Paket Set</option>
                            </select>
                        </div>

                        {{-- Tipe Pre-Order --}}
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label form-label-sm fw-bold text-dark mb-1">
                                <i class="fas fa-clock text-secondary me-1"></i>Tipe Pre-Order (PO)
                            </label>
                            <select name="is_preorder" class="form-select form-select-sm bg-white">
                                <option value="">Semua Tipe (PO & Reguler)</option>
                                <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                                <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                            </select>
                        </div>

                        {{-- Cari Nama / SKU --}}
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label form-label-sm fw-bold text-dark mb-1">
                                <i class="fas fa-search text-secondary me-1"></i>Cari Nama / SKU
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm bg-white" placeholder="Ketik nama produk atau SKU...">
                        </div>

                        {{-- Switches & Options --}}
                        <div class="col-12 mt-3 pt-3 border-top">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                            Sembunyikan Produk dengan Stok 0
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="only_different" value="1" id="onlyDifferent" {{ request()->boolean('only_different') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-danger" for="onlyDifferent">
                                            ⚠️ Hanya Tampilkan Stok Berbeda (Beda Gudang vs Marketplace)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-light py-3 px-4 d-flex justify-content-end align-items-center border-top">
                    <button type="submit" class="btn btn-success btn-md px-4 rounded-3 fw-bold shadow-sm">
                        <i class="fas fa-print me-2"></i> Cetak Laporan Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
