@extends('layouts.app')
@php
    $isEdit = isset($employee->id);
    $title = $isEdit ? 'Edit Karyawan' : 'Tambah Karyawan';
    $actionUrl = $isEdit ? route('employees.update', $employee->id) : route('employees.store');
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
                        <a href="{{ route('employees.index') }}" class="text-decoration-none">
                            <i class="fas fa-users me-1"></i>Karyawan
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </nav>

            <div class="dashboard-card p-0 overflow-hidden">

                {{-- Header --}}
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div
                        class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2" style="width: 38px; height: 38px;">
                        <i class="fas fa-{{ $isEdit ? 'user-pen' : 'user-plus' }}"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $title }}</h5>
                        <p class="mb-0 text-muted small">
                            {{ $isEdit ? 'Perbarui informasi data profil karyawan' : 'Tambahkan data profil karyawan baru' }}
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

                    {{-- Nama Karyawan --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-user me-1 text-primary"></i>
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name"
                            class="form-control form-control-sm @error('name') is-invalid @enderror"
                            placeholder="Contoh: Budi Santoso" value="{{ old('name', $employee->name ?? '') }}"
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email & Telepon --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-envelope me-1 text-primary"></i>
                                Email
                            </label>
                            <input type="email" name="email"
                                class="form-control form-control-sm @error('email') is-invalid @enderror"
                                placeholder="budi@example.com"
                                value="{{ old('email', $employee->email ?? '') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-phone me-1 text-primary"></i>
                                No. Handphone
                            </label>
                            <input type="text" name="phone"
                                class="form-control form-control-sm @error('phone') is-invalid @enderror"
                                placeholder="08xxxxxxxxxx" value="{{ old('phone', $employee->phone ?? '') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Posisi & Tanggal Bergabung --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-briefcase me-1 text-primary"></i>
                                Posisi / Jabatan
                            </label>
                            <input type="text" name="position"
                                class="form-control form-control-sm @error('position') is-invalid @enderror"
                                placeholder="Staf Admin, Kasir, dll"
                                value="{{ old('position', $employee->position ?? '') }}">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt me-1 text-primary"></i>
                                Tanggal Bergabung
                            </label>
                            <input type="date" name="join_date"
                                class="form-control form-control-sm @error('join_date') is-invalid @enderror"
                                value="{{ old('join_date', $employee->join_date ? $employee->join_date->format('Y-m-d') : '') }}">
                            @error('join_date')
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
                            placeholder="Alamat tempat tinggal...包装">{{ old('address', $employee->address ?? '') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-4">
                        <div>
                            <div class="fw-semibold small">Status Karyawan</div>
                            <div class="text-muted small">
                                Karyawan aktif dapat melakukan presensi mandiri dan dimasukkan dalam daftar gaji bulanan
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0 ms-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1"
                                id="isActive" {{ old('is_active', $employee->is_active ?? true) ? 'checked' : '' }}>
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
                            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
