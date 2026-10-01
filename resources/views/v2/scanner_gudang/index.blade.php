@extends('v2.layouts.app')

@section('title', 'Layar Scanner Gudang V2')

@push('styles')
<style>
    /* ── Scanner Gudang V2 Modern Station Theme ── */
    .scanner-hero-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 12px;
        color: #ffffff;
        padding: 20px 24px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
    }
    
    .scanner-card {
        background: #ffffff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .scanner-card.active-step {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), 0 10px 25px -5px rgba(59, 130, 246, 0.1);
    }

    .scanner-card.step-completed {
        border-color: #10b981;
        background-color: #f8fafc;
    }

    .scan-input-lg {
        font-size: 1.2rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-radius: 8px;
        border: 2px solid #cbd5e1;
        transition: all 0.2s ease;
    }

    .scan-input-lg:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        background-color: #ffffff;
    }

    .input-icon-box {
        background: #f1f5f9;
        border: 2px solid #cbd5e1;
        border-right: none;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
        padding: 0 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        font-size: 1.3rem;
    }

    .item-verify-card {
        border: 1px solid #e2e8f0;
        border-left: 6px solid #94a3b8;
        border-radius: 10px;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .item-verify-card.status-pending {
        border-left-color: #94a3b8;
    }

    .item-verify-card.status-partial {
        border-left-color: #f59e0b;
        background: #fffbeb;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.08);
    }

    .item-verify-card.status-complete {
        border-left-color: #10b981;
        background: #ecfdf5;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
    }

    .badge-channel-shopee { background: linear-gradient(135deg, #ee4d2d 0%, #ff7337 100%); color: #fff; }
    .badge-channel-tiktok { background: linear-gradient(135deg, #000000 0%, #25f4ee 100%); color: #fff; }
    .badge-channel-tokopedia { background: linear-gradient(135deg, #03ac0e 0%, #20d02b 100%); color: #fff; }
    .badge-channel-lazada { background: linear-gradient(135deg, #0f146d 0%, #1e239e 100%); color: #fff; }
    .badge-channel-general { background: #64748b; color: #fff; }

    .pulse-green {
        animation: pulse-green-glow 1.5s infinite;
    }

    @keyframes pulse-green-glow {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        70% { box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .empty-state-svg {
        width: 110px;
        height: 110px;
        opacity: 0.85;
    }

    /* Modal Styling */
    .v2-modal-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">
    <!-- Top Bar Navigation Header -->
    <div class="scanner-hero-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-30 rounded-pill px-2.5 py-1 fs-12 fw-semibold">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> TERMINAL PACKING AKTIF
                </span>
                <span class="text-slate-400 fs-12">• Gudang Utama</span>
            </div>
            <h4 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                <i class="bi bi-qr-code-scan text-primary"></i> Layar Scanner Gudang
            </h4>
            <p class="text-slate-300 fs-13 mb-0 mt-1">Verifikasi & Pengemasan Pesanan Otomatis (Pick & Pack Barcode Scanner)</p>
        </div>

        <!-- Action Control Pill Controls -->
        <div class="d-flex flex-wrap align-items-center gap-3 bg-white bg-opacity-10 p-2.5 rounded-3 border border-white border-opacity-10">
            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input" type="checkbox" id="autoShipToggle" checked style="width: 2.5em; height: 1.25em; cursor: pointer;">
                <label class="form-check-label fw-semibold text-white fs-12 mb-0" for="autoShipToggle" style="cursor: pointer;">
                    <i class="bi bi-send-fill text-success me-1"></i> Auto-Kirim API
                </label>
            </div>
            <div class="vr bg-white opacity-25" style="height: 20px;"></div>
            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input" type="checkbox" id="soundToggle" checked style="width: 2.5em; height: 1.25em; cursor: pointer;">
                <label class="form-check-label fw-semibold text-white fs-12 mb-0" for="soundToggle" style="cursor: pointer;">
                    <i class="bi bi-volume-up-fill text-info me-1"></i> Suara Scanner
                </label>
            </div>
            <div class="vr bg-white opacity-25" style="height: 20px;"></div>
            <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-1.5 fw-semibold" id="btnRefreshPage" title="Reset / Scan Baru">
                <i class="bi bi-arrow-clockwise"></i> Reset Terminal
            </button>
        </div>
    </div>

    <!-- Scanner Layout Grid -->
    <div class="row g-4">
        <!-- Left Column: Step Inputs -->
        <div class="col-lg-5">
            <!-- Step 1: Scan Invoice / Resi -->
            <div class="card scanner-card active-step mb-3 shadow-sm" id="cardStep1">
                <div class="card-header bg-light py-2.5 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">1</span>
                        <span>Scan Resi / Invoice</span>
                    </h6>
                    <span class="badge bg-secondary text-white fw-semibold fs-11" id="orderStatusBadge">MENUNGGU SCAN</span>
                </div>
                <div class="card-body p-3.5">
                    <form id="formScanOrder" autocomplete="off" onsubmit="return false;">
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-secondary fs-12 mb-1">Nomor Resi / Invoice / Marketplace Order ID</label>
                            <div class="input-group">
                                <span class="input-icon-box"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" class="form-control scan-input-lg" id="inputScanOrder" placeholder="Scan Barcode Resi / Invoice..." autofocus autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="btnClearOrderInput" title="Bersihkan Input"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="form-text text-muted fs-11 mt-1 d-flex align-items-center gap-1">
                                <i class="bi bi-info-circle text-primary"></i> Arahkan scanner ke barcode resi atau ketik nomor lalu tekan Enter.
                            </div>
                        </div>
                    </form>

                    <!-- Info Box Pesanan Terpilih -->
                    <div id="orderSummaryBox" class="d-none mt-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <span class="badge badge-channel-general px-2 py-1 me-1 fs-11" id="badgeChannel">Marketplace</span>
                                    <span class="fw-bold text-dark fs-13" id="txtStoreName">-</span>
                                </div>
                                <span class="badge bg-info text-white fw-bold fs-11" id="txtCourier">-</span>
                            </div>
                            <div class="row g-2 mb-2 fs-12">
                                <div class="col-6">
                                    <span class="text-muted d-block fs-11">No. Resi / Invoice:</span>
                                    <strong class="text-dark fs-12 text-break" id="txtInvoiceNum">-</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block fs-11">Nama Pembeli:</span>
                                    <strong class="text-dark fs-12" id="txtBuyerName">-</strong>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top fs-12">
                                <span class="text-muted fs-11">Status Packing:</span>
                                <span class="badge bg-warning text-dark fw-bold" id="txtPackingStatus">SEDANG DIKEMAS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Scan Barcode / SKU Item -->
            <div class="card scanner-card opacity-50 mb-3 shadow-sm" id="cardStep2">
                <div class="card-header bg-light py-2.5 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="fw-bold mb-0 text-secondary d-flex align-items-center gap-2" id="titleStep2">
                        <span class="badge bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">2</span>
                        <span>Scan Barcode Produk / SKU</span>
                    </h6>
                    <span class="badge bg-secondary text-white fw-bold fs-11" id="scanProgressBadge">0 / 0 SKU</span>
                </div>
                <div class="card-body p-3.5">
                    <form id="formScanItem" autocomplete="off" onsubmit="return false;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary fs-12 mb-1">Barcode Produk / Kode SKU</label>
                            <div class="input-group">
                                <span class="input-icon-box"><i class="bi bi-barcode"></i></span>
                                <input type="text" class="form-control scan-input-lg" id="inputScanItem" placeholder="Scan Barcode Produk..." disabled autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="btnClearItemInput" disabled title="Bersihkan Input"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="form-text text-muted fs-11 mt-1">Scan setiap pcs barang hingga kuantitas terpenuhi.</div>
                        </div>
                    </form>

                    <!-- Progress Bar Packing -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-12 text-secondary fw-semibold">Kemajuan Pengemasan:</span>
                            <span class="fs-12 fw-bold text-primary" id="txtProgressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" id="progressBarPacking" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Tombol Selesai Kemas -->
                    <button class="btn btn-secondary btn-lg w-100 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2 fs-14" id="btnCompletePack" disabled>
                        <i class="bi bi-check-circle-fill fs-5"></i> Selesai & Simpan Kemasan
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Verification Item List -->
        <div class="col-lg-7">
            <div class="card border shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-light py-2.5 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-primary"></i> Daftar Barang Pesanan
                    </h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 fw-bold fs-12" id="totalItemsBadge">0 Produk</span>
                </div>
                <div class="card-body p-3">
                    <!-- Modern Placeholder Visual State -->
                    <div id="emptyItemsState" class="text-center py-5 my-3">
                        <svg class="empty-state-svg mb-3 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2" stroke="#94a3b8" stroke-dasharray="4 4"/>
                            <path d="M7 8v8M10 8v8M13 8v8M16 8v8M19 8v8" stroke="#cbd5e1" stroke-width="2"/>
                            <path d="M5 12h14" stroke="#3b82f6" stroke-width="2" stroke-dasharray="2 2"/>
                        </svg>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Pesanan Di-scan</h6>
                        <p class="text-muted fs-12 mx-auto" style="max-width: 380px;">Silakan scan barcode resi atau nomor invoice di sebelah kiri untuk menampilkan daftar barang yang harus dikemas.</p>
                    </div>

                    <!-- List Item Cards Container -->
                    <div id="itemsContainer" class="d-none d-flex flex-column gap-2.5">
                        <!-- Dynamic Item Cards -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Substitusi / Tukar Produk -->
<div class="modal fade" id="modalSubstitute" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="v2-modal-header p-3 d-flex justify-content-between align-items-center">
                <h6 class="modal-title fw-bold text-white mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-repeat text-warning"></i> Tukar Produk / Substitusi Item
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="substituteOrderItemId">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <span class="text-muted fs-11 d-block mb-1">Barang Asal Dipesan:</span>
                    <strong class="text-dark fs-13 d-block" id="substituteOriginalName">-</strong>
                    <span class="badge bg-secondary fs-11 mt-1" id="substituteOriginalSku">SKU: -</span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark fs-12">Pilih Produk Pengganti</label>
                    <select class="form-select select2-substitute" id="selectSubstituteProduct" style="width: 100%;">
                        <option value="">-- Cari Nama / SKU / Barcode Produk --</option>
                    </select>
                    <div class="form-text text-muted fs-11 mt-1">Pilih produk aktif yang memiliki stok fisik di gudang.</div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark fs-12">Catatan Alasan Penukaran</label>
                    <input type="text" class="form-control" id="inputSubstituteNote" placeholder="Contoh: Stok fisik varian ini rusak / habis">
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning btn-sm fw-bold text-dark d-flex align-items-center gap-1.5" id="btnSaveSubstitute">
                    <i class="bi bi-check-lg"></i> Simpan Penukaran
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // -------------------------------------------------------------
    // Global State Variables
    // -------------------------------------------------------------
    let currentOrder = null;
    let scannedItems = {}; // { order_item_id: scanned_qty }
    let scannedSources = {}; // { order_item_id: [ { source, consignment_item_id, barcode } ] }

    // -------------------------------------------------------------
    // Web Audio Synthesizer (Zero asset dependency)
    // -------------------------------------------------------------
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioCtx;
    }

    function playBeep(freq = 880, type = 'sine', duration = 0.15) {
        if (!$('#soundToggle').is(':checked')) return;
        try {
            const ctx = getAudioContext();
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, ctx.currentTime);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + duration);
        } catch (e) {
            console.error('Audio error:', e);
        }
    }

    function playSuccessBeep() {
        playBeep(880, 'sine', 0.1);
        setTimeout(() => playBeep(1320, 'sine', 0.15), 100);
    }

    function playErrorBuzzer() {
        playBeep(220, 'sawtooth', 0.3);
        setTimeout(() => playBeep(180, 'sawtooth', 0.3), 150);
    }

    function playFanfare() {
        if (!$('#soundToggle').is(':checked')) return;
        const notes = [523.25, 659.25, 783.99, 1046.50];
        notes.forEach((freq, idx) => {
            setTimeout(() => playBeep(freq, 'triangle', 0.2), idx * 120);
        });
    }

    // -------------------------------------------------------------
    // Auto-focus Logic for Hardware Barcode Scanner
    // -------------------------------------------------------------
    function focusOrderInput() {
        $('#inputScanOrder').focus().select();
    }

    function focusItemInput() {
        $('#inputScanItem').focus().select();
    }

    $('#btnClearOrderInput').on('click', function() {
        $('#inputScanOrder').val('');
        focusOrderInput();
    });

    $('#btnClearItemInput').on('click', function() {
        $('#inputScanItem').val('');
        focusItemInput();
    });

    $('#btnRefreshPage').on('click', function() {
        resetScannerState();
    });

    // -------------------------------------------------------------
    // Step 1: Submit Scan Resi / Invoice
    // -------------------------------------------------------------
    $('#inputScanOrder').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const identifier = $(this).val().trim();
            if (identifier) {
                fetchOrderDetails(identifier);
            }
        }
    });

    function fetchOrderDetails(identifier) {
        Swal.fire({
            title: 'Memuat Pesanan...',
            text: 'Mencari resi / invoice ' + identifier,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '/v2/scanner-gudang/order/' + encodeURIComponent(identifier),
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                Swal.close();
                if (res.success && res.order) {
                    playSuccessBeep();
                    loadOrderIntoScanner(res.order);
                } else {
                    playErrorBuzzer();
                    Swal.fire('Gagal', res.message || 'Pesanan tidak ditemukan.', 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                playErrorBuzzer();
                let errMsg = 'Pesanan tidak ditemukan atau bermasalah.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                Swal.fire('Perhatian', errMsg, 'warning');
                $('#inputScanOrder').select();
            }
        });
    }

    function loadOrderIntoScanner(order) {
        currentOrder = order;
        scannedItems = {};
        scannedSources = {};

        // Update Summary Info Box
        $('#cardStep1').removeClass('active-step').addClass('step-completed');
        $('#orderStatusBadge').removeClass('bg-secondary text-white').addClass('bg-success text-white').text('PESANAN DIMUAT');
        $('#orderSummaryBox').removeClass('d-none');
        
        let chCode = (order.channel_code || 'general').toLowerCase();
        let chClass = 'badge-channel-' + chCode;
        if (!['shopee', 'tiktok', 'tokopedia', 'lazada'].includes(chCode)) {
            chClass = 'badge-channel-general';
        }

        $('#badgeChannel').attr('class', 'badge me-1 ' + chClass).text(order.channel_name || 'Marketplace');
        $('#txtStoreName').text(order.store_name || '-');
        $('#txtCourier').text(order.courier || '-');
        $('#txtInvoiceNum').text(order.invoice_number || '-');
        $('#txtBuyerName').text(order.buyer_name || '-');
        $('#txtPackingStatus').text((order.packing_status || 'packing').toUpperCase());

        // Activate Step 2
        $('#cardStep2').removeClass('opacity-50').addClass('active-step');
        $('#titleStep2').removeClass('text-secondary').addClass('text-primary');
        $('#inputScanItem').prop('disabled', false).val('');
        $('#btnClearItemInput').prop('disabled', false);

        // Render Item Cards
        renderOrderItems(order.items);
        updateProgressUI();
        focusItemInput();
    }

    // -------------------------------------------------------------
    // Render Items Verification List
    // -------------------------------------------------------------
    function renderOrderItems(items) {
        $('#emptyItemsState').addClass('d-none');
        const container = $('#itemsContainer').removeClass('d-none').empty();

        $('#totalItemsBadge').text(items.length + ' Item Produk');

        items.forEach(item => {
            scannedItems[item.id] = 0;
            scannedSources[item.id] = [];

            let subTag = '';
            if (item.is_substituted) {
                subTag = `<span class="badge bg-warning text-dark me-1"><i class="bi bi-arrow-repeat me-1"></i>Substitusi: ${item.original_product_name || item.original_sku}</span>`;
            }

            let sourceBadges = '';
            if (item.total_titipan_stock > 0) {
                sourceBadges += `<span class="badge bg-info text-white me-1"><i class="bi bi-box-seam me-1"></i>Titipan: ${item.total_titipan_stock} pcs</span>`;
            }
            sourceBadges += `<span class="badge bg-secondary"><i class="bi bi-building me-1"></i>Gudang: ${item.gudang_stock} pcs</span>`;

            let imgUrl = item.image || '/assets/img/products/product1.jpg';

            let cardHtml = `
                <div class="item-verify-card status-pending p-3 shadow-sm" id="itemCard_${item.id}" data-item-id="${item.id}" data-sku="${item.sku}" data-barcode="${item.barcode || ''}">
                    <div class="d-flex align-items-center gap-3">
                        <img src="${imgUrl}" alt="${item.name}" class="rounded-2 border" style="width: 52px; height: 52px; object-fit: cover;" onerror="this.src='/assets/img/products/product1.jpg'">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    ${subTag}
                                    <h6 class="fw-bold text-dark mb-1 fs-13">${item.name}</h6>
                                    <div class="d-flex flex-wrap align-items-center gap-1.5">
                                        <span class="badge bg-dark fs-11">SKU: ${item.sku}</span>
                                        ${item.barcode ? `<span class="badge bg-light text-dark border fs-11"><i class="bi bi-barcode me-1"></i>${item.barcode}</span>` : ''}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary rounded-pill px-3 py-1.5 fs-12 fw-bold" id="qtyBadge_${item.id}">
                                        <span id="scannedQty_${item.id}">0</span> / ${item.quantity} pcs
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                <div class="fs-11">
                                    ${sourceBadges}
                                </div>
                                <div>
                                    <button class="btn btn-xs btn-outline-warning btn-substitute-item d-flex align-items-center gap-1 py-1 px-2 fs-11 fw-semibold" data-item-id="${item.id}" data-name="${item.name}" data-sku="${item.sku}">
                                        <i class="bi bi-arrow-repeat"></i> Tukar Produk
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(cardHtml);
        });
    }

    // -------------------------------------------------------------
    // Step 2: Scan Barcode SKU Item
    // -------------------------------------------------------------
    $('#inputScanItem').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const scannedCode = $(this).val().trim();
            if (scannedCode) {
                processItemScan(scannedCode);
            }
        }
    });

    function processItemScan(scannedCode) {
        if (!currentOrder || !currentOrder.items) return;

        const cleanCode = scannedCode.toLowerCase();
        let matchedItem = null;

        // Matching logic: check SKU or Barcode exactly or partially
        for (let item of currentOrder.items) {
            let itemSku = (item.sku || '').toLowerCase();
            let itemBarcode = (item.barcode || '').toLowerCase();

            if (itemSku === cleanCode || itemBarcode === cleanCode) {
                matchedItem = item;
                break;
            }
        }

        // Fallback matching
        if (!matchedItem) {
            for (let item of currentOrder.items) {
                let itemSku = (item.sku || '').toLowerCase();
                let itemBarcode = (item.barcode || '').toLowerCase();

                if (cleanCode.includes(itemSku) || (itemBarcode && cleanCode.includes(itemBarcode))) {
                    matchedItem = item;
                    break;
                }
            }
        }

        if (!matchedItem) {
            playErrorBuzzer();
            Swal.fire({
                icon: 'error',
                title: 'Item Tidak Sesuai!',
                text: `Barcode / SKU '${scannedCode}' TIDAK ADA dalam daftar pesanan ini!`,
                timer: 2000,
                showConfirmButton: false
            });
            $('#inputScanItem').val('').focus();
            return;
        }

        // Check if qty max reached
        const currentScanned = scannedItems[matchedItem.id] || 0;
        if (currentScanned >= matchedItem.quantity) {
            playErrorBuzzer();
            Swal.fire({
                icon: 'warning',
                title: 'Kuantitas Sudah Terpenuhi',
                text: `Item '${matchedItem.name}' sudah lengkap di-scan (${matchedItem.quantity} pcs).`,
                timer: 1800,
                showConfirmButton: false
            });
            $('#inputScanItem').val('').focus();
            return;
        }

        // Record scan
        scannedItems[matchedItem.id] = currentScanned + 1;

        // Check source (consignment vs warehouse)
        let srcType = 'warehouse';
        let consId = null;
        if (matchedItem.active_consignments && matchedItem.active_consignments.length > 0) {
            let activeCons = matchedItem.active_consignments.find(c => c.sisa_stok > 0);
            if (activeCons) {
                srcType = 'consignment';
                consId = activeCons.consignment_item_id;
                activeCons.sisa_stok -= 1;
            }
        }

        scannedSources[matchedItem.id].push({
            source: srcType,
            consignment_item_id: consId,
            barcode: scannedCode
        });

        playSuccessBeep();
        $('#inputScanItem').val('');

        updateItemUI(matchedItem.id, scannedItems[matchedItem.id], matchedItem.quantity);
        updateProgressUI();
        focusItemInput();
    }

    function updateItemUI(itemId, scannedQty, targetQty) {
        const card = $(`#itemCard_${itemId}`);
        $(`#scannedQty_${itemId}`).text(scannedQty);

        card.removeClass('status-pending status-partial status-complete');

        if (scannedQty >= targetQty) {
            card.addClass('status-complete');
            $(`#qtyBadge_${itemId}`).removeClass('bg-secondary bg-warning').addClass('bg-success text-white');
        } else if (scannedQty > 0) {
            card.addClass('status-partial');
            $(`#qtyBadge_${itemId}`).removeClass('bg-secondary bg-success').addClass('bg-warning text-dark');
        } else {
            card.addClass('status-pending');
        }
    }

    function updateProgressUI() {
        if (!currentOrder || !currentOrder.items) return;

        let totalRequiredPcs = 0;
        let totalScannedPcs = 0;
        let totalRequiredSkus = currentOrder.items.length;
        let completedSkus = 0;

        currentOrder.items.forEach(item => {
            totalRequiredPcs += item.quantity;
            let sc = scannedItems[item.id] || 0;
            totalScannedPcs += sc;
            if (sc >= item.quantity) {
                completedSkus++;
            }
        });

        let percent = totalRequiredPcs > 0 ? Math.round((totalScannedPcs / totalRequiredPcs) * 100) : 0;
        $('#progressBarPacking').css('width', percent + '%').text(percent + '%');
        $('#txtProgressPercent').text(percent + '%');
        $('#scanProgressBadge').text(`${completedSkus} / ${totalRequiredSkus} SKU`);

        if (totalScannedPcs >= totalRequiredPcs && totalRequiredPcs > 0) {
            $('#btnCompletePack').prop('disabled', false).removeClass('btn-secondary').addClass('btn-success pulse-green');
            playFanfare();
        } else {
            $('#btnCompletePack').prop('disabled', true).removeClass('btn-success pulse-green').addClass('btn-secondary');
        }
    }

    // -------------------------------------------------------------
    // Selesai & Simpan Kemasan
    // -------------------------------------------------------------
    $('#btnCompletePack').on('click', function() {
        if (!currentOrder) return;

        const autoShip = $('#autoShipToggle').is(':checked');

        Swal.fire({
            title: 'Konfirmasi Verifikasi Kemasan',
            text: `Apakah Anda yakin ingin menyelesaikan kemasan pesanan invoice '${currentOrder.invoice_number}'?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Selesai Kemas!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                submitCompletePack(autoShip);
            }
        });
    });

    function submitCompletePack(autoShip) {
        Swal.fire({
            title: 'Menyimpan Kemasan...',
            text: 'Memproses pengurangan stok & verifikasi.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: `/v2/scanner-gudang/order/${currentOrder.id}/complete`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                auto_ship: autoShip ? 1 : 0,
                item_sources: scannedSources
            },
            dataType: 'json',
            success: function(res) {
                Swal.close();
                if (res.success) {
                    playFanfare();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Kemas!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        resetScannerState();
                    });
                } else {
                    playErrorBuzzer();
                    Swal.fire('Gagal', res.message || 'Gagal menyelesikan verifikasi kemasan.', 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                playErrorBuzzer();
                let msg = 'Terjadi kesalahan server.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Error', msg, 'error');
            }
        });
    }

    function resetScannerState() {
        currentOrder = null;
        scannedItems = {};
        scannedSources = {};

        $('#cardStep1').addClass('active-step').removeClass('step-completed');
        $('#orderStatusBadge').removeClass('bg-success text-white').addClass('bg-secondary text-white').text('MENUNGGU SCAN');
        $('#inputScanOrder').val('');
        $('#orderSummaryBox').addClass('d-none');

        $('#cardStep2').addClass('opacity-50').removeClass('active-step');
        $('#titleStep2').removeClass('text-primary').addClass('text-secondary');
        $('#inputScanItem').prop('disabled', true).val('');
        $('#btnClearItemInput').prop('disabled', true);
        $('#scanProgressBadge').text('0 / 0 SKU');
        $('#txtProgressPercent').text('0%');
        $('#progressBarPacking').css('width', '0%');
        $('#btnCompletePack').prop('disabled', true).removeClass('pulse-green').addClass('btn-secondary');

        $('#itemsContainer').empty().addClass('d-none');
        $('#emptyItemsState').removeClass('d-none');
        $('#totalItemsBadge').text('0 Produk');

        focusOrderInput();
    }

    // -------------------------------------------------------------
    // Substitusi Produk (Tukar Produk)
    // -------------------------------------------------------------
    $(document).on('click', '.btn-substitute-item', function() {
        const itemId = $(this).data('item-id');
        const name = $(this).data('name');
        const sku = $(this).data('sku');

        $('#substituteOrderItemId').val(itemId);
        $('#substituteOriginalName').text(name);
        $('#substituteOriginalSku').text('SKU: ' + sku);
        $('#inputSubstituteNote').val('');
        $('#selectSubstituteProduct').val('').trigger('change');

        $('#modalSubstitute').modal('show');
    });

    // Select2 Ajax for Product Substitution
    $('#selectSubstituteProduct').select2({
        dropdownParent: $('#modalSubstitute'),
        placeholder: '-- Cari Produk Pengganti (Nama / SKU / Barcode) --',
        ajax: {
            url: '{{ route("v2.scanner_gudang.products_search") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: $.map(data.products, function(prod) {
                        return {
                            id: prod.id,
                            text: `[${prod.sku}] ${prod.name} (Stok: ${prod.stock} ${prod.unit || 'pcs'})`
                        };
                    })
                };
            },
            cache: true
        }
    });

    $('#btnSaveSubstitute').on('click', function() {
        const itemId = $('#substituteOrderItemId').val();
        const newMasterId = $('#selectSubstituteProduct').val();
        const note = $('#inputSubstituteNote').val();

        if (!newMasterId) {
            Swal.fire('Peringatan', 'Silakan pilih produk pengganti terlebih dahulu.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Memproses Penukaran...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: `/v2/scanner-gudang/order-item/${itemId}/substitute`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                new_master_product_id: newMasterId,
                note: note
            },
            dataType: 'json',
            success: function(res) {
                Swal.close();
                if (res.success) {
                    $('#modalSubstitute').modal('hide');
                    playSuccessBeep();
                    Swal.fire('Berhasil', res.message, 'success').then(() => {
                        // Refresh order details to update items list
                        if (currentOrder) {
                            fetchOrderDetails(currentOrder.invoice_number);
                        }
                    });
                } else {
                    playErrorBuzzer();
                    Swal.fire('Gagal', res.message || 'Gagal menukar produk.', 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                playErrorBuzzer();
                let msg = 'Terjadi kesalahan.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Auto-focus order input on start
    focusOrderInput();
});
</script>
@endpush
