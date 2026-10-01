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

{{-- ── Data Table Stock Opname ── --}}
<div class="v2-card p-3 p-md-4 shadow-sm mb-4">
    <div class="table-responsive border rounded-3 overflow-hidden bg-white">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO</th>
                    <th class="py-2.5" style="width: 160px;">TANGGAL & WAKTU</th>
                    <th class="py-2.5">BARANG & SKU</th>
                    <th class="text-center py-2.5" style="width: 140px;">QTY SELISIH</th>
                    <th class="text-center py-2.5" style="width: 120px;">STOK AKHIR</th>
                    <th class="text-end pe-3 py-2.5" style="width: 110px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opnames as $index => $row)
                    @php
                        $itemName = $row->masterProduct ? $row->masterProduct->name : ($row->inventoryItem ? $row->inventoryItem->name : 'Item Hilang');
                        $itemSku  = $row->masterProduct ? $row->masterProduct->sku : ($row->inventoryItem ? $row->inventoryItem->sku : '-');
                        $unit     = $row->masterProduct->unit ?? ($row->inventoryItem->unit ?? 'PCS');
                        $diffQty  = $row->quantity;
                    @endphp
                    <tr>
                        <td class="ps-3 py-2 text-center text-muted">
                            {{ $opnames->firstItem() + $index }}
                        </td>
                        <td class="py-2">
                            <div class="fw-bold text-dark">{{ $row->created_at ? $row->created_at->format('d/m/Y') : '-' }}</div>
                            <span class="text-muted small" style="font-size: 0.72rem;">
                                {{ $row->created_at ? $row->created_at->format('H:i') : '-' }} WIB
                            </span>
                        </td>
                        <td class="py-2">
                            <div class="fw-bold text-dark">{{ $itemName }}</div>
                            <span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">
                                SKU: {{ $itemSku ?: '—' }}
                            </span>
                        </td>
                        <td class="text-center py-2">
                            @if($diffQty > 0)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold">
                                    +{{ number_format($diffQty, 0, ',', '.') }} {{ $unit }}
                                </span>
                            @elseif($diffQty < 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold">
                                    {{ number_format($diffQty, 0, ',', '.') }} {{ $unit }}
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 fw-bold">
                                    0 {{ $unit }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center py-2 font-monospace fw-semibold text-dark">
                            {{ number_format($row->balance_after ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-end pe-3 py-2">
                            <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 fw-semibold" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#detailModalSO{{ $row->id }}">
                                Detail
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Belum ada riwayat stock opname yang dicatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($opnames->hasPages())
        <div class="pt-3 border-top mt-3">
            {{ $opnames->links() }}
        </div>
    @endif
</div>

{{-- ── Premium Detail Modals Stock Opname ── --}}
@foreach ($opnames as $row)
    @php
        $itemName = $row->masterProduct ? $row->masterProduct->name : ($row->inventoryItem ? $row->inventoryItem->name : 'Item Hilang');
        $itemSku  = $row->masterProduct ? $row->masterProduct->sku : ($row->inventoryItem ? $row->inventoryItem->sku : '-');
        $unit     = $row->masterProduct->unit ?? ($row->inventoryItem->unit ?? 'PCS');
        $diffQty  = $row->quantity;
        $qtyBefore = ($row->balance_after ?? 0) - $diffQty;

        $userName = $row->user->name ?? 'Sistem / Admin';
        $userInitial = strtoupper(substr($userName, 0, 2));
    @endphp
    <div class="modal fade" id="detailModalSO{{ $row->id }}" tabindex="-1" aria-labelledby="detailModalSOLabel{{ $row->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <!-- Modal Header -->
                <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <div class="d-flex align-items-center gap-2">
                        <div>
                            <h6 class="modal-title fw-bold mb-0 text-white" id="detailModalSOLabel{{ $row->id }}" style="letter-spacing: 0.02em;">
                                DETAIL AUDIT STOCK OPNAME #{{ $row->id }}
                            </h6>
                            <span class="text-white-50 small" style="font-size: 0.72rem;">Portal ERP V2 Gudang Jadi</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4 bg-white" style="font-size: 0.84rem;">
                    <!-- Top Hero Card Banner -->
                    <div class="p-3 mb-3.5 d-flex align-items-center justify-content-between flex-wrap gap-2 border rounded-3" style="background: {{ $diffQty > 0 ? '#f0fdf4' : ($diffQty < 0 ? '#fff1f2' : '#f8fafc') }}; border-color: {{ $diffQty > 0 ? '#bbf7d0' : ($diffQty < 0 ? '#fecdd3' : '#e2e8f0') }} !important;">
                        <div>
                            <span class="text-uppercase fw-semibold d-block small {{ $diffQty > 0 ? 'text-success' : ($diffQty < 0 ? 'text-danger' : 'text-secondary') }}" style="font-size: 0.68rem; letter-spacing: 0.05em;">JUMLAH SELISIH STOK (OPNAME)</span>
                            <div class="fw-bold {{ $diffQty > 0 ? 'text-success' : ($diffQty < 0 ? 'text-danger' : 'text-secondary') }}" style="font-size: 1.7rem; line-height: 1.1;">
                                {{ $diffQty > 0 ? '+' : '' }}{{ number_format($diffQty, 0, ',', '.') }} <span style="font-size: 1rem;">{{ $unit }}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $diffQty > 0 ? 'bg-success' : ($diffQty < 0 ? 'bg-danger' : 'bg-secondary') }} text-white px-3 py-1.5 rounded-pill fw-semibold shadow-sm mb-1 d-inline-block" style="font-size: 0.72rem;">
                                {{ $diffQty > 0 ? 'Selisih Plus (+)' : ($diffQty < 0 ? 'Selisih Minus (-)' : 'Stok Sesuai (0)') }}
                            </span>
                            <div class="text-secondary small fw-semibold" style="font-size: 0.75rem;">
                                Tanggal: {{ $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-' }} WIB
                            </div>
                        </div>
                    </div>

                    <!-- Produk Master Info Card -->
                    <div class="card border rounded-3 p-3 mb-3 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-muted fw-bold small uppercase" style="font-size: 0.68rem; letter-spacing: 0.04em;">NAMA BARANG AUDIT</span>
                            <span class="badge bg-white text-dark border font-monospace px-2 py-0.5" style="font-size: 0.7rem;">
                                SKU: {{ $itemSku ?: '-' }}
                            </span>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1">
                            {{ $itemName }}
                        </div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            Satuan Utama: <b>{{ $unit }}</b>
                        </div>
                    </div>

                    <!-- Stats Grid Comparison -->
                    <div class="row g-2 mb-3.5">
                        <div class="col-4">
                            <div class="border rounded-3 p-2.5 text-center bg-white shadow-xs">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;">STOK SEBELUM</span>
                                <span class="fw-bold text-secondary fs-6">{{ number_format($qtyBefore, 0, ',', '.') }} {{ $unit }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-2.5 text-center {{ $diffQty > 0 ? 'bg-success bg-opacity-10 border-success border-opacity-25' : ($diffQty < 0 ? 'bg-danger bg-opacity-10 border-danger border-opacity-25' : 'bg-light') }} shadow-xs">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;">SELISIH OPNAME</span>
                                <span class="fw-bold {{ $diffQty > 0 ? 'text-success' : ($diffQty < 0 ? 'text-danger' : 'text-secondary') }} fs-6">
                                    {{ $diffQty > 0 ? '+' : '' }}{{ number_format($diffQty, 0, ',', '.') }} {{ $unit }}
                                </span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 p-2.5 text-center bg-primary bg-opacity-10 border-primary border-opacity-25 shadow-xs">
                                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;">STOK FISIK AKHIR</span>
                                <span class="fw-bold text-primary fs-6">{{ number_format($row->balance_after ?? 0, 0, ',', '.') }} {{ $unit }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan & Referensi Audit -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <span class="text-muted d-block small" style="font-size:0.7rem;">NOMOR REFERENSI / SPK</span>
                                <div class="fw-semibold text-dark" style="font-size:0.82rem;">{{ $row->reference ?: 'Penyesuaian Stock Opname' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <span class="text-muted d-block small" style="font-size:0.7rem;">AUDITOR / PETUGAS</span>
                                <div class="d-flex align-items-center gap-2 mt-0.5">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700;">
                                        {{ $userInitial }}
                                    </div>
                                    <span class="fw-semibold text-dark" style="font-size:0.82rem;">{{ $userName }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light py-2 px-4 border-top d-flex justify-content-between">
                    <span class="text-muted small" style="font-size: 0.72rem;">Ref ID: #SO-{{ $row->id }}</span>
                    <button type="button" class="btn btn-sm btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
