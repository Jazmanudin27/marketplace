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

        // 2. Connected Marketplace Stores Status
        $connectedStores = Store::with('channel')
            ->where('tenant_id', $tenantId)
            ->get();

        // 3. Recent Transactions & Orders (High Density Table)
        $recentOrders = Order::with(['store.channel'])
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 4. Low Stock Warning Items
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
            'lowStockProducts'
        ));
    }
}
