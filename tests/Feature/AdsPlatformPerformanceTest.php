<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\Channel;
use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsPlatformPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'Ads Test Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Marketing Manager',
            'email' => 'marketing@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $channel->id,
            'store_name' => 'Toko Shopee',
            'marketplace_store_id' => '111',
            'status' => 'connected',
        ]);
    }

    public function test_ads_dashboard_calculates_correct_platform_aggregations(): void
    {
        $today = now()->toDateString();

        // Platform 1: TikTok (Ads account platform = tiktok)
        $tiktokAccount = AdsAccount::create([
            'tenant_id' => $this->tenant->id,
            'platform' => 'tiktok',
            'account_name' => 'Akun TikTok',
            'is_active' => true,
        ]);

        $tiktokCampaign = AdsCampaign::create([
            'tenant_id' => $this->tenant->id,
            'ads_account_id' => $tiktokAccount->id,
            'name' => 'TikTok Campaign 1',
            'is_active' => true,
        ]);

        AdsPerformanceLog::create([
            'tenant_id' => $this->tenant->id,
            'ads_campaign_id' => $tiktokCampaign->id,
            'date' => $today,
            'ad_spend' => 30000,
            'clicks' => 50,
            'impressions' => 500,
        ]);

        // Platform 2: Meta (Ads account platform = meta)
        $metaAccount = AdsAccount::create([
            'tenant_id' => $this->tenant->id,
            'platform' => 'meta',
            'account_name' => 'Akun Meta',
            'is_active' => true,
        ]);

        $metaCampaign = AdsCampaign::create([
            'tenant_id' => $this->tenant->id,
            'ads_account_id' => $metaAccount->id,
            'name' => 'Meta Campaign 1',
            'is_active' => true,
        ]);

        AdsPerformanceLog::create([
            'tenant_id' => $this->tenant->id,
            'ads_campaign_id' => $metaCampaign->id,
            'date' => $today,
            'ad_spend' => 50000,
            'clicks' => 120,
            'impressions' => 1200,
        ]);

        // Add attributed orders to TikTok Campaign (conversions = 2, total = 90000)
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-TK-1',
            'invoice_number' => 'INV-TK-1',
            'order_status' => 'COMPLETED',
            'order_date' => now(),
            'total_amount' => 45000,
            'net_amount' => 45000,
            'hpp_total' => 20000,
            'ads_campaign_id' => $tiktokCampaign->id,
            'is_dropship' => false,
        ]);

        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-TK-2',
            'invoice_number' => 'INV-TK-2',
            'order_status' => 'COMPLETED',
            'order_date' => now(),
            'total_amount' => 45000,
            'net_amount' => 45000,
            'hpp_total' => 20000,
            'ads_campaign_id' => $tiktokCampaign->id,
            'is_dropship' => false,
        ]);

        // Attributed order to Meta Campaign (conversions = 1, total = 100000)
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-MT-1',
            'invoice_number' => 'INV-MT-1',
            'order_status' => 'COMPLETED',
            'order_date' => now(),
            'total_amount' => 100000,
            'net_amount' => 100000,
            'hpp_total' => 50000,
            'ads_campaign_id' => $metaCampaign->id,
            'is_dropship' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('marketing.ads.index'));

        $response->assertStatus(200);

        // Verify platformStats in the view context
        $platformStats = $response->viewData('platformStats');

        $this->assertNotNull($platformStats);

        // TikTok checks
        $this->assertEquals(30000.0, $platformStats['tiktok']['spend']);
        $this->assertEquals(90000.0, $platformStats['tiktok']['revenue']);
        $this->assertEquals(2, $platformStats['tiktok']['conversions']);
        $this->assertEquals(3.0, $platformStats['tiktok']['roas']);
        $this->assertEquals(15000.0, $platformStats['tiktok']['cpc']);

        // Meta checks
        $this->assertEquals(50000.0, $platformStats['meta']['spend']);
        $this->assertEquals(100000.0, $platformStats['meta']['revenue']);
        $this->assertEquals(1, $platformStats['meta']['conversions']);
        $this->assertEquals(2.0, $platformStats['meta']['roas']);
        $this->assertEquals(50000.0, $platformStats['meta']['cpc']);
    }

    public function test_ads_dashboard_filters_by_platform(): void
    {
        $tiktokAccount = AdsAccount::create([
            'tenant_id' => $this->tenant->id,
            'platform' => 'tiktok',
            'account_name' => 'Akun TikTok',
            'is_active' => true,
        ]);

        $tiktokCampaign = AdsCampaign::create([
            'tenant_id' => $this->tenant->id,
            'ads_account_id' => $tiktokAccount->id,
            'name' => 'TikTok Campaign Unique',
            'is_active' => true,
        ]);

        $metaAccount = AdsAccount::create([
            'tenant_id' => $this->tenant->id,
            'platform' => 'meta',
            'account_name' => 'Akun Meta',
            'is_active' => true,
        ]);

        $metaCampaign = AdsCampaign::create([
            'tenant_id' => $this->tenant->id,
            'ads_account_id' => $metaAccount->id,
            'name' => 'Meta Campaign Unique',
            'is_active' => true,
        ]);

        // 1. Filter dashboard index by tiktok
        $responseIndex = $this->actingAs($this->user)
            ->get(route('marketing.ads.index', ['platform' => 'tiktok']));

        $responseIndex->assertStatus(200);
        $campaignsIndex = $responseIndex->viewData('campaigns');
        $this->assertCount(1, $campaignsIndex);
        $this->assertEquals('TikTok Campaign Unique', $campaignsIndex->first()->name);

        // 2. Filter campaigns list by meta
        $responseCamp = $this->actingAs($this->user)
            ->get(route('marketing.ads.campaigns', ['platform' => 'meta']));

        $responseCamp->assertStatus(200);
        $campaignsList = $responseCamp->viewData('campaigns');
        $this->assertCount(1, $campaignsList);
        $this->assertEquals('Meta Campaign Unique', $campaignsList->first()->name);
    }
}
