<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\MasterProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterProductReportTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Report Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Report Admin',
            'email'     => 'admin@reporttest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);
    }

    public function test_can_view_master_product_report_page(): void
    {
        $this->actingAs($this->user);

        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'TEST-SINGLE-01',
            'name' => 'Produk Single Test',
            'price' => 50000,
            'cost_price' => 30000,
            'stock' => 10,
            'is_bundle' => false,
        ]);

        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SET-TEST-01',
            'name' => 'Produk Set Test',
            'price' => 100000,
            'cost_price' => 60000,
            'stock' => 5,
            'is_bundle' => true,
        ]);

        $response = $this->get(route('reports.master_product'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.master_product');
        $response->assertSee('TEST-SINGLE-01');
        $response->assertSee('SET-TEST-01');
        $response->assertSee('Set / Bundle');
        $response->assertSee('Single');
    }

    public function test_can_view_print_master_product_report(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('reports.master_product.print'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.print_master_product');
        $response->assertSee('LAPORAN MASTER PRODUK');
    }

    public function test_can_export_master_product_report_csv(): void
    {
        $this->actingAs($this->user);

        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'EXPORT-SKU-01',
            'name' => 'Produk Export Test',
            'price' => 25000,
            'cost_price' => 15000,
            'stock' => 20,
            'is_bundle' => false,
        ]);

        $response = $this->get(route('reports.master_product.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('EXPORT-SKU-01', $response->streamedContent());
    }
}
