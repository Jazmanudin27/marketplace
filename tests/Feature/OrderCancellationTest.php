<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Order;
use App\Models\MasterProduct;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Store $store;
    protected Channel $channel;
    protected MasterProduct $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'My Enterprise',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Supervisor',
            'email' => 'supervisor@comp.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channel->id,
            'store_name' => 'Main Outlet',
            'status' => 'connected',
            'marketplace_store_id' => '123456',
        ]);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Buyer J',
        ]);

        $this->product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pakaian Casual',
            'sku' => 'CLOTH-001',
            'stock' => 10,
            'cost_price' => 50000,
            'price' => 100000,
        ]);
    }

    public function test_can_manually_cancel_order_and_record_details(): void
    {
        // 1. Create a ready_to_ship order with stock deducted
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'order_marketplace_id' => 'SHP-99238',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer J',
            'total_amount' => 100000,
            'order_date' => now(),
            'is_stock_deducted' => true,
            'is_stock_returned' => false,
        ]);

        $this->product->recordStockMovement(2, 'out', 'Pesanan Masuk: SHP-99238');

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'master_product_id' => $this->product->id,
            'product_name' => 'Pakaian Casual',
            'quantity' => 2,
            'price' => 50000,
            'total_price' => 100000,
        ]);

        // Stock was 10, we deducted 2 in our scenario so let's adjust physical stock
        $this->product->update(['stock' => 8]);

        // 2. Perform cancellation via route
        $response = $this->actingAs($this->user)
            ->post(route('orders.cancel', $order), [
                'cancel_reason' => 'Stok habis / rusak',
            ]);

        $response->assertRedirect();
        
        $order->refresh();
        $this->product->refresh();

        // 3. Verify status, cancel reason and who cancelled it
        $this->assertEquals(Order::STATUS_CANCELLED, $order->order_status);
        $this->assertEquals('Stok habis / rusak', $order->cancel_reason);
        $this->assertStringContainsString('Supervisor', $order->cancelled_by);

        // 4. Verify stock is returned to 10 (since 2 were returned)
        $this->assertTrue($order->is_stock_returned);
        $this->assertEquals(10, $this->product->stock);
    }

    public function test_export_orders_contains_cancellation_details(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'order_marketplace_id' => 'SHP-EXPORT-1',
            'order_status' => Order::STATUS_CANCELLED,
            'buyer_name' => 'Buyer Export',
            'total_amount' => 150000,
            'order_date' => now(),
            'cancel_reason' => 'Salah pesan barang',
            'cancelled_by' => 'Supervisor',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.export', ['status' => 'CANCELLED']));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Salah pesan barang', $content);
        $this->assertStringContainsString('Supervisor', $content);
        $this->assertStringContainsString('SHP-EXPORT-1', $content);
    }
}
