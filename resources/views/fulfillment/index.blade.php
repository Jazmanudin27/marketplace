@extends('layouts.app')
@section('title', 'Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Pemenuhan Pesanan')

@section('content')
<div class="row">
    <div class="col-md-12">

        {{-- Stats Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100 border rounded shadow-sm bg-white border-start border-primary border-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="fs-2 text-primary opacity-75"><i class="fas fa-box"></i></div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">{{ $stats['total'] }}</h4>
                            <p class="text-muted mb-0 small">Total Siap Kirim</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border rounded shadow-sm bg-white border-start border-warning border-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="fs-2 text-warning opacity-75"><i class="fas fa-clock"></i></div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">{{ $stats['pending'] }}</h4>
                            <p class="text-muted mb-0 small">Belum Diproses</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border rounded shadow-sm bg-white border-start border-info border-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="fs-2 text-info opacity-75"><i class="fas fa-pallet"></i></div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">{{ $stats['packing'] }}</h4>
                            <p class="text-muted mb-0 small">Sedang Dikemas</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border rounded shadow-sm bg-white border-start border-success border-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="fs-2 text-success opacity-75"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">{{ $stats['verified'] }}</h4>
                            <p class="text-muted mb-0 small">Selesai Scan (Verified)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card border rounded shadow-sm bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-barcode"></i> Antrean Kemas Pesanan</h5>
                    <div>
                        <a href="{{ route('fulfillment.scan_page') }}"
                            class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 fw-semibold">
                            <i class="fas fa-expand"></i> Buka Layar Scanner (Scan Massal)
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover border align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>INVOICE / ID</th>
                                <th>TOKO / CHANNEL</th>
                                <th>PEMBELI</th>
                                <th>DETAIL BARANG</th>
                                <th>KURIR</th>
                                <th>STATUS KEMAS</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td class="font-monospace">
                                        <span class="fw-bold text-dark">{{ $order->invoice_number ?? $order->order_marketplace_id }}</span>
                                        <div class="small text-muted mt-1">ID: {{ $order->order_marketplace_id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $order->store->store_name }}</div>
                                        <div class="small text-muted mt-1">
                                            <span class="badge bg-light text-dark border">
                                                {{ $order->store->channel->name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $order->buyer_name ?? '-' }}</div>
                                        <div class="small text-muted">{{ $order->buyer_phone ?? '' }}</div>
                                    </td>
                                    <td>
                                        <ul class="ps-3 mb-0 small">
                                            @foreach ($order->items as $item)
                                                <li class="text-dark">
                                                    <span class="text-primary fw-semibold">{{ $item->quantity }}x</span>
                                                    {{ $item->product_name }}
                                                    <span class="font-monospace bg-light border px-1 rounded small">
                                                        {{ $item->sku ?? 'No SKU' }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $order->courier ?? '-' }}</div>
                                        @if ($order->tracking_number)
                                            <div class="small font-monospace text-muted mt-1">
                                                <i class="fas fa-receipt text-secondary"></i> {{ $order->tracking_number }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->packing_status === 'verified')
                                            <span class="badge bg-success py-2 px-3">
                                                <i class="fas fa-check-circle"></i> Selesai Scan
                                            </span>
                                            @if ($order->packed_at)
                                                <div class="small text-muted mt-1">
                                                    {{ $order->packed_at->format('d/m H:i') }}
                                                </div>
                                            @endif
                                        @elseif($order->packing_status === 'packing')
                                            <span class="badge bg-warning text-dark py-2 px-3">
                                                <i class="fas fa-box-open"></i> Sedang Dikemas
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border py-2 px-3">
                                                <i class="fas fa-clock"></i> Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->packing_status === 'verified')
                                            <div class="d-flex flex-column gap-1">
                                                <form action="{{ route('orders.ship', $order->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm w-100 py-1">
                                                        <i class="fas fa-paper-plane"></i> Kirim Resi
                                                    </button>
                                                </form>
                                                <a href="{{ route('orders.print', $order->id) }}" target="_blank"
                                                    class="btn btn-outline-secondary btn-sm w-100 py-1">
                                                    <i class="fas fa-print"></i> Cetak Label
                                                </a>
                                            </div>
                                        @else
                                            <a href="{{ route('fulfillment.scan_page', ['invoice' => $order->invoice_number ?? $order->order_marketplace_id]) }}"
                                                class="btn btn-primary btn-sm w-100 py-1 fw-semibold">
                                                <i class="fas fa-barcode"></i> Scan & Kemas
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-5">
                                        <i class="fas fa-box-open fs-1 text-muted opacity-25 mb-3 d-block"></i>
                                        Tidak ada pesanan Siap Kirim saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
