@extends('layouts.app')
@section('title', isset($category->id) ? 'Edit Kategori' : 'Tambah Kategori')
@section('page-title', isset($category->id) ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')

    <div class="mb-3">
        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="row justify-content-start">
        <div class="col-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold">
                        @if (isset($category->id))
                            <i class="fas fa-edit me-2 text-primary"></i>Edit Kategori
                        @else
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Tambah Kategori
                        @endif
                    </h6>
                </div>
                <div class="card-body">
                    <form
                        action="{{ isset($category->id) ? route('categories.update', $category) : route('categories.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($category->id))
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                <input type="text" id="name" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $category->name ?? '') }}" placeholder="Masukkan nama kategori"
                                    required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ isset($category->id) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>

                @if (isset($category->id))
                    <div class="card-footer border-top py-3">
                        <form action="{{ route('categories.destroy', $category) }}" method="POST"
                            onsubmit="return confirm('Hapus kategori \'{{ $category->name }}\'? Pastikan tidak ada produk yang menggunakannya.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash me-1"></i>Hapus Kategori Ini
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
