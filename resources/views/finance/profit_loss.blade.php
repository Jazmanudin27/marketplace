@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi & Buku Kas')

@section('content')
{{-- Filter Tanggal --}}
<div class="card border rounded shadow-sm bg-white mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('finance.profit_loss') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill">
                        <i class="fas fa-filter me-1"></i> Saring
                    </button>
                    <a href="{{ route('finance.profit_loss') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Row Ringkasan Saldo Buku Kas & Net Profit --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border rounded shadow-sm bg-white h-100 border-start border-success border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fas fa-university text-success"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 font-monospace text-dark">Rp {{ number_format($balanceKasBesar, 0, ',', '.') }}</div>
                    <div class="text-muted small">Saldo Buku: Kas Besar</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Akumulasi seluruh waktu (Tunai Bank & Utama)</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border rounded shadow-sm bg-white h-100 border-start border-warning border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fas fa-wallet text-warning"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 font-monospace text-dark">Rp {{ number_format($balanceKasKecil, 0, ',', '.') }}</div>
                    <div class="text-muted small">Saldo Buku: Kas Kecil</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Akumulasi seluruh waktu (Kas Toko & Petty Cash)</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border rounded shadow-sm bg-white h-100 border-start {{ $netProfit >= 0 ? 'border-success' : 'border-danger' }} border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-3 {{ $netProfit >= 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fas fa-chart-line {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 font-monospace {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                    </div>
                    <div class="text-muted small">Laba Bersih (Net Profit) Periode Ini</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                        Margin Laba Bersih: <strong class="{{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $profitMargin }}%</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- LAPORAN LABA RUGI STATEMENT --}}
<div class="card border rounded shadow-sm bg-white mb-3">
    <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fas fa-file-invoice-dollar text-info me-2"></i>
            Laporan Laba Rugi Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        </h6>
        <span class="badge bg-secondary small">Cash & Accrual Consolidated</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-2 px-3 small text-muted text-uppercase">Komponen Laba Rugi</th>
                        <th class="text-end py-2 px-3 small text-muted text-uppercase" style="width:30%">Nominal (Rupiah)</th>
                        <th class="text-end py-2 px-3 small text-muted text-uppercase" style="width:20%">Persentase (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-light">
                        <td class="fw-bold px-3 small text-dark"><i class="fas fa-chevron-right me-2 text-success" style="font-size:0.8rem;"></i>I. PENDAPATAN OPERASIONAL & PENJUALAN</td>
                        <td></td><td></td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">A. Penjualan Online (Escrow Bersih / Marketplace Payout)</td>
                        <td class="text-end font-monospace small text-dark">Rp {{ number_format($onlineRevenue, 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($onlineRevenue / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">B. Penjualan Offline (Toko Fisik)</td>
                        <td class="text-end font-monospace small text-dark">Rp {{ number_format($offlineRevenue, 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($offlineRevenue / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-semibold ps-4 small text-dark">Total Pendapatan Penjualan</td>
                        <td class="text-end font-monospace fw-bold text-success small">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-success small">100.0%</td>
                    </tr>

                    <tr class="table-light">
                        <td class="fw-bold px-3 small text-dark"><i class="fas fa-chevron-right me-2 text-danger" style="font-size:0.8rem;"></i>II. HARGA POKOK PENJUALAN (HPP)</td>
                        <td></td><td></td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">A. HPP Produk Penjualan Online</td>
                        <td class="text-end font-monospace text-danger small">- Rp {{ number_format($onlineHpp, 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($onlineHpp / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">B. HPP Produk Penjualan Offline</td>
                        <td class="text-end font-monospace text-danger small">- Rp {{ number_format($offlineHpp, 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($offlineHpp / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr class="table-danger">
                        <td class="fw-semibold ps-4 small text-dark">Total Harga Pokok Penjualan (HPP)</td>
                        <td class="text-end font-monospace fw-bold text-danger small">- Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-danger small">{{ $totalSalesRevenue > 0 ? round(($totalHpp / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>

                    <tr class="table-primary">
                        <td class="fw-bold px-3 small text-dark"><i class="fas fa-equals me-2"></i>III. LABA KOTOR (GROSS PROFIT)</td>
                        <td class="text-end font-monospace fw-bold text-primary small">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-primary small">{{ $totalSalesRevenue > 0 ? round(($grossProfit / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>

                    <tr class="table-light">
                        <td class="fw-bold px-3 small text-dark"><i class="fas fa-chevron-right me-2 text-info" style="font-size:0.8rem;"></i>IV. PENDAPATAN & PEMASUKAN LAIN-LAIN</td>
                        <td></td><td></td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">A. Pemasukan Non-Penjualan (Investasi, Refund, Jasa, Lainnya)</td>
                        <td class="text-end font-monospace text-info small">Rp {{ number_format($totalOtherIncome, 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($totalOtherIncome / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>

                    <tr class="table-light">
                        <td class="fw-bold px-3 small text-dark"><i class="fas fa-chevron-right me-2 text-warning" style="font-size:0.8rem;"></i>V. PENGELUARAN & BIAYA OPERASIONAL</td>
                        <td></td><td></td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">A. Gaji Karyawan</td>
                        <td class="text-end font-monospace text-muted small">- Rp {{ number_format($expensesByCategory['salary'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($expensesByCategory['salary'] / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">B. Sewa Tempat</td>
                        <td class="text-end font-monospace text-muted small">- Rp {{ number_format($expensesByCategory['rent'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($expensesByCategory['rent'] / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">C. Utilitas & Operasional</td>
                        <td class="text-end font-monospace text-muted small">- Rp {{ number_format($expensesByCategory['utilities'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($expensesByCategory['utilities'] / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">D. Bayar Hutang Supplier</td>
                        <td class="text-end font-monospace text-muted small">- Rp {{ number_format($expensesByCategory['pembelian_supplier'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($expensesByCategory['pembelian_supplier'] / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted small">E. Lain-lain</td>
                        <td class="text-end font-monospace text-muted small">- Rp {{ number_format($expensesByCategory['other'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted small">{{ $totalSalesRevenue > 0 ? round(($expensesByCategory['other'] / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr class="table-danger">
                        <td class="fw-semibold ps-4 small text-dark">Total Pengeluaran & Biaya Operasional</td>
                        <td class="text-end font-monospace fw-bold text-danger small">- Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-danger small">{{ $totalSalesRevenue > 0 ? round(($totalExpenses / $totalSalesRevenue) * 100, 1) : 0 }}%</td>
                    </tr>

                    <tr class="{{ $netProfit >= 0 ? 'table-success' : 'table-danger' }}">
                        <td class="px-3 fw-bold small text-dark"><i class="fas fa-chart-line me-2"></i>VI. LABA BERSIH PERIODE INI (NET PROFIT)</td>
                        <td class="text-end font-monospace fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1.1rem;">
                            {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                        </td>
                        <td class="text-end fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1.1rem;">{{ $profitMargin }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- DETIL DATA PENDUKUNG --}}
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border rounded shadow-sm bg-white h-100">
            <div class="card-header bg-success bg-opacity-10 py-2 px-3 border-0">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-arrow-up text-success me-2"></i>Rincian Pemasukan Lain-Lain (Periode Ini)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Tanggal</th><th>Judul</th><th>Kas Tujuan</th><th class="text-end">Nominal</th></tr>
                        </thead>
                        <tbody>
                            @forelse($otherIncomes as $inc)
                                <tr>
                                    <td class="font-monospace small">{{ $inc->income_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold small text-dark">{{ $inc->title }}</div>
                                        <small class="text-muted">{{ $inc->category_label }}</small>
                                    </td>
                                    <td><span class="badge bg-secondary small">{{ $inc->payment_destination_label }}</span></td>
                                    <td class="font-monospace fw-bold text-success text-end small">Rp {{ number_format($inc->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada pemasukan lain pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border rounded shadow-sm bg-white h-100">
            <div class="card-header bg-danger bg-opacity-10 py-2 px-3 border-0">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-arrow-down text-danger me-2"></i>Rincian Pengeluaran Operasional (Periode Ini)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Tanggal</th><th>Judul</th><th>Kas Asal</th><th class="text-end">Nominal</th></tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $exp)
                                <tr>
                                    <td class="font-monospace small">{{ $exp->expense_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold small text-dark">{{ $exp->title }}</div>
                                        <small class="text-muted">{{ $exp->category_label }}</small>
                                    </td>
                                    <td><span class="badge bg-secondary small">{{ $exp->payment_source_label }}</span></td>
                                    <td class="font-monospace fw-bold text-danger text-end small">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada pengeluaran pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
