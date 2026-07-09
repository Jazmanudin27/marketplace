<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\MasterProduct;
use App\Models\ProductionOrder;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionHppReportTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Hpp Report Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Warehouse Admin',
            'email'     => 'warehouse@reporttest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin', // has view-warehouse-reports permission
        ]);
    }

    public function test_can_view_production_hpp_report_filters(): void
    {
        $this->actingAs($this->user);

        $product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-FINISHED-GOOD',
            'name' => 'Baju Batik Master',
            'price' => 150000,
            'stock' => 0,
        ]);

        $response = $this->get(route('reports.production_hpp'));
        $response->assertStatus(200);
        $response->assertSee('Filter Laporan HPP Produksi');
        $response->assertSee('Baju Batik Master');
    }

    public function test_can_print_production_hpp_report_with_calculated_data(): void
    {
        $this->actingAs($this->user);

        // 1. Create Finished Good Master Product
        $product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-FINISHED-GOOD',
            'name' => 'Baju Batik Master',
            'price' => 150000,
            'stock' => 10,
            'cost_price' => 50000,
        ]);

        // 2. Create Raw Material
        $material = InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'MAT-KAIN',
            'name' => 'Kain Katun',
            'type' => 'bahan',
            'cost_price' => 20000,
            'stock' => 100,
            'unit' => 'meter',
        ]);

        // 3. Create Completed Production Order
        $order = ProductionOrder::create([
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $product->id,
            'quantity' => 10,
            'status' => 'completed',
            'requested_by' => $this->user->id,
        ]);

        // 4. Create Material Consumption stock movement record
        StockMovement::create([
            'tenant_id' => $this->tenant->id,
            'inventory_item_id' => $material->id,
            'quantity' => -15, // Consumed 15 meters
            'type' => 'out',
            'reference' => 'Konsumsi Produksi SPK #' . $order->id,
            'user_id' => $this->user->id,
            'balance_after' => 85,
        ]);

        // 5. Create Labor cost record
        $order->actualLabors()->create([
            'service_name' => 'QC & Jahit',
            'actual_cost' => 50000, // Total Rp 50.000 labor cost
        ]);

        // Access the print page
        $response = $this->get(route('reports.production_hpp.print', [
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+1 day')),
            'product_id' => $product->id
        ]));

        $response->assertStatus(200);

        // Expected calculations:
        // Raw Material Cost: 15 * 20.000 = Rp 300.000
        // Labor Cost: Rp 50.000
        // Total cost: Rp 350.000
        // HPP per Unit: 350.000 / 10 = Rp 35.000
        $response->assertSee('LAPORAN HITUNGAN HPP PRODUKSI (SPK SELESAI)');
        $response->assertSee('Baju Batik Master');
        $response->assertSee('300.000'); // material cost
        $response->assertSee('50.000');  // labor cost
        $response->assertSee('350.000'); // total cost
        $response->assertSee('35.000');  // HPP per Unit
    }
}
