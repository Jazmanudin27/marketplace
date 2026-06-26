@extends('layouts.app')
@section('title', 'Tambah Pengaduan')
@section('page-title', 'Tambah Pengaduan Barang Rusak')

@section('content')
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-lg-8 col-md-10">
                <div class="card border shadow-sm">
                    <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-plus me-2 text-primary"></i>Buat Pengaduan Baru
                        </h6>
                    </div>

                    <div class="card-body p-4">
                        <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data"
                            id="complaint-form" class="needs-validation" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold text-dark small">Nama Pengadu <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control rounded-3 @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="Masukkan nama" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold text-dark small">Nomor HP <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone"
                                    class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                    value="{{ old('phone') }}" placeholder="Contoh: 08123456789" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold text-dark small">Alamat <span
                                        class="text-danger">*</span></label>
                                <textarea name="address" id="address" rows="3"
                                    class="form-control rounded-3 @error('address') is-invalid @enderror" placeholder="Masukkan alamat" required>{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold text-dark small">Deskripsi Kerusakan
                                    <span class="text-danger">*</span></label>
                                <textarea name="description" id="description" rows="4"
                                    class="form-control rounded-3 @error('description') is-invalid @enderror"
                                    placeholder="Jelaskan secara detail barang apa yang rusak dan kondisi kerusakannya..." required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="photos" class="form-label fw-semibold text-dark small">Foto Barang Rusak
                                    (Maksimal 3 Foto) <span class="text-secondary">(Opsional)</span></label>
                                <input type="file" name="photos[]" id="photos"
                                    class="form-control rounded-3 @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror"
                                    multiple accept="image/*">
                                <div class="form-text small text-muted">Format yang didukung: JPG, JPEG, PNG, GIF. Maksimal
                                    ukuran per foto: 2 MB. Pilih maksimal 3 foto.</div>

                                @error('photos')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('photos.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">
                                    <i class="fas fa-save me-1"></i> Simpan Pengaduan
                                </button>
                                <a href="{{ route('complaints.index') }}"
                                    class="btn btn-outline-secondary rounded-3 px-4 fw-semibold">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('complaint-form').addEventListener('submit', function(e) {
            var fileInput = document.getElementById('photos');
            var files = fileInput.files;
            if (files.length > 3) {
                alert(
                    'Maksimal foto yang diperbolehkan untuk diunggah adalah 3 foto. Silakan pilih ulang foto Anda.'
                );
                fileInput.value = ''; // Reset file input
                e.preventDefault();
                return false;
            }
        });
    </script>
@endsection
