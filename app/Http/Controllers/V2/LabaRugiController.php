<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\Expense;
use App\Models\Income;
use App\Models\FundTransfer;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LabaRugiController extends Controller
{
    /**
     * Display V2 Laba Rugi (Profit & Loss) dashboard & report.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Date Filter (Default: awal bulan s/d hari ini)
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', Carbon::now()->toDateString());

        // 1. ONLINE SALES (MARKETPLACE)
        $onlineOrders = Order::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('order_status', ['CANCELLED'])
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $onlineRevenue = (float) $onlineOrders->sum('net_amount');
        $onlineGrossSales = (float) $onlineOrders->sum('total_amount');
        $onlineHpp = 0.0;
        foreach ($onlineOrders as $order) {
            $onlineHpp += (float) $order->hpp_total;
        }

        // 2. OFFLINE SALES (POS)
        $offlineSales = OfflineSale::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->where('status', OfflineSale::STATUS_COMPLETED)
            ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $offlineRevenue = (float) $offlineSales->sum('grand_total');
        $offlineHpp = 0.0;
        foreach ($offlineSales as $sale) {
            $offlineHpp += (float) $sale->hpp_total;
        }

        // 3. OTHER INCOMES
        $otherIncomes = Income::where('tenant_id', $tenantId)
            ->whereBetween('income_date', [$dateFrom, $dateTo])
            ->get();

        $totalOtherIncome = (float) $otherIncomes->sum('amount');

        // TOTAL OMSET & LABA KOTOR
        $totalSalesRevenue = $onlineRevenue + $offlineRevenue;
        $totalHpp = $onlineHpp + $offlineHpp;
        $grossProfit = $totalSalesRevenue - $totalHpp;
        $grossMargin = $totalSalesRevenue > 0 ? round(($grossProfit / $totalSalesRevenue) * 100, 2) : 0;

        // 4. OPERATING EXPENSES
        $expenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->get();

        $allExpenseCats = FinanceCategory::where('tenant_id', $tenantId)->expense()->get();
        if ($allExpenseCats->isEmpty()) {
            FinanceCategory::seedDefaultsForTenant($tenantId);
            $allExpenseCats = FinanceCategory::where('tenant_id', $tenantId)->expense()->get();
        }

        $groupedExpenses = $expenses->groupBy('category');
        $expensesCategoryList = [];
        foreach ($allExpenseCats as $cat) {
            $amt = (float) ($groupedExpenses[$cat->code] ?? collect())->sum('amount');
            $expensesCategoryList[] = [
                'code' => $cat->code,
                'name' => $cat->name,
                'amount' => $amt,
            ];
        }

        // Dynamic categories missing from master
        $defaultLabels = [
            'salary'               => 'Gaji Karyawan',
            'rent'                 => 'Sewa Tempat',
            'utilities'            => 'Utilitas & Operasional',
            'pembelian_supplier'   => 'Bayar Hutang Supplier',
            'other'                => 'Lain-lain',
        ];
        foreach ($groupedExpenses as $catCode => $group) {
            $found = false;
            foreach ($expensesCategoryList as $existing) {
                if ($existing['code'] === $catCode) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $catName = $defaultLabels[$catCode] ?? ucwords(str_replace('_', ' ', $catCode));
                $expensesCategoryList[] = [
                    'code'   => $catCode,
                    'name'   => $catName,
                    'amount' => (float) $group->sum('amount'),
                ];
            }
        }

        $totalExpenses = (float) $expenses->sum('amount');

        // 5. NET PROFIT (LABA BERSIH)
        $netProfit = $grossProfit + $totalOtherIncome - $totalExpenses;
        $netRevenueWithOther = $totalSalesRevenue + $totalOtherIncome;
        $profitMargin = $netRevenueWithOther > 0 ? round(($netProfit / $netRevenueWithOther) * 100, 2) : 0;

        // 6. CASH POOLS
        $cumIncomesKasBesar = (float) Income::where('tenant_id', $tenantId)->where('payment_destination', 'kas_besar')->sum('amount');
        $cumIncomesKasKecil = (float) Income::where('tenant_id', $tenantId)->where('payment_destination', 'kas_kecil')->sum('amount');

        $cumExpensesKasBesar = (float) Expense::where('tenant_id', $tenantId)->where('payment_source', 'kas_besar')->sum('amount');
        $cumExpensesKasKecil = (float) Expense::where('tenant_id', $tenantId)->where('payment_source', 'kas_kecil')->sum('amount');

        $cumTransfersToBesar = (float) FundTransfer::where('tenant_id', $tenantId)->where('destination', 'kas_besar')->sum('amount');
        $cumTransfersFromBesar = (float) FundTransfer::where('tenant_id', $tenantId)->where('source', 'kas_besar')->sum('amount');

        $cumTransfersToKecil = (float) FundTransfer::where('tenant_id', $tenantId)->where('destination', 'kas_kecil')->sum('amount');
        $cumTransfersFromKecil = (float) FundTransfer::where('tenant_id', $tenantId)->where('source', 'kas_kecil')->sum('amount');

        $balanceKasBesar = ($cumIncomesKasBesar + $cumTransfersToBesar) - ($cumExpensesKasBesar + $cumTransfersFromBesar);
        $balanceKasKecil = ($cumIncomesKasKecil + $cumTransfersToKecil) - ($cumExpensesKasKecil + $cumTransfersFromKecil);

        return view('v2.laba_rugi.index', compact(
            'dateFrom',
            'dateTo',
            'onlineOrders',
            'onlineRevenue',
            'onlineGrossSales',
            'onlineHpp',
            'offlineSales',
            'offlineRevenue',
            'offlineHpp',
            'otherIncomes',
            'totalOtherIncome',
            'totalSalesRevenue',
            'totalHpp',
            'grossProfit',
            'grossMargin',
            'expensesCategoryList',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'balanceKasBesar',
            'balanceKasKecil'
        ));
    }
}
