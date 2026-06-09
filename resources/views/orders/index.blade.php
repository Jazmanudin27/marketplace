@extends('layouts.app')
@section('title', 'Daftar Pesanan')
@section('page-title', 'Manajemen Pesanan')
@section('content')
<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-shopping-cart"></i> Semua Pesanan</h3>
        <form action="{{ route('orders.sync') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn-primary-sm" style="background:var(--primary);"><i class="fas fa-sync"></i> Tarik Pesanan Terbaru</button>
        </form>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice / ID</th><th>Pembeli</th><th>Toko</th><th>Channel</th>
                    <th>Total</th><th>Kurir</th><th>Tanggal</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="mono">
                        <a href="{{ route('orders.show', $order) }}" style="color:var(--primary); font-weight:bold; text-decoration:none;">
                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                        </a>
                    </td>
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
