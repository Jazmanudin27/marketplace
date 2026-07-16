<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\MasterProduct;
use App\Models\ProductRecipe;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRecipeBulkCopyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Recipe Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Production Admin',
            'email'     => 'prod@recipetest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);
    }

    public function test_can_bulk_copy_recipe_to_multiple_products(): void
    {
        $this->actingAs($this->user);

        // 1. Create Products
        $sourceProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SRC-001',
            'name' => 'Source Product',
            'price' => 10000,
        ]);

        $destProduct1 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'DEST-001',
            'name' => 'Dest Product 1',
            'price' => 12000,
        ]);

        $destProduct2 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'DEST-002',
            'name' => 'Dest Product 2',
            'price' => 15000,
        ]);

        // Create an inventory item for BOM
        $item = InventoryItem::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'INV-001',
            'name' => 'Bahan Baku A',
            'type' => 'bahan',
            'cost_price' => 1000,
            'stock' => 100,
            'unit' => 'gram',
        ]);

        // 2. Create Active Recipe for Source Product
        $recipe = ProductRecipe::create([
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $sourceProduct->id,
            'name' => 'Formula Utama',
            'batch_qty' => 5,
            'is_active' => true,
        ]);

        $recipe->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 10.5,
        ]);

        $recipe->labors()->create([
            'service_name' => 'Jasa Jahit',
            'qty' => 1,
            'unit_cost' => 5000,
            'default_cost' => 5000,
        ]);

        // 3. Post to bulk-copy route from the index page
        $response = $this->from(route('product_recipes.index'))->post(route('product_recipes.bulk_copy'), [
            'source_product_id' => $sourceProduct->id,
            'destination_product_ids' => [
                $destProduct1->id,
                $destProduct2->id
            ]
        ]);

        // Assert redirect
        $response->assertRedirect(route('product_recipes.index'));
        $response->assertSessionHas('success');

        // 4. Verify recipes created for target products
        $recipe1 = ProductRecipe::with(['items', 'labors'])
            ->where('master_product_id', $destProduct1->id)
            ->where('is_active', true)
            ->first();

        $recipe2 = ProductRecipe::with(['items', 'labors'])
            ->where('master_product_id', $destProduct2->id)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($recipe1);
        $this->assertNotNull($recipe2);

        $this->assertEquals(5, $recipe1->batch_qty);
        $this->assertEquals(5, $recipe2->batch_qty);

        // Check BOM items
        $this->assertCount(1, $recipe1->items);
        $this->assertEquals($item->id, $recipe1->items->first()->inventory_item_id);
        $this->assertEquals(10.5, $recipe1->items->first()->quantity);

        // Check Labors
        $this->assertCount(1, $recipe1->labors);
        $this->assertEquals('Jasa Jahit', $recipe1->labors->first()->service_name);
        $this->assertEquals(5000, $recipe1->labors->first()->unit_cost);
    }

    public function test_can_perform_negative_search(): void
    {
        $this->actingAs($this->user);

        // Create Batik product
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BTK-001',
            'name' => 'Baju Batik Pria',
            'price' => 100000,
        ]);

        // Create Polos product
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'PLS-001',
            'name' => 'Kaos Polos Hitam',
            'price' => 50000,
        ]);

        // Search normally for batik
        $response = $this->get(route('product_recipes.index', ['search' => 'Batik']));
        $response->assertStatus(200);
        $response->assertSee('Baju Batik Pria');
        $response->assertDontSee('Kaos Polos Hitam');

        // Search with != batik
        $responseNeg = $this->get(route('product_recipes.index', ['search' => '!= Batik']));
        $responseNeg->assertStatus(200);
        $responseNeg->assertDontSee('Baju Batik Pria');
        $responseNeg->assertSee('Kaos Polos Hitam');

        // Search with !batik
        $responseNeg2 = $this->get(route('product_recipes.index', ['search' => '!Batik']));
        $responseNeg2->assertStatus(200);
        $responseNeg2->assertDontSee('Baju Batik Pria');
        $responseNeg2->assertSee('Kaos Polos Hitam');
    }
}
