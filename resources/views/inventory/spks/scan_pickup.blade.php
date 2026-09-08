@extends('layouts.app')
@section('title', 'Penerimaan Hasil Produksi - SPK #' . $spk->no_spk)
@section('page-title', 'Penerimaan Hasil Produksi')

@push('styles')
<style>
    /* Subtle highlight pulse on successful scan */
    .row-scan-pulse {
        animation: rowPulseGreen 0.8s ease;
    }
    @keyframes rowPulseGreen {
        0% { background-color: rgba(16, 185, 129, 0.35) !important; }
        100% { background-color: inherit; }
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── 1. Scanner & Filter Control Card (Gaya Menu User) ───────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3 px-3">

                    {{-- SPK Meta Info & Quick Summary --}}
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 flex-shrink-0 small fw-bold">
                                        <i class="fas fa-file-invoice me-1"></i>SPK #{{ $spk->no_spk }}
                                    </span>
                                    <span class="badge {{ $spk->tipe_spk === 'stok_gudang' ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' }} small">
                                        <i class="fas {{ $spk->tipe_spk === 'stok_gudang' ? 'fa-warehouse' : 'fa-shopping-cart' }} me-1"></i>
                                        {{ $spk->tipe_spk === 'stok_gudang' ? 'Produksi Stok Gudang' : 'Pesanan Klien' }}
                                    </span>
                                    @if($spk->is_urgent)
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 small fw-bold">
                                            <i class="fas fa-bolt me-1"></i>URGENT
                                        </span>
                                    @endif
                                    <span class="text-muted small">
                                        Pemesan: <strong class="text-dark">{{ $spk->pemesan ?: 'Gudang Internal' }}</strong>
                                        @if($spk->instansi) &bull; Instansi: <span class="text-dark">{{ $spk->instansi }}</span> @endif
                                        @if($spk->deadline) &bull; Target: <span class="text-danger fw-semibold"><i class="far fa-clock me-1"></i>{{ $spk->deadline->format('d/m/Y') }}</span> @endif
                                    </span>
                                </div>

                                {{-- Ringkasan Angka --}}
                                <div class="d-flex align-items-center gap-3 flex-wrap small ms-auto">
                                    <span class="text-muted">Target: <strong class="text-dark" id="stat-total-target">{{ $totalTarget }}</strong> pcs</span>
                                    <span class="text-muted">&bull; Diterima: <strong class="text-success" id="stat-total-diambil">{{ $totalDiambil }}</strong> pcs</span>
                                    <span class="text-muted">&bull; Sisa: <strong class="text-danger" id="stat-total-sisa">{{ $totalSisa }}</strong> pcs</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" id="overall-percent">
                                        {{ $percentComplete }}% Selesai
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">

                    {{-- Baris Input Scanner --}}
                    <form id="scan-form" onsubmit="return false;">
                        <div class="row g-2 align-items-end">

                            {{-- Input Barcode / SKU Kemasan --}}
                            <div class="col-md-5">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-barcode text-primary me-1"></i>Scan / Ketik Barcode / SKU Kemasan
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="barcode-input" class="form-control form-control-sm font-monospace fw-bold"
                                        placeholder="Tembak barcode kemasan atau ketik SKU..." autofocus autocomplete="off">
                                    <button type="button" id="btn-submit-scan" class="btn btn-primary btn-sm px-3">
                                        <i class="fas fa-arrow-right me-1"></i>Proses
                                    </button>
                                </div>
                            </div>

                            {{-- Qty per Scan --}}
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-calculator text-muted me-1"></i>Qty / Scan
                                </label>
                                <input type="number" id="qty-scan-input" class="form-control form-control-sm text-center fw-bold"
                                    value="1" min="1" max="100">
                            </div>

                            {{-- Petugas Penerima --}}
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-user-check text-muted me-1"></i>Petugas Penerima
                                </label>
                                <input type="text" id="nama-pengambil-input" class="form-control form-control-sm"
                                    value="{{ Auth::user()->name ?? 'Petugas Gudang' }}" placeholder="Nama Penerima">
                            </div>

                            {{-- Tombol Kamera & Toggle Suara --}}
                            <div class="col-md-2 d-flex align-items-center justify-content-md-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-toggle-camera" title="Gunakan Kamera HP / Webcam">
                                    <i class="fas fa-camera text-primary me-1"></i><span id="camera-btn-text">Kamera</span>
                                </button>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="sound-toggle" checked style="cursor: pointer;">
                                    <label class="form-check-label small fw-semibold text-muted" for="sound-toggle" title="Bunyi Alarm / Suara">
                                        <i class="fas fa-volume-high"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Container Kamera jika diaktifkan --}}
                    <div id="camera-scanner-container" class="d-none mt-3 p-3 bg-dark rounded border text-center">
                        <div id="reader" style="max-width: 360px; margin: 0 auto;"></div>
                        <small class="text-white opacity-75 d-block mt-2">Arahkan kamera tepat ke QR Code atau Barcode kemasan</small>
                    </div>

                    {{-- Live Alert Feedback Banner --}}
                    <div id="live-feedback" class="d-none alert py-2 px-3 mt-3 mb-0 small" role="alert"></div>

                </div>
            </div>

            {{-- ── 2. Tabel Utama: Daftar Item & Kuota SPK (Gaya Menu User) ───── --}}
            <div class="card border shadow-sm mb-3">
                {{-- Card Header --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-boxes-stacked me-2"></i>Daftar Item &amp; Kuota SPK
                        </h6>
                        <p class="text-muted mb-0 small mt-1">
                            Monitoring penerimaan hasil produksi per varian ukuran &mdash; <span class="text-primary fw-semibold">SPK #{{ $spk->no_spk }}</span>
                        </p>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('spks.show', $spk->id) }}" class="btn btn-secondary btn-sm px-3">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke SPK
                        </a>
                        <a href="{{ route('spks.print_labels', $spk->id) }}" target="_blank" class="btn btn-dark btn-sm px-3">
                            <i class="fas fa-tags me-1"></i> Cetak Label Stiker
                        </a>
                        <a href="{{ route('spks.print', $spk->id) }}" target="_blank" class="btn btn-outline-primary btn-sm px-3">
                            <i class="fas fa-print me-1"></i> Cetak SPK
                        </a>
                    </div>
                </div>

                <div class="card-body p-3">

                    {{-- Progres Akumulasi --}}
                    <div class="d-flex align-items-center justify-content-between mb-1.5 small">
                        <span class="fw-semibold text-muted">Akumulasi Total Selesai:</span>
                        <span class="fw-bold text-primary" id="overall-progress-text">
                            {{ $totalDiambil }} dari {{ $totalTarget }} pcs ({{ $percentComplete }}%)
                        </span>
                    </div>
                    <div class="progress mb-3" style="height: 7px;">
                        <div class="progress-bar bg-primary" id="overall-progress-bar" style="width: {{ $percentComplete }}%;"></div>
                    </div>

                    {{-- Tabel Item SPK --}}
                    <div class="table-responsive rounded border mt-2">
                        <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">#</th>
                                    <th>NAMA PRODUK</th>
                                    <th class="text-center" style="width: 90px;">UKURAN</th>
                                    <th>SKU / BARCODE</th>
                                    <th class="text-center" style="width: 100px;">TARGET</th>
                                    <th class="text-center" style="width: 110px;">DITERIMA</th>
                                    <th class="text-center" style="width: 110px;">SISA KUOTA</th>
                                    <th style="width: 170px;">PROGRES</th>
                                    <th class="text-center" style="width: 130px;">STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="spk-items-table-body">
                                @forelse($spk->items as $i => $item)
                                    @php
                                        $itemDiambil = (int) $item->qty_diambil;
                                        $itemSisa = (int) $item->sisa_qty;
                                        $itemTarget = (int) $item->quantity;
                                        $isComplete = ($itemSisa == 0);
                                        $itemPct = $itemTarget > 0 ? min(100, round(($itemDiambil / $itemTarget) * 100)) : 0;
                                    @endphp
                                    <tr id="item-row-{{ $item->id }}" class="{{ $isComplete ? 'table-success bg-opacity-25' : '' }}">
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <strong class="text-dark small">{{ $item->nama_produk }}</strong>
                                            @if($item->catatan)
                                                <div class="text-muted" style="font-size: 0.72rem;">{{ $item->catatan }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 fw-bold px-2 py-1" id="item-size-badge-{{ $item->id }}">
                                                {{ $item->ukuran ?: 'ALL' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-monospace small text-primary fw-semibold">{{ $item->sku ?: '—' }}</span>
                                            @if($item->masterProduct && $item->masterProduct->barcode)
                                                <div class="font-monospace text-muted" style="font-size: 0.72rem;">
                                                    BC: {{ $item->masterProduct->barcode }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-bold">
                                                {{ $itemTarget }} pcs
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold" id="item-diambil-badge-{{ $item->id }}">
                                                <span id="item-diambil-{{ $item->id }}">{{ $itemDiambil }}</span> pcs
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $isComplete ? 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }} fw-bold" id="item-sisa-badge-{{ $item->id }}">
                                                <span id="item-sisa-{{ $item->id }}">{{ $itemSisa }}</span> pcs
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-primary' }}"
                                                        id="item-progress-fill-{{ $item->id }}"
                                                        style="width: {{ $itemPct }}%;"></div>
                                                </div>
                                                <span class="small fw-bold text-muted" id="item-pct-text-{{ $item->id }}">{{ $itemPct }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($isComplete)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" id="item-status-badge-{{ $item->id }}">
                                                    <i class="fas fa-check-circle me-1"></i>Lengkap
                                                </span>
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" id="item-status-badge-{{ $item->id }}">
                                                    <i class="fas fa-hourglass-half me-1"></i>Sisa {{ $itemSisa }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4 small">
                                            <i class="fas fa-boxes-stacked me-2 opacity-50"></i>
                                            Tidak ada item dalam SPK ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            {{-- ── 3. Tabel Riwayat Scan Sesi Ini (Gaya Menu User) ─────────────── --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-history me-2"></i>Riwayat Scan Penerimaan Sesi Ini
                        </h6>
                        <p class="text-muted mb-0 small mt-1">
                            Daftar item kemasan yang baru saja di-scan dan dimasukkan ke stok barang jadi
                        </p>
                    </div>
                    <span class="text-muted small">
                        Total: <strong class="text-dark" id="scan-counter-badge">{{ $recentPickups->count() }}</strong> catatan
                    </span>
                </div>

                <div class="card-body p-3">
                    <div class="table-responsive rounded border mt-1">
                        <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">#</th>
                                    <th style="width: 140px;">WAKTU</th>
                                    <th>NAMA PRODUK</th>
                                    <th class="text-center" style="width: 90px;">UKURAN</th>
                                    <th class="text-center" style="width: 90px;">JUMLAH</th>
                                    <th>PETUGAS PENERIMA</th>
                                    <th>CATATAN</th>
                                    <th class="text-center" style="width: 80px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="scan-log-tbody">
                                @forelse($recentPickups as $idx => $pickup)
                                    <tr id="pickup-row-{{ $pickup->id }}">
                                        <td class="text-center text-muted small">{{ $idx + 1 }}</td>
                                        <td class="small text-muted font-monospace">
                                            {{ $pickup->tanggal_ambil ? $pickup->tanggal_ambil->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td>
                                            <strong class="text-dark small">{{ $pickup->item->nama_produk ?? 'Item' }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 small">
                                                {{ $pickup->item->ukuran ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold">
                                                +{{ $pickup->qty_diambil }} pcs
                                            </span>
                                        </td>
                                        <td class="small text-dark">
                                            {{ $pickup->nama_pengambil }}
                                        </td>
                                        <td class="small text-muted">
                                            {{ $pickup->catatan ?: '-' }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm"
                                                onclick="deletePickupRecord({{ $pickup->id }})" title="Hapus / Batalkan">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="scan-log-empty-row">
                                        <td colspan="8" class="text-center text-muted py-4 small">
                                            <i class="fas fa-barcode opacity-50 me-1"></i>
                                            Belum ada barcode yang di-scan pada sesi ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── 4. MODAL ALERT: BARANG SALAH / KUOTA PENUH (GAYA MODAL MENU USER) ─ --}}
    <div class="modal fade" id="modalScanAlert" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-danger bg-opacity-10 border-bottom" id="modal-alert-header">
                    <div class="bg-danger text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5"
                        id="modal-alert-icon-wrap" style="width: 40px; height: 40px;">
                        <i class="fas fa-exclamation-triangle" id="modal-alert-icon"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="modal-alert-title">Peringatan Scan!</h5>
                        <p class="mb-0 text-muted small" id="modal-alert-subtitle">Barang tidak sesuai atau kuota penuh</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <h6 class="fw-bold text-dark fs-5 mb-1" id="modal-alert-msg-primary">Barcode Tidak Terdaftar</h6>
                        <p class="text-muted small mb-0" id="modal-alert-msg-detail">Item ini bukan bagian dari SPK yang sedang diproses.</p>
                    </div>
                    <div class="alert alert-warning bg-warning bg-opacity-10 border border-warning border-opacity-25 py-2 px-3 mb-0 small text-dark">
                        <i class="fas fa-info-circle me-1 text-warning"></i>
                        Tekan <strong>[Spasi]</strong> atau klik tombol <strong>Tutup &amp; Scan Ulang</strong> untuk kembali memindai.
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-end border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal" id="btn-alert-dismiss">
                        Tutup &amp; Scan Ulang
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const barcodeInput = document.getElementById('barcode-input');
        const btnSubmitScan = document.getElementById('btn-submit-scan');
        const liveFeedback = document.getElementById('live-feedback');
        const soundToggle = document.getElementById('sound-toggle');
        const qtyScanInput = document.getElementById('qty-scan-input');
        const namaPengambilInput = document.getElementById('nama-pengambil-input');
        const btnToggleCamera = document.getElementById('btn-toggle-camera');
        const cameraContainer = document.getElementById('camera-scanner-container');
        const cameraBtnText = document.getElementById('camera-btn-text');

        const modalAlertEl = document.getElementById('modalScanAlert');
        const modalAlertObj = new bootstrap.Modal(modalAlertEl);
        const modalAlertTitle = document.getElementById('modal-alert-title');
        const modalAlertSubtitle = document.getElementById('modal-alert-subtitle');
        const modalAlertMsgPrimary = document.getElementById('modal-alert-msg-primary');
        const modalAlertMsgDetail = document.getElementById('modal-alert-msg-detail');
        const modalAlertHeader = document.getElementById('modal-alert-header');
        const modalAlertIcon = document.getElementById('modal-alert-icon');
        const modalAlertIconWrap = document.getElementById('modal-alert-icon-wrap');
        const btnAlertDismiss = document.getElementById('btn-alert-dismiss');

        let isProcessing = false;
        let html5QrCode = null;
        let isCameraActive = false;

        // Web Audio API Sound Synthesizer
        let audioCtx = null;
        function initAudio() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        }

        function playBeep(freq, type, duration, volume = 0.15) {
            if (!soundToggle.checked) return;
            try {
                initAudio();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(volume, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {
                console.error("Audio synth error:", e);
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

        function playCompleteFanfare() {
            playBeep(523.25, 'triangle', 0.09, 0.25); // C5
            setTimeout(() => playBeep(659.25, 'triangle', 0.09, 0.25), 110); // E5
            setTimeout(() => playBeep(783.99, 'triangle', 0.09, 0.25), 220); // G5
            setTimeout(() => playBeep(1046.50, 'triangle', 0.3, 0.25), 330); // C6
        }

        // Keydown Enter from Hardware Barcode Gun
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitScan();
            }
        });

        btnSubmitScan.addEventListener('click', () => submitScan());

        // Focus kembali ke input saat modal ditutup
        modalAlertEl.addEventListener('hidden.bs.modal', function() {
            barcodeInput.focus();
        });

        // Shortcut keyboard [Spasi] untuk menutup modal alert
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space' && modalAlertEl.classList.contains('show')) {
                e.preventDefault();
                modalAlertObj.hide();
                barcodeInput.focus();
            }
        });

        // Submit Scan Processing via AJAX
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

            fetch(`{{ route('spks.process_scan_pickup', $spk->id) }}`, {
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
                    catatan: 'Scan QR / Barcode Kemasan'
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
                console.error("Network or parsing error:", err);
                handleScanError({
                    title: 'KESALAHAN JARINGAN!',
                    message: 'Tidak dapat terhubung ke server. Silakan coba lagi.',
                    detail: err.message
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

        // Handle Scan Success
        function handleScanSuccess(data) {
            const item = data.item;

            if (data.all_completed) {
                playCompleteFanfare();
            } else {
                playSuccessChime();
            }

            // Update Item Row di Tabel
            const rowEl = document.getElementById(`item-row-${item.id}`);
            const diambilEl = document.getElementById(`item-diambil-${item.id}`);
            const sisaEl = document.getElementById(`item-sisa-${item.id}`);
            const sisaBadgeEl = document.getElementById(`item-sisa-badge-${item.id}`);
            const progressFillEl = document.getElementById(`item-progress-fill-${item.id}`);
            const pctTextEl = document.getElementById(`item-pct-text-${item.id}`);
            const statusBadgeEl = document.getElementById(`item-status-badge-${item.id}`);

            if (rowEl) {
                if (diambilEl) diambilEl.innerText = item.qty_diambil;
                if (sisaEl) sisaEl.innerText = item.sisa_qty;

                const pct = item.quantity > 0 ? Math.min(100, Math.round((item.qty_diambil / item.quantity) * 100)) : 0;
                if (progressFillEl) progressFillEl.style.width = `${pct}%`;
                if (pctTextEl) pctTextEl.innerText = `${pct}%`;

                if (item.is_completed) {
                    rowEl.className = 'table-success bg-opacity-25';
                    if (progressFillEl) progressFillEl.className = 'progress-bar bg-success';
                    if (sisaBadgeEl) {
                        sisaBadgeEl.className = 'badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-bold';
                    }
                    if (statusBadgeEl) {
                        statusBadgeEl.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                        statusBadgeEl.innerHTML = '<i class="fas fa-check-circle me-1"></i>Lengkap';
                    }
                } else {
                    if (sisaBadgeEl) {
                        sisaBadgeEl.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold';
                    }
                    if (statusBadgeEl) {
                        statusBadgeEl.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                        statusBadgeEl.innerHTML = `<i class="fas fa-hourglass-half me-1"></i>Sisa ${item.sisa_qty}`;
                    }
                }

                // Efek visual pulse pada baris yang baru terupdate
                rowEl.classList.remove('row-scan-pulse');
                void rowEl.offsetWidth; // trigger reflow
                rowEl.classList.add('row-scan-pulse');
            }

            // Update Total Angka Header & Akumulasi
            document.getElementById('stat-total-diambil').innerText = data.spk_total_diambil;
            document.getElementById('stat-total-sisa').innerText = data.spk_total_sisa;
            document.getElementById('overall-percent').innerText = `${data.percent_complete}% Selesai`;
            document.getElementById('overall-progress-bar').style.width = `${data.percent_complete}%`;
            
            const overallText = document.getElementById('overall-progress-text');
            if (overallText) {
                overallText.innerText = `${data.spk_total_diambil} dari ${data.spk_total_target || '{{ $totalTarget }}'} pcs (${data.percent_complete}%)`;
            }

            // Live Banner Feedback
            liveFeedback.className = 'alert alert-success border-0 py-2 px-3 mt-3 mb-0 small d-flex align-items-center gap-2';
            liveFeedback.innerHTML = `
                <i class="fas fa-check-circle fs-6 text-success"></i>
                <div>
                    <strong>Scan Berhasil:</strong> Menerima ${data.pickup.qty} pcs <strong>${item.nama_produk} (Ukuran: ${item.ukuran || 'ALL'})</strong>. Sisa kuota: ${item.sisa_qty} pcs.
                </div>
            `;
            liveFeedback.classList.remove('d-none');

            // Tambahkan baris baru ke Riwayat Scan
            const tbody = document.getElementById('scan-log-tbody');
            const emptyRow = document.getElementById('scan-log-empty-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `pickup-row-${data.pickup.id}`;
            tr.className = 'table-success bg-opacity-25';
            tr.innerHTML = `
                <td class="text-center text-muted small"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Baru</span></td>
                <td class="small text-muted font-monospace">${data.pickup.tanggal}</td>
                <td><strong class="text-dark small">${item.nama_produk}</strong></td>
                <td class="text-center">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 small">${item.ukuran || '—'}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold">+${data.pickup.qty} pcs</span>
                </td>
                <td class="small text-dark">${data.pickup.nama_pengambil}</td>
                <td class="small text-muted">Scan QR / Barcode Kemasan</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm"
                            onclick="deletePickupRecord(${data.pickup.id})" title="Hapus / Batalkan">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);

            // Update Counter Badge
            const counterEl = document.getElementById('scan-counter-badge');
            if (counterEl) {
                const currentCount = parseInt(counterEl.innerText) || 0;
                counterEl.innerText = currentCount + 1;
            }

            // Jika seluruh SPK selesai
            if (data.all_completed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Seluruh Pesanan Selesai!',
                    text: 'Semua item dalam SPK ini telah 100% diterima ke gudang.',
                    confirmButtonText: 'Selesai',
                    confirmButtonColor: '#10b981'
                });
            }
        }

        // Handle Scan Error (Alert Modal & Buzzer)
        function handleScanError(data) {
            playErrorBuzzer();

            const isQuota = data.error_type === 'quota_exceeded';

            modalAlertTitle.innerHTML = `<i class="fas fa-triangle-exclamation me-1"></i> ${data.title || 'Peringatan Scan!'}`;
            modalAlertSubtitle.innerText = isQuota ? 'Kuota pesanan varian ini telah terpenuhi' : 'Barang tidak sesuai atau bukan bagian dari SPK ini';
            modalAlertMsgPrimary.innerText = data.message || 'Barcode tidak sesuai.';
            modalAlertMsgDetail.innerText = data.detail || '';

            if (isQuota) {
                modalAlertHeader.className = 'modal-header d-flex align-items-center gap-3 p-3 bg-warning bg-opacity-10 border-bottom';
                modalAlertIconWrap.className = 'bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5';
                modalAlertIcon.className = 'fas fa-boxes-packing';
                btnAlertDismiss.className = 'btn btn-warning btn-sm px-4 text-dark fw-semibold';
            } else {
                modalAlertHeader.className = 'modal-header d-flex align-items-center gap-3 p-3 bg-danger bg-opacity-10 border-bottom';
                modalAlertIconWrap.className = 'bg-danger text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5';
                modalAlertIcon.className = 'fas fa-exclamation-triangle';
                btnAlertDismiss.className = 'btn btn-secondary btn-sm px-4';
            }

            modalAlertObj.show();

            // Feedback Banner di bawah form
            liveFeedback.className = 'alert alert-danger border-0 py-2 px-3 mt-3 mb-0 small d-flex align-items-center gap-2';
            liveFeedback.innerHTML = `
                <i class="fas fa-circle-exclamation fs-6 text-danger"></i>
                <div>
                    <strong>${data.title || 'Error!'}:</strong> ${data.message}
                </div>
            `;
            liveFeedback.classList.remove('d-none');
        }

        // Batalkan / Hapus Catatan Penerimaan via AJAX
        window.deletePickupRecord = function(pickupId) {
            if (!confirm("Apakah Anda yakin ingin membatalkan dan menghapus catatan penerimaan ini?")) return;

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
                    location.reload();
                } else {
                    alert(data.message || "Gagal menghapus.");
                }
            })
            .catch(err => {
                alert("Terjadi kesalahan: " + err.message);
            });
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
