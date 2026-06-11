@extends('layouts.app')
@section('title', 'Laporan Profit')
@section('page-title', 'Laporan Profit per Pesanan')

@section('content')
{{-- KPI Summary Cards --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem;">

    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(99,102,241,.18),rgba(99,102,241,.06)); border-color:rgba(99,102,241,.35);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-receipt"></i> Total Transaksi
        </div>
        <div style="font-size:1.6rem; font-weight:800; color:var(--text-primary);">{{ number_format($totalCount) }}</div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">Online + Offline</div>
    </div>

    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(59,130,246,.06)); border-color:rgba(59,130,246,.35);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-money-bill-wave"></i> Total Pendapatan Bersih
        </div>
        <div style="font-size:1.3rem; font-weight:800; color:#60a5fa;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">Pencairan bersih + Offline</div>
    </div>

    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(239,68,68,.18),rgba(239,68,68,.06)); border-color:rgba(239,68,68,.35);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-box"></i> Total HPP (COGS)
        </div>
        <div style="font-size:1.3rem; font-weight:800; color:#f87171;">Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">Harga pokok produk terjual</div>
    </div>

    <div class="dashboard-card" style="background:linear-gradient(135deg,rgba(16,185,129,.18),rgba(16,185,129,.06)); border-color:rgba(16,185,129,.35);">
        <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:.5rem;">
            <i class="fas fa-chart-line"></i> Laba Bersih (Net Profit)
        </div>
        <div style="font-size:1.3rem; font-weight:800; color:{{ $totalProfit >= 0 ? '#34d399' : '#f87171' }};">
            {{ $totalProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($totalProfit), 0, ',', '.') }}
        </div>
        <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:.25rem;">
            Margin rata-rata: <strong style="color:{{ $avgMargin >= 0 ? '#34d399' : '#f87171' }};">{{ $avgMargin }}%</strong>
        </div>
    </div>

</div>

{{-- Filter Bar --}}
<div class="dashboard-card" style="margin-bottom:1rem;">
    <form method="GET" action="{{ route('profit.index') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;">
        <div style="flex:1; min-width:140px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm form-control-dark">
        </div>
        <div style="flex:1; min-width:140px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm form-control-dark">
        </div>
        <div style="flex:1; min-width:160px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Toko</label>
            <select name="store_id" class="form-select form-select-sm form-select-dark">
                <option value="">Semua Toko</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                        {{ $store->store_name }} ({{ $store->channel->name }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="flex:1; min-width:150px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Status Pesanan</label>
            <select name="status" class="form-select form-select-sm form-select-dark">
                <option value="COMPLETED" {{ $status === 'COMPLETED' ? 'selected' : '' }}>Selesai (COMPLETED)</option>
                <option value="DELIVERED" {{ $status === 'DELIVERED' ? 'selected' : '' }}>Terkirim (DELIVERED)</option>
                <option value="SHIPPED"   {{ $status === 'SHIPPED' ? 'selected' : '' }}>Dikirim (SHIPPED)</option>
                <option value="ALL"       {{ $status === 'ALL' ? 'selected' : '' }}>Semua (kecuali Batal)</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </div>
        <div>
            <a href="{{ route('profit.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-undo me-1"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- 1. STATEMENT LABA RUGI KONSOLIDASI --}}
