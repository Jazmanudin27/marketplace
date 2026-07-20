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
    <h6 class="fw-bold mb-2 text-dark px-1">Daftar Produk Ready</h6>
    <div class="d-flex flex-column mb-3">
        @forelse($products as $product)
            @php
                $isLow = $product->stock <= $product->min_stock;
            @endphp
            <div class="product-item">
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
@endsection
