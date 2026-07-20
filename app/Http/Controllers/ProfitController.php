<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfitController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filter tanggal default: 30 hari terakhir
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());
        $storeId  = $request->get('store_id');
        $status   = $request->get('status', 'COMPLETED');

        // Ambil semua toko milik tenant untuk dropdown filter
        $stores = Store::where('tenant_id', $tenantId)->with('channel')->get();

        // Query pesanan online dengan filter
        $query = Order::with('items.masterProduct', 'store.channel')
            ->where('tenant_id', $tenantId)
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($status && $status !== 'ALL') {
            $query->where('order_status', $status);
        } else {
            $query->whereNotIn('order_status', ['CANCELLED']);
        }

        $orders = $query->orderByDesc('order_date')->paginate(15)->withQueryString();

        // 1. ONLINE (Marketplace) Aggregates
        $onlineQuery = Order::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($storeId) {
            $onlineQuery->where('store_id', $storeId);
        }
        if ($status && $status !== 'ALL') {
            $onlineQuery->where('order_status', $status);
        } else {
            $onlineQuery->whereNotIn('order_status', ['CANCELLED']);
        }

        $allOnline = $onlineQuery->get();

        $onlineOmzet    = (float) $allOnline->sum('total_amount');
        $onlineFee      = (float) $allOnline->sum('marketplace_fee');
        $onlineDiscount = (float) $allOnline->sum('discount_amount');
        $onlineNet      = (float) $allOnline->sum('net_amount');
        $onlineHpp      = (float) $allOnline->sum('hpp_total');
        $onlineProfit   = (float) $allOnline->sum('net_profit');
        $onlineCount    = $allOnline->count();

        // 2. OFFLINE (Physical Store) Aggregates
        // Offline sales are only included if we select "Semua Toko" (no storeId filter)
        $offlineOmzet    = 0.0;
        $offlineDiscount = 0.0;
        $offlineNet      = 0.0;
        $offlineHpp      = 0.0;
        $offlineProfit   = 0.0;
        $offlineCount    = 0;
        $allOffline      = collect();

        if (!$storeId) {
            $offlineQuery = \App\Models\OfflineSale::with('items.masterProduct')
                ->where('tenant_id', $tenantId)
                ->where('status', \App\Models\OfflineSale::STATUS_COMPLETED)
                ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

            $allOffline = $offlineQuery->get();

            $offlineOmzet    = (float) $allOffline->sum('total_amount');
            $offlineDiscount = (float) $allOffline->sum('discount_amount');
            $offlineNet      = (float) $allOffline->sum('grand_total');
            $offlineHpp      = (float) $allOffline->sum('hpp_total');
            $offlineProfit   = (float) $allOffline->sum('net_profit');
            $offlineCount    = $allOffline->count();
        }

        // 3. CONSOLIDATED Totals (Laba Rugi Gabungan)
        $totalRevenue = $onlineNet + $offlineNet; // Net Payout / Cash-in received
        $totalHpp     = $onlineHpp + $offlineHpp;
        $totalProfit  = $onlineProfit + $offlineProfit;
        $totalCount   = $onlineCount + $offlineCount;
        $avgMargin    = $totalRevenue > 0
            ? round(($totalProfit / $totalRevenue) * 100, 2)
            : 0;

        // 3b. OPERATIONAL & NON-HPP EXPENSES (Deductions)
        $expenseQuery = \App\Models\Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo);

        $totalExpenses = (float) $expenseQuery->sum('amount');
        
        // Grouping needs to re-fetch or use query clone
        $expensesByCategory = \App\Models\Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();

        $payrollQuery = \App\Models\Payroll::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);
            
        $totalPayroll = (float) $payrollQuery->sum('net_salary');

        $adSpendQuery = \App\Models\AdsPerformanceLog::where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo);
            
        $totalAdSpend = (float) $adSpendQuery->sum('ad_spend');

        $totalDeductions = $totalExpenses + $totalPayroll + $totalAdSpend;
        $realNetProfit = $totalProfit - $totalDeductions;
        $realNetMargin = $totalRevenue > 0
            ? round(($realNetProfit / $totalRevenue) * 100, 2)
            : 0;

        $expensesBreakdown = [
            'salary_payroll' => $totalPayroll,
            'ad_spend' => $totalAdSpend,
            'expense_salary' => $expensesByCategory['salary'] ?? 0.0,
            'expense_rent' => $expensesByCategory['rent'] ?? 0.0,
            'expense_utilities' => $expensesByCategory['utilities'] ?? 0.0,
            'expense_supplier' => $expensesByCategory['pembelian_supplier'] ?? 0.0,
            'expense_other' => $expensesByCategory['other'] ?? 0.0,
        ];

        // 4. DROPSHIP specific aggregates
        $dropshipOnline = $allOnline->where('is_dropship', true);
        $dropshipOffline = $allOffline->where('is_dropship', true);

        $dsOnlineNet = (float) $dropshipOnline->sum('net_amount');
        $dsOnlineHpp = (float) $dropshipOnline->sum('hpp_total');
        $dsOnlineProfit = (float) $dropshipOnline->sum('net_profit');
        $dsOnlineCount = $dropshipOnline->count();

        $dsOfflineNet = (float) $dropshipOffline->sum('grand_total');
        $dsOfflineHpp = (float) $dropshipOffline->sum('hpp_total');
        $dsOfflineProfit = (float) $dropshipOffline->sum('net_profit');
        $dsOfflineCount = $dropshipOffline->count();

        $totalDsRevenue = $dsOnlineNet + $dsOfflineNet;
        $totalDsHpp = $dsOnlineHpp + $dsOfflineHpp;
        $totalDsProfit = $dsOnlineProfit + $dsOfflineProfit;
        $totalDsCount = $dsOnlineCount + $dsOfflineCount;
        $dsAvgMargin = $totalDsRevenue > 0 ? round(($totalDsProfit / $totalDsRevenue) * 100, 2) : 0;

        // 5. REGULAR specific aggregates (Non-dropship)
        $regOnline = $allOnline->where('is_dropship', false);
        $regOffline = $allOffline->where('is_dropship', false);

        $regOnlineNet = (float) $regOnline->sum('net_amount');
        $regOnlineHpp = (float) $regOnline->sum('hpp_total');
        $regOnlineProfit = (float) $regOnline->sum('net_profit');
        $regOnlineCount = $regOnline->count();

        $regOfflineNet = (float) $regOffline->sum('grand_total');
        $regOfflineHpp = (float) $regOffline->sum('hpp_total');
        $regOfflineProfit = (float) $regOffline->sum('net_profit');
        $regOfflineCount = $regOffline->count();

        $totalRegRevenue = $regOnlineNet + $regOfflineNet;
        $totalRegHpp = $regOnlineHpp + $regOfflineHpp;
        $totalRegProfit = $regOnlineProfit + $regOfflineProfit;
        $totalRegCount = $regOnlineCount + $regOfflineCount;
        $regAvgMargin = $totalRegRevenue > 0 ? round(($totalRegProfit / $totalRegRevenue) * 100, 2) : 0;

        return view('profit.index', compact(
            'orders',
            'stores',
            'dateFrom',
            'dateTo',
            'storeId',
            'status',
            
            // Consolidated
            'totalRevenue',
            'totalHpp',
            'totalProfit',
            'totalCount',
            'avgMargin',

            // Net Profit Deductions
            'totalExpenses',
            'totalPayroll',
            'totalAdSpend',
            'totalDeductions',
            'realNetProfit',
            'realNetMargin',
            'expensesBreakdown',

            // Dropship segments
            'totalDsRevenue',
            'totalDsHpp',
            'totalDsProfit',
            'totalDsCount',
            'dsAvgMargin',

            // Regular segments
            'totalRegRevenue',
            'totalRegHpp',
            'totalRegProfit',
            'totalRegCount',
            'regAvgMargin',
 
            // Online specific
            'onlineOmzet',
            'onlineFee',
            'onlineDiscount',
            'onlineNet',
            'onlineHpp',
            'onlineProfit',
            'onlineCount',
 
            // Offline specific
            'offlineOmzet',
            'offlineDiscount',
            'offlineNet',
            'offlineHpp',
            'offlineProfit',
            'offlineCount',
            'allOffline'
        ));
    }

    public function marginReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Default filters
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());
        $storeId  = $request->get('store_id');

        // Stores filter dropdown
        $stores = Store::where('tenant_id', $tenantId)->with('channel')->get();

        // 1. Get Online Order Items
        $onlineQuery = \App\Models\OrderItem::whereHas('order', function($q) use ($tenantId, $dateFrom, $dateTo, $storeId) {
            $q->where('tenant_id', $tenantId)
              ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
              ->whereNotIn('order_status', ['CANCELLED']);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        })->with(['order.store.channel', 'masterProduct']);

        $onlineItems = $onlineQuery->get();

        // 2. Get Offline Sale Items
        $offlineQuery = \App\Models\OfflineSaleItem::whereHas('offlineSale', function($q) use ($tenantId, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)
              ->where('status', \App\Models\OfflineSale::STATUS_COMPLETED)
              ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        })->with(['offlineSale', 'masterProduct']);

        $offlineItems = $storeId ? collect() : $offlineQuery->get();

        // 3. Group by master_product_id
        $productMetrics = [];

        // Process Online
        foreach ($onlineItems as $item) {
            $prodId = $item->master_product_id;
            if (!$prodId) continue;

            if (!isset($productMetrics[$prodId])) {
                $productMetrics[$prodId] = [
                    'name' => $item->masterProduct ? $item->masterProduct->name : $item->product_name,
                    'sku' => $item->masterProduct ? $item->masterProduct->sku : $item->sku,
                    'qty_sold' => 0,
                    'gross_sales' => 0.0,
                    'allocated_fees' => 0.0,
                    'net_payout' => 0.0,
                    'actual_hpp' => 0.0,
                    'current_hpp' => $item->masterProduct ? (float)$item->masterProduct->cost_price : 0.0,
                ];
            }

            // Proportional Fee Allocation
            $orderTotal = (float) $item->order->total_amount;
            $orderFee = (float) $item->order->marketplace_fee;
            $itemTotal = (float) $item->total_price;
            
            $allocatedFee = 0.0;
            if ($orderTotal > 0) {
                $allocatedFee = ($itemTotal / $orderTotal) * $orderFee;
            }

            $productMetrics[$prodId]['qty_sold'] += $item->quantity;
            $productMetrics[$prodId]['gross_sales'] += $itemTotal;
            $productMetrics[$prodId]['allocated_fees'] += $allocatedFee;
            $productMetrics[$prodId]['actual_hpp'] += $item->hpp_subtotal > 0 
                ? (float)$item->hpp_subtotal 
                : ($item->masterProduct ? (float)$item->masterProduct->cost_price * $item->quantity : 0.0);
        }

        // Process Offline
        foreach ($offlineItems as $item) {
            $prodId = $item->master_product_id;
            if (!$prodId) continue;

            if (!isset($productMetrics[$prodId])) {
                $productMetrics[$prodId] = [
                    'name' => $item->masterProduct ? $item->masterProduct->name : $item->product_name,
                    'sku' => $item->masterProduct ? $item->masterProduct->sku : $item->sku,
                    'qty_sold' => 0,
                    'gross_sales' => 0.0,
                    'allocated_fees' => 0.0,
                    'net_payout' => 0.0,
                    'actual_hpp' => 0.0,
                    'current_hpp' => $item->masterProduct ? (float)$item->masterProduct->cost_price : 0.0,
                ];
            }

            $itemTotal = (float) $item->subtotal;
            $currentHpp = $item->masterProduct ? (float)$item->masterProduct->cost_price : 0.0;
            $calculatedHpp = $currentHpp * $item->quantity;

            $productMetrics[$prodId]['qty_sold'] += $item->quantity;
            $productMetrics[$prodId]['gross_sales'] += $itemTotal;
            $productMetrics[$prodId]['actual_hpp'] += $calculatedHpp;
        }

        // Calculate Final Metrics
        foreach ($productMetrics as $prodId => &$m) {
            $m['net_payout'] = $m['gross_sales'] - $m['allocated_fees'];
            $m['net_profit'] = $m['net_payout'] - $m['actual_hpp'];
            $m['margin_pct'] = $m['net_payout'] > 0 
                ? round(($m['net_profit'] / $m['net_payout']) * 100, 2) 
                : 0.0;
        }
        unset($m);

        // Sort by gross sales descending
        uasort($productMetrics, function($a, $b) {
            return $b['gross_sales'] <=> $a['gross_sales'];
        });

        return view('profit.margin', compact('productMetrics', 'stores', 'dateFrom', 'dateTo', 'storeId'));
    }
}
