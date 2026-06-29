<?php

namespace Database\Seeders;

use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use App\Models\Order;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MarketingAdsMockSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            return;
        }

        $tenantId = $tenant->id;

        // 1. Create Ads Accounts
        $metaAccount = AdsAccount::updateOrCreate(
            ['tenant_id' => $tenantId, 'platform' => 'meta'],
            ['account_name' => 'Meta Business - Aspartech Ads', 'account_id' => 'act_294819482', 'is_active' => true]
        );

        $googleAccount = AdsAccount::updateOrCreate(
            ['tenant_id' => $tenantId, 'platform' => 'google'],
            ['account_name' => 'Google Ads - Aspartech Search', 'account_id' => '384-921-2093', 'is_active' => true]
        );

        $tiktokAccount = AdsAccount::updateOrCreate(
            ['tenant_id' => $tenantId, 'platform' => 'tiktok'],
            ['account_name' => 'TikTok For Business - Aspartech Spark', 'account_id' => 'act_tt8391829', 'is_active' => true]
        );

        // 2. Create Campaigns
        $camp1 = AdsCampaign::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Meta Ads - Promo Gamis Premium'],
            [
                'ads_account_id' => $metaAccount->id,
                'campaign_id_platform' => 'camp_meta_001',
                'target_omzet' => 30000000.00,
                'target_roas' => 3.00,
                'status' => 'ACTIVE',
                'is_active' => true
            ]
        );

        $camp2 = AdsCampaign::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Google Search - Gamis Terlaris'],
            [
                'ads_account_id' => $googleAccount->id,
                'campaign_id_platform' => 'camp_google_002',
                'target_omzet' => 20000000.00,
                'target_roas' => 2.50,
                'status' => 'ACTIVE',
                'is_active' => true
            ]
        );

        $camp3 = AdsCampaign::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'TikTok Ads - Gamis Lebaran Viral'],
            [
                'ads_account_id' => $tiktokAccount->id,
                'campaign_id_platform' => 'camp_tiktok_003',
                'target_omzet' => 50000000.00,
                'target_roas' => 4.00,
                'status' => 'ACTIVE',
                'is_active' => true
            ]
        );

        // 3. Create Daily logs for the past 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');

            // Camp 1: Good ROAS (Stable performance)
            AdsPerformanceLog::updateOrCreate(
                ['tenant_id' => $tenantId, 'ads_campaign_id' => $camp1->id, 'date' => $date],
                [
                    'ad_spend' => rand(150000, 250000),
                    'clicks' => rand(300, 600),
                    'impressions' => rand(10000, 20000)
                ]
            );

            // Camp 2: Wasteful ROAS (Low conversion rate)
            AdsPerformanceLog::updateOrCreate(
                ['tenant_id' => $tenantId, 'ads_campaign_id' => $camp2->id, 'date' => $date],
                [
                    'ad_spend' => rand(200000, 400000),
                    'clicks' => rand(400, 800),
                    'impressions' => rand(15000, 30000)
                ]
            );

            // Camp 3: High Scale / Super Profitable ROAS
            AdsPerformanceLog::updateOrCreate(
                ['tenant_id' => $tenantId, 'ads_campaign_id' => $camp3->id, 'date' => $date],
                [
                    'ad_spend' => rand(100000, 180000),
                    'clicks' => rand(500, 1000),
                    'impressions' => rand(20000, 40000)
                ]
            );
        }

        // 4. Attribute existing orders to make the dashboard alive
        // Grab non-cancelled orders
        $orders = Order::where('tenant_id', $tenantId)
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->orderBy('id')
            ->get();

        $camps = [$camp1, $camp2, $camp3];
        
        foreach ($orders as $index => $order) {
            // Distribute orders among campaigns to generate different ROAS levels:
            // Camp 1 (Meta): gets 30% of orders -> Average ROAS
            // Camp 2 (Google): gets 10% of orders -> Low ROAS (Boros)
            // Camp 3 (TikTok): gets 50% of orders -> High ROAS (Aman/Profit)
            
            $mod = $index % 10;
            if ($mod < 3) {
                $assignedCamp = $camp1;
            } elseif ($mod === 3) {
                $assignedCamp = $camp2;
            } elseif ($mod >= 4 && $mod < 9) {
                $assignedCamp = $camp3;
            } else {
                $assignedCamp = null; // Unattributed order
            }

            if ($assignedCamp) {
                $order->update([
                    'ads_campaign_id' => $assignedCamp->id,
                    'utm_campaign' => $assignedCamp->name,
                    'utm_source' => $assignedCamp->adsAccount->platform
                ]);
            }
        }
    }
}
