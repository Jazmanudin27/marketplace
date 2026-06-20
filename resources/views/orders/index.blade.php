@extends('layouts.app')
@section('title', 'Daftar Pesanan')
@section('page-title', 'Manajemen Pesanan')
@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- ── Filter Card ───────────────────────────────────────────── --}}
            <div class="dashboard-card mb-3 py-3">
                <form method="GET" action="{{ route('orders.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-shopping-bag me-1 text-muted"></i>Channel
                            </label>
                            <select name="channel_id" class="form-select form-select-sm">
                                <option value="">Semua Channel</option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->id }}"
                                        {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-store me-1 text-muted"></i>Toko
                            </label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">Semua Toko</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}"
                                        {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-truck me-1 text-muted"></i>Kurir
                            </label>
                            <select name="courier" class="form-select form-select-sm">
                                <option value="">Semua Kurir</option>
                                @foreach ($couriers as $courier)
                                    <option value="{{ $courier }}" {{ request('courier') == $courier ? 'selected' : '' }}>
                                        {{ $courier }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-info-circle me-1 text-muted"></i>Status
                            </label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ str_replace('_', ' ', $status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-calendar-alt me-1 text-muted"></i>Rentang Tanggal
                            </label>
                            <div class="d-flex gap-2">
                                <input type="date" name="start_date" class="form-control form-control-sm"
                                    value="{{ request('start_date') }}">
                                <input type="date" name="end_date" class="form-control form-control-sm"
                                    value="{{ request('end_date') }}">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if (request()->anyFilled(['channel_id', 'store_id', 'courier', 'status', 'start_date', 'end_date']))
                                <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="dashboard-card">
                <div class="card-header-line d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2 text-primary"></i>Daftar Pesanan</h5>
                        <p class="text-muted mb-0 mt-1 small">Kelola pesanan dari toko online dan marketplace</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" form="mass-print-form" class="btn btn-success btn-sm px-3">
                            <i class="fas fa-print me-1"></i> Cetak Massal
                        </button>
                        <form action="{{ route('orders.sync') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fas fa-sync me-1"></i> Tarik Pesanan
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <form id="mass-print-form" action="{{ route('orders.mass_print') }}" method="POST" target="_blank">
                        @csrf
                        <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" id="check-all" class="form-check-input">
                                    </th>
                                    <th>INVOICE / ID</th>
                                    <th>PEMBELI</th>
                                    <th>TOKO &amp; CHANNEL</th>
                                    <th class="text-end">TOTAL</th>
                                    <th>KURIR</th>
                                    <th>TANGGAL</th>
                                    <th class="text-center">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                                class="order-checkbox form-check-input">
                                        </td>
                                        <td>
                                            <a href="{{ route('orders.show', $order) }}"
                                                class="text-decoration-none fw-bold text-primary small">
                                                {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                            </a>
                                            <div class="text-muted small mt-1">ID: {{ $order->order_marketplace_id }}</div>
                                        </td>
                                        <td>
                                            <strong class="text-white small">{{ $order->buyer_name ?? '-' }}</strong>
                                        </td>
                                        <td>
                                            <div class="lh-sm">
                                                <strong class="text-white small">{{ $order->store->store_name }}</strong>
                                                <div class="mt-1">
                                                    <span class="badge bg-secondary channel-{{ $order->store->channel->code }} small">
                                                        {{ $order->store->channel->name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace">
                                            <strong class="text-white small">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            <span class="small text-white-50">
                                                <i class="fas fa-truck me-1 text-secondary"></i>{{ $order->courier ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="small text-white-50">
                                            {{ $order->order_date->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $order->status_badge }} bg-opacity-10 text-{{ $order->status_badge }} border border-{{ $order->status_badge }} border-opacity-10 small">
                                                {{ str_replace('_', ' ', $order->order_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-shopping-basket fa-2x mb-3 d-block opacity-25"></i>
                                            <p class="mb-0 small">Belum ada data pesanan.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </form>
                </div>

                @if ($orders->hasPages())
                    <div class="mt-3">
                        {{ $orders->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
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
