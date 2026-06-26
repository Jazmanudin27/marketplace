<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengaduan Barang Rusak - {{ $tenant->name }}</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }
        .mobile-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffff;
            min-height: 100vh;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .header-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 2.5rem 1.5rem;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
        }
        .form-card {
            margin-top: -1.5rem;
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 2rem 1.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }
        .form-control, .form-select {
            border: 1.5px solid #e2e8f0;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .input-group-text {
            background-color: transparent;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            border-radius: 0.75rem 0 0 0.75rem;
            color: #64748b;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 0.75rem 0.75rem 0;
        }
        .btn-submit {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.9rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        .file-upload-wrapper {
            position: relative;
            width: 100%;
            border: 2px dashed #cbd5e1;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            background-color: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .file-upload-wrapper:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .file-upload-preview {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .preview-img-container {
            position: relative;
            width: 70px;
            height: 70px;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .preview-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>

<div class="mobile-container d-flex flex-column">
    <!-- Header Banner -->
    <div class="header-banner text-center">
        <h4 class="fw-bold mb-1">Pengaduan Barang</h4>
        <p class="mb-0 text-white-50 small">Laporkan barang rusak atau tidak sesuai dengan mudah</p>
        <div class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1.5 mt-2 fw-semibold small">
            <i class="fas fa-store me-1"></i> {{ $tenant->name }}
        </div>
    </div>

    <!-- Form Content -->
    <div class="form-card mx-3 mb-4">
        @if ($errors->any())
            <div class="alert alert-danger rounded-3 mb-3 small" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> Mohon periksa kembali inputan Anda.
            </div>
        @endif

        <form action="{{ route('complaints.mobile.store', $tenant->id) }}" method="POST" enctype="multipart/form-data" id="mobile-complaint-form" class="needs-validation" novalidate>
            @csrf

            <!-- Nama Pengadu -->
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold text-dark small mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nama Anda" required>
                </div>
                @error('name')
                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Nomor HP -->
            <div class="mb-3">
                <label for="phone" class="form-label fw-semibold text-dark small mb-1">Nomor WhatsApp/HP <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="Contoh: 0812XXXXXXXX" required>
                </div>
                @error('phone')
                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Alamat -->
            <div class="mb-3">
                <label for="address" class="form-label fw-semibold text-dark small mb-1">Alamat Pengiriman/Lengkap <span class="text-danger">*</span></label>
                <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat lengkap penerimaan barang" required>{{ old('address') }}</textarea>
                @error('address')
                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Deskripsi Kerusakan -->
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold text-dark small mb-1">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Jelaskan detail produk yang rusak dan jenis kerusakannya..." required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- File Upload -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-dark small mb-1">Foto Bukti Kerusakan (Maksimal 3) <span class="text-secondary">(Opsional)</span></label>
                <div class="file-upload-wrapper">
                    <i class="fas fa-cloud-upload-alt text-primary fs-3 mb-2"></i>
                    <p class="mb-1 fw-medium small text-dark">Ketuk untuk memilih foto</p>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Maks. 3 file, masing-masing maks. 2MB</p>
                    <input type="file" name="photos[]" id="photos-input" class="file-upload-input" multiple accept="image/*">
                </div>
                <div class="file-upload-preview" id="preview-container"></div>
                @error('photos')
                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                @enderror
                @error('photos.*')
                    <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-submit w-100 mt-2">
                <i class="fas fa-paper-plane me-1"></i> Kirim Laporan Pengaduan
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="text-center pb-4 mt-auto text-muted" style="font-size: 0.8rem;">
        Powered by <span class="fw-semibold text-primary">ASPARTECH</span>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const photosInput = document.getElementById('photos-input');
    const previewContainer = document.getElementById('preview-container');
    const form = document.getElementById('mobile-complaint-form');

    photosInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        const files = Array.from(this.files);

        if (files.length > 3) {
            alert('Maksimal foto yang diperbolehkan untuk diunggah adalah 3 foto. Silakan pilih ulang foto Anda.');
            this.value = '';
            return false;
        }

        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'preview-img-container';
                imgContainer.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                previewContainer.appendChild(imgContainer);
            }
            reader.readAsDataURL(file);
        });
    });

    form.addEventListener('submit', function(e) {
        if (photosInput.files.length > 3) {
            alert('Maksimal foto yang diperbolehkan untuk diunggah adalah 3 foto. Silakan pilih ulang foto Anda.');
            photosInput.value = '';
            e.preventDefault();
            return false;
        }
    });
</script>
</body>
</html>
