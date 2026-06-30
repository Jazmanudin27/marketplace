<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use App\Services\ShopeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncShopeeAdsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee-ads:sync {--days=14 : Jumlah hari ke belakang untuk disinkronisasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data pengeluaran (spend) Shopee Ads secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle(ShopeeService $shopee)
    {
        $days = (int) $this->option('days');
        $this->info("Starting Shopee Ads sync process for the past {$days} days...");
        Log::info("[Shopee Ads Sync] Starting sync process", ['days' => $days]);

        // Ambil semua toko Shopee yang terhubung
        $stores = Store::whereHas('channel', function ($query) {
            $query->where('code', 'shopee');
        })
        ->where('status', '!=', 'disconnected')
        ->get();

        if ($stores->isEmpty()) {
            $this->info('No connected Shopee stores found.');
            return;
        }

        foreach ($stores as $store) {
            $this->info("Syncing ads for store: {$store->store_name} (ID: {$store->id})");
            Log::info("[Shopee Ads Sync] Processing store", ['store_id' => $store->id, 'name' => $store->store_name]);

            try {
                $accessToken = $store->getValidAccessToken();
                $shopId = (int) $store->marketplace_store_id;

                for ($i = 0; $i < $days; $i++) {
                    $dateObj = now()->subDays($i);
                    $dateStr = $dateObj->format('Y-m-d'); // YYYY-MM-DD format

                    $this->info("- Fetching data for date: {$dateStr}");
                    
                    // Panggil API performa iklan Shopee
                    $campaignsList = $shopee->getAdsPerformance($accessToken, $shopId, $dateStr, $dateStr);

                    if (empty($campaignsList)) {
                        $this->info("  No ad campaigns or data found on {$dateStr}.");
                        continue;
                    }

                    $this->info("  Found " . count($campaignsList) . " campaigns. Syncing performance logs...");

                    foreach ($campaignsList as $item) {
                        $platformCampaignId = (string)($item['campaign_id'] ?? '');
                        if (empty($platformCampaignId)) {
                            continue;
                        }

                        // 1. Dapatkan atau buat AdsAccount untuk platform Shopee di tenant ini
                        $adsAccount = AdsAccount::firstOrCreate(
                            [
                                'tenant_id' => $store->tenant_id,
                                'platform' => 'shopee',
                                'account_id' => (string)$shopId,
                            ],
                            [
                                'account_name' => 'Shopee Ads ' . $store->store_name,
                                'is_active' => true,
                            ]
                        );

                        // 2. Dapatkan atau buat AdsCampaign
                        $campaignName = $item['campaign_name'] ?? ('Shopee Ads ' . $platformCampaignId);
                        $adsCampaign = AdsCampaign::firstOrCreate(
                            [
                                'tenant_id' => $store->tenant_id,
                                'ads_account_id' => $adsAccount->id,
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
                        if (isset($item['campaign_name']) && $adsCampaign->name !== $item['campaign_name']) {
                            $adsCampaign->update(['name' => $item['campaign_name']]);
                        }

                        // 3. Simpan logs performa harian
                        // Di Shopee Ads, field cost/spend adalah biaya iklan, click/clicks adalah jumlah klik, impression/impressions adalah impresi
                        $spend = (float)($item['cost'] ?? $item['spend'] ?? 0);
                        $clicks = (int)($item['click'] ?? $item['clicks'] ?? 0);
                        $impressions = (int)($item['impression'] ?? $item['impressions'] ?? 0);

                        AdsPerformanceLog::updateOrCreate(
                            [
                                'tenant_id' => $store->tenant_id,
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
                $this->error("Error syncing ads for store {$store->store_name}: " . $e->getMessage());
                Log::error("[Shopee Ads Sync] Failed to sync store ads", [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('Shopee Ads sync process completed.');
    }
}
