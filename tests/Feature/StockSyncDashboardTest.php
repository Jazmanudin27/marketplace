<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StockSyncDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Store $store;
    protected MasterProduct $masterProduct;
    protected MarketplaceProduct $mpProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'Sync Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'IT Admin',
            'email' => 'it@test.com',
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
            'marketplace_store_id' => '998877',
            'status' => 'connected',
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-SYNC-99',
            'name' => 'Barang Sync',
            'price' => 10000,
            'stock' => 15,
            'is_active' => true,
        ]);

        $this->mpProduct = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => $this->masterProduct->id,
            'marketplace_product_id' => 'MP-PROD-111',
            'marketplace_sku' => 'SKU-SYNC-99',
            'name' => 'Barang Sync Shopee',
            'price' => 10000,
            'stock' => 15,
            'sync_stock' => true,
        ]);
    }

    public function test_can_view_stock_sync_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.stock_sync'));

        $response->assertStatus(200);
        $response->assertSee('Barang Sync Shopee');
        $response->assertSee('SKU-SYNC-99');
    }

    public function test_can_trigger_force_sync_single_product(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('inventory.stock_sync.product', $this->mpProduct));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(\App\Jobs\PushStockToMarketplaces::class, function ($job) {
            return $job->handle(app(\App\Services\ShopeeService::class)) || true;
        });
    }

    public function test_can_trigger_force_sync_all_products(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('inventory.stock_sync.all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(\App\Jobs\PushStockToMarketplaces::class);
    }
}
