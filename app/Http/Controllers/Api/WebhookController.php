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
                Log::warning('[Webhook] Shopee signature mismatch — request ditolak', [
                    'expected' => $expected,
                    'received' => $signature,
                    'url' => $fullUrl,
                ]);
                return response()->json(['message' => 'unauthorized'], 401);
            }
        }

        $data = $request->json()->all();

        Log::info('[Webhook] Shopee payload decoded', ['code' => $data['code'] ?? null, 'shop_id' => $data['shop_id'] ?? null]);

        // Shopee sends order updates with code = 3 (bisa berupa string atau integer)
        if (isset($data['code']) && $data['code'] == 3) {
            $shopId = $data['shop_id'] ?? null;
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
                    $timeTo = now()->timestamp;

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
