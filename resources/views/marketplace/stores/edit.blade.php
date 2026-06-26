@extends('layouts.app')
@section('title', 'Edit Toko')
@section('page-title', 'Edit Toko')

@section('content')
    <div class="row justify-content-start">
        <div class="col-12 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('stores.index') }}" class="btn btn-sm btn-outline-secondary rounded-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-edit fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Edit Detail Toko</h5>
                    </div>

                    <form id="edit-store-form" action="{{ route('stores.update', $store) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Platform (Disabled) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Platform Marketplace</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-plug"></i></span>
                                <input type="text" class="form-control bg-light border-start-0" value="{{ $store->channel->name }}" disabled>
                            </div>
                        </div>

                        <!-- Nama Toko -->
                        <div class="mb-3">
                            <label for="store_name" class="form-label fw-semibold small text-muted">Nama Toko <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-store"></i></span>
                                <input type="text" id="store_name" name="store_name"
                                    class="form-control border-start-0 @error('store_name') is-invalid @enderror"
                                    value="{{ old('store_name', $store->store_name) }}" required>
                            </div>
                            @error('store_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status Koneksi -->
                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold small text-muted">Status Koneksi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-signal"></i></span>
                                <select id="status" name="status"
                                    class="form-select border-start-0 @error('status') is-invalid @enderror">
                                    <option value="connected" {{ old('status', $store->status) === 'connected' ? 'selected' : '' }}>Terhubung</option>
                                    <option value="disconnected" {{ old('status', $store->status) === 'disconnected' ? 'selected' : '' }}>Terputus</option>
                                </select>
                            </div>
                            @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Metode Penyerahan -->
                        <div class="mb-4">
                            <label for="shipping_handover_method" class="form-label fw-semibold small text-muted">Metode Penyerahan Kurir <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-truck-loading"></i></span>
                                <select id="shipping_handover_method" name="shipping_handover_method"
                                    class="form-select border-start-0 @error('shipping_handover_method') is-invalid @enderror">
                                    <option value="DROP_OFF" {{ old('shipping_handover_method', $store->shipping_handover_method) === 'DROP_OFF' ? 'selected' : '' }}>Drop-off (Antar ke Cabang)</option>
                                    <option value="PICK_UP" {{ old('shipping_handover_method', $store->shipping_handover_method) === 'PICK_UP' ? 'selected' : '' }}>Pickup (Dijemput Kurir)</option>
                                </select>
                            </div>
                            @error('shipping_handover_method')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>

                    <hr class="my-4 text-muted opacity-25">

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3">
                        <!-- Form Delete Terpisah (Tidak Boleh Nested) -->
                        <form action="{{ route('stores.destroy', $store) }}" method="POST"
                            onsubmit="return confirm('Hapus toko ini? Semua data terkait akan ikut terhapus.')"
                            class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100 rounded-3">
                                <i class="fas fa-trash me-1"></i> Hapus Toko
                            </button>
                        </form>

                        <div class="d-flex gap-2">
                            <a href="{{ route('stores.index') }}" class="btn btn-light border flex-fill px-4 rounded-3">Batal</a>
                            <button type="submit" form="edit-store-form" class="btn btn-success flex-fill px-4 rounded-3 fw-semibold">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
