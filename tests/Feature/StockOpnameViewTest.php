<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\MasterProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_opname_massal_create_view_renders_clean_bootstrap5()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.local',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        MasterProduct::create([
            'tenant_id' => $tenant->id,
            'sku' => 'SERAGAM-SD-01',
            'name' => 'Baju Seragam SD',
            'stock' => 50,
            'price' => 75000,
        ]);

        $response = $this->actingAs($user)->get(route('stock_opnames.create'));

        $response->assertStatus(200);
        $response->assertSee('Form Stock Opname Massal');
        $response->assertSee('SERAGAM-SD-01');
        $response->assertSee('table table-bordered table-hover');
    }
}
