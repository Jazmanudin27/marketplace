@extends('layouts.app')
@section('title', 'Laporan Penjualan Toko & Saluran')
@section('page-title', 'Laporan Penjualan Per Toko & Marketplace')

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
        <div class="col-lg-12">
            <div class="card border shadow-sm bg-white mb-3">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-store text-primary me-2"></i>Kinerja Omset & Biaya Admin Per Toko
                    </h6>
                    <span class="badge bg-primary px-3 py-2">
                        <i class="fas fa-calendar-alt me-1"></i> {{ date('d M Y', strtotime($dateFrom)) }} – {{ date('d M Y', strtotime($dateTo)) }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr class="text-uppercase small">
                                    <th class="ps-3">Nama Toko</th>
                                    <th>Saluran (Channel)</th>
                                    <th class="text-center">Jumlah Order</th>
                                    <th class="text-end">Omset Kotor</th>
                                    <th class="text-end">Biaya Admin</th>
                                    <th class="text-end text-success">Omset Bersih (Net)</th>
                                    <th class="text-end pe-3">Rata-rata Order (AOV)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totOrders = 0;
                                    $totGross = 0;
                                    $totAdmin = 0;
                                    $totNet = 0;
                                @endphp
                                @forelse($storeStats as $stat)
                                    @php
                                        $totOrders += $stat['orders'];
                                        $totGross  += ($stat['gross_sales'] ?? 0);
                                        $totAdmin  += ($stat['admin_fee'] ?? 0);
                                        $totNet    += $stat['sales'];
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">{{ $stat['name'] }}</td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary py-1 px-2 border border-primary border-opacity-10 small fw-semibold">
                                                {{ $stat['channel'] }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-semibold text-dark">{{ number_format($stat['orders']) }} order</td>
                                        <td class="text-end font-monospace text-dark">
                                            Rp {{ number_format($stat['gross_sales'] ?? $stat['sales'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace text-danger">
                                            Rp {{ number_format($stat['admin_fee'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            Rp {{ number_format($stat['sales'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace pe-3 text-muted">
                                            Rp {{ number_format($stat['aov'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="fas fa-store-slash fa-2x mb-3 text-secondary opacity-25"></i>
                                            <p class="mb-0">Tidak ada transaksi dalam rentang tanggal ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($storeStats) > 0)
                                <tfoot class="table-light fw-bold border-top">
                                    <tr>
                                        <td colspan="2" class="ps-3 text-uppercase">TOTAL KESELURAHAN</td>
                                        <td class="text-center">{{ number_format($totOrders) }} order</td>
                                        <td class="text-end font-monospace">Rp {{ number_format($totGross, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace text-danger">Rp {{ number_format($totAdmin, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($totNet, 0, ',', '.') }}</td>
                                        <td class="text-end pe-3 font-monospace">Rp {{ number_format($totOrders > 0 ? $totNet / $totOrders : 0, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
