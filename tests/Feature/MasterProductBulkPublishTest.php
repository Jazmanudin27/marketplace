<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Jobs\PublishProductToMarketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MasterProductBulkPublishTest extends TestCase
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
            'name'   => 'Bulk Publish Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@bulktest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        // Admin role automatically bypasses permissions via Gate::before in AppServiceProvider

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

    public function test_bulk_publish_requires_product_ids(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('products.bulk_publish'));
        $response->assertStatus(302);
        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('error');
    }

    public function test_bulk_publish_shows_form_with_products(): void
    {
        $this->actingAs($this->user);

        $prodA = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-A',
            'name'      => 'Kaos Polos Premium A',
            'price'     => 150000,
            'stock'     => 10,
        ]);

        $prodB = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-B',
            'name'      => 'Celana Jeans Cargo B',
            'price'     => 250000,
            'stock'     => 15,
        ]);

        $response = $this->get(route('products.bulk_publish', ['ids' => [$prodA->id, $prodB->id]]));
        $response->assertStatus(200);
        $response->assertSee('Kaos Polos Premium A');
        $response->assertSee('Celana Jeans Cargo B');
        $response->assertSee('Toko Shopee');
        $response->assertSee('Toko TikTok');
    }

    public function test_store_bulk_publish_dispatches_jobs(): void
    {
        $this->actingAs($this->user);
        Queue::fake();

        $prodA = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-A',
            'name'      => 'Kaos Polos Premium A',
            'price'     => 150000,
            'stock'     => 10,
            'weight'    => 0.2, // must have weight > 0
        ]);

        $prodB = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-B',
            'name'      => 'Celana Jeans Cargo B',
            'price'     => 250000,
            'stock'     => 15,
            'weight'    => 0.5, // must have weight > 0
        ]);

        $postData = [
            'product_ids' => [$prodA->id, $prodB->id],
            'stores' => [$this->storeShopee->id, $this->storeTiktok->id],
            'categories' => [
                $this->storeShopee->id => 'shopee_cat_123',
                $this->storeTiktok->id => 'tiktok_cat_456',
            ],
            'category_names' => [
                $this->storeShopee->id => 'Shopee Category Distro',
                $this->storeTiktok->id => 'TikTok Category Fashion',
            ],
            'save_mapping' => [
                $this->storeShopee->id => '1',
                $this->storeTiktok->id => '1',
            ]
        ];

        $response = $this->post(route('products.bulk_publish.store'), $postData);
        $response->assertStatus(302);
        $response->assertRedirect(route('products.index'));

        // Dispatches 4 jobs: 2 products * 2 stores
        Queue::assertPushed(PublishProductToMarketplace::class, 4);
    }

    public function test_store_bulk_publish_appends_size_chart_id(): void
    {
        $this->actingAs($this->user);
        Queue::fake();

        $prod = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'SKU-SIZE-CHART',
            'name'      => 'Baju Koko Modern',
            'price'     => 120000,
            'stock'     => 5,
            'weight'    => 0.3,
        ]);

        $postData = [
            'product_ids' => [$prod->id],
            'stores' => [$this->storeShopee->id],
            'categories' => [
                $this->storeShopee->id => '101776',
            ],
            'category_names' => [
                $this->storeShopee->id => 'Atasan Lainnya',
            ],
            'size_chart_ids' => [
                $this->storeShopee->id => '998877',
            ]
        ];

        $response = $this->post(route('products.bulk_publish.store'), $postData);
        $response->assertStatus(302);

        // Verify the PublicationLog category_id is stored as 101776|998877
        $this->assertDatabaseHas('publication_logs', [
            'master_product_id' => $prod->id,
            'store_id' => $this->storeShopee->id,
            'category_id' => '101776|998877',
        ]);
    }
}
