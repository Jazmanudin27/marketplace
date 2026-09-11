@extends('layouts.app')
@section('title', 'Penerimaan Hasil Produksi — Scan Karung Multi-SPK')
@section('page-title', 'Penerimaan Hasil Produksi (Scan Karung Multi-SPK)')

@push('styles')
<style>
    .row-scan-pulse {
        animation: rowPulseGreen 0.8s ease;
    }
    @keyframes rowPulseGreen {
        0% { background-color: rgba(16, 185, 129, 0.35) !important; }
        100% { background-color: inherit; }
    }
    .badge-spk {
        font-family: monospace;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-2 px-md-3 py-2">

    {{-- HEADER CARD: FAST SCAN KARUNG MULTI-SPK --}}
    <div class="card border-0 shadow-sm rounded-3 bg-gradient mb-3 overflow-hidden" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #ffffff;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill small fw-bold">
                            <i class="fas fa-boxes-packing me-1"></i>MODE MULTI-SPK FAST SCAN
                        </span>
                        <span class="badge bg-info bg-opacity-20 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill small">
                            <i class="fas fa-industry me-1"></i>{{ $activeSpksCount }} SPK Aktif
                        </span>
                    </div>
                    <h4 class="fw-extrabold text-white mb-1 d-flex align-items-center gap-2">
                        <i class="fas fa-barcode text-warning"></i>
                        <span>Scan Karung Penerimaan Hasil Produksi</span>
                    </h4>
                    <p class="text-slate-300 small mb-0 opacity-75">
                        Tembak stiker barcode/QR produk langsung dari karung. Sistem otomatis mencocokkan & menyimpan penerimaan ke SPK & SKU yang sesuai.
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" id="btn-toggle-sound" class="btn btn-outline-light btn-sm rounded-2 px-3 fw-semibold">
                        <i class="fas fa-volume-up me-1 text-warning"></i><span id="sound-status-label">Suara: ON</span>
                    </button>
                    <a href="{{ route('spks.index') }}" class="btn btn-secondary btn-sm rounded-2 px-3 fw-semibold text-white">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke SPK
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- KPI Summary Sesi Scan --}}
        <div class="col-12 col-md-4 col-xl-3">
            <div class="card border shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase tracking-wider">Total Transaksi Sesi Ini</span>
                        <h2 class="fw-bold text-dark mb-0 mt-1" id="stat-session-count">0</h2>
                        <span class="text-secondary small">Kali Scan Berhasil</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total Volume Pcs:</span>
                        <span class="fw-bold text-success fs-5" id="stat-session-pcs">0 Pcs</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Scanner Utama --}}
        <div class="col-12 col-md-8 col-xl-9">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3">
                    <form id="scan-form" onsubmit="return false;">
                        <div class="row g-2 align-items-end">

                            {{-- Input Barcode / SKU / QR --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm fw-bold mb-1 text-dark">
                                    <i class="fas fa-qrcode text-primary me-1"></i>Scan / Ketik Barcode / QR Label
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="barcode-input" class="form-control form-control-sm font-monospace fw-bold fs-6 py-2"
                                        placeholder="Tembak barcode kemasan dari karung..." autofocus autocomplete="off">
                                    <button type="button" id="btn-submit-scan" class="btn btn-primary btn-sm px-3 fw-bold">
                                        <i class="fas fa-arrow-right me-1"></i>Proses
                                    </button>
                                </div>
                            </div>

                            {{-- Qty Scan --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-calculator text-muted me-1"></i>Qty / Scan
                                </label>
                                <input type="number" id="qty-scan-input" class="form-control form-control-sm text-center fw-bold py-2"
                                    value="1" min="1" max="500">
                            </div>

                            {{-- Petugas Penerima --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-user-check text-muted me-1"></i>Petugas
                                </label>
                                <input type="text" id="nama-pengambil-input" class="form-control form-control-sm py-2"
                                    value="{{ Auth::user()->name ?? 'Petugas Gudang' }}" placeholder="Nama Penerima">
                            </div>

                            {{-- Toggle Kamera --}}
                            <div class="col-12 col-md-2">
                                <button type="button" id="btn-toggle-camera" class="btn btn-outline-secondary btn-sm w-100 py-2 fw-semibold">
                                    <i class="fas fa-camera me-1"></i><span id="camera-btn-text">Kamera</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Container Camera QR Reader (Hidden by default) --}}
                    <div id="camera-scanner-container" class="mt-3 d-none text-center bg-dark p-2 rounded-3">
                        <div id="reader" style="max-width: 320px; margin: 0 auto;"></div>
                        <small class="text-white opacity-75 mt-1 d-block">Arahkan kamera ke QR Code label stiker pakaian</small>
                    </div>

                    {{-- Banner Live Result Alert --}}
                    <div id="live-alert-banner" class="mt-3 d-none">
                        <div id="live-alert-box" class="alert border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-3">
                            <div id="live-alert-icon" class="fs-2"></div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span id="badge-spk-num" class="badge bg-dark text-white fw-bold px-2 py-1"></span>
                                    <span id="badge-prod-num" class="badge bg-secondary text-white small"></span>
                                    <span id="badge-size-num" class="badge bg-primary text-white small"></span>
                                </div>
                                <h6 id="live-alert-title" class="fw-bold mb-1"></h6>
                                <p id="live-alert-msg" class="mb-0 small"></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- TABEL RIWAYAT SCAN SESI INI & RIWAYAT TERBARU --}}
    <div class="card border shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="fas fa-history text-primary"></i>
                <span>Riwayat Scan Penerimaan Karung Sesi Ini</span>
            </h6>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border small">Terbaru 50 Transaksi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-history">
                    <thead class="table-light">
                        <tr class="text-uppercase small text-muted">
                            <th class="ps-3" style="width: 130px;">Waktu</th>
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
                                <td class="ps-3 text-muted small font-monospace">
                                    {{ $p->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @if($spk)
                                        <a href="{{ route('spks.show', $spk->id) }}" target="_blank" class="fw-bold text-decoration-none text-primary badge-spk">
                                            #{{ $spk->no_spk }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $spk->no_produksi ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $p->item->nama_produk ?? 'Produk' }}</div>
                                    <span class="text-muted small font-monospace">{{ $p->item->sku ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ $p->item->ukuran ?: 'All' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                                        +{{ $p->qty_diambil }} Pcs
                                    </span>
                                </td>
                                <td class="small text-secondary">
                                    <i class="fas fa-user-circle me-1"></i>{{ $p->nama_pengambil ?: ($p->pemberi->name ?? 'Gudang') }}
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 border-0"
                                        title="Batalkan Catatan Penerimaan Ini" onclick="cancelPickup({{ $p->id }})">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-barcode fs-2 d-block mb-2 opacity-50"></i>
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

{{-- Audio Context & HTML5 QR Scanner Script --}}
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

        // Auto Focus Input Barcode
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
            btnToggleSound.classList.toggle('btn-outline-light', isSoundEnabled);
            btnToggleSound.classList.toggle('btn-outline-secondary', !isSoundEnabled);
        });

        // Web Audio Synthesizer (Zero External File Dependencies)
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
                console.error("Audio error:", e);
            }
        }

        function playSuccessChime() {
            playBeep(880, 'sine', 0.08, 0.2); // A5
            setTimeout(() => playBeep(1318.5, 'sine', 0.12, 0.2), 90); // E6
        }

        function playErrorBuzzer() {
            playBeep(160, 'sawtooth', 0.35, 0.3);
            setTimeout(() => playBeep(140, 'sawtooth', 0.35, 0.3), 100);
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
            }
        }

        // Keydown Enter on Barcode Input
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
            btnSubmitScan.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>Proses...`;

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
                btnSubmitScan.innerHTML = `<i class="fas fa-arrow-right me-1"></i>Proses`;
                barcodeInput.value = '';
                barcodeInput.focus();
            });
        }

        function handleScanSuccess(res) {
            playSuccessChime();

            // Update stats counter
            sessionCount++;
            sessionPcs += res.pickup.qty;
            statSessionCount.innerText = sessionCount;
            statSessionPcs.innerText = sessionPcs + " Pcs";

            // Display Alert Banner
            liveAlertBanner.classList.remove('d-none');
            liveAlertBox.className = 'alert alert-success border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-3 bg-success bg-opacity-10 text-success';
            liveAlertIcon.innerHTML = `<i class="fas fa-check-circle text-success"></i>`;
            badgeSpkNum.innerText = `SPK #${res.spk.no_spk}`;
            badgeProdNum.innerText = `PROD #${res.spk.no_produksi}`;
            badgeSizeNum.innerText = `SIZE: ${res.item.ukuran}`;
            liveAlertTitle.innerText = res.message;
            liveAlertMsg.innerText = `Terima: ${res.item.nama_produk} (SKU: ${res.item.sku}) | Total Diterima: ${res.item.qty_diambil} / Target: ${res.item.quantity} pcs (${res.item.sisa_qty} sisa)`;

            // Prepend row to table
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `pickup-row-${res.pickup.id}`;
            tr.className = 'row-scan-pulse';
            tr.innerHTML = `
                <td class="ps-3 text-muted small font-monospace">${res.pickup.tanggal}</td>
                <td>
                    <a href="/spks/${res.spk.id}" target="_blank" class="fw-bold text-decoration-none text-primary badge-spk">
                        #${res.spk.no_spk}
                    </a>
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace">
                        ${res.spk.no_produksi}
                    </span>
                </td>
                <td>
                    <div class="fw-bold text-dark">${res.item.nama_produk}</div>
                    <span class="text-muted small font-monospace">${res.item.sku}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary">${res.item.ukuran}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold fs-6">
                        +${res.pickup.qty} Pcs
                    </span>
                </td>
                <td class="small text-secondary">
                    <i class="fas fa-user-circle me-1"></i>${res.pickup.nama_pengambil}
                </td>
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 border-0"
                        title="Batalkan Catatan Penerimaan Ini" onclick="cancelPickup(${res.pickup.id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            tbodyHistory.insertBefore(tr, tbodyHistory.firstChild);
        }

        function handleScanError(res) {
            playErrorBuzzer();

            liveAlertBanner.classList.remove('d-none');
            liveAlertBox.className = 'alert alert-danger border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-3 bg-danger bg-opacity-10 text-danger';
            liveAlertIcon.innerHTML = `<i class="fas fa-exclamation-triangle text-danger"></i>`;
            badgeSpkNum.innerText = `GAGAL`;
            badgeProdNum.innerText = `ERR`;
            badgeSizeNum.innerText = `!`;
            liveAlertTitle.innerText = res.title || 'PENERIMAAN GAGAL!';
            liveAlertMsg.innerText = (res.message || 'Kode tidak dapat diproses.') + (res.detail ? ' ' + res.detail : '');
        }

        // Cancel Pickup
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
