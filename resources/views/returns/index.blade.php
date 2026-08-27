@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border mb-4">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fas fa-undo-alt text-primary me-2"></i>Pusat Resolusi & Retur
        </h5>
        <div>
            <button type="submit" form="syncForm" class="btn btn-success btn-sm fw-semibold rounded-pill px-3">
                <i class="fas fa-sync-alt me-1"></i> Tarik Data
            </button>
        </div>
    </div>

    {{-- Sync Form --}}
    <form id="syncForm" action="{{ route('returns.sync') }}" method="POST" class="d-none">
        @csrf
    </form>

    {{-- Alert Messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Main Bootstrap 5 Card --}}
    <div class="card border shadow-sm rounded">
        
        {{-- Status Tabs --}}
        <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs" id="returnStatusTabs">
                <li class="nav-item">
                    <a class="nav-link {{ is_null($status) && is_null($isRestocked) ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('returns.index') }}">
                        Semua <span class="badge bg-secondary ms-1 rounded-pill">{{ $totalReturns }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $isRestocked === '0' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['is_restocked' => '0', 'status' => null])) }}">
                        Dalam Pengecekan (Belum QC) <span class="badge bg-secondary ms-1 rounded-pill">{{ $pendingQc }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $isRestocked === '1' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['is_restocked' => '1', 'status' => null])) }}">
                        Sudah QC (Selesai QC) <span class="badge bg-secondary ms-1 rounded-pill">{{ $alreadyQc }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'REQUESTED' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['status' => 'REQUESTED', 'is_restocked' => null])) }}">
                        Pengajuan Baru <span class="badge bg-secondary ms-1 rounded-pill">{{ $newRequested }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status === 'CLOSED' || $status === 'COMPLETED' ? 'active fw-bold text-primary' : 'text-secondary' }}" href="{{ route('returns.index', array_merge(request()->except('page'), ['status' => 'CLOSED', 'is_restocked' => null])) }}">
                        Selesai <span class="badge bg-secondary ms-1 rounded-pill">{{ $completedClosed }}</span>
                    </a>
                </li>
            </ul>
        </div>

        {{-- Filter Bar --}}
        <div class="card-body bg-light border-top border-bottom py-3">
            <form method="GET" action="{{ route('returns.index') }}" class="mb-0">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if (request('is_restocked') !== null && request('is_restocked') !== '')
                    <input type="hidden" name="is_restocked" value="{{ request('is_restocked') }}">
                @endif
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">
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
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">
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
                        <label class="form-label small fw-bold text-secondary mb-1">
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
                        <label class="form-label small fw-bold text-secondary mb-1">
                            <i class="fas fa-search me-1 text-muted"></i>Cari Resi / Invoice
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Resi / Invoice..." value="{{ $search }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm fw-semibold rounded-pill px-3 flex-grow-1">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if ($search || $channelId || $storeId || $status || ($isRestocked !== null && $isRestocked !== ''))
                                <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="Reset Filter">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Summary Bar --}}
        <div class="card-body bg-white border-bottom py-2 px-3">
            <span class="small text-secondary fw-semibold">
                <i class="fas fa-list-ul me-1"></i>
                <strong>{{ $returns->total() }}</strong> Pengajuan Retur Ditemukan
                @if ($returns->total() > 0)
                    &nbsp;·&nbsp; Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}
                @endif
            </span>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr class="table-light text-secondary border-bottom">
                        <th class="ps-3 py-2 text-start">Produk</th>
                        <th class="py-2 text-center">Jumlah Pengembalian Dana</th>
                        <th class="py-2 text-center">Alasan & Status</th>
                        <th class="py-2 text-center">Tindakan QC / Gudang</th>
                        <th class="py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        @php
                            $channelCode = strtolower($ret->store->channel->code ?? '');
                            $badgeClass = 'bg-secondary';
                            if ($channelCode === 'shopee') {
                                $badgeClass = 'bg-danger';
                            } elseif ($channelCode === 'tiktok') {
                                $badgeClass = 'bg-dark';
                            } elseif ($channelCode === 'lazada') {
                                $badgeClass = 'bg-primary';
                            } elseif ($channelCode === 'tokopedia') {
                                $badgeClass = 'bg-success';
                            }
                        @endphp
                        
                        <!-- Group Header -->
                        <tr class="table-light border-top border-bottom">
                            <td colspan="5" class="py-2 px-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 small">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $badgeClass }} px-2 py-1">
                                            <i class="fas fa-shopping-bag me-1"></i>{{ strtoupper($ret->store->channel->name ?? 'Marketplace') }}
                                        </span>
                                        <span class="text-secondary fw-bold">
                                            <i class="fas fa-store me-1"></i>{{ $ret->store->store_name }}
                                        </span>
                                        <span class="text-muted">|</span>
                                        <span class="fw-bold text-dark">
                                            <i class="fas fa-user me-1 text-secondary"></i>{{ $ret->order->buyer_name ?? '-' }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap align-items-center gap-3 font-monospace">
                                        <div>
                                            <span class="text-muted">No. Pengajuan:</span>
                                            <span class="fw-bold text-dark">{{ $ret->return_sn }}</span>
                                            <button type="button" class="btn btn-link p-0 text-decoration-none text-secondary" data-clipboard-text="{{ $ret->return_sn }}" title="Salin No. Pengajuan">
                                                <i class="far fa-copy ms-1"></i>
                                            </button>
                                        </div>
                                        <div>
                                            <span class="text-muted">No. Pesanan:</span>
                                            <a href="{{ route('orders.show', $ret->order->id) }}" class="text-primary fw-bold text-decoration-none">
                                                {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                            </a>
                                            <button type="button" class="btn btn-link p-0 text-decoration-none text-secondary" data-clipboard-text="{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}" title="Salin No. Pesanan">
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
                                        <div class="text-muted">
                                            <i class="far fa-clock me-1"></i>{{ $ret->created_at->format('d M Y, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Group Body -->
                        <tr class="border-bottom">
                            <!-- Column 1: Produk -->
                            <td class="ps-3 py-3 align-top">
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($ret->items as $rItem)
                                        @php
                                            $orderItem = $rItem->orderItem;
                                            $mpProduct = $orderItem ? $orderItem->marketplaceProduct : null;
                                            $imgUrl = $orderItem ? $orderItem->product_image : null;
                                            $sku = $orderItem ? ($orderItem->sku ?? ($mpProduct ? $mpProduct->sku : '—')) : '—';
                                            $variant = $orderItem ? ($orderItem->variant_name ?? '—') : '—';
                                        @endphp
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="position-relative border rounded overflow-hidden flex-shrink-0 bg-light" style="width: 50px; height: 50px;">
                                                @if($imgUrl)
                                                    <img src="{{ $imgUrl }}" width="50" height="50" alt="Product Image" class="object-fit-cover">
                                                @else
                                                    <div class="d-flex align-items-center justify-content-center h-100 w-100 text-muted">
                                                        <i class="fas fa-box fs-5"></i>
                                                    </div>
                                                @endif
                                                <span class="position-absolute bottom-0 end-0 bg-dark bg-opacity-75 text-white px-1 py-0 small font-monospace fw-bold" style="font-size: 0.65rem;">
                                                    x{{ $rItem->quantity }}
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="text-dark fw-bold text-truncate small">
                                                    {{ $mpProduct ? $mpProduct->name : ($orderItem->product_name ?? 'Item Tidak Ditemukan') }}
                                                </div>
                                                <div class="text-muted mt-1 small d-flex flex-wrap align-items-center gap-2">
                                                    @if($sku && $sku !== '—')
                                                        <span>SKU: <span class="fw-medium text-secondary">{{ $sku }}</span></span>
                                                    @endif
                                                    @if($variant && $variant !== '—')
                                                        <span class="badge bg-light text-dark border px-2 py-0.5">Variasi: {{ $variant }}</span>
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
                                    <div class="text-success fw-bold font-monospace">
                                        Rp {{ number_format($ret->refund_amount, 0, ',', '.') }}
                                    </div>
                                    <div class="small text-muted">Pengembalian Dana</div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            
                            <!-- Column 3: Alasan & Status -->
                            <td class="align-top py-3 px-3">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge rounded-pill bg-danger text-white px-2.5 py-1 fw-bold">
                                        {{ strtoupper($ret->status) }}
                                    </span>
                                    
                                    <div class="small text-secondary mt-2 text-center">
                                        Alasan: <span class="fw-medium text-dark">{{ $ret->formatted_reason }}</span>
                                    </div>
                                    
                                    @php $hasProof = false; @endphp
                                    @foreach ($ret->items as $rItem)
                                        @if($rItem->inspection_photo)
                                            @php $hasProof = true; @endphp
                                        @endif
                                    @endforeach
                                    @if($hasProof)
                                        <div class="mt-2 text-center">
                                            <span class="text-muted small d-block mb-1">Bukti Foto QC:</span>
                                            <div class="d-flex gap-1 justify-content-center">
                                                @foreach ($ret->items as $rItem)
                                                    @if($rItem->inspection_photo)
                                                        <a href="{{ asset($rItem->inspection_photo) }}" target="_blank" class="btn btn-outline-info btn-xs py-0.5 px-2 rounded-pill" title="Lihat Foto Bukti QC">
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
                                                <span class="badge bg-danger text-white px-2 py-1">
                                                    <i class="fas fa-hourglass-end me-1"></i>SLA Habis
                                                </span>
                                            @elseif($diffInHours <= 24)
                                                <span class="badge bg-danger text-white px-2 py-1" title="Deadline Respons Retur">
                                                    Sisa: {{ (int) $diffInHours }} Jam
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark px-2 py-1" title="Deadline Respons Retur">
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
                                        <span class="badge rounded-pill bg-success text-white px-2.5 py-1 mb-1 fw-bold">
                                            <i class="fas fa-check-circle me-1"></i>Sudah QC
                                        </span>
                                        @if ($ret->checkedBy)
                                            <span class="small text-muted font-monospace">
                                                <i class="fas fa-user-check me-1"></i>{{ $ret->checkedBy->name }}
                                            </span>
                                        @endif
                                        <span class="small text-muted font-monospace mt-1">
                                            {{ $ret->updated_at->format('d M Y, H:i') }}
                                        </span>
                                        
                                        @foreach($ret->items as $rItem)
                                            <div class="mt-1 d-flex flex-column align-items-center gap-1">
                                                @if ($rItem->inspection_status === 'GOOD')
                                                    <span class="badge rounded-pill bg-success text-white px-2 py-0.5">
                                                        <i class="fas fa-check me-1"></i>Layak Jual
                                                    </span>
                                                @elseif ($rItem->inspection_status === 'DEFECTIVE')
                                                    <span class="badge rounded-pill bg-danger text-white px-2 py-0.5">
                                                        <i class="fas fa-times me-1"></i>Rusak/Cacat
                                                    </span>
                                                @endif
                                                @if ($rItem->inspection_notes)
                                                    <span class="text-muted fst-italic text-wrap text-center">"{{ $rItem->inspection_notes }}"</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="badge rounded-pill bg-warning text-dark px-2.5 py-1 mb-1 fw-bold">
                                            <i class="fas fa-hourglass-half me-1"></i>Belum QC
                                        </span>
                                        <span class="small text-muted text-center">Menunggu QC Fisik</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Column 5: Aksi -->
                            <td class="text-center align-top py-3">
                                <div class="d-flex flex-column gap-2 align-items-center">
                                    @if ($ret->is_restocked)
                                        @if($ret->replacement_order_id)
                                            <div class="p-2 border rounded bg-light small" style="min-width: 120px;">
                                                <span class="text-muted d-block mb-1">Order Pengganti:</span>
                                                <a href="{{ route('orders.show', $ret->replacement_order_id) }}" class="text-primary fw-bold text-decoration-none">
                                                    <i class="fas fa-external-link-alt me-1"></i>{{ $ret->replacementOrder->invoice_number ?? 'Lihat Order' }}
                                                </a>
                                            </div>
                                        @else
                                            <form action="{{ route('returns.replacement', $ret->id) }}" method="POST" class="w-100 d-flex justify-content-center">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold w-100" style="max-width: 120px;" onclick="return confirm('Apakah Anda yakin ingin membuat pesanan pengganti gratis untuk retur ini?')">
                                                    <i class="fas fa-exchange-alt me-1"></i>Kirim Pengganti
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold shadow-sm w-100" style="max-width: 120px;"
                                            data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                            <i class="fas fa-clipboard-check me-1"></i>Terima & QC
                                        </button>
                                    @endif
                                    <a href="{{ route('orders.show', $ret->order->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold px-3 mt-1 w-100" style="max-width: 120px;">
                                        <i class="fas fa-eye me-1"></i>Lihat Order
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-5 text-muted">
                                <i class="fas fa-box-open fs-1 opacity-25 mb-3 d-block"></i>
                                Belum ada data barang retur.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($returns->hasPages())
            <div class="card-footer bg-white d-flex justify-content-center">
                {{ $returns->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
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
                                <div class="alert alert-info py-2 px-3 mb-3 small">
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
