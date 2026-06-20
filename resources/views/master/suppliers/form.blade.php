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
        <div class="col-md-8 col-lg-7">

            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('suppliers.index') }}" class="text-decoration-none">
                            <i class="fas fa-truck me-1"></i>Supplier
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </nav>

            <div class="dashboard-card p-0 overflow-hidden">

                {{-- Header --}}
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div
                        class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2">
                        <i class="fas fa-{{ $isEdit ? 'pen' : 'truck' }}"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $title }}</h5>
                        <p class="mb-0 text-muted small">
                            {{ $isEdit ? 'Perbarui informasi supplier yang ada' : 'Tambahkan supplier atau vendor baru' }}
                        </p>
                    </div>
                </div>

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="p-4 pb-0">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa kembali inputan Anda:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ $actionUrl }}" method="POST" class="p-4">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    {{-- Nama Supplier --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-store me-1 text-primary"></i>
                            Nama Supplier / Perusahaan <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name"
                            class="form-control form-control-sm @error('name') is-invalid @enderror"
                            placeholder="Contoh: PT. Maju Bersama Tekstil" value="{{ old('name', $supplier->name ?? '') }}"
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Kontak & Telepon --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-tie me-1 text-primary"></i>
                                Kontak Person / PIC
                            </label>
                            <input type="text" name="contact_person"
                                class="form-control form-control-sm @error('contact_person') is-invalid @enderror"
                                placeholder="Nama penanggung jawab"
                                value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
                            @error('contact_person')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-phone me-1 text-primary"></i>
                                No. HP / Telepon
                            </label>
                            <input type="text" name="phone"
                                class="form-control form-control-sm @error('phone') is-invalid @enderror"
                                placeholder="08xx-xxxx-xxxx" value="{{ old('phone', $supplier->phone ?? '') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                            Alamat Lengkap
                        </label>
                        <textarea name="address" rows="3" class="form-control form-control-sm @error('address') is-invalid @enderror"
                            placeholder="Jl. ..., Kecamatan, Kota, Provinsi">{{ old('address', $supplier->address ?? '') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-4">
                        <div>
                            <div class="fw-semibold small">Status Supplier</div>
                            <div class="text-muted small">
                                Supplier aktif dapat dipilih saat membuat pesanan pembelian
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0 ms-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1"
                                id="isActive" {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-secondary btn-sm px-4 d-inline-flex align-items-center gap-1"
                            onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 d-inline-flex align-items-center gap-1">
                            <i class="fas fa-{{ $isEdit ? 'save' : 'plus' }}"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Supplier' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
