<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Tenant;
use App\Services\OrderTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderThermalPrintTest extends TestCase
{
    use RefreshDatabase;

    private function setupBaseData()
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
            'access_token' => '',
            'status' => 'active',
        ]);

        return [$tenant, $user, $store];
    }

    public function test_single_order_print_renders_thermal_resi_view_successfully()
    {
        [$tenant, $user, $store] = $this->setupBaseData();

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
        $response->assertSee('JY1195984105');
        $response->assertSee('585062237481240338');
    }

    public function test_single_order_print_correctly_identifies_cod_and_non_cod()
    {
        [$tenant, $user, $store] = $this->setupBaseData();

        $codOrder = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORDER-COD-101',
            'order_date' => now(),
            'tracking_number' => 'RESI-COD-101',
            'courier' => 'J&T Express',
            'buyer_name' => 'Budi COD',
            'total_amount' => 150000,
            'payment_method' => 'Cash on Delivery',
            'order_status' => 'READY_TO_SHIP',
        ]);

        $nonCodOrder = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORDER-NONCOD-102',
            'order_date' => now(),
            'tracking_number' => 'RESI-NONCOD-102',
            'courier' => 'J&T Express',
            'buyer_name' => 'Siti Non COD',
            'total_amount' => 200000,
            'payment_method' => 'Transfer Bank',
            'order_status' => 'READY_TO_SHIP',
        ]);

        $this->assertTrue($codOrder->is_cod);
        $this->assertFalse($nonCodOrder->is_cod);

        $responseCod = $this->actingAs($user)->get(route('orders.print', $codOrder->id));
        $responseCod->assertStatus(200);
        $responseCod->assertSee('COD');
        $responseCod->assertDontSee('NON-COD');

        $responseNonCod = $this->actingAs($user)->get(route('orders.print', $nonCodOrder->id));
        $responseNonCod->assertStatus(200);
        $responseNonCod->assertSee('NON-COD');
    }

    public function test_mass_print_auto_fetches_tracking_number_and_succeeds()
    {
        [$tenant, $user, $store] = $this->setupBaseData();

        $order1 = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORDER-001',
            'order_date' => now(),
            'tracking_number' => 'EXISTING-RESI-1',
            'courier' => 'J&T',
            'buyer_name' => 'Budi',
            'total_amount' => 50000,
            'order_status' => 'READY_TO_SHIP',
        ]);

        $order2 = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORDER-002',
            'order_date' => now(),
            'tracking_number' => null, // Resi belum ada
            'courier' => 'SiCepat',
            'buyer_name' => 'Siti',
            'total_amount' => 75000,
            'order_status' => 'READY_TO_SHIP',
        ]);

        // Mock OrderTrackingService agar berhasil menarik resi untuk order2
        $this->mock(OrderTrackingService::class, function ($mock) {
            $mock->shouldReceive('fetchTrackingNumber')
                ->once()
                ->andReturn('AUTO-PULLED-RESI-999');
        });

        $response = $this->actingAs($user)->post(route('orders.mass_print'), [
            'order_ids' => [$order1->id, $order2->id],
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('orders.mass_print');
        $response->assertSee('EXISTING-RESI-1');
        $response->assertSee('AUTO-PULLED-RESI-999');

        // Pastikan order ditandai is_printed
        $this->assertTrue($order1->fresh()->is_printed);
        $this->assertTrue($order2->fresh()->is_printed);
        $this->assertEquals('AUTO-PULLED-RESI-999', $order2->fresh()->tracking_number);
    }

    public function test_mass_print_refuses_when_tracking_number_cannot_be_fetched()
    {
        [$tenant, $user, $store] = $this->setupBaseData();

        $orderWithoutTracking = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORDER-NO-RESI',
            'order_date' => now(),
            'tracking_number' => null,
            'courier' => 'J&T',
            'buyer_name' => 'Doni',
            'total_amount' => 100000,
            'order_status' => 'READY_TO_SHIP',
        ]);

        // Mock OrderTrackingService mengembalikan null (resi belum diterbitkan oleh marketplace)
        $this->mock(OrderTrackingService::class, function ($mock) {
            $mock->shouldReceive('fetchTrackingNumber')
                ->once()
                ->andReturn(null);
        });

        $response = $this->actingAs($user)->post(route('orders.mass_print'), [
            'order_ids' => [$orderWithoutTracking->id],
        ]);

        // Tolak cetak dengan view print_error dan status 422
        $response->assertStatus(422);
        $response->assertViewIs('orders.print_error');
        $response->assertSee('Cetak Resi Ditolak');
        $response->assertSee('ORDER-NO-RESI');

        // Pastikan order TIDAK ditandai is_printed
        $this->assertFalse((bool) $orderWithoutTracking->fresh()->is_printed);
    }

    public function test_single_print_auto_fetches_tracking_number_and_refuses_if_unavailable()
    {
        [$tenant, $user, $store] = $this->setupBaseData();

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => 'ORDER-SINGLE-EMPTY',
            'order_date' => now(),
            'tracking_number' => '', // kosong
            'courier' => 'JNE',
            'buyer_name' => 'Rina',
            'total_amount' => 80000,
            'order_status' => 'READY_TO_SHIP',
        ]);

        // Mock OrderTrackingService gagal menarik resi
        $this->mock(OrderTrackingService::class, function ($mock) {
            $mock->shouldReceive('fetchTrackingNumber')
                ->once()
                ->andReturn(null);
        });

        $response = $this->actingAs($user)->get(route('orders.print', $order->id));

        $response->assertStatus(422);
        $response->assertViewIs('orders.print_error');
        $this->assertFalse((bool) $order->fresh()->is_printed);
    }

    public function test_order_tracking_service_auto_arranges_shipping_and_returns_tracking_number()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'shopee.local',
        ]);

        $channel = Channel::create([
            'code' => 'shopee',
            'name' => 'Shopee',
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'channel_id' => $channel->id,
            'store_name' => 'Shopee Official',
            'marketplace_store_id' => '99887766',
            'access_token' => 'dummy_shopee_token',
            'status' => 'active',
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'order_marketplace_id' => '240910SHOPEE01',
            'order_date' => now(),
            'tracking_number' => null,
            'courier' => 'SPX Standard',
            'buyer_name' => 'Ani',
            'total_amount' => 120000,
            'order_status' => 'READY_TO_SHIP',
        ]);

        $shopeeMock = $this->mock(\App\Services\ShopeeService::class, function ($mock) {
            // Cek awal gagal/kosong
            $mock->shouldReceive('getTrackingNumber')
                ->once()
                ->andThrow(new \RuntimeException('Shopee API Error [logistics.tracking_number_not_found]'));

            // Auto ship_order dipanggil
            $mock->shouldReceive('shipOrder')
                ->once()
                ->andReturn([]);

            // Cek pasca ship_order berhasil
            $mock->shouldReceive('getTrackingNumber')
                ->once()
                ->andReturn(['tracking_number' => 'SPXID9988776655']);
        });

        $trackingService = app(OrderTrackingService::class);
        $trackingNo = $trackingService->fetchTrackingNumber($order);

        $this->assertEquals('SPXID9988776655', $trackingNo);
        $this->assertEquals('SPXID9988776655', $order->fresh()->tracking_number);
        $this->assertEquals(Order::STATUS_SHIPPED, $order->fresh()->order_status);
    }
}
