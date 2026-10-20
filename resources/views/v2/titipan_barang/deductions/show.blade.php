@extends('v2.layouts.app')

@section('title', 'Detail Pengeluaran Stok Titipan #' . $deduction->id)

@section('content')

@php
    $cItem   = $deduction->consignmentItem;
    $cons    = $cItem ? $cItem->consignment : null;
    $sup     = $cons ? $cons->supplier : null;
    $prod    = $cItem ? $cItem->masterProduct : null;
    $order   = $deduction->order;
    $dateObj = $deduction->deduction_date ?: $deduction->created_at;
    $totalHpp = $cItem ? ($deduction->quantity * $cItem->unit_cost_price) : 0;
@endphp

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-receipt-cutoff text-danger fs-5"></i> Detail Pengeluaran Titipan #{{ $deduction->id }}
        </h1>
        <p class="text-muted small mb-0">
            Pencatatan pemotongan stok titipan pada {{ $dateObj ? $dateObj->format('d/m/Y H:i') : '-' }} WIB
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.titipan_barang.deductions.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
        </a>
        <a href="{{ route('v2.titipan_barang.deductions.edit', $deduction->id) }}" class="btn btn-sm btn-warning text-dark py-1.5 px-3 fw-semibold">
            <i class="bi bi-pencil me-1"></i> Edit Pengeluaran
        </a>
        <form action="{{ route('v2.titipan_barang.deductions.destroy', $deduction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengeluaran ini? Stok titipan akan dikembalikan sebanyak {{ $deduction->quantity }} pcs.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger py-1.5 px-3 fw-semibold">
                <i class="bi bi-trash me-1"></i> Batalkan / Hapus
            </button>
        </form>
    </div>
</div>

