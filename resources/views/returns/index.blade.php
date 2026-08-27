@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')
<style>
    .transition-hover {
        transition: transform 0.22s ease-in-out, box-shadow 0.22s ease-in-out;
    }
    .transition-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 .75rem 2rem rgba(0,0,0,.12)!important;
    }
    .badge-shopee {
        background-color: #FF5722 !important;
        color: #fff !important;
    }
    .badge-tiktok {
        background-color: #000000 !important;
        color: #fff !important;
        box-shadow: inset 0 0 0 1px #ff0050;
    }
    .badge-lazada {
        background-color: #0f146d !important;
        color: #fff !important;
    }
    .badge-tokopedia {
        background-color: #42b549 !important;
        color: #fff !important;
    }
    @keyframes pulse-red {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .pulse-danger-dot {
        width: 6px;
        height: 6px;
        background-color: #ef4444;
        border-radius: 50%;
        display: inline-block;
        animation: pulse-red 1.6s infinite;
    }
    .copy-btn {
        color: #6c757d;
        transition: color 0.15s ease-in-out;
    }
    .copy-btn:hover {
        color: #FF5722;
    }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

    {{-- Statistics & Analytics Section --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="row g-3 h-100">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm bg-gradient bg-primary text-white h-100 transition-hover">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small opacity-75 text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Retur</span>
                                <i class="fas fa-undo-alt opacity-50"></i>
                            </div>
                            <h3 class="fw-bold mb-0 mt-2">{{ $totalReturns }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm bg-gradient bg-warning text-white h-100 transition-hover">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small opacity-75 text-uppercase fw-semibold" style="font-size: 0.72rem;">Belum QC</span>
                                <i class="fas fa-clipboard-list opacity-50"></i>
                            </div>
                            <h3 class="fw-bold mb-0 mt-2">{{ $pendingQc }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm bg-gradient bg-success text-white h-100 transition-hover">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small opacity-75 text-uppercase fw-semibold" style="font-size: 0.72rem;">Layak Jual</span>
                                <i class="fas fa-check-circle opacity-50"></i>
                            </div>
                            <h3 class="fw-bold mb-0 mt-2">{{ $goodCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm bg-gradient bg-danger text-white h-100 transition-hover">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small opacity-75 text-uppercase fw-semibold" style="font-size: 0.72rem;">Rusak / Cacat</span>
                                <i class="fas fa-times-circle opacity-50"></i>
                            </div>
                            <h3 class="fw-bold mb-0 mt-2">{{ $defectiveCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;"><i class="fas fa-chart-pie text-info me-2"></i>Alasan Retur Terbanyak</h6>
                    <div class="d-flex align-items-center justify-content-center" style="height: 100px; position: relative;">
                        @if($reasonsStats->isEmpty())
                            <span class="small text-muted">Belum ada data alasan retur.</span>
                        @else
                            <canvas id="reasonsDonutChart"></canvas>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('returns.index') }}" class="mb-0">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1" style="font-size: 0.78rem;">
                            <i class="fas fa-shopping-bag me-1 text-muted"></i>Channel
                        </label>
                        <select name="channel_id" class="form-select form-select-sm">
                            <option value="">Semua Channel</option>
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->id }}" {{ $channelId == $channel->id ? 'selected' : '' }}>
                                    {{ $channel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1" style="font-size: 0.78rem;">
                            <i class="fas fa-store me-1 text-muted"></i>Toko
                        </label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="">Semua Toko</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1" style="font-size: 0.78rem;">
                            <i class="fas fa-info-circle me-1 text-muted"></i>Status Retur
                        </label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $statVal)
                                <option value="{{ $statVal }}" {{ $status == $statVal ? 'selected' : '' }}>
                                    {{ $statVal }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1" style="font-size: 0.78rem;">
                            <i class="fas fa-clipboard-check me-1 text-muted"></i>Tindakan QC
                        </label>
                        <select name="is_restocked" class="form-select form-select-sm">
                            <option value="">Semua Tindakan</option>
                            <option value="0" {{ $isRestocked === '0' ? 'selected' : '' }}>Belum QC</option>
                            <option value="1" {{ $isRestocked === '1' ? 'selected' : '' }}>Sudah QC</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1" style="font-size: 0.78rem;">
                            <i class="fas fa-search me-1 text-muted"></i>Cari Resi / Invoice
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Resi / Invoice..." value="{{ $search }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                        <a href="{{ route('returns.export', request()->query()) }}" class="btn btn-outline-success btn-sm px-3 fw-semibold">
                            <i class="fas fa-file-csv me-1"></i> Ekspor CSV
                        </a>
                        @if ($search || $channelId || $storeId || $status || ($isRestocked !== null && $isRestocked !== ''))
                            <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto ms-md-auto">
                        <button type="submit" form="syncForm" class="btn btn-success btn-sm fw-semibold w-100">
                            <i class="fas fa-sync-alt me-1"></i> Tarik Data
                        </button>
                    </div>
                </div>
            </form>
            <form id="syncForm" action="{{ route('returns.sync') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info bg-opacity-10 py-2 px-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-undo-alt me-2 text-info"></i>Pusat Resolusi & Retur</h6>
            <small class="text-muted d-block">Pantau pesanan yang dibatalkan atau dikembalikan oleh pembeli, lalu kembalikan stok fisik ke gudang secara otomatis.</small>
        </div>
        <div class="card-body p-0">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3 mb-0" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Shopee Style Sub-tabs Navigation -->
            <div class="border-bottom px-3 pt-2 bg-light bg-opacity-50">
                <ul class="nav nav-tabs border-0" id="returnTabs" role="tablist" style="margin-bottom: -1px;">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link border-0 pb-2 px-3 {{ is_null($status) && is_null($isRestocked) ? 'active fw-bold border-bottom border-primary text-primary' : 'text-secondary' }}" 
                           style="border-width: 0 0 3px 0 !important; border-style: solid !important; border-color: {{ is_null($status) && is_null($isRestocked) ? '#FF5722' : 'transparent' }} !important; color: {{ is_null($status) && is_null($isRestocked) ? '#FF5722' : '#6c757d' }} !important;" 
                           href="{{ route('returns.index') }}">Semua</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link border-0 pb-2 px-3 {{ $isRestocked === '0' ? 'active fw-bold border-bottom border-primary text-primary' : 'text-secondary' }}" 
                           style="border-width: 0 0 3px 0 !important; border-style: solid !important; border-color: {{ $isRestocked === '0' ? '#FF5722' : 'transparent' }} !important; color: {{ $isRestocked === '0' ? '#FF5722' : '#6c757d' }} !important;" 
                           href="{{ route('returns.index', array_merge(request()->except('page'), ['is_restocked' => '0', 'status' => null])) }}">Dalam Pengecekan (Belum QC)</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link border-0 pb-2 px-3 {{ $isRestocked === '1' ? 'active fw-bold border-bottom border-primary text-primary' : 'text-secondary' }}" 
                           style="border-width: 0 0 3px 0 !important; border-style: solid !important; border-color: {{ $isRestocked === '1' ? '#FF5722' : 'transparent' }} !important; color: {{ $isRestocked === '1' ? '#FF5722' : '#6c757d' }} !important;" 
                           href="{{ route('returns.index', array_merge(request()->except('page'), ['is_restocked' => '1', 'status' => null])) }}">Sudah QC (Selesai QC)</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link border-0 pb-2 px-3 {{ $status === 'REQUESTED' ? 'active fw-bold border-bottom border-primary text-primary' : 'text-secondary' }}" 
                           style="border-width: 0 0 3px 0 !important; border-style: solid !important; border-color: {{ $status === 'REQUESTED' ? '#FF5722' : 'transparent' }} !important; color: {{ $status === 'REQUESTED' ? '#FF5722' : '#6c757d' }} !important;" 
                           href="{{ route('returns.index', array_merge(request()->except('page'), ['status' => 'REQUESTED', 'is_restocked' => null])) }}">Pengajuan Baru</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link border-0 pb-2 px-3 {{ $status === 'CLOSED' || $status === 'COMPLETED' ? 'active fw-bold border-bottom border-primary text-primary' : 'text-secondary' }}" 
                           style="border-width: 0 0 3px 0 !important; border-style: solid !important; border-color: {{ $status === 'CLOSED' || $status === 'COMPLETED' ? '#FF5722' : 'transparent' }} !important; color: {{ $status === 'CLOSED' || $status === 'COMPLETED' ? '#FF5722' : '#6c757d' }} !important;" 
                           href="{{ route('returns.index', array_merge(request()->except('page'), ['status' => 'CLOSED', 'is_restocked' => null])) }}">Selesai</a>
                    </li>
                </ul>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6;">
                            <th class="ps-3 py-3" style="width: 35%;">Produk</th>
                            <th class="py-3 text-center" style="width: 15%;">Jumlah Pengembalian Dana</th>
                            <th class="py-3 text-center" style="width: 20%;">Alasan & Status</th>
                            <th class="py-3 text-center" style="width: 15%;">Tindakan QC / Gudang</th>
                            <th class="py-3 text-center" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $ret)
                            @php
                                $channelCode = strtolower($ret->store->channel->code ?? '');
                                $badgeClass = 'bg-secondary';
                                if ($channelCode === 'shopee') {
                                    $badgeClass = 'badge-shopee';
                                } elseif ($channelCode === 'tiktok') {
                                    $badgeClass = 'badge-tiktok';
                                } elseif ($channelCode === 'lazada') {
                                    $badgeClass = 'badge-lazada';
                                } elseif ($channelCode === 'tokopedia') {
                                    $badgeClass = 'badge-tokopedia';
                                }
                            @endphp
                            
                            <!-- Group Header (Buyer info, Store info, Invoice links, No. Pengajuan) -->
                            <tr class="table-light" style="background-color: #fafbfc; border-top: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                <td colspan="5" class="py-2 px-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $badgeClass }} px-2 py-1 font-monospace" style="font-size: 0.7rem;">
                                                <i class="fas fa-shopping-bag me-1"></i>{{ strtoupper($ret->store->channel->name ?? 'Marketplace') }}
                                            </span>
                                            <span class="text-secondary small fw-bold">
                                                <i class="fas fa-store me-1"></i>{{ $ret->store->store_name }}
                                            </span>
                                            <span class="text-muted">|</span>
                                            <span class="fw-bold text-dark small">
                                                <i class="fas fa-user me-1 text-muted text-secondary"></i>{{ $ret->order->buyer_name ?? '-' }}
                                            </span>
                                        </div>
                                        
                                        <div class="d-flex flex-wrap align-items-center gap-3 font-monospace small" style="font-size: 0.78rem;">
                                            <div>
                                                <span class="text-muted">No. Pengajuan:</span>
                                                <span class="fw-bold text-dark">{{ $ret->return_sn }}</span>
                                                <button type="button" class="btn btn-link btn-xs p-0 text-muted copy-btn" data-clipboard-text="{{ $ret->return_sn }}" title="Salin No. Pengajuan">
                                                    <i class="far fa-copy ms-1"></i>
                                                </button>
                                            </div>
                                            <div>
                                                <span class="text-muted">No. Pesanan:</span>
                                                <a href="{{ route('orders.show', $ret->order->id) }}" class="text-primary fw-bold text-decoration-none">
                                                    {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                                </a>
                                                <button type="button" class="btn btn-link btn-xs p-0 text-muted copy-btn" data-clipboard-text="{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}" title="Salin No. Pesanan">
                                                    <i class="far fa-copy ms-1"></i>
                                                </button>
                                            </div>
                                            @if ($ret->return_tracking_number)
                                                <div>
                                                    <span class="text-muted">Resi Retur:</span>
                                                    <span class="fw-bold text-secondary">{{ $ret->return_tracking_number }}</span>
                                                    @if ($ret->shipping_provider)
                                                        <span class="text-muted">({{ $ret->shipping_provider }})</span>
                                                    @endif
                                                </div>
                                            @endif
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                <i class="far fa-clock me-1"></i>{{ $ret->created_at->format('d M Y, H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Group Body -->
                            <tr style="border-bottom: 2px solid #dee2e6;">
                                <!-- Column 1: Produk -->
                                <td class="ps-3 py-3 align-top">
                                    <div class="d-flex flex-column gap-3">
                                        @foreach ($ret->items as $rItem)
                                            @php
                                                $orderItem = $rItem->orderItem;
                                                $mpProduct = $orderItem->marketplaceProduct ?? null;
                                                $imgUrl = $orderItem->product_image ?? null;
                                                $sku = $orderItem->sku ?? $mpProduct->sku ?? '—';
                                                $variant = $orderItem->variant_name ?? '—';
                                            @endphp
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="position-relative border rounded overflow-hidden" style="width: 54px; height: 54px; flex-shrink: 0; background-color: #f8f9fa;">
                                                    @if($imgUrl)
                                                        <img src="{{ $imgUrl }}" alt="Product Image" style="width: 100%; height: 100%; object-fit: cover;">
                                                    @else
                                                        <div class="d-flex align-items-center justify-content-center h-100 w-100 text-muted" style="background-color: #f3f4f6;">
                                                            <i class="fas fa-box fs-5"></i>
                                                        </div>
                                                    @endif
                                                    <span class="position-absolute bottom-0 end-0 bg-dark bg-opacity-75 text-white px-1.5 py-0.25 small font-monospace fw-bold" style="font-size: 0.65rem; border-top-left-radius: 4px;">
                                                        x{{ $rItem->quantity }}
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="text-dark fw-bold text-truncate-2 small" style="font-size: 0.82rem; line-height: 1.3;">
                                                        {{ $mpProduct ? $mpProduct->name : ($orderItem->product_name ?? 'Item Tidak Ditemukan') }}
                                                    </div>
                                                    <div class="text-muted mt-1 d-flex flex-wrap align-items-center gap-2" style="font-size: 0.72rem;">
                                                        @if($sku && $sku !== '—')
                                                            <span>SKU: <span class="fw-medium text-secondary">{{ $sku }}</span></span>
                                                        @endif
                                                        @if($variant && $variant !== '—')
                                                            <span class="badge bg-light text-dark border px-1.5 py-0.5" style="font-size: 0.65rem;">Variasi: {{ $variant }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                
                                <!-- Column 2: Jumlah Pengembalian Dana -->
                                <td class="text-center align-top py-3">
                                    @if ($ret->refund_amount > 0)
                                        <div class="text-success fw-bold font-monospace" style="font-size: 0.95rem;">
                                            Rp {{ number_format($ret->refund_amount, 0, ',', '.') }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.68rem;">Pengembalian Dana</div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                
                                <!-- Column 3: Alasan & Status -->
                                <td class="align-top py-3 px-3">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small fw-bold">
                                            {{ strtoupper($ret->status) }}
                                        </span>
                                        
                                        <div class="small text-muted text-center mt-2 p-2 rounded bg-light border border-light-subtle" style="font-size: 0.75rem; max-width: 220px; line-height: 1.4;">
                                            <i class="fas fa-quote-left text-muted me-1 small"></i>
                                            <span>{{ $ret->reason ?? 'Tidak ada alasan' }}</span>
                                            <i class="fas fa-quote-right text-muted ms-1 small"></i>
                                        </div>
                                        
                                        @php $hasProof = false; @endphp
                                        @foreach ($ret->items as $rItem)
                                            @if($rItem->inspection_photo)
                                                @php $hasProof = true; @endphp
                                            @endif
                                        @endforeach
                                        @if($hasProof)
                                            <div class="mt-2 text-center">
                                                <span class="text-muted small d-block mb-1" style="font-size: 0.68rem;">Bukti Foto QC:</span>
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @foreach ($ret->items as $rItem)
                                                        @if($rItem->inspection_photo)
                                                            <a href="{{ asset($rItem->inspection_photo) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-1.5" title="Lihat Foto Bukti QC">
                                                                <i class="fas fa-camera me-1"></i>Foto
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if(!$ret->is_restocked && $ret->sla_deadline)
                                            @php
                                                $diffInHours = round(now()->diffInHours($ret->sla_deadline, false));
                                            @endphp
                                            <div class="mt-2 text-center">
                                                @if($diffInHours < 0)
                                                    <span class="badge bg-danger text-white px-2 py-1" style="font-size: 0.7rem;">
                                                        <i class="fas fa-hourglass-end me-1"></i>SLA Habis
                                                    </span>
                                                @elseif($diffInHours <= 24)
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.7rem;" title="Deadline Respons Retur">
                                                        <span class="pulse-danger-dot me-1"></span>Sisa: {{ (int) $diffInHours }} Jam
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2 py-1" style="font-size: 0.7rem;" title="Deadline Respons Retur">
                                                        <i class="fas fa-clock me-1"></i>Sisa: {{ round($diffInHours / 24) }} Hari
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- Column 4: Tindakan QC / Gudang -->
                                <td class="text-center align-top py-3">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        @if ($ret->is_restocked)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5 small mb-1 fw-bold">
                                                <i class="fas fa-clipboard-check me-1"></i>Sudah QC
                                            </span>
                                            @if ($ret->checkedBy)
                                                <span class="small text-muted font-monospace" style="font-size: 0.7rem;">
                                                    <i class="fas fa-user-check me-1"></i>{{ $ret->checkedBy->name }}
                                                </span>
                                            @endif
                                            <span class="small text-muted font-monospace mt-1" style="font-size: 0.68rem;">
                                                {{ $ret->updated_at->format('d M Y, H:i') }}
                                            </span>
                                            
                                            @foreach($ret->items as $rItem)
                                                <div class="mt-1.5 d-flex flex-column align-items-center gap-0.5">
                                                    @if ($rItem->inspection_status === 'GOOD')
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                                                            <i class="fas fa-check-circle me-0.5"></i>Layak Jual
                                                        </span>
                                                    @elseif ($rItem->inspection_status === 'DEFECTIVE')
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                                                            <i class="fas fa-times-circle me-0.5"></i>Rusak/Cacat
                                                        </span>
                                                    @endif
                                                    @if ($rItem->inspection_notes)
                                                        <span class="text-muted fst-italic text-wrap text-center" style="font-size: 0.65rem; max-width: 140px;">"{{ $rItem->inspection_notes }}"</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle px-2.5 py-1.5 small mb-1 fw-bold">
                                                <i class="fas fa-hourglass-half me-1"></i>Belum QC
                                            </span>
                                            <span class="small text-muted text-center" style="font-size: 0.68rem; line-height: 1.3;">Perlu pemeriksaan<br>fisik barang</span>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- Column 5: Aksi -->
                                <td class="text-center align-top py-3">
                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        @if ($ret->is_restocked)
                                            @if($ret->replacement_order_id)
                                                <div class="p-2 border rounded bg-light" style="font-size: 0.72rem; min-width: 130px; line-height: 1.3;">
                                                    <span class="text-muted d-block mb-1" style="font-size: 0.65rem;">Order Pengganti:</span>
                                                    <a href="{{ route('orders.show', $ret->replacement_order_id) }}" class="text-primary fw-bold text-decoration-none">
                                                        <i class="fas fa-external-link-alt me-1"></i>{{ $ret->replacementOrder->invoice_number ?? 'Lihat Order' }}
                                                    </a>
                                                </div>
                                            @else
                                                <form action="{{ route('returns.replacement', $ret->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-xs fw-semibold px-2 py-1" style="font-size: 0.7rem;" onclick="return confirm('Apakah Anda yakin ingin membuat pesanan pengganti gratis untuk retur ini?')">
                                                        <i class="fas fa-exchange-alt me-1"></i>Kirim Pengganti
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm w-100" style="max-width: 130px;"
                                                data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                                <i class="fas fa-clipboard-check me-1"></i>Terima & QC
                                            </button>
                                        @endif
                                        <a href="{{ route('orders.show', $ret->order->id) }}" class="btn btn-outline-secondary btn-xs fw-semibold px-2 py-1 mt-1" style="font-size: 0.7rem;">
                                            <i class="fas fa-eye me-1"></i>Lihat Order
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center p-5 text-muted">
                                    <i class="fas fa-box-open fs-1 opacity-25 mb-3 d-block"></i>
                                    Belum ada data barang retur. Klik "Tarik Data" untuk memeriksa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $returns->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

    {{-- Render QC Modals outside table for valid DOM structure --}}
    @foreach ($returns as $ret)
        @if (!$ret->is_restocked)
            <!-- Modal QC -->
            <div class="modal fade text-start" id="qcModal-{{ $ret->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold text-dark" id="qcModalLabel-{{ $ret->id }}">
                                <i class="fas fa-undo-alt text-primary me-2"></i>QC Retur: {{ $ret->return_sn }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('returns.restock', $ret->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.78rem;">
                                    <i class="fas fa-info-circle me-1"></i> Periksa fisik masing-masing produk di bawah ini, unggah foto bukti fisik, dan tentukan kelayakannya untuk dikembalikan ke stok aktif gudang.
                                </div>
                                
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($ret->items as $rItem)
                                        @php 
                                            $orderItem = $rItem->orderItem;
                                            $mpProduct = $orderItem ? $orderItem->marketplaceProduct : null;
                                            $itemName = $mpProduct ? $mpProduct->name : ($orderItem->product_name ?? 'Item Tidak Ditemukan');
                                        @endphp
                                        <div class="border rounded p-3 bg-light bg-opacity-50">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="fas fa-box text-muted"></i>
                                                <span class="badge bg-secondary">{{ $rItem->quantity }} Pcs</span>
                                                <span class="fw-semibold small text-dark">{{ $itemName }}</span>
                                            </div>
                                            
                                            <div class="row g-2">
                                                <div class="col-12 col-md-5">
                                                    <label class="form-label fw-semibold small mb-1">Hasil Inspeksi / Kondisi:</label>
                                                    <select name="items[{{ $rItem->id }}][inspection_status]" class="form-select form-select-sm" required>
                                                        <option value="GOOD">Layak Jual / Good (Masuk Stok)</option>
                                                        <option value="DEFECTIVE">Rusak / Defective (Abaikan Stok)</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-7">
                                                    <label class="form-label fw-semibold small mb-1">Catatan (Opsional):</label>
                                                    <input type="text" name="items[{{ $rItem->id }}][inspection_notes]" class="form-control form-control-sm" placeholder="Contoh: Plastik terbuka, mulus...">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small mb-1 mt-1">Unggah Foto Bukti QC (Opsional):</label>
                                                    <input type="file" name="items[{{ $rItem->id }}][photo]" class="form-control form-control-sm" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                                    <i class="fas fa-check me-1"></i>Simpan Hasil QC
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

@if(!$reasonsStats->isEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('reasonsDonutChart').getContext('2d');
        const data = @json($reasonsStats->pluck('count'));
        const labels = @json($reasonsStats->pluck('reason'));

        // Shorten reasons labels if too long
        const shortLabels = labels.map(label => label.length > 20 ? label.substring(0, 17) + '...' : label);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: shortLabels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#4f46e5', // indigo
                        '#0ea5e9', // sky
                        '#f59e0b', // amber
                        '#ef4444', // red
                        '#10b981'  // emerald
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            boxWidth: 8,
                            padding: 6,
                            font: {
                                size: 8.5
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return labels[context[0].dataIndex];
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    });
</script>
@endif

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const text = this.getAttribute('data-clipboard-text');
                navigator.clipboard.writeText(text).then(() => {
                    const icon = this.querySelector('i');
                    icon.className = 'fas fa-check text-success';
                    setTimeout(() => {
                        icon.className = 'far fa-copy';
                    }, 1500);
                });
            });
        });
    });
</script>
@endsection
