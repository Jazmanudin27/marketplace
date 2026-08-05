<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected MasterProduct $masterProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Inventory Test Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@inventorytest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-INV-01',
            'name'       => 'Produk Inventory Test',
            'price'      => 10000,
            'cost_price' => 5000,
            'stock'      => 10,
            'is_active'  => true,
        ]);
    }

    public function test_inventory_index_redirects_to_mutations(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.index'));

        $response->assertRedirect(route('inventory.mutations.index'));
    }

    public function test_inventory_ledger_redirects_to_mutations(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.ledger', $this->masterProduct));

        $response->assertRedirect(route('inventory.mutations.index'));
    }
}
