<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\MasterProduct;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Filter Parameters for Omset Chart & Analytics
        $selectedMonth = (int) $request->input('month', date('n'));
        $selectedYear  = (int) $request->input('year', date('Y'));
        $selectedStore = $request->input('store_id');

        // 1. Stat Summary Metrics
        $todaySales = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $todayOrders = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->count();

        $monthlySales = Order::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $thisMonth)
            ->sum('total_amount');

        $monthlyOrders = Order::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $thisMonth)
            ->count();

        $totalProducts = MasterProduct::where('tenant_id', $tenantId)->count();
        $lowStockCount = MasterProduct::where('tenant_id', $tenantId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // 2. Connected Marketplace Stores
        $connectedStores = Store::with('channel')
            ->where('tenant_id', $tenantId)
            ->get();

        // 3. Omset Chart Query (Harian per Bulan & Tahun & Toko Terpilih)
        $availableYears = Order::where('tenant_id', $tenantId)
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }
        if (!in_array((int) date('Y'), $availableYears)) {
            array_unshift($availableYears, (int) date('Y'));
        }

        $daysInMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->daysInMonth;

        $chartQuery = Order::where('tenant_id', $tenantId)
            ->whereYear('created_at', $selectedYear)
            ->whereMonth('created_at', $selectedMonth);

        if (!empty($selectedStore)) {
            $chartQuery->where('store_id', $selectedStore);
        }

        $salesByDay = (clone $chartQuery)
            ->selectRaw('DAY(created_at) as day, SUM(total_amount) as total_sales')
            ->groupBy('day')
            ->pluck('total_sales', 'day')
            ->toArray();

        $ordersByDay = (clone $chartQuery)
            ->selectRaw('DAY(created_at) as day, COUNT(*) as total_orders')
            ->groupBy('day')
            ->pluck('total_orders', 'day')
            ->toArray();

        $chartLabels     = [];
        $chartSalesData  = [];
        $chartOrdersData = [];
        $periodTotalSales  = 0;
        $periodTotalOrders = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartLabels[] = sprintf('%02d/%02d', $d, $selectedMonth);
            $salesVal  = (float) ($salesByDay[$d] ?? 0);
            $ordersVal = (int) ($ordersByDay[$d] ?? 0);

            $chartSalesData[]  = $salesVal;
            $chartOrdersData[] = $ordersVal;
            $periodTotalSales  += $salesVal;
            $periodTotalOrders += $ordersVal;
        }

        // 4. Recent Transactions & Orders (High Density Table)
        $recentOrders = Order::with(['store.channel'])
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Low Stock Warning Items
        $lowStockProducts = MasterProduct::where('tenant_id', $tenantId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        $stats = [
            'today_sales' => $todaySales,
            'today_orders' => $todayOrders,
            'monthly_sales' => $monthlySales,
            'monthly_orders' => $monthlyOrders,
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
        ];

        return view('v2.dashboard.index', compact(
            'stats',
            'connectedStores',
            'recentOrders',
            'lowStockProducts',
            'selectedMonth',
            'selectedYear',
            'selectedStore',
            'availableYears',
            'chartLabels',
            'chartSalesData',
            'chartOrdersData',
            'periodTotalSales',
            'periodTotalOrders'
        ));
    }
}
