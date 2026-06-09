@extends('layouts.app')
@section('title', 'Detail Pesanan')
@section('page-title', 'Detail Pesanan')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('orders.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan
    </a>

    <div style="display:grid; grid-template-columns: 1fr 320px; gap:1rem; margin-top:1rem;">

        {{-- Detail Pesanan --}}
        <div class="dashboard-card">
            <div class="card-header-line">
                <h3><i class="fas fa-receipt"></i> {{ $order->invoice_number ?? $order->order_marketplace_id }}</h3>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <span class="badge badge-{{ $order->status_badge }}">{{ str_replace('_', ' ', $order->order_status) }}</span>
                    @if(!in_array($order->order_status, ['SHIPPED', 'CANCELLED', 'DELIVERED']))
                    <form action="{{ route('orders.process', $order->id) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn-primary-sm" style="background:#4CAF50; border-color:#4CAF50;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Memproses...'; this.disabled=true; this.form.submit();">
                            <i class="fas fa-truck-loading"></i> Proses Pesanan
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="detail-row">
                <span class="detail-label">Pembeli</span>
                <span class="detail-value">{{ $order->buyer_name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">No. Telp</span>
                <span class="detail-value">{{ $order->buyer_phone ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Alamat</span>
                <span class="detail-value">{{ $order->shipping_address ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Kurir</span>
                <span class="detail-value">{{ $order->courier ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">No. Resi</span>
                <span class="detail-value mono">{{ $order->tracking_number ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tanggal Pesanan</span>
                <span class="detail-value">{{ $order->order_date->format('d M Y, H:i') }}</span>
            </div>

            <hr style="border-color:var(--border); margin:1rem 0;">

            <h4 style="font-size:0.85rem; margin-bottom:0.75rem; color:var(--text-secondary);">
                <i class="fas fa-box"></i> Item Pesanan
            </h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th style="text-align:right;">Harga</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td class="fw-bold">{{ $item->product_name }}</td>
                        <td class="mono">{{ $item->sku ?? '-' }}</td>
                        <td class="mono" style="text-align:right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">{{ $item->quantity }}</td>
                        <td class="mono fw-bold" style="text-align:right;">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ringkasan Pembayaran --}}
        <div>
            <div class="dashboard-card">
                <h3 style="font-size:0.88rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-wallet"></i> Ringkasan Pembayaran
                </h3>
                <div class="detail-row">
                    <span class="detail-label">Total Produk</span>
                    <span class="detail-value mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ongkos Kirim</span>
                    <span class="detail-value mono">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Diskon</span>
                    <span class="detail-value mono text-danger">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Komisi Marketplace</span>
                    <span class="detail-value mono text-danger">- Rp {{ number_format($order->marketplace_fee, 0, ',', '.') }}</span>
                </div>
                <hr style="border-color:var(--border); margin:0.75rem 0;">
                <div class="detail-row">
                    <span class="detail-label fw-bold" style="color:var(--text-primary);">Pendapatan Bersih</span>
                    <span class="detail-value mono" style="color:var(--success); font-weight:800; font-size:1.05rem;">
                        Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="dashboard-card" style="margin-top:1rem;">
                <h3 style="font-size:0.88rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-store"></i> Info Toko
                </h3>
                <div class="detail-row">
                    <span class="detail-label">Platform</span>
                    <span class="channel-badge channel-{{ $order->store->channel->code }}">
                        {{ $order->store->channel->name }}
                    </span>
                </div>
                <div class="detail-row" style="margin-top:0.5rem;">
                    <span class="detail-label">Nama Toko</span>
                    <span class="detail-value">{{ $order->store->store_name }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
