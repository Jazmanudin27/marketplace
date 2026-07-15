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

        if (!$tenant) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Perusahaan tidak ditemukan. Silakan hubungi Administrator.');
        }

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

        // Pesanan mendekati/melewati batas pengiriman (deadline ≤ 24 jam dari sekarang)
        $urgentOrders = Order::with('store.channel')
                             ->where('tenant_id', $tenant->id)
                             ->deadlineUrgent()
                             ->orderBy('ship_before_date')
                             ->get();

        // Statistik per channel
        $stores = Store::with('channel')
                       ->where('tenant_id', $tenant->id)
                       ->withCount('orders')
                       ->get();

        $initialChartData = $this->buildChartData($tenant->id, 'monthly');

        // Online dropship stats
        $onlineDropshipCount = Order::where('tenant_id', $tenant->id)
                                    ->where('is_dropship', true)
                                    ->count();
        $onlineDropshipRevenue = Order::where('tenant_id', $tenant->id)
                                     ->where('is_dropship', true)
                                     ->where('order_status', '!=', Order::STATUS_CANCELLED)
                                     ->sum('net_amount');

        // Offline dropship stats
        $offlineDropshipCount = \App\Models\OfflineSale::where('tenant_id', $tenant->id)
                                    ->where('is_dropship', true)
                                    ->count();
        $offlineDropshipRevenue = \App\Models\OfflineSale::where('tenant_id', $tenant->id)
                                     ->where('is_dropship', true)
                                     ->where('status', '!=', \App\Models\OfflineSale::STATUS_CANCELLED)
                                     ->sum('grand_total');

        // Total orders (online + offline)
        $totalOnlineOrders = Order::where('tenant_id', $tenant->id)->count();
        $totalOfflineOrders = \App\Models\OfflineSale::where('tenant_id', $tenant->id)->count();
        $totalAllOrders = $totalOnlineOrders + $totalOfflineOrders;
        $totalDropshipOrders = $onlineDropshipCount + $offlineDropshipCount;
        $dropshipRatio = $totalAllOrders > 0 ? round(($totalDropshipOrders / $totalAllOrders) * 100, 1) : 0;

        // Top Cancel Reasons
        $topCancelReasons = Order::where('tenant_id', $tenant->id)
            ->where('order_status', Order::STATUS_CANCELLED)
            ->whereNotNull('cancel_reason')
            ->where('cancel_reason', '!=', '')
            ->selectRaw('cancel_reason, count(*) as count')
            ->groupBy('cancel_reason')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'totalStores', 'totalProducts', 'todayOrders', 'todayRevenue',
            'monthRevenue', 'pendingOrders', 'recentOrders', 'lowStockProducts',
            'urgentOrders', 'stores', 'initialChartData',
            'onlineDropshipCount', 'onlineDropshipRevenue',
            'offlineDropshipCount', 'offlineDropshipRevenue',
            'dropshipRatio', 'topCancelReasons'
        ));
    }

    public function getChartData(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $scope = $request->query('scope', 'monthly');

        $chartData = $this->buildChartData($tenant->id, $scope);

        return response()->json($chartData);
    }

    private function buildChartData($tenantId, $scope)
    {
        $labels = [];
        $current = [];
        $previous = [];
        $currentLabel = '';
        $previousLabel = '';

        if ($scope === 'daily') {
            $currentLabel = 'Hari Ini';
            $previousLabel = 'Kemarin';

            // 24 Hours
            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02d:00', $h);
                $current[$h] = 0;
                $previous[$h] = 0;
            }

            $todayData = Order::where('tenant_id', $tenantId)
                ->whereDate('order_date', \Carbon\Carbon::today())
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('HOUR(order_date) as hr, SUM(net_amount) as total')
                ->groupBy('hr')
                ->pluck('total', 'hr')
                ->toArray();

            $yesterdayData = Order::where('tenant_id', $tenantId)
                ->whereDate('order_date', \Carbon\Carbon::yesterday())
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('HOUR(order_date) as hr, SUM(net_amount) as total')
                ->groupBy('hr')
                ->pluck('total', 'hr')
                ->toArray();

            foreach ($todayData as $hr => $val) {
                $current[(int)$hr] = (float)$val;
            }
            foreach ($yesterdayData as $hr => $val) {
                $previous[(int)$hr] = (float)$val;
            }

            $current = array_values($current);
            $previous = array_values($previous);

        } elseif ($scope === 'yearly') {
            $currentLabel = 'Tahun Ini (' . date('Y') . ')';
            $previousLabel = 'Tahun Lalu (' . (date('Y') - 1) . ')';

            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            for ($m = 1; $m <= 12; $m++) {
                $labels[] = $monthNames[$m - 1];
                $current[$m] = 0;
                $previous[$m] = 0;
            }

            $thisYearData = Order::where('tenant_id', $tenantId)
                ->whereBetween('order_date', [
                    \Carbon\Carbon::now()->startOfYear(),
                    \Carbon\Carbon::now()->endOfYear()
                ])
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('MONTH(order_date) as mth, SUM(net_amount) as total')
                ->groupBy('mth')
                ->pluck('total', 'mth')
                ->toArray();

            $lastYearData = Order::where('tenant_id', $tenantId)
                ->whereBetween('order_date', [
                    \Carbon\Carbon::now()->subYear()->startOfYear(),
                    \Carbon\Carbon::now()->subYear()->endOfYear()
                ])
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('MONTH(order_date) as mth, SUM(net_amount) as total')
                ->groupBy('mth')
                ->pluck('total', 'mth')
                ->toArray();

            foreach ($thisYearData as $mth => $val) {
                $current[(int)$mth] = (float)$val;
            }
            foreach ($lastYearData as $mth => $val) {
                $previous[(int)$mth] = (float)$val;
            }

            $current = array_values($current);
            $previous = array_values($previous);

        } else { // monthly
            $currentLabel = 'Bulan Ini (' . \Carbon\Carbon::now()->translatedFormat('F') . ')';
            $previousLabel = 'Bulan Lalu (' . \Carbon\Carbon::now()->subMonth()->translatedFormat('F') . ')';

            $maxDays = max(
                \Carbon\Carbon::now()->daysInMonth,
                \Carbon\Carbon::now()->subMonth()->daysInMonth
            );

            for ($d = 1; $d <= $maxDays; $d++) {
                $labels[] = 'Tgl ' . $d;
                $current[$d] = 0;
                $previous[$d] = 0;
            }

            $thisMonthData = Order::where('tenant_id', $tenantId)
                ->whereBetween('order_date', [
                    \Carbon\Carbon::now()->startOfMonth(),
                    \Carbon\Carbon::now()->endOfMonth()
                ])
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('DAY(order_date) as dy, SUM(net_amount) as total')
                ->groupBy('dy')
                ->pluck('total', 'dy')
                ->toArray();

            $lastMonthData = Order::where('tenant_id', $tenantId)
                ->whereBetween('order_date', [
                    \Carbon\Carbon::now()->subMonth()->startOfMonth(),
                    \Carbon\Carbon::now()->subMonth()->endOfMonth()
                ])
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('DAY(order_date) as dy, SUM(net_amount) as total')
                ->groupBy('dy')
                ->pluck('total', 'dy')
                ->toArray();

            foreach ($thisMonthData as $dy => $val) {
                $current[(int)$dy] = (float)$val;
            }
            foreach ($lastMonthData as $dy => $val) {
                $previous[(int)$dy] = (float)$val;
            }

            $current = array_values($current);
            $previous = array_values($previous);
        }

        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previous,
            'currentLabel' => $currentLabel,
            'previousLabel' => $previousLabel,
        ];
    }
}

