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

class MasterProductFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Channel $channelShopee;
    protected Channel $channelTiktok;
    protected Store $storeShopee;
    protected Store $storeTiktok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Filter Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@filtertest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->channelShopee = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->channelTiktok = Channel::create([
            'name' => 'TikTok',
            'code' => 'tiktok',
        ]);

        $this->storeShopee = Store::create([
            'tenant_id'           => $this->tenant->id,
            'channel_id'          => $this->channelShopee->id,
            'store_name'          => 'Toko Shopee',
            'marketplace_store_id'=> 'SHOPEE_001',
            'status'              => 'connected',
        ]);

        $this->storeTiktok = Store::create([
            'tenant_id'           => $this->tenant->id,
            'channel_id'          => $this->channelTiktok->id,
            'store_name'          => 'Toko TikTok',
            'marketplace_store_id'=> 'TIKTOK_001',
            'status'              => 'connected',
        ]);
    }

    public function test_can_filter_master_products_by_channel_and_store(): void
    {
        $this->actingAs($this->user);

        // Product A: linked to Shopee
        $prodA = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-A',
            'name'      => 'Sepatu Sneakers Premium A',
            'price'     => 150000,
            'stock'     => 10,
        ]);

        MarketplaceProduct::create([
            'store_id' => $this->storeShopee->id,
            'master_product_id' => $prodA->id,
            'marketplace_product_id' => 'MP-A',
            'marketplace_sku' => 'SKU-A',
            'name' => 'Sepatu Sneakers Premium A',
            'price' => 150000,
            'stock' => 10,
        ]);

        // Product B: linked to TikTok
        $prodB = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-B',
            'name'      => 'Kaos Distro Keren B',
            'price'     => 85000,
            'stock'     => 20,
        ]);

        MarketplaceProduct::create([
            'store_id' => $this->storeTiktok->id,
            'master_product_id' => $prodB->id,
            'marketplace_product_id' => 'MP-B',
            'marketplace_sku' => 'SKU-B',
            'name' => 'Kaos Distro Keren B',
            'price' => 85000,
            'stock' => 20,
        ]);

        // Product C: unlinked
        $prodC = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-C',
            'name'      => 'Topi Baseball C',
            'price'     => 45000,
            'stock'     => 5,
        ]);

        // 1. Unfiltered
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
        $response->assertSee('Sepatu Sneakers Premium A');
        $response->assertSee('Kaos Distro Keren B');
        $response->assertSee('Topi Baseball C');

        // 2. Filter by Channel (Shopee)
        $response = $this->get(route('products.index', ['channel_id' => $this->channelShopee->id]));
        $response->assertStatus(200);
        $response->assertSee('Sepatu Sneakers Premium A');
        $response->assertDontSee('Kaos Distro Keren B');
        $response->assertDontSee('Topi Baseball C');

        // 3. Filter by Channel (TikTok)
        $response = $this->get(route('products.index', ['channel_id' => $this->channelTiktok->id]));
        $response->assertStatus(200);
        $response->assertDontSee('Sepatu Sneakers Premium A');
        $response->assertSee('Kaos Distro Keren B');
        $response->assertDontSee('Topi Baseball C');

        // 4. Filter by Store (Shopee Store)
        $response = $this->get(route('products.index', ['store_id' => $this->storeShopee->id]));
        $response->assertStatus(200);
        $response->assertSee('Sepatu Sneakers Premium A');
        $response->assertDontSee('Kaos Distro Keren B');
        $response->assertDontSee('Topi Baseball C');
    }

    public function test_can_filter_master_products_by_link_status(): void
    {
        $this->actingAs($this->user);

        // Product A: linked
        $prodA = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-A',
            'name'      => 'Sepatu Sneakers Premium A',
            'price'     => 150000,
            'stock'     => 10,
        ]);

        MarketplaceProduct::create([
            'store_id' => $this->storeShopee->id,
            'master_product_id' => $prodA->id,
            'marketplace_product_id' => 'MP-A',
            'marketplace_sku' => 'SKU-A',
            'name' => 'Sepatu Sneakers Premium A',
            'price' => 150000,
            'stock' => 10,
        ]);

        // Product B: unlinked
        $prodB = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-B',
            'name'      => 'Kaos Distro Keren B',
            'price'     => 85000,
            'stock'     => 20,
        ]);

        // 1. Filter by link_status = unlinked
        $response = $this->get(route('products.index', ['link_status' => 'unlinked']));
        $response->assertStatus(200);
        $response->assertDontSee('Sepatu Sneakers Premium A');
        $response->assertSee('Kaos Distro Keren B');

        // 2. Filter by link_status = linked
        $response = $this->get(route('products.index', ['link_status' => 'linked']));
        $response->assertStatus(200);
        $response->assertSee('Sepatu Sneakers Premium A');
        $response->assertDontSee('Kaos Distro Keren B');
    }
}
