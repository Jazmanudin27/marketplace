<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
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
            return redirect()->route('login')->with('error', 'Perusahaan tidak ditemukan.');
        }

        $totalStoresCount = Store::where('tenant_id', $tenant->id)->count();
        $totalOmset = Order::where('tenant_id', $tenant->id)
            ->whereMonth('order_date', now()->month)
            ->where('order_status', '!=', Order::STATUS_CANCELLED)
            ->sum('net_amount');

        $totalOrdersCount = Order::where('tenant_id', $tenant->id)->count();
        $pendingOrdersCount = Order::where('tenant_id', $tenant->id)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->count();

        $shopeeOmset = Order::where('tenant_id', $tenant->id)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%shopee%'))
            ->sum('net_amount');
        $shopeeOrdersCount = Order::where('tenant_id', $tenant->id)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%shopee%'))
            ->count();

        $tiktokOmset = Order::where('tenant_id', $tenant->id)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%tiktok%'))
            ->sum('net_amount');
        $tiktokOrdersCount = Order::where('tenant_id', $tenant->id)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%tiktok%'))
            ->count();

        $lazadaOmset = Order::where('tenant_id', $tenant->id)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%lazada%'))
            ->sum('net_amount');
        $lazadaOrdersCount = Order::where('tenant_id', $tenant->id)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%lazada%'))
            ->count();

        $recentOrders = Order::with('store.channel')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('v2.dashboard.index', compact(
            'totalStoresCount',
            'totalOmset',
            'totalOrdersCount',
            'pendingOrdersCount',
            'shopeeOmset',
            'shopeeOrdersCount',
            'tiktokOmset',
            'tiktokOrdersCount',
            'lazadaOmset',
            'lazadaOrdersCount',
            'recentOrders'
        ));
    }
}
