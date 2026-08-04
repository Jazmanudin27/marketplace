<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Order;
use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use App\Models\StockMovement;
use App\Jobs\PullOrdersFromTiktok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiktokStockDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Channel $channel;
    protected MasterProduct $masterProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'TikTok Enterprise',
            'status' => 'active',
        ]);

        $this->channel = Channel::create([
            'name' => 'TikTok',
            'code' => 'tiktok',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channel->id,
            'store_name' => 'TikTok Store Test',
            'status' => 'connected',
            'marketplace_store_id' => '998877',
            'shop_cipher' => 'cipher123',
            'access_token' => 'token123',
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kemeja Flanel Merah',
            'sku' => 'KMJ-FLN-MRH',
            'stock' => 100,
            'cost_price' => 50000,
            'price' => 85000,
        ]);

        MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'master_product_id' => $this->masterProduct->id,
            'marketplace_product_id' => '1735843179446044440',
            'marketplace_variant_id' => 'VAR-112233',
            'marketplace_sku' => 'KMJ-FLN-MRH',
            'name' => 'Kemeja Flanel Merah - M',
            'price' => 85000,
            'stock' => 100,
        ]);
    }

    public function test_tiktok_order_deducts_stock_and_records_movement()
    {
        $mockTiktokOrder = [
            'order_id' => 'TT-ORDER-999',
            'order_status' => '111', // Numeric AWAITING_SHIPMENT in TikTok v2
            'create_time' => time(),
            'recipient_address' => [
                'name' => 'Budi Santoso',
                'phone' => '08123456789',
                'full_address' => 'Jl. Merdeka No. 10 Jakarta',
            ],
            'payment_info' => [
                'total_amount' => 85000,
                'shipping_fee' => 10000,
                'seller_discount' => 0,
            ],
            'item_list' => [
                [
                    'product_id' => '1735843179446044440',
                    'sku_id' => 'VAR-112233',
                    'seller_sku' => 'KMJ-FLN-MRH',
                    'product_name' => 'Kemeja Flanel Merah - M',
                    'quantity' => 2,
                    'sku_sale_price' => 85000,
                ]
            ]
        ];

        $job = new PullOrdersFromTiktok($this->store, time() - 3600, time());
        
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('processOrder');
        $method->setAccessible(true);
        $method->invoke($job, $mockTiktokOrder);

        $order = Order::where('order_marketplace_id', 'TT-ORDER-999')->first();
        $this->assertNotNull($order);
        $this->assertEquals('READY_TO_SHIP', $order->order_status);
        $this->assertTrue((bool)$order->is_stock_deducted);

        // Verify StockMovement record created
        $movement = StockMovement::where('master_product_id', $this->masterProduct->id)
            ->where('reference', 'like', '%TT-ORDER-999%')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-2, $movement->quantity);
        $this->assertEquals('out', $movement->type);

        // Verify MasterProduct stock deducted from 100 to 98
        $this->masterProduct->refresh();
        $this->assertEquals(98, $this->masterProduct->stock);
    }
}
