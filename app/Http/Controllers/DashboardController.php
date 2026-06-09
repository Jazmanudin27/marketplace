<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;

        // Statistik ringkasan
        $totalStores        = Store::where('tenant_id', $tenant->id)->count();
        $totalProducts      = MasterProduct::where('tenant_id', $tenant->id)->count();
        $todayOrders        = Order::where('tenant_id', $tenant->id)
                                   ->whereDate('order_date', today())
                                   ->count();
        $todayRevenue       = Order::where('tenant_id', $tenant->id)
                                   ->whereDate('order_date', today())
                                   ->where('order_status', '!=', Order::STATUS_CANCELLED)
                                   ->sum('net_amount');
        $monthRevenue       = Order::where('tenant_id', $tenant->id)
                                   ->whereMonth('order_date', now()->month)
                                   ->where('order_status', '!=', Order::STATUS_CANCELLED)
                                   ->sum('net_amount');
        $pendingOrders      = Order::where('tenant_id', $tenant->id)
                                   ->where('order_status', Order::STATUS_READY_TO_SHIP)
                                   ->count();

        // Pesanan terbaru
        $recentOrders = Order::with('store.channel')
                             ->where('tenant_id', $tenant->id)
                             ->orderByDesc('order_date')
                             ->limit(10)
                             ->get();

        // Stok hampir habis
        $lowStockProducts = MasterProduct::where('tenant_id', $tenant->id)
                                         ->whereColumn('stock', '<=', 'min_stock')
                                         ->where('is_active', true)
                                         ->orderBy('stock')
                                         ->limit(8)
                                         ->get();

        // Statistik per channel
        $stores = Store::with('channel')
                       ->where('tenant_id', $tenant->id)
                       ->withCount('orders')
                       ->get();

        return view('dashboard.index', compact(
            'totalStores', 'totalProducts', 'todayOrders', 'todayRevenue',
            'monthRevenue', 'pendingOrders', 'recentOrders', 'lowStockProducts', 'stores'
        ));
    }
}
