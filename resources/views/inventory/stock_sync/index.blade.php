@extends('layouts.app')
@section('title', 'Pemetaan & Status Stok Produk')
@section('page-title', 'Sinkronisasi Stok')

@push('styles')
<style>
    /* ── Stock Sync Page Styles ── */
    .stock-badge-match {
        background:#dcfce7;color:#15803d;border:1px solid #86efac;
        border-radius:20px;font-size:11px;font-weight:700;padding:3px 10px;white-space:nowrap;
    }
    .stock-badge-diff {
        background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;
        border-radius:20px;font-size:11px;font-weight:700;padding:3px 10px;white-space:nowrap;
    }
    .stock-badge-nomap {
        background:#f3f4f6;color:#9ca3af;border:1px solid #d1d5db;
        border-radius:20px;font-size:11px;font-weight:700;padding:3px 10px;white-space:nowrap;
    }
    .stock-badge-syncoff {
        background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;
        border-radius:20px;font-size:11px;font-weight:700;padding:3px 10px;white-space:nowrap;
    }
    .stock-num-local  { font-size:17px;font-weight:800;color:#1e293b;font-family:monospace; }
    .stock-num-market { font-size:17px;font-weight:800;font-family:monospace; }
    .stock-num-market.match { color:#16a34a; }
    .stock-num-market.diff  { color:#dc2626; }
    .diff-pill {
        font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
        display:inline-block;
    }
    .diff-pill.plus  { background:#fef9c3;color:#92400e; }
    .diff-pill.minus { background:#fee2e2;color:#991b1b; }
    .diff-pill.zero  { background:#f0fdf4;color:#166534; }

    /* Summary stat cards */
    .stat-card {
        border-radius:12px;padding:12px 18px;border:1px solid;cursor:pointer;
        transition:transform .15s,box-shadow .15s;text-decoration:none;display:block;
    }
    .stat-card:hover { transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.1); }
    .stat-card.active { box-shadow:0 0 0 3px rgba(37,99,235,.35); }
    .stat-card.s-all   { background:#f0f9ff;border-color:#bae6fd;color:#0369a1; }
    .stat-card.s-sync  { background:#f0fdf4;border-color:#bbf7d0;color:#15803d; }
    .stat-card.s-diff  { background:#fef2f2;border-color:#fecaca;color:#dc2626; }
    .stat-card.s-nomap { background:#fafafa;border-color:#e2e8f0;color:#64748b; }
    .stat-card.s-syncoff{ background:#fff7ed;border-color:#fed7aa;color:#c2410c; }
    .stat-card .num    { font-size:28px;font-weight:900;line-height:1;display:block; }
    .stat-card .lbl    { font-size:11px;font-weight:700;margin-top:2px; }

    /* Filter bar */
    .filter-bar { background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px; }
    .filter-bar .form-label { font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;margin-bottom:3px; }
    .filter-bar .form-select,.filter-bar .form-control { font-size:12px;border-radius:8px; }

    /* Active filter pills */
    .active-filter-pill {
        display:inline-flex;align-items:center;gap:5px;
        background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;
        border-radius:20px;font-size:11px;font-weight:700;padding:3px 10px;
    }
    .active-filter-pill .remove { cursor:pointer;opacity:.7; }
    .active-filter-pill .remove:hover { opacity:1; }

    /* Table */
    .stock-table thead th {
        background:#f1f5f9;font-size:11px;font-weight:700;
        text-transform:uppercase;letter-spacing:.4px;color:#475569;
        padding:9px 10px;border-bottom:2px solid #e2e8f0;white-space:nowrap;
    }
    .stock-table tbody td { padding:10px;vertical-align:middle;border-color:#f1f5f9; }
    .stock-table tbody tr { transition:background .1s; }
    .stock-table tbody tr:hover td { background:#f8fafc !important; }
    .stock-table tbody tr.row-diff td  { background:#fff8f8; }
    .stock-table tbody tr.row-nomap td { background:#fafafa; }

    .safety-note { font-size:10px;color:#94a3b8;margin-top:1px; }
    .ch-badge { font-size:10px;padding:2px 7px;border-radius:5px;font-weight:700;border:1px solid; }
    .ch-shopee   { background:#fff0e6;color:#ee4d2d;border-color:#ffc5b0; }
    .ch-tiktok   { background:#f0f0f0;color:#010101;border-color:#ccc; }
    .ch-tokopedia{ background:#f0fdf4;color:#03ac0e;border-color:#a7f3d0; }
    .ch-lazada   { background:#f5f0ff;color:#6d28d9;border-color:#ddd6fe; }
    .ch-default  { background:#f3f4f6;color:#6b7280;border-color:#d1d5db; }

    /* Log panel */
    .log-panel { max-height:320px;overflow-y:auto; }
</style>
@endpush

@section('content')
@php
    $totalAll      = $mappedProducts->total();
    $totalSinkron  = 0; $totalBeda = 0; $totalTidakMap = 0; $totalSyncOff = 0;
    foreach ($mappedProducts as $mp) {
        if (!$mp->masterProduct) { $totalTidakMap++; continue; }
        if (!$mp->sync_stock)   { $totalSyncOff++; continue; }
        $expected = max(0, $mp->masterProduct->stock - ($mp->safety_stock ?? 0));
        ($mp->stock === $expected) ? $totalSinkron++ : $totalBeda++;
    }

    // Active filter labels
    $activeFilters = [];
    $filterLabels  = ['match'=>'✅ Sinkron','diff'=>'⚠️ Berbeda','nomap'=>'🔗 Belum Map'];
    if (request('filter') && isset($filterLabels[request('filter')])) {
        $activeFilters['filter'] = $filterLabels[request('filter')];
    }
    if (request('channel'))     $activeFilters['channel']     = '📡 '.strtoupper(request('channel'));
    if (request('store_id'))    $activeFilters['store_id']    = '🏪 Toko Dipilih';
    if (request('sync_status')) $activeFilters['sync_status'] = request('sync_status')==='on' ? '🟢 Sync Aktif' : '⏸ Sync Mati';
    if (request('search'))      $activeFilters['search']      = '🔍 '.request('search');
@endphp

{{-- ═══ SUMMARY STAT CARDS ═══ --}}
<div class="row g-2 mb-3">
    @php
        $statCards = [
            ['key'=>null,     'val'=>null,     'class'=>'s-all',    'num'=>$totalAll,     'lbl'=>'Total SKU',       'icon'=>'📦'],
            ['key'=>'filter', 'val'=>'match',  'class'=>'s-sync',   'num'=>$totalSinkron, 'lbl'=>'Sinkron',         'icon'=>'✅'],
            ['key'=>'filter', 'val'=>'diff',   'class'=>'s-diff',   'num'=>$totalBeda,    'lbl'=>'Berbeda / Perlu Sync', 'icon'=>'⚠️'],
            ['key'=>'filter', 'val'=>'nomap',  'class'=>'s-nomap',  'num'=>$totalTidakMap,'lbl'=>'Belum Map ke Produk', 'icon'=>'🔗'],
            ['key'=>'sync_status','val'=>'off','class'=>'s-syncoff','num'=>$totalSyncOff, 'lbl'=>'Sync Dimatikan',  'icon'=>'⏸'],
        ];
    @endphp
    @foreach($statCards as $sc)
        @php
            $isActive = request($sc['key']) === $sc['val'];
            $href = $sc['key']
                ? request()->fullUrlWithQuery([$sc['key'] => $sc['val'], 'page' => 1])
                : route('inventory.stock_sync');
        @endphp
        <div class="col-6 col-sm-4 col-md-2-4" style="flex:1;min-width:120px;">
            <a href="{{ $href }}" class="stat-card {{ $sc['class'] }} {{ $isActive ? 'active' : '' }}">
                <span class="num">{{ $sc['num'] }}</span>
                <span class="lbl">{{ $sc['icon'] }} {{ $sc['lbl'] }}</span>
            </a>
        </div>
    @endforeach
</div>

{{-- ═══ MAIN CARD (col-12 full width) ═══ --}}
<div class="card border-0 shadow-sm rounded-3 mb-3" style="overflow:hidden;">

    {{-- ── Header ── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 py-3 border-bottom"
         style="background:#fff;">
        <h5 class="fw-bold text-dark mb-0 fs-6">
            <i class="fas fa-sync text-primary me-2"></i>Pemetaan &amp; Perbandingan Stok
        </h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold"
                    data-bs-toggle="collapse" data-bs-target="#logPanel">
                <i class="fas fa-history me-1"></i> Log Sync
                @if($syncLogs->count())
                    <span class="badge bg-secondary ms-1">{{ $syncLogs->count() }}</span>
                @endif
            </button>
            <form action="{{ route('inventory.stock_sync.all') }}" method="POST"
                  onsubmit="return confirm('Sinkronisasi semua produk ke marketplace?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                    <i class="fas fa-cloud-upload-alt me-1"></i> Sync Massal Semua
                </button>
            </form>
        </div>
    </div>

    {{-- ── Log Panel (collapsible) ── --}}
    <div class="collapse" id="logPanel">
        <div class="border-bottom" style="background:#fafafa;">
            <div class="log-panel">
                @forelse($syncLogs as $log)
                    <div class="d-flex align-items-start gap-3 px-4 py-2 border-bottom" style="font-size:12px;">
                        <span class="badge bg-{{ $log->status_badge }} mt-1" style="font-size:10px;white-space:nowrap;">
                            {{ $log->status_label }}
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <span class="fw-bold text-dark">{{ $log->sku }}</span>
                            <span class="text-muted ms-2">Stok: <strong class="text-primary">{{ $log->pushed_stock }}</strong></span>
                            <span class="badge bg-light text-dark border ms-1" style="font-size:10px;">{{ strtoupper($log->channel_code) }}</span>
                            @if($log->status === 'failed' && $log->error_message)
                                <div class="text-danger mt-1" style="font-size:10px;">{{ Str::limit($log->error_message, 80) }}</div>
                            @endif
                        </div>
                        <span class="text-muted" style="font-size:10px;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">Belum ada riwayat sinkronisasi.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <form method="GET" id="filterForm">
        <div class="filter-bar m-3 mb-0">
            <div class="row g-2 align-items-end">

                {{-- Search --}}
                <div class="col-md-3">
                    <label class="form-label">🔍 Cari Produk / SKU</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           value="{{ request('search') }}"
                           placeholder="Nama, SKU, ID marketplace...">
                </div>

                {{-- Status Stok --}}
                <div class="col-md-2">
                    <label class="form-label">📊 Status Stok</label>
                    <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">— Semua Status —</option>
                        <option value="match"  {{ request('filter')==='match'  ? 'selected':'' }}>✅ Sinkron</option>
                        <option value="diff"   {{ request('filter')==='diff'   ? 'selected':'' }}>⚠️ Berbeda / Perlu Sync</option>
                        <option value="nomap"  {{ request('filter')==='nomap'  ? 'selected':'' }}>🔗 Belum Map ke Produk</option>
                    </select>
                </div>

                {{-- Channel --}}
                <div class="col-md-2">
                    <label class="form-label">📡 Channel</label>
                    <select name="channel" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">— Semua Channel —</option>
                        @foreach($channels as $ch)
                            <option value="{{ $ch->code }}" {{ request('channel')===$ch->code ? 'selected':'' }}>
                                {{ $ch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Toko --}}
                <div class="col-md-2">
                    <label class="form-label">🏪 Toko</label>
                    <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">— Semua Toko —</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ request('store_id')==$st->id ? 'selected':'' }}>
                                {{ $st->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Sync --}}
                <div class="col-md-2">
                    <label class="form-label">⚡ Sinkronisasi</label>
                    <select name="sync_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">— Semua —</option>
                        <option value="on"  {{ request('sync_status')==='on'  ? 'selected':'' }}>🟢 Sync Aktif</option>
                        <option value="off" {{ request('sync_status')==='off' ? 'selected':'' }}>⏸ Sync Mati</option>
                    </select>
                </div>

                {{-- Submit + Reset --}}
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request()->anyFilled(['search','filter','channel','store_id','sync_status']))
                        <a href="{{ route('inventory.stock_sync') }}"
                           class="btn btn-outline-secondary btn-sm" title="Reset semua filter">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>

            </div>
        </div>

        {{-- Active Filter Pills --}}
        @if(count($activeFilters))
            <div class="d-flex flex-wrap gap-2 px-3 pt-2 pb-1">
                <span class="text-muted" style="font-size:11px;line-height:24px;">Filter aktif:</span>
                @foreach($activeFilters as $key => $label)
                    <span class="active-filter-pill">
                        {{ $label }}
                        <a href="{{ request()->fullUrlWithQuery([$key => null, 'page' => 1]) }}"
                           class="remove text-decoration-none text-inherit ms-1">✕</a>
                    </span>
                @endforeach
            </div>
        @endif
    </form>

    {{-- ── Legenda ── --}}
    <div class="d-flex flex-wrap gap-3 align-items-center px-4 py-2 border-top border-bottom"
         style="background:#fafafa;font-size:11px;color:#64748b;">
        <span>📖 <strong>Stok Lokal</strong> = stok di gudang ERP</span>
        <span>·</span>
        <span><strong>Ekspektasi MP</strong> = Lokal − Safety Stock</span>
        <span>·</span>
        <span><strong>Stok Marketplace</strong> = stok terakhir di-push</span>
        <span>·</span>
        <span><strong>Selisih</strong> = Marketplace − Ekspektasi</span>
        <span class="ms-auto text-muted">{{ $mappedProducts->total() }} produk ditemukan</span>
    </div>

    {{-- ── TABLE ── --}}
    <div class="table-responsive">
        <table class="table stock-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="min-width:220px;">Produk / Toko</th>
                    <th class="text-center" style="min-width:90px;">
                        Stok Lokal<br><span style="font-weight:400;color:#94a3b8;">(ERP)</span>
                    </th>
                    <th class="text-center" style="min-width:70px;">Safety<br>Stock</th>
                    <th class="text-center" style="min-width:100px;">
                        Ekspektasi<br><span style="font-weight:400;color:#94a3b8;">Marketplace</span>
                    </th>
                    <th class="text-center" style="min-width:100px;">
                        Stok<br>Marketplace
                    </th>
                    <th class="text-center" style="min-width:80px;">Selisih</th>
                    <th class="text-center" style="min-width:105px;">Status</th>
                    <th class="text-center" style="min-width:65px;">Last Sync</th>
                    <th class="text-center" style="min-width:70px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mappedProducts as $mp)
                    @php
                        $localStock  = $mp->masterProduct ? (int)$mp->masterProduct->stock : null;
                        $safetyStock = (int)($mp->safety_stock ?? 0);
                        $expectedMp  = $localStock !== null ? max(0, $localStock - $safetyStock) : null;
                        $marketStock = (int)$mp->stock;
                        $selisih     = $expectedMp !== null ? ($marketStock - $expectedMp) : null;
                        $isSinkron   = ($selisih === 0);
                        $isNoMap     = ($localStock === null);
                        $syncOff     = !$mp->sync_stock;

                        $rowClass = $isNoMap ? 'row-nomap' : (!$isSinkron && !$syncOff ? 'row-diff' : '');
                        $chCode = strtolower($mp->store->channel->code ?? 'default');
                    @endphp
                    <tr class="{{ $rowClass }}">

                        {{-- Produk --}}
                        <td class="px-3">
                            @if($mp->image_url)
                                <img src="{{ $mp->image_url }}" alt="" class="rounded me-2 float-start"
                                     style="width:36px;height:36px;object-fit:cover;border:1px solid #e5e7eb;">
                            @endif
                            <div style="overflow:hidden;">
                                <div class="fw-bold text-dark" style="font-size:12px;line-height:1.3;">
                                    {{ Str::limit($mp->name, 40) }}
                                </div>
                                <div class="font-monospace text-muted mt-1" style="font-size:10px;">
                                    SKU: <strong>{{ $mp->marketplace_sku ?? '—' }}</strong>
                                </div>
                                <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                    <span class="ch-badge ch-{{ $chCode }}">
                                        {{ strtoupper($chCode) }}
                                    </span>
                                    <span class="text-muted" style="font-size:10px;">{{ $mp->store->store_name }}</span>
                                </div>
                                @if(!$mp->masterProduct)
                                    <div class="text-danger mt-1" style="font-size:10px;">
                                        <i class="fas fa-exclamation-triangle"></i> Belum terhubung ke produk master
                                    </div>
                                @else
                                    <div class="text-primary mt-1" style="font-size:10px;">
                                        <i class="fas fa-link"></i> {{ $mp->masterProduct->sku }}
                                    </div>
                                @endif
                            </div>
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
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25"
                                  style="font-size:12px;">
                                {{ $safetyStock }}
                            </span>
                        </td>

                        {{-- Ekspektasi MP --}}
                        <td class="text-center">
                            @if($expectedMp !== null)
                                <span class="fw-bold font-monospace" style="font-size:16px;color:#475569;">
                                    {{ number_format($expectedMp) }}
                                </span>
                                <div class="safety-note">seharusnya</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Stok Marketplace --}}
                        <td class="text-center">
                            <span class="stock-num-market {{ $isNoMap ? '' : ($isSinkron ? 'match' : 'diff') }}">
                                {{ number_format($marketStock) }}
                            </span>
                            @if(!$mp->last_synced_at)
                                <div class="safety-note text-warning">belum sync</div>
                            @endif
                        </td>

                        {{-- Selisih --}}
                        <td class="text-center">
                            @if($selisih !== null)
                                @if($selisih === 0)
                                    <span class="diff-pill zero">± 0</span>
                                @elseif($selisih > 0)
                                    <span class="diff-pill plus">+{{ $selisih }}</span>
                                    <div class="safety-note text-warning">MP lebih</div>
                                @else
                                    <span class="diff-pill minus">{{ $selisih }}</span>
                                    <div class="safety-note text-danger">MP kurang</div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="text-center">
                            @if($isNoMap)
                                <span class="stock-badge-nomap">🔗 Belum Map</span>
                            @elseif($syncOff)
                                <span class="stock-badge-syncoff">⏸ Sync Mati</span>
                            @elseif($isSinkron)
                                <span class="stock-badge-match">✅ Sinkron</span>
                            @else
                                <span class="stock-badge-diff">⚠️ Perlu Sync</span>
                            @endif
                        </td>

                        {{-- Last Sync --}}
                        <td class="text-center safety-note">
                            @if($mp->last_synced_at)
                                {{ $mp->last_synced_at->format('d/m H:i') }}<br>
                                <span class="text-muted" style="font-size:9px;">{{ $mp->last_synced_at->diffForHumans() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="text-center">
                            @if($mp->masterProduct)
                                <form action="{{ route('inventory.stock_sync.product', $mp) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm {{ ($isSinkron || $syncOff) ? 'btn-outline-secondary' : 'btn-warning fw-bold' }} px-2"
                                            title="{{ $isSinkron ? 'Sync ulang stok' : 'Perbaiki selisih stok!' }}">
                                        <i class="fas fa-sync" style="font-size:11px;"></i>
                                        {{ ($isSinkron || $syncOff) ? 'Sync' : 'Fix!' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="fas fa-box-open fa-2x mb-3 d-block opacity-25"></i>
                            Tidak ada produk yang sesuai filter.
                            @if(request()->anyFilled(['search','filter','channel','store_id','sync_status']))
                                <br><a href="{{ route('inventory.stock_sync') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    Reset Filter
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted" style="font-size:12px;">
            Menampilkan {{ $mappedProducts->firstItem() ?? 0 }}–{{ $mappedProducts->lastItem() ?? 0 }}
            dari {{ $mappedProducts->total() }} produk
        </div>
        {{ $mappedProducts->links() }}
    </div>

</div>

{{-- ── PANDUAN BACA STOK (row bawah) ── --}}
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body py-2 px-4">
        <div class="d-flex flex-wrap gap-4 align-items-center" style="font-size:11px;color:#64748b;">
            <strong class="text-dark">💡 Cara Baca:</strong>
            <span><strong class="text-dark">Stok Lokal</strong> = stok di gudang ERP Anda</span>
            <span><strong class="text-dark">Safety Stock</strong> = buffer tidak di-push (contoh: lokal=100, safety=10 → push 90)</span>
            <span><strong class="text-dark">Ekspektasi</strong> = Lokal − Safety = yang seharusnya di marketplace</span>
            <span>
                <span class="diff-pill zero me-1">±0</span> Sinkron &nbsp;
                <span class="diff-pill minus me-1">-X</span> MP kurang → tekan <strong>Fix!</strong> &nbsp;
                <span class="diff-pill plus me-1">+X</span> MP lebih dari ekspektasi
            </span>
        </div>
    </div>
</div>

@endsection
