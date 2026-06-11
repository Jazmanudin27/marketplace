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
                    <form action="{{ route('orders.ship', $order->id) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn-primary-sm" style="background:#4CAF50; border-color:#4CAF50;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Memproses...'; this.disabled=true; this.form.submit();">
                            <i class="fas fa-truck-loading"></i> Kirim Pesanan
                        </button>
                    </form>
                    @endif
                    
                    @if(in_array($order->order_status, ['SHIPPED', 'READY_TO_SHIP']))
                        @if(empty($order->tracking_number))
                            <form action="{{ route('orders.tracking', $order->id) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-primary-sm" style="background:#FF9800; border-color:#FF9800;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Menarik...'; this.disabled=true; this.form.submit();">
                                    <i class="fas fa-sync"></i> Tarik Resi
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="btn-primary-sm" style="background:#2196F3; border-color:#2196F3; text-decoration:none;">
                            <i class="fas fa-print"></i> Cetak Invoice
                        </a>
                    @endif
                </div>
            </div>

            <div class="detail-row">
                <span class="detail-label">Pembeli</span>
                <span class="detail-value">
                    @if($order->customer_id)
                        <a href="{{ route('customers.show', $order->customer_id) }}" style="text-decoration:none; font-weight:600; color:var(--primary);">
                            {{ $order->buyer_name ?? '-' }} <i class="fas fa-external-link-alt" style="font-size:0.75rem;"></i>
                        </a>
                    @else
                        {{ $order->buyer_name ?? '-' }}
                    @endif
                </span>
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
                @if($order->financial_breakdown)
                    @php $fb = $order->financial_breakdown; @endphp
                    <div class="detail-row">
                        <span class="detail-label">Total Pembayaran Pembeli</span>
                        <span class="detail-value mono">Rp {{ number_format($fb['buyer_total_amount'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label text-muted" style="font-size:0.75rem;">(Harga Asli Produk)</span>
                        <span class="detail-value mono text-muted" style="font-size:0.75rem;">Rp {{ number_format($fb['original_price'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    
                    <hr style="border-color:var(--border); margin:0.75rem 0; border-style:dashed;">
                    
                    <div class="detail-row">
                        <span class="detail-label">Ongkir Dibayar Pembeli</span>
                        <span class="detail-value mono">Rp {{ number_format($fb['buyer_paid_shipping_fee'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Ongkir Aktual (Ekspedisi)</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($fb['actual_shipping_fee'] ?? 0, 0, ',', '.') }}</span>
                    </div>

                    <hr style="border-color:var(--border); margin:0.75rem 0; border-style:dashed;">

                    <div class="detail-row">
                        <span class="detail-label">Voucher Toko (Seller)</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($fb['voucher_from_seller'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label text-muted" style="font-size:0.75rem;">Voucher Shopee (Ditanggung Shopee)</span>
                        <span class="detail-value mono text-muted" style="font-size:0.75rem;">Rp {{ number_format($fb['voucher_from_shopee'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    
                    <hr style="border-color:var(--border); margin:0.75rem 0; border-style:dashed;">

                    <div class="detail-row">
                        <span class="detail-label">Biaya Layanan (Service Fee)</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($fb['service_fee'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biaya Komisi / Affiliate</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($fb['commission_fee'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biaya Transaksi</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($fb['seller_transaction_fee'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                @else
                    <div class="detail-row">
                        <span class="detail-label">Total Produk</span>
                        <span class="detail-value mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Ongkos Kirim</span>
                        <span class="detail-value mono">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Diskon Toko</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Estimasi Komisi Marketplace</span>
                        <span class="detail-value mono text-danger">- Rp {{ number_format($order->marketplace_fee, 0, ',', '.') }}</span>
                    </div>
                    @if($order->order_status !== 'COMPLETED')
                    <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.5rem; text-align:right;">
                        *Rincian pasti akan muncul saat pesanan Selesai.
                    </div>
                    @endif
                @endif

                <hr style="border-color:var(--border); margin:0.75rem 0;">
                <div class="detail-row">
                    <span class="detail-label fw-bold" style="color:var(--text-primary);">Pendapatan Bersih (Escrow)</span>
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

            {{-- 💰 Panel Analisis Profit --}}
            @php
                $order->load('items.masterProduct');
                $hppTotal   = $order->hpp_total;
                $netProfit  = $order->net_profit;
                $margin     = $order->profit_margin;
                $profitColor = $netProfit >= 0 ? 'var(--success)' : '#ef4444';
            @endphp
            <div class="dashboard-card" style="margin-top:1rem;">
                <h3 style="font-size:0.88rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-chart-line"></i> Analisis Profit
                </h3>
                <div class="detail-row">
                    <span class="detail-label">Pendapatan Bersih (Escrow)</span>
                    <span class="detail-value mono">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">HPP Total Item</span>
                    <span class="detail-value mono text-danger">
                        @if($hppTotal > 0)
                            - Rp {{ number_format($hppTotal, 0, ',', '.') }}
                        @else
                            <span style="color:var(--text-secondary); font-size:0.78rem;">HPP belum diset</span>
                        @endif
                    </span>
                </div>
                <hr style="border-color:var(--border); margin:0.75rem 0;">
                <div class="detail-row">
                    <span class="detail-label fw-bold" style="color:var(--text-primary);">Net Profit</span>
                    <span class="detail-value mono" style="color:{{ $profitColor }}; font-weight:800; font-size:1.05rem;">
                        {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                    </span>
                </div>
                <div class="detail-row" style="margin-top:0.35rem;">
                    <span class="detail-label" style="font-size:0.78rem; color:var(--text-secondary);">Margin</span>
                    <span class="detail-value" style="font-size:0.85rem; font-weight:700; color:{{ $profitColor }};">
                        {{ $margin }}%
                    </span>
                </div>
                @if($hppTotal <= 0)
                <div style="font-size:0.72rem; color:var(--text-secondary); margin-top:0.5rem; padding:0.5rem; background:rgba(255,255,255,.04); border-radius:6px;">
                    <i class="fas fa-info-circle"></i> Set <strong>Harga Pokok (HPP)</strong> di Master Produk agar profit dihitung akurat.
                </div>
                @endif
            </div>
        </div>

        {{-- ✅ Panel Tracking Pengiriman (hanya Shopee + ada resi) --}}
        @if($order->store->channel->code === 'shopee' && !in_array($order->order_status, ['UNPAID', 'READY_TO_SHIP', 'CANCELLED']))
        <div class="dashboard-card" style="margin-top:1rem;">
            <div class="card-header-line" style="cursor:pointer;" onclick="loadTracking()" id="tracking-toggle">
                <h3><i class="fas fa-shipping-fast"></i> Tracking Pengiriman Real-time</h3>
                <button class="btn-primary-sm" style="background:#17a2b8; border-color:#17a2b8;" id="btn-load-tracking">
                    <i class="fas fa-sync" id="tracking-spin"></i> Muat Tracking
                </button>
            </div>
            <div id="tracking-panel" style="display:none; padding:1rem 0;">
                <div id="tracking-loading" style="text-align:center; color:var(--text-secondary); padding:1.5rem;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i><br><small style="margin-top:.5rem; display:block;">Mengambil data dari Shopee...</small>
                </div>
                <div id="tracking-result" style="display:none;"></div>
                <div id="tracking-error" style="display:none; color:#ef4444; padding:1rem; text-align:center;"></div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function loadTracking() {
    const panel = document.getElementById('tracking-panel');
    const loading = document.getElementById('tracking-loading');
    const result = document.getElementById('tracking-result');
    const errDiv = document.getElementById('tracking-error');
    const spin = document.getElementById('tracking-spin');
    const btn = document.getElementById('btn-load-tracking');

    if (panel.style.display === 'block') {
        panel.style.display = 'none';
        return;
    }

    panel.style.display = 'block';
    loading.style.display = 'block';
    result.style.display = 'none';
    errDiv.style.display = 'none';
    spin.className = 'fas fa-spinner fa-spin';
    btn.disabled = true;

    fetch('{{ route("orders.tracking.detail", $order->id) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        loading.style.display = 'none';
        spin.className = 'fas fa-map-marker-alt';
        btn.disabled = false;

        if (data.error) {
            errDiv.textContent = '⚠️ ' + data.error;
            errDiv.style.display = 'block';
            return;
        }

        const info = data.tracking_info || {};
        const details = info.tracking_info || [];
        const carrier = info.first_mile_tracking_number || '{{ $order->tracking_number ?? "-" }}';

        let html = `<div style="padding:0 0.25rem;">
            <div style="font-size:0.78rem; color:var(--text-secondary); margin-bottom:1rem;">
                <i class="fas fa-barcode"></i> No. Resi: <strong style="color:var(--text-primary);">${carrier}</strong>
            </div>`;

        if (details.length === 0) {
            html += `<div style="text-align:center; color:var(--text-secondary); padding:1rem;">
                <i class="fas fa-clock fa-2x" style="opacity:.4;"></i><br>
                <small style="display:block; margin-top:.5rem;">Belum ada update tracking dari kurir.</small>
            </div>`;
        } else {
            html += `<div class="tracking-timeline">`;
            details.forEach((item, idx) => {
                const isFirst = idx === 0;
                const tsDate = item.seconds ? new Date(item.seconds * 1000) : null;
                const dateStr = tsDate ? tsDate.toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}) : '';
                html += `
                <div class="tracking-step ${isFirst ? 'active' : ''}">
                    <div class="tracking-dot ${isFirst ? 'dot-active' : ''}"></div>
                    <div class="tracking-content">
                        <div class="tracking-desc">${item.description || '-'}</div>
                        ${dateStr ? `<div class="tracking-time"><i class="fas fa-clock"></i> ${dateStr}</div>` : ''}
                    </div>
                </div>`;
            });
            html += `</div>`;
        }
        html += `</div>`;
        result.innerHTML = html;
        result.style.display = 'block';
    })
    .catch(err => {
        loading.style.display = 'none';
        btn.disabled = false;
        spin.className = 'fas fa-sync';
        errDiv.textContent = '⚠️ Gagal menghubungi server: ' + err.message;
        errDiv.style.display = 'block';
    });
}
</script>
@endpush

@push('styles')
<style>
.tracking-timeline { padding-left: 0.5rem; }
.tracking-step { display: flex; gap: 1rem; padding-bottom: 1.25rem; position: relative; }
.tracking-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 9px; top: 20px; bottom: 0;
    width: 2px;
    background: var(--border);
}
.tracking-step.active::after { background: var(--primary); }
.tracking-dot {
    width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
    background: var(--border); margin-top: 2px; position: relative; z-index: 1;
    border: 3px solid var(--bg-card);
    box-shadow: 0 0 0 2px var(--border);
}
.tracking-dot.dot-active {
    background: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.25);
}
.tracking-content { flex: 1; }
.tracking-desc { font-weight: 600; font-size: 0.875rem; color: var(--text-primary); }
.tracking-time { font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; }
</style>
@endpush
@endsection
