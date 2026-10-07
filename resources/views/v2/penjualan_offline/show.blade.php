@extends('v2.layouts.app')

@section('title', 'Detail Penjualan Offline #' . $sale->sale_number)

@section('content')

<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-receipt text-primary fs-5"></i> Detail Nota Penjualan #{{ $sale->sale_number }}
        </h1>
        <p class="text-muted small mb-0">Rincian produk, transaksi kasir, dan pembayaran nota penjualan offline</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ url('/offline-sales/' . $sale->id . '/print-receipt') }}" target="_blank" class="btn btn-sm btn-dark px-3">
            <i class="bi bi-printer me-1"></i> Cetak Struk Nota
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Column: Details & Items -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold mb-0">Rincian Item Produk</h6>
                <span class="badge bg-light text-dark border">
                    {{ \Carbon\Carbon::parse($sale->sold_at)->format('d M Y H:i') }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size: 0.83rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Produk / SKU</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">HPP Modal</th>
                            <th class="text-end pe-3">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $item->masterProduct->name ?? $item->item_name ?? 'Produk POS' }}</div>
                                    <small class="text-muted font-monospace">SKU: {{ $item->masterProduct->sku ?? $item->sku ?? '-' }}</small>
                                </td>
                                <td class="text-center fw-bold">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 font-monospace fw-bold text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Subtotal Produk:</td>
                            <td class="text-end pe-3 font-monospace fw-bold">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                        </tr>
                        @if((float)$sale->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="text-end text-danger fw-semibold">Diskon Penjualan:</td>
                                <td class="text-end pe-3 font-monospace text-danger fw-bold">- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if((float)$sale->shipping_fee > 0)
                            <tr>
                                <td colspan="4" class="text-end text-muted fw-semibold">Biaya Pengiriman:</td>
                                <td class="text-end pe-3 font-monospace fw-bold">+ Rp {{ number_format($sale->shipping_fee, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="table-active">
                            <td colspan="4" class="text-end fw-bold fs-6">GRAND TOTAL:</td>
                            <td class="text-end pe-3 font-monospace fw-bold fs-6 text-primary">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payments History -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0">Riwayat Pembayaran Kasir</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size: 0.83rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tanggal Bayar</th>
                            <th>Metode</th>
                            <th>Tujuan Kas / Bank</th>
                            <th>Keterangan</th>
                            <th class="text-end pe-3">Jumlah Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sale->payments as $pmt)
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($pmt->payment_date ?: $pmt->created_at)->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ strtoupper($pmt->payment_method) }}</span></td>
                                <td>{{ ucwords(str_replace('_', ' ', $pmt->payment_destination ?: 'kas_besar')) }}</td>
                                <td class="text-muted">{{ $pmt->notes ?: '-' }}</td>
                                <td class="text-end pe-3 font-monospace fw-bold text-success">Rp {{ number_format($pmt->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted fst-italic">Belum ada riwayat pembayaran tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Buyer Info & Summary -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0">Informasi Pelanggan</h6>
            </div>
            <div class="card-body p-3" style="font-size: 0.85rem;">
                <div class="mb-2">
                    <small class="text-muted d-block">Nama Pembeli:</small>
                    <span class="fw-bold text-dark fs-6">{{ $sale->buyer_name ?: 'Pelanggan Walk-in (Umum)' }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">No. Telepon / WA:</small>
                    <span>{{ $sale->buyer_phone ?: '-' }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Kasir / Operator:</small>
                    <span>{{ $sale->user->name ?? 'Admin POS' }}</span>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block">Catatan Nota:</small>
                    <span class="fst-italic text-secondary">{{ $sale->notes ?: '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0">Status Pembayaran</h6>
            </div>
            <div class="card-body p-3" style="font-size: 0.85rem;">
                @php
                    $sisa = max(0, (float)$sale->grand_total - (float)$sale->paid_amount);
                    $isLunas = (float)$sale->paid_amount >= (float)$sale->grand_total;
                @endphp
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Tagihan:</span>
                    <span class="fw-bold font-monospace">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Terbayar:</span>
                    <span class="fw-bold font-monospace text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 mb-3">
                    <span class="text-muted">Sisa Piutang:</span>
                    <span class="fw-bold font-monospace {{ $sisa > 0 ? 'text-danger fs-6' : 'text-muted' }}">
                        {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : 'LUNAS (Rp 0)' }}
                    </span>
                </div>
                <div class="text-center p-2 rounded-3 {{ $isLunas ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-danger bg-opacity-10 text-danger border border-danger' }}">
                    <i class="bi bi-{{ $isLunas ? 'check-circle-fill' : 'exclamation-triangle-fill' }} me-1"></i>
                    <strong>{{ $isLunas ? 'PEMBAYARAN LUNAS' : 'MASIH ADA SISA PIUTANG' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
