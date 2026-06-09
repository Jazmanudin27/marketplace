@extends('layouts.app')
@section('title', isset($category->id) ? 'Edit Kategori' : 'Tambah Kategori')
@section('page-title', isset($category->id) ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('categories.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="dashboard-card" style="max-width: 500px; margin-top: 1rem;">
        <div class="card-header-line">
            <h3>
                @if(isset($category->id))
                    <i class="fas fa-edit"></i> Edit Kategori
                @else
                    <i class="fas fa-plus-circle"></i> Tambah Kategori
                @endif
            </h3>
        </div>

        <form action="{{ isset($category->id) ? route('categories.update', $category) : route('categories.store') }}" method="POST">
            @csrf
            @if(isset($category->id)) @method('PUT') @endif

            <div class="form-group">
                <label for="name" class="form-label">Nama Kategori <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-folder input-icon"></i>
                    <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $category->name ?? '') }}" required>
                </div>
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-actions" style="display:flex; justify-content:flex-end; gap:0.5rem;">
                <a href="{{ route('categories.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-auth" style="width:auto; padding: 0.7rem 1.5rem;">
                    <i class="fas fa-save"></i> {{ isset($category->id) ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
            </div>
        </form>

        @if(isset($category->id))
        <div style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
            <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori ini? Pastikan tidak ada produk yang menggunakannya.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger-sm"><i class="fas fa-trash"></i> Hapus Kategori Ini</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
