@extends('layouts.mobile')

@section('title', 'Laporan Penjualan')
@section('header-title', 'Penjualan')

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

    .order-item {
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

    .badge-light-grey {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .badge-success-light {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .badge-indigo-light {
        background: #e0e7ff;
        color: #4f46e5;
        border: 1px solid rgba(79, 70, 229, 0.15);
    }

    .badge-danger-light {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.15);
    }
</style>
@endsection

@section('content')
    <!-- Summary Revenue Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="dashboard-card p-3">
                <span class="card-label">Omset Hari Ini</span>
                <h4 class="card-value text-success">
                    Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                </h4>
            </div>
        </div>
        <div class="col-6">
            <div class="dashboard-card p-3">
                <span class="card-label">Omset Bulan Ini</span>
                <h4 class="card-value text-primary">
                    Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="mb-3">
        <form action="{{ route('mobile.owner.sales') }}" method="GET" class="m-0">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-control search-input w-100" 
                       value="{{ $search }}" placeholder="Cari pembeli, toko, atau no. invoice...">
            </div>
        </form>
    </div>

    <!-- Orders List -->
    <h6 class="fw-bold mb-2 text-dark px-1">Daftar Transaksi</h6>
    <div class="d-flex flex-column mb-3">
        @forelse($orders as $order)
            <div class="order-item">
                <div style="flex: 1; min-width: 0; padding-right: 10px;">
                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">{{ $order->buyer_name }}</div>
                    <div class="text-muted text-truncate font-monospace" style="font-size: 0.72rem; margin-top: 2px;">
                        {{ $order->invoice_number }}
                    </div>
                    <div class="d-flex gap-1.5 align-items-center mt-2 flex-wrap">
                        <span class="badge badge-premium badge-light-grey">
                            {{ $order->store->store_name }}
                        </span>
                        <span class="text-muted small" style="font-size: 0.7rem;">
                            {{ $order->order_date->format('d M H:i') }}
                        </span>
                    </div>
                </div>
                <div class="text-end" style="white-space: nowrap;">
                    <div class="fw-bold text-success" style="font-size: 0.92rem;">
                        Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                    </div>
                    @php
                        $badgeClass = 'badge-light-grey';
                        if ($order->order_status === 'COMPLETED' || $order->order_status === 'DELIVERED') {
                            $badgeClass = 'badge-success-light';
                        } elseif ($order->order_status === 'READY_TO_SHIP' || $order->order_status === 'SHIPPED') {
                            $badgeClass = 'badge-indigo-light';
                        } elseif ($order->order_status === 'CANCELLED') {
                            $badgeClass = 'badge-danger-light';
                        }
                    @endphp
                    <span class="badge badge-premium {{ $badgeClass }} mt-1.5 d-inline-block">
                        {{ str_replace('_', ' ', $order->order_status) }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white border rounded-4 text-muted small">
                <i class="fas fa-shopping-cart opacity-30 fs-2 mb-2 d-block text-secondary"></i>
                Tidak ada data transaksi penjualan.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3 mb-4">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
@endsection
