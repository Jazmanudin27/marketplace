@extends('layouts.app')
@section('title', 'Laporan Laju Perputaran Stok Gudang')
@section('page-title', 'Laporan Perputaran Stok')

@section('content')
    {{-- Filter Bar --}}
    <div class="card border shadow-sm mb-4 bg-white">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('reports.inventory_turnover') }}">
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
                            <i class="fas fa-sync-alt me-1"></i> Hitung Laju Perputaran
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        <!-- Card 1: Total COGS -->
        <div class="col-md-4">
            <div class="card h-100 border shadow-sm bg-white">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-file-invoice-dollar text-danger me-2"></i>Total HPP Produk Keluar (COGS)
                    </div>
                    <div class="fw-bold fs-4 text-danger">Rp {{ number_format($totalCogsValue, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Nilai pokok barang terjual di periode ini</div>
                </div>
            </div>
        </div>

        <!-- Card 2: Average Inventory Value -->
        <div class="col-md-4">
            <div class="card h-100 border shadow-sm bg-white">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-warehouse text-primary me-2"></i>Nilai Rata-rata Persediaan
                    </div>
                    <div class="fw-bold fs-4 text-primary">Rp {{ number_format($totalAvgStockValue, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Estimasi aset stok mengendap di gudang</div>
                </div>
            </div>
        </div>

        <!-- Card 3: Days Sales in Inventory (DSI) -->
        <div class="col-md-4">
            <div class="card h-100 border shadow-sm bg-white {{ $totalDsi <= 30 ? 'bg-success-subtle border-success' : ($totalDsi <= 90 ? 'bg-warning-subtle border-warning' : 'bg-danger-subtle border-danger') }}">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-history me-2"></i>Rata-rata Hari Persediaan Habis (DSI)
                    </div>
                    <div class="fw-bold fs-4 text-dark">
                        @if($totalDsi > 365)
                            Tidak Terbatas (>365 Hari)
                        @else
                            {{ number_format($totalDsi, 1) }} Hari
                        @endif
                    </div>
                    <div class="small text-muted mt-1">
                        Rasio Perputaran Konsolidasi: <strong>{{ number_format($totalTurnoverRatio, 2) }}x</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Data Table --}}
    <div class="card border shadow-sm bg-white">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-boxes text-secondary me-2"></i>Rasio Perputaran & Hari Penjualan Stok per SKU (DSI)
            </h6>
            <span class="badge bg-secondary font-monospace">Rentang: {{ $daysInPeriod }} Hari</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr class="text-uppercase small" style="font-size: 0.75rem;">
                            <th class="ps-3">Produk / SKU</th>
                            <th class="text-center">Stok Awal</th>
                            <th class="text-center">Stok Akhir</th>
                            <th class="text-center">Stok Rerata</th>
                            <th class="text-center">Qty Keluar</th>
                            <th class="text-end">Total COGS (HPP)</th>
                            <th class="text-center">Rasio Putar</th>
                            <th class="text-center">Hari Habis (DSI)</th>
                            <th class="text-center pe-3">Indikator</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($turnoverData as $row)
                            @php
                                $dsiLabel = $row['dsi'] > 365 ? 'Tidak Terputar' : number_format($row['dsi'], 1) . ' Hari';
                                
                                // Indikator
                                if ($row['qty_sold'] == 0) {
                                    $indicator = 'Dead Stock';
                                    $badge = 'bg-danger';
                                } elseif ($row['dsi'] <= 15) {
                                    $indicator = 'Fast Moving';
                                    $badge = 'bg-success';
                                } elseif ($row['dsi'] <= 45) {
                                    $indicator = 'Normal';
                                    $badge = 'bg-primary';
                                } else {
                                    $indicator = 'Slow Moving';
                                    $badge = 'bg-warning text-dark';
                                }
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $row['name'] }}</div>
                                    <small class="text-muted font-monospace">{{ $row['sku'] }}</small>
                                </td>
                                <td class="text-center text-dark">{{ number_format($row['starting_stock'], 1) }}</td>
                                <td class="text-center text-dark">{{ number_format($row['ending_stock'], 1) }}</td>
                                <td class="text-center text-secondary">{{ number_format($row['avg_stock'], 1) }}</td>
                                <td class="text-center fw-bold text-dark">{{ number_format($row['qty_sold']) }} pcs</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($row['cogs'], 0, ',', '.') }}</td>
                                <td class="text-center font-monospace text-primary fw-bold">{{ number_format($row['ratio'], 2) }}x</td>
                                <td class="text-center font-monospace fw-semibold {{ $row['dsi'] > 365 ? 'text-danger' : 'text-dark' }}">{{ $dsiLabel }}</td>
                                <td class="text-center pe-3">
                                    <span class="badge {{ $badge }} px-2.5 py-1 rounded small" style="font-size: 0.72rem;">
                                        {{ $indicator }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    Tidak ada data produk master untuk dianalisis.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
