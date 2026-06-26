<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Pengaduan {{ $tenant->name }}</title>
    <meta name="description" content="Laporkan barang rusak dari {{ $tenant->name }} dengan mudah dan cepat.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --accent: #f59e0b;
            --danger: #ef4444;
            --success: #10b981;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-soft: #f8fafc;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f0f1a;
            min-height: 100vh;
            color: var(--text-dark);
        }

        .mobile-wrap {
            max-width: 430px;
            margin: 0 auto;
            min-height: 100vh;
            background: #ffffff;
            position: relative;
            overflow-x: hidden;
        }

        /* ─── Hero Header ─── */
        .hero {
            background: linear-gradient(145deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
            padding: 3rem 1.5rem 4.5rem;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -30px;
            width: 250px; height: 250px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 50px;
            padding: 0.35rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            margin-bottom: 1rem;
        }
        .hero h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        .hero p {
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .hero-icon-bubble {
            position: absolute;
            top: 1.5rem; right: 1.5rem;
            width: 56px; height: 56px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        /* ─── Form Card ─── */
        .form-sheet {
            background: #fff;
            border-radius: 2rem 2rem 0 0;
            margin-top: -2rem;
            padding: 1.75rem 1.25rem 6rem;
            position: relative;
            z-index: 1;
            box-shadow: 0 -4px 30px rgba(0,0,0,0.06);
        }

        /* ─── Step Indicator ─── */
        .step-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
        }
        .step-line { flex: 1; height: 3px; background: var(--border); border-radius: 99px; }
        .step-line.active { background: var(--primary); }
        .step-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* ─── Section Label ─── */
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--primary);
            background: #ede9fe;
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
            margin-bottom: 1rem;
        }

        /* ─── Input Fields ─── */
        .field-group { margin-bottom: 1.1rem; }
        .field-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.4rem;
        }
        .field-label span.req { color: var(--danger); font-weight: 700; }
        .field-label span.opt {
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--text-muted);
            background: var(--bg-soft);
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
        }

        .input-box {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 2;
        }
        .input-icon.top { top: 1rem; transform: none; }

        .form-control, .form-select {
            border: 1.5px solid var(--border);
            border-radius: 0.875rem;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--text-dark);
            background: var(--bg-soft);
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            width: 100%;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }
        .form-control.is-invalid {
            border-color: var(--danger);
            background: #fef2f2;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
        }
        textarea.form-control { padding-top: 0.85rem; resize: none; }
        .field-err {
            font-size: 0.78rem;
            color: var(--danger);
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ─── Photo Upload Zone ─── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 1.25rem;
            padding: 1.5rem 1rem;
            text-align: center;
            background: var(--bg-soft);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .upload-zone.drag-over {
            border-color: var(--primary);
            background: #ede9fe;
        }
        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 3;
        }
        .upload-icon-wrap {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }
        .upload-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.2rem;
        }
        .upload-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .upload-hint strong { color: var(--primary); }

        /* ─── Photo Preview Grid ─── */
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .preview-item {
            aspect-ratio: 1;
            border-radius: 0.75rem;
            overflow: hidden;
            position: relative;
            border: 2px solid var(--border);
            animation: fadeInScale 0.3s ease;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 5;
            backdrop-filter: blur(4px);
        }
        .photo-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--success);
            background: #d1fae5;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            margin-top: 0.5rem;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        /* ─── Fixed Submit Bar ─── */
        .submit-bar {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 430px;
            padding: 1rem 1.25rem;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(16px);
            border-top: 1px solid var(--border);
            z-index: 100;
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 0.95rem;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            letter-spacing: 0.01em;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(79, 70, 229, 0.45);
        }
        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .btn-submit.loading { opacity: 0.8; pointer-events: none; }

        /* ─── Alert Error ─── */
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.875rem;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
            font-size: 0.83rem;
            color: #dc2626;
        }

        /* ─── Footer ─── */
        .page-footer {
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            padding: 0.5rem 0 1rem;
        }
        .page-footer strong { color: var(--primary); }

        /* ─── Info Cards ─── */
        .info-card {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 1rem;
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
        }
        .info-card .icon { font-size: 1.1rem; color: var(--success); margin-top: 1px; }
        .info-card p { font-size: 0.8rem; color: #15803d; line-height: 1.5; margin: 0; }
    </style>
</head>
<body>

<div class="mobile-wrap">

    {{-- ─── Hero Header ─── --}}
    <div class="hero">
        <div class="hero-icon-bubble">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="hero-badge">
            <i class="fas fa-store"></i>
            {{ $tenant->name }}
        </div>
        <h1>Laporan<br>Barang Rusak</h1>
        <p>Isi formulir berikut dan kami akan segera<br>menindaklanjuti laporan Anda.</p>
    </div>

    {{-- ─── Form Sheet ─── --}}
    <div class="form-sheet">

        {{-- Error Alert --}}
        @if ($errors->any())
        <div class="alert-err">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <strong>Ada yang perlu diperbaiki:</strong><br>
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Info Card --}}
        <div class="info-card">
            <span class="icon"><i class="fas fa-circle-info"></i></span>
            <p>Laporan Anda akan <strong>langsung diterima</strong> dan tim kami akan menghubungi Anda via WhatsApp untuk tindak lanjut.</p>
        </div>

        <form action="{{ route('complaints.mobile.store', $tenant->id) }}" method="POST"
              enctype="multipart/form-data" id="complaint-form" novalidate>
            @csrf

            {{-- ── Bagian Data Diri ── --}}
            <div class="section-tag"><i class="fas fa-user-circle"></i> Data Diri</div>

            {{-- Nama --}}
            <div class="field-group">
                <div class="field-label">
                    Nama Lengkap <span class="req">*</span>
                </div>
                <div class="input-box">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           placeholder="Nama lengkap Anda" required autocomplete="name">
                </div>
                @error('name')
                <div class="field-err"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>
                @enderror
            </div>

            {{-- Nomor HP --}}
            <div class="field-group">
                <div class="field-label">
                    Nomor WhatsApp / HP <span class="req">*</span>
                </div>
                <div class="input-box">
                    <i class="fas fa-mobile-screen input-icon"></i>
                    <input type="tel" name="phone" id="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}"
                           placeholder="Contoh: 08123456789" required autocomplete="tel">
                </div>
                @error('phone')
                <div class="field-err"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>
                @enderror
            </div>

            {{-- Alamat --}}
            <div class="field-group">
                <div class="field-label">
                    Alamat Lengkap <span class="req">*</span>
                </div>
                <div class="input-box">
                    <i class="fas fa-location-dot input-icon top" style="top:0.95rem;"></i>
                    <textarea name="address" id="address" rows="3"
                              class="form-control @error('address') is-invalid @enderror"
                              placeholder="Jalan, kota, dan kode pos..." required
                              autocomplete="street-address">{{ old('address') }}</textarea>
                </div>
                @error('address')
                <div class="field-err"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>
                @enderror
            </div>

            {{-- ── Bagian Laporan ── --}}
            <div class="section-tag mt-2"><i class="fas fa-triangle-exclamation"></i> Detail Kerusakan</div>

            {{-- Deskripsi --}}
            <div class="field-group">
                <div class="field-label">
                    Deskripsi Kerusakan <span class="req">*</span>
                </div>
                <div class="input-box">
                    <i class="fas fa-pen-to-square input-icon top" style="top:0.95rem;"></i>
                    <textarea name="description" id="description" rows="5"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Ceritakan barang apa yang rusak, bagaimana kondisinya, kapan terjadinya..." required>{{ old('description') }}</textarea>
                </div>
                @error('description')
                <div class="field-err"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>
                @enderror
            </div>

            {{-- ── Upload Foto ── --}}
            <div class="field-group">
                <div class="field-label">
                    Foto Bukti Kerusakan <span class="opt">Maks. 3 Foto</span>
                </div>

                <div class="upload-zone" id="upload-zone">
                    <input type="file" name="photos[]" id="photos-input" multiple accept="image/*">
                    <div class="upload-icon-wrap">
                        <i class="fas fa-camera-retro"></i>
                    </div>
                    <div class="upload-title">Tap untuk ambil / pilih foto</div>
                    <div class="upload-hint">JPG, PNG, GIF &bull; maks. <strong>2MB</strong> per foto</div>
                </div>

                <div class="preview-grid" id="preview-grid"></div>
                <div id="photo-count" style="display:none;" class="photo-count-badge">
                    <i class="fas fa-check-circle"></i>
                    <span id="count-text"></span>
                </div>

                @error('photos')
                <div class="field-err"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>
                @enderror
                @error('photos.*')
                <div class="field-err"><i class="fas fa-circle-xmark"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="page-footer">
                Powered by <strong>ASPARTECH</strong> &bull; Sistem ERP Marketplace
            </div>

        </form>
    </div>

