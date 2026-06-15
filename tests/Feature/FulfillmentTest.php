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

class FulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $channel;
    protected $store;
    protected $masterProduct;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Fulfillment Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Warehouse Staff',
            'email' => 'warehouse@test.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse',
        ]);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->tenant->id);

        $this->channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channel->id,
            'store_name' => 'Shopee Warehouse Store',
            'marketplace_store_id' => 'SHOPEE_WH_STORE_ID',
            'status' => 'connected',
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-BARCODE-ABC',
            'name' => 'Barcoded Shoe Product',
            'price' => 120000,
            'stock' => 50,
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-MP-998877',
            'invoice_number' => 'INV-2026-999',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'packing_status' => 'pending',
            'buyer_name' => 'John Buyer',
            'courier' => 'J&T Express',
            'total_amount' => 120000,
            'order_date' => now(),
            'is_stock_deducted' => false,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'master_product_id' => $this->masterProduct->id,
            'sku' => 'SKU-BARCODE-ABC',
            'product_name' => 'Barcoded Shoe Product',
            'price' => 120000,
            'quantity' => 2,
            'total_price' => 240000,
        ]);
    }

    /**
     * Test getOrderDetails via AJAX returns correct structure and updates packing_status to packing
     */
    public function test_get_order_details_ajax_success(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('fulfillment.order_details', 'INV-2026-999'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'order' => [
                'id',
                'invoice_number',
                'buyer_name',
                'courier',
                'store_name',
                'channel_code',
                'channel_name',
                'packing_status',
                'items' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'image',
                        'quantity'
                    ]
                ]
            ]
        ]);

        $this->order->refresh();
        $this->assertEquals('packing', $this->order->packing_status);
    }

    /**
     * Test completePack updates packing status to verified, packed_at timestamp, and processes stock movement
     */
    public function test_complete_pack_success(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('fulfillment.complete_pack', $this->order->id), [
                'auto_ship' => '0'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shipped' => false
        ]);

        $this->order->refresh();
        $this->assertEquals('verified', $this->order->packing_status);
        $this->assertNotNull($this->order->packed_at);

        // Verify that stock is deducted from MasterProduct
        $this->masterProduct->refresh();
        // Initially 50, item qty is 2, expected new stock = 48
        $this->assertEquals(48, $this->masterProduct->stock);
        $this->assertTrue($this->order->is_stock_deducted);
    }

    /**
     * Test getValidAccessToken auto refreshes token when expired
     */
    public function test_get_valid_access_token_refreshes_token_when_expired(): void
    {
        // 1. Set the token to be expired
        $this->store->update([
            'access_token' => 'EXPIRED_ACCESS_TOKEN',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
            'token_expires_at' => now()->subHour(),
        ]);

        // 2. Mock ShopeeService refreshAccessToken call
        $mockShopee = $this->createMock(\App\Services\ShopeeService::class);
        $mockShopee->expects($this->once())
            ->method('refreshAccessToken')
            ->with('VALID_REFRESH_TOKEN', 0) // because store id SHOPEE_WH_STORE_ID cast to int is 0
            ->willReturn([
                'access_token' => 'NEW_FRESH_TOKEN',
                'refresh_token' => 'NEW_REFRESH_TOKEN',
                'expire_in' => 3600,
            ]);

        $this->app->instance(\App\Services\ShopeeService::class, $mockShopee);

        // 3. Call getValidAccessToken()
        $token = $this->store->getValidAccessToken();

        // 4. Assert token matches and database is updated
        $this->assertEquals('NEW_FRESH_TOKEN', $token);
        $this->store->refresh();
        $this->assertEquals('NEW_FRESH_TOKEN', $this->store->access_token);
        $this->assertEquals('NEW_REFRESH_TOKEN', $this->store->refresh_token);
        $this->assertTrue($this->store->token_expires_at->isFuture());
    }
}
