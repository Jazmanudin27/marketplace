@extends('layouts.app')
@section('title', isset($brand->id) ? 'Edit Merk' : 'Tambah Merk')
@section('page-title', isset($brand->id) ? 'Edit Merk' : 'Tambah Merk')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('brands.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="dashboard-card" style="max-width: 500px; margin-top: 1rem;">
        <div class="card-header-line">
            <h3>
                @if(isset($brand->id))
                    <i class="fas fa-edit"></i> Edit Merk
                @else
                    <i class="fas fa-plus-circle"></i> Tambah Merk
                @endif
            </h3>
        </div>

        <form action="{{ isset($brand->id) ? route('brands.update', $brand) : route('brands.store') }}" method="POST">
            @csrf
            @if(isset($brand->id)) @method('PUT') @endif

            <div class="form-group">
                <label for="name" class="form-label">Nama Merk <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-certificate input-icon"></i>
                    <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $brand->name ?? '') }}" required>
                </div>
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-actions" style="display:flex; justify-content:flex-end; gap:0.5rem;">
                <a href="{{ route('brands.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-auth" style="width:auto; padding: 0.7rem 1.5rem;">
                    <i class="fas fa-save"></i> {{ isset($brand->id) ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
            </div>
        </form>

        @if(isset($brand->id))
        <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
            <form action="{{ route('brands.destroy', $brand) }}" method="POST" onsubmit="return confirm('Hapus merk ini? Pastikan tidak ada produk yang menggunakannya.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger-sm"><i class="fas fa-trash"></i> Hapus Merk Ini</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
