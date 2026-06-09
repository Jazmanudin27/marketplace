@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Master Produk')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('products.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="dashboard-card" style="max-width: 700px; margin-top: 1rem;">
        <div class="card-header-line">
            <h3><i class="fas fa-plus-circle"></i> Tambah Produk Baru</h3>
        </div>

        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="form-row-2">
                <div class="form-group">
                    <label for="sku" class="form-label">SKU <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-barcode input-icon"></i>
                        <input type="text" id="sku" name="sku" class="form-input"
                            placeholder="Contoh: SKU-001" value="{{ old('sku') }}" required>
                    </div>
                    @error('sku')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="unit" class="form-label">Satuan</label>
                    <div class="input-wrapper">
                        <i class="fas fa-cube input-icon"></i>
                        <input type="text" id="unit" name="unit" class="form-input"
                            placeholder="pcs, kg, box..." value="{{ old('unit') }}">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="name" class="form-label">Nama Produk <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-tag input-icon"></i>
                    <input type="text" id="name" name="name" class="form-input"
                        placeholder="Nama produk lengkap" value="{{ old('name') }}" required>
                </div>
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="price" class="form-label">Harga Jual (Rp) <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill input-icon"></i>
                        <input type="number" id="price" name="price" class="form-input"
                            placeholder="0" value="{{ old('price') }}" min="0" step="100" required>
                    </div>
                    @error('price')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="cost_price" class="form-label">Harga Modal (Rp)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-coins input-icon"></i>
                        <input type="number" id="cost_price" name="cost_price" class="form-input"
                            placeholder="0" value="{{ old('cost_price') }}" min="0" step="100">
                    </div>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="stock" class="form-label">Stok Awal <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-warehouse input-icon"></i>
                        <input type="number" id="stock" name="stock" class="form-input"
                            placeholder="0" value="{{ old('stock', 0) }}" min="0" required>
                    </div>
                    @error('stock')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="min_stock" class="form-label">Stok Minimum (Safety Stock)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-exclamation-triangle input-icon"></i>
                        <input type="number" id="min_stock" name="min_stock" class="form-input"
                            placeholder="0" value="{{ old('min_stock', 0) }}" min="0">
                    </div>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="category" class="form-label">Kategori</label>
                    <div class="input-wrapper">
                        <i class="fas fa-folder input-icon"></i>
                        <input type="text" id="category" name="category" class="form-input"
                            placeholder="Fashion, Elektronik..." value="{{ old('category') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="brand" class="form-label">Merk / Brand</label>
                    <div class="input-wrapper">
                        <i class="fas fa-certificate input-icon"></i>
                        <input type="text" id="brand" name="brand" class="form-input"
                            placeholder="Nama merk" value="{{ old('brand') }}">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-auth" style="width:auto; padding: 0.7rem 1.5rem;">
                    <i class="fas fa-save"></i> Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
