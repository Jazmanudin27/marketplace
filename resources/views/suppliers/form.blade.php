@extends('layouts.app')

@php
    $isEdit = isset($supplier);
    $title = $isEdit ? 'Edit Supplier' : 'Tambah Supplier';
    $actionUrl = $isEdit ? route('suppliers.update', $supplier->id) : route('suppliers.store');
@endphp

@section('title', $title)
@section('page-title', $title)

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-8">
            <div class="dashboard-card">
                <div class="card-header-line mb-4">
                    <h3><i class="fas {{ $isEdit ? 'fa-edit' : 'fa-plus-circle' }}"></i> Form {{ $title }}</h3>
                </div>
                <form action="{{ $actionUrl }}" method="POST">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label class="form-label text-muted">Nama Supplier / Perusahaan <span
                                class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm form-control-dark" required
                            value="{{ old('name', $supplier->name ?? '') }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Kontak Person / PIC</label>
                            <input type="text" name="contact_person"
                                class="form-control form-control-sm form-control-dark"
                                value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">No. HP / Telepon</label>
                            <input type="text" name="phone" class="form-control form-control-sm form-control-dark"
                                value="{{ old('phone', $supplier->phone ?? '') }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted">Alamat Lengkap</label>
                        <textarea name="address" class="form-control form-control-sm form-control-dark" rows="3">{{ old('address', $supplier->address ?? '') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check d-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" name="is_active" value="1"
                                id="isActive" {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label text-muted" for="isActive" style="padding-top: 2px;">
                                Status Aktif (Bisa dipilih pada saat pembelian)
                            </label>
                        </div>
                    </div>

                    <hr style="border-color: var(--border);" class="my-4">

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm me-2">Batal</a>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
