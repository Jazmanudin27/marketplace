@extends('layouts.mobile')

@section('title', 'Scan Kemasan Produk')
@section('header-title', 'Scan Barcode')

@section('styles')
<style>
    .scanner-viewfinder {
        position: relative;
        height: 180px;
        background: rgba(0, 0, 0, 0.4);
        border: 2px dashed rgba(129, 140, 248, 0.5);
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.6);
    }

    .laser-line {
        position: absolute;
        width: 100%;
        height: 2px;
        background: rgba(239, 68, 68, 0.8);
        box-shadow: 0 0 8px rgba(239, 68, 68, 1);
        animation: scanAnim 2s infinite ease-in-out;
    }

    @keyframes scanAnim {
        0% { top: 10%; }
        50% { top: 90%; }
        100% { top: 10%; }
    }

    .scan-feedback {
        font-size: 0.8rem;
        color: var(--text-muted);
        text-align: center;
        margin-top: 10px;
    }

    /* Result Panel */
    .scan-result-card {
        border-top: 4px solid var(--primary);
    }
</style>
@endsection

@section('content')
<!-- Scanner Viewfinder Box -->
<div class="glass-card p-4 mb-4">
    <div class="scanner-viewfinder">
        <div class="laser-line"></div>
        <i class="fas fa-barcode fa-3x mb-2 text-muted" style="opacity: 0.5;"></i>
        <span class="fw-bold text-muted" style="font-size: 0.85rem;">Siap Memindai...</span>
    </div>
    
    <!-- Input Trigger for Scanners -->
    <div class="mt-4">
        <label class="form-label text-muted" style="font-size: 0.8rem;" id="focus-label">Fokuskan kursor di sini & tembak barcode kemasan:</label>
        <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--border-card); color:var(--text-muted);">
                <i class="fas fa-keyboard"></i>
            </span>
            <input type="text" id="barcode-input" class="form-control custom-input border-start-0" placeholder="Scan barcode atau ketik SKU..." autofocus autocomplete="off">
            <button id="search-btn" class="btn btn-premium" type="button">
                <i class="fas fa-search"></i> Cari
            </button>
        </div>
        <div class="scan-feedback">
            <i class="fas fa-info-circle me-1"></i> Input otomatis mensimulasikan pembacaan barcode hardware.
        </div>
    </div>
</div>

