<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\MasterProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterProductAutoBundleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Bundle Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@bundletest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        // Assign permission if needed or bypass via actingAs
    }

    public function test_can_auto_bundle_products_based_on_sku(): void
    {
        $this->actingAs($this->user);

        // 1. Create single products
        $baju = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BP-SMP-LPJ-L',
            'name' => 'Baju Putih SMP L/P L',
            'price' => 50000,
            'cost_price' => 30000,
            'stock' => 10,
            'is_bundle' => false,
        ]);

        $rok = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'ROK-BIRU-SMP-XXL',
            'name' => 'Rok Biru SMP XXL',
            'price' => 60000,
            'cost_price' => 40000,
            'stock' => 5,
            'is_bundle' => false,
        ]);

        // 2. Create Set product
        $setProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SET-BP-SMP-LPJ-L-ROK-BIRU-SMP-XXL',
            'name' => 'Set Seragam SMP L / XXL',
            'price' => 110000,
            'stock' => 0,
            'is_bundle' => false,
        ]);

        // 3. Trigger autoBundle
        $response = $this->post(route('products.auto_bundle'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh setProduct
        $setProduct = $setProduct->fresh();

        $this->assertTrue((bool)$setProduct->is_bundle);
        $this->assertCount(2, $setProduct->components);

        // Dynamic stock should be min(10, 5) = 5
        $this->assertEquals(5, $setProduct->stock);

        // Cost price should be 30000 + 40000 = 70000
        $this->assertEquals(70000.0, (float)$setProduct->cost_price);
    }
}
