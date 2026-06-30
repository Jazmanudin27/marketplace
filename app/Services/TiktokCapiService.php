<?php

namespace App\Services;

use App\Models\AdsAccount;
use App\Models\Order;
use App\Models\TiktokCapiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * TikTok Conversions API (CAPI) Service
 *
 * Mengirim event Purchase ke TikTok Events API v2 secara server-side,
 * sehingga konversi tercatat meski pembeli menggunakan ad-blocker.
 *
 * Dokumentasi: https://business-api.tiktok.com/portal/docs?id=1771101027431425
 */
class TiktokCapiService
{
    const EVENTS_API_URL = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

    /**
     * Kirim Purchase event ke TikTok CAPI untuk satu order.
     * Fungsi ini idempotent — jika order sudah pernah dikirim, tidak akan dikirim ulang.
     */
    public function sendPurchaseEvent(Order $order): TiktokCapiLog
    {
        // Cek apakah sudah pernah dikirim
        if ($order->capi_sent_at !== null) {
            Log::info("TikTok CAPI: Order #{$order->id} sudah dikirim sebelumnya, skip.");
            return TiktokCapiLog::where('order_id', $order->id)
                ->where('status', TiktokCapiLog::STATUS_SENT)
                ->latest()
                ->firstOrNew([]);
        }

        // Ambil AdsAccount yang terasosiasi dengan campaign
        $campaign = $order->adsCampaign;
        if (!$campaign) {
            throw new \RuntimeException("Order #{$order->id} tidak memiliki campaign yang terasosiasi.");
        }

        $account = $campaign->adsAccount;
        if (!$account || $account->platform !== 'tiktok') {
            throw new \RuntimeException("Campaign bukan platform TikTok, skip CAPI.");
        }

        if (empty($account->pixel_id) || empty($account->events_access_token)) {
            throw new \RuntimeException(
                "TikTok CAPI belum dikonfigurasi. Isi Pixel ID dan Events Access Token di halaman Campaign."
            );
        }

        $eventId = (string) Str::uuid();

        // Buat log record dulu dengan status pending
        $capiLog = TiktokCapiLog::create([
            'tenant_id'     => $order->tenant_id,
            'order_id'      => $order->id,
            'ads_account_id'=> $account->id,
            'event_id'      => $eventId,
            'status'        => TiktokCapiLog::STATUS_PENDING,
        ]);

        try {
            $payload = $this->buildEventPayload($order, $account->pixel_id, $eventId);

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Access-Token' => $account->events_access_token,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::EVENTS_API_URL, $payload);

            $responseBody = $response->json();
            $httpStatus   = $response->status();

            if ($response->successful() && ($responseBody['code'] ?? -1) === 0) {
                // Sukses
                $capiLog->update([
                    'status'        => TiktokCapiLog::STATUS_SENT,
                    'http_status'   => $httpStatus,
                    'response_body' => $responseBody,
                    'sent_at'       => now(),
                ]);

                // Tandai order sudah dikirim
                $order->update(['capi_sent_at' => now()]);

                Log::info("TikTok CAPI: Event Purchase berhasil dikirim untuk Order #{$order->id}");
            } else {
                throw new \RuntimeException(
                    'TikTok CAPI Error: ' . ($responseBody['message'] ?? $response->body())
                );
            }
        } catch (\Throwable $e) {
            $capiLog->update([
                'status'        => TiktokCapiLog::STATUS_FAILED,
                'http_status'   => isset($httpStatus) ? $httpStatus : null,
                'response_body' => isset($responseBody) ? $responseBody : null,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("TikTok CAPI: Gagal kirim event untuk Order #{$order->id}: " . $e->getMessage());
        }

        return $capiLog;
    }

    /**
     * Kirim ulang event untuk order yang gagal sebelumnya.
     */
    public function retryFailedEvents(int $tenantId, int $limit = 50): int
    {
        $failedLogs = TiktokCapiLog::where('tenant_id', $tenantId)
            ->where('status', TiktokCapiLog::STATUS_FAILED)
            ->with('order.adsCampaign.adsAccount')
            ->limit($limit)
            ->get();

        $successCount = 0;
        foreach ($failedLogs as $log) {
            if (!$log->order) continue;
            // Reset capi_sent_at agar bisa dikirim ulang
            $log->order->update(['capi_sent_at' => null]);
            $result = $this->sendPurchaseEvent($log->order);
            if ($result->status === TiktokCapiLog::STATUS_SENT) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Build payload sesuai format TikTok Events API v2.
     */
    protected function buildEventPayload(Order $order, string $pixelId, string $eventId): array
    {
        $eventTime = $order->order_date
            ? Carbon::parse($order->order_date)->timestamp
            : now()->timestamp;

        // Hash PII dengan SHA-256 (TikTok requirement)
        $userProperties = [];

        if (!empty($order->buyer_phone)) {
            $phone = $this->normalizePhone($order->buyer_phone);
            $userProperties['phone_number'] = hash('sha256', $phone);
        }

        // TikTok mengharapkan setidaknya satu identifier
        // Gunakan kombinasi nama+alamat jika phone tidak ada
        if (empty($userProperties)) {
            $userProperties['external_id'] = hash('sha256', $order->buyer_name . $order->tenant_id);
        }

        // Properties properti (produk yang dibeli)
        $contents = [];
        if ($order->relationLoaded('items') && $order->items->count() > 0) {
            foreach ($order->items as $item) {
                $contents[] = [
                    'content_id'   => (string) ($item->marketplace_product_id ?? $item->id),
                    'content_name' => $item->product_name ?? 'Produk',
                    'quantity'     => (int) ($item->quantity ?? 1),
                    'price'        => (float) ($item->unit_price ?? 0),
                ];
            }
        } else {
            $contents[] = [
                'content_id'   => 'order_' . $order->id,
                'content_name' => 'Order #' . ($order->invoice_number ?? $order->id),
                'quantity'     => 1,
                'price'        => (float) $order->net_amount,
            ];
        }

        return [
            'pixel_code'  => $pixelId,
            'event_source'=> 'web',
            'partner_name'=> 'ERP_Marketplace',
            'data'        => [
                [
                    'event'          => 'Purchase',
                    'event_time'     => $eventTime,
                    'event_id'       => $eventId,
                    'user'           => $userProperties,
                    'properties'     => [
                        'currency'         => 'IDR',
                        'value'            => (float) $order->net_amount,
                        'content_type'     => 'product',
                        'contents'         => $contents,
                        'order_id'         => (string) ($order->invoice_number ?? $order->id),
                        'num_items'        => count($contents),
                    ],
                    'page'           => [
                        'url'  => config('app.url'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Normalisasi nomor telepon ke format E.164 (tanpa +).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        // Indonesia: ganti 08 atau 628 dengan 628
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}
