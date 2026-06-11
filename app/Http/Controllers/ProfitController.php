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
}
