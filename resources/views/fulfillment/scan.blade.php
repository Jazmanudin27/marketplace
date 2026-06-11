@extends('layouts.app')
@section('title', 'Scanner Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Layar Scanner Gudang')

@section('content')
<div class="row">
    <!-- Kolom Kiri: Form & Pemindai -->
    <div class="col-md-5">
        <div class="dashboard-card" style="margin-bottom: 1.5rem;">
            <div class="card-header-line">
                <h3><i class="fas fa-barcode"></i> Langkah 1: Scan Invoice</h3>
            </div>
            <div style="margin-top: 1rem;">
                <label for="invoice-scan-input" class="form-label fw-bold">Nomor Invoice / Kode Pesanan</label>
                <div class="input-group">
                    <span class="input-group-text" style="background: var(--bg-card2); border: 1px solid var(--border); color: var(--text-secondary);">
                        <i class="fas fa-file-invoice"></i>
                    </span>
                    <input type="text" id="invoice-scan-input" class="form-control" placeholder="Scan resi/invoice di sini..." style="background: var(--bg); color: var(--text-primary); border: 1px solid var(--border); font-size: 1.1rem; font-weight: 500;" autofocus autocomplete="off">
                </div>
                <div class="form-text text-muted" style="margin-top: 0.5rem; font-size: 0.8rem;">
                    Arahkan scanner atau ketik nomor invoice, lalu tekan <strong>Enter</strong>.
                </div>
            </div>
        </div>

        <div class="dashboard-card d-none" id="product-scan-section">
            <div class="card-header-line">
                <h3><i class="fas fa-box"></i> Langkah 2: Scan SKU Produk</h3>
            </div>
            <div style="margin-top: 1rem;">
                <label for="sku-scan-input" class="form-label fw-bold" style="color: var(--primary);">Scan Barcode / SKU Barang</label>
                <div class="input-group">
                    <span class="input-group-text" style="background: var(--bg-card2); border: 1px solid var(--border); color: var(--primary);">
                        <i class="fas fa-barcode"></i>
                    </span>
                    <input type="text" id="sku-scan-input" class="form-control" placeholder="Scan barcode SKU barang di sini..." style="background: var(--bg); color: var(--text-primary); border: 1px solid var(--border); font-size: 1.2rem; font-weight: bold; letter-spacing: 1px;" autocomplete="off" disabled>
                </div>
                
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" id="auto-ship-toggle" checked style="cursor: pointer;">
                    <label class="form-check-label fw-bold" for="auto-ship-toggle" style="cursor: pointer; color: var(--text-primary);">
                        Otomatis Request Kirim & Cetak Resi
                    </label>
                </div>
                <div class="form-text text-muted" style="font-size: 0.8rem; margin-top: 0.25rem;">
                    Saat scan produk selesai, sistem akan otomatis mengirim status siap kirim ke Shopee/TikTok dan membuka tab cetak label resi.
                </div>
            </div>
        </div>
        
        <div class="dashboard-card" style="background: rgba(108, 99, 255, 0.05); border: 1px dashed var(--primary); margin-top: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;"><i class="fas fa-keyboard"></i> Pintasan Scanner & Tips</h4>
            <ul style="font-size: 0.82rem; color: var(--text-secondary); padding-left: 1.25rem; margin-bottom: 0; line-height: 1.6;">
                <li>Pastikan kursor aktif pada input teks yang dituju (berwarna ungu menyala).</li>
                <li>Gunakan scanner yang diprogram mengirim karakter <strong>Enter (CRLF)</strong> di akhir kode.</li>
                <li>Jika produk tidak memiliki barcode, Anda bisa mengetik SKU secara manual lalu tekan Enter.</li>
            </ul>
        </div>
    </div>

    <!-- Kolom Kanan: Detail Pesanan yang Sedang Diproses -->
    <div class="col-md-7">
        <!-- Keadaan Kosong (Belum ada pesanan dimuat) -->
        <div class="dashboard-card text-center" id="empty-state" style="padding: 5rem 2rem; border: 1px dashed var(--border);">
            <i class="fas fa-truck-loading" style="font-size: 4rem; color: var(--text-muted); opacity: 0.3; margin-bottom: 1.5rem;"></i>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-secondary);">Silakan Scan Nomor Invoice</h3>
            <p class="text-muted" style="max-width: 380px; margin: 0.5rem auto 0; font-size: 0.88rem;">
                Scan kode resi pengiriman atau invoice marketplace untuk memuat detail pesanan dan memulai verifikasi produk.
            </p>
        </div>

        <!-- Detail Pesanan (Hidden by default, loaded via JS) -->
        <div class="dashboard-card d-none" id="order-details-card">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3" style="border-color: var(--border) !important;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-primary);" id="order-invoice-title">Invoice</h3>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Toko: <span class="fw-bold text-light" id="order-store-name">-</span> 
                        <span class="badge ms-2" id="order-channel-badge" style="font-size: 0.7rem; padding: 3px 6px;">-</span>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">Pembeli</div>
                    <div style="font-weight: 700; color: var(--text-primary);" id="order-buyer-name">-</div>
                </div>
            </div>

            <div class="my-3 d-flex justify-content-between align-items-center bg-dark p-3 rounded" style="background: var(--bg-card2) !important; border: 1px solid var(--border);">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Layanan Ekspedisi</div>
                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary);" id="order-courier-name">-</div>
                </div>
                <div class="text-end" id="order-status-wrapper">
                    <span class="badge bg-warning text-dark" id="order-packing-status-badge" style="padding: 6px 12px;">Sedang Dikemas</span>
                </div>
            </div>

            <h4 style="font-size: 0.95rem; font-weight: bold; margin: 1.5rem 0 0.75rem; color: var(--text-secondary);">
                <i class="fas fa-list"></i> Daftar Barang yang Harus Diambil & Diverifikasi
            </h4>

            <div id="items-list-container">
                <!-- Items rows will be inserted here dynamically -->
            </div>

            <!-- Tombol Konfirmasi Manual -->
            <div class="mt-4 border-top pt-3" style="border-color: var(--border) !important; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary btn-sm" id="btn-reset" style="background: #334155; border: none; padding: 8px 16px;">Batal / Reset</button>
                <button type="button" class="btn btn-primary btn-sm d-none" id="btn-submit-verification" style="background: var(--primary); border: none; padding: 8px 20px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> Konfirmasi Kemas
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
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
        invoiceInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = invoiceInput.value.trim();
                if (val) {
                    loadOrder(val);
                }
            }
        });

        // 2. Scan SKU / Barcode Barang
        skuInput.addEventListener('keypress', function (e) {
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
                        return res.json().then(err => { throw new Error(err.message || "Gagal memuat pesanan.") });
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
            document.getElementById('order-invoice-title').innerText = "Invoice: " + order.invoice_number;
            document.getElementById('order-store-name').innerText = order.store_name;
            document.getElementById('order-buyer-name').innerText = order.buyer_name;
            document.getElementById('order-courier-name').innerText = order.courier;

            const badge = document.getElementById('order-channel-badge');
            badge.innerText = order.channel_name.toUpperCase();
            badge.className = `badge ms-2 channel-${order.channel_code}`;

            const packingBadge = document.getElementById('order-packing-status-badge');
            packingBadge.innerText = order.packing_status === 'verified' ? 'Selesai Scan' : 'Sedang Dikemas';
            packingBadge.className = `badge ${order.packing_status === 'verified' ? 'bg-success' : 'bg-warning text-dark'}`;

            // Populate items list
            itemsList.innerHTML = '';
            scanCounts = {};

            order.items.forEach(item => {
                scanCounts[item.id] = 0;

                const itemRow = document.createElement('div');
                itemRow.id = `item-row-${item.id}`;
                itemRow.className = 'd-flex align-items-center gap-3 p-3 mb-2 rounded item-verification-row';
                itemRow.style.background = 'var(--bg-card2)';
                itemRow.style.border = '1px solid var(--border)';
                itemRow.style.transition = 'all 0.2s';

                const imageHtml = item.image 
                    ? `<img src="${item.image}" alt="${item.name}" style="width: 55px; height: 55px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);">`
                    : `<div style="width: 55px; height: 55px; background: var(--bg); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #555; border: 1px solid var(--border);"><i class="fas fa-image"></i></div>`;

                itemRow.innerHTML = `
                    ${imageHtml}
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${item.name}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; display: flex; align-items:center; gap: 8px;">
                            <span>SKU: <strong style="color: var(--text-secondary); font-family: monospace;">${item.sku || '-'}</strong></span>
                        </div>
                        <div class="progress mt-2" style="height: 6px; background: var(--bg);">
                            <div class="progress-bar" id="progress-bar-${item.id}" role="progressbar" style="width: 0%; background: var(--primary);" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="text-end px-2" style="min-width: 80px;">
                        <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-muted);" id="scan-qty-${item.id}">0</span>
                        <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);"> / ${item.quantity}</span>
                    </div>
                `;
                itemsList.appendChild(itemRow);
            });

            // Enable SKU scanner input
            skuInput.disabled = false;
            skuInput.value = '';
            skuInput.focus();

            checkVerificationProgress();
        }

        function processSkuScan(barcode) {
            if (!activeOrder) return;

            let matchedItem = null;
            // Cari item dengan SKU yang cocok
            activeOrder.items.forEach(item => {
                if (item.sku && item.sku.toLowerCase() === barcode.toLowerCase()) {
                    matchedItem = item;
                }
            });

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
                    row.style.background = 'rgba(16, 185, 129, 0.1)';
                    row.style.borderColor = 'var(--success)';
                    setTimeout(() => {
                        row.style.background = 'var(--bg-card2)';
                        row.style.borderColor = scanCounts[itemId] === matchedItem.quantity ? 'var(--success)' : 'var(--border)';
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
                const originalBackground = skuInput.style.background;
                skuInput.style.background = 'rgba(239, 68, 68, 0.2)';
                skuInput.style.borderColor = 'var(--danger)';
                setTimeout(() => {
                    skuInput.style.background = 'var(--bg)';
                    skuInput.style.borderColor = 'var(--border)';
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
                textQty.style.color = 'var(--success)';
                bar.style.width = '100%';
                bar.style.backgroundColor = 'var(--success)';
                row.style.borderColor = 'var(--success)';
            } else {
                textQty.style.color = 'var(--primary)';
                bar.style.width = `${percentage}%`;
                bar.style.backgroundColor = 'var(--primary)';
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
                body: JSON.stringify({ auto_ship: autoShip })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Suksess
                    alert(data.message);
                    
                    // Cetak label pengiriman otomatis jika autoShip sukses dan return resi cetak
                    if (data.shipped) {
                        window.open(`/orders/${activeOrder.id}/print`, '_blank');
                    }

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
