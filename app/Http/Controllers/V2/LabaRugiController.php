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

        // 1. ONLINE SALES (MARKETPLACE - PENJUALAN DILEPAS / SELESAI)
        // Diselaraskan dengan Laporan Penjualan Dilepas (Filter status COMPLETED/SELESAI & tanggal pencairan/completed_at)
        $onlineOrders = Order::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR'])
            ->where(function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                  ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                      $subQ->whereNull('completed_at')
                           ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                  });
            })
            ->get();

        $onlineRevenue = (float) $onlineOrders->sum('net_amount');
        $onlineGrossSales = (float) $onlineOrders->sum('total_amount');
        $onlineRefunds = 0.0;
        $onlineMarketplaceFee = 0.0;
        $onlineHpp = 0.0;
        foreach ($onlineOrders as $order) {
            $onlineRefunds += (float) $order->refund_amount;
            if ($order->recon_status === 'RECONCILED') {
                $onlineMarketplaceFee += abs((float) $order->getRawOriginal('marketplace_fee'));
            } else {
                $absFee = (float) $order->marketplace_fee;
                if ($absFee <= 0 && !empty($order->fee_breakdown_details['total_fee'])) {
                    $absFee = abs((float) $order->fee_breakdown_details['total_fee']);
                }
                $onlineMarketplaceFee += $absFee;
            }
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

        // 3. OTHER INCOMES - Ditiadakan (Hanya Penjualan Produk & Biaya Operasional)
        $otherIncomes = collect();
        $totalOtherIncome = 0.0;

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

        // Filter kategori pengeluaran modal/HPP yang tidak boleh masuk biaya operasional (double counting)
        $excludedExpenseCodes = ['pembelian_supplier', 'bahan_baku', 'biaya_produksi', 'supplier_payable'];

        $operatingExpenses = $expenses->filter(function ($exp) use ($excludedExpenseCodes) {
            return !in_array(strtolower($exp->category), $excludedExpenseCodes);
        });

        // 1b. BREAKDOWN PER TOKO MARKETPLACE (UNTUK DRILLDOWN / ACCORDION)
        $storeBreakdown = [];
        $ordersByStore = $onlineOrders->groupBy('store_id');
        foreach ($ordersByStore as $sId => $sOrders) {
            $firstOrd = $sOrders->first();
            $sName = $firstOrd->store->store_name ?? ('Toko #' . $sId);
            $cName = $firstOrd->store->channel->name ?? 'Marketplace';
            $sGross = (float) $sOrders->sum('total_amount');
            $sNet = (float) $sOrders->sum('net_amount');
            $sRefund = 0.0;
            $sFee = 0.0;
            $sHpp = 0.0;
            foreach ($sOrders as $ord) {
                $sRefund += (float) $ord->refund_amount;
                if ($ord->recon_status === 'RECONCILED') {
                    $sFee += abs((float) $ord->getRawOriginal('marketplace_fee'));
                } else {
                    $absFee = (float) $ord->marketplace_fee;
                    if ($absFee <= 0 && !empty($ord->fee_breakdown_details['total_fee'])) {
                        $absFee = abs((float) $ord->fee_breakdown_details['total_fee']);
                    }
                    $sFee += $absFee;
                }
                $sHpp += (float) $ord->hpp_total;
            }
            $storeBreakdown[] = [
                'store_id' => $sId,
                'store_name' => $sName,
                'channel_name' => $cName,
                'orders_count' => $sOrders->count(),
                'gross_sales' => $sGross,
                'refund_amount' => $sRefund,
                'marketplace_fee' => $sFee,
                'net_revenue' => $sNet > 0 ? $sNet : $sGross,
                'hpp' => $sHpp,
            ];
        }
        usort($storeBreakdown, fn($a, $b) => $b['net_revenue'] <=> $a['net_revenue']);

        $groupedExpenses = $operatingExpenses->groupBy('category');
        $expensesCategoryList = [];
        foreach ($allExpenseCats as $cat) {
            if (in_array(strtolower($cat->code), $excludedExpenseCodes)) {
                continue;
            }
            $catItems = ($groupedExpenses[$cat->code] ?? collect());
            $amt = (float) $catItems->sum('amount');
            $detailItems = $catItems->map(function($exp) {
                return [
                    'id' => $exp->id,
                    'date' => $exp->expense_date,
                    'title' => $exp->title ?: ($exp->description ?: 'Pengeluaran'),
                    'amount' => (float)$exp->amount,
                    'payment_source' => $exp->payment_source,
                    'notes' => $exp->description ?: '',
                ];
            })->sortByDesc('date')->values()->all();

            $expensesCategoryList[] = [
                'code' => $cat->code,
                'name' => $cat->name,
                'amount' => $amt,
                'items' => $detailItems,
            ];
        }

        // Dynamic categories missing from master
        $defaultLabels = [
            'salary'               => 'Gaji Karyawan',
            'rent'                 => 'Sewa Tempat',
            'utilities'            => 'Utilitas & Operasional',
            'other'                => 'Lain-lain',
        ];
        foreach ($groupedExpenses as $catCode => $group) {
            if (in_array(strtolower($catCode), $excludedExpenseCodes)) {
                continue;
            }
            $found = false;
            foreach ($expensesCategoryList as $existing) {
                if ($existing['code'] === $catCode) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $catName = $defaultLabels[$catCode] ?? ucwords(str_replace('_', ' ', $catCode));
                $detailItems = $group->map(function($exp) {
                    return [
                        'id' => $exp->id,
                        'date' => $exp->expense_date,
                        'title' => $exp->title ?: ($exp->description ?: 'Pengeluaran'),
                        'amount' => (float)$exp->amount,
                        'payment_source' => $exp->payment_source,
                        'notes' => $exp->description ?: '',
                    ];
                })->sortByDesc('date')->values()->all();

                $expensesCategoryList[] = [
                    'code'   => $catCode,
                    'name'   => $catName,
                    'amount' => (float) $group->sum('amount'),
                    'items'  => $detailItems,
                ];
            }
        }

        $totalExpenses = (float) $operatingExpenses->sum('amount');

        // 5. NET PROFIT (LABA BERSIH) MURNI
        $netProfit = $grossProfit - $totalExpenses;
        $netRevenueWithOther = $totalSalesRevenue;
        $profitMargin = $totalSalesRevenue > 0 ? round(($netProfit / $totalSalesRevenue) * 100, 2) : 0;

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
            'onlineRefunds',
            'onlineMarketplaceFee',
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
            'balanceKasKecil',
            'storeBreakdown'
        ));
    }
}
