<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSaleCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_sales_create_view_renders_successfully()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.local',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(route('offline_sales.create'));

        $response->assertStatus(200);
        $response->assertSee('Transaksi Penjualan Baru');
        $response->assertSee('const method = $(\'#payment-method-select\').val();', false);
    }
}
