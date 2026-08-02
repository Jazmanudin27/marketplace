<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $spk ? 'Status Pesanan ' . ($spk->no_produksi ?: $spk->no_spk) : 'Lacak Pesanan Produksi' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --dark-navy: #0f172a;
            --card-bg: #ffffff;
            --body-bg: #f8fafc;
            --accent-green: #10b981;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            padding-bottom: 120px;
            margin: 0;
        }

        /* ── Top Header Banner ── */
        .hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%);
            color: #ffffff;
            padding: 24px 20px 48px;
            border-bottom-left-radius: 28px;
            border-bottom-right-radius: 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
            position: relative;
        }

        .hero-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .hero-brand-title {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #93c5fd;
        }

        .spk-code-tag {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            color: #ffffff;
        }

        .hero-title {
            font-size: 26px;
            font-weight: 900;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 13px;
            color: #cbd5e1;
            margin: 0;
        }

        /* ── Floating Main Card ── */
        .main-floating-card {
            margin-top: -30px;
            background: #ffffff;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        /* ── Custom Progress Bar ── */
        .progress-container {
            background: #f1f5f9;
            border-radius: 12px;
            height: 12px;
            overflow: hidden;
            margin: 14px 0 8px;
            position: relative;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #10b981 100%);
            height: 100%;
            border-radius: 12px;
            transition: width 0.8s ease-in-out;
        }

        /* ── Stepper Timeline ── */
        .timeline-box {
            position: relative;
            padding-left: 28px;
        }

        .timeline-box::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 10px;
            bottom: 10px;
            width: 3px;
            background: #e2e8f0;
            border-radius: 3px;
        }

        .timeline-step {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-step:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -28px;
            top: 2px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            z-index: 2;
            transition: all 0.3s;
        }

        .timeline-step.completed .timeline-dot {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 0 0 4px #d1fae5;
        }

        .timeline-step.active .timeline-dot {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 0 0 5px #dbeafe;
            animation: pulse-ring 1.8s infinite;
        }

        .timeline-step.pending .timeline-dot {
            background: #f1f5f9;
            color: #94a3b8;
            border: 2px solid #cbd5e1;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70% { box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        .timeline-content {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 14px;
            padding: 12px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .timeline-step.active .timeline-content {
            border: 2px solid #93c5fd;
            background: #eff6ff;
        }

        .timeline-title {
            font-size: 14px;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-desc {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Cards UI ── */
        .app-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .card-header-clean {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body-clean {
            padding: 16px 18px;
        }

        /* ── Size Grid ── */
        .size-chip-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
            min-width: 60px;
        }

        .size-chip-label {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
        }

        .size-chip-val {
            font-size: 15px;
            font-weight: 900;
            color: #2563eb;
        }

        /* ── Image Gallery Box ── */
        .gallery-thumb {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .gallery-thumb:hover {
            transform: scale(1.02);
        }

        /* ── Sticky Bottom Floating Bar ── */
        .sticky-customer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            padding: 12px 16px;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1040;
            display: flex;
            gap: 10px;
        }

        .btn-wa-cs {
            background: #25d366;
            color: #ffffff;
            font-weight: 800;
            border-radius: 14px;
            padding: 12px;
            flex: 1;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25);
        }

        .btn-wa-cs:hover {
            background: #1eb956;
            color: #ffffff;
        }

        .btn-copy-link {
            background: #eff6ff;
            color: #2563eb;
            border: 1.5px solid #bfdbfe;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 800;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- ── SEARCH BAR IF NO SPK FOUND OR DIRECT SEARCH ── -->
    @if(!$spk)
        <div class="container px-3 pt-5 text-center">
            <div class="app-card p-4 my-5 mx-auto" style="max-width: 480px;">
                <div class="fs-1 mb-3">🔍</div>
                <h4 class="fw-extrabold text-dark mb-2">Lacak Status Pesanan Produksi</h4>
                <p class="text-muted small mb-4">Masukkan Nomor Pesanan, SPK, atau Nomor Produksi Anda untuk melihat progress pengerjaan.</p>

                <form action="{{ route('spks.customer_track', 'search') }}" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" name="search" class="form-control form-control-lg text-uppercase fw-bold" placeholder="Contoh: JN2608001 / SPK..." value="{{ $search ?? '' }}" required>
                        <button class="btn btn-primary fw-bold px-4" type="submit">Cari</button>
                    </div>
                </form>

                @if(!empty($search))
                    <div class="alert alert-warning small fw-bold mt-3 mb-0">
                        ⚠️ Pesanan dengan kata kunci "<strong>{{ $search }}</strong>" tidak ditemukan. Mohon periksa kembali nomor pesanan Anda.
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- ── HERO BANNER HEADER ── -->
        <div class="hero-banner">
            <div class="container-fluid px-2">
                <div class="hero-brand">
                    <div class="hero-brand-title">STATUS PRODUKSI PESANAN</div>
                    <div class="spk-code-tag">
                        <i class="fas fa-barcode me-1"></i> {{ $spk->no_produksi ?: $spk->no_spk }}
                    </div>
                </div>
                <h1 class="hero-title">{{ strtoupper($spk->pemesan ?: 'PELANGGAN') }}</h1>
                <p class="hero-subtitle">
                    @if($spk->instansi)
                        <i class="fas fa-building me-1 opacity-75"></i> {{ $spk->instansi }} &bull;
                    @endif
                    Total {{ number_format($totalPcs) }} Pcs Produk
                </p>
            </div>
        </div>

        <div class="container-fluid px-3">
            <!-- ── FLOATING MAIN CARD: PROGRESS & STATUS ── -->
            <div class="main-floating-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <span class="text-muted small fw-bold d-block style="font-size: 11px;">TAHAPAN SAAT INI:</span>
                        <h5 class="fw-extrabold text-primary mb-0" style="font-size: 17px;">
                            {{ $stagesList[$currentStageKey]['label'] ?? $currentStageKey }}
                        </h5>
                    </div>
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-extrabold fs-6">
                        {{ $progressPct }}%
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="progress-container">
                    <div class="progress-bar-fill" style="width: {{ $progressPct }}%;"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center small text-muted mt-2" style="font-size: 11.5px;">
                    <span>
                        <i class="far fa-calendar-alt me-1 text-primary"></i>Masuk: {{ $spk->tanggal ? $spk->tanggal->format('d M Y') : '-' }}
                    </span>
                    @if($spk->deadline)
                        <span class="fw-bold text-dark">
                            <i class="far fa-clock me-1 text-danger"></i>Target: {{ $spk->deadline->format('d M Y') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- ── CARD 1: VISUAL TIMELINE STEPPER (10 STAGES) ── -->
            <div class="app-card">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <i class="fas fa-route text-primary"></i> Timeline Pengerjaan
                    </h3>
                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">
                        Tahap {{ $currentStageIdx }} dari 10
                    </span>
                </div>
                <div class="card-body-clean">
                    <div class="timeline-box">
                        @php $stepNo = 1; @endphp
                        @foreach($stagesList as $stageKey => $stageMeta)
                            @php
                                $statusClass = 'pending';
                                if ($stepNo < $currentStageIdx) {
                                    $statusClass = 'completed';
                                } elseif ($stepNo === $currentStageIdx) {
                                    $statusClass = 'active';
                                }
                                $stepNo++;
                            @endphp
                            <div class="timeline-step {{ $statusClass }}">
                                <div class="timeline-dot">
                                    @if($statusClass === 'completed')
                                        <i class="fas fa-check"></i>
                                    @elseif($statusClass === 'active')
                                        <i class="fas fa-spinner fa-spin"></i>
                                    @else
                                        <span>{{ $loop->iteration }}</span>
                                    @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <i class="{{ $stageMeta['icon'] }} me-1 opacity-75"></i>
                                        <span>{{ $stageMeta['label'] }}</span>
                                        @if($statusClass === 'active')
                                            <span class="badge bg-primary ms-auto" style="font-size: 9px; padding: 3px 8px;">SEDANG DIPROSES</span>
                                        @elseif($statusClass === 'completed')
                                            <span class="badge bg-success bg-opacity-10 text-success ms-auto" style="font-size: 9px; padding: 3px 8px;">SELESAI</span>
                                        @endif
                                    </div>
                                    <div class="timeline-desc">
                                        @if($statusClass === 'completed')
                                            Tahap pengerjaan ini telah selesai dilaksanakan.
                                        @elseif($statusClass === 'active')
                                            Tim produksi kami sedang mengerjakan tahap ini.
                                        @else
                                            Tahapan selanjutnya setelah tahapan aktif selesai.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ── CARD 2: DETAIL RINCIAN PRODUK & VARIAN ── -->
            <div class="app-card">
                <div class="card-header-clean">
                    <h3 class="card-header-title">
                        <i class="fas fa-tshirt text-warning"></i> Rincian Pesanan Produk
                    </h3>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle fw-bold" style="font-size: 10px;">
                        {{ strtoupper($fabricName) }}
                    </span>
                </div>
                <div class="card-body-clean">
                    @foreach($variantRows as $modelName => $row)
                        <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-extrabold text-dark" style="font-size: 15px;">
                                    {{ $modelName }}
                                </div>
                                <span class="badge bg-primary text-white fw-bold">
                                    {{ number_format($row['subtotal']) }} Pcs
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                @foreach($row['sizes'] as $sz)
                                    <div class="size-chip-item">
                                        <div class="size-chip-label">Size {{ $sz['size'] }}</div>
                                        <div class="size-chip-val">{{ number_format($sz['quantity']) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- ── CARD 3: GALERI FOTO MOCKUP & PROGRES PRODUKSI ── -->
            @if(!empty($photos))
                <div class="app-card">
                    <div class="card-header-clean">
                        <h3 class="card-header-title">
                            <i class="fas fa-images text-purple"></i> Foto Desain & Bukti Produksi
                        </h3>
                    </div>
                    <div class="card-body-clean">
                        <div class="row g-2">
                            @foreach($photos as $img)
                                <div class="col-6 col-sm-4">
                                    <div class="position-relative">
                                        <img src="{{ $img['url'] }}" class="gallery-thumb" alt="{{ $img['title'] }}" onclick="openImageModal('{{ $img['url'] }}', '{{ $img['title'] }}')">
                                        <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-60 text-white p-1 text-center small text-truncate rounded-bottom-3" style="font-size: 10px;">
                                            {{ $img['title'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- ── STICKY FOOTER ACTION BAR ── -->
        <div class="sticky-customer-bar">
            <button type="button" class="btn-copy-link" onclick="copyTrackingLink()">
                <i class="fas fa-link"></i>
                <span>Salin Link</span>
            </button>
            @php
                $waText = rawurlencode("Halo Admin, saya mau menanyakan update pesanan " . ($spk->no_produksi ?: $spk->no_spk) . " (" . ($spk->pemesan ?: 'Customer') . ")");
            @endphp
            <a href="https://wa.me/?text={{ $waText }}" target="_blank" class="btn-wa-cs">
                <i class="fab fa-whatsapp fs-5"></i>
                <span>Tanya CS via WhatsApp</span>
            </a>
        </div>

        <!-- ── IMAGE MODAL PREVIEW ── -->
        <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 bg-dark text-white">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="modalImgTitle">Detail Foto</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-3">
                        <img id="modalImgSrc" src="" class="img-fluid rounded-3" style="max-height: 80vh;" alt="Preview">
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openImageModal(url, title) {
            document.getElementById('modalImgSrc').src = url;
            document.getElementById('modalImgTitle').innerText = title;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        function copyTrackingLink() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                alert('✅ Link tracking pesanan berhasil disalin ke clipboard!');
            }).catch(function() {
                const dummy = document.createElement('input');
                document.body.appendChild(dummy);
                dummy.value = window.location.href;
                dummy.select();
                document.execCommand('copy');
                document.body.removeChild(dummy);
                alert('✅ Link tracking pesanan berhasil disalin ke clipboard!');
            });
        }
    </script>
</body>
</html>
