@extends('v2.layouts.app')

@section('title', 'Mutasi Barang Keluar - Gudang Jadi V2')

@push('styles')
<style>
/* ─── Gudang Jadi V2 Custom Styles ─── */
.bk-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.bk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.bk-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.bk-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.bk-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.bk-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}

/* ── Custom Badge & Table Styles ── */
.bg-purple-subtle { background-color: #f3e8ff !important; }
.text-purple { color: #7e22ce !important; }
.border-purple-subtle { border-color: #e9d5ff !important; }

.gj-table-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    color: #ffffff !important;
}
.gj-table-header th {
    color: #f8fafc !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.04em !important;
    font-size: 0.74rem !important;
    border: none !important;
    padding: 12px 14px !important;
}
.gj-table-row {
    transition: all 0.15s ease-in-out;
}
.gj-table-row:hover {
    background-color: #fff1f2 !important;
    box-shadow: inset 3px 0 0 #ef4444;
}
.gj-qty-badge-out {
    background: linear-gradient(135deg, #f43f5e, #e11d48);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 5px 14px;
    border-radius: 50rem;
    box-shadow: 0 2px 6px rgba(244, 63, 94, 0.25);
    display: inline-block;
}
.gj-product-icon-out {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.gj-avatar-initial-red {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #b91c1c);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.72rem;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Mutasi Barang Keluar (Gudang Jadi)
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.gudang_jadi.create', ['type' => 'out']) }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#dc2626; border:none;">
            <i class="bi bi-plus-lg me-1"></i> Catat Barang Keluar
        </a>
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

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Transaksi Keluar -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-danger border-3">
            <div class="bk-kpi-title text-danger">Total Transaksi Keluar</div>
            <div class="bk-kpi-value text-danger">
                {{ number_format($totalTransactions, 0, ',', '.') }} Transaksi
            </div>
            <div class="bk-kpi-sub">Total pengeluaran barang dari gudang</div>
            <i class="bi bi-box-arrow-up-right bk-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Total Qty Dikeluarkan -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-warning border-3">
            <div class="bk-kpi-title text-warning">Total Qty Dikeluarkan</div>
            <div class="bk-kpi-value text-warning">
                -{{ number_format($totalOutboundQty, 0, ',', '.') }} PCS
            </div>
            <div class="bk-kpi-sub">Akumulasi quantity produk dikeluarkan</div>
            <i class="bi bi-boxes bk-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Variasi Produk -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-primary border-3">
            <div class="bk-kpi-title text-primary">Variasi Produk Master</div>
            <div class="bk-kpi-value text-primary">
                {{ number_format($totalUniqueProducts, 0, ',', '.') }} SKU
            </div>
            <div class="bk-kpi-sub">Jumlah varian produk master</div>
            <i class="bi bi-tags-fill bk-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Petugas Operasional -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="bk-kpi-card border-start border-dark border-3">
            <div class="bk-kpi-title text-dark">Petugas Operasional</div>
            <div class="bk-kpi-value text-dark">
                Aktif
            </div>
            <div class="bk-kpi-sub">Pencatatan mutasi gudang terkontrol</div>
            <i class="bi bi-person-badge bk-kpi-icon text-dark"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.gudang_jadi.keluar') }}" class="row g-2 align-items-end">
        <!-- Pencarian -->
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Cari Nama Produk / SKU / Ref</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Cari nama, SKU, ref..." value="{{ request('search') }}">
            </div>
        </div>

        <!-- Pilih Produk Master -->
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Pilih Produk Master</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">Semua Produk Master</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                        [{{ $p->sku }}] {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Tanggal Mulai -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
        </div>

        <!-- Tanggal Selesai -->
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
        </div>

        <!-- Action Buttons Inline -->
        <div class="col-12 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;" title="Terapkan Filter">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request()->anyFilled(['search', 'start_date', 'end_date', 'product_id']))
                <a href="{{ route('v2.gudang_jadi.keluar') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-4 border">
    <!-- Card Toolbar Header -->
    <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-3 border border-danger border-opacity-25">
                <i class="bi bi-journal-minus fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0">Riwayat Mutasi Barang Keluar</h6>
                <p class="text-muted small mb-0" style="font-size: 0.74rem;">Daftar pengeluaran dan pemakaian produk dari persediaan gudang jadi</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                <i class="bi bi-layers-fill me-1"></i> Total {{ number_format($mutations->total(), 0, ',', '.') }} Records
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="gj-table-header">
                <tr>
                    <th class="ps-3 text-center" style="width: 50px;">NO</th>
                    <th style="width: 150px;"><i class="bi bi-calendar3 me-1"></i> WAKTU</th>
                    <th><i class="bi bi-box-seam me-1"></i> PRODUK MASTER & SKU</th>
                    <th><i class="bi bi-bookmark-star me-1"></i> KATEGORI & CATATAN REFERENSI</th>
                    <th class="text-center" style="width: 140px;"><i class="bi bi-dash-circle me-1"></i> QTY KELUAR</th>
                    <th class="text-center" style="width: 130px;"><i class="bi bi-shield-check me-1"></i> STATUS</th>
                    <th class="pe-3" style="width: 160px;"><i class="bi bi-person-badge me-1"></i> PETUGAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $index => $m)
                    @php
                        // Parse Reference & Category
                        $refText = $m->reference ?? 'Mutasi Keluar';
                        $category = 'Pengeluaran Stok';
                        $detail = '';
                        $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                        $iconClass = 'bi-box-arrow-up-right';

                        $refLower = strtolower($refText);

                        if (str_contains($refLower, 'penjualan') || str_contains($refLower, 'pesanan') || str_contains($refLower, 'order')) {
                            $category = 'Pengeluaran Pesanan';
                            $badgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                            $iconClass = 'bi-cart-check-fill';
                            $parts = explode(':', $refText, 2);
                            $detail = isset($parts[1]) ? trim($parts[1]) : '';
                        } elseif (str_contains($refLower, 'sample') || str_contains($refLower, 'sampel')) {
                            $category = 'Sample / Display';
                            $badgeClass = 'bg-purple-subtle text-purple border-purple-subtle';
                            $iconClass = 'bi-gift-fill';
                            $detail = $refText;
                        } elseif (str_contains($refLower, 'rusak') || str_contains($refLower, 'afval')) {
                            $category = 'Barang Rusak / Afval';
                            $badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                            $iconClass = 'bi-exclamation-triangle-fill';
                            $detail = $refText;
                        } else {
                            $parts = explode(':', $refText, 2);
                            if (count($parts) > 1) {
                                $category = trim($parts[0]);
                                $detail = trim($parts[1]);
                            } else {
                                $category = 'Pengeluaran Stok';
                                $detail = $refText;
                            }
                        }

                        $userName = $m->user->name ?? 'Ruang Seragam';
                        $userInitial = strtoupper(substr($userName, 0, 2));
                    @endphp
                    <tr class="gj-table-row">
                        <!-- NO -->
                        <td class="ps-3 text-center">
                            <span class="badge bg-light text-secondary border rounded-circle" style="width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">
                                {{ $mutations->firstItem() + $index }}
                            </span>
                        </td>

                        <!-- WAKTU -->
                        <td>
                            <div class="fw-bold text-dark" style="font-size: 0.83rem;">
                                {{ $m->created_at ? $m->created_at->format('d/m/Y') : '-' }}
                            </div>
                            <div class="text-muted d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                <i class="bi bi-clock"></i> {{ $m->created_at ? $m->created_at->format('H:i') : '-' }} WIB
                            </div>
                        </td>

                        <!-- PRODUK MASTER & SKU -->
                        <td>
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="gj-product-icon-out bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-0.5">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div class="overflow-hidden">
                                    @if($m->masterProduct)
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 320px;" title="{{ $m->masterProduct->name }}">
                                            {{ $m->masterProduct->name }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                            <span class="badge bg-slate-100 text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem; background:#f1f5f9;">
                                                <i class="bi bi-qr-code me-1 text-muted"></i>{{ $m->masterProduct->sku }}
                                            </span>
                                            <span class="text-muted" style="font-size: 0.68rem;">
                                                • Stok saat ini: <b>{{ number_format($m->masterProduct->stock, 0, ',', '.') }}</b> {{ $m->masterProduct->unit ?? 'PCS' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted italic">— (Produk Master Tidak Ditemukan)</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- KATEGORI & CATATAN REFERENSI -->
                        <td>
                            <div class="mb-1">
                                <span class="badge {{ $badgeClass }} border px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 0.68rem;">
                                    <i class="bi {{ $iconClass }} me-1"></i>{{ $category }}
                                </span>
                            </div>
                            @if($detail)
                                <div class="text-secondary small font-monospace text-truncate" style="max-width: 280px; font-size: 0.73rem;" title="{{ $detail }}">
                                    <i class="bi bi-hash me-0.5 text-muted"></i>{{ $detail }}
                                </div>
                            @endif
                        </td>

                        <!-- QTY KELUAR -->
                        <td class="text-center">
                            <span class="gj-qty-badge-out">
                                -{{ number_format(abs($m->quantity), 0, ',', '.') }} {{ $m->masterProduct->unit ?? 'PCS' }}
                            </span>
                        </td>

                        <!-- STATUS -->
                        <td class="text-center">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.7rem;">
                                <i class="bi bi-box-arrow-up-right" style="font-size: 0.65rem;"></i> Dikeluarkan
                            </span>
                        </td>

                        <!-- PETUGAS -->
                        <td class="pe-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gj-avatar-initial-red shadow-sm">
                                    {{ $userInitial }}
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 110px; font-size:0.78rem;" title="{{ $userName }}">
                                        {{ $userName }}
                                    </div>
                                    <div class="text-muted" style="font-size:0.65rem;">Petugas Gudang</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-25"></i>
                            <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Mutasi Keluar</h6>
                            <p class="small text-muted mb-0">Tidak ada transaksi pengeluaran barang keluar yang sesuai dengan filter.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($mutations->hasPages())
        <div class="p-3 border-top bg-light d-flex align-items-center justify-content-between">
            <span class="text-muted small">Menampilkan {{ $mutations->firstItem() }} - {{ $mutations->lastItem() }} dari {{ $mutations->total() }} data</span>
            <div>{{ $mutations->links() }}</div>
        </div>
    @endif
</div>

@endsection
