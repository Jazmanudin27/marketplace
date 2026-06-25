@extends('layouts.app')
@section('title', 'Laporan Profit')
@section('page-title', 'Laporan Profit per Pesanan')

@section('content')
    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-3">

        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(99,102,241,.18),rgba(99,102,241,.06)); border-color:rgba(99,102,241,.35);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-receipt"></i> Total Transaksi
                </div>
                <div class="fw-bold fs-4 text-white">{{ number_format($totalCount) }}</div>
                <div class="small text-muted mt-1">Online + Offline</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(59,130,246,.06)); border-color:rgba(59,130,246,.35);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-money-bill-wave"></i> Total Pendapatan Bersih
                </div>
                <div class="fw-bold fs-4 text-info">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                <div class="small text-muted mt-1">Pencairan bersih + Offline</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(239,68,68,.18),rgba(239,68,68,.06)); border-color:rgba(239,68,68,.35);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-box"></i> Total HPP (COGS)
                </div>
                <div class="fw-bold fs-4 text-danger">Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
                <div class="small text-muted mt-1">Harga pokok produk terjual</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100"
                style="background:linear-gradient(135deg,rgba(16,185,129,.18),rgba(16,185,129,.06)); border-color:rgba(16,185,129,.35);">
                <div class="text-muted form-label-sm fw-semibold text-uppercase mb-2">
                    <i class="fas fa-chart-line"></i> Laba Bersih (Net Profit)
                </div>
                <div class="fw-bold fs-4" style="color:{{ $totalProfit >= 0 ? '#34d399' : '#f87171' }};">
                    {{ $totalProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($totalProfit), 0, ',', '.') }}
                </div>
                <div class="small text-muted mt-1">
                    Margin rata-rata: <strong
                        style="color:{{ $avgMargin >= 0 ? '#34d399' : '#f87171' }};">{{ $avgMargin }}%</strong>
                </div>
            </div>
        </div>

    </div>

    {{-- Filter Bar --}}
    <div class="dashboard-card mb-3 py-3">
        <form method="GET" action="{{ route('profit.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Toko</label>
                    <select name="store_id" class="form-select form-select-sm">
                        <option value="">Semua Toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }} ({{ $store->channel->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Status Pesanan</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="COMPLETED" {{ $status === 'COMPLETED' ? 'selected' : '' }}>Selesai (COMPLETED)
                        </option>
                        <option value="DELIVERED" {{ $status === 'DELIVERED' ? 'selected' : '' }}>Terkirim (DELIVERED)
                        </option>
                        <option value="SHIPPED" {{ $status === 'SHIPPED' ? 'selected' : '' }}>Dikirim (SHIPPED)</option>
                        <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>Semua (kecuali Batal)</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    @if (request()->anyFilled(['date_from', 'date_to', 'store_id', 'status']))
                        <a href="{{ route('profit.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- 1. STATEMENT LABA RUGI KONSOLIDASI --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-primary"></i> Laporan Laba Rugi Konsolidasi
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Laporan kinerja penjualan gabungan online dan offline
                        </p>
                    </div>
                    <span class="badge badge-secondary p-2">Metode Cash-Basis</span>
                </div>

                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ELEMEN LABA RUGI</th>
                                <th class="text-end" style="width: 25%">ONLINE (MARKETPLACE)</th>
                                <th class="text-end" style="width: 25%">OFFLINE (TOKO FISIK)</th>
                                <th class="text-end pe-3"
                                    style="width: 25%; background: rgba(16, 185, 129, 0.08); color: #34d399 !important;">
                                    KONSOLIDASI (TOTAL)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold ps-3"><i class="fas fa-arrow-up text-success me-2"></i> Pendapatan
                                    Kotor (Gross Sales)</td>
                                <td class="text-end font-monospace">Rp {{ number_format($onlineOmzet, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($offlineOmzet, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-success pe-3"
                                    style="background: rgba(16, 185, 129, 0.02);">Rp
                                    {{ number_format($onlineOmzet + $offlineOmzet, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-3"><i class="fas fa-percentage text-danger me-2"></i> Potongan &
                                    Diskon Penjualan</td>
                                <td class="text-end font-monospace text-muted">- Rp
                                    {{ number_format($onlineDiscount, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">- Rp
                                    {{ number_format($offlineDiscount, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted pe-3"
                                    style="background: rgba(16, 185, 129, 0.02);">- Rp
                                    {{ number_format($onlineDiscount + $offlineDiscount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-3"><i class="fas fa-calculator text-danger me-2"></i> Biaya Admin &
                                    Ongkir Marketplace</td>
                                <td class="text-end font-monospace text-muted">- Rp
                                    {{ number_format($onlineFee, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-muted">—</td>
                                <td class="text-end font-monospace text-muted pe-3"
                                    style="background: rgba(16, 185, 129, 0.02);">-
                                    Rp {{ number_format($onlineFee, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-active">
                                <td class="fw-bold ps-3"><i class="fas fa-wallet text-primary me-2"></i> Pendapatan Bersih
                                    (Net Revenue / Cash-In)</td>
                                <td class="text-end font-monospace fw-semibold">Rp
                                    {{ number_format($onlineNet, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-semibold">Rp
                                    {{ number_format($offlineNet, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-primary pe-3"
                                    style="background: rgba(16, 185, 129, 0.02);">Rp
                                    {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold ps-3"><i class="fas fa-box text-danger me-2"></i> Harga Pokok
                                    Penjualan (HPP / COGS)</td>
                                <td class="text-end font-monospace text-danger">- Rp
                                    {{ number_format($onlineHpp, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-danger">- Rp
                                    {{ number_format($offlineHpp, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-danger pe-3"
                                    style="background: rgba(16, 185, 129, 0.02);">- Rp
                                    {{ number_format($totalHpp, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="fs-6" style="background: rgba(16, 185, 129, 0.08);">
                                <td class="fw-bold ps-3" style="color: #34d399;"><i class="fas fa-chart-line me-2"></i>
                                    LABA BERSIH (NET PROFIT)</td>
                                <td class="text-end font-monospace fw-bold text-success">Rp
                                    {{ number_format($onlineProfit, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-success">Rp
                                    {{ number_format($offlineProfit, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-success pe-3"
                                    style="background: rgba(16, 185, 129, 0.1);">Rp
                                    {{ number_format($totalProfit, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="fs-7">
                                <td class="fw-semibold text-muted ps-3"><i class="fas fa-chart-pie me-2"></i> Margin Laba
                                    Bersih (%)</td>
                                <td class="text-end text-muted">
                                    {{ $onlineNet > 0 ? round(($onlineProfit / $onlineNet) * 100, 2) : 0 }}%</td>
                                <td class="text-end text-muted">
                                    {{ $offlineNet > 0 ? round(($offlineProfit / $offlineNet) * 100, 2) : 0 }}%</td>
                                <td class="text-end fw-bold text-success pe-3"
                                    style="background: rgba(16, 185, 129, 0.02);">{{ $avgMargin }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. TABBED DETAILS FOR TRANSACTIONS --}}
    <ul class="nav nav-tabs mb-3" id="profitTabs" role="tablist"
        style="border-bottom:1px solid rgba(255,255,255,0.08) !important;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="online-details-tab" data-bs-toggle="tab"
                data-bs-target="#online-details-pane" type="button" role="tab" aria-controls="online-details-pane"
                aria-selected="true" style="font-weight:600;">
                <i class="fas fa-shopping-bag me-2 text-primary"></i> Rincian Transaksi Online (Marketplace)
                <span class="badge bg-primary ms-1" style="font-size:0.7rem;">{{ $orders->total() }}</span>
            </button>
        </li>
        @if (!$storeId)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="offline-details-tab" data-bs-toggle="tab"
                    data-bs-target="#offline-details-pane" type="button" role="tab"
                    aria-controls="offline-details-pane" aria-selected="false" style="font-weight:600;">
                    <i class="fas fa-store-slash me-2 text-success"></i> Rincian Penjualan Offline
                    <span class="badge bg-success ms-1" style="font-size:0.7rem;">{{ $allOffline->count() }}</span>
                </button>
            </li>
        @endif
    </ul>

    <div class="tab-content" id="profitTabsContent">

        {{-- TAB 1: DETIL ONLINE --}}
        <div class="tab-pane fade show active" id="online-details-pane" role="tabpanel"
            aria-labelledby="online-details-tab">
            <div class="dashboard-card">
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Invoice</th>
                                <th>Toko</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="text-end" style="width:11%">Omzet</th>
                                <th class="text-end" style="width:11%">Escrow</th>
                                <th class="text-end" style="width:11%">HPP</th>
                                <th class="text-end" style="width:12%">Net Profit</th>
                                <th class="text-center pe-3" style="width:12%">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $hpp = $order->hpp_total;
                                    $profit = $order->net_profit;
                                    $mg = $order->profit_margin;
                                    $rowStyle = '';
                                    if ($profit < 0) {
                                        $rowStyle = 'background: rgba(239, 68, 68, 0.04) !important;';
                                    }
                                @endphp
                                <tr style="{{ $rowStyle }}">
                                    <td class="ps-3 font-monospace">
                                        <a href="{{ route('orders.show', $order) }}"
                                            class="fw-semibold text-decoration-none text-primary">
                                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                        </a>
                                        <button class="btn btn-action-sm btn-outline-info ms-2 py-0 px-1" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#fb-detail-{{ $order->id }}"
                                            aria-expanded="false" aria-controls="fb-detail-{{ $order->id }}"
                                            style="font-size: 0.65rem; border-color: rgba(96, 165, 250, 0.4); color: #60a5fa;">
                                            <i class="fas fa-eye me-1"></i>Detail
                                        </button>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-white" style="font-size:0.8rem;">
                                            {{ $order->store->store_name }}</div>
                                        <span class="channel-tag channel-{{ $order->store->channel->code }} mt-1"
                                            style="font-size:0.65rem;">
                                            {{ $order->store->channel->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $order->status_badge === 'success' ? 'badge-success' : ($order->status_badge === 'danger' ? 'badge-danger' : ($order->status_badge === 'warning' ? 'badge-warning' : 'badge-secondary')) }} px-2 py-1">
                                            {{ str_replace('_', ' ', $order->order_status) }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $order->order_date->format('d M Y') }}
                                    </td>
                                    <td class="text-end font-monospace small text-white">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace small text-info">
                                        Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace small text-danger">
                                        @if ($hpp > 0)
                                            Rp {{ number_format($hpp, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td
                                        class="text-end font-monospace fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $profit >= 0 ? '' : '-' }}Rp {{ number_format(abs($profit), 0, ',', '.') }}
                                    </td>
                                    <td class="text-center pe-3">
                                        @php
                                            $mgAbs = abs($mg);
                                            $mgW = min($mgAbs, 100);
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 justify-content-center">
                                            <div class="rounded-pill overflow-hidden bg-secondary bg-opacity-25"
                                                style="width:40px; height:6px;">
                                                <div class="h-100 rounded-pill"
                                                    style="width:{{ $mgW }}%; background:{{ $profit >= 0 ? '#34d399' : '#f87171' }};">
                                                </div>
                                            </div>
                                            <span
                                                class="small fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}"
                                                style="min-width:32px; text-align:right;">
                                                {{ $mg }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                @php
                                    $fb = $order->financial_breakdown ?? [];
                                @endphp
                                <tr class="collapse" id="fb-detail-{{ $order->id }}"
                                    style="background: rgba(255, 255, 255, 0.015);">
                                    <td colspan="9" class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="fw-bold mb-1 text-white" style="font-size:0.8rem;"><i
                                                        class="fas fa-info-circle me-1"></i> Rincian Pendapatan</div>
                                                <table class="table table-sm table-borderless text-muted mb-0"
                                                    style="font-size:0.75rem;">
                                                    <tr>
                                                        <td>Harga Produk (Product Amount)</td>
                                                        <td class="text-end text-white mono">Rp
                                                            {{ number_format($fb['original_price'] ?? $order->total_amount, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Ongkos Kirim (Shipping Fee)</td>
                                                        <td class="text-end text-white mono">Rp
                                                            {{ number_format($fb['actual_shipping_fee'] ?? ($fb['buyer_paid_shipping_fee'] ?? $order->shipping_fee), 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="fw-bold mb-1 text-white" style="font-size:0.8rem;"><i
                                                        class="fas fa-percentage me-1"></i> Potongan & Komisi</div>
                                                <table class="table table-sm table-borderless text-muted mb-0"
                                                    style="font-size:0.75rem;">
                                                    <tr>
                                                        <td>Komisi Platform (Platform Commission)</td>
                                                        <td class="text-end text-danger mono">- Rp
                                                            {{ number_format(($fb['service_fee'] ?? 0) + ($fb['seller_transaction_fee'] ?? 0) ?: $order->marketplace_fee, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Komisi Affiliate (Affiliate Commission)</td>
                                                        <td class="text-end text-danger mono">- Rp
                                                            {{ number_format($fb['commission_fee'] ?? 0, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="fw-bold mb-1 text-white" style="font-size:0.8rem;"><i
                                                        class="fas fa-tags me-1"></i> Voucher & Penyesuaian</div>
                                                <table class="table table-sm table-borderless text-muted mb-0"
                                                    style="font-size:0.75rem;">
                                                    <tr>
                                                        <td>Subsidi Voucher (Voucher Subsidy)</td>
                                                        <td class="text-end text-success mono">+ Rp
                                                            {{ number_format($fb['voucher_from_shopee'] ?? 0, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Penyesuaian (Adjustment)</td>
                                                        <td
                                                            class="text-end mono {{ ($fb['adjustment_amount'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ ($fb['adjustment_amount'] ?? 0) < 0 ? '-' : '+' }} Rp
                                                            {{ number_format(abs($fb['adjustment_amount'] ?? 0), 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="fas fa-shopping-bag fa-3x mb-3 d-block opacity-25"></i>
                                        <div class="fw-semibold text-light mb-1">Tidak ada data pesanan online untuk filter
                                            ini.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $orders->links() }}</div>
            </div>
        </div>

        @if (!$storeId)
            {{-- TAB 2: DETIL OFFLINE --}}
            <div class="tab-pane fade" id="offline-details-pane" role="tabpanel" aria-labelledby="offline-details-tab">
                <div class="dashboard-card">
                    <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                        <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">No. Transaksi</th>
                                    <th>Pembeli</th>
                                    <th>Metode</th>
                                    <th>Tanggal</th>
                                    <th class="text-end" style="width:14%">Subtotal</th>
                                    <th class="text-end" style="width:14%">Diskon</th>
                                    <th class="text-end" style="width:14%">HPP</th>
                                    <th class="text-end pe-3" style="width:15%">Net Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allOffline as $sale)
                                    @php
                                        $saleHpp = $sale->hpp_total;
                                        $saleProfit = $sale->net_profit;
                                        $rowStyle = '';
                                        if ($saleProfit < 0) {
                                            $rowStyle = 'background: rgba(239, 68, 68, 0.04) !important;';
                                        }
                                    @endphp
                                    <tr style="{{ $rowStyle }}">
                                        <td class="ps-3 font-monospace">
                                            <a href="{{ route('offline_sales.show', $sale) }}"
                                                class="fw-semibold text-decoration-none text-primary">
                                                {{ $sale->sale_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-white" style="font-size:0.8rem;">
                                                {{ $sale->buyer_name ?: '(Umum)' }}</div>
                                            @if ($sale->buyer_phone)
                                                <span class="text-muted small"
                                                    style="font-size:0.65rem;">{{ $sale->buyer_phone }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-secondary px-2 py-1">{{ $sale->payment_method_label }}</span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $sale->sold_at ? $sale->sold_at->format('d M Y') : '-' }}
                                        </td>
                                        <td class="text-end font-monospace small text-white">
                                            Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace small text-danger">
                                            @if ($sale->discount_amount > 0)
                                                - Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace small text-danger">
                                            Rp {{ number_format($saleHpp, 0, ',', '.') }}
                                        </td>
                                        <td
                                            class="text-end font-monospace fw-bold pe-3 {{ $saleProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                            Rp {{ number_format($saleProfit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-store-slash fa-3x mb-3 d-block opacity-25"></i>
                                            <div class="fw-semibold text-light mb-1">Tidak ada transaksi offline untuk
                                                periode ini.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
