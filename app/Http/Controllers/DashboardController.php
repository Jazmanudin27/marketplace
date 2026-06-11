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

        // Data Grafik 30 Hari Terakhir
        $thirtyDaysAgo = \Carbon\Carbon::now()->subDays(29)->startOfDay();
        $dailyData = Order::where('tenant_id', $tenant->id)
            ->where('order_date', '>=', $thirtyDaysAgo)
            ->where('order_status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('DATE(order_date) as date, SUM(total_amount) as gross_total, SUM(net_amount) as net_total')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date')
            ->toArray();

        // Isi tanggal yang kosong
        $chartDates = [];
        $chartGross = [];
        $chartNet = [];
        for ($i = 0; $i < 30; $i++) {
            $dateObj = \Carbon\Carbon::now()->subDays(29 - $i);
            $dateString = $dateObj->format('Y-m-d');
            $chartDates[] = $dateObj->format('d M');
            $chartGross[] = $dailyData[$dateString]['gross_total'] ?? 0;
            $chartNet[] = $dailyData[$dateString]['net_total'] ?? 0;
        }

        return view('dashboard.index', compact(
            'totalStores', 'totalProducts', 'todayOrders', 'todayRevenue',
            'monthRevenue', 'pendingOrders', 'recentOrders', 'lowStockProducts', 'stores',
            'chartDates', 'chartGross', 'chartNet'
        ));
    }
}
