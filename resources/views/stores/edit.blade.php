@extends('layouts.app')
@section('title', 'Edit Toko')
@section('page-title', 'Edit Toko')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('stores.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="dashboard-card" style="max-width: 600px; margin-top: 1rem;">
        <div class="card-header-line">
            <h3><i class="fas fa-edit"></i> Edit Toko</h3>
        </div>

        <form action="{{ route('stores.update', $store) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Platform</label>
                <div class="input-wrapper">
                    <i class="fas fa-plug input-icon"></i>
                    <input type="text" class="form-input" value="{{ $store->channel->name }}" disabled>
                </div>
            </div>

            <div class="form-group">
                <label for="store_name" class="form-label">Nama Toko</label>
                <div class="input-wrapper">
                    <i class="fas fa-store input-icon"></i>
                    <input type="text" id="store_name" name="store_name"
                        class="form-input" value="{{ old('store_name', $store->store_name) }}" required>
                </div>
                @error('store_name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status Koneksi</label>
                <div class="input-wrapper">
                    <i class="fas fa-signal input-icon"></i>
                    <select id="status" name="status" class="form-input" style="padding-left:2.5rem;">
                        <option value="connected" {{ old('status', $store->status) === 'connected' ? 'selected' : '' }}>Terhubung</option>
                        <option value="disconnected" {{ old('status', $store->status) === 'disconnected' ? 'selected' : '' }}>Terputus</option>
                    </select>
                </div>
                @error('status')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <form action="{{ route('stores.destroy', $store) }}" method="POST" style="display:inline;"
                    onsubmit="return confirm('Hapus toko ini? Semua data terkait akan ikut terhapus.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-sm"><i class="fas fa-trash"></i> Hapus</button>
                </form>
                <div style="display:flex; gap:0.5rem; margin-left:auto;">
                    <a href="{{ route('stores.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-auth" style="width:auto; padding: 0.7rem 1.5rem;">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