<!-- Product Detail Result Panel (hidden by default) -->
<div id="product-result" class="d-none">
    <div class="glass-card p-4 scan-result-card mb-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width: 60px; height: 60px; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.05); border:1px solid var(--border-card);" class="d-flex align-items-center justify-content-center">
                <img id="res-image" src="" alt="Product Image" style="width:100%; height:100%; object-fit: cover;">
            </div>
            <div>
                <h4 id="res-name" class="mb-0" style="font-size: 1rem; font-weight: 600;"></h4>
                <div style="font-size: 0.75rem; color: var(--text-muted);" class="mt-1">
                    SKU: <span id="res-sku" class="mono fw-bold text-white"></span>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3 text-center">
            <div class="col-6">
                <div class="p-2" style="background: rgba(255,255,255,0.02); border:1px solid var(--border-card); border-radius: 10px;">
                    <small class="text-muted d-block" style="font-size: 0.65rem;">Stok Saat Ini</small>
                    <span id="res-stock" class="fw-bold" style="font-size: 1.15rem; color:#818cf8;"></span>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2" style="background: rgba(255,255,255,0.02); border:1px solid var(--border-card); border-radius: 10px;">
                    <small class="text-muted d-block" style="font-size: 0.65rem;">Harga HPP</small>
                    <span id="res-hpp" class="fw-bold text-white" style="font-size: 1.15rem;"></span>
                </div>
            </div>
        </div>

        <hr class="border-light border-opacity-10 my-3">

        <!-- Quick Operation tabs -->
        <ul class="nav nav-tabs mb-3 d-flex gap-2" style="border:none;" id="scan-tab" role="tablist">
            <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link active btn-sm w-100 py-2" style="font-size:0.75rem;" id="scan-adjust-tab" data-bs-toggle="tab" data-bs-target="#scan-adjust" type="button" role="tab" aria-controls="scan-adjust" aria-selected="true">
                    <i class="fas fa-edit me-1"></i> Update Stok
                </button>
            </li>
            <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link btn-sm w-100 py-2" style="font-size:0.75rem;" id="scan-produce-tab" data-bs-toggle="tab" data-bs-target="#scan-produce" type="button" role="tab" aria-controls="scan-produce" aria-selected="false">
                    <i class="fas fa-hammer me-1"></i> Order Produksi
                </button>
            </li>
        </ul>

        <div class="tab-content" id="scan-tabContent">
            <!-- Update Stok Tab -->
            <div class="tab-pane fade show active" id="scan-adjust" role="tabpanel" aria-labelledby="scan-adjust-tab">
                <form id="scan-adjust-form">
                    <input type="hidden" id="adjust-product-id">
                    <div class="row g-2">
                        <div class="col-4">
                            <select id="adjust-type" class="form-select custom-input py-2" style="font-size:0.8rem;" required>
                                <option value="in">Tambah</option>
                                <option value="out">Kurang</option>
                                <option value="adj">Setel</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <input type="number" id="adjust-qty" class="form-control custom-input py-2" style="font-size:0.8rem;" placeholder="Qty" required>
                        </div>
                        <div class="col-4">
                            <input type="text" id="adjust-ref" class="form-control custom-input py-2" style="font-size:0.8rem;" placeholder="Catatan" required value="Scan Barcode">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-premium w-100 btn-sm mt-3 py-2" style="font-size: 0.8rem;">
                        <i class="fas fa-save me-1"></i> Simpan Stok Baru
                    </button>
                </form>
            </div>

            <!-- Order Produksi Tab -->
            <div class="tab-pane fade" id="scan-produce" role="tabpanel" aria-labelledby="scan-produce-tab">
                <form id="scan-produce-form">
                    <input type="hidden" id="produce-product-id">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 0.75rem;">Qty untuk Dipesan ke Bagian Produksi</label>
                        <input type="number" id="produce-qty" class="form-control custom-input" placeholder="Masukkan Qty Produksi" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-premium w-100 btn-sm py-2" style="font-size: 0.8rem; background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                        <i class="fas fa-paper-plane me-1"></i> Kirim ke Produksi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const barcodeInput = document.getElementById('barcode-input');
        const searchBtn = document.getElementById('search-btn');
        const productResult = document.getElementById('product-result');
        
        const resImage = document.getElementById('res-image');
        const resName = document.getElementById('res-name');
        const resSku = document.getElementById('res-sku');
        const resStock = document.getElementById('res-stock');
        const resHpp = document.getElementById('res-hpp');
        
        const adjustProductId = document.getElementById('adjust-product-id');
        const produceProductId = document.getElementById('produce-product-id');

        // Refocus barcode input when clicking viewfinder
        document.querySelector('.scanner-viewfinder').addEventListener('click', () => {
            barcodeInput.focus();
        });

        // Trigger search on Enter key press (hardware scanner emulator)
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProduct();
            }
        });

        searchBtn.addEventListener('click', searchProduct);

        function searchProduct() {
            const skuVal = barcodeInput.value.trim();
            if (!skuVal) return;

            // Clear previous result
            productResult.classList.add('d-none');

            // AJAX request to fetch product details
            fetch(`{{ route('mobile.gudang.scan') }}?sku=${encodeURIComponent(skuVal)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error("Produk tidak ditemukan.");
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const product = data.product;
                    
                    // Populate details
                    resImage.src = product.image_url;
                    resName.innerText = product.name;
                    resSku.innerText = product.sku;
                    resStock.innerText = `${product.stock} pcs`;
                    resHpp.innerText = `Rp ${new Intl.NumberFormat('id-ID').format(product.cost_price)}`;
                    
                    adjustProductId.value = product.id;
                    produceProductId.value = product.id;

                    // Show result panel
                    productResult.classList.remove('d-none');
                    barcodeInput.value = ''; // clear input for next scan
                }
            })
            .catch(err => {
                alert(err.message);
                barcodeInput.value = '';
            });
        }

        // Adjust Stock AJAX Form Submit
        document.getElementById('scan-adjust-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = adjustProductId.value;
            const type = document.getElementById('adjust-type').value;
            const qty = document.getElementById('adjust-qty').value;
            const ref = document.getElementById('adjust-ref').value;

            fetch(`/mobile/gudang/adjust-stock/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ quantity: qty, type: type, reference: ref })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    resStock.innerText = `${data.new_stock} pcs`;
                    document.getElementById('adjust-qty').value = '';
                } else {
                    alert("Gagal memperbarui stok.");
                }
            })
            .catch(err => alert("Terjadi kesalahan: " + err.message));
        });

        // Request Production AJAX Form Submit
        document.getElementById('scan-produce-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = produceProductId.value;
            const qty = document.getElementById('produce-qty').value;

            fetch(`{{ route('mobile.gudang.request_production') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ master_product_id: id, quantity: qty })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    document.getElementById('produce-qty').value = '';
                    
                    // Switch back to adjust tab
                    const adjustTabTrigger = new bootstrap.Tab(document.getElementById('scan-adjust-tab'));
                    adjustTabTrigger.show();
                } else {
                    alert("Gagal mengirim permintaan produksi.");
                }
            })
            .catch(err => alert("Terjadi kesalahan: " + err.message));
        });
    });
</script>
@endsection
