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
            'reason' => 'Buyer changed mind',
            'status' => 'REQUESTED',
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_RETURN, $order->order_status);
    }
}
