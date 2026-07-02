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
            box-shadow: 0 .75rem 2rem rgba(0, 0, 0, .12) !important;
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
    </style>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-gradient bg-primary text-white h-100 transition-hover">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="small opacity-75 text-uppercase fw-semibold d-block mb-1"
                                style="font-size: 0.72rem;">Total Retur</span>
                            <h3 class="fw-bold mb-0">{{ $totalReturns }}</h3>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded p-2 text-white">
                            <i class="fas fa-undo-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-gradient bg-warning text-white h-100 transition-hover">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="small opacity-75 text-uppercase fw-semibold d-block mb-1"
                                style="font-size: 0.72rem;">Belum QC</span>
                            <h3 class="fw-bold mb-0 text-white">{{ $pendingQc }}</h3>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded p-2 text-white">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-gradient bg-success text-white h-100 transition-hover">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="small opacity-75 text-uppercase fw-semibold d-block mb-1"
                                style="font-size: 0.72rem;">Layak Jual (Good)</span>
                            <h3 class="fw-bold mb-0">{{ $goodCount }}</h3>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded p-2 text-white">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-gradient bg-danger text-white h-100 transition-hover">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="small opacity-75 text-uppercase fw-semibold d-block mb-1"
                                style="font-size: 0.72rem;">Rusak / Cacat</span>
                            <h3 class="fw-bold mb-0">{{ $defectiveCount }}</h3>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded p-2 text-white">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
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
                        <a href="{{ route('returns.export', request()->query()) }}"
                            class="btn btn-outline-success btn-sm px-3 fw-semibold">
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
            <small class="text-muted d-block">Pantau pesanan yang dibatalkan atau dikembalikan oleh pembeli, lalu
                kembalikan stok fisik ke gudang secara otomatis.</small>
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
                                            <i
                                                class="fas fa-shopping-bag me-1"></i>{{ strtoupper($ret->store->channel->name ?? 'Marketplace') }}
                                        </span>
                                        <span class="text-secondary small fw-medium">
                                            <i class="fas fa-store me-1"></i>{{ $ret->store->store_name }}
                                        </span>
                                    </div>
                                    <div class="fw-bold font-monospace text-dark small" style="font-size: 0.85rem;">
                                        {{ $ret->return_sn }}</div>
                                    <div class="small text-muted mt-1">
                                        Invoice Asli: <a href="{{ route('orders.show', $ret->order->id) }}"
                                            class="text-primary fw-semibold text-decoration-none">
                                            {{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}
                                        </a>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Pembeli: <span
                                            class="fw-semibold text-dark">{{ $ret->order->buyer_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @foreach ($ret->items as $rItem)
                                            @php $mpProduct = $rItem->orderItem->marketplaceProduct ?? null; @endphp
                                            <div class="small text-muted d-flex align-items-start gap-1">
                                                <span
                                                    class="badge bg-light text-primary border border-primary-subtle px-2 py-1"
                                                    style="font-size: 0.72rem;">{{ $rItem->quantity }}x</span>
                                                <span class="text-wrap" style="max-width: 320px;">
                                                    {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small">{{ strtoupper($ret->status) }}</span>
                                    <div class="small text-muted fst-italic text-wrap mt-2 px-2"
                                        style="max-width: 200px; margin: 0 auto;">
                                        "{{ $ret->reason ?? 'Tidak ada alasan' }}"
                                    </div>
                                    @if ($ret->refund_amount > 0)
                                        <div class="mt-2 text-success fw-bold small" style="font-size: 0.8rem;">
                                            <i class="fas fa-wallet me-1"></i>Refund: Rp
                                            {{ number_format($ret->refund_amount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($ret->is_restocked)
                                        @if ($ret->inspection_status === 'GOOD')
                                            <div class="d-flex flex-column align-items-center">
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1.5 small mb-1 fw-bold">
                                                    <i class="fas fa-check-circle me-1"></i>Layak Jual
                                                </span>
                                                <span class="small text-muted font-monospace"
                                                    style="font-size: 0.72rem;">Stok Otomatis Ditambah</span>
                                                @if ($ret->inspection_notes)
                                                    <div class="mt-2 text-muted small text-wrap bg-light border p-1 rounded"
                                                        style="max-width:160px; font-size: 0.72rem;">
                                                        <i
                                                            class="fas fa-comment-alt text-secondary me-1"></i>"{{ $ret->inspection_notes }}"
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif ($ret->inspection_status === 'DEFECTIVE')
                                            <div class="d-flex flex-column align-items-center">
                                                <span
                                                    class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-3 py-1.5 small mb-1 fw-bold">
                                                    <i class="fas fa-times-circle me-1"></i>Rusak / Cacat
                                                </span>
                                                <span class="small text-muted font-monospace"
                                                    style="font-size: 0.72rem;">Stok Diabaikan</span>
                                                @if ($ret->inspection_notes)
                                                    <div class="mt-2 text-muted small text-wrap bg-light border p-1 rounded"
                                                        style="max-width:160px; font-size: 0.72rem;">
                                                        <i
                                                            class="fas fa-comment-alt text-secondary me-1"></i>"{{ $ret->inspection_notes }}"
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1.5 small fw-bold">
                                                <i class="fas fa-check-circle me-1"></i>Stok Dikembalikan
                                            </span>
                                        @endif
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
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title fw-bold text-dark"
                                                            id="qcModalLabel-{{ $ret->id }}">
                                                            <i class="fas fa-undo-alt text-primary me-2"></i>QC Retur:
                                                            {{ $ret->return_sn }}
                                                        </h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('returns.restock', $ret->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label
                                                                    class="form-label fw-semibold small text-muted text-uppercase">Barang
                                                                    yang Diretur:</label>
                                                                <div class="bg-light p-2 rounded">
                                                                    <ul
                                                                        class="list-group list-group-flush mb-0 bg-transparent">
                                                                        @foreach ($ret->items as $rItem)
                                                                            @php $mpProduct = $rItem->orderItem->marketplaceProduct ?? null; @endphp
                                                                            <li
                                                                                class="list-group-item px-0 py-2 d-flex align-items-center gap-2 bg-transparent">
                                                                                <i class="fas fa-box text-muted"></i>
                                                                                <span
                                                                                    class="badge bg-secondary">{{ $rItem->quantity }}
                                                                                    Pcs</span>
                                                                                <span
                                                                                    class="small fw-medium">{{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <hr class="my-3 text-muted">
                                                            <div class="mb-3">
                                                                <label for="inspection_status-{{ $ret->id }}"
                                                                    class="form-label fw-semibold">Hasil Inspeksi / Kondisi
                                                                    Barang</label>
                                                                <select name="inspection_status"
                                                                    id="inspection_status-{{ $ret->id }}"
                                                                    class="form-select form-select-sm" required>
                                                                    <option value="GOOD">Layak Jual / Good (Kembali ke
                                                                        Stok Aktif)</option>
                                                                    <option value="DEFECTIVE">Rusak / Defective (Tidak
                                                                        Mengubah Stok)</option>
                                                                </select>
                                                                <div class="form-text mt-2 small text-muted">
                                                                    <i class="fas fa-info-circle text-info me-1"></i>
                                                                    Jika dipilih <strong>Layak Jual</strong>, stok gudang
                                                                    akan bertambah dan otomatis di-push ke marketplace
                                                                    terkait.
                                                                </div>
                                                            </div>
                                                            <div class="mb-0">
                                                                <label for="inspection_notes-{{ $ret->id }}"
                                                                    class="form-label fw-semibold">Catatan Inspeksi
                                                                    (Opsional)</label>
                                                                <textarea name="inspection_notes" id="inspection_notes-{{ $ret->id }}" rows="3"
                                                                    class="form-control form-control-sm" placeholder="Tulis deskripsi kondisi fisik barang..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit"
                                                                class="btn btn-primary btn-sm fw-semibold">
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

@endsection
