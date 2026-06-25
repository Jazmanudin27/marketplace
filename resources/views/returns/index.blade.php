@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('returns.index') }}" class="mb-0">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-shopping-bag me-1"></i>Channel
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
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-store me-1"></i>Toko
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
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-info-circle me-1"></i>Status Retur
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
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-clipboard-check me-1"></i>Tindakan QC
                        </label>
                        <select name="is_restocked" class="form-select form-select-sm">
                            <option value="">Semua Tindakan</option>
                            <option value="0" {{ $isRestocked === '0' ? 'selected' : '' }}>Belum QC</option>
                            <option value="1" {{ $isRestocked === '1' ? 'selected' : '' }}>Sudah QC</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-search me-1"></i>Cari Resi / Invoice
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Resi / Invoice..." value="{{ $search }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
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
            <small class="text-muted d-block">Pantau pesanan yang dibatalkan atau dikembalikan oleh pembeli, lalu kembalikan stok fisik ke gudang.</small>
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
                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">WAKTU DIBUAT</th>
                            <th>DETAIL RETUR & INVOICE ASLI</th>
                            <th>BARANG YANG DIRETUR</th>
                            <th class="text-center">ALASAN / STATUS</th>
                            <th class="text-center">TINDAKAN GUDANG</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $ret)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $ret->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    <div class="fw-bold font-monospace small">{{ $ret->return_sn }}</div>
                                    <div class="small text-muted mt-1">
                                        Asal: <a href="{{ route('orders.show', $ret->order->id) }}"
                                            class="text-primary fw-medium text-decoration-none">{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}</a>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Pembeli: <span class="fw-semibold text-dark">{{ $ret->order->buyer_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <ul class="ps-3 mb-0 small text-muted">
                                        @foreach ($ret->items as $rItem)
                                            @php $mpProduct = $rItem->orderItem->marketplaceProduct ?? null; @endphp
                                            <li>
                                                <span class="text-primary fw-semibold">{{ $rItem->quantity }}x</span>
                                                {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger small">{{ $ret->status }}</span>
                                    <div class="small text-muted fst-italic text-wrap mt-1">
                                        "{{ $ret->reason ?? 'Tidak ada alasan' }}"
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($ret->is_restocked)
                                        @if ($ret->inspection_status === 'GOOD')
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-success small mb-1">
                                                    <i class="fas fa-check-circle me-1"></i>Layak Jual
                                                </span>
                                                <span class="small text-muted">Stok Ditambah</span>
                                                @if ($ret->inspection_notes)
                                                    <div class="mt-1 text-muted small text-wrap" style="max-width:150px;">
                                                        <i class="fas fa-comment-alt text-secondary me-1"></i>"{{ $ret->inspection_notes }}"
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif ($ret->inspection_status === 'DEFECTIVE')
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-danger small mb-1">
                                                    <i class="fas fa-times-circle me-1"></i>Rusak / Cacat
                                                </span>
                                                <span class="small text-muted">Stok Diabaikan</span>
                                                @if ($ret->inspection_notes)
                                                    <div class="mt-1 text-muted small text-wrap" style="max-width:150px;">
                                                        <i class="fas fa-comment-alt text-secondary me-1"></i>"{{ $ret->inspection_notes }}"
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge bg-success small">
                                                <i class="fas fa-check-circle me-1"></i>Stok Dikembalikan
                                            </span>
                                        @endif
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm px-3 fw-medium"
                                            data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                            <i class="fas fa-clipboard-check me-1"></i>Terima & QC
                                        </button>
                                        <div class="small text-muted mt-2">
                                            Klik untuk periksa fisik<br>dan terima barang.
                                        </div>

                                        <!-- Modal QC -->
                                        <div class="modal fade text-start" id="qcModal-{{ $ret->id }}"
                                            tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold" id="qcModalLabel-{{ $ret->id }}">
                                                            <i class="fas fa-undo-alt text-primary me-2"></i>QC Retur: {{ $ret->return_sn }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('returns.restock', $ret->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small text-muted text-uppercase">Barang yang Diretur:</label>
                                                                <ul class="list-group list-group-flush mb-0">
                                                                    @foreach ($ret->items as $rItem)
                                                                        @php $mpProduct = $rItem->orderItem->marketplaceProduct ?? null; @endphp
                                                                        <li class="list-group-item px-0 py-2 d-flex align-items-center gap-2">
                                                                            <i class="fas fa-box text-muted"></i>
                                                                            <span class="badge bg-secondary">{{ $rItem->quantity }} Pcs</span>
                                                                            <span class="small">{{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}</span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                            <hr>
                                                            <div class="mb-3">
                                                                <label for="inspection_status-{{ $ret->id }}" class="form-label fw-semibold">Hasil Inspeksi / Kondisi Barang</label>
                                                                <select name="inspection_status" id="inspection_status-{{ $ret->id }}"
                                                                    class="form-select form-select-sm" required>
                                                                    <option value="GOOD">Layak Jual / Good (Kembali ke Stok Aktif)</option>
                                                                    <option value="DEFECTIVE">Rusak / Defective (Tidak Mengubah Stok)</option>
                                                                </select>
                                                                <div class="form-text mt-2 small">
                                                                    <i class="fas fa-info-circle text-info me-1"></i>
                                                                    Jika dipilih <strong>Layak Jual</strong>, stok gudang akan bertambah dan otomatis di-push ke Shopee/TikTok.
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="inspection_notes-{{ $ret->id }}" class="form-label fw-semibold">Catatan Inspeksi (Opsional)</label>
                                                                <textarea name="inspection_notes" id="inspection_notes-{{ $ret->id }}" rows="3"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Tulis deskripsi kondisi fisik barang..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
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

@endsection
