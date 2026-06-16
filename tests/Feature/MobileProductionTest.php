<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\ProductionOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileProductionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $gudangUser;
    protected User $produksiUser;
    protected MasterProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Mobile Test Tenant',
            'status' => 'active',
        ]);

        $this->gudangUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gudang User',
            'email' => 'gudang@mobiletest.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse',
        ]);

        $this->produksiUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produksi User',
            'email' => 'produksi@mobiletest.com',
            'password' => bcrypt('password'),
            'role' => 'produksi',
        ]);

        $this->product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-MOBILE-01',
            'name' => 'Produk Mobile',
            'price' => 100000,
            'cost_price' => 40000,
            'stock' => 10,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_gudang_can_adjust_stock_manually(): void
    {
        $this->actingAs($this->gudangUser);

        // Add 5 stock
        $response = $this->post(route('mobile.gudang.adjust_stock', $this->product->id), [
            'quantity' => 5,
            'type' => 'in',
            'reference' => 'Manual Add Mobile',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(15, $this->product->fresh()->stock);

        // Subtract 3 stock
        $this->post(route('mobile.gudang.adjust_stock', $this->product->id), [
            'quantity' => 3,
            'type' => 'out',
            'reference' => 'Manual Sub Mobile',
        ]);

        $this->assertEquals(12, $this->product->fresh()->stock);
    }

    public function test_gudang_can_request_production(): void
    {
        $this->actingAs($this->gudangUser);

        // Request production of 20 units
        $response = $this->post(route('mobile.gudang.request_production'), [
            'master_product_id' => $this->product->id,
            'quantity' => 20,
        ]);

        $response->assertSessionHas('success');

        // Assert production order is created in database
        $this->assertDatabaseHas('production_orders', [
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $this->product->id,
            'quantity' => 20,
            'status' => 'pending',
            'requested_by' => $this->gudangUser->id,
        ]);
    }

    public function test_produksi_can_start_and_complete_production_order(): void
    {
        // 1. Create a request from warehouse
        $productionOrder = ProductionOrder::create([
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $this->product->id,
            'quantity' => 50,
            'status' => 'pending',
            'requested_by' => $this->gudangUser->id,
        ]);

        $this->actingAs($this->produksiUser);

        // 2. Start production
        $responseStart = $this->post(route('mobile.produksi.start', $productionOrder));
        $responseStart->assertSessionHas('success');
        $this->assertEquals('producing', $productionOrder->fresh()->status);

        // Assert stock has NOT increased yet
        $this->assertEquals(10, $this->product->fresh()->stock);

        // 3. Complete production
        $responseComplete = $this->post(route('mobile.produksi.complete', $productionOrder));
        $responseComplete->assertSessionHas('success');
        $this->assertEquals('completed', $productionOrder->fresh()->status);

        // Assert stock HAS increased by 50 (from 10 to 60)
        $this->assertEquals(60, $this->product->fresh()->stock);
    }
}
