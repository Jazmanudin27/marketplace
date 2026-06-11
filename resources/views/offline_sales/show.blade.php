@extends('layouts.app')

@section('title', 'Detail Transaksi — ' . $offlineSale->sale_number)
@section('page-title', 'Detail Penjualan Offline')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb" style="background:transparent;padding:0;">
            <li class="breadcrumb-item"><a href="{{ route('offline_sales.index') }}" class="text-decoration-none">Penjualan Offline</a></li>
            <li class="breadcrumb-item active">{{ $offlineSale->sale_number }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        {{-- LEFT: Item detail --}}
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-line">
                    <h3><i class="fas fa-receipt"></i> {{ $offlineSale->sale_number }}</h3>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge badge-{{ $offlineSale->status_badge }}">{{ $offlineSale->status_label }}</span>
                        <a href="{{ route('offline_sales.print', $offlineSale->id) }}" target="_blank"
                            class="btn btn-sm btn-info text-white">
                            <i class="fas fa-print me-1"></i> Cetak Struk
                        </a>
                        @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_COMPLETED)
                            <form action="{{ route('offline_sales.cancel', $offlineSale->id) }}" method="POST" class="m-0"
                                onsubmit="return confirm('Yakin ingin membatalkan transaksi ini? Stok produk akan dikembalikan.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times me-1"></i> Batalkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <h4 style="font-size:.85rem; font-weight:700; margin:1rem 0 .75rem; color:var(--text-secondary);">
                    <i class="fas fa-box me-1"></i> Item yang Dijual
                </h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>SKU</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($offlineSale->items as $item)
                            <tr>
                                <td class="fw-bold">{{ $item->product_name }}</td>
                                <td class="mono text-muted">{{ $item->sku ?? '-' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="mono text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="mono fw-bold text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RIGHT: Ringkasan --}}
        <div class="col-lg-4">
            <div class="dashboard-card">
                <h3 style="font-size:.88rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-wallet"></i> Ringkasan Pembayaran
                </h3>
                <div class="detail-row">
                    <span class="detail-label">Subtotal</span>
                    <span class="detail-value mono">Rp {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Diskon</span>
                    <span class="detail-value mono text-danger">- Rp {{ number_format($offlineSale->discount_amount, 0, ',', '.') }}</span>
                </div>
                <hr style="border-color:var(--border);margin:.75rem 0;">
                <div class="detail-row">
                    <span class="detail-label fw-bold">Grand Total</span>
                    <span class="detail-value mono" style="font-size:1.1rem;font-weight:800;color:var(--success);">
                        Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dibayar</span>
                    <span class="detail-value mono">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Kembalian</span>
                    <span class="detail-value mono text-primary fw-bold">Rp {{ number_format($offlineSale->change_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="dashboard-card" style="margin-top:1rem;">
                <h3 style="font-size:.88rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-info-circle"></i> Info Transaksi
                </h3>
                <div class="detail-row">
                    <span class="detail-label">Pembeli</span>
                    <span class="detail-value">{{ $offlineSale->buyer_name ?: '(Umum)' }}</span>
                </div>
                @if($offlineSale->buyer_phone)
                    <div class="detail-row">
                        <span class="detail-label">No. HP</span>
                        <span class="detail-value">{{ $offlineSale->buyer_phone }}</span>
                    </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Kasir</span>
                    <span class="detail-value">{{ $offlineSale->user->name ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pembayaran</span>
                    <span class="detail-value"><span class="badge bg-secondary">{{ $offlineSale->payment_method_label }}</span></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu</span>
                    <span class="detail-value">{{ $offlineSale->sold_at?->format('d M Y, H:i') ?? '-' }}</span>
                </div>
                @if($offlineSale->notes)
                    <div class="detail-row">
                        <span class="detail-label">Catatan</span>
                        <span class="detail-value">{{ $offlineSale->notes }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
