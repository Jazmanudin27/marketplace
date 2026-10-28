@extends('v2.layouts.app')

@section('title', 'Pesanan Dropship / Lazada')

@push('styles')
<style>
.dropship-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 18px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dropship-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.dropship-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    margin-bottom: 4px;
}
.dropship-kpi-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.dropship-kpi-sub {
    font-size: 0.72rem;
    color: #9ca3af;
    margin-top: 4px;
}
.dropship-kpi-icon {
    position: absolute;
    right: 14px;
    bottom: 12px;
    font-size: 2.2rem;
    opacity: 0.12;
    pointer-events: none;
}
.nav-pills-custom .nav-link {
    color: #64748b;
    font-weight: 600;
    font-size: 0.82rem;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.15s ease;
}
.nav-pills-custom .nav-link.active {
    background-color: #0284c7;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-truck text-info fs-5"></i> Pesanan Dropship &amp; Lazada
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Pencatatan pesanan sederhana khusus Dropship dan Lazada dengan detail produk, qty, dan harga.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.dropship.create', ['channel_type' => 'dropship']) }}" class="btn btn-sm text-white py-1.5 px-3 rounded-2 fw-semibold shadow-sm" style="background:#0284c7; border:none;">
            <i class="bi bi-plus-lg me-1"></i> Input Dropship
        </a>
        <a href="{{ route('v2.dropship.create', ['channel_type' => 'lazada']) }}" class="btn btn-sm text-white py-1.5 px-3 rounded-2 fw-semibold shadow-sm" style="background:#0f172a; border:none;">
            <i class="bi bi-bag-plus me-1"></i> Input Lazada
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── KPI Cards ── --}}
<div class="row g-2.5 mb-3">
    <div class="col-6 col-md-3">
        <div class="dropship-kpi-card border-start border-primary border-3">
            <div class="dropship-kpi-title text-primary">Total Pesanan</div>
            <div class="dropship-kpi-value text-dark">
                {{ number_format($totalOrdersCount, 0, ',', '.') }}
            </div>
            <div class="dropship-kpi-sub">Jumlah transaksi tercatat</div>
            <i class="bi bi-receipt dropship-kpi-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dropship-kpi-card border-start border-info border-3">
            <div class="dropship-kpi-title text-info">Total Kuantitas</div>
            <div class="dropship-kpi-value text-info font-monospace">
                {{ number_format($totalQty, 0, ',', '.') }} PCS
            </div>
            <div class="dropship-kpi-sub">Total unit barang terjual</div>
            <i class="bi bi-boxes dropship-kpi-icon text-info"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dropship-kpi-card border-start border-success border-3">
            <div class="dropship-kpi-title text-success">Total Omset</div>
            <div class="dropship-kpi-value text-success font-monospace">
                Rp {{ number_format($totalOmset, 0, ',', '.') }}
            </div>
            <div class="dropship-kpi-sub">Terbayar: Rp {{ number_format($totalPaidOmset, 0, ',', '.') }}</div>
            <i class="bi bi-cash-stack dropship-kpi-icon text-success"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dropship-kpi-card border-start border-danger border-3">
            <div class="dropship-kpi-title text-danger">Belum Lunas / Piutang</div>
            <div class="dropship-kpi-value text-danger font-monospace">
                Rp {{ number_format($totalUnpaidOmset, 0, ',', '.') }}
            </div>
            <div class="dropship-kpi-sub">Tagihan belum diterima</div>
            <i class="bi bi-exclamation-circle dropship-kpi-icon text-danger"></i>
        </div>
    </div>
</div>

