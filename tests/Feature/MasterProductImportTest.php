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

    public function test_import_updates_last_product_import_at_and_blocks_second_import_within_week(): void
    {
        $csvContent1 = implode("\n", [
            'sku;nama_produk;harga_jual',
            'SKU-FIRST-01;Produk Pertama;10000',
        ]);
        $file1 = UploadedFile::fake()->createWithContent('import1.csv', $csvContent1);

        $res1 = $this->post(route('v2.produk.import'), ['file' => $file1]);
        $res1->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertNotNull($this->tenant->last_product_import_at);

        // Second import immediately after should be blocked
        $csvContent2 = implode("\n", [
            'sku;nama_produk;harga_jual',
            'SKU-SECOND-02;Produk Kedua;20000',
        ]);
        $file2 = UploadedFile::fake()->createWithContent('import2.csv', $csvContent2);

        $res2 = $this->post(route('v2.produk.import'), ['file' => $file2]);
        $res2->assertSessionHas('error');

        // Product from blocked import should not exist
        $secondProduct = MasterProduct::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SKU-SECOND-02')
            ->first();
        $this->assertNull($secondProduct);

        // Fast forward 8 days in past for last_product_import_at
        $this->tenant->update([
            'last_product_import_at' => now()->subDays(8),
        ]);

        $res3 = $this->post(route('v2.produk.import'), ['file' => $file2]);
        $res3->assertSessionHas('success');

        $secondProduct = MasterProduct::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SKU-SECOND-02')
            ->first();
        $this->assertNotNull($secondProduct);
    }

    public function test_index_reflects_import_lock_status(): void
    {
        $resBefore = $this->get(route('v2.produk.index'));
        $resBefore->assertOk();
        $resBefore->assertViewHas('canImport', true);

        // Set last import to 2 days ago
        $this->tenant->update([
            'last_product_import_at' => now()->subDays(2),
        ]);

        $resAfter = $this->get(route('v2.produk.index'));
        $resAfter->assertOk();
        $resAfter->assertViewHas('canImport', false);
        $resAfter->assertSee('Batas Frekuensi Import Sedang Aktif');
        $resAfter->assertSee('Terkunci (Maks 1x Seminggu)');
    }
}

