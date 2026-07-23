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

        // Tandai pesanan sebagai SUDAH DIPRINT
        Order::whereIn('id', $orders->pluck('id'))->update([
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        // Jika hanya 1 order TikTok yang dipilih, coba ambil dokumen PDF resmi dari API TikTok
        if ($orders->count() === 1) {
            $order = $orders->first();
            $store = $order->store;
            if ($store && in_array($store->channel->code ?? '', ['tiktok', 'tokopedia']) && !empty($store->access_token)) {
                try {
                    $docData = $tiktokService->getShippingDocument(
                        $store->access_token,
                        $store->marketplace_store_id ?? '',
                        $order->order_marketplace_id
                    );
                    if (!empty($docData['doc_url'])) {
                        return redirect($docData['doc_url']);
                    }
                } catch (\Exception $e) {
                    // Fallback ke template resi thermal lokal
                }
            }
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

        return view('orders.mass_print', compact('orders', 'pickList'));
    }
}
