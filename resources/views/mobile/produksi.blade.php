@extends('layouts.mobile')

@section('title', 'Dasbor Produksi')
@section('header-title', 'Dasbor SPK Produksi')

@section('styles')
<style>
    body {
        background-color: #f1f5f9 !important;
        font-family: 'Outfit', 'Inter', sans-serif;
    }

    /* Summary Stats Grid */
    .summary-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px 16px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

    .summary-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    /* Filter Pills */
    .filter-pill {
        font-size: 0.78rem;
        font-weight: 700;
        padding: 8px 14px;
        border-radius: 20px;
        text-decoration: none;
        color: #64748b;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .filter-pill.active {
        background: linear-gradient(135deg, #4f46e5, #3730a3);
        color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }

    /* SPK List Card */
    .spk-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
        margin-bottom: 16px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .spk-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4f46e5, #0ea5e9);
    }

    .spk-card.stok-gudang::before {
        background: linear-gradient(90deg, #0ea5e9, #10b981);
    }

    .spk-header {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        padding: 14px 16px;
    }

    .spk-body {
        padding: 16px;
    }

    .search-container {
        position: relative;
    }

    .search-input {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 12px 16px 12px 42px;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        color: #0f172a;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
    }

    .search-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
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

    .btn-gradient-primary {
        background: linear-gradient(135deg, #4f46e5, #3730a3);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        transition: all 0.2s ease;
    }

    .btn-gradient-primary:active {
        transform: scale(0.98);
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.2);
    }
</style>
@endsection

@section('content')

    <!-- Notification for Pending Warehouse Requests if any -->
    @if(isset($pendingOrders) && count($pendingOrders) > 0)
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-3 p-3 text-dark d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-hourglass-half text-amber-600 fs-5"></i>
                <div>
                    <div class="fw-bold small">Request Produksi Gudang Pending</div>
                    <div class="small opacity-75">{{ count($pendingOrders) }} barang perlu respon produksi.</div>
                </div>
            </div>
            <span class="badge bg-dark text-warning rounded-pill px-3 py-1.5 fw-bold">{{ count($pendingOrders) }}</span>
        </div>
    @endif

    <!-- Header Stats Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="summary-card d-flex align-items-center gap-3">
                <div class="summary-icon bg-indigo-50 text-indigo" style="background:#e0e7ff; color:#4f46e5;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <span class="text-muted d-block small" style="font-size:0.68rem; font-weight:600;">TOTAL SPK</span>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-size:1.1rem;">{{ $spks->total() }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="summary-card d-flex align-items-center gap-3">
                <div class="summary-icon bg-emerald-50 text-emerald" style="background:#d1fae5; color:#059669;">
                    <i class="fas fa-industry"></i>
                </div>
                <div>
                    <span class="text-muted d-block small" style="font-size:0.68rem; font-weight:600;">SPK AKTIF</span>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-size:1.1rem;">{{ $spks->count() }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="mb-3">
        <form action="{{ route('mobile.produksi') }}" method="GET" id="filterForm" class="m-0">
            <input type="hidden" name="tipe_spk" id="inputTipeSpk" value="{{ $tipeSpk ?? '' }}">
            
            <div class="search-container mb-2">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-control search-input w-100" 
                       value="{{ $search ?? '' }}" placeholder="Cari No. SPK, No. Produksi, Pemesan...">
            </div>

            <!-- Filter Pills -->
            <div class="d-flex gap-2 overflow-auto pb-1" style="scrollbar-width: none;">
                <a href="javascript:void(0)" onclick="setFilter('')" class="filter-pill {{ empty($tipeSpk) ? 'active' : '' }}">
                    <i class="fas fa-list"></i> Semua SPK
                </a>
                <a href="javascript:void(0)" onclick="setFilter('pesanan_pelanggan')" class="filter-pill {{ ($tipeSpk ?? '') === 'pesanan_pelanggan' ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i> 🛒 Pesanan
                </a>
                <a href="javascript:void(0)" onclick="setFilter('stok_gudang')" class="filter-pill {{ ($tipeSpk ?? '') === 'stok_gudang' ? 'active' : '' }}">
                    <i class="fas fa-warehouse"></i> 🏬 Stok Gudang
                </a>
            </div>
        </form>
    </div>

    <!-- SPK List -->
    <div class="d-flex flex-column mb-3">
        @forelse($spks as $spk)
            <div class="spk-card {{ ($spk->tipe_spk ?? '') === 'stok_gudang' ? 'stok-gudang' : '' }}">
                <!-- SPK Card Header -->
                <div class="spk-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block small" style="font-size: 0.68rem; font-weight:600;">NO. SPK</span>
                        <h6 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 0.9rem;">{{ $spk->no_spk }}</h6>
                    </div>
                    <div class="text-end">
                        <span class="text-muted d-block small" style="font-size: 0.68rem; font-weight:600;">NO. PRODUKSI</span>
                        <span class="badge bg-white text-dark font-monospace border fw-bold px-2 py-1" style="font-size: 0.72rem; border-color:#cbd5e1 !important;">
                            {{ $spk->no_produksi }}
                        </span>
                    </div>
                </div>
                
                <div class="spk-body">
                    <!-- SPK Type Badge -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        @if(($spk->tipe_spk ?? '') === 'stok_gudang')
                            <span class="badge bg-emerald-50 text-emerald border fw-bold px-2.5 py-1" style="background:#ecfdf5; color:#059669; border-color:#a7f3d0 !important; font-size: 0.72rem;">
                                🏬 Produksi Stok Gudang
                            </span>
                        @else
                            <span class="badge bg-indigo-50 text-indigo border fw-bold px-2.5 py-1" style="background:#e0e7ff; color:#4f46e5; border-color:#c7d2fe !important; font-size: 0.72rem;">
                                🛒 Pesanan Pelanggan
                            </span>
                        @endif

                        <span class="small text-muted font-monospace" style="font-size:0.7rem;">
                            <i class="fas fa-layer-group me-1"></i>{{ $spk->items->count() }} Item
                        </span>
                    </div>

                    <!-- SPK Metadata -->
                    <div class="row g-2 mb-2 p-2.5 rounded-3 bg-light" style="background:#f8fafc; border:1px solid #f1f5f9;">
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.68rem; font-weight:600;">TANGGAL SPK</span>
                            <span class="small fw-bold text-dark">{{ $spk->tanggal ? $spk->tanggal->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.68rem; font-weight:600;">TARGET DEADLINE</span>
                            <span class="small fw-bold text-danger"><i class="far fa-clock me-1"></i>{{ $spk->deadline ? $spk->deadline->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-12 mt-2 pt-2 border-top border-slate-200">
                            <span class="text-muted d-block" style="font-size: 0.68rem; font-weight:600;">PEMESAN / INSTANSI</span>
                            <span class="small fw-bold text-dark">
                                {{ $spk->pemesan ?: '-' }} 
                                @if($spk->instansi) <span class="text-muted">({{ $spk->instansi }})</span> @endif
                            </span>
                        </div>
                    </div>

                    <!-- Action Button to Dedicated Mobile Detail -->
                    <div class="mt-3">
                        <a href="{{ route('mobile.spk.detail', $spk->id) }}" class="btn btn-gradient-primary w-100 py-2.5 shadow-sm text-decoration-none d-flex align-items-center justify-content-center gap-2" style="font-size: 0.85rem;">
                            <i class="fas fa-edit"></i> BUKA DETAIL &amp; UPDATE PROGRES
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white border rounded-4 text-muted small shadow-sm">
                <i class="fas fa-clipboard-check opacity-30 display-4 mb-2 d-block text-secondary"></i>
                Tidak ada data SPK produksi ditemukan.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3 mb-4">
        {{ $spks->links('pagination::bootstrap-5') }}
    </div>

@endsection

@section('scripts')
<script>
    function setFilter(tipe) {
        document.getElementById('inputTipeSpk').value = tipe;
        document.getElementById('filterForm').submit();
    }
</script>
@endsection
