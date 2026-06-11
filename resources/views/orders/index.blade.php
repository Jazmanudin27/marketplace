@extends('layouts.app')
@section('title', 'Daftar Pesanan')
@section('page-title', 'Manajemen Pesanan')
@section('content')
    <div class="dashboard-card">
        <div class="card-header-line">
            <h3><i class="fas fa-shopping-cart"></i> Semua Pesanan</h3>
            <div style="display: flex; gap: 10px;">
                <button type="submit" form="mass-print-form" class="btn-primary-sm" style="background:#28a745;"><i
                        class="fas fa-print"></i> Cetak Massal</button>
                <form action="{{ route('orders.sync') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-primary-sm" style="background:var(--primary);"><i
                            class="fas fa-sync"></i> Tarik Pesanan Terbaru</button>
                </form>
            </div>
        </div>
        <div class="table-wrapper">
            <form id="mass-print-form" action="{{ route('orders.mass_print') }}" method="POST" target="_blank">
                @csrf
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="check-all"></th>
                            <th>Invoice / ID</th>
                            <th>Pembeli</th>
                            <th>Toko</th>
                            <th>Channel</th>
                            <th>Total</th>
                            <th>Kurir</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                        class="order-checkbox"></td>
                                <td class="mono">
                                    <a href="{{ route('orders.show', $order) }}"
                                        style="color:var(--primary); font-weight:bold; text-decoration:none;">
                                        {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                    </a>
                                </td>
                                <td>{{ $order->buyer_name ?? '-' }}</td>
                                <td>{{ $order->store->store_name }}</td>
                                <td><span
                                        class="channel-tag channel-{{ $order->store->channel->code }}">{{ $order->store->channel->name }}</span>
                                </td>
                                <td class="mono fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td>{{ $order->courier ?? '-' }}</td>
                                <td>{{ $order->order_date->format('d/m/Y H:i') }}</td>
                                <td><span
                                        class="badge badge-{{ $order->status_badge }}">{{ str_replace('_', ' ', $order->order_status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding:2rem;">Belum ada pesanan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
        <div style="margin-top:1rem;">{{ $orders->links() }}</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('check-all');
            const checkboxes = document.querySelectorAll('.order-checkbox');

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = checkAll.checked);
                });
            }
        });
    </script>
@endsection
