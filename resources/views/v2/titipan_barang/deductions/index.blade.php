@extends('v2.layouts.app')

@section('title', 'Riwayat Pengeluaran Stok Titipan Barang (Konsinyasi) V2')

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
            <i class="bi bi-box-arrow-up-right text-danger"></i> Pengeluaran Stok Titipan Barang
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Pemotongan stok titipan manual (terlewat scan, retur, rusak, koreksi)</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.titipan_barang.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-2" style="font-size: 0.78rem;">
            <i class="bi bi-box-arrow-in-down me-1"></i> Penerimaan
        </a>
        <a href="{{ route('v2.titipan_barang.stock_card') }}" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-2" style="font-size: 0.78rem;">
            <i class="bi bi-card-checklist me-1"></i> Kartu Stok
        </a>
        <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm py-1 px-3 fw-semibold text-white shadow-sm" style="background:#dc2626; border:none; border-radius:6px; font-size: 0.8rem;">
            <i class="bi bi-plus-lg me-1"></i> Catat Baru
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

{{-- ── Compact KPI Bar with Sumber Stok Breakdown ── --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-danger border-3">
            <div class="compact-kpi-label text-danger">Total Pengeluaran</div>
            <div class="compact-kpi-val text-dark">{{ number_format($totalCount, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">Trx</span></div>
            <div class="compact-kpi-sub">Total: {{ number_format($totalQty, 0, ',', '.') }} PCS keluar</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-warning border-3" style="background: #fffbeb;">
            <div class="compact-kpi-label text-warning-emphasis">
                <i class="bi bi-person-badge text-warning me-1"></i>Titipan Supplier
            </div>
            <div class="compact-kpi-val text-warning-emphasis">{{ number_format($qtyHanyaTitipan, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">PCS</span></div>
            <div class="compact-kpi-sub">
                @if($rekapPerSupplier->isNotEmpty())
                    Top: {{ Str::limit($rekapPerSupplier->keys()->first(), 16) }} ({{ $rekapPerSupplier->first()['qty'] }} pcs)
                @else
                    Hanya stok titipan
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-success border-3" style="background: #f0fdf4;">
            <div class="compact-kpi-label text-success">
                <i class="bi bi-building-check text-success me-1"></i>Potong Gudang Master
            </div>
            <div class="compact-kpi-val text-success">{{ number_format($qtyPotongGudang, 0, ',', '.') }} <span class="small fw-normal text-muted" style="font-size:0.75rem;">PCS</span></div>
            <div class="compact-kpi-sub">Stok gudang ikut berkurang</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="compact-kpi-box border-start border-primary border-3">
            <div class="compact-kpi-label text-primary">Nilai HPP Modal</div>
            <div class="compact-kpi-val text-primary font-monospace" style="font-size: 1.05rem;">Rp {{ number_format($totalHppAmount, 0, ',', '.') }}</div>
            <div class="compact-kpi-sub">{{ $totalUnscannedOrders }} Terlewat, {{ $totalManualCorrections }} Koreksi</div>
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
                        <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Cari Resi / Inv / Produk / Ref..." value="{{ request('search') }}">
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
                    <select name="stock_source" class="form-select form-select-sm fw-semibold">
                        <option value="">Semua Sumber Stok</option>
                        <option value="supplier" {{ request('stock_source') === 'supplier' ? 'selected' : '' }}>🏷️ Dari Supplier Saja</option>
                        <option value="gudang" {{ request('stock_source') === 'gudang' ? 'selected' : '' }}>🏭 Potong Gudang Master</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="reason" class="form-select form-select-sm">
                        <option value="">Semua Alasan</option>
                        <option value="unscanned_order" {{ request('reason') == 'unscanned_order' ? 'selected' : '' }}>Pesanan Terlewat Scan</option>
                        <option value="manual_adjustment" {{ request('reason') == 'manual_adjustment' ? 'selected' : '' }}>Koreksi Stok Manual</option>
                        <option value="return_supplier" {{ request('reason') == 'return_supplier' ? 'selected' : '' }}>Retur ke Supplier</option>
                        <option value="damaged" {{ request('reason') == 'damaged' ? 'selected' : '' }}>Barang Rusak / Hilang</option>
                        <option value="direct_sale" {{ request('reason') == 'direct_sale' ? 'selected' : '' }}>Penjualan Offline / Langsung</option>
                        <option value="other" {{ request('reason') == 'other' ? 'selected' : '' }}>Lainnya</option>
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

{{-- ── Simplified Main Table ── --}}
<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-list-check text-danger"></i>
            <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.03em; font-size: 0.75rem;">Daftar Pengeluaran Stok Titipan</span>
            <span class="badge bg-light text-muted border px-1.5 py-0.5" style="font-size:0.68rem;">
                {{ $deductions->total() }} Data
            </span>
        </div>
    </div>

    @if($deductions->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                <thead class="bg-light text-muted" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-2" style="width: 130px;">WAKTU</th>
                        <th class="py-2">PRODUK & TITIPAN</th>
                        <th class="py-2">ALASAN & TERKAIT PESANAN</th>
                        <th class="text-center py-2" style="width: 120px;">QTY KELUAR</th>
                        <th class="text-center pe-3 py-2" style="width: 110px;">AKSI</th>
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
                            {{-- 1. WAKTU & OPERATOR --}}
                            <td class="ps-3 py-2">
                                <div class="fw-semibold text-dark">{{ $dateObj ? $dateObj->format('d/m/Y') : '-' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{{ $dateObj ? $dateObj->format('H:i') . ' WIB' : '' }}</div>
                                <div class="text-muted text-truncate mt-0.5" style="font-size: 0.68rem; max-width: 120px;" title="Operator: {{ $ded->user ? $ded->user->name : 'Sistem' }}">
                                    <i class="bi bi-person me-0.5 opacity-50"></i>{{ $ded->user ? $ded->user->name : 'Sistem' }}
                                </div>
                            </td>

                            {{-- 2. PRODUK, SKU & SUPPLIER/BATCH --}}
                            <td class="py-2">
                                <div class="fw-bold text-dark lh-sm text-truncate" style="max-width: 340px;" title="{{ $prod ? $prod->name : 'Produk Terhapus' }}">
                                    {{ $prod ? $prod->name : 'Produk Terhapus' }}
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

                            {{-- 3. ALASAN & PESANAN / REFERENSI --}}
                            <td class="py-2">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <span class="badge {{ $ded->reason_badge_class }} px-1.5 py-0.5" style="font-size: 0.68rem;">
                                        {{ $ded->reason_label }}
                                    </span>
                                    @if($ded->deduct_master_stock)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0.5 fw-bold" style="font-size: 0.65rem;" title="Stok fisik gudang master ikut berkurang">
                                            <i class="bi bi-building-check me-0.5"></i>Gudang & Titipan
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1 py-0.5 fw-bold" style="font-size: 0.65rem;" title="Hanya memotong stok titipan supplier, stok master gudang tidak berkurang">
                                            <i class="bi bi-person-badge me-0.5"></i>Titipan: {{ $sup ? $sup->name : 'Supplier' }}
                                        </span>
                                    @endif
                                </div>

                                @if($order)
                                    <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                        <a href="{{ route('orders.show', $order->id) }}" class="fw-semibold text-primary text-decoration-none small d-inline-flex align-items-center gap-1" target="_blank" style="font-size: 0.75rem;">
                                            <i class="bi bi-receipt"></i> {{ $order->invoice_number ?: $order->order_marketplace_id }}
                                        </a>
                                        @if($order->store)
                                            <span class="text-muted small" style="font-size: 0.68rem;">
                                                [{{ $order->store->store_name }}]
                                            </span>
                                        @endif
                                        @if($order->tracking_number)
                                            <span class="text-muted font-monospace small" style="font-size: 0.68rem;" title="Resi: {{ $order->tracking_number }}">
                                                <i class="bi bi-truck me-0.5"></i>{{ Str::limit($order->tracking_number, 12) }}
                                            </span>
                                        @endif
                                    </div>
                                @elseif($ded->reference_no)
                                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                        Ref: <span class="font-monospace fw-semibold text-dark">{{ $ded->reference_no }}</span>
                                    </div>
                                @endif

                                @if($ded->notes)
                                    <div class="text-muted fst-italic text-truncate mt-0.5" style="max-width: 250px; font-size: 0.68rem;" title="{{ $ded->notes }}">
                                        "{{ $ded->notes }}"
                                    </div>
                                @endif
                            </td>

                            {{-- 4. QTY KELUAR & HPP --}}
                            <td class="text-center py-2">
                                <div class="fw-bold text-danger font-monospace fs-6">
                                    -{{ number_format($ded->quantity) }} PCS
                                </div>
                                @if($cItem && $cItem->unit_cost_price > 0)
                                    <div class="text-muted" style="font-size: 0.68rem;">
                                        HPP: Rp {{ number_format($cItem->unit_cost_price, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>

                            {{-- 5. AKSI (Detail, Edit, Hapus) --}}
                            <td class="text-center pe-3 py-2">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('v2.titipan_barang.deductions.show', $ded->id) }}" class="btn btn-sm btn-info text-white py-1 px-2 shadow-sm" title="Detail Pengeluaran" style="border-radius:6px; font-size:0.75rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('v2.titipan_barang.deductions.edit', $ded->id) }}" class="btn btn-sm btn-warning text-white py-1 px-2 shadow-sm" title="Edit Pengeluaran" style="border-radius:6px; font-size:0.75rem;">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('v2.titipan_barang.deductions.destroy', $ded->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan pengeluaran ini? Stok titipan akan dikembalikan sebanyak {{ $ded->quantity }} pcs.')">
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
                <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-box-arrow-up-right fs-4"></i>
                </div>
            </div>
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Belum Ada Riwayat Pengeluaran Stok Titipan</h6>
            <p class="text-muted small mb-2.5" style="font-size: 0.75rem;">Semua pemotongan stok titipan manual akan tercatat di sini.</p>
            <a href="{{ route('v2.titipan_barang.deductions.create') }}" class="btn btn-sm text-white px-3 py-1 fw-semibold" style="background:#dc2626; border:none; border-radius:6px; font-size: 0.78rem;">
                <i class="bi bi-plus-lg me-1"></i> Catat Pengeluaran Baru
            </a>
        </div>
    @endif
</div>

@endsection
