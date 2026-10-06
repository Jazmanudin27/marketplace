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

        // Filter Parameters for Omset Chart & Analytics (Per Tahun)
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

        // 3. Omset Chart Query (Bulanan per Tahun & Toko Terpilih)
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

        $chartQuery = Order::where('tenant_id', $tenantId)
            ->whereYear('created_at', $selectedYear);

        if (!empty($selectedStore)) {
            $chartQuery->where('store_id', $selectedStore);
        }

        $salesByMonth = (clone $chartQuery)
            ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as total_sales')
            ->groupBy('month')
            ->pluck('total_sales', 'month')
            ->toArray();

        $ordersByMonth = (clone $chartQuery)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total_orders')
            ->groupBy('month')
            ->pluck('total_orders', 'month')
            ->toArray();

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $chartLabels       = [];
        $chartSalesData    = [];
        $chartOrdersData   = [];
        $periodTotalSales  = 0;
        $periodTotalOrders = 0;

        for ($m = 1; $m <= 12; $m++) {
            $chartLabels[]     = $monthNames[$m];
            $salesVal          = (float) ($salesByMonth[$m] ?? 0);
            $ordersVal         = (int) ($ordersByMonth[$m] ?? 0);

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
