@extends('layouts.app')
@section('title', 'Rekonsiliasi Keuangan')
@section('page-title', 'Rekonsiliasi & Margin')
@section('content')

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <!-- Total Cair -->
    <div class="col-12 col-md-6">
        <div class="card border rounded shadow-sm bg-white">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                    <i class="fas fa-wallet fs-5"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-dark">Rp {{ number_format($totalNetPage, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Cair (Net Amount)</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Selisih -->
    <div class="col-12 col-md-6">
        <div class="card border rounded shadow-sm bg-white {{ $totalDiscrepancyPage > 0 ? 'border-warning' : '' }}">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $totalDiscrepancyPage > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-primary bg-opacity-10 text-primary' }}" style="width: 48px; height: 48px;">
                    <i class="fas fa-balance-scale fs-5"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 {{ $totalDiscrepancyPage > 0 ? 'text-warning' : 'text-primary' }}">Rp {{ number_format($totalDiscrepancyPage, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Selisih (Discrepancy)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border rounded shadow-sm bg-white">
    <div class="card-body">
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Detail Pesanan Selesai (Completed)</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice / ID</th>
                        <th>Toko (Channel)</th>
                        <th>Penjualan (A)</th>
                        <th>Ongkir (B)</th>
                        <th>Biaya Admin (C)</th>
                        <th>Pencairan (Actual)</th>
                        <th>Selisih</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="{{ $order->has_discrepancy ? 'table-danger' : '' }}">
                        <td class="font-monospace">
                            <a href="{{ route('orders.show', $order) }}" class="text-primary fw-bold text-decoration-none">
                                {{ $order->invoice_number ?? $order->order_marketplace_id }}
                            </a>
                            <button class="btn btn-xs btn-outline-info ms-2 py-0 px-1" type="button" data-bs-toggle="collapse" data-bs-target="#fb-detail-{{ $order->id }}" aria-expanded="false" aria-controls="fb-detail-{{ $order->id }}" style="font-size: 0.65rem;">
                                <i class="fas fa-eye me-1"></i>Rincian
                            </button><br>
                            <small class="text-muted">{{ $order->order_date->format('d/m/y H:i') }}</small>
                        </td>
                        <td>
                            <span class="text-dark fw-semibold">{{ $order->store->store_name }}</span><br>
                            <span class="badge bg-light text-dark border">{{ $order->store->channel->name }}</span>
                        </td>
                        <td class="font-monospace">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="font-monospace text-muted">- Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</td>
                        <td class="font-monospace {{ $order->is_high_fee ? 'text-danger' : 'text-muted' }}">
                            - Rp {{ number_format($order->marketplace_fee, 0, ',', '.') }}<br>
                            <small>({{ $order->fee_percentage }}%)</small>
                        </td>
                        <td class="font-monospace fw-bold text-success">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</td>
                        
                        <td class="font-monospace fw-bold {{ $order->discrepancy_amount > 0 ? 'text-danger' : 'text-dark' }}">
                            {{ $order->discrepancy_amount != 0 ? 'Rp '.number_format($order->discrepancy_amount, 0, ',', '.') : '-' }}
                        </td>
                        
                        <td>
                            @if($order->has_discrepancy)
                                <span class="badge bg-danger">Discrepancy</span>
                            @elseif($order->is_high_fee)
                                <span class="badge bg-warning text-dark">High Fee</span>
                            @else
                                <span class="badge bg-success">Matched</span>
                            @endif
                        </td>
                    </tr>

                    @php
                        $fb = $order->financial_breakdown ?? [];
                    @endphp
                    <tr class="collapse" id="fb-detail-{{ $order->id }}">
                        <td colspan="8" class="p-3 bg-light">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="fw-bold mb-2 text-dark small"><i class="fas fa-info-circle me-1 text-success"></i> Rincian Pendapatan</div>
                                    <table class="table table-sm table-borderless text-muted mb-0" style="font-size:0.75rem;">
                                        <tr>
                                            <td>Harga Produk (Product Amount)</td>
                                            <td class="text-end text-dark font-monospace">Rp {{ number_format($fb['original_price'] ?? $order->total_amount, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Ongkos Kirim (Shipping Fee)</td>
                                            <td class="text-end text-dark font-monospace">Rp {{ number_format($fb['actual_shipping_fee'] ?? ($fb['buyer_paid_shipping_fee'] ?? $order->shipping_fee), 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold mb-2 text-dark small"><i class="fas fa-percentage me-1 text-danger"></i> Potongan & Komisi</div>
                                    <table class="table table-sm table-borderless text-muted mb-0" style="font-size:0.75rem;">
                                        <tr>
                                            <td>Komisi Platform (Platform Commission)</td>
                                            <td class="text-end text-danger font-monospace">- Rp {{ number_format(($fb['service_fee'] ?? 0) + ($fb['seller_transaction_fee'] ?? 0) ?: $order->marketplace_fee, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Komisi Affiliate (Affiliate Commission)</td>
                                            <td class="text-end text-danger font-monospace">- Rp {{ number_format($fb['commission_fee'] ?? 0, 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold mb-2 text-dark small"><i class="fas fa-tags me-1 text-warning"></i> Voucher & Penyesuaian</div>
                                    <table class="table table-sm table-borderless text-muted mb-0" style="font-size:0.75rem;">
                                        <tr>
                                            <td>Subsidi Voucher (Voucher Subsidy)</td>
                                            <td class="text-end text-success font-monospace">+ Rp {{ number_format($fb['voucher_from_shopee'] ?? 0, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Penyesuaian (Adjustment)</td>
                                            <td class="text-end font-monospace {{ ($fb['adjustment_amount'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ ($fb['adjustment_amount'] ?? 0) < 0 ? '-' : '+' }} Rp {{ number_format(abs($fb['adjustment_amount'] ?? 0), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Belum ada pesanan yang selesai</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
