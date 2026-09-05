@extends('layouts.app')
@section('title', 'Scanner Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Layar Scanner Gudang')

@push('styles')
<style>
    .item-verification-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }
    .item-verification-card:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06) !important;
        border-color: #cbd5e1;
    }
    .item-verification-card.is-scanned {
        animation: pulseScan 0.4s ease;
    }
    @keyframes pulseScan {
        0% { transform: scale(1); }
        50% { transform: scale(1.015); }
        100% { transform: scale(1); }
    }
    .btn-open-substitute {
        transition: all 0.2s ease;
        font-size: 0.75rem !important;
        letter-spacing: 0.2px;
    }
    .btn-open-substitute:hover {
        background-color: #fef3c7 !important;
        border-color: #f59e0b !important;
        color: #92400e !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2) !important;
    }
    #modalSubstituteItem .modal-content {
        background-color: #f8fafc;
    }
    .substitute-product-item {
        transition: all 0.18s ease-in-out;
        cursor: pointer;
    }
    .substitute-product-item:hover {
        border-color: #3b82f6 !important;
        background-color: #eff6ff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.08) !important;
    }
    .shadow-2xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .fs-7 {
        font-size: 0.78rem !important;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <!-- Kolom Kiri: Form & Pemindai -->
        <div class="col-md-5">
            <div class="card border shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-dark mb-3"><i class="fas fa-barcode"></i> Langkah 1: Scan Resi / Invoice</h5>
                    <div class="mt-3">
                        <label for="invoice-scan-input" class="form-label fw-bold">Nomor Resi / Invoice / Order ID</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-secondary border-opacity-25 text-secondary">
                                <i class="fas fa-file-invoice"></i>
                            </span>
                            <input type="text" id="invoice-scan-input"
                                class="form-control border-secondary border-opacity-25 fs-5 fw-medium"
                                placeholder="Scan resi/invoice di sini..." autofocus autocomplete="off">
                        </div>
                        <div class="form-text text-muted mt-2 small">
                            Arahkan scanner atau ketik nomor resi/invoice, lalu tekan <strong>Enter</strong>.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border shadow-sm mb-4 d-none" id="product-scan-section">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-dark mb-3"><i class="fas fa-box"></i> Langkah 2: Scan SKU Produk</h5>
                    <div class="mt-3">
                        <label for="sku-scan-input" class="form-label fw-bold text-primary">Scan Barcode / SKU Barang</label>
                        <div class="input-group">
                            <span class="input-group-text text-primary bg-light border-secondary border-opacity-25">
                                <i class="fas fa-barcode"></i>
                            </span>
                            <input type="text" id="sku-scan-input"
                                class="form-control border-secondary border-opacity-25 fs-4 fw-bold font-monospace letter-spacing-1"
                                placeholder="Scan barcode SKU barang di sini..." autocomplete="off" disabled>
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="auto-ship-toggle" checked
                                style="cursor: pointer;">
                            <label class="form-check-label fw-bold text-dark cursor-pointer" for="auto-ship-toggle">
                                Otomatis Request Kirim & Cetak Resi
                            </label>
                        </div>
                        <div class="form-text text-muted mt-1 small">
                            Saat scan produk selesai, sistem akan otomatis mengirim status siap kirim ke Shopee/TikTok dan
                            membuka tab cetak label resi.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border border-primary border-dashed bg-primary bg-opacity-5 mb-4">
                <div class="card-body">
                    <h4 class="h6 fw-bold text-primary mb-2"><i class="fas fa-keyboard"></i> Pintasan Scanner & Tips</h4>
                    <ul class="small text-secondary-emphasis ps-3 mb-0" style="line-height: 1.6;">
                        <li>Pastikan kursor aktif pada input teks yang dituju (berwarna biru/primary).</li>
                        <li>Gunakan scanner yang diprogram mengirim karakter <strong>Enter (CRLF)</strong> di akhir kode.</li>
                        <li>Jika produk tidak memiliki barcode, Anda bisa mengetik SKU secara manual lalu tekan Enter.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Detail Pesanan yang Sedang Diproses -->
        <div class="col-md-7">
            <!-- Keadaan Kosong (Belum ada pesanan dimuat) -->
            <div class="card border border-dashed text-center py-5 px-4" id="empty-state">
                <div class="card-body">
                    <i class="fas fa-truck-loading text-muted opacity-25 mb-4" style="font-size: 4rem;"></i>
                    <h3 class="h5 fw-bold text-secondary">Silakan Scan Nomor Resi / Invoice</h3>
                    <p class="text-muted mx-auto mt-2 small" style="max-width: 380px;">
                        Scan kode resi pengiriman atau invoice marketplace untuk memuat detail pesanan dan memulai verifikasi
                        produk.
                    </p>
                </div>
            </div>

            <!-- Detail Pesanan (Hidden by default, loaded via JS) -->
            <div class="card border shadow-sm mb-4 d-none" id="order-details-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start border-bottom pb-3">
                        <div>
                            <h3 class="h5 fw-bold text-dark" id="order-invoice-title">Invoice</h3>
                            <div class="small text-muted mt-1">
                                Toko: <span class="fw-bold text-dark" id="order-store-name">-</span>
                                <span class="badge ms-2 text-dark border bg-light" id="order-channel-badge"
                                    style="font-size: 0.7rem; padding: 3px 6px;">-</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Pembeli</div>
                            <div class="fw-bold text-dark" id="order-buyer-name">-</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded border my-3">
                        <div>
                            <div class="small text-muted">Layanan Ekspedisi</div>
                            <div class="fw-bold text-dark fs-5" id="order-courier-name">-</div>
                        </div>
                        <div class="text-end" id="order-status-wrapper">
                            <span class="badge bg-warning text-dark px-3 py-2" id="order-packing-status-badge">Sedang
                                Dikemas</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 34px; height: 34px;">
                                <i class="fas fa-boxes-packing fs-6"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Barang yang Harus Diambil & Diverifikasi</h6>
                                <div class="text-muted" style="font-size: 0.75rem;">Ambil barang fisik di rak & scan barcode sesuai daftar di bawah</div>
                            </div>
                        </div>
                        <div id="items-completion-badge" class="badge rounded-pill bg-light text-secondary border px-3 py-1.5 font-monospace fw-semibold" style="font-size: 0.75rem;">
                            <span id="items-verified-count">0</span> / <span id="items-total-count">0</span> Lengkap
                        </div>
                    </div>

                    <div id="items-list-container" class="d-flex flex-column gap-2">
                        <!-- Items rows will be inserted here dynamically -->
                    </div>

                    <!-- Tombol Konfirmasi Manual & Status Bar -->
                    <div class="mt-4 border-top pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold" id="btn-reset">
                            <i class="fas fa-redo-alt me-1"></i> Batal / Reset Order
                        </button>
                        <button type="button" class="btn btn-success px-4 py-2 fs-6 fw-bold d-none shadow-sm" id="btn-submit-verification">
                            <i class="fas fa-check-double me-1.5"></i> Selesai & Konfirmasi Kemas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tukar SKU / Substitusi Produk (Full Screen) -->
    <div class="modal fade" id="modalSubstituteItem" tabindex="-1" aria-labelledby="modalSubstituteItemLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0 bg-light d-flex flex-column" style="height: 100vh;">
                <!-- Header -->
                <div class="modal-header bg-white border-bottom px-4 py-3 align-items-center flex-shrink-0 shadow-2xs">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-15 text-warning rounded-3" style="width: 44px; height: 44px;">
                            <i class="fas fa-exchange-alt fs-5"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="modal-title fw-bold text-dark mb-0 fs-5" id="modalSubstituteItemLabel">
                                    Tukar / Ganti Varian Produk (Substitusi Gudang)
                                </h5>
                                <span class="badge bg-warning text-dark px-2.5 py-1" style="font-size: 0.72rem;">
                                    <i class="fas fa-sync-alt me-1"></i>Mutasi Otomatis
                                </span>
                            </div>
                            <div class="text-muted small mt-0.5" id="substitute-modal-order-subtitle">
                                Ganti varian produk karena stok kosong/rusak atas kesepakatan pembeli. Mutasi kartu stok akan disesuaikan otomatis.
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body (Scrollable Split-View) -->
                <div class="modal-body p-4 overflow-y-auto flex-grow-1">
                    <div class="row g-4 h-100">
                        <!-- Kolom Kiri: Produk Asli & Alasan (col-lg-5 col-xl-4) -->
                        <div class="col-12 col-lg-5 col-xl-4 d-flex flex-column gap-3">
                            <!-- Card Produk Asli -->
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small">
                                        <i class="fas fa-box-open text-warning me-1"></i> 1. Produk Asli Pesanan
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle small px-2 py-0.5" style="font-size: 0.7rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Stok Gudang Kosong
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex gap-3 align-items-center">
                                        <img id="substitute-old-img" src="/images/placeholder.png" class="rounded-3 border bg-light flex-shrink-0" style="width: 68px; height: 68px; object-fit: cover;" alt="Produk Asli">
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark mb-1 text-truncate" id="substitute-old-name" style="font-size: 0.95rem;">-</div>
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.78rem;">
                                                    SKU: <strong class="text-dark" id="substitute-old-sku">-</strong>
                                                </span>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.78rem;">
                                                    Qty: <strong class="text-dark" id="substitute-old-qty">1</strong> pcs
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Alasan Penukaran -->
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-header bg-white border-bottom py-2.5 px-3">
                                    <span class="fw-bold text-dark small">
                                        <i class="fas fa-comment-dots text-primary me-1"></i> 2. Alasan Penukaran
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <label class="form-label small fw-semibold text-muted mb-1">Pilih Alasan Kesepakatan:</label>
                                    <select class="form-select mb-2" id="substitute-reason-select">
                                        <option value="Persetujuan Chat Pembeli (Stok Asli Habis)">Persetujuan Chat Pembeli (Stok Asli Habis)</option>
                                        <option value="Persetujuan Chat Pembeli (Ganti Ukuran / Warna)">Persetujuan Chat Pembeli (Ganti Ukuran / Warna)</option>
                                        <option value="Barang Rusak / Cacat / Reject di Gudang">Barang Rusak / Cacat / Reject di Gudang</option>
                                        <option value="custom">Alasan Lainnya (Ketik Manual)...</option>
                                    </select>
                                    <input type="text" class="form-control d-none" id="substitute-reason-custom" placeholder="Tulis alasan penukaran...">
                                </div>
                            </div>

                            <!-- Card Dampak Sistem & Kartu Stok -->
                            <div class="card border-0 shadow-sm rounded-3 bg-white">
                                <div class="card-header bg-white border-bottom py-2.5 px-3">
                                    <span class="fw-bold text-dark small">
                                        <i class="fas fa-shield-alt text-success me-1"></i> 3. Dampak Otomatis Sistem
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex flex-column gap-2 small text-secondary">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="fas fa-check-circle text-success mt-1"></i>
                                            <span><strong>Kartu Stok Lama:</strong> Pengurangan stok lama otomatis dibatalkan (mutasi kembali <em>IN</em>).</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="fas fa-check-circle text-success mt-1"></i>
                                            <span><strong>Kartu Stok Baru:</strong> Stok produk pengganti otomatis dipotong (mutasi keluar <em>OUT</em>).</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="fas fa-check-circle text-success mt-1"></i>
                                            <span><strong>Scanner Gudang:</strong> Target barcode pada scanner langsung dialihkan ke SKU baru.</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="fas fa-check-circle text-success mt-1"></i>
                                            <span><strong>Audit Trail:</strong> Catatan pergantian otomatis tertera di detail pesanan.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Cari & Pilih Produk Pengganti (col-lg-7 col-xl-8) -->
                        <div class="col-12 col-lg-7 col-xl-8 d-flex flex-column">
                            <div class="card border-0 shadow-sm rounded-3 flex-grow-1 d-flex flex-column" style="min-height: 520px;">
                                <div class="card-header bg-white border-bottom p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="substitute-search-input" class="form-label fw-bold text-dark mb-0 fs-6">
                                            <i class="fas fa-search text-primary me-1"></i> Cari & Pilih Produk Pengganti
                                        </label>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1" style="font-size: 0.72rem;">
                                            <i class="fas fa-barcode me-1"></i>Mendukung Barcode Scanner
                                        </span>
                                    </div>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-secondary border-opacity-25 text-muted">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control border-secondary border-opacity-25 fs-6" id="substitute-search-input"
                                            placeholder="Ketik nama produk, SKU (contoh: LPJ-L), atau langsung tembak barcode fisik..." autocomplete="off">
                                        <button class="btn btn-outline-secondary border-secondary border-opacity-25 d-none" type="button" id="btn-clear-sub-search">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="form-text text-muted small mt-1 d-flex justify-content-between">
                                        <span><i class="fas fa-lightbulb text-warning me-1"></i>Ketik nama/SKU atau scan barcode produk fisik dengan alat scanner.</span>
                                        <span class="text-secondary font-monospace">Tekan Enter = Pilih item pertama</span>
                                    </div>
                                </div>

                                <div class="card-body p-3 d-flex flex-column flex-grow-1 overflow-hidden">
                                    <!-- Banner Produk Terpilih (Muncul jika sudah memilih produk) -->
                                    <div id="substitute-selected-product" class="p-3 mb-3 bg-success bg-opacity-10 border border-success border-2 rounded-3 d-none flex-shrink-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-3">
                                                <img id="substitute-new-img" src="/images/placeholder.png" class="rounded-3 border bg-white flex-shrink-0" style="width: 58px; height: 58px; object-fit: cover;">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>PRODUK PENGGANTI TERPILIH</span>
                                                        <span class="badge bg-light text-dark border font-monospace" id="substitute-new-sku-badge">SKU: -</span>
                                                    </div>
                                                    <div class="fw-bold text-dark fs-6" id="substitute-new-name">-</div>
                                                    <div class="small text-muted font-monospace mt-0.5">
                                                        SKU: <strong class="text-dark" id="substitute-new-sku">-</strong> | Sisa Stok Fisik: <span id="substitute-new-stock" class="fw-bold text-success">0</span> pcs
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-3 py-1.5 rounded-pill" id="btn-cancel-selected-product">
                                                <i class="fas fa-times me-1"></i> Batal Pilih / Ganti Produk
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Area Hasil Pencarian Live -->
                                    <div id="substitute-search-container" class="flex-grow-1 overflow-y-auto pe-1">
                                        <div id="substitute-search-results" class="d-flex flex-column gap-2">
                                            <!-- Dynamic result items -->
                                        </div>
                                        <div id="substitute-search-placeholder" class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-5 text-muted">
                                            <div class="bg-light border rounded-circle p-4 mb-3 text-secondary" style="width: 76px; height: 76px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-barcode fs-2 opacity-50"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Cari Produk atau Scan Barcode</h6>
                                            <p class="small text-muted mb-0" style="max-width: 360px;">
                                                Ketik nama produk / varian, SKU pengganti, atau tembakkan scanner ke barcode produk pengganti.
                                            </p>
                                        </div>
                                    </div>

                                    <input type="hidden" id="substitute-selected-product-id">
                                    <input type="hidden" id="substitute-target-item-id">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer (Sticky) -->
                <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small" id="substitute-footer-summary">
                            Silakan pilih produk pengganti di kolom sebelah kanan, lalu klik tombol Konfirmasi.
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Batal
                        </button>
                        <button type="button" class="btn btn-primary px-4 py-2 fw-semibold" id="btn-confirm-substitute" disabled>
                            <i class="fas fa-check-circle me-1"></i> Konfirmasi Tukar Produk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const invoiceInput = document.getElementById('invoice-scan-input');
            const skuInput = document.getElementById('sku-scan-input');
            const emptyState = document.getElementById('empty-state');
            const orderCard = document.getElementById('order-details-card');
            const productScanSection = document.getElementById('product-scan-section');
            const itemsList = document.getElementById('items-list-container');
            const btnReset = document.getElementById('btn-reset');
            const btnSubmit = document.getElementById('btn-submit-verification');
            const autoShipToggle = document.getElementById('auto-ship-toggle');

            let activeOrder = null;
            let scanCounts = {}; // order_item_id -> count of scans

            // Web Audio API Synthesis
            let audioCtx = null;

            function initAudio() {
                if (!audioCtx) {
                    audioCtx = new(window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
            }

            function playBeep(freq, type, duration, volume = 0.1) {
                try {
                    initAudio();
                    const osc = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();
                    osc.type = type;
                    osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                    gainNode.gain.setValueAtTime(volume, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
                    osc.connect(gainNode);
                    gainNode.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + duration);
                } catch (e) {
                    console.error("Audio error:", e);
                }
            }

            function playSuccess() {
                playBeep(880, 'sine', 0.08, 0.15); // A5
                setTimeout(() => playBeep(1200, 'sine', 0.12, 0.15), 80); // D6
            }

            function playError() {
                playBeep(180, 'sawtooth', 0.35, 0.2); // buzzing low sound
            }

            function playComplete() {
                playBeep(523.25, 'sine', 0.08, 0.15); // C5
                setTimeout(() => playBeep(659.25, 'sine', 0.08, 0.15), 100); // E5
                setTimeout(() => playBeep(783.99, 'sine', 0.08, 0.15), 200); // G5
                setTimeout(() => playBeep(1046.50, 'sine', 0.2, 0.15), 300); // C6
            }

            // 1. Scan Invoice Nomor / ID
            function handleInvoiceSubmit(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = invoiceInput.value.trim();
                    if (val) {
                        loadOrder(val);
                    }
                }
            }
            invoiceInput.addEventListener('keydown', handleInvoiceSubmit);
            invoiceInput.addEventListener('keypress', handleInvoiceSubmit);

            // 2. Scan SKU / Barcode Barang
            function handleSkuSubmit(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = skuInput.value.trim();
                    if (val) {
                        processSkuScan(val);
                    }
                }
            }
            skuInput.addEventListener('keydown', handleSkuSubmit);
            skuInput.addEventListener('keypress', handleSkuSubmit);

            btnReset.addEventListener('click', resetAll);
            btnSubmit.addEventListener('click', submitFulfillment);

            function loadOrder(invoiceNumber) {
                resetAll(false); // Clear previous loaded state but do not clear input
                initAudio();

                fetch(`/fulfillment/order/${encodeURIComponent(invoiceNumber)}`)
                    .then(res => {
                        if (!res.ok) {
                            return res.json().then(err => {
                                throw new Error(err.message || "Gagal memuat pesanan.")
                            });
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.success) {
                            activeOrder = data.order;
                            displayOrder(activeOrder);
                            playSuccess();
                        }
                    })
                    .catch(err => {
                        playError();
                        alert(err.message || "Koneksi bermasalah atau pesanan tidak ditemukan.");
                        invoiceInput.value = '';
                        invoiceInput.focus();
                    });
            }

            function displayOrder(order) {
                // Hide empty state and show order details
                emptyState.classList.add('d-none');
                orderCard.classList.remove('d-none');
                productScanSection.classList.remove('d-none');

                // Populate metadata
                const resiBadge = order.tracking_number ? ` <span class="badge bg-secondary ms-2 fw-normal" style="font-size: 0.8rem;"><i class="fas fa-shipping-fast me-1"></i>Resi: ${order.tracking_number}</span>` : '';
                document.getElementById('order-invoice-title').innerHTML = "Invoice: " + order.invoice_number + resiBadge;
                document.getElementById('order-store-name').innerText = order.store_name;
                document.getElementById('order-buyer-name').innerText = order.buyer_name;
                document.getElementById('order-courier-name').innerText = order.courier + (order.tracking_number ? ` (${order.tracking_number})` : '');

                const badge = document.getElementById('order-channel-badge');
                badge.innerText = order.channel_name.toUpperCase();
                badge.className = `badge ms-2 text-dark border bg-light channel-${order.channel_code}`;

                const packingBadge = document.getElementById('order-packing-status-badge');
                packingBadge.innerText = order.packing_status === 'verified' ? 'Selesai Scan' : 'Sedang Dikemas';
                packingBadge.className =
                    `badge ${order.packing_status === 'verified' ? 'bg-success' : 'bg-warning text-dark'}`;


                // Populate items list
                scanCounts = {};
                order.items.forEach(item => {
                    scanCounts[item.id] = 0;
                });
                renderItemsList(order.items, order);

                // Enable SKU scanner input
                skuInput.disabled = false;
                skuInput.value = '';
                skuInput.focus();

                checkVerificationProgress();
            }

            function updateCompletionProgress() {
                if (!activeOrder || !activeOrder.items) return;
                let completedCount = 0;
                activeOrder.items.forEach(it => {
                    if ((scanCounts[it.id] || 0) >= it.quantity) {
                        completedCount++;
                    }
                });
                const totalCount = activeOrder.items.length;
                const verifiedCountEl = document.getElementById('items-verified-count');
                const totalCountEl = document.getElementById('items-total-count');
                const badgeEl = document.getElementById('items-completion-badge');

                if (verifiedCountEl && totalCountEl) {
                    verifiedCountEl.innerText = completedCount;
                    totalCountEl.innerText = totalCount;
                }

                if (badgeEl) {
                    if (completedCount === totalCount && totalCount > 0) {
                        badgeEl.className = 'badge rounded-pill bg-success text-white px-3 py-1.5 font-monospace fw-semibold';
                        badgeEl.innerHTML = `<i class="fas fa-check-double me-1"></i>Semua Lengkap (${completedCount}/${totalCount})`;
                    } else {
                        badgeEl.className = 'badge rounded-pill bg-light text-secondary border px-3 py-1.5 font-monospace fw-semibold';
                        badgeEl.innerHTML = `<span id="items-verified-count">${completedCount}</span> / <span id="items-total-count">${totalCount}</span> Lengkap`;
                    }
                }
            }

            function renderItemsList(items, order) {
                itemsList.innerHTML = '';
                items.forEach(item => {
                    if (typeof scanCounts[item.id] === 'undefined') {
                        scanCounts[item.id] = 0;
                    }

                    const itemRow = document.createElement('div');
                    itemRow.id = `item-row-${item.id}`;
                    itemRow.className = 'card item-verification-card border rounded-3 position-relative bg-white mb-3 shadow-2xs';

                    const imageHtml = item.image ?
                        `<img src="${item.image}" alt="${escapeHtml(item.name)}" class="rounded-3 border flex-shrink-0" style="width: 64px; height: 64px; object-fit: cover;">` :
                        `<div class="rounded-3 border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 64px; height: 64px;"><i class="fas fa-image fs-4 opacity-50"></i></div>`;

                    const barcodePill = item.barcode ?
                        `<span class="badge bg-light text-secondary border font-monospace py-1 px-2" style="font-size: 0.72rem;" title="Barcode Produk">
                            <i class="fas fa-barcode me-1 text-muted"></i>${escapeHtml(item.barcode)}
                         </span>` : '';

                    const substituteBadge = item.is_substituted ?
                        `<span class="badge bg-warning bg-opacity-20 text-dark border border-warning py-1 px-2.5" style="font-size: 0.75rem;" title="Alasan: ${escapeHtml(item.substitution_note || '')}">
                            <i class="fas fa-arrow-left-right text-warning me-1"></i>Diganti dari: <strong>${escapeHtml(item.original_sku || '-')}</strong>
                         </span>` : '';

                    const canSubstitute = order.packing_status !== 'verified' && !String(item.id).includes('-');
                    const substituteBtn = canSubstitute ?
                        `<button type="button" class="btn btn-sm btn-outline-warning text-dark border-warning bg-white rounded-pill px-3 py-1 shadow-2xs btn-open-substitute fw-semibold" 
                            data-id="${item.id}" 
                            data-sku="${escapeHtml(item.sku || '')}" 
                            data-name="${escapeHtml(item.name || '')}" 
                            data-qty="${item.quantity}"
                            data-image="${escapeHtml(item.image || '')}">
                            <i class="fas fa-exchange-alt text-warning me-1.5"></i>Tukar / Ganti Varian
                         </button>` : '';

                    itemRow.innerHTML = `
                        <!-- Left Status Stripe -->
                        <div id="stripe-${item.id}" class="position-absolute top-0 bottom-0 start-0" style="width: 5px; background: #cbd5e1; transition: background 0.25s ease;"></div>
                        
                        <!-- Top Row: Thumbnail + Product Details + Counter Box -->
                        <div class="p-3 d-flex align-items-center gap-3 ps-3">
                            ${imageHtml}
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-dark mb-1 text-wrap" style="font-size: 0.95rem; line-height: 1.35;" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
                                <div class="d-flex align-items-center flex-wrap gap-1.5">
                                    <span class="badge bg-light text-dark border font-monospace py-1 px-2.5" style="font-size: 0.75rem;">
                                        <i class="fas fa-tag me-1 text-muted"></i>SKU: <strong>${escapeHtml(item.sku || '-')}</strong>
                                    </span>
                                    ${barcodePill}
                                    ${substituteBadge}
                                </div>
                            </div>
                            
                            <!-- Quantitative Counter Box -->
                            <div class="item-qty-box text-center px-3 py-2 rounded-3 border flex-shrink-0" id="qty-box-${item.id}" style="min-width: 110px; background: #f8fafc; transition: all 0.25s ease;">
                                <div class="d-flex align-items-baseline justify-content-center">
                                    <span class="fs-3 fw-bold font-monospace text-secondary" id="scan-qty-${item.id}">0</span>
                                    <span class="fs-6 fw-semibold text-muted ms-1"> / ${item.quantity}</span>
                                </div>
                                <div class="small fw-semibold mt-0.5 text-uppercase" id="scan-badge-${item.id}" style="font-size: 0.65rem; letter-spacing: 0.5px; color: #64748b;">
                                    <i class="fas fa-barcode me-1"></i>Scan SKU
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Action & Progress Toolbar -->
                        <div class="border-top bg-light bg-opacity-50 px-3 py-2 d-flex justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 320px;">
                                <div class="progress flex-grow-1 rounded-pill bg-secondary bg-opacity-15" style="height: 7px;">
                                    <div class="progress-bar rounded-pill bg-primary" id="progress-bar-${item.id}" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
                                </div>
                                <span class="font-monospace text-muted fw-bold flex-shrink-0" id="progress-text-${item.id}" style="font-size: 0.72rem;">0%</span>
                            </div>
                            <div class="flex-shrink-0">
                                ${substituteBtn}
                            </div>
                        </div>
                    `;
                    itemsList.appendChild(itemRow);
                    updateItemUI(item);
                });
                updateCompletionProgress();
            }

            function processSkuScan(barcode) {
                if (!activeOrder) return;

                let matchedItem = null;
                const cleanBarcode = barcode.trim().toLowerCase();

                // 1. Cari item dengan SKU atau Barcode yang cocok (utamakan yang belum selesai di-scan)
                for (let i = 0; i < activeOrder.items.length; i++) {
                    const item = activeOrder.items[i];
                    const skuMatch = item.sku && item.sku.trim().toLowerCase() === cleanBarcode;
                    const barcodeMatch = item.barcode && item.barcode.trim().toLowerCase() === cleanBarcode;
                    if (skuMatch || barcodeMatch) {
                        matchedItem = item;
                        if (scanCounts[item.id] < item.quantity) {
                            break; // Stop pada item pertama yang belum lengkap scan-nya
                        }
                    }
                }

                // 2. Fallback: Jika operator scan SKU lama dari item yang sudah disubstitusi
                if (!matchedItem) {
                    for (let i = 0; i < activeOrder.items.length; i++) {
                        const item = activeOrder.items[i];
                        if (item.original_sku && item.original_sku.trim().toLowerCase() === cleanBarcode) {
                            playError();
                            alert(`PERHATIAN: Barang '${item.original_sku}' sudah disubstitusi/diganti ke '${item.sku}'!\nSilakan scan barcode produk pengganti: ${item.sku}`);
                            skuInput.value = '';
                            skuInput.focus();
                            return;
                        }
                    }
                }

                // 3. Fallback: Jika barcode adalah suffix ukuran (misal SKU 'LPJ-M' di-scan 'm')
                if (!matchedItem) {
                    for (let i = 0; i < activeOrder.items.length; i++) {
                        const item = activeOrder.items[i];
                        const s = (item.sku || '').trim().toLowerCase();
                        if (s.endsWith('-' + cleanBarcode) || s.endsWith('_' + cleanBarcode) || s.endsWith(' ' + cleanBarcode)) {
                            matchedItem = item;
                            if (scanCounts[item.id] < item.quantity) {
                                break;
                            }
                        }
                    }
                }

                if (matchedItem) {
                    const itemId = matchedItem.id;
                    // Cek apakah item sudah penuh dipindai
                    if (scanCounts[itemId] < matchedItem.quantity) {
                        scanCounts[itemId]++;

                        // Mainkan bunyi bip sukses
                        playSuccess();

                        // Update UI
                        updateItemUI(matchedItem);

                        // Beri efek pulse & highlight hijau lembut
                        const row = document.getElementById(`item-row-${itemId}`);
                        if (row) {
                            row.classList.add('is-scanned');
                            setTimeout(() => row.classList.remove('is-scanned'), 400);
                        }

                        // Fokus kembali
                        skuInput.value = '';
                        skuInput.focus();

                        checkVerificationProgress();
                    } else {
                        // Item sudah melebihi jumlah pesanan
                        playError();
                        alert(`Item "${matchedItem.name}" sudah lengkap! Tidak perlu memindai lagi.`);
                        skuInput.value = '';
                        skuInput.focus();
                    }
                } else {
                    // SKU tidak cocok sama sekali
                    playError();
                    skuInput.style.background = 'rgba(220, 53, 69, 0.15)';
                    skuInput.style.borderColor = '#dc3545';
                    setTimeout(() => {
                        skuInput.style.background = '#ffffff';
                        skuInput.style.borderColor = '#dee2e6';
                    }, 500);
                    alert(`Barcode/SKU "${barcode}" tidak ditemukan dalam pesanan ini!`);
                    skuInput.value = '';
                    skuInput.focus();
                }
            }

            function updateItemUI(item) {
                const current = scanCounts[item.id];
                const target = item.quantity;
                const percentage = Math.round(Math.min(100, (current / target) * 100));

                const textQty = document.getElementById(`scan-qty-${item.id}`);
                const bar = document.getElementById(`progress-bar-${item.id}`);
                const textProgress = document.getElementById(`progress-text-${item.id}`);
                const row = document.getElementById(`item-row-${item.id}`);
                const stripe = document.getElementById(`stripe-${item.id}`);
                const qtyBox = document.getElementById(`qty-box-${item.id}`);
                const badge = document.getElementById(`scan-badge-${item.id}`);

                if (textQty) textQty.innerText = current;
                if (textProgress) textProgress.innerText = `${percentage}%`;

                if (current >= target) {
                    // Lengkap / Completed
                    if (bar) {
                        bar.style.width = '100%';
                        bar.className = 'progress-bar rounded-pill bg-success';
                    }
                    if (row) {
                        row.style.borderColor = '#86efac';
                        row.style.background = '#ffffff';
                    }
                    if (stripe) stripe.style.background = '#10b981';
                    if (qtyBox) {
                        qtyBox.style.background = '#ecfdf5';
                        qtyBox.style.borderColor = '#a7f3d0';
                    }
                    if (textQty) {
                        textQty.className = 'fs-3 fw-bold font-monospace text-success';
                    }
                    if (badge) {
                        badge.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>LENGKAP</span>';
                    }
                    if (textProgress) {
                        textProgress.className = 'font-monospace text-success fw-bold flex-shrink-0';
                    }
                } else if (current > 0) {
                    // In Progress
                    if (bar) {
                        bar.style.width = `${percentage}%`;
                        bar.className = 'progress-bar rounded-pill bg-primary';
                    }
                    if (row) {
                        row.style.borderColor = '#93c5fd';
                        row.style.background = '#ffffff';
                    }
                    if (stripe) stripe.style.background = '#3b82f6';
                    if (qtyBox) {
                        qtyBox.style.background = '#eff6ff';
                        qtyBox.style.borderColor = '#bfdbfe';
                    }
                    if (textQty) {
                        textQty.className = 'fs-3 fw-bold font-monospace text-primary';
                    }
                    if (badge) {
                        badge.innerHTML = `<span class="text-primary fw-bold"><i class="fas fa-spinner fa-spin me-1"></i>${current}/${target}</span>`;
                    }
                    if (textProgress) {
                        textProgress.className = 'font-monospace text-primary fw-bold flex-shrink-0';
                    }
                } else {
                    // Pending
                    if (bar) {
                        bar.style.width = '0%';
                        bar.className = 'progress-bar rounded-pill bg-primary';
                    }
                    if (row) {
                        row.style.borderColor = '#e2e8f0';
                        row.style.background = '#ffffff';
                    }
                    if (stripe) stripe.style.background = '#cbd5e1';
                    if (qtyBox) {
                        qtyBox.style.background = '#f8fafc';
                        qtyBox.style.borderColor = '#e2e8f0';
                    }
                    if (textQty) {
                        textQty.className = 'fs-3 fw-bold font-monospace text-secondary';
                    }
                    if (badge) {
                        badge.innerHTML = '<span class="text-secondary"><i class="fas fa-barcode me-1"></i>Scan SKU</span>';
                    }
                    if (textProgress) {
                        textProgress.className = 'font-monospace text-muted fw-bold flex-shrink-0';
                    }
                }
                updateCompletionProgress();
            }

            function checkVerificationProgress() {
                if (!activeOrder) return;

                let allVerified = true;
                activeOrder.items.forEach(item => {
                    if (scanCounts[item.id] < item.quantity) {
                        allVerified = false;
                    }
                });

                if (allVerified) {
                    // Semua barang terverifikasi!
                    playComplete();
                    btnSubmit.classList.remove('d-none');
                    skuInput.disabled = true;

                    // Jika checkbox Auto-Ship aktif, kirim submit otomatis
                    if (autoShipToggle.checked) {
                        setTimeout(() => {
                            submitFulfillment();
                        }, 800);
                    }
                } else {
                    btnSubmit.classList.add('d-none');
                }
            }

            function submitFulfillment() {
                if (!activeOrder) return;

                const autoShip = autoShipToggle.checked ? 1 : 0;

                // Tampilkan loading state
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses...`;

                fetch(`/fulfillment/order/${activeOrder.id}/complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            auto_ship: autoShip
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Suksess
                            alert(data.message);

                            resetAll(true); // Reset all and focus to invoice
                        } else {
                            playError();
                            alert(data.message || "Gagal menyimpan hasil verifikasi.");
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = `<i class="fas fa-check-circle"></i> Konfirmasi Kemas`;
                        }
                    })
                    .catch(err => {
                        playError();
                        console.error(err);
                        alert("Terjadi kesalahan jaringan atau server.");
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = `<i class="fas fa-check-circle"></i> Konfirmasi Kemas`;
                    });
            }

            function resetAll(clearInvoiceInput = true) {
                activeOrder = null;
                scanCounts = {};

                emptyState.classList.remove('d-none');
                orderCard.classList.add('d-none');
                productScanSection.classList.add('d-none');
                skuInput.disabled = true;
                skuInput.value = '';

                btnSubmit.classList.add('d-none');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<i class="fas fa-check-circle"></i> Konfirmasi Kemas`;

                if (clearInvoiceInput) {
                    invoiceInput.value = '';
                    invoiceInput.focus();
                }
            }

            // Delegasi Event Klik Tukar SKU
            itemsList.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-open-substitute');
                if (btn) {
                    const id = btn.dataset.id;
                    const sku = btn.dataset.sku;
                    const name = btn.dataset.name;
                    const qty = btn.dataset.qty;
                    const image = btn.dataset.image;
                    openSubstituteModal(id, sku, name, qty, image);
                }
            });

            // Modal & Elemen Substitusi Fullscreen
            const modalEl = document.getElementById('modalSubstituteItem');
            const bsSubstituteModal = new bootstrap.Modal(modalEl);
            const subModalOrderSubtitle = document.getElementById('substitute-modal-order-subtitle');
            const subOldImg = document.getElementById('substitute-old-img');
            const subOldName = document.getElementById('substitute-old-name');
            const subOldSku = document.getElementById('substitute-old-sku');
            const subOldQty = document.getElementById('substitute-old-qty');
            const subSearchInput = document.getElementById('substitute-search-input');
            const btnClearSubSearch = document.getElementById('btn-clear-sub-search');
            const subSearchResults = document.getElementById('substitute-search-results');
            const subSearchPlaceholder = document.getElementById('substitute-search-placeholder');
            const subSelectedProduct = document.getElementById('substitute-selected-product');
            const subNewImg = document.getElementById('substitute-new-img');
            const subNewName = document.getElementById('substitute-new-name');
            const subNewSku = document.getElementById('substitute-new-sku');
            const subNewSkuBadge = document.getElementById('substitute-new-sku-badge');
            const subNewStock = document.getElementById('substitute-new-stock');
            const subTargetItemId = document.getElementById('substitute-target-item-id');
            const subSelectedProductId = document.getElementById('substitute-selected-product-id');
            const btnCancelSelected = document.getElementById('btn-cancel-selected-product');
            const subFooterSummary = document.getElementById('substitute-footer-summary');
            const subReasonSelect = document.getElementById('substitute-reason-select');
            const subReasonCustom = document.getElementById('substitute-reason-custom');
            const btnConfirmSubstitute = document.getElementById('btn-confirm-substitute');

            function openSubstituteModal(id, sku, name, qty, image) {
                subTargetItemId.value = id;
                subOldName.innerText = name;
                subOldSku.innerText = sku;
                subOldQty.innerText = qty;
                if (subOldImg) {
                    subOldImg.src = image || '/images/placeholder.png';
                }
                if (subModalOrderSubtitle && activeOrder) {
                    subModalOrderSubtitle.innerHTML = `Pesanan: <strong>#${escapeHtml(activeOrder.invoice_number)}</strong> &bull; Pembeli: <strong>${escapeHtml(activeOrder.buyer_name)}</strong> &bull; Toko: <strong>${escapeHtml(activeOrder.store_name)}</strong>`;
                }

                // Reset modal state
                subSearchInput.value = '';
                btnClearSubSearch.classList.add('d-none');
                subSearchResults.innerHTML = '';
                subSearchPlaceholder.classList.remove('d-none');
                subSelectedProduct.classList.add('d-none');
                subSelectedProductId.value = '';
                subReasonSelect.value = 'Persetujuan Chat Pembeli (Stok Asli Habis)';
                subReasonCustom.classList.add('d-none');
                subReasonCustom.value = '';
                subFooterSummary.innerHTML = 'Silakan cari & pilih produk pengganti di kolom sebelah kanan.';
                btnConfirmSubstitute.disabled = true;

                bsSubstituteModal.show();
                setTimeout(() => {
                    subSearchInput.focus();
                }, 150);
            }

            modalEl.addEventListener('shown.bs.modal', function() {
                subSearchInput.focus();
                subSearchInput.select();
            });

            // Pastikan jika operator scan barcode saat modal terbuka, teks masuk ke subSearchInput
            modalEl.addEventListener('keydown', function(e) {
                const active = document.activeElement;
                if (active !== subSearchInput && active !== subReasonCustom && active !== subReasonSelect) {
                    if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                        subSearchInput.focus();
                    }
                }
            });

            let searchTimeout = null;
            function executeSubstituteSearch(query, isScan = false) {
                clearTimeout(searchTimeout);
                const q = (query || '').trim();
                if (!q) {
                    btnClearSubSearch.classList.add('d-none');
                    subSearchResults.innerHTML = '';
                    subSearchPlaceholder.classList.remove('d-none');
                    return;
                }

                btnClearSubSearch.classList.remove('d-none');
                subSearchPlaceholder.classList.add('d-none');
                subSearchResults.innerHTML = `
                    <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 text-center py-4 px-3 text-primary">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div class="fw-semibold">Mencari produk "${escapeHtml(q)}"...</div>
                    </div>
                `;

                fetch(`/fulfillment/products/search?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        subSearchResults.innerHTML = '';
                        if (!data || data.length === 0) {
                            playError();
                            subSearchResults.innerHTML = `
                                <div class="card border border-danger border-opacity-25 bg-danger bg-opacity-10 text-center py-4 px-3">
                                    <i class="fas fa-exclamation-triangle text-danger fs-3 mb-2"></i>
                                    <div class="fw-bold text-dark">Tidak ditemukan produk dengan kata kunci / barcode "${escapeHtml(q)}"</div>
                                    <div class="small text-muted mt-1">Pastikan barcode atau SKU sudah terdaftar di Master Produk.</div>
                                </div>
                            `;
                            return;
                        }

                        // Cek apakah ada exact match pada SKU atau Barcode
                        const cleanQ = q.toLowerCase();
                        const exactMatch = data.find(p => 
                            (p.sku && p.sku.trim().toLowerCase() === cleanQ) ||
                            (p.barcode && p.barcode.trim().toLowerCase() === cleanQ)
                        );

                        // Jika hasil scan barcode (Enter ditekan) dan ditemukan exact match atau hanya 1 produk:
                        if (isScan && (exactMatch || data.length === 1)) {
                            const chosen = exactMatch || data[0];
                            const chosenImg = chosen.image_url ? (chosen.image_url.startsWith('http') ? chosen.image_url : '/storage/' + chosen.image_url) : '/images/placeholder.png';
                            playSuccess();
                            selectSubstituteProduct(chosen, chosenImg);
                            return;
                        }

                        // Tampilkan hasil pencarian
                        data.forEach((p, idx) => {
                            const card = document.createElement('div');
                            const isFirst = idx === 0;
                            card.className = `card border ${isFirst ? 'border-primary border-2' : 'border-secondary border-opacity-25'} shadow-2xs rounded-3 p-2.5 substitute-product-item bg-white`;
                            card.setAttribute('tabindex', '0');

                            const imgUrl = p.image_url ? (p.image_url.startsWith('http') ? p.image_url : '/storage/' + p.image_url) : '/images/placeholder.png';
                            const stockBadge = p.stock > 0 ?
                                `<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fs-7 fw-semibold"><i class="fas fa-check-circle me-1"></i>Stok: ${p.stock} pcs</span>` :
                                `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fs-7 fw-semibold"><i class="fas fa-times-circle me-1"></i>Habis (0)</span>`;

                            const barcodeTag = p.barcode ?
                                `<span class="badge bg-light text-secondary border font-monospace me-1" style="font-size: 0.72rem;"><i class="fas fa-barcode me-1"></i>${escapeHtml(p.barcode)}</span>` : '';

                            card.innerHTML = `
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3 min-w-0">
                                        <img src="${imgUrl}" class="rounded-3 border flex-shrink-0" style="width: 52px; height: 52px; object-fit: cover;" alt="${escapeHtml(p.name)}">
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark text-truncate mb-1" style="font-size: 0.9rem;">${escapeHtml(p.name)}</div>
                                            <div class="d-flex align-items-center flex-wrap gap-1">
                                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.75rem;">
                                                    SKU: <strong class="text-primary">${escapeHtml(p.sku)}</strong>
                                                </span>
                                                ${barcodeTag}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                        ${stockBadge}
                                        <button type="button" class="btn btn-sm ${p.stock > 0 ? 'btn-primary' : 'btn-outline-secondary'} rounded-pill px-3 py-1 fw-semibold text-nowrap">
                                            <i class="fas fa-check me-1"></i>Pilih
                                        </button>
                                    </div>
                                </div>
                            `;

                            card.addEventListener('click', () => selectSubstituteProduct(p, imgUrl));
                            card.addEventListener('keydown', function(ev) {
                                if (ev.key === 'Enter') {
                                    ev.preventDefault();
                                    selectSubstituteProduct(p, imgUrl);
                                }
                            });
                            subSearchResults.appendChild(card);
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        playError();
                        subSearchResults.innerHTML = `
                            <div class="card border border-danger p-3 text-center text-danger small">
                                Gagal memuat pencarian produk. Silakan periksa jaringan server.
                            </div>
                        `;
                    });
            }

            subSearchInput.addEventListener('input', function() {
                const q = this.value.trim();
                clearTimeout(searchTimeout);
                if (q.length > 0) {
                    btnClearSubSearch.classList.remove('d-none');
                    searchTimeout = setTimeout(() => {
                        executeSubstituteSearch(q, false);
                    }, 250);
                } else {
                    btnClearSubSearch.classList.add('d-none');
                    subSearchResults.innerHTML = '';
                    subSearchPlaceholder.classList.remove('d-none');
                }
            });

            // Tangkap enter saat scan barcode atau ketik di input pencarian modal
            function handleSubSearchEnter(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = subSearchInput.value.trim();
                    if (q) {
                        const firstItem = subSearchResults.querySelector('.substitute-product-item');
                        if (firstItem && subSearchResults.children.length > 0 && !subSearchResults.querySelector('.spinner-border')) {
                            firstItem.click();
                        } else {
                            executeSubstituteSearch(q, true);
                        }
                    }
                }
            }
            subSearchInput.addEventListener('keydown', handleSubSearchEnter);
            subSearchInput.addEventListener('keypress', handleSubSearchEnter);

            btnClearSubSearch.addEventListener('click', function() {
                subSearchInput.value = '';
                this.classList.add('d-none');
                subSearchResults.innerHTML = '';
                subSearchPlaceholder.classList.remove('d-none');
                subSearchInput.focus();
            });

            function selectSubstituteProduct(p, imgUrl) {
                subSelectedProductId.value = p.id;
                subNewName.innerText = p.name;
                subNewSku.innerText = p.sku;
                subNewSkuBadge.innerText = 'SKU: ' + p.sku;
                subNewStock.innerText = p.stock;
                if (subNewImg) {
                    subNewImg.src = imgUrl || (p.image_url ? (p.image_url.startsWith('http') ? p.image_url : '/storage/' + p.image_url) : '/images/placeholder.png');
                }

                if (p.stock <= 0) {
                    subNewStock.className = 'text-danger fw-bold';
                } else {
                    subNewStock.className = 'text-success fw-bold';
                }

                subSelectedProduct.classList.remove('d-none');
                subSearchResults.innerHTML = '';
                subSearchPlaceholder.classList.add('d-none');
                subSearchInput.value = '';
                btnClearSubSearch.classList.add('d-none');
                btnConfirmSubstitute.disabled = false;

                const oldSkuText = subOldSku.innerText;
                subFooterSummary.innerHTML = `Akan menukar: <span class="badge bg-light text-dark border font-monospace">${escapeHtml(oldSkuText)}</span> &rarr; <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">${escapeHtml(p.sku)}</span> (Stok Gudang: ${p.stock} pcs).`;

                // Fokuskan otomatis ke tombol Konfirmasi agar operator bisa langsung tekan Enter!
                setTimeout(() => {
                    btnConfirmSubstitute.focus();
                }, 100);
            }

            btnCancelSelected.addEventListener('click', function() {
                subSelectedProductId.value = '';
                subSelectedProduct.classList.add('d-none');
                subSearchPlaceholder.classList.remove('d-none');
                btnConfirmSubstitute.disabled = true;
                subFooterSummary.innerHTML = 'Silakan cari & pilih produk pengganti di kolom sebelah kanan.';
                subSearchInput.focus();
            });

            subReasonSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    subReasonCustom.classList.remove('d-none');
                    subReasonCustom.focus();
                } else {
                    subReasonCustom.classList.add('d-none');
                }
            });

            btnConfirmSubstitute.addEventListener('click', function() {
                const itemId = subTargetItemId.value;
                const newProductId = subSelectedProductId.value;
                let reason = subReasonSelect.value;
                if (reason === 'custom') {
                    reason = subReasonCustom.value.trim() || 'Alasan Lainnya';
                }

                if (!newProductId) {
                    alert('Silakan pilih produk pengganti terlebih dahulu.');
                    return;
                }

                btnConfirmSubstitute.disabled = true;
                btnConfirmSubstitute.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>Menyimpan...`;

                fetch(`/fulfillment/order-item/${itemId}/substitute`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        new_master_product_id: newProductId,
                        reason: reason
                    })
                })
                .then(res => res.json())
                .then(data => {
                    btnConfirmSubstitute.disabled = false;
                    btnConfirmSubstitute.innerHTML = `<i class="fas fa-check-circle me-1"></i>Konfirmasi Tukar Produk`;

                    if (data.success) {
                        bsSubstituteModal.hide();
                        playSuccess();

                        // Update item di activeOrder
                        const idx = activeOrder.items.findIndex(it => String(it.id) === String(itemId));
                        if (idx !== -1) {
                            activeOrder.items[idx].sku = data.item.sku;
                            activeOrder.items[idx].barcode = data.item.barcode;
                            activeOrder.items[idx].name = data.item.name;
                            activeOrder.items[idx].image = data.item.image;
                            activeOrder.items[idx].is_substituted = true;
                            activeOrder.items[idx].original_sku = data.item.original_sku;
                            activeOrder.items[idx].original_product_name = data.item.original_product_name;
                            activeOrder.items[idx].substitution_note = data.item.substitution_note;

                            // Reset scan count untuk item ini agar bisa discan dengan barcode baru
                            scanCounts[itemId] = 0;
                        }

                        renderItemsList(activeOrder.items, activeOrder);
                        checkVerificationProgress();

                        alert(data.message + "\nSilakan scan barcode produk pengganti (" + data.item.sku + ").");

                        skuInput.value = '';
                        skuInput.focus();
                    } else {
                        playError();
                        alert(data.message || 'Gagal menukar item pesanan.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    btnConfirmSubstitute.disabled = false;
                    btnConfirmSubstitute.innerHTML = `<i class="fas fa-check-circle me-1"></i>Konfirmasi Tukar Produk`;
                    playError();
                    alert('Terjadi kesalahan jaringan atau server saat menukar item.');
                });
            });

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Auto load invoice jika dioper dari daftar pesanan
            const urlParams = new URLSearchParams(window.location.search);
            const autoInvoice = urlParams.get('invoice');
            if (autoInvoice) {
                invoiceInput.value = autoInvoice;
                loadOrder(autoInvoice);
            }
        });
    </script>
@endsection