{{-- ── Quick KPI Metric Banner ── --}}
<div class="row g-2.5 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-danger border-3">
            <span class="text-muted text-uppercase fw-semibold small" style="font-size:0.68rem;">JUMLAH POTONG STOK</span>
            <div class="fw-bold text-danger font-monospace fs-4 mt-1">
                -{{ number_format($deduction->quantity) }} PCS
            </div>
            <small class="text-muted" style="font-size:0.7rem;">Stok titipan terpotong</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-primary border-3">
            <span class="text-muted text-uppercase fw-semibold small" style="font-size:0.68rem;">NILAI HPP MODAL</span>
            <div class="fw-bold text-primary font-monospace fs-5 mt-1">
                Rp {{ number_format($totalHpp, 0, ',', '.') }}
            </div>
            <small class="text-muted" style="font-size:0.7rem;">@ Rp {{ number_format($cItem ? $cItem->unit_cost_price : 0, 0, ',', '.') }} / pcs</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-warning border-3">
            <span class="text-muted text-uppercase fw-semibold small" style="font-size:0.68rem;">ALASAN PENGELUARAN</span>
            <div class="mt-1.5">
                <span class="badge {{ $deduction->reason_badge_class }} px-2.5 py-1 fw-bold fs-6">
                    {{ $deduction->reason_label }}
                </span>
            </div>
            <small class="text-muted mt-1 d-block" style="font-size:0.7rem;">Kategori transaksi</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-info border-3">
            <span class="text-muted text-uppercase fw-semibold small" style="font-size:0.68rem;">POTONG MASTER GUDANG</span>
            <div class="mt-1.5">
                @if($deduction->deduct_master_stock)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i>Ya, Stok Master Terpotong
                    </span>
                @else
                    <span class="badge bg-light text-muted border px-2 py-1 fw-semibold">
                        <i class="bi bi-dash-circle me-1"></i>Tidak Potong Master
                    </span>
                @endif
            </div>
            <small class="text-muted mt-1 d-block" style="font-size:0.7rem;">Status sinkronisasi stok master</small>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- ── Detail Pengeluaran & Order Terkait ── --}}
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3">
                <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.04em;">
                    <i class="bi bi-info-circle text-primary me-1"></i> Rincian Pengeluaran
                </span>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0" style="font-size: 0.84rem;">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width: 170px;">ID Pengeluaran</td>
                            <td class="fw-bold text-dark font-monospace">#{{ $deduction->id }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Waktu Pencatatan</td>
                            <td class="text-dark fw-semibold">
                                {{ $dateObj ? $dateObj->format('d F Y, H:i:s') : '-' }} WIB
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alasan Pengeluaran</td>
                            <td>
                                <span class="badge {{ $deduction->reason_badge_class }} px-2 py-0.5">
                                    {{ $deduction->reason_label }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. Referensi Dokumen</td>
                            <td class="font-monospace text-dark fw-semibold">
                                {{ $deduction->reference_no ?: '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Barcode Terpindai</td>
                            <td class="font-monospace text-muted small">
                                <code>{{ $deduction->scanned_barcode ?: 'MANUAL-INPUT' }}</code>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Petugas Pencatat</td>
                            <td class="text-dark">
                                <i class="bi bi-person-circle me-1 text-secondary"></i>
                                {{ $deduction->user ? $deduction->user->name : 'Sistem' }}
                                <small class="text-muted">({{ $deduction->user ? $deduction->user->email : '' }})</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Catatan / Keterangan</td>
                            <td class="text-dark fst-italic">
                                {{ $deduction->notes ?: 'Tidak ada catatan tambahan.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pesanan Marketplace Terkait --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.04em;">
                    <i class="bi bi-cart-check text-success me-1"></i> Pesanan Marketplace Terkait
                </span>
                @if($order)
                    <a href="{{ route('orders.show', $order->id) }}" class="btn btn-xs btn-outline-primary py-0.5 px-2" style="font-size:0.7rem;" target="_blank">
                        Buka Halaman Pesanan <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                @endif
            </div>
            <div class="card-body p-3">
                @if($order)
                    <div class="p-2.5 rounded-2 bg-light border mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="fw-bold text-dark font-monospace fs-6">{{ $order->invoice_number ?: $order->order_marketplace_id }}</span>
                                <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                    @if($order->store)
                                        <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5" style="font-size:0.68rem;">
                                            {{ $order->store->store_name }}
                                        </span>
                                    @endif
                                    @if($order->tracking_number)
                                        <span class="badge bg-light text-muted border font-monospace px-1.5 py-0.5" style="font-size:0.68rem;">
                                            Resi: {{ $order->tracking_number }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                                Status: {{ $order->order_status }}
                            </span>
                        </div>
                        <div class="mt-2 text-muted small" style="font-size:0.75rem;">
                            Pembeli: <strong class="text-dark">{{ $order->buyer_name ?: '-' }}</strong> 
                            ({{ $order->buyer_phone ?: 'No Phone' }})
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark small mb-2">Item dalam Pesanan Ini:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.78rem;">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th>PRODUK</th>
                                    <th>SKU</th>
                                    <th class="text-center">QTY ORDER</th>
                                    <th>SUMBER PEMENUHAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $oItem)
                                    @php
                                        $isMatched = $oItem->id === $deduction->order_item_id || ($prod && $oItem->master_product_id === $prod->id);
                                    @endphp
                                    <tr class="{{ $isMatched ? 'table-warning' : '' }}">
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $oItem->masterProduct ? $oItem->masterProduct->name : $oItem->product_name }}</div>
                                            @if($isMatched)
                                                <span class="badge bg-danger text-white py-0.5 px-1.5" style="font-size:0.65rem;">
                                                    Item Terpotong Konsinyasi
                                                </span>
                                            @endif
                                        </td>
                                        <td class="font-monospace text-muted">{{ $oItem->sku ?: '-' }}</td>
                                        <td class="text-center fw-bold">{{ $oItem->quantity }} pcs</td>
                                        <td>
                                            @if($oItem->fulfillment_source === 'consignment')
                                                <span class="badge bg-info text-white">Konsinyasi</span>
                                            @elseif($oItem->fulfillment_source === 'spk')
                                                <span class="badge bg-warning text-dark">SPK Produksi</span>
                                            @else
                                                <span class="badge bg-secondary">Gudang Biasa</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-dash-circle fs-3 d-block mb-1 text-secondary"></i>
                        <span class="small">Pengeluaran ini tidak dihubungkan ke pesanan marketplace tertentu (pengeluaran manual / langsung).</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Detail Batch Konsinyasi & Produk ── --}}
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.04em;">
                    <i class="bi bi-box-seam text-warning me-1"></i> Batch Konsinyasi Asal
                </span>
                @if($cons)
                    <a href="{{ route('v2.titipan_barang.show', $cons->id) }}" class="btn btn-xs btn-outline-secondary py-0.5 px-2" style="font-size:0.7rem;">
                        Lihat Batch <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
            <div class="card-body p-3">
                @if($cons)
                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.82rem;">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 140px;">No. Referensi</td>
                                <td class="fw-bold font-monospace text-primary">{{ $cons->reference_number }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Supplier</td>
                                <td class="fw-semibold text-dark">{{ $sup ? $sup->name : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal Masuk</td>
                                <td>{{ $cons->consignment_date ? $cons->consignment_date->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status Batch</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        {{ strtoupper($cons->status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                @else
                    <div class="text-muted small">Data batch titipan tidak ditemukan.</div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3">
                <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.04em;">
                    <i class="bi bi-tag text-info me-1"></i> Produk Master
                </span>
            </div>
            <div class="card-body p-3">
                @if($prod)
                    <h6 class="fw-bold text-dark mb-1.5 lh-sm">{{ $prod->name }}</h6>
                    <div class="d-flex align-items-center gap-1.5 mb-3 flex-wrap">
                        <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-0.5" style="font-size:0.72rem;">
                            SKU: {{ $prod->sku }}
                        </span>
                        <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size:0.7rem;">
                            Satuan: {{ $prod->unit ?: 'PCS' }}
                        </span>
                    </div>

                    <div class="p-2.5 rounded-2 bg-light border mb-2">
                        <div class="row g-2 text-center" style="font-size: 0.78rem;">
                            <div class="col-4">
                                <span class="text-muted d-block" style="font-size:0.68rem;">TOTAL TITIP</span>
                                <span class="fw-bold text-primary">{{ number_format($cItem ? $cItem->qty_received : 0) }} PCS</span>
                            </div>
                            <div class="col-4 border-start border-end">
                                <span class="text-muted d-block" style="font-size:0.68rem;">TOTAL TERJUAL</span>
                                <span class="fw-bold text-danger">{{ number_format($cItem ? (int)$cItem->qty_sold : 0) }} PCS</span>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block" style="font-size:0.68rem;">SISA STOK</span>
                                <span class="fw-bold text-success">{{ number_format($cItem ? $cItem->sisa_stok : 0) }} PCS</span>
                            </div>
                        </div>
                    </div>

                    <table class="table table-sm table-borderless mb-0 mt-2" style="font-size:0.8rem;">
                        <tbody>
                            <tr>
                                <td class="text-muted">Harga Beli Titip (HPP):</td>
                                <td class="text-end fw-bold font-monospace text-dark">
                                    Rp {{ number_format($cItem ? $cItem->unit_cost_price : 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Harga Jual Estimasi:</td>
                                <td class="text-end fw-bold font-monospace text-secondary">
                                    Rp {{ number_format($cItem ? $cItem->unit_selling_price : 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                @else
                    <div class="text-muted small">Data master produk tidak ditemukan.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
