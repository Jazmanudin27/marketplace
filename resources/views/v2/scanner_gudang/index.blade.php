@extends('v2.layouts.app')

@section('title', 'Layar Scanner Gudang')

@push('styles')
<style>
    .scanner-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.2s ease-in-out;
    }
    .scanner-card.active-step {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    }
    .scan-input-lg {
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .item-verify-card {
        border-left: 5px solid #cbd5e1;
        transition: all 0.2s ease;
    }
    .item-verify-card.status-pending {
        border-left-color: #cbd5e1;
        background-color: #ffffff;
    }
    .item-verify-card.status-partial {
        border-left-color: #f59e0b;
        background-color: #fffbeb;
    }
    .item-verify-card.status-complete {
        border-left-color: #10b981;
        background-color: #ecfdf5;
    }
    .badge-channel-shopee { background-color: #ee4d2d; color: white; }
    .badge-channel-tiktok { background-color: #000000; color: white; }
    .badge-channel-tokopedia { background-color: #03ac0e; color: white; }
    .badge-channel-lazada { background-color: #0f146d; color: white; }
    .badge-channel-general { background-color: #64748b; color: white; }
    
    .pulse-animation {
        animation: pulse-border 1.5s infinite;
    }
    @keyframes pulse-border {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
</style>
@endpush

@section('content')
<div class="content">
    <div class="page-header">
        <div class="add-item d-flex">
            <div class="page-title">
                <h4>Layar Scanner Gudang</h4>
                <h6>Verifikasi & Kemas Pesanan (Pick & Pack Barcode Scanner)</h6>
            </div>
        </div>
        <ul class="table-top-head">
            <li>
                <div class="form-check form-switch d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="autoShipToggle" checked style="width: 2.5em; height: 1.25em; cursor: pointer;">
                    <label class="form-check-label fw-bold text-dark fs-13 mb-0" for="autoShipToggle" style="cursor: pointer;">
                        Auto-Kirim Marketplace API
                    </label>
                </div>
            </li>
            <li>
                <div class="form-check form-switch d-flex align-items-center gap-2 ms-3">
                    <input class="form-check-input" type="checkbox" id="soundToggle" checked style="width: 2.5em; height: 1.25em; cursor: pointer;">
                    <label class="form-check-label fw-bold text-dark fs-13 mb-0" for="soundToggle" style="cursor: pointer;">
                        Suara Scanner
                    </label>
                </div>
            </li>
            <li>
                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh" id="btnRefreshPage"><i data-feather="rotate-ccw" class="feather-rotate-ccw"></i></a>
            </li>
        </ul>
    </div>

    <!-- Scanner Layout Grid -->
    <div class="row">
        <!-- Left Column: Scan Action Inputs -->
        <div class="col-lg-5">
            <!-- Step 1: Scan Invoice / Resi -->
            <div class="card scanner-card active-step mb-3" id="cardStep1">
                <div class="card-header bg-soft-primary d-flex justify-content-between align-items-center py-2">
                    <h5 class="card-title mb-0 text-primary fw-bold">
                        <span class="badge bg-primary rounded-circle me-1">1</span> Scan Resi / Invoice
                    </h5>
                    <span class="badge bg-light text-dark fw-bold fs-12" id="orderStatusBadge">MENUNGGU SCAN</span>
                </div>
                <div class="card-body">
                    <form id="formScanOrder" autocomplete="off" onsubmit="return false;">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-13">Nomor Resi / Invoice / Marketplace Order ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i data-feather="search"></i></span>
                                <input type="text" class="form-control scan-input-lg border-start-0" id="inputScanOrder" placeholder="Scan Barcode Resi / Invoice..." autofocus autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="btnClearOrderInput"><i data-feather="x"></i></button>
                            </div>
                            <small class="text-muted fs-11 mt-1 d-block">Gunakan barcode scanner atau ketik nomor resi/invoice lalu tekan Enter.</small>
                        </div>
                    </form>

                    <!-- Info Box Pesanan Terpilih -->
                    <div id="orderSummaryBox" class="d-none">
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge badge-channel-general me-1" id="badgeChannel">Marketplace</span>
                                    <span class="fw-bold text-dark fs-14" id="txtStoreName">-</span>
                                </div>
                                <span class="badge bg-info text-white fw-bold" id="txtCourier">-</span>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <span class="text-muted fs-12 d-block">Invoice / Resi:</span>
                                    <strong class="text-dark fs-13" id="txtInvoiceNum">-</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted fs-12 d-block">Pembeli:</span>
                                    <strong class="text-dark fs-13" id="txtBuyerName">-</strong>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-muted fs-12">Status Packing:</span>
                                <span class="badge bg-warning text-dark fw-bold" id="txtPackingStatus">SEDANG DIKEMAS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Scan Barcode / SKU Item -->
            <div class="card scanner-card opacity-50 mb-3" id="cardStep2">
                <div class="card-header bg-soft-secondary d-flex justify-content-between align-items-center py-2">
                    <h5 class="card-title mb-0 text-secondary fw-bold" id="titleStep2">
                        <span class="badge bg-secondary rounded-circle me-1">2</span> Scan Barcode Produk / SKU
                    </h5>
                    <span class="badge bg-secondary text-white fw-bold" id="scanProgressBadge">0 / 0 SKU</span>
                </div>
                <div class="card-body">
                    <form id="formScanItem" autocomplete="off" onsubmit="return false;">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-13">Barcode Item / Kode SKU</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i data-feather="barcode"></i></span>
                                <input type="text" class="form-control scan-input-lg border-start-0" id="inputScanItem" placeholder="Scan Barcode Produk..." disabled autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="btnClearItemInput" disabled><i data-feather="x"></i></button>
                            </div>
                            <small class="text-muted fs-11 mt-1 d-block">Scan setiap pcs barang hingga jumlah kuantitas terpenuhi.</small>
                        </div>
                    </form>

                    <!-- Progress Bar Packing -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-12 text-muted fw-bold">Kemajuan Pemindaian Barang:</span>
                            <span class="fs-12 fw-bold text-primary" id="txtProgressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" id="progressBarPacking" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Tombol Selesai Kemas -->
                    <button class="btn btn-success btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2" id="btnCompletePack" disabled>
                        <i data-feather="check-circle"></i> Selesai & Simpan Kemasan
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Verification Item List -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i data-feather="package" class="me-1"></i> Daftar Barang Pesanan
                    </h5>
                    <span class="badge bg-soft-info text-info fw-bold" id="totalItemsBadge">0 Produk</span>
                </div>
                <div class="card-body p-3">
                    <!-- Placeholder saat belum scan resi -->
                    <div id="emptyItemsState" class="text-center py-5">
                        <img src="/assets/img/icons/barcode.svg" alt="Scan Barcode" style="width: 80px; opacity: 0.4;" class="mb-3">
                        <h6 class="text-muted fw-bold">Belum Ada Pesanan Di-scan</h6>
                        <p class="text-muted fs-12">Silakan scan nomor resi atau invoice pesanan di sebelah kiri untuk memuat daftar barang yang harus dikemas.</p>
                    </div>

                    <!-- List Item Cards -->
                    <div id="itemsContainer" class="d-none space-y-3">
                        <!-- Dynamic Item Cards Inserted by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Substitusi / Tukar Produk -->
<div class="modal fade" id="modalSubstitute" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-soft">
                <h5 class="modal-title text-dark fw-bold">
                    <i data-feather="repeat" class="me-1"></i> Tukar Produk / Substitusi Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="substituteOrderItemId">
                <div class="p-3 bg-light rounded mb-3 border">
                    <span class="text-muted fs-11 d-block">Barang Asal:</span>
                    <strong class="text-dark fs-13 d-block" id="substituteOriginalName">-</strong>
                    <span class="badge bg-secondary fs-11" id="substituteOriginalSku">SKU: -</span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark fs-13">Pilih Produk Pengganti</label>
                    <select class="form-select select2-substitute" id="selectSubstituteProduct" style="width: 100%;">
                        <option value="">-- Cari Nama / SKU / Barcode Produk --</option>
                    </select>
                    <small class="text-muted fs-11 mt-1 d-block">Pilih master produk aktif yang tersedia di gudang.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark fs-13">Catatan Alasan Penukaran</label>
                    <input type="text" class="form-control" id="inputSubstituteNote" placeholder="Contoh: Stok barang fisik habis / rusak">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning fw-bold text-dark" id="btnSaveSubstitute">
                    <i data-feather="check"></i> Simpan Penukaran
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    feather.replace();

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
        $('#cardStep1').removeClass('active-step pulse-animation').addClass('bg-soft-light');
        $('#orderStatusBadge').removeClass('bg-light text-dark').addClass('bg-success text-white').text('PESANAN DIMUAT');
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
        $('#cardStep2').removeClass('opacity-50').addClass('active-step pulse-animation');
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
                subTag = `<div class="badge bg-warning text-dark me-1 mb-1">Substitusi: ${item.original_product_name || item.original_sku}</div>`;
            }

            let sourceBadges = '';
            if (item.total_titipan_stock > 0) {
                sourceBadges += `<span class="badge bg-info text-white me-1">Stok Titipan: ${item.total_titipan_stock} pcs</span>`;
            }
            sourceBadges += `<span class="badge bg-secondary">Stok Gudang: ${item.gudang_stock} pcs</span>`;

            let imgUrl = item.image || '/assets/img/products/product1.jpg';

            let cardHtml = `
                <div class="card item-verify-card status-pending mb-2 shadow-sm" id="itemCard_${item.id}" data-item-id="${item.id}" data-sku="${item.sku}" data-barcode="${item.barcode || ''}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${imgUrl}" alt="${item.name}" class="rounded border" style="width: 55px; height: 55px; object-fit: cover;" onerror="this.src='/assets/img/products/product1.jpg'">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        ${subTag}
                                        <h6 class="fw-bold text-dark mb-1 fs-14">${item.name}</h6>
                                        <span class="badge bg-dark fs-11 me-2">SKU: ${item.sku}</span>
                                        ${item.barcode ? `<span class="badge bg-light text-dark border fs-11 me-2"><i class="fa fa-barcode me-1"></i>${item.barcode}</span>` : ''}
                                    </div>
                                    <div class="text-end ms-2">
                                        <span class="badge bg-secondary rounded-pill px-3 py-2 fs-13" id="qtyBadge_${item.id}">
                                            <span id="scannedQty_${item.id}">0</span> / ${item.quantity} pcs
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                    <div class="fs-11">
                                        ${sourceBadges}
                                    </div>
                                    <div>
                                        <button class="btn btn-xs btn-outline-warning btn-substitute-item" data-item-id="${item.id}" data-name="${item.name}" data-sku="${item.sku}">
                                            <i data-feather="repeat" style="width: 12px; height: 12px;"></i> Tukar Produk
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(cardHtml);
        });

        feather.replace();
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
            $('#btnCompletePack').prop('disabled', false).removeClass('btn-secondary').addClass('btn-success pulse-animation');
            playFanfare();
        } else {
            $('#btnCompletePack').prop('disabled', true).removeClass('btn-success pulse-animation').addClass('btn-secondary');
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

        $('#cardStep1').addClass('active-step').removeClass('bg-soft-light');
        $('#orderStatusBadge').removeClass('bg-success text-white').addClass('bg-light text-dark').text('MENUNGGU SCAN');
        $('#inputScanOrder').val('');
        $('#orderSummaryBox').addClass('d-none');

        $('#cardStep2').addClass('opacity-50').removeClass('active-step pulse-animation');
        $('#titleStep2').removeClass('text-primary').addClass('text-secondary');
        $('#inputScanItem').prop('disabled', true).val('');
        $('#btnClearItemInput').prop('disabled', true);
        $('#scanProgressBadge').text('0 / 0 SKU');
        $('#txtProgressPercent').text('0%');
        $('#progressBarPacking').css('width', '0%');
        $('#btnCompletePack').prop('disabled', true).removeClass('pulse-animation');

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