{{-- ── Tabs Filter & Table Card ── --}}
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4" style="border: 1px solid #eef2f6 !important;">
    {{-- Channel Tabs --}}
    <div class="p-3 border-bottom bg-light bg-opacity-50 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <ul class="nav nav-pills nav-pills-custom gap-1 mb-0">
            <li class="nav-item">
                <a class="nav-link {{ $channelType === 'all' ? 'active' : '' }}" href="{{ route('v2.dropship.index', array_merge(request()->except('channel_type', 'page'), ['channel_type' => 'all'])) }}">
                    <i class="bi bi-grid me-1"></i> Semua ({{ $tabCounts['all'] }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $channelType === 'dropship' ? 'active' : '' }}" href="{{ route('v2.dropship.index', array_merge(request()->except('channel_type', 'page'), ['channel_type' => 'dropship'])) }}">
                    <i class="bi bi-truck me-1"></i> Dropship ({{ $tabCounts['dropship'] }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $channelType === 'lazada' ? 'active' : '' }}" href="{{ route('v2.dropship.index', array_merge(request()->except('channel_type', 'page'), ['channel_type' => 'lazada'])) }}">
                    <i class="bi bi-bag-check me-1"></i> Lazada ({{ $tabCounts['lazada'] }})
                </a>
            </li>
        </ul>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('v2.dropship.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="hidden" name="channel_type" value="{{ $channelType }}">
            
            {{-- Filter Status Pembayaran --}}
            <select name="payment_status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                <option value="all" {{ request('payment_status') == 'all' ? 'selected' : '' }}>Semua Bayar</option>
                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Dicicil (DP)</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
            </select>

            <div class="input-group input-group-sm" style="width: 200px;">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Cari nota / nama / hp..." value="{{ request('search') }}">
            </div>
            <input type="date" name="date_from" class="form-control form-control-sm" style="width: 130px;" value="{{ request('date_from') }}" title="Tanggal Mulai">
            <span class="text-muted small">-</span>
            <input type="date" name="date_to" class="form-control form-control-sm" style="width: 130px;" value="{{ request('date_to') }}" title="Tanggal Akhir">
            <button type="submit" class="btn btn-sm btn-primary px-2.5 fw-semibold">
                <i class="bi bi-funnel"></i>
            </button>
            @if(request()->hasAny(['search', 'date_from', 'date_to', 'payment_status']))
                <a href="{{ route('v2.dropship.index', ['channel_type' => $channelType]) }}" class="btn btn-sm btn-outline-secondary px-2" title="Reset Filter">
                    <i class="bi bi-x-circle"></i>
                </a>
            @endif
        </form>
    </div>

    {{-- Main Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
            <thead class="bg-light text-secondary text-uppercase fw-bold border-bottom" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                <tr>
                    <th class="ps-3.5 py-2.5" style="width: 15%;">NO. NOTA</th>
                    <th class="py-2.5 text-center" style="width: 13%;">SALURAN &amp; STATUS</th>
                    <th class="py-2.5 text-center" style="width: 10%;">TANGGAL</th>
                    <th class="py-2.5" style="width: 17%;">PELANGGAN</th>
                    <th class="py-2.5" style="width: 20%;">DETAIL PRODUK</th>
                    <th class="py-2.5 text-end" style="width: 15%;">TAGIHAN &amp; BAYAR</th>
                    <th class="pe-3.5 py-2.5 text-center" style="width: 10%;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="ps-3.5 py-3">
                            <a href="{{ route('v2.dropship.show', $order->id) }}" class="fw-bold font-monospace text-primary text-decoration-none d-block">
                                {{ $order->order_number }}
                            </a>
                            @if($order->notes)
                                <small class="text-muted text-truncate d-block" style="max-width: 170px;" title="{{ $order->notes }}">
                                    <i class="bi bi-chat-text me-0.5"></i>{{ $order->notes }}
                                </small>
                            @endif
                        </td>
                        <td class="text-center py-3">
                            <div class="d-flex flex-column align-items-center gap-1">
                                @if($order->channel_type === 'lazada')
                                    <span class="badge bg-dark-subtle text-dark border border-dark-subtle rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.7rem;">
                                        <i class="bi bi-bag-fill me-1 text-danger"></i>Lazada
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.7rem;">
                                        <i class="bi bi-truck me-1"></i>Dropship
                                    </span>
                                @endif

                                {{-- Payment Status Badge --}}
                                @if($order->payment_status === 'paid' || ($order->paid_amount >= $order->total_amount && $order->total_amount > 0))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.68rem;">
                                        <i class="bi bi-check-circle-fill me-0.5"></i> Lunas
                                    </span>
                                @elseif($order->payment_status === 'partial' || ($order->paid_amount > 0 && $order->paid_amount < $order->total_amount))
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.68rem;">
                                        <i class="bi bi-pie-chart-fill me-0.5 text-warning"></i> Dicicil
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.68rem;">
                                        <i class="bi bi-clock-history me-0.5"></i> Belum Lunas
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center py-3 text-secondary">
                            <i class="bi bi-calendar3 me-1 text-muted"></i>
                            {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3">
                            <span class="fw-bold text-dark d-block" style="font-size:0.87rem;">{{ $order->customer_name }}</span>
                            @if($order->customer_phone)
                                <small class="text-muted"><i class="bi bi-telephone me-0.5"></i>{{ $order->customer_phone }}</small>
                            @else
                                <small class="text-muted">Tanpa Kontak</small>
                            @endif
                        </td>
                        <td class="py-3">
                            <div class="d-flex flex-column gap-1">
                                @foreach($order->items->take(2) as $it)
                                    <div class="d-flex align-items-center justify-content-between small">
                                        <span class="text-truncate" style="max-width: 190px;" title="{{ $it->product_name }}">
                                            <strong>{{ $it->quantity }}x</strong> {{ $it->product_name }}
                                        </span>
                                        <span class="font-monospace text-muted" style="font-size: 0.72rem;">
                                            @ Rp {{ number_format($it->unit_price, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                                @if($order->items->count() > 2)
                                    <span class="badge bg-light text-secondary border align-self-start" style="font-size: 0.68rem;">
                                        +{{ $order->items->count() - 2 }} produk lainnya
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="text-end py-3">
                            <span class="fw-bold text-dark font-monospace d-block" style="font-size:0.88rem;">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </span>
                            @if($order->payment_status === 'paid' || ($order->paid_amount >= $order->total_amount && $order->total_amount > 0))
                                <small class="text-success fw-semibold" style="font-size: 0.72rem;">
                                    <i class="bi bi-check2"></i> Terbayar Penuh
                                </small>
                            @else
                                <small class="text-danger fw-semibold" style="font-size: 0.72rem;">
                                    Sisa: Rp {{ number_format(max(0, $order->total_amount - $order->paid_amount), 0, ',', '.') }}
                                </small>
                            @endif
                            <small class="text-muted d-block" style="font-size: 0.7rem;">
                                Total {{ number_format($order->total_qty) }} PCS
                            </small>
                        </td>
                        <td class="text-center pe-3.5 py-3">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('v2.dropship.show', $order->id) }}" class="btn btn-xs btn-outline-primary py-1 px-1.5 rounded" title="Lihat Detail & Pembayaran">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('v2.dropship.edit', $order->id) }}" class="btn btn-xs btn-outline-warning py-1 px-1.5 rounded text-dark" title="Edit Pesanan">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('v2.dropship.destroy', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan {{ $order->order_number }} ini? Stok produk akan dikembalikan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-1.5 rounded" title="Hapus Pesanan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-truck fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada transaksi pesanan dropship/lazada yang tercatat.
                            <div class="mt-2">
                                <a href="{{ route('v2.dropship.create') }}" class="btn btn-sm btn-info text-white fw-semibold py-1.5 px-3 rounded-2">
                                    <i class="bi bi-plus-lg me-1"></i> Buat Pesanan Baru
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="card-footer bg-white py-2.5 px-3 border-top d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} data
            </span>
            <div>
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@endsection
