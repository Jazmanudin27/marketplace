@extends('layouts.app')
@section('title', 'Daftar Pesanan')
@section('page-title', 'Manajemen Pesanan')
@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Alert Batas Pengiriman ── --}}
            @if ($urgentOrders->isNotEmpty())
                @php
                    $overdueCount = $urgentOrders->filter(fn($o) => $o->ship_before_date->isPast())->count();
                    $soonCount = $urgentOrders->count() - $overdueCount;
                @endphp
                <div class="alert alert-dismissible fade show border-start border-4 p-0 mb-3 overflow-hidden shadow-sm {{ $overdueCount > 0 ? 'alert-danger border-danger' : 'alert-warning border-warning' }}"
                    role="alert">
                    <div class="d-flex align-items-stretch">
                        <div class="d-flex align-items-center justify-content-center px-3 {{ $overdueCount > 0 ? 'bg-danger' : 'bg-warning' }}"
                            style="min-width:52px;">
                            <i class="bi bi-clock-fill fs-4 text-white"></i>
                        </div>
                        <div class="flex-grow-1 p-2 px-3">
                            <h6 class="fw-bold mb-1 {{ $overdueCount > 0 ? 'text-danger' : 'text-warning' }} small">
                                ⚠️ {{ $urgentOrders->count() }} Pesanan Harus Segera Dikirim!
                            </h6>
                            <p class="mb-0 small text-secondary">
                                @if ($overdueCount > 0)
                                    <span class="badge bg-danger me-1">{{ $overdueCount }} Overdue</span>
                                @endif
                                @if ($soonCount > 0)
                                    <span class="badge bg-warning text-dark me-1">{{ $soonCount }} Dalam 24 Jam</span>
                                @endif
                                Batas waktu pengiriman sudah lewat atau kurang dari 24 jam.
                            </p>
                        </div>
                        <div class="d-flex align-items-center pe-3">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            @endif


            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="card border shadow-sm overflow-hidden">
                <div
                    class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shopping-cart me-2 text-info"></i>Daftar Pesanan
                        </h6>
                        <small class="text-muted d-block mt-1">Kelola pesanan dari toko online dan marketplace</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" form="mass-print-form" class="btn btn-success btn-sm px-3 rounded-3">
                            <i class="fas fa-print me-1"></i> Cetak Massal
                        </button>
                        <form action="{{ route('orders.sync') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">
                                <i class="fas fa-sync me-1"></i> Tarik Pesanan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body p-3">

                    {{-- ── Filter Card ───────────────────────────────────────────── --}}
                    <div class="card border shadow-sm p-3 mb-3">
                        <form method="GET" action="{{ route('orders.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-shopping-bag me-1 text-secondary"></i>Channel
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
                                    <label class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-store me-1 text-secondary"></i>Toko
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
                                    <label class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-truck me-1 text-secondary"></i>Kurir
                                    </label>
                                    <select name="courier" class="form-select form-select-sm">
                                        <option value="">Semua Kurir</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier }}"
                                                {{ request('courier') == $courier ? 'selected' : '' }}>
                                                {{ $courier }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-info-circle me-1 text-secondary"></i>Status
                                    </label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">Semua Status</option>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}"
                                                {{ request('status') == $status ? 'selected' : '' }}>
                                                {{ str_replace('_', ' ', $status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-hourglass-half me-1 text-secondary"></i>Batas Kirim
                                    </label>
                                    <select name="deadline_status" class="form-select form-select-sm">
                                        <option value="">Semua Batas Kirim</option>
                                        <option value="overdue"
                                            {{ request('deadline_status') == 'overdue' ? 'selected' : '' }}>
                                            Overdue</option>
                                        <option value="urgent"
                                            {{ request('deadline_status') == 'urgent' ? 'selected' : '' }}>
                                            Urgent (&lt; 24 Jam)</option>
                                        <option value="safe"
                                            {{ request('deadline_status') == 'safe' ? 'selected' : '' }}>Aman
                                            (&gt; 24 Jam)</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label fw-bold small text-dark mb-1">
                                        <i class="fas fa-calendar-alt me-1 text-secondary"></i>Rentang Tanggal
                                    </label>
                                    <div class="d-flex gap-2">
                                        <input type="date" name="start_date" class="form-control form-control-sm"
                                            value="{{ request('start_date') }}">
                                        <input type="date" name="end_date" class="form-control form-control-sm"
                                            value="{{ request('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    @if (request()->anyFilled(['channel_id', 'store_id', 'courier', 'status', 'start_date', 'end_date', 'deadline_status']))
                                        <a href="{{ route('orders.index') }}"
                                            class="btn btn-secondary btn-sm px-3 rounded-3">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive rounded border">
                        <form id="mass-print-form" action="{{ route('orders.mass_print') }}" method="POST"
                            target="_blank">
                            @csrf
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead>
                                    <tr class="small">
                                        <th class="text-center" style="width: 40px;">
                                            <input type="checkbox" id="check-all" class="form-check-input">
                                        </th>
                                        <th>INVOICE / ID</th>
                                        <th>PEMBELI</th>
                                        <th>TOKO &amp; CHANNEL</th>
                                        <th class="text-end">TOTAL</th>
                                        <th>KURIR</th>
                                        <th>TANGGAL</th>
                                        <th>BATAS KIRIM</th>
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
                                                <div class="text-muted small mt-1" style="font-size:0.68rem;">ID:
                                                    {{ $order->order_marketplace_id }}</div>
                                            </td>
                                            <td>
                                                <strong class="text-dark small">{{ $order->buyer_name ?? '-' }}</strong>
                                            </td>
                                            <td>
                                                <div class="lh-sm">
                                                    <strong
                                                        class="text-dark small">{{ $order->store->store_name }}</strong>
                                                    <div class="mt-1">
                                                        <span
                                                            class="badge bg-secondary channel-{{ $order->store->channel->code }} small">
                                                            {{ $order->store->channel->name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end font-monospace">
                                                <strong class="text-dark small">Rp
                                                    {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                <span class="small text-muted">
                                                    <i
                                                        class="fas fa-truck me-1 text-secondary"></i>{{ $order->courier ?? '—' }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ $order->order_date->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="small text-center">
                                                @if ($order->ship_before_date)
                                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.72rem;">
                                                        {{ $order->ship_before_date->format('d/m/Y H:i') }}
                                                    </div>
                                                    @if ($order->is_ship_overdue)
                                                        <span
                                                            class="badge bg-danger-subtle text-danger border border-danger-subtle"
                                                            style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                            <i class="bi bi-exclamation-circle me-1"></i>Overdue
                                                        </span>
                                                    @elseif ($order->is_ship_urgent)
                                                        <span
                                                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                                                            style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                            <i
                                                                class="bi bi-clock me-1"></i>{{ $order->ship_before_date->diffForHumans() }}
                                                        </span>
                                                    @else
                                                        <span
                                                            class="badge bg-success-subtle text-success border border-success-subtle"
                                                            style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                            <i
                                                                class="bi bi-check-circle me-1"></i>{{ $order->ship_before_date->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-{{ $order->status_badge ?? 'secondary' }}-subtle text-{{ $order->status_badge ?? 'secondary' }} border border-{{ $order->status_badge ?? 'secondary' }}-subtle"
                                                    style="font-size:0.7rem; padding: 0.25em 0.5em;">
                                                    {{ str_replace('_', ' ', $order->order_status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-5">
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
