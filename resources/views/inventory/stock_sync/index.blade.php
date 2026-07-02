@extends('layouts.app')
@section('title', 'Sinkronisasi Stok Marketplace')
@section('page-title', 'Sinkronisasi Stok')

@section('content')
<div class="row g-3">
    {{-- Left: Statistik & Pemetaan Produk --}}
    <div class="col-md-8">
        <div class="card border rounded shadow-sm bg-white mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fas fa-sync text-primary me-2"></i>Pemetaan & Status Stok Produk
                    </h5>
                    <form action="{{ route('inventory.stock_sync.all') }}" method="POST" onsubmit="return confirm('Apakah Anda ingin memicu pembaruan stok massal untuk semua produk?')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                            <i class="fas fa-cloud-upload-alt me-1"></i> Sinkronisasi Massal Semua
                        </button>
                    </form>
                </div>

                {{-- Search --}}
                <form method="GET" class="mb-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari SKU, Nama Produk, atau ID Marketplace...">
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="fas fa-search"></i> Cari
                        </button>
                    </div>
                </form>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-striped table-hover border align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th>NAMA PRODUK / MARKETPLACE SKU</th>
                                <th>MARKETPLACE / TOKO</th>
                                <th class="text-center">STOK LOKAL</th>
                                <th class="text-center">SAFETY STOCK</th>
                                <th class="text-center">STOK TERKIRIM</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappedProducts as $mp)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $mp->name }}</div>
                                        <div class="font-monospace text-muted small mt-1">
                                            SKU: {{ $mp->marketplace_sku ?? 'No SKU' }} | ID: {{ $mp->marketplace_product_id }}
                                        </div>
                                        @if($mp->masterProduct)
                                            <div class="small text-primary mt-1">
                                                <i class="fas fa-link"></i> Terhubung ke: {{ $mp->masterProduct->name }} ({{ $mp->masterProduct->sku }})
                                            </div>
                                        @else
                                            <div class="small text-danger mt-1">
                                                <i class="fas fa-exclamation-triangle"></i> Belum terhubung ke produk master
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $mp->store->store_name }}</div>
                                        <span class="badge bg-light text-dark border small mt-1">
                                            {{ $mp->store->channel->name }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        {{ $mp->masterProduct ? $mp->masterProduct->stock : '-' }}
                                    </td>
                                    <td class="text-center text-muted">
                                        {{ $mp->safety_stock ?? 0 }}
                                    </td>
                                    <td class="text-center font-monospace text-success fw-bold">
                                        {{ $mp->stock }}
                                    </td>
                                    <td class="text-center">
                                        @if($mp->sync_stock)
                                            <span class="badge bg-success-subtle text-success py-1 px-2 border border-success border-opacity-25">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2 border border-secondary border-opacity-25">Mati</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($mp->masterProduct)
                                            <form action="{{ route('inventory.stock_sync.product', $mp) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm px-2" title="Force Sync Stok">
                                                    <i class="fas fa-sync"></i> Sync
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-sync fa-2x mb-3 text-secondary opacity-25"></i>
                                        <p class="mb-0 small">Belum ada pemetaan produk marketplace.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $mappedProducts->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Log Sinkronisasi Stok Terbaru --}}
    <div class="col-md-4">
        <div class="card border rounded shadow-sm bg-white overflow-hidden">
            <div class="card-header bg-light border-bottom py-2 px-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-history text-secondary me-2"></i>Log Sinkronisasi Terbaru</h6>
            </div>
            <div class="card-body p-0" style="max-height: 550px; overflow-y: auto;">
                <ul class="list-group list-group-flush">
                    @forelse($syncLogs as $log)
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-{{ $log->status_badge }} text-uppercase" style="font-size: 0.65rem;">
                                    {{ $log->status_label }}
                                </span>
                                <small class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="small fw-semibold text-dark">{{ $log->sku }}</div>
                            <div class="small text-muted">
                                Stok Terkirim: <strong class="text-primary">{{ $log->pushed_stock }}</strong> | Saluran: {{ strtoupper($log->channel_code) }}
                            </div>
                            @if($log->status === 'failed' && $log->error_message)
                                <div class="mt-1 p-2 bg-danger-subtle text-danger rounded border border-danger border-opacity-10" style="font-size: 0.72rem; word-break: break-all;">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $log->error_message }}
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item p-4 text-center text-muted small">
                            Belum ada riwayat aktivitas sinkronisasi.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
