@extends('layouts.app')
@section('title', 'Scanner Penerimaan Barang SPK #' . $spk->no_spk)
@section('page-title', 'Penerimaan Hasil Produksi')

@push('styles')
<style>
    :root {
        --spk-primary: #3b82f6;
        --spk-indigo: #4f46e5;
        --spk-emerald: #10b981;
        --spk-danger: #ef4444;
        --spk-amber: #f59e0b;
    }

    .scanner-wrapper {
        font-family: 'Inter', system-ui, sans-serif;
    }

    /* Top SPK Hero Banner */
    .spk-hero-banner {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
        border-radius: 16px;
        color: #fff;
        padding: 24px;
        box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.25);
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .spk-hero-banner::after {
        content: '\f466';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 20px;
        bottom: -20px;
        font-size: 8rem;
        color: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    /* Summary Stat Cards */
    .stat-pill-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 16px 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-pill-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }

    /* Viewfinder / Input Box */
    .scanner-input-card {
        background: #ffffff;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .scanner-viewfinder {
        background: #f8fafc;
        border: 2.5px dashed #94a3b8;
        border-radius: 14px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .scanner-viewfinder:hover, .scanner-viewfinder.is-active {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .scanner-input-field {
        font-size: 1.25rem !important;
        font-weight: 700;
        letter-spacing: 0.8px;
        border-radius: 12px !important;
        border: 2px solid #3b82f6 !important;
        padding: 12px 16px !important;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12) !important;
    }
    .scanner-input-field:focus {
        border-color: #1d4ed8 !important;
        box-shadow: 0 0 0 5px rgba(29, 78, 216, 0.22) !important;
        outline: none;
    }

    /* Item Grid Cards */
    .spk-item-grid-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        transition: all 0.25s ease-in-out;
        position: relative;
        overflow: hidden;
    }
    .spk-item-grid-card.is-completed {
        border-color: #10b981;
        background: #f0fdf4;
    }
    .spk-item-grid-card.pulse-scan {
        animation: cardScanPulse 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    @keyframes cardScanPulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        40% { transform: scale(1.03); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); border-color: #10b981; }
        100% { transform: scale(1); }
    }

    /* Error Shake Animation */
    .shake-error {
        animation: shakeErrorAnim 0.45s cubic-bezier(.36,.07,.19,.97) both;
    }
    @keyframes shakeErrorAnim {
        10%, 90% { transform: translate3d(-2px, 0, 0); }
        20%, 80% { transform: translate3d(4px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
        40%, 60% { transform: translate3d(6px, 0, 0); }
    }

    /* Progress bar */
    .item-progress-track {
        height: 10px;
        border-radius: 6px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .item-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #10b981);
        border-radius: 6px;
        transition: width 0.35s ease-out;
    }
    .item-progress-fill.fill-completed {
        background: #10b981 !important;
    }

    /* Camera viewfinder container */
    #reader {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
    }
    #reader video {
        border-radius: 12px;
    }

    /* Size Badge Badge-Style */
    .size-badge-pill {
        font-size: 1.15rem;
        font-weight: 800;
        min-width: 48px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-2 px-md-4 py-3 scanner-wrapper">

    {{-- Top Action Navigation Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('spks.show', $spk->id) }}" class="btn btn-outline-secondary btn-sm fw-bold px-3 shadow-2xs">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail SPK
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('spks.print_labels', $spk->id) }}" target="_blank" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm">
                <i class="fas fa-tags me-1"></i> Cetak Label Stiker Kemasan
            </a>
            <a href="{{ route('spks.print', $spk->id) }}" target="_blank" class="btn btn-outline-primary btn-sm fw-bold px-3">
                <i class="fas fa-print me-1"></i> Lembar Cetak SPK
            </a>
        </div>
    </div>

    {{-- SPK Hero Header Banner --}}
    <div class="spk-hero-banner">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-white text-primary fw-extrabold px-2.5 py-1 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        {{ $spk->tipe_spk === 'stok_gudang' ? '🏬 Produksi Stok Gudang' : '🛒 Pesanan Klien' }}
                    </span>
                    @if($spk->is_urgent)
                        <span class="badge bg-danger text-white fw-bold px-2 py-1"><i class="fas fa-bolt me-1"></i>URGENT</span>
                    @endif
                    <span class="badge bg-light bg-opacity-25 text-white fw-medium px-2 py-1">
                        Tahap: {{ strtoupper($spk->tahap_saat_ini ?: 'PRODUKSI') }}
                    </span>
                </div>
                <h3 class="fw-extrabold mb-1 text-white font-monospace" style="letter-spacing: -0.5px;">
                    SPK #{{ $spk->no_spk }}
                    @if($spk->no_produksi)
                        <span class="fs-6 opacity-75 fw-normal font-monospace ms-2">({{ $spk->no_produksi }})</span>
                    @endif
                </h3>
                <p class="mb-0 text-white text-opacity-90 small">
                    <strong>Pemesan:</strong> {{ $spk->pemesan ?: 'Internal' }}
                    @if($spk->instansi) | <strong>Instansi:</strong> {{ $spk->instansi }} @endif
                    | <strong>Deadline:</strong> <span class="badge bg-warning text-dark px-2 py-0.5">{{ $spk->deadline ? $spk->deadline->format('d M Y') : '—' }}</span>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="bg-white bg-opacity-15 p-3 rounded-4 backdrop-blur d-inline-block text-center text-lg-end" style="min-width: 220px;">
                    <span class="text-white text-opacity-75 small d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Progres Penerimaan Barang</span>
                    <div class="d-flex align-items-baseline justify-content-center justify-content-lg-end gap-1 my-1">
                        <span class="fs-2 fw-black text-white" id="overall-percent">{{ $percentComplete }}%</span>
                        <span class="small text-white text-opacity-80">Selesai</span>
                    </div>
                    <div class="progress bg-white bg-opacity-25" style="height: 6px; border-radius: 4px;">
                        <div class="progress-bar bg-warning" id="overall-progress-bar" style="width: {{ $percentComplete }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Row (Total, Diambil, Sisa) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-pill-card d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="fas fa-boxes-stacked fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-bold d-block text-uppercase" style="font-size: 0.68rem;">Total Pesanan</span>
                    <h4 class="fw-black text-dark mb-0" id="stat-total-target">{{ $totalTarget }} <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-pill-card d-flex align-items-center gap-3" style="border-left: 4px solid var(--spk-emerald);">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="fas fa-circle-check fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-bold d-block text-uppercase" style="font-size: 0.68rem;">Sudah Diterima</span>
                    <h4 class="fw-black text-success mb-0" id="stat-total-diambil">{{ $totalDiambil }} <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-pill-card d-flex align-items-center gap-3" style="border-left: 4px solid var(--spk-danger);">
                <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="fas fa-hourglass-half fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-bold d-block text-uppercase" style="font-size: 0.68rem;">Sisa Belum Diambil</span>
                    <h4 class="fw-black text-danger mb-0" id="stat-total-sisa">{{ $totalSisa }} <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-pill-card d-flex align-items-center gap-3">
                <div class="rounded-3 bg-indigo bg-opacity-10 text-indigo p-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px; background-color:#e0e7ff; color:#4f46e5;">
                    <i class="fas fa-layer-group fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-bold d-block text-uppercase" style="font-size: 0.68rem;">Varian Ukuran</span>
                    <h4 class="fw-black text-dark mb-0">{{ $spk->items->count() }} <span class="fs-6 fw-normal text-muted">item</span></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Scanner & Item List Grid -->
    <div class="row g-4 mb-4">

        <!-- Left Column: Interactive Scanner Controls -->
        <div class="col-lg-5">
            <div class="scanner-input-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-barcode text-primary"></i> Scan QR / Barcode Kemasan
                    </h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="sound-toggle" checked style="cursor: pointer;">
                        <label class="form-check-label small fw-bold text-muted" for="sound-toggle"><i class="fas fa-volume-high"></i> Suara</label>
                    </div>
                </div>

                <!-- Hardware Barcode Viewfinder -->
                <div class="scanner-viewfinder mb-3" id="viewfinder-box">
                    <i class="fas fa-barcode fa-3x text-primary mb-2 d-block"></i>
                    <div class="fw-bold text-dark">Siap Memindai Barcode / QR Kemasan</div>
                    <div class="small text-muted mt-1">Arahkan scanner hardware barcode / QR code pada bungkus produk</div>
                </div>

                <!-- Real-Time Barcode Input Box -->
                <form id="scan-form" onsubmit="return false;" class="mb-3">
                    <label for="barcode-input" class="form-label fw-bold text-dark small mb-1">
                        Input Tembak Barcode / QR Code:
                    </label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-light border-2 border-end-0 border-primary text-primary">
                            <i class="fas fa-qrcode fs-5"></i>
                        </span>
                        <input type="text" id="barcode-input" class="form-control scanner-input-field border-start-0"
                            placeholder="Scan QR/Barcode atau ketik SKU..." autofocus autocomplete="off">
                        <button class="btn btn-primary fw-bold px-3" type="button" id="btn-submit-scan">
                            <i class="fas fa-check"></i> Proses
                        </button>
                    </div>
                    <div class="form-text text-muted small" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle text-primary me-1"></i> Scanner barcode akan otomatis menekan tombol <em>Enter</em> untuk memotong SPK seketika.
                    </div>
                </form>

                <!-- Camera Scanner Toggle Button -->
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-3" id="btn-toggle-camera">
                        <i class="fas fa-camera"></i> <span id="camera-btn-text">Gunakan Kamera HP / Laptop</span>
                    </button>
                </div>

                <!-- HTML5 Camera Viewfinder (Hidden by default) -->
                <div id="camera-scanner-container" class="d-none mb-3 bg-dark p-2 rounded-3 text-center">
                    <div id="reader"></div>
                    <small class="text-white opacity-75 d-block mt-2">Arahkan kamera ke QR Code atau Barcode produk kemasan</small>
                </div>

                <!-- Receiver / Intake Setting -->
                <div class="bg-light p-3 rounded-3 border mb-3">
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label text-muted small fw-bold mb-1">Nama Penerima / Pengambil:</label>
                            <input type="text" id="nama-pengambil-input" class="form-control form-control-sm fw-bold"
                                value="{{ Auth::user()->name ?? 'Petugas Gudang' }}" placeholder="Nama Penerima">
                        </div>
                        <div class="col-4">
                            <label class="form-label text-muted small fw-bold mb-1">Qty / Scan:</label>
                            <input type="number" id="qty-scan-input" class="form-control form-control-sm text-center fw-bold"
                                value="1" min="1" max="100">
                        </div>
                    </div>
                </div>

                <!-- Live Status Feedback Banner -->
                <div id="live-feedback" class="d-none alert border-0 rounded-3 p-3 mb-0 shadow-sm transition-all"></div>
            </div>
        </div>

        <!-- Right Column: List of SPK Items with Quantities & Live Progress -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-list-check text-primary"></i> Target Item &amp; Variasi Ukuran SPK
                    </h6>
                    <span class="badge bg-light text-secondary border fw-bold">
                        {{ $spk->items->count() }} Ukuran Terdaftar
                    </span>
                </div>
                <div class="card-body p-3 p-md-4" style="background:#f8fafc;">

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
                                <div class="spk-item-grid-card {{ $isComplete ? 'is-completed' : '' }}" id="item-card-{{ $item->id }}" data-item-id="{{ $item->id }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="size-badge-pill {{ $isComplete ? 'bg-success text-white' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' }}"
                                                  id="item-size-badge-{{ $item->id }}">
                                                {{ $item->ukuran ?: 'ALL' }}
                                            </span>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.92rem;">{{ $item->nama_produk }}</h6>
                                                <small class="text-muted font-monospace d-block" style="font-size: 0.72rem;">
                                                    SKU: <strong class="text-primary">{{ $item->sku ?: '—' }}</strong>
                                                    @if($item->masterProduct && $item->masterProduct->barcode)
                                                        | BC: <span class="text-secondary">{{ $item->masterProduct->barcode }}</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>

                                        <span class="badge {{ $isComplete ? 'bg-success' : 'bg-warning text-dark' }} fw-bold px-2 py-1 rounded-pill"
                                              id="item-status-badge-{{ $item->id }}" style="font-size: 0.7rem;">
                                            {{ $isComplete ? '✅ LENGKAP' : '⏳ SISA ' . $itemSisa }}
                                        </span>
                                    </div>

                                    <!-- Progress & Quantities -->
                                    <div class="d-flex justify-content-between align-items-center mt-3 mb-1" style="font-size: 0.78rem;">
                                        <span class="text-muted">
                                            Diterima: <strong class="text-success fs-6" id="item-diambil-{{ $item->id }}">{{ $itemDiambil }}</strong>
                                            / <span class="text-dark fw-bold" id="item-target-{{ $item->id }}">{{ $itemTarget }}</span> pcs
                                        </span>
                                        <span class="fw-bold {{ $isComplete ? 'text-success' : 'text-danger' }}">
                                            Sisa: <strong class="fs-6" id="item-sisa-{{ $item->id }}">{{ $itemSisa }}</strong> pcs
                                        </span>
                                    </div>

                                    <div class="item-progress-track">
                                        <div class="item-progress-fill {{ $isComplete ? 'fill-completed' : '' }}"
                                             id="item-progress-fill-{{ $item->id }}"
                                             style="width: {{ $itemPct }}%;"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>

            <!-- Recent Scan Log Table in This Session -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-clock-rotate-left text-primary"></i> Riwayat Scan Sesi Ini
                    </h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" id="scan-counter-badge">
                        {{ $recentPickups->count() }} Pengambilan
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 18%;">Waktu</th>
                                    <th>Item &amp; Ukuran</th>
                                    <th class="text-center" style="width: 15%;">Jumlah</th>
                                    <th style="width: 25%;">Penerima</th>
                                    <th class="text-center" style="width: 12%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="scan-log-tbody">
                                @forelse($recentPickups as $pickup)
                                    <tr id="pickup-row-{{ $pickup->id }}">
                                        <td class="text-muted fw-semibold">
                                            {{ $pickup->tanggal_ambil ? $pickup->tanggal_ambil->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $pickup->item->nama_produk ?? 'Item' }}</strong>
                                            <span class="badge bg-secondary ms-1">{{ $pickup->item->ukuran ?? '—' }}</span>
                                        </td>
                                        <td class="text-center fw-bold text-success">
                                            +{{ $pickup->qty_diambil }} pcs
                                        </td>
                                        <td class="text-muted">
                                            {{ $pickup->nama_pengambil }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-delete-pickup"
                                                onclick="deletePickupRecord({{ $pickup->id }})" title="Hapus / Batalkan">
                                                <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="scan-log-empty-row">
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-barcode opacity-25 fs-3 d-block mb-1"></i>
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

<!-- Modal Alert Peringatan: Barang Salah / Kuota Penuh -->
<div class="modal fade" id="modalScanAlert" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden shake-error" id="modal-alert-content">
            <div class="modal-header bg-danger text-white py-3 px-4" id="modal-alert-header">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modal-alert-title">
                    <i class="fas fa-triangle-exclamation fs-4"></i> BARANG TIDAK SESUAI!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center"
                     id="modal-alert-icon-wrap" style="width: 76px; height: 76px;">
                    <i class="fas fa-times-circle fa-3x" id="modal-alert-icon"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="modal-alert-msg-primary">Barcode / SKU Tidak Sesuai</h5>
                <div class="p-3 bg-light rounded-3 border text-secondary small mb-3" id="modal-alert-msg-detail">
                    Item ini bukan bagian dari SPK yang sedang diproses.
                </div>
                <div class="text-muted small" style="font-size: 0.75rem;">
                    Tekan <strong>[Spasi]</strong> atau klik tombol di bawah untuk lanjut memindai.
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-4 justify-content-center">
                <button type="button" class="btn btn-danger fw-bold px-4 rounded-pill" data-bs-dismiss="modal" id="btn-alert-dismiss">
                    OK, Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- HTML5 QR Code Scanner Library for Mobile / Webcam Scanning -->
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

        // Modal Alert Elements
        const modalAlertEl = document.getElementById('modalScanAlert');
        const modalAlertObj = new bootstrap.Modal(modalAlertEl);
        const modalAlertTitle = document.getElementById('modal-alert-title');
        const modalAlertMsgPrimary = document.getElementById('modal-alert-msg-primary');
        const modalAlertMsgDetail = document.getElementById('modal-alert-msg-detail');
        const modalAlertHeader = document.getElementById('modal-alert-header');
        const modalAlertIcon = document.getElementById('modal-alert-icon');
        const modalAlertIconWrap = document.getElementById('modal-alert-icon-wrap');
        const modalAlertContent = document.getElementById('modal-alert-content');

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
            // Bright, pleasant double-beep
            playBeep(880, 'sine', 0.08, 0.2); // A5
            setTimeout(() => playBeep(1318.5, 'sine', 0.12, 0.2), 90); // E6
        }

        function playErrorBuzzer() {
            // Low sawtooth alarm buzzer
            playBeep(160, 'sawtooth', 0.35, 0.3);
            setTimeout(() => playBeep(140, 'sawtooth', 0.35, 0.3), 100);
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
            }
        }

        function playCompleteFanfare() {
            // Victory 4-tone celebration
            playBeep(523.25, 'triangle', 0.09, 0.25); // C5
            setTimeout(() => playBeep(659.25, 'triangle', 0.09, 0.25), 110); // E5
            setTimeout(() => playBeep(783.99, 'triangle', 0.09, 0.25), 220); // G5
            setTimeout(() => playBeep(1046.50, 'triangle', 0.3, 0.25), 330); // C6
        }

        // Refocus barcode input when clicking anywhere on viewfinder
        viewfinderBox.addEventListener('click', () => {
            barcodeInput.focus();
            viewfinderBox.classList.add('is-active');
            setTimeout(() => viewfinderBox.classList.remove('is-active'), 500);
        });

        // Trigger on Enter Key from Hardware Scanner
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitScan();
            }
        });

        btnSubmitScan.addEventListener('click', submitScan);

        // Refocus input whenever alert modal is closed
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
            btnSubmitScan.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses...`;

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
                btnSubmitScan.innerHTML = `<i class="fas fa-check"></i> Proses`;
                barcodeInput.value = '';
                barcodeInput.focus();
            });
        }

        // On Successful Scan
        function handleScanSuccess(data) {
            const item = data.item;

            // 1. Play Audio Chime
            if (data.all_completed) {
                playCompleteFanfare();
            } else {
                playSuccessChime();
            }

            // 2. Update Item Card DOM
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
                    cardEl.classList.add('is-completed');
                    progressFillEl.classList.add('fill-completed');
                    statusBadgeEl.className = 'badge bg-success fw-bold px-2 py-1 rounded-pill';
                    statusBadgeEl.innerHTML = '✅ LENGKAP';
                    sizeBadgeEl.className = 'size-badge-pill bg-success text-white';
                } else {
                    statusBadgeEl.className = 'badge bg-warning text-dark fw-bold px-2 py-1 rounded-pill';
                    statusBadgeEl.innerHTML = `⏳ SISA ${item.sisa_qty}`;
                }

                // Trigger Pulse Animation
                cardEl.classList.remove('pulse-scan');
                void cardEl.offsetWidth; // trigger reflow
                cardEl.classList.add('pulse-scan');
            }

            // 3. Update Overall Totals
            document.getElementById('stat-total-diambil').innerHTML = `${data.spk_total_diambil} <span class="fs-6 fw-normal text-muted">pcs</span>`;
            document.getElementById('stat-total-sisa').innerHTML = `${data.spk_total_sisa} <span class="fs-6 fw-normal text-muted">pcs</span>`;
            document.getElementById('overall-percent').innerText = `${data.percent_complete}%`;
            document.getElementById('overall-progress-bar').style.width = `${data.percent_complete}%`;

            // 4. Show Live Feedback Banner
            liveFeedback.className = 'alert alert-success border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-2';
            liveFeedback.innerHTML = `
                <i class="fas fa-check-circle fs-4 text-success"></i>
                <div>
                    <strong>Scan Berhasil!</strong> Menerima ${data.pickup.qty} pcs <strong>${item.nama_produk} (Size: ${item.ukuran})</strong>.
                    <span class="d-block small opacity-75">Sisa kuota: ${item.sisa_qty} pcs.</span>
                </div>
            `;
            liveFeedback.classList.remove('d-none');

            // 5. Prepend Record to Log Table
            const tbody = document.getElementById('scan-log-tbody');
            const emptyRow = document.getElementById('scan-log-empty-row');
            if (emptyRow) emptyRow.remove();

            const tr = document.createElement('tr');
            tr.id = `pickup-row-${data.pickup.id}`;
            tr.className = 'bg-success bg-opacity-10';
            tr.innerHTML = `
                <td class="text-muted fw-semibold">${data.pickup.tanggal}</td>
                <td>
                    <strong class="text-dark">${item.nama_produk}</strong>
                    <span class="badge bg-primary ms-1">${item.ukuran}</span>
                </td>
                <td class="text-center fw-bold text-success">+${data.pickup.qty} pcs</td>
                <td class="text-muted">${data.pickup.nama_pengambil}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-delete-pickup"
                        onclick="deletePickupRecord(${data.pickup.id})" title="Hapus / Batalkan">
                        <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
                    </button>
                </td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);

            // If completely done, alert user with celebration
            if (data.all_completed) {
                Swal.fire({
                    icon: 'success',
                    title: '🎉 SELURUH PESANAN LENGKAP!',
                    text: 'Semua item dalam SPK ini telah diterima 100%. Status SPK otomatis diperbarui menjadi Selesai!',
                    confirmButtonText: 'Mantap!',
                    confirmButtonColor: '#10b981'
                });
            }
        }

        // On Scan Error (Alert & Buzzer)
        function handleScanError(data) {
            playErrorBuzzer();

            const isQuota = data.error_type === 'quota_exceeded';

            modalAlertTitle.innerHTML = `<i class="fas fa-triangle-exclamation fs-4"></i> ${data.title || 'PERINGATAN SCAN!'}`;
            modalAlertMsgPrimary.innerText = data.message || 'Terjadi kesalahan saat memproses barcode.';
            modalAlertMsgDetail.innerText = data.detail || '';

            if (isQuota) {
                modalAlertHeader.className = 'modal-header bg-warning text-dark py-3 px-4';
                modalAlertIconWrap.className = 'rounded-circle bg-warning bg-opacity-10 text-warning mx-auto mb-3 d-flex align-items-center justify-content-center';
                modalAlertIcon.className = 'fas fa-box-check fa-3x text-warning-emphasis';
                document.getElementById('btn-alert-dismiss').className = 'btn btn-warning fw-bold px-4 rounded-pill text-dark';
            } else {
                modalAlertHeader.className = 'modal-header bg-danger text-white py-3 px-4';
                modalAlertIconWrap.className = 'rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center';
                modalAlertIcon.className = 'fas fa-times-circle fa-3x text-danger';
                document.getElementById('btn-alert-dismiss').className = 'btn btn-danger fw-bold px-4 rounded-pill';
            }

            modalAlertObj.show();

            // Also show red feedback banner below input
            liveFeedback.className = 'alert alert-danger border-0 rounded-3 p-3 mb-0 shadow-sm d-flex align-items-center gap-2';
            liveFeedback.innerHTML = `
                <i class="fas fa-circle-exclamation fs-4 text-danger"></i>
                <div>
                    <strong>${data.title || 'Error!'}</strong> ${data.message}
                </div>
            `;
            liveFeedback.classList.remove('d-none');
        }

        // Delete / Undo Pickup Record via AJAX
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
                    const row = document.getElementById(`pickup-row-${pickupId}`);
                    if (row) row.remove();
                    // Reload page to re-sync item counters safely
                    location.reload();
                } else {
                    alert(data.message || "Gagal menghapus.");
                }
            })
            .catch(err => {
                alert("Terjadi kesalahan: " + err.message);
            });
        };

        // Camera Scanner Toggle via Html5Qrcode
        btnToggleCamera.addEventListener('click', function() {
            if (!isCameraActive) {
                startCameraScanner();
            } else {
                stopCameraScanner();
            }
        });

        function startCameraScanner() {
            cameraContainer.classList.remove('d-none');
            cameraBtnText.innerText = "Matikan Kamera Scanner";
            btnToggleCamera.classList.replace('btn-outline-primary', 'btn-outline-danger');

            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    // On QR Code or Barcode Scanned successfully
                    if (!isProcessing) {
                        barcodeInput.value = decodedText;
                        submitScan(decodedText);
                    }
                },
                (errorMessage) => {
                    // Scan error/no QR in frame - ignore
                }
            ).then(() => {
                isCameraActive = true;
            }).catch(err => {
                console.error("Camera access failed:", err);
                alert("Gagal mengakses kamera: " + err + ". Pastikan izin kamera aktif.");
                stopCameraScanner();
            });
        }

        function stopCameraScanner() {
            if (html5QrCode && isCameraActive) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    cameraContainer.classList.add('d-none');
                    cameraBtnText.innerText = "Gunakan Kamera HP / Laptop";
                    btnToggleCamera.classList.replace('btn-outline-danger', 'btn-outline-primary');
                    isCameraActive = false;
                }).catch(err => console.error("Error stopping camera:", err));
            } else {
                cameraContainer.classList.add('d-none');
                cameraBtnText.innerText = "Gunakan Kamera HP / Laptop";
                btnToggleCamera.classList.replace('btn-outline-danger', 'btn-outline-primary');
                isCameraActive = false;
            }
        }
    });
</script>
@endpush
