<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Order;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_printed_order_is_categorized_in_processed_status()
    {
        $tenant = Tenant::create([
            'name' => 'My Enterprise',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@comp.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $channel = Channel::create(['name' => 'Shopee', 'code' => 'shopee']);
        $store = Store::create([
            'tenant_id' => $tenant->id,
            'channel_id' => $channel->id,
            'store_name' => 'Toko Test',
            'marketplace_store_id' => '123456',
        ]);

        // Unprinted, no tracking -> to_process
        $order1 = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORD-1001',
            'order_status' => 'READY_TO_SHIP',
            'order_date' => now(),
            'is_printed' => false,
            'tracking_number' => null,
            'total_amount' => 50000,
        ]);

        // Printed -> processed
        $order2 = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORD-1002',
            'order_status' => 'READY_TO_SHIP',
            'order_date' => now(),
            'is_printed' => true,
            'tracking_number' => null,
            'total_amount' => 75000,
        ]);

        // Test filter process_status = to_process
        $res1 = $this->actingAs($user)->get(route('orders.index', ['process_status' => 'to_process']));
        $res1->assertStatus(200);
        $res1->assertSee('ORD-1001');
        $res1->assertDontSee('ORD-1002');
        $res1->assertDontSee('Status Cetak');

        // Test filter process_status = processed
        $res2 = $this->actingAs($user)->get(route('orders.index', ['process_status' => 'processed']));
        $res2->assertStatus(200);
        $res2->assertSee('ORD-1002');
        $res2->assertDontSee('ORD-1001');
    }
}
