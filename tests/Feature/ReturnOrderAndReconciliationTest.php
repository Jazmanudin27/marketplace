<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReturnOrderAndReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name'   => 'Notification & Reconciliation Test Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Finance Admin',
            'email'     => 'finance@recontest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
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
            'store_name' => 'Test Store',
            'marketplace_store_id' => 'TEST_STORE_ID',
            'status' => 'connected',
        ]);
    }

    public function test_return_order_triggers_wa_notification_when_restocked(): void
    {
        Http::fake([
            'wa.aspartech.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-12345',
            'invoice_number' => 'INV-12345',
            'order_status' => Order::STATUS_COMPLETED,
            'buyer_name' => 'Buyer A',
            'is_dropship' => true,
            'dropshipper_name' => 'Dropshipper A',
            'dropshipper_phone' => '081234567890',
            'order_date' => now(),
        ]);

        $returnOrder = ReturnOrder::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'return_sn' => 'RET-999',
            'reason' => 'Defective',
            'status' => 'pending',
            'is_restocked' => false,
            'inspection_status' => 'GOOD',
            'inspection_notes' => 'Barang masih bagus',
        ]);

        $returnOrder->update(['is_restocked' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'wa.aspartech.com/api/send-message') &&
                str_contains($request['to'], '081234567890') &&
                str_contains($request['message'], 'RET-999') &&
                str_contains($request['message'], 'Layak Jual');
        });
    }

    public function test_reconciliation_index_with_status_filter(): void
    {
        $order1 = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-01',
            'invoice_number' => 'INV-01',
            'order_status' => Order::STATUS_COMPLETED,
            'buyer_name' => 'Buyer 1',
            'order_date' => now(),
            'total_amount' => 100000,
            'net_amount' => 90000,
            'recon_status' => 'pending',
        ]);

        $order2 = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-02',
            'invoice_number' => 'INV-02',
            'order_status' => Order::STATUS_COMPLETED,
            'buyer_name' => 'Buyer 2',
            'order_date' => now(),
            'total_amount' => 100000,
            'net_amount' => 90000,
            'recon_status' => 'resolved',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('finance.reconciliation', ['recon_status' => 'resolved']));

        $response->assertStatus(200);
        $response->assertSee('INV-02');
        $response->assertDontSee('INV-01');
    }

    public function test_reconciliation_update_status_and_notes(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-01',
            'invoice_number' => 'INV-01',
            'order_status' => Order::STATUS_COMPLETED,
            'buyer_name' => 'Buyer 1',
            'order_date' => now(),
            'total_amount' => 100000,
            'net_amount' => 90000,
            'recon_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('orders.reconcile.update', $order), [
                'recon_status' => 'resolved',
                'recon_notes' => 'Discrepancy resolved by CS refund',
            ]);

        $response->assertRedirect();
        
        $order->refresh();
        $this->assertEquals('resolved', $order->recon_status);
        $this->assertEquals('Discrepancy resolved by CS refund', $order->recon_notes);
    }

    public function test_pull_returns_from_tiktok_job_executes_successfully(): void
    {
        // 1. Create a TikTok store
        $tiktokChannel = Channel::create([
            'name' => 'TikTok Shop',
            'code' => 'tiktok',
        ]);
        
        $tiktokStore = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $tiktokChannel->id,
            'store_name' => 'TikTok Store Test',
            'marketplace_store_id' => 'TIKTOK_STORE_TEST_ID',
            'shop_cipher' => 'CIPHER123',
            'access_token' => 'TT-ACCESS-123',
            'refresh_token' => 'TT-REFRESH-123',
            'token_expires_at' => now()->addHours(24),
            'status' => 'connected',
        ]);

        // 2. Create the original order
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $tiktokStore->id,
            'order_marketplace_id' => 'TT-ORD-888',
            'invoice_number' => 'INV-TT-888',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer TikTok',
            'order_date' => now(),
        ]);
        
        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'TikTok Product',
            'quantity' => 1,
            'price' => 75000,
            'total_price' => 75000,
        ]);

        // 3. Mock the TikTok returns search API
        Http::fake([
            'open-api.tiktokglobalshop.com/return_refund/202309/returns/search*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'return_orders' => [
                        [
                            'return_id' => 'TT-RET-999',
                            'order_id' => 'TT-ORD-888',
                            'return_reason_text' => 'Buyer changed mind',
                            'return_status' => 'REQUESTED',
                            'return_tracking_number' => 'TT-TRACK-999',
                            'return_provider_name' => 'J&T Express',
                            'seller_next_action_response' => [
                                [
                                    'action' => 'SELLER_RESPOND_RECEIVE_PACKAGE',
                                    'deadline' => 1782980538
                                ]
                            ],
                            'refund_amount' => [
                                'refund_total' => 75000,
                            ],
                            'return_line_items' => [
                                [
                                    'quantity' => 1,
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        // 4. Dispatch the job
        \App\Jobs\PullReturnsFromTiktok::dispatchSync($tiktokStore);

        // 5. Verify return order created and original order status changed
        $this->assertDatabaseHas('return_orders', [
            'tenant_id' => $this->tenant->id,
            'store_id' => $tiktokStore->id,
            'return_sn' => 'TT-RET-999',
            'return_tracking_number' => 'TT-TRACK-999',
            'shipping_provider' => 'J&T Express',
            'reason' => 'Buyer changed mind',
            'status' => 'REQUESTED',
            'sla_deadline' => \Carbon\Carbon::createFromTimestamp(1782980538)->toDateTimeString(),
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_RETURN, $order->order_status);
    }

    public function test_item_level_qc_saves_details_and_checked_by_and_records_stock_movements(): void
    {
        // 1. Create a Return Order
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-QC-TEST',
            'invoice_number' => 'INV-QC-TEST',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer QC',
            'order_date' => now(),
        ]);

        $masterProduct1 = \App\Models\MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Master Product 1',
            'sku' => 'SKU-01',
            'stock' => 10,
            'price' => 50000,
        ]);

        $mpProduct1 = \App\Models\MarketplaceProduct::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'master_product_id' => $masterProduct1->id,
            'name' => 'Marketplace Product 1',
            'sku' => 'SKU-01',
            'marketplace_product_id' => 'MP-PROD-01',
            'marketplace_variant_id' => 'MP-VAR-01',
        ]);

        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'marketplace_product_id' => $mpProduct1->id,
            'product_name' => 'Marketplace Product 1',
            'quantity' => 2,
            'price' => 50000,
            'total_price' => 100000,
        ]);

        $returnOrder = ReturnOrder::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'return_sn' => 'RET-QC-TEST-01',
            'status' => 'REQUESTED',
            'is_restocked' => false,
        ]);

        $returnOrderItem = \App\Models\ReturnOrderItem::create([
            'return_order_id' => $returnOrder->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 2,
            'inspection_status' => 'PENDING',
        ]);

        // 2. Perform QC (Post to restock route)
        $response = $this->actingAs($this->user)
            ->post(route('returns.restock', $returnOrder), [
                'items' => [
                    $returnOrderItem->id => [
                        'inspection_status' => 'GOOD',
                        'inspection_notes' => 'Barang mulus layak jual',
                    ]
                ]
            ]);

        $response->assertRedirect();

        // 3. Verify ReturnOrderItem, ReturnOrder, and Stock Movement are updated correctly
        $returnOrderItem->refresh();
        $this->assertEquals('GOOD', $returnOrderItem->inspection_status);
        $this->assertEquals('Barang mulus layak jual', $returnOrderItem->inspection_notes);

        $returnOrder->refresh();
        $this->assertTrue($returnOrder->is_restocked);
        $this->assertEquals('GOOD', $returnOrder->inspection_status);
        $this->assertEquals($this->user->id, $returnOrder->checked_by);

        // Verify stock is increased by 2
        $masterProduct1->refresh();
        $this->assertEquals(12, $masterProduct1->stock);

        $this->assertDatabaseHas('stock_movements', [
            'master_product_id' => $masterProduct1->id,
            'quantity' => 2,
            'type' => 'in',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_item_level_qc_with_photo_upload(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-PHOTO-TEST',
            'invoice_number' => 'INV-PHOTO-TEST',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer Photo',
            'order_date' => now(),
        ]);

        $masterProduct1 = \App\Models\MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Master Product Photo',
            'sku' => 'SKU-PHOTO',
            'stock' => 10,
            'price' => 50000,
        ]);

        $mpProduct1 = \App\Models\MarketplaceProduct::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'master_product_id' => $masterProduct1->id,
            'name' => 'Marketplace Product Photo',
            'sku' => 'SKU-PHOTO',
            'marketplace_product_id' => 'MP-PHOTO-01',
            'marketplace_variant_id' => 'MP-PHOTO-VAR',
        ]);

        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'marketplace_product_id' => $mpProduct1->id,
            'product_name' => 'Marketplace Product Photo',
            'quantity' => 1,
            'price' => 50000,
            'total_price' => 50000,
        ]);

        $returnOrder = ReturnOrder::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'return_sn' => 'RET-PHOTO-TEST',
            'status' => 'REQUESTED',
            'is_restocked' => false,
        ]);

        $returnOrderItem = \App\Models\ReturnOrderItem::create([
            'return_order_id' => $returnOrder->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'inspection_status' => 'PENDING',
        ]);

        \Illuminate\Support\Facades\Storage::fake('public');
        $fakePhoto = \Illuminate\Http\UploadedFile::fake()->image('defect.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('returns.restock', $returnOrder), [
                'items' => [
                    $returnOrderItem->id => [
                        'inspection_status' => 'DEFECTIVE',
                        'inspection_notes' => 'Pecah di jalan',
                        'photo' => $fakePhoto,
                    ]
                ]
            ]);

        $response->assertRedirect();

        $returnOrderItem->refresh();
        $this->assertEquals('DEFECTIVE', $returnOrderItem->inspection_status);
        $this->assertEquals('Pecah di jalan', $returnOrderItem->inspection_notes);
        $this->assertNotNull($returnOrderItem->inspection_photo);
        $this->assertStringContainsString('uploads/returns/', $returnOrderItem->inspection_photo);

        // Cleanup local created files
        if (file_exists(public_path($returnOrderItem->inspection_photo))) {
            unlink(public_path($returnOrderItem->inspection_photo));
        }
    }

    public function test_replacement_order_creation_reduces_stock(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-REPL-TEST',
            'invoice_number' => 'INV-REPL-TEST',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Buyer Replacement',
            'buyer_phone' => '0812345678',
            'shipping_address' => 'Jakarta Barat',
            'courier' => 'J&T',
            'order_date' => now(),
        ]);

        $masterProduct1 = \App\Models\MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Master Product Repl',
            'sku' => 'SKU-REPL',
            'stock' => 10,
            'price' => 50000,
        ]);

        $mpProduct1 = \App\Models\MarketplaceProduct::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'master_product_id' => $masterProduct1->id,
            'name' => 'Marketplace Product Repl',
            'sku' => 'SKU-REPL',
            'marketplace_product_id' => 'MP-REPL-01',
            'marketplace_variant_id' => 'MP-REPL-VAR',
        ]);

        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'marketplace_product_id' => $mpProduct1->id,
            'product_name' => 'Marketplace Product Repl',
            'quantity' => 1,
            'price' => 50000,
            'total_price' => 50000,
        ]);

        $returnOrder = ReturnOrder::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'return_sn' => 'RET-REPL-TEST',
            'status' => 'REQUESTED',
            'is_restocked' => true,
        ]);

        \App\Models\ReturnOrderItem::create([
            'return_order_id' => $returnOrder->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'inspection_status' => 'GOOD',
        ]);

        // Create replacement order
        $response = $this->actingAs($this->user)
            ->post(route('returns.replacement', $returnOrder));

        $response->assertRedirect();

        $returnOrder->refresh();
        $this->assertNotNull($returnOrder->replacement_order_id);

        $replacementOrder = $returnOrder->replacementOrder;
        $this->assertEquals(Order::STATUS_READY_TO_SHIP, $replacementOrder->order_status);
        $this->assertEquals(0, $replacementOrder->total_amount);
        $this->assertEquals('Buyer Replacement', $replacementOrder->buyer_name);

        // Verify stock is reduced by 1 for the replacement
        $masterProduct1->refresh();
        $this->assertEquals(9, $masterProduct1->stock);

        $this->assertDatabaseHas('stock_movements', [
            'master_product_id' => $masterProduct1->id,
            'quantity' => -1,
            'type' => 'out',
            'user_id' => $this->user->id,
        ]);
    }
}
