@extends('layouts.app')
@section('title', 'Tambah Tukang Jahit')
@section('page-title', 'Tambah Tukang Jahit')

@section('content')
<div class="mx-auto" style="max-width: 600px;">
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
            <div>
                <h5 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Tambah Tukang Jahit</h5>
            </div>
            <a href="{{ route('tailors.index') }}" class="btn btn-sm btn-light fw-semibold px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <form action="{{ route('tailors.store') }}" method="POST" class="m-0">
            @csrf
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required placeholder="Contoh: Pak Slamet" value="{{ old('name') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">No. HP / Telepon</label>
                    <input type="text" name="phone" class="form-control form-control-sm" placeholder="Contoh: 0812-3456-7890" value="{{ old('phone') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alamat</label>
                    <textarea name="address" class="form-control form-control-sm" rows="3" placeholder="Alamat lengkap penjahit...">{{ old('address') }}</textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold small d-block">Status Keaktifan <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_active" id="active_yes" value="1" checked>
                        <label class="form-check-label small" for="active_yes">Aktif</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_active" id="active_no" value="0">
                        <label class="form-check-label small" for="active_no">Non-Aktif</label>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light py-3 px-4 d-flex justify-content-end gap-2">
                <a href="{{ route('tailors.index') }}" class="btn btn-sm btn-outline-secondary px-3">Batal</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
