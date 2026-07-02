@extends('layouts.app')
@section('title', 'Laporan Saldo & Piutang Reseller')
@section('page-title', 'Laporan Saldo & Piutang')

@section('content')
    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Saldo Reseller -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border shadow-sm bg-white">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-wallet text-primary me-2"></i>Total Saldo Mengendap
                    </div>
                    <div class="fw-bold fs-4 text-primary">Rp {{ number_format($totalResellerBalance, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Akumulasi deposit/saldo aktif</div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Piutang Aktif -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border shadow-sm bg-white">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-file-invoice-dollar text-danger me-2"></i>Total Piutang Berjalan
                    </div>
                    <div class="fw-bold fs-4 text-danger">Rp {{ number_format($agingSummary['total'], 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Outstanding receivables dari POS</div>
                </div>
            </div>
        </div>

        <!-- Card 3: Piutang Lancar (0-30 Hari) -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border shadow-sm bg-white">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-clock text-success me-2"></i>Piutang Lancar (0-30 Hari)
                    </div>
                    <div class="fw-bold fs-4 text-success">Rp {{ number_format($agingSummary['current'], 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Belum jatuh tempo / baru</div>
                </div>
            </div>
        </div>

        <!-- Card 4: Piutang Jatuh Tempo (31+ Hari) -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border shadow-sm bg-white">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Piutang Tertunggak (>30 Hari)
                    </div>
                    <div class="fw-bold fs-4 text-warning">
                        Rp {{ number_format($agingSummary['31_60'] + $agingSummary['61_90'] + $agingSummary['90_plus'], 0, ',', '.') }}
                    </div>
                    <div class="small text-muted mt-1">Perlu ditindaklanjuti segera</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left: Reseller Receivables Aging Table --}}
        <div class="col-lg-8">
            <div class="card border shadow-sm bg-white mb-3">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-file-invoice text-danger me-2"></i>Analisis Umur Piutang per Pelanggan (Aging Schedule)
                    </h6>
                    <span class="badge bg-danger">Beban Kredit POS</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr class="text-uppercase small" style="font-size: 0.75rem;">
                                    <th class="ps-3">Nama Pelanggan / Reseller</th>
                                    <th class="text-end">0-30 Hari</th>
                                    <th class="text-end">31-60 Hari</th>
                                    <th class="text-end">61-90 Hari</th>
                                    <th class="text-end">90+ Hari</th>
                                    <th class="text-end pe-3">Total Piutang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customerAging as $cId => $aging)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ $aging['name'] }}</div>
                                            <small class="text-muted">{{ $aging['phone'] }}</small>
                                        </td>
                                        <td class="text-end font-monospace">Rp {{ number_format($aging['current'], 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace text-warning">Rp {{ number_format($aging['31_60'], 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace text-warning-emphasis">Rp {{ number_format($aging['61_90'], 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace text-danger fw-bold">Rp {{ number_format($aging['90_plus'], 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace fw-bold text-dark pe-3">Rp {{ number_format($aging['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fas fa-check-circle fa-2x mb-3 text-success opacity-25"></i>
                                            <p class="mb-0">Tidak ada piutang luar biasa yang belum terbayar.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Deposit Balances List --}}
        <div class="col-lg-4">
            <div class="card border shadow-sm bg-white">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-wallet text-primary me-2"></i>Saldo Aktif Terbesar
                    </h6>
                    <span class="badge bg-primary">Top Deposit</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                            <thead>
                                <tr class="table-light">
                                    <th class="ps-3">Nama</th>
                                    <th class="text-end pe-3">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($resellers as $reseller)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ $reseller->name }}</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">{{ $reseller->phone }}</small>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary pe-3">
                                            Rp {{ number_format($reseller->balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">
                                            Tidak ada reseller dengan saldo mengendap.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
