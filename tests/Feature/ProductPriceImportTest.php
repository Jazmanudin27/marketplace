<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductPriceImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Test',
            'email' => 'admin_import@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_download_price_template_with_semicolon_columns(): void
    {
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Baju Test Template',
            'sku' => 'SKU-TMP-01',
            'price' => 100000,
            'cost_price' => 50000,
            'est_kain' => 1.5,
            'est_biaya_produksi' => 20000,
            'is_bundle' => false,
            'stock' => 10,
        ]);

        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Paket Bundle Test',
            'sku' => 'SKU-BUNDLE-99',
            'price' => 250000,
            'cost_price' => 120000,
            'is_bundle' => true,
            'stock' => 5,
        ]);

        $response = $this->get(route('products.download_price_template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('sku;harga_jual;hpp;est_kain;est_biaya_produksi', $content);
        $this->assertStringContainsString('SKU-TMP-01', $content);
        $this->assertStringNotContainsString('SKU-BUNDLE-99', $content);
    }

    public function test_csv_import_updates_valid_values_and_ignores_zero_or_empty_with_semicolon(): void
    {
        $product1 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produk A',
            'sku' => 'SKU-001',
            'price' => 100000,
            'cost_price' => 50000,
            'est_kain' => 1.5,
            'est_biaya_produksi' => 20000,
            'stock' => 10,
        ]);

        $product2 = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produk B (Tetap)',
            'sku' => 'SKU-002',
            'price' => 150000,
            'cost_price' => 75000,
            'est_kain' => 2.0,
            'est_biaya_produksi' => 30000,
            'stock' => 5,
        ]);

        // CSV content with semicolon delimiter (standard for Excel in Windows/ID locale):
        // SKU-001: updates all values
        // SKU-002: provides 0 or empty values, so old values must remain unchanged
        $csvContent = implode("\n", [
            'sku;harga_jual;hpp;est_kain;est_biaya_produksi',
            'SKU-001;120000;60000;1.8;25000',
            'SKU-002;0;;0;0',
        ]);

        $file = UploadedFile::fake()->createWithContent('import_harga_test.csv', $csvContent);

        $response = $this->post(route('products.import_prices'), [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');

        // Check Product 1 updated
        $product1->refresh();
        $this->assertEquals(120000, (float) $product1->price);
        $this->assertEquals(60000, (float) $product1->cost_price);
        $this->assertEquals(1.8, (float) $product1->est_kain);
        $this->assertEquals(25000, (float) $product1->est_biaya_produksi);

        // Check Product 2 unchanged
        $product2->refresh();
        $this->assertEquals(150000, (float) $product2->price);
        $this->assertEquals(75000, (float) $product2->cost_price);
        $this->assertEquals(2.0, (float) $product2->est_kain);
        $this->assertEquals(30000, (float) $product2->est_biaya_produksi);
    }
}
