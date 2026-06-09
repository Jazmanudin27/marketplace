@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-body">
            <div class="stat-value">{{ number_format($todayOrders) }}</div>
            <div class="stat-label">Pesanan Hari Ini</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</div>
            <div class="stat-label">Pendapatan Hari Ini</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</div>
            <div class="stat-label">Pendapatan Bulan Ini</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div class="stat-body">
            <div class="stat-value">{{ number_format($pendingOrders) }}</div>
            <div class="stat-label">Siap Kirim</div>
        </div>
        <div class="stat-glow"></div>
    </div>
</div>

{{-- CHANNEL OVERVIEW --}}
<div class="section-title">
    <i class="fas fa-plug"></i> Status Toko Marketplace
</div>
<div class="stores-grid">
    @forelse($stores as $store)
        <div class="store-card channel-{{ $store->channel->code }}">
            <div class="store-header">
                <div class="channel-badge channel-{{ $store->channel->code }}">
                    @if($store->channel->code === 'shopee')
                        <i class="fas fa-shopping-bag"></i>
                    @elseif($store->channel->code === 'tiktok')
                        <i class="fab fa-tiktok"></i>
                    @elseif($store->channel->code === 'tokopedia')
                        <i class="fas fa-store"></i>
                    @else
                        <i class="fas fa-globe"></i>
                    @endif
                    {{ $store->channel->name }}
                </div>
                <div class="store-status {{ $store->status === 'connected' ? 'status-connected' : 'status-disconnected' }}">
                    <span class="status-dot"></span>
                    {{ $store->status === 'connected' ? 'Terhubung' : 'Terputus' }}
                </div>
            </div>
            <div class="store-name">{{ $store->store_name }}</div>
            <div class="store-stat">
                <i class="fas fa-shopping-cart"></i> {{ number_format($store->orders_count) }} Pesanan
            </div>
        </div>
    @empty
        <div class="empty-state">
            <i class="fas fa-plug"></i>
            <p>Belum ada toko yang terhubung.</p>
            <a href="{{ route('stores.create') }}" class="btn-primary-sm">Tambah Toko</a>
        </div>
    @endforelse
</div>

{{-- BOTTOM GRID: Recent Orders + Low Stock --}}
<div class="dashboard-bottom-grid">

    {{-- Recent Orders --}}
    <div class="dashboard-card">
        <div class="card-header-line">
            <h3><i class="fas fa-clock"></i> Pesanan Terbaru</h3>
            <a href="{{ route('orders.index') }}" class="view-all-link">Lihat Semua</a>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Pembeli</th>
                        <th>Channel</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td class="mono">{{ $order->invoice_number ?? $order->order_marketplace_id }}</td>
                            <td>{{ $order->buyer_name ?? '-' }}</td>
                            <td>
                                <span class="channel-tag channel-{{ $order->store->channel->code }}">
                                    {{ $order->store->channel->name }}
                                </span>
                            </td>
                            <td class="mono fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge badge-{{ $order->status_badge }}">
                                    {{ str_replace('_', ' ', $order->order_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada pesanan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Low Stock Products --}}
    <div class="dashboard-card">
        <div class="card-header-line">
            <h3><i class="fas fa-exclamation-triangle text-warning"></i> Stok Hampir Habis</h3>
            <a href="{{ route('products.index') }}" class="view-all-link">Lihat Semua</a>
        </div>
        @forelse($lowStockProducts as $product)
            <div class="stock-item">
                <div class="stock-info">
                    <div class="stock-name">{{ $product->name }}</div>
                    <div class="stock-sku mono">{{ $product->sku }}</div>
                </div>
                <div class="stock-right">
                    <div class="stock-number {{ $product->stock === 0 ? 'text-danger' : 'text-warning' }}">
                        {{ number_format($product->stock) }}
                    </div>
                    <div class="stock-label">/ Min {{ number_format($product->min_stock) }}</div>
                </div>
            </div>
        @empty
            <div class="empty-mini">
                <i class="fas fa-check-circle text-success"></i>
                <p>Semua stok dalam kondisi aman!</p>
            </div>
        @endforelse
    </div>
</div>

@endsection
