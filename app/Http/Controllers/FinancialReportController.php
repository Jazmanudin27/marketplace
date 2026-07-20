<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\Expense;
use App\Models\Income;
use App\Models\FundTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filter tanggal default: 30 hari terakhir
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());

        // ==========================================
        // 1. REVENUE (PENDAPATAN)
        // ==========================================
        
        // A. Online Sales (Marketplace)
        $onlineOrders = Order::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('order_status', ['CANCELLED'])
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $onlineRevenue = (float) $onlineOrders->sum('net_amount'); // Pencairan Bersih
        $onlineHpp = 0.0;
        foreach ($onlineOrders as $order) {
            $onlineHpp += $order->hpp_total;
        }

        // B. Offline Sales
        $offlineSales = OfflineSale::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->where('status', OfflineSale::STATUS_COMPLETED)
            ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $offlineRevenue = (float) $offlineSales->sum('grand_total');
        $offlineHpp = 0.0;
        foreach ($offlineSales as $sale) {
            $offlineHpp += $sale->hpp_total;
        }

        // C. Pemasukan Lain-Lain
        $otherIncomes = Income::where('tenant_id', $tenantId)
            ->whereBetween('income_date', [$dateFrom, $dateTo])
            ->get();

        $totalOtherIncome = (float) $otherIncomes->sum('amount');

        // Total Pendapatan & HPP
        $totalSalesRevenue = $onlineRevenue + $offlineRevenue;
        $totalHpp = $onlineHpp + $offlineHpp;
        $grossProfit = $totalSalesRevenue - $totalHpp;

        // ==========================================
        // 2. OPERATING EXPENSES (PENGELUARAN OPERASIONAL)
        // ==========================================
        $expenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->get();

        $expensesByCategory = [
            'salary' => (float) $expenses->where('category', 'salary')->sum('amount'),
            'rent' => (float) $expenses->where('category', 'rent')->sum('amount'),
            'utilities' => (float) $expenses->where('category', 'utilities')->sum('amount'),
            'pembelian_supplier' => (float) $expenses->where('category', 'pembelian_supplier')->sum('amount'),
            'other' => (float) $expenses->where('category', 'other')->sum('amount'),
        ];

        $totalExpenses = (float) $expenses->sum('amount');

        // ==========================================
        // 3. NET PROFIT (LABA BERSIH)
        // ==========================================
        $netProfit = $grossProfit + $totalOtherIncome - $totalExpenses;

        // Margin Persentase
        $netRevenueWithOther = $totalSalesRevenue + $totalOtherIncome;
        $profitMargin = $netRevenueWithOther > 0 ? round(($netProfit / $netRevenueWithOther) * 100, 2) : 0;

        // ==========================================
        // 4. CASH POOLS BALANCES (AKUMULASI CUMULATIVE)
        // ==========================================
        // Hitung saldo Kas Besar & Kas Kecil secara kumulatif (seluruh waktu)
        
        // Pemasukan & Pengeluaran
        $cumIncomesKasBesar = (float) Income::where('tenant_id', $tenantId)->where('payment_destination', 'kas_besar')->sum('amount');
        $cumIncomesKasKecil = (float) Income::where('tenant_id', $tenantId)->where('payment_destination', 'kas_kecil')->sum('amount');

        $cumExpensesKasBesar = (float) Expense::where('tenant_id', $tenantId)->where('payment_source', 'kas_besar')->sum('amount');
        $cumExpensesKasKecil = (float) Expense::where('tenant_id', $tenantId)->where('payment_source', 'kas_kecil')->sum('amount');

        // Transfer dana
        $cumTransfersToBesar = (float) FundTransfer::where('tenant_id', $tenantId)->where('destination', 'kas_besar')->sum('amount');
        $cumTransfersFromBesar = (float) FundTransfer::where('tenant_id', $tenantId)->where('source', 'kas_besar')->sum('amount');

        $cumTransfersToKecil = (float) FundTransfer::where('tenant_id', $tenantId)->where('destination', 'kas_kecil')->sum('amount');
        $cumTransfersFromKecil = (float) FundTransfer::where('tenant_id', $tenantId)->where('source', 'kas_kecil')->sum('amount');

        // Penjualan Online (masuk ke Kas Besar / Rekening Bank Utama)
        $cumOnlineSales = (float) Order::where('tenant_id', $tenantId)->where('order_status', 'COMPLETED')->sum('net_amount');

        // Penjualan Offline:
        // Cash (tunai) masuk ke Kas Kecil, non-cash (transfer, qris, kartu) masuk ke Kas Besar
        $cumOfflineSalesTunai = (float) OfflineSale::where('tenant_id', $tenantId)->where('status', OfflineSale::STATUS_COMPLETED)->where('payment_method', 'tunai')->sum('grand_total');
        $cumOfflineSalesNonTunai = (float) OfflineSale::where('tenant_id', $tenantId)->where('status', OfflineSale::STATUS_COMPLETED)->whereIn('payment_method', ['transfer', 'qris', 'kartu'])->sum('grand_total');

        // Saldo akhir
        $balanceKasBesar = $cumIncomesKasBesar + $cumOnlineSales + $cumOfflineSalesNonTunai + $cumTransfersToBesar - $cumExpensesKasBesar - $cumTransfersFromBesar;
        $balanceKasKecil = $cumIncomesKasKecil + $cumOfflineSalesTunai + $cumTransfersToKecil - $cumExpensesKasKecil - $cumTransfersFromKecil;

        return view('finance.profit_loss', compact(
            'dateFrom',
            'dateTo',
            'onlineRevenue',
            'onlineHpp',
            'offlineRevenue',
            'offlineHpp',
            'totalOtherIncome',
            'totalSalesRevenue',
            'totalHpp',
            'grossProfit',
            'expensesByCategory',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'balanceKasBesar',
            'balanceKasKecil',
            'otherIncomes',
            'expenses'
        ));
    }
}