<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="dashboard-card">
            <div class="card-header-line">
                <h3><i class="fas fa-file-invoice-dollar text-primary"></i> Laporan Laba Rugi Konsolidasi</h3>
                <span class="badge bg-secondary p-2" style="font-size:.72rem;">Metode Cash-Basis</span>
            </div>
            
            <div class="table-responsive mt-3">
                <table class="table table-hover table-bordered border-secondary mb-0" style="color: var(--text-primary); border-color: rgba(255,255,255,0.06) !important;">
                    <thead style="background: rgba(255, 255, 255, 0.03);">
                        <tr>
                            <th class="py-3 px-3 fs-7" style="color: var(--text-muted);">ELEMEN LABA RUGI</th>
                            <th class="text-end py-3 px-3 fs-7" style="width: 25%; color: var(--text-muted);">ONLINE (MARKETPLACE)</th>
                            <th class="text-end py-3 px-3 fs-7" style="width: 25%; color: var(--text-muted);">OFFLINE (TOKO FISIK)</th>
                            <th class="text-end py-3 px-3 fs-7" style="width: 25%; background: rgba(16, 185, 129, 0.05); color: #34d399;">KONSOLIDASI (TOTAL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold px-3"><i class="fas fa-arrow-up text-success me-2"></i> Pendapatan Kotor (Gross Sales)</td>
                            <td class="text-end mono px-3">Rp {{ number_format($onlineOmzet, 0, ',', '.') }}</td>
                            <td class="text-end mono px-3">Rp {{ number_format($offlineOmzet, 0, ',', '.') }}</td>
                            <td class="text-end mono fw-bold text-success px-3" style="background: rgba(16, 185, 129, 0.02);">Rp {{ number_format($onlineOmzet + $offlineOmzet, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted px-3"><i class="fas fa-percentage text-danger me-2" style="font-size: 0.8rem;"></i> Potongan & Diskon Penjualan</td>
                            <td class="text-end mono text-muted px-3">- Rp {{ number_format($onlineDiscount, 0, ',', '.') }}</td>
                            <td class="text-end mono text-muted px-3">- Rp {{ number_format($offlineDiscount, 0, ',', '.') }}</td>
                            <td class="text-end mono text-muted px-3" style="background: rgba(16, 185, 129, 0.02);">- Rp {{ number_format($onlineDiscount + $offlineDiscount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted px-3"><i class="fas fa-calculator text-danger me-2" style="font-size: 0.8rem;"></i> Biaya Admin & Ongkir Marketplace</td>
                            <td class="text-end mono text-muted px-3">- Rp {{ number_format($onlineFee, 0, ',', '.') }}</td>
                            <td class="text-end mono text-muted px-3">-</td>
                            <td class="text-end mono text-muted px-3" style="background: rgba(16, 185, 129, 0.02);">- Rp {{ number_format($onlineFee, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-active" style="background: rgba(255, 255, 255, 0.015);">
                            <td class="fw-bold px-3"><i class="fas fa-wallet text-primary me-2"></i> Pendapatan Bersih (Net Revenue / Cash-In)</td>
                            <td class="text-end mono fw-semibold px-3">Rp {{ number_format($onlineNet, 0, ',', '.') }}</td>
                            <td class="text-end mono fw-semibold px-3">Rp {{ number_format($offlineNet, 0, ',', '.') }}</td>
                            <td class="text-end mono fw-bold text-primary px-3" style="background: rgba(16, 185, 129, 0.02);">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-3"><i class="fas fa-box text-danger me-2"></i> Harga Pokok Penjualan (HPP / COGS)</td>
                            <td class="text-end mono text-danger px-3">- Rp {{ number_format($onlineHpp, 0, ',', '.') }}</td>
                            <td class="text-end mono text-danger px-3">- Rp {{ number_format($offlineHpp, 0, ',', '.') }}</td>
                            <td class="text-end mono fw-bold text-danger px-3" style="background: rgba(16, 185, 129, 0.02);">- Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fs-6" style="border-top: 2px double var(--border); background: rgba(16, 185, 129, 0.08);">
                            <td class="fw-bold px-3" style="color: #34d399;"><i class="fas fa-chart-line me-2"></i> LABA BERSIH (NET PROFIT)</td>
                            <td class="text-end mono fw-bold text-success px-3">Rp {{ number_format($onlineProfit, 0, ',', '.') }}</td>
                            <td class="text-end mono fw-bold text-success px-3">Rp {{ number_format($offlineProfit, 0, ',', '.') }}</td>
                            <td class="text-end mono fw-bold text-success px-3" style="background: rgba(16, 185, 129, 0.1);">Rp {{ number_format($totalProfit, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fs-7">
                            <td class="fw-semibold text-muted px-3"><i class="fas fa-chart-pie me-2"></i> Margin Laba Bersih (%)</td>
                            <td class="text-end text-muted px-3">{{ $onlineNet > 0 ? round(($onlineProfit / $onlineNet) * 100, 2) : 0 }}%</td>
                            <td class="text-end text-muted px-3">{{ $offlineNet > 0 ? round(($offlineProfit / $offlineNet) * 100, 2) : 0 }}%</td>
                            <td class="text-end fw-bold text-success px-3" style="background: rgba(16, 185, 129, 0.02);">{{ $avgMargin }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 2. TABBED DETAILS FOR TRANSACTIONS --}}
<ul class="nav nav-tabs mb-3" id="profitTabs" role="tablist" style="border-bottom:1px solid var(--border);">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="online-details-tab" data-bs-toggle="tab" data-bs-target="#online-details-pane" type="button" role="tab" aria-controls="online-details-pane" aria-selected="true" style="font-weight:600;">
            <i class="fas fa-shopping-bag me-2 text-primary"></i> Rincian Transaksi Online (Marketplace)
            <span class="badge bg-primary ms-1" style="font-size:0.7rem;">{{ $orders->total() }}</span>
        </button>
    </li>
    @if(!$storeId)
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="offline-details-tab" data-bs-toggle="tab" data-bs-target="#offline-details-pane" type="button" role="tab" aria-controls="offline-details-pane" aria-selected="false" style="font-weight:600;">
            <i class="fas fa-store-slash me-2 text-success"></i> Rincian Penjualan Offline
            <span class="badge bg-success ms-1" style="font-size:0.7rem;">{{ $allOffline->count() }}</span>
        </button>
    </li>
    @endif
</ul>

<div class="tab-content" id="profitTabsContent">
    
    {{-- TAB 1: DETIL ONLINE --}}
    <div class="tab-pane fade show active" id="online-details-pane" role="tabpanel" aria-labelledby="online-details-tab">
        <div class="dashboard-card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Toko</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th style="text-align:right;">Omzet</th>
                            <th style="text-align:right;">Escrow</th>
                            <th style="text-align:right;">HPP</th>
                            <th style="text-align:right;">Net Profit</th>
                            <th style="text-align:center;">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        @php
                            $hpp    = $order->hpp_total;
                            $profit = $order->net_profit;
                            $mg     = $order->profit_margin;
                            $profitBg = $profit >= 0
                                ? 'rgba(16,185,129,.07)'
                                : 'rgba(239,68,68,.07)';
                            $profitCol = $profit >= 0 ? '#34d399' : '#f87171';
                        @endphp
                        <tr style="background:{{ $profitBg }};">
                            <td class="mono">
                                <a href="{{ route('orders.show', $order) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                </a>
                            </td>
                            <td>
                                <div style="font-size:0.82rem; font-weight:500;">{{ $order->store->store_name }}</div>
                                <span class="channel-tag channel-{{ $order->store->channel->code }}" style="font-size:0.68rem;">{{ $order->store->channel->name }}</span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $order->status_badge }}">{{ str_replace('_',' ',$order->order_status) }}</span>
                            </td>
                            <td style="font-size:0.8rem; color:var(--text-secondary);">
                                {{ $order->order_date->format('d M Y') }}
                            </td>
                            <td class="mono" style="text-align:right; font-size:0.82rem;">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="mono" style="text-align:right; font-size:0.82rem; color:#60a5fa;">
                                Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                            </td>
                            <td class="mono" style="text-align:right; font-size:0.82rem; color:#f87171;">
                                @if($hpp > 0)
                                    Rp {{ number_format($hpp, 0, ',', '.') }}
                                @else
                                    <span style="color:var(--text-secondary); font-size:0.75rem;">-</span>
                                @endif
                            </td>
                            <td class="mono fw-bold" style="text-align:right; font-size:0.9rem; color:{{ $profitCol }};">
                                {{ $profit >= 0 ? '' : '-' }}Rp {{ number_format(abs($profit), 0, ',', '.') }}
                            </td>
                            <td style="text-align:center;">
                                @php
                                    $mgAbs = abs($mg);
                                    $mgW   = min($mgAbs, 100);
                                @endphp
                                <div style="display:flex; align-items:center; gap:.4rem; justify-content:center;">
                                    <div style="width:52px; height:6px; border-radius:3px; background:var(--border); overflow:hidden;">
                                        <div style="width:{{ $mgW }}%; height:100%; background:{{ $profit >= 0 ? '#34d399' : '#f87171' }}; border-radius:3px; transition:width .3s;"></div>
                                    </div>
                                    <span style="font-size:0.78rem; font-weight:700; color:{{ $profitCol }}; min-width:36px; text-align:right;">
                                        {{ $mg }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                                Tidak ada data pesanan online untuk filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:1rem;">{{ $orders->links() }}</div>
        </div>
    </div>

    @if(!$storeId)
    {{-- TAB 2: DETIL OFFLINE --}}
    <div class="tab-pane fade" id="offline-details-pane" role="tabpanel" aria-labelledby="offline-details-tab">
        <div class="dashboard-card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Pembeli</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                            <th style="text-align:right;">Subtotal</th>
                            <th style="text-align:right;">Diskon</th>
                            <th style="text-align:right;">HPP</th>
                            <th style="text-align:right;">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allOffline as $sale)
                        @php
                            $saleHpp = $sale->hpp_total;
                            $saleProfit = $sale->net_profit;
                            $profitBg = $saleProfit >= 0 ? 'rgba(16,185,129,.07)' : 'rgba(239,68,68,.07)';
                            $profitCol = $saleProfit >= 0 ? '#34d399' : '#f87171';
                        @endphp
                        <tr style="background:{{ $profitBg }};">
                            <td class="mono">
                                <a href="{{ route('offline_sales.show', $sale) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    {{ $sale->sale_number }}
                                </a>
                            </td>
                            <td>
                                <div style="font-size:0.82rem; font-weight:500;">{{ $sale->buyer_name ?: '(Umum)' }}</div>
                                <span class="text-muted small" style="font-size:0.68rem;">{{ $sale->buyer_phone ?? '' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $sale->payment_method_label }}</span>
                            </td>
                            <td style="font-size:0.8rem; color:var(--text-secondary);">
                                {{ $sale->sold_at ? $sale->sold_at->format('d M Y') : '-' }}
                            </td>
                            <td class="mono" style="text-align:right; font-size:0.82rem;">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="mono text-danger" style="text-align:right; font-size:0.82rem;">
                                @if($sale->discount_amount > 0)
                                    - Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="mono" style="text-align:right; font-size:0.82rem; color:#f87171;">
                                Rp {{ number_format($saleHpp, 0, ',', '.') }}
                            </td>
                            <td class="mono fw-bold" style="text-align:right; font-size:0.9rem; color:{{ $profitCol }};">
                                Rp {{ number_format($saleProfit, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                                Tidak ada transaksi offline untuk periode ini.
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
