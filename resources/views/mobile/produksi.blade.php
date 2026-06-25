@extends('layouts.mobile')

@section('title', 'Produksi Dashboard')
@section('header-title', 'Produksi Dashboard')

@section('content')
<!-- Active Production Queue Section -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3 border-bottom border-warning border-opacity-25">
        <h6 class="m-0 fw-bold text-warning">
            <i class="fas fa-hourglass-half me-1"></i> Antrean Permintaan (Pending)
        </h6>
        <span class="badge bg-warning text-dark">
            {{ count($pendingOrders) }} Pesanan
        </span>
    </div>

    <div class="card-body p-3">
        <div class="d-flex flex-column gap-2">
            @forelse($pendingOrders as $order)
                <div class="p-3 border rounded bg-light">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold text-dark small">{{ $order->masterProduct->name }}</div>
                            <small class="text-muted d-block mt-0.5">SKU: <code class="text-primary font-monospace">{{ $order->masterProduct->sku }}</code></small>
                        </div>
                        <span class="badge bg-warning text-dark">Pending</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <small class="text-muted">
                            Jumlah: <strong class="text-dark">{{ $order->quantity }} pcs</strong>
                        </small>
                        <form action="{{ route('mobile.produksi.start', $order) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm py-1 px-3">
                                <i class="fas fa-play me-1"></i> Mulai Produksi
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    Tidak ada antrean permintaan produksi saat ini.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Producing (In Progress) Section -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3 border-bottom border-primary border-opacity-25">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-cog fa-spin me-1"></i> Sedang Diproduksi (In Progress)
        </h6>
        <span class="badge bg-primary">
            {{ count($producingOrders) }} Diproses
        </span>
    </div>

    <div class="card-body p-3">
        <div class="d-flex flex-column gap-2">
            @forelse($producingOrders as $order)
                <div class="p-3 border rounded bg-light">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold text-dark small">{{ $order->masterProduct->name }}</div>
                            <small class="text-muted d-block mt-0.5">SKU: <code class="text-primary font-monospace">{{ $order->masterProduct->sku }}</code></small>
                        </div>
                        <span class="badge bg-primary">Diproses</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <small class="text-muted">
                            Jumlah: <strong class="text-dark">{{ $order->quantity }} pcs</strong>
                        </small>
                        <div class="d-flex gap-2">
                            <!-- Cancel Button -->
                            <form action="{{ route('mobile.produksi.cancel', $order) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="return confirm('Batalkan permintaan produksi ini?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <!-- Complete Button -->
                            <form action="{{ route('mobile.produksi.complete', $order) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm py-1 px-3">
                                    <i class="fas fa-check me-1"></i> Selesai
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    Tidak ada barang yang sedang diproduksi.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- History Log -->
<div class="card border shadow-sm">
    <div class="card-header bg-primary bg-opacity-10 py-2 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-history me-1"></i> Riwayat Produksi
        </h6>
    </div>
    
    <div class="card-body p-3">
        <div class="d-flex flex-column gap-3">
            @forelse($completedOrders as $order)
                <div class="d-flex justify-content-between align-items-center pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <div class="fw-bold text-dark small">{{ $order->masterProduct->name }}</div>
                        <div class="text-muted small mt-1">
                            Jumlah: <strong class="text-dark">{{ $order->quantity }} pcs</strong>
                            <span class="mx-1">•</span>
                            <span>{{ $order->updated_at->format('d/m H:i') }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="badge {{ $order->status === 'completed' ? 'bg-success' : 'bg-danger' }}">
                            {{ $order->status === 'completed' ? 'Selesai' : 'Batal' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-3 text-muted small">
                    Belum ada riwayat produksi hari ini.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
