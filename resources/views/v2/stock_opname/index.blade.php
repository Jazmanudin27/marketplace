@extends('v2.layouts.app')

@section('title', 'Stock Opname V2')

@push('styles')
<style>
/* ─── Stock Opname V2 Custom Styles (Matching Barang Keluar V2) ─── */
.so-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.so-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.so-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.so-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.so-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.so-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- ── Page Header & Tabs ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-clipboard-check text-warning fs-5"></i> Stock Opname (Gudang Jadi)
        </h1>
        <p class="text-muted small mb-0">Pencatatan dan audit stok fisik persediaan barang secara berkala</p>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── Nav Tabs & Action Buttons ── --}}
<div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link text-dark bg-white border fw-semibold px-3 py-1.5" style="font-size: 0.82rem; border-radius: 8px;" href="{{ route('v2.gudang_jadi.index') }}">
                <i class="bi bi-journals me-1.5"></i> Semua Mutasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-dark bg-white border fw-semibold px-3 py-1.5" style="font-size: 0.82rem; border-radius: 8px;" href="{{ route('v2.gudang_jadi.index', ['type' => 'in']) }}">
                <i class="bi bi-box-arrow-in-down me-1.5 text-success"></i> Mutasi Masuk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-dark bg-white border fw-semibold px-3 py-1.5" style="font-size: 0.82rem; border-radius: 8px;" href="{{ route('v2.gudang_jadi.index', ['type' => 'out']) }}">
                <i class="bi bi-box-arrow-up-right me-1.5 text-danger"></i> Mutasi Keluar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active bg-warning text-dark fw-semibold px-3 py-1.5" style="font-size: 0.82rem; border-radius: 8px;" href="{{ Route::has('v2.stock_opname.index') ? route('v2.stock_opname.index') : url('/v2/stock-opname') }}">
                <i class="bi bi-clipboard-check me-1.5 text-dark"></i> Stock Opname
            </a>
        </li>
    </ul>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.stock_opname.create') }}" class="btn btn-sm text-white fw-semibold py-1.5 px-3" style="background:#d97706; border:none; border-radius:6px;">
            + Catat Stock Opname
        </a>
    </div>
</div>

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Transaksi Opname -->
    <div class="col-12 col-md-6">
        <div class="so-kpi-card border-start border-warning border-3">
            <div class="so-kpi-title text-warning">Total Transaksi Opname</div>
            <div class="so-kpi-value text-warning">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="so-kpi-sub">Total sesi pencatatan penyesuaian stok fisik</div>
            <i class="bi bi-clipboard-data so-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Total Selisih Qty -->
    <div class="col-12 col-md-6">
        <div class="so-kpi-card border-start {{ $totalDiffQty >= 0 ? 'border-success' : 'border-danger' }} border-3">
            <div class="so-kpi-title {{ $totalDiffQty >= 0 ? 'text-success' : 'text-danger' }}">Akumulasi Selisih Qty</div>
            <div class="so-kpi-value {{ $totalDiffQty >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $totalDiffQty >= 0 ? '+' : '' }}{{ number_format($totalDiffQty, 0, ',', '.') }} Qty
            </div>
            <div class="so-kpi-sub">Total selisih fisik dikurangi sistem</div>
            <i class="bi bi-arrow-down-up so-kpi-icon {{ $totalDiffQty >= 0 ? 'text-success' : 'text-danger' }}"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.stock_opname.index') }}" class="row g-2 align-items-end">
        <!-- Pencarian -->
        <div class="col-12 col-md-5">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Barang / SKU / Referensi</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama barang, SKU, PIC..." value="{{ request('search') }}">
        </div>

        <!-- Tanggal Mulai -->
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <!-- Tanggal Selesai -->
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Selesai</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>

        <!-- Action Buttons Inline -->
        <div class="col-12 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;" title="Terapkan Filter">
                <i class="bi bi-funnel"></i>
            </button>
            <a href="{{ route('v2.stock_opname.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;" title="Reset Filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 170px;">TANGGAL & PERIODE</th>
                    <th class="py-2.5">NAMA BARANG / SKU</th>
                    <th class="text-center py-2.5" style="width: 120px;">TIPE ITEM</th>
                    <th class="text-center py-2.5" style="width: 130px;">SELISIH QTY</th>
                    <th class="text-end py-2.5" style="width: 140px;">STOK AKHIR</th>
                    <th class="text-end pe-3 py-2.5" style="width: 250px;">REFERENSI / PETUGAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opnames as $row)
                    @php
                        $itemName = $row->masterProduct ? $row->masterProduct->name : ($row->inventoryItem ? $row->inventoryItem->name : 'Item Hilang');
                        $itemSku  = $row->masterProduct ? $row->masterProduct->sku : ($row->inventoryItem ? $row->inventoryItem->sku : '-');
                        $itemType = $row->masterProduct ? 'PRODUK JADI' : ($row->inventoryItem ? strtoupper($row->inventoryItem->type) : 'BARANG');
                        $diffQty  = $row->quantity;
                    @endphp
                    <tr>
                        <td class="ps-3 py-2.5">
                            <div class="fw-bold text-dark">{{ $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-' }}</div>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                <i class="bi bi-clock me-1"></i>{{ $row->created_at ? $row->created_at->diffForHumans() : '-' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $itemName }}</div>
                            <div class="font-monospace text-muted" style="font-size:0.72rem;">SKU: {{ $itemSku ?: '—' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                {{ $itemType }}
                            </span>
                        </td>
                        <td class="text-center fw-bold font-monospace" style="font-size: 0.88rem;">
                            @if($diffQty > 0)
                                <span class="text-success"><i class="bi bi-caret-up-fill me-0.5"></i>+{{ number_format($diffQty, 0, ',', '.') }}</span>
                            @elseif($diffQty < 0)
                                <span class="text-danger"><i class="bi bi-caret-down-fill me-0.5"></i>{{ number_format($diffQty, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace" style="font-size: 0.88rem;">
                            {{ number_format($row->balance_after, 0, ',', '.') }}
                        </td>
                        <td class="text-end pe-3">
                            <div class="fw-semibold text-dark" style="font-size: 0.78rem;">
                                {{ $row->reference }}
                            </div>
                            <div class="text-muted small" style="font-size: 0.7rem;">
                                Oleh: <strong>{{ $row->user->name ?? 'Sistem' }}</strong>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-clipboard-x fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            Belum ada riwayat stock opname yang dicatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($opnames->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $opnames->links() }}
        </div>
    @endif
</div>

@endsection
