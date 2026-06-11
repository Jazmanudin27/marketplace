@extends('layouts.app')
@section('title', 'Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Pemenuhan Pesanan')

@section('content')
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div>
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Siap Kirim</div>
        </div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-label">Belum Diproses</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-pallet"></i></div>
        <div>
            <div class="stat-value">{{ $stats['packing'] }}</div>
            <div class="stat-label">Sedang Dikemas</div>
        </div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="stat-value">{{ $stats['verified'] }}</div>
            <div class="stat-label">Selesai Scan (Verified)</div>
        </div>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-barcode"></i> Antrean Kemas Pesanan</h3>
        <div>
            <a href="{{ route('fulfillment.scan_page') }}" class="btn-primary-sm" style="background: var(--primary); padding: 8px 16px; font-weight:600; display: inline-flex; align-items:center; gap:5px;">
                <i class="fas fa-expand"></i> Buka Layar Scanner (Scan Massal)
            </a>
        </div>
    </div>

    <div class="table-responsive" style="margin-top: 1.5rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice / ID</th>
                    <th>Toko / Channel</th>
                    <th>Pembeli</th>
                    <th>Detail Barang</th>
                    <th>Kurir</th>
                    <th>Status Kemas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="mono">
                        <span style="font-weight: 700; color: var(--text-primary);">{{ $order->invoice_number ?? $order->order_marketplace_id }}</span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">ID: {{ $order->order_marketplace_id }}</div>
                    </td>
                    <td>
                        <div style="font-weight: 500;">{{ $order->store->store_name }}</div>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 0.2rem;">
                            <span class="channel-badge channel-{{ $order->store->channel->code }}">
                                {{ $order->store->channel->name }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $order->buyer_name ?? '-' }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $order->buyer_phone ?? '' }}</div>
                    </td>
                    <td>
                        <ul style="padding-left: 1rem; margin-bottom: 0; font-size: 0.85rem; color: var(--text-secondary);">
                            @foreach($order->items as $item)
                                <li>
                                    <span style="color: var(--primary); font-weight: 600;">{{ $item->quantity }}x</span> 
                                    {{ $item->product_name }} 
                                    <span style="font-family: monospace; background: rgba(255,255,255,0.05); padding: 2px 4px; border-radius: 4px; font-size: 0.75rem;">
                                        {{ $item->sku ?? 'No SKU' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        <div style="font-weight: 500; color: var(--text-primary);">{{ $order->courier ?? '-' }}</div>
                        @if($order->tracking_number)
                            <div style="font-size: 0.8rem; font-family: monospace; color: var(--text-muted); margin-top: 0.2rem;">
                                <i class="fas fa-receipt"></i> {{ $order->tracking_number }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($order->packing_status === 'verified')
                            <span class="badge bg-success" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px;">
                                <i class="fas fa-check-circle"></i> Selesai Scan
                            </span>
                            @if($order->packed_at)
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">
                                    {{ $order->packed_at->format('d/m H:i') }}
                                </div>
                            @endif
                        @elseif($order->packing_status === 'packing')
                            <span class="badge bg-warning text-dark" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px;">
                                <i class="fas fa-box-open"></i> Sedang Dikemas
                            </span>
                        @else
                            <span class="badge bg-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.1) !important; color: var(--text-secondary);">
                                <i class="fas fa-clock"></i> Menunggu
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($order->packing_status === 'verified')
                            <div style="display: flex; gap: 5px; flex-direction: column;">
                                <form action="{{ route('orders.ship', $order->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-primary-sm" style="width: 100%; text-align: center; background: #22c55e; border: none; color: white; padding: 6px; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-paper-plane"></i> Kirim Resi
                                    </button>
                                </form>
                                <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="btn-primary-sm text-center" style="background: rgba(255,255,255,0.1); border: 1px solid var(--border); color: var(--text-primary); padding: 6px; border-radius: 4px; text-decoration: none;">
                                    <i class="fas fa-print"></i> Cetak Label
                                </a>
                            </div>
                        @else
                            <a href="{{ route('fulfillment.scan_page', ['invoice' => $order->invoice_number ?? $order->order_marketplace_id]) }}" class="btn-primary-sm text-center" style="background: var(--primary); border: none; color: white; padding: 6px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: block; font-weight: 500;">
                                <i class="fas fa-barcode"></i> Scan & Kemas
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding: 3rem;">
                        <i class="fas fa-box-open" style="font-size: 2.5rem; color: var(--text-muted); opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
                        Tidak ada pesanan Siap Kirim saat ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
