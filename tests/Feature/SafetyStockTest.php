<?php

namespace Tests\Feature;

use App\Jobs\PushStockToMarketplaces;
use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SafetyStockTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $channelShopee;
    protected $channelTiktok;
    protected $storeShopee;
    protected $storeTiktok;
    protected $masterProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->channelShopee = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->channelTiktok = Channel::create([
            'name' => 'TikTok Shop',
            'code' => 'tiktok',
        ]);

        $this->storeShopee = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channelShopee->id,
            'store_name' => 'Shopee Store Test',
            'marketplace_store_id' => 'SHOPEE_TEST_STORE_ID',
            'status' => 'connected',
            'access_token' => 'access-token-shopee',
            'token_expires_at' => now()->addHours(1),
        ]);

        $this->storeTiktok = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channelTiktok->id,
            'store_name' => 'TikTok Store Test',
            'marketplace_store_id' => 'TIKTOK_TEST_STORE_ID',
            'status' => 'connected',
            'access_token' => 'access-token-tiktok',
            'shop_cipher' => 'tiktok-cipher',
            'token_expires_at' => now()->addHours(1),
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-TEST-01',
            'name' => 'Test Product Sneakers',
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * Test that updateSettings updates the db and dispatches PushStockToMarketplaces job
     */
    public function test_update_settings_success(): void
    {
        $mpProduct = MarketplaceProduct::create([
            'store_id' => $this->storeShopee->id,
            'master_product_id' => $this->masterProduct->id,
            'marketplace_product_id' => 'MP-SHOPEE-999',
            'marketplace_sku' => 'MP-SKU-999',
            'name' => 'MP Product Name',
            'price' => 100000,
            'stock' => 10,
            'sync_stock' => false,
            'safety_stock' => 0,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->user)
            ->put(route('marketplace_products.update_settings', $mpProduct->id), [
                'sync_stock' => '1',
                'safety_stock' => '3',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $mpProduct->refresh();
        $this->assertTrue($mpProduct->sync_stock);
        $this->assertEquals(3, $mpProduct->safety_stock);

        Queue::assertPushed(PushStockToMarketplaces::class);
    }

    /**
     * Test PushStockToMarketplaces job calculates and pushes stock subtracting safety stock
     */
    public function test_push_stock_job_respects_safety_stock(): void
    {
        // 1. Setup Shopee product with safety stock = 2 (master stock is 10, expected pushed = 8)
        $mpShopee = MarketplaceProduct::create([
            'store_id' => $this->storeShopee->id,
            'master_product_id' => $this->masterProduct->id,
            'marketplace_product_id' => '123456',
            'marketplace_variant_id' => 'v123',
            'marketplace_sku' => 'MP-SKU-SHOPEE',
            'name' => 'MP Shopee',
            'price' => 100000,
            'stock' => 10,
            'sync_stock' => true,
            'safety_stock' => 2,
        ]);

        // 2. Setup TikTok product with safety stock = 12 (master stock is 10, expected pushed = 0, no negative)
        $mpTiktok = MarketplaceProduct::create([
            'store_id' => $this->storeTiktok->id,
            'master_product_id' => $this->masterProduct->id,
            'marketplace_product_id' => '654321',
            'marketplace_variant_id' => 'v654',
            'marketplace_sku' => 'MP-SKU-TIKTOK',
            'name' => 'MP Tiktok',
            'price' => 100000,
            'stock' => 10,
            'sync_stock' => true,
            'safety_stock' => 12,
        ]);

        // Mock ShopeeService
        $shopeeMock = Mockery::mock(ShopeeService::class);
        $shopeeMock->shouldReceive('updateStock')
            ->once()
            ->with(
                'access-token-shopee',
                (int)$this->storeShopee->marketplace_store_id,
                123456,
                8, // Expected 10 - 2 = 8
                'v123'
            )
            ->andReturn([]);
        $this->app->instance(ShopeeService::class, $shopeeMock);

        // Mock TiktokService
        $tiktokMock = Mockery::mock(TiktokService::class);
        $tiktokMock->shouldReceive('updateStock')
            ->once()
            ->with(
                'access-token-tiktok',
                'tiktok-cipher',
                '654321',
                'v654',
                0 // Expected max(0, 10 - 12) = 0
            )
            ->andReturn([]);
        $this->app->instance(TiktokService::class, $tiktokMock);

        // Run job
        $job = new PushStockToMarketplaces($this->masterProduct->id, 10);
        $job->handle($shopeeMock);

        // Assert local db values are updated to the actual pushed values
        $mpShopee->refresh();
        $mpTiktok->refresh();

        $this->assertEquals(8, $mpShopee->stock);
        $this->assertEquals(0, $mpTiktok->stock);
    }
}
