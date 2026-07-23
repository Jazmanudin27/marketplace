<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected MasterProduct $product1;
    protected MasterProduct $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Calculator Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Calculator',
            'email' => 'calc@tenant.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->product1 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-CALC-01',
            'name' => 'Produk Kalkulator 1',
            'price' => 100000,
            'cost_price' => 70000,
            'stock' => 50,
            'is_active' => true,
        ]);

        $this->product2 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-CALC-02',
            'name' => 'Produk Kalkulator 2',
            'price' => 200000,
            'cost_price' => 140000,
            'stock' => 30,
            'is_active' => true,
        ]);
    }

    public function test_can_access_bulk_price_calculator_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('products.bulk_price_calculator'));
        $response->assertStatus(200);
        $response->assertSee('Kalkulator &amp; Setting Harga Masal', false);
        $response->assertSee('SKU-CALC-01');
        $response->assertSee('SKU-CALC-02');
    }

    public function test_can_update_bulk_prices_and_dispatch_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->post(route('products.bulk_price_calculator.update'), [
            'sync_to_marketplace' => 1,
            'products' => [
                [
                    'id' => $this->product1->id,
                    'new_price' => 125000,
                ],
                [
                    'id' => $this->product2->id,
                    'new_price' => 250000,
                ],
            ],
        ]);

        $response->assertRedirect(route('products.bulk_price_calculator'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_products', [
            'id' => $this->product1->id,
            'price' => 125000,
        ]);

        $this->assertDatabaseHas('master_products', [
            'id' => $this->product2->id,
            'price' => 250000,
        ]);

        Queue::assertPushed(\App\Jobs\PushPriceToMarketplaces::class, 2);
    }
}
