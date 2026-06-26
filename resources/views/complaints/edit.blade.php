@extends('layouts.app')
@section('title', 'Ubah Pengaduan')
@section('page-title', 'Ubah Pengaduan Barang Rusak')

@section('content')
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-lg-8 col-md-10">
            <div class="card border shadow-sm">
                <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-edit me-2 text-primary"></i>Ubah Pengaduan & Status #{{ $complaint->id }}</h6>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('complaints.update', $complaint->id) }}" method="POST" enctype="multipart/form-data" id="complaint-form" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-semibold text-dark small">Nama Pengadu <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control rounded-3 @error('name') is-invalid @enderror" value="{{ old('name', $complaint->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold text-dark small">Status Pengaduan <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select rounded-3 @error('status') is-invalid @enderror" required>
                                    <option value="Pending" {{ old('status', $complaint->status) === 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Diproses" {{ old('status', $complaint->status) === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                                    <option value="Selesai" {{ old('status', $complaint->status) === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                    <option value="Dibatalkan" {{ old('status', $complaint->status) === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold text-dark small">Nomor HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-control rounded-3 @error('phone') is-invalid @enderror" value="{{ old('phone', $complaint->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label fw-semibold text-dark small">Alamat Pelanggan <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" rows="3" class="form-control rounded-3 @error('address') is-invalid @enderror" required>{{ old('address', $complaint->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold text-dark small">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" rows="4" class="form-control rounded-3 @error('description') is-invalid @enderror" required>{{ old('description', $complaint->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Existing Photos -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark small d-block">Foto Bukti Saat Ini</label>
                            <div class="row g-2">
                                @php $hasPhotos = false; @endphp
                                @for ($i = 1; $i <= 3; $i++)
                                    @php $field = 'photo_' . $i; @endphp
                                    @if ($complaint->$field)
                                        @php $hasPhotos = true; @endphp
                                        <div class="col-md-3 col-4">
                                            <div class="border rounded-3 overflow-hidden shadow-sm">
                                                <img src="{{ asset('storage/' . $complaint->$field) }}" alt="Foto {{ $i }}" class="img-fluid w-100" style="height: 100px; object-fit: cover;">
                                            </div>
                                        </div>
                                    @endif
                                @endfor

                                @if (!$hasPhotos)
                                    <div class="col-12">
                                        <div class="p-3 text-center border rounded-3 bg-light text-secondary small">
                                            Tidak ada foto bukti kerusakan saat ini.
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="photos" class="form-label fw-semibold text-dark small">Ganti Foto Bukti (Maksimal 3 Foto) <span class="text-secondary">(Opsional)</span></label>
                            <input type="file" name="photos[]" id="photos" class="form-control rounded-3 @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror" multiple accept="image/*">
                            <div class="form-text small text-muted">Aksi ini akan mengganti semua foto yang ada dengan foto baru yang Anda pilih. Format: JPG, JPEG, PNG, GIF. Maksimal: 2 MB per foto.</div>
                            
                            @error('photos')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('photos.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">
                                <i class="fas fa-save me-1"></i> Perbarui Pengaduan
                            </button>
                            <a href="{{ route('complaints.show', $complaint->id) }}" class="btn btn-outline-secondary rounded-3 px-4 fw-semibold">
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
            alert('Maksimal foto yang diperbolehkan untuk diunggah adalah 3 foto. Silakan pilih ulang foto Anda.');
            fileInput.value = ''; // Reset file input
            e.preventDefault();
            return false;
        }
    });
</script>
@endsection
