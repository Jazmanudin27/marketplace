@extends('layouts.app')
@section('title', 'Daftar Pesanan')
@section('page-title', 'Manajemen Pesanan')
@section('content')
<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-shopping-bag"></i> Semua Pesanan</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice</th><th>Pembeli</th><th>Toko</th><th>Channel</th>
                    <th>Total</th><th>Kurir</th><th>Tanggal</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="mono">{{ $order->invoice_number ?? $order->order_marketplace_id }}</td>
                    <td>{{ $order->buyer_name ?? '-' }}</td>
                    <td>{{ $order->store->store_name }}</td>
                    <td><span class="channel-tag channel-{{ $order->store->channel->code }}">{{ $order->store->channel->name }}</span></td>
                    <td class="mono fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td>{{ $order->courier ?? '-' }}</td>
                    <td>{{ $order->order_date->format('d/m/Y H:i') }}</td>
                    <td><span class="badge badge-{{ $order->status_badge }}">{{ str_replace('_', ' ', $order->order_status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">Belum ada pesanan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $orders->links() }}</div>
</div>
@endsection