</div>

{{-- ─── Fixed Submit Bar ─── --}}
<div class="submit-bar">
    <button type="submit" form="complaint-form" class="btn-submit" id="submit-btn">
        <i class="fas fa-paper-plane"></i>
        Kirim Laporan Sekarang
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const photosInput = document.getElementById('photos-input');
    const previewGrid  = document.getElementById('preview-grid');
    const countBadge   = document.getElementById('photo-count');
    const countText    = document.getElementById('count-text');
    const uploadZone   = document.getElementById('upload-zone');
    const submitBtn    = document.getElementById('submit-btn');
    const form         = document.getElementById('complaint-form');

    let selectedFiles  = [];

    // ── Drag & Drop visual feedback ──
    uploadZone.addEventListener('dragover', e => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
    });

    // ── File change handler ──
    photosInput.addEventListener('change', function () {
        const newFiles = Array.from(this.files);
        const combined = [...selectedFiles, ...newFiles];

        if (combined.length > 3) {
            alert('Maksimal 3 foto yang diperbolehkan. Silakan pilih ulang.');
            this.value = '';
            return;
        }

        selectedFiles = combined;
        renderPreviews();
        this.value = ''; // reset so same file can be re-added after removal
    });

    function renderPreviews() {
        previewGrid.innerHTML = '';

        // rebuild the DataTransfer to keep selectedFiles in sync with input
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        photosInput.files = dt.files;

        if (selectedFiles.length === 0) {
            countBadge.style.display = 'none';
            return;
        }

        countText.textContent = selectedFiles.length + ' foto dipilih';
        countBadge.style.display = 'inline-flex';

        selectedFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = e => {
                const item = document.createElement('div');
                item.className = 'preview-item';
                item.innerHTML = `
                    <img src="${e.target.result}" alt="Foto ${idx+1}">
                    <button class="remove-btn" type="button" data-index="${idx}" aria-label="Hapus foto">
                        <i class="fas fa-xmark"></i>
                    </button>`;
                previewGrid.appendChild(item);

                item.querySelector('.remove-btn').addEventListener('click', function () {
                    const i = parseInt(this.dataset.index);
                    selectedFiles.splice(i, 1);
                    renderPreviews();
                });
            };
            reader.readAsDataURL(file);
        });
    }

    // ── Form submit guard ──
    form.addEventListener('submit', function (e) {
        if (selectedFiles.length > 3) {
            alert('Maksimal 3 foto yang diperbolehkan.');
            e.preventDefault();
            return;
        }
        // Loading state
        submitBtn.classList.add('loading');
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mengirim...';
    });
</script>
</body>
</html>
