@extends('v2.layouts.app')

@section('title', 'Riwayat Stok Opname Titipan Barang (Konsinyasi) V2')

@push('styles')
<style>
.compact-kpi-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    transition: all 0.15s ease;
}
.compact-kpi-box:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.compact-kpi-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    margin-bottom: 2px;
}
.compact-kpi-val {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.compact-kpi-sub {
    font-size: 0.68rem;
    color: #94a3b8;
    margin-top: 2px;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0" style="font-size: 1.15rem;">
            <i class="bi bi-clipboard-check text-warning fs-5"></i> Riwayat Stok Opname Titipan Barang
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
            Hasil pemeriksaan fisik stok titipan konsinyasi gudang dan penyesuaian selisih ke sistem ERP
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.titipan_barang.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-2" style="font-size: 0.78rem;">
            <i class="bi bi-box-arrow-in-down me-1"></i> Penerimaan
        </a>
        <a href="{{ route('v2.titipan_barang.stock_card') }}" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-2" style="font-size: 0.78rem;">
            <i class="bi bi-card-checklist me-1"></i> Kartu Stok
        </a>
        <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm py-1 px-3 fw-semibold text-white shadow-sm" style="background:#2563eb; border:none; border-radius:6px; font-size: 0.8rem;">
            <i class="bi bi-qr-code-scan me-1"></i> Mulai Scan Stok Opname
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-2.5" role="alert" style="font-size: 0.82rem;">
        <i class="bi bi-check-circle-fill me-1.5 text-success"></i>{{ session('success') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-2.5" role="alert" style="font-size: 0.82rem;">
        <i class="bi bi-exclamation-triangle-fill me-1.5 text-danger"></i>{{ session('error') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-primary border-3">
            <div class="compact-kpi-label text-primary">Total Riwayat Opname</div>
            <div class="compact-kpi-val text-dark">{{ number_format($totalCount, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">Item</span></div>
            <div class="compact-kpi-sub">Hasil audit & koreksi fisik</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-success border-3" style="background: #f0fdf4;">
            <div class="compact-kpi-label text-success">
                <i class="bi bi-box-seam text-success me-1"></i>Total Fisik Terhitung
            </div>
            <div class="compact-kpi-val text-success">{{ number_format($totalActualExamined, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">PCS</span></div>
            <div class="compact-kpi-sub">{{ $totalMatchCount ?? 0 }} item pas / cocok fisik</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-danger border-3" style="background: #fef2f2;">
            <div class="compact-kpi-label text-danger">
                <i class="bi bi-dash-circle text-danger me-1"></i>Total Selisih Kurang
            </div>
            <div class="compact-kpi-val text-danger">-{{ number_format($totalDiffMinus, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">PCS</span></div>
            <div class="compact-kpi-sub">Fisik lebih sedikit dari ERP</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-info border-3" style="background: #f0f9ff;">
            <div class="compact-kpi-label text-info-emphasis">
                <i class="bi bi-plus-circle text-info me-1"></i>Total Selisih Lebih
            </div>
            <div class="compact-kpi-val text-info-emphasis">+{{ number_format($totalDiffPlus, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">PCS</span></div>
            <div class="compact-kpi-sub">Surplus fisik di gudang</div>
        </div>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
    <div class="card-body p-2.5">
        <form method="GET" action="{{ route('v2.titipan_barang.deductions.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Cari SKU / Produk / Ref / PIC..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="supplier_id" class="form-select form-select-sm select2">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="reason" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        <option value="manual_adjustment" {{ request('reason') == 'manual_adjustment' ? 'selected' : '' }}>Koreksi Opname Gudang</option>
                        <option value="damaged" {{ request('reason') == 'damaged' ? 'selected' : '' }}>Rusak / Hilang</option>
                        <option value="unscanned_order" {{ request('reason') == 'unscanned_order' ? 'selected' : '' }}>Terlewat Scan Order</option>
                        <option value="return_supplier" {{ request('reason') == 'return_supplier' ? 'selected' : '' }}>Retur ke Supplier</option>
                        <option value="direct_sale" {{ request('reason') == 'direct_sale' ? 'selected' : '' }}>Penjualan Langsung</option>
                        <option value="other" {{ request('reason') == 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="stock_source" class="form-select form-select-sm fw-semibold">
                        <option value="">Semua Sinkronisasi</option>
                        <option value="supplier" {{ request('stock_source') === 'supplier' ? 'selected' : '' }}>🏷️ Titipan Saja</option>
                        <option value="gudang" {{ request('stock_source') === 'gudang' ? 'selected' : '' }}>🏭 Gudang Master Juga</option>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <input type="date" name="date_from" class="form-control form-control-sm px-1" value="{{ request('date_from') }}" title="Tanggal Mulai">
                </div>
                <div class="col-6 col-md-1">
                    <input type="date" name="date_to" class="form-control form-control-sm px-1" value="{{ request('date_to') }}" title="Tanggal Akhir">
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Filter"><i class="bi bi-filter"></i></button>
                    @if(request()->hasAny(['search', 'supplier_id', 'stock_source', 'reason', 'date_from', 'date_to']))
                        <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Main Table Riwayat Opname ── --}}
<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-list-check text-primary"></i>
            <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.03em; font-size: 0.75rem;">Daftar Hasil Stok Opname Titipan</span>
            <span class="badge bg-light text-muted border px-1.5 py-0.5" style="font-size:0.68rem;">
                {{ $deductions->total() }} Data
            </span>
        </div>
        <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 fw-semibold" style="font-size: 0.72rem;">
            <i class="bi bi-plus-lg me-1"></i> Scan Baru
        </a>
    </div>

    @if($deductions->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                <thead class="bg-light text-muted" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-2" style="width: 140px;">WAKTU & PETUGAS</th>
                        <th class="py-2">PRODUK & TITIPAN</th>
                        <th class="text-center py-2" style="width: 150px;">ERP vs FISIK</th>
                        <th class="text-center py-2" style="width: 130px;">SELISIH</th>
                        <th class="py-2">KATEGORI & CATATAN</th>
                        <th class="text-center pe-3 py-2" style="width: 80px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deductions as $ded)
                        @php
                            $cItem   = $ded->consignmentItem;
                            $cons    = $cItem ? $cItem->consignment : null;
                            $sup     = $cons ? $cons->supplier : null;
                            $prod    = $cItem ? $cItem->masterProduct : null;
                            $order   = $ded->order;
                            $dateObj = $ded->deduction_date ?: $ded->created_at;
                            $sysStock = $ded->system_stock;
                            $actStock = $ded->actual_stock;
                            $diffQty  = $ded->difference_qty !== null ? $ded->difference_qty : -$ded->quantity;
                        @endphp
                        <tr>
                            {{-- 1. WAKTU & OPERATOR --}}
                            <td class="ps-3 py-2">
                                <div class="fw-semibold text-dark">{{ $dateObj ? $dateObj->format('d/m/Y') : '-' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{{ $dateObj ? $dateObj->format('H:i') . ' WIB' : '' }}</div>
                                <div class="text-muted text-truncate mt-0.5" style="font-size: 0.68rem; max-width: 130px;" title="Petugas: {{ $ded->user ? $ded->user->name : 'Sistem' }}">
                                    <i class="bi bi-person me-0.5 opacity-50"></i>{{ $ded->user ? $ded->user->name : 'Sistem' }}
                                </div>
                            </td>

                            {{-- 2. PRODUK, SKU & SUPPLIER/BATCH --}}
                            <td class="py-2">
                                <div class="fw-bold text-dark lh-sm text-truncate" style="max-width: 320px;" title="{{ $prod ? $prod->name : 'Produk' }}">
                                    {{ $prod ? $prod->name : 'Produk' }}
                                </div>
                                <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                    <span class="badge bg-light text-secondary border font-monospace px-1 py-0.5" style="font-size: 0.65rem;">
                                        {{ $prod ? $prod->sku : '-' }}
                                    </span>
                                    @if($cons)
                                        <span class="text-muted small">•</span>
                                        <a href="{{ route('v2.titipan_barang.show', $cons->id) }}" class="text-decoration-none small text-primary d-inline-flex align-items-center gap-0.5 font-monospace" style="font-size: 0.72rem;">
                                            <i class="bi bi-box-seam me-0.5"></i>{{ $cons->reference_number }}
                                        </a>
                                        <span class="text-secondary small fw-semibold" style="font-size: 0.72rem;">
                                            ({{ $sup ? $sup->name : '-' }})
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 3. STOK ERP vs FISIK GUDANG --}}
                            <td class="text-center py-2">
                                @if($sysStock !== null && $actStock !== null)
                                    <div class="d-flex align-items-center justify-content-center gap-1 font-monospace">
                                        <span class="badge bg-light text-primary border" title="Stok ERP">
                                            ERP: {{ $sysStock }}
                                        </span>
                                        <i class="bi bi-arrow-right text-muted" style="font-size:0.65rem;"></i>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" title="Stok Fisik Gudang">
                                            Fisik: {{ $actStock }}
                                        </span>
                                    </div>
                                @else
                                    <div class="text-danger fw-bold font-monospace">
                                        -{{ number_format($ded->quantity) }} PCS
                                    </div>
                                    <span class="text-muted" style="font-size:0.68rem;">Penyesuaian Langsung</span>
                                @endif
                            </td>

                            {{-- 4. SELISIH BADGE --}}
                            <td class="text-center py-2">
                                @if($diffQty === 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-circle me-1"></i>Sesuai (0)
                                    </span>
                                @elseif($diffQty < 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-dash-circle me-1"></i>{{ $diffQty }} PCS
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-plus-circle me-1"></i>+{{ $diffQty }} PCS
                                    </span>
                                @endif
                            </td>

                            {{-- 5. KATEGORI & CATATAN --}}
                            <td class="py-2">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <span class="badge {{ $ded->reason_badge_class }} px-1.5 py-0.5" style="font-size: 0.68rem;">
                                        {{ $ded->reason_label }}
                                    </span>
                                    @if($ded->deduct_master_stock)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0.5 fw-bold" style="font-size: 0.65rem;" title="Sinkron juga ke stok master gudang">
                                            <i class="bi bi-building-check me-0.5"></i>Gudang Master
                                        </span>
                                    @endif
                                </div>

                                @if($ded->notes)
                                    <div class="text-muted small text-truncate mt-1" style="max-width: 280px; font-size: 0.7rem;" title="{{ $ded->notes }}">
                                        {{ $ded->notes }}
                                    </div>
                                @endif
                            </td>

                            {{-- 6. AKSI --}}
                            <td class="text-center pe-3 py-2">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('v2.titipan_barang.deductions.show', $ded->id) }}" class="btn btn-sm btn-info text-white py-1 px-2 shadow-sm" title="Detail Opname" style="border-radius:6px; font-size:0.75rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('v2.titipan_barang.deductions.destroy', $ded->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan hasil opname ini? Penyesuaian stok akan dikembalikan ke posisi sebelumnya.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger text-white py-1 px-2 shadow-sm" title="Batalkan / Hapus" style="border-radius:6px; font-size:0.75rem;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($deductions->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted" style="font-size: 0.72rem;">
                        Menampilkan {{ $deductions->firstItem() }} - {{ $deductions->lastItem() }} dari total {{ $deductions->total() }} data
                    </small>
                    {{ $deductions->links() }}
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-5">
            <div class="mb-2">
                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-clipboard-check fs-4"></i>
                </div>
            </div>
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Belum Ada Riwayat Stok Opname Titipan</h6>
            <p class="text-muted small mb-2.5" style="font-size: 0.75rem;">Mulai scan barcode fisik produk di gudang untuk mencocokkan stok fisik dengan ERP.</p>
            <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm text-white px-3 py-1.5 fw-semibold" style="background:#2563eb; border:none; border-radius:6px; font-size: 0.78rem;">
                <i class="bi bi-qr-code-scan me-1"></i> Mulai Scan Stok Opname
            </a>
        </div>
    @endif
</div>

@endsection
