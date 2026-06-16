@extends('layouts.mobile')

@section('title', 'Produksi Dashboard')
@section('header-title', 'Produksi Dashboard')

@section('content')
<!-- Active Production Queue Section -->
<div class="glass-card p-4 mb-4" style="border-top: 4px solid var(--accent-yellow);">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0" style="font-size: 1rem; font-weight: 600; color:var(--accent-yellow);">
            <i class="fas fa-hourglass-half me-1"></i> Antrean Permintaan (Pending)
        </h4>
        <span class="badge bg-warning bg-opacity-20 text-warning" style="font-size: 0.7rem;">
            {{ count($pendingOrders) }} Pesanan
        </span>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse($pendingOrders as $order)
            <div class="p-3" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-card); border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div style="font-size: 0.9rem; font-weight: 600;">{{ $order->masterProduct->name }}</div>
                        <small class="text-muted d-block mt-0.5">SKU: <span class="mono">{{ $order->masterProduct->sku }}</span></small>
                    </div>
                    <span class="badge status-badge status-pending">Pending</span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light border-opacity-10">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Jumlah: <span class="fw-bold text-white">{{ $order->quantity }} pcs</span>
                    </div>
                    <form action="{{ route('mobile.produksi.start', $order) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-premium btn-sm py-1.5 px-3" style="font-size: 0.75rem;">
                            <i class="fas fa-play me-1"></i> Mulai Produksi
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted" style="font-size: 0.85rem;">
                Tidak ada antrean permintaan produksi saat ini.
            </div>
        @endforelse
    </div>
</div>

<!-- Producing (In Progress) Section -->
<div class="glass-card p-4 mb-4" style="border-top: 4px solid var(--accent-blue);">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0" style="font-size: 1rem; font-weight: 600; color:var(--accent-blue);">
            <i class="fas fa-cog fa-spin me-1"></i> Sedang Diproduksi (In Progress)
        </h4>
        <span class="badge bg-info bg-opacity-20 text-info" style="font-size: 0.7rem;">
            {{ count($producingOrders) }} Diproses
        </span>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse($producingOrders as $order)
            <div class="p-3" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-card); border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div style="font-size: 0.9rem; font-weight: 600;">{{ $order->masterProduct->name }}</div>
                        <small class="text-muted d-block mt-0.5">SKU: <span class="mono">{{ $order->masterProduct->sku }}</span></small>
                    </div>
                    <span class="badge status-badge status-producing">Diproses</span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light border-opacity-10">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Jumlah: <span class="fw-bold text-white">{{ $order->quantity }} pcs</span>
                    </div>
                    <div class="d-flex gap-2">
                        <!-- Cancel Button -->
                        <form action="{{ route('mobile.produksi.cancel', $order) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-secondary-custom btn-sm py-1.5 px-2.5" style="font-size: 0.75rem; color: var(--accent-red); border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05);" onclick="return confirm('Batalkan permintaan produksi ini?')">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        <!-- Complete Button -->
                        <form action="{{ route('mobile.produksi.complete', $order) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-premium btn-sm py-1.5 px-3" style="font-size: 0.75rem; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                                <i class="fas fa-check me-1"></i> Selesai
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted" style="font-size: 0.85rem;">
                Tidak ada barang yang sedang diproduksi.
            </div>
        @endforelse
    </div>
</div>

<!-- History Log -->
<div class="glass-card p-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-history me-1"></i> Riwayat Produksi
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($completedOrders as $order)
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600;">{{ $order->masterProduct->name }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);" class="mt-1">
                        Jumlah: <span class="fw-bold">{{ $order->quantity }} pcs</span>
                        <span class="mx-1">•</span>
                        <span>{{ $order->updated_at->format('d/m H:i') }}</span>
                    </div>
                </div>
                <div>
                    <span class="badge status-badge status-{{ $order->status }}">
                        {{ $order->status === 'completed' ? 'Selesai' : 'Batal' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-3 text-muted" style="font-size: 0.8rem;">
                Belum ada riwayat produksi hari ini.
            </div>
        @endforelse
    </div>
</div>
@endsection
