@extends('layouts.app')
@section('title', 'Edit Toko')
@section('page-title', 'Edit Toko')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-8">
            <a href="{{ route('stores.index') }}" class="btn btn-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <div class="dashboard-card">
                <div class="card-header-line mb-4">
                    <h3><i class="fas fa-edit"></i> Edit Toko</h3>
                </div>

                <form id="edit-store-form" action="{{ route('stores.update', $store) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label text-muted">Platform</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"
                                style="background-color: var(--bg-card); border-color: var(--border); color: var(--text-muted);"><i
                                    class="fas fa-plug"></i></span>
                            <input type="text" class="form-control form-control-sm form-control-dark"
                                value="{{ $store->channel->name }}" disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="store_name" class="form-label text-muted">Nama Toko <span
                                class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"
                                style="background-color: var(--bg-card); border-color: var(--border); color: var(--text-muted);"><i
                                    class="fas fa-store"></i></span>
                            <input type="text" id="store_name" name="store_name"
                                class="form-control form-control-sm form-control-dark @error('store_name') is-invalid @enderror"
                                value="{{ old('store_name', $store->store_name) }}" required>
                        </div>
                        @error('store_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label text-muted">Status Koneksi <span
                                class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"
                                style="background-color: var(--bg-card); border-color: var(--border); color: var(--text-muted);"><i
                                    class="fas fa-signal"></i></span>
                            <select id="status" name="status"
                                class="form-select form-select-sm form-select-dark @error('status') is-invalid @enderror">
                                <option value="connected"
                                    {{ old('status', $store->status) === 'connected' ? 'selected' : '' }}>Terhubung</option>
                                <option value="disconnected"
                                    {{ old('status', $store->status) === 'disconnected' ? 'selected' : '' }}>Terputus
                                </option>
                            </select>
                        </div>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="shipping_handover_method" class="form-label text-muted">Metode Penyerahan Kurir <span
                                class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"
                                style="background-color: var(--bg-card); border-color: var(--border); color: var(--text-muted);"><i
                                    class="fas fa-truck-loading"></i></span>
                            <select id="shipping_handover_method" name="shipping_handover_method"
                                class="form-select form-select-sm form-select-dark @error('shipping_handover_method') is-invalid @enderror">
                                <option value="DROP_OFF"
                                    {{ old('shipping_handover_method', $store->shipping_handover_method) === 'DROP_OFF' ? 'selected' : '' }}>Drop-off (Antar ke Cabang)</option>
                                <option value="PICK_UP"
                                    {{ old('shipping_handover_method', $store->shipping_handover_method) === 'PICK_UP' ? 'selected' : '' }}>Pickup (Dijemput Kurir)</option>
                            </select>
                        </div>
                        @error('shipping_handover_method')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </form>

                <hr style="border-color: var(--border);" class="my-4">

                <div class="d-flex justify-content-between align-items-center">
                    <!-- Form Delete Terpisah (Tidak Boleh Nested) -->
                    <form action="{{ route('stores.destroy', $store) }}" method="POST"
                        onsubmit="return confirm('Hapus toko ini? Semua data terkait akan ikut terhapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Hapus Toko
                        </button>
                    </form>

                    <!-- Tombol Simpan mengarah ke form Edit di atas -->
                    <div>
                        <a href="{{ route('stores.index') }}" class="btn btn-secondary btn-sm me-2">Batal</a>
                        <button type="submit" form="edit-store-form" class="btn btn-success btn-sm">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
