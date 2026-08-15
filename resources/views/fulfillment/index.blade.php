@extends('layouts.app')
@section('title', 'Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Pemenuhan Pesanan')

@section('content')
<div class="container-fluid px-0">

    {{-- Page Header --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white overflow-hidden position-relative" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);">
        <div class="card-body p-4 position-relative z-1">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 bg-white bg-opacity-20 p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; backdrop-filter: blur(8px);">
                        <i class="fas fa-boxes-packing fs-2 text-white"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-white">Antrean Kemas & Pemenuhan Pesanan</h4>
                        <p class="text-white text-opacity-85 mb-0 small">
                            Kelola verifikasi packing, cetak resi thermal massal, dan pengiriman resi ke marketplace secara real-time.
                        </p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('fulfillment.scan_page') }}" class="btn btn-light btn-md px-3 py-2 fw-bold text-primary rounded-3 shadow-sm d-inline-flex align-items-center gap-2 hover-scale">
                        <i class="fas fa-barcode fs-5"></i>
                        <span>Layar Scanner Barcode</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="position-absolute end-0 bottom-0 opacity-10 pe-4 pb-2 d-none d-md-block pointer-events-none">
            <i class="fas fa-shipping-fast" style="font-size: 10rem; color: #fff;"></i>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a href="{{ route('fulfillment.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-all hover-shadow-lg {{ !request('packing_status') ? 'ring-2 ring-primary bg-primary bg-opacity-10' : 'bg-white' }}">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="fas fa-box-archive fs-4 text-primary"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-muted small fw-semibold text-uppercase letter-spacing-1">Total Siap Kirim</div>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($stats['total']) }}</h3>
                            <div class="d-flex gap-1 mt-1 flex-wrap" style="font-size:0.7rem;">
                                <span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 px-1.5 py-0.5">
                                    {{ $stats['unprinted'] }} Belum Print
                                </span>
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-1.5 py-0.5">
                                    {{ $stats['printed'] }} Sudah Print
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['packing_status' => 'pending'])) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-all hover-shadow-lg {{ request('packing_status') === 'pending' ? 'ring-2 ring-warning bg-warning bg-opacity-10' : 'bg-white' }}">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning bg-opacity-10 p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="fas fa-clock fs-4 text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase letter-spacing-1">Belum Diproses</div>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($stats['pending']) }}</h3>
                            <span class="badge bg-warning bg-opacity-20 text-warning-emphasis border border-warning border-opacity-25 mt-1" style="font-size:0.68rem;">Menunggu Pack</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['packing_status' => 'packing'])) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-all hover-shadow-lg {{ request('packing_status') === 'packing' ? 'ring-2 ring-info bg-info bg-opacity-10' : 'bg-white' }}">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-info bg-opacity-10 p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="fas fa-dolly fs-4 text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase letter-spacing-1">Sedang Dikemas</div>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($stats['packing']) }}</h3>
                            <span class="badge bg-info bg-opacity-20 text-info border border-info border-opacity-25 mt-1" style="font-size:0.68rem;">Dalam Proses</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['packing_status' => 'verified'])) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-all hover-shadow-lg {{ request('packing_status') === 'verified' ? 'ring-2 ring-success bg-success bg-opacity-10' : 'bg-white' }}">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                            <i class="fas fa-circle-check fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase letter-spacing-1">Selesai Scan (Verified)</div>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($stats['verified']) }}</h3>
                            <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 mt-1" style="font-size:0.68rem;">Siap Kirim</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Filter Bar Tabs --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <ul class="nav nav-pills bg-white p-1.5 rounded-4 border shadow-sm gap-1">
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-bold small rounded-3 {{ !request('print_status') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" 
                   href="{{ route('fulfillment.index', array_merge(request()->except('print_status', 'page'))) }}">
                    <i class="fas fa-layer-group me-1.5"></i>Semua Data
                    <span class="badge {{ !request('print_status') ? 'bg-white text-primary' : 'bg-secondary bg-opacity-15 text-secondary' }} rounded-pill ms-1">
                        {{ number_format($stats['total']) }}
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-bold small rounded-3 {{ request('print_status') === 'unprinted' ? 'active bg-warning text-dark shadow-sm' : 'text-secondary' }}" 
                   href="{{ route('fulfillment.index', array_merge(request()->query(), ['print_status' => 'unprinted', 'page' => 1])) }}">
                    <i class="fas fa-print me-1.5"></i>Belum Cetak (Unprinted)
                    <span class="badge {{ request('print_status') === 'unprinted' ? 'bg-dark text-warning' : 'bg-warning bg-opacity-20 text-warning-emphasis' }} rounded-pill ms-1">
                        {{ number_format($stats['unprinted']) }}
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-bold small rounded-3 {{ request('print_status') === 'printed' ? 'active bg-success text-white shadow-sm' : 'text-secondary' }}" 
                   href="{{ route('fulfillment.index', array_merge(request()->query(), ['print_status' => 'printed', 'page' => 1])) }}">
                    <i class="fas fa-check-circle me-1.5"></i>Sudah Cetak (Printed)
                    <span class="badge {{ request('print_status') === 'printed' ? 'bg-white text-success' : 'bg-success bg-opacity-20 text-success' }} rounded-pill ms-1">
                        {{ number_format($stats['printed']) }}
                    </span>
                </a>
            </li>
        </ul>
    </div>

    {{-- Filter Panel Card --}}
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fulfillment.index') }}">
                @if(request('print_status'))
                    <input type="hidden" name="print_status" value="{{ request('print_status') }}">
                @endif
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4 col-xl-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-search me-1"></i>Pencarian Pesanan
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0"
                                placeholder="Cari Invoice, ID, Pembeli, SKU..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-2 col-xl-1.5">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-shopping-bag me-1"></i>Channel
                        </label>
                        <select name="channel_id" class="form-select form-select-sm">
                            <option value="">Semua Channel</option>
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->id }}" {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                    {{ $channel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-1.5">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-store me-1"></i>Toko
                        </label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="">Semua Toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-1.5">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-truck me-1"></i>Kurir
                        </label>
                        <select name="courier" class="form-select form-select-sm">
                            <option value="">Semua Kurir</option>
                            @foreach ($couriers as $cr)
                                <option value="{{ $cr }}" {{ request('courier') == $cr ? 'selected' : '' }}>
                                    {{ $cr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-1.5">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-box-open me-1"></i>Status Kemas
                        </label>
                        <select name="packing_status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('packing_status') === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                            <option value="packing" {{ request('packing_status') === 'packing' ? 'selected' : '' }}>Sedang Dikemas (Packing)</option>
                            <option value="verified" {{ request('packing_status') === 'verified' ? 'selected' : '' }}>Selesai Scan (Verified)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-1.5">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-tag me-1"></i>Tipe Produk
                        </label>
                        <select name="is_po" class="form-select form-select-sm">
                            <option value="">Semua Tipe</option>
                            <option value="po" {{ request('is_po') === 'po' ? 'selected' : '' }}>PO / SPK</option>
                            <option value="ready" {{ request('is_po') === 'ready' ? 'selected' : '' }}>Ready Stock</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-1.5">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-clock me-1"></i>Batas Kirim
                        </label>
                        <select name="deadline_status" class="form-select form-select-sm">
                            <option value="">Semua Batas</option>
                            <option value="overdue" {{ request('deadline_status') === 'overdue' ? 'selected' : '' }}>Terlewat</option>
                            <option value="urgent" {{ request('deadline_status') === 'urgent' ? 'selected' : '' }}>Mendesak (<=24j)</option>
                            <option value="safe" {{ request('deadline_status') === 'safe' ? 'selected' : '' }}>Aman (>24j)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-xl-3">
                        <label class="form-label fw-semibold small text-secondary mb-1">
                            <i class="fas fa-calendar-alt me-1"></i>Rentang Tanggal
                        </label>
                        <div class="d-flex gap-1">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3 fw-semibold shadow-sm">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        @if (request()->anyFilled([
                                'search', 'channel_id', 'store_id', 'courier',
                                'packing_status', 'print_status', 'is_po',
                                'deadline_status', 'start_date', 'end_date'
                            ]))
                            <a href="{{ route('fulfillment.index') }}" class="btn btn-light text-secondary btn-sm px-3 rounded-3 border">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                    <i class="fas fa-list-check fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Daftar Antrean Kemas</h5>
                    <small class="text-muted">Menampilkan {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} dari {{ number_format($orders->total()) }} pesanan</small>
                </div>
            </div>

            {{-- Action Buttons Bar (Top of Table) --}}
            <div class="d-flex flex-wrap gap-2">
                <button type="button" id="btn-top-label"
                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1.5 fw-bold px-3 py-2 rounded-3 shadow-sm"
                    title="Cetak Resi Massal (Kertas Stiker Thermal)">
                    <i class="fas fa-print"></i> Cetak Resi Massal (Thermal)
                </button>

                <button type="button" id="btn-top-ship"
                    class="btn btn-success btn-sm d-inline-flex align-items-center gap-1.5 fw-bold px-3 py-2 rounded-3 shadow-sm"
                    title="Kirim Resi Massal ke Marketplace">
                    <i class="fas fa-paper-plane"></i> Kirim Resi (Ship)
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <form method="POST" id="batch-form">
                @csrf
                <div class="table-responsive" style="min-width: 100%;">
                    <table class="table table-hover align-middle mb-0" style="min-width: 1280px;">
                        <thead class="table-light border-bottom">
                            <tr class="text-uppercase text-secondary small fw-bold letter-spacing-1" style="font-size: 0.75rem;">
                                <th style="width: 45px;" class="ps-3 text-center">
                                    <input type="checkbox" id="check-all" class="form-check-input" style="cursor: pointer;">
                                </th>
                                <th style="width: 220px;">Pesanan & Toko</th>
                                <th style="width: 100px;" class="text-center">Tipe</th>
                                <th style="width: 160px;">Pembeli</th>
                                <th>Detail Barang / SKU</th>
                                <th style="width: 180px;">Kurir & Resi</th>
                                <th style="width: 140px;" class="text-center">Batas Kirim</th>
                                <th style="width: 120px;" class="text-center">Print</th>
                                <th style="width: 130px;" class="text-center">Status Kemas</th>
                                <th style="width: 140px;" class="pe-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($orders as $order)
                                @php
                                    $channelCode = strtolower($order->store->channel->code ?? '');
                                    $channelBgMap = [
                                        'shopee' => ['bg' => '#ff5722', 'icon' => 'fas fa-shopping-bag'],
                                        'tiktok' => ['bg' => '#000000', 'icon' => 'fab fa-tiktok'],
                                        'tokopedia' => ['bg' => '#42b549', 'icon' => 'fas fa-store'],
                                        'lazada' => ['bg' => '#0f146d', 'icon' => 'fas fa-shopping-cart'],
                                    ];
                                    $chMeta = $channelBgMap[$channelCode] ?? ['bg' => '#6c757d', 'icon' => 'fas fa-store'];
                                @endphp
                                <tr>
                                    <td class="ps-3 text-center">
                                        <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="form-check-input order-checkbox" style="cursor: pointer;">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark font-monospace mb-1" style="font-size: 0.85rem;">
                                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="badge text-white px-2 py-0.5 rounded-2 small fw-semibold" style="background-color: {{ $chMeta['bg'] }}; font-size:0.68rem;">
                                                <i class="{{ $chMeta['icon'] }} me-1"></i>{{ $order->store->channel->name }}
                                            </span>
                                            <span class="text-muted small text-truncate" style="max-width: 120px;" title="{{ $order->store->store_name }}">
                                                {{ $order->store->store_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($order->hasPreorderItems() || $order->spks->isNotEmpty())
                                            <span class="badge text-white px-2 py-1 small fw-bold rounded-2 shadow-xs" style="background-color: #8b5cf6;" title="Barang Pre-Order / Produksi SPK">
                                                <i class="fas fa-clock me-1"></i>PO / SPK
                                            </span>
                                            @if ($order->spks->isNotEmpty())
                                                <div class="small font-monospace text-primary fw-semibold mt-1" style="font-size: 0.68rem;">
                                                    #{{ $order->spks->first()->no_spk }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge px-2 py-1 small fw-bold rounded-2" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;" title="Barang Ready Stock">
                                                <i class="fas fa-circle-check me-1"></i>READY
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 150px;" title="{{ $order->buyer_name }}">
                                            {{ $order->buyer_name ?? '—' }}
                                        </div>
                                        @if($order->buyer_phone)
                                            <div class="small text-muted font-monospace" style="font-size:0.72rem;">{{ $order->buyer_phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="py-1">
                                            @foreach ($order->items as $item)
                                                <div class="d-flex align-items-baseline gap-1.5 mb-1 last:mb-0">
                                                    <span class="badge bg-primary bg-opacity-15 text-primary fw-bold px-1.5 py-0.5 rounded" style="font-size:0.7rem;">
                                                        {{ $item->quantity }}x
                                                    </span>
                                                    <span class="text-dark small fw-medium text-truncate" style="max-width: 220px;" title="{{ $item->product_name }}">
                                                        {{ $item->product_name }}
                                                    </span>
                                                    <span class="font-monospace bg-light text-muted border px-1 rounded" style="font-size:0.68rem;">
                                                        {{ $item->sku ?? 'No SKU' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small mb-1">
                                            <i class="fas fa-truck text-secondary me-1"></i>{{ $order->courier ?? '—' }}
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            @if (!empty($order->tracking_number))
                                                <div class="bg-light border rounded px-2 py-0.5 font-monospace text-dark fw-bold d-inline-flex align-items-center gap-1" style="font-size:0.72rem;" title="Nomor Resi">
                                                    <i class="fas fa-barcode text-muted"></i>
                                                    <span>{{ $order->tracking_number }}</span>
                                                </div>
                                            @else
                                                <button type="button" 
                                                    class="btn btn-xs btn-outline-warning text-dark border-warning rounded-2 py-0.5 px-2 fw-semibold btn-fetch-single-tracking shadow-xs"
                                                    data-order-id="{{ $order->id }}"
                                                    style="font-size: 0.68rem;"
                                                    title="Tarik Resi dari Marketplace">
                                                    <i class="fas fa-arrows-rotate me-1"></i>Tarik Resi
                                                </button>
                                            @endif

                                            @if (in_array($order->order_status, ['SHIPPED', 'DELIVERED', 'COMPLETED']))
                                                <div>
                                                    <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-0.5 rounded-2" style="font-size: 0.68rem;">
                                                        <i class="fas fa-circle-check me-1"></i>Sudah Kirim
                                                    </span>
                                                </div>
                                            @elseif ($order->order_status !== 'CANCELLED')
                                                <div>
                                                    <button type="button" 
                                                        class="btn btn-xs btn-outline-primary rounded-2 py-0.5 px-2 fw-semibold btn-ship-single-order shadow-xs"
                                                        data-order-id="{{ $order->id }}"
                                                        style="font-size: 0.68rem;"
                                                        title="Kirim Pesanan ke Marketplace">
                                                        <i class="fas fa-paper-plane me-1"></i>Kirim Pesanan
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($order->ship_before_date)
                                            <div class="fw-bold text-dark mb-1 font-monospace" style="font-size: 0.75rem;">
                                                {{ $order->ship_before_date->format('d/m/Y H:i') }}
                                            </div>
                                            @if (!in_array($order->order_status, ['SHIPPED', 'DELIVERED', 'COMPLETED', 'FINISHED', 'CANCELLED', 'SELESAI', 'BATAL', 'IN_CANCEL']))
                                                @if ($order->is_ship_overdue)
                                                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 px-2 py-0.5 rounded-2" style="font-size: 0.68rem;">
                                                        <i class="fas fa-triangle-exclamation me-1"></i>Terlewat
                                                    </span>
                                                @elseif ($order->is_ship_urgent)
                                                    <span class="badge bg-warning bg-opacity-20 text-warning-emphasis border border-warning border-opacity-25 px-2 py-0.5 rounded-2" style="font-size: 0.68rem;">
                                                        <i class="fas fa-clock me-1"></i>{{ $order->ship_before_date->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-0.5 rounded-2" style="font-size: 0.68rem;">
                                                        <i class="fas fa-check-circle me-1"></i>{{ $order->ship_before_date->diffForHumans() }}
                                                    </span>
                                                @endif
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($order->is_printed)
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 py-1 px-2.5 rounded-2 fw-semibold" style="font-size:0.72rem;">
                                                <i class="fas fa-check-circle me-1"></i>Sudah Print
                                            </span>
                                            @if ($order->printed_at)
                                                <div class="small font-monospace text-muted mt-1" style="font-size:0.68rem;">
                                                    {{ $order->printed_at->format('d/m H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 py-1 px-2.5 rounded-2" style="font-size:0.72rem;">
                                                <i class="fas fa-clock me-1"></i>Belum Print
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($order->packing_status === 'verified')
                                            <span class="badge bg-success py-1.5 px-2.5 rounded-2 shadow-xs" style="font-size:0.75rem;">
                                                <i class="fas fa-circle-check me-1"></i> Verified
                                            </span>
                                            @if ($order->packed_at)
                                                <div class="small font-monospace text-muted mt-1" style="font-size:0.68rem;">
                                                    {{ $order->packed_at->format('d/m H:i') }}
                                                </div>
                                            @endif
                                        @elseif($order->packing_status === 'packing')
                                            <span class="badge bg-warning text-dark py-1.5 px-2.5 rounded-2 shadow-xs" style="font-size:0.75rem;">
                                                <i class="fas fa-box-open me-1"></i> Packing
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border py-1.5 px-2.5 rounded-2" style="font-size:0.75rem;">
                                                <i class="fas fa-hourglass-start me-1"></i> Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-center">
                                        <div class="d-flex flex-column gap-1">
                                            @if ($order->packing_status === 'verified')
                                                <form action="{{ route('orders.ship', $order->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm w-100 py-1 px-2 fw-semibold rounded-2 shadow-xs" style="font-size:0.75rem;">
                                                        <i class="fas fa-paper-plane me-1"></i> Kirim Resi
                                                    </button>
                                                </form>
                                                <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm w-100 py-1 px-2 rounded-2" style="font-size:0.75rem;">
                                                    <i class="fas fa-print me-1"></i> Cetak Label
                                                </a>
                                            @else
                                                <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 py-1 px-2 fw-semibold rounded-2 shadow-xs" style="font-size:0.75rem;">
                                                    <i class="fas fa-print me-1"></i> Cetak Label
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <div class="py-4">
                                            <i class="fas fa-box-open text-muted opacity-25 mb-3 display-4 d-block"></i>
                                            <h6 class="fw-bold text-dark mb-1">Tidak Ada Pesanan Siap Kirim</h6>
                                            <p class="small text-muted mb-0">Coba ubah kata kunci pencarian atau filter rentang tanggal Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <form id="single-tracking-form" action="" method="POST" class="d-none">
                @csrf
            </form>

            <div class="p-3 border-top bg-light rounded-bottom-4">
                {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const batchForm = $('#batch-form');
            const checkAll = $('#check-all');
            const checkboxes = $('.order-checkbox');

            $(document).on('click', '.btn-fetch-single-tracking', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                const orderId = $btn.data('order-id');
                const originalHtml = $btn.html();

                $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Menarik...').prop('disabled', true);

                fetch(`{{ url('/orders') }}/${orderId}/tracking`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Resi Berhasil Ditarik!',
                                text: data.message,
                                timer: 2500,
                                showConfirmButton: false
                            });
                        } else {
                            alert(data.message);
                        }

                        const parentDiv = $btn.closest('div');
                        if (parentDiv.length && data.tracking_number) {
                            parentDiv.replaceWith(`
                                <div class="bg-light border rounded px-2 py-0.5 font-monospace text-dark fw-bold d-inline-flex align-items-center gap-1" style="font-size:0.72rem;" title="Nomor Resi">
                                    <i class="fas fa-barcode text-muted"></i>
                                    <span>${data.tracking_number}</span>
                                </div>
                            `);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Tarik Resi',
                                text: data.message || 'Terjadi kesalahan saat menarik resi.',
                                confirmButtonColor: '#0d6efd'
                            });
                        } else {
                            alert(data.message || 'Gagal menarik resi.');
                        }
                        $btn.html(originalHtml).prop('disabled', false);
                    }
                })
                .catch(err => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Tarik Resi',
                            text: 'Terjadi kesalahan sistem atau koneksi: ' + err.message,
                            confirmButtonColor: '#0d6efd'
                        });
                    } else {
                        alert('Terjadi kesalahan koneksi.');
                    }
                    $btn.html(originalHtml).prop('disabled', false);
                });
            });

            $(document).on('click', '.btn-ship-single-order', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const orderId = $(this).data('order-id');
                if (confirm('Kirim pesanan ini ke Marketplace? Status akan diperbarui.')) {
                    const trackingForm = $('#single-tracking-form');
                    trackingForm.attr('action', `/orders/${orderId}/ship`);
                    $(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Mengirim...').prop('disabled', true);
                    trackingForm.submit();
                }
            });

            checkAll.on('change', function() {
                checkboxes.prop('checked', this.checked);
            });

            checkboxes.on('change', function() {
                checkAll.prop('checked', checkboxes.length === $('.order-checkbox:checked').length);
            });

            // Cetak Resi / Label Massal -> Kertas Stiker Thermal
            $('#btn-top-label').on('click', function() {
                const checked = $('.order-checkbox:checked');
                if (checked.length === 0) {
                    if (!confirm(
                            'Tidak ada pesanan yang diceklis. Apakah Anda ingin mencetak resi untuk SELURUH pesanan yang tampil di filter saat ini?'
                        )) {
                        return;
                    }
                }
                batchForm.attr('action', "{{ route('orders.mass_print') }}");
                batchForm.attr('method', "POST");
                batchForm.attr('target', "_blank");
                batchForm.submit();
            });

            // Kirim Resi ke API Marketplace
            $('#btn-top-ship').on('click', function() {
                const checked = $('.order-checkbox:checked');
                if (checked.length === 0) {
                    alert('Pilih minimal satu pesanan dengan mencentang kotak untuk pengiriman resi.');
                    return;
                }
                if (confirm('Kirim resi massal ke marketplace untuk pesanan terpilih?')) {
                    batchForm.attr('action', "{{ route('fulfillment.batch_ship') }}");
                    batchForm.attr('method', "POST");
                    batchForm.removeAttr('target');
                    batchForm.submit();
                }
            });
        });
    </script>
@endpush
