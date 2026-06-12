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

        // ---------------------------------------------------------------
        // Validasi HMAC Signature dari Shopee
        // Base string: URL + "|" + raw_body
        // Secret key : SHOPEE_PARTNER_KEY
        // Dokumentasi: https://open.shopee.com/documents/v2/OpenAPI_BestPractice
        // ---------------------------------------------------------------
        $partnerKey = env('SHOPEE_PARTNER_KEY');
        if ($partnerKey) {
            $rawBody   = $request->getContent();
            $fullUrl   = $request->fullUrl();
            $baseStr   = $fullUrl . '|' . $rawBody;
            $signature = $request->header('Authorization') ?? '';
            $expected  = hash_hmac('sha256', $baseStr, $partnerKey);

            if (!hash_equals($expected, $signature)) {
                Log::warning('[Webhook] Shopee signature mismatch — request ditolak', [
                    'expected' => $expected,
                    'received' => $signature,
                    'url'      => $fullUrl,
                ]);
                return response()->json(['message' => 'unauthorized'], 401);
            }
        }

        $data = $request->json()->all();

        Log::info('[Webhook] Shopee payload decoded', ['code' => $data['code'] ?? null, 'shop_id' => $data['shop_id'] ?? null]);

        // Shopee sends order updates with code = 3 (bisa berupa string atau integer)
        if (isset($data['code']) && $data['code'] == 3) {
            $shopId  = $data['shop_id'] ?? null;
            $orderSn = $data['data']['ordersn'] ?? null;

            if ($shopId && $orderSn) {
                // Cari toko kita yang memiliki shop_id (marketplace_store_id) ini
                $store = Store::where('marketplace_store_id', (string) $shopId)->first();

                if (!$store) {
                    Log::warning("[Webhook] Tidak ada Store dengan marketplace_store_id: {$shopId}");
                } elseif ($store->status !== 'connected') {
                    Log::warning("[Webhook] Store {$store->name} tidak berstatus connected (status: {$store->status})");
                } else {
                    Log::info("[Webhook] Triggering sync for Store: {$store->name}, Order: {$orderSn}");

                    $timeFrom = now()->subDays(3)->timestamp;
                    $timeTo   = now()->timestamp;

                    PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);
                    Log::info("[Webhook] Job PullOrdersFromShopee dispatched untuk store {$store->name}");
                }
            } else {
                Log::warning('[Webhook] Shopee code=3 tapi shop_id atau ordersn kosong', $data);
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
