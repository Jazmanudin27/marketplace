<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\Store;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    public function dashboard(Request $request)
    {
        $tenantId = Auth::user()?->tenant_id ?? 1;

        $totalStoresCount = Store::where('tenant_id', $tenantId)->count();
        $totalOmset = Order::where('tenant_id', $tenantId)
            ->whereMonth('order_date', now()->month)
            ->where('order_status', '!=', Order::STATUS_CANCELLED)
            ->sum('net_amount');

        $totalOrdersCount = Order::where('tenant_id', $tenantId)->count();
        $pendingOrdersCount = Order::where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->count();

        $shopeeOmset = Order::where('tenant_id', $tenantId)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%shopee%'))
            ->sum('net_amount');
        $shopeeOrdersCount = Order::where('tenant_id', $tenantId)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%shopee%'))
            ->count();

        $tiktokOmset = Order::where('tenant_id', $tenantId)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%tiktok%'))
            ->sum('net_amount');
        $tiktokOrdersCount = Order::where('tenant_id', $tenantId)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%tiktok%'))
            ->count();

        $lazadaOmset = Order::where('tenant_id', $tenantId)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%lazada%'))
            ->sum('net_amount');
        $lazadaOrdersCount = Order::where('tenant_id', $tenantId)
            ->whereHas('store.channel', fn($q) => $q->where('name', 'like', '%lazada%'))
            ->count();

        $recentOrders = Order::with('store.channel')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totalStoresCount' => $totalStoresCount,
                'totalOmset' => $totalOmset,
                'totalOrdersCount' => $totalOrdersCount,
                'pendingOrdersCount' => $pendingOrdersCount,
                'shopeeOmset' => $shopeeOmset,
                'shopeeOrdersCount' => $shopeeOrdersCount,
                'tiktokOmset' => $tiktokOmset,
                'tiktokOrdersCount' => $tiktokOrdersCount,
                'lazadaOmset' => $lazadaOmset,
                'lazadaOrdersCount' => $lazadaOrdersCount,
                'recentOrders' => $recentOrders,
            ]
        ]);
    }

    public function produk(Request $request)
    {
        $tenantId = Auth::user()?->tenant_id ?? 1;

        $query = MasterProduct::with(['category', 'brand'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(15);
        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'categories' => $categories,
                'brands' => $brands,
            ]
        ]);
    }
}
