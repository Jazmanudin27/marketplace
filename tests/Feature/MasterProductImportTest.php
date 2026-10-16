<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Import Test Tenant',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Import',
            'email' => 'admin_import_v2@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($this->user);
    }

    public function test_import_updates_existing_product_and_ignores_empty_fields(): void
    {
        $existing = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produk Awal',
            'sku' => 'SKU-EXIST-01',
            'price' => 50000,
            'cost_price' => 30000,
            'stock' => 15,
            'unit' => 'pcs',
            'is_preorder' => false,
        ]);

        // CSV only provides new price, other columns left blank
        $csvContent = implode("\n", [
            'sku;nama_produk;harga_jual;hpp_harga_beli;stok;satuan;is_preorder',
            'SKU-EXIST-01;;75000;;;;',
        ]);

        $file = UploadedFile::fake()->createWithContent('import_test.csv', $csvContent);

        $response = $this->post(route('v2.produk.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');

        $existing->refresh();
        // Price should be updated
        $this->assertEquals(75000, (float) $existing->price);
        // Untouched fields must retain original data
        $this->assertEquals('Produk Awal', $existing->name);
        $this->assertEquals(30000, (float) $existing->cost_price);
        $this->assertEquals(15, $existing->stock);
        $this->assertEquals('pcs', $existing->unit);
        $this->assertFalse($existing->is_preorder);
    }

    public function test_import_creates_new_product_when_sku_not_found_with_auto_po(): void
    {
        // CSV provides SKU that does not exist yet, name is empty, is_preorder is empty
        $csvContent = implode("\n", [
            'sku;nama_produk;harga_jual;hpp_harga_beli;stok;satuan;is_preorder',
            'SKU-NEW-01;;100000;60000;5;pcs;',
        ]);

        $file = UploadedFile::fake()->createWithContent('import_new.csv', $csvContent);

        $response = $this->post(route('v2.produk.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');

        $newProduct = MasterProduct::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SKU-NEW-01')
            ->first();

        $this->assertNotNull($newProduct);
        // Name defaults to SKU if empty
        $this->assertEquals('SKU-NEW-01', $newProduct->name);
        // New product automatically becomes PO
        $this->assertTrue($newProduct->is_preorder);
        $this->assertEquals(100000, (float) $newProduct->price);
        $this->assertEquals(60000, (float) $newProduct->cost_price);
        $this->assertEquals(5, $newProduct->stock);
        $this->assertEquals('pcs', $newProduct->unit);
    }

    public function test_import_creates_new_product_with_explicit_non_po(): void
    {
        $csvContent = implode("\n", [
            'sku;nama_produk;harga_jual;is_preorder',
            'SKU-READY-01;Produk Ready;85000;0',
        ]);

        $file = UploadedFile::fake()->createWithContent('import_ready.csv', $csvContent);

        $response = $this->post(route('v2.produk.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');

        $product = MasterProduct::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SKU-READY-01')
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals('Produk Ready', $product->name);
        // Explicitly set to 0 -> is_preorder is false
        $this->assertFalse($product->is_preorder);
    }
}
