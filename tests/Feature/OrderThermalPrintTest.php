<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderThermalPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_order_print_renders_thermal_resi_view_successfully()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.local',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $channel = Channel::create([
            'code' => 'tiktok',
            'name' => 'TikTok Shop',
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'channel_id' => $channel->id,
            'store_name' => 'Nusantara Seragam',
            'marketplace_store_id' => '12345678',
            'status' => 'active',
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => '585062237481240338',
            'order_date' => now(),
            'tracking_number' => 'JY1195984105',
            'courier' => 'J&T Express',
            'buyer_name' => 'Ayu',
            'buyer_phone' => '082321358006',
            'shipping_address' => 'kp neglasari rt 02 rw11 desa haurwangi kecamatan haurwangi',
            'total_amount' => 150000,
            'order_status' => 'READY_TO_SHIP',
        ]);

        $response = $this->actingAs($user)->get(route('orders.print', $order->id));

        $response->assertStatus(200);
        $response->assertSee('J&T');
        $response->assertSee('JY1195984105');
        $response->assertSee('585062237481240338');
    }
}
