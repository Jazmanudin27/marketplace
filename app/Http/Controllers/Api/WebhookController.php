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
            $rawBody = $request->getContent();
            $fullUrl = $request->fullUrl();
            $baseStr = $fullUrl . '|' . $rawBody;
            $signature = $request->header('Authorization') ?? '';
            $expected = hash_hmac('sha256', $baseStr, $partnerKey);

            if (!hash_equals($expected, $signature)) {
                // Toleransi reverse-proxy SSL (Nginx / Cloudflare): coba validasi dengan skema https jika fullUrl mendeteksi http
                $altUrl = str_starts_with($fullUrl, 'http://')
                    ? preg_replace('/^http:/i', 'https:', $fullUrl)
                    : preg_replace('/^https:/i', 'http:', $fullUrl);
                $expectedAlt = hash_hmac('sha256', $altUrl . '|' . $rawBody, $partnerKey);

                if (!hash_equals($expectedAlt, $signature)) {
                    Log::warning('[Webhook] Shopee signature mismatch — request ditolak', [
                        'expected' => $expected,
                        'received' => $signature,
                        'url' => $fullUrl,
                    ]);
                    return response()->json(['message' => 'unauthorized'], 401);
                }
            }
        }

        $data = $request->json()->all();

        Log::info('[Webhook] Shopee payload decoded', ['code' => $data['code'] ?? null, 'shop_id' => $data['shop_id'] ?? null]);

        // Shopee sends order updates with code = 3 (status), code = 4 (tracking/logistics), and code = 29 (return/refund)
        if (isset($data['code']) && in_array((int)$data['code'], [3, 4, 29])) {
            $shopId = $data['shop_id'] ?? null;
            $orderSn = $data['data']['ordersn'] ?? null;

            if ($shopId && $orderSn) {
                // Cari toko kita yang memiliki shop_id (marketplace_store_id) ini
                $store = Store::where('marketplace_store_id', (string) $shopId)->first();

                if (!$store) {
                    Log::warning("[Webhook] Tidak ada Store dengan marketplace_store_id: {$shopId}");
                } elseif ($store->status !== 'connected') {
                    Log::warning("[Webhook] Store {$store->store_name} tidak berstatus connected (status: {$store->status})");
                } else {
                    Log::info("[Webhook] Triggering sync for Store: {$store->store_name}, Order: {$orderSn} (Event Code: {$data['code']})");

                    // 1. Jika Event Code 4 (Tracking No Update), Shopee biasanya menyertakan data resi langsung
                    $trackingNo = $data['data']['tracking_no'] ?? $data['data']['tracking_number'] ?? null;
                    if (!empty($trackingNo) && !(str_starts_with($trackingNo, 'PSG') || str_starts_with($trackingNo, 'psg'))) {
                        $existingOrder = Order::where('tenant_id', $store->tenant_id)
                            ->where('order_marketplace_id', $orderSn)
                            ->first();
                        if ($existingOrder) {
                            $existingOrder->tracking_number = $trackingNo;
                            $existingOrder->save();
                            Log::info("[Webhook] Resi {$trackingNo} langsung di-update ke Order {$orderSn} dari payload Code 4");
                        }
                    }

                    $timeFrom = now()->subDays(3)->timestamp;
                    $timeTo = now()->timestamp;

                    // 2. Eksekusi sinkronisasi langsung (dispatchSync) agar realtime tanpa tertunda di antrean queue
                    try {
                        PullOrdersFromShopee::dispatchSync($store, $timeFrom, $timeTo, false, $orderSn);
                        Log::info("[Webhook] Job PullOrdersFromShopee dispatchSync selesai untuk store {$store->store_name} dengan order_sn {$orderSn}");
                    } catch (\Throwable $e) {
                        Log::error("[Webhook] Gagal dispatchSync PullOrdersFromShopee: " . $e->getMessage() . " — beralih ke antrean queue");
                        PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo, false, $orderSn);
                    }
                }
            } else {
                Log::warning('[Webhook] Shopee code=' . $data['code'] . ' tapi shop_id atau ordersn kosong', $data);
            }
        }

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
        $orderId = $data['data']['order_id'] ?? null;

        if ($shopId) {
            $store = Store::where('marketplace_store_id', (string) $shopId)->first();

            if ($store && $store->status === 'connected') {
                Log::info("[Webhook] Triggering sync for TikTok Store: {$store->store_name}, Type: {$type}, Order: {$orderId}");

                // Trigger sinkronisasi pesanan dari 1 hari terakhir
                // untuk memastikan pesanan yang menyebabkan event ini tertarik ke database ERP
                $timeFrom = now()->subDays(1)->timestamp;
                $timeTo = now()->timestamp;

                try {
                    \App\Jobs\PullOrdersFromTiktok::dispatchSync($store, $timeFrom, $timeTo, false, $orderId);
                    Log::info("[Webhook] Job PullOrdersFromTiktok dispatchSync selesai untuk store {$store->store_name} dengan order_id {$orderId}");
                } catch (\Throwable $e) {
                    Log::error("[Webhook] Gagal dispatchSync PullOrdersFromTiktok: " . $e->getMessage() . " — beralih ke antrean queue");
                    \App\Jobs\PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo, false, $orderId);
                }
            }
        }

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Handle TikTok Lead Generation push notification
     */
    public function tiktokLeads(Request $request)
    {
        Log::info('[Webhook] Received TikTok Lead push notification', $request->all());

        // TikTok Lead Generation Webhook Payload
        $advertiserId = $request->input('advertiser_id');
        if (!$advertiserId) {
            $advertiserId = $request->input('data.advertiser_id');
        }

        if (!$advertiserId) {
            Log::warning('[Webhook] TikTok Lead: advertiser_id tidak ditemukan di payload.');
            return response()->json(['message' => 'invalid_payload'], 400);
        }

        // Cari AdsAccount dengan advertiser_id ini untuk mendapatkan tenant_id
        $account = \App\Models\AdsAccount::where('advertiser_id', $advertiserId)->first();
        if (!$account) {
            Log::warning("[Webhook] TikTok Lead: Tidak ada akun iklan ERP dengan Advertiser ID {$advertiserId}");
            return response()->json(['message' => 'account_not_mapped'], 404);
        }

        $tenantId = $account->tenant_id;

        // Ambil data form
        $name = '';
        $phone = '';
        $email = '';
        $notes = [];

        // Parsing lead form data
        $formData = $request->input('lead_form_data') ?: $request->input('data.lead_form_data') ?: [];

        foreach ($formData as $field) {
            $key = strtolower($field['key'] ?? '');
            $val = $field['value'] ?? '';

            if (str_contains($key, 'name') || str_contains($key, 'nama')) {
                $name = $val;
            } elseif (str_contains($key, 'phone') || str_contains($key, 'telp') || str_contains($key, 'handphone') || str_contains($key, 'wa')) {
                $phone = $val;
            } elseif (str_contains($key, 'email') || str_contains($key, 'surel')) {
                $email = $val;
            } else {
                $notes[] = ($field['key'] ?? '') . ': ' . $val;
            }
        }

        // Fallback jika format berbeda
        if (empty($name)) $name = $request->input('name') ?: $request->input('data.name') ?: 'TikTok Lead';
        if (empty($phone)) $phone = $request->input('phone') ?: $request->input('data.phone') ?: '';
        if (empty($email)) $email = $request->input('email') ?: $request->input('data.email') ?: '';

        if (empty($phone)) {
            Log::warning('[Webhook] TikTok Lead: No telepon kosong, abaikan lead.');
            return response()->json(['message' => 'phone_required'], 400);
        }

        // Normalisasi nomor telepon
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        // Cek/buat Customer baru di database
        $customer = \App\Models\Customer::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'phone' => $phone
            ],
            [
                'name' => $name,
                'tags' => 'tiktok_lead',
                'address' => implode(', ', $notes) ?: 'Berasal dari TikTok Lead Ad',
            ]
        );

        Log::info("[Webhook] TikTok Lead disimpan sebagai Customer #{$customer->id}");

        // Kirim WhatsApp notifikasi jika dikonfigurasi di .env
        $recipient = env('WHATSAPP_ALERT_RECIPIENT');
        if ($recipient) {
            $campaignName = $request->input('campaign_name') ?: $request->input('data.campaign_name') ?: 'Unknown Campaign';
            $waMessage = "⚡ *NEW TIKTOK ADS LEAD* ⚡\n\n"
                . "Ada prospek baru yang mengisi formulir iklan Anda!\n\n"
                . "Nama: *" . $name . "*\n"
                . "WA: *" . $phone . "*\n"
                . "Email: *" . ($email ?: '—') . "*\n"
                . "Campaign: *" . $campaignName . "*\n"
                . "Keterangan: " . ($customer->address) . "\n\n"
                . "Segera hubungi leads ini via WhatsApp untuk follow-up!";

            try {
                \App\Services\WhatsAppService::send($recipient, $waMessage);
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim WA notifikasi lead baru: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'success', 'customer_id' => $customer->id], 200);
    }
}
