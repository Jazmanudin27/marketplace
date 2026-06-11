@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
    $hasExpiredStores = $stores->contains('status', 'expired');
@endphp

@if ($hasExpiredStores)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 mt-2" role="alert" style="background-color: rgba(245, 158, 11, 0.12); border-left: 4px solid #f59e0b !important;">
        <i class="fas fa-exclamation-triangle fs-4 text-warning"></i>
        <div class="flex-grow-1">
            <h6 class="alert-heading fw-bold mb-1 text-warning">Koneksi Toko Terputus!</h6>
            <p class="mb-0 small text-muted">Beberapa toko integrasi Anda memerlukan otorisasi ulang karena token kedaluwarsa. Silakan kunjungi <a href="{{ route('stores.index') }}" class="alert-link text-warning fw-bold text-decoration-underline">Kelola Toko</a> untuk menghubungkannya kembali.</p>
        </div>
    </div>
@endif

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
    @if($lowStockProducts->count() > 0)
    <div class="stat-card" style="background: linear-gradient(135deg, rgba(239,68,68,0.15) 0%, rgba(220,38,38,0.08) 100%); border: 1px solid rgba(239,68,68,0.25);">
        <div class="stat-icon" style="background:rgba(239,68,68,0.2); color:#ef4444;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-body">
            <div class="stat-value" style="color:#ef4444;">{{ $lowStockProducts->count() }}</div>
            <div class="stat-label">Stok Menipis!</div>
        </div>
        <div class="stat-glow" style="background:radial-gradient(ellipse at center, rgba(239,68,68,0.15) 0%, transparent 70%);"></div>
    </div>
    @endif
</div>

{{-- REVENUE CHART --}}
<div class="dashboard-card mb-4 mt-4">
    <div class="card-header-line">
        <h3><i class="fas fa-chart-area"></i> Grafik Pendapatan (30 Hari Terakhir)</h3>
    </div>
    <div style="position: relative; height:300px; width:100%">
        <canvas id="revenueChart"></canvas>
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
                @if($store->status === 'connected')
                    <div class="store-status status-connected">
                        <span class="status-dot"></span>
                        Terhubung
                    </div>
                @elseif($store->status === 'expired')
                    <div class="store-status text-warning" style="background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.25); color: #fbbf24 !important;">
                        <span class="status-dot bg-warning" style="background-color: #fbbf24 !important;"></span>
                        Expired
                    </div>
                @else
                    <div class="store-status status-disconnected">
                        <span class="status-dot"></span>
                        Terputus
                    </div>
                @endif
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
            <h3>
                <i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i>
                Stok Menipis
                @if($lowStockProducts->count() > 0)
                    <span style="background:rgba(239,68,68,.2); color:#ef4444; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px; margin-left:6px;">{{ $lowStockProducts->count() }}</span>
                @endif
            </h3>
            <a href="{{ route('products.index') }}" class="view-all-link">Lihat Semua</a>
        </div>
        @forelse($lowStockProducts as $product)
            @php
                $pct = $product->min_stock > 0 ? min(100, round(($product->stock / $product->min_stock) * 100)) : 0;
                $isCritical = $product->stock === 0;
                $barColor = $isCritical ? '#ef4444' : ($pct <= 50 ? '#f59e0b' : '#10b981');
            @endphp
            <div class="stock-item" style="padding:0.65rem 0; border-bottom:1px solid var(--border);">
                <div class="stock-info" style="flex:1; min-width:0;">
                    <div class="stock-name" style="font-weight:600; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                         title="{{ $product->name }}">{{ $product->name }}</div>
                    <div class="stock-sku mono" style="font-size:0.72rem; color:var(--text-secondary);">{{ $product->sku }}</div>
                    {{-- Progress bar stok --}}
                    <div style="margin-top:5px; background:var(--border); border-radius:4px; height:4px; overflow:hidden;">
                        <div style="width:{{ $pct }}%; height:100%; background:{{ $barColor }}; border-radius:4px; transition:width .4s;"></div>
                    </div>
                </div>
                <div class="stock-right" style="text-align:right; margin-left:1rem; flex-shrink:0;">
                    <div style="font-size:1.1rem; font-weight:800; color:{{ $barColor }};">
                        {{ number_format($product->stock) }}
                    </div>
                    <div style="font-size:0.68rem; color:var(--text-secondary);">min {{ number_format($product->min_stock) }}</div>
                    @if($isCritical)
                        <span style="background:rgba(239,68,68,.15); color:#ef4444; font-size:0.62rem; padding:1px 6px; border-radius:10px; font-weight:700;">HABIS</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-mini" style="text-align:center; padding:2rem 1rem;">
                <i class="fas fa-check-circle" style="font-size:2rem; color:#10b981; opacity:.7;"></i>
                <p style="margin:.75rem 0 0; font-size:0.85rem; color:var(--text-secondary);">Semua stok dalam kondisi aman! ✅</p>
            </div>
        @endforelse
    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Gradient fill for Net Profit
    let gradientNet = ctx.createLinearGradient(0, 0, 0, 300);
    gradientNet.addColorStop(0, 'rgba(76, 175, 80, 0.4)');
    gradientNet.addColorStop(1, 'rgba(76, 175, 80, 0.0)');

    // Gradient fill for Gross Revenue
    let gradientGross = ctx.createLinearGradient(0, 0, 0, 300);
    gradientGross.addColorStop(0, 'rgba(108, 99, 255, 0.4)');
    gradientGross.addColorStop(1, 'rgba(108, 99, 255, 0.0)');

    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartDates),
            datasets: [
                {
                    label: 'Keuntungan Bersih (Escrow)',
                    data: @json($chartNet),
                    borderColor: '#4CAF50',
                    backgroundColor: gradientNet,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4CAF50',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Penjualan (Kotor)',
                    data: @json($chartGross),
                    borderColor: '#6c63ff',
                    backgroundColor: gradientGross,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#6c63ff',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.5)',
                        callback: function(value, index, values) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000) + ' Jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000) + ' Rb';
                            }
                            return 'Rp ' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.5)',
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });
});
</script>
@endsection
