<?php

namespace App\Console\Commands;

use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncTiktokAdsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok-ads:sync {--days=14 : Jumlah hari ke belakang untuk disinkronisasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data pengeluaran (spend) TikTok Ads secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Starting TikTok Ads sync process for the past {$days} days...");
        Log::info("[TikTok Ads Sync] Starting sync process", ['days' => $days]);

        $appId = config('services.tiktok_ads.app_id');
        $secret = config('services.tiktok_ads.secret');

        if (empty($appId) || empty($secret)) {
            $this->error('TikTok Ads credentials not configured in .env.');
            return;
        }

        // Ambil semua akun iklan TikTok yang aktif
        $accounts = AdsAccount::where('platform', 'tiktok')
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No active TikTok Ads accounts found.');
            return;
        }

        foreach ($accounts as $account) {
            $this->info("Syncing ads for advertiser account: {$account->account_name} (ID: {$account->account_id})");
            Log::info("[TikTok Ads Sync] Processing advertiser account", [
                'account_id' => $account->account_id,
                'name' => $account->account_name
            ]);

            try {
                // Loop untuk setiap hari
                for ($i = 0; $i < $days; $i++) {
                    $dateObj = now()->subDays($i);
                    $dateStr = $dateObj->format('Y-m-d'); // YYYY-MM-DD

                    $this->info("- Fetching data for date: {$dateStr}");

                    // Panggil API integrated report TikTok Ads
                    $reportUrl = "https://business-api.tiktok.com/open_api/v1.3/reports/integrated/get/";
                    
                    $response = Http::timeout(30)
                        ->withHeaders(['Access-Token' => $account->access_token])
                        ->get($reportUrl, [
                            'app_id' => $appId,
                            'secret' => $secret,
                            'advertiser_id' => $account->account_id,
                            'report_type' => 'BASIC',
                            'data_level' => 'AUCTION_CAMPAIGN',
                            'dimensions' => json_encode(['stat_time_day', 'campaign_id', 'campaign_name']),
                            'metrics' => json_encode(['spend', 'clicks', 'impressions']),
                            'start_date' => $dateStr,
                            'end_date' => $dateStr,
                            'page_size' => 100,
                        ]);

                    if ($response->failed()) {
                        Log::error('[TikTok Ads Sync] Report API failed', [
                            'status' => $response->status(),
                            'body' => $response->body()
                        ]);
                        continue;
                    }

                    $data = $response->json();
                    if (($data['code'] ?? 0) !== 0) {
                        Log::warning('[TikTok Ads Sync] Report API error response', ['data' => $data]);
                        continue;
                    }

                    $campaignsList = $data['data']['list'] ?? [];
                    if (empty($campaignsList)) {
                        $this->info("  No ad campaigns or data found on {$dateStr}.");
                        continue;
                    }

                    $this->info("  Found " . count($campaignsList) . " campaigns. Syncing performance logs...");

                    foreach ($campaignsList as $item) {
                        $dimensions = $item['dimensions'] ?? [];
                        $metrics = $item['metrics'] ?? [];

                        $platformCampaignId = (string)($dimensions['campaign_id'] ?? '');
                        if (empty($platformCampaignId)) {
                            continue;
                        }

                        $campaignName = $dimensions['campaign_name'] ?? ('TikTok Ads ' . $platformCampaignId);

                        // 1. Dapatkan atau buat AdsCampaign
                        $adsCampaign = AdsCampaign::firstOrCreate(
                            [
                                'tenant_id' => $account->tenant_id,
                                'ads_account_id' => $account->id,
                                'campaign_id_platform' => $platformCampaignId,
                            ],
                            [
                                'name' => $campaignName,
                                'target_roas' => 2.00,
                                'target_omzet' => 0,
                                'status' => 'ACTIVE',
                                'is_active' => true,
                            ]
                        );

                        // Update nama campaign jika berbeda
                        if (isset($dimensions['campaign_name']) && $adsCampaign->name !== $dimensions['campaign_name']) {
                            $adsCampaign->update(['name' => $dimensions['campaign_name']]);
                        }

                        // 2. Simpan logs performa harian
                        $spend = (float)($metrics['spend'] ?? 0);
                        $clicks = (int)($metrics['clicks'] ?? 0);
                        $impressions = (int)($metrics['impressions'] ?? 0);

                        AdsPerformanceLog::updateOrCreate(
                            [
                                'tenant_id' => $account->tenant_id,
                                'ads_campaign_id' => $adsCampaign->id,
                                'date' => $dateStr,
                            ],
                            [
                                'ad_spend' => $spend,
                                'clicks' => $clicks,
                                'impressions' => $impressions,
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error syncing ads for account {$account->account_name}: " . $e->getMessage());
                Log::error("[TikTok Ads Sync] Failed to sync account ads", [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('TikTok Ads sync process completed.');
    }
}
