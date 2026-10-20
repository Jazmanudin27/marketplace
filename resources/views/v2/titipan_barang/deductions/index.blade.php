@extends('v2.layouts.app')

@section('title', 'Riwayat Pengeluaran Stok Titipan Barang (Konsinyasi) V2')

@push('styles')
<style>
.deduction-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.deduction-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
}
.deduction-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    margin-bottom: 4px;
}
.deduction-kpi-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.deduction-kpi-sub {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 4px;
}
.deduction-kpi-icon {
    position: absolute;
    right: 14px;
    bottom: 12px;
    font-size: 2.2rem;
    opacity: 0.12;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Pengeluaran Stok Titipan Barang
        </h1>
        <p class="text-muted small mb-0">Kelola pemotongan stok titipan manual (pesanan terlewat scan, retur ke supplier, barang rusak, sample, dll)</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.titipan_barang.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 rounded-2 fw-semibold">
            <i class="bi bi-box-arrow-in-down me-1"></i> Penerimaan Titipan
        </a>
        <a href="{{ route('v2.titipan_barang.stock_card') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 rounded-2 fw-semibold">
            <i class="bi bi-card-checklist me-1"></i> Kartu Stok
        </a>
        <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#dc2626; border:none; border-radius:6px;">
            <i class="bi bi-plus-lg me-1"></i> Catat Pengeluaran Baru
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="deduction-kpi-card border-start border-danger border-3">
            <div class="deduction-kpi-title text-danger">Total Pengeluaran</div>
            <div class="deduction-kpi-value text-dark">
                {{ number_format($totalCount, 0, ',', '.') }} Transaksi
            </div>
            <div class="deduction-kpi-sub">Riwayat pemotongan terfilter</div>
            <i class="bi bi-receipt deduction-kpi-icon text-danger"></i>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="deduction-kpi-card border-start border-warning border-3">
            <div class="deduction-kpi-title text-warning">Total Qty Keluar</div>
            <div class="deduction-kpi-value text-warning-emphasis">
                {{ number_format($totalQty, 0, ',', '.') }} PCS
            </div>
            <div class="deduction-kpi-sub">Total unit barang yang dikeluarkan</div>
            <i class="bi bi-boxes deduction-kpi-icon text-warning"></i>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="deduction-kpi-card border-start border-primary border-3">
            <div class="deduction-kpi-title text-primary">Nilai HPP Modal</div>
            <div class="deduction-kpi-value text-primary font-monospace">
                Rp {{ number_format($totalHppAmount, 0, ',', '.') }}
            </div>
            <div class="deduction-kpi-sub">Estimasi modal titipan keluar</div>
            <i class="bi bi-cash-stack deduction-kpi-icon text-primary"></i>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="deduction-kpi-card border-start border-info border-3">
            <div class="deduction-kpi-title text-info">Kategori Pengeluaran</div>
            <div class="deduction-kpi-value text-dark" style="font-size: 1rem;">
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2">
                    <i class="bi bi-qr-code-scan me-1"></i>{{ $totalUnscannedOrders }} Terlewat Scan
                </span>
            </div>
            <div class="deduction-kpi-sub mt-1.5">{{ $totalManualCorrections }} Koreksi / Retur / Kerusakan</div>
            <i class="bi bi-pie-chart deduction-kpi-icon text-info"></i>
        </div>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('v2.titipan_barang.deductions.index') }}">
            <div class="row g-2.5 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Cari Pengeluaran</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Invoice / Resi / Produk / Ref..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm select2">
                        <option value="">-- Semua Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Alasan Pengeluaran</label>
                    <select name="reason" class="form-select form-select-sm">
                        <option value="">-- Semua Alasan --</option>
                        <option value="unscanned_order" {{ request('reason') == 'unscanned_order' ? 'selected' : '' }}>Pesanan Terlewat Scan</option>
                        <option value="manual_adjustment" {{ request('reason') == 'manual_adjustment' ? 'selected' : '' }}>Koreksi Stok Manual</option>
                        <option value="return_supplier" {{ request('reason') == 'return_supplier' ? 'selected' : '' }}>Retur ke Supplier</option>
                        <option value="damaged" {{ request('reason') == 'damaged' ? 'selected' : '' }}>Barang Rusak / Hilang</option>
                        <option value="direct_sale" {{ request('reason') == 'direct_sale' ? 'selected' : '' }}>Penjualan Offline / Langsung</option>
                        <option value="other" {{ request('reason') == 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Tanggal Mulai</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1" style="font-size:0.72rem;">Tanggal Akhir</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold" title="Terapkan Filter"><i class="bi bi-filter"></i></button>
                    <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Main Table Card ── --}}
