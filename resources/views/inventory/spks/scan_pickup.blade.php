@extends('layouts.app')
@section('title', 'Penerimaan Hasil Produksi - SPK #' . $spk->no_spk)
@section('page-title', 'Penerimaan Hasil Produksi')

@push('styles')
<style>
    body {
        background-color: #f8fafc !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    /* Elegant Card Transitions */
    .card-hover-shadow {
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .card-hover-shadow:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08) !important;
        transform: translateY(-2px);
    }

    /* Interactive Item Card */
    .item-card-row {
        border-left: 4px solid #e2e8f0;
        transition: all 0.25s ease-in-out;
    }
    .item-card-row.is-active-card {
        border-left-color: #3b82f6;
    }
    .item-card-row.is-complete-card {
        border-left-color: #10b981;
        background-color: #f0fdf4 !important;
    }

    /* Pulse animation on successful scan */
    .pulse-success {
        animation: pulseGreen 0.6s ease;
    }
    @keyframes pulseGreen {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
        50% { transform: scale(1.02); box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
        100% { transform: scale(1); }
    }

    /* Scanner Viewfinder Box */
    .scanner-box-drop {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        background-color: #ffffff;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .scanner-box-drop:hover, .scanner-box-drop.is-focused {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }

    /* Scanner Big Input */
    .scanner-main-input {
        font-size: 1.15rem !important;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .scanner-main-input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
    }

    /* Size Badge Indicator */
    .badge-size-large {
        font-size: 1.25rem;
        font-weight: 800;
        min-width: 52px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    /* Camera Scanner Box */
    #reader {
        width: 100%;
        max-width: 460px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
    }
    #reader video {
        border-radius: 12px;
    }

    /* Modal Shake on Error */
    .shake-anim {
        animation: modalShake 0.4s cubic-bezier(.36,.07,.19,.97) both;
    }
    @keyframes modalShake {
        10%, 90% { transform: translate3d(-2px, 0, 0); }
        20%, 80% { transform: translate3d(4px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
        40%, 60% { transform: translate3d(6px, 0, 0); }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- ── 1. TOP HEADER & BREADCRUMB ── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('spks.show', $spk->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke SPK
                </a>
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1.5 rounded-pill" style="font-size: 0.75rem;">
                    {{ $spk->tipe_spk === 'stok_gudang' ? '🏬 Produksi Stok Gudang' : '🛒 Pesanan Klien' }}
                </span>
                @if($spk->is_urgent)
                    <span class="badge bg-danger text-white fw-bold px-2 py-1 rounded-pill"><i class="fas fa-bolt me-1"></i>URGENT</span>
                @endif
            </div>
            <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2 font-monospace">
                SPK #{{ $spk->no_spk }}
                @if($spk->no_produksi)
                    <span class="text-secondary fw-normal fs-6">({{ $spk->no_produksi }})</span>
                @endif
            </h4>
            <div class="text-muted small mt-0.5">
                Pemesan: <strong class="text-dark">{{ $spk->pemesan ?: 'Internal / Gudang' }}</strong>
                @if($spk->instansi) &bull; Instansi: <span class="text-dark">{{ $spk->instansi }}</span> @endif
                @if($spk->deadline) &bull; Target Selesai: <span class="text-danger fw-semibold"><i class="far fa-clock me-1"></i>{{ $spk->deadline->format('d M Y') }}</span> @endif
            </div>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('spks.print_labels', $spk->id) }}" target="_blank" class="btn btn-dark btn-sm fw-semibold rounded-3 px-3 shadow-sm">
                <i class="fas fa-tags me-1.5"></i> Cetak Label Stiker
            </a>
            <a href="{{ route('spks.print', $spk->id) }}" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold rounded-3 px-3">
                <i class="fas fa-print me-1.5"></i> Cetak SPK
            </a>
        </div>
    </div>

    <!-- ── 2. SUMMARY METRICS ROW (SIMPLE & ELEGAN) ── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white card-hover-shadow">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Target Total</span>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill p-1.5"><i class="fas fa-boxes-stacked"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold text-dark mb-0" id="stat-total-target">{{ $totalTarget }}</h3>
                    <span class="text-muted small">pcs</span>
                </div>
                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Total seluruh varian di SPK</small>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white card-hover-shadow">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-success small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sudah Diterima</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-1.5"><i class="fas fa-circle-check"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold text-success mb-0" id="stat-total-diambil">{{ $totalDiambil }}</h3>
                    <span class="text-muted small">pcs</span>
                </div>
                <small class="text-success mt-1 d-block fw-semibold" style="font-size: 0.75rem;">
                    <i class="fas fa-arrow-trend-up me-1"></i>Tercatat masuk gudang
                </small>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white card-hover-shadow">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-danger small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sisa Belum Diambil</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill p-1.5"><i class="fas fa-hourglass-half"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold text-danger mb-0" id="stat-total-sisa">{{ $totalSisa }}</h3>
                    <span class="text-muted small">pcs</span>
                </div>
                <small class="text-danger mt-1 d-block fw-semibold" style="font-size: 0.75rem;">
                    Kurang {{ $totalSisa }} pcs untuk selesai
                </small>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white card-hover-shadow">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-primary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Progres Penerimaan</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill p-1.5"><i class="fas fa-chart-pie"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold text-primary mb-0" id="overall-percent">{{ $percentComplete }}%</h3>
                    <span class="text-muted small">selesai</span>
                </div>
                <div class="progress mt-2" style="height: 6px; border-radius: 4px;">
                    <div class="progress-bar bg-primary" id="overall-progress-bar" style="width: {{ $percentComplete }}%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 3. MAIN WORKSPACE: SCANNER (LEFT) & SPK VARIANT CARDS (RIGHT) ── -->
    <div class="row g-4">

        <!-- LEFT COLUMN: SCANNER INPUT & SETTINGS -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-barcode text-primary"></i> Pemindai Barcode / QR
                        </h6>
                        <small class="text-muted">Arahkan scanner hardware atau kamera HP</small>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="sound-toggle" checked style="cursor: pointer;">
                        <label class="form-check-label small fw-semibold text-muted" for="sound-toggle">
                            <i class="fas fa-volume-high"></i> Suara
                        </label>
                    </div>
                </div>

                <div class="card-body p-4">

                    <!-- Big Scanner Drop Target Viewfinder -->
                    <div class="scanner-box-drop p-4 text-center mb-3" id="viewfinder-box">
                        <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                            <i class="fas fa-qrcode fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Siap Menerima Scan</h6>
                        <p class="text-muted small mb-0">Tembakkan barcode kemasan produk. Input akan otomatis terisi &amp; diproses seketika.</p>
                    </div>

                    <!-- Input Group -->
                    <form id="scan-form" onsubmit="return false;" class="mb-3">
                        <label for="barcode-input" class="form-label small fw-bold text-secondary mb-1">
                            Ketik / Tembak Barcode Kemasan:
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-2 border-end-0 border-primary text-primary">
                                <i class="fas fa-keyboard"></i>
                            </span>
                            <input type="text" id="barcode-input"
                                   class="form-control form-control-lg scanner-main-input border-2 border-primary border-start-0"
                                   placeholder="Scan barcode atau ketik SKU..." autofocus autocomplete="off">
                            <button class="btn btn-primary fw-bold px-3" type="button" id="btn-submit-scan">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Camera Toggle Button -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-semibold rounded-3 d-flex align-items-center justify-content-center gap-2" id="btn-toggle-camera">
                            <i class="fas fa-camera text-primary"></i> <span id="camera-btn-text">Nyalakan Kamera HP / Webcam</span>
                        </button>
                    </div>

                    <!-- Camera Viewfinder Box (Hidden by default) -->
                    <div id="camera-scanner-container" class="d-none mb-3 bg-dark p-2 rounded-3 text-center">
                        <div id="reader"></div>
                        <small class="text-white opacity-75 d-block mt-2">Arahkan kamera tepat pada QR Code / Barcode kemasan</small>
                    </div>

                    <!-- Settings (Receiver & Qty) -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="row g-2">
                            <div class="col-8">
                                <label class="form-label text-muted small fw-semibold mb-1">Penerima / Pengambil:</label>
                                <input type="text" id="nama-pengambil-input" class="form-control form-control-sm fw-bold"
                                       value="{{ Auth::user()->name ?? 'Petugas Gudang' }}" placeholder="Nama Penerima">
                            </div>
                            <div class="col-4">
                                <label class="form-label text-muted small fw-semibold mb-1">Qty per Scan:</label>
                                <input type="number" id="qty-scan-input" class="form-control form-control-sm text-center fw-bold"
                                       value="1" min="1" max="100">
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Alert Banner -->
                    <div id="live-feedback" class="d-none alert border-0 rounded-3 p-3 mb-0 shadow-sm transition-all"></div>

                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: VARIANT / SIZE ITEMS STATUS -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-boxes-packing text-primary"></i> Daftar Ukuran &amp; Kuota SPK
                        </h6>
                        <small class="text-muted">Status kuota berkurang otomatis setiap kali kemasan di-scan</small>
                    </div>
                    <span class="badge bg-light text-secondary border fw-bold">
                        {{ $spk->items->count() }} Varian Ukuran
                    </span>
                </div>

                <div class="card-body p-4 pt-2">
                    <div class="row g-3" id="spk-items-container">
                        @foreach($spk->items as $item)
                            @php
                                $itemDiambil = (int) $item->qty_diambil;
                                $itemSisa = (int) $item->sisa_qty;
                                $itemTarget = (int) $item->quantity;
                                $isComplete = ($itemSisa == 0);
                                $itemPct = $itemTarget > 0 ? min(100, round(($itemDiambil / $itemTarget) * 100)) : 0;
                            @endphp

                            <div class="col-md-6">
                                <div class="card border shadow-sm rounded-3 p-3 h-100 item-card-row {{ $isComplete ? 'is-complete-card' : 'bg-white' }}"
                                     id="item-card-{{ $item->id }}" data-item-id="{{ $item->id }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <span class="badge-size-large {{ $isComplete ? 'bg-success text-white' : 'bg-dark text-white' }}"
                                                  id="item-size-badge-{{ $item->id }}">
                                                {{ $item->ukuran ?: 'ALL' }}
                                            </span>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">
                                                    {{ $item->nama_produk }}
                                                </h6>
                                                <small class="text-muted font-monospace d-block" style="font-size: 0.72rem;">
                                                    SKU: <strong class="text-primary">{{ $item->sku ?: '—' }}</strong>
                                                    @if($item->masterProduct && $item->masterProduct->barcode)
                                                        &bull; BC: <span class="text-secondary">{{ $item->masterProduct->barcode }}</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>

                                        <span class="badge {{ $isComplete ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }} fw-bold px-2.5 py-1 rounded-pill"
                                              id="item-status-badge-{{ $item->id }}" style="font-size: 0.72rem;">
                                            {{ $isComplete ? '✅ Lengkap' : '⏳ Sisa ' . $itemSisa }}
                                        </span>
                                    </div>

                                    <!-- Counts & Progress -->
                                    <div class="d-flex justify-content-between align-items-baseline mt-3 mb-1.5" style="font-size: 0.8rem;">
                                        <span class="text-muted">
                                            Diterima: <strong class="text-dark fs-6" id="item-diambil-{{ $item->id }}">{{ $itemDiambil }}</strong>
                                            / <span class="text-secondary">{{ $itemTarget }} pcs</span>
                                        </span>
                                        <span class="fw-bold {{ $isComplete ? 'text-success' : 'text-danger' }}">
                                            Sisa: <strong class="fs-6" id="item-sisa-{{ $item->id }}">{{ $itemSisa }}</strong> pcs
                                        </span>
                                    </div>

                                    <div class="progress" style="height: 7px; border-radius: 4px;">
                                        <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-primary' }}"
                                             id="item-progress-fill-{{ $item->id }}"
                                             style="width: {{ $itemPct }}%;"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ── 4. RECENT SCAN LOG TABLE ── -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-clock-rotate-left text-primary"></i> Riwayat Scan Sesi Ini
                        </h6>
                        <small class="text-muted">Daftar item yang baru saja diterima &amp; dipotong kuotanya</small>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary fw-semibold rounded-pill px-2.5 py-1" id="scan-counter-badge">
                        {{ $recentPickups->count() }} Pengambilan
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 260px;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 20%;" class="ps-4">Waktu</th>
                                    <th>Item &amp; Ukuran</th>
                                    <th class="text-center" style="width: 15%;">Jumlah</th>
                                    <th style="width: 25%;">Penerima</th>
                                    <th class="text-center pe-4" style="width: 12%;">Batal</th>
                                </tr>
                            </thead>
                            <tbody id="scan-log-tbody">
                                @forelse($recentPickups as $pickup)
                                    <tr id="pickup-row-{{ $pickup->id }}">
                                        <td class="text-muted ps-4">
                                            {{ $pickup->tanggal_ambil ? $pickup->tanggal_ambil->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $pickup->item->nama_produk ?? 'Item' }}</strong>
                                            <span class="badge bg-dark-subtle text-dark border ms-1">{{ $pickup->item->ukuran ?? '—' }}</span>
                                        </td>
                                        <td class="text-center fw-bold text-success">
                                            +{{ $pickup->qty_diambil }} pcs
                                        </td>
                                        <td class="text-secondary">
                                            {{ $pickup->nama_pengambil }}
                                        </td>
                                        <td class="text-center pe-4">
                                            <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2 rounded-2"
                                                    onclick="deletePickupRecord({{ $pickup->id }})" title="Hapus / Batalkan">
                                                <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="scan-log-empty-row">
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-barcode opacity-25 fs-2 d-block mb-1"></i>
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
</div>

<!-- ── MODAL ALERT: BARANG SALAH / KUOTA PENUH (CLEAN BOOTSTRAP 5) ── -->
<div class="modal fade" id="modalScanAlert" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden shake-anim" id="modal-alert-content">
            <div class="modal-header bg-danger text-white py-3 px-4 border-0" id="modal-alert-header">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="modal-alert-title">
                    <i class="fas fa-triangle-exclamation"></i> BARANG TIDAK SESUAI
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-danger-subtle text-danger mx-auto mb-3 d-flex align-items-center justify-content-center"
                     id="modal-alert-icon-wrap" style="width: 72px; height: 72px;">
                    <i class="fas fa-times-circle fa-3x" id="modal-alert-icon"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="modal-alert-msg-primary">Barcode Tidak Terdaftar</h5>
                <div class="p-3 bg-light rounded-3 text-secondary small mb-3 border text-start" id="modal-alert-msg-detail">
                    Item ini bukan bagian dari SPK yang sedang diproses.
                </div>
                <div class="text-muted small">
                    Tekan <strong>[Spasi]</strong> atau klik tombol di bawah untuk melanjutkan scan.
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 px-4 justify-content-center">
                <button type="button" class="btn btn-danger fw-semibold px-4 rounded-pill" data-bs-dismiss="modal" id="btn-alert-dismiss">
                    OK, Saya Mengerti
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
        const viewfinderBox = document.getElementById('viewfinder-box');
        const soundToggle = document.getElementById('sound-toggle');
        const qtyScanInput = document.getElementById('qty-scan-input');
        const namaPengambilInput = document.getElementById('nama-pengambil-input');
        const btnToggleCamera = document.getElementById('btn-toggle-camera');
        const cameraContainer = document.getElementById('camera-scanner-container');
        const cameraBtnText = document.getElementById('camera-btn-text');

        const modalAlertEl = document.getElementById('modalScanAlert');
        const modalAlertObj = new bootstrap.Modal(modalAlertEl);
        const modalAlertTitle = document.getElementById('modal-alert-title');
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

        // Viewfinder click autofocus
        viewfinderBox.addEventListener('click', () => {
            barcodeInput.focus();
            viewfinderBox.classList.add('is-focused');
            setTimeout(() => viewfinderBox.classList.remove('is-focused'), 500);
        });

        // Keydown Enter from Hardware Barcode Gun
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitScan();
            }
        });

        btnSubmitScan.addEventListener('click', submitScan);

        modalAlertEl.addEventListener('hidden.bs.modal', function() {
            barcodeInput.focus();
        });

        // Submit Scan Processing
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
            btnSubmitScan.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

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
                btnSubmitScan.innerHTML = `<i class="fas fa-arrow-right"></i>`;
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

            // Update Item Card DOM
            const cardEl = document.getElementById(`item-card-${item.id}`);
            const diambilEl = document.getElementById(`item-diambil-${item.id}`);
            const sisaEl = document.getElementById(`item-sisa-${item.id}`);
            const progressFillEl = document.getElementById(`item-progress-fill-${item.id}`);
            const statusBadgeEl = document.getElementById(`item-status-badge-${item.id}`);
            const sizeBadgeEl = document.getElementById(`item-size-badge-${item.id}`);

            if (cardEl) {
                diambilEl.innerText = item.qty_diambil;
                sisaEl.innerText = item.sisa_qty;

                const pct = item.quantity > 0 ? Math.min(100, Math.round((item.qty_diambil / item.quantity) * 100)) : 0;
                progressFillEl.style.width = `${pct}%`;

                if (item.is_completed) {
                    cardEl.classList.remove('bg-white');
                    cardEl.classList.add('is-complete-card');
                    progressFillEl.className = 'progress-bar bg-success';
                    statusBadgeEl.className = 'badge bg-success-subtle text-success fw-bold px-2.5 py-1 rounded-pill';
                    statusBadgeEl.innerHTML = '✅ Lengkap';
                    sizeBadgeEl.className = 'badge-size-large bg-success text-white';
                } else {
                    statusBadgeEl.className = 'badge bg-warning-subtle text-warning-emphasis fw-bold px-2.5 py-1 rounded-pill';
                    statusBadgeEl.innerHTML = `⏳ Sisa ${item.sisa_qty}`;
                }

                // Green pulse effect
                cardEl.classList.remove('pulse-success');
                void cardEl.offsetWidth; // trigger reflow
                cardEl.classList.add('pulse-success');
            }

            // Update Global Stats
            document.getElementById('stat-total-diambil').innerText = data.spk_total_diambil;
            document.getElementById('stat-total-sisa').innerText = data.spk_total_sisa;
            document.getElementById('overall-percent').innerText = `${data.percent_complete}%`;
            document.getElementById('overall-progress-bar').style.width = `${data.percent_complete}%`;

            // Live Banner
            liveFeedback.className = 'alert alert-success border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-2';
            liveFeedback.innerHTML = `
                <i class="fas fa-check-circle fs-5 text-success"></i>
                <div class="small">
                    <strong>Scan Berhasil!</strong> Menerima ${data.pickup.qty} pcs <strong>${item.nama_produk} (Size: ${item.ukuran})</strong>.
                    <span class="d-block text-secondary">Sisa kuota: ${item.sisa_qty} pcs.</span>
                </div>
            `;
            liveFeedback.classList.remove('d-none');

            // Add row to log table
            const tbody = document.getElementById('scan-log-tbody');
            const emptyRow = document.getElementById('scan-log-empty-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `pickup-row-${data.pickup.id}`;
            tr.className = 'table-success bg-opacity-25';
            tr.innerHTML = `
                <td class="text-muted ps-4">${data.pickup.tanggal}</td>
                <td>
                    <strong class="text-dark">${item.nama_produk}</strong>
                    <span class="badge bg-dark-subtle text-dark border ms-1">${item.ukuran}</span>
                </td>
                <td class="text-center fw-bold text-success">+${data.pickup.qty} pcs</td>
                <td class="text-secondary">${data.pickup.nama_pengambil}</td>
                <td class="text-center pe-4">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2 rounded-2"
                            onclick="deletePickupRecord(${data.pickup.id})" title="Hapus / Batalkan">
                        <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
                    </button>
                </td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);

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

        // Handle Scan Error
        function handleScanError(data) {
            playErrorBuzzer();

            const isQuota = data.error_type === 'quota_exceeded';

            modalAlertTitle.innerHTML = `<i class="fas fa-triangle-exclamation me-1"></i> ${data.title || 'PERINGATAN SCAN'}`;
            modalAlertMsgPrimary.innerText = data.message || 'Barcode tidak sesuai.';
            modalAlertMsgDetail.innerText = data.detail || '';

            if (isQuota) {
                modalAlertHeader.className = 'modal-header bg-warning text-dark py-3 px-4 border-0';
                modalAlertIconWrap.className = 'rounded-circle bg-warning-subtle text-warning-emphasis mx-auto mb-3 d-flex align-items-center justify-content-center';
                modalAlertIcon.className = 'fas fa-box-check fa-3x text-warning-emphasis';
                btnAlertDismiss.className = 'btn btn-warning fw-semibold px-4 rounded-pill text-dark';
            } else {
                modalAlertHeader.className = 'modal-header bg-danger text-white py-3 px-4 border-0';
                modalAlertIconWrap.className = 'rounded-circle bg-danger-subtle text-danger mx-auto mb-3 d-flex align-items-center justify-content-center';
                modalAlertIcon.className = 'fas fa-times-circle fa-3x text-danger';
                btnAlertDismiss.className = 'btn btn-danger fw-semibold px-4 rounded-pill';
            }

            modalAlertObj.show();

            liveFeedback.className = 'alert alert-danger border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-2';
            liveFeedback.innerHTML = `
                <i class="fas fa-circle-exclamation fs-5 text-danger"></i>
                <div class="small">
                    <strong>${data.title || 'Error!'}</strong> ${data.message}
                </div>
            `;
            liveFeedback.classList.remove('d-none');
        }

        // Undo / Delete Pickup Record
        window.deletePickupRecord = function(pickupId) {
            if (!confirm("Batalkan dan hapus catatan penerimaan ini?")) return;

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
            cameraBtnText.innerText = "Matikan Kamera";
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
                    cameraBtnText.innerText = "Nyalakan Kamera HP / Webcam";
                    btnToggleCamera.classList.replace('btn-outline-danger', 'btn-outline-secondary');
                    isCameraActive = false;
                }).catch(err => console.error("Stop camera error:", err));
            } else {
                cameraContainer.classList.add('d-none');
                cameraBtnText.innerText = "Nyalakan Kamera HP / Webcam";
                btnToggleCamera.classList.replace('btn-outline-danger', 'btn-outline-secondary');
                isCameraActive = false;
            }
        }
    });
</script>
@endpush
