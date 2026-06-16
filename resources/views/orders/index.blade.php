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

        <!-- Filter Section -->
        <div
            style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1rem; margin-top: 1rem; margin-bottom: 1rem;">
            <form method="GET" action="{{ route('orders.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label"
                            style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem;">Channel</label>
                        <select name="channel_id" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua Channel</option>
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->id }}"
                                    {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                    {{ $channel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"
                            style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem;">Toko</label>
                        <select name="store_id" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua Toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}"
                                    {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"
                            style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem;">Kurir</label>
                        <select name="courier" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua Kurir</option>
                            @foreach ($couriers as $courier)
                                <option value="{{ $courier }}" {{ request('courier') == $courier ? 'selected' : '' }}>
                                    {{ $courier }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"
                            style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem;">Status</label>
                        <select name="status" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"
                            style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem;">Rentang
                            Tanggal</label>
                        <div class="d-flex gap-2">
                            <input type="date" name="start_date" class="form-control form-control-sm form-control-dark"
                                value="{{ request('start_date') }}" placeholder="Dari">
                            <input type="date" name="end_date" class="form-control form-control-sm form-control-dark"
                                value="{{ request('end_date') }}" placeholder="Ke">
                        </div>
                    </div>
                    <div class="col-md-1 text-end">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <button type="submit" class="btn-primary-sm"
                                style="height: 31px; display: inline-flex; justify-content: center; align-items: center; font-size: 0.78rem; padding: 0 0.8rem;"
                                title="Filter">
                                <i class="fas fa-filter"></i>
                            </button>
                            @if (request()->anyFilled(['channel_id', 'store_id', 'courier', 'status', 'start_date', 'end_date']))
                                <a href="{{ route('orders.index') }}" class="btn-primary-sm"
                                    style="background: var(--bg-card2); border: 1px solid var(--border); color: var(--text-primary); height: 31px; padding: 0 0.65rem; display: inline-flex; align-items: center; justify-content: center;"
                                    title="Reset Filter">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
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
        <div style="margin-top:1rem;">{{ $orders->links('pagination::bootstrap-5') }}</div>
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
