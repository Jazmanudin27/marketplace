<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Store $store;
    protected MasterProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'Fulfillment Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Staff Gudang',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse',
        ]);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->tenant->id);
        $this->user->assignRole('admin');

        $channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $channel->id,
            'store_name' => 'Store Shopee',
            'marketplace_store_id' => '12345',
            'status' => 'connected',
        ]);

        $this->product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-BATCH-01',
            'name' => 'Produk Batch',
            'price' => 20000,
            'stock' => 50,
            'is_active' => true,
        ]);
    }

    public function test_can_view_aggregated_batch_pick_list(): void
    {
        $order1 = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-B1',
            'invoice_number' => 'INV-B1',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer 1',
            'order_date' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'master_product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'price' => 20000,
            'quantity' => 2,
            'total_price' => 40000,
        ]);

        $order2 = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-B2',
            'invoice_number' => 'INV-B2',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer 2',
            'order_date' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'master_product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'price' => 20000,
            'quantity' => 3,
            'total_price' => 60000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('fulfillment.batch_picklist', [
                'ids' => [$order1->id, $order2->id]
            ]));

        $response->assertStatus(200);
        $response->assertSee('SKU-BATCH-01');
        // Total aggregated picking quantity is 5 (2 + 3)
        $response->assertSee('5');
    }

    public function test_can_verify_packing_massal_and_deduct_stock(): void
    {
        $order1 = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-B1',
            'invoice_number' => 'INV-B1',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer 1',
            'order_date' => now(),
            'is_stock_deducted' => false,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'master_product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'price' => 20000,
            'quantity' => 2,
            'total_price' => 40000,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('fulfillment.batch_verify'), [
                'ids' => [$order1->id]
            ]);

        $response->assertRedirect();
        
        $order1->refresh();
        $this->assertEquals('verified', $order1->packing_status);
        $this->assertTrue((bool)$order1->is_stock_deducted);

        // Local stock decremented by 2 (50 - 2 = 48)
        $this->product->refresh();
        $this->assertEquals(48, $this->product->stock);
    }
}
