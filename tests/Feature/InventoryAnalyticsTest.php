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

class InventoryAnalyticsTest extends TestCase
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
            'name'   => 'Analytics Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@analytictest.com',
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
            'store_name'          => 'Toko Analytics Test',
            'marketplace_store_id'=> 'SHOPEE_ANALYTICS_001',
            'status'              => 'connected',
        ]);
    }

    private function makeOrder(int $daysAgo, string $status = 'COMPLETED'): Order
    {
        return Order::create([
            'tenant_id'             => $this->tenant->id,
            'store_id'              => $this->store->id,
            'order_marketplace_id'  => 'ORD-ANALYTICS-' . uniqid(),
            'invoice_number'        => 'INV-ANALYTICS-' . uniqid(),
            'order_status'          => $status,
            'buyer_name'            => 'Test Buyer',
            'total_amount'          => 200000,
            'net_amount'            => 180000,
            'order_date'            => now()->subDays($daysAgo),
        ]);
    }

    public function test_inventory_analytics_page_accessible_for_admin(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('reports.analytics'));

        $response->assertStatus(200);
        $response->assertSee('Analitik Inventori');
    }

    public function test_deadstock_detection_rules(): void
    {
        // 1. Product A: created 100 days ago, never sold, stock = 10 -> Should be deadstock (since 100 > 90 days)
        $productDead = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-DEAD-01',
            'name'       => 'Sandal Deadstock Terbengkalai',
            'price'      => 50000,
            'cost_price' => 20000,
            'stock'      => 10,
            'is_active'  => true,
        ]);
        \DB::table('master_products')->where('id', $productDead->id)->update(['created_at' => now()->subDays(100)]);

        // 2. Product B: created 100 days ago, sold 10 days ago, stock = 5 -> NOT deadstock (since last sale 10 < 90 days ago)
        $productActive = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-ACTIVE-02',
            'name'       => 'Sepatu Lari Sangat Laris',
            'price'      => 150000,
            'cost_price' => 80000,
            'stock'      => 5,
            'is_active'  => true,
        ]);
        \DB::table('master_products')->where('id', $productActive->id)->update(['created_at' => now()->subDays(100)]);

        $order = $this->makeOrder(10); // Sold 10 days ago
        OrderItem::create([
            'order_id'          => $order->id,
            'master_product_id' => $productActive->id,
            'sku'               => $productActive->sku,
            'product_name'      => $productActive->name,
            'quantity'          => 2,
            'price'             => 150000,
            'total_price'       => 300000,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('reports.analytics', [
            'deadstock_days' => 90
        ]));

        $response->assertStatus(200);
        
        // Product A should be listed in the deadstock table
        $response->assertSee($productDead->sku);
        $response->assertSee($productDead->name);

        // Product B should NOT be categorized as deadstock, but instead shown in the planner
        // Check if Sandal Deadstock is marked as deadstock
        $this->assertTrue(
            collect($response->viewData('deadstockProducts'))->contains('sku', 'SKU-DEAD-01')
        );
        $this->assertFalse(
            collect($response->viewData('deadstockProducts'))->contains('sku', 'SKU-ACTIVE-02')
        );
    }

    public function test_sales_forecasting_run_rate_and_reorder_recommendation(): void
    {
        // Product C: stock = 10, sold 30 units in the last 30 days.
        // Run rate = 30 / 30 = 1.0 unit/day.
        // Days of cover = 10 / 1.0 = 10 days.
        // Recommended Qty for 30 days coverage = (1.0 * 30) - 10 = 20 units.
        $product = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-FORECAST-03',
            'name'       => 'Kaos Kaki Polos Premium',
            'price'      => 30000,
            'cost_price' => 10000,
            'stock'      => 10,
            'is_active'  => true,
        ]);

        // Place 3 orders, each of qty 10 in the last 30 days (total 30)
        for ($i = 0; $i < 3; $i++) {
            $order = $this->makeOrder(5 * ($i + 1)); // 5, 10, 15 days ago
            OrderItem::create([
                'order_id'          => $order->id,
                'master_product_id' => $product->id,
                'sku'               => $product->sku,
                'product_name'      => $product->name,
                'quantity'          => 10,
                'price'             => 30000,
                'total_price'       => 300000,
            ]);
        }

        $this->actingAs($this->user);

        // Request with target_coverage = 30
        $response = $this->get(route('reports.analytics', [
            'target_coverage' => 30
        ]));

        $response->assertStatus(200);

        // Retrieve the forecast product data from response viewData
        $forecastList = collect($response->viewData('forecastProducts'));
        $dataC = $forecastList->firstWhere('sku', 'SKU-FORECAST-03');

        $this->assertNotNull($dataC);
        $this->assertEquals(30, $dataC['sold_30']);
        $this->assertEquals(1.0, $dataC['run_rate']);
        $this->assertEquals(10.0, $dataC['days_of_cover']);
        $this->assertEquals(20, $dataC['recommended_qty']); // (1.0 * 30) - 10 = 20
    }
}
