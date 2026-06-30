<?php

namespace App\Services;

use App\Models\AdsAccount;
use App\Models\TiktokAudience;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiktokAudienceService
{
    const BASE_URL = 'https://business-api.tiktok.com/open_api/v1.3';

    /**
     * Membuat custom audience baru di TikTok Ads Manager
     */
    public function createAudience(TiktokAudience $audience): string
    {
        $account = $audience->adsAccount;
        if (!$account || empty($account->events_access_token) || empty($account->advertiser_id)) {
            throw new \RuntimeException("TikTok credentials (Events Access Token atau Advertiser ID) belum dikonfigurasi.");
        }

        $url = self::BASE_URL . '/dmp/custom_audience/create/';

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Access-Token' => $account->events_access_token,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'advertiser_id' => $account->advertiser_id,
                'custom_audience_name' => $audience->name,
                'calculate_type' => 'MANUAL',
            ]);

        $resData = $response->json();

        if ($response->successful() && ($resData['code'] ?? -1) === 0) {
            $audienceId = $resData['data']['custom_audience_id'] ?? null;
            if (!$audienceId) {
                throw new \RuntimeException("TikTok API tidak mengembalikan custom_audience_id");
            }
            return (string) $audienceId;
        } else {
            throw new \RuntimeException(
                "Gagal membuat audience di TikTok: " . ($resData['message'] ?? $response->body())
            );
        }
    }

    /**
     * Sync data pembeli ke TikTok Custom Audience
     */
    public function syncAudience(TiktokAudience $audience): bool
    {
        $account = $audience->adsAccount;
        if (!$account || empty($account->events_access_token) || empty($account->advertiser_id)) {
            throw new \RuntimeException("TikTok credentials belum dikonfigurasi.");
        }

        // 1. Buat audience di TikTok jika belum ada ID-nya
        if (empty($audience->tiktok_audience_id)) {
            try {
                $tiktokId = $this->createAudience($audience);
                $audience->update([
                    'tiktok_audience_id' => $tiktokId,
                    'status' => TiktokAudience::STATUS_UPLOADING,
                ]);
            } catch (\Throwable $e) {
                $audience->update([
                    'status' => TiktokAudience::STATUS_FAILED,
                    'error_message' => "Gagal inisiasi audience: " . $e->getMessage(),
                ]);
                Log::error("TikTok Custom Audience Sync Error: " . $e->getMessage());
                return false;
            }
        }

        // 2. Ambil data pembeli berdasarkan tipe audience
        $query = Order::where('tenant_id', $audience->tenant_id)
            ->whereNotNull('buyer_phone');

        if ($audience->type === TiktokAudience::TYPE_HIGH_VALUE) {
            // Nilai total order >= Rp 500.000
            $query->where('net_amount', '>=', 500000);
        }

        $orders = $query->get(['buyer_phone', 'buyer_name']);

        if ($orders->isEmpty()) {
            $audience->update([
                'status' => TiktokAudience::STATUS_ACTIVE,
                'last_synced_at' => now(),
                'customer_count' => 0,
                'error_message' => 'Tidak ada data pembeli untuk di-upload.',
            ]);
            return true;
        }

        // 3. Format dan hash data pembeli
        $userList = [];
        foreach ($orders as $order) {
            $phone = $this->normalizePhone($order->buyer_phone);
            if (!empty($phone)) {
                $userList[] = [
                    'id_type' => 'PHONE_SHA256',
                    'id_value' => hash('sha256', $phone),
                ];
            }
        }

        // Buat data unique
        $userList = array_values(array_unique($userList, SORT_REGULAR));

        // Pecah menjadi beberapa chunk karena TikTok membatasi ukuran batch
        $chunks = array_chunk($userList, 1000);
        $totalUploaded = 0;

        $url = self::BASE_URL . '/dmp/custom_audience/user/update/';

        foreach ($chunks as $chunk) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Access-Token' => $account->events_access_token,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, [
                        'advertiser_id' => $account->advertiser_id,
                        'custom_audience_id' => $audience->tiktok_audience_id,
                        'action' => 'ADD',
                        'user_list' => $chunk,
                    ]);

                $resData = $response->json();

                if (!$response->successful() || ($resData['code'] ?? -1) !== 0) {
                    throw new \RuntimeException(
                        "Gagal upload batch data: " . ($resData['message'] ?? $response->body())
                    );
                }

                $totalUploaded += count($chunk);
            } catch (\Throwable $e) {
                $audience->update([
                    'status' => TiktokAudience::STATUS_FAILED,
                    'error_message' => "Gagal upload data: " . $e->getMessage(),
                ]);
                Log::error("TikTok DMP Upload Error: " . $e->getMessage());
                return false;
            }
        }

        // 4. Update status sukses
        $audience->update([
            'status' => TiktokAudience::STATUS_ACTIVE,
            'customer_count' => $totalUploaded,
            'last_synced_at' => now(),
            'error_message' => null,
        ]);

        return true;
    }

    /**
     * Sinkronisasi semua custom audience aktif untuk tenant
     */
    public function syncAll(int $tenantId): int
    {
        $audiences = TiktokAudience::where('tenant_id', $tenantId)->get();
        $successCount = 0;

        foreach ($audiences as $audience) {
            if ($this->syncAudience($audience)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Normalisasi nomor telepon ke format E.164 (tanpa +).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}
