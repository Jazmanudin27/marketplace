@extends('layouts.app')
@section('title', 'Pemetaan & Status Stok Produk')
@section('page-title', 'Sinkronisasi Stok')

@push('styles')
<style>
    .stock-compare-card {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .stock-badge-match {
        background: #dcfce7; color: #15803d;
        border: 1px solid #86efac;
        border-radius: 20px;
        font-size: 11px; font-weight: 700;
        padding: 3px 10px; white-space: nowrap;
    }
    .stock-badge-diff {
        background: #fef2f2; color: #dc2626;
        border: 1px solid #fca5a5;
        border-radius: 20px;
        font-size: 11px; font-weight: 700;
        padding: 3px 10px; white-space: nowrap;
    }
    .stock-badge-nomap {
        background: #f3f4f6; color: #9ca3af;
        border: 1px solid #d1d5db;
        border-radius: 20px;
        font-size: 11px; font-weight: 700;
        padding: 3px 10px; white-space: nowrap;
    }
    .stock-num-local {
        font-size: 16px; font-weight: 800;
        color: #1e293b; font-family: monospace;
    }
    .stock-num-market {
        font-size: 16px; font-weight: 800;
        font-family: monospace;
    }
    .stock-num-market.match { color: #16a34a; }
    .stock-num-market.diff  { color: #dc2626; }
    .stock-arrow {
        font-size: 18px; color: #94a3b8;
    }
    .diff-pill {
        font-size: 10px; font-weight: 700;
        padding: 1px 7px; border-radius: 10px;
        display: inline-block; margin-top: 2px;
    }
    .diff-pill.plus  { background:#fef9c3; color:#92400e; }
    .diff-pill.minus { background:#fee2e2; color:#991b1b; }
    .diff-pill.zero  { background:#f0fdf4; color:#166534; }

    /* Stats summary bar */
    .stat-pill {
        border-radius: 10px;
        padding: 10px 18px;
        font-size: 13px;
        border: 1px solid;
    }
    .stat-pill.sync   { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
    .stat-pill.nosync { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    .stat-pill.nomap  { background: #f8fafc; border-color: #e2e8f0; color: #64748b; }
    .stat-pill .num   { font-size: 22px; font-weight: 800; display: block; line-height: 1; }

    /* Filter tab */
    .filter-tabs .nav-link { font-size: 12px; font-weight: 600; padding: 5px 14px; border-radius: 20px; color: #64748b; }
    .filter-tabs .nav-link.active { background: #1d4ed8; color: #fff; }

    /* Row highlight */
    tr.row-diff td { background: #fff5f5 !important; }
    tr.row-nomap td { background: #fafafa !important; }

    .safety-note { font-size: 10px; color: #94a3b8; }
</style>
@endpush

@section('content')
@php
    // Hitung statistik untuk summary bar
    $totalAll    = $mappedProducts->total();
    $totalSinkron = 0;
    $totalBeda    = 0;
    $totalTidakMap = 0;
    foreach ($mappedProducts as $mp) {
        if (!$mp->masterProduct) { $totalTidakMap++; continue; }
        $shouldBe = max(0, $mp->masterProduct->stock - ($mp->safety_stock ?? 0));
        if ($mp->stock === $shouldBe) $totalSinkron++;
        else $totalBeda++;
    }
@endphp

<div class="row g-3">
    {{-- ── LEFT: Tabel Pemetaan & Status Stok ── --}}
    <div class="col-xl-8 col-md-7">

        {{-- Summary Bar --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="stat-pill sync">
                <span class="num">{{ $totalSinkron }}</span>
                ✅ Sinkron
            </div>
            <div class="stat-pill nosync">
                <span class="num">{{ $totalBeda }}</span>
                ⚠️ Berbeda
            </div>
            <div class="stat-pill nomap">
                <span class="num">{{ $totalTidakMap }}</span>
                🔗 Belum Map
            </div>
            <div class="stat-pill" style="background:#f0f9ff;border-color:#bae6fd;color:#0369a1;">
                <span class="num">{{ $totalAll }}</span>
                📦 Total SKU
            </div>
        </div>

        <div class="card stock-compare-card bg-white mb-3">
            <div class="card-body p-0">

                {{-- Header + Actions --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-sync text-primary me-2"></i>Pemetaan & Perbandingan Stok
                    </h5>
                    <form action="{{ route('inventory.stock_sync.all') }}" method="POST"
                          onsubmit="return confirm('Sinkronisasi semua produk ke marketplace?')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                            <i class="fas fa-cloud-upload-alt me-1"></i> Sync Massal Semua
                        </button>
                    </form>
                </div>

                {{-- Search + Filter --}}
                <div class="p-3 border-bottom bg-light d-flex flex-wrap gap-2 align-items-center">
                    <form method="GET" class="flex-grow-1" style="min-width:200px;">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control"
                                   value="{{ request('search') }}"
                                   placeholder="Cari SKU, Nama Produk, ID Marketplace...">
                            @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
                            <button type="submit" class="btn btn-primary px-3">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    {{-- Filter tabs --}}
                    <ul class="nav filter-tabs mb-0">
                        <li class="nav-item">
                            <a class="nav-link {{ !request('filter') ? 'active' : '' }}"
                               href="{{ request()->fullUrlWithQuery(['filter' => null, 'page' => 1]) }}">Semua</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('filter') === 'diff' ? 'active' : '' }}"
                               href="{{ request()->fullUrlWithQuery(['filter' => 'diff', 'page' => 1]) }}">⚠️ Berbeda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('filter') === 'nomap' ? 'active' : '' }}"
                               href="{{ request()->fullUrlWithQuery(['filter' => 'nomap', 'page' => 1]) }}">🔗 Belum Map</a>
                        </li>
                    </ul>
                </div>

                {{-- Legenda --}}
                <div class="px-3 py-2 border-bottom d-flex flex-wrap gap-3 align-items-center" style="font-size:11px; background:#fafafa;">
                    <span class="text-muted fw-semibold">📖 Keterangan:</span>
                    <span><strong>Stok Lokal</strong> = stok di sistem ERP</span>
                    <span class="text-muted">|</span>
                    <span><strong>Ekspektasi Marketplace</strong> = Lokal − Safety Stock</span>
                    <span class="text-muted">|</span>
                    <span><strong>Stok Marketplace</strong> = stok yang terakhir ter-push / tercatat</span>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                        <thead style="background:#f8fafc; font-size:11px;">
                            <tr class="text-uppercase text-muted">
                                <th class="px-3 py-2" style="min-width:180px;">Produk / Toko</th>
                                <th class="text-center py-2" style="min-width:80px;">
                                    Stok Lokal<br>
                                    <span class="text-muted fw-normal" style="text-transform:none; font-size:10px;">(ERP)</span>
                                </th>
                                <th class="text-center py-2" style="min-width:60px;">
                                    Safety<br>Stock
                                </th>
                                <th class="text-center py-2" style="min-width:90px;">
                                    Ekspektasi<br>
                                    <span style="text-transform:none; font-size:10px;">(Lokal − Safety)</span>
                                </th>
                                <th class="text-center py-2" style="min-width:90px;">
                                    Stok<br>Marketplace
                                </th>
                                <th class="text-center py-2" style="min-width:90px;">Selisih</th>
                                <th class="text-center py-2" style="min-width:100px;">Status Sync</th>
                                <th class="text-center py-2" style="min-width:60px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappedProducts as $mp)
                                @php
                                    $localStock    = $mp->masterProduct ? (int)$mp->masterProduct->stock : null;
                                    $safetyStock   = (int)($mp->safety_stock ?? 0);
                                    $expectedMp    = $localStock !== null ? max(0, $localStock - $safetyStock) : null;
                                    $marketStock   = (int)$mp->stock;
                                    $selisih       = $expectedMp !== null ? ($marketStock - $expectedMp) : null;
                                    $isSinkron     = ($selisih === 0);
                                    $isNoMap       = ($localStock === null);

                                    $rowClass = $isNoMap ? 'row-nomap' : (!$isSinkron ? 'row-diff' : '');
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    {{-- Produk Info --}}
                                    <td class="px-3 py-2">
                                        <div class="fw-bold text-dark" style="font-size:13px;">{{ Str::limit($mp->name, 35) }}</div>
                                        <div class="font-monospace text-muted mt-1" style="font-size:10px;">
                                            SKU: <strong>{{ $mp->marketplace_sku ?? '—' }}</strong>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark border" style="font-size:10px;">
                                                {{ $mp->store->channel->name ?? '?' }}
                                            </span>
                                            <span class="text-muted ms-1" style="font-size:11px;">{{ $mp->store->store_name }}</span>
                                        </div>
                                        @if(!$mp->masterProduct)
                                            <div class="small text-danger mt-1" style="font-size:10px;">
                                                <i class="fas fa-exclamation-triangle"></i> Belum terhubung ke produk master
                                            </div>
                                        @else
                                            <div class="small text-primary mt-1" style="font-size:10px;">
                                                <i class="fas fa-link"></i> {{ $mp->masterProduct->name }} ({{ $mp->masterProduct->sku }})
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Stok Lokal --}}
                                    <td class="text-center">
                                        @if($localStock !== null)
                                            <span class="stock-num-local">{{ number_format($localStock) }}</span>
                                            <div class="safety-note">pcs</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Safety Stock --}}
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25">
                                            {{ $safetyStock }}
                                        </span>
                                    </td>

                                    {{-- Ekspektasi Marketplace --}}
                                    <td class="text-center">
                                        @if($expectedMp !== null)
                                            <span class="fw-bold font-monospace" style="font-size:15px; color:#64748b;">
                                                {{ number_format($expectedMp) }}
                                            </span>
                                            <div class="safety-note">seharusnya</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Stok Marketplace (Aktual tercatat) --}}
                                    <td class="text-center">
                                        @if($mp->last_synced_at)
                                            <span class="stock-num-market {{ $isSinkron ? 'match' : 'diff' }}">
                                                {{ number_format($marketStock) }}
                                            </span>
                                            <div class="safety-note">{{ $mp->last_synced_at->diffForHumans() }}</div>
                                        @else
                                            <span class="stock-num-market text-muted">{{ number_format($marketStock) }}</span>
                                            <div class="safety-note text-warning">belum pernah sync</div>
                                        @endif
                                    </td>

                                    {{-- Selisih --}}
                                    <td class="text-center">
                                        @if($selisih !== null)
                                            @if($selisih === 0)
                                                <span class="diff-pill zero">± 0</span>
                                            @elseif($selisih > 0)
                                                <span class="diff-pill plus">+{{ $selisih }}</span>
                                                <div class="safety-note text-warning">MP lebih banyak</div>
                                            @else
                                                <span class="diff-pill minus">{{ $selisih }}</span>
                                                <div class="safety-note text-danger">MP lebih sedikit</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Status Sync --}}
                                    <td class="text-center">
                                        @if($isNoMap)
                                            <span class="stock-badge-nomap">🔗 Belum Map</span>
                                        @elseif(!$mp->sync_stock)
                                            <span class="stock-badge-nomap">⏸ Sync Mati</span>
                                        @elseif($isSinkron)
                                            <span class="stock-badge-match">✅ Sinkron</span>
                                        @else
                                            <span class="stock-badge-diff">⚠️ Berbeda</span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="text-center">
                                        @if($mp->masterProduct)
                                            <form action="{{ route('inventory.stock_sync.product', $mp) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm {{ $isSinkron ? 'btn-outline-secondary' : 'btn-warning' }} px-2 fw-semibold"
                                                    title="Push stok sekarang">
                                                    <i class="fas fa-sync" style="font-size:11px;"></i>
                                                    {{ $isSinkron ? 'Sync' : 'Fix!' }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="fas fa-sync fa-2x mb-3 text-secondary opacity-25"></i>
                                        <p class="mb-0 small">Belum ada pemetaan produk marketplace.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top">
                    {{ $mappedProducts->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Log Sinkronisasi ── --}}
    <div class="col-xl-4 col-md-5">
        <div class="card border rounded shadow-sm bg-white overflow-hidden">
            <div class="card-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-history text-secondary me-2"></i>Log Sinkronisasi Terbaru
                </h6>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25">
                    {{ $syncLogs->count() }} entri
                </span>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    @forelse($syncLogs as $log)
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-{{ $log->status_badge }} text-uppercase"
                                      style="font-size: 0.65rem;">
                                    {{ $log->status_label }}
                                </span>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    {{ $log->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <div class="small fw-semibold text-dark">{{ $log->sku }}</div>
                            <div class="small text-muted">
                                Stok: <strong class="text-primary">{{ $log->pushed_stock }}</strong>
                                &nbsp;|&nbsp; {{ strtoupper($log->channel_code) }}
                            </div>
                            @if($log->status === 'failed' && $log->error_message)
                                <div class="mt-1 p-2 bg-danger-subtle text-danger rounded border border-danger border-opacity-10"
                                     style="font-size: 0.7rem; word-break: break-all;">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $log->error_message }}
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item p-4 text-center text-muted small">
                            Belum ada riwayat sinkronisasi.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Quick Guide --}}
        <div class="card border rounded shadow-sm bg-white mt-3">
            <div class="card-header bg-light border-bottom py-2 px-3">
                <h6 class="fw-bold text-dark mb-0 fs-6">💡 Cara Baca Kolom Stok</h6>
            </div>
            <div class="card-body p-3" style="font-size:12px;">
                <div class="mb-2">
                    <strong>Stok Lokal (ERP)</strong><br>
                    <span class="text-muted">Jumlah stok yang ada di gudang / sistem ERP Anda.</span>
                </div>
                <div class="mb-2">
                    <strong>Safety Stock</strong><br>
                    <span class="text-muted">Buffer yang tidak di-push ke marketplace. Contoh: lokal=100, safety=10 → push 90.</span>
                </div>
                <div class="mb-2">
                    <strong>Ekspektasi Marketplace</strong><br>
                    <span class="text-muted">Stok yang <em>seharusnya</em> tampil di marketplace = Lokal − Safety.</span>
                </div>
                <div class="mb-2">
                    <strong>Stok Marketplace</strong><br>
                    <span class="text-muted">Stok yang terakhir berhasil di-push / tercatat di marketplace.</span>
                </div>
                <div class="mb-0">
                    <strong>Selisih</strong><br>
                    <span class="text-muted">
                        <span class="diff-pill zero me-1">± 0</span> = sinkron<br>
                        <span class="diff-pill minus me-1">-X</span> = stok marketplace kurang, tekan <strong>Fix!</strong><br>
                        <span class="diff-pill plus me-1">+X</span> = stok marketplace lebih banyak dari ekspektasi
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
