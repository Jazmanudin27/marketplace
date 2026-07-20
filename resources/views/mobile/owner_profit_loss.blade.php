@extends('layouts.mobile')

@section('title', 'Laporan Laba Rugi')
@section('header-title', 'Laba Rugi')

@section('styles')
<style>
    body {
        background-color: #f8fafc !important;
    }

    .report-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .report-header {
        background-color: #fafafa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 14px 16px;
    }

    .report-body {
        padding: 16px;
    }

    .section-title {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #475569;
        margin-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }

    .financial-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        font-size: 0.85rem;
    }

    .financial-row.total-row {
        border-top: 1px dashed #e2e8f0;
        font-weight: 700;
        color: #0f172a;
        padding: 12px 0 6px 0;
        margin-top: 4px;
    }

    .net-profit-card {
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 20px;
        text-align: center;
        border: 1px solid rgba(79, 70, 229, 0.1);
    }
</style>
@endsection

@section('content')
    <!-- Date Filter Form -->
    <div class="report-card p-3 mb-3">
        <form action="{{ route('mobile.owner.profit_loss') }}" method="GET" class="m-0">
            <div class="row g-2 align-items-center">
                <div class="col-5">
                    <label class="form-label text-muted mb-1" style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;">Dari</label>
                    <input type="date" name="date_from" class="form-control form-control-sm rounded-2" 
                           value="{{ $dateFrom }}" style="font-size: 0.8rem;">
                </div>
                <div class="col-5">
                    <label class="form-label text-muted mb-1" style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;">Sampai</label>
                    <input type="date" name="date_to" class="form-control form-control-sm rounded-2" 
                           value="{{ $dateTo }}" style="font-size: 0.8rem;">
                </div>
                <div class="col-2 text-end">
                    <label class="form-label text-transparent mb-1 d-block" style="font-size: 0.65rem;">Submit</label>
                    <button type="submit" class="btn btn-primary btn-sm rounded-2 w-100" style="padding: 5px 0;">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Net Profit Card Summary -->
    <div class="net-profit-card">
        <span class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.68rem; letter-spacing: 0.5px; color: #4f46e5 !important;">LABA BERSIH BERSAMA</span>
        <h3 class="fw-bold my-1 text-primary" style="font-size: 1.5rem;">
            Rp {{ number_format($netProfit, 0, ',', '.') }}
        </h3>
        <span class="badge bg-primary text-white py-1.5 px-3 rounded-pill mt-1" style="font-size: 0.7rem; font-weight: 700;">
            Margin: {{ $profitMargin }}%
        </span>
    </div>

    <!-- Detailed Profit & Loss Report -->
    <div class="report-card">
        <div class="report-header">
            <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-primary"></i> Rincian Laba Rugi
            </h6>
        </div>
        
        <div class="report-body">
            <!-- 1. PENDAPATAN (REVENUE) -->
            <div class="mb-4">
                <h6 class="section-title">1. Pendapatan (Revenue)</h6>
                <div class="financial-row">
                    <span class="text-muted">Penjualan Online (Marketplace)</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($onlineRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">Penjualan Offline / Grosir</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($offlineRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">Pemasukan Lain-Lain</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($totalOtherIncome, 0, ',', '.') }}</span>
                </div>
                <div class="financial-row total-row">
                    <span>Total Pendapatan</span>
                    <span class="text-success">Rp {{ number_format($totalSalesRevenue + $totalOtherIncome, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- 2. HPP (COST OF GOODS SOLD) -->
            <div class="mb-4">
                <h6 class="section-title">2. Harga Pokok Penjualan (HPP)</h6>
                <div class="financial-row">
                    <span class="text-muted">HPP Pesanan Online</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($onlineHpp, 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">HPP Pesanan Offline</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($offlineHpp, 0, ',', '.') }}</span>
                </div>
                <div class="financial-row total-row">
                    <span>Total HPP</span>
                    <span class="text-danger">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- 3. LABA KOTOR (GROSS PROFIT) -->
            <div class="mb-4 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark" style="font-size: 0.85rem;">Laba Kotor (Gross Profit)</span>
                <span class="fw-bold text-success" style="font-size: 0.95rem;">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
            </div>

            <!-- 4. OPERATIONAL EXPENSES -->
            <div class="mb-2">
                <h6 class="section-title">4. Pengeluaran Operasional (Beban)</h6>
                <div class="financial-row">
                    <span class="text-muted">Beban Gaji Karyawan</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($expensesByCategory['salary'], 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">Beban Sewa & Tempat</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($expensesByCategory['rent'], 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">Beban Utilitas & Operasional</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($expensesByCategory['utilities'], 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">Pembayaran Hutang Supplier</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($expensesByCategory['pembelian_supplier'], 0, ',', '.') }}</span>
                </div>
                <div class="financial-row">
                    <span class="text-muted">Beban Lain-Lain</span>
                    <span class="text-dark fw-medium">Rp {{ number_format($expensesByCategory['other'], 0, ',', '.') }}</span>
                </div>
                <div class="financial-row total-row">
                    <span>Total Beban Operasional</span>
                    <span class="text-danger">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
