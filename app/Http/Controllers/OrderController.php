<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('store.channel')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderByDesc('order_date')
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        $order->load('items.masterProduct', 'store.channel');
        return view('orders.show', compact('order'));
    }

    public function process(Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        
        if ($store->channel->code === 'shopee') {
            \App\Jobs\ProcessShopeeOrder::dispatch($order);
            return back()->with('success', 'Pesanan sedang diproses ke Shopee (Job dikirim ke antrean). Refresh halaman ini beberapa saat lagi.');
        }

        return back()->with('error', 'Channel tidak didukung.');
    }

    public function sync(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        // Ambil semua toko shopee milik tenant ini
        $stores = \App\Models\Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function($q) {
                $q->where('code', 'shopee');
            })->get();

        if ($stores->isEmpty()) {
            return back()->with('error', 'Anda belum mengintegrasikan toko Shopee.');
        }

        // Tarik pesanan 7 hari terakhir sebagai default
        $timeTo = time();
        $timeFrom = strtotime('-7 days', $timeTo);

        foreach ($stores as $store) {
            \App\Jobs\PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);
        }

        return back()->with('success', 'Perintah tarik pesanan telah dikirim. Pesanan akan segera muncul dalam beberapa saat.');
    }
}
