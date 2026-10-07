@extends('v2.layouts.app')

@section('title', 'Laporan Laba Rugi V2')

@push('styles')
<style>
/* ─── Laba Rugi V2 Custom Styles ─── */
.lr-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.lr-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.lr-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.lr-kpi-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.lr-kpi-sub {
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 4px;
}
.lr-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2.2rem;
    opacity: 0.12;
    pointer-events: none;
}

.statement-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}
.statement-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 16px;
    border-bottom: 2px solid #e2e8f0;
}
.statement-table td {
    padding: 11px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.85rem;
}
.statement-header-row {
    background: #f1f5f9;
    font-weight: 700;
    color: #1e293b;
}
.statement-subtotal-row {
    background: #f8fafc;
    font-weight: 600;
    color: #334155;
}
.statement-grand-row {
    background: #eff6ff;
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e40af;
    border-top: 2px solid #3b82f6;
    border-bottom: 2px solid #3b82f6;
}
.statement-net-positive {
    background: #f0fdf4 !important;
    color: #15803d !important;
    border-top: 2px solid #22c55e !important;
    border-bottom: 2px solid #22c55e !important;
}
.statement-net-negative {
    background: #fef2f2 !important;
    color: #b91c1c !important;
    border-top: 2px solid #ef4444 !important;
    border-bottom: 2px solid #ef4444 !important;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-graph-up-arrow text-success fs-5"></i> Laporan Laba Rugi (Profit & Loss)
        </h1>
        <p class="text-muted small mb-0">Rincian pendapatan, HPP, pengeluaran operasional, dan laba bersih periode terpilih</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.laba_rugi.index') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px;" title="Refresh Data">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
        <button type="button" onclick="window.print()" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#1e293b; border:none; border-radius:8px;">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

{{-- ── Filter Form Card ── --}}
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('v2.laba_rugi.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Mulai</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                    <i class="bi bi-filter me-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('v2.laba_rugi.index', ['date_from' => \Carbon\Carbon::now()->startOfMonth()->toDateString(), 'date_to' => \Carbon\Carbon::now()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm px-3">
                    Bulan Ini
                </a>
                <a href="{{ route('v2.laba_rugi.index', ['date_from' => \Carbon\Carbon::now()->subDays(30)->toDateString(), 'date_to' => \Carbon\Carbon::now()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm px-3">
                    30 Hari Terakhir
                </a>
                <a href="{{ route('v2.laba_rugi.index', ['date_from' => \Carbon\Carbon::now()->startOfYear()->toDateString(), 'date_to' => \Carbon\Carbon::now()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm px-3">
                    Tahun Ini
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ── KPI Cards Row ── --}}
<div class="row g-3 mb-4">
    <!-- Card 1: Total Omset -->
    <div class="col-md-2.4 col-sm-6 col-12" style="width: 20%;">
        <div class="lr-kpi-card">
            <div class="lr-kpi-title">Total Omset Penjualan</div>
            <div class="lr-kpi-value text-primary">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</div>
            <div class="lr-kpi-sub">Marketplace & POS Offline</div>
            <i class="bi bi-cart-check lr-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Card 2: Total HPP -->
    <div class="col-md-2.4 col-sm-6 col-12" style="width: 20%;">
        <div class="lr-kpi-card">
            <div class="lr-kpi-title">Total HPP Modal</div>
            <div class="lr-kpi-value text-warning">Rp {{ number_format($totalHpp, 0, ',', '.') }}</div>
            <div class="lr-kpi-sub">Modal Pokok Barang Terjual</div>
            <i class="bi bi-box-seam lr-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Card 3: Laba Kotor -->
    <div class="col-md-2.4 col-sm-6 col-12" style="width: 20%;">
        <div class="lr-kpi-card">
            <div class="lr-kpi-title">Laba Kotor (Gross)</div>
            <div class="lr-kpi-value text-info">Rp {{ number_format($grossProfit, 0, ',', '.') }}</div>
            <div class="lr-kpi-sub">Margin Kotor: <strong>{{ $grossMargin }}%</strong></div>
            <i class="bi bi-pie-chart lr-kpi-icon text-info"></i>
        </div>
    </div>
    <!-- Card 4: Operasional -->
    <div class="col-md-2.4 col-sm-6 col-12" style="width: 20%;">
        <div class="lr-kpi-card">
            <div class="lr-kpi-title">Biaya Operasional</div>
            <div class="lr-kpi-value text-danger">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</div>
            <div class="lr-kpi-sub">Total Pengeluaran Rutin</div>
            <i class="bi bi-receipt lr-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Card 5: Laba Bersih -->
    <div class="col-md-2.4 col-sm-6 col-12" style="width: 20%;">
        <div class="lr-kpi-card">
            <div class="lr-kpi-title">Laba Bersih (Net Profit)</div>
            <div class="lr-kpi-value {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </div>
            <div class="lr-kpi-sub">Margin Bersih: <strong class="{{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $profitMargin }}%</strong></div>
            <i class="bi bi-graph-up-arrow lr-kpi-icon {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}"></i>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- ── Main P&L Statement Table ── --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-journal-text text-primary me-2"></i>Laporan Laba Rugi Konsolidasi
                </h6>
                <span class="badge bg-light text-dark border">
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th>Elemen Laba Rugi</th>
                            <th class="text-end">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- 1. PENDAPATAN PENJUALAN --}}
                        <tr class="statement-header-row">
                            <td colspan="2"><i class="bi bi-bag-check me-2"></i>I. PENDAPATAN PENJUALAN</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Penjualan Online Marketplace (Pencairan Bersih)</td>
                            <td class="text-end font-monospace">Rp {{ number_format($onlineRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Penjualan Offline POS (Kasir Store)</td>
                            <td class="text-end font-monospace">Rp {{ number_format($offlineRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="statement-subtotal-row">
                            <td class="ps-4 fw-bold">TOTAL PENDAPATAN PENJUALAN</td>
                            <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 2. HPP --}}
                        <tr class="statement-header-row">
                            <td colspan="2"><i class="bi bi-boxes me-2"></i>II. HARGA POKOK PENJUALAN (HPP)</td>
                        </tr>
                        <tr>
                            <td class="ps-4">HPP Modal Barang - Online Marketplace</td>
                            <td class="text-end font-monospace">Rp {{ number_format($onlineHpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">HPP Modal Barang - Offline POS</td>
                            <td class="text-end font-monospace">Rp {{ number_format($offlineHpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="statement-subtotal-row">
                            <td class="ps-4 fw-bold">TOTAL HARGA POKOK PENJUALAN (HPP)</td>
                            <td class="text-end font-monospace fw-bold text-danger">(Rp {{ number_format($totalHpp, 0, ',', '.') }})</td>
                        </tr>

                        {{-- 3. LABA KOTOR --}}
                        <tr class="statement-subtotal-row" style="background:#e0f2fe; color:#0369a1;">
                            <td class="fw-bold fs-6"><i class="bi bi-bar-chart me-2"></i>III. LABA KOTOR (GROSS PROFIT)</td>
                            <td class="text-end font-monospace fw-bold fs-6">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 4. PEMASUKAN LAIN-LAIN --}}
                        <tr class="statement-header-row">
                            <td colspan="2"><i class="bi bi-plus-circle me-2"></i>IV. PEMASUKAN LAIN-LAIN NON-OPERASIONAL</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Pendapatan / Incomes Tambahan</td>
                            <td class="text-end font-monospace">Rp {{ number_format($totalOtherIncome, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 5. BIAYA OPERASIONAL --}}
                        <tr class="statement-header-row">
                            <td colspan="2"><i class="bi bi-receipt-cutoff me-2"></i>V. BIAYA & PENGELUARAN OPERASIONAL</td>
                        </tr>
                        @forelse($expensesCategoryList as $expCat)
                            <tr>
                                <td class="ps-4">{{ $expCat['name'] }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($expCat['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="ps-4 text-muted fst-italic">Belum ada rincian pengeluaran operasional</td>
                                <td class="text-end font-monospace">Rp 0</td>
                            </tr>
                        @endforelse
                        <tr class="statement-subtotal-row">
                            <td class="ps-4 fw-bold">TOTAL BIAYA OPERASIONAL</td>
                            <td class="text-end font-monospace fw-bold text-danger">(Rp {{ number_format($totalExpenses, 0, ',', '.') }})</td>
                        </tr>

                        {{-- 6. LABA BERSIH --}}
                        <tr class="{{ $netProfit >= 0 ? 'statement-net-positive' : 'statement-net-negative' }}">
                            <td class="fw-bold fs-6 py-3 ps-3">
                                <i class="bi bi-graph-up-arrow me-2"></i>VI. LABA BERSIH PERIODE INI (NET PROFIT)
                            </td>
                            <td class="text-end font-monospace fw-bold fs-6 py-3 pe-3">
                                Rp {{ number_format($netProfit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Right Column: Cash Pools & Performance Summary ── --}}
    <div class="col-lg-4">
        <!-- Card Cash Pools -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-wallet2 text-warning me-2"></i>Saldo Kas Real-time
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-secondary fw-semibold">Saldo Kas Besar</span>
                        <i class="bi bi-bank text-primary"></i>
                    </div>
                    <div class="fs-5 fw-bold text-dark font-monospace">
                        Rp {{ number_format($balanceKasBesar, 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size: 0.7rem;">Rekening Bank & Utama</small>
                </div>

                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-secondary fw-semibold">Saldo Kas Kecil (Petty Cash)</span>
                        <i class="bi bi-cash-stack text-success"></i>
                    </div>
                    <div class="fs-5 fw-bold text-dark font-monospace">
                        Rp {{ number_format($balanceKasKecil, 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size: 0.7rem;">Operasional Harian Toko</small>
                </div>

                <div class="p-3 rounded-3 border" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-success fw-bold">Total Liquid Cash</span>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </div>
                    <div class="fs-5 fw-bold text-success font-monospace">
                        Rp {{ number_format($balanceKasBesar + $balanceKasKecil, 0, ',', '.') }}
                    </div>
                    <small class="text-success opacity-75" style="font-size: 0.7rem;">Total Dana Siap Pakai</small>
                </div>
            </div>
        </div>

        <!-- Card Quick Info -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-info me-2"></i>Informasi Rasio Keuangan
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-secondary">Gross Margin</span>
                    <span class="small fw-bold text-dark">{{ $grossMargin }}%</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-secondary">Net Margin</span>
                    <span class="small fw-bold {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">{{ $profitMargin }}%</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-secondary">Rasio HPP ke Omset</span>
                    <span class="small fw-bold text-dark">{{ $totalSalesRevenue > 0 ? round(($totalHpp / $totalSalesRevenue) * 100, 2) : 0 }}%</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="small text-secondary">Rasio Biaya Operasional</span>
                    <span class="small fw-bold text-dark">{{ $totalSalesRevenue > 0 ? round(($totalExpenses / $totalSalesRevenue) * 100, 2) : 0 }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
