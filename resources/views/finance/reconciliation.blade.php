@extends('layouts.app')
@section('title', 'Rekonsiliasi Keuangan')
@section('page-title', 'Rekonsiliasi & Margin')
@section('content')

<div class="stats-grid mb-4">
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($totalNetPage, 0, ',', '.') }}</div>
            <div class="stat-label">Total Cair (Net Amount)</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card {{ $totalDiscrepancyPage > 0 ? 'stat-warning' : 'stat-primary' }}">
        <div class="stat-icon"><i class="fas fa-balance-scale"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($totalDiscrepancyPage, 0, ',', '.') }}</div>
            <div class="stat-label">Total Selisih (Discrepancy)</div>
        </div>
        <div class="stat-glow"></div>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-file-invoice-dollar"></i> Detail Pesanan Selesai (Completed)</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice / ID</th>
                    <th>Toko (Channel)</th>
                    <th>Penjualan (A)</th>
                    <th>Ongkir (B)</th>
                    <th>Biaya Admin (C)</th>
                    <th>Pencairan (Actual)</th>
                    <th>Selisih</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr style="{{ $order->has_discrepancy ? 'background-color: rgba(239, 68, 68, 0.08);' : '' }}">
                    <td class="mono">
                        <a href="{{ route('orders.show', $order) }}" style="color:var(--primary); font-weight:bold; text-decoration:none;">
                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                        </a><br>
                        <small class="text-muted">{{ $order->order_date->format('d/m/y H:i') }}</small>
                    </td>
                    <td>
                        {{ $order->store->store_name }}<br>
                        <span class="channel-tag channel-{{ $order->store->channel->code }}">{{ $order->store->channel->name }}</span>
                    </td>
                    <td class="mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td class="mono text-muted">- Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</td>
                    <td class="mono {{ $order->is_high_fee ? 'text-danger' : 'text-muted' }}">
                        - Rp {{ number_format($order->marketplace_fee, 0, ',', '.') }}<br>
                        <small>({{ $order->fee_percentage }}%)</small>
                    </td>
                    <td class="mono fw-bold" style="color: #28a745;">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</td>
                    
                    <td class="mono fw-bold" style="color: {{ $order->discrepancy_amount > 0 ? '#dc3545' : '#333' }};">
                        {{ $order->discrepancy_amount != 0 ? 'Rp '.number_format($order->discrepancy_amount, 0, ',', '.') : '-' }}
                    </td>
                    
                    <td>
                        @if($order->has_discrepancy)
                            <span class="badge badge-danger">Discrepancy</span>
                        @elseif($order->is_high_fee)
                            <span class="badge badge-warning">High Fee</span>
                        @else
                            <span class="badge badge-success">Matched</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">Belum ada pesanan yang selesai</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $orders->links() }}</div>
</div>
@endsection
