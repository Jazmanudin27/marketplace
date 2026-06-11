<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Channel $channel;
    protected Store $store;
    protected MasterProduct $masterProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Profit Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@profittest.com',
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
            'store_name'          => 'Toko Profit Test',
            'marketplace_store_id'=> 'SHOPEE_PROFIT_001',
            'status'              => 'connected',
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-PROFIT-01',
            'name'       => 'Produk Profit Test',
            'price'      => 150000,
            'cost_price' => 50000,
            'stock'      => 100,
            'is_active'  => true,
        ]);
    }

    private function makeOrder(float $netAmount = 200000): Order
    {
        return Order::create([
            'tenant_id'             => $this->tenant->id,
            'store_id'              => $this->store->id,
            'order_marketplace_id'  => 'ORD-PROFIT-' . uniqid(),
            'invoice_number'        => 'INV-PROFIT-' . uniqid(),
            'order_status'          => 'COMPLETED',
            'buyer_name'            => 'Test Buyer',
            'total_amount'          => 250000,
            'net_amount'            => $netAmount,
            'order_date'            => now(),
        ]);
    }

    public function test_hpp_total_accessor_uses_snapshot(): void
    {
        $order = $this->makeOrder(200000);

        OrderItem::create([
            'order_id'          => $order->id,
            'master_product_id' => $this->masterProduct->id,
            'sku'               => 'SKU-PROFIT-01',
            'product_name'      => 'Produk Profit Test',
            'quantity'          => 2,
            'price'             => 120000,
            'total_price'       => 240000,
            'cost_price'        => 50000,
            'hpp_subtotal'      => 100000,  // snapshot: 50000 × 2
        ]);

        $order->load('items.masterProduct');

        // Accessor harus menggunakan nilai hpp_subtotal (snapshot), bukan live cost_price
        $this->assertEquals(100000.0, $order->hpp_total);
    }

    public function test_net_profit_equals_net_amount_minus_hpp(): void
    {
        $order = $this->makeOrder(150000);

        OrderItem::create([
            'order_id'          => $order->id,
            'master_product_id' => $this->masterProduct->id,
            'sku'               => 'SKU-PROFIT-01',
            'product_name'      => 'Produk Profit Test',
            'quantity'          => 1,
            'price'             => 160000,
            'total_price'       => 160000,
            'cost_price'        => 40000,
            'hpp_subtotal'      => 40000,
        ]);

        $order->load('items.masterProduct');

        // Net profit = 150000 - 40000 = 110000
        $this->assertEquals(110000.0, $order->net_profit);
    }

    public function test_profit_margin_percentage(): void
    {
        $order = $this->makeOrder(200000);

        OrderItem::create([
            'order_id'          => $order->id,
            'master_product_id' => $this->masterProduct->id,
            'sku'               => 'SKU-PROFIT-01',
            'product_name'      => 'Produk Profit Test',
            'quantity'          => 2,
            'price'             => 120000,
            'total_price'       => 240000,
            'cost_price'        => 50000,
            'hpp_subtotal'      => 100000,
        ]);

        $order->load('items.masterProduct');

        // profit = 200000 - 100000 = 100000
        // margin = (100000 / 200000) * 100 = 50.0
        $this->assertEquals(50.0, $order->profit_margin);
    }

    public function test_profit_dashboard_accessible_for_admin(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('profit.index'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Profit');
    }

    public function test_profit_dashboard_shows_order_in_table(): void
    {
        $order = $this->makeOrder(100000);

        OrderItem::create([
            'order_id'          => $order->id,
            'master_product_id' => $this->masterProduct->id,
            'sku'               => 'SKU-PROFIT-01',
            'product_name'      => 'Produk Profit Test',
            'quantity'          => 1,
            'price'             => 120000,
            'total_price'       => 120000,
            'cost_price'        => 30000,
            'hpp_subtotal'      => 30000,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('profit.index', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to'   => now()->toDateString(),
            'status'    => 'COMPLETED',
        ]));

        $response->assertStatus(200);
        $response->assertSee($order->invoice_number ?? $order->order_marketplace_id);
    }
}
