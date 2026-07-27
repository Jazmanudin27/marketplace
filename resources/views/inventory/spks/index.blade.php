@extends('layouts.app')
@section('title', 'Marketing & Pengiriman')
@section('page-title', 'Marketing & Pengiriman')

@section('content')
<div class="container-fluid px-2 px-md-3 py-2">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-5 align-middle"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- HEADER SECTION --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Marketing &amp; Pengiriman</h3>
            <p class="text-muted small mb-0">Pantau pesanan, bagikan link pelacakan, dan atur pengambilan Urgent.</p>
        </div>
        @can('spks.create')
        <div class="d-flex gap-2">
            <a href="{{ route('spks.create') }}" class="btn btn-primary fw-bold px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Tambah Order</span>
                <i class="fas fa-caret-down opacity-50 ms-1"></i>
            </a>
        </div>
        @endcan
    </div>

    {{-- FILTER TABS & SEARCH BAR --}}
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                {{-- SCROLLABLE TABS --}}
                <div class="d-flex align-items-center gap-2 overflow-auto py-1 scrollbar-hidden" style="white-space: nowrap; -webkit-overflow-scrolling: touch;">
                    @php
                        $currStage = request('stage');
                        $isUrgent = request('urgent') == '1';
                        $hasFilter = request()->anyFilled(['search', 'stage', 'urgent', 'tipe_spk', 'date_from', 'date_to']);
                    @endphp

                    {{-- Tab: Semua SPK --}}
                    <a href="{{ route('spks.index') }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ (!$currStage && !$isUrgent) ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-layer-group"></i>
                        <span>Semua SPK</span>
                    </a>

                    {{-- Tab: Urgent --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['urgent' => $isUrgent ? null : '1'])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $isUrgent ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-bolt text-warning"></i>
                        <span>Urgent</span>
                    </a>

                    {{-- Tab: Pesanan Baru --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['stage' => 'pesanan_baru', 'urgent' => null])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $currStage === 'pesanan_baru' ? 'btn-danger bg-opacity-75 text-white shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-palette text-danger"></i>
                        <span>Pesanan Baru</span>
                    </a>

                    {{-- Tab: Potong --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['stage' => 'potong', 'urgent' => null])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $currStage === 'potong' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-scissors text-info"></i>
                        <span>Potong</span>
                    </a>

                    {{-- Tab: Jahit --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['stage' => 'jahit', 'urgent' => null])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $currStage === 'jahit' ? 'btn-purple text-white shadow-sm' : 'btn-light text-secondary border-0' }}"
                       style="{{ $currStage === 'jahit' ? 'background-color: #8b5cf6;' : '' }}">
                        <i class="fas fa-tshirt text-purple" style="color:#a855f7;"></i>
                        <span>Jahit</span>
                    </a>

                    {{-- Tab: LKPK --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['stage' => 'lkpk', 'urgent' => null])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $currStage === 'lkpk' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-gem text-emerald" style="color:#10b981;"></i>
                        <span>LKPK</span>
                    </a>

                    {{-- Tab: QC --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['stage' => 'qc', 'urgent' => null])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $currStage === 'qc' ? 'btn-info text-white shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-search text-primary"></i>
                        <span>QC</span>
                    </a>

                    {{-- Tab: Packing & Send --}}
                    <a href="{{ route('spks.index', array_merge(request()->query(), ['stage' => 'packing', 'urgent' => null])) }}" 
                       class="btn btn-sm rounded-pill fw-bold px-3 py-2 d-inline-flex align-items-center gap-1.5 transition-all {{ $currStage === 'packing' ? 'btn-dark text-white shadow-sm' : 'btn-light text-secondary border-0' }}">
                        <i class="fas fa-box-open text-warning"></i>
                        <span>Packing &amp; Send</span>
                    </a>
                </div>

                {{-- SEARCH BOX --}}
                <div class="w-100 w-lg-auto" style="min-width: 260px;">
                    <form action="{{ route('spks.index') }}" method="GET" class="m-0">
                        @if($currStage)<input type="hidden" name="stage" value="{{ $currStage }}">@endif
                        @if($isUrgent)<input type="hidden" name="urgent" value="1">@endif
                        
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="text" name="search" class="form-control form-control-sm rounded-pill ps-5 pe-3 py-2 bg-light border-0 shadow-none text-dark" 
                                   placeholder="Cari Pelanggan / SPK..." value="{{ request('search') }}"
                                   onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SPK CARDS GRID CONTAINER --}}
    <div class="row g-3 mb-4">
        @forelse($spks as $index => $row)
            @php
                $queueNo = ($spks->currentPage() - 1) * $spks->perPage() + $index + 1;
                $trackingUrl = route('mobile.spk.detail', $row->id);
                $waText = rawurlencode("Halo " . ($row->pemesan ?: 'Pelanggan') . ", berikut link tracking status produksi SPK " . $row->no_spk . ": " . $trackingUrl);
            @endphp
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white transition-hover position-relative" style="border: 1px solid rgba(0,0,0,0.06) !important;">
                    
                    {{-- Urgent Ribbon Badge --}}
                    <div id="urgent-badge-{{ $row->id }}" class="position-absolute top-0 end-0 me-3 mt-2 {{ $row->is_urgent ? '' : 'd-none' }}">
                        <span class="badge bg-danger shadow-sm rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-bolt me-1"></i>URGENT</span>
                    </div>

                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        
                        {{-- CARD HEADER ROW: Image + Info --}}
                        <div class="d-flex gap-3 mb-3">
                            {{-- Product Image Thumbnail --}}
                            <div class="flex-shrink-0">
                                @if($row->image_url)
                                    <img src="{{ $row->image_url }}" alt="Desain SPK" 
                                         class="rounded-3 border bg-light object-fit-cover shadow-sm" 
                                         style="width: 84px; height: 84px;">
                                @else
                                    <div class="rounded-3 border bg-light d-flex flex-column align-items-center justify-content-center text-muted shadow-sm" 
                                         style="width: 84px; height: 84px;">
                                        <i class="fas fa-tshirt fs-3 opacity-40 mb-1 text-primary"></i>
                                        <span style="font-size: 8px;" class="fw-semibold text-uppercase text-muted">No Image</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Info Meta --}}
                            <div class="flex-grow-1 min-w-0">
                                {{-- Kode Produksi & Queue Badge --}}
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="font-monospace text-muted fw-bold small opacity-75">{{ $row->no_produksi ?: 'NO-PROD' }}</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-2 px-2 py-0.5 fw-bold" style="font-size: 10px;">
                                        ANTRIAN #{{ $queueNo }}
                                    </span>
                                </div>

                                {{-- Customer Name --}}
                                <h6 class="fw-extrabold text-dark mb-0 text-truncate font-sans" style="font-size: 0.98rem; letter-spacing: -0.2px;">
                                    {{ strtoupper($row->pemesan ?: 'GUEST') }}
                                </h6>

                                {{-- Instansi / Toko --}}
                                <div class="text-muted text-truncate mb-2" style="font-size: 0.78rem;">
                                    <i class="fas fa-home me-1 opacity-50"></i>{{ $row->instansi ?: '-' }}
                                </div>

                                {{-- Current Stage Status Pill --}}
                                <div class="mb-1">
                                    <span class="badge rounded-2 px-2 py-1 fw-bold text-uppercase d-inline-flex align-items-center gap-1 shadow-2xs" 
                                          style="font-size: 10px; background-color: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe;">
                                        <i class="fas fa-clipboard-list"></i>
                                        <span>{{ $row->current_stage_name }}</span>
                                    </span>
                                </div>

                                {{-- Quantity & Variant --}}
                                <div class="fw-bold text-dark d-flex align-items-center gap-1 mt-1" style="font-size: 0.82rem;">
                                    <i class="fas fa-tshirt text-primary" style="font-size: 11px;"></i>
                                    <span class="text-primary">{{ $row->total_pcs }} Pcs</span>
                                    <span class="text-muted fw-normal ms-1">| {{ $row->variant_summary }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- DATES ROW (MASUK & DEADLINE) --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light bg-opacity-75 rounded-3 p-2 border border-light text-center">
                                    <span class="text-muted d-block small mb-0.5" style="font-size: 10px;">
                                        <i class="far fa-calendar-alt me-1 opacity-70"></i>Masuk:
                                    </span>
                                    <span class="fw-bold text-dark" style="font-size: 0.78rem;">
                                        {{ $row->tanggal ? $row->tanggal->format('d M') : '-' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light bg-opacity-75 rounded-3 p-2 border border-light text-center">
                                    <span class="text-muted d-block small mb-0.5" style="font-size: 10px;">
                                        <i class="fas fa-flag me-1 opacity-70"></i>DL:
                                    </span>
                                    <span class="fw-bold {{ $row->deadline ? 'text-dark' : 'text-muted' }}" style="font-size: 0.78rem;">
                                        {{ $row->deadline ? $row->deadline->format('d M') : '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="d-flex flex-column gap-2">
                            {{-- Row 1: Detail/Edit & Link Track --}}
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('spks.show', $row) }}" 
                                       class="btn btn-sm btn-outline-secondary w-100 rounded-3 fw-bold py-1.5 bg-white text-dark border-opacity-25 d-inline-flex align-items-center justify-content-center gap-1"
                                       style="font-size: 0.78rem;">
                                        <i class="far fa-file-alt text-secondary"></i>
                                        <span>Detail / Edit</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                                       class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1 transition-all"
                                       style="font-size: 0.78rem; background-color: #e6f7ed; color: #059669; border: 1px solid #a7f3d0;"
                                       title="Bagikan Tautan Pelacakan ke Pelanggan via WhatsApp">
                                        <i class="fab fa-whatsapp fs-6 text-success"></i>
                                        <span>Link Track</span>
                                    </a>
                                </div>
                            </div>

                            {{-- Row 2: Cetak SPK & Ambil Urgent --}}
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('spks.print', $row->id) }}" target="_blank"
                                       class="btn btn-sm rounded-3 fw-bold py-1.5 w-100 d-inline-flex align-items-center justify-content-center gap-1 transition-all"
                                       style="font-size: 0.78rem; background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;"
                                       title="Cetak SPK Perintah Kerja (A4 Half-Page)">
                                        <i class="fas fa-print text-primary"></i>
                                        <span>Cetak SPK</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <button type="button" 
                                            class="btn btn-sm w-100 rounded-3 fw-extrabold py-1.5 d-inline-flex align-items-center justify-content-center gap-1 transition-all toggle-urgent-btn"
                                            data-id="{{ $row->id }}"
                                            data-url="{{ route('spks.toggle_urgent', $row->id) }}"
                                            style="font-size: 0.78rem; background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5;">
                                        <i class="fas fa-bolt text-warning"></i>
                                        <span id="urgent-btn-text-{{ $row->id }}">
                                            {{ $row->is_urgent ? 'BATAL URGENT' : 'AMBIL URGENT' }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 py-5">
                <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                    <div class="card-body">
                        <i class="fas fa-clipboard-list fa-3x text-muted opacity-30 mb-3"></i>
                        <h6 class="fw-bold text-dark mb-1">Tidak Ada Data SPK Produksi</h6>
                        <p class="text-muted small mb-3">Belum ada SPK yang sesuai dengan filter atau kata kunci pencarian Anda.</p>
                        @can('spks.create')
                        <a href="{{ route('spks.create') }}" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold">
                            <i class="fas fa-plus me-1"></i> Buat SPK Baru
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    @if($spks->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $spks->links() }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // AJAX Toggle Urgent Status
    const urgentBtns = document.querySelectorAll('.toggle-urgent-btn');
    urgentBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const spkId = this.getAttribute('data-id');
            const url = this.getAttribute('data-url');
            const btnTextEl = document.getElementById('urgent-btn-text-' + spkId);
            const badgeEl = document.getElementById('urgent-badge-' + spkId);

            this.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                if (data.success) {
                    if (data.is_urgent) {
                        btnTextEl.innerText = 'BATAL URGENT';
                        if (badgeEl) badgeEl.classList.remove('d-none');
                    } else {
                        btnTextEl.innerText = 'AMBIL URGENT';
                        if (badgeEl) badgeEl.classList.add('d-none');
                    }
                }
            })
            .catch(err => {
                this.disabled = false;
                console.error(err);
            });
        });
    });
});
</script>

<style>
.scrollbar-hidden::-webkit-scrollbar {
    display: none;
}
.scrollbar-hidden {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.transition-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.transition-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08) !important;
}
.fw-extrabold {
    font-weight: 800;
}
</style>
@endsection
