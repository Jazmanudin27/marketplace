@extends('layouts.mobile')

@section('title', 'Tracking SPK')
@section('header-title', 'Tracking SPK')

@section('styles')
<style>
    body {
        background-color: #f8fafc !important;
    }

    .spk-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        margin-bottom: 15px;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .spk-header {
        background-color: #fafafa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
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
        font-size: 0.65rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-success-light {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .badge-warning-light {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.15);
    }

    .badge-primary-light {
        background: #e0e7ff;
        color: #4f46e5;
        border: 1px solid rgba(79, 70, 229, 0.15);
    }

    .search-container {
        position: relative;
    }

    .search-input {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 10px 16px 10px 40px;
        font-size: 0.88rem;
        transition: all 0.2s ease;
        color: #0f172a;
    }

    .search-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
    <!-- Search Form -->
    <div class="mb-3">
        <form action="{{ route('mobile.owner.spk') }}" method="GET" class="m-0">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-control search-input w-100" 
                       value="{{ $search }}" placeholder="Cari SPK, no produksi, pemesan...">
            </div>
        </form>
    </div>

    <!-- SPK List -->
    <h6 class="fw-bold mb-3 text-dark px-1">Daftar Surat Perintah Kerja (SPK)</h6>
    <div class="d-flex flex-column mb-3">
        @forelse($spks as $spk)
            <div class="spk-card">
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
                    <!-- SPK Metadata -->
                    <div class="row g-2 mb-3 pb-3 border-bottom border-light">
                        <div class="col-6">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Tanggal SPK</span>
                            <span class="small fw-semibold text-dark">{{ $spk->tanggal->format('d M Y') }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Target Deadline</span>
                            <span class="small fw-semibold text-danger">{{ $spk->deadline->format('d M Y') }}</span>
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
                                        <span>Penjahit: <strong class="text-indigo">{{ $item->penjahit ?: 'Belum ditunjuk' }}</strong></span>
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
                                        } elseif ($item->status === 'Sedang Dikerjakan' || $item->status === 'Dipotong' || $item->status === 'Dijahit') {
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
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white border rounded-4 text-muted small">
                <i class="fas fa-tasks opacity-30 fs-2 mb-2 d-block text-secondary"></i>
                Tidak ada data SPK produksi.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3 mb-4">
        {{ $spks->links('pagination::bootstrap-5') }}
    </div>
@endsection
