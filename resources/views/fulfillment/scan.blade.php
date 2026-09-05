@extends('layouts.app')
@section('title', 'Scanner Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Layar Scanner Gudang')

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

                    <h4 class="h6 fw-bold my-3 text-secondary">
                        <i class="fas fa-list"></i> Daftar Barang yang Harus Diambil & Diverifikasi
                    </h4>

                    <div id="items-list-container">
                        <!-- Items rows will be inserted here dynamically -->
                    </div>

                    <!-- Tombol Konfirmasi Manual -->
                    <div class="mt-4 border-top pt-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset">Batal / Reset</button>
                        <button type="button" class="btn btn-primary btn-sm d-none fw-semibold" id="btn-submit-verification">
                            <i class="fas fa-check-circle"></i> Konfirmasi Kemas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tukar SKU / Substitusi Produk -->
    <div class="modal fade" id="modalSubstituteItem" tabindex="-1" aria-labelledby="modalSubstituteItemLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning bg-opacity-15 py-3">
                    <h5 class="modal-title fs-6 fw-bold text-dark" id="modalSubstituteItemLabel">
                        <i class="fas fa-exchange-alt text-warning me-2"></i>Tukar / Ganti Varian Produk (Substitusi)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border d-flex align-items-center gap-3 mb-3 p-3">
                        <div class="text-warning fs-3"><i class="fas fa-box-open"></i></div>
                        <div class="min-w-0">
                            <div class="small text-muted">Produk Asli Pesanan:</div>
                            <div class="fw-bold text-dark text-truncate" id="substitute-old-name">-</div>
                            <div class="small text-secondary font-monospace">SKU: <span id="substitute-old-sku" class="fw-bold">-</span> | Qty: <span id="substitute-old-qty" class="fw-bold">1</span> pcs</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Cari Produk Pengganti (Ketik Nama / SKU / Scan Barcode):</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="substitute-search-input" placeholder="Contoh: LPJ-L atau scan barcode..." autocomplete="off">
                        </div>
                        <div id="substitute-search-results" class="list-group mt-2 border rounded shadow-sm d-none" style="max-height: 200px; overflow-y: auto;">
                            <!-- Search results populated dynamically -->
                        </div>
                        <div id="substitute-selected-product" class="p-2 mt-2 bg-success bg-opacity-10 border border-success rounded d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-success me-1">Dipilih</span>
                                    <strong class="text-dark" id="substitute-new-name">Nama Produk</strong>
                                    <div class="small text-muted font-monospace">SKU: <span id="substitute-new-sku"></span> | Stok: <strong id="substitute-new-stock" class="text-success">0</strong> pcs</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" id="btn-cancel-selected-product"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <input type="hidden" id="substitute-selected-product-id">
                        <input type="hidden" id="substitute-target-item-id">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Alasan Penukaran:</label>
                        <select class="form-select form-select-sm mb-2" id="substitute-reason-select">
                            <option value="Persetujuan Chat Pembeli (Stok Asli Habis)">Persetujuan Chat Pembeli (Stok Asli Habis)</option>
                            <option value="Persetujuan Chat Pembeli (Ganti Ukuran / Warna)">Persetujuan Chat Pembeli (Ganti Ukuran / Warna)</option>
                            <option value="Barang Rusak / Reject (Ganti Produk)">Barang Rusak / Reject (Ganti Produk)</option>
                            <option value="custom">Alasan Lainnya (Ketik Manual)...</option>
                        </select>
                        <input type="text" class="form-control form-control-sm d-none" id="substitute-reason-custom" placeholder="Tulis alasan penukaran...">
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0" style="font-size: 0.78rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        Stok produk pengganti akan otomatis dipotong, dan stok produk lama batal dipotong. Catatan penukaran otomatis tersimpan di riwayat pesanan.
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm fw-semibold" id="btn-confirm-substitute" disabled>
                        <i class="fas fa-check me-1"></i>Konfirmasi Tukar Produk
                    </button>
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
            invoiceInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = invoiceInput.value.trim();
                    if (val) {
                        loadOrder(val);
                    }
                }
            });

            // 2. Scan SKU / Barcode Barang
            skuInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = skuInput.value.trim();
                    if (val) {
                        processSkuScan(val);
                    }
                }
            });

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

            function renderItemsList(items, order) {
                itemsList.innerHTML = '';
                items.forEach(item => {
                    if (typeof scanCounts[item.id] === 'undefined') {
                        scanCounts[item.id] = 0;
                    }

                    const itemRow = document.createElement('div');
                    itemRow.id = `item-row-${item.id}`;
                    itemRow.className =
                        'd-flex align-items-center gap-3 p-3 mb-2 rounded item-verification-row bg-light border';
                    itemRow.style.transition = 'all 0.2s';

                    const imageHtml = item.image ?
                        `<img src="${item.image}" alt="${escapeHtml(item.name)}" class="rounded border" style="width: 55px; height: 55px; object-fit: cover;">` :
                        `<div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 55px; height: 55px;"><i class="fas fa-image"></i></div>`;

                    const substituteBadge = item.is_substituted ?
                        `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning ms-1" style="font-size: 0.72rem;" title="Alasan: ${escapeHtml(item.substitution_note || '')}">
                            <i class="fas fa-exchange-alt me-1 text-warning"></i>Ganti dari: <strong>${escapeHtml(item.original_sku || '-')}</strong>
                         </span>` : '';

                    const canSubstitute = order.packing_status !== 'verified' && !String(item.id).includes('-');
                    const substituteBtn = canSubstitute ?
                        `<button type="button" class="btn btn-sm btn-outline-warning text-dark py-0 px-2 rounded-pill ms-2 text-nowrap btn-open-substitute" 
                            data-id="${item.id}" 
                            data-sku="${escapeHtml(item.sku || '')}" 
                            data-name="${escapeHtml(item.name || '')}" 
                            data-qty="${item.quantity}"
                            style="font-size: 0.72rem;">
                            <i class="fas fa-exchange-alt me-1 text-warning"></i>Tukar SKU
                         </button>` : '';

                    itemRow.innerHTML = `
                    ${imageHtml}
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-dark text-truncate">${escapeHtml(item.name)}</div>
                        <div class="small text-muted mt-1 d-flex align-items-center flex-wrap gap-1">
                            <span>SKU: <strong class="text-secondary font-monospace">${escapeHtml(item.sku || '-')}</strong></span>
                            ${substituteBadge}
                            ${substituteBtn}
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-primary" id="progress-bar-${item.id}" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="text-end px-2" style="min-width: 80px;">
                        <span class="fs-4 fw-extrabold text-muted" id="scan-qty-${item.id}">${scanCounts[item.id] || 0}</span>
                        <span class="fs-6 fw-medium text-muted"> / ${item.quantity}</span>
                    </div>
                `;
                    itemsList.appendChild(itemRow);
                    updateItemUI(item);
                });
            }

            function processSkuScan(barcode) {
                if (!activeOrder) return;

                let matchedItem = null;
                const cleanBarcode = barcode.trim().toLowerCase();
                // Cari item dengan SKU atau Barcode yang cocok (utamakan yang belum selesai di-scan)
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

                if (matchedItem) {
                    const itemId = matchedItem.id;
                    // Cek apakah item sudah penuh dipindai
                    if (scanCounts[itemId] < matchedItem.quantity) {
                        scanCounts[itemId]++;

                        // Mainkan bunyi bip sukses
                        playSuccess();

                        // Update UI
                        updateItemUI(matchedItem);

                        // Beri efek highlight hijau lembut sekilas
                        const row = document.getElementById(`item-row-${itemId}`);
                        row.style.background = 'rgba(25, 135, 84, 0.1)';
                        row.style.borderColor = '#198754';
                        setTimeout(() => {
                            row.style.background = '#f8f9fa';
                            row.style.borderColor = scanCounts[itemId] === matchedItem.quantity ?
                                '#198754' : '#dee2e6';
                        }, 400);

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
                    skuInput.style.background = 'rgba(220, 53, 69, 0.2)';
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
                const percentage = (current / target) * 100;

                const textQty = document.getElementById(`scan-qty-${item.id}`);
                const bar = document.getElementById(`progress-bar-${item.id}`);
                const row = document.getElementById(`item-row-${item.id}`);

                textQty.innerText = current;

                // Warnai angka & bar sesuai progres
                if (current === target) {
                    textQty.style.color = '#198754';
                    bar.style.width = '100%';
                    bar.style.backgroundColor = '#198754';
                    row.style.borderColor = '#198754';
                } else {
                    textQty.style.color = '#0d6efd';
                    bar.style.width = `${percentage}%`;
                    bar.style.backgroundColor = '#0d6efd';
                }
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
                    openSubstituteModal(id, sku, name, qty);
                }
            });

            // Modal & Elemen Substitusi
            const modalEl = document.getElementById('modalSubstituteItem');
            const bsSubstituteModal = new bootstrap.Modal(modalEl);
            const subOldName = document.getElementById('substitute-old-name');
            const subOldSku = document.getElementById('substitute-old-sku');
            const subOldQty = document.getElementById('substitute-old-qty');
            const subSearchInput = document.getElementById('substitute-search-input');
            const subSearchResults = document.getElementById('substitute-search-results');
            const subSelectedProduct = document.getElementById('substitute-selected-product');
            const subNewName = document.getElementById('substitute-new-name');
            const subNewSku = document.getElementById('substitute-new-sku');
            const subNewStock = document.getElementById('substitute-new-stock');
            const subTargetItemId = document.getElementById('substitute-target-item-id');
            const subSelectedProductId = document.getElementById('substitute-selected-product-id');
            const btnCancelSelected = document.getElementById('btn-cancel-selected-product');
            const subReasonSelect = document.getElementById('substitute-reason-select');
            const subReasonCustom = document.getElementById('substitute-reason-custom');
            const btnConfirmSubstitute = document.getElementById('btn-confirm-substitute');

            function openSubstituteModal(id, sku, name, qty) {
                subTargetItemId.value = id;
                subOldName.innerText = name;
                subOldSku.innerText = sku;
                subOldQty.innerText = qty;

                // Reset modal state
                subSearchInput.value = '';
                subSearchResults.innerHTML = '';
                subSearchResults.classList.add('d-none');
                subSelectedProduct.classList.add('d-none');
                subSelectedProductId.value = '';
                subReasonSelect.value = 'Persetujuan Chat Pembeli (Stok Asli Habis)';
                subReasonCustom.classList.add('d-none');
                subReasonCustom.value = '';
                btnConfirmSubstitute.disabled = true;

                bsSubstituteModal.show();
                setTimeout(() => {
                    subSearchInput.focus();
                }, 400);
            }

            let searchTimeout = null;
            subSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const q = this.value.trim();
                if (q.length < 1) {
                    subSearchResults.innerHTML = '';
                    subSearchResults.classList.add('d-none');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`/fulfillment/products/search?q=${encodeURIComponent(q)}`)
                        .then(res => res.json())
                        .then(data => {
                            subSearchResults.innerHTML = '';
                            if (!data || data.length === 0) {
                                subSearchResults.innerHTML = `<div class="list-group-item text-muted small text-center py-3">Tidak ada produk cocok dengan "${escapeHtml(q)}"</div>`;
                                subSearchResults.classList.remove('d-none');
                                return;
                            }

                            data.forEach(p => {
                                const a = document.createElement('a');
                                a.href = 'javascript:void(0)';
                                a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3';
                                const stockBadge = p.stock > 0 ?
                                    `<span class="badge bg-success-subtle text-success border border-success">Stok: ${p.stock}</span>` :
                                    `<span class="badge bg-danger-subtle text-danger border border-danger">Stok: ${p.stock}</span>`;

                                a.innerHTML = `
                                    <div class="min-w-0 me-2">
                                        <div class="fw-semibold text-dark text-truncate small">${escapeHtml(p.name)}</div>
                                        <div class="font-monospace text-secondary" style="font-size: 0.75rem;">SKU: ${escapeHtml(p.sku)}</div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        ${stockBadge}
                                    </div>
                                `;
                                a.addEventListener('click', () => selectSubstituteProduct(p));
                                subSearchResults.appendChild(a);
                            });
                            subSearchResults.classList.remove('d-none');
                        })
                        .catch(err => console.error(err));
                }, 250);
            });

            // Tekan enter saat scan barcode di modal pencarian produk
            subSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstItem = subSearchResults.querySelector('.list-group-item-action');
                    if (firstItem) {
                        firstItem.click();
                    }
                }
            });

            function selectSubstituteProduct(p) {
                subSelectedProductId.value = p.id;
                subNewName.innerText = p.name;
                subNewSku.innerText = p.sku;
                subNewStock.innerText = p.stock;
                if (p.stock <= 0) {
                    subNewStock.className = 'text-danger fw-bold';
                } else {
                    subNewStock.className = 'text-success fw-bold';
                }

                subSelectedProduct.classList.remove('d-none');
                subSearchResults.classList.add('d-none');
                subSearchInput.value = '';
                btnConfirmSubstitute.disabled = false;
            }

            btnCancelSelected.addEventListener('click', function() {
                subSelectedProductId.value = '';
                subSelectedProduct.classList.add('d-none');
                btnConfirmSubstitute.disabled = true;
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
                    btnConfirmSubstitute.innerHTML = `<i class="fas fa-check me-1"></i>Konfirmasi Tukar Produk`;

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
                    btnConfirmSubstitute.innerHTML = `<i class="fas fa-check me-1"></i>Konfirmasi Tukar Produk`;
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
