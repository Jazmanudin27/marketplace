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

    .search-container {
        position: relative;
    }

    .search-input {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 10px 14px 10px 38px;
        font-size: 0.82rem;
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
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.88rem;
    }
</style>
@endsection

@section('content')
    <!-- Search & Filter Form -->
    <div class="mb-3">
        <form action="{{ route('mobile.owner.spk') }}" method="GET" class="m-0">
            <div class="row g-2">
                <div class="col-7">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control search-input w-100" 
                               value="{{ $search ?? '' }}" placeholder="Cari SPK, pemesan...">
                    </div>
                </div>
                <div class="col-5">
                    <select name="tipe_spk" class="form-select search-input py-2" onchange="this.form.submit()" style="font-size:0.75rem; padding-left:10px;">
                        <option value="">-- Tipe SPK --</option>
                        <option value="pesanan_pelanggan" {{ ($tipeSpk ?? '') === 'pesanan_pelanggan' ? 'selected' : '' }}>🛒 Pesanan</option>
                        <option value="stok_gudang" {{ ($tipeSpk ?? '') === 'stok_gudang' ? 'selected' : '' }}>🏬 Stok Gudang</option>
                    </select>
                </div>
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
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Tanggal SPK</span>
                            <span class="small fw-semibold text-dark">{{ $spk->tanggal ? $spk->tanggal->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Target Deadline</span>
                            <span class="small fw-semibold text-danger">{{ $spk->deadline ? $spk->deadline->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-muted d-block small mb-0.5" style="font-size: 0.68rem;">Pemesan / Instansi</span>
                            <span class="small fw-semibold text-dark">
                                {{ $spk->pemesan ?: '-' }} 
                                @if($spk->instansi) <span class="text-muted">({{ $spk->instansi }})</span> @endif
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-top">
                        <a href="{{ route('spks.show', $spk->id) }}" class="btn btn-sm btn-primary w-100 fw-bold rounded-3 shadow-sm py-2" style="font-size: 0.8rem;">
                            <i class="fas fa-eye me-1"></i> Buka Detail &amp; Update Progres SPK
                        </a>
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
