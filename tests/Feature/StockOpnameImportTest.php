<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StockOpnameImportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_download_template()
    {
        $response = $this->get(route('stock_opnames.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('SKU,Jumlah', $response->getContent());
    }

    public function test_can_import_stock_opname_csv()
    {
        $product1 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-TEST-001',
            'name' => 'Produk Test 1',
            'stock' => 10,
            'price' => 50000,
        ]);

        $product2 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-TEST-002',
            'name' => 'Produk Test 2',
            'stock' => 25,
            'price' => 75000,
        ]);

        $csvContent = "SKU,Jumlah\nSKU-TEST-001,15\nSKU-TEST-002,25\nUNKNOWN-SKU,100\n";
        $file = UploadedFile::fake()->createWithContent('opname.csv', $csvContent);

        $response = $this->post(route('stock_opnames.import.store'), [
            'file' => $file,
            'opname_date' => date('Y-m-d'),
            'pic' => 'Admin Opname',
        ]);

        $response->assertRedirect(route('stock_opnames.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_errors');

        $this->assertEquals(15, $product1->fresh()->stock);
        $this->assertEquals(25, $product2->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $product1->id,
            'quantity' => 5,
            'type' => 'adj',
        ]);
    }
}
