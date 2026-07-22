<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Auth;

class OrderPrintController extends Controller
{
    public function massPrint(Request $request, ShopeeService $shopeeService, TiktokService $tiktokService)
    {
        $orderIds = $request->input('order_ids', $request->input('ids', []));
        
        if (empty($orderIds)) {
            return back()->with('error', 'Pilih setidaknya satu pesanan untuk dicetak.');
        }

        $orders = Order::whereIn('id', $orderIds)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->with('items.masterProduct', 'store.channel', 'customer')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Generate Pick List data (Summary of all items to pick)
        $pickList = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $sku = $item->sku ?? 'NO-SKU';
                if (!isset($pickList[$sku])) {
                    $pickList[$sku] = [
                        'name' => $item->product_name,
                        'qty' => 0
                    ];
                }
                $pickList[$sku]['qty'] += $item->quantity;
            }
        }

        // Untuk mencetak massal, kita akan render view HTML yang berisi Pick List 
        // di halaman pertama, diikuti oleh rincian/AWB pesanan di halaman berikutnya
        // dengan CSS page-break.
        
        return view('orders.mass_print', compact('orders', 'pickList'));
    }
}
