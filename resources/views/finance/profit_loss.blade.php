@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi & Buku Kas')

@section('content')
{{-- Filter Tanggal --}}
<div class="dashboard-card mb-4">
    <form method="GET" action="{{ route('finance.profit_loss') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;">
        <div style="flex:1; min-width:180px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm form-control-dark">
        </div>
        <div style="flex:1; min-width:180px;">
            <label style="font-size:0.72rem; color:var(--text-secondary); display:block; margin-bottom:.25rem;">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm form-control-dark">
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter me-1"></i> Saring Laporan
            </button>
            <a href="{{ route('finance.profit_loss') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-undo me-1"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- Row Ringkasan Saldo Buku Kas & Net Profit --}}
<div class="row g-4 mb-4">
    {{-- Saldo Kas Besar --}}
    <div class="col-md-4">
        <div class="stat-card stat-success" style="height: 100%;">
            <div class="stat-icon"><i class="fas fa-university"></i></div>
            <div class="stat-body">
                <div class="stat-value">Rp {{ number_format($balanceKasBesar, 0, ',', '.') }}</div>
                <div class="stat-label">Saldo Buku: Kas Besar</div>
                <small class="text-muted mt-1 display-block" style="font-size:0.7rem;">Akumulasi seluruh waktu (Tunai Bank & Utama)</small>
            </div>
            <div class="stat-glow"></div>
        </div>
    </div>

    {{-- Saldo Kas Kecil --}}
    <div class="col-md-4">
        <div class="stat-card stat-warning" style="height: 100%;">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-body">
                <div class="stat-value">Rp {{ number_format($balanceKasKecil, 0, ',', '.') }}</div>
                <div class="stat-label">Saldo Buku: Kas Kecil</div>
                <small class="text-muted mt-1 display-block" style="font-size:0.7rem;">Akumulasi seluruh waktu (Kas Toko & Petty Cash)</small>
            </div>
            <div class="stat-glow"></div>
        </div>
    </div>

    {{-- Laba Bersih --}}
    <div class="col-md-4">
        <div class="stat-card {{ $netProfit >= 0 ? 'stat-primary' : 'stat-danger' }}" style="height: 100%;">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-body">
                <div class="stat-value" style="color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
                    {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                </div>
                <div class="stat-label">Laba Bersih (Net Profit) Periode Ini</div>
                <small class="text-muted mt-1 display-block" style="font-size:0.7rem;">
                    Margin Laba Bersih: <strong style="color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }}">{{ $profitMargin }}%</strong>
                </small>
            </div>
            <div class="stat-glow"></div>
        </div>
    </div>
</div>

{{-- LAPORAN LABA RUGI STATEMENT --}}
<div class="dashboard-card mb-4">
    <div class="card-header-line">
        <h3><i class="fas fa-file-invoice-dollar text-primary"></i> Laporan Laba Rugi Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</h3>
        <span class="badge bg-secondary p-2">Cash & Accrual Consolidated</span>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-hover table-bordered border-secondary mb-0" style="color: var(--text-primary); border-color: rgba(255,255,255,0.06) !important;">
            <thead style="background: rgba(255, 255, 255, 0.03);">
                <tr>
                    <th class="py-3 px-3 fs-7" style="color: var(--text-muted);">KOMPONEN LABA RUGI</th>
                    <th class="text-end py-3 px-3 fs-7" style="width: 30%; color: var(--text-muted);">NOMINAL (RUPIAH)</th>
                    <th class="text-end py-3 px-3 fs-7" style="width: 20%; color: var(--text-muted);">PERSENTASE (%)</th>
                </tr>
            </thead>
            <tbody>
                {{-- 1. PENDAPATAN --}}
                <tr class="table-active" style="background: rgba(255, 255, 255, 0.02);">
                    <td class="fw-bold px-3"><i class="fas fa-chevron-right me-2 text-success" style="font-size: 0.8rem;"></i> I. PENDAPATAN OPERASIONAL & PENJUALAN</td>
                    <td class="text-end px-3"></td>
                    <td class="text-end px-3"></td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">A. Penjualan Online (Escrow Bersih / Marketplace Payout)</td>
                    <td class="text-end mono ps-4">Rp {{ number_format($onlineRevenue, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($onlineRevenue / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted">B. Penjualan Offline (Toko Fisik)</td>
                    <td class="text-end mono ps-4">Rp {{ number_format($offlineRevenue, 0, ',', '.') }}</td>
                    <td class="text-end text-muted">
                        {{ $totalSalesRevenue > 0 ? round(($offlineRevenue / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>
                <tr style="background: rgba(16, 185, 129, 0.02);">
                    <td class="fw-semibold ps-4 text-success">Total Pendapatan Penjualan</td>
                    <td class="text-end mono fw-bold text-success">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success">100.0%</td>
                </tr>

                {{-- 2. HPP --}}
                <tr class="table-active" style="background: rgba(255, 255, 255, 0.02);">
                    <td class="fw-bold px-3"><i class="fas fa-chevron-right me-2 text-danger" style="font-size: 0.8rem;"></i> II. HARGA POKOK PENJUALAN (HPP)</td>
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
                <tr style="background: rgba(239, 68, 68, 0.02);">
                    <td class="fw-semibold ps-4 text-danger">Total Harga Pokok Penjualan (HPP)</td>
                    <td class="text-end mono fw-bold text-danger">- Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">
                        {{ $totalSalesRevenue > 0 ? round(($totalHpp / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- LABA KOTOR --}}
                <tr class="table-active" style="background: rgba(99, 102, 241, 0.08);">
                    <td class="fw-bold px-3 text-primary"><i class="fas fa-equals me-2"></i> III. LABA KOTOR (GROSS PROFIT)</td>
                    <td class="text-end mono fw-bold text-primary">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-primary">
                        {{ $totalSalesRevenue > 0 ? round(($grossProfit / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- 3. PEMASUKAN LAIN-LAIN --}}
                <tr class="table-active" style="background: rgba(255, 255, 255, 0.02);">
                    <td class="fw-bold px-3"><i class="fas fa-chevron-right me-2 text-info" style="font-size: 0.8rem;"></i> IV. PENDAPATAN & PEMASUKAN LAIN-LAIN</td>
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
                <tr class="table-active" style="background: rgba(255, 255, 255, 0.02);">
                    <td class="fw-bold px-3"><i class="fas fa-chevron-right me-2 text-warning" style="font-size: 0.8rem;"></i> V. PENGELUARAN & BIAYA OPERASIONAL</td>
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
                <tr style="background: rgba(239, 68, 68, 0.02);">
                    <td class="fw-semibold ps-4 text-danger">Total Pengeluaran & Biaya Operasional</td>
                    <td class="text-end mono fw-bold text-danger">- Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">
                        {{ $totalSalesRevenue > 0 ? round(($totalExpenses / $totalSalesRevenue) * 100, 1) : 0 }}%
                    </td>
                </tr>

                {{-- LABA BERSIH (NET PROFIT) --}}
                <tr class="fs-6" style="border-top: 2px double var(--border); background: {{ $netProfit >= 0 ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }};">
                    <td class="fw-bold px-3" style="color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};"><i class="fas fa-chart-line me-2"></i> VI. LABA BERSIH PERIODE INI (NET PROFIT)</td>
                    <td class="text-end mono fw-bold" style="font-size: 1.1rem; color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
                        {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                    </td>
                    <td class="text-end fw-bold" style="font-size: 1.1rem; color: {{ $netProfit >= 0 ? '#34d399' : '#f87171' }};">
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
        <div class="dashboard-card" style="height: 100%;">
            <div class="card-header-line">
                <h3><i class="fas fa-arrow-up text-success"></i> Rincian Pemasukan Lain-Lain (Periode Ini)</h3>
            </div>
            <div class="table-wrapper mt-2">
                <table class="data-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Kas Tujuan</th>
                            <th style="text-align:right;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($otherIncomes as $inc)
                        <tr>
                            <td class="mono">{{ $inc->income_date->format('d/m/Y') }}</td>
                            <td>
                                <strong>{{ $inc->title }}</strong><br>
                                <small class="text-muted">{{ $inc->category_label }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $inc->payment_destination_label }}</span></td>
                            <td class="mono fw-bold text-success" style="text-align:right;">Rp {{ number_format($inc->amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding:1.5rem;">Tidak ada pemasukan lain pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabel Pengeluaran Operasional --}}
    <div class="col-lg-6">
        <div class="dashboard-card" style="height: 100%;">
            <div class="card-header-line">
                <h3><i class="fas fa-arrow-down text-danger"></i> Rincian Pengeluaran Operasional (Periode Ini)</h3>
            </div>
            <div class="table-wrapper mt-2">
                <table class="data-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Kas Asal</th>
                            <th style="text-align:right;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                        <tr>
                            <td class="mono">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td>
                                <strong>{{ $exp->title }}</strong><br>
                                <small class="text-muted">{{ $exp->category_label }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $exp->payment_source_label }}</span></td>
                            <td class="mono fw-bold text-danger" style="text-align:right;">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding:1.5rem;">Tidak ada pengeluaran pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
