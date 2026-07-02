@extends('layouts.app')
@section('title', 'Laporan Penjualan Toko & Saluran')
@section('page-title', 'Laporan Toko & Saluran')

@section('content')
    {{-- Filter Bar --}}
    <div class="card border shadow-sm mb-4 bg-white">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('reports.store_sales') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold">
                            <i class="fas fa-filter me-1"></i> Filter Laporan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left: Store sales table --}}
        <div class="col-lg-8">
            <div class="card border shadow-sm bg-white mb-3">
                <div class="card-header bg-light py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-store text-primary me-2"></i>Kinerja Penjualan per Toko / Cabang
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr class="text-uppercase small">
                                    <th class="ps-3">Nama Toko</th>
                                    <th>Saluran (Channel)</th>
                                    <th class="text-end">Total Omset Bersih</th>
                                    <th class="text-center">Kuantitas</th>
                                    <th class="text-center">Pesanan</th>
                                    <th class="text-end pe-3">Rata-rata Order (AOV)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($storeStats as $stat)
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">{{ $stat['name'] }}</td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2 border border-secondary border-opacity-10 small">
                                                {{ $stat['channel'] }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace fw-semibold text-primary">
                                            Rp {{ number_format($stat['sales'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-center text-dark">{{ number_format($stat['quantity']) }} pcs</td>
                                        <td class="text-center text-dark">{{ number_format($stat['orders']) }} order</td>
                                        <td class="text-end font-monospace pe-3 text-muted">
                                            Rp {{ number_format($stat['aov'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fas fa-store-slash fa-2x mb-3 text-secondary opacity-25"></i>
                                            <p class="mb-0">Tidak ada transaksi dalam rentang tanggal ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Channel Summary & Top Products --}}
        <div class="col-lg-4">
            {{-- Group by Channel Card --}}
            <div class="card border shadow-sm bg-white mb-3">
                <div class="card-header bg-light py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-chart-pie text-secondary me-2"></i>Rekap per Saluran (Channel)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="font-size: 0.85rem;">
                        @forelse($channelStats as $ch)
                            <li class="list-group-item p-3 d-flex flex-column gap-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark">{{ $ch['name'] }}</strong>
                                    <span class="font-monospace text-primary fw-bold">
                                        Rp {{ number_format($ch['sales'], 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>{{ $ch['orders'] }} Transaksi</span>
                                    <span>AOV: Rp {{ number_format($ch['aov'], 0, ',', '.') }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item p-4 text-center text-muted">
                                Belum ada data.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Best Selling Products --}}
            <div class="card border shadow-sm bg-white">
                <div class="card-header bg-light py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-fire text-danger me-2"></i>5 Produk Terlaris Periode Ini
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="font-size: 0.85rem;">
                        @forelse($topProducts as $item)
                            <li class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div style="max-width: 70%;">
                                    <div class="fw-bold text-dark text-truncate">{{ $item->product_name }}</div>
                                    <small class="text-muted font-monospace">{{ $item->sku }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success-subtle text-success py-1 px-2.5 rounded-pill fw-bold">
                                        {{ $item->total_qty }} Pcs
                                    </span>
                                    <div class="small text-muted font-monospace mt-1" style="font-size: 0.72rem;">
                                        Rp {{ number_format($item->total_rev, 0, ',', '.') }}
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item p-4 text-center text-muted">
                                Belum ada data produk terjual.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
