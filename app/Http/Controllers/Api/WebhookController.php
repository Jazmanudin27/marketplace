<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Jobs\PullOrdersFromShopee;
use App\Models\Order;

class WebhookController extends Controller
{
    /**
     * Handle Shopee push notifications (Webhooks)
     */
    public function shopee(Request $request)
    {
        Log::info('[Webhook] Received Shopee push notification', $request->all());

        // Validasi payload dan partner ID
        $data = $request->json()->all();

        // Shopee sends order updates with code = 3
        if (isset($data['code']) && $data['code'] === 3) {
            $shopId = $data['shop_id'] ?? null;
            $orderSn = $data['data']['ordersn'] ?? null;

            if ($shopId && $orderSn) {
                // Cari toko kita yang memiliki shop_id (marketplace_store_id) ini
                $store = Store::where('marketplace_store_id', (string) $shopId)->first();

                if ($store && $store->status === 'connected') {
                    Log::info("[Webhook] Triggering sync for Store: {$store->name}, Order: {$orderSn}");

                    // Kita bisa langsung dispatch PullOrdersFromShopee untuk order tersebut.
                    // Karena PullOrdersFromShopee biasanya menerima time range, 
                    // kita bisa menarik pesanan 1 hari kebelakang sampai sekarang.
                    // Atau kita bisa melakukan logic khusus untuk order ini saja jika kita buat method-nya.
                    // Untuk saat ini, mari trigger sinkronisasi 3 hari terakhir (cukup ringan)
                    $timeFrom = now()->subDays(3)->timestamp;
                    $timeTo = now()->timestamp;

                    PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);
                }
            }
        }

        // Untuk webhook Shopee B2C Returns (code = 4) dll bisa ditambahkan di sini.

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Handle TikTok push notifications (Webhooks)
     */
    public function tiktok(Request $request)
    {
        Log::info('[Webhook] Received TikTok push notification', $request->all());

        $data = $request->json()->all();

        // TikTok Shop API v2 Webhook Payload Structure
        // Usually contains 'type', 'shop_id', 'data'
        $type = $data['type'] ?? null;
        $shopId = $data['shop_id'] ?? null;

        if ($shopId) {
            $store = Store::where('marketplace_store_id', (string) $shopId)->first();

            if ($store && $store->status === 'connected') {
                Log::info("[Webhook] Triggering sync for TikTok Store: {$store->name}, Type: {$type}");

                // Trigger sinkronisasi pesanan dari 1 hari terakhir
                // untuk memastikan pesanan yang menyebabkan event ini tertarik ke database ERP
                $timeFrom = now()->subDays(1)->timestamp;
                $timeTo = now()->timestamp;

                \App\Jobs\PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
            }
        }

        return response()->json(['message' => 'success'], 200);
    }
}
