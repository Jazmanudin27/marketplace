@extends('layouts.app')
@section('title', 'Edit Produk')
@section('page-title', 'Edit Master Produk')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('products.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="dashboard-card" style="max-width: 700px; margin-top: 1rem;">
        <div class="card-header-line">
            <h3><i class="fas fa-edit"></i> Edit Produk — <span class="mono" style="color:var(--primary)">{{ $product->sku }}</span></h3>
        </div>

        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <div class="input-wrapper">
                        <i class="fas fa-barcode input-icon"></i>
                        <input type="text" class="form-input" value="{{ $product->sku }}" disabled>
                    </div>
                </div>
                <div class="form-group">
                    <label for="unit" class="form-label">Satuan</label>
                    <div class="input-wrapper">
                        <i class="fas fa-cube input-icon"></i>
                        <input type="text" id="unit" name="unit" class="form-input"
                            value="{{ old('unit', $product->unit) }}" placeholder="pcs, kg, box...">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="name" class="form-label">Nama Produk <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-tag input-icon"></i>
                    <input type="text" id="name" name="name" class="form-input"
                        value="{{ old('name', $product->name) }}" required>
                </div>
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="price" class="form-label">Harga Jual (Rp) <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill input-icon"></i>
                        <input type="number" id="price" name="price" class="form-input"
                            value="{{ old('price', $product->price) }}" min="0" step="100" required>
                    </div>
                    @error('price')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="cost_price" class="form-label">Harga Modal (Rp)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-coins input-icon"></i>
                        <input type="number" id="cost_price" name="cost_price" class="form-input"
                            value="{{ old('cost_price', $product->cost_price) }}" min="0" step="100">
                    </div>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="stock" class="form-label">Stok Fisik <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-warehouse input-icon"></i>
                        <input type="number" id="stock" name="stock" class="form-input"
                            value="{{ old('stock', $product->stock) }}" min="0" required>
                    </div>
                    @error('stock')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="min_stock" class="form-label">Stok Minimum</label>
                    <div class="input-wrapper">
                        <i class="fas fa-exclamation-triangle input-icon"></i>
                        <input type="number" id="min_stock" name="min_stock" class="form-input"
                            value="{{ old('min_stock', $product->min_stock) }}" min="0">
                    </div>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="category" class="form-label">Kategori</label>
                    <div class="input-wrapper">
                        <i class="fas fa-folder input-icon"></i>
                        <input type="text" id="category" name="category" class="form-input"
                            value="{{ old('category', $product->category) }}" placeholder="Fashion, Elektronik...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="brand" class="form-label">Merk / Brand</label>
                    <div class="input-wrapper">
                        <i class="fas fa-certificate input-icon"></i>
                        <input type="text" id="brand" name="brand" class="form-input"
                            value="{{ old('brand', $product->brand) }}" placeholder="Nama merk">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <form action="{{ route('products.destroy', $product) }}" method="POST" style="display:inline;"
                    onsubmit="return confirm('Hapus produk ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-sm"><i class="fas fa-trash"></i> Hapus</button>
                </form>
                <div style="display:flex; gap:0.5rem; margin-left:auto;">
                    <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-auth" style="width:auto; padding: 0.7rem 1.5rem;">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
