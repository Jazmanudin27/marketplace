<?php

namespace App\Services;

use App\Models\AdsCampaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiktokAdsApiService
{
    /**
     * Pause atau aktifkan campaign di TikTok Ads Manager via API
     */
    public function updateCampaignStatus(AdsCampaign $campaign, string $status): bool
    {
        $account = $campaign->adsAccount;
        if (!$account || $account->platform !== 'tiktok') {
            Log::warning("[TikTok API] Gagal update status: campaign #{$campaign->id} bukan platform TikTok.");
            return false;
        }

        // Gunakan access_token / developer token yang ada di ads_accounts
        $token = $account->access_token ?: $account->events_access_token;
        $advertiserId = $account->account_id ?: $account->advertiser_id;

        if (empty($token) || empty($advertiserId) || empty($campaign->campaign_id_platform)) {
            Log::warning("[TikTok API] Gagal update status: Credentials atau Platform ID kosong untuk campaign #{$campaign->id}.");
            return false;
        }

        // Status mapping: TikTok menerima 'enable' atau 'disable'
        $optStatus = strtolower($status) === 'active' ? 'enable' : 'disable';

        try {
            $url = 'https://business-api.tiktok.com/open_api/v1.3/campaign/status/update/';
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'advertiser_id' => $advertiserId,
                    'campaign_ids' => [$campaign->campaign_id_platform],
                    'opt_status' => $optStatus,
                ]);

            $resData = $response->json();
            if ($response->successful() && ($resData['code'] ?? -1) === 0) {
                Log::info("[TikTok API] Berhasil update status campaign #{$campaign->id} ({$campaign->name}) menjadi {$optStatus}.");
                return true;
            } else {
                Log::error("[TikTok API] Error respon update status: " . json_encode($resData));
            }
        } catch (\Throwable $e) {
            Log::error("[TikTok API] Eksepsi saat update status campaign: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Sesuaikan budget campaign di TikTok Ads Manager via API
     */
    public function updateCampaignBudget(AdsCampaign $campaign, float $newBudget): bool
    {
        $account = $campaign->adsAccount;
        if (!$account || $account->platform !== 'tiktok') {
            Log::warning("[TikTok API] Gagal update budget: campaign #{$campaign->id} bukan platform TikTok.");
            return false;
        }

        $token = $account->access_token ?: $account->events_access_token;
        $advertiserId = $account->account_id ?: $account->advertiser_id;

        if (empty($token) || empty($advertiserId) || empty($campaign->campaign_id_platform)) {
            Log::warning("[TikTok API] Gagal update budget: Credentials atau Platform ID kosong untuk campaign #{$campaign->id}.");
            return false;
        }

        try {
            $url = 'https://business-api.tiktok.com/open_api/v1.3/campaign/update/';
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'advertiser_id' => $advertiserId,
                    'campaign_id' => $campaign->campaign_id_platform,
                    'budget' => $newBudget,
                ]);

            $resData = $response->json();
            if ($response->successful() && ($resData['code'] ?? -1) === 0) {
                Log::info("[TikTok API] Berhasil update budget campaign #{$campaign->id} menjadi Rp " . number_format($newBudget));
                return true;
            } else {
                Log::error("[TikTok API] Error respon update budget: " . json_encode($resData));
            }
        } catch (\Throwable $e) {
            Log::error("[TikTok API] Eksepsi saat update budget: " . $e->getMessage());
        }

        return false;
    }
}
