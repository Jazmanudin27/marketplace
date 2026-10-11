@extends('layouts.app')
@section('title', 'Laporan Profit')
@section('page-title', 'Laporan Profit per Pesanan')

@section('content')
    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-3">
        <!-- Card 1: Total Transaksi -->
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-receipt me-1"></i> Total Transaksi
                    </div>
                    <div class="fw-bold fs-5 text-dark">{{ number_format($totalCount) }}</div>
                    <div class="small text-muted mt-1">Online + Offline</div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Pendapatan Bersih -->
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-money-bill-wave me-1"></i> Omset Bersih (Net Revenue)
                    </div>
                    <div class="fw-bold fs-5 text-primary">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Pencairan + Offline</div>
                </div>
            </div>
        </div>

        <!-- Card 3: HPP (COGS) -->
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-box me-1"></i> Total HPP (COGS)
                    </div>
                    <div class="fw-bold fs-5 text-danger">Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Harga pokok produk</div>
                </div>
            </div>
        </div>

        <!-- Card 4: Laba Kotor (Gross Profit) -->
        <div class="col-6 col-md-6 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-chart-pie me-1"></i> Laba Kotor
                    </div>
                    <div class="fw-bold fs-5 text-warning">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">
                        Margin: <strong>{{ $avgMargin }}%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Laba Bersih Riil (Net Profit) -->
        <div class="col-6 col-md-6 col-lg">
            <div class="card h-100 border-0 shadow-sm bg-success text-white">
                <div class="card-body py-3 px-3">
                    <div class="text-white-50 small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-chart-line me-1"></i> Laba Bersih Riil
                    </div>
                    <div class="fw-bold fs-5 text-white">
                        {{ $realNetProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($realNetProfit), 0, ',', '.') }}
                    </div>
                    <div class="small text-white-50 mt-1">
                        Margin Bersih: <strong>{{ $realNetMargin }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('profit.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Toko</label>
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
                        <label class="form-label form-label-sm fw-semibold mb-1">Status Pesanan</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="COMPLETED" {{ $status === 'COMPLETED' ? 'selected' : '' }}>Selesai (COMPLETED)</option>
                            <option value="DELIVERED" {{ $status === 'DELIVERED' ? 'selected' : '' }}>Terkirim (DELIVERED)</option>
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
    </div>

    {{-- 1. STATEMENT LABA RUGI KONSOLIDASI --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-file-invoice-dollar me-2 text-info"></i>Laporan Laba Rugi Konsolidasi
                </h6>
                <small class="text-muted d-block mt-1">Laporan kinerja penjualan gabungan online dan offline</small>
            </div>
            <span class="badge bg-secondary">Metode Cash-Basis</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ELEMEN LABA RUGI</th>
                            <th class="text-end" style="width:25%">ONLINE (MARKETPLACE)</th>
                            <th class="text-end" style="width:25%">OFFLINE (TOKO FISIK)</th>
                            <th class="text-end pe-3" style="width:25%">KONSOLIDASI (TOTAL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold ps-3">
                                <i class="fas fa-arrow-up text-success me-2"></i>Pendapatan Kotor (Gross Sales)
                            </td>
                            <td class="text-end font-monospace small">Rp {{ number_format($onlineOmzet, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small">Rp {{ number_format($offlineOmzet, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-success pe-3">Rp {{ number_format($onlineOmzet + $offlineOmzet, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">
                                <i class="fas fa-percentage text-danger me-2"></i>Potongan & Diskon Penjualan
                            </td>
                            <td class="text-end font-monospace small text-muted">- Rp {{ number_format($onlineDiscount, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small text-muted">- Rp {{ number_format($offlineDiscount, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small text-muted pe-3">- Rp {{ number_format($onlineDiscount + $offlineDiscount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">
                                <i class="fas fa-calculator text-danger me-2"></i>Biaya Admin & Ongkir Marketplace
                            </td>
                            <td class="text-end font-monospace small text-muted">- Rp {{ number_format($onlineFee, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small text-muted">—</td>
                            <td class="text-end font-monospace small text-muted pe-3">- Rp {{ number_format($onlineFee, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold ps-3">
                                <i class="fas fa-wallet text-primary me-2"></i>Pendapatan Bersih (Net Revenue / Cash-In)
                            </td>
                            <td class="text-end font-monospace small fw-semibold">Rp {{ number_format($onlineNet, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-semibold">Rp {{ number_format($offlineNet, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-primary pe-3">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold ps-3">
                                <i class="fas fa-box text-danger me-2"></i>Harga Pokok Penjualan (HPP / COGS)
                            </td>
                            <td class="text-end font-monospace small text-danger">- Rp {{ number_format($onlineHpp, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small text-danger">- Rp {{ number_format($offlineHpp, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-danger pe-3">- Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold ps-3">
                                <i class="fas fa-chart-bar me-2"></i>LABA KOTOR (GROSS PROFIT)
                            </td>
                            <td class="text-end font-monospace small fw-bold text-dark">Rp {{ number_format($onlineProfit, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-dark">Rp {{ number_format($offlineProfit, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-dark pe-3">Rp {{ number_format($totalProfit, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 small">
                                <i class="fas fa-chart-pie me-2"></i>Margin Laba Kotor (%)
                            </td>
                            <td class="text-end small text-muted">{{ $onlineNet > 0 ? round(($onlineProfit / $onlineNet) * 100, 2) : 0 }}%</td>
                            <td class="text-end small text-muted">{{ $offlineNet > 0 ? round(($offlineProfit / $offlineNet) * 100, 2) : 0 }}%</td>
                            <td class="text-end small fw-bold pe-3">{{ $avgMargin }}%</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="bg-light fw-bold ps-3 small text-muted text-uppercase">Beban Operasional & Pengeluaran Non-HPP (Deductions)</td>
                        </tr>
                        <tr>
                            <td class="ps-3 text-muted">
                                <i class="fas fa-users-cog me-2"></i>Beban Gaji Karyawan (Payroll HRD)
                            </td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-danger font-monospace small pe-3">- Rp {{ number_format($totalPayroll, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3 text-muted">
                                <i class="fas fa-file-invoice me-2"></i>Beban Biaya Umum (Operational Expenses)
                            </td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-danger font-monospace small pe-3">- Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3 text-muted">
                                <i class="fas fa-ad me-2"></i>Beban Pemasaran & Iklan (Ad Spend)
                            </td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-danger font-monospace small pe-3">- Rp {{ number_format($totalAdSpend, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-secondary">
                            <td class="fw-bold ps-3">
                                <i class="fas fa-minus-circle me-2"></i>Total Beban Pengeluaran (Total Deductions)
                            </td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-danger font-monospace small fw-bold pe-3">- Rp {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold ps-3 text-success">
                                <i class="fas fa-chart-line me-2"></i>LABA BERSIH SEBENARNYA (REAL NET PROFIT)
                            </td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end text-muted font-monospace small">—</td>
                            <td class="text-end font-monospace small fw-bold text-success pe-3">Rp {{ number_format($realNetProfit, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-success-subtle">
                            <td class="fw-bold ps-3 text-success small">
                                <i class="fas fa-chart-area me-2"></i>Margin Laba Bersih Riil (%)
                            </td>
                            <td class="text-end text-muted small">—</td>
                            <td class="text-end text-muted small">—</td>
                            <td class="text-end small fw-bold text-success pe-3">{{ $realNetMargin }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ANALISIS SEGMEN DROPSHIP VS REGULER --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-shipping-fast me-2 text-warning"></i>Analisis Segmen: Dropship vs Reguler
                </h6>
                <small class="text-muted d-block mt-1">Perbandingan kinerja penjualan & profitabilitas antara pesanan dropship dan eceran biasa</small>
            </div>
            <span class="badge bg-warning text-dark">Segmentasi Kontributor</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">METRIK / ELEMEN SEGMEN</th>
                            <th class="text-end" style="width:25%">REGULER (NON-DROPSHIP)</th>
                            <th class="text-end" style="width:25%">DROPSHIP</th>
                            <th class="text-end pe-3" style="width:25%">TOTAL GABUNGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold ps-3">
                                <i class="fas fa-shopping-cart text-muted me-2"></i>Jumlah Transaksi
                            </td>
                            <td class="text-end font-monospace small">{{ number_format($totalRegCount) }}</td>
                            <td class="text-end font-monospace small">{{ number_format($totalDsCount) }}</td>
                            <td class="text-end font-monospace small fw-bold pe-3">{{ number_format($totalCount) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold ps-3">
                                <i class="fas fa-wallet text-primary me-2"></i>Pendapatan Bersih (Net Revenue)
                            </td>
                            <td class="text-end font-monospace small">Rp {{ number_format($totalRegRevenue, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small">Rp {{ number_format($totalDsRevenue, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-primary pe-3">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold ps-3">
                                <i class="fas fa-box text-danger me-2"></i>Harga Pokok Penjualan (HPP)
                            </td>
                            <td class="text-end font-monospace small text-danger">- Rp {{ number_format($totalRegHpp, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small text-danger">- Rp {{ number_format($totalDsHpp, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-danger pe-3">- Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold ps-3 text-success">
                                <i class="fas fa-chart-line me-2"></i>LABA BERSIH (NET PROFIT)
                            </td>
                            <td class="text-end font-monospace small fw-bold text-success">Rp {{ number_format($totalRegProfit, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-success">Rp {{ number_format($totalDsProfit, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-bold text-success pe-3">Rp {{ number_format($totalProfit, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 small">
                                <i class="fas fa-chart-pie me-2"></i>Margin Laba Bersih (%)
                            </td>
                            <td class="text-end small text-muted">{{ $regAvgMargin }}%</td>
                            <td class="text-end small text-muted">{{ $dsAvgMargin }}%</td>
                            <td class="text-end small fw-bold text-success pe-3">{{ $avgMargin }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 2. TABBED DETAILS FOR TRANSACTIONS --}}
    <ul class="nav nav-tabs mb-0" id="profitTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active small fw-semibold" id="online-details-tab" data-bs-toggle="tab"
                data-bs-target="#online-details-pane" type="button" role="tab">
                <i class="fas fa-shopping-bag me-1 text-primary"></i>Rincian Transaksi Online (Marketplace)
                <span class="badge bg-primary ms-1">{{ $orders->total() }}</span>
            </button>
        </li>
        @if (!$storeId)
            <li class="nav-item" role="presentation">
                <button class="nav-link small fw-semibold" id="offline-details-tab" data-bs-toggle="tab"
                    data-bs-target="#offline-details-pane" type="button" role="tab">
                    <i class="fas fa-store-slash me-1 text-success"></i>Rincian Penjualan Offline
                    <span class="badge bg-success ms-1">{{ $allOffline->count() }}</span>
                </button>
            </li>
        @endif
    </ul>

    <div class="tab-content" id="profitTabsContent">

        {{-- TAB 1: DETIL ONLINE --}}
        <div class="tab-pane fade show active" id="online-details-pane" role="tabpanel">
            <div class="card border-0 shadow-sm border-top-0 rounded-top-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
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
                                        $hpp    = $order->hpp_total;
                                        $profit = $order->net_profit;
                                        $mg     = $order->profit_margin;
                                    @endphp
                                    <tr class="{{ $profit < 0 ? 'table-danger' : '' }}">
                                        <td class="ps-3 font-monospace small">
                                            <a href="{{ route('orders.show', $order) }}"
                                                class="fw-semibold text-decoration-none text-primary">
                                                {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                            </a>
                                            <button class="btn btn-outline-secondary btn-sm ms-1 py-0 px-1" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#fb-detail-{{ $order->id }}"
                                                style="font-size:0.65rem;">
                                                <i class="fas fa-eye me-1"></i>Detail
                                            </button>
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold">{{ $order->store->store_name }}</div>
                                            <span class="badge bg-secondary bg-opacity-75" style="font-size:0.65rem;">
                                                {{ $order->store->channel->name }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($order->status_badge) {
                                                    'success' => 'bg-success',
                                                    'danger'  => 'bg-danger',
                                                    'warning' => 'bg-warning text-dark',
                                                    default   => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }} small">
                                                {{ str_replace('_', ' ', $order->order_status) }}
                                            </span>
                                        </td>
                                        <td class="small text-muted">{{ $order->order_date->format('d M Y') }}</td>
                                        <td class="text-end font-monospace small">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace small text-primary">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace small text-danger">
                                            @if ($hpp > 0)
                                                Rp {{ number_format($hpp, 0, ',', '.') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace small fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $profit >= 0 ? '' : '-' }}Rp {{ number_format(abs($profit), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center pe-3">
                                            @php
                                                $mgAbs = abs($mg);
                                                $mgW   = min($mgAbs, 100);
                                            @endphp
                                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                                <div class="progress" style="width:40px; height:6px;">
                                                    <div class="progress-bar {{ $profit >= 0 ? 'bg-success' : 'bg-danger' }}"
                                                        style="width:{{ $mgW }}%;"></div>
                                                </div>
                                                <span class="small fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}"
                                                    style="min-width:32px; text-align:right;">
                                                    {{ $mg }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>

                                    @php $fb = $order->financial_breakdown ?? []; @endphp
                                    <tr class="collapse bg-light" id="fb-detail-{{ $order->id }}">
                                        <td colspan="9" class="p-3">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <div class="fw-semibold mb-1 small"><i class="fas fa-info-circle me-1 text-info"></i>Rincian Pendapatan</div>
                                                    <table class="table table-sm table-borderless mb-0 small text-muted">
                                                        <tr>
                                                            <td>Harga Produk (Product Amount)</td>
                                                            <td class="text-end font-monospace">Rp {{ number_format($fb['original_price'] ?? $order->total_amount, 0, ',', '.') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Ongkos Kirim (Shipping Fee)</td>
                                                            <td class="text-end font-monospace">Rp {{ number_format($fb['actual_shipping_fee'] ?? ($fb['buyer_paid_shipping_fee'] ?? $order->shipping_fee), 0, ',', '.') }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="fw-semibold mb-1 small"><i class="fas fa-percentage me-1 text-danger"></i>Potongan & Komisi</div>
                                                    <table class="table table-sm table-borderless mb-0 small text-muted">
                                                        <tr>
                                                            <td>Komisi Platform (Platform Commission)</td>
                                                            <td class="text-end font-monospace text-danger">- Rp {{ number_format(($fb['service_fee'] ?? 0) + ($fb['seller_transaction_fee'] ?? 0) ?: $order->marketplace_fee, 0, ',', '.') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Komisi Affiliate (Affiliate Commission)</td>
                                                            <td class="text-end font-monospace text-danger">- Rp {{ number_format($fb['commission_fee'] ?? 0, 0, ',', '.') }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="fw-semibold mb-1 small"><i class="fas fa-tags me-1 text-primary"></i>Voucher & Penyesuaian</div>
                                                    <table class="table table-sm table-borderless mb-0 small text-muted">
                                                        <tr>
                                                            <td>Subsidi Voucher (Voucher Subsidy)</td>
                                                            <td class="text-end font-monospace text-success">+ Rp {{ number_format($fb['voucher_from_shopee'] ?? 0, 0, ',', '.') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Penyesuaian (Adjustment)</td>
                                                            <td class="text-end font-monospace {{ ($fb['adjustment_amount'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                                {{ ($fb['adjustment_amount'] ?? 0) < 0 ? '-' : '+' }} Rp {{ number_format(abs($fb['adjustment_amount'] ?? 0), 0, ',', '.') }}
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
                                            <div class="fw-semibold">Tidak ada data pesanan online untuk filter ini.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2">{{ $orders->links() }}</div>
                </div>
            </div>
        </div>

        @if (!$storeId)
            {{-- TAB 2: DETIL OFFLINE --}}
            <div class="tab-pane fade" id="offline-details-pane" role="tabpanel">
                <div class="card border-0 shadow-sm border-top-0 rounded-top-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
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
                                            $saleHpp    = $sale->hpp_total;
                                            $saleProfit = $sale->net_profit;
                                        @endphp
                                        <tr class="{{ $saleProfit < 0 ? 'table-danger' : '' }}">
                                            <td class="ps-3 font-monospace small">
                                                <a href="{{ route('offline_sales.show', $sale) }}"
                                                    class="fw-semibold text-decoration-none text-primary">
                                                    {{ $sale->sale_number }}
                                                </a>
                                            </td>
                                            <td class="small">
                                                <div class="fw-semibold">{{ $sale->buyer_name ?: '(Umum)' }}</div>
                                                @if ($sale->buyer_phone)
                                                    <span class="text-muted" style="font-size:0.7rem;">{{ $sale->buyer_phone }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary small">{{ $sale->payment_method_label }}</span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ $sale->sold_at ? $sale->sold_at->format('d M Y') : '-' }}
                                            </td>
                                            <td class="text-end font-monospace small">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
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
                                            <td class="text-end font-monospace small fw-bold pe-3 {{ $saleProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                                Rp {{ number_format($saleProfit, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-5">
                                                <i class="fas fa-store-slash fa-3x mb-3 d-block opacity-25"></i>
                                                <div class="fw-semibold">Tidak ada transaksi offline untuk periode ini.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- 1b. RINCIAN BIAYA OPERASIONAL & BAGAN DONUT --}}
    <div class="row g-3 mb-3">
        <!-- Breakdown Table -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark">Rincian Pengeluaran Operasional & Non-HPP</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                            <thead>
                                <tr class="table-light">
                                    <th class="ps-3">Kategori Beban</th>
                                    <th class="text-end pe-3">Jumlah (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-3 fw-semibold text-dark">Gaji Karyawan (Payroll HRD)</td>
                                    <td class="text-end font-monospace pe-3 text-dark">Rp {{ number_format($totalPayroll, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-dark">Gaji Karyawan (Beban Manual/Expense)</td>
                                    <td class="text-end font-monospace pe-3 text-dark">Rp {{ number_format($expensesBreakdown['expense_salary'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-dark">Sewa Tempat (Expense)</td>
                                    <td class="text-end font-monospace pe-3 text-dark">Rp {{ number_format($expensesBreakdown['expense_rent'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-dark">Utilitas & Operasional (Expense)</td>
                                    <td class="text-end font-monospace pe-3 text-dark">Rp {{ number_format($expensesBreakdown['expense_utilities'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-dark">Biaya Lain-lain (Expense)</td>
                                    <td class="text-end font-monospace pe-3 text-dark">Rp {{ number_format($expensesBreakdown['expense_other'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 fw-semibold text-dark">Beban Pemasaran & Iklan (Ad Spend)</td>
                                    <td class="text-end font-monospace pe-3 text-dark">Rp {{ number_format($totalAdSpend, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-secondary fw-bold">
                                    <td class="ps-3 text-dark">Total Beban</td>
                                    <td class="text-end font-monospace pe-3 text-danger">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donut Chart -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark">Bagan Kontribusi Pengeluaran</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center p-3" style="min-height: 250px;">
                    <div style="width: 100%; max-width: 220px; max-height: 220px;">
                        <canvas id="expensesContributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('expensesContributionChart').getContext('2d');
            
            const dataPayroll = {{ $totalPayroll + $expensesBreakdown['expense_salary'] }};
            const dataRent = {{ $expensesBreakdown['expense_rent'] }};
            const dataUtilities = {{ $expensesBreakdown['expense_utilities'] }};
            const dataOther = {{ $expensesBreakdown['expense_other'] }};
            const dataAd = {{ $totalAdSpend }};
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Gaji Karyawan', 'Sewa Tempat', 'Utilitas', 'Lain-lain', 'Iklan/Ads'],
                    datasets: [{
                        data: [dataPayroll, dataRent, dataUtilities, dataOther, dataAd],
                        backgroundColor: [
                            '#4f46e5', // indigo
                            '#f59e0b', // amber
                            '#10b981', // emerald
                            '#6b7280', // gray
                            '#ef4444'  // red
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