<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
    <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-list-check text-danger"></i>
            <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.04em;">Daftar Riwayat Pengeluaran Stok Titipan</span>
            <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size:0.7rem;">
                {{ $deductions->total() }} Data
            </span>
        </div>
    </div>

    @if($deductions->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-3 py-2.5" style="width: 140px;">WAKTU / TANGGAL</th>
                        <th class="py-2.5">PRODUK & SKU</th>
                        <th class="py-2.5">BATCH TITIPAN (SUPPLIER)</th>
                        <th class="text-center py-2.5" style="width: 100px;">QTY KELUAR</th>
                        <th class="py-2.5">ALASAN PENGELUARAN</th>
                        <th class="py-2.5">TERKAIT PESANAN</th>
                        <th class="text-center py-2.5">POTONG MASTER</th>
                        <th class="py-2.5">OPERATOR</th>
                        <th class="text-center pe-3 py-2.5" style="width: 110px;">AKSI</th>
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
                        @endphp
                        <tr>
                            <td class="ps-3 py-2.5">
                                <div class="fw-semibold text-dark">{{ $dateObj ? $dateObj->format('d/m/Y') : '-' }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ $dateObj ? $dateObj->format('H:i') . ' WIB' : '' }}</small>
                            </td>
                            <td class="py-2.5">
                                <div class="fw-bold text-dark lh-sm" style="max-width: 260px; word-break: break-word;">
                                    {{ $prod ? $prod->name : 'Produk Terhapus' }}
                                </div>
                                <div class="d-flex align-items-center gap-1.5 mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace px-1.5 py-0.5" style="font-size:0.68rem;">
                                        SKU: {{ $prod ? $prod->sku : '-' }}
                                    </span>
                                    @if($cItem && $cItem->unit_cost_price > 0)
                                        <span class="text-muted small" style="font-size:0.7rem;">
                                            HPP: Rp {{ number_format($cItem->unit_cost_price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5">
                                @if($cons)
                                    <a href="{{ route('v2.titipan_barang.show', $cons->id) }}" class="text-decoration-none fw-semibold font-monospace small text-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-box-seam"></i> {{ $cons->reference_number }}
                                    </a>
                                    <div class="text-muted small" style="font-size:0.72rem;">
                                        {{ $sup ? $sup->name : 'Supplier' }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center py-2.5">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold font-monospace fs-6">
                                    -{{ number_format($ded->quantity) }} PCS
                                </span>
                            </td>
                            <td class="py-2.5">
                                <span class="badge {{ $ded->reason_badge_class }} px-2 py-1 mb-1 d-inline-block">
                                    {{ $ded->reason_label }}
                                </span>
                                @if($ded->reference_no)
                                    <div class="text-muted small" style="font-size:0.72rem;">
                                        Ref: <span class="font-monospace fw-semibold text-dark">{{ $ded->reference_no }}</span>
                                    </div>
                                @endif
                                @if($ded->notes)
                                    <div class="text-muted fst-italic small text-truncate" style="max-width: 200px; font-size:0.7rem;" title="{{ $ded->notes }}">
                                        "{{ $ded->notes }}"
                                    </div>
                                @endif
                            </td>
                            <td class="py-2.5">
                                @if($order)
                                    <a href="{{ route('orders.show', $order->id) }}" class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-1" target="_blank">
                                        <i class="bi bi-receipt"></i> {{ $order->invoice_number ?: $order->order_marketplace_id }}
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                        @if($order->store)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size:0.65rem;">
                                                {{ $order->store->store_name }}
                                            </span>
                                        @endif
                                        @if($order->tracking_number)
                                            <span class="badge bg-light text-muted border font-monospace px-1.5 py-0.5" style="font-size:0.65rem;" title="Resi: {{ $order->tracking_number }}">
                                                <i class="bi bi-truck me-0.5"></i>{{ Str::limit($order->tracking_number, 12) }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">Tanpa Order Marketplace</span>
                                @endif
                            </td>
                            <td class="text-center py-2.5">
                                @if($ded->deduct_master_stock)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="Stok fisik gudang master ikut dikurangi saat input">
                                        <i class="bi bi-check-circle me-1"></i>Ya
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border" title="Stok master tidak dipotong tambahan (sudah dipotong scanner / sistem lain)">
                                        <i class="bi bi-dash-circle me-1"></i>Tidak
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 text-muted small" style="font-size:0.75rem;">
                                <i class="bi bi-person me-1"></i>{{ $ded->user ? $ded->user->name : 'Sistem' }}
                            </td>
                            <td class="text-center pe-3 py-2.5">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('v2.titipan_barang.deductions.show', $ded->id) }}" class="btn btn-sm btn-outline-info p-1 px-2" title="Detail Pengeluaran" style="border-radius:5px;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('v2.titipan_barang.deductions.edit', $ded->id) }}" class="btn btn-sm btn-outline-warning p-1 px-2" title="Edit Pengeluaran" style="border-radius:5px;">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('v2.titipan_barang.deductions.destroy', $ded->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan pengeluaran ini? Stok titipan akan dikembalikan sebanyak {{ $ded->quantity }} pcs.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Batalkan / Hapus" style="border-radius:5px;">
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
            <div class="card-footer bg-white border-top py-2.5 px-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Menampilkan {{ $deductions->firstItem() }} - {{ $deductions->lastItem() }} dari total {{ $deductions->total() }} catatan pengeluaran
                    </small>
                    {{ $deductions->links() }}
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-5">
            <div class="mb-3">
                <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-box-arrow-up-right fs-2"></i>
                </div>
            </div>
            <h6 class="fw-bold text-dark mb-1">Belum Ada Riwayat Pengeluaran Stok Titipan</h6>
            <p class="text-muted small mb-3">Semua pemotongan stok titipan (scan kemas terlewat, retur supplier, atau koreksi) akan tercatat di sini.</p>
            <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm text-white px-3 py-1.5 fw-semibold" style="background:#dc2626; border:none; border-radius:6px;">
                <i class="bi bi-plus-lg me-1"></i> Catat Pengeluaran Sekarang
            </a>
        </div>
    @endif
</div>

@endsection
