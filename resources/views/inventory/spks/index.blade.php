@extends('layouts.app')
@section('title', 'Marketing & Pengiriman')
@section('page-title', 'Marketing & Pengiriman')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Top KPI Stats Cards ──────────────────────────────── --}}
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Antrian Produksi</span>
                                <h4 class="fw-bold text-dark mb-0">
                                    {{ number_format($stats['total_produksi'] ?? $spks->total()) }}
                                    <span class="fs-6 fw-normal text-muted">Grup SPK</span>
                                </h4>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-boxes-stacked fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Pesanan Urgent</span>
                                <h4 class="fw-bold text-danger mb-0">
                                    {{ number_format($stats['total_urgent'] ?? 0) }}
                                    <span class="fs-6 fw-normal text-muted">SPK</span>
                                </h4>
                            </div>
                            <div class="bg-warning bg-opacity-15 text-warning rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-bolt fs-5 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Volume Pcs</span>
                                <h4 class="fw-bold text-success mb-0">
                                    {{ number_format($stats['total_pcs'] ?? 0) }}
                                    <span class="fs-6 fw-normal text-muted">Pcs</span>
                                </h4>
                            </div>
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-tshirt fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Filter Bar ──────────────────────────────────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3 px-3">
                    <form method="GET" action="{{ route('spks.index') }}" id="filterForm">
                        <div class="row g-2 align-items-end">

                            {{-- Cari SPK / Pemesan --}}
                            <div class="col-md-4">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-search text-muted me-1"></i>Cari SPK / Pemesan / Instansi
                                </label>
                                <input type="text" name="search" id="filterSearch" class="form-control form-control-sm"
                                    placeholder="Ketik No. SPK, pemesan, produk..." value="{{ request('search') }}">
                            </div>

                            {{-- Filter Status / Tahap --}}
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-filter text-muted me-1"></i>Tahap / Status
                                </label>
                                @php
                                    $currStage = request('stage');
                                    $isUrgent = request('urgent') == '1';
                                    $selectedFilter = $isUrgent ? 'urgent' : ($currStage ?: '');
                                @endphp
                                <select name="stage" id="filterStage" class="form-select form-select-sm">
                                    <option value="" {{ $selectedFilter === '' ? 'selected' : '' }}>-- Semua Status --</option>
                                    <option value="urgent" {{ $selectedFilter === 'urgent' ? 'selected' : '' }}>⚡ Pesanan Urgent</option>
                                    <option value="draft" {{ $selectedFilter === 'draft' ? 'selected' : '' }}>📝 DRAFT (Belum Deal)</option>
                                    <option value="desain" {{ $selectedFilter === 'desain' ? 'selected' : '' }}>🎨 Tahap Desain &amp; Mockup</option>
                                    <option value="pesanan_baru" {{ $selectedFilter === 'pesanan_baru' ? 'selected' : '' }}>📋 Pesanan Baru / Perencanaan</option>
                                    <option value="sampling" {{ $selectedFilter === 'sampling' ? 'selected' : '' }}>⏳ Antrian &amp; Sampling</option>
                                    <option value="potong" {{ $selectedFilter === 'potong' ? 'selected' : '' }}>✂️ Tahap Potong</option>
                                    <option value="sablon_bordir" {{ $selectedFilter === 'sablon_bordir' ? 'selected' : '' }}>🎨 Sablon / Bordir</option>
                                    <option value="jahit" {{ $selectedFilter === 'jahit' ? 'selected' : '' }}>🪡 Tahap Jahit</option>
                                    <option value="lkpk" {{ $selectedFilter === 'lkpk' ? 'selected' : '' }}>💿 Tahap LKPK (Kancing)</option>
                                    <option value="qc" {{ $selectedFilter === 'qc' ? 'selected' : '' }}>🔍 Quality Control (QC)</option>
                                    <option value="packing" {{ $selectedFilter === 'packing' ? 'selected' : '' }}>📦 Packing / Finishing</option>
                                    <option value="selesai" {{ $selectedFilter === 'selesai' ? 'selected' : '' }}>✅ Selesai (Finished Good)</option>
                                    <option value="dikirim" {{ $selectedFilter === 'dikirim' ? 'selected' : '' }}>🚀 Telah Dikirim (Shipped)</option>
                                </select>
                            </div>

                            {{-- Filter Tipe SPK --}}
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-tag text-muted me-1"></i>Tipe SPK
                                </label>
                                <select name="tipe_spk" id="filterTipeSpk" class="form-select form-select-sm">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="pesanan_pelanggan" {{ request('tipe_spk') === 'pesanan_pelanggan' ? 'selected' : '' }}>🛒 Pesanan Pelanggan</option>
                                    <option value="stok_gudang" {{ request('tipe_spk') === 'stok_gudang' ? 'selected' : '' }}>🏬 Stok Gudang</option>
                                </select>
                            </div>

                            {{-- Tombol Filter --}}
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fas fa-search me-1"></i>Terapkan
                                </button>
                                <a href="{{ route('spks.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>

                            {{-- Info Hasil --}}
                            <div class="col-md ms-auto text-end">
                                <span class="text-muted small">
                                    Menampilkan <strong class="text-dark">{{ $spks->count() }}</strong> dari <strong class="text-dark">{{ $spks->total() }}</strong> antrian
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Tabel Utama ─────────────────────────────────────── --}}
            <div class="card border shadow-sm">
                {{-- Header --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-industry me-2"></i>Daftar Antrian SPK Produksi &amp; Pengiriman</h6>
                        <p class="text-muted mb-0 small mt-1">Pantau seluruh antrian pesanan SPK, status proses, dan tracking pengiriman</p>
                    </div>
                    @can('spks.create')
                        <a href="{{ route('spks.create') }}" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-plus me-1"></i> Buat SPK Baru
                        </a>
                    @endcan
                </div>

                <div class="card-body p-3">
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

                    {{-- Tabel --}}
                    <div class="table-responsive rounded border mt-2">
                        <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 45px;">#</th>
                                    <th>NO. PRODUKSI / SPK</th>
                                    <th>PEMESAN &amp; INSTANSI</th>
                                    <th>PRODUK &amp; JUMLAH</th>
                                    <th>TAHAP &amp; PROGRES</th>
                                    <th>TANGGAL &amp; DEADLINE</th>
                                    <th class="text-center" style="width: 190px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($spks as $index => $row)
                                    @php
                                        $queueNo = ($spks->currentPage() - 1) * $spks->perPage() + $index + 1;
                                        $spkGroup = $row->sub_spks ?? collect([$row]);
                                        $spkCount = $spkGroup->count();
                                        $totalPcsGroup = $spkGroup->sum(fn($s) => $s->total_pcs);
                                        $isUrgentGroup = $spkGroup->contains('is_urgent', true);
                                        $isDraftGroup = $spkGroup->contains(
                                            fn($s) => str_contains(strtoupper($s->current_stage_name), 'DRAFT') ||
                                                str_contains(strtoupper($s->tahap_saat_ini ?? ''), 'DRAFT'),
                                        );

                                        $firstSpk = $spkGroup->first() ?? $row;
                                        $mainImageUrl =
                                            $firstSpk->image_url ??
                                            ($firstSpk->items->pluck('masterProduct.image_url')->filter()->first() ??
                                                $spkGroup->pluck('image_url')->filter()->first());

                                        $trackingUrl = route('spks.customer_track', $row->no_produksi ?: $row->id);
                                        $waText = rawurlencode(
                                            'Halo ' .
                                                ($row->pemesan ?: 'Pelanggan') .
                                                ', berikut link tracking status produksi SPK ' .
                                                ($row->no_produksi ?: $row->no_spk) .
                                                ': ' .
                                                $trackingUrl,
                                        );

                                        // Deadline indicator
                                        $deadlineBadge = '<span class="text-muted small">-</span>';
                                        if ($row->deadline) {
                                            $daysLeft = (int) now()->startOfDay()->diffInDays($row->deadline->startOfDay(), false);
                                            if ($daysLeft < 0) {
                                                $deadlineBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">' . $row->deadline->format('d/m/Y') . ' (Lewat ' . abs($daysLeft) . ' hari)</span>';
                                            } elseif ($daysLeft === 0) {
                                                $deadlineBadge = '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">' . $row->deadline->format('d/m/Y') . ' (Hari ini)</span>';
                                            } else {
                                                $deadlineBadge = '<span class="badge bg-light text-dark border">' . $row->deadline->format('d/m/Y') . ' (' . $daysLeft . ' hari lagi)</span>';
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        {{-- 1. # --}}
                                        <td class="text-center text-muted small fw-semibold">{{ $queueNo }}</td>

                                        {{-- 2. NO PRODUKSI / SPK --}}
                                        <td>
                                            <div class="fw-bold font-monospace text-dark small">
                                                <i class="fas fa-hashtag text-primary me-1"></i>{{ $row->no_produksi ?: ($row->no_spk ?: 'SPK-' . $row->id) }}
                                            </div>
                                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.72rem;">
                                                    Antrian #{{ $queueNo }}
                                                </span>
                                                @if ($isUrgentGroup)
                                                    <span class="badge bg-danger text-white" style="font-size: 0.72rem;">
                                                        <i class="fas fa-bolt me-1"></i>URGENT
                                                    </span>
                                                @endif
                                                @if ($isDraftGroup)
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.72rem;">
                                                        📝 Draft
                                                    </span>
                                                @endif
                                                @if ($spkCount > 1)
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 0.72rem;">
                                                        📦 {{ $spkCount }} SPK
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- 3. PEMESAN & INSTANSI --}}
                                        <td>
                                            <strong class="text-dark small d-block">{{ strtoupper($row->pemesan ?: 'GUEST') }}</strong>
                                            @if ($row->instansi)
                                                <div class="text-muted small">
                                                    <i class="fas fa-building me-1 text-secondary opacity-75"></i>{{ $row->instansi }}
                                                </div>
                                            @endif
                                            <div class="mt-1">
                                                @if (($row->tipe_spk ?? '') === 'stok_gudang')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.7rem;">
                                                        🏬 Stok Gudang
                                                    </span>
                                                @else
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.7rem;">
                                                        🛒 Pesanan Pelanggan
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- 4. PRODUK & JUMLAH --}}
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                {{-- Thumbnail Image --}}
                                                @if ($mainImageUrl)
                                                    <a href="javascript:void(0)" class="image-preview-trigger flex-shrink-0"
                                                        data-image="{{ $mainImageUrl }}"
                                                        data-title="{{ $row->no_produksi ?: $row->no_spk }} - {{ $row->pemesan }}"
                                                        title="Klik untuk melihat foto desain">
                                                        <img src="{{ $mainImageUrl }}" alt="Desain"
                                                            class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                                                    </a>
                                                @else
                                                    <div class="rounded border bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0"
                                                        style="width: 44px; height: 44px;">
                                                        <i class="fas fa-tshirt opacity-50"></i>
                                                    </div>
                                                @endif

                                                {{-- Info Item / Breakdown --}}
                                                <div class="min-w-0">
                                                    <div class="fw-bold text-primary small">
                                                        {{ number_format($totalPcsGroup) }} Pcs
                                                        @if ($spkCount > 1)
                                                            <span class="text-muted fw-normal">({{ $spkCount }} Jenis SPK)</span>
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted text-truncate" style="max-width: 200px;">
                                                        @foreach ($spkGroup as $subSpk)
                                                            <span class="d-inline-block me-1">
                                                                {{ $subSpk->kategori ?: 'SPK ' . ($loop->index + 1) }}
                                                                ({{ $subSpk->total_pcs }} pcs)@if(!$loop->last),@endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- 5. TAHAP & PROGRES --}}
                                        <td>
                                            @foreach ($spkGroup as $subSpk)
                                                @php
                                                    $stageName = strtoupper($subSpk->current_stage_name);
                                                    $badgeClass = 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25';
                                                    if (str_contains($stageName, 'DRAFT')) {
                                                        $badgeClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25';
                                                    } elseif (str_contains($stageName, 'DESAIN') || str_contains($stageName, 'MOCKUP')) {
                                                        $badgeClass = 'bg-info bg-opacity-10 text-info border-info border-opacity-25';
                                                    } elseif (str_contains($stageName, 'POTONG')) {
                                                        $badgeClass = 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25';
                                                    } elseif (str_contains($stageName, 'JAHIT')) {
                                                        $badgeClass = 'bg-info bg-opacity-10 text-info border-info border-opacity-25';
                                                    } elseif (str_contains($stageName, 'LKPK') || str_contains($stageName, 'KANCING')) {
                                                        $badgeClass = 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25';
                                                    } elseif (str_contains($stageName, 'QC')) {
                                                        $badgeClass = 'bg-info bg-opacity-10 text-info border-info border-opacity-25';
                                                    } elseif (str_contains($stageName, 'PACKING') || str_contains($stageName, 'SELESAI')) {
                                                        $badgeClass = 'bg-success bg-opacity-10 text-success border-success border-opacity-25';
                                                    } elseif (str_contains($stageName, 'KIRIM') || str_contains($stageName, 'DIKIRIM')) {
                                                        $badgeClass = 'bg-dark bg-opacity-10 text-dark border-dark border-opacity-25';
                                                    }
                                                @endphp
                                                <div class="mb-1">
                                                    @if ($spkCount > 1)
                                                        <span class="text-muted small me-1">{{ $subSpk->kategori ?: 'SPK ' . ($loop->index + 1) }}:</span>
                                                    @endif
                                                    <span class="badge {{ $badgeClass }} border" style="font-size: 0.72rem;">
                                                        {{ $subSpk->current_stage_name }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </td>

                                        {{-- 6. TANGGAL & DEADLINE --}}
                                        <td>
                                            <div class="small text-muted mb-1">
                                                <i class="far fa-calendar-alt me-1 opacity-75"></i>Masuk:
                                                <span class="text-dark fw-semibold">{{ $row->tanggal ? $row->tanggal->format('d/m/Y') : '-' }}</span>
                                            </div>
                                            <div class="small">
                                                <span class="text-muted d-block mb-0.5" style="font-size: 0.72rem;">Target Deadline:</span>
                                                {!! $deadlineBadge !!}
                                            </div>
                                        </td>

                                        {{-- 7. AKSI --}}
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                                {{-- Detail / Edit --}}
                                                <a href="{{ route('spks.show', $row) }}" class="btn btn-warning btn-sm"
                                                    title="Detail & Edit SPK">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                {{-- Customer Tracking Link --}}
                                                <a href="{{ $trackingUrl }}" target="_blank"
                                                    class="btn btn-info text-white btn-sm" title="Tracking Customer">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>

                                                {{-- Kirim WA --}}
                                                <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                                                    class="btn btn-success btn-sm" title="Bagikan Link Tracking via WhatsApp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>

                                                {{-- Cetak SPK --}}
                                                <a href="{{ route('spks.print', $row->id) }}" target="_blank"
                                                    class="btn btn-secondary btn-sm" title="Cetak SPK">
                                                    <i class="fas fa-print"></i>
                                                </a>

                                                {{-- Hapus SPK --}}
                                                <form action="{{ route('spks.destroy', $row) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPK {{ $row->no_produksi ?: $row->no_spk }} ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus SPK">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4 small">
                                            <i class="fas fa-clipboard-list me-2 opacity-50"></i>
                                            Tidak ada data SPK yang sesuai dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($spks->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-muted small">
                                Menampilkan {{ $spks->firstItem() ?? 0 }} - {{ $spks->lastItem() ?? 0 }} dari {{ $spks->total() }} data
                            </span>
                            <div>
                                {{ $spks->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal Lightbox Preview Desain ──────────────────────── --}}
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content overflow-hidden">
                <div class="modal-header d-flex align-items-center gap-2 p-3 bg-light border-bottom">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="imagePreviewTitle">
                        <i class="fas fa-image text-primary me-1"></i> Preview Desain SPK
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 text-center bg-light">
                    <img id="imagePreviewSrc" src="" alt="Desain SPK" class="img-fluid rounded border shadow-sm"
                        style="max-height: 70vh; object-fit: contain;">
                </div>
                <div class="modal-footer bg-light px-3 py-2 d-flex justify-content-between border-top">
                    <span class="text-muted small">Foto desain produk SPK</span>
                    <a id="imageDownloadBtn" href="" download target="_blank" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-download me-1"></i> Buka Ukuran Asli
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lightbox Modal for Image Preview
            const imageTriggers = document.querySelectorAll('.image-preview-trigger');
            const modalEl = document.getElementById('imagePreviewModal');
            const modalImg = document.getElementById('imagePreviewSrc');
            const modalTitle = document.getElementById('imagePreviewTitle');
            const downloadBtn = document.getElementById('imageDownloadBtn');

            if (imageTriggers.length > 0 && modalEl) {
                const previewModal = new bootstrap.Modal(modalEl);
                imageTriggers.forEach(trigger => {
                    trigger.addEventListener('click', function() {
                        const imgSrc = this.getAttribute('data-image');
                        const titleText = this.getAttribute('data-title') || 'Preview Desain SPK';

                        if (imgSrc) {
                            modalImg.src = imgSrc;
                            modalTitle.innerHTML = `<i class="fas fa-image text-primary me-1"></i> Desain SPK: ${titleText}`;
                            downloadBtn.href = imgSrc;
                            previewModal.show();
                        }
                    });
                });
            }
        });
    </script>
@endpush
