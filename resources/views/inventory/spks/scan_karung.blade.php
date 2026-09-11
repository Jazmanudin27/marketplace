@extends('layouts.app')
@section('title', 'Scan Karung Penerimaan Hasil Produksi')
@section('page-title', 'Penerimaan Hasil Produksi')

@push('styles')
<style>
    .row-scan-pulse {
        animation: rowPulseGreen 0.8s ease;
    }
    @keyframes rowPulseGreen {
        0% { background-color: #d1e7dd !important; }
        100% { background-color: inherit; }
    }
    .font-monospace-code {
        font-family: var(--bs-font-monospace);
        letter-spacing: 0.5px;
    }
    #barcode-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- HEADER UTAMA (CLEAN BOOTSTRAP 5) --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-qr-code-scan text-primary"></i>
                <span>Scan Karung Penerimaan Hasil Produksi</span>
            </h4>
            <p class="text-muted small mb-0">
                Mode Fast Scan Karung Multi-SPK — Tembak label stiker produk secara acak dari karung, sistem otomatis menyimpan ke SPK &amp; SKU terkait.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btn-toggle-sound" class="btn btn-outline-secondary btn-sm rounded-2 px-3 fw-semibold">
                <i class="bi bi-volume-up me-1 text-primary"></i><span id="sound-status-label">Suara: ON</span>
            </button>
            <a href="{{ route('spks.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    {{-- RINGKASAN METRIK SESI & FORM SCANNER --}}
    <div class="row g-3 mb-4">
        
        {{-- CARD FORM SCANNER UTAMA --}}
        <div class="col-12 col-lg-8">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 p-md-4">
                    <form id="scan-form" onsubmit="return false;">
                        <div class="row g-3 align-items-end">

                            {{-- Input Barcode / SKU / QR --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="bi bi-barcode text-primary me-1"></i>Scan / Ketik Barcode / QR Label
                                </label>
                                <div class="input-group">
                                    <input type="text" id="barcode-input" class="form-control form-control-lg font-monospace-code fw-bold fs-6"
                                        placeholder="Tembak barcode kemasan dari karung..." autofocus autocomplete="off">
                                    <button type="button" id="btn-submit-scan" class="btn btn-primary px-4 fw-bold">
                                        <i class="bi bi-arrow-right me-1"></i>Proses
                                    </button>
                                </div>
                            </div>

                            {{-- Qty / Scan --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-semibold text-dark small mb-1">
                                    <i class="bi bi-calculator me-1"></i>Qty / Scan
                                </label>
                                <input type="number" id="qty-scan-input" class="form-control form-control-lg text-center fw-bold fs-6"
                                    value="1" min="1" max="500">
                            </div>

                            {{-- Petugas Penerima --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-semibold text-dark small mb-1">
                                    <i class="bi bi-person-check me-1"></i>Petugas
                                </label>
                                <input type="text" id="nama-pengambil-input" class="form-control form-control-lg fs-6"
                                    value="{{ Auth::user()->name ?? 'Petugas Gudang' }}" placeholder="Nama Penerima">
                            </div>

                            {{-- Kamera Toggle --}}
                            <div class="col-12 col-md-2">
                                <button type="button" id="btn-toggle-camera" class="btn btn-outline-secondary btn-lg w-100 fs-6 fw-semibold">
                                    <i class="bi bi-camera me-1"></i><span id="camera-btn-text">Kamera</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Container Kamera QR Reader --}}
                    <div id="camera-scanner-container" class="mt-3 d-none text-center bg-light p-3 rounded-3 border">
                        <div id="reader" style="max-width: 320px; margin: 0 auto;"></div>
                        <small class="text-muted mt-2 d-block">Arahkan kamera ke QR Code label stiker pakaian</small>
                    </div>

                    {{-- BANNER FEEDBACK HASIL SCAN (LIVE ALERT) --}}
                    <div id="live-alert-banner" class="mt-3 d-none">
                        <div id="live-alert-box" class="alert alert-success border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-3">
                            <div id="live-alert-icon" class="fs-3"></div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span id="badge-spk-num" class="badge bg-dark px-2 py-1"></span>
                                    <span id="badge-prod-num" class="badge bg-secondary"></span>
                                    <span id="badge-size-num" class="badge bg-primary"></span>
                                </div>
                                <h6 id="live-alert-title" class="fw-bold mb-1"></h6>
                                <p id="live-alert-msg" class="mb-0 small"></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- METRIK STATISTIK SESI SCAN --}}
        <div class="col-12 col-lg-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-bar-chart-line text-primary me-1"></i>Statistik Sesi Scan Ini
                    </h6>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-center gap-3">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Total Scan Berhasil</span>
                            <h3 class="fw-bold text-dark mb-0" id="stat-session-count">0</h3>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                            <i class="bi bi-qr-code fs-4"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Total Volume Pcs</span>
                            <h3 class="fw-bold text-success mb-0" id="stat-session-pcs">0 Pcs</h3>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-2 bg-info bg-opacity-10 border border-info border-opacity-25 text-info">
                        <span class="small fw-semibold"><i class="bi bi-info-circle me-1"></i>Status SPK Aktif:</span>
                        <span class="fw-bold small">{{ $activeSpksCount }} SPK Berjalan</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TABEL RIWAYAT SCAN SESI INI --}}
    <div class="card border shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i>
                <span>Riwayat Scan Penerimaan Karung Sesi Ini</span>
            </h6>
            <span class="badge bg-light text-dark border small fw-normal">Terbaru 50 Transaksi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small text-muted">
                            <th class="ps-3" style="width: 140px;">Waktu</th>
                            <th>No SPK</th>
                            <th>Kode Produksi</th>
                            <th>Produk &amp; SKU</th>
                            <th class="text-center">Size</th>
                            <th class="text-center">Qty Diterima</th>
                            <th>Petugas</th>
                            <th class="text-center pe-3" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-history">
                        @forelse($recentPickups as $p)
                            @php
                                $spk = $p->item->spk ?? null;
                            @endphp
                            <tr id="pickup-row-{{ $p->id }}">
                                <td class="ps-3 text-muted small font-monospace-code">
                                    {{ $p->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @if($spk)
                                        <a href="{{ route('spks.show', $spk->id) }}" target="_blank" class="fw-bold text-decoration-none text-primary font-monospace-code">
                                            #{{ $spk->no_spk }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace-code">
                                        {{ $spk->no_produksi ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $p->item->nama_produk ?? 'Produk' }}</div>
                                    <span class="text-muted small font-monospace-code">{{ $p->item->sku ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ $p->item->ukuran ?: 'All' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold fs-6">
                                        +{{ $p->qty_diambil }} Pcs
                                    </span>
                                </td>
                                <td class="small text-secondary">
                                    <i class="bi bi-person me-1"></i>{{ $p->nama_pengambil ?: ($p->pemberi->name ?? 'Gudang') }}
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 border-0"
                                        title="Batalkan Catatan Penerimaan Ini" onclick="cancelPickup({{ $p->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-qr-code fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    Belum ada data scan penerimaan pada sesi ini. Tembak barcode untuk mulai!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const barcodeInput = document.getElementById('barcode-input');
        const qtyScanInput = document.getElementById('qty-scan-input');
        const namaPengambilInput = document.getElementById('nama-pengambil-input');
        const btnSubmitScan = document.getElementById('btn-submit-scan');
        const btnToggleSound = document.getElementById('btn-toggle-sound');
        const soundStatusLabel = document.getElementById('sound-status-label');
        const btnToggleCamera = document.getElementById('btn-toggle-camera');
        const cameraBtnText = document.getElementById('camera-btn-text');
        const cameraContainer = document.getElementById('camera-scanner-container');
        const liveAlertBanner = document.getElementById('live-alert-banner');
        const liveAlertBox = document.getElementById('live-alert-box');
        const liveAlertIcon = document.getElementById('live-alert-icon');
        const liveAlertTitle = document.getElementById('live-alert-title');
        const liveAlertMsg = document.getElementById('live-alert-msg');
        const badgeSpkNum = document.getElementById('badge-spk-num');
        const badgeProdNum = document.getElementById('badge-prod-num');
        const badgeSizeNum = document.getElementById('badge-size-num');

        const statSessionCount = document.getElementById('stat-session-count');
        const statSessionPcs = document.getElementById('stat-session-pcs');
        const tbodyHistory = document.getElementById('tbody-history');
        const emptyRow = document.getElementById('empty-row');

        let isSoundEnabled = true;
        let isProcessing = false;
        let html5QrCode = null;
        let isCameraActive = false;
        let sessionCount = 0;
        let sessionPcs = 0;

        // Auto Focus pada Barcode Input
        barcodeInput.focus();
        document.addEventListener('click', function(e) {
            if (!e.target.closest('input, button, a, select, textarea, table')) {
                barcodeInput.focus();
            }
        });

        // Toggle Sound
        btnToggleSound.addEventListener('click', function() {
            isSoundEnabled = !isSoundEnabled;
            soundStatusLabel.innerText = isSoundEnabled ? "Suara: ON" : "Suara: OFF";
            btnToggleSound.classList.toggle('btn-outline-secondary', !isSoundEnabled);
            btnToggleSound.classList.toggle('btn-outline-primary', isSoundEnabled);
        });

        // Web Audio Synthesizer (Zero External Dependencies)
        function playBeep(freq = 880, type = 'sine', duration = 0.1, vol = 0.2) {
            if (!isSoundEnabled) return;
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(vol, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {
                console.error("Audio synth error:", e);
            }
        }

        function playSuccessChime() {
            playBeep(880, 'sine', 0.08, 0.2);
            setTimeout(() => playBeep(1318.5, 'sine', 0.12, 0.2), 90);
        }

        function playErrorBuzzer() {
            playBeep(160, 'sawtooth', 0.35, 0.3);
            setTimeout(() => playBeep(140, 'sawtooth', 0.35, 0.3), 100);
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
            }
        }

        // Keydown Enter Barcode Input
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitScan();
            }
        });

        btnSubmitScan.addEventListener('click', function() {
            submitScan();
        });

        function submitScan(scannedVal = null) {
            if (isProcessing) return;

            const code = (scannedVal !== null ? scannedVal : barcodeInput.value).trim();
            if (!code) {
                barcodeInput.focus();
                return;
            }

            const qty = parseInt(qtyScanInput.value) || 1;
            const namaPengambil = namaPengambilInput.value.trim();

            isProcessing = true;
            btnSubmitScan.disabled = true;
            btnSubmitScan.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Proses...`;

            fetch(`{{ route('spks.process_scan_karung') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    code: code,
                    qty: qty,
                    nama_pengambil: namaPengambil,
                    catatan: 'Fast Scan Multi-SPK Karung'
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.success) {
                    handleScanSuccess(body);
                } else {
                    handleScanError(body);
                }
            })
            .catch(err => {
                handleScanError({
                    title: 'TERJADI KESALAHAN!',
                    message: err.message || 'Gagal menghubungkan ke server.'
                });
            })
            .finally(() => {
                isProcessing = false;
                btnSubmitScan.disabled = false;
                btnSubmitScan.innerHTML = `<i class="bi bi-arrow-right me-1"></i>Proses`;
                barcodeInput.value = '';
                barcodeInput.focus();
            });
        }

        function handleScanSuccess(res) {
            playSuccessChime();

            // Update stats
            sessionCount++;
            sessionPcs += res.pickup.qty;
            statSessionCount.innerText = sessionCount;
            statSessionPcs.innerText = sessionPcs + " Pcs";

            // Live Alert Banner
            liveAlertBanner.classList.remove('d-none');
            liveAlertBox.className = 'alert alert-success border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-3';
            liveAlertIcon.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i>`;
            badgeSpkNum.innerText = `SPK #${res.spk.no_spk}`;
            badgeProdNum.innerText = `PROD #${res.spk.no_produksi}`;
            badgeSizeNum.innerText = `SIZE: ${res.item.ukuran}`;
            liveAlertTitle.innerText = res.message;
            liveAlertMsg.innerText = `Terima: ${res.item.nama_produk} (SKU: ${res.item.sku}) | Total Diterima: ${res.item.qty_diambil} / Target: ${res.item.quantity} pcs (${res.item.sisa_qty} sisa)`;

            // Prepend Row
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `pickup-row-${res.pickup.id}`;
            tr.className = 'row-scan-pulse';
            tr.innerHTML = `
                <td class="ps-3 text-muted small font-monospace-code">${res.pickup.tanggal}</td>
                <td>
                    <a href="/spks/${res.spk.id}" target="_blank" class="fw-bold text-decoration-none text-primary font-monospace-code">
                        #${res.spk.no_spk}
                    </a>
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace-code">
                        ${res.spk.no_produksi}
                    </span>
                </td>
                <td>
                    <div class="fw-bold text-dark">${res.item.nama_produk}</div>
                    <span class="text-muted small font-monospace-code">${res.item.sku}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary">${res.item.ukuran}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold fs-6">
                        +${res.pickup.qty} Pcs
                    </span>
                </td>
                <td class="small text-secondary">
                    <i class="bi bi-person me-1"></i>${res.pickup.nama_pengambil}
                </td>
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 border-0"
                        title="Batalkan Catatan Penerimaan Ini" onclick="cancelPickup(${res.pickup.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbodyHistory.insertBefore(tr, tbodyHistory.firstChild);
        }

        function handleScanError(res) {
            playErrorBuzzer();

            liveAlertBanner.classList.remove('d-none');
            liveAlertBox.className = 'alert alert-danger border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-3';
            liveAlertIcon.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger"></i>`;
            badgeSpkNum.innerText = `GAGAL`;
            badgeProdNum.innerText = `ERR`;
            badgeSizeNum.innerText = `!`;
            liveAlertTitle.innerText = res.title || 'PENERIMAAN GAGAL!';
            liveAlertMsg.innerText = (res.message || 'Kode tidak dapat diproses.') + (res.detail ? ' ' + res.detail : '');
        }

        // Batalkan Pickup
        window.cancelPickup = function(pickupId) {
            if (!confirm("Apakah Anda yakin ingin membatalkan & menghapus catatan penerimaan ini?")) return;

            fetch(`/spks/pickups/${pickupId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`pickup-row-${pickupId}`);
                    if (row) row.remove();
                } else {
                    alert(data.message || "Gagal menghapus.");
                }
            })
            .catch(err => alert("Terjadi kesalahan: " + err.message));
        };

        // Camera Scanner via HTML5-QRCode
        btnToggleCamera.addEventListener('click', function() {
            if (!isCameraActive) {
                startCameraScanner();
            } else {
                stopCameraScanner();
            }
        });

        function startCameraScanner() {
            cameraContainer.classList.remove('d-none');
            cameraBtnText.innerText = "Tutup Kamera";
            btnToggleCamera.classList.replace('btn-outline-secondary', 'btn-outline-danger');

            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 240, height: 240 } };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    if (!isProcessing) {
                        barcodeInput.value = decodedText;
                        submitScan(decodedText);
                    }
                },
                (errorMessage) => {}
            ).then(() => {
                isCameraActive = true;
            }).catch(err => {
                console.error("Camera error:", err);
                alert("Gagal membuka kamera. Pastikan izin kamera telah diberikan.");
                stopCameraScanner();
            });
        }

        function stopCameraScanner() {
            if (html5QrCode && isCameraActive) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    cameraContainer.classList.add('d-none');
                    cameraBtnText.innerText = "Kamera";
                    btnToggleCamera.classList.replace('btn-outline-danger', 'btn-outline-secondary');
                    isCameraActive = false;
                }).catch(err => console.error("Stop camera error:", err));
            } else {
                cameraContainer.classList.add('d-none');
                cameraBtnText.innerText = "Kamera";
                btnToggleCamera.classList.replace('btn-outline-danger', 'btn-outline-secondary');
                isCameraActive = false;
            }
        }
    });
</script>
@endpush
@endsection
