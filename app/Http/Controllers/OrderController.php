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
        // Tetap ada untuk backward compatibility, tapi kita buatkan fungsi ship() yang lebih spesifik
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        
        if ($store->channel->code === 'shopee') {
            \App\Jobs\ProcessShopeeOrder::dispatch($order);
            return back()->with('success', 'Pesanan sedang diproses ke Shopee (Job dikirim ke antrean). Refresh halaman ini beberapa saat lagi.');
        }

        return back()->with('error', 'Channel tidak didukung.');
    }

    public function ship(Order $order, \App\Services\ShopeeService $shopeeService, \App\Services\TiktokService $tiktokService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        
        if ($store->channel->code === 'shopee') {
            try {
                $shopeeService->shipOrder(
                    $store->access_token,
                    (int) $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                try {
                    $trackRes = $shopeeService->getTrackingNumber(
                        $store->access_token,
                        (int) $store->marketplace_store_id,
                        $order->order_marketplace_id
                    );
                    if (!empty($trackRes['tracking_number'])) {
                        $order->tracking_number = $trackRes['tracking_number'];
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
                
                $order->order_status = Order::STATUS_SHIPPED;
                $order->save();
                
                return back()->with('success', 'Pesanan berhasil diproses pengirimannya (Drop-off sukses) ke Shopee.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses pengiriman Shopee: ' . $e->getMessage());
            }
        } elseif ($store->channel->code === 'tiktok') {
            try {
                $tiktokService->shipOrder(
                    $store->access_token,
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                $order->order_status = Order::STATUS_SHIPPED;
                $order->save();
                
                return back()->with('success', 'Pesanan berhasil diproses pengirimannya (Drop-off sukses) ke TikTok.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses pengiriman TikTok: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Channel tidak didukung.');
    }

    public function fetchTracking(Order $order, \App\Services\ShopeeService $shopeeService, \App\Services\TiktokService $tiktokService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        
        if ($store->channel->code === 'shopee') {
            try {
                $response = $shopeeService->getTrackingNumber(
                    $store->access_token,
                    (int) $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                if (!empty($response['tracking_number'])) {
                    $order->tracking_number = $response['tracking_number'];
                    $order->save();
                    return back()->with('success', 'Resi berhasil ditarik: ' . $order->tracking_number);
                }
                
                return back()->with('error', 'Resi belum tersedia dari kurir.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal menarik resi: ' . $e->getMessage());
            }
        } elseif ($store->channel->code === 'tiktok') {
            try {
                // Di TikTok, biasanya dokumen pengiriman bisa dipanggil setelah status shipped.
                $response = $tiktokService->getShippingDocument(
                    $store->access_token,
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                if (!empty($response['doc_url'])) {
                    return redirect($response['doc_url']);
                }
                
                return back()->with('error', 'Resi TikTok belum tersedia atau tidak dikembalikan oleh API.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal menarik resi: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Channel tidak didukung.');
    }

    public function sync(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        // Ambil semua toko shopee & tiktok milik tenant ini
        $stores = \App\Models\Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['shopee', 'tiktok']);
            })->get();

        if ($stores->isEmpty()) {
            return back()->with('error', 'Anda belum mengintegrasikan toko.');
        }

        // Tarik pesanan 7 hari terakhir sebagai default
        $timeTo = time();
        $timeFrom = strtotime('-7 days', $timeTo);

        foreach ($stores as $store) {
            if ($store->channel->code === 'shopee') {
                \App\Jobs\PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);
            } elseif ($store->channel->code === 'tiktok') {
                \App\Jobs\PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
            }
        }

        return back()->with('success', 'Perintah tarik pesanan telah dikirim. Pesanan akan segera muncul dalam beberapa saat.');
    }

    public function print(Order $order, \App\Services\ShopeeService $shopeeService, \App\Services\TiktokService $tiktokService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        $order->load('items', 'store.channel');
        
        $store = $order->store;

        // Coba untuk fetch dokumen pengiriman dari marketplace API
        try {
            if ($store->channel->code === 'tiktok') {
                $response = $tiktokService->getShippingDocument(
                    $store->access_token,
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                if (!empty($response['doc_url'])) {
                    return redirect($response['doc_url']);
                }
            } elseif ($store->channel->code === 'shopee') {
                // Untuk Shopee bisa ditambahkan jika ada fungsi getShippingDocument
                // $response = $shopeeService->getShippingDocumentUrl(...);
            }
        } catch (\Exception $e) {
            // Jika error, gunakan invoice standar lokal
        }

        return view('orders.print', compact('order'));
    }
}
