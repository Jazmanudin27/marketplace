<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ProductImportLog;
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

    public function test_import_allows_up_to_three_imports_and_blocks_fourth_within_week(): void
    {
        // 1st import -> should succeed
        $file1 = UploadedFile::fake()->createWithContent('import1.csv', "sku;nama_produk;harga_jual\nSKU-FIRST-01;Produk Pertama;10000");
        $res1 = $this->post(route('v2.produk.import'), ['file' => $file1]);
        $res1->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertNotNull($this->tenant->last_product_import_at);
        $this->assertEquals(1, ProductImportLog::where('tenant_id', $this->tenant->id)->count());

        // 2nd import -> should succeed
        $file2 = UploadedFile::fake()->createWithContent('import2.csv', "sku;nama_produk;harga_jual\nSKU-SECOND-02;Produk Kedua;20000");
        $res2 = $this->post(route('v2.produk.import'), ['file' => $file2]);
        $res2->assertSessionHas('success');
        $this->assertEquals(2, ProductImportLog::where('tenant_id', $this->tenant->id)->count());

        // 3rd import -> should succeed
        $file3 = UploadedFile::fake()->createWithContent('import3.csv', "sku;nama_produk;harga_jual\nSKU-THIRD-03;Produk Ketiga;30000");
        $res3 = $this->post(route('v2.produk.import'), ['file' => $file3]);
        $res3->assertSessionHas('success');
        $this->assertEquals(3, ProductImportLog::where('tenant_id', $this->tenant->id)->count());

        // 4th import immediately after -> should be blocked!
        $file4 = UploadedFile::fake()->createWithContent('import4.csv', "sku;nama_produk;harga_jual\nSKU-FOURTH-04;Produk Keempat;40000");
        $res4 = $this->post(route('v2.produk.import'), ['file' => $file4]);
        $res4->assertSessionHas('error');

        // Product from blocked 4th import should not exist
        $fourthProduct = MasterProduct::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SKU-FOURTH-04')
            ->first();
        $this->assertNull($fourthProduct);

        // Fast forward oldest import log by 8 days in the past (so only 2 imports remain in 7-day window)
        $oldestLog = ProductImportLog::where('tenant_id', $this->tenant->id)->orderBy('created_at', 'asc')->first();
        $oldestLog->update(['created_at' => now()->subDays(8)]);

        $file5 = UploadedFile::fake()->createWithContent('import5.csv', "sku;nama_produk;harga_jual\nSKU-FOURTH-04;Produk Keempat;40000");
        $res5 = $this->post(route('v2.produk.import'), ['file' => $file5]);
        $res5->assertSessionHas('success');

        $fourthProduct = MasterProduct::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SKU-FOURTH-04')
            ->first();
        $this->assertNotNull($fourthProduct);
    }

    public function test_index_reflects_import_lock_status(): void
    {
        $resBefore = $this->get(route('v2.produk.index'));
        $resBefore->assertOk();
        $resBefore->assertViewHas('canImport', true);
        $resBefore->assertViewHas('remainingQuota', 3);

        // Insert 3 import logs within 2 days
        for ($i = 1; $i <= 3; $i++) {
            ProductImportLog::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'filename' => "test_{$i}.csv",
                'created_at' => now()->subDays(1),
            ]);
        }

        $resAfter = $this->get(route('v2.produk.index'));
        $resAfter->assertOk();
        $resAfter->assertViewHas('canImport', false);
        $resAfter->assertViewHas('remainingQuota', 0);
        $resAfter->assertSee('Batas Frekuensi Import Sedang Aktif');
        $resAfter->assertSee('Terkunci (Maks 3x Seminggu)');
    }

    public function test_super_admin_bypasses_weekly_import_limit(): void
    {
        // 3 logs already exist
        for ($i = 1; $i <= 3; $i++) {
            ProductImportLog::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'filename' => "test_{$i}.csv",
                'created_at' => now()->subDays(1),
            ]);
        }

        $superAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
        ]);

        $this->actingAs($superAdmin);

        $resIndex = $this->get(route('v2.produk.index'));
        $resIndex->assertOk();
        $resIndex->assertViewHas('canImport', true);

        $file = UploadedFile::fake()->createWithContent('super_admin.csv', "sku;nama_produk;harga_jual\nSKU-SUPER-01;Super Product;50000");
        $resImport = $this->post(route('v2.produk.import'), ['file' => $file]);
        $resImport->assertSessionHas('success');
    }

    public function test_custom_weekly_import_limit_via_config_and_tenant_attribute(): void
    {
        // 1. Set global config to 7
        config(['app.product_import_weekly_limit' => 7]);

        $this->assertEquals(7, $this->tenant->getMaxWeeklyProductImports());
        $status = $this->tenant->getProductImportQuotaStatus();
        $this->assertEquals(7, $status['max_limit']);
        $this->assertEquals(7, $status['remaining_quota']);

        // 2. Tenant override to 10
        $this->tenant->update(['weekly_product_import_limit' => 10]);
        $this->assertEquals(10, $this->tenant->fresh()->getMaxWeeklyProductImports());
        $statusOverride = $this->tenant->fresh()->getProductImportQuotaStatus();
        $this->assertEquals(10, $statusOverride['max_limit']);
        $this->assertEquals(10, $statusOverride['remaining_quota']);

        // 3. View index with custom limit
        $res = $this->get(route('v2.produk.index'));
        $res->assertOk();
        $res->assertViewHas('maxImportLimit', 10);
        $res->assertViewHas('remainingQuota', 10);
        $res->assertSee('10/10 Tersedia');
    }
}




