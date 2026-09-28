@extends('v2.layouts.app')

@section('title', 'Edit Master Produk V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square text-primary fs-5"></i> Edit Master Produk V2
        </h1>
        <p class="v2-page-subtitle mb-0">Ubah rincian informasi produk master, SKU, harga, dan alokasi stok</p>
    </div>
    <div>
        <a href="{{ route('v2.produk.index') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Produk
        </a>
    </div>
</div>

<!-- Full Width Edit Form Container -->
<form method="POST" action="{{ route('v2.produk.update', $product->id) }}">
    @csrf
    @method('PUT')

    <div class="v2-card mb-3 shadow-sm border w-100">
        <div class="v2-card-header bg-light py-2.5">
            <h6 class="v2-card-title d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-primary"></i> Informasi Utama Produk Master
            </h6>
            <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-0.5" style="font-size: 0.72rem;">
                SKU: {{ $product->sku }}
            </span>
        </div>
        <div class="v2-card-body p-3.5">
            <div class="row g-3">
                <!-- Nama Produk -->
                <div class="col-12">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Nama Produk Master <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SKU & SKU Induk -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Kode SKU Master <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control form-control-sm font-monospace @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}" required>
                    @error('sku')
                        <div class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Kode SKU Induk (Parent SKU)</label>
                    <input type="text" name="sku_induk" class="form-control form-control-sm font-monospace" value="{{ old('sku_induk', $product->sku_induk ?? $product->sku) }}">
                </div>

                <!-- Kategori & Brand -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Kategori Produk</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Brand / Merek</label>
                    <select name="brand_id" class="form-select form-select-sm">
                        <option value="">-- Tanpa Brand --</option>
                        @foreach($brands as $br)
                            <option value="{{ $br->id }}" {{ old('brand_id', $product->brand_id) == $br->id ? 'selected' : '' }}>
                                {{ $br->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Harga Beli (HPP) & Harga Jual -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Harga Beli / HPP (Rp)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="cost_price" class="form-control font-monospace" value="{{ old('cost_price', (int)($product->cost_price ?? 0)) }}" min="0">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Harga Jual Acuan (Rp)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="price" class="form-control font-monospace" value="{{ old('price', (int)($product->selling_price ?? $product->price ?? 0)) }}" min="0">
                    </div>
                </div>

                <!-- Estimasi Kain & Est. Biaya Produksi -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Estimasi Kain (CM/Pcs)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="est_kain" class="form-control font-monospace" placeholder="150" value="{{ old('est_kain', $product->est_kain) }}" step="0.1" min="0">
                        <span class="input-group-text">CM / Pcs</span>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Est. Biaya Produksi (Rp)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="est_biaya_produksi" class="form-control font-monospace" placeholder="0" value="{{ old('est_biaya_produksi', (int)($product->est_biaya_produksi ?? 0)) }}" min="0">
                    </div>
                </div>

                <!-- Stok, Min Stock & Satuan -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Jumlah Stok Gudang</label>
                    <input type="number" name="stock" class="form-control form-control-sm font-monospace" value="{{ old('stock', $product->stock ?? 0) }}" min="0">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Ambang Stok Minimal</label>
                    <input type="number" name="min_stock" class="form-control form-control-sm font-monospace" value="{{ old('min_stock', $product->min_stock ?? 5) }}" min="0">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold" style="font-size: 0.78rem;">Satuan Unit</label>
                    <input type="text" name="unit" class="form-control form-control-sm" value="{{ old('unit', $product->unit ?? 'pcs') }}">
                </div>

                <!-- Config Checklist (Bundle / Preorder) -->
                <div class="col-12 col-md-6">
                    <div class="p-2.5 border rounded bg-light">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_bundle" name="is_bundle" value="1" {{ old('is_bundle', $product->is_bundle) ? 'checked' : '' }}>
                            <label class="form-check-input-label fw-bold text-dark ms-1" for="is_bundle" style="font-size: 0.78rem;">
                                Produk Paket / Bundle
                            </label>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.68rem;">Centang jika produk ini merupakan gabungan beberapa komponen barang.</div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-2.5 border rounded bg-light">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_preorder" name="is_preorder" value="1" {{ old('is_preorder', $product->is_preorder) ? 'checked' : '' }}>
                            <label class="form-check-input-label fw-bold text-dark ms-1" for="is_preorder" style="font-size: 0.78rem;">
                                Status Pre-Order (PO)
                            </label>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.68rem;">Centang jika produk ini dijual dengan sistem indent / PO.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="v2-card-footer bg-light p-3 border-top d-flex align-items-center justify-content-between">
            <a href="{{ route('v2.produk.index') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-3">
                <i class="bi bi-x-circle me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-4 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Produk
            </button>
        </div>
    </div>
</form>
@endsection
