@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

    @php
        $hasExpiredStores = $stores->contains('status', 'expired');
    @endphp

    @if ($hasExpiredStores)
        <div class="alert alert-warning alert-dismissible fade show border-start border-4 border-warning d-flex align-items-center gap-2 p-2 px-3 mb-3"
            role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading fw-bold mb-1 text-warning small">Koneksi Toko Terputus!</h6>
                <p class="mb-0 small text-secondary">Beberapa toko integrasi Anda
                    memerlukan otorisasi ulang karena
                    token kedaluwarsa. Silakan kunjungi <a href="{{ route('stores.index') }}"
                        class="alert-link text-warning fw-bold text-decoration-underline">Kelola Toko</a> untuk
                    menhubungkannya kembali.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($urgentOrders->isNotEmpty())
        @php
            $overdueCount = $urgentOrders->filter(fn($o) => $o->ship_before_date->isPast())->count();
            $soonCount    = $urgentOrders->count() - $overdueCount;
        @endphp
        <div class="alert alert-dismissible fade show border-start border-4 p-0 mb-3 overflow-hidden shadow-sm {{ $overdueCount > 0 ? 'alert-danger border-danger' : 'alert-warning border-warning' }}"
            role="alert">
            <div class="d-flex align-items-stretch">
                <div class="d-flex align-items-center justify-content-center px-3 {{ $overdueCount > 0 ? 'bg-danger' : 'bg-warning' }}" style="min-width:52px;">
                    <i class="bi bi-clock-fill fs-4 text-white"></i>
                </div>
                <div class="flex-grow-1 p-2 px-3">
                    <h6 class="fw-bold mb-1 {{ $overdueCount > 0 ? 'text-danger' : 'text-warning' }} small">
                        ⚠️ {{ $urgentOrders->count() }} Pesanan Harus Segera Dikirim!
                    </h6>
                    <p class="mb-1 small text-secondary">
                        @if ($overdueCount > 0)
                            <span class="badge bg-danger me-1">{{ $overdueCount }} Overdue</span>
                        @endif
                        @if ($soonCount > 0)
                            <span class="badge bg-warning text-dark me-1">{{ $soonCount }} Dalam 24 Jam</span>
                        @endif
                        Pesanan berikut memiliki batas waktu pengiriman yang sudah lewat atau kurang dari 24 jam.
                    </p>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach ($urgentOrders->take(5) as $uo)
                            <a href="{{ route('orders.show', $uo->id) }}"
                               class="badge text-decoration-none {{ $uo->is_ship_overdue ? 'bg-danger' : 'bg-warning text-dark' }}">
                                {{ $uo->order_marketplace_id }}
                                &bull; {{ $uo->ship_before_date->diffForHumans() }}
                            </a>
                        @endforeach
                        @if ($urgentOrders->count() > 5)
                            <a href="{{ route('orders.index') }}" class="badge bg-secondary text-decoration-none">
                                +{{ $urgentOrders->count() - 5 }} lainnya
                            </a>
                        @endif
                    </div>
                </div>
                <div class="d-flex align-items-center pe-3">
                    <a href="{{ route('orders.index', ['status' => 'READY_TO_SHIP']) }}"
                       class="btn btn-sm {{ $overdueCount > 0 ? 'btn-danger' : 'btn-warning' }} fw-semibold me-2">
                        Lihat Pesanan
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Welcome Card -->
    <div class="card bg-primary text-white border-0 rounded-3 shadow-sm mb-3">
        <div class="card-body p-3 py-3">
            <div class="row align-items-center">
                <div class="col-md-8 col-sm-12">
                    <h4 class="fw-bold mb-1 text-white">Selamat Datang, {{ Auth::user()->name }} 👋</h4>
                    <p class="mb-0 text-white-50 small">
                        Kelola operasional bisnis dan integrasi marketplace Anda untuk
                        <strong class="text-white">{{ Auth::user()->tenant->name }}</strong> dalam satu dashboard terpusat.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-3 mb-3">

        <!-- Card 1: Today's Orders -->
        <div class="col-lg-3 col-md-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Pesanan Hari Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark" id="totalApps">{{ number_format($todayOrders) }}</h4>
                                <span class="text-success small fw-bold ms-2"><i class="bi bi-arrow-up-short"></i> 4%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-primary opacity-75">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Today's Revenue -->
        <div class="col-lg-3 col-md-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Pendapatan Hari Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</h5>
                                <span class="text-success small fw-bold ms-2"><i class="bi bi-arrow-up-short"></i> 8%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-success opacity-75">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Monthly Revenue -->
        <div class="col-lg-3 col-md-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Pendapatan Bulan Ini</small>
                            <div class="d-flex align-items-center mt-1">
                                <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</h5>
                                <span class="text-danger small fw-bold ms-2"><i class="bi bi-arrow-down-short"></i>
                                    2%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-warning opacity-75">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Ready to Ship -->
        <div class="col-lg-3 col-md-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold">Siap Dikirim</small>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="fw-bold mb-0 text-dark" id="totalUsers">{{ number_format($pendingOrders) }}
                                </h4>
                                <span class="text-danger small fw-bold ms-2"><i class="bi bi-arrow-down-short"></i>
                                    1%</span>
                            </div>
                        </div>
                        <div class="fs-2 text-danger opacity-75">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Critical Stock Alert Card -->
    @if ($lowStockProducts->count() > 0)
        <div class="card bg-danger-subtle border-start border-4 border-danger border-0 mb-3 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between gap-2 py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-danger mb-0 small">Stok Kritis!</h6>
                        <span class="text-dark opacity-75 small">Terdapat <strong
                                class="text-danger">{{ $lowStockProducts->count() }}</strong> produk dengan
                            persediaan yang menipis atau habis.</span>
                    </div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-3">Lihat
                    Detail</a>
            </div>
        </div>
    @endif

    <!-- Marketplace Channels Overview -->
    <div class="mb-3">
        <h6 class="text-secondary text-uppercase fw-bold mb-2 small d-flex align-items-center gap-2">
            <i class="bi bi-plug-fill text-primary"></i> Status Toko Marketplace
        </h6>
        <div class="row g-2">
            @forelse($stores as $store)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="card border rounded-3 h-100 shadow-sm">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                @php
                                    $badgeClass = 'bg-secondary';
                                    if ($store->channel->code === 'shopee') {
                                        $badgeClass = 'bg-danger text-white';
                                    } elseif ($store->channel->code === 'tokopedia') {
                                        $badgeClass = 'bg-success text-white';
                                    } elseif ($store->channel->code === 'tiktok') {
                                        $badgeClass = 'bg-dark text-white';
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }} px-2 py-1">
                                    @if ($store->channel->code === 'shopee')
                                        <i class="bi bi-bag-fill me-1"></i>
                                    @elseif($store->channel->code === 'tiktok')
                                        <i class="bi bi-tiktok me-1"></i>
                                    @elseif($store->channel->code === 'tokopedia')
                                        <i class="bi bi-shop me-1"></i>
                                    @else
                                        <i class="bi bi-globe me-1"></i>
                                    @endif
                                    {{ $store->channel->name }}
                                </span>

                                @if ($store->status === 'connected')
                                    <span class="d-flex align-items-center gap-1 text-success fw-medium small">
                                        <span class="bg-success rounded-circle p-1"></span>
                                        Aktif
                                    </span>
                                @elseif($store->status === 'expired')
                                    <span class="d-flex align-items-center gap-1 text-warning fw-medium small">
                                        <span class="bg-warning rounded-circle p-1"></span>
                                        Expired
                                    </span>
                                @else
                                    <span class="d-flex align-items-center gap-1 text-danger fw-medium small">
                                        <span class="bg-danger rounded-circle p-1"></span>
                                        Off
                                    </span>
                                @endif
                            </div>
                            <h6 class="fw-bold mb-1 text-truncate text-dark" title="{{ $store->store_name }}">
                                {{ $store->store_name }}</h6>
                            <div class="text-secondary small d-flex align-items-center gap-1">
                                <i class="bi bi-receipt"></i> {{ number_format($store->orders_count) }} Pesanan
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border rounded-3 text-center p-4 shadow-sm">
                        <div class="card-body py-4">
                            <i class="bi bi-plug fs-3 text-secondary mb-2"></i>
                            <p class="card-text text-secondary mb-2 small">Belum ada toko yang terhubung.</p>
                            @can('manage-stores')
                                <a href="{{ route('stores.create') }}" class="btn btn-primary btn-sm px-2 rounded-3">Tambah
                                    Toko</a>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Chart & Integrations Summary Row -->
    <div class="row g-3 mb-3">
        <!-- Column: Sales Analytics Chart -->
        <div class="col-lg-8">
            <div class="card border rounded-3 h-100">
                <div class="card-header py-2 px-3">
                    <div class="row w-100 g-2 align-items-center m-0">
                        <div class="col-sm-8 p-0">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bar-chart-line text-primary fs-6"></i>
                                <span class="fw-bold text-dark">Grafik Pendapatan & Perbandingan</span>
                            </div>
                        </div>
                        <div class="col-sm-4 p-0 d-flex justify-content-sm-end">
                            <select id="chartScope"
                                class="form-select form-select-sm w-auto bg-white text-dark border-secondary">
                                <option value="daily">Hari Ini vs Kemarin</option>
                                <option value="monthly" selected>Bulan Ini vs Bulan Lalu</option>
                                <option value="yearly">Tahun Ini vs Tahun Lalu</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="ratio ratio-21x9">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column: Operational Summary -->
        <div class="col-lg-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary fs-6"></i>
                        <span class="fw-bold text-dark">Ringkasan Operasional</span>
                    </div>
                </div>
                <div class="card-body d-flex flex-column gap-2 justify-content-center p-3">

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success-subtle text-success p-2 rounded-circle lh-1">
                            <i class="bi bi-plug fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Koneksi Integrasi</span>
                            <span
                                class="fw-semibold text-secondary small">{{ $stores->where('status', 'connected')->count() }}
                                dari {{ $totalStores }} Toko Aktif</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary-subtle text-primary p-2 rounded-circle lh-1">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Katalog Produk</span>
                            <span class="fw-semibold text-secondary small">{{ number_format($totalProducts) }} Master
                                Produk</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-warning-subtle text-warning p-2 rounded-circle lh-1">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Proses Pengiriman</span>
                            <span class="fw-semibold text-secondary small">{{ number_format($pendingOrders) }} Pesanan
                                Siap Kirim</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-info-subtle text-info p-2 rounded-circle lh-1">
                            <i class="bi bi-chat-dots fs-5"></i>
                        </div>
                        <div>
                            <span class="d-block text-secondary small">Layanan Pelanggan</span>
                            <span class="fw-semibold text-secondary small">Inbox Chat Terhubung</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Recent Orders & Stock warning grid -->
    <div class="row g-3 p-2">

        <!-- Column: Recent Orders Table -->
        <div class="col-12 col-xxl-8">
            <div class="card border rounded-3 h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-primary fs-6"></i>
                        <span class="fw-bold text-dark">Pesanan Terbaru</span>
                    </div>
                    <a href="{{ route('orders.index') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-bold text-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Invoice</th>
                                    <th>Pembeli</th>
                                    <th>Toko / Channel</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td class="text-dark small">
                                            {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td class="font-monospace fw-semibold text-dark small">
                                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                        </td>
                                        <td class="fw-medium text-dark small">
                                            {{ $order->buyer_name ?? '-' }}</td>
                                        <td>
                                            @php
                                                $badgeClass = 'bg-secondary';
                                                if ($order->store->channel->code === 'shopee') {
                                                    $badgeClass = 'bg-danger text-white';
                                                } elseif ($order->store->channel->code === 'tokopedia') {
                                                    $badgeClass = 'bg-success text-white';
                                                } elseif ($order->store->channel->code === 'tiktok') {
                                                    $badgeClass = 'bg-dark text-white';
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeClass }} px-2 py-1">
                                                {{ $order->store->store_name }}
                                            </span>
                                        </td>
                                        <td class="font-monospace fw-bold text-dark small">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @php
                                                $badgeColor = 'bg-secondary';
                                                if ($order->status_badge === 'success') {
                                                    $badgeColor = 'bg-success text-white';
                                                } elseif ($order->status_badge === 'warning') {
                                                    $badgeColor = 'bg-warning text-dark';
                                                } elseif ($order->status_badge === 'danger') {
                                                    $badgeColor = 'bg-danger text-white';
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeColor }} px-2 py-1">
                                                {{ str_replace('_', ' ', $order->order_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-secondary">
                                            <i
                                                class="bi bi-check-circle-fill fs-3 text-success opacity-75 mb-2 d-block"></i>
                                            <p class="mb-0 small">Belum ada pesanan terbaru</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column: Low Stock Products list -->
        <div class="col-12 col-xxl-4">
            <div class="card border rounded-3 h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle text-warning fs-6"></i>
                        <span class="fw-bold text-dark">Stok Menipis</span>
                        @if ($lowStockProducts->count() > 0)
                            <span class="badge bg-danger rounded-pill px-2 py-1">{{ $lowStockProducts->count() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('products.index') }}"
                        class="btn btn-link btn-sm text-decoration-none p-0 fw-bold text-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-3">
                    @forelse($lowStockProducts as $product)
                        @php
                            $pct =
                                $product->min_stock > 0
                                    ? min(100, round(($product->stock / $product->min_stock) * 100))
                                    : 0;
                            $isCritical = $product->stock === 0;
                            $barColor = $isCritical ? 'bg-danger' : ($pct <= 50 ? 'bg-warning' : 'bg-success');
                            $textColor = $isCritical ? 'text-danger' : ($pct <= 50 ? 'text-warning' : 'text-success');
                        @endphp
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                            <div class="flex-grow-1 min-width-0 pe-3">
                                <div class="fw-semibold text-truncate text-dark small" title="{{ $product->name }}">
                                    {{ $product->name }}</div>
                                <div class="text-secondary font-monospace small">
                                    SKU: {{ $product->sku }}</div>
                                <div class="progress mt-1">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar"
                                        style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold fs-5 {{ $textColor }}">{{ number_format($product->stock) }}</div>
                                <div class="text-secondary small">min {{ number_format($product->min_stock) }}</div>
                                @if ($isCritical)
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-1 small">HABIS</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary">
                            <i class="bi bi-check-circle-fill fs-3 text-success opacity-75 mb-2 d-block"></i>
                            <p class="mb-0 small">Semua stok aman! ✅</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');

            // Gradient fill for Net Profit
            let gradientNet = ctx.createLinearGradient(0, 0, 0, 220);
            gradientNet.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            gradientNet.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            // Gradient fill for Gross Revenue
            let gradientGross = ctx.createLinearGradient(0, 0, 0, 220);
            gradientGross.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
            gradientGross.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($initialChartData['labels']),
                    datasets: [{
                            label: @json($initialChartData['currentLabel']),
                            data: @json($initialChartData['current']),
                            borderColor: '#10b981',
                            backgroundColor: gradientNet,
                            borderWidth: 3,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10b981',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: @json($initialChartData['previousLabel']),
                            data: @json($initialChartData['previous']),
                            borderColor: '#3b82f6',
                            backgroundColor: gradientGross,
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3b82f6',
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
                                color: '#475569',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#ffffff',
                            titleColor: '#0f172a',
                            bodyColor: '#334155',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            maximumFractionDigits: 0
                                        }).format(context.parsed.y);
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
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 10
                                },
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
                                color: '#64748b',
                                font: {
                                    family: 'Inter, Outfit, sans-serif',
                                    size: 10
                                },
                                maxTicksLimit: 12
                            }
                        }
                    }
                }
            });

            // AJAX scope filtering
            const chartScope = document.getElementById('chartScope');
            chartScope.addEventListener('change', function() {
                const scope = this.value;
                fetch(`/dashboard/chart-data?scope=${scope}`)
                    .then(response => response.json())
                    .then(data => {
                        revenueChart.data.labels = data.labels;

                        revenueChart.data.datasets[0].label = data.currentLabel;
                        revenueChart.data.datasets[0].data = data.current;

                        revenueChart.data.datasets[1].label = data.previousLabel;
                        revenueChart.data.datasets[1].data = data.previous;

                        revenueChart.update();
                    })
                    .catch(err => {
                        console.error('Error fetching chart data:', err);
                    });
            });
        });
    </script>
@endpush
