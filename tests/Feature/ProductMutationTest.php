<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Mutation Tenant']);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Warehouse Admin',
            'email'     => 'admin@warehouse.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);
    }

    public function test_can_view_mutation_history_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.mutations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.mutations.index');
    }

    public function test_can_view_create_mutation_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.mutations.create', ['type' => 'in']));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.mutations.create');
    }

    public function test_can_store_inbound_mutation(): void
    {
        $product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'INBOUND-001',
            'name'      => 'Produk Inbound Test',
            'price'     => 100000,
            'stock'     => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('inventory.mutations.store'), [
                'type'            => 'in',
                'date'            => '2026-07-31',
                'category_reason' => 'Hasil Produksi Selesai',
                'notes'           => 'SPK #001 Batch 1',
                'items'           => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => 15,
                    ],
                ],
            ]);

        $response->assertRedirect(route('inventory.mutations.index'));
        $response->assertSessionHas('success');

        // Verify stock updated from 10 to 25
        $this->assertDatabaseHas('master_products', [
            'id'    => $product->id,
            'stock' => 25,
        ]);

        // Verify stock movement ledger created
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id'         => $this->tenant->id,
            'master_product_id' => $product->id,
            'type'              => 'in',
            'quantity'          => 15,
            'balance_after'     => 25,
        ]);
    }

    public function test_can_store_outbound_mutation(): void
    {
        $product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku'       => 'OUTBOUND-001',
            'name'      => 'Produk Outbound Test',
            'price'     => 100000,
            'stock'     => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('inventory.mutations.store'), [
                'type'            => 'out',
                'date'            => '2026-07-31',
                'category_reason' => 'Barang Rusak / Cacat Jahitan',
                'notes'           => 'QC Reject',
                'items'           => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => 5,
                    ],
                ],
            ]);

        $response->assertRedirect(route('inventory.mutations.index'));
        $response->assertSessionHas('success');

        // Verify stock updated from 50 to 45
        $this->assertDatabaseHas('master_products', [
            'id'    => $product->id,
            'stock' => 45,
        ]);

        // Verify stock movement ledger created
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id'         => $this->tenant->id,
            'master_product_id' => $product->id,
            'type'              => 'out',
            'quantity'          => -5,
            'balance_after'     => 45,
        ]);
    }
}
