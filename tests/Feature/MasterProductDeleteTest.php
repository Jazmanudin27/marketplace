<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Models\Channel;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@deletetest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);
    }

    public function test_can_delete_unlinked_master_product(): void
    {
        $product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'UNLINKED-001',
            'name'      => 'Produk Master Belum Terhubung',
            'price'     => 50000,
            'stock'     => 10,
        ]);

        $this->assertFalse($product->isLinked());

        $response = $this->actingAs($this->user)
            ->delete(route('products.destroy', $product->id));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('master_products', ['id' => $product->id]);
    }

    public function test_cannot_delete_linked_master_product(): void
    {
        $channel = Channel::create(['name' => 'Shopee', 'code' => 'shopee']);
        $store = Store::create([
            'tenant_id'            => $this->tenant->id,
            'channel_id'           => $channel->id,
            'store_name'           => 'Toko Shopee Test',
            'marketplace_store_id' => '123456',
            'status'               => 'connected',
        ]);

        $product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'LINKED-001',
            'name'      => 'Produk Master Sudah Terhubung',
            'price'     => 50000,
            'stock'     => 10,
        ]);

        MarketplaceProduct::create([
            'store_id'               => $store->id,
            'master_product_id'      => $product->id,
            'marketplace_product_id' => 'mp_1001',
            'marketplace_sku'        => 'LINKED-001',
            'name'                   => 'Produk Shopee',
            'price'                  => 50000,
            'stock'                  => 10,
        ]);

        $this->assertTrue($product->isLinked());

        $response = $this->actingAs($this->user)
            ->delete(route('products.destroy', $product->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('master_products', ['id' => $product->id]);
    }

    public function test_bulk_delete_deletes_only_unlinked_products(): void
    {
        $channel = Channel::create(['name' => 'Shopee', 'code' => 'shopee']);
        $store = Store::create([
            'tenant_id'            => $this->tenant->id,
            'channel_id'           => $channel->id,
            'store_name'           => 'Toko Shopee Test',
            'marketplace_store_id' => '123456',
            'status'               => 'connected',
        ]);

        $unlinkedProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'UNLINKED-002',
            'name'      => 'Produk Unlinked Bulk',
            'price'     => 50000,
            'stock'     => 10,
        ]);

        $linkedProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'LINKED-002',
            'name'      => 'Produk Linked Bulk',
            'price'     => 50000,
            'stock'     => 10,
        ]);

        MarketplaceProduct::create([
            'store_id'               => $store->id,
            'master_product_id'      => $linkedProduct->id,
            'marketplace_product_id' => 'mp_1002',
            'marketplace_sku'        => 'LINKED-002',
            'name'                   => 'Produk Shopee Bulk',
            'price'                  => 50000,
            'stock'                  => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('products.bulk_delete'), [
                'product_ids' => [$unlinkedProduct->id, $linkedProduct->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $this->assertDatabaseMissing('master_products', ['id' => $unlinkedProduct->id]);
        $this->assertDatabaseHas('master_products', ['id' => $linkedProduct->id]);
    }
}
