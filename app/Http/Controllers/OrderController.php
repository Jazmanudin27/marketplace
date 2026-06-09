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
}
