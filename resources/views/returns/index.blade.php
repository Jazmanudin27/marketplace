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

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th class="ps-3 py-3">WAKTU DIBUAT</th>
                            <th class="py-3">DETAIL RETUR & INVOICE ASLI</th>
                            <th class="py-3">BARANG YANG DIRETUR</th>
                            <th class="py-3 text-center">ALASAN / STATUS</th>
                            <th class="py-3 text-center">TINDAKAN GUDANG</th>
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
                            <tr class="align-middle">
                                <td class="ps-3 text-muted small">{{ $ret->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge {{ $badgeClass }} small font-monospace px-2 py-1">
                                            <i class="fas fa-shopping-bag me-1"></i>{{ strtoupper($ret->store->channel->name ?? 'Marketplace') }}
                                        </span>
                                        <span class="text-secondary small fw-medium">
                                            <i class="fas fa-store me-1"></i>{{ $ret->store->store_name }}
                                        </span>
                                    </div>
                                    <div class="fw-bold font-monospace text-dark small" style="font-size: 0.85rem;">{{ $ret->return_sn }}</div>
                                    
                                    @if ($ret->return_tracking_number)
                                        <div class="small text-dark mt-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-truck text-muted me-1"></i>Resi: <span class="fw-bold text-secondary">{{ $ret->return_tracking_number }}</span>
                                            @if ($ret->shipping_provider)
                                                <span class="text-muted">({{ $ret->shipping_provider }})</span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                        Invoice Asli: <a href="{{ route('orders.show', $ret->order->id) }}"
                                            class="text-primary fw-semibold text-decoration-none">
                                            {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                        </a>
                                    </div>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                        Pembeli: <span class="fw-semibold text-dark">{{ $ret->order->buyer_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($ret->items as $rItem)
                                            @php $mpProduct = $rItem->orderItem->marketplaceProduct ?? null; @endphp
                                            <div class="small text-muted d-flex align-items-start gap-1 p-2 rounded bg-light border border-light-subtle">
                                                <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.72rem;">{{ $rItem->quantity }}x</span>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="text-dark fw-medium" style="font-size: 0.78rem;">
                                                            {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                                        </span>
                                                        @if($rItem->inspection_photo)
                                                            <a href="{{ asset($rItem->inspection_photo) }}" target="_blank" class="btn btn-outline-info btn-xs py-0 px-1.5" title="Lihat Foto Bukti QC">
                                                                <i class="fas fa-camera"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                    @if ($ret->is_restocked)
                                                        <div class="mt-1 d-flex align-items-center gap-1">
                                                            @if ($rItem->inspection_status === 'GOOD')
                                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-1 py-0.5" style="font-size: 0.65rem;">
                                                                    <i class="fas fa-check-circle me-0.5"></i>Layak Jual
                                                                </span>
                                                            @elseif ($rItem->inspection_status === 'DEFECTIVE')
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-1 py-0.5" style="font-size: 0.65rem;">
                                                                    <i class="fas fa-times-circle me-0.5"></i>Rusak/Cacat
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-1 py-0.5" style="font-size: 0.65rem;">
                                                                    Pending
                                                                </span>
                                                            @endif
                                                            @if ($rItem->inspection_notes)
                                                                <span class="text-muted fst-italic" style="font-size: 0.65rem;"> - "{{ $rItem->inspection_notes }}"</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small">{{ strtoupper($ret->status) }}</span>
                                    <div class="small text-muted fst-italic text-wrap mt-2 px-2" style="max-width: 200px; margin: 0 auto; font-size: 0.78rem;">
                                        "{{ $ret->reason ?? 'Tidak ada alasan' }}"
                                    </div>
                                    @if ($ret->refund_amount > 0)
                                        <div class="mt-2 text-success fw-bold small" style="font-size: 0.8rem;">
                                            <i class="fas fa-wallet me-1"></i>Refund: Rp {{ number_format($ret->refund_amount, 0, ',', '.') }}
                                        </div>
                                    @endif

                                    {{-- SLA Countdown Badge --}}
                                    @if(!$ret->is_restocked && $ret->sla_deadline)
                                        @php
                                            $diffInHours = round(now()->diffInHours($ret->sla_deadline, false));
                                        @endphp
                                        <div class="mt-2">
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
                                </td>
                                <td class="text-center">
                                    @if ($ret->is_restocked)
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1.5 small mb-1 fw-bold">
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

                                            {{-- Replacement Order Action/Tautan --}}
                                            @if($ret->replacement_order_id)
                                                <div class="mt-2 p-1.5 border rounded bg-light" style="font-size: 0.72rem; min-width: 140px;">
                                                    <span class="text-muted d-block" style="font-size: 0.65rem;">Order Pengganti:</span>
                                                    <a href="{{ route('orders.show', $ret->replacement_order_id) }}" class="text-primary fw-bold text-decoration-none">
                                                        <i class="fas fa-external-link-alt me-1"></i>{{ $ret->replacementOrder->invoice_number ?? 'Lihat Order' }}
                                                    </a>
                                                </div>
                                            @else
                                                <form action="{{ route('returns.replacement', $ret->id) }}" method="POST" class="mt-2">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-xs fw-semibold px-2 py-1" style="font-size: 0.7rem;" onclick="return confirm('Apakah Anda yakin ingin membuat pesanan pengganti gratis untuk retur ini?')">
                                                        <i class="fas fa-exchange-alt me-1"></i>Kirim Pengganti
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm"
                                            data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                            <i class="fas fa-clipboard-check me-1"></i>Terima & QC
                                        </button>
                                        <div class="small text-muted mt-2" style="font-size: 0.72rem;">
                                            Klik untuk periksa fisik<br>dan terima barang.
                                        </div>

                                        <!-- Modal QC -->
                                        <div class="modal fade text-start" id="qcModal-{{ $ret->id }}"
                                            tabindex="-1" aria-hidden="true">
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
                                                                    @php $mpProduct = $rItem->orderItem->marketplaceProduct ?? null; @endphp
                                                                    <div class="border rounded p-3 bg-light bg-opacity-50">
                                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                                            <i class="fas fa-box text-muted"></i>
                                                                            <span class="badge bg-secondary">{{ $rItem->quantity }} Pcs</span>
                                                                            <span class="fw-semibold small text-dark">{{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}</span>
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

@endsection
