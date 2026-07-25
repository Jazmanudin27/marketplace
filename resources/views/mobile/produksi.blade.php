@extends('layouts.mobile')

@section('title', 'Dasbor Produksi')
@section('header-title', 'Dasbor SPK Produksi')

@section('styles')
<style>
    body {
        background-color: #f8fafc !important;
    }

    .spk-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
        margin-bottom: 16px;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .spk-header {
        background-color: #fafafa;
        border-bottom: 1px solid #f1f5f9;
        padding: 12px 16px;
    }

    .spk-body {
        padding: 16px;
    }

    .spk-item-row {
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .spk-item-row:last-child {
        border-bottom: none;
    }

    .badge-premium {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-success-light {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .badge-warning-light {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .badge-primary-light {
        background: #e0e7ff;
        color: #4f46e5;
        border: 1px solid rgba(79, 70, 229, 0.2);
    }

    .search-container {
        position: relative;
    }

    .search-input {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 12px;
        padding: 11px 16px 11px 40px;
        font-size: 0.88rem;
        transition: all 0.2s ease;
        color: #0f172a;
    }

    .search-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        outline: none;
    }

    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
    }
</style>
@endsection

@section('content')

    <!-- Notification for Pending Warehouse Requests if any -->
    @if(isset($pendingOrders) && count($pendingOrders) > 0)
        <div class="card border border-warning bg-warning bg-opacity-10 rounded-3 mb-3 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold text-dark small">
                    <i class="fas fa-hourglass-half text-warning me-1"></i> Request Produksi Gudang Pending
                </div>
                <span class="badge bg-warning text-dark fw-bold">{{ count($pendingOrders) }} Pesanan</span>
            </div>
            <div class="small text-muted">
                Terdapat {{ count($pendingOrders) }} barang yang di-request oleh tim gudang untuk diproduksi.
            </div>
        </div>
    @endif

    <!-- Search Form -->
    <div class="mb-3">
        <form action="{{ route('mobile.produksi') }}" method="GET" class="m-0">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-control search-input w-100" 
                       value="{{ $search ?? '' }}" placeholder="Cari No. SPK, No. Produksi, Pemesan...">
            </div>
        </form>
    </div>

    <!-- Section Title -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-clipboard-list text-primary me-2"></i>Daftar SPK Produksi</h6>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 fw-bold">
            Total: {{ $spks->total() }} SPK
        </span>
    </div>

    <!-- SPK List -->
    <div class="d-flex flex-column mb-3">
        @forelse($spks as $spk)
            <div class="spk-card">
                <!-- SPK Card Header -->
                <div class="spk-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block small" style="font-size: 0.68rem;">No. SPK</span>
                        <h6 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 0.85rem;">{{ $spk->no_spk }}</h6>
                    </div>
                    <div class="text-end">
                        <span class="text-muted d-block small" style="font-size: 0.68rem;">No. Produksi</span>
                        <span class="badge bg-light text-dark font-monospace border fw-bold" style="font-size: 0.72rem;">{{ $spk->no_produksi }}</span>
                    </div>
                </div>
                
                <div class="spk-body">
                    <!-- SPK Type Badge -->
                    <div class="mb-2">
                        @if(($spk->tipe_spk ?? '') === 'stok_gudang')
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-bold px-2 py-1" style="font-size: 0.7rem;">
                                🏬 Produksi Stok Gudang
                            </span>
                        @else
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold px-2 py-1" style="font-size: 0.7rem;">
                                🛒 Pesanan Pelanggan
                            </span>
                        @endif
                    </div>

                    <!-- SPK Metadata -->
                    <div class="row g-2 mb-3 pb-3 border-bottom border-light">
                        <div class="col-6">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Tanggal SPK</span>
                            <span class="small fw-semibold text-dark">{{ $spk->tanggal ? $spk->tanggal->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Target Deadline</span>
                            <span class="small fw-semibold text-danger">{{ $spk->deadline ? $spk->deadline->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Pemesan / Instansi</span>
                            <span class="small fw-semibold text-dark">
                                {{ $spk->pemesan ?: '-' }} 
                                @if($spk->instansi) <span class="text-muted">({{ $spk->instansi }})</span> @endif
                            </span>
                        </div>
                    </div>

                    <!-- SPK Items -->
                    <h6 class="fw-bold text-dark mb-2" style="font-size: 0.8rem;">Daftar Item Produksi:</h6>
                    <div class="d-flex flex-column">
                        @foreach($spk->items as $item)
                            <div class="spk-item-row d-flex justify-content-between align-items-center">
                                <div style="flex: 1; min-width: 0; padding-right: 10px;">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.82rem;">{{ $item->nama_produk }}</div>
                                    <div class="d-flex gap-1.5 align-items-center mt-1 text-muted" style="font-size: 0.7rem;">
                                        <span>Size: <strong>{{ $item->ukuran ?: '-' }}</strong></span>
                                        <span>•</span>
                                        <span>Penjahit: <strong class="text-primary">{{ $item->penjahit ?: 'Belum ditunjuk' }}</strong></span>
                                    </div>
                                </div>
                                <div class="text-end" style="white-space: nowrap;">
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.82rem;">
                                        {{ $item->quantity }} pcs
                                    </div>
                                    @php
                                        $badgeBg = 'badge-primary-light';
                                        if ($item->status === 'Selesai') {
                                            $badgeBg = 'badge-success-light';
                                        } elseif (in_array($item->status, ['Sedang Dikerjakan', 'Dipotong', 'Dijahit'])) {
                                            $badgeBg = 'badge-warning-light';
                                        }
                                    @endphp
                                    <span class="badge badge-premium {{ $badgeBg }}">
                                        {{ $item->status ?: 'Menunggu' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Direct Action Button to SPK Detail & Stage Progress -->
                    <div class="mt-3 pt-2 border-top">
                        <a href="{{ route('spks.show', $spk->id) }}" class="btn btn-primary w-100 fw-bold rounded-3 shadow-sm py-2" style="font-size: 0.82rem;">
                            <i class="fas fa-edit me-1.5"></i> BUKA DETAIL &amp; UPDATE PROGRES SPK
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white border rounded-4 text-muted small">
                <i class="fas fa-tasks opacity-30 fs-2 mb-2 d-block text-secondary"></i>
                Tidak ada data SPK produksi ditemukan.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3 mb-4">
        {{ $spks->links('pagination::bootstrap-5') }}
    </div>

@endsection
