@extends('v2.layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-receipt text-info fs-5"></i> Detail Pesanan: {{ $order->order_number }}
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Rincian pesanan saluran {{ ucfirst($order->channel_type) }} untuk pelanggan {{ $order->customer_name }}.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.dropship.edit', $order->id) }}" class="btn btn-sm btn-outline-warning text-dark py-1.5 px-3 fw-semibold">
            <i class="bi bi-pencil-square me-1"></i> Edit Pesanan
        </a>
        <a href="{{ route('v2.dropship.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Info Card --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100" style="border: 1px solid #eef2f6 !important;">
            <h6 class="fw-bold text-dark border-bottom pb-2.5 mb-3" style="font-size:0.9rem;">
                <i class="bi bi-info-circle text-primary me-1"></i> Informasi Pesanan
            </h6>

            <table class="table table-sm table-borderless mb-0" style="font-size:0.83rem;">
                <tr>
                    <td class="text-muted py-1.5" style="width: 40%;">No. Nota</td>
                    <td class="fw-bold font-monospace text-primary py-1.5">: {{ $order->order_number }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">Saluran / Status</td>
                    <td class="py-1.5">: 
                        @if($order->channel_type === 'lazada')
                            <span class="badge bg-dark-subtle text-dark border border-dark-subtle rounded-pill px-2.5 py-0.5 fw-bold">
                                <i class="bi bi-bag-fill me-1 text-danger"></i>Lazada
                            </span>
                        @else
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-0.5 fw-bold">
                                <i class="bi bi-truck me-1"></i>Dropship
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">Tanggal Pesanan</td>
                    <td class="fw-semibold text-dark py-1.5">: {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">Nama Pelanggan</td>
                    <td class="fw-bold text-dark py-1.5">: {{ $order->customer_name }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">No. Telp / HP</td>
                    <td class="text-dark py-1.5">: {{ $order->customer_phone ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">Keterangan</td>
                    <td class="text-dark py-1.5">: {{ $order->notes ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">Dicatat Oleh</td>
                    <td class="text-dark py-1.5">: {{ $order->user ? $order->user->name : 'Sistem' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-1.5">Dibuat Pada</td>
                    <td class="text-muted py-1.5">: {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            </table>

            <div class="p-3 bg-light rounded-3 border mt-4">
                <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                    <span>Total Kuantitas:</span>
                    <strong class="text-dark font-monospace">{{ number_format($order->total_qty) }} PCS</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small">Total Nilai Pesanan:</span>
                    <strong class="text-success fs-5 fw-bold font-monospace">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Produk Card --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100" style="border: 1px solid #eef2f6 !important;">
            <h6 class="fw-bold text-dark border-bottom pb-2.5 mb-3" style="font-size:0.9rem;">
                <i class="bi bi-box-seam text-success me-1"></i> Rincian Produk ({{ $order->items->count() }} Jenis)
            </h6>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
                    <thead class="bg-light text-secondary text-uppercase fw-semibold border-bottom" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-2" style="width: 45%;">PRODUK &amp; SKU</th>
                            <th class="text-center py-2" style="width: 15%;">KUANTITAS</th>
                            <th class="text-end py-2" style="width: 20%;">HARGA SATUAN</th>
                            <th class="text-end pe-3 py-2" style="width: 20%;">JUMLAH (RP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $it)
                            <tr>
                                <td class="ps-3 py-2.5">
                                    <span class="fw-bold text-dark d-block">{{ $it->product_name }}</span>
                                    <small class="font-monospace text-muted">{{ $it->sku ?: '-' }}</small>
                                </td>
                                <td class="text-center font-monospace py-2.5">
                                    <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 fw-bold rounded-pill">
                                        {{ number_format($it->quantity) }} PCS
                                    </span>
                                </td>
                                <td class="text-end font-monospace py-2.5">
                                    Rp {{ number_format($it->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-success py-2.5">
                                    Rp {{ number_format($it->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="ps-3 py-2.5 text-uppercase">TOTAL KESELURUHAN</td>
                            <td class="text-center font-monospace py-2.5">{{ number_format($order->total_qty) }} PCS</td>
                            <td></td>
                            <td class="text-end pe-3 font-monospace text-success fs-6 py-2.5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
