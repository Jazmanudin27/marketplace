@extends('layouts.app')

@section('title', 'Detail Transaksi — ' . $offlineSale->sale_number)
@section('page-title', 'Detail Penjualan Offline')

@section('content')
<div class="row">
    <div class="col-md-12">

        {{-- HEADER --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success rounded border border-success border-opacity-10 d-flex align-items-center justify-content-center"
                    style="width:48px;height:48px;font-size:1.25rem;">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <h4 class="mb-0 text-white fw-bold">Detail Transaksi: {{ $offlineSale->sale_number }}</h4>
                    <p class="text-muted mb-0 small">Detail penjualan offline & status pembayaran</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('offline_sales.index') }}" class="btn btn-secondary btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
                <a href="{{ route('offline_sales.print', $offlineSale->id) }}" target="_blank"
                    class="btn btn-primary btn-sm px-3 text-white">
                    <i class="fas fa-print me-1"></i> Cetak Struk
                </a>
                @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_COMPLETED)
                    <form action="{{ route('offline_sales.cancel', $offlineSale->id) }}" method="POST" class="m-0"
                        onsubmit="return confirm('Yakin ingin membatalkan transaksi ini? Stok produk akan dikembalikan.')">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm px-3">
                            <i class="fas fa-times-circle me-1"></i> Batalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="row g-3">
            {{-- LEFT: Item detail --}}
            <div class="col-lg-8">
                <div class="dashboard-card mb-3">
                    <div class="card-header-line d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-info-circle me-2"></i>Informasi Transaksi
                        </h5>
                        <span class="badge bg-{{ $offlineSale->status_badge }} bg-opacity-10 text-{{ $offlineSale->status_badge }} border border-{{ $offlineSale->status_badge }} border-opacity-10 small text-uppercase">
                            {{ $offlineSale->status_label }}
                        </span>
                    </div>

                    {{-- info row --}}
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">Pembeli</small>
                                <span class="fw-bold text-white small">
                                    @if ($offlineSale->customer_id)
                                        <a href="{{ route('customers.show', $offlineSale->customer_id) }}" class="text-decoration-none text-primary fw-bold">
                                            {{ $offlineSale->buyer_name ?: '(Umum)' }} <i class="fas fa-external-link-alt ms-1 small"></i>
                                        </a>
                                    @else
                                        {{ $offlineSale->buyer_name ?: '(Umum)' }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">No. HP Pembeli</small>
                                <span class="font-monospace fw-semibold text-white small">{{ $offlineSale->buyer_phone ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">Kasir</small>
                                <span class="fw-bold text-white small">{{ $offlineSale->user->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">Metode Pembayaran</small>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 small fw-medium mt-1">
                                    {{ $offlineSale->payment_method_label }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">Waktu Transaksi</small>
                                <span class="fw-semibold text-white small">{{ $offlineSale->sold_at?->format('d M Y, H:i') ?? '-' }}</span>
                            </div>
                        </div>
                        @if($offlineSale->customer && $offlineSale->customer->address)
                            <div class="col-md-12">
                                <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">Alamat Pembeli</small>
                                    <span class="text-white-50 text-wrap small">{{ $offlineSale->customer->address }}</span>
                                </div>
                            </div>
                        @endif
                        @if($offlineSale->notes)
                            <div class="col-md-12">
                                <div class="p-3 border border-secondary border-opacity-10 rounded h-100 bg-black bg-opacity-10">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1 small" style="font-size: 0.65rem;">Catatan</small>
                                    <span class="text-white-50 text-wrap small">{{ $offlineSale->notes }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Table Item --}}
                    <div class="card-header-line d-flex align-items-center mb-3">
                        <h5 class="mb-0 text-secondary" style="font-size:0.9rem;"><i class="fas fa-box me-2"></i>Item Yang Dijual</h5>
                    </div>
                    <div class="table-responsive rounded border border-secondary border-opacity-10">
                        <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">PRODUK</th>
                                    <th>SKU</th>
                                    <th class="text-center">QTY</th>
                                    <th class="text-end">HARGA SATUAN</th>
                                    <th class="text-end">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($offlineSale->items as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <strong class="text-white small">{{ $item->product_name }}</strong>
                                        </td>
                                        <td><code class="text-info font-monospace small">{{ $item->sku ?? '-' }}</code></td>
                                        <td class="text-center small">{{ $item->quantity }}</td>
                                        <td class="text-end font-monospace small">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace text-primary fw-semibold small">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Ringkasan --}}
            <div class="col-lg-4">
                <div class="dashboard-card mb-3">
                    <div class="card-header-line mb-3">
                        <h5 class="mb-0 text-white"><i class="fas fa-wallet me-2 text-success"></i>Ringkasan Pembayaran</h5>
                    </div>
                    <div class="p-3 border border-secondary border-opacity-10 rounded bg-black bg-opacity-10 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Subtotal</span>
                            <span class="font-monospace text-white small">Rp {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Diskon</span>
                            <span class="font-monospace text-danger small">- Rp {{ number_format($offlineSale->discount_amount, 0, ',', '.') }}</span>
                        </div>
                        <hr style="border-color:rgba(255,255,255,0.08); margin:0.75rem 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-white fw-bold small">Grand Total</span>
                            <span class="font-monospace text-success fw-bold fs-5">Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="p-3 border border-secondary border-opacity-10 rounded bg-black bg-opacity-10">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Dibayar</span>
                            <span class="font-monospace text-white small">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Kembalian</span>
                            <span class="font-monospace text-primary fw-bold small">Rp {{ number_format($offlineSale->change_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
