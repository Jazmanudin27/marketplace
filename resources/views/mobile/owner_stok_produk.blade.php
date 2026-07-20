@extends('layouts.mobile')

@section('title', 'Stok Produk')
@section('header-title', 'Stok Produk')

@section('styles')
<style>
    body {
        background-color: #f8fafc !important;
    }

    .dashboard-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
    }

    .card-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
    }

    .card-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 4px;
        margin-bottom: 0;
    }

    .search-container {
        position: relative;
    }

    .search-input {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 10px 16px 10px 40px;
        font-size: 0.88rem;
        transition: all 0.2s ease;
        color: #0f172a;
    }

    .search-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.04);
        border-radius: 14px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.01);
    }

    .badge-premium {
        font-size: 0.65rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-danger-light {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.15);
    }

    .badge-success-light {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .badge-light-grey {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
</style>
@endsection

@section('content')
    <!-- Summary Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="dashboard-card p-3">
                <span class="card-label">Nilai Total Stok</span>
                <h4 class="card-value text-info">
                    Rp {{ number_format($totalStockValue, 0, ',', '.') }}
                </h4>
            </div>
        </div>
        <div class="col-6">
            <div class="dashboard-card p-3">
                <span class="card-label">Stok Menipis</span>
                <h4 class="card-value text-danger">
                    {{ $lowStockCount }} <span class="text-muted small fw-normal">Produk</span>
                </h4>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="mb-3">
        <form action="{{ route('mobile.owner.stok_produk') }}" method="GET" class="m-0">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-control search-input w-100" 
                       value="{{ $search }}" placeholder="Cari nama produk atau SKU...">
            </div>
        </form>
    </div>

    <!-- Products List -->
    <h6 class="fw-bold mb-2 text-dark px-1">Daftar Produk Ready (Klik untuk Detail)</h6>
    <div class="d-flex flex-column mb-3">
        @forelse($products as $product)
            @php
                $isLow = $product->stock <= $product->min_stock;
            @endphp
            <div class="product-item" style="cursor: pointer; transition: transform 0.2s;" onclick="showProductDetail({{ $product->id }})">
                <div style="flex: 1; min-width: 0; padding-right: 10px;">
                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">{{ $product->name }}</div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">
                        SKU: <code class="font-monospace text-primary bg-light px-1 py-0.5 rounded">{{ $product->sku }}</code>
                    </small>
                    <div class="text-muted mt-2" style="font-size: 0.72rem;">
                        <span class="me-2">HPP: Rp {{ number_format($product->cost_price, 0, ',', '.') }}</span>
                        <span>Jual: Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="text-end" style="white-space: nowrap;">
                    <span class="badge badge-premium {{ $isLow ? 'badge-danger-light' : 'badge-success-light' }} d-inline-block">
                        Stok: {{ $product->stock }} pcs
                    </span>
                    <div class="text-muted mt-1.5" style="font-size: 0.68rem;">
                        Min. Stok: {{ $product->min_stock }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white border rounded-4 text-muted small">
                <i class="fas fa-box-open opacity-30 fs-2 mb-2 d-block text-secondary"></i>
                Tidak ada produk ditemukan.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3 mb-4">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header py-3 border-bottom-0">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i> Detail Produk Ready
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <!-- Image & Name -->
                    <div class="text-center mb-3">
                        <img id="detailImg" src="" alt="Produk" class="img-fluid rounded-3 border bg-light mb-3" style="max-height: 140px; object-fit: contain; width: 100%;">
                        <h5 id="detailName" class="fw-bold text-dark mb-1"></h5>
                        <code id="detailSku" class="font-monospace text-primary bg-light px-2 py-1 rounded small"></code>
                    </div>

                    <!-- Specs -->
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.68rem;">Stok Saat Ini</span>
                                <h5 id="detailStock" class="fw-bold text-dark mb-0"></h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.68rem;">Min. Stok</span>
                                <h5 id="detailMinStock" class="fw-bold text-dark mb-0"></h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.68rem;">Harga HPP</span>
                                <h5 id="detailCostPrice" class="fw-bold text-dark mb-0 text-success"></h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.68rem;">Harga Jual</span>
                                <h5 id="detailPrice" class="fw-bold text-dark mb-0 text-primary"></h5>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Deskripsi Produk:</h6>
                        <p id="detailDescription" class="text-muted small mb-0 lh-base"></p>
                    </div>

                    <!-- Stock Movements (Kartu Stok) -->
                    <div>
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Log Riwayat Stok (Terbaru):</h6>
                        <div class="d-flex flex-column gap-2" id="detailMovements">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function showProductDetail(productId) {
        // Fetch via AJAX
        fetch(`/mobile/owner/stok-produk/${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const p = data.product;
                    
                    document.getElementById('detailImg').src = p.image_url;
                    document.getElementById('detailName').innerText = p.name;
                    document.getElementById('detailSku').innerText = p.sku;
                    document.getElementById('detailStock').innerText = p.stock + ' pcs';
                    document.getElementById('detailMinStock').innerText = p.min_stock + ' pcs';
                    document.getElementById('detailCostPrice').innerText = 'Rp ' + p.cost_price;
                    document.getElementById('detailPrice').innerText = 'Rp ' + p.price;
                    document.getElementById('detailDescription').innerText = p.description;

                    const movementsContainer = document.getElementById('detailMovements');
                    movementsContainer.innerHTML = '';

                    if (data.movements.length === 0) {
                        movementsContainer.innerHTML = '<div class="text-center py-3 text-muted small">Tidak ada riwayat mutasi stok.</div>';
                    } else {
                        data.movements.forEach(m => {
                            const isAdd = m.type === 'IN';
                            const badgeBg = isAdd ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                            const typeLabel = isAdd ? 'Masuk' : (m.type === 'OUT' ? 'Keluar' : 'Penyesuaian');
                            const sign = isAdd ? '+' : (m.type === 'OUT' ? '-' : '');

                            const html = `
                                <div class="p-2 border rounded-3 bg-white" style="font-size: 0.75rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge ${badgeBg}">${typeLabel}</span>
                                        <span class="text-muted" style="font-size: 0.68rem;">${m.date}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold text-dark text-truncate" style="max-width: 180px;">${m.reference}</span>
                                        <span class="fw-bold ${isAdd ? 'text-success' : 'text-danger'}">${sign}${m.quantity} pcs</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.68rem;">
                                        <span>Saldo: ${m.balance_after} pcs</span>
                                        <span>Operator: ${m.operator}</span>
                                    </div>
                                </div>
                            `;
                            movementsContainer.innerHTML += html;
                        });
                    }

                    // Open Modal
                    const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
                    modal.show();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mengambil detail produk.');
            });
    }
</script>
@endsection
