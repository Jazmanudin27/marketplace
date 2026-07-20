@extends('layouts.mobile')

@section('title', 'Stok Barang')
@section('header-title', 'Stok Barang')

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

    .barang-item {
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
</style>
@endsection

@section('content')
    <!-- Summary Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="dashboard-card p-3">
                <span class="card-label">Total Jenis Barang</span>
                <h4 class="card-value text-indigo">
                    {{ $totalItemsCount }} <span class="text-muted small fw-normal">Item</span>
                </h4>
            </div>
        </div>
        <div class="col-6">
            <div class="dashboard-card p-3">
                <span class="card-label">Stok Menipis</span>
                <h4 class="card-value text-danger">
                    {{ $lowStockCount }} <span class="text-muted small fw-normal">Item</span>
                </h4>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="mb-3">
        <form action="{{ route('mobile.owner.stok_barang') }}" method="GET" class="m-0">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-control search-input w-100" 
                       value="{{ $search }}" placeholder="Cari nama barang atau kode SKU...">
            </div>
        </form>
    </div>

    <!-- Inventory Items List -->
    <h6 class="fw-bold mb-2 text-dark px-1">Daftar Bahan Baku / Kain (Klik untuk Detail)</h6>
    <div class="d-flex flex-column mb-3">
        @forelse($items as $item)
            @php
                $isLow = $item->stock <= $item->min_stock;
            @endphp
            <div class="barang-item" style="cursor: pointer; transition: transform 0.2s;" onclick="showItemDetail({{ $item->id }})">
                <div style="flex: 1; min-width: 0; padding-right: 10px;">
                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">{{ $item->name }}</div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">
                        SKU: <code class="font-monospace text-primary bg-light px-1 py-0.5 rounded">{{ $item->sku }}</code>
                    </small>
                    <div class="text-muted mt-2" style="font-size: 0.72rem;">
                        <span>Kategori: {{ ucfirst($item->category ?? 'Umum') }}</span>
                    </div>
                </div>
                <div class="text-end" style="white-space: nowrap;">
                    <span class="badge badge-premium {{ $isLow ? 'badge-danger-light' : 'badge-success-light' }} d-inline-block">
                        Stok: {{ number_format($item->stock, 1) }} {{ $item->unit ?: 'pcs' }}
                    </span>
                    <div class="text-muted mt-1.5" style="font-size: 0.68rem;">
                        Min. Stok: {{ $item->min_stock }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white border rounded-4 text-muted small">
                <i class="fas fa-scroll opacity-30 fs-2 mb-2 d-block text-secondary"></i>
                Tidak ada data bahan baku/kain.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3 mb-4">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>

    <!-- Item Detail Modal -->
    <div class="modal fade" id="itemDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header py-3 border-bottom-0">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle text-indigo"></i> Detail Bahan Baku / Kain
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="text-center mb-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; border: 1px solid rgba(0,0,0,0.05);">
                            <i class="fas fa-scroll text-indigo fs-3"></i>
                        </div>
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
                                <span class="text-muted d-block small mb-1" style="font-size: 0.68rem;">Kategori</span>
                                <h5 id="detailCategory" class="fw-bold text-dark mb-0 text-indigo"></h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.68rem;">Satuan Unit</span>
                                <h5 id="detailUnit" class="fw-bold text-dark mb-0 text-primary"></h5>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Deskripsi / Keterangan:</h6>
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
    function showItemDetail(itemId) {
        // Fetch via AJAX
        fetch(`/mobile/owner/stok-barang/${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.item;
                    
                    document.getElementById('detailName').innerText = item.name;
                    document.getElementById('detailSku').innerText = item.sku;
                    document.getElementById('detailStock').innerText = item.stock + ' ' + item.unit;
                    document.getElementById('detailMinStock').innerText = item.min_stock + ' ' + item.unit;
                    document.getElementById('detailCategory').innerText = item.category;
                    document.getElementById('detailUnit').innerText = item.unit;
                    document.getElementById('detailDescription').innerText = item.description;

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
                                        <span class="fw-bold ${isAdd ? 'text-success' : 'text-danger'}">${sign}${m.quantity} ${item.unit}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.68rem;">
                                        <span>Saldo: ${m.balance_after} ${item.unit}</span>
                                        <span>Operator: ${m.operator}</span>
                                    </div>
                                </div>
                            `;
                            movementsContainer.innerHTML += html;
                        });
                    }

                    // Open Modal
                    const modal = new bootstrap.Modal(document.getElementById('itemDetailModal'));
                    modal.show();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mengambil detail barang.');
            });
    }
</script>
@endsection
