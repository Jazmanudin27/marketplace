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

class ProductCloningTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Channel $channel;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Cloning Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@cloningtest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id'           => $this->tenant->id,
            'channel_id'          => $this->channel->id,
            'store_name'          => 'Toko Cloning Test',
            'marketplace_store_id'=> 'SHOPEE_CLONE_001',
            'status'              => 'connected',
        ]);
    }

    public function test_clone_and_publish_redirects_if_already_mapped(): void
    {
        $master = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-EXISTING',
            'name'       => 'Master Product Existing',
            'price'      => 100000,
            'stock'      => 10,
            'is_active'  => true,
        ]);

        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => $master->id,
            'marketplace_product_id' => 'MP-111',
            'marketplace_sku' => 'SKU-EXISTING',
            'name' => 'Marketplace Product Name',
            'price' => 100000,
            'stock' => 10,
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('marketplace_products.clone_and_publish', $mp->id));

        $response->assertRedirect(route('products.publish', $master->id));
    }

    public function test_clone_and_publish_auto_links_if_sku_already_exists_in_master(): void
    {
        $master = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-MATCH',
            'name'       => 'Master Product Match',
            'price'      => 120000,
            'stock'      => 20,
            'is_active'  => true,
        ]);

        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => null,
            'marketplace_product_id' => 'MP-222',
            'marketplace_sku' => 'SKU-MATCH',
            'name' => 'Marketplace Product Name Unmapped',
            'price' => 120000,
            'stock' => 20,
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('marketplace_products.clone_and_publish', $mp->id));

        $response->assertRedirect(route('products.publish', $master->id));

        $mp->refresh();
        $this->assertEquals($master->id, $mp->master_product_id);
    }

    public function test_clone_and_publish_creates_master_product_and_redirects(): void
    {
        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => null,
            'marketplace_product_id' => 'MP-333',
            'marketplace_sku' => 'SKU-NEW-CLONE',
            'name' => 'Marketplace Product Sneaker Premium',
            'price' => 150000,
            'stock' => 5,
            'image_url' => 'http://example.com/sneaker.jpg',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('marketplace_products.clone_and_publish', $mp->id));

        // Harus membuat MasterProduct baru
        $newMaster = MasterProduct::where('sku', 'SKU-NEW-CLONE')->first();
        $this->assertNotNull($newMaster);
        $this->assertEquals('Marketplace Product Sneaker Premium', $newMaster->name);
        $this->assertEquals(150000, (float)$newMaster->price);
        $this->assertEquals(5, $newMaster->stock);
        $this->assertEquals('http://example.com/sneaker.jpg', $newMaster->image_url);
        $this->assertEquals(0.1, $newMaster->weight);

        $response->assertRedirect(route('products.publish', $newMaster->id));

        $mp->refresh();
        $this->assertEquals($newMaster->id, $mp->master_product_id);
    }

    public function test_clone_button_and_form_visible_on_marketplace_products_page(): void
    {
        $master = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-MAP',
            'name'       => 'Master Product For Mapped',
            'price'      => 100000,
            'stock'      => 10,
            'is_active'  => true,
        ]);

        $mpMapped = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => $master->id,
            'marketplace_product_id' => 'MP-MAP',
            'marketplace_sku' => 'SKU-MAP',
            'name' => 'Mapped Product',
            'price' => 100000,
            'stock' => 5,
        ]);

        $mpUnmapped = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => null,
            'marketplace_product_id' => 'MP-UNMAP',
            'marketplace_sku' => 'SKU-UNMAP',
            'name' => 'Unmapped Product',
            'price' => 100000,
            'stock' => 5,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('marketplace_products.index'));

        $response->assertStatus(200);
        // Harus ada tombol / link salin ke toko lain
        $response->assertSee('Salin ke Toko Lain');
    }

    public function test_can_filter_marketplace_products(): void
    {
        $this->actingAs($this->user);

        // Create a different channel and store
        $otherChannel = Channel::create([
            'name' => 'Tokopedia',
            'code' => 'tokopedia',
        ]);
        $otherStore = Store::create([
            'tenant_id'            => $this->tenant->id,
            'channel_id'           => $otherChannel->id,
            'store_name'           => 'Toko Tokped',
            'marketplace_store_id' => 'TOKOPEDIA_001',
            'status'               => 'connected',
        ]);

        $prodA = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'marketplace_product_id' => 'MP-A',
            'marketplace_sku' => 'SKU-AAA',
            'name' => 'Sandal Gunung Eiger',
            'price' => 100000,
            'stock' => 5,
        ]);

        $prodB = MarketplaceProduct::create([
            'store_id' => $otherStore->id,
            'marketplace_product_id' => 'MP-B',
            'marketplace_sku' => 'SKU-BBB',
            'name' => 'Sepatu Compass',
            'price' => 200000,
            'stock' => 10,
        ]);

        // 1. Filter by Name
        $response = $this->get(route('marketplace_products.index', ['name' => 'Compass']));
        $response->assertStatus(200);
        $response->assertSee('Sepatu Compass');
        $response->assertDontSee('Sandal Gunung Eiger');

        // 2. Filter by SKU
        $response = $this->get(route('marketplace_products.index', ['sku' => 'SKU-AAA']));
        $response->assertStatus(200);
        $response->assertSee('Sandal Gunung Eiger');
        $response->assertDontSee('Sepatu Compass');

        // 3. Filter by Channel
        $response = $this->get(route('marketplace_products.index', ['channel_id' => $otherChannel->id]));
        $response->assertStatus(200);
        $response->assertSee('Sepatu Compass');
        $response->assertDontSee('Sandal Gunung Eiger');

        // 4. Filter by Store
        $response = $this->get(route('marketplace_products.index', ['store_id' => $this->store->id]));
        $response->assertStatus(200);
        $response->assertSee('Sandal Gunung Eiger');
        $response->assertDontSee('Sepatu Compass');
    }

    public function test_bulk_auto_link_by_sku(): void
    {
        $this->actingAs($this->user);

        // 1. Create multiple unlinked Marketplace Products with the same SKU (with whitespaces)
        $mp1 = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => null,
            'marketplace_product_id' => 'MP-AUTO-1',
            'marketplace_sku' => '  SKU-AUTO-LINK  ', // test whitespace trim
            'name' => 'MP Product 1',
            'price' => 150000,
            'stock' => 50,
        ]);

        $mp2 = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => null,
            'marketplace_product_id' => 'MP-AUTO-2',
            'marketplace_sku' => 'SKU-AUTO-LINK',
            'name' => 'MP Product 2',
            'price' => 150000,
            'stock' => 50,
        ]);

        // 2. Create a Master Product after marketplace products are created
        $master = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-AUTO-LINK',
            'name' => 'Master Product Auto Link',
            'price' => 150000,
            'stock' => 50,
            'is_active' => true,
        ]);

        // 3. Make POST request to auto_link route
        $response = $this->post(route('marketplace_products.auto_link'));

        // 4. Assert redirect back and correct flash message
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Berhasil menautkan secara otomatis 2 produk marketplace berdasarkan kesamaan SKU.');

        // 5. Verify database has been updated
        $this->assertDatabaseHas('marketplace_products', [
            'id' => $mp1->id,
            'master_product_id' => $master->id,
            'sync_stock' => true,
        ]);

        $this->assertDatabaseHas('marketplace_products', [
            'id' => $mp2->id,
            'master_product_id' => $master->id,
            'sync_stock' => true,
        ]);
    }

    public function test_index_shows_direct_link_button_when_sku_matches(): void
    {
        $this->actingAs($this->user);

        // 1. Create Marketplace Product first (with an empty or unmatching SKU to avoid observer auto-link)
        $mp = MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => null,
            'marketplace_product_id' => 'MP-DIRECT-1',
            'marketplace_sku' => '',
            'name' => 'MP Product Direct Match',
            'price' => 150000,
            'stock' => 50,
        ]);

        // 2. Create Master Product
        $master = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-DIRECT-MATCH',
            'name' => 'Master Product Direct Match',
            'price' => 150000,
            'stock' => 50,
            'is_active' => true,
        ]);

        // 3. Update the Marketplace Product's SKU directly via DB query builder to bypass Eloquent observers
        \Illuminate\Support\Facades\DB::table('marketplace_products')
            ->where('id', $mp->id)
            ->update(['marketplace_sku' => 'SKU-DIRECT-MATCH']);

        // 4. Access the index page
        $response = $this->get(route('marketplace_products.index'));

        $response->assertStatus(200);
        
        // Assert we see the direct "Tautkan ke Master" button
        $response->assertSee('Tautkan ke Master');
        $response->assertSee('Cocok: Master Product Direct Match');
    }
}
