@extends('layouts.mobile')

@section('title', 'Scan Kemasan Produk')
@section('header-title', 'Scan Barcode')

@section('content')
    <!-- Scanner Viewfinder Box -->
    <div class="card border shadow-sm p-4 mb-4">
        <div class="scanner-viewfinder border border-secondary border-2 rounded-3 bg-light text-center py-5"
            style="border-style: dashed !important; cursor: pointer;">
            <i class="fas fa-barcode fa-3x mb-2 text-secondary"></i>
            <div class="fw-bold text-dark small">Siap Memindai...</div>
        </div>

        <!-- Input Trigger for Scanners -->
        <div class="mt-3">
            <label class="form-label text-muted small" id="focus-label">Fokuskan kursor di sini & tembak barcode
                kemasan:</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fas fa-keyboard"></i>
                </span>
                <input type="text" id="barcode-input" class="form-control border-start-0"
                    placeholder="Scan barcode atau ketik SKU..." autofocus autocomplete="off">
                <button id="search-btn" class="btn btn-primary" type="button">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
            <div class="text-center text-muted small mt-2">
                <i class="fas fa-info-circle me-1"></i> Input otomatis mensimulasikan pembacaan barcode hardware.
            </div>
        </div>
    </div>

    <!-- Product Detail Result Panel (hidden by default) -->
    <div id="product-result" class="d-none">
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-primary bg-opacity-10 py-3 px-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <img id="res-image" src="" alt="Product Image" class="rounded border bg-light"
                        style="width: 60px; height: 60px; object-fit: cover;">
                    <div>
                        <h6 id="res-name" class="mb-0 fw-bold text-dark"></h6>
                        <small class="text-muted d-block mt-1">
                            SKU: <code id="res-sku" class="text-primary font-monospace fw-bold"></code>
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-body p-3">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6">
                        <div class="p-2 bg-light border rounded">
                            <small class="text-muted d-block">Stok Saat Ini</small>
                            <span id="res-stock" class="fw-bold text-primary fs-5"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light border rounded">
                            <small class="text-muted d-block">Harga HPP</small>
                            <span id="res-hpp" class="fw-bold text-dark fs-5"></span>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Quick Operation tabs -->
                <ul class="nav nav-pills mb-3 d-flex gap-2" id="scan-tab" role="tablist">
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link active btn-sm w-100 py-2" id="scan-adjust-tab" data-bs-toggle="pill"
                            data-bs-target="#scan-adjust" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-edit me-1"></i> Update Stok
                        </button>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link btn-sm w-100 py-2" id="scan-produce-tab" data-bs-toggle="pill"
                            data-bs-target="#scan-produce" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-hammer me-1"></i> Order Produksi
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="scan-tabContent">
                    <!-- Update Stok Tab -->
                    <div class="tab-pane fade show active" id="scan-adjust" role="tabpanel">
                        <form id="scan-adjust-form">
                            <input type="hidden" id="adjust-product-id">
                            <div class="row g-2">
                                <div class="col-4">
                                    <select id="adjust-type" class="form-select form-select-sm" required>
                                        <option value="in">Tambah</option>
                                        <option value="out">Kurang</option>
                                        <option value="adj">Setel</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <input type="number" id="adjust-qty" class="form-control form-control-sm"
                                        placeholder="Qty" required>
                                </div>
                                <div class="col-4">
                                    <input type="text" id="adjust-ref" class="form-control form-control-sm"
                                        placeholder="Catatan" required value="Scan Barcode">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-sm mt-3">
                                <i class="fas fa-save me-1"></i> Simpan Stok Baru
                            </button>
                        </form>
                    </div>

                    <!-- Order Produksi Tab -->
                    <div class="tab-pane fade" id="scan-produce" role="tabpanel">
                        <form id="scan-produce-form">
                            <input type="hidden" id="produce-product-id">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Qty untuk Dipesan ke Bagian Produksi</label>
                                <input type="number" id="produce-qty" class="form-control form-control-sm"
                                    placeholder="Masukkan Qty Produksi" min="1" required>
                            </div>
                            <button type="submit" class="btn btn-info text-white w-100 btn-sm">
                                <i class="fas fa-paper-plane me-1"></i> Kirim ke Produksi
                            </button>
                        </form>
                    </div>
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
                            resHpp.innerText =
                                `Rp ${new Intl.NumberFormat('id-ID').format(product.cost_price)}`;

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
                        body: JSON.stringify({
                            quantity: qty,
                            type: type,
                            reference: ref
                        })
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
                        body: JSON.stringify({
                            master_product_id: id,
                            quantity: qty
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            document.getElementById('produce-qty').value = '';

                            // Switch back to adjust tab
                            const adjustTabTrigger = new bootstrap.Tab(document.getElementById(
                                'scan-adjust-tab'));
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
