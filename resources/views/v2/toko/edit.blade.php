@extends('v2.layouts.app')

@section('title', 'Edit Pengaturan Toko V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-gear-fill text-primary fs-5"></i> Pengaturan Toko "{{ $store->store_name }}"
        </h1>
        <p class="v2-page-subtitle mb-0">Ubah nama toko, status koneksi, metode penyerahan paket (Handover Shipping), dan logo toko.</p>
    </div>
    <div>
        <a href="{{ url('/v2/toko') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Toko
        </a>
    </div>
</div>

<div class="row justify-content-start">
    <div class="col-12 col-lg-8">
        <div class="v2-card shadow-sm border mb-3">
            <div class="v2-card-header bg-light py-2.5">
                <h6 class="v2-card-title d-flex align-items-center gap-2 m-0">
                    <i class="bi bi-shop text-primary"></i> Detail & Konfigurasi Toko
                </h6>
            </div>
            <div class="v2-card-body p-3.5">
                <form action="{{ url('/v2/toko/' . $store->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label form-label-sm fw-bold text-dark">Channel Marketplace</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $store->channel->name ?? 'Marketplace' }}" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label form-label-sm fw-bold text-dark">Shop ID / Code Marketplace</label>
                            <input type="text" class="form-control form-control-sm font-monospace bg-light" value="{{ $store->marketplace_store_id }}" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="store_name" class="form-label form-label-sm fw-bold text-dark">Nama Toko di ERP <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" id="store_name" class="form-control form-control-sm" value="{{ old('store_name', $store->store_name) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label form-label-sm fw-bold text-dark">Status Koneksi Toko <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select form-select-sm" required>
                                <option value="connected" {{ old('status', $store->status) == 'connected' ? 'selected' : '' }}>Terhubung (Connected)</option>
                                <option value="expired" {{ old('status', $store->status) == 'expired' ? 'selected' : '' }}>Expired (Membutuhkan Relink)</option>
                                <option value="disconnected" {{ old('status', $store->status) == 'disconnected' ? 'selected' : '' }}>Terputus (Disconnected)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="shipping_handover_method" class="form-label form-label-sm fw-bold text-dark">Metode Penyerahan Paket Kurir (Handover Method) <span class="text-danger">*</span></label>
                        <select name="shipping_handover_method" id="shipping_handover_method" class="form-select form-select-sm" required>
                            <option value="DROP_OFF" {{ old('shipping_handover_method', $store->shipping_handover_method ?? 'DROP_OFF') == 'DROP_OFF' ? 'selected' : '' }}>DROP OFF (Kirim/Antar Paket Sendiri ke Gerai Kurir)</option>
                            <option value="PICK_UP" {{ old('shipping_handover_method', $store->shipping_handover_method) == 'PICK_UP' ? 'selected' : '' }}>PICK UP (Jemput Paket oleh Kurir di Alamat Gudang)</option>
                        </select>
                        <div class="form-text" style="font-size: 0.68rem;">Metode ini digunakan saat melakukan request resi / cetak resi otomatis dari marketplace.</div>
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label form-label-sm fw-bold text-dark">Logo Toko (Opsional)</label>
                        @if($store->logo_url)
                            <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded bg-light">
                                <img src="{{ $store->logo_url }}" alt="Logo" class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                <div class="form-check form-check-sm">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                                    <label class="form-check-label text-danger" style="font-size: 0.75rem;" for="remove_logo">Hapus Logo Saat Ini</label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="logo" id="logo" class="form-control form-control-sm" accept="image/*">
                    </div>

                    <div class="pt-2 border-top text-end">
                        <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan Toko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
