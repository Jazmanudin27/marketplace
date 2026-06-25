@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi & Buku Kas')

@section('content')
{{-- Filter Tanggal --}}
<div class="dashboard-card mb-4 py-3">
    <form method="GET" action="{{ route('finance.profit_loss') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm form-control-dark">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm form-control-dark">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="fas fa-filter me-1"></i> Saring Laporan
                </button>
                <a href="{{ route('finance.profit_loss') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- Row Ringkasan Saldo Buku Kas & Net Profit --}}
<div class="row g-3 mb-4">
    {{-- Saldo Kas Besar --}}
    <div class="col-12 col-md-4">
        <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
            <div class="stat-icon flex-shrink-0" style="background:rgba(16,185,129,.15);color:#34d399">
                <i class="fas fa-university"></i>
            </div>
            <div class="min-width-0">
                <div class="fw-bold fs-4 text-white">Rp {{ number_format($balanceKasBesar, 0, ',', '.') }}</div>
                <div class="text-muted small">Saldo Buku: Kas Besar</div>
                <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Akumulasi seluruh waktu (Tunai Bank & Utama)</small>
            </div>
        </div>
    </div>

    {{-- Saldo Kas Kecil --}}
    <div class="col-12 col-md-4">
        <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
            <div class="stat-icon flex-shrink-0" style="background:rgba(245,158,11,.15);color:#fbbf24">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="min-width-0">
                <div class="fw-bold fs-4 text-white">Rp {{ number_format($balanceKasKecil, 0, ',', '.') }}</div>
                <div class="text-muted small">Saldo Buku: Kas Kecil</div>
                <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Akumulasi seluruh waktu (Kas Toko & Petty Cash)</small>
            </div>
        </div>
    </div>

    {{-- Laba Bersih --}}
    <div class="col-12 col-md-4">
        <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3" style="border-color: {{ $netProfit >= 0 ? 'rgba(52,211,153,0.25)' : 'rgba(248,113,113,0.25)' }};">
            <div class="stat-icon flex-shrink-0" style="background: {{ $netProfit >= 0 ? 'rgba(52,211,153,0.15)' : 'rgba(248,113,113,0.15)' }}; color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="min-width-0">
                <div class="fw-bold fs-4" style="color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
                    {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                </div>
                <div class="text-muted small">Laba Bersih (Net Profit) Periode Ini</div>
                <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                    Margin Laba Bersih: <strong style="color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }}">{{ $profitMargin }}%</strong>
                </small>
            </div>
        </div>
    </div>
</div>

{{-- LAPORAN LABA RUGI STATEMENT --}}
<div class="dashboard-card mb-4">
    <div class="card-header-line d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Laporan Laba Rugi Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</h3>
        <span class="badge badge-secondary p-2">Cash & Accrual Consolidated</span>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-bordered table-premium-dark align-middle mb-0">
            <thead>
                <tr>
                    <th class="py-3 px-3 fs-7 text-muted text-uppercase">Komponen Laba Rugi</th>
                    <th class="text-end py-3 px-3 fs-7 text-muted text-uppercase" style="width: 30%;">Nominal (Rupiah)</th>
                    <th class="text-end py-3 px-3 fs-7 text-muted text-uppercase" style="width: 20%;">Persentase (%)</th>
                </tr>
            </thead>
            <tbody>
                {{-- 1. PENDAPATAN --}}
                <tr class="table-active">
                    <td class="fw-bold px-3 text-white"><i class="fas fa-chevron-right me-2 text-success" style="font-size: 0.8rem;"></i> I. PENDAPATAN OPERASIONAL & PENJUALAN</td>
                    <td class="text-end px-3"></td>
                    <td class="text-end px-3"></td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">A. Penjualan Online (Escrow Bersih / Marketplace Payout)</td>
                    <td class="text-end mono ps-4 text-light">Rp {{ number_format($onlineRevenue, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($onlineRevenue / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">B. Penjualan Offline (Toko Fisik)</td>
                    <td class="text-end mono ps-4 text-light">Rp {{ number_format($offlineRevenue, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($offlineRevenue / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr class="bg-success bg-opacity-10">
                    <td class="fw-semibold ps-4 text-success">Total Pendapatan Penjualan</td>
                    <td class="text-end mono fw-bold text-success">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success">100.0%</td>
                </tr>

                {{-- 2. HPP --}}
                <tr class="table-active">
                    <td class="fw-bold px-3 text-white"><i class="fas fa-chevron-right me-2 text-danger" style="font-size: 0.8rem;"></i> II. HARGA POKOK PENJUALAN (HPP)</td>
                    <td class="text-end px-3"></td>
                    <td class="text-end px-3"></td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">A. HPP Produk Penjualan Online</td>
                    <td class="text-end mono text-danger ps-4">- Rp {{ number_format($onlineHpp, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($onlineHpp / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">B. HPP Produk Penjualan Offline</td>
                    <td class="text-end mono text-danger ps-4">- Rp {{ number_format($offlineHpp, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($offlineHpp / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr class="bg-danger bg-opacity-10">
                    <td class="fw-semibold ps-4 text-danger">Total Harga Pokok Penjualan (HPP)</td>
                    <td class="text-end mono fw-bold text-danger">- Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">
                        {{ $totalSalesRevenue > 0 ? round(($totalHpp / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- LABA KOTOR --}}
                <tr class="bg-primary bg-opacity-10">
                    <td class="fw-bold px-3 text-primary"><i class="fas fa-equals me-2"></i> III. LABA KOTOR (GROSS PROFIT)</td>
                    <td class="text-end mono fw-bold text-primary">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-primary">
                        {{ $totalSalesRevenue > 0 ? round(($grossProfit / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- 3. PEMASUKAN LAIN-LAIN --}}
                <tr class="table-active">
                    <td class="fw-bold px-3 text-white"><i class="fas fa-chevron-right me-2 text-info" style="font-size: 0.8rem;"></i> IV. PENDAPATAN & PEMASUKAN LAIN-LAIN</td>
                    <td class="text-end px-3"></td>
                    <td class="text-end px-3"></td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">A. Pemasukan Non-Penjualan (Investasi, Refund, Jasa, Lainnya)</td>
                    <td class="text-end mono text-info ps-4">Rp {{ number_format($totalOtherIncome, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($totalOtherIncome / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- 4. BIAYA OPERASIONAL --}}
                <tr class="table-active">
                    <td class="fw-bold px-3 text-white"><i class="fas fa-chevron-right me-2 text-warning" style="font-size: 0.8rem;"></i> V. PENGELUARAN & BIAYA OPERASIONAL</td>
                    <td class="text-end px-3"></td>
                    <td class="text-end px-3"></td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">A. Gaji Karyawan</td>
                    <td class="text-end mono text-muted ps-4">- Rp {{ number_format($expensesByCategory['salary'], 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($expensesByCategory['salary'] / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">B. Sewa Tempat</td>
                    <td class="text-end mono text-muted ps-4">- Rp {{ number_format($expensesByCategory['rent'], 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($expensesByCategory['rent'] / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">C. Utilitas & Operasional (Listrik, Air, Internet, Packing dll)</td>
                    <td class="text-end mono text-muted ps-4">- Rp {{ number_format($expensesByCategory['utilities'], 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($expensesByCategory['utilities'] / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">D. Lain-lain</td>
                    <td class="text-end mono text-muted ps-4">- Rp {{ number_format($expensesByCategory['other'], 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($expensesByCategory['other'] / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr class="bg-danger bg-opacity-10">
                    <td class="fw-semibold ps-4 text-danger">Total Pengeluaran & Biaya Operasional</td>
                    <td class="text-end mono fw-bold text-danger">- Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">
                        {{ $totalSalesRevenue > 0 ? round(($totalExpenses / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- LABA BERSIH (NET PROFIT) --}}
                <tr class="fs-6 fw-bold" style="border-top: 2px double rgba(255,255,255,0.15); background: {{ $netProfit >= 0 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' }};">
                    <td class="px-3" style="color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};"><i class="fas fa-chart-line me-2"></i> VI. LABA BERSIH PERIODE INI (NET PROFIT)</td>
                    <td class="text-end mono" style="font-size: 1.1rem; color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
                        {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                    </td>
                    <td class="text-end text-uppercase" style="font-size: 1.1rem; color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
                        {{ $profitMargin }}%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- DETIL DATA PENDUKUNG (INCOMES & EXPENSES) --}}
<div class="row g-4">
    {{-- Tabel Pemasukan Lain-Lain --}}
    <div class="col-lg-6">
        <div class="dashboard-card h-100">
            <div class="card-header-line">
                <h3><i class="fas fa-arrow-up text-success me-2"></i>Rincian Pemasukan Lain-Lain (Periode Ini)</h3>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered table-premium-dark align-middle mb-0" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Kas Tujuan</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($otherIncomes as $inc)
                        <tr>
                            <td class="mono">{{ $inc->income_date->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold text-white">{{ $inc->title }}</div>
                                <small class="text-muted">{{ $inc->category_label }}</small>
                            </td>
                            <td><span class="badge badge-secondary px-2 py-1">{{ $inc->payment_destination_label }}</span></td>
                            <td class="mono fw-bold text-success text-end">Rp {{ number_format($inc->amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Tidak ada pemasukan lain pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabel Pengeluaran Operasional --}}
    <div class="col-lg-6">
        <div class="dashboard-card h-100">
            <div class="card-header-line">
                <h3><i class="fas fa-arrow-down text-danger me-2"></i>Rincian Pengeluaran Operasional (Periode Ini)</h3>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered table-premium-dark align-middle mb-0" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Kas Asal</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                        <tr>
                            <td class="mono">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold text-white">{{ $exp->title }}</div>
                                <small class="text-muted">{{ $exp->category_label }}</small>
                            </td>
                            <td><span class="badge badge-secondary px-2 py-1">{{ $exp->payment_source_label }}</span></td>
                            <td class="mono fw-bold text-danger text-end">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Tidak ada pengeluaran pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
