@extends('layouts.app')
@section('title', isset($product->id) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', isset($product->id) ? 'Edit Master Produk' : 'Tambah Master Produk')

@section('content')
    <div class="form-page-wrapper">
        <a href="{{ route('products.index') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="dashboard-card col-md-12">
            <div class="card-header-line">
                <h3>
                    @if (isset($product->id))
                        <i class="fas fa-edit"></i> Edit Produk — <span class="mono"
                            style="color:var(--primary)">{{ $product->sku }}</span>
                    @else
                        <i class="fas fa-plus-circle"></i> Tambah Produk Baru
                    @endif
                </h3>
            </div>

            <form action="{{ isset($product->id) ? route('products.update', $product) : route('products.store') }}"
                method="POST">
                @csrf
                @if (isset($product->id))
                    @method('PUT')
                @endif

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">SKU</label>
                        <div class="input-wrapper">
                            <i class="fas fa-barcode input-icon"></i>
                            <input type="text" name="sku" class="form-input"
                                value="{{ old('sku', $product->sku ?? '') }}"
                                {{ isset($product->id) ? 'disabled' : 'required' }} placeholder="Contoh: SKU-001">
                        </div>
                        @error('sku')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="unit" class="form-label">Satuan</label>
                        <div class="input-wrapper">
                            <i class="fas fa-cube input-icon"></i>
                            <input type="text" id="unit" name="unit" class="form-input"
                                value="{{ old('unit', $product->unit ?? '') }}" placeholder="pcs, kg, box...">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name" class="form-label">Nama Produk <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-tag input-icon"></i>
                        <input type="text" id="name" name="name" class="form-input"
                            value="{{ old('name', $product->name ?? '') }}" required placeholder="Nama produk lengkap">
                    </div>
                    @error('name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="price" class="form-label">Harga Jual (Rp) <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-money-bill input-icon"></i>
                            <input type="number" id="price" name="price" class="form-input"
                                value="{{ old('price', $product->price ?? '') }}" min="0" step="100" required
                                placeholder="0">
                        </div>
                        @error('price')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="cost_price" class="form-label">Harga Modal (Rp)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-wallet input-icon"></i>
                            <input type="number" id="cost_price" name="cost_price" class="form-input"
                                value="{{ old('cost_price', $product->cost_price ?? '') }}" min="0" step="100"
                                placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="stock" class="form-label">Stok Fisik <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-warehouse input-icon"></i>
                            <input type="number" id="stock" name="stock" class="form-input"
                                value="{{ old('stock', $product->stock ?? 0) }}" min="0" required placeholder="0">
                        </div>
                        @error('stock')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="min_stock" class="form-label">Stok Minimum</label>
                        <div class="input-wrapper">
                            <i class="fas fa-exclamation-triangle input-icon"></i>
                            <input type="number" id="min_stock" name="min_stock" class="form-input"
                                value="{{ old('min_stock', $product->min_stock ?? 0) }}" min="0" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="category_id" class="form-label">Kategori</label>
                        <div class="input-wrapper">
                            <i class="fas fa-folder input-icon"></i>
                            <select id="category_id" name="category_id" class="form-input" style="padding-left: 2.5rem;">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="brand_id" class="form-label">Merk / Brand</label>
                        <div class="input-wrapper">
                            <i class="fas fa-certificate input-icon"></i>
                            <select id="brand_id" name="brand_id" class="form-input" style="padding-left: 2.5rem;">
                                <option value="">-- Pilih Merk --</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-actions" style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-auth" style="width:auto; padding: 0.7rem 1.5rem;">
                        <i class="fas fa-save"></i> {{ isset($product->id) ? 'Simpan Perubahan' : 'Simpan Produk' }}
                    </button>
                </div>
            </form>

            @if (isset($product->id))
                <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <form action="{{ route('products.destroy', $product) }}" method="POST"
                        onsubmit="return confirm('Hapus produk ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger-sm"><i class="fas fa-trash"></i> Hapus Produk
                            Ini</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
