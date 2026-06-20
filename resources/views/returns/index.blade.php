@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Filter Card ───────────────────────────────────────────── --}}
            <div class="dashboard-card mb-3 py-3">
                <form method="GET" action="{{ route('returns.index') }}" class="mb-0">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
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
                            <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
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
                            <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
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
                            <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                                <i class="fas fa-clipboard-check me-1"></i>Tindakan QC
                            </label>
                            <select name="is_restocked" class="form-select form-select-sm">
                                <option value="">Semua Tindakan</option>
                                <option value="0" {{ $isRestocked === '0' ? 'selected' : '' }}>Belum QC</option>
                                <option value="1" {{ $isRestocked === '1' ? 'selected' : '' }}>Sudah QC</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                                <i class="fas fa-search me-1"></i>Cari Resi / Invoice
                            </label>
                            <input type="text" name="search"
                                class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                placeholder="Resi / Invoice..." value="{{ $search }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if ($search || $channelId || $storeId || $status || ($isRestocked !== null && $isRestocked !== ''))
                                <a href="{{ route('returns.index') }}" class="btn btn-secondary btn-sm px-2">
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

            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="dashboard-card">
                {{-- ── Header ──────────────────────────────────────────── --}}
                <div class="card-header-line">
                    <h5 class="mb-0"><i class="fas fa-undo-alt me-2 text-primary"></i>Pusat Resolusi &amp; Retur</h5>
                    <p class="text-muted mb-0 mt-1 small">
                        Pantau pesanan yang dibatalkan atau dikembalikan oleh pembeli, lalu kembalikan stok fisik ke gudang.
                    </p>
                </div>

                {{-- ── Alert ───────────────────────────────────────────── --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- ── Tabel ────────────────────────────────────────────── --}}
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">WAKTU DIBUAT</th>
                                <th>DETAIL RETUR &amp; INVOICE ASLI</th>
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
                                        <div class="fw-bold text-white font-monospace">{{ $ret->return_sn }}</div>
                                        <div class="small text-muted mt-1">
                                            Asal: <a href="{{ route('orders.show', $ret->order->id) }}"
                                                class="text-primary fw-medium text-decoration-none">{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}</a>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Pembeli: <span
                                                class="fw-bold text-light">{{ $ret->order->buyer_name ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <ul class="ps-3 mb-0 small text-secondary-emphasis">
                                            @foreach ($ret->items as $rItem)
                                                @php
                                                    $mpProduct = $rItem->orderItem->marketplaceProduct ?? null;
                                                @endphp
                                                <li>
                                                    <span class="text-primary fw-semibold">{{ $rItem->quantity }}x</span>
                                                    {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2.5 py-1.5 rounded-pill mb-1 small">{{ $ret->status }}</span>
                                        <div class="small text-muted fst-italic text-wrap mt-1">
                                            "{{ $ret->reason ?? 'Tidak ada alasan' }}"
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($ret->is_restocked)
                                            @if ($ret->inspection_status === 'GOOD')
                                                <div class="d-flex flex-column align-items-center">
                                                    <span
                                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-3 py-1.5 rounded-pill mb-1">
                                                        <i class="fas fa-check-circle me-1"></i> Layak Jual
                                                    </span>
                                                    <span class="small text-muted">Stok Ditambah</span>
                                                    @if ($ret->inspection_notes)
                                                        <div class="mt-1 text-muted small text-wrap"
                                                            style="max-width: 150px; line-height: 1.2;">
                                                            <i
                                                                class="fas fa-comment-alt text-secondary me-1"></i>"{{ $ret->inspection_notes }}"
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif ($ret->inspection_status === 'DEFECTIVE')
                                                <div class="d-flex flex-column align-items-center">
                                                    <span
                                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-3 py-1.5 rounded-pill mb-1">
                                                        <i class="fas fa-times-circle me-1"></i> Rusak / Cacat
                                                    </span>
                                                    <span class="small text-muted">Stok Diabaikan</span>
                                                    @if ($ret->inspection_notes)
                                                        <div class="mt-1 text-muted small text-wrap"
                                                            style="max-width: 150px; line-height: 1.2;">
                                                            <i
                                                                class="fas fa-comment-alt text-secondary me-1"></i>"{{ $ret->inspection_notes }}"
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-3 py-2 rounded">
                                                    <i class="fas fa-check-circle me-1"></i> Stok Dikembalikan
                                                </span>
                                            @endif
                                        @else
                                            <button type="button" class="btn btn-primary btn-sm px-3 fw-medium"
                                                data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                                <i class="fas fa-clipboard-check me-1"></i> Terima &amp; QC
                                            </button>
                                            <div class="small text-muted mt-2">
                                                Klik untuk periksa fisik<br>dan terima barang.
                                            </div>

                                            <!-- Modal QC untuk Retur ini -->
                                            <div class="modal fade text-start" id="qcModal-{{ $ret->id }}"
                                                tabindex="-1" aria-labelledby="qcModalLabel-{{ $ret->id }}"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div
                                                        class="modal-content bg-dark border border-secondary border-opacity-25">
                                                        <div
                                                            class="modal-header border-bottom border-secondary border-opacity-25">
                                                            <h5 class="modal-title text-white"
                                                                id="qcModalLabel-{{ $ret->id }}">
                                                                <i class="fas fa-undo-alt text-primary me-2"></i> Quality
                                                                Control Retur: {{ $ret->return_sn }}
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('returns.restock', $ret->id) }}"
                                                            method="POST" class="mb-0">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label
                                                                        class="form-label fw-bold text-muted small uppercase">Barang
                                                                        yang Diretur:</label>
                                                                    <ul class="list-group list-group-flush mb-0">
                                                                        @foreach ($ret->items as $rItem)
                                                                            @php
                                                                                $mpProduct =
                                                                                    $rItem->orderItem
                                                                                        ->marketplaceProduct ?? null;
                                                                            @endphp
                                                                            <li
                                                                                class="list-group-item px-0 py-2 text-light bg-transparent border-0 d-flex align-items-center gap-2">
                                                                                <i class="fas fa-box text-secondary"></i>
                                                                                <span
                                                                                    class="badge bg-secondary bg-opacity-20 text-secondary border border-secondary border-opacity-20 px-2 py-1">{{ $rItem->quantity }}
                                                                                    Pcs</span>
                                                                                <span
                                                                                    class="small">{{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>

                                                                <hr class="my-3 border-secondary border-opacity-25">

                                                                <div class="mb-3">
                                                                    <label for="inspection_status-{{ $ret->id }}"
                                                                        class="form-label fw-bold text-white">Hasil
                                                                        Inspeksi / Kondisi Barang</label>
                                                                    <select name="inspection_status"
                                                                        id="inspection_status-{{ $ret->id }}"
                                                                        class="form-select bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                                                        required>
                                                                        <option value="GOOD">Layak Jual / Good (Kembali
                                                                            ke Stok Aktif)</option>
                                                                        <option value="DEFECTIVE">Rusak / Defective (Tidak
                                                                            Mengubah Stok Gudang)</option>
                                                                    </select>
                                                                    <div class="form-text mt-2 small text-muted">
                                                                        <i class="fas fa-info-circle text-info me-1"></i>
                                                                        Jika dipilih <strong>Layak Jual</strong>, stok
                                                                        gudang akan bertambah dan otomatis di-push ke
                                                                        Shopee/TikTok.
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="inspection_notes-{{ $ret->id }}"
                                                                        class="form-label fw-bold text-white">Catatan
                                                                        Inspeksi (Opsional)</label>
                                                                    <textarea name="inspection_notes" id="inspection_notes-{{ $ret->id }}" rows="3"
                                                                        class="form-control bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                                                                        placeholder="Tulis deskripsi kondisi fisik barang, misal: 'Kemasan sobek sedikit tapi produk aman' atau 'Kaki penyangga patah, tidak layak jual'."></textarea>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="modal-footer border-top border-secondary border-opacity-25">
                                                                <button type="button" class="btn btn-secondary btn-sm"
                                                                    data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary btn-sm fw-semibold">
                                                                    <i class="fas fa-check me-1"></i> Simpan Hasil QC
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
                                        <i class="fas fa-box-open fs-1 text-muted opacity-25 mb-3 d-block"></i>
                                        Belum ada data barang retur. Klik "Tarik Data Retur Shopee" untuk memeriksa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $returns->withQueryString()->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
@endsection
