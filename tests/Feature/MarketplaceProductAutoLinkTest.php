<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceProductAutoLinkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Test',
            'email'     => 'admin@autolinktest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $channel = Channel::create(['name' => 'Shopee', 'code' => 'shopee']);
        $this->store = Store::create([
            'tenant_id'            => $this->tenant->id,
            'channel_id'           => $channel->id,
            'marketplace_store_id' => '123456',
            'store_name'           => 'Toko Shopee Test',
            'is_active'            => true,
        ]);
    }

    public function test_marketplace_product_automatically_links_to_master_product_case_insensitively(): void
    {
        $master = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BB-TH-BIRU-LPK-XL',
            'name' => 'Kaos Biru LPK XL',
            'price' => 50000,
            'stock' => 10,
        ]);

        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'marketplace_product_id' => 'MP-12345',
            'marketplace_sku' => 'bb-th-biru-lpk-xl ', // Lowercase with trailing space
            'name' => 'Shopee Kaos Biru LPK XL',
            'price' => 50000,
            'stock' => 10,
        ]);

        $this->assertEquals($master->id, $mp->fresh()->master_product_id);
        $this->assertTrue((bool)$mp->fresh()->sync_stock);
    }

    public function test_creating_master_product_automatically_links_existing_unlinked_marketplace_products(): void
    {
        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'marketplace_product_id' => 'MP-9999',
            'marketplace_sku' => 'BB-TH-BIRU-LPK-XL',
            'name' => 'Shopee Kaos Biru LPK XL Unlinked',
            'price' => 50000,
            'stock' => 5,
        ]);

        $this->assertNull($mp->fresh()->master_product_id);

        $master = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BB-TH-BIRU-LPK-XL',
            'name' => 'Kaos Biru LPK XL Master',
            'price' => 50000,
            'stock' => 5,
        ]);

        $this->assertEquals($master->id, $mp->fresh()->master_product_id);
        $this->assertTrue((bool)$mp->fresh()->sync_stock);
    }

    public function test_auto_link_route_links_matching_skus(): void
    {
        $master = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BB-TH-BIRU-LPK-XL',
            'name' => 'Kaos Biru LPK XL',
            'price' => 50000,
            'stock' => 10,
        ]);

        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'marketplace_product_id' => 'MP-777',
            'marketplace_sku' => 'bb-th-biru-lpk-xl',
            'name' => 'Shopee Kaos Biru LPK XL',
            'price' => 50000,
            'stock' => 10,
        ]);

        // Force unlink quietly for testing route execution
        $mp->updateQuietly(['master_product_id' => null]);
        $this->assertNull($mp->fresh()->master_product_id);

        $response = $this->actingAs($this->user)
            ->post(route('marketplace_products.auto_link'));

        $response->assertRedirect();
        $this->assertEquals($master->id, $mp->fresh()->master_product_id);
    }
}
